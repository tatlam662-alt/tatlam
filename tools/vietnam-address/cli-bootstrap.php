<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit("Not found\n");
}

$projectRoot = dirname(__DIR__, 2);
require_once $projectRoot . '/upload/config.php';

function panda_tools_database(): mysqli {
	if (!extension_loaded('mysqli')) {
		fwrite(STDERR, "Required PHP extension: mysqli.\n");
		exit(1);
	}

	try {
		$db = new mysqli((string)DB_HOSTNAME, (string)DB_USERNAME, (string)DB_PASSWORD, (string)DB_DATABASE, (int)DB_PORT);
	} catch (Throwable $error) {
		fwrite(STDERR, 'Database connection failed: ' . $error->getMessage() . PHP_EOL);
		exit(1);
	}

	if ($db->connect_errno) {
		fwrite(STDERR, 'Database connection failed: ' . $db->connect_error . PHP_EOL);
		exit(1);
	}

	if (!$db->set_charset('utf8mb4')) {
		fwrite(STDERR, 'Could not set database connection charset to utf8mb4.' . PHP_EOL);
		exit(1);
	}

	return $db;
}
