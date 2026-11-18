<?php
declare(strict_types=1);

/**
 * Normalize UTF-8 language/template files and activate Vietnamese/English.
 * Run once from a terminal: php tools/fix_master_panda.php
 * This utility is deliberately CLI-only. It creates backups under .backups/.
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit("Not found\n");
}

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/upload/config.php';

if (!extension_loaded('mysqli') || !extension_loaded('mbstring')) {
	fwrite(STDERR, "Required PHP extensions: mysqli and mbstring.\n");
	exit(1);
}

$prefix = (string)DB_PREFIX;
if (!preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) {
	fwrite(STDERR, "Invalid DB_PREFIX.\n");
	exit(1);
}

$timestamp = date('Ymd-His');
$backupRoot = $projectRoot . '/.backups/fix-master-' . $timestamp;
$backupFiles = $backupRoot . '/files';
if (!mkdir($backupFiles, 0775, true) && !is_dir($backupFiles)) {
	fwrite(STDERR, "Cannot create backup directory.\n");
	exit(1);
}

$roots = [
	$projectRoot . '/upload/catalog/language/vi-vn',
	$projectRoot . '/upload/catalog/language/en-gb',
	$projectRoot . '/upload/catalog/view/template'
];
$changed = [];
$invalidUtf8 = [];
$replacementChars = [];

foreach ($roots as $root) {
	if (!is_dir($root)) {
		fwrite(STDERR, "Missing directory: {$root}\n");
		exit(1);
	}

	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
	foreach ($iterator as $fileInfo) {
		if (!$fileInfo->isFile() || !in_array(strtolower($fileInfo->getExtension()), ['php', 'twig'], true)) {
			continue;
		}

		$file = $fileInfo->getPathname();
		$contents = file_get_contents($file);
		if ($contents === false) {
			fwrite(STDERR, "Cannot read file: {$file}\n");
			exit(1);
		}

		$original = $contents;
		if (str_starts_with($contents, "\xEF\xBB\xBF")) {
			$contents = substr($contents, 3);
		}

		if (!mb_check_encoding($contents, 'UTF-8')) {
			$contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
			if (!mb_check_encoding($contents, 'UTF-8')) {
				$invalidUtf8[] = $file;
				continue;
			}
		}

		if (str_contains($contents, "\xEF\xBF\xBD")) {
			$replacementChars[] = $file;
		}

		if ($contents !== $original) {
			$relative = substr($file, strlen($projectRoot) + 1);
			$backup = $backupFiles . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
			if (!is_dir(dirname($backup))) {
				mkdir(dirname($backup), 0775, true);
			}
			if (!copy($file, $backup) || file_put_contents($file, $contents) === false) {
				fwrite(STDERR, "Failed to back up or normalize: {$file}\n");
				exit(1);
			}
			$changed[] = $relative;
		}
	}
}

// Fill only absent counterpart files; existing translations are never overwritten.
$viRoot = $projectRoot . '/upload/catalog/language/vi-vn';
$enRoot = $projectRoot . '/upload/catalog/language/en-gb';
foreach ([$viRoot, $enRoot] as $sourceRoot) {
	$targetRoot = $sourceRoot === $viRoot ? $enRoot : $viRoot;
	$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS));
	foreach ($iterator as $fileInfo) {
		if (!$fileInfo->isFile() || strtolower($fileInfo->getExtension()) !== 'php') {
			continue;
		}

		$relative = substr($fileInfo->getPathname(), strlen($sourceRoot) + 1);
		$target = $targetRoot . DIRECTORY_SEPARATOR . $relative;
		if (!is_file($target)) {
			if (!is_dir(dirname($target))) {
				mkdir(dirname($target), 0775, true);
			}
			if (!copy($fileInfo->getPathname(), $target)) {
				fwrite(STDERR, "Could not copy missing language file: {$target}\n");
				exit(1);
			}
			$changed[] = substr($target, strlen($projectRoot) + 1) . ' (copied fallback; translate content separately)';
		}
	}
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli((string)DB_HOSTNAME, (string)DB_USERNAME, (string)DB_PASSWORD, (string)DB_DATABASE, (int)DB_PORT);
$db->set_charset('utf8mb4');

$table = '`' . $prefix . 'language`';
$columns = [];
$columnResult = $db->query('SHOW COLUMNS FROM ' . $table);
while ($column = $columnResult->fetch_assoc()) {
	$columns[$column['Field']] = true;
}

$languages = [
	[
		'name' => 'Tiếng Việt',
		'code' => 'vi-vn',
		'locale' => 'vi_VN.UTF-8,vi_VN,vi-vn,vietnamese',
		'image' => 'vi-vn.png',
		'directory' => 'vi-vn',
		'sort_order' => 1,
		'status' => 1
	],
	[
		'name' => 'English',
		'code' => 'en-gb',
		'locale' => 'en-US,en_US,en-gb,english',
		'image' => 'en-gb.png',
		'directory' => 'en-gb',
		'sort_order' => 2,
		'status' => 1
	]
];

$beforeResult = $db->query('SELECT * FROM ' . $table);
$before = $beforeResult->fetch_all(MYSQLI_ASSOC);
file_put_contents($backupRoot . '/language-before.json', json_encode($before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
$settingTable = '`' . $prefix . 'setting`';
$settingBefore = $db->query("SELECT * FROM {$settingTable} WHERE `store_id` = '0' AND `code` = 'config' AND `key` = 'config_language_catalog'")->fetch_all(MYSQLI_ASSOC);
file_put_contents($backupRoot . '/config-language-before.json', json_encode($settingBefore, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$db->begin_transaction();
foreach ($languages as $language) {
	if (isset($columns['extension'])) {
		$language['extension'] = '';
	}
	$fieldNames = array_keys(array_intersect_key($language, $columns));
	$fieldSql = implode(', ', array_map(static fn(string $field): string => '`' . $field . '`', $fieldNames));
	$values = array_map(static function (string $field) use ($db, $language): string {
		$value = $language[$field];
		return is_int($value) ? (string)$value : "'" . $db->real_escape_string((string)$value) . "'";
	}, $fieldNames);
	$updates = [];
	foreach ($fieldNames as $field) {
		if ($field !== 'code') {
			$updates[] = '`' . $field . '` = VALUES(`' . $field . '`)';
		}
	}
	$sql = 'INSERT INTO ' . $table . ' (' . $fieldSql . ') VALUES (' . implode(', ', $values) . ') ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
	$db->query($sql);
}
$db->query("UPDATE {$table} SET `status` = '0' WHERE `code` NOT IN ('vi-vn', 'en-gb')");
$defaultLanguage = $db->query("SELECT `setting_id` FROM {$settingTable} WHERE `store_id` = '0' AND `code` = 'config' AND `key` = 'config_language_catalog' LIMIT 1")->fetch_assoc();
if ($defaultLanguage) {
	$db->query("UPDATE {$settingTable} SET `value` = 'vi-vn', `serialized` = '0' WHERE `setting_id` = '" . (int)$defaultLanguage['setting_id'] . "'");
} else {
	$db->query("INSERT INTO {$settingTable} SET `store_id` = '0', `code` = 'config', `key` = 'config_language_catalog', `value` = 'vi-vn', `serialized` = '0'");
}
$db->commit();

$db->close();

// The native language selector caches its enabled language list under this prefix.
if (defined('DIR_CACHE') && is_dir(DIR_CACHE)) {
	foreach (glob(rtrim(DIR_CACHE, '/\\') . DIRECTORY_SEPARATOR . 'cache.language.*') ?: [] as $cacheFile) {
		if (is_file($cacheFile)) {
			@unlink($cacheFile);
		}
	}
}

echo "Activated Vietnamese (vi-vn) and English (en-gb).\n";
echo 'Normalized files: ' . count($changed) . "\n";
echo "Backup: {$backupRoot}\n";
if ($replacementChars) {
	echo "Files containing Unicode replacement characters need manual text restoration:\n";
	foreach (array_unique($replacementChars) as $file) {
		echo ' - ' . substr($file, strlen($projectRoot) + 1) . "\n";
	}
}
if ($invalidUtf8) {
	echo "Files that could not be converted safely:\n";
	foreach ($invalidUtf8 as $file) {
		echo ' - ' . substr($file, strlen($projectRoot) + 1) . "\n";
	}
}
echo "Review copied fallback language files and translate them before publishing.\n";

