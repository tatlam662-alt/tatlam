<?php
declare(strict_types=1);

/**
 * PanDaShop manufacturer cleanup runner for OpenCart 4.
 *
 * Safe by default: this file is CLI-only and runs as a report unless --apply
 * is explicitly supplied. It intentionally preserves order history.
 *
 * Usage:
 *   php clean_brands.php                 # report only
 *   php clean_brands.php --dry-run       # report only
 *   php clean_brands.php --apply         # write manufacturers, remap, delete unmatched products
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

$options = array_slice($argv, 1);
$unknownOptions = array_diff($options, ['--dry-run', '--apply']);

if ($unknownOptions || (in_array('--dry-run', $options, true) && in_array('--apply', $options, true))) {
	fwrite(STDERR, "Usage: php clean_brands.php [--dry-run|--apply]\n");
	exit(2);
}

$apply = in_array('--apply', $options, true);

require __DIR__ . '/config.php';

$pdo = new PDO(
	'mysql:host=' . DB_HOSTNAME . ';port=' . DB_PORT . ';dbname=' . DB_DATABASE . ';charset=utf8mb4',
	DB_USERNAME,
	DB_PASSWORD,
	[
		PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		PDO::ATTR_EMULATE_PREPARES   => false
	]
);

$prefix = DB_PREFIX;
$brands = [
	'ASUS'            => ['ASUS', 'ROG', 'TUF'],
	'MSI'             => ['MSI', 'MAG', 'Ventus'],
	'Gigabyte'        => ['Gigabyte', 'AORUS'],
	'Intel'           => ['Intel', 'Core i', 'Core Ultra'],
	'AMD'             => ['AMD', 'Ryzen'],
	'Corsair'         => ['Corsair', 'Vengeance'],
	'Samsung'         => ['Samsung', 'Odyssey', '990 Pro'],
	'Dell'            => ['Dell', 'Alienware'],
	'LG'              => ['LG', 'UltraGear'],
	'NZXT'            => ['NZXT', 'Kraken'],
	'Kingston'        => ['Kingston', 'Fury'],
	'Logitech'        => ['Logitech', 'LIGHTSPEED'],
	'Razer'           => ['Razer', 'Viper', 'DeathAdder'],
	'Akko'            => ['Akko', 'MonsGeek'],
	'MonsGeek'        => ['MonsGeek (brand record only)'],
	'SteelSeries'     => ['SteelSeries'],
	'HyperX'          => ['HyperX', 'Pulsefire'],
	'Wooting'         => ['Wooting'],
	'Pulsar'          => ['Pulsar'],
	'Artisan'         => ['Artisan'],
	'DJI'             => ['DJI', 'Mavic', 'Osmo'],
	'Autel Robotics'  => ['Autel', 'Autel Robotics'],
	'BetaFPV'         => ['BetaFPV', 'Cetus'],
	'Sony'            => ['Sony', 'INZONE', 'Alpha'],
	'RØDE'            => ['RØDE', 'RODE'],
	'Shure'           => ['Shure'],
	'JBL'             => ['JBL'],
	'Canon'           => ['Canon'],
	'GoPro'           => ['GoPro'],
	'Insta360'        => ['Insta360'],
	'Zhiyun'          => ['Zhiyun'],
	'Anker'           => ['Anker'],
	'Ugreen'          => ['Ugreen'],
	'Baseus'          => ['Baseus'],
	'SmallRig'        => ['SmallRig'],
	'SanDisk'         => ['SanDisk'],
	'Freewell'        => ['Freewell'],
	'PGYTECH'         => ['PGYTECH']
];

/** Normalize brand text for Vietnamese/Unicode-safe matching. */
function normalizeBrandText(string $value): string {
	$value = strtr($value, ['Ø' => 'O', 'ø' => 'o']);

	if (function_exists('iconv')) {
		$ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
		if ($ascii !== false) {
			$value = $ascii;
		}
	}

	return strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? $value));
}

