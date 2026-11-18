<?php
declare(strict_types=1);

// Local development helper for generated OpenCart cache files only.
$remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteAddress, ['127.0.0.1', '::1'], true)) {
	http_response_code(403);
	header('Content-Type: text/plain; charset=utf-8');
	echo 'Forbidden: this cache helper is available from localhost only.';
	exit;
}

require_once __DIR__ . '/config.php';

$storageRoot = realpath(DIR_STORAGE);
$targets = [
	'cache' => realpath(DIR_CACHE),
	'modification' => realpath(DIR_STORAGE . 'modification' . DIRECTORY_SEPARATOR)
];

if ($storageRoot === false) {
	http_response_code(500);
	header('Content-Type: text/plain; charset=utf-8');
	echo 'OpenCart storage directory is unavailable.';
	exit;
}

$removeGeneratedFiles = static function (string $directory): int {
	$deleted = 0;
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ($iterator as $item) {
		$path = $item->getPathname();
		if ($item->isLink()) {
			continue;
		}
		if ($item->isFile() && strtolower($item->getFilename()) !== 'index.html') {
			if (@unlink($path)) {
				$deleted++;
			}
		} elseif ($item->isDir()) {
			@rmdir($path);
		}
	}

	return $deleted;
};

$counts = ['cache' => 0, 'modification' => 0];
$storagePrefix = rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

foreach ($targets as $name => $directory) {
	if ($directory === false) {
		continue;
	}
	$normalizedRoot = strtolower($storagePrefix);
	$normalizedTarget = strtolower(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
	if (strpos($normalizedTarget, $normalizedRoot) !== 0 || is_link($directory)) {
		continue;
	}
	$counts[$name] = $removeGeneratedFiles($directory);
}

// Reset PHP's in-memory bytecode cache as well as OpenCart's generated files.
$opcacheReset = function_exists('opcache_reset') ? @opcache_reset() : false;

header('Content-Type: text/plain; charset=utf-8');
echo 'OpenCart generated files cleared. Cache: ' . $counts['cache'] . '; modification: ' . $counts['modification'] . '; OPcache reset: ' . ($opcacheReset ? 'yes' : 'no/unavailable') . '. Refresh modifications in Admin if the storefront relies on generated OCMOD files, then reload with Ctrl + F5.';
