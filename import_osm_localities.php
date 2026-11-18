<?php
/** Import ward-matched OpenStreetMap locality suggestions into OpenCart. */
if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	exit("CLI only.\n");
}

require __DIR__ . '/upload/config.php';

$dataFile = __DIR__ . '/data/vietnam_osm_localities_2026-10-18.json';
if (!is_file($dataFile)) {
	fwrite(STDERR, 'OSM locality data file was not found: ' . $dataFile . PHP_EOL);
	exit(1);
}

$rows = json_decode((string)file_get_contents($dataFile), true);
if (!is_array($rows)) {
	fwrite(STDERR, 'OSM locality data file is not valid JSON.' . PHP_EOL);
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
$columnResult = $db->query("SHOW COLUMNS FROM `{$table}` LIKE 'source_name'");
if (!$columnResult || !$columnResult->num_rows) {
	fwrite(STDERR, 'Run sync_vietnam_locations_2026.php first to prepare the locality source columns.' . PHP_EOL);
	exit(1);
}

$insert = $db->prepare("INSERT IGNORE INTO `{$table}` (`ward_code`, `category`, `name`, `status`, `is_verified`, `source_name`, `source_url`) SELECT ?, ?, ?, 1, 0, ?, ? FROM `{$wardTable}` WHERE `code` = ? LIMIT 1");
if (!$insert) {
	fwrite(STDERR, 'Could not prepare OSM locality import: ' . $db->error . PHP_EOL);
	exit(1);
}

$sourceName = 'OpenStreetMap contributors (gợi ý bản đồ, chưa xác minh hành chính)';
$sourceUrl = 'https://www.openstreetmap.org/copyright';
$inserted = 0;
$skipped = 0;
$reclassified = 0;
$duplicatesDeactivated = 0;
$db->begin_transaction();

// The source feature tag is only an approximate level. Explicit Vietnamese
// locality names are clearer, so keep named Tổ dân phố/Khu phố/Thôn in the
// main field and named Xóm/Ấp/Khu dân cư/Cụm dân cư in the child field.
$reclassifications = [
	['from' => 'subhamlet', 'to' => 'hamlet', 'pattern' => '^(Tổ dân phố|TDP|Khu phố|KP|Thôn)([[:space:]]|$)'],
	['from' => 'hamlet', 'to' => 'subhamlet', 'pattern' => '^(Xóm|Ấp|Khu dân cư|Cụm dân cư)([[:space:]]|$)']
];

foreach ($reclassifications as $rule) {
	$pattern = $db->real_escape_string($rule['pattern']);
	$from = $db->real_escape_string($rule['from']);
	$to = $db->real_escape_string($rule['to']);

	$deactivateDuplicates = "UPDATE `{$table}` `candidate` INNER JOIN `{$table}` `existing` ON (`existing`.`ward_code` = `candidate`.`ward_code` AND `existing`.`name` = `candidate`.`name` AND `existing`.`category` = '{$to}') SET `candidate`.`status` = '0' WHERE `candidate`.`source_name` LIKE 'OpenStreetMap%' AND `candidate`.`is_verified` = '0' AND `candidate`.`status` = '1' AND `candidate`.`category` = '{$from}' AND `candidate`.`name` REGEXP '{$pattern}'";
	if (!$db->query($deactivateDuplicates)) {
		$db->rollback();
		fwrite(STDERR, 'Could not deactivate duplicate OSM localities: ' . $db->error . PHP_EOL);
		exit(1);
	}
	$duplicatesDeactivated += $db->affected_rows;

	$moveCategory = "UPDATE `{$table}` `candidate` LEFT JOIN `{$table}` `existing` ON (`existing`.`ward_code` = `candidate`.`ward_code` AND `existing`.`name` = `candidate`.`name` AND `existing`.`category` = '{$to}') SET `candidate`.`category` = '{$to}' WHERE `candidate`.`source_name` LIKE 'OpenStreetMap%' AND `candidate`.`is_verified` = '0' AND `candidate`.`status` = '1' AND `candidate`.`category` = '{$from}' AND `candidate`.`name` REGEXP '{$pattern}' AND `existing`.`hamlet_id` IS NULL";
	if (!$db->query($moveCategory)) {
		$db->rollback();
		fwrite(STDERR, 'Could not classify OSM localities: ' . $db->error . PHP_EOL);
		exit(1);
	}
	$reclassified += $db->affected_rows;
}

foreach ($rows as $row) {
	$wardCode = (string)($row['ward_code'] ?? '');
	$category = (string)($row['category'] ?? '');
	$name = trim((string)($row['name'] ?? ''));
	if (preg_match('/^(?:Tổ dân phố|TDP|Khu phố|KP|Thôn)(?:\s|$)/iu', $name)) {
		$category = 'hamlet';
	} elseif (preg_match('/^(?:Xóm|Ấp|Khu dân cư|Cụm dân cư)(?:\s|$)/iu', $name)) {
		$category = 'subhamlet';
	}
	if ($wardCode === '' || !preg_match('/^[a-zA-Z0-9_-]{1,32}$/', $wardCode) || !in_array($category, ['hamlet', 'subhamlet'], true) || $name === '' || mb_strlen($name, 'UTF-8') > 128) {
		$skipped++;
		continue;
	}

	$insert->bind_param('ssssss', $wardCode, $category, $name, $sourceName, $sourceUrl, $wardCode);
	if (!$insert->execute()) {
		$db->rollback();
		fwrite(STDERR, 'Import failed for ' . $name . ': ' . $insert->error . PHP_EOL);
		exit(1);
	}
	if ($insert->affected_rows === 1) $inserted++;
	else $skipped++;
}

$db->commit();
$count = $db->query("SELECT COUNT(*) AS `total` FROM `{$table}` WHERE `source_name` LIKE 'OpenStreetMap%' AND `is_verified` = '0' AND `status` = '1'");
echo 'Inserted: ' . $inserted . PHP_EOL;
echo 'Skipped (existing/invalid): ' . $skipped . PHP_EOL;
echo 'Reclassified by explicit locality type: ' . $reclassified . PHP_EOL;
echo 'Duplicate suggestions deactivated: ' . $duplicatesDeactivated . PHP_EOL;
echo 'OSM suggestions now available: ' . (int)$count->fetch_assoc()['total'] . PHP_EOL;
echo 'Source data: Geofabrik Vietnam OSM extract, 2026-10-18; see data/OPENSTREETMAP-LICENSE.md' . PHP_EOL;
