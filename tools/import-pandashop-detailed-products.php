<?php
declare(strict_types=1);

/** One-time CLI importer for the detailed products supplied for PanDaShop. */
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only.\n");
	exit(1);
}

$root = dirname(__DIR__);
require_once $root . '/upload/config.php';

$products = [
	[
		'model' => 'FLY-DJI-06', 'name' => 'Flycam DJI Air 3 (DJI RC-N2)', 'price' => 24990000, 'category' => 'Flycam & Drone',
		'image_url' => 'https://dji-official-fe.djicdn.com/cms/uploads/213b2c283084364405373bbd8cbe7b41.png',
		'description' => '<p>DJI Air 3 là dòng flycam tầm trung đỉnh cao sở hữu hệ thống camera kép đầu tiên của dòng Air series, tích hợp cả camera góc rộng và camera tele trung bình 3x cùng cảm biến CMOS 1/1.3 inch.</p><ul><li>Camera kép quay video 4K/60fps HDR và 4K/100fps max speed.</li><li>Thời gian bay tối đa lên đến 46 phút, tăng 48% so với thế hệ trước.</li><li>Cảm biến vật cản đa hướng APAS 5.0 giúp chuyến bay an toàn tuyệt đối.</li><li>Hệ thống truyền tải hình ảnh DJI O4 cho khoảng cách xa tới 20km.</li></ul>',
		'attributes' => ['Trọng lượng cất cánh' => '720g', 'Thời gian bay tối đa' => '46 phút', 'Cảm biến hình ảnh' => 'Kép 1/1.3-inch CMOS', 'Độ phân giải video' => '4K/60fps HDR, 4K/100fps', 'Kháng gió' => '12 m/s (Cấp 6)', 'Khoảng cách truyền sóng' => '20 km (DJI O4)']
	],
	[
		'model' => 'FLY-DJI-07', 'name' => 'Flycam DJI Mavic 3 Classic (DJI RC)', 'price' => 38990000, 'category' => 'Flycam & Drone',
		'image_url' => 'https://dji-official-fe.djicdn.com/cms/uploads/e4566d58288849b2ef84a141df80c109.png',
		'description' => '<p>DJI Mavic 3 Classic sở hữu cảm biến Hasselblad 4/3 CMOS huyền thoại, đem lại chất lượng hình ảnh cấp độ điện ảnh trong một thân máy nhỏ gọn.</p><ul><li>Camera Hasselblad 4/3 CMOS quay video 5.1K/50fps và 4K/120fps.</li><li>Công nghệ màu Hasselblad Natural Colour Solution (HNCS) cho màu sắc chân thực.</li><li>Thời gian bay 46 phút kèm định vị GPS/Galileo/BeiDou chính xác cao.</li></ul>',
		'attributes' => ['Cảm biến' => 'Hasselblad 4/3 CMOS', 'Khẩu độ' => 'f/2.8 - f/11', 'Độ phân giải video' => '5.1K/50fps, 4K/120fps', 'Thời gian bay' => '46 phút', 'Dung lượng pin' => '5000 mAh']
	],
	[
		'model' => 'CAM-DJI-05', 'name' => 'Tay cầm chống rung DJI RS 4 Pro Combo', 'price' => 24990000, 'category' => 'Camera & Gimbal',
		'image_url' => 'https://dji-official-fe.djicdn.com/cms/uploads/2f5b40d418701031b2c40c838e12bfa4.png',
		'description' => '<p>DJI RS 4 Pro là hệ thống ổn định Flagship hỗ trợ hoàn hảo cho các dòng máy ảnh cine chuyên nghiệp như Sony FX6, Canon C70, RED Komodo.</p><ul><li>Tải trọng mạnh mẽ lên tới 4.5kg với cánh tay trục bằng sợi carbon siêu bền.</li><li>Khóa trục tự động thế hệ 2 cho khả năng thiết lập và quay nhanh chóng.</li><li>Hỗ trợ lấy nét tự động LiDAR thế hệ mới khoảng cách xa tới 20m.</li></ul>',
		'attributes' => ['Tải trọng tối đa' => '4.5 kg', 'Chất liệu khung' => 'Sợi Carbon (Carbon Fiber)', 'Thời lượng pin' => '13 giờ (Sạc nhanh PD 18W)', 'Kết nối' => 'Bluetooth 5.1, USB-C', 'Màn hình' => 'Touchscreen OLED 1.8 inch']
	],
	[
		'model' => 'CAM-DJI-06', 'name' => 'Gimbal điện thoại DJI Osmo Mobile 6', 'price' => 3390000, 'category' => 'Camera & Gimbal',
		'image_url' => 'https://dji-official-fe.djicdn.com/cms/uploads/2c89659b85cae1f8d4e410b0e5ad57c9.png',
		'description' => '<p>Gimbal chống rung 3 trục thông minh dành cho smartphone với gậy kéo dài tích hợp và theo dõi chủ thể ActiveTrack 6.0 mượt mà.</p>',
		'attributes' => ['Chống rung' => '3 trục cơ học', 'Trọng lượng' => '309g', 'Tải trọng điện thoại' => '170g - 290g', 'Thời gian hoạt động' => '6.4 giờ']
	],
	[
		'model' => 'PC-GPU-02', 'name' => 'Card màn hình MSI GeForce RTX 4080 Super 16G GAMING X SLIM', 'price' => 31990000, 'category' => 'Linh kiện PC',
		'image_url' => 'https://vsp.vn/vsp-uploads/2024/02/rtx-4080-super-gaming-x-slim-01.png',
		'description' => '<p>MSI RTX 4080 Super GAMING X SLIM đem lại hiệu năng đồ họa cực khủng với thiết kế mỏng nhẹ hơn, phù hợp cho mọi thùng máy PC Gaming cao cấp.</p>',
		'attributes' => ['Dung lượng VRAM' => '16GB GDDR6X', 'Băng thông bộ nhớ' => '256-bit', 'Nguồn đề xuất' => '750W trở lên (Cổng 16-pin 12VHPWR)', 'Xung nhịp Boost' => '2610 MHz', 'Cổng xuất hình' => '3x DisplayPort 1.4a, 1x HDMI 2.1a']
	],
	[
		'model' => 'PC-CPU-02', 'name' => 'Bộ vi xử lý AMD Ryzen 7 7800X3D', 'price' => 10990000, 'category' => 'Linh kiện PC',
		'image_url' => 'https://images.aorus.com/products/CPU/AMD-Ryzen-7-7800X3D.png',
		'description' => '<p>AMD Ryzen 7 7800X3D là VUA CPU Gaming hiện nay nhờ công nghệ bộ nhớ đệm 3D V-Cache độc quyền dung lượng lên tới 104MB.</p>',
		'attributes' => ['Số nhân / Số luồng' => '8 Nhân / 16 Luồng', 'Xung nhịp cơ bản' => '4.2 GHz (Boost lên 5.0 GHz)', 'Bộ nhớ đệm L3 Cache' => '96MB (Tổng Cache 104MB)', 'Chuẩn Socket' => 'AM5', 'Tiến trình sản xuất' => 'TSMC 5nm FinFET']
	],
	[
		'model' => 'PC-PSU-01', 'name' => 'Nguồn máy tính Corsair RM1000e 1000W 80 Plus Gold ATX 3.0', 'price' => 4290000, 'category' => 'Linh kiện PC',
		'image_url' => 'https://res.cloudinary.com/corsair-pwa/image/upload/RM1000e_01.png',
	'description' => '<p>Nguồn máy tính chuẩn ATX 3.0 và PCIe 5.0 Full Modular trang bị sẵn cáp 12VHPWR cho Card RTX 4000 series.</p>',
		'attributes' => ['Công suất thực' => '1000 Watt', 'Chứng nhận hiệu suất' => '80 PLUS Gold', 'Chuẩn nguồn' => 'ATX 3.0 / PCIe 5.0', 'Quạt tản nhiệt' => '120mm FDB Fan (Zero RPM Mode)']
	],
	[
		'model' => 'KB-WOOT-01', 'name' => 'Bàn phím cơ Wooting 60HE+ (Rapid Trigger)', 'price' => 5990000, 'category' => 'Bàn phím gaming',
		'image_url' => 'https://wooting.io/assets/images/products/w60he/gallery/w60he_01.png',
		'description' => '<p>Bàn phím cơ trang bị Switch từ tính Lekker đầu tiên trên thế giới tích hợp tính năng Rapid Trigger cực nhạy cho game thủ Valorant/CS2.</p>',
		'attributes' => ['Loại Switch' => 'Lekker Hall Effect Magnetic Switch', 'Mức điểm nhận lệnh' => 'Tùy chỉnh từ 0.1mm - 4.0mm', 'Layout' => '60% Compact', 'Polling Rate' => '1000Hz (Độ trễ thấp <1ms)']
	],
	[
		'model' => 'MS-WL-01', 'name' => 'Chuột không dây WLmouse Beast X Magnesium', 'price' => 3890000, 'category' => 'Chuột gaming',
		'image_url' => 'https://wlmouse.com/cdn/shop/files/beast-x-black.png',
		'description' => '<p>Chuột gaming vỏ Hợp Kim Ma-giê siêu nhẹ đục lỗ tổ ong độc đáo với trọng lượng chỉ 39g.</p>',
		'attributes' => ['Trọng lượng' => '39 gram', 'Cảm biến' => 'PAW3395 (26,000 DPI)', 'Polling Rate' => 'Hỗ trợ 4000Hz / 8000Hz Wireless Dongle', 'Vỏ ngoài' => 'Magnesium Alloy']
	],
	[
		'model' => 'MON-SAMS-01', 'name' => 'Màn hình Cong Samsung Odyssey OLED G8 34 inch 175Hz', 'price' => 24990000, 'category' => 'Màn hình',
		'image_url' => 'https://images.samsung.com/is/image/samsung/p6pim/vn/ls34bg850sexxv/gallery/vn-odyssey-oled-g8-g85sb-ls34bg850sexxv-534346452',
		'description' => '<p>Màn hình cong QD-OLED siêu đỉnh độ phân giải Ultra-WQHD (3440 x 1440), tần số quét 175Hz và thời gian phản hồi 0.03ms.</p>',
		'attributes' => ['Kích thước màn hình' => '34 inch Ultrawide (21:9)', 'Tấm nền' => 'QD-OLED Cong 1800R', 'Độ phân giải' => 'Ultra-WQHD (3440 x 1440)', 'Tần số quét' => '175 Hz', 'Thời gian phản hồi' => '0.03ms (GtG)']
	],
];

