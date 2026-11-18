<?php
namespace Opencart\Catalog\Controller\Checkout;
/**
 * Class PaymentMethod
 *
 * @package Opencart\Catalog\Controller\Checkout
 */
class PaymentMethod extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('checkout/payment_method');

		if (isset($this->session->data['payment_method'])) {
			$data['payment_method'] = $this->session->data['payment_method']['name'];
			$data['code'] = $this->session->data['payment_method']['code'];
		} else {
			$data['payment_method'] = '';
			$data['code'] = '';
		}

		if (isset($this->session->data['comment'])) {
			$data['comment'] = $this->session->data['comment'];
		} else {
			$data['comment'] = '';
		}

		if (isset($this->session->data['agree'])) {
			$data['agree'] = $this->session->data['agree'];
		} else {
			$data['agree'] = '';
		}

		// Information
		$this->load->model('catalog/information');

		$information_info = $this->model_catalog_information->getInformation((int)$this->config->get('config_checkout_id'));

		if ($information_info) {
			$data['text_agree'] = sprintf($this->language->get('text_agree'), $this->url->link('information/information.info', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&information_id=' . $this->config->get('config_checkout_id')), $information_info['title']);
		} else {
			$data['text_agree'] = '';
		}

		$data['language'] = ($this->request->get['language'] ?? $this->config->get('config_language'));
		$data['error_comment_save'] = $this->language->get('error_comment_save');

		return $this->load->view('checkout/payment_method', $data);
	}

	/**
	 * Get Methods
	 *
	 * @return void
	 */
	public function getMethods(): void {
		$this->load->language('checkout/payment_method');

		$json = [];

		// Validate cart has products and has stock.
		if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout')) || !$this->cart->hasMinimum()) {
			$json['redirect'] = $this->url->link('checkout/cart', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);
		}

		if (!$json) {
			// Validate if customer session data is set
			if (!isset($this->session->data['customer'])) {
				$json['error'] = $this->language->get('error_customer');
			}

			if ($this->config->get('config_checkout_payment_address') && !isset($this->session->data['payment_address'])) {
				$json['error'] = $this->language->get('error_payment_address');
			}

			// Validate shipping
			if ($this->cart->hasShipping()) {
				// Validate shipping address
				if (!isset($this->session->data['shipping_address']['address_id'])) {
					$json['error'] = $this->language->get('error_shipping_address');
				}

				// Validate shipping method
				if (!isset($this->session->data['shipping_method'])) {
					$json['error'] = $this->language->get('error_shipping_method');
				}
			}
		}

		if (!$json) {
			$payment_address = [];

			if ($this->config->get('config_checkout_payment_address') && isset($this->session->data['payment_address'])) {
				$payment_address = $this->session->data['payment_address'];
			} elseif ($this->config->get('config_checkout_shipping_address') && isset($this->session->data['shipping_address']['address_id'])) {
				$payment_address = $this->session->data['shipping_address'];
			}

			// Payment method
			$this->load->model('checkout/payment_method');

			$payment_methods = $this->model_checkout_payment_method->getMethods($payment_address);

			if ($payment_methods) {
				$json['payment_methods'] = $this->session->data['payment_methods'] = $payment_methods;
			} else {
				$json['error'] = sprintf($this->language->get('error_no_payment'), $this->url->link('information/contact', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'))));
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('checkout/payment_method');

		$json = [];

		if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST') {
			$this->response->addHeader('Allow: POST');
			$this->response->addHeader('HTTP/1.1 405 Method Not Allowed');
			$json['error'] = $this->language->get('error_invalid_request');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Validate cart has products and has stock.
		if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout')) || !$this->cart->hasMinimum()) {
			$json['redirect'] = $this->url->link('checkout/cart', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true);
		}

		if (!$json) {
			// Validate has payment address if required
			if ($this->config->get('config_checkout_payment_address') && !isset($this->session->data['payment_address'])) {
				$json['error'] = $this->language->get('error_payment_address');
			}

			// Validate shipping
			if ($this->cart->hasShipping()) {
				// Validate shipping address
				if (!isset($this->session->data['shipping_address']['address_id'])) {
					$json['error'] = $this->language->get('error_shipping_address');
				}

				// Validate shipping method
				if (!isset($this->session->data['shipping_method'])) {
					$json['error'] = $this->language->get('error_shipping_method');
				}
			}

			// Refresh available quotes before saving. The address or shipping method
			// may have changed since the options were rendered, leaving stale quotes
			// in the session and causing a visibly selected option to be rejected.
			$selected_payment_method = null;
			$payment_method_input = $this->request->post['payment_method'] ?? null;
			$requested_payment_code = is_scalar($payment_method_input) ? trim((string)$payment_method_input) : '';

			if (!$json && $requested_payment_code !== '') {
				$payment_address = [];

				if ($this->config->get('config_checkout_payment_address') && isset($this->session->data['payment_address'])) {
					$payment_address = $this->session->data['payment_address'];
				} elseif ($this->config->get('config_checkout_shipping_address') && isset($this->session->data['shipping_address']['address_id'])) {
					$payment_address = $this->session->data['shipping_address'];
				}

				$this->load->model('checkout/payment_method');
				$payment_methods = $this->model_checkout_payment_method->getMethods($payment_address);
				$this->session->data['payment_methods'] = $payment_methods;

				foreach ($payment_methods as $payment_group_code => $payment_group) {
					if (!is_array($payment_group)) {
						continue;
					}

					foreach (($payment_group['option'] ?? []) as $payment_option_key => $payment_option) {
						if (!is_array($payment_option)) {
							continue;
						}

						$quote_code = (string)($payment_option['code'] ?? '');
						$canonical_quote_code = (string)($payment_group['code'] ?? $payment_group_code) . '.' . $payment_option_key;

						if ($requested_payment_code === $quote_code || $requested_payment_code === $canonical_quote_code) {
							$selected_payment_method = $payment_option;
							break 2;
						}
					}
				}
			}

			if (!$json && !$selected_payment_method) {
				$available_payment_codes = [];
				foreach (($payment_methods ?? []) as $payment_group_code => $payment_group) {
					foreach (($payment_group['option'] ?? []) as $payment_option_key => $payment_option) {
						$available_payment_codes[] = [
							'code' => (string)($payment_option['code'] ?? ''),
							'canonical' => (string)($payment_group['code'] ?? $payment_group_code) . '.' . $payment_option_key
						];
					}
				}
				$this->log->write('[payment_method.save] rejected requested=' . json_encode($requested_payment_code) . ' available=' . json_encode($available_payment_codes));
				$json['error'] = $this->language->get('error_payment_method');
			}
		}

		if (!$json) {
			$this->session->data['payment_method'] = $selected_payment_method;

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Comment
	 *
	 * @return void
	 */
	public function comment(): void {
		$this->load->language('checkout/payment_method');
		$this->load->model('checkout/order');

		$json = [];

		if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST') {
			$this->response->addHeader('Allow: POST');
			$this->response->addHeader('HTTP/1.1 405 Method Not Allowed');
			$json['error'] = $this->language->get('error_invalid_request');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		if (isset($this->session->data['order_id'])) {
			$order_id = (int)$this->session->data['order_id'];
		} else {
			$order_id = 0;
		}

		$comment_value = $this->request->post['comment'] ?? '';
		if (!is_scalar($comment_value)) {
			$this->response->addHeader('HTTP/1.1 400 Bad Request');
			$json['error'] = $this->language->get('error_invalid_request');
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}
		$comment = (string)$comment_value;

		$this->session->data['comment'] = $comment;

		$order_info = $this->model_checkout_order->getOrder($order_id);

		if ($order_info) {
			$this->model_checkout_order->editComment($order_id, $comment);
		}

		$json['success'] = $this->language->get('text_comment');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Agree
	 *
	 * @return void
	 */
	public function agree(): void {
		$this->load->language('checkout/payment_method');

		$json = [];

		if (isset($this->request->post['agree'])) {
			$this->session->data['agree'] = $this->request->post['agree'];
		} else {
			unset($this->session->data['agree']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
