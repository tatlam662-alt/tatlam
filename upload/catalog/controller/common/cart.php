<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Cart
 *
 * Can be called from $this->load->controller('common/cart');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Cart extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('common/cart');

		$totals = [];
		$taxes = $this->cart->getTaxes();
		$total = 0;

		// Cart
		$this->load->model('checkout/cart');

		// Image
		$this->load->model('tool/image');

		// Upload
		$this->load->model('tool/upload');

		// Display prices
		if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
			($this->model_checkout_cart->getTotals)($totals, $taxes, $total);

			$price_status = true;
		} else {
			$price_status = false;
		}

		$data['cart_count'] = (int)$this->cart->countProducts();
		$data['text_remove_one'] = $this->language->get('text_remove_one');
		$data['text_items'] = sprintf($this->language->get('text_items'), $data['cart_count'], $this->currency->format($total, $this->session->data['currency']));
		$data['text_shopping_cart'] = $this->language->get('text_shopping_cart');
		$data['text_cart_product_count'] = sprintf(
			$this->language->get($data['cart_count'] === 1 ? 'text_cart_one_item' : 'text_cart_many_items'),
			$data['cart_count']
		);

		// Products
		$data['products'] = [];

		$products = $this->model_checkout_cart->getProducts();

		foreach ($products as $product) {
			if ($product['option']) {
				foreach ($product['option'] as $key => $option) {
					if ($option['type'] != 'file') {
						$value = $option['value'];
					} else {
						$upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($upload_info) {
							$value = $upload_info['name'];
						} else {
							$value = '';
						}
					}

					$product['option'][$key]['value'] = (oc_strlen($value) > 20 ? oc_substr($value, 0, 20) . '..' : $value);
				}
			}

			$subscription = '';

			if ($product['subscription'] && $price_status) {
				if ($product['subscription']['duration']) {
					$subscription .= sprintf($this->language->get('text_subscription_duration'), $product['subscription']['price_text'], $product['subscription']['cycle'], $product['subscription']['frequency'], $product['subscription']['duration']);
				} else {
					$subscription .= sprintf($this->language->get('text_subscription_cancel'), $product['subscription']['price_text'], $product['subscription']['cycle'], $product['subscription']['frequency']);
				}
			}

			$data['products'][] = [
				'thumb'        => $this->model_tool_image->resize($product['image'], $this->config->get('config_image_cart_width'), $this->config->get('config_image_cart_height')),
				'subscription' => $subscription,
				'price'        => $price_status ? $product['price_text'] : '',
				'total'        => $price_status ? $product['total_text'] : '',
				'remove'       => $this->url->link('common/cart.remove', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&key=' . (int)$product['cart_id']),
				'href'         => $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $product['product_id'])
			] + $product;
		}

		// Totals
		$data['totals'] = [];

		foreach ($totals as $total) {
			$data['totals'][] = ['text' => $this->currency->format($total['value'], $this->session->data['currency'])] + $total;
		}

		$data['list'] = $this->url->link('common/cart.info', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['remove'] = $this->url->link('common/cart.remove', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		$data['cart'] = $this->url->link('checkout/cart', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['checkout'] = $this->url->link('checkout/checkout', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		return $this->load->view('common/cart', $data);
	}

	/**
	 * Info
	 *
	 * @return void
	 */
	public function info(): void {
		$this->response->setOutput($this->index());
	}

	/**
	 * Remove Product
	 *
	 * @return void
	 */
	public function remove(): void {
		$this->load->language('checkout/cart');

		$json = [];

		$key = (int)($this->request->post['key'] ?? $this->request->get['key'] ?? 0);

		$cart_product = null;
		foreach ($this->cart->getProducts() as $product) {
			if ((int)$product['cart_id'] === $key) {
				$cart_product = $product;
				break;
			}
		}

		if (!$key || !$cart_product) {
			$json['error'] = $this->language->get('error_product');
		}

		if (!$json) {
			if ((int)$cart_product['quantity'] > 1) {
				$this->cart->update($key, (int)$cart_product['quantity'] - 1);
			} else {
				$this->cart->remove($key);
			}

			$json['success'] = $this->language->get('text_remove');
			unset($this->session->data['order_id']);
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['reward']);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
