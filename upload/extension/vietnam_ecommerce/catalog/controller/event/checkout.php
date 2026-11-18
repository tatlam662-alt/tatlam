<?php
declare(strict_types=1);
namespace Opencart\Catalog\Controller\Extension\VietnamEcommerce\Event;

class Checkout extends \Opencart\System\Engine\Controller {
	public function singlePage(string &$route, array &$data, string &$code, string &$output): void {
		$file = DIR_EXTENSION . 'vietnam_ecommerce/catalog/view/template/checkout/single_page.twig';
		if (is_file($file) && isset($data['heading_title'])) {
			$this->load->language('extension/vietnam_ecommerce/checkout/single_page');
			foreach ($this->language->all() as $key => $value) $data[$key] = $value;
			$this->load->language('extension/vietnam_ecommerce/checkout/address');
			$data['districts_url'] = html_entity_decode($this->url->link('extension/vietnam_ecommerce/checkout/address.getDistricts', 'language=' . $this->config->get('config_language')));
			$data['wards_url'] = html_entity_decode($this->url->link('extension/vietnam_ecommerce/checkout/address.getWards', 'language=' . $this->config->get('config_language')));
			$data['save_address_url'] = html_entity_decode($this->url->link('extension/vietnam_ecommerce/checkout/address.saveSelection', 'language=' . $this->config->get('config_language')));
			$data['text_select'] = $this->language->get('text_select');
			$data['entry_district'] = $this->language->get('entry_district');
			$data['entry_ward'] = $this->language->get('entry_ward');
			$data['error_address'] = $this->language->get('error_address');
			$code = (string)file_get_contents($file);
		}
	}

	public function shippingAddress(string &$route, array &$data, string &$output): void {
		$this->load->language('extension/vietnam_ecommerce/checkout/address');
		$partial = $this->load->view('extension/vietnam_ecommerce/checkout/address_fields', [
			'heading_district' => $this->language->get('heading_district'),
			'entry_district' => $this->language->get('entry_district'),
			'entry_ward' => $this->language->get('entry_ward'),
			'text_select' => $this->language->get('text_select'),
			'error_address' => $this->language->get('error_address'),
			'districts_url' => html_entity_decode($this->url->link('extension/vietnam_ecommerce/checkout/address.getDistricts', 'language=' . $this->config->get('config_language'))),
			'wards_url' => html_entity_decode($this->url->link('extension/vietnam_ecommerce/checkout/address.getWards', 'language=' . $this->config->get('config_language'))),
			'save_url' => html_entity_decode($this->url->link('extension/vietnam_ecommerce/checkout/address.saveSelection', 'language=' . $this->config->get('config_language')))
		]);
		$position = strripos($output, '</fieldset>');
		if ($position !== false) $output = substr_replace($output, $partial, $position, 0);
		$this->hideOptionalFields($output, 'shipping');
	}

	public function paymentAddress(string &$route, array &$data, string &$output): void {
		$this->hideOptionalFields($output, 'payment');
	}

	public function success(string &$route, array &$data, string &$code, string &$output): void {
		$orderId = (int)($this->session->data['vietnam_ecommerce_vietqr_order_id'] ?? 0);
		if (!$orderId || !isset($data['text_message'])) return;
		unset($this->session->data['vietnam_ecommerce_vietqr_order_id']);
		$this->load->language('extension/vietnam_ecommerce/payment/vietqr');
		$this->load->model('checkout/order');
		$order = $this->model_checkout_order->getOrder($orderId);
		if (!$order || strtoupper((string)$order['currency_code']) !== 'VND') return;
		$bankId = (string)$this->config->get('payment_vietqr_bank_id');
		$accountNo = (string)$this->config->get('payment_vietqr_account_no');
		if ($bankId === '' || $accountNo === '') return;
		$amount = (int)round((float)$order['total']);
		$qrUrl = 'https://img.vietqr.io/image/' . rawurlencode($bankId) . '-' . rawurlencode($accountNo) . '-compact2.png?amount=' . $amount . '&addInfo=' . rawurlencode('PANDA' . $orderId);
		$partial = $this->load->view('extension/vietnam_ecommerce/payment/vietqr', [
			'qr_url' => $qrUrl,
			'bank_name' => (string)$this->config->get('payment_vietqr_bank_name'),
			'account_name' => (string)$this->config->get('payment_vietqr_account_name'),
			'account_no' => $accountNo,
			'order_id' => $orderId,
			'text_transfer_note' => $this->language->get('text_transfer_note'),
			'text_qr_unavailable' => $this->language->get('text_qr_unavailable'),
			'text_order_reference' => $this->language->get('text_order_reference'),
			'text_bank' => $this->language->get('text_bank'),
			'text_account_name' => $this->language->get('text_account_name'),
			'text_account_no' => $this->language->get('text_account_no'),
			'text_qr_alt' => $this->language->get('text_qr_alt'),
			'text_checkout_hint' => $this->language->get('text_checkout_hint')
		]);
		$data['text_message'] .= $partial;
	}

	private function hideOptionalFields(string &$output, string $prefix): void {
		$ids = ['company', 'address-2', 'postcode', 'fax'];
		$selectors = [];
		foreach ($ids as $id) $selectors[] = '#input-' . $prefix . '-' . $id;
		$script = '<script>(function(){document.querySelectorAll("' . implode(',', $selectors) . '").forEach(function(el){var row=el.closest(".col.mb-3");if(row){row.remove();}else{el.remove();}});})();</script>';
		$position = strripos($output, '</fieldset>');
		if ($position !== false) $output = substr_replace($output, $script, $position, 0);
	}
}
