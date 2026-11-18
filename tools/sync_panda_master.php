<?php
declare(strict_types=1);

/**
 * PanDaShop catalog synchronization utility for OpenCart 4.
 *
 * CLI only. Safe preview is the default:
 *   php tools/sync_panda_master.php
 * Apply brand/image/category changes:
 *   php tools/sync_panda_master.php --apply
 * Also delete products whose brands cannot be identified:
 *   php tools/sync_panda_master.php --apply --delete-unmatched
 *
 * A JSON backup is written under .backups/ before any changes are applied.
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit("Not found\n");
}

$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/upload/config.php';

const PANDA_BRANDS = [
	'ASUS' => ['asus', 'rog', 'tuf'],
	'MSI' => ['msi', 'ventus', 'gaming x slim'],
	'Gigabyte' => ['gigabyte', 'aorus'],
	'Intel' => ['intel', 'core i3', 'core i5', 'core i7', 'core i9'],
	'AMD' => ['amd', 'ryzen'],
	'Corsair' => ['corsair', 'vengeance', 'rm1000e', 'hs80'],
	'Samsung' => ['samsung', 'odyssey', '990 pro'],
	'Dell' => ['dell', 'ultrasharp'],
	'LG' => ['lg', 'ultragear'],
	'NZXT' => ['nzxt'],
	'Kingston' => ['kingston', 'fury'],
	'Western Digital' => ['western digital', 'wd black', 'wd blue'],
	'Logitech' => ['logitech', 'lightspeed', 'g pro x superlight'],
	'Razer' => ['razer', 'deathadder', 'blackshark', 'viper v'],
	'Akko' => ['akko'],
	'Keychron' => ['keychron'],
	'Wooting' => ['wooting'],
	'MonsGeek' => ['monsgeek'],
	'SteelSeries' => ['steelseries', 'arctis nova'],
	'HyperX' => ['hyperx'],
	'Pulsar' => ['pulsar'],
	'Artisan' => ['artisan'],
	'DJI' => ['dji', 'mavic', 'osmo', 'avata', 'phantom', 'air 3s', 'air 3', 'mini 4 pro', 'mini 3 pro'],
	'Autel Robotics' => ['autel'],
	'BetaFPV' => ['betafpv', 'cet us', 'cetus'],
	'Skydio' => ['skydio'],
	'Sony' => ['sony', 'inzone', 'alpha'],
	'Canon' => ['canon'],
	'GoPro' => ['gopro', 'hero camera'],
	'Insta360' => ['insta360'],
	'RØDE' => ['rode', 'røde', 'wireless pro'],
	'Shure' => ['shure', 'mv7'],
	'JBL' => ['jbl'],
	'Marshall' => ['marshall'],
	'Elgato' => ['elgato'],
	'Audio-Technica' => ['audio-technica', 'audio technica'],
	'Zhiyun' => ['zhiyun'],
	'FeiyuTech' => ['feiyutech', 'feiyu tech'],
	'Anker' => ['anker'],
	'Ugreen' => ['ugreen'],
	'Baseus' => ['baseus'],
	'SmallRig' => ['smallrig'],
	'Tilta' => ['tilta'],
	'SanDisk' => ['sandisk'],
	'Freewell' => ['freewell'],
	'PGYTECH' => ['pgytech'],
];

function pandaTable(string $prefix, string $table): string {
	return '`' . $prefix . $table . '`';
}

function pandaSlug(string $value): string {
	$value = trim($value);
	$ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
	$value = strtolower($ascii !== false ? $ascii : $value);
	$value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
	return trim($value, '-') ?: 'product';
}

function pandaBrand(array $product, array $rules): ?string {
	$haystack = mb_strtolower(implode(' ', array_merge([$product['model']], $product['names'])), 'UTF-8');
	$haystack = str_replace(['ø', 'Ø'], ['o', 'o'], $haystack);

	foreach ($rules as $brand => $aliases) {
		foreach ($aliases as $alias) {
			$alias = mb_strtolower($alias, 'UTF-8');
			if (preg_match('/^[a-z0-9 ]+$/i', $alias)) {
				$pattern = '/(?<![\pL\pN])' . preg_quote($alias, '/') . '(?![\pL\pN])/iu';
			} else {
				$pattern = '/' . preg_quote($alias, '/') . '/iu';
			}
			if (preg_match($pattern, $haystack)) {
				return $brand;
			}
		}
	}

	return null;
}

function pandaImageInfo(string $file): ?array {
	if (!is_file($file) || !is_readable($file)) {
		return null;
	}
	$info = @getimagesize($file);
	return is_array($info) ? $info : null;
}

function pandaPublicHost(string $host): bool {
	if ($host === '' || in_array(strtolower($host), ['localhost', 'localhost.localdomain'], true)) {
		return false;
	}
	$ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (gethostbynamel($host) ?: []);
	if (!$ips) {
		return false;
	}
	foreach ($ips as $ip) {
		if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
			return false;
		}
	}
	return true;
}

function pandaDownloadJpeg(string $url, string $target): bool {
	if (!function_exists('curl_init')) {
		return false;
	}
	$parts = parse_url($url);
	if (!is_array($parts) || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true) || !pandaPublicHost((string)($parts['host'] ?? ''))) {
		return false;
	}
	$body = '';
	$tooLarge = false;
	$curl = curl_init($url);
	curl_setopt_array($curl, [
		CURLOPT_RETURNTRANSFER => false,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_TIMEOUT => 12,
		CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		CURLOPT_USERAGENT => 'PanDaShop image sync/1.0',
		CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body, &$tooLarge): int {
			if (strlen($body) + strlen($chunk) > 8 * 1024 * 1024) {
				$tooLarge = true;
				return 0;
			}
			$body .= $chunk;
			return strlen($chunk);
		}
	]);
	$ok = curl_exec($curl) !== false && !$tooLarge && curl_getinfo($curl, CURLINFO_RESPONSE_CODE) === 200;
	curl_close($curl);
	if (!$ok || strlen($body) < 128) {
		return false;
	}
	$metadata = @getimagesizefromstring($body);
	if (!is_array($metadata) || $metadata[0] < 40 || $metadata[1] < 40 || $metadata[0] > 8000 || $metadata[1] > 8000 || ($metadata[0] * $metadata[1]) > 16000000) {
		return false;
	}
	$image = @imagecreatefromstring($body);
	if (!$image) {
		return false;
	}
	$width = imagesx($image);
	$height = imagesy($image);
	$background = imagecreatetruecolor($width, $height);
	$white = imagecolorallocate($background, 255, 255, 255);
	imagefill($background, 0, 0, $white);
	imagecopy($background, $image, 0, 0, 0, 0, $width, $height);
	$written = imagejpeg($background, $target, 90);
	imagedestroy($background);
	imagedestroy($image);
	return $written && pandaImageInfo($target) !== null;
}

function pandaSnapshot(mysqli $db, string $prefix, array $unknownIds, string $backupFile): void {
	$tables = ['product', 'product_description', 'product_to_category', 'product_attribute', 'product_image', 'product_to_store', 'product_discount', 'product_special', 'product_reward', 'product_filter', 'product_option', 'product_recurring', 'product_related', 'product_code', 'review', 'manufacturer', 'manufacturer_description', 'manufacturer_to_store', 'category_path'];
	$snapshot = ['created_at' => date(DATE_ATOM), 'tables' => []];
	$idList = array_values(array_unique(array_map('intval', $unknownIds)));
	foreach ($tables as $table) {
		$full = $prefix . $table;
		$exists = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($full) . "'");
		if (!$exists->num_rows) {
			continue;
		}
		if ($table === 'manufacturer' || $table === 'manufacturer_to_store') {
			$result = $db->query('SELECT * FROM ' . pandaTable($prefix, $table));
		} elseif ($table === 'category_path' || $table === 'product' || $table === 'product_to_category') {
			$result = $db->query('SELECT * FROM ' . pandaTable($prefix, $table));
		} elseif (!$idList) {
			continue;
		} else {
			$ids = implode(',', $idList);
			$condition = '`product_id` IN (' . $ids . ')';
			if ($table === 'product_related') {
				$condition = '(' . $condition . ' OR `related_id` IN (' . $ids . '))';
			}
			$result = $db->query('SELECT * FROM ' . pandaTable($prefix, $table) . ' WHERE ' . $condition);
		}
		$snapshot['tables'][$table] = $result->fetch_all(MYSQLI_ASSOC);
	}
	if (file_put_contents($backupFile, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX) === false) {
		throw new RuntimeException('Unable to write pre-change backup: ' . $backupFile);
	}
}

$apply = in_array('--apply', $argv, true);
$deleteUnmatched = in_array('--delete-unmatched', $argv, true);
if ($deleteUnmatched && !$apply) {
	fwrite(STDERR, "--delete-unmatched requires --apply.\n");
	exit(2);
}
if (!extension_loaded('mysqli') || !extension_loaded('mbstring') || !extension_loaded('gd')) {
	fwrite(STDERR, "Required PHP extensions: mysqli, mbstring, gd.\n");
	exit(2);
}
$prefix = (string)DB_PREFIX;
if (!preg_match('/^[A-Za-z0-9_]+$/', $prefix)) {
	fwrite(STDERR, "Invalid DB_PREFIX in upload/config.php.\n");
	exit(2);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli((string)DB_HOSTNAME, (string)DB_USERNAME, (string)DB_PASSWORD, (string)DB_DATABASE, (int)DB_PORT);
$db->set_charset('utf8mb4');
$productTable = pandaTable($prefix, 'product');
$descriptionTable = pandaTable($prefix, 'product_description');
$manufacturerTable = pandaTable($prefix, 'manufacturer');
$manufacturerStoreTable = pandaTable($prefix, 'manufacturer_to_store');
$assignmentTable = pandaTable($prefix, 'product_to_category');
$pathTable = pandaTable($prefix, 'category_path');

$productResult = $db->query("SELECT `p`.`product_id`, `p`.`model`, `p`.`image`, `p`.`manufacturer_id`, `p`.`status`, GROUP_CONCAT(DISTINCT `pd`.`name` SEPARATOR ' | ') AS `all_names` FROM {$productTable} `p` LEFT JOIN {$descriptionTable} `pd` ON (`pd`.`product_id` = `p`.`product_id`) GROUP BY `p`.`product_id`");
$products = [];
$usedBrands = [];
$unmatchedIds = [];
$imageRepairs = [];
$categoriesToAdd = [];

while ($row = $productResult->fetch_assoc()) {
	$row['names'] = $row['all_names'] !== null ? explode(' | ', $row['all_names']) : [];
	$row['brand'] = pandaBrand($row, PANDA_BRANDS);
	$products[(int)$row['product_id']] = $row;
	if ($row['brand'] === null) {
		$unmatchedIds[] = (int)$row['product_id'];
	} else {
		$usedBrands[$row['brand']] = true;
	}

	$image = trim((string)$row['image']);
	$imagePath = DIR_IMAGE . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $image);
	$external = preg_match('#^https?://#i', $image) === 1;
	if ($external || $image === '' || $image === 'no_image.png' || !pandaImageInfo($imagePath)) {
		$candidate = 'catalog/products/' . pandaSlug((string)$row['model']) . '.jpg';
		$candidatePath = DIR_IMAGE . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
		if (!pandaImageInfo($candidatePath) && $external && $apply) {
			$directory = dirname($candidatePath);
			if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
				throw new RuntimeException('Unable to create image directory: ' . $directory);
			}
			if (!pandaDownloadJpeg($image, $candidatePath)) {
				@unlink($candidatePath);
			}
		}
		if (pandaImageInfo($candidatePath)) {
			$imageRepairs[(int)$row['product_id']] = $candidate;
		} else {
			$imageRepairs[(int)$row['product_id']] = null;
		}
	}

	$assigned = $db->query('SELECT `category_id` FROM ' . $assignmentTable . " WHERE `product_id` = '" . (int)$row['product_id'] . "'")->fetch_all(MYSQLI_ASSOC);
	foreach ($assigned as $assignment) {
		$categoryId = (int)$assignment['category_id'];
		$ancestors = $db->query('SELECT `path_id` FROM ' . $pathTable . " WHERE `category_id` = '{$categoryId}' AND `path_id` <> '{$categoryId}'")->fetch_all(MYSQLI_ASSOC);
		foreach ($ancestors as $ancestor) {
			$parentId = (int)$ancestor['path_id'];
			$exists = $db->query('SELECT 1 FROM ' . $assignmentTable . " WHERE `product_id` = '" . (int)$row['product_id'] . "' AND `category_id` = '{$parentId}' LIMIT 1");
			if (!$exists->num_rows) {
				$categoriesToAdd[(int)$row['product_id']][$parentId] = true;
			}
		}
	}
}

$pandaManufacturers = $db->query("SELECT `manufacturer_id`, `name` FROM {$manufacturerTable} WHERE LOWER(`name`) LIKE '%pandashop%'")->fetch_all(MYSQLI_ASSOC);
$missingBrands = [];
foreach (array_keys($usedBrands) as $brand) {
	$stmt = $db->prepare("SELECT `manufacturer_id` FROM {$manufacturerTable} WHERE LOWER(`name`) = LOWER(?) LIMIT 1");
	$stmt->bind_param('s', $brand);
	$stmt->execute();
	if (!$stmt->get_result()->num_rows) {
		$missingBrands[] = $brand;
	}
	$stmt->close();
}

$missingImageIds = array_keys(array_filter($imageRepairs, static fn ($path): bool => $path === null));
echo "PanDaShop catalog sync - " . ($apply ? 'APPLY' : 'DRY RUN') . "\n";
echo 'Products scanned: ' . count($products) . "\n";
echo 'Recognized brands used: ' . count($usedBrands) . "\n";
echo 'Brands to create: ' . ($missingBrands ? implode(', ', $missingBrands) : 'none') . "\n";
echo 'Products with recognized brand: ' . (count($products) - count($unmatchedIds)) . "\n";
echo 'Products without a recognized brand: ' . count($unmatchedIds) . ($deleteUnmatched ? ' (will be deleted)' : ' (kept; use --delete-unmatched to delete)') . "\n";
echo 'PanDaShop/Panda manufacturer records to remove: ' . count($pandaManufacturers) . "\n";
echo 'Products needing image repair: ' . count($imageRepairs) . "\n";
echo 'Products with no valid model JPG candidate: ' . count($missingImageIds) . "\n";
echo 'Missing parent-category links to add: ' . array_sum(array_map('count', $categoriesToAdd)) . "\n";

foreach ($unmatchedIds as $id) {
	$product = $products[$id];
	echo "UNMATCHED\t{$id}\t{$product['model']}\t" . implode(' | ', $product['names']) . "\n";
}
foreach ($missingImageIds as $id) {
	echo "NO_IMAGE_SOURCE\t{$id}\t{$products[$id]['model']}\t" . implode(' | ', $products[$id]['names']) . "\n";
}

if (!$apply) {
	echo "Preview only. Re-run with --apply to write changes.\n";
	exit(0);
}

$backupDirectory = $projectRoot . DIRECTORY_SEPARATOR . '.backups';
if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0700, true) && !is_dir($backupDirectory)) {
	throw new RuntimeException('Unable to create backup directory.');
}
$backupFile = $backupDirectory . DIRECTORY_SEPARATOR . 'sync-panda-master-' . date('Ymd-His') . '.json';
pandaSnapshot($db, $prefix, $deleteUnmatched ? $unmatchedIds : [], $backupFile);
echo 'Backup written: ' . $backupFile . "\n";

$storeIds = [];
$storeResult = $db->query('SELECT `store_id` FROM ' . pandaTable($prefix, 'store'));
while ($storeRow = $storeResult->fetch_assoc()) {
	$storeIds[] = (int)$storeRow['store_id'];
}
if (!$storeIds) {
	$storeIds = [0];
}
$db->begin_transaction();
try {
	$brandIds = [];
	foreach (array_keys($usedBrands) as $brand) {
		$stmt = $db->prepare("SELECT `manufacturer_id` FROM {$manufacturerTable} WHERE LOWER(`name`) = LOWER(?) LIMIT 1");
		$stmt->bind_param('s', $brand);
		$stmt->execute();
	$result = $stmt->get_result()->fetch_assoc();
		$stmt->close();
		if ($result) {
			$brandIds[$brand] = (int)$result['manufacturer_id'];
		} else {
			$stmt = $db->prepare("INSERT INTO {$manufacturerTable} (`name`, `image`, `sort_order`) VALUES (?, '', 0)");
			$stmt->bind_param('s', $brand);
			$stmt->execute();
			$brandIds[$brand] = (int)$db->insert_id;
			$stmt->close();
		}
		foreach ($storeIds as $storeId) {
			$stmt = $db->prepare("INSERT IGNORE INTO {$manufacturerStoreTable} (`manufacturer_id`, `store_id`) VALUES (?, ?)");
			$stmt->bind_param('ii', $brandIds[$brand], $storeId);
			$stmt->execute();
			$stmt->close();
		}
	}

	$updateManufacturer = $db->prepare("UPDATE {$productTable} SET `manufacturer_id` = ? WHERE `product_id` = ?");
	foreach ($products as $id => $product) {
		if ($product['brand'] !== null) {
			$manufacturerId = $brandIds[$product['brand']];
			$updateManufacturer->bind_param('ii', $manufacturerId, $id);
			$updateManufacturer->execute();
		}
	}
	$updateManufacturer->close();

	$updateImage = $db->prepare("UPDATE {$productTable} SET `image` = ? WHERE `product_id` = ?");
	foreach ($imageRepairs as $id => $image) {
		if ($image !== null) {
			$updateImage->bind_param('si', $image, $id);
			$updateImage->execute();
		}
	}
	$updateImage->close();

	$insertCategory = $db->prepare("INSERT IGNORE INTO {$assignmentTable} (`product_id`, `category_id`) VALUES (?, ?)");
	foreach ($categoriesToAdd as $productId => $parentIds) {
		foreach (array_keys($parentIds) as $parentId) {
			$parentId = (int)$parentId;
			$insertCategory->bind_param('ii', $productId, $parentId);
			$insertCategory->execute();
		}
	}
	$insertCategory->close();

	if ($pandaManufacturers) {
		$ids = implode(',', array_map(static fn (array $row): int => (int)$row['manufacturer_id'], $pandaManufacturers));
		$db->query("UPDATE {$productTable} SET `manufacturer_id` = 0 WHERE `manufacturer_id` IN ({$ids})");
		$db->query("DELETE FROM {$manufacturerStoreTable} WHERE `manufacturer_id` IN ({$ids})");
		$descriptionName = $prefix . 'manufacturer_description';
		$hasDescription = $db->query("SHOW TABLES LIKE '" . $db->real_escape_string($descriptionName) . "'")->num_rows > 0;
		if ($hasDescription) {
			$columns = $db->query('SHOW COLUMNS FROM ' . pandaTable($prefix, 'manufacturer_description'))->fetch_all(MYSQLI_ASSOC);
			if (in_array('name', array_column($columns, 'Field'), true)) {
				$db->query("DELETE FROM " . pandaTable($prefix, 'manufacturer_description') . " WHERE LOWER(`name`) LIKE '%pandashop%'");
			} elseif (in_array('manufacturer_id', array_column($columns, 'Field'), true)) {
				$db->query("DELETE FROM " . pandaTable($prefix, 'manufacturer_description') . " WHERE `manufacturer_id` IN ({$ids})");
			}
		}
		$db->query("DELETE FROM {$manufacturerTable} WHERE `manufacturer_id` IN ({$ids})");
	}

	if ($deleteUnmatched && $unmatchedIds) {
		$ids = implode(',', array_map('intval', $unmatchedIds));
		$tables = $db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM);
		foreach ($tables as $tableRow) {
			$tableName = (string)$tableRow[0];
			if (!str_starts_with($tableName, $prefix . 'product_')) {
				continue;
			}
			$safeTableName = str_replace('`', '``', $tableName);
			$columns = $db->query('SHOW COLUMNS FROM `' . $safeTableName . '`')->fetch_all(MYSQLI_ASSOC);
			$columnNames = array_column($columns, 'Field');
			if (in_array('product_id', $columnNames, true)) {
				$condition = '`product_id` IN (' . $ids . ')';
				if (in_array('related_id', $columnNames, true)) {
					$condition = '(' . $condition . ' OR `related_id` IN (' . $ids . '))';
				}
				$db->query('DELETE FROM `' . $safeTableName . '` WHERE ' . $condition);
			}
		}
		$db->query("DELETE FROM {$productTable} WHERE `product_id` IN ({$ids})");
	}

	$db->commit();
} catch (Throwable $exception) {
	$db->rollback();
	throw $exception;
}

echo "Changes committed successfully. Review the backup before removing it.\n";

