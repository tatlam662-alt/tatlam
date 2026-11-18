<?php
namespace Opencart\System\Library\PanDaShop;

class Reward {
	public const POINT_VALUE = 1000;
	public const REDEMPTION_CAP_PERCENT = 20;
	public const CHECKIN_STREAK_DAYS = 7;
	public const CHECKIN_STREAK_BONUS = 5;

	public static function getCheckinCycleDay(int $streak): int {
		if ($streak < 1) {
			return 1;
		}

		return (($streak - 1) % self::CHECKIN_STREAK_DAYS) + 1;
	}

	public static function getCheckinBasePoints(int $streak): int {
		return self::getCheckinCycleDay($streak);
	}

	public static function getCheckinBonus(int $streak): int {
		return self::getCheckinCycleDay($streak) === self::CHECKIN_STREAK_DAYS
			? self::CHECKIN_STREAK_BONUS
			: 0;
	}

	/**
	 * @param array<int, array<string, mixed>> $products
	 *
	 * @return float
	 */
	public static function getEligibleSubtotal(array $products): float {
		$subtotal = 0.0;

		foreach ($products as $product) {
			if (!empty($product['subscription'])) {
				continue;
			}

			$subtotal += max(0.0, (float)($product['total'] ?? 0));
		}

		return $subtotal;
	}

	/**
	 * @param array<int, array<string, mixed>> $products
	 * @param float                            $available_points
	 *
	 * @return int
	 */
	public static function getMaximumRedeemablePoints(array $products, float $available_points): int {
		$eligible_subtotal = self::getEligibleSubtotal($products);
		$cart_limit = (int)floor(($eligible_subtotal * self::REDEMPTION_CAP_PERCENT / 100) / self::POINT_VALUE);

		return max(0, min((int)floor($available_points), $cart_limit));
	}
}
