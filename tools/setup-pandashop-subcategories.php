<?php
declare(strict_types=1);

/**
 * Idempotent OpenCart 4 category/subcategory setup for PanDaShop.
 * Run from the project root: C:\xampp\php\php.exe tools\setup-pandashop-subcategories.php
 * CLI-only by design; this file must not be exposed as a web installer.
 */
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only. Run this file from a terminal.\n");
	exit(1);
}

$root = dirname(__DIR__);
$configFile = $root . '/upload/config.php';
if (!is_file($configFile)) {
	fwrite(STDERR, "OpenCart config not found: {$configFile}\n");
	exit(1);
}
require_once $configFile;

// Demand-based, multi-brand subcategories. Product model references are curated below.
$taxonomy = [
	'Flycam & Drone' => [
		['Flycam Mini & Bỏ Túi (<249g)', [], ['FLY-DJI-01', 'DJI-MINI4PRO-FMC', 'DJI-NEO', 'DJI-NEO-FMC', 'FLY-DJI-05']],
		['Flycam Tầm Trung (Quay 4K/6K)', [], ['DJI-AIR3S', 'FLY-DJI-02', 'FLY-DJI-06', 'FLY-AUTEL-01']],
		['Flycam Điện Ảnh & Cao Cấp', [], ['DJI-MAVIC3PRO', 'FLY-DJI-04', 'FLY-DJI-07']],
		['FPV & Racing Drone', [], ['FLY-DJI-03', 'FLY-BETA-01']],
	],
	'Camera & Gimbal' => [
		['Camera Bỏ Túi & Vlog', [], ['DJI-POCKET3', 'CAM-DJI-01']],
		['Action Cam (Camera Hành Trình)', [], ['DJI-ACTION5', 'CAM-DJI-03']],
		['Gimbal Máy Ảnh Chuyên Nghiệp', [], ['DJI-RS4MINI', 'DJI-RS4', 'CAM-DJI-02', 'CAM-DJI-05']],
		['Gimbal Điện Thoại', [], ['CAM-DJI-06']],
	],
	'Thiết bị Âm thanh' => [
		['Micro Không Dây (Wireless)', [], ['DJI-MIC2', 'CAM-DJI-04', 'AUDIO-DJI-01', 'AUDIO-DJI-02', 'AUD-RODE-01']],
		['Micro Podcasting & Streamer', [], ['AUD-SHURE-01']],
		['Loa Bluetooth & Di Động', [], ['AUD-JBL-01']],
		['Tai Nghe Studio & Chống Ồn', [], []],
	],
	'Phụ kiện Công nghệ' => [
		['Sạc Nhanh & Pin Dự Phòng', [], ['DJI-BAT-MINI4', 'ACC-DJI-01', 'ACC-ANKER-01']],
		['Hub Chuyển Đổi & Docking', [], ['DJI-HUB-MINI4', 'ACC-UGREEN-01']],
		['Kính Lọc ND & Optic Filter', [], ['DJI-ND-MINI4', 'ACC-DJI-03']],
		['Khung Bảo Vệ & Rig Máy Ảnh', [], ['ACC-SMALL-01']],
		['Thẻ Nhớ & Ổ Cứng Di Động', [], ['ACC-DJI-02']],
		['Bảo vệ & Phụ kiện Flycam', [], ['DJI-PROP-MINI4', 'DJI-PAD-75']],
		['Túi & Balo Công nghệ', [], ['DJI-BAG-01']],
		['Điều khiển & Kính FPV', [], ['DJI-GOGGLES3', 'DJI-RC2']],
	],
	'Bàn phím gaming' => [
		['Bàn Phím Cơ Wireless', [], ['NX-KB-TKL', 'KB-LOGI-01', 'KB-AKKO-01', 'KB-ASUS-01']],
		['Bàn Phím Custom / Hotswap', [], ['NX-KB-75', 'KB-MONS-01']],
		['Bàn Phím Rapid Trigger (Magnet)', [], ['KB-WOOT-01']],
		['Keycap & Switch Custom', [], []],
	],
	'Chuột gaming' => [
		['Chuột Không Dây LIGHTSPEED', [], ['NX-MS-8K', 'MS-LOGI-01', 'MS-RAZER-01']],
		['Chuột Siêu Nhẹ (Ultralight)', [], ['NX-MS-UL', 'MS-WL-01']],
		['Chuột Ergonomic Công Xưởng', [], ['MS-LOGI-02', 'MS-RAZER-02']],
		['Lót Chuột & Accessories', [], ['NX-PAD-XXL']],
	],
	'Tai nghe gaming' => [
		['Tai Nghe Không Dây Esports', [], ['NX-HS-VOID', 'HP-STEEL-01', 'HP-HYPER-01', 'HP-RAZER-01']],
		['Tai Nghe Giả Lập 7.1 / Spatial', [], ['HP-SONY-01', 'HP-CORSAIR-01']],
		['Micro Streamer & Accessories', [], []],
		['Tai Nghe In-Ear / Earbuds', [], []],
	],
	'Linh kiện PC' => [
		['Card Màn Hình (VGA)', [], ['PC-GPU-01', 'PC-GPU-02']],
		['Bộ Vi Xử Lý (CPU)', [], ['PC-CPU-01', 'PC-CPU-02']],
		['Bo Mạch Chủ (Mainboard)', [], ['PC-MB-01']],
		['RAM & SSD NVMe High-Speed', [], ['NX-RAM-32', 'NX-SSD-2T', 'PC-SSD-01', 'PC-RAM-01']],
		['Nguồn (PSU) & Tản Nhiệt', [], ['NX-PSU-850', 'NX-AIO-360', 'PC-PSU-01']],
	],
	'Màn hình' => [
		['Màn Hình Gaming (144Hz+)', [], ['NX-MON-27', 'MON-ASUS-01']],
		['Màn Hình Đồ Họa (4K / OLED)', [], ['MON-DELL-01']],
		['Màn Hình Cong Ultrawide', [], ['MON-SAMS-01']],
	],
	'Bộ kit / Combo' => [
		['Trọn Bộ PC Gaming Neon', [], ['KIT-PANDA-01', 'KIT-BATTLE-NEON', 'KIT-RENDER-START']],
		['Combo Creator / Streamer', [], ['KIT-PANDA-02', 'KIT-CREATOR-AIR']],
		['Combo Gaming Gear Đồng Bộ', [], ['KIT-RAZER-01']],
	],
];

