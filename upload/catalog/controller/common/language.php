<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Language
 *
 * Can be called from $this->load->controller('common/language');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Language extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('common/language');

		$current_language = isset($this->request->get['language']) && is_scalar($this->request->get['language']) ? (string)$this->request->get['language'] : (string)$this->config->get('config_language');
		$data['action'] = $this->url->link('common/language.save', 'language=' . rawurlencode($current_language));

		$this->load->model('localisation/language');
		$results = $this->model_localisation_language->getLanguages();

		$data['languages'] = [];
		$request_lng = isset($this->request->get['language']) && is_scalar($this->request->get['language']) ? (string)$this->request->get['language'] : '';
		$code = $request_lng !== '' ? $request_lng : (string)$this->config->get('config_language');

		foreach ($results as $result) {
			// Keep the native Vietnamese/English switch readable even if DB labels are blank or corrupted.
			$result['name'] = match (strtolower((string)$result['code'])) {
				'vi-vn' => html_entity_decode('Ti&#7871;ng Vi&#7879;t', ENT_QUOTES, 'UTF-8'),
				'en-gb' => 'English',
				default => trim((string)($result['name'] ?? '')) ?: (string)$result['code']
			};

			$data['languages'][$result['code']] = $result;

			if ($result['code'] == $request_lng) {
				$code = $result['code'];
			}
		}

		if (!$data['languages']) {
			return '';
		}

		// Recover gracefully if the configured language was disabled or removed.
		if (!isset($data['languages'][$code])) {
			$code = (string)array_key_first($data['languages']);
		}

		$data['name'] = $data['languages'][$code]['name'];
		$data['image'] = $data['languages'][$code]['image'];
		$data['code'] = $code;

		// Build the url
		$url_data = $this->request->get;

		if (isset($url_data['route']) && is_scalar($url_data['route'])) {
			$route = (string)$url_data['route'];
		} else {
			$route = $this->config->get('action_default');
		}

		unset($url_data['route']);
		unset($url_data['_route_']);
		unset($url_data['language']);

		$url = '';

		if ($url_data) {
			$url .= '&' . urldecode(http_build_query($url_data, '', '&'));
		}

		// Make sure we are not using SEO urls
		$data['redirect'] = HTTP_SERVER . 'index.php?route=' . $route . $url;

		foreach ($data['languages'] as $language_code => &$language) {
			$language['href'] = $data['redirect'] . '&language=' . rawurlencode((string)$language_code);
		}
		unset($language);

		return $this->load->view('common/language', $data);
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('common/language');

		$json = [];

		$post_info = [
			'code'     => isset($this->request->post['code']) && is_scalar($this->request->post['code']) ? trim((string)$this->request->post['code']) : '',
			'redirect' => isset($this->request->post['redirect']) && is_scalar($this->request->post['redirect']) ? (string)$this->request->post['redirect'] : ''
		];

		// Language
		$this->load->model('localisation/language');

		$language_info = $this->model_localisation_language->getLanguageByCode($post_info['code']);

		if (!$language_info || empty($language_info['status'])) {
			$json['error'] = $this->language->get('error_language');
		}

		if (!$json) {
			unset($this->session->data['order_id']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);

			if ($post_info['redirect']) {
				$redirect = urldecode(html_entity_decode($post_info['redirect'], ENT_QUOTES, 'UTF-8'));

				// Build the url
				$url_info = parse_url($redirect);

				$query = [];

				if (!empty($url_info['query'])) {
					parse_str($url_info['query'], $query);
				}

				$route = isset($query['route']) && is_scalar($query['route']) ? (string)$query['route'] : (string)$this->config->get('action_default');

				unset($query['route']);

				$query['language'] = $post_info['code'];

				$redirect = $this->url->link($route, $query, true);
			} else {
				$redirect = '';
			}

			if (str_starts_with($redirect, $this->config->get('config_url'))) {
				$json['redirect'] = $redirect;
			} else {
				$json['redirect'] = $this->url->link($this->config->get('action_default'), 'language=' . $post_info['code'], true);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
