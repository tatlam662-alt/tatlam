<?php
declare(strict_types=1);

/**
 * Idempotent PanDaShop category rename and multibrand catalog import.
 * Run from project root: C:\\xampp\\php\\php.exe tools\\import-pandashop-multibrand-products.php
 * This script is deliberately CLI-only; do not expose it through the web server.
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only. Run from a terminal.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/upload/config.php';

$renames = [
    'Flycam DJI' => 'Flycam & Drone',
    'Âm thanh DJI' => 'Thiết bị Âm thanh',
    'Phụ kiện DJI' => 'Phụ kiện Công nghệ',
];

$products = [
    [
        'model' => 'FLY-AUTEL-01', 'manufacturer' => 'Autel Robotics', 'category' => 'Flycam & Drone',
        'name' => 'Flycam Autel EVO Lite+ Premium Bundle', 'price' => 28990000,
        'image_url' => 'https://m.media-amazon.com/images/I/71rS3M5pvhL._AC_SL1500_.jpg',
        'description' => '<p>Flycam Autel EVO Lite+ trang bị cảm biến CMOS 1 inch cùng thuật toán quay đêm Moonlight AI, hỗ trợ quay phim 6K/30fps trong điều kiện thiếu sáng.</p>',
        'attributes' => ['Cảm biến' => '1-inch CMOS (Khẩu độ f/2.8–f/11)', 'Độ phân giải video' => '6K/30fps, 4K/60fps', 'Kháng gió' => 'Cấp 7 (15 m/s)', 'Thời gian bay' => '40 phút'],
    ],
    [
        'model' => 'FLY-BETA-01', 'manufacturer' => 'BetaFPV', 'category' => 'Flycam & Drone',
        'name' => 'Bộ Flycam FPV BetaFPV Cetus Pro FPV Kit', 'price' => 5290000,
        'image_url' => 'https://m.media-amazon.com/images/I/61NfT+7aK7L._AC_SL1000_.jpg',
        'description' => '<p>Combo FPV tập bay tích hợp kính thực tế ảo VR02, tay điều khiển LiteRadio 2 SE và khung drone bền, phù hợp cho người mới làm quen với FPV.</p>',
        'attributes' => ['Dòng máy' => 'FPV Racing / Whoop', 'Động cơ' => 'Brushless (không chổi than)', 'Kính FPV' => 'VR02 FPV Goggles 5.8G', 'Pin' => '1S 450mAh (bay 4–5 phút mỗi viên)'],
    ],
    [
        'model' => 'AUD-RODE-01', 'manufacturer' => 'RØDE', 'category' => 'Thiết bị Âm thanh',
        'name' => 'Micro Không Dây RØDE Wireless PRO System', 'price' => 11890000,
        'image_url' => 'https://m.media-amazon.com/images/I/61+RshW2GUL._AC_SL1500_.jpg',
        'description' => '<p>Hệ thống micro không dây chuyên nghiệp với khả năng ghi âm nội bộ 32-bit float, hỗ trợ đồng bộ timecode cho quy trình quay và hậu kỳ.</p>',
        'attributes' => ['Công nghệ ghi âm' => '32-bit float On-board Recording', 'Bộ nhớ trong' => '32GB (lưu tới 40 giờ audio)', 'Khoảng cách truyền' => '260 m (đường truyền thẳng)', 'Đồng bộ thời gian' => 'Timecode Sync tích hợp'],
    ],
    [
        'model' => 'AUD-SHURE-01', 'manufacturer' => 'Shure', 'category' => 'Thiết bị Âm thanh',
        'name' => 'Micro Podcasting & Streamer Shure MV7+ USB/XLR', 'price' => 7990000,
        'image_url' => 'https://m.media-amazon.com/images/I/61x5J6Z+pLL._AC_SL1500_.jpg',
        'description' => '<p>Micro dynamic dành cho podcast và livestream, hỗ trợ đồng thời kết nối USB-C và XLR, tích hợp xử lý âm thanh số và công cụ giảm tiếng bật hơi.</p>',
        'attributes' => ['Loại micro' => 'Dynamic (định hướng Cardioid)', 'Cổng kết nối' => 'USB-C và XLR', 'Dải tần số' => '50 Hz–16.000 Hz', 'Tính năng' => 'DSP Auto Level Mode, Digital Popper Stopper'],
    ],
    [
        'model' => 'AUD-JBL-01', 'manufacturer' => 'JBL', 'category' => 'Thiết bị Âm thanh',
        'name' => 'Loa Bluetooth Di Động JBL Charge 5', 'price' => 3690000,
        'image_url' => 'https://m.media-amazon.com/images/I/71YvR29PClL._AC_SL1500_.jpg',
        'description' => '<p>Loa di động JBL Original Pro Sound đạt chuẩn chống nước bụi IP67, phù hợp sử dụng ngoài trời và có thể sạc dự phòng cho thiết bị di động.</p>',
        'attributes' => ['Công suất' => '40W RMS', 'Chống nước và bụi' => 'IP67', 'Thời lượng pin' => 'Tối đa 20 giờ', 'Kết nối' => 'Bluetooth 5.1, JBL PartyBoost'],
    ],
    [
        'model' => 'ACC-ANKER-01', 'manufacturer' => 'Anker', 'category' => 'Phụ kiện Công nghệ',
        'name' => 'Củ Sạc Nhanh Anker Prime 67W GaN 3 Cổng', 'price' => 1290000,
        'image_url' => 'https://m.media-amazon.com/images/I/61mI8mI-HNL._AC_SL1500_.jpg',
        'description' => '<p>Củ sạc GaN nhỏ gọn, hỗ trợ sạc nhanh đồng thời laptop, máy tính bảng và điện thoại với tổng công suất tối đa 67W.</p>',
        'attributes' => ['Công suất tối đa' => '67W PowerIQ 4.0', 'Công nghệ' => 'GaNPrime', 'Cổng sạc' => '2 USB-C và 1 USB-A', 'Bảo vệ an toàn' => 'ActiveShield 2.0 kiểm soát nhiệt độ'],
    ],
    [
        'model' => 'ACC-UGREEN-01', 'manufacturer' => 'Ugreen', 'category' => 'Phụ kiện Công nghệ',
        'name' => 'Hub Chuyển Đổi Ugreen 7-in-1 USB-C Dual 4K 60Hz HDMI PD 100W', 'price' => 990000,
        'image_url' => 'https://m.media-amazon.com/images/I/61S1qC2yTLL._AC_SL1500_.jpg',
        'description' => '<p>Hub USB-C 7 trong 1 vỏ nhôm, hỗ trợ xuất hình 4K 60Hz, truyền dữ liệu tốc độ cao và sạc Power Delivery cho laptop tương thích.</p>',
        'attributes' => ['Cổng xuất hình' => 'HDMI 4K@60Hz', 'Cổng sạc' => 'USB-C Power Delivery 100W', 'Tốc độ truyền dữ liệu' => 'USB 3.0 (5Gbps)', 'Chất liệu' => 'Hợp kim nhôm'],
    ],
    [
        'model' => 'ACC-SMALL-01', 'manufacturer' => 'SmallRig', 'category' => 'Phụ kiện Công nghệ',
        'name' => 'Khung Bảo Vệ Rig SmallRig Cage for Sony A7 IV / A7S III / A1', 'price' => 1590000,
        'image_url' => 'https://m.media-amazon.com/images/I/61MvU+i3S9L._AC_SL1200_.jpg',
        'description' => '<p>Khung bảo vệ hợp kim nhôm dành cho máy ảnh Sony Alpha, mở rộng điểm gắn phụ kiện và hỗ trợ tháo lắp nhanh với ngàm Arca-Swiss.</p>',
        'attributes' => ['Chất liệu' => 'Hợp kim nhôm Aluminum Alloy', 'Tương thích' => 'Sony Alpha 7 IV, Alpha 7S III, Alpha 1', 'Tích hợp' => 'Ngàm Arca-Swiss Quick Release'],
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
    $languages = $pdo->query("SELECT `language_id`, `code` FROM `{$prefix}language` WHERE `status` = 1")->fetchAll();
    $languageIds = array_map(static fn(array $row): int => (int)$row['language_id'], $languages);
    $viLanguageId = 0;
    foreach ($languages as $language) {
        if ($language['code'] === 'vi-vn') {
            $viLanguageId = (int)$language['language_id'];
            break;
        }
    }
    if ($viLanguageId === 0 || $languageIds === []) {
        throw new RuntimeException('Enabled vi-vn language was not found.');
    }

    $imageDirectory = DIR_IMAGE . 'catalog/pandashop-multibrand/';
    if (!is_dir($imageDirectory) && !mkdir($imageDirectory, 0755, true) && !is_dir($imageDirectory)) {
        throw new RuntimeException('Could not create product image directory.');
    }
    $images = [];
    $fallbackImage = is_file(DIR_IMAGE . 'no_image.png') ? 'no_image.png' : (is_file(DIR_IMAGE . 'placeholder.png') ? 'placeholder.png' : '');
    if ($fallbackImage === '') {
        throw new RuntimeException('No local OpenCart fallback image was found.');
    }
    $existingImageQuery = $pdo->prepare("SELECT `image` FROM `{$prefix}product` WHERE `model` = ? LIMIT 1");
    foreach ($products as $product) {
        $path = (string)parse_url($product['image_url'], PHP_URL_PATH);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = 'jpg';
        }
        $fileName = strtolower(str_replace('-', '_', $product['model'])) . '.' . $extension;
        $target = $imageDirectory . $fileName;
        $relative = 'catalog/pandashop-multibrand/' . $fileName;
        if (is_file($target) && @getimagesize($target) !== false) {
            $images[$product['model']] = $relative;
            continue;
        }
        $downloaded = false;
        if (function_exists('curl_init')) {
            $temp = $target . '.part';
            $handle = @fopen($temp, 'wb');
            if ($handle !== false) {
                $curl = curl_init($product['image_url']);
                curl_setopt_array($curl, [CURLOPT_FILE => $handle, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_TIMEOUT => 15, CURLOPT_USERAGENT => 'PanDaShop Catalog Import/1.0', CURLOPT_FAILONERROR => true, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS]);
                $ok = curl_exec($curl);
                $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                fclose($handle);
                if ($ok === true && $status >= 200 && $status < 300 && is_file($temp) && filesize($temp) > 256 && @getimagesize($temp) !== false && @rename($temp, $target)) {
                    $downloaded = true;
                } else {
                    @unlink($temp);
                }
            }
        }
        if ($downloaded) {
            $images[$product['model']] = $relative;
        } else {
            $existingImageQuery->execute([$product['model']]);
            $existing = (string)$existingImageQuery->fetchColumn();
            $images[$product['model']] = $existing !== '' && is_file(DIR_IMAGE . $existing) ? $existing : $fallbackImage;
            fwrite(STDERR, "WARNING: source image unavailable for {$product['model']}; using {$images[$product['model']]}.\n");
        }
    }

    $pdo->beginTransaction();

    // Resolve the Vietnamese category IDs before renaming, then update every language row for those IDs.
    $categoryIdByName = $pdo->prepare("SELECT c.`category_id` FROM `{$prefix}category` c INNER JOIN `{$prefix}category_description` cd ON cd.`category_id`=c.`category_id` WHERE cd.`language_id`=? AND cd.`name`=? AND c.`parent_id`=0 LIMIT 2");
    $categoryNames = [];
    foreach ($renames as $oldName => $newName) {
        $categoryIdByName->execute([$viLanguageId, $oldName]);
        $ids = $categoryIdByName->fetchAll(PDO::FETCH_COLUMN);
        if (count($ids) === 0) {
            $categoryIdByName->execute([$viLanguageId, $newName]);
            $ids = $categoryIdByName->fetchAll(PDO::FETCH_COLUMN);
        }
        if (count($ids) !== 1) {
            throw new RuntimeException("Could not uniquely resolve root category {$oldName}.");
        }
        $categoryNames[$newName] = (int)$ids[0];
        $updateCategoryName = $pdo->prepare("UPDATE `{$prefix}category_description` SET `name`=? WHERE `category_id`=? AND `name` IN (?,?)");
        $updateCategoryName->execute([$newName, (int)$ids[0], $oldName, $newName]);
    }

    $stockStatusQuery = $pdo->prepare("SELECT `value` FROM `{$prefix}setting` WHERE `store_id`=0 AND `key`='config_stock_status_id' LIMIT 1");
    $stockStatusQuery->execute();
    $stockStatusId = (int)$stockStatusQuery->fetchColumn();
    if ($stockStatusId < 1) {
        $stockStatusQuery = $pdo->prepare("SELECT `stock_status_id` FROM `{$prefix}stock_status` WHERE `language_id`=? ORDER BY `stock_status_id` LIMIT 1");
        $stockStatusQuery->execute([$viLanguageId]);
        $stockStatusId = (int)$stockStatusQuery->fetchColumn();
    }
    if ($stockStatusId < 1) {
        throw new RuntimeException('No stock status is configured.');
    }

    $attributeGroupName = 'Thông số kỹ thuật';
    $groupQuery = $pdo->prepare("SELECT ag.`attribute_group_id` FROM `{$prefix}attribute_group` ag INNER JOIN `{$prefix}attribute_group_description` agd ON agd.`attribute_group_id`=ag.`attribute_group_id` WHERE agd.`language_id`=? AND agd.`name`=? LIMIT 1");
    $groupQuery->execute([$viLanguageId, $attributeGroupName]);
    $attributeGroupId = (int)$groupQuery->fetchColumn();
    if ($attributeGroupId === 0) {
        $pdo->exec("INSERT INTO `{$prefix}attribute_group` (`sort_order`) VALUES (0)");
        $attributeGroupId = (int)$pdo->lastInsertId();
        $insertGroupDescription = $pdo->prepare("INSERT INTO `{$prefix}attribute_group_description` (`attribute_group_id`,`language_id`,`name`) VALUES (?,?,?)");
        foreach ($languageIds as $id) {
            $insertGroupDescription->execute([$attributeGroupId, $id, $attributeGroupName]);
        }
    }

    $manufacturerQuery = $pdo->prepare("SELECT `manufacturer_id` FROM `{$prefix}manufacturer` WHERE `name`=? LIMIT 2");
    $productQuery = $pdo->prepare("SELECT `product_id` FROM `{$prefix}product` WHERE `model`=? LIMIT 2");
    $attributeQuery = $pdo->prepare("SELECT a.`attribute_id` FROM `{$prefix}attribute` a INNER JOIN `{$prefix}attribute_description` ad ON ad.`attribute_id`=a.`attribute_id` WHERE a.`attribute_group_id`=? AND ad.`language_id`=? AND ad.`name`=? LIMIT 1");
    $report = ['category_renames' => [], 'inserted' => 0, 'updated' => 0, 'products' => []];
    foreach ($categoryNames as $name => $categoryId) {
        $report['category_renames'][] = ['category_id' => $categoryId, 'name' => $name];
    }

    foreach ($products as $product) {
        $categoryId = $categoryNames[$product['category']] ?? 0;
        if ($categoryId === 0) {
            throw new RuntimeException('Category lookup failed: ' . $product['category']);
        }

        $manufacturerQuery->execute([$product['manufacturer']]);
        $manufacturerIds = $manufacturerQuery->fetchAll(PDO::FETCH_COLUMN);
        if (count($manufacturerIds) > 1) {
            throw new RuntimeException('Duplicate manufacturer name: ' . $product['manufacturer']);
        }
        $manufacturerId = (int)($manufacturerIds[0] ?? 0);
        if ($manufacturerId === 0) {
            $pdo->prepare("INSERT INTO `{$prefix}manufacturer` (`name`,`image`,`sort_order`) VALUES (?, '', 0)")->execute([$product['manufacturer']]);
            $manufacturerId = (int)$pdo->lastInsertId();
        }
        $pdo->prepare("INSERT INTO `{$prefix}manufacturer_to_store` (`manufacturer_id`,`store_id`) VALUES (?,0) ON DUPLICATE KEY UPDATE `store_id`=VALUES(`store_id`)")->execute([$manufacturerId]);

        $productQuery->execute([$product['model']]);
        $productIds = $productQuery->fetchAll(PDO::FETCH_COLUMN);
        if (count($productIds) > 1) {
            throw new RuntimeException('Duplicate model found: ' . $product['model']);
        }
        $productId = (int)($productIds[0] ?? 0);
        if ($productId > 0) {
            $pdo->prepare("UPDATE `{$prefix}product` SET `quantity`=100, `stock_status_id`=?, `image`=?, `manufacturer_id`=?, `shipping`=1, `price`=?, `status`=1, `date_modified`=NOW() WHERE `product_id`=?")->execute([$stockStatusId, $images[$product['model']], $manufacturerId, $product['price'], $productId]);
            $report['updated']++;
            $action = 'updated';
        } else {
            $pdo->prepare("INSERT INTO `{$prefix}product` (`model`,`location`,`variant`,`override`,`quantity`,`stock_status_id`,`image`,`manufacturer_id`,`shipping`,`price`,`points`,`tax_class_id`,`date_available`,`weight`,`weight_class_id`,`length`,`width`,`height`,`length_class_id`,`subtract`,`minimum`,`rating`,`sort_order`,`status`,`date_added`,`date_modified`) VALUES (?, '', '', '', 100, ?, ?, ?, 1, ?, 0, 0, CURDATE(), 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 1, NOW(), NOW())")->execute([$product['model'], $stockStatusId, $images[$product['model']], $manufacturerId, $product['price']]);
            $productId = (int)$pdo->lastInsertId();
            $report['inserted']++;
            $action = 'inserted';
        }

        // The source description is already trusted, curated HTML; retain it and append escaped specification values.
        $description = $product['description'] . '<h3>Thông số kỹ thuật</h3><ul>';
        foreach ($product['attributes'] as $label => $value) {
            $description .= '<li><strong>' . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ':</strong> ' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
        }
        $description .= '</ul>';
        $saveDescription = $pdo->prepare("INSERT INTO `{$prefix}product_description` (`product_id`,`language_id`,`name`,`description`,`tag`,`meta_title`,`meta_description`,`meta_keyword`) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`),`description`=VALUES(`description`),`tag`=VALUES(`tag`),`meta_title`=VALUES(`meta_title`),`meta_description`=VALUES(`meta_description`),`meta_keyword`=VALUES(`meta_keyword`)");
        foreach ($languageIds as $id) {
            $saveDescription->execute([$productId, $id, $product['name'], $description, $product['name'], $product['name'], '', '']);
        }

        $pdo->prepare("DELETE FROM `{$prefix}product_to_category` WHERE `product_id`=?")->execute([$productId]);
        $pdo->prepare("INSERT INTO `{$prefix}product_to_category` (`product_id`,`category_id`) VALUES (?,?)")->execute([$productId, $categoryId]);
        $pdo->prepare("INSERT INTO `{$prefix}product_to_store` (`product_id`,`store_id`) VALUES (?,0) ON DUPLICATE KEY UPDATE `store_id`=VALUES(`store_id`)")->execute([$productId]);
        $pdo->prepare("DELETE FROM `{$prefix}product_attribute` WHERE `product_id`=?")->execute([$productId]);
        foreach ($product['attributes'] as $attributeName => $attributeValue) {
            $attributeQuery->execute([$attributeGroupId, $viLanguageId, $attributeName]);
            $attributeId = (int)$attributeQuery->fetchColumn();
            if ($attributeId === 0) {
                $pdo->prepare("INSERT INTO `{$prefix}attribute` (`attribute_group_id`,`sort_order`) VALUES (?,0)")->execute([$attributeGroupId]);
                $attributeId = (int)$pdo->lastInsertId();
                $insertAttributeDescription = $pdo->prepare("INSERT INTO `{$prefix}attribute_description` (`attribute_id`,`language_id`,`name`) VALUES (?,?,?)");
                foreach ($languageIds as $id) {
                    $insertAttributeDescription->execute([$attributeId, $id, $attributeName]);
                }
            }
            $saveAttributeValue = $pdo->prepare("INSERT INTO `{$prefix}product_attribute` (`product_id`,`attribute_id`,`language_id`,`text`) VALUES (?,?,?,?)");
            foreach ($languageIds as $id) {
                $saveAttributeValue->execute([$productId, $attributeId, $id, $attributeValue]);
            }
        }
        $report['products'][] = ['model' => $product['model'], 'product_id' => $productId, 'manufacturer' => $product['manufacturer'], 'category' => $product['category'], 'action' => $action, 'attributes' => count($product['attributes']), 'image' => $images[$product['model']]];
    }

    $pdo->commit();

    $cacheWarnings = [];
    $cacheDirectory = rtrim(DIR_STORAGE, '/\\') . DIRECTORY_SEPARATOR . 'cache';
    foreach (glob($cacheDirectory . DIRECTORY_SEPARATOR . 'cache.product.*') ?: [] as $cacheFile) {
        if (is_file($cacheFile) && !@unlink($cacheFile)) {
            $cacheWarnings[] = basename($cacheFile);
        }
    }
    $report['cache_warnings'] = $cacheWarnings;
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Import failed; database transaction rolled back: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