/** Match a whole word/phrase, avoiding accidental substring matches. */
function brandTextContains(string $text, string $term): bool {
	$text = normalizeBrandText($text);
	$term = normalizeBrandText($term);

	if ($term === 'core i') {
		return (bool)preg_match('/\bcore\s*i[3579]?\b/i', $text);
	}

	if ($term === 'core ultra') {
		return (bool)preg_match('/\bcore\s+ultra\b/i', $text);
	}

	if ($term === 'mini series dji') {
		return (bool)preg_match('/\bmini\s+[234]\s*(?:pro|se|4k)?\b/i', $text);
	}

	return (bool)preg_match('/(?<![a-z0-9])' . preg_quote($term, '/') . '(?![a-z0-9])/i', $text);
}

/** OpenCart 4 stores the manufacturer label in manufacturer.name (not a description table). */
function tableExists(PDO $pdo, string $table): bool {
	$query = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
	$query->execute([$table]);
	return (int)$query->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $table, string $column): bool {
	$query = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
	$query->execute([$table, $column]);
	return (int)$query->fetchColumn() > 0;
}

/** Return IDs from a safe, fixed allowlist of tables with product relations. */
function deleteProductRelations(PDO $pdo, string $prefix, array $productIds): void {
	if (!$productIds) {
		return;
	}

	$relationTables = [
		'product_description', 'product_to_category', 'product_attribute', 'product_image',
		'product_to_store', 'product_to_layout', 'product_filter', 'product_code',
		'product_discount', 'product_special', 'product_reward', 'product_recurring',
		'product_subscription', 'product_to_download', 'product_report', 'product_viewed', 'review'
	];
	$placeholders = implode(',', array_fill(0, count($productIds), '?'));

	foreach ($relationTables as $table) {
		$fullTable = $prefix . $table;
		if (tableExists($pdo, $fullTable) && columnExists($pdo, $fullTable, 'product_id')) {
			$stmt = $pdo->prepare('DELETE FROM `' . $fullTable . '` WHERE `product_id` IN (' . $placeholders . ')');
			$stmt->execute($productIds);
		}
	}

	$optionTable = $prefix . 'product_option';
	$valueTable = $prefix . 'product_option_value';
	if (tableExists($pdo, $optionTable) && tableExists($pdo, $valueTable)) {
		$stmt = $pdo->prepare('DELETE FROM `' . $valueTable . '` WHERE `product_option_id` IN (SELECT `product_option_id` FROM `' . $optionTable . '` WHERE `product_id` IN (' . $placeholders . '))');
		$stmt->execute($productIds);
	}

	if (tableExists($pdo, $optionTable)) {
		$stmt = $pdo->prepare('DELETE FROM `' . $optionTable . '` WHERE `product_id` IN (' . $placeholders . ')');
		$stmt->execute($productIds);
	}

	$relatedTable = $prefix . 'product_related';
	if (tableExists($pdo, $relatedTable)) {
		$stmt = $pdo->prepare('DELETE FROM `' . $relatedTable . '` WHERE `product_id` IN (' . $placeholders . ') OR `related_id` IN (' . $placeholders . ')');
		$stmt->execute(array_merge($productIds, $productIds));
	}

	$seoTable = $prefix . 'seo_url';
	if (tableExists($pdo, $seoTable) && columnExists($pdo, $seoTable, 'query')) {
		$queries = array_map(static fn (int $id): string => 'product_id=' . $id, $productIds);
		$stmt = $pdo->prepare('DELETE FROM `' . $seoTable . '` WHERE `query` IN (' . implode(',', array_fill(0, count($queries), '?')) . ')');
		$stmt->execute($queries);
	}

	$stmt = $pdo->prepare('DELETE FROM `' . $prefix . 'product` WHERE `product_id` IN (' . $placeholders . ')');
	$stmt->execute($productIds);
}

function clearOpenCartCache(string $cacheDirectory): int {
	if (!is_dir($cacheDirectory)) {
		return 0;
	}

	$deleted = 0;
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($cacheDirectory, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ($iterator as $entry) {
		if ($entry->isLink()) {
			if (@unlink($entry->getPathname())) {
				$deleted++;
			}
		} elseif ($entry->isFile()) {
			if (@unlink($entry->getPathname())) {
				$deleted++;
			}
		} elseif ($entry->isDir()) {
			@rmdir($entry->getPathname());
		}
	}

	return $deleted;
}

try {
	$manufacturerTable = $prefix . 'manufacturer';
	$productTable = $prefix . 'product';
	$descriptionTable = $prefix . 'product_description';

	if (!tableExists($pdo, $manufacturerTable) || !tableExists($pdo, $productTable) || !tableExists($pdo, $descriptionTable)) {
		throw new RuntimeException('Thiếu một hoặc nhiều bảng OpenCart cần thiết trong database.');
	}

	if ($apply) {
		$pdo->beginTransaction();
	}

	$manufacturerRows = $pdo->query('SELECT `manufacturer_id`, `name` FROM `' . $manufacturerTable . '`')->fetchAll();
	$manufacturerIdsByName = [];
	$manufacturerNamesById = [];
	foreach ($manufacturerRows as $row) {
		$key = normalizeBrandText((string)$row['name']);
		$manufacturerIdsByName[$key] = (int)$row['manufacturer_id'];
		$manufacturerNamesById[(int)$row['manufacturer_id']] = (string)$row['name'];
	}

	$stores = tableExists($pdo, $prefix . 'store')
		? array_map('intval', $pdo->query('SELECT `store_id` FROM `' . $prefix . 'store`')->fetchAll(PDO::FETCH_COLUMN))
		: [];
	$stores[] = 0;
	$stores = array_values(array_unique($stores));

	$brandAdded = [];
	$brandExisting = [];
	foreach (array_keys($brands) as $brandName) {
		$key = normalizeBrandText($brandName);
		if (!isset($manufacturerIdsByName[$key])) {
			$brandAdded[] = $brandName;
			if ($apply) {
				$stmt = $pdo->prepare('INSERT INTO `' . $manufacturerTable . '` (`name`, `image`, `sort_order`) VALUES (?, ?, 0)');
				$stmt->execute([$brandName, '']);
				$id = (int)$pdo->lastInsertId();
				$manufacturerIdsByName[$key] = $id;
				$manufacturerNamesById[$id] = $brandName;
				if (tableExists($pdo, $prefix . 'manufacturer_to_store')) {
					$storeStmt = $pdo->prepare('INSERT IGNORE INTO `' . $prefix . 'manufacturer_to_store` (`manufacturer_id`, `store_id`) VALUES (?, ?)');
					foreach ($stores as $storeId) {
						$storeStmt->execute([$id, $storeId]);
					}
				}
			}
		} else {
			$brandExisting[] = $brandName;
			if ($apply && tableExists($pdo, $prefix . 'manufacturer_to_store')) {
				$storeStmt = $pdo->prepare('INSERT IGNORE INTO `' . $prefix . 'manufacturer_to_store` (`manufacturer_id`, `store_id`) VALUES (?, ?)');
				foreach ($stores as $storeId) {
					$storeStmt->execute([$manufacturerIdsByName[$key], $storeId]);
				}
			}
		}
	}
	$standardBrandKeys = array_fill_keys(array_map('normalizeBrandText', array_keys($brands)), true);

	// MonsGeek is inserted as requested, but the supplied mapping rules point MonsGeek products to Akko.
	$nameQuery = $pdo->query('SELECT `p`.`product_id`, `p`.`manufacturer_id`, `p`.`model`, `pd`.`name` FROM `' . $productTable . '` `p` LEFT JOIN `' . $descriptionTable . '` `pd` ON (`pd`.`product_id` = `p`.`product_id`) ORDER BY `p`.`product_id`');
	$products = [];
	foreach ($nameQuery as $row) {
		$id = (int)$row['product_id'];
		if (!isset($products[$id])) {
			$products[$id] = [
				'product_id'      => $id,
				'manufacturer_id' => (int)$row['manufacturer_id'],
				'model'           => (string)$row['model'],
				'names'           => []
			];
		}
		if (!empty($row['name'])) {
			$products[$id]['names'][] = (string)$row['name'];
		}
	}

	$plans = [];
	$ambiguous = [];
	$unmatched = [];
	$retained = 0;
	foreach ($products as $product) {
		$text = implode(' ', $product['names']) . ' ' . $product['model'];
		$candidates = [];

		foreach ($brands as $brandName => $aliases) {
			if ($brandName === 'MonsGeek') {
				continue;
			}

			foreach ($aliases as $alias) {
				if (brandTextContains($text, $alias)) {
					$candidates[$brandName] = true;
				}
			}
		}

		// DJI's Mini family is intentionally specific to avoid classifying generic mini PCs as drones.
		if (brandTextContains($text, 'Mini 4 Pro') || brandTextContains($text, 'Mini 3 Pro') || brandTextContains($text, 'Mini 3') || brandTextContains($text, 'Mini 2') || brandTextContains($text, 'Mini 4K')) {
			$candidates['DJI'] = true;
		}

		$candidateNames = array_keys($candidates);
		$currentName = $manufacturerNamesById[$product['manufacturer_id']] ?? '';
		if (count($candidateNames) === 1) {
			$plans[$product['product_id']] = $candidateNames[0];
		} elseif (count($candidateNames) > 1) {
			if (in_array($currentName, $candidateNames, true)) {
				$retained++;
			} elseif (normalizeBrandText($currentName) === 'pandashop') {
				$unmatched[$product['product_id']] = $product['names'][0] ?? $product['model'];
			} else {
				$ambiguous[$product['product_id']] = ['name' => $product['names'][0] ?? $product['model'], 'brands' => $candidateNames];
			}
		} elseif (isset($standardBrandKeys[normalizeBrandText($currentName)])) {
			$retained++;
		} else {
			$unmatched[$product['product_id']] = $product['names'][0] ?? $product['model'];
		}
	}

	$orderReferencedIds = [];
	if (tableExists($pdo, $prefix . 'order_product')) {
		$orderReferencedIds = array_map('intval', $pdo->query('SELECT DISTINCT `product_id` FROM `' . $prefix . 'order_product` WHERE `product_id` > 0')->fetchAll(PDO::FETCH_COLUMN));
	}
	$masterReferencedIds = [];
	if (columnExists($pdo, $productTable, 'master_id')) {
		$masterReferencedIds = array_map('intval', $pdo->query('SELECT DISTINCT `master_id` FROM `' . $productTable . '` WHERE `master_id` > 0')->fetchAll(PDO::FETCH_COLUMN));
	}

	$protected = [];
	$deletableIds = [];
	foreach ($unmatched as $id => $name) {
		if (in_array((int)$id, $orderReferencedIds, true) || in_array((int)$id, $masterReferencedIds, true)) {
			$protected[$id] = $name;
		} else {
			$deletableIds[] = (int)$id;
		}
	}

	$updated = 0;
	if ($apply) {
		$updateStmt = $pdo->prepare('UPDATE `' . $productTable . '` SET `manufacturer_id` = ? WHERE `product_id` = ? AND `manufacturer_id` <> ?');
		foreach ($plans as $productId => $brandName) {
			$brandId = $manufacturerIdsByName[normalizeBrandText($brandName)] ?? 0;
			$currentId = $products[$productId]['manufacturer_id'];
			if ($brandId > 0 && $brandId !== $currentId) {
				$updateStmt->execute([$brandId, $productId, $brandId]);
				$updated += $updateStmt->rowCount();
			}
		}

		foreach (array_chunk($deletableIds, 300) as $chunk) {
			deleteProductRelations($pdo, $prefix, $chunk);
		}

		// Keep historic orders intact; hide unrecognized ordered items and detach them from PanDaShop.
		if ($protected) {
			$ids = array_map('intval', array_keys($protected));
			$placeholders = implode(',', array_fill(0, count($ids), '?'));
			$stmt = $pdo->prepare('UPDATE `' . $productTable . '` SET `manufacturer_id` = 0, `status` = 0 WHERE `product_id` IN (' . $placeholders . ')');
			$stmt->execute($ids);
		}

		$manufacturerQuery = $pdo->prepare('SELECT `manufacturer_id` FROM `' . $manufacturerTable . '` WHERE LOWER(TRIM(`name`)) = LOWER(?)');
		$manufacturerQuery->execute(['PanDaShop']);
		$pandaIds = array_map('intval', $manufacturerQuery->fetchAll(PDO::FETCH_COLUMN));
		foreach ($pandaIds as $id) {
			if (tableExists($pdo, $prefix . 'manufacturer_description')) {
				$stmt = $pdo->prepare('DELETE FROM `' . $prefix . 'manufacturer_description` WHERE `manufacturer_id` = ?');
				$stmt->execute([$id]);
			}
			foreach (['manufacturer_to_store', 'manufacturer_to_layout'] as $relation) {
				$table = $prefix . $relation;
				if (tableExists($pdo, $table)) {
					$stmt = $pdo->prepare('DELETE FROM `' . $table . '` WHERE `manufacturer_id` = ?');
					$stmt->execute([$id]);
				}
			}
			$seoTable = $prefix . 'seo_url';
			if (tableExists($pdo, $seoTable) && columnExists($pdo, $seoTable, 'query')) {
				$stmt = $pdo->prepare('DELETE FROM `' . $seoTable . '` WHERE `query` = ?');
				$stmt->execute(['manufacturer_id=' . $id]);
			}
			$stmt = $pdo->prepare('DELETE FROM `' . $manufacturerTable . '` WHERE `manufacturer_id` = ?');
			$stmt->execute([$id]);
		}
	}

	if ($apply) {
		$pdo->commit();
	}

	$cacheDeleted = 0;
	if ($apply && defined('DIR_CACHE')) {
		$cacheDeleted = clearOpenCartCache(DIR_CACHE);
	}

	$mode = $apply ? 'APPLY' : 'DRY RUN';
	echo "PanDaShop brand cleanup — {$mode}\n";
	echo 'Manufacturers already present: ' . count($brandExisting) . "\n";
	echo 'Manufacturers to insert/inserted: ' . count($brandAdded) . "\n";
	echo 'Products to remap/remapped: ' . count($plans) . ($apply ? ' (' . $updated . ' changed)' : '') . "\n";
	echo 'Products with an existing recognized manufacturer retained: ' . $retained . "\n";
	echo 'Ambiguous products kept for manual review: ' . count($ambiguous) . "\n";
	echo 'Unmatched products eligible for removal: ' . count($deletableIds) . ($apply ? ' (deleted)' : '') . "\n";
	echo 'Unmatched products protected by order/master references: ' . count($protected) . ($apply ? ' (hidden; order history preserved)' : '') . "\n";
	echo 'PanDaShop manufacturer records removed: ' . (isset($pandaIds) ? count($pandaIds) : 'run with --apply to count') . "\n";
	if ($apply) {
		echo 'Cache files removed: ' . $cacheDeleted . "\n";
	}

	if ($brandAdded) {
		echo "\nManufacturers to add:\n- " . implode("\n- ", $brandAdded) . "\n";
	}
	if ($ambiguous) {
		echo "\nAmbiguous products (up to 100 shown):\n";
		foreach (array_slice($ambiguous, 0, 100, true) as $id => $item) {
			echo '- #' . $id . ' ' . $item['name'] . ' [' . implode(', ', $item['brands']) . "]\n";
		}
	}
	if ($unmatched) {
		echo "\nUnmatched products eligible for deletion (up to 200 shown):\n";
		foreach (array_slice($unmatched, 0, 200, true) as $id => $name) {
			echo '- #' . $id . ' ' . $name . (isset($protected[$id]) ? ' [protected]' : '') . "\n";
		}
	}
	if (!$apply) {
		echo "\nNo changes were made. Review this report, then run: php clean_brands.php --apply\n";
	}
} catch (Throwable $exception) {
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}
	fwrite(STDERR, 'FAILED: ' . $exception->getMessage() . "\n");
	exit(1);
}
