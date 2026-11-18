<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Api;
/**
 * Class Reward
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Api
 */
class Reward extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return array<string, mixed>
	 */
	public function index(): array {
		$this->load->language('extension/opencart/api/reward');

		$output = [];

		$raw_reward = trim((string)($this->request->post['reward'] ?? ''));

		if ($raw_reward === '' && ($this->request->get['call'] ?? '') == 'confirm') {
			return [];
		}

		$reward = $raw_reward === '' ? 0 : (ctype_digit($raw_reward) ? (int)$raw_reward : -1);

		if ($reward < 0) {
			$output['error'] = $this->language->get('error_confirm');
		}

		// 1. Validate customer data exists
		if (!isset($this->session->data['customer'])) {
			$output['error'] = $this->language->get('error_customer');
		}

		// 2. Validate cart has products.
		if (!$this->cart->hasProducts()) {
			$output['error'] = $this->language->get('error_product');
		}

		if (!$this->config->get('total_reward_status')) {
			$output['error'] = $this->language->get('error_status');
		}

		if (!$output) {
			$available = $this->customer->getRewardPoints();

			$products = $this->cart->getProducts();
			require_once DIR_SYSTEM . 'library/pandashop/reward.php';
			$maximum = \Opencart\System\Library\PanDaShop\Reward::getMaximumRedeemablePoints($products, $available);

			if ($reward > $available) {
				$output['error'] = sprintf($this->language->get('error_points'), $reward);
			}

			if ($reward > $maximum) {
				$output['error'] = sprintf($this->language->get('error_maximum'), $maximum);
			}
		}

		if (!$output) {
			$this->session->data['reward'] = $reward;

			$output['success'] = $this->language->get('text_success');
		}

		return $output;
	}
}
