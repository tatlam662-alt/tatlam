<?php
namespace Opencart\Catalog\Controller\Information;

class OrderLookup extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('information/order_lookup');
		$language = (string)($this->request->get['language'] ?? $this->config->get('config_language'));
		$this->document->setTitle($this->language->get('heading_title'));

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_intro'] = $this->language->get('text_intro');
		$data['entry_order_id'] = $this->language->get('entry_order_id');
		$data['entry_email'] = $this->language->get('entry_email');
		$data['button_lookup'] = $this->language->get('button_lookup');
		$data['text_privacy'] = $this->language->get('text_privacy');
		$data['text_date_ordered'] = $this->language->get('text_date_ordered');
		$data['text_order_status'] = $this->language->get('text_order_status');
		$data['error_lookup'] = '';
		$data['error_rate_limit'] = '';
		$data['order'] = [];
		$data['order_id'] = '';
		$data['email'] = '';
		$data['action'] = $this->url->link('information/order_lookup', 'language=' . $language);

		$attempt_key = 'guest_order_lookup';
		$attempts = $this->session->data[$attempt_key] ?? ['count' => 0, 'locked_until' => 0];
		$now = time();
		$locked_until = is_array($attempts) ? (int)($attempts['locked_until'] ?? 0) : 0;

		if (!is_array($attempts) || ($locked_until > 0 && $locked_until <= $now)) {
			$attempts = ['count' => 0, 'locked_until' => 0];
		}

		if ((int)$attempts['locked_until'] > $now) {
			$data['error_rate_limit'] = $this->language->get('error_rate_limit');
		}

		if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) === 'POST') {
			$order_id = $this->request->post['order_id'] ?? '';
			$email = $this->request->post['email'] ?? '';
			$data['order_id'] = is_scalar($order_id) ? trim((string)$order_id) : '';
			$data['email'] = is_scalar($email) ? trim((string)$email) : '';
			$bucket = intdiv($now, 900);
			$bucket_expiry = max(1, 900 - ($now % 900));
			$rate_secret = (string)$this->config->get('config_encryption');
			$order_rate_key = ctype_digit($data['order_id']) && (int)$data['order_id'] > 0
				? 'guest_order_lookup_order.' . hash_hmac('sha256', $data['order_id'], $rate_secret) . '.' . $bucket
				: '';
			$email_rate_key = filter_var($data['email'], FILTER_VALIDATE_EMAIL)
				? 'guest_order_lookup_email.' . hash_hmac('sha256', strtolower($data['email']), $rate_secret) . '.' . $bucket
				: '';
			$order_attempts = $order_rate_key !== '' ? (int)$this->cache->get($order_rate_key) : 0;
			$email_attempts = $email_rate_key !== '' ? (int)$this->cache->get($email_rate_key) : 0;
			$shared_rate_limited = $order_attempts >= 20 || $email_attempts >= 20;

			if ((int)$attempts['locked_until'] > $now || $shared_rate_limited) {
				$data['error_rate_limit'] = $this->language->get('error_rate_limit');
			} else {
				$attempts['count'] = (int)$attempts['count'] + 1;
				$this->session->data[$attempt_key] = $attempts;

				if (ctype_digit($data['order_id']) && (int)$data['order_id'] > 0 && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
					$this->load->model('account/order');
					$order = $this->model_account_order->getGuestOrderStatus((int)$data['order_id'], $data['email']);

					if ($order) {
						unset($this->session->data[$attempt_key]);
						$data['order'] = [
							'id' => (int)$order['order_id'],
							'status' => (string)$order['status'],
							'date_added' => date($this->language->get('date_format_short'), strtotime((string)$order['date_added']))
						];
					} else {
						$data['error_lookup'] = $this->language->get('error_lookup');
					}
				} else {
					$data['error_lookup'] = $this->language->get('error_lookup');
				}

				if (!$data['order']) {
					$order_attempts++;
					$email_attempts++;

					if ($order_rate_key !== '') {
						$this->cache->set($order_rate_key, $order_attempts, $bucket_expiry);
					}

					if ($email_rate_key !== '') {
						$this->cache->set($email_rate_key, $email_attempts, $bucket_expiry);
					}

					if ((int)$attempts['count'] >= 5 || $order_attempts >= 20 || $email_attempts >= 20) {
						$attempts['locked_until'] = $now + 900;
						$this->session->data[$attempt_key] = $attempts;
						$data['error_lookup'] = '';
						$data['error_rate_limit'] = $this->language->get('error_rate_limit');
					}
				}
			}
		}

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', 'language=' . $language)
			],
			[
				'text' => $data['heading_title'],
				'href' => $this->url->link('information/order_lookup', 'language=' . $language)
			]
		];

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/order_lookup', $data));
	}
}
