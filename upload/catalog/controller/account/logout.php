<?php
namespace Opencart\Catalog\Controller\Account;
/**
 * Class Logout
 *
 * @package Opencart\Catalog\Controller\Account
 */
class Logout extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		if ($this->customer->isLogged()) {
			$this->customer->logout();

			unset($this->session->data['order_id']);
			unset($this->session->data['customer']);
			unset($this->session->data['customer_token']);
			unset($this->session->data['shipping_address']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_address']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['comment']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['checkout_recipient']);
			unset($this->session->data['auto_logout_token']);
			unset($this->session->data['auto_logout_pending_at']);

			$this->response->redirect($this->url->link('account/logout', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
		}

		$this->load->language('account/logout');

		$this->document->setTitle($this->language->get('heading_title'));
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_message'] = $this->language->get('text_message');
		$data['is_logout'] = true;
		$data['text_logout_success_title'] = $this->language->get('text_logout_success_title');
		$data['text_logout_success_body'] = $this->language->get('text_logout_success_body');
		$data['text_logout_shop_title'] = $this->language->get('text_logout_shop_title');
		$data['text_logout_shop_body'] = $this->language->get('text_logout_shop_body');
		$data['text_logout_login_title'] = $this->language->get('text_logout_login_title');
		$data['text_logout_login_body'] = $this->language->get('text_logout_login_body');
		$data['text_logout_home_button'] = $this->language->get('text_logout_home_button');

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_logout'),
			'href' => $this->url->link('account/logout', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		$data['continue'] = $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['login'] = $this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/logout', $data));
	}

	/** End the customer session when the visitor exits the shop. */
	public function beaconLogout(): void {
		$this->response->addHeader('Content-Type: application/json');
		$this->response->addHeader('Cache-Control: no-store, private');

		$token = (string)($this->request->post['token'] ?? '');
		$session_token = (string)($this->session->data['auto_logout_token'] ?? '');

		if ($this->request->server['REQUEST_METHOD'] === 'POST' && $this->customer->isLogged() && $session_token !== '' && hash_equals($session_token, $token)) {
			$this->session->data['auto_logout_pending_at'] = time();
		}

		$this->response->setOutput(json_encode(['success' => true]));
	}
}
