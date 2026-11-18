<?php
declare(strict_types=1);
namespace Opencart\Catalog\Model\Extension\VietnamEcommerce\Shipping;

class Ghtk extends \Opencart\System\Engine\Model {
	public function getQuote(array $address): array {
		$this->load->language('extension/vietnam_ecommerce/shipping/ghtk');
		if (!$this->config->get('shipping_ghtk_status')) return [];

		$token = trim((string)$this->config->get('shipping_ghtk_token'));
		$districtId = (int)($address['district_id'] ?? $this->session->data['district_id'] ?? 0);
		$wardId = (int)($address['ward_id'] ?? $this->session->data['ward_id'] ?? 0);
		$zoneId = (int)($address['zone_id'] ?? 0);
		$zoneName = trim((string)($address['zone'] ?? ''));
		$district = $this->getDistrict($districtId, $zoneId);
		$ward = $this->getWard($wardId, $districtId);
		$quoteData = [];

		if ($token !== '' && $district && $ward && $zoneName !== '') {
			$payload = [
				'pick_province' => (string)$this->config->get('shipping_ghtk_pick_province'),
				'pick_district' => (string)$this->config->get('shipping_ghtk_pick_district'),
				'province' => $zoneName,
				'district' => $district,
				'ward' => $ward,
				'address' => trim((string)($address['address_1'] ?? '')),
				'weight' => max(1, (int)$this->config->get('shipping_ghtk_default_weight')),
				'value' => (int)round((float)($this->cart->getSubTotal() ?? 0)),
				'deliver_option' => 'none'
			];
			$fee = $this->requestFee($token, $payload);

			if ($fee !== null) {
				$quoteData['ghtk'] = $this->makeQuote($fee, $this->language->get('text_description'));
			}
		}

		if (!$quoteData && $this->config->get('shipping_ghtk_fallback_enabled')) {
			$fee = max(0, (float)$this->config->get('shipping_ghtk_fallback_fee'));
			$quoteData['ghtk'] = $this->makeQuote($fee, $this->language->get('text_fallback_description'));
		}

		if (!$quoteData) {
			return [];
		}

		return [
			'code' => 'ghtk',
			'name' => $this->language->get('heading_title'),
			'quote' => $quoteData,
			'sort_order' => (int)$this->config->get('shipping_ghtk_sort_order'),
			'error' => false
		];
	}

	private function getDistrict(int $districtId, int $zoneId): string {
		if (!$districtId) return '';
		$query = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "zone_district` WHERE `district_id` = '" . $districtId . "' AND `zone_id` = '" . $zoneId . "' AND `status` = '1'");
		return $query->num_rows ? (string)$query->row['name'] : '';
	}

	private function getWard(int $wardId, int $districtId): string {
		if (!$wardId || !$districtId) return '';
		$query = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "zone_ward` WHERE `ward_id` = '" . $wardId . "' AND `district_id` = '" . $districtId . "' AND `status` = '1'");
		return $query->num_rows ? (string)$query->row['name'] : '';
	}

	private function requestFee(string $token, array $payload): ?float {
		if (!function_exists('curl_init')) return null;
		$url = 'https://services.giaohangtietkiem.vn/services/shipment/fee?' . http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
		$handle = curl_init($url);
		if ($handle === false) return null;
		curl_setopt_array($handle, [
			CURLOPT_HTTPGET => true,
			CURLOPT_HTTPHEADER => ['Token: ' . $token],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 3,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_NOSIGNAL => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_SSL_VERIFYHOST => 2
		]);
		$body = curl_exec($handle);
		$status = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
		$error = curl_errno($handle);
		curl_close($handle);
		if ($error !== CURLE_OK || !is_string($body) || $status < 200 || $status >= 300) return null;
		$result = json_decode($body, true);
		if (!is_array($result) || empty($result['success']) || !isset($result['fee']['fee']) || !is_numeric($result['fee']['fee'])) return null;
		return max(0, (float)$result['fee']['fee']);
	}

	private function makeQuote(float $fee, string $name): array {
		$taxClassId = (int)$this->config->get('shipping_ghtk_tax_class_id');
		$currency = (string)($this->session->data['currency'] ?? $this->config->get('config_currency'));
		return [
			'code' => 'ghtk.ghtk',
			'name' => $name,
			'cost' => $fee,
			'tax_class_id' => $taxClassId,
			'text' => $this->currency->format($this->tax->calculate($fee, $taxClassId, (bool)$this->config->get('config_tax')), $currency)
		];
	}
}