try {
	$pdo = new PDO(
		'mysql:host=' . DB_HOSTNAME . ';port=' . DB_PORT . ';dbname=' . DB_DATABASE . ';charset=utf8mb4',
		DB_USERNAME,
		DB_PASSWORD,
		[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
	);
	$prefix = DB_PREFIX;
	$languageRows = $pdo->query("SELECT `language_id`,`code` FROM `{$prefix}language` WHERE `status`=1")->fetchAll();
	$viLanguageId = 0;
	$storeIds = array_map('intval', $pdo->query("SELECT `store_id` FROM `{$prefix}store`")->fetchAll(PDO::FETCH_COLUMN));
	$storeIds[] = 0;
	$storeIds = array_values(array_unique($storeIds));
	foreach ($languageRows as $language) {
		if ($language['code'] === 'vi-vn') {
			$viLanguageId = (int)$language['language_id'];
		}
	}
	if (!$viLanguageId) {
		throw new RuntimeException('Enabled vi-vn language not found.');
	}
} catch (Throwable $exception) {
	fwrite(STDERR, 'Database initialization failed: ' . $exception->getMessage() . PHP_EOL);
	exit(1);
}

$categoryColumns = array_column($pdo->query("SHOW COLUMNS FROM `{$prefix}category`")->fetchAll(), 'Field');
$hasTopColumn = in_array('top', $categoryColumns, true);

try {
	$pdo->beginTransaction();
	$parentQuery = $pdo->prepare("SELECT c.`category_id` FROM `{$prefix}category` c JOIN `{$prefix}category_description` cd ON cd.`category_id`=c.`category_id` WHERE cd.`language_id`=? AND cd.`name`=? AND c.`parent_id`=0 AND c.`status`=1 LIMIT 1");
	$findChild = $pdo->prepare("SELECT c.`category_id` FROM `{$prefix}category` c JOIN `{$prefix}category_description` cd ON cd.`category_id`=c.`category_id` WHERE cd.`language_id`=? AND cd.`name` IN (%s) AND c.`parent_id`=? ORDER BY FIELD(cd.`name`,%s) LIMIT 1");
	$insertCategory = $pdo->prepare("INSERT INTO `{$prefix}category` (`image`,`parent_id`,`sort_order`,`status`) VALUES ('',?,0,1)");
	$updateCategorySql = "UPDATE `{$prefix}category` SET `parent_id`=?, `status`=1" . ($hasTopColumn ? ", `top`=1" : '') . " WHERE `category_id`=?";
	$updateCategory = $pdo->prepare($updateCategorySql);
	$insertDescription = $pdo->prepare("INSERT INTO `{$prefix}category_description` (`category_id`,`language_id`,`name`,`description`,`meta_title`,`meta_description`,`meta_keyword`) VALUES (?,?,?,'',?,'','') ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `meta_title`=VALUES(`meta_title`)");
	$storeInsert = $pdo->prepare("INSERT INTO `{$prefix}category_to_store` (`category_id`,`store_id`) VALUES (?,?) ON DUPLICATE KEY UPDATE `store_id`=VALUES(`store_id`)");

	$categoryIds = [];
	$parentIds = [];
	$categoryReport = [];
	foreach ($taxonomy as $parentName => $children) {
		$parentQuery->execute([$viLanguageId, $parentName]);
		$parentId = (int)$parentQuery->fetchColumn();
		if (!$parentId) {
			throw new RuntimeException("Parent category not found or disabled: {$parentName}");
		}
		$parentIds[$parentName] = $parentId;
		foreach ($children as [$name, $aliases, $models]) {
			$lookupNames = array_values(array_unique(array_merge([$name], $aliases)));
			$quoted = implode(',', array_fill(0, count($lookupNames), '?'));
			$order = implode(',', array_fill(0, count($lookupNames), '?'));
			$lookup = $pdo->prepare(sprintf(
				"SELECT c.`category_id` FROM `%s` c JOIN `%s` cd ON cd.`category_id`=c.`category_id` WHERE cd.`language_id`=? AND cd.`name` IN (%s) AND c.`parent_id`=? ORDER BY FIELD(cd.`name`,%s) LIMIT 1",
				$prefix . 'category', $prefix . 'category_description', $quoted, $order
			));
			$lookup->execute(array_merge([$viLanguageId], $lookupNames, [$parentId], $lookupNames));
			$categoryId = (int)$lookup->fetchColumn();
			$action = 'existing';
			if (!$categoryId) {
				$insertCategory->execute([$parentId]);
				$categoryId = (int)$pdo->lastInsertId();
				$action = 'created';
			}
			$updateCategory->execute([$parentId, $categoryId]);
			foreach ($languageRows as $language) {
				// The store currently uses Vietnamese labels in both enabled storefront/admin languages.
				$insertDescription->execute([$categoryId, (int)$language['language_id'], $name, $name]);
			}
			foreach ($storeIds as $storeId) {
				$storeInsert->execute([$categoryId, $storeId]);
			}
			$categoryIds[$name] = $categoryId;
			$categoryReport[] = ['parent' => $parentName, 'name' => $name, 'category_id' => $categoryId, 'action' => $action];
		}
	}

	// Rebuild category_path from parent_id so every path has correct ancestors and levels.
	$categories = $pdo->query("SELECT `category_id`,`parent_id` FROM `{$prefix}category`")->fetchAll();
	$parents = [];
	foreach ($categories as $category) {
		$parents[(int)$category['category_id']] = (int)$category['parent_id'];
	}
	$pathRows = [];
	foreach ($parents as $categoryId => $_parentId) {
		$chain = [];
		$current = $categoryId;
		$seen = [];
		while ($current > 0) {
			if (isset($seen[$current])) {
				throw new RuntimeException("Category parent cycle detected at category_id {$current}.");
			}
			$seen[$current] = true;
			$chain[] = $current;
			$current = $parents[$current] ?? 0;
		}
		$chain = array_reverse($chain);
		foreach ($chain as $level => $pathId) {
			$pathRows[] = [$categoryId, $pathId, $level];
		}
	}
	$pdo->exec("DELETE FROM `{$prefix}category_path`");
	$pathInsert = $pdo->prepare("INSERT INTO `{$prefix}category_path` (`category_id`,`path_id`,`level`) VALUES (?,?,?)");
	foreach ($pathRows as $row) {
		$pathInsert->execute($row);
	}

	// Assign products only where the requested taxonomy has a confident matching subcategory.
	$modelToCategory = [];
	foreach ($taxonomy as $parentName => $children) {
		foreach ($children as [$name, $_aliases, $models]) {
			foreach ($models as $model) {
				if (isset($modelToCategory[$model])) {
					throw new RuntimeException("Product model mapped to multiple categories: {$model}");
				}
				$modelToCategory[$model] = ['parent_id' => $parentIds[$parentName], 'child_id' => $categoryIds[$name]];
			}
		}
	}
	$productFind = $pdo->prepare("SELECT `product_id` FROM `{$prefix}product` WHERE `model`=? LIMIT 2");
	$branchIds = implode(',', array_map('intval', array_values($parentIds)));
	$productRemoveCategories = $pdo->prepare("DELETE p2c FROM `{$prefix}product_to_category` p2c JOIN `{$prefix}category_path` cp ON cp.`category_id`=p2c.`category_id` WHERE p2c.`product_id`=? AND cp.`path_id` IN ({$branchIds})");
	$productAssign = $pdo->prepare("INSERT INTO `{$prefix}product_to_category` (`product_id`,`category_id`) VALUES (?,?) ON DUPLICATE KEY UPDATE `category_id`=VALUES(`category_id`)");
	$assigned = [];
	$missingModels = [];
	foreach ($modelToCategory as $model => $categoryIdsForProduct) {
		$productFind->execute([$model]);
		$productIds = $productFind->fetchAll(PDO::FETCH_COLUMN);
		if (count($productIds) > 1) {
			throw new RuntimeException("Duplicate product model found: {$model}");
		}
		if (!$productIds) {
			$missingModels[] = $model;
			continue;
		}
		$productId = (int)$productIds[0];
		$productRemoveCategories->execute([$productId]);
		$productAssign->execute([$productId, $categoryIdsForProduct['parent_id']]);
		$productAssign->execute([$productId, $categoryIdsForProduct['child_id']]);
		$assigned[] = ['model' => $model, 'product_id' => $productId, 'parent_category_id' => $categoryIdsForProduct['parent_id'], 'child_category_id' => $categoryIdsForProduct['child_id']];
	}

	// Hide legacy subcategories that are no longer part of the normalized taxonomy.
	$activeCategoryIds = array_values($categoryIds);
	$activePlaceholders = implode(',', array_fill(0, count($activeCategoryIds), '?'));
	$deactivateSql = "UPDATE `{$prefix}category` SET `status`=0 WHERE `parent_id` IN ({$branchIds}) AND `category_id` NOT IN ({$activePlaceholders})";
	$deactivate = $pdo->prepare($deactivateSql);
	$deactivate->execute($activeCategoryIds);
	$deactivatedCategories = $deactivate->rowCount();

	$pdo->commit();

	$cacheDirectory = rtrim(DIR_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'cache';
	$cacheDeleted = 0;
	if (is_dir($cacheDirectory)) {
		foreach (new DirectoryIterator($cacheDirectory) as $entry) {
			if ($entry->isFile() || $entry->isLink()) {
				if (@unlink($entry->getPathname())) {
					$cacheDeleted++;
				}
			}
		}
	}

	$report = [
		'created_or_verified_subcategories' => count($categoryReport),
		'product_links_updated' => count($assigned),
		'legacy_categories_disabled' => $deactivatedCategories,
		'products_not_found' => $missingModels,
		'category_top_column_supported' => $hasTopColumn,
		'category_top_note' => $hasTopColumn ? 'top=1 set for subcategories' : 'This OpenCart 4 schema has no category.top column; status and category_path were updated.',
		'cache_files_deleted' => $cacheDeleted,
		'categories' => $categoryReport,
		'product_assignments' => $assigned,
	];
	$reportJson = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	$logDirectory = rtrim(DIR_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'logs';
	if (is_dir($logDirectory)) {
		@file_put_contents($logDirectory . DIRECTORY_SEPARATOR . 'pandashop-subcategories-' . date('Ymd-His') . '.json', $reportJson . PHP_EOL);
	}
	echo $reportJson . PHP_EOL;
} catch (Throwable $exception) {
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}
	fwrite(STDERR, 'Category setup rolled back: ' . $exception->getMessage() . PHP_EOL);
	exit(1);
}
