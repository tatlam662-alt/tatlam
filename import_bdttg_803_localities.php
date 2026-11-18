<?php
/** Import locality names from the Ministry's 2026 Decision 803 annexes. */
if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	exit("CLI only.\n");
}

require __DIR__ . '/upload/config.php';

$dataFile = $argv[1] ?? (__DIR__ . '/data/official_bdttg_803_localities_2026.json');
if (!is_file($dataFile)) {
	fwrite(STDERR, 'Locality data file not found: ' . $dataFile . PHP_EOL);
	exit(1);
}

$rows = json_decode((string)file_get_contents($dataFile), true);
if (!is_array($rows) || !$rows) {
	fwrite(STDERR, 'Locality data file is empty or invalid JSON.' . PHP_EOL);
	exit(1);
}

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($db->connect_errno) {
	fwrite(STDERR, 'Database connection failed: ' . $db->connect_error . PHP_EOL);
	exit(1);
}
$db->set_charset('utf8mb4');

$table = DB_PREFIX . 'vn_hamlet';
$wardTable = DB_PREFIX . 'vn_ward';
$provinceTable = DB_PREFIX . 'vn_province';
$sourceName = 'Bộ Dân tộc và Tôn giáo - Quyết định 803/QĐ-BDTTG (danh sách 2026-2030)';
$sourceUrl = 'https://bdttg.gov.vn/thong-bao/danh-sach-thon-xa-vung-dtts-mn-xa-khu-vuc-i-ii-iii-giai-doan-2026-2030.htm';

$columns = $db->query("SHOW COLUMNS FROM `{$table}`");
$columnNames = [];
while ($columns && ($column = $columns->fetch_assoc())) {
	$columnNames[$column['Field']] = true;
}
foreach (['source_name', 'source_url', 'is_verified', 'ward_code', 'category', 'name'] as $required) {
	if (!isset($columnNames[$required])) {
		fwrite(STDERR, 'Missing required column ' . $required . '; update the locality schema first.' . PHP_EOL);
		exit(1);
	}
}

$provinces = [];
$provinceQuery = $db->query("SELECT `code`, `name`, `full_name` FROM `{$provinceTable}`");
while ($provinceQuery && ($province = $provinceQuery->fetch_assoc())) {
	$provinces[$province['name']][] = $province;
}

$fold = static function (string $value): string {
	$value = mb_strtolower(trim($value), 'UTF-8');
	$value = str_replace(['’', '‘', 'ʻ', '`'], "'", $value);
	$value = preg_replace('/^(?:xã|phường|đặc khu|thị trấn)\s+/u', '', $value);
	$groups = [
		'a' => 'àáảãạăằắẳẵặâầấẩẫậ',
		'e' => 'èéẻẽẹêềếểễệ',
		'i' => 'ìíỉĩị',
		'o' => 'òóỏõọôồốổỗộơờớởỡợ',
		'u' => 'ùúủũụưừứửữự',
		'y' => 'ỳýỷỹỵ',
		'd' => 'đ'
	];
	$characters = [];
	foreach ($groups as $base => $letters) {
		foreach (preg_split('//u', $letters, -1, PREG_SPLIT_NO_EMPTY) as $letter) {
			$characters[$letter] = $base;
		}
	}
	$value = strtr($value, $characters);
	return preg_replace('/[^a-z0-9]+/', '', $value);
};

$exact = static function (string $value): string {
	$value = mb_strtolower(trim($value), 'UTF-8');
	$value = str_replace(['’', '‘', 'ʻ', '`'], "'", $value);
	return trim(preg_replace('/^(?:xã|phường|đặc khu|thị trấn)\s+/u', '', $value));
};

