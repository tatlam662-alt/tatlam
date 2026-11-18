<?php
declare(strict_types=1);
namespace Opencart\Catalog\Model\Extension\VietnamEcommerce\Payment;

class Vietqr extends \Opencart\System\Engine\Model {
	public function getMethods(array $address = []): array {
		$this->load->language('extension/vietnam_ecommerce/payment/vietqr');
		if (!$this->config->get('payment_vietqr_status') || !$this->config->get('payment_vietqr_bank_id') || !$this->config->get('payment_vietqr_account_no')) return [];
		if ($this->cart->hasSubscription()) return [];
		$option = ['vietqr' => ['code' => 'vietqr.vietqr', 'name' => $this->language->get('text_payment_method')]];
		return ['code' => 'vietqr', 'name' => $this->language->get('heading_title'), 'option' => $option, 'sort_order' => (int)$this->config->get('payment_vietqr_sort_order')];
	}
}
