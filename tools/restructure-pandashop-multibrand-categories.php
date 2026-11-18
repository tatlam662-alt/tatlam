<?php
declare(strict_types=1);

/** CLI-only, repeatable category taxonomy migration for PanDaShop. */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only. Run: C:\\xampp\\php\\php.exe tools\\restructure-pandashop-multibrand-categories.php\n");
    exit(1);
}
require_once dirname(__DIR__) . '/upload/config.php';

$taxonomy = [
    'Flycam & Drone' => [
        'Flycam Mini & Bỏ Túi (<249g)' => ['FLY-DJI-01','DJI-MINI4PRO-FMC','DJI-NEO','DJI-NEO-FMC','FLY-DJI-05'],
        'Flycam Tầm Trung (Quay 4K/6K)' => ['DJI-AIR3S','FLY-DJI-02','FLY-DJI-06','FLY-AUTEL-01'],
        'Flycam Điện Ảnh & Cao Cấp' => ['DJI-MAVIC3PRO','FLY-DJI-04','FLY-DJI-07'],
        'FPV & Racing Drone' => ['FLY-DJI-03','FLY-BETA-01'],
    ],
    'Camera & Gimbal' => [
        'Camera Bỏ Túi & Vlog' => ['DJI-POCKET3','CAM-DJI-01'],
        'Action Cam (Camera Hành Trình)' => ['DJI-ACTION5','CAM-DJI-03'],
        'Gimbal Máy Ảnh Chuyên Nghiệp' => ['DJI-RS4MINI','DJI-RS4','CAM-DJI-02','CAM-DJI-05'],
        'Gimbal Điện Thoại' => ['CAM-DJI-06'],
    ],
    'Thiết bị Âm thanh' => [
        'Micro Không Dây (Wireless)' => ['DJI-MIC2','CAM-DJI-04','AUDIO-DJI-01','AUDIO-DJI-02','AUD-RODE-01'],
        'Micro Podcasting & Streamer' => ['AUD-SHURE-01'],
        'Loa Bluetooth & Di Động' => ['AUD-JBL-01'],
        'Tai Nghe Studio & Chống Ồn' => [],
    ],
    'Phụ kiện Công nghệ' => [
        'Sạc Nhanh & Pin Dự Phòng' => ['DJI-BAT-MINI4','ACC-DJI-01','ACC-ANKER-01'],
        'Hub Chuyển Đổi & Docking' => ['DJI-HUB-MINI4','ACC-UGREEN-01'],
        'Kính Lọc ND & Optic Filter' => ['DJI-ND-MINI4','ACC-DJI-03'],
        'Khung Bảo Vệ & Rig Máy Ảnh' => ['ACC-SMALL-01'],
        'Thẻ Nhớ & Ổ Cứng Di Động' => ['ACC-DJI-02'],
        // Preserve existing accessory families that are outside the requested five groups.
        'Bảo vệ & Phụ kiện Flycam' => ['DJI-PROP-MINI4','DJI-PAD-75'],
        'Túi & Balo Công nghệ' => ['DJI-BAG-01'],
        'Điều khiển & Kính FPV' => ['DJI-GOGGLES3','DJI-RC2'],
    ],
];

