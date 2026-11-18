<?php
namespace Opencart\Catalog\Model\Localisation;

/** Learns sanitized address names after a customer address/order is persisted. */
class VietnamAddress extends \Opencart\System\Engine\Model {
	/** Learn names only after an address/order has been persisted. */
	public function learnFromAddress(array $address): void {
		if ((int)($address['country_id'] ?? 0) !== 230 || (int)($address['zone_id'] ?? 0) < 1) {
			return;
		}

		$address2 = trim((string)($address['address_2'] ?? ''));
		$addressParts = explode('|', $address2);
		if (count($addressParts) < 2 || !preg_match('/^\s*([^:]+):\s*(.*?)\s*$/u', trim($addressParts[0]), $wardMatch)) {
			return;
		}

		$wardName = trim($wardMatch[2]);
		$names = ['hamlet' => '', 'subhamlet' => ''];
		foreach (array_slice($addressParts, 1) as $addressPart) {
			if (!preg_match('/^\s*([^:]+):\s*(.*)$/u', trim($addressPart), $addressMatch)) {
				continue;
			}

			$label = mb_strtolower(trim($addressMatch[1]), 'UTF-8');
			$normalizedLabel = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label) ?: $label);
			if (str_contains($normalizedLabel, 'to') || str_contains($normalizedLabel, 'thon') || str_contains($normalizedLabel, 'khu pho')) {
				$names['hamlet'] = trim($addressMatch[2]);
			} elseif (str_contains($normalizedLabel, 'xom') || str_contains($normalizedLabel, 'ap')) {
				$names['subhamlet'] = trim($addressMatch[2]);
			}
		}
		if ($wardName === '') {
			return;
		}

		$ward = $this->db->query("SELECT `w`.`code` FROM `" . DB_PREFIX . "vn_ward` `w` INNER JOIN `" . DB_PREFIX . "vn_province` `p` ON (`p`.`code` = `w`.`province_code`) INNER JOIN `" . DB_PREFIX . "zone` `z` ON (`z`.`zone_id` = `p`.`zone_id`) WHERE `z`.`zone_id` = '" . (int)$address['zone_id'] . "' AND `z`.`country_id` = '230' AND (`w`.`full_name` = '" . $this->db->escape($wardName) . "' OR `w`.`name` = '" . $this->db->escape($wardName) . "') LIMIT 1");
		if (!$ward->num_rows) {
			return;
		}

		foreach ($names as $category => $name) {
			$name = $this->normalizeName($name);
			if ($name === '' || $this->sameLocality($name, (string)$ward->row['code'])) {
				continue;
			}
			$this->db->query("INSERT INTO `" . DB_PREFIX . "vn_hamlet` SET `ward_code` = '" . $this->db->escape($ward->row['code']) . "', `category` = '" . $this->db->escape($category) . "', `name` = '" . $this->db->escape($name) . "', `status` = '1', `is_verified` = '0', `source_name` = 'Khách hàng PanDaShop (chưa xác minh)' ON DUPLICATE KEY UPDATE `hamlet_id` = LAST_INSERT_ID(`hamlet_id`)");
		}
	}

	private function sameLocality(string $name, string $wardCode): bool {
		$query = $this->db->query("SELECT `name`, `full_name` FROM `" . DB_PREFIX . "vn_ward` WHERE `code` = '" . $this->db->escape($wardCode) . "' LIMIT 1");
		if (!$query->num_rows) {
			return false;
		}

		$normalize = static function (string $value): string {
			$value = trim((string)preg_replace('/^(?:Phường|Xã|Thị trấn|Đặc khu)\s+/iu', '', $value));
			return mb_strtolower($value, 'UTF-8');
		};

		$normalizedName = $normalize($name);
		return $normalizedName !== '' && ($normalizedName === $normalize((string)$query->row['name']) || $normalizedName === $normalize((string)$query->row['full_name']));
	}

	private function normalizeName(string $name): string {
		$name = html_entity_decode(strip_tags($name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$name = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $name) ?? '';
		$name = preg_replace('/\s+/u', ' ', trim($name)) ?? '';

		if ($name === '' || strlen($name) > 512 || preg_match_all('/./us', $name) > 128 || !preg_match('/[\p{L}]/u', $name) || !preg_match('/^[\p{L}\p{N}][\p{L}\p{N}\s.\-\'(),\/]{1,127}$/u', $name)) {
			return '';
		}
		if (preg_match('/^(?:tổ|thôn|xóm|ấp|khóm|đội)\s+\d+$/ui', $name) || preg_match('/^(?:không có|không biết|khác|n\/?a|none|null|test)$/iu', $name)) {
			return '';
		}

		return $name;
	}
}