$wardMap = [];
$unresolved = [];
$wardRowsByProvince = [];
$resolvedRows = [];
foreach ($rows as $index => $row) {
	$provinceName = trim((string)($row['province_name'] ?? ''));
	$wardName = trim((string)($row['ward_name'] ?? ''));
	$name = trim((string)($row['name'] ?? ''));
	if ($provinceName === '' || $wardName === '' || $name === '' || mb_strlen($name, 'UTF-8') > 128 || preg_match('/^[xX\s]+$/u', $name)) {
		$unresolved[] = ['row' => $index + 1, 'province' => $provinceName, 'ward' => $wardName, 'name' => $name];
		continue;
	}
	if (!isset($wardMap[$provinceName . '|' . $wardName])) {
		if (count($provinces[$provinceName] ?? []) !== 1) {
			$unresolved[] = ['row' => $index + 1, 'province' => $provinceName, 'ward' => $wardName, 'name' => $name];
			continue;
		}
		$provinceCode = (string)$provinces[$provinceName][0]['code'];
		if (!isset($wardRowsByProvince[$provinceCode])) {
			$query = $db->query("SELECT `code`, `name`, `full_name` FROM `{$wardTable}` WHERE `province_code` = '" . $db->real_escape_string($provinceCode) . "'");
			$wardRowsByProvince[$provinceCode] = $query ? $query->fetch_all(MYSQLI_ASSOC) : [];
		}
		$matches = [];
		foreach ($wardRowsByProvince[$provinceCode] as $ward) {
			if ($exact((string)$ward['name']) === $exact($wardName) || $exact((string)$ward['full_name']) === $exact($wardName)) {
				$matches[(string)$ward['code']] = true;
			}
		}
		if (!$matches) {
			foreach ($wardRowsByProvince[$provinceCode] as $ward) {
				if ($fold((string)$ward['name']) === $fold($wardName) || $fold((string)$ward['full_name']) === $fold($wardName)) {
					$matches[(string)$ward['code']] = true;
				}
			}
		}
		if (count($matches) !== 1) {
			$unresolved[] = ['row' => $index + 1, 'province' => $provinceName, 'ward' => $wardName, 'matches' => count($matches)];
			continue;
		}
		$wardMap[$provinceName . '|' . $wardName] = (string)array_key_first($matches);
	}
	$resolvedRows[] = [$wardMap[$provinceName . '|' . $wardName], $name];
}

if ($unresolved) {
	fwrite(STDERR, 'Import cancelled: ' . count($unresolved) . ' invalid or unresolved rows; database was not changed.' . PHP_EOL);
	foreach (array_slice($unresolved, 0, 20) as $row) {
		fwrite(STDERR, json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL);
	}
	exit(1);
}

$insert = $db->prepare("INSERT INTO `{$table}` (`ward_code`, `category`, `name`, `status`, `is_verified`, `source_name`, `source_url`) VALUES (?, 'hamlet', ?, 1, 1, ?, ?) ON DUPLICATE KEY UPDATE `status` = 1, `is_verified` = 1, `source_name` = VALUES(`source_name`), `source_url` = VALUES(`source_url`)");
if (!$insert) {
	fwrite(STDERR, 'Could not prepare locality import: ' . $db->error . PHP_EOL);
	exit(1);
}

$db->begin_transaction();
try {
	$imported = 0;
	foreach ($resolvedRows as [$wardCode, $name]) {
		$insert->bind_param('ssss', $wardCode, $name, $sourceName, $sourceUrl);
		if (!$insert->execute()) {
			throw new RuntimeException($insert->error);
		}
		$imported++;
	}
	$db->commit();
} catch (Throwable $error) {
	$db->rollback();
	fwrite(STDERR, 'Import failed and was rolled back: ' . $error->getMessage() . PHP_EOL);
	exit(1);
}

$coverage = $db->query("SELECT COUNT(DISTINCT `ward_code`) AS `wards`, COUNT(*) AS `names` FROM `{$table}` WHERE `source_name` = '" . $db->real_escape_string($sourceName) . "'")->fetch_assoc();
echo 'Imported/updated records: ' . $imported . PHP_EOL;
echo 'Current wards covered by this source: ' . (int)$coverage['wards'] . PHP_EOL;
echo 'Unique names from this source: ' . (int)$coverage['names'] . PHP_EOL;
echo 'Source: ' . $sourceUrl . PHP_EOL;


