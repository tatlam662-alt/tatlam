<?php
declare(strict_types=1);

namespace Opencart\Catalog\Controller\Extension\VietnamEcommerce\Address;

class Lookup extends \Opencart\System\Engine\Controller {
	public function districts(): void { $this->json([]); }
	public function wards(): void {
		$zoneId = (int)($this->request->get['zone_id'] ?? 0);
		$provinceCode = trim((string)($this->request->get['province_code'] ?? ''));
		if ($provinceCode === '' && $zoneId > 0) {
			$p = $this->db->query("SELECT `code` FROM `" . DB_PREFIX . "vn_province` WHERE `zone_id` = '" . $zoneId . "' LIMIT 1");
			$provinceCode = $p->num_rows ? (string)$p->row['code'] : '';
		}
		if ($provinceCode === '') { $this->json([]); return; }
		$query = $this->db->query("SELECT `code`, `name`, `full_name` FROM `" . DB_PREFIX . "vn_ward` WHERE `province_code` = '" . $this->db->escape($provinceCode) . "' ORDER BY `name` ASC");
		$this->json($query->rows);
	}
	public function save(): void {
		$zoneId = (int)($this->request->post['zone_id'] ?? 0);
		$wardCode = trim((string)($this->request->post['ward_code'] ?? ''));
		$query = $this->db->query("SELECT `p`.`code` AS `province_code`, `p`.`name` AS `province_name`, `w`.`code` AS `ward_code`, `w`.`name` AS `ward_name`, `w`.`full_name` AS `ward_full_name` FROM `" . DB_PREFIX . "vn_ward` `w` INNER JOIN `" . DB_PREFIX . "vn_province` `p` ON (`p`.`code` = `w`.`province_code`) WHERE `p`.`zone_id` = '" . $zoneId . "' AND `w`.`code` = '" . $this->db->escape($wardCode) . "' LIMIT 1");
		if ($zoneId < 1 || !preg_match('/^[A-Za-z0-9-]{1,32}$/', $wardCode) || !$query->num_rows) { $this->json(['error' => 'Địa chỉ đã chọn không hợp lệ.']); return; }
		$this->session->data['vn_address_selection'] = $query->row;
		$this->json(['success' => true] + $query->row);
	}
	private function json(array $data): void { $this->response->addHeader('Content-Type: application/json; charset=utf-8'); $this->response->setOutput((string)json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)); }
}