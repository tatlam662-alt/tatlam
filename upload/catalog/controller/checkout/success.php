<?php
namespace Opencart\Catalog\Controller\Checkout;

/**
 * Order success page.
 *
 * The order id is taken from the checkout session (never from the URL). A
 * session-scoped last_order_id allows a refresh without exposing other orders.
 */
class Success extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('checkout/success');
		$data['text_guest_order_lookup'] = $this->language->get('text_guest_order_lookup');
		$this->document->setTitle('Đặt hàng thành công - PanDaShop');

		$language = $this->request->get['language'] ?? $this->config->get('config_language');
		$orderId = (int)($this->session->data['order_id'] ?? $this->session->data['last_order_id'] ?? 0);

		$data['order_successful'] = false;
		$data['order_summary'] = [];
		$data['products'] = [];
		$data['totals'] = [];
		$data['earned_coins'] = 0;
		$data['continue'] = $this->url->link('common/home', 'language=' . $language);
		$data['order_history'] = '';
		$data['account_orders'] = '';
		$data['guest_order_lookup'] = '';
		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', 'language=' . $language)
			],
			[
				'text' => $this->language->get('text_success'),
				'href' => $this->url->link('checkout/success', 'language=' . $language)
			]
		];

		if ($orderId > 0) {
			$this->load->model('checkout/order');
			$orderInfo = $this->model_checkout_order->getOrder($orderId);

			// A browser session must not reveal a previous customer's order after
			// logout or account switching.
			$ownerId = (int)($orderInfo['customer_id'] ?? 0);
			if ($orderInfo && (($ownerId > 0 && (!$this->customer->isLogged() || $ownerId !== (int)$this->customer->getId())) || ($ownerId === 0 && $this->customer->isLogged()))) {
				$orderInfo = [];
			}

			if ($orderInfo) {
				$this->load->model('catalog/product');
				$this->load->model('tool/image');

				$data['order_successful'] = true;
				$data['order_id'] = $orderId;
				$data['customer_name'] = trim((string)($orderInfo['firstname'] ?? '') . ' ' . (string)($orderInfo['lastname'] ?? ''));
				$data['telephone'] = (string)($orderInfo['telephone'] ?? '');
				$data['email'] = (string)($orderInfo['email'] ?? '');
				$data['payment_method'] = $this->methodTitle($orderInfo['payment_method'] ?? []);
				$data['shipping_method'] = $this->methodTitle($orderInfo['shipping_method'] ?? []);
				$data['shipping_address'] = implode(', ', array_filter([
					(string)($orderInfo['shipping_address_1'] ?? ''),
					(string)($orderInfo['shipping_address_2'] ?? ''),
					(string)($orderInfo['shipping_city'] ?? ''),
					(string)($orderInfo['shipping_zone'] ?? ''),
					(string)($orderInfo['shipping_country'] ?? '')
			], static fn(string $part): bool => trim($part) !== ''));
				$data['order_date'] = date($this->language->get('datetime_format'), strtotime((string)($orderInfo['date_added'] ?? 'now')));
				$data['order_status'] = (string)($orderInfo['order_status'] ?? $this->language->get('text_success'));
				$data['order_summary'] = [
					'id' => $orderId,
					'total' => $this->currency->format((float)$orderInfo['total'], $orderInfo['currency_code'], (float)$orderInfo['currency_value'])
				];

				$productAmount = 0.0;
				foreach ($orderInfo['products'] as $product) {
					$productId = (int)$product['product_id'];
					$productInfo = $this->model_catalog_product->getProduct($productId);
					$image = (string)($productInfo['image'] ?? '');
					$quantity = (int)$product['quantity'];
					$productAmount += (float)$product['total'];

					$data['products'][] = [
						'name' => (string)$product['name'],
						'model' => (string)$product['model'],
						'quantity' => $quantity,
						'price' => $this->currency->format((float)$product['price'], $orderInfo['currency_code'], (float)$orderInfo['currency_value']),
						'total' => $this->currency->format((float)$product['total'], $orderInfo['currency_code'], (float)$orderInfo['currency_value']),
						'thumb' => $image !== '' ? $this->model_tool_image->resize($image, 88, 88) : '',
						'href' => $this->url->link('product/product', 'language=' . $language . '&product_id=' . $productId)
					];
				}

				foreach ($orderInfo['totals'] as $total) {
					$data['totals'][] = [
						'title' => (string)$total['title'],
						'text' => $this->currency->format((float)$total['value'], $orderInfo['currency_code'], (float)$orderInfo['currency_value'])
					];
				}

				// This is a displayed estimate only. OpenCart's reward ledger is
				// intentionally not modified by the success page.
				$data['earned_coins'] = (int)floor($productAmount / 100000);

				if ($this->customer->isLogged()) {
					$customerToken = (string)($this->session->data['customer_token'] ?? '');
					$data['order_history'] = $this->url->link('account/order.info', 'language=' . $language . '&customer_token=' . $customerToken . '&order_id=' . $orderId);
					$data['account_orders'] = $this->url->link('account/order', 'language=' . $language . '&customer_token=' . $customerToken);
				} else {
					$data['guest_order_lookup'] = $this->url->link('information/order_lookup', 'language=' . $language);
				}

				// Retain only this session's most recent order for a safe refresh.
				$this->session->data['last_order_id'] = $orderId;
				$this->cart->clear();
				foreach ([
					'order_id', 'payment_method', 'payment_methods', 'shipping_method', 'shipping_methods',
					'comment', 'agree', 'coupon', 'reward', 'voucher', 'vouchers', 'checkout_recipient',
					'vn_address_selection', 'vn_shipping_address_selection'
				] as $key) {
					unset($this->session->data[$key]);
				}
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');

		$this->response->setOutput($this->load->view('common/success', $data));
	}

	private function methodTitle(mixed $method): string {
		if (is_array($method)) {
			return (string)($method['name'] ?? $method['title'] ?? '');
		}

		return is_scalar($method) ? (string)$method : '';
	}
}
