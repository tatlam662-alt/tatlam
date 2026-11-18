<?php
namespace Opencart\System\Library\PanDaShop;

final class Membership {
	public const BONUS_MARKER = '[PANDASHOP:MEMBER_BONUS]';

	public static function getTiers(): array {
		return [
			['key' => 'member', 'orders' => 0, 'bonus_percent' => 0],
			['key' => 'silver', 'orders' => 5, 'bonus_percent' => 5],
			['key' => 'gold', 'orders' => 15, 'bonus_percent' => 10],
			['key' => 'diamond', 'orders' => 30, 'bonus_percent' => 15]
		];
	}

	public static function getTierForOrderCount(int $completed_orders): array {
		$tier = self::getTiers()[0];

		foreach (self::getTiers() as $candidate) {
			if ($completed_orders < $candidate['orders']) {
				break;
			}

			$tier = $candidate;
		}

		return $tier;
	}

	public static function getProgress(int $completed_orders): array {
		$tiers = self::getTiers();
		$current = self::getTierForOrderCount($completed_orders);
		$current_index = array_search($current['key'], array_column($tiers, 'key'), true);
		$next = $tiers[$current_index + 1] ?? null;

		if ($next) {
			$range = $next['orders'] - $current['orders'];
			$progress = $range > 0 ? (int)floor((($completed_orders - $current['orders']) / $range) * 100) : 0;
		} else {
			$progress = 100;
		}

		return [
			'current' => $current,
			'next' => $next,
			'orders_to_next' => $next ? max(0, $next['orders'] - $completed_orders) : 0,
			'progress_percent' => min(100, max(0, $progress)),
			'tiers' => $tiers
		];
	}

	public static function syncOrderBonus(\Opencart\System\Library\DB $db, int $order_id, array $complete_status_ids): void {
		$complete_status_ids = array_values(array_unique(array_filter(array_map('intval', $complete_status_ids), static fn(int $status_id): bool => $status_id > 0)));

		$db->query('START TRANSACTION');

		try {
			$order_query = $db->query("SELECT `customer_id`, `store_id`, `order_status_id`, `language_code` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . $order_id . "' FOR UPDATE");

			if (!$order_query->num_rows || !(int)$order_query->row['customer_id']) {
				$db->query('COMMIT');
				return;
			}

			$order = $order_query->row;
			$is_complete = $complete_status_ids && in_array((int)$order['order_status_id'], $complete_status_ids, true);

			if (!$is_complete) {
				$db->query("DELETE FROM `" . DB_PREFIX . "customer_reward` WHERE `order_id` = '" . $order_id . "' AND LOCATE('" . self::BONUS_MARKER . "', `description`) > 0");
				$db->query('COMMIT');
				return;
			}

			$base_reward_query = $db->query("SELECT COALESCE(SUM(`points`), 0) AS `points` FROM `" . DB_PREFIX . "customer_reward` WHERE `order_id` = '" . $order_id . "' AND `customer_id` = '" . (int)$order['customer_id'] . "' AND `points` > '0' AND LOCATE('" . self::BONUS_MARKER . "', `description`) = 0");
			$base_points = (int)$base_reward_query->row['points'];

			if ($base_points < 1) {
				$db->query("DELETE FROM `" . DB_PREFIX . "customer_reward` WHERE `order_id` = '" . $order_id . "' AND LOCATE('" . self::BONUS_MARKER . "', `description`) > 0");
				$db->query('COMMIT');
				return;
			}

			$existing_bonus = $db->query("SELECT `customer_reward_id` FROM `" . DB_PREFIX . "customer_reward` WHERE `order_id` = '" . $order_id . "' AND LOCATE('" . self::BONUS_MARKER . "', `description`) > 0 LIMIT 1");

			if ($existing_bonus->num_rows) {
				$db->query('COMMIT');
				return;
			}

			$statuses = implode(',', $complete_status_ids);
			$order_count_query = $db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "order` WHERE `customer_id` = '" . (int)$order['customer_id'] . "' AND `store_id` = '" . (int)$order['store_id'] . "' AND `order_status_id` IN (" . $statuses . ")");
			$tier = self::getTierForOrderCount((int)$order_count_query->row['total']);
			$bonus_points = (int)floor($base_points * ($tier['bonus_percent'] / 100));

			if ($bonus_points > 0) {
				$language_code = (string)$order['language_code'];
				$label = $language_code === 'vi-vn' ? 'Thưởng thêm PanDaCoins theo hạng hội viên' : 'PanDaMember tier bonus';
				$description = self::BONUS_MARKER . ' ' . $label . ' (+' . $tier['bonus_percent'] . '%)';

				$db->query("INSERT INTO `" . DB_PREFIX . "customer_reward` SET `customer_id` = '" . (int)$order['customer_id'] . "', `order_id` = '" . $order_id . "', `description` = '" . $db->escape($description) . "', `points` = '" . $bonus_points . "', `date_added` = NOW()");
			}

			$db->query('COMMIT');
		} catch (\Throwable $error) {
			$db->query('ROLLBACK');
			throw $error;
		}
	}
}
