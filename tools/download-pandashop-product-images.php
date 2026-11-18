<?php
declare(strict_types=1);

/** CLI-only downloader for externally hosted OpenCart product images. */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only. Run: C:\\xampp\\php\\php.exe tools\\download-pandashop-product-images.php\n");
    exit(1);
}
require_once dirname(__DIR__) . '/upload/config.php';
if (!function_exists('curl_init')) {
    fwrite(STDERR, "PHP cURL extension is required.\n");
    exit(1);
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOSTNAME . ';port=' . DB_PORT . ';dbname=' . DB_DATABASE . ';charset=utf8mb4', DB_USERNAME, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $prefix = DB_PREFIX;
    $allProducts = $pdo->query("SELECT `product_id`,`model`,`image` FROM `{$prefix}product` ORDER BY `product_id`")->fetchAll();
    $sourceMap = [];
    foreach (['import-pandashop-products.php', 'import-pandashop-detailed-products.php', 'import-pandashop-supplemental-products.php', 'import-pandashop-multibrand-products.php'] as $sourceFile) {
        $sourcePath = dirname(__DIR__) . '/tools/' . $sourceFile;
        if (!is_file($sourcePath)) { continue; }
        $sourceText = (string)file_get_contents($sourcePath);
        preg_match_all("~'model'\\s*=>\\s*'([^']+)'.*?'image_url'\\s*=>\\s*'([^']+)'~s", $sourceText, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) { $sourceMap[$match[1]] = $match[2]; }
        // The original seed importer stores compact positional rows instead of named image_url keys.
        if ($sourceFile === 'import-pandashop-products.php') {
            preg_match_all("~\\['([^']+)'\\s*,\\s*'[^']*'\\s*,\\s*[0-9]+\\s*,\\s*'[^']*'\\s*,\\s*'(https?://[^']+)'~", $sourceText, $rows, PREG_SET_ORDER);
            foreach ($rows as $row) { $sourceMap[$row[1]] = $row[2]; }
        }
    }
    $products = [];
    $externalCount = 0;
    $recoveredSourceCount = 0;
    foreach ($allProducts as $row) {
        $image = trim((string)$row['image']);
        if (preg_match('~^https?://~i', $image) === 1) {
            $products[] = ['row' => $row, 'url' => $image];
            $externalCount++;
        } elseif (in_array(strtolower($image), ['no_image.png', 'placeholder.png', ''], true) && isset($sourceMap[$row['model']])) {
            $products[] = ['row' => $row, 'url' => $sourceMap[$row['model']]];
            $recoveredSourceCount++;
        }
    }
    $directory = DIR_IMAGE . 'catalog/products/';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create upload/image/catalog/products directory.');
    }
    $savePath = $pdo->prepare("UPDATE `{$prefix}product` SET `image`=?,`date_modified`=NOW() WHERE `product_id`=?");
    $report = ['products_scanned' => count($allProducts), 'external_urls_found' => $externalCount, 'source_urls_recovered_from_importers' => $recoveredSourceCount, 'downloaded' => [], 'failed' => [], 'placeholder_images' => [], 'missing_local_files' => []];
    foreach ($allProducts as $row) {
        $image = trim((string)$row['image']);
        if (preg_match('~^https?://~i', $image) === 1) { continue; }
        if ($image === '' || in_array(strtolower($image), ['no_image.png', 'placeholder.png'], true)) {
            $report['placeholder_images'][] = ['model' => $row['model'], 'image' => $image];
        } elseif (!is_file(DIR_IMAGE . $image)) {
            $report['missing_local_files'][] = ['model' => $row['model'], 'image' => $image];
        }
    }
    foreach ($products as $candidate) {
        $product = $candidate['row'];
        $url = (string)$candidate['url'];
        $parts = parse_url($url);
        if (!is_array($parts) || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http','https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            $report['failed'][] = ['model' => $product['model'], 'reason' => 'Unsupported or malformed URL'];
            continue;
        }
        $host = (string)$parts['host'];
        $resolvedIp = gethostbyname($host);
        if (filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            $report['failed'][] = ['model' => $product['model'], 'reason' => 'Host did not resolve to a public IP'];
            continue;
        }
        $model = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string)$product['model']);
        if ($model === '' || $model === '-' || $model === null) {
            $report['failed'][] = ['product_id' => (int)$product['product_id'], 'reason' => 'Unsafe or empty model'];
            continue;
        }
        $target = $directory . $model . '.jpg';
        $temp = $target . '.part';
        $handle = @fopen($temp, 'wb');
        if ($handle === false) {
            $report['failed'][] = ['model' => $product['model'], 'reason' => 'Cannot create local temporary image'];
            continue;
        }
        $bytes = 0;
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 4,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_USERAGENT => 'PanDaShop Product Image Import/1.0',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FAILONERROR => true,
            CURLOPT_WRITEFUNCTION => static function ($curlHandle, string $chunk) use ($handle, &$bytes): int {
                $length = strlen($chunk);
                $bytes += $length;
                if ($bytes > 20 * 1024 * 1024) { return 0; }
                return fwrite($handle, $chunk);
            },
        ]);
        $ok = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        fclose($handle);
        $size = @getimagesize($temp);
        if ($ok !== true || $status < 200 || $status >= 300 || $bytes < 256 || $size === false || $size[0] < 100 || $size[1] < 100) {
            @unlink($temp);
            $report['failed'][] = ['model' => $product['model'], 'status' => $status, 'reason' => $curlError !== '' ? $curlError : 'HTTP response was not a valid product image (minimum 100x100 pixels)'];
            continue;
        }
        $mime = (string)($size['mime'] ?? '');
        if ($mime !== 'image/jpeg') {
            $sourceBytes = file_get_contents($temp);
            $image = $sourceBytes !== false && function_exists('imagecreatefromstring') ? @imagecreatefromstring($sourceBytes) : false;
            if ($image !== false && function_exists('imagejpeg')) {
                $converted = imagejpeg($image, $target, 90);
                imagedestroy($image);
                @unlink($temp);
                if (!$converted) {
                    @unlink($target);
                    $report['failed'][] = ['model' => $product['model'], 'reason' => 'Could not convert source image to JPEG'];
                    continue;
                }
                $relative = 'catalog/products/' . $model . '.jpg';
            } else {
                $extensionByMime = ['image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
                $extension = $extensionByMime[$mime] ?? '';
                if ($extension === '') {
                    @unlink($temp);
                    $report['failed'][] = ['model' => $product['model'], 'reason' => 'Unsupported image type: ' . $mime];
                    continue;
                }
                $target = $directory . $model . '.' . $extension;
                if (!@rename($temp, $target)) {
                    @unlink($temp);
                    $report['failed'][] = ['model' => $product['model'], 'reason' => 'Cannot save converted image format'];
                    continue;
                }
                $relative = 'catalog/products/' . $model . '.' . $extension;
            }
        } else {
            if (!@rename($temp, $target)) {
                @unlink($temp);
                $report['failed'][] = ['model' => $product['model'], 'reason' => 'Cannot move validated image into catalog directory'];
                continue;
            }
            $relative = 'catalog/products/' . $model . '.jpg';
        }
        $savePath->execute([$relative, (int)$product['product_id']]);
        $report['downloaded'][] = ['model' => $product['model'], 'product_id' => (int)$product['product_id'], 'image' => $relative];
    }
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Image import failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
