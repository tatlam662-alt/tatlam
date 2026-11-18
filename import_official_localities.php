<?php
/** Import a published, ward-scoped official locality list into OpenCart. */
if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	exit("CLI only.\n");
}

require __DIR__ . '/upload/config.php';

$dataFile = $argv[1] ?? (__DIR__ . '/data/official_thanhhoa_localities_2026.json');
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
$sourceName = 'Sở Nội vụ tỉnh Thanh Hóa - Công văn 5487/SNV-XDCQ&CTTN (2026)';
$sourceUrl = 'https://baothanhhoa.vn/danh-sach-ten-goi-thon-to-dan-pho-thuoc-cac-xa-phuong-tren-dia-ban-tinh-thanh-hoa-296782.htm';

$columns = $db->query("SHOW COLUMNS FROM `{$table}`");
$columnNames = [];
while ($columns && ($column = $columns->fetch_assoc())) {
	$columnNames[$column['Field']] = true;
}
foreach (['source_name', 'source_url', 'is_verified', 'ward_code', 'category', 'name'] as $required) {
	if (!isset($columnNames[$required])) {
		fwrite(STDERR, 'Missing required column ' . $required . '; run the Vietnam locality schema update first.' . PHP_EOL);
		exit(1);
	}
}

$province = $db->query("SELECT `code` FROM `{$provinceTable}` WHERE `name` = 'Tỉnh Thanh Hóa' OR `full_name` = 'Tỉnh Thanh Hóa' LIMIT 2");
if (!$province || $province->num_rows !== 1) {
	fwrite(STDERR, 'Could not identify exactly one current Thanh Hóa province record.' . PHP_EOL);
	exit(1);
}
$provinceCode = (string)$province->fetch_assoc()['code'];

$wardLookup = $db->prepare("SELECT `code` FROM `{$wardTable}` WHERE `province_code` = ? AND (`name` = ? OR `full_name` = ?) LIMIT 2");
$insert = $db->prepare("INSERT INTO `{$table}` (`ward_code`, `category`, `name`, `status`, `is_verified`, `source_name`, `source_url`) VALUES (?, 'hamlet', ?, 1, 1, ?, ?) ON DUPLICATE KEY UPDATE `status` = 1, `is_verified` = 1, `source_name` = VALUES(`source_name`), `source_url` = VALUES(`source_url`)");
if (!$wardLookup || !$insert) {
	fwrite(STDERR, 'Could not prepare locality import: ' . $db->error . PHP_EOL);
	exit(1);
}

$resolved = [];
$invalid = 0;
foreach ($rows as $row) {
	$wardName = trim((string)($row['ward_name'] ?? ''));
	$name = trim((string)($row['name'] ?? ''));
	if ($wardName === '' || $name === '' || mb_strlen($name, 'UTF-8') > 128) {
		$invalid++;
		continue;
	}
	$wardLookup->bind_param('sss', $provinceCode, $wardName, $wardName);
	$wardLookup->execute();
	$result = $wardLookup->get_result();
	if (!$result || $result->num_rows !== 1) {
		$resolved[] = ['ward_name' => $wardName, 'name' => $name, 'matches' => $result ? $result->num_rows : 0];
		continue;
	}
	$resolved[] = ['ward_code' => (string)$result->fetch_assoc()['code'], 'ward_name' => $wardName, 'name' => $name];
}

$unmatched = array_values(array_filter($resolved, static fn($row) => !isset($row['ward_code'])));
if ($invalid || $unmatched) {
	fwrite(STDERR, 'Import stopped before writing: ' . $invalid . ' invalid rows, ' . count($unmatched) . ' unresolved ward/name rows.' . PHP_EOL);
	foreach (array_slice($unmatched, 0, 20) as $row) {
		fwrite(STDERR, ($row['matches'] ?? 0) . ' match(es): ' . $row['ward_name'] . ' / ' . $row['name'] . PHP_EOL);
	}
	exit(1);
}

$db->begin_transaction();
try {
	$imported = 0;
	foreach ($resolved as $row) {
		$wardCode = $row['ward_code'];
		$name = $row['name'];
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
echo 'Imported/updated: ' . $imported . PHP_EOL;
echo 'Official wards covered: ' . (int)$coverage['wards'] . PHP_EOL;
echo 'Official suggestions from this source: ' . (int)$coverage['names'] . PHP_EOL;
echo 'Source: ' . $sourceUrl . PHP_EOL;