$imageDirectory = DIR_IMAGE . 'catalog/pandashop-detailed/';
if (!is_dir($imageDirectory) && !mkdir($imageDirectory, 0755, true) && !is_dir($imageDirectory)) {
	fwrite(STDERR, "Cannot create image directory.\n");
	exit(1);
}
if (!function_exists('curl_init')) {
	fwrite(STDERR, "PHP cURL extension is required. No database changes were made.\n");
	exit(1);
}

try {
	$pdo = new PDO('mysql:host=' . DB_HOSTNAME . ';port=' . DB_PORT . ';dbname=' . DB_DATABASE . ';charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
	$prefix = DB_PREFIX;
	$languageRows = $pdo->query("SELECT `language_id`, `code` FROM `{$prefix}language` WHERE `status` = 1")->fetchAll();
	$languageId = 0;
	$languageIds = [];
	foreach ($languageRows as $language) {
		$languageIds[] = (int)$language['language_id'];
		if ($language['code'] === 'vi-vn') {
			$languageId = (int)$language['language_id'];
		}
	}
	if (!$languageId) {
		throw new RuntimeException('Enabled vi-vn language was not found.');
	}
} catch (Throwable $exception) {
	fwrite(STDERR, 'Database connection failed; no changes made: ' . $exception->getMessage() . PHP_EOL);
	exit(1);
}

// Download and validate the entire image set before any DB write.
$images = [];
$existingImageQuery = $pdo->prepare("SELECT `image` FROM `{$prefix}product` WHERE `model`=? LIMIT 1");
foreach ($products as $product) {
	$extension = strtolower((string)pathinfo((string)parse_url($product['image_url'], PHP_URL_PATH), PATHINFO_EXTENSION));
	if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
		$extension = 'jpg';
	}
	$fileName = strtolower(str_replace('-', '_', $product['model'])) . '.' . $extension;
	$tempFile = $imageDirectory . $fileName . '.part';
	$targetFile = $imageDirectory . $fileName;
	$handle = fopen($tempFile, 'wb');
	if ($handle === false) {
		throw new RuntimeException("Cannot write image for {$product['model']}; DB unchanged.");
	}
	$curl = curl_init($product['image_url']);
	curl_setopt_array($curl, [CURLOPT_FILE => $handle, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 4, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 35, CURLOPT_USERAGENT => 'PanDaShop Catalog Import/1.0', CURLOPT_FAILONERROR => true]);
	$ok = curl_exec($curl);
	$status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$error = curl_error($curl);
	curl_close($curl);
	fclose($handle);
	if ($ok !== true || $status < 200 || $status >= 300 || !is_file($tempFile) || filesize($tempFile) < 256 || @getimagesize($tempFile) === false) {
		@unlink($tempFile);
		$existingImageQuery->execute([$product['model']]);
		$existingImage = (string)$existingImageQuery->fetchColumn();
		if ($existingImage !== '' && is_file(DIR_IMAGE . $existingImage)) {
			$images[$product['model']] = $existingImage;
		} elseif (is_file(DIR_IMAGE . 'no_image.png')) {
			$images[$product['model']] = 'no_image.png';
		} else {
			fwrite(STDERR, "Image download/validation failed for {$product['model']} (HTTP {$status}): {$error}; no local fallback exists. No database changes were made.\n");
			exit(1);
		}
		fwrite(STDERR, "WARNING: {$product['model']} uses a local fallback image because its source could not be reached.\n");
		continue;
	}
	if (!rename($tempFile, $targetFile)) {
		@unlink($tempFile);
		fwrite(STDERR, "Could not save image for {$product['model']}; DB unchanged.\n");
		exit(1);
	}
	$images[$product['model']] = 'catalog/pandashop-detailed/' . $fileName;
}

