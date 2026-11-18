<?php
declare(strict_types=1);

/**
 * CLI-only, idempotent importer for the 13 supplemental PanDaShop products.
 * Run from the project root: C:\xampp\php\php.exe tools\import-pandashop-supplemental-products.php
 */
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "This importer can only be run from the command line.\n");
	exit(1);
}

$root = dirname(__DIR__);
$configFile = $root . '/upload/config.php';
if (!is_file($configFile)) {
	fwrite(STDERR, "OpenCart config not found: {$configFile}\n");
	exit(1);
}
require_once $configFile;

$products = [
	[
		'model' => 'AUDIO-DJI-01', 'manufacturer' => 'DJI', 'category' => 'Thiết bị Âm thanh',
		'name' => 'Micro Không Dây DJI Mic 2 (1 TX + 1 RX)', 'price' => 5490000,
		'image_url' => 'https://dji-official-fe.djicdn.com/cms/uploads/d765e90d79d6756e1b6f0e9b92f72e3a.png',
		'description' => '<p>Micro thu âm đơn gọn nhẹ cho vlogger, chống ồn thông minh AI, hỗ trợ ghi âm nội bộ 32-bit float.</p>',
		'attributes' => ['Loại' => 'Micro không dây', 'Thời lượng pin' => '6 giờ (18 giờ kèm hộp)', 'Khoảng cách truyền' => '250 m', 'Tần số' => '2.4 GHz']
	],
	[
		'model' => 'AUDIO-DJI-02', 'manufacturer' => 'DJI', 'category' => 'Thiết bị Âm thanh',
		'name' => 'Micro Thu Âm DJI Mic Mini (Compact Wireless)', 'price' => 2890000,
		'image_url' => 'https://dji-official-fe.djicdn.com/cms/uploads/d765e90d79d6756e1b6f0e9b92f72e3a.png',
		'description' => '<p>Dòng micro siêu nhỏ nhẹ chỉ 10 g, kẹp áo tiện lợi, kết nối trực tiếp Bluetooth với điện thoại và Osmo Pocket 3.</p>',
		'attributes' => ['Trọng lượng' => '10 g', 'Kết nối' => 'Bluetooth / 2.4 GHz', 'Pin' => '5.5 giờ']
	],
	[
		'model' => 'ACC-DJI-01', 'manufacturer' => 'DJI', 'category' => 'Phụ kiện Công nghệ',
		'name' => 'Pin Flycam DJI Mini 4 Pro / Mini 3 Series Intelligent Flight Battery Plus', 'price' => 2190000,
		'image_url' => 'https://dji-official-fe.djicdn.com/cms/uploads/22378f8770d4948a33502ee2bf21d8fa.png',
		'description' => '<p>Pin dung lượng cao gia tăng thời gian bay lên tới 45 phút cho dòng DJI Mini 3 và Mini 4 Pro.</p>',
		'attributes' => ['Dung lượng' => '3850 mAh', 'Loại pin' => 'Li-ion 2S', 'Thời gian bay tối đa' => '45 phút']
	],
	[
		'model' => 'ACC-DJI-02', 'manufacturer' => 'SanDisk', 'category' => 'Phụ kiện Công nghệ',
		'name' => 'Thẻ nhớ MicroSDXC SanDisk Extreme Pro 256GB V30 A2 200MB/s', 'price' => 890000,
		'image_url' => 'https://images.sandisk.com/is/image/sandisk/sandisk-extreme-pro-uhs-i-microsd-256gb',
		'description' => '<p>Thẻ nhớ chuyên dụng cho Flycam và Camera Action quay video 4K/60fps mượt mà, hạn chế giật lag.</p>',
		'attributes' => ['Dung lượng' => '256 GB', 'Tốc độ đọc' => '200 MB/s', 'Tốc độ ghi' => '140 MB/s', 'Chuẩn' => 'U3 V30 A2 MicroSDXC']
	],
	[
		'model' => 'ACC-DJI-03', 'manufacturer' => 'DJI', 'category' => 'Phụ kiện Công nghệ',
		'name' => 'Bộ Lọc Kính ND Filter Set DJI Air 3S (ND8/16/32/64)', 'price' => 2290000,
		'image_url' => 'https://dji-official-fe.djicdn.com/cms/uploads/a832621ec6cf6970bc23bc7a1a4b4ee9.png',
		'description' => '<p>Bộ kính lọc giảm sáng cao cấp giúp kiểm soát tốc độ màn trập mượt mà khi quay phim dưới nắng gắt.</p>',
		'attributes' => ['Bộ sản phẩm' => 'ND8, ND16, ND32, ND64', 'Chất liệu' => 'Thủy tinh quang học cao cấp']
	],
	[
		'model' => 'HP-RAZER-01', 'manufacturer' => 'Razer', 'category' => 'Tai nghe gaming',
		'name' => 'Tai nghe Không Dây Razer BlackShark V2 Pro (2023 Edition)', 'price' => 4990000,
		'image_url' => 'https://assets2.razerzone.com/images/pnx.assets/f3050a4d53edeb8142337d111e15e8bb/razer-blackshark-v2-pro-2023-500x500.png',
		'description' => '<p>Tai nghe Esports chuyên nghiệp với Micro HyperClear Super Wideband và thời lượng pin lên tới 70 giờ.</p>',
		'attributes' => ['Kết nối' => 'Wireless 2.4 GHz Razer HyperSpeed / Bluetooth 5.2', 'Driver' => 'TriForce Titanium 50 mm', 'Pin' => '70 giờ']
	],
	[
		'model' => 'HP-SONY-01', 'manufacturer' => 'Sony', 'category' => 'Tai nghe gaming',
		'name' => 'Tai nghe Gaming Không Dây Sony INZONE H9 ANC', 'price' => 5990000,
		'image_url' => 'https://m.media-amazon.com/images/I/61pB3d2bKGL._AC_SL1500_.jpg',
		'description' => '<p>Tai nghe chống ồn chủ động ANC, giả lập âm thanh vòm 360 Spatial Sound for Gaming.</p>',
		'attributes' => ['Công nghệ' => 'Chống ồn chủ động ANC', 'Thời lượng pin' => '32 giờ', 'Âm thanh' => '360 Spatial Sound']
	],
	[
		'model' => 'HP-CORSAIR-01', 'manufacturer' => 'Corsair', 'category' => 'Tai nghe gaming',
		'name' => 'Tai nghe Corsair HS80 RGB Wireless Spatial Audio', 'price' => 3490000,
		'image_url' => 'https://res.cloudinary.com/corsair-pwa/image/upload/HS80_RGB_WIRELESS_01.png',
		'description' => '<p>Tai nghe thiết kế đệm vải thoáng khí, tích hợp âm thanh Dolby Atmos 24-bit/96 kHz chân thực.</p>',
		'attributes' => ['Chuẩn âm thanh' => 'Dolby Atmos', 'Tần số đáp ứng' => '20 Hz - 40 kHz', 'Driver' => 'Neodymium 50 mm Custom']
	],
	[
		'model' => 'KIT-PANDA-01', 'manufacturer' => 'PanDaShop', 'category' => 'Bộ kit / Combo',
		'name' => 'Trọn Bộ PC Gaming Neon Chiến Game PANDA NEON 01 (i7 14700K / RTX 4070 Super / 32GB RAM)', 'price' => 45990000,
		'image_url' => 'https://dlcdnrog.asus.com/rog/media/1704764491717.webp',
		'description' => '<p>Bộ PC Gaming dựng sẵn, cân bằng hiệu năng và thẩm mỹ đèn LED Neon, phù hợp chơi game AAA ở độ phân giải 2K/4K.</p>',
		'attributes' => ['CPU' => 'Intel Core i7 14700K', 'VGA' => 'ASUS ROG RTX 4070 Super 12GB', 'RAM' => '32GB DDR5 Corsair RGB', 'SSD' => '1TB NVMe']
	],
	[
		'model' => 'KIT-PANDA-02', 'manufacturer' => 'PanDaShop', 'category' => 'Bộ kit / Combo',
		'name' => 'Combo Streamer & Creator Pro (DJI Osmo Pocket 3 + DJI Mic 2)', 'price' => 20990000,
		'image_url' => 'https://dji-official-fe.djicdn.com/cms/uploads/9b4764b85c276f5367b6139cdd9493f1.png',
		'description' => '<p>Bộ đôi dành cho nhà sáng tạo nội dung, vlog, TikTok và livestream với hình ảnh 4K cùng âm thanh không dây chuẩn studio.</p>',
		'attributes' => ['Camera' => 'Osmo Pocket 3 Creator Combo', 'Âm thanh' => 'DJI Mic 2 Wireless', 'Quà tặng' => 'Thẻ nhớ 128GB High Speed']
	],
	[
		'model' => 'KIT-RAZER-01', 'manufacturer' => 'Razer', 'category' => 'Bộ kit / Combo',
		'name' => 'Bộ Combo Gaming Gear Razer Quartz Pink (Bàn phím + Chuột + Tai nghe + Lót chuột)', 'price' => 8990000,
		'image_url' => 'https://assets2.razerzone.com/images/pnx.assets/d244ffb00712711d999ed65d06b6e41b/razer-viper-v3-pro-black-500x500.png',
		'description' => '<p>Trọn bộ gear màu Quartz Pink dành cho streamer, gồm bàn phím cơ BlackWidow, chuột Viper, tai nghe Kraken và lót chuột.</p>',
		'attributes' => ['Màu sắc' => 'Quartz Pink', 'Bao gồm' => '4 món gear Razer']
	],
	[
		'model' => 'MS-LOGI-02', 'manufacturer' => 'Logitech', 'category' => 'Chuột gaming',
		'name' => 'Chuột Không Dây Logitech G502 X LIGHTSPEED', 'price' => 3190000,
		'image_url' => 'https://resource.logitechg.com/w_1000,c_limit,q_auto,f_auto,dpr_2.0/d_transparent.gif/content/dam/gaming/en/products/g502x-lightspeed/gallery/g502x-lightspeed-gallery-1-black.png',
		'description' => '<p>Chuột gaming với switch quang-cơ LIGHTFORCE và cảm biến HERO 25K chính xác.</p>',
		'attributes' => ['Switch' => 'LIGHTFORCE Hybrid', 'Cảm biến' => 'HERO 25K', 'Trọng lượng' => '102 g']
	],
	[
		'model' => 'KB-ASUS-01', 'manufacturer' => 'ASUS', 'category' => 'Bàn phím gaming',
		'name' => 'Bàn Phím Cơ ASUS ROG Azoth Wireless Custom 75%', 'price' => 6490000,
		'image_url' => 'https://dlcdnrog.asus.com/rog/media/1673024888921.webp',
		'description' => '<p>Bàn phím cơ custom cao cấp trang bị màn hình OLED, núm xoay 3 chiều, gasket mount và switch ROG NX pre-lubed.</p>',
		'attributes' => ['Màn hình' => 'OLED 2 inch', 'Layout' => '75% Custom', 'Kết nối' => 'Tri-Mode (2.4 GHz / Bluetooth / Cable)']
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
	$languageId = 0;
	foreach ($languages as $language) {
		if ($language['code'] === 'vi-vn') {
			$languageId = (int)$language['language_id'];
		}
	}
	if (!$languageId) {
		throw new RuntimeException('Enabled vi-vn language was not found.');
	}
} catch (Throwable $exception) {
	fwrite(STDERR, 'Database connection/setup failed: ' . $exception->getMessage() . PHP_EOL);
	exit(1);
}

$imageDirectory = DIR_IMAGE . 'catalog/pandashop-supplemental/';
if (!is_dir($imageDirectory) && !mkdir($imageDirectory, 0755, true) && !is_dir($imageDirectory)) {
	fwrite(STDERR, "Cannot create image directory.\n");
	exit(1);
}

// Resolve images before opening the transaction. Failed external URLs use the current image or OpenCart's no-image asset.
$images = [];
$existingImageQuery = $pdo->prepare("SELECT `image` FROM `{$prefix}product` WHERE `model` = ? LIMIT 1");
foreach ($products as $product) {
	$extension = strtolower(pathinfo((string)parse_url($product['image_url'], PHP_URL_PATH), PATHINFO_EXTENSION));
	if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
		$extension = 'jpg';
	}
	$fileName = strtolower(str_replace('-', '_', $product['model'])) . '.' . $extension;
	$target = $imageDirectory . $fileName;
	$relative = 'catalog/pandashop-supplemental/' . $fileName;
	if (is_file($target) && @getimagesize($target) !== false) {
		$images[$product['model']] = $relative;
		continue;
	}
	$downloaded = false;
	if (function_exists('curl_init')) {
		$temp = $target . '.part';
		$handle = fopen($temp, 'wb');
		if ($handle !== false) {
			$curl = curl_init($product['image_url']);
			curl_setopt_array($curl, [CURLOPT_FILE => $handle, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 4, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_TIMEOUT => 20, CURLOPT_USERAGENT => 'PanDaShop Catalog Import/1.0', CURLOPT_FAILONERROR => true]);
			$ok = curl_exec($curl);
			$status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			fclose($handle);
			if ($ok === true && $status >= 200 && $status < 300 && is_file($temp) && filesize($temp) > 256 && @getimagesize($temp) !== false && rename($temp, $target)) {
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
		$images[$product['model']] = $existing !== '' && is_file(DIR_IMAGE . $existing)
			? $existing
			: (is_file(DIR_IMAGE . 'no_image.png') ? 'no_image.png' : '');
		if ($images[$product['model']] === '') {
			throw new RuntimeException("Image unavailable and no local fallback exists for {$product['model']}.");
		}
		fwrite(STDERR, "WARNING: {$product['model']} uses fallback image {$images[$product['model']]}.\n");
	}
}

try {
	$stockQuery = $pdo->prepare("SELECT `stock_status_id` FROM `{$prefix}stock_status` WHERE `language_id` = ? ORDER BY `stock_status_id` LIMIT 1");
	$stockQuery->execute([$languageId]);
	$stockStatusId = (int)$stockQuery->fetchColumn();
	$attributeGroupQuery = $pdo->prepare("SELECT ag.`attribute_group_id` FROM `{$prefix}attribute_group` ag JOIN `{$prefix}attribute_group_description` agd ON agd.`attribute_group_id`=ag.`attribute_group_id` WHERE agd.`language_id`=? AND agd.`name`=? LIMIT 1");
	$attributeGroupQuery->execute([$languageId, 'Thông số kỹ thuật']);
	$attributeGroupId = (int)$attributeGroupQuery->fetchColumn();
	$categoryQuery = $pdo->prepare("SELECT c.`category_id` FROM `{$prefix}category` c JOIN `{$prefix}category_description` cd ON cd.`category_id`=c.`category_id` WHERE cd.`language_id`=? AND cd.`name`=? AND c.`status`=1 LIMIT 1");
	$manufacturerQuery = $pdo->prepare("SELECT `manufacturer_id` FROM `{$prefix}manufacturer` WHERE `name`=? LIMIT 1");
	$productQuery = $pdo->prepare("SELECT `product_id` FROM `{$prefix}product` WHERE `model`=? LIMIT 2");
	$attributeQuery = $pdo->prepare("SELECT a.`attribute_id` FROM `{$prefix}attribute` a JOIN `{$prefix}attribute_description` ad ON ad.`attribute_id`=a.`attribute_id` WHERE a.`attribute_group_id`=? AND ad.`language_id`=? AND ad.`name`=? LIMIT 1");

	$pdo->beginTransaction();
	if (!$attributeGroupId) {
		$pdo->exec("INSERT INTO `{$prefix}attribute_group` (`sort_order`) VALUES (0)");
		$attributeGroupId = (int)$pdo->lastInsertId();
		$insert = $pdo->prepare("INSERT INTO `{$prefix}attribute_group_description` (`attribute_group_id`,`language_id`,`name`) VALUES (?,?,?)");
		foreach ($languageIds as $id) {
			$insert->execute([$attributeGroupId, $id, 'Thông số kỹ thuật']);
		}
	}

	$report = ['inserted' => 0, 'updated' => 0, 'products' => []];
	foreach ($products as $product) {
		$categoryQuery->execute([$languageId, $product['category']]);
		$categoryId = (int)$categoryQuery->fetchColumn();
		if (!$categoryId) {
			throw new RuntimeException('Enabled category not found: ' . $product['category']);
		}

		$manufacturerQuery->execute([$product['manufacturer']]);
		$manufacturerId = (int)$manufacturerQuery->fetchColumn();
		if (!$manufacturerId) {
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
		if ($productId) {
			$save = $pdo->prepare("UPDATE `{$prefix}product` SET `quantity`=100, `stock_status_id`=?, `image`=?, `manufacturer_id`=?, `shipping`=1, `price`=?, `status`=1, `date_modified`=NOW() WHERE `product_id`=?");
			$save->execute([$stockStatusId, $images[$product['model']], $manufacturerId, $product['price'], $productId]);
			$report['updated']++;
			$action = 'updated';
		} else {
			$save = $pdo->prepare("INSERT INTO `{$prefix}product` (`model`,`location`,`variant`,`override`,`quantity`,`stock_status_id`,`image`,`manufacturer_id`,`shipping`,`price`,`points`,`tax_class_id`,`date_available`,`weight`,`weight_class_id`,`length`,`width`,`height`,`length_class_id`,`subtract`,`minimum`,`rating`,`sort_order`,`status`,`date_added`,`date_modified`) VALUES (?, '', '', '', 100, ?, ?, ?, 1, ?, 0, 0, CURDATE(), 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 1, NOW(), NOW())");
			$save->execute([$product['model'], $stockStatusId, $images[$product['model']], $manufacturerId, $product['price']]);
			$productId = (int)$pdo->lastInsertId();
			$report['inserted']++;
			$action = 'inserted';
		}

		$description = $pdo->prepare("INSERT INTO `{$prefix}product_description` (`product_id`,`language_id`,`name`,`description`,`tag`,`meta_title`,`meta_description`,`meta_keyword`) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`), `tag`=VALUES(`tag`), `meta_title`=VALUES(`meta_title`)");
		foreach ($languageIds as $id) {
			$description->execute([$productId, $id, $product['name'], $product['description'], $product['name'], $product['name'], '', '']);
		}

		$pdo->prepare("DELETE FROM `{$prefix}product_to_category` WHERE `product_id`=?")->execute([$productId]);
		$pdo->prepare("INSERT INTO `{$prefix}product_to_category` (`product_id`,`category_id`) VALUES (?,?)")->execute([$productId, $categoryId]);
		$pdo->prepare("INSERT INTO `{$prefix}product_to_store` (`product_id`,`store_id`) VALUES (?,0) ON DUPLICATE KEY UPDATE `store_id`=VALUES(`store_id`)")->execute([$productId]);
		$pdo->prepare("DELETE FROM `{$prefix}product_attribute` WHERE `product_id`=?")->execute([$productId]);

		foreach ($product['attributes'] as $attributeName => $attributeValue) {
			$attributeQuery->execute([$attributeGroupId, $languageId, $attributeName]);
			$attributeId = (int)$attributeQuery->fetchColumn();
			if (!$attributeId) {
				$pdo->prepare("INSERT INTO `{$prefix}attribute` (`attribute_group_id`,`sort_order`) VALUES (?,0)")->execute([$attributeGroupId]);
				$attributeId = (int)$pdo->lastInsertId();
				$insertAttributeDescription = $pdo->prepare("INSERT INTO `{$prefix}attribute_description` (`attribute_id`,`language_id`,`name`) VALUES (?,?,?)");
				foreach ($languageIds as $id) {
					$insertAttributeDescription->execute([$attributeId, $id, $attributeName]);
				}
			}
			$insertValue = $pdo->prepare("INSERT INTO `{$prefix}product_attribute` (`product_id`,`attribute_id`,`language_id`,`text`) VALUES (?,?,?,?)");
			foreach ($languageIds as $id) {
				$insertValue->execute([$productId, $attributeId, $id, $attributeValue]);
			}
		}
		$report['products'][] = ['model' => $product['model'], 'product_id' => $productId, 'category' => $product['category'], 'manufacturer' => $product['manufacturer'], 'action' => $action, 'attributes' => count($product['attributes'])];
	}
	$pdo->commit();

	if (is_dir(DIR_STORAGE . 'cache/')) {
		foreach (glob(DIR_STORAGE . 'cache/cache.product.*') ?: [] as $cacheFile) {
			if (is_file($cacheFile)) {
				@unlink($cacheFile);
			}
		}
	}
	$reportFile = DIR_STORAGE . 'logs/pandashop-supplemental-import-' . date('Ymd-His') . '.json';
	@file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $exception) {
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}
	fwrite(STDERR, 'Import aborted; transaction rolled back: ' . $exception->getMessage() . PHP_EOL);
	exit(1);
}
