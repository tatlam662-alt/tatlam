<?php
namespace Opencart\Catalog\Model\Account;
/**
 * Class Reward
 *
 * Can be called using $this->load->model('account/reward');
 *
 * @package Opencart\Catalog\Model\Account
 */
class Reward extends \Opencart\System\Engine\Model {
	/**
	 * Add Reward
	 *
	 * Create a new customer reward record in the database.
	 *
	 * @param int    $customer_id primary key of the customer record
	 * @param int    $order_id    primary key of the order record
	 * @param string $description
	 * @param int    $points
	 *
	 * @return void
	 *
	 * @example
	 *
	 * $this->load->model('account/reward');
	 *
	 * $this->model_account_reward->addReward($customer_id, $order_id, $description, $points);
	 */
	public function addReward(int $customer_id, int $order_id, string $description, int $points): void {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "customer_reward` SET `customer_id` = '" . (int)$customer_id . "', `order_id` = '" . (int)$order_id . "', `description` = '" . $this->db->escape($description) . "', `points` = '" . (int)$points . "', `date_added` = NOW()");
	}

	/**
	 * Check whether the customer already claimed today's daily reward.
	 */
	public function hasDailyCheckin(int $customer_id): bool {
		$query = $this->db->query("SELECT `customer_reward_id` FROM `" . DB_PREFIX . "customer_reward` WHERE `customer_id` = '" . (int)$customer_id . "' AND (LOCATE('[PANDASHOP:DAILY_CHECKIN] ', `description`) = 1 OR LOCATE('Điểm danh hàng ngày', `description`) > 0) AND `date_added` >= CURDATE() AND `date_added` < (CURDATE() + INTERVAL 1 DAY) LIMIT 1");

		return (bool)$query->num_rows;
	}

	/**
	 * Claim the daily reward atomically. Returns the award details when claimed,
	 * false when already claimed, and null when the claim could not be saved.
	 *
	 * @return array<string, int>|false|null
	 */
	public function claimDailyCheckin(int $customer_id): array|false|null {
		try {
			$this->db->query("START TRANSACTION");

			// Serialize claims for the same customer, including simultaneous requests.
			$customer_query = $this->db->query("SELECT `customer_id` FROM `" . DB_PREFIX . "customer` WHERE `customer_id` = '" . (int)$customer_id . "' FOR UPDATE");

			if (!$customer_query->num_rows) {
				$this->db->query("ROLLBACK");
				return null;
			}

			$today_query = $this->db->query("SELECT `customer_reward_id` FROM `" . DB_PREFIX . "customer_reward` WHERE `customer_id` = '" . (int)$customer_id . "' AND (LOCATE('[PANDASHOP:DAILY_CHECKIN] ', `description`) = 1 OR LOCATE('Điểm danh hàng ngày', `description`) > 0) AND `date_added` >= CURDATE() AND `date_added` < (CURDATE() + INTERVAL 1 DAY) LIMIT 1");

			if ($today_query->num_rows) {
				$this->db->query("ROLLBACK");
				return false;
			}

			require_once DIR_SYSTEM . 'library/pandashop/reward.php';
			$week_day_query = $this->db->query("SELECT WEEKDAY(CURDATE()) + 1 AS `week_day`");
			$week_day = (int)$week_day_query->row['week_day'];
			$streak_query = $this->db->query("SELECT DATEDIFF(CURDATE(), DATE(`date_added`)) AS `days_since` FROM `" . DB_PREFIX . "customer_reward` WHERE `customer_id` = '" . (int)$customer_id . "' AND LOCATE('[PANDASHOP:DAILY_CHECKIN] ', `description`) = 1 AND `date_added` >= (CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY) AND `date_added` < CURDATE() ORDER BY `date_added` DESC");
			$streak = 1;

			if ($week_day !== 1) {
				$expected_days_since = 1;

				foreach ($streak_query->rows as $streak_row) {
					if ((int)$streak_row['days_since'] !== $expected_days_since) {
						break;
					}

					$streak++;
					$expected_days_since++;
				}
			}

			$cycle_day = \Opencart\System\Library\PanDaShop\Reward::getCheckinCycleDay($streak);
			$base_points = \Opencart\System\Library\PanDaShop\Reward::getCheckinBasePoints($streak);
			$bonus = \Opencart\System\Library\PanDaShop\Reward::getCheckinBonus($streak);
			$points = $base_points + $bonus;
			$description = '[PANDASHOP:DAILY_CHECKIN] [PANDASHOP:STREAK:' . $streak . '] ' . $this->language->get('text_checkin_reward_earned');

			if ($bonus) {
				$description .= ' | ' . sprintf($this->language->get('text_checkin_streak_bonus'), $bonus);
			}

			$this->db->query("INSERT INTO `" . DB_PREFIX . "customer_reward` SET `customer_id` = '" . (int)$customer_id . "', `order_id` = '0', `description` = '" . $this->db->escape($description) . "', `points` = '" . (int)$points . "', `date_added` = NOW()");
			$this->db->query("COMMIT");

			return [
				'points' => $points,
				'base_points' => $base_points,
				'streak' => $streak,
				'cycle_day' => $cycle_day,
				'week_day' => $week_day,
				'bonus' => $bonus
			];
		} catch (\Throwable $e) {
			$this->log->write('PanDaShop daily reward check-in failed: ' . $e->getMessage());

			try {
				$this->db->query("ROLLBACK");
			} catch (\Throwable $rollback_error) {
				$this->log->write('PanDaShop daily reward check-in rollback failed: ' . $rollback_error->getMessage());
			}

			return null;
		}
	}

	public function getDailyCheckinStreak(int $customer_id): int {
		require_once DIR_SYSTEM . 'library/pandashop/reward.php';
		$query = $this->db->query("SELECT DATEDIFF(CURDATE(), DATE(`date_added`)) AS `days_since` FROM `" . DB_PREFIX . "customer_reward` WHERE `customer_id` = '" . (int)$customer_id . "' AND LOCATE('[PANDASHOP:DAILY_CHECKIN] ', `description`) = 1 AND `date_added` >= (CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY) AND `date_added` < (CURDATE() + INTERVAL 1 DAY) ORDER BY `date_added` DESC");

		if (!$query->rows || (int)$query->rows[0]['days_since'] > 1) {
			return 0;
		}

		$streak = 0;
		$expected_days_since = (int)$query->rows[0]['days_since'];

		foreach ($query->rows as $row) {
			if ((int)$row['days_since'] !== $expected_days_since) {
				break;
			}

			$streak++;
			$expected_days_since++;
		}

		return $streak;
	}

	/**
	 * Return the current Monday-to-Sunday check-in calendar.
	 *
	 * @return array{current_day: int, checked_days: array<int, int>}
	 */
	public function getWeeklyCheckinCalendar(int $customer_id): array {
		$query = $this->db->query("SELECT WEEKDAY(CURDATE()) + 1 AS `current_day`, GROUP_CONCAT(DISTINCT (WEEKDAY(DATE(`date_added`)) + 1)) AS `checked_days` FROM `" . DB_PREFIX . "customer_reward` WHERE `customer_id` = '" . (int)$customer_id . "' AND LOCATE('[PANDASHOP:DAILY_CHECKIN] ', `description`) = 1 AND `date_added` >= (CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY) AND `date_added` < (CURDATE() + INTERVAL 1 DAY)");
		$checked_days = [];

		if (!empty($query->row['checked_days'])) {
			$checked_days = array_map('intval', explode(',', $query->row['checked_days']));
		}

		return [
			'current_day' => (int)$query->row['current_day'],
			'checked_days' => $checked_days
		];
	}

	/**
	 * Delete Rewards
	 *
	 * Delete customer reward records in the database.
	 *
	 * @param int $customer_id primary key of the customer record
	 * @param int $order_id    primary key of the order record
	 *
	 * @return void
	 *
	 * @example
	 *
	 * $this->load->model('account/reward');
	 *
	 * $this->model_account_reward->deleteRewards($customer_id, $order_id);
	 */
	public function deleteRewards(int $customer_id, int $order_id = 0): void {
		$sql = "DELETE FROM `" . DB_PREFIX . "customer_reward` WHERE `customer_id` = '" . (int)$customer_id . "'";

		if ($order_id) {
			$sql .= " AND `order_id` = '" . (int)$order_id . "'";
		}

		$this->db->query($sql);
	}

	/**
	 * Delete Rewards By Order ID
	 *
	 * Delete customer rewards by order record in the database.
	 *
	 * @param int $order_id primary key of the order record
	 *
	 * @return void
	 *
	 * @example
	 *
	 * $this->load->model('account/reward');
	 *
	 * $this->model_account_reward->deleteRewardsByOrderId($order_id);
	 */
	public function deleteRewardsByOrderId(int $order_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "customer_reward` WHERE `order_id` = '" . (int)$order_id . "' AND `points` < 0");
	}

	/**
	 * Get Rewards
	 *
	 * Get the record of the customer reward records in the database.
	 *
	 * @param int                  $customer_id primary key of the customer record
	 * @param array<string, mixed> $data        array of filters
	 *
	 * @return array<int, array<string, mixed>> reward records that have customer ID
	 *
	 * @example
	 *
	 * $filter_data = [
	 *     'sort'  => 'date_added',
	 *     'order' => 'DESC',
	 *     'start' => 0,
	 *     'limit' => 10
	 * ];
	 *
	 * $this->load->model('account/reward');
	 *
	 * $results = $this->model_account_reward->getRewards($customer_id, $filter_data);
	 */
	public function getRewards(int $customer_id, array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "customer_reward` WHERE `customer_id` = '" . (int)$customer_id . "'";

		$sort_data = [
			'points',
			'description',
			'date_added'
		];

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY `" . $data['sort'] . "`";
		} else {
			$sql .= " ORDER BY `date_added`";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			$start = isset($data['start']) ? (int)$data['start'] : 0;
			$limit = isset($data['limit']) ? (int)$data['limit'] : 20;

			if ($start < 0) {
				$start = 0;
			}

			if ($limit < 1) {
				$limit = 20;
			}

			$sql .= " LIMIT " . $start . "," . $limit;
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	/**
	 * Get Total Rewards
	 *
	 * Get the total number of total cutomer reward records in the database.
	 *
	 * @param int $customer_id primary key of the customer record
	 *
	 * @return int total number of reward records that have customer ID
	 *
	 * @example
	 *
	 * $this->load->model('account/reward');
	 *
	 * $reward_total = $this->model_account_reward->getTotalRewards($customer_id);
	 */
	public function getTotalRewards(int $customer_id): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "customer_reward` WHERE `customer_id` = '" . (int)$customer_id . "'");

		return (int)$query->row['total'];
	}

	/**
	 * Get Reward Total
	 *
	 * @param int $customer_id primary key of the customer record
	 *
	 * @return int
	 *
	 * @example
	 *
	 * $this->load->model('account/reward');
	 *
	 * $reward_total = $this->model_account_reward->getTotalReward($customer_id);
	 */
	public function getRewardTotal(int $customer_id): int {
		$query = $this->db->query("SELECT SUM(`points`) AS `total` FROM `" . DB_PREFIX . "customer_reward` WHERE `customer_id` = '" . (int)$customer_id . "' GROUP BY `customer_id`");

		if ($query->num_rows) {
			return (int)$query->row['total'];
		} else {
			return 0;
		}
	}
}