try {
	$stockStatus = (int)$pdo->query("SELECT `stock_status_id` FROM `{$prefix}stock_status` WHERE `language_id`={$languageId} ORDER BY `stock_status_id` LIMIT 1")->fetchColumn();
	$groupQuery = $pdo->prepare("SELECT ag.`attribute_group_id` FROM `{$prefix}attribute_group` ag JOIN `{$prefix}attribute_group_description` agd ON agd.`attribute_group_id`=ag.`attribute_group_id` WHERE agd.`language_id`=? AND agd.`name`=? LIMIT 1");
	$groupQuery->execute([$languageId, 'Thông số kỹ thuật']);
	$attributeGroupId = (int)$groupQuery->fetchColumn();

	$pdo->beginTransaction();
	if (!$attributeGroupId) {
		$pdo->exec("INSERT INTO `{$prefix}attribute_group` (`sort_order`) VALUES (0)");
		$attributeGroupId = (int)$pdo->lastInsertId();
		$groupInsert = $pdo->prepare("INSERT INTO `{$prefix}attribute_group_description` (`attribute_group_id`,`language_id`,`name`) VALUES (?,?,?)");
		foreach ($languageIds as $id) {
			$groupInsert->execute([$attributeGroupId, $id, 'Thông số kỹ thuật']);
		}
	}

	$report = ['inserted' => 0, 'updated' => 0, 'products' => []];
	foreach ($products as $product) {
		$categoryQuery = $pdo->prepare("SELECT c.`category_id` FROM `{$prefix}category` c JOIN `{$prefix}category_description` cd ON cd.`category_id`=c.`category_id` WHERE cd.`language_id`=? AND cd.`name`=? AND c.`status`=1 LIMIT 1");
		$categoryQuery->execute([$languageId, $product['category']]);
		$categoryId = (int)$categoryQuery->fetchColumn();
		if (!$categoryId) {
			throw new RuntimeException("Active category not found: {$product['category']}");
		}

		$find = $pdo->prepare("SELECT `product_id` FROM `{$prefix}product` WHERE `model`=? LIMIT 2");
		$find->execute([$product['model']]);
		$ids = $find->fetchAll(PDO::FETCH_COLUMN);
		if (count($ids) > 1) {
			throw new RuntimeException("Duplicate model {$product['model']}; transaction aborted.");
		}
		$productId = (int)($ids[0] ?? 0);
		if ($productId) {
			$save = $pdo->prepare("UPDATE `{$prefix}product` SET `quantity`=100, `stock_status_id`=?, `image`=?, `price`=?, `status`=1, `date_modified`=NOW() WHERE `product_id`=?");
			$save->execute([$stockStatus, $images[$product['model']], $product['price'], $productId]);
			$report['updated']++;
		} else {
			$save = $pdo->prepare("INSERT INTO `{$prefix}product` (`model`,`location`,`variant`,`override`,`quantity`,`stock_status_id`,`image`,`manufacturer_id`,`shipping`,`price`,`points`,`tax_class_id`,`date_available`,`weight`,`weight_class_id`,`length`,`width`,`height`,`length_class_id`,`subtract`,`minimum`,`rating`,`sort_order`,`status`,`date_added`,`date_modified`) VALUES (?, '', '', '', 100, ?, ?, 0, 1, ?, 0, 0, CURDATE(), 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 1, NOW(), NOW())");
			$save->execute([$product['model'], $stockStatus, $images[$product['model']], $product['price']]);
			$productId = (int)$pdo->lastInsertId();
			$report['inserted']++;
		}

		foreach ($languageIds as $id) {
			$desc = $pdo->prepare("INSERT INTO `{$prefix}product_description` (`product_id`,`language_id`,`name`,`description`,`tag`,`meta_title`,`meta_description`,`meta_keyword`) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE `name`=VALUES(`name`), `description`=VALUES(`description`), `tag`=VALUES(`tag`), `meta_title`=VALUES(`meta_title`)");
			$desc->execute([$productId, $id, $product['name'], $product['description'], $product['name'], $product['name'], '', '']);
		}

		$pdo->prepare("DELETE FROM `{$prefix}product_to_category` WHERE `product_id`=?")->execute([$productId]);
		$pdo->prepare("INSERT INTO `{$prefix}product_to_category` (`product_id`,`category_id`) VALUES (?,?)")->execute([$productId, $categoryId]);
		$pdo->prepare("INSERT INTO `{$prefix}product_to_store` (`product_id`,`store_id`) VALUES (?,0) ON DUPLICATE KEY UPDATE `store_id`=VALUES(`store_id`)")->execute([$productId]);
		$pdo->prepare("DELETE FROM `{$prefix}product_attribute` WHERE `product_id`=?")->execute([$productId]);

		foreach ($product['attributes'] as $attributeName => $attributeValue) {
			$attributeQuery = $pdo->prepare("SELECT a.`attribute_id` FROM `{$prefix}attribute` a JOIN `{$prefix}attribute_description` ad ON ad.`attribute_id`=a.`attribute_id` WHERE a.`attribute_group_id`=? AND ad.`language_id`=? AND ad.`name`=? LIMIT 1");
			$attributeQuery->execute([$attributeGroupId, $languageId, $attributeName]);
			$attributeId = (int)$attributeQuery->fetchColumn();
			if (!$attributeId) {
				$pdo->prepare("INSERT INTO `{$prefix}attribute` (`attribute_group_id`,`sort_order`) VALUES (?,0)")->execute([$attributeGroupId]);
				$attributeId = (int)$pdo->lastInsertId();
				$attributeDescription = $pdo->prepare("INSERT INTO `{$prefix}attribute_description` (`attribute_id`,`language_id`,`name`) VALUES (?,?,?)");
				foreach ($languageIds as $id) {
					$attributeDescription->execute([$attributeId, $id, $attributeName]);
				}
			}
			$attributeValueInsert = $pdo->prepare("INSERT INTO `{$prefix}product_attribute` (`product_id`,`attribute_id`,`language_id`,`text`) VALUES (?,?,?,?)");
			foreach ($languageIds as $id) {
				$attributeValueInsert->execute([$productId, $attributeId, $id, $attributeValue]);
			}
		}
		$report['products'][] = ['model' => $product['model'], 'product_id' => $productId, 'action' => $ids ? 'updated' : 'inserted', 'attributes' => count($product['attributes'])];
	}
	$pdo->commit();
	@file_put_contents(DIR_STORAGE . 'logs/pandashop-detailed-import-' . date('Ymd-His') . '.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	if (is_dir(DIR_STORAGE . 'cache/')) {
		foreach (glob(DIR_STORAGE . 'cache/*') ?: [] as $cacheFile) {
			if (is_file($cacheFile)) {
				@unlink($cacheFile);
			}
		}
	}
	echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $exception) {
	if ($pdo instanceof PDO && $pdo->inTransaction()) {
		$pdo->rollBack();
	}
	fwrite(STDERR, 'Import aborted: ' . $exception->getMessage() . PHP_EOL);
	exit(1);
}