try {
    $pdo = new PDO('mysql:host=' . DB_HOSTNAME . ';port=' . DB_PORT . ';dbname=' . DB_DATABASE . ';charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $prefix = DB_PREFIX;
    $languages = $pdo->query("SELECT `language_id`,`code` FROM `{$prefix}language` WHERE `status`=1")->fetchAll();
    $viLanguageId = 0;
    foreach ($languages as $language) {
        if ($language['code'] === 'vi-vn') { $viLanguageId = (int)$language['language_id']; break; }
    }
    if (!$viLanguageId) { throw new RuntimeException('Enabled vi-vn language not found.'); }
    $storeIds = array_map('intval', $pdo->query("SELECT `store_id` FROM `{$prefix}store`")->fetchAll(PDO::FETCH_COLUMN));
    $storeIds = array_values(array_unique(array_merge([0], $storeIds)));
    $categoryColumns = array_column($pdo->query("SHOW COLUMNS FROM `{$prefix}category`")->fetchAll(), 'Field');
    $hasTop = in_array('top', $categoryColumns, true);

    $pdo->beginTransaction();
    $parentLookup = $pdo->prepare("SELECT c.`category_id` FROM `{$prefix}category` c JOIN `{$prefix}category_description` cd ON cd.`category_id`=c.`category_id` WHERE c.`parent_id`=0 AND c.`status`=1 AND cd.`language_id`=? AND cd.`name`=? LIMIT 2");
    $categoryIdsByName = [];
    $rootIds = [];
    $created = 0;
    $verified = 0;
    $insertCategory = $pdo->prepare("INSERT INTO `{$prefix}category` (`image`,`parent_id`,`sort_order`,`status`) VALUES ('',?,0,1)");
    $upsertDescription = $pdo->prepare("INSERT INTO `{$prefix}category_description` (`category_id`,`language_id`,`name`,`description`,`meta_title`,`meta_description`,`meta_keyword`) VALUES (?,?,?,'',?,'','') ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`meta_title`=VALUES(`meta_title`)");
    $upsertStore = $pdo->prepare("INSERT INTO `{$prefix}category_to_store` (`category_id`,`store_id`) VALUES (?,?) ON DUPLICATE KEY UPDATE `store_id`=VALUES(`store_id`)");
    foreach ($taxonomy as $rootName => $children) {
        $parentLookup->execute([$viLanguageId, $rootName]);
        $parentIds = $parentLookup->fetchAll(PDO::FETCH_COLUMN);
        if (count($parentIds) !== 1) { throw new RuntimeException("Root category not uniquely found: {$rootName}"); }
        $parentId = (int)$parentIds[0];
        $rootIds[$rootName] = $parentId;
        $findChild = $pdo->prepare("SELECT c.`category_id` FROM `{$prefix}category` c JOIN `{$prefix}category_description` cd ON cd.`category_id`=c.`category_id` WHERE c.`parent_id`=? AND cd.`language_id`=? AND cd.`name`=? LIMIT 2");
        foreach ($children as $name => $_models) {
            $findChild->execute([$parentId, $viLanguageId, $name]);
            $ids = $findChild->fetchAll(PDO::FETCH_COLUMN);
            if (count($ids) > 1) { throw new RuntimeException("Duplicate child category: {$name}"); }
            if ($ids) {
                $categoryId = (int)$ids[0];
                $verified++;
                $setStatus = "UPDATE `{$prefix}category` SET `parent_id`=?,`status`=1" . ($hasTop ? ",`top`=1" : '') . " WHERE `category_id`=?";
                $pdo->prepare($setStatus)->execute([$parentId, $categoryId]);
            } else {
                $insertCategory->execute([$parentId]);
                $categoryId = (int)$pdo->lastInsertId();
                $created++;
            }
            foreach ($languages as $language) {
                $upsertDescription->execute([$categoryId, (int)$language['language_id'], $name, $name]);
            }
            foreach ($storeIds as $storeId) { $upsertStore->execute([$categoryId, $storeId]); }
            $categoryIdsByName[$name] = $categoryId;
        }
    }

    // Resolve all configured models first and reject ambiguity before editing any product links.
    $modelToCategory = [];
    foreach ($taxonomy as $children) {
        foreach ($children as $categoryName => $models) {
            foreach ($models as $model) {
                if (isset($modelToCategory[$model])) { throw new RuntimeException("Model mapped more than once: {$model}"); }
                $modelToCategory[$model] = $categoryIdsByName[$categoryName];
            }
        }
    }
    $findProduct = $pdo->prepare("SELECT `product_id` FROM `{$prefix}product` WHERE `model`=? LIMIT 2");
    $missing = [];
    $mapped = [];
    $removeBranchLinks = $pdo->prepare("DELETE p2c FROM `{$prefix}product_to_category` p2c JOIN `{$prefix}category_path` cp ON cp.`category_id`=p2c.`category_id` WHERE p2c.`product_id`=? AND cp.`path_id` IN (" . implode(',', array_map('intval', array_values($rootIds))) . ")");
    $addCategory = $pdo->prepare("INSERT INTO `{$prefix}product_to_category` (`product_id`,`category_id`) VALUES (?,?) ON DUPLICATE KEY UPDATE `category_id`=VALUES(`category_id`)");
    foreach ($modelToCategory as $model => $categoryId) {
        $findProduct->execute([$model]);
        $ids = $findProduct->fetchAll(PDO::FETCH_COLUMN);
        if (count($ids) > 1) { throw new RuntimeException("Duplicate product model in database: {$model}"); }
        if (!$ids) { $missing[] = $model; continue; }
        $productId = (int)$ids[0];
        $removeBranchLinks->execute([$productId]);
        $addCategory->execute([$productId, $categoryId]);
        $mapped[] = ['model' => $model, 'product_id' => $productId, 'category_id' => $categoryId];
    }

    // Disable obsolete direct children under the four rewritten roots; their IDs and descriptions remain intact.
    $activeChildIds = array_values($categoryIdsByName);
    $inClause = implode(',', array_fill(0, count($activeChildIds), '?'));
    $setInactiveSql = "UPDATE `{$prefix}category` SET `status`=0 WHERE `parent_id` IN (" . implode(',', array_map('intval', array_values($rootIds))) . ") AND `category_id` NOT IN ({$inClause})";
    $deactivate = $pdo->prepare($setInactiveSql);
    $deactivate->execute($activeChildIds);
    $deactivated = $deactivate->rowCount();

    // Rebuild closure paths after the category changes, with cycle detection.
    $all = $pdo->query("SELECT `category_id`,`parent_id` FROM `{$prefix}category`")->fetchAll();
    $parents = [];
    foreach ($all as $row) { $parents[(int)$row['category_id']] = (int)$row['parent_id']; }
    $pathRows = [];
    foreach ($parents as $id => $_parent) {
        $chain = [];
        $current = $id;
        $seen = [];
        while ($current > 0) {
            if (isset($seen[$current])) { throw new RuntimeException("Category parent cycle at ID {$current}"); }
            $seen[$current] = true;
            $chain[] = $current;
            $current = $parents[$current] ?? 0;
        }
        foreach (array_reverse($chain) as $level => $pathId) { $pathRows[] = [$id, $pathId, $level]; }
    }
    $pdo->exec("DELETE FROM `{$prefix}category_path`");
    $insertPath = $pdo->prepare("INSERT INTO `{$prefix}category_path` (`category_id`,`path_id`,`level`) VALUES (?,?,?)");
    foreach ($pathRows as $row) { $insertPath->execute($row); }

    $pdo->commit();

    $cacheDir = rtrim(DIR_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'cache';
    $cacheDeleted = 0;
    $cacheFailed = [];
    if (is_dir($cacheDir)) {
        foreach (new DirectoryIterator($cacheDir) as $entry) {
            if ($entry->isDot() || !$entry->isFile() && !$entry->isLink()) { continue; }
            if (str_starts_with($entry->getFilename(), 'cache.category.') || str_starts_with($entry->getFilename(), 'cache.product.')) {
                if (@unlink($entry->getPathname())) { $cacheDeleted++; } else { $cacheFailed[] = $entry->getFilename(); }
            }
        }
    }
    $report = [
        'created_subcategories' => $created,
        'verified_subcategories' => $verified,
        'active_subcategories' => count($categoryIdsByName),
        'obsolete_categories_disabled' => $deactivated,
        'products_mapped' => count($mapped),
        'models_not_found' => $missing,
        'cache_files_deleted' => $cacheDeleted,
        'cache_files_not_deleted' => $cacheFailed,
        'categories' => array_map(static fn(string $root, array $children): array => ['parent' => $root, 'parent_id' => $rootIds[$root], 'children' => array_map(static fn(string $name): array => ['name' => $name, 'category_id' => $categoryIdsByName[$name]], array_keys($children))], array_keys($taxonomy), array_values($taxonomy)),
        'product_assignments' => $mapped,
    ];
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) { $pdo->rollBack(); }
    fwrite(STDERR, 'Migration failed; transaction rolled back: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
