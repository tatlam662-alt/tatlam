<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Module;
/**
 * Class Account
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Module
 */
class Account extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(array $setting = []): string {
		// This navigation is rendered centrally by common/column_left on account pages.
		// Ignore stale layout assignments that would otherwise render a second copy.
		if (empty($setting['panda_account_sidebar'])) {
			return '';
		}

		$this->load->language('extension/opencart/module/account');
		$this->load->language('account/account');

		$data['logged'] = $this->customer->isLogged();
		$data['current_route'] = (string)($this->request->get['route'] ?? '');
		foreach (['account', 'orders', 'wishlist', 'profile', 'password', 'address', 'reward', 'returns', 'logout'] as $key) {
			$data['text_nav_' . $key] = $this->language->get('text_nav_' . $key);
		}
		$data['customer_name'] = '';
		$data['customer_group_name'] = '';

		if ($data['logged']) {
			$data['customer_name'] = trim($this->customer->getLastName() . ' ' . $this->customer->getFirstName());
			$this->load->model('account/order');
			require_once DIR_SYSTEM . 'library/pandashop/membership.php';
			$complete_status_ids = array_values(array_unique(array_filter(array_map('intval', (array)$this->config->get('config_complete_status')), static fn(int $status_id): bool => $status_id > 0)));
			$completed_orders = $complete_status_ids ? $this->model_account_order->getTotalOrders($complete_status_ids) : 0;
			$progress = \Opencart\System\Library\PanDaShop\Membership::getProgress($completed_orders);
			$data['customer_group_name'] = $this->language->get('text_member_tier_' . $progress['current']['key']);
		}

		$data['register'] = $this->url->link('account/register', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['login'] = $this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['logout'] = $this->url->link('account/logout', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['forgotten'] = $this->url->link('account/forgotten', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['account'] = $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['edit'] = $this->url->link('account/edit', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['password'] = $this->url->link('account/password', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['address'] = $this->url->link('account/address', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['wishlist'] = $this->url->link('account/wishlist', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['order'] = $this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['download'] = $this->url->link('account/download', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['reward'] = $this->url->link('account/reward', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['return'] = $this->url->link('account/returns', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['transaction'] = $this->url->link('account/transaction', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['newsletter'] = $this->url->link('account/newsletter', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['subscription'] = $this->url->link('account/subscription', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['payment_method'] = $this->url->link('account/payment_method', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));

		return $this->load->view('extension/opencart/module/account', $data);
	}
}
