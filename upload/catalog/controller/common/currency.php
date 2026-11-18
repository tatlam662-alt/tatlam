<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Currency
 *
 * Can be called from $this->load->controller('common/currency');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Currency extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('common/currency');

		$current_language = isset($this->request->get['language']) && is_scalar($this->request->get['language']) ? (string)$this->request->get['language'] : (string)$this->config->get('config_language');
		$data['action'] = $this->url->link('common/currency.save', 'language=' . rawurlencode($current_language));

		// Currencies
		$data['currencies'] = [];

		$this->load->model('localisation/currency');

		$results = $this->model_localisation_currency->getCurrencies();

		foreach ($results as $result) {
			if ($result['status']) {
				$data['currencies'][$result['code']] = $result;
			}
		}

		$code = (string)($this->session->data['currency'] ?? '');

		if (!$data['currencies']) {
			return '';
		}

		if (!isset($data['currencies'][$code])) {
			$default_code = (string)$this->config->get('config_currency');
			$code = isset($data['currencies'][$default_code]) ? $default_code : (string)array_key_first($data['currencies']);
			$this->session->data['currency'] = $code;
		}

		$data['code'] = $code;
		$data['title'] = $data['currencies'][$code]['title'];
		$data['symbol_left'] = $data['currencies'][$code]['symbol_left'];
		$data['symbol_right'] = $data['currencies'][$code]['symbol_right'];

		$url_data = $this->request->get;

		if (isset($url_data['route']) && is_scalar($url_data['route'])) {
			$route = (string)$url_data['route'];
		} else {
			$route = $this->config->get('action_default');
		}

		unset($url_data['route']);
		unset($url_data['_route_']);

		$url = '';

		if ($url_data) {
			$url .= '&' . urldecode(http_build_query($url_data, '', '&'));
		}

		$data['redirect'] = $this->url->link($route, $url);

		return $this->load->view('common/currency', $data);
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('common/currency');

		$json = [];

		$post_info = [
			'code'     => isset($this->request->post['code']) && is_scalar($this->request->post['code']) ? trim((string)$this->request->post['code']) : '',
			'redirect' => isset($this->request->post['redirect']) && is_scalar($this->request->post['redirect']) ? (string)$this->request->post['redirect'] : ''
		];

		// Currency
		$this->load->model('localisation/currency');

		$currency_info = $this->model_localisation_currency->getCurrencyByCode($post_info['code']);

		if (!$currency_info) {
			$json['error'] = $this->language->get('error_currency');
		}

		if (!$json) {
			$this->session->data['currency'] = $post_info['code'];

			unset($this->session->data['order_id']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);

			$option = [
				'expires'  => time() + 60 * 60 * 24 * 30,
				'path'     => $this->config->get('session_path'),
				'secure'   => !empty($this->request->server['HTTPS']),
				'httponly' => true,
				'samesite' => 'Lax'
			];

			setcookie('currency', $this->session->data['currency'], $option);

			if ($post_info['redirect']) {
				$redirect = urldecode(html_entity_decode($post_info['redirect'], ENT_QUOTES, 'UTF-8'));
			} else {
				$redirect = '';
			}

			if (str_starts_with($redirect, $this->config->get('config_url'))) {
				$json['redirect'] = $redirect;
			} else {
				$json['redirect'] = $this->url->link($this->config->get('action_default'), 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
