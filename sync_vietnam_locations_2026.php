<?php
/** Prepares separate ward-scoped suggestion lists for hamlet and subhamlet fields. */
if (PHP_SAPI !== 'cli') {
	http_response_code(403);
	exit("CLI only.\n");
}

require __DIR__ . '/upload/config.php';

$db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, (int)DB_PORT);
if ($db->connect_errno) {
	fwrite(STDERR, 'Database connection failed: ' . $db->connect_error . PHP_EOL);
	exit(1);
}
$db->set_charset('utf8mb4');

$table = DB_PREFIX . 'vn_hamlet';
$sql = "CREATE TABLE IF NOT EXISTS `{$table}` (
	`hamlet_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
	`ward_code` VARCHAR(32) NOT NULL,
	`category` VARCHAR(24) NOT NULL DEFAULT 'hamlet',
	`name` VARCHAR(128) NOT NULL,
	`status` TINYINT(1) NOT NULL DEFAULT 1,
	`is_verified` TINYINT(1) NOT NULL DEFAULT 1,
	`source_name` VARCHAR(190) NULL DEFAULT NULL,
	`source_url` VARCHAR(500) NULL DEFAULT NULL,
	PRIMARY KEY (`hamlet_id`),
	UNIQUE KEY `uq_ward_hamlet_category` (`ward_code`, `category`, `name`),
	KEY `idx_ward_status_category_name` (`ward_code`, `status`, `category`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$db->query($sql)) {
	fwrite(STDERR, 'Could not create hamlet table: ' . $db->error . PHP_EOL);
	exit(1);
}

foreach ([
	'source_name' => "VARCHAR(190) NULL DEFAULT NULL",
	'source_url' => "VARCHAR(500) NULL DEFAULT NULL"
] as $column => $definition) {
	$columnResult = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '" . $db->real_escape_string($column) . "'");
	if ($columnResult && !$columnResult->num_rows && !$db->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}")) {
		fwrite(STDERR, 'Could not add hamlet source metadata: ' . $db->error . PHP_EOL);
		exit(1);
	}
}

// Upgrade the earlier non-destructive schema in place. Existing names remain
// assigned to the first address level; no records are deleted or fabricated.
$columns = $db->query("SHOW COLUMNS FROM `{$table}` LIKE 'category'");
if ($columns && !$columns->num_rows) {
	if (!$db->query("ALTER TABLE `{$table}` ADD COLUMN `category` VARCHAR(24) NOT NULL DEFAULT 'hamlet' AFTER `ward_code`")) {
		fwrite(STDERR, 'Could not add hamlet category: ' . $db->error . PHP_EOL);
		exit(1);
	}
	$db->query("ALTER TABLE `{$table}` DROP INDEX `uq_ward_hamlet`");
	if (!$db->query("ALTER TABLE `{$table}` ADD UNIQUE KEY `uq_ward_hamlet_category` (`ward_code`, `category`, `name`), ADD KEY `idx_ward_status_category_name` (`ward_code`, `status`, `category`, `name`)")) {
		fwrite(STDERR, 'Could not upgrade hamlet indexes: ' . $db->error . PHP_EOL);
		exit(1);
	}
}

$verifiedColumn = $db->query("SHOW COLUMNS FROM `{$table}` LIKE 'is_verified'");
if ($verifiedColumn && !$verifiedColumn->num_rows) {
	if (!$db->query("ALTER TABLE `{$table}` ADD COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 1 AFTER `status`")) {
		fwrite(STDERR, 'Could not add verification flag: ' . $db->error . PHP_EOL);
		exit(1);
	}
	// Preserve pre-existing suggestion rows as approved legacy data.
	if (!$db->query("UPDATE `{$table}` SET `is_verified` = 1 WHERE `status` = 1")) {
		fwrite(STDERR, 'Could not retain legacy suggestions: ' . $db->error . PHP_EOL);
		exit(1);
	}
}

$province = $db->query('SELECT COUNT(*) AS `total` FROM `' . DB_PREFIX . 'vn_province`');
$ward = $db->query('SELECT COUNT(*) AS `total` FROM `' . DB_PREFIX . 'vn_ward`');
if (!$province || !$ward) {
	fwrite(STDERR, 'The existing vn_province/vn_ward tables are missing. Import the official dataset first.' . PHP_EOL);
	exit(1);
}

echo 'Created/verified ' . $table . PHP_EOL;
echo 'Province records: ' . (int)$province->fetch_assoc()['total'] . PHP_EOL;
echo 'Ward records: ' . (int)$ward->fetch_assoc()['total'] . PHP_EOL;
echo 'Both address levels support separate ward-scoped autocomplete; typed values remain supported.' . PHP_EOL;
