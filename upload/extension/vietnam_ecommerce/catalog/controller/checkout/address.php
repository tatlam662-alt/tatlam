<?php
declare(strict_types=1);

namespace Opencart\Catalog\Controller\Extension\VietnamEcommerce\Checkout;

class Address extends \Opencart\System\Engine\Controller {
	public function getDistricts(): void {
		// The official 2025 structure no longer has district-level checkout data.
		$this->respond([]);
	}

	public function getWards(): void {
		$provinceCode = $this->code($this->request->get['province_code'] ?? $this->request->post['province_code'] ?? null);
		$zoneId = $this->positiveInt($this->request->get['zone_id'] ?? $this->request->post['zone_id'] ?? null);
		if ($provinceCode === '' && $zoneId) {
			$province = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "vn_province` WHERE `zone_id` = '" . $zoneId . "' LIMIT 1");
			$provinceCode = $province->num_rows ? (string)$province->row['code'] : '';
		}
		if ($provinceCode === '') { $this->respond(['error' => 'Vui lòng chọn tỉnh/thành phố.'], 400); return; }
		$query = $this->db->query("SELECT `code`, `name`, `full_name` FROM `" . DB_PREFIX . "vn_ward` WHERE `province_code` = '" . $this->db->escape($provinceCode) . "' ORDER BY `name` ASC");
		$this->respond($query->rows);
	}

	public function saveSelection(): void {
		$zoneId = $this->positiveInt($this->request->post['zone_id'] ?? null);
		$wardCode = $this->code($this->request->post['ward_code'] ?? null);
		$query = $this->db->query("SELECT `p`.`name` AS `province_name`, `w`.`code` AS `ward_code`, `w`.`name` AS `ward_name`, `w`.`full_name` AS `ward_full_name` FROM `" . DB_PREFIX . "vn_ward` `w` INNER JOIN `" . DB_PREFIX . "vn_province` `p` ON (`p`.`code` = `w`.`province_code`) WHERE `p`.`zone_id` = '" . $zoneId . "' AND `w`.`code` = '" . $this->db->escape($wardCode) . "' LIMIT 1");
		if (!$zoneId || $wardCode === '' || !$query->num_rows) { $this->respond(['error' => 'Vui lòng chọn tỉnh/thành phố và phường/xã.'], 422); return; }
		$this->session->data['vn_address_selection'] = $query->row;
		$this->respond(['success' => true] + $query->row);
	}

	private function positiveInt(mixed $value): int {
		if (!is_scalar($value)) return 0;
		$id = filter_var((string)$value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		return $id === false ? 0 : (int)$id;
	}
	private function code(mixed $value): string {
		if (!is_scalar($value)) return '';
		$value = trim((string)$value);
		return preg_match('/^[A-Za-z0-9-]{1,32}$/', $value) ? $value : '';
	}
	private function respond(array $payload, int $status = 200): void {
		if ($status !== 200) $this->response->addHeader('HTTP/1.1 ' . $status . ' Error');
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput((string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
	}
}