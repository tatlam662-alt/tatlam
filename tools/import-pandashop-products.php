<?php
declare(strict_types=1);

/**
 * One-time, idempotent import of the PanDaShop sample products from the supplied brief.
 * Run from the project root with: C:\xampp\php\php.exe tools\import-pandashop-products.php
 * The importer refuses web execution and downloads every image before opening a DB transaction.
 */
if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only.\n");
	exit(1);
}

$root = dirname(__DIR__);
$configPath = $root . '/upload/config.php';
if (!is_file($configPath)) {
	fwrite(STDERR, "OpenCart config not found: {$configPath}\n");
	exit(1);
}
require_once $configPath;

$products = [
	['FLY-DJI-01', 'DJI Mini 4 Pro (DJI RC 2)', 25990000, 'Flycam & Drone', 'https://dji-official-fe.djicdn.com/cms/uploads/22378f8770d4948a33502ee2bf21d8fa.png', 'Flycam siêu nhẹ 249g, quay 4K/60fps HDR, cảm biến đa hướng, tay cầm màn hình sáng.'],
	['FLY-DJI-02', 'DJI Air 3S Fly More Combo', 39490000, 'Flycam & Drone', 'https://dji-official-fe.djicdn.com/cms/uploads/a832621ec6cf6970bc23bc7a1a4b4ee9.png', 'Camera kép cảm biến 1 inch, quay 4K/120fps, thời gian bay 45 phút.'],
	['FLY-DJI-03', 'DJI Avata 2 Fly More Combo', 23990000, 'Flycam & Drone', 'https://dji-official-fe.djicdn.com/cms/uploads/8f1f5139fb2b01a1419a4a7538c5b2a0.png', 'Flycam FPV góc siêu rộng 155 độ, trải nghiệm bay nhập vai chuyên nghiệp.'],
	['FLY-DJI-04', 'DJI Mavic 3 Pro (DJI RC)', 49990000, 'Flycam & Drone', 'https://dji-official-fe.djicdn.com/cms/uploads/7a8e530666ba8a183d2c8849b25121b8.png', 'Hệ thống 3 camera Hasselblad cao cấp, quay 5.1K, zoom quang học 7x.'],
	['FLY-DJI-05', 'DJI Neo Fly More Combo', 8990000, 'Flycam & Drone', 'https://dji-official-fe.djicdn.com/cms/uploads/5ef89025e1a384b6cbcf6238b97d21c0.png', 'Flycam bỏ túi 135g cất cánh từ lòng bàn tay, quay 4K AI Tracking.'],
	['CAM-DJI-01', 'DJI Osmo Pocket 3 Creator Combo', 15890000, 'Camera & Gimbal', 'https://dji-official-fe.djicdn.com/cms/uploads/9b4764b85c276f5367b6139cdd9493f1.png', 'Camera bỏ túi cảm biến 1 inch, màn hình xoay OLED 2 inch, chống rung 3 trục.'],
	['CAM-DJI-02', 'Tay cầm chống rung DJI RS 4 Combo', 12990000, 'Camera & Gimbal', 'https://dji-official-fe.djicdn.com/cms/uploads/049c06efae1ec7b6113b2e75e13ef6cb.png', 'Gimbal máy ảnh chuyên nghiệp tải trọng 3kg, khóa trục tự động thế hệ mới.'],
	['CAM-DJI-03', 'Camera Hành Trình DJI Osmo Action 5 Pro', 9190000, 'Camera & Gimbal', 'https://dji-official-fe.djicdn.com/cms/uploads/403d154ee426d425bc5455d3fcd0c3eb.png', 'Action cam cảm biến 1/1.3 inch, quay 4K/120fps, chống nước 20m không cần vỏ.'],
	['CAM-DJI-04', 'Microphone Không Dây DJI Mic 2 (2 TX + 1 RX)', 8490000, 'Camera & Gimbal', 'https://dji-official-fe.djicdn.com/cms/uploads/d765e90d79d6756e1b6f0e9b92f72e3a.png', 'Micro thu âm không dây khử tiếng ồn thông minh, ghi âm nội bộ 32-bit float.'],
	['PC-GPU-01', 'Card màn hình ASUS ROG Strix GeForce RTX 4070 Super 12GB', 21990000, 'Linh kiện PC', 'https://dlcdnrog.asus.com/rog/media/1704764491717.webp', 'Kiến trúc Ada Lovelace, DLSS 3, tản nhiệt Axial-tech 3 quạt siêu mát.'],
	['PC-SSD-01', 'Ổ cứng SSD Samsung 990 Pro 1TB NVMe M.2', 3290000, 'Linh kiện PC', 'https://images.samsung.com/is/image/samsung/p6pim/vn/mz-v9p1t0bw/gallery/vn-990pro-nvme-m2-ssd-mz-v9p1t0bw-534241696', 'Tốc độ đọc 7450 MB/s, chuẩn PCIe 4.0 NVMe tối ưu cho Gaming và làm đồ họa.'],
	['PC-CPU-01', 'Bộ vi xử lý Intel Core i7-14700K', 10890000, 'Linh kiện PC', 'https://m.media-amazon.com/images/I/61S7I3L3SLL._AC_SL1500_.jpg', '20 nhân 28 luồng, xung nhịp turbo lên tới 5.6GHz, hỗ trợ DDR5 & PCIe 5.0.'],
	['PC-MB-01', 'Mainboard ASUS ROG STRIX Z790-A GAMING WIFI', 9890000, 'Linh kiện PC', 'https://dlcdnrog.asus.com/rog/media/1666946058145.webp', 'Bo mạch chủ tông trắng cao cấp, PCIe 5.0, Wi-Fi 6E, dàn pha nguồn 16+1 power stages.'],
	['PC-RAM-01', 'RAM Corsair Vengeance RGB 32GB (2x16GB) DDR5 6000MHz', 3690000, 'Linh kiện PC', 'https://res.cloudinary.com/corsair-pwa/image/upload/f_auto,q_auto/content/CMH32GX5M2B6000C30-01.png', 'RAM DDR5 tốc độ cao, dải đèn LED RGB dynamic 10 vùng tùy chỉnh.'],
	['KB-AKKO-01', 'Bàn phím cơ AKKO 3068B Plus Multi-Modes', 1890000, 'Bàn phím gaming', 'https://akko.vn/wp-content/uploads/2022/03/akko-3068b-plus-black-gold-01.jpg', 'Layout 65% nhỏ gọn, 3 chế độ kết nối (Bluetooth/2.4G/Type-C), Hotswap.'],
	['KB-MONS-01', 'Bàn phím cơ Custom MonsGeek M1W V3 RGB', 2490000, 'Bàn phím gaming', 'https://akko.vn/wp-content/uploads/2023/07/monsgeek-m1w-black-01.jpg', 'Vỏ nhôm CNC nguyên khối, Gasket mount gõ êm, kết nối 3 mode không dây.'],
	['KB-LOGI-01', 'Bàn phím cơ không dây Logitech G915 TKL LIGHTSPEED', 4290000, 'Bàn phím gaming', 'https://resource.logitechg.com/w_1000,c_limit,q_auto,f_auto,dpr_2.0/d_transparent.gif/content/dam/gaming/en/products/g915-tkl/g915-tkl-gallery-1-carbon.png', 'Thiết kế siêu mỏng Ultra-thin, Switch GL Low Profile, kết nối LIGHTSPEED 1ms.'],
	['MS-LOGI-01', 'Chuột Gaming Logitech G Pro X Superlight 2', 3590000, 'Chuột gaming', 'https://resource.logitechg.com/w_1000,c_limit,q_auto,f_auto,dpr_2.0/d_transparent.gif/content/dam/gaming/en/products/gpro-x-superlight-2/gallery/gpro-x-superlight-2-black-gallery-1.png', 'Trọng lượng siêu nhẹ 60g, cảm biến HERO 2 32,000 DPI, Switch LIGHTFORCE hybrid.'],
	['MS-RAZER-01', 'Chuột không dây Razer Viper V3 Pro', 3990000, 'Chuột gaming', 'https://assets2.razerzone.com/images/pnx.assets/d244ffb00712711d999ed65d06b6e41b/razer-viper-v3-pro-black-500x500.png', 'Cảm biến Focus Pro 35K Gen-2, Polling rate 8000Hz không dây, nặng chỉ 54g.'],
	['MS-RAZER-02', 'Chuột Gaming Razer DeathAdder V3 Pro', 3290000, 'Chuột gaming', 'https://assets2.razerzone.com/images/pnx.assets/90d40234a7199434e3a9b1d0b3b9b6d1/razer-deathadder-v3-pro-black-500x500.png', 'Thiết kế Ergonomic cho game thủ FPS, Switch quang học Gen-3.'],
	['HP-STEEL-01', 'Tai nghe không dây SteelSeries Arctis Nova Pro Wireless', 8990000, 'Tai nghe gaming', 'https://media.steelseriescdn.com/thumbs/catalog/items/61520/be58e6587c474d28a3064601198e3fb2.png.500x400_q100_crop-fit_optimize.png', 'Âm thanh Hi-Res Audio, chống ồn chủ động ANC, hệ thống 2 pin sạc thay phiên.'],
	['HP-HYPER-01', 'Tai nghe Gaming HyperX Cloud III Wireless', 3490000, 'Tai nghe gaming', 'https://row.hyperx.com/cdn/shop/files/hyperx_cloud_iii_wireless_black_red_1_main.jpg', 'Thời lượng pin lên tới 120 giờ, màng loa 53mm góc nghiêng, đệm tai cao su non êm ái.'],
	['MON-ASUS-01', 'Màn hình Gaming ASUS ROG Swift OLED PG27AQDM 27 inch 240Hz', 22990000, 'Màn hình', 'https://dlcdnrog.asus.com/rog/media/1673024888921.webp', 'Tấm nền OLED 2K QHD, tần số quét 240Hz, thời gian phản hồi siêu tốc 0.03ms.'],
	['MON-DELL-01', 'Màn hình Đồ Họa Dell UltraSharp U2724D 27 inch 2K 120Hz', 10490000, 'Màn hình', 'https://i.dell.com/is/image/DellContent/content/dam/ss2/product-images/dell-client-products/peripherals/monitors/u-series/u2724d/media-gallery/monitor-ultrasharp-u2724d-gallery-1.psd', 'Tấm nền IPS Black độ tương phản 2000:1, chuẩn màu 98% Display P3, tần số quét 120Hz.'],
];

