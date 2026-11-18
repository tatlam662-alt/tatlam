<?php
namespace Opencart\Catalog\Controller\Product;

class Stock extends \Opencart\System\Engine\Controller {
	public function subscribe(): void {
		$this->load->language('product/product');

		$product_id = (int)($this->request->post['product_id'] ?? 0);
		$language_code = (string)($this->request->get['language'] ?? $this->config->get('config_language'));
		$language_code = in_array($language_code, ['vi-vn', 'en-gb', 'fr-fr'], true) ? $language_code : 'vi-vn';
		$token = (string)($this->request->post['stock_alert_token'] ?? '');
		$email = $this->customer->isLogged()
			? (string)$this->customer->getEmail()
			: trim((string)($this->request->post['email'] ?? ''));

		$error = '';

		if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST' || !$product_id || !$token || !isset($this->session->data['stock_alert_token']) || !hash_equals((string)$this->session->data['stock_alert_token'], $token)) {
			$error = $this->language->get('error_stock_alert_token');
		} elseif (empty($this->request->post['consent'])) {
			$error = $this->language->get('error_stock_alert_consent');
		} elseif (!oc_validate_email($email) || oc_strlen($email) > 96) {
			$error = $this->language->get('error_stock_alert_email');
		} else {
			$this->load->model('catalog/product');
			$product_info = $this->model_catalog_product->getProduct($product_id);

			if (!$product_info || (int)$product_info['quantity'] > 0) {
				$error = $this->language->get('error_stock_alert_unavailable');
			} else {
				require_once DIR_SYSTEM . 'library/pandashop/stock_alert.php';

				try {
					$result = \Opencart\System\Library\PanDaShop\StockAlert::subscribe(
						$this->db,
						$this->config,
						$this->log,
						$product_id,
						$this->customer->isLogged() ? (int)$this->customer->getId() : 0,
						$email,
						$language_code,
						oc_get_ip()
					);

					switch ($result) {
						case 'active':
							$this->session->data['stock_alert_message'] = $this->language->get('text_stock_alert_registered');
							break;
						case 'pending':
							$this->session->data['stock_alert_message'] = $this->language->get('text_stock_alert_confirmation_sent');
							break;
						case 'already':
							$this->session->data['stock_alert_message'] = $this->language->get('text_stock_alert_already');
							break;
						case 'rate_limited':
							$error = $this->language->get('error_stock_alert_rate');
							break;
						default:
							$error = $this->language->get('error_stock_alert_email_delivery');
							break;
					}
				} catch (\Exception $exception) {
					$this->log->write('Stock alert registration failed. Check the database and mail logs.');
					$error = $this->language->get('error_stock_alert_service');
				}
			}
		}

		unset($this->session->data['stock_alert_token']);

		if ($error) {
			$this->session->data['stock_alert_error'] = $error;
		}

		$this->redirectToProduct($product_id, $language_code);
	}

	public function confirm(): void {
		$this->load->language('product/product');
		$language_code = (string)($this->request->get['language'] ?? $this->config->get('config_language'));
		$language_code = in_array($language_code, ['vi-vn', 'en-gb', 'fr-fr'], true) ? $language_code : 'vi-vn';
		$token = (string)($this->request->get['token'] ?? '');

		require_once DIR_SYSTEM . 'library/pandashop/stock_alert.php';
		$product_id = \Opencart\System\Library\PanDaShop\StockAlert::confirm($this->db, $this->config, $this->log, $token);

		if ($product_id) {
			$this->session->data['stock_alert_message'] = $this->language->get('text_stock_alert_confirmed');
		} else {
			$this->session->data['stock_alert_error'] = $this->language->get('error_stock_alert_link');
		}

		$this->response->addHeader('Referrer-Policy: no-referrer');
		$this->response->addHeader('Cache-Control: no-store, private');
		$this->response->addHeader('X-Robots-Tag: noindex, nofollow');
		$this->redirectToProduct($product_id, $language_code);
	}

	public function unsubscribe(): void {
		$this->load->language('product/product');
		$language_code = (string)($this->request->get['language'] ?? $this->config->get('config_language'));
		$language_code = in_array($language_code, ['vi-vn', 'en-gb', 'fr-fr'], true) ? $language_code : 'vi-vn';
		$token = (string)($this->request->get['token'] ?? '');

		require_once DIR_SYSTEM . 'library/pandashop/stock_alert.php';
		$product_id = \Opencart\System\Library\PanDaShop\StockAlert::unsubscribe($this->db, $token);

		if ($product_id) {
			$this->session->data['stock_alert_message'] = $this->language->get('text_stock_alert_unsubscribed');
		} else {
			$this->session->data['stock_alert_error'] = $this->language->get('error_stock_alert_link');
		}

		$this->response->addHeader('Referrer-Policy: no-referrer');
		$this->response->addHeader('Cache-Control: no-store, private');
		$this->response->addHeader('X-Robots-Tag: noindex, nofollow');
		$this->redirectToProduct($product_id, $language_code);
	}

	private function redirectToProduct(int $product_id, string $language_code): void {
		if ($product_id > 0) {
			$this->response->redirect($this->url->link('product/product', 'language=' . rawurlencode($language_code) . '&product_id=' . $product_id));
		} else {
			$this->response->redirect($this->url->link('common/home', 'language=' . rawurlencode($language_code)));
		}
	}
}
