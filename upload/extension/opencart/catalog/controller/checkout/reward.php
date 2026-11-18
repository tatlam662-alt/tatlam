<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Checkout;
/**
 * Class Reward
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Checkout
 */
class Reward extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(bool $checkout = false): string {
		if ($this->config->get('total_reward_status')) {
			$available = (int)$this->customer->getRewardPoints();
			$products = $this->cart->getProducts();
			require_once DIR_SYSTEM . 'library/pandashop/reward.php';
			$maximum = \Opencart\System\Library\PanDaShop\Reward::getMaximumRedeemablePoints($products, $available);

			if ($maximum > 0) {
				$this->load->language('extension/opencart/checkout/reward');

				$data['heading_title'] = sprintf($this->language->get('heading_title'), $available);
				$data['entry_reward'] = sprintf($this->language->get('entry_reward'), $maximum);
				$data['text_reward_limit'] = sprintf($this->language->get('text_reward_limit'), $maximum, $maximum * \Opencart\System\Library\PanDaShop\Reward::POINT_VALUE);
				$data['text_reward_value'] = $this->language->get('text_reward_value');
				$data['text_reward_apply'] = $this->language->get('text_reward_apply');
				$data['text_reward_remove'] = $this->language->get('text_reward_remove');
				$data['text_reward_clear'] = $this->language->get('text_reward_clear');
				$data['text_reward_error'] = $this->language->get('text_reward_error');
				$data['checkout'] = $checkout;
				$data['refresh'] = $this->url->link('checkout/confirm.confirm', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);

				$data['save'] = $this->url->link('extension/opencart/checkout/reward.save', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);
				$data['list'] = $this->url->link('checkout/cart.list', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);

				if (isset($this->session->data['reward'])) {
					$data['reward'] = $this->session->data['reward'];
				} else {
					$data['reward'] = '';
				}

				$data['maximum'] = $maximum;
				$data['applied'] = !empty($this->session->data['reward']);

				return $this->load->view('extension/opencart/checkout/reward', $data);
			}
		}

		return '';
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/opencart/checkout/reward');

		$json = [];

		$raw_reward = trim((string)($this->request->post['reward'] ?? ''));
		$reward = ctype_digit($raw_reward) ? (int)$raw_reward : -1;

		if (!$this->config->get('total_reward_status')) {
			$json['error'] = $this->language->get('error_status');
		}

		if (!$this->customer->isLogged()) {
			$json['error'] = $this->language->get('error_customer');
		}

		require_once DIR_SYSTEM . 'library/pandashop/reward.php';
		$products = $this->cart->getProducts();
		$available = $this->customer->getRewardPoints();
		$maximum = \Opencart\System\Library\PanDaShop\Reward::getMaximumRedeemablePoints($products, $available);

		if ($reward < 0) {
			$json['error'] = $this->language->get('error_reward');
		} elseif ($reward > $available) {
			$json['error'] = sprintf($this->language->get('error_points'), $reward);
		}

		if ($reward > $maximum) {
			$json['error'] = sprintf($this->language->get('error_maximum'), $maximum);
		}

		if (!$json) {
			$json['success'] = $this->language->get('text_success');

			if ($reward) {
				$this->session->data['reward'] = $reward;
			} else {
				unset($this->session->data['reward']);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