$imageDirectory = DIR_IMAGE . 'catalog/pandashop-samples/';
if (!is_dir($imageDirectory) && !mkdir($imageDirectory, 0755, true) && !is_dir($imageDirectory)) {
	fwrite(STDERR, "Cannot create image directory: {$imageDirectory}\n");
	exit(1);
}

if (!function_exists('curl_init')) {
	fwrite(STDERR, "PHP cURL extension is required to download product images. No database changes were made.\n");
	exit(1);
}

try {
	$pdo = new PDO('mysql:host=' . DB_HOSTNAME . ';port=' . DB_PORT . ';dbname=' . DB_DATABASE . ';charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
	$prefix = DB_PREFIX;
	$languageQuery = $pdo->prepare("SELECT `language_id` FROM `{$prefix}language` WHERE `code` = 'vi-vn' AND `status` = 1 LIMIT 1");
	$languageQuery->execute();
	$languageId = (int)$languageQuery->fetchColumn();
	if (!$languageId) {
		throw new RuntimeException('Enabled vi-vn language was not found.');
	}
} catch (Throwable $exception) {
	fwrite(STDERR, 'Database connection failed; no changes made: ' . $exception->getMessage() . PHP_EOL);
	exit(1);
}

$images = [];
$existingImageQuery = $pdo->prepare("SELECT p.`image` FROM `{$prefix}product` p LEFT JOIN `{$prefix}product_description` pd ON pd.`product_id`=p.`product_id` AND pd.`language_id`=? WHERE p.`model`=? OR pd.`name`=? ORDER BY (p.`model`=?) DESC LIMIT 1");
foreach ($products as [$model, $name, $price, $categoryName, $imageUrl]) {
	$extension = strtolower((string)pathinfo((string)parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
	if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
		$extension = 'jpg';
	}
	$fileName = strtolower(str_replace('-', '_', $model)) . '.' . $extension;
	$target = $imageDirectory . $fileName;
	$handle = fopen($target . '.part', 'wb');
	if ($handle === false) {
		fwrite(STDERR, "Cannot write image for {$model}. No database changes were made.\n");
		exit(1);
	}
	$curl = curl_init($imageUrl);
	curl_setopt_array($curl, [
		CURLOPT_FILE => $handle,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 4,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_TIMEOUT => 35,
		CURLOPT_USERAGENT => 'PanDaShop Catalog Import/1.0',
		CURLOPT_FAILONERROR => true
	]);
	$ok = curl_exec($curl);
	$status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
	$error = curl_error($curl);
	curl_close($curl);
	fclose($handle);
	$size = is_file($target . '.part') ? filesize($target . '.part') : 0;
	if ($ok !== true || $status < 200 || $status >= 300 || $size < 256) {
		@unlink($target . '.part');
		$existingImageQuery->execute([$languageId, $model, $name, $model]);
		$existingImage = (string)$existingImageQuery->fetchColumn();
		if ($existingImage !== '' && is_file(DIR_IMAGE . $existingImage)) {
			$images[$model] = $existingImage;
		} elseif (is_file(DIR_IMAGE . 'no_image.png')) {
			$images[$model] = 'no_image.png';
		} else {
			fwrite(STDERR, "Image download failed for {$model} (HTTP {$status}): {$error}; no local fallback exists. No database changes were made.\n");
			exit(1);
		}
		fwrite(STDERR, "WARNING: {$model} uses a local fallback image because its source could not be reached.\n");
		continue;
	}
	if (!rename($target . '.part', $target)) {
		@unlink($target . '.part');
		fwrite(STDERR, "Could not finalize image for {$model}. No database changes were made.\n");
		exit(1);
	}
	$images[$model] = 'catalog/pandashop-samples/' . $fileName;
}

try {
	$pdo->beginTransaction();
	$report = ['inserted' => 0, 'updated' => 0, 'products' => []];
	foreach ($products as [$model, $name, $price, $categoryName, $imageUrl, $description]) {
		$categoryQuery = $pdo->prepare("SELECT c.`category_id` FROM `{$prefix}category` c INNER JOIN `{$prefix}category_description` cd ON cd.`category_id` = c.`category_id` WHERE cd.`language_id` = ? AND cd.`name` = ? AND c.`status` = 1 LIMIT 1");
		$categoryQuery->execute([$languageId, $categoryName]);
		$categoryId = (int)$categoryQuery->fetchColumn();
		if (!$categoryId && $categoryName === 'Tai nghe gaming') {
			$categoryId = 73;
		}
		if (!$categoryId) {
			throw new RuntimeException("Enabled category not found: {$categoryName}");
		}

		$find = $pdo->prepare("SELECT DISTINCT p.`product_id`, p.`model`, pd.`name` FROM `{$prefix}product` p LEFT JOIN `{$prefix}product_description` pd ON pd.`product_id` = p.`product_id` AND pd.`language_id` = ? WHERE p.`model` = ? OR pd.`name` = ?");
		$find->execute([$languageId, $model, $name]);
		$matches = $find->fetchAll();
		$ids = array_values(array_unique(array_map(static fn(array $row): int => (int)$row['product_id'], $matches)));
		if (count($ids) > 1) {
			throw new RuntimeException("Ambiguous duplicate for {$model} / {$name}; no database changes committed.");
		}
		$productId = $ids[0] ?? 0;
		if ($productId) {
			$update = $pdo->prepare("UPDATE `{$prefix}product` SET `model`=?, `quantity`=100, `stock_status_id`=?, `image`=?, `price`=?, `status`=1, `date_modified`=NOW() WHERE `product_id`=?");
			$stockStatus = (int)$pdo->query("SELECT `stock_status_id` FROM `{$prefix}stock_status` WHERE `language_id`={$languageId} ORDER BY `stock_status_id` LIMIT 1")->fetchColumn();
			$update->execute([$model, $stockStatus, $images[$model], $price, $productId]);
			$report['updated']++;
		} else {
			$stockStatus = (int)$pdo->query("SELECT `stock_status_id` FROM `{$prefix}stock_status` WHERE `language_id`={$languageId} ORDER BY `stock_status_id` LIMIT 1")->fetchColumn();
			$insert = $pdo->prepare("INSERT INTO `{$prefix}product` (`model`,`location`,`variant`,`override`,`quantity`,`stock_status_id`,`image`,`manufacturer_id`,`shipping`,`price`,`points`,`tax_class_id`,`date_available`,`weight`,`weight_class_id`,`length`,`width`,`height`,`length_class_id`,`subtract`,`minimum`,`rating`,`sort_order`,`status`,`date_added`,`date_modified`) VALUES (?, '', '', '', 100, ?, ?, 0, 1, ?, 0, 0, CURDATE(), 0, 0, 0, 0, 0, 0, 1, 1, 0, 0, 1, NOW(), NOW())");
			$insert->execute([$model, $stockStatus, $images[$model], $price]);
			$productId = (int)$pdo->lastInsertId();
			$pdo->prepare("INSERT INTO `{$prefix}product_to_store` (`product_id`,`store_id`) VALUES (?,0)")->execute([$productId]);
			$report['inserted']++;
		}

		$descriptionQuery = $pdo->prepare("SELECT `product_id` FROM `{$prefix}product_description` WHERE `product_id`=? AND `language_id`=?");
		$descriptionQuery->execute([$productId, $languageId]);
		if ($descriptionQuery->fetchColumn()) {
			$saveDescription = $pdo->prepare("UPDATE `{$prefix}product_description` SET `name`=?, `description`=?, `tag`=?, `meta_title`=? WHERE `product_id`=? AND `language_id`=?");
			$saveDescription->execute([$name, $description, $name, $name, $productId, $languageId]);
		} else {
			$saveDescription = $pdo->prepare("INSERT INTO `{$prefix}product_description` (`product_id`,`language_id`,`name`,`description`,`tag`,`meta_title`,`meta_description`,`meta_keyword`) VALUES (?,?,?,?,?,?,?,?)");
			$saveDescription->execute([$productId, $languageId, $name, $description, $name, $name, '', '']);
		}

		$pdo->prepare("DELETE FROM `{$prefix}product_to_category` WHERE `product_id`=?")->execute([$productId]);
		$pdo->prepare("INSERT INTO `{$prefix}product_to_category` (`product_id`,`category_id`) VALUES (?,?)")->execute([$productId, $categoryId]);
		if (!$pdo->query("SELECT 1 FROM `{$prefix}product_to_store` WHERE `product_id`={$productId} AND `store_id`=0 LIMIT 1")->fetchColumn()) {
			$pdo->prepare("INSERT INTO `{$prefix}product_to_store` (`product_id`,`store_id`) VALUES (?,0)")->execute([$productId]);
		}
		$report['products'][] = ['model' => $model, 'product_id' => $productId, 'action' => in_array($productId, $ids, true) ? 'updated' : 'inserted'];
	}
	$pdo->commit();
	@file_put_contents(DIR_STORAGE . 'logs/pandashop-sample-import-' . date('Ymd-His') . '.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	$pdo = null;
	if (is_dir(DIR_STORAGE . 'cache/')) {
		foreach (glob(DIR_STORAGE . 'cache/*') ?: [] as $cacheFile) {
			if (is_file($cacheFile)) {
				@unlink($cacheFile);
			}
		}
	}
	echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Throwable $exception) {
	if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
		$pdo->rollBack();
	}
	fwrite(STDERR, 'Import aborted: ' . $exception->getMessage() . PHP_EOL);
	exit(1);
}
