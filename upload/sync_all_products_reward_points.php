<?php
/**
 * One-time PanDaCoins earning-points synchronization.
 * Run from a terminal: php sync_all_products_reward_points.php
 * This script is intentionally CLI-only because it writes every product's
 * reward configuration and must not be exposed as a public web endpoint.
 */

if (PHP_SAPI !== 'cli') {
	http_response_code(404);
	exit;
}

require_once(__DIR__ . '/config.php');
require_once(DIR_SYSTEM . 'startup.php');

try {
	$db = new \Opencart\System\Library\DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, DB_PORT, DB_SSL_KEY, DB_SSL_CERT, DB_SSL_CA);
	$group_query = $db->query("SELECT `customer_group_id` FROM `" . DB_PREFIX . "customer_group` ORDER BY `customer_group_id`");
	$customer_group_ids = array_map(static fn(array $row): int => (int)$row['customer_group_id'], $group_query->rows);

	if (!$customer_group_ids) {
		throw new \RuntimeException('No customer groups were found; no products were changed.');
	}

	$product_query = $db->query("SELECT `product_id`, `price` FROM `" . DB_PREFIX . "product`");
	$updated_count = 0;
	$db->query('START TRANSACTION');

	foreach ($product_query->rows as $product) {
		$product_id = (int)$product['product_id'];
		$price = max(0, (float)$product['price']);
		$points = max(1, (int)floor($price / 100000));

		foreach ($customer_group_ids as $customer_group_id) {
			$db->query("INSERT INTO `" . DB_PREFIX . "product_reward` SET `product_id` = '" . $product_id . "', `customer_group_id` = '" . $customer_group_id . "', `points` = '" . $points . "' ON DUPLICATE KEY UPDATE `points` = VALUES(`points`)");
		}

		$updated_count++;
	}

	$db->query('COMMIT');
	fwrite(STDOUT, 'SUCCESS: synchronized earning points for ' . $updated_count . " products across " . count($customer_group_ids) . " customer groups.\n");
	fwrite(STDOUT, "The product.points redemption requirement was left unchanged.\n");
} catch (\Throwable $e) {
	if (isset($db)) {
		try {
			$db->query('ROLLBACK');
		} catch (\Throwable $rollback_error) {
			// Keep the original error for the command output.
		}
	}

	fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
	exit(1);
}
