<?php
namespace Opencart\Catalog\Controller\Account;
/**
 * Class Order
 *
 * @package Opencart\Catalog\Controller\Account
 */
class Order extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('account/order');

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			$this->response->redirect($this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
		}

		$this->document->setTitle($this->language->get('heading_title'));

		$filter = isset($this->request->get['status']) ? (string)$this->request->get['status'] : 'all';
		$allowed_filters = ['all', 'processing', 'shipping', 'complete', 'cancelled'];

		if (!in_array($filter, $allowed_filters, true)) {
			$filter = 'all';
		}

		$url = $filter === 'all' ? '' : '&status=' . $filter;

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . $url)
		];

		$limit = 10;

		$data['orders'] = [];

		// Order
		$this->load->model('account/order');

		// Order Status
		$this->load->model('localisation/order_status');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$status_ids = [];
		$status_labels = [
			'all'        => $this->language->get('text_filter_all'),
			'processing' => $this->language->get('text_filter_processing'),
			'shipping'   => $this->language->get('text_filter_shipping'),
			'complete'   => $this->language->get('text_filter_complete'),
			'cancelled'  => $this->language->get('text_filter_cancelled')
		];

		if ($filter !== 'all') {
			foreach ($this->model_localisation_order_status->getOrderStatuses() as $status_info) {
				$status_name = function_exists('mb_strtolower') ? mb_strtolower($status_info['name'], 'UTF-8') : strtolower($status_info['name']);
				$status_ascii = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $status_name) : $status_name;
				$status_ascii = $status_ascii !== false ? $status_ascii : $status_name;

				if (preg_match('/cancel|canceled|cancelled|void|refund|denied|failed|huy|hoan tien/', $status_ascii)) {
					$status_group = 'cancelled';
				} elseif (preg_match('/complete|completed|delivered|hoan tat|da giao/', $status_ascii)) {
					$status_group = 'complete';
				} elseif (preg_match('/ship|shipped|delivery|delivering|transit|dang giao/', $status_ascii)) {
					$status_group = 'shipping';
				} else {
					$status_group = 'processing';
				}

				if ($status_group === $filter) {
					$status_ids[] = (int)$status_info['order_status_id'];
				}
			}
		}

		$data['order_filters'] = [];

		foreach ($status_labels as $key => $label) {
			$data['order_filters'][] = [
				'key' => $key,
				'label' => $label,
				'href' => $this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . ($key === 'all' ? '' : '&status=' . $key)),
				'active' => $filter === $key
			];
		}

		$filter_has_statuses = $filter === 'all' || count($status_ids) > 0;
		$results = $filter_has_statuses ? $this->model_account_order->getOrders(($page - 1) * $limit, $limit, $status_ids) : [];
		$cancellation_status_id = $this->getCancellationOrderStatusId();

		$data['text_delete_cancelled_order_confirm'] = $this->language->get('text_delete_cancelled_order_confirm');
		$data['text_delete_cancelled_order'] = $this->language->get('button_delete_cancelled_order');
		$data['error_delete_cancelled_order'] = $this->language->get('error_delete_cancelled_order');
		$data['text_cancel_order'] = $this->language->get('button_cancel_order');
		$data['text_cancel_order_confirm'] = $this->language->get('text_cancel_order_confirm');
		$data['error_cancel_order'] = $this->language->get('error_cancel_order');

		foreach ($results as $result) {
			$order_status_info = $this->model_localisation_order_status->getOrderStatus($result['order_status_id']);

			if ($order_status_info) {
				$order_status = $order_status_info['name'];
			} else {
				$order_status = '';
			}

			$status_ascii = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', strtolower($order_status)) : strtolower($order_status);
			$status_ascii = $status_ascii !== false ? $status_ascii : strtolower($order_status);
			$status_class = preg_match('/cancel|canceled|cancelled|void|refund|huy|hoan tien/', $status_ascii) ? 'cancelled' : (preg_match('/complete|completed|delivered|hoan tat|da giao/', $status_ascii) ? 'complete' : (preg_match('/ship|delivery|transit|dang giao/', $status_ascii) ? 'shipping' : 'processing'));

			$order_products = $this->model_account_order->getProducts((int)$result['order_id']);
			$first_product = [];

			if ($order_products) {
				$catalog_product = $this->model_catalog_product->getProduct((int)$order_products[0]['product_id']);
				$first_product = [
					'name' => $order_products[0]['name'],
					'quantity' => (int)$order_products[0]['quantity'],
					'thumb' => ($catalog_product && $catalog_product['image'] && is_file(DIR_IMAGE . $catalog_product['image'])) ? $this->model_tool_image->resize($catalog_product['image'], 72, 72) : ''
				];
			}

			$delete_url = $this->url->link('account/order.remove', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&order_id=' . (int)$result['order_id']);
			$query_position = strpos($delete_url, '?');
			if ($query_position !== false) {
				$url_path = substr($delete_url, 0, $query_position);
				if ($url_path !== '' && substr($url_path, -1) !== '/') {
					$delete_url = $url_path . '/' . substr($delete_url, $query_position);
				}
			}

			$cancel_url = $this->normalizeActionUrl($this->url->link('account/order.cancel', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&order_id=' . (int)$result['order_id']));

			$data['orders'][] = [
				'status'        => $order_status,
				'status_class'  => $status_class,
				'first_product' => $first_product,
				'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'product_total' => $this->model_account_order->getTotalProductsByOrderId($result['order_id']),
				'total'         => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
				'can_cancel'    => $this->isOrderAwaitingShopConfirmation($result),
				'cancel'        => $cancel_url,
				'can_delete'    => $cancellation_status_id > 0 && (int)$result['order_status_id'] === $cancellation_status_id,
				'delete'        => $delete_url,
				'view'          => $this->url->link('account/order.info', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&order_id=' . $result['order_id']),
			] + $result;
		}

		$order_total = $filter_has_statuses ? $this->model_account_order->getTotalOrders($status_ids) : 0;

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $order_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . $url . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($order_total - $limit)) ? $order_total : ((($page - 1) * $limit) + $limit), $order_total, ceil($order_total / $limit));

		$data['continue'] = $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['shop'] = $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/order_list', $data));
	}

	/**
	 * Info
	 *
	 * @return \Opencart\System\Engine\Action|null
	 */
	public function info(): ?\Opencart\System\Engine\Action {
		$this->load->language('account/order');

		if (isset($this->request->get['order_id'])) {
			$order_id = (int)$this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			$this->response->redirect($this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
		}

		// Order
		$this->load->model('account/order');

		$order_info = $this->model_account_order->getOrder($order_id);

		if ($order_info) {
			$heading_title = sprintf($this->language->get('text_order'), $order_info['order_id']);
			$data['heading_title'] = $heading_title;

			$this->document->setTitle($heading_title);

			$url = '';

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$data['breadcrumbs'] = [];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'])
			];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . $url)
			];

			$data['breadcrumbs'][] = [
				'text' => $heading_title,
				'href' => $this->url->link('account/order.info', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&order_id=' . $order_id . $url)
			];

			if ($order_info['invoice_no']) {
				$data['invoice_no'] = $order_info['invoice_prefix'] . $order_info['invoice_no'];
			} else {
				$data['invoice_no'] = '';
			}

			$data['order_id'] = $order_id;
			$data['order_status_id'] = (int)$order_info['order_status_id'];

			// Order Status
			$this->load->model('localisation/order_status');

			$order_status_info = $this->model_localisation_order_status->getOrderStatus($order_info['order_status_id']);

			if ($order_status_info) {
				$data['order_status'] = $order_status_info['name'];
			} else {
				$data['order_status'] = '';
			}

			$data['can_cancel_order'] = $this->isOrderAwaitingShopConfirmation($order_info);
			$data['cancel_order'] = $this->normalizeActionUrl($this->url->link('account/order.cancel', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&order_id=' . $order_id));
			$data['text_cancel_order'] = $this->language->get('button_cancel_order');
			$data['text_order_status'] = $this->language->get('text_order_status');
			$data['text_cancel_order_confirm'] = $this->language->get('text_cancel_order_confirm');
			$data['error_cancel_order'] = $this->language->get('error_cancel_order');

			$status_text = function_exists('mb_strtolower') ? mb_strtolower($data['order_status'], 'UTF-8') : strtolower($data['order_status']);
			$status_ascii = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $status_text) : $status_text;
			$status_ascii = $status_ascii !== false ? $status_ascii : $status_text;
			$data['order_cancelled'] = (bool)preg_match('/cancel|canceled|cancelled|void|refund|huy|hoan tien/', $status_ascii);
			$data['order_status_class'] = $data['order_cancelled'] ? 'cancelled' : (preg_match('/complete|completed|delivered|hoan tat|da giao/', $status_ascii) ? 'complete' : (preg_match('/ship|delivery|transit|dang giao/', $status_ascii) ? 'shipping' : 'processing'));
			$data['order_step'] = 1;
			$status_id = (int)$order_info['order_status_id'];
			$processing_status_ids = array_map('intval', (array)$this->config->get('config_processing_status'));
			$complete_status_ids = array_map('intval', (array)$this->config->get('config_complete_status'));
			$data['order_steps'] = [
				$this->language->get('text_order_step_1'),
				$this->language->get('text_order_step_2'),
				$this->language->get('text_order_step_3'),
				$this->language->get('text_order_step_4')
			];

			if (str_contains($status_ascii, 'hoan tat') || str_contains($status_ascii, 'complete') || str_contains($status_ascii, 'delivered')) {
				$data['order_step'] = 4;
			} elseif (str_contains($status_ascii, 'giao') || str_contains($status_ascii, 'shipping') || str_contains($status_ascii, 'transit')) {
				$data['order_step'] = 3;
			} elseif (str_contains($status_ascii, 'xac nhan') || str_contains($status_ascii, 'confirm') || str_contains($status_ascii, 'processing')) {
				$data['order_step'] = 2;
			}

			if ($data['order_step'] === 1 && in_array($status_id, $processing_status_ids, true)) {
				$data['order_step'] = 2;
			}

			if (in_array($status_id, $complete_status_ids, true)) {
				$data['order_step'] = 4;
			}

			$data['date_added'] = date($this->language->get('date_format_short'), strtotime($order_info['date_added']));
			$data['text_back_to_orders'] = $this->language->get('text_history');

			// A compact, escaped shipping address for the order dashboard.
			$shipping_address_parts = array_values(array_unique(array_filter(array_map(static function ($value): string {
				return trim((string)$value);
			}, [
				$order_info['shipping_address_1'] ?? '',
				$order_info['shipping_address_2'] ?? '',
				$order_info['shipping_city'] ?? '',
				$order_info['shipping_zone'] ?? '',
				$order_info['shipping_country'] ?? ''
			]), static function (string $value): bool {
				return $value !== '';
			})));

			$shipping_name = trim(($order_info['shipping_lastname'] ?? '') . ' ' . ($order_info['shipping_firstname'] ?? ''));
			$shipping_contact = [];

			if ($shipping_name !== '') {
				$shipping_contact[] = '<strong>' . htmlspecialchars($shipping_name, ENT_QUOTES, 'UTF-8') . '</strong>';
			}

			if (!empty($order_info['telephone'])) {
				$shipping_contact[] = '<span class="pd-order-phone"><i class="fa-solid fa-phone" aria-hidden="true"></i>' . htmlspecialchars((string)$order_info['telephone'], ENT_QUOTES, 'UTF-8') . '</span>';
			}

			if ($shipping_address_parts) {
				$shipping_contact[] = htmlspecialchars(implode(', ', $shipping_address_parts), ENT_QUOTES, 'UTF-8');
			}

			$data['shipping_address_clean'] = implode('<br>', $shipping_contact);

			// Payment Address
			if ($order_info['payment_address_format']) {
				$format = $order_info['payment_address_format'];
			} else {
				$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
			}

			$find = [
				'{firstname}',
				'{lastname}',
				'{company}',
				'{address_1}',
				'{address_2}',
				'{city}',
				'{postcode}',
				'{zone}',
				'{zone_code}',
				'{country}'
			];

			$replace = [
				'firstname' => $order_info['payment_firstname'],
				'lastname'  => $order_info['payment_lastname'],
				'company'   => $order_info['payment_company'],
				'address_1' => $order_info['payment_address_1'],
				'address_2' => $order_info['payment_address_2'],
				'city'      => $order_info['payment_city'],
				'postcode'  => $order_info['payment_postcode'],
				'zone'      => $order_info['payment_zone'],
				'zone_code' => $order_info['payment_zone_code'],
				'country'   => $order_info['payment_country']
			];

			$pattern_1 = [
				"\r\n",
				"\r",
				"\n"
			];

			$pattern_2 = [
				"/\\s\\s+/",
				"/\r\r+/",
				"/\n\n+/"
			];

			$data['payment_address'] = str_replace($pattern_1, '<br/>', preg_replace($pattern_2, '<br/>', trim(str_replace($find, $replace, $format))));

			if (isset($order_info['payment_method']['name'])) {
				$data['payment_method'] = $order_info['payment_method']['name'];
			} else {
				$data['payment_method'] = '';
			}

			// Shipping Address
			if ($order_info['shipping_method']) {
				if ($order_info['shipping_address_format']) {
					$format = $order_info['shipping_address_format'];
				} else {
					$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
				}

				$find = [
					'{firstname}',
					'{lastname}',
					'{company}',
					'{address_1}',
					'{address_2}',
					'{city}',
					'{postcode}',
					'{zone}',
					'{zone_code}',
					'{country}'
				];

				$replace = [
					'firstname' => $order_info['shipping_firstname'],
					'lastname'  => $order_info['shipping_lastname'],
					'company'   => $order_info['shipping_company'],
					'address_1' => $order_info['shipping_address_1'],
					'address_2' => $order_info['shipping_address_2'],
					'city'      => $order_info['shipping_city'],
					'postcode'  => $order_info['shipping_postcode'],
					'zone'      => $order_info['shipping_zone'],
					'zone_code' => $order_info['shipping_zone_code'],
					'country'   => $order_info['shipping_country']
				];

				$data['shipping_address'] = str_replace($pattern_1, '<br/>', preg_replace($pattern_2, '<br/>', trim(str_replace($find, $replace, $format))));

				$data['shipping_method'] = $order_info['shipping_method']['name'];
			} else {
				$data['shipping_address'] = '';
				$data['shipping_method'] = '';
			}

			// Subscription
			$this->load->model('account/subscription');

			// Product
			$this->load->model('catalog/product');
			$this->load->model('tool/image');

			// Upload
			$this->load->model('tool/upload');

			// Products
			$data['products'] = [];

			$products = $this->model_account_order->getProducts($order_id);

			foreach ($products as $product) {
				$catalog_product = $this->model_catalog_product->getProduct((int)$product['product_id']);
				$product_thumb = ($catalog_product && $catalog_product['image'] && is_file(DIR_IMAGE . $catalog_product['image'])) ? $this->model_tool_image->resize($catalog_product['image'], 72, 72) : '';
				$option_data = [];

				$options = $this->model_account_order->getOptions($order_id, $product['order_product_id']);

				foreach ($options as $option) {
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

					$option_data[] = ['value' => (oc_strlen($value) > 20 ? oc_substr($value, 0, 20) . '..' : $value)] + $option;
				}

				$subscription_plan = '';

				$order_subscription_info = $this->model_account_order->getSubscription($order_id, $product['order_product_id']);

				if ($order_subscription_info) {
					if ($order_subscription_info['trial_status']) {
						$trial_price = $this->currency->format($order_subscription_info['trial_price'] + ($this->config->get('config_tax') ? $order_subscription_info['trial_tax'] : 0), $order_info['currency_code'], $order_info['currency_value']);
						$trial_cycle = $order_subscription_info['trial_cycle'];
						$trial_frequency = $this->language->get('text_' . $order_subscription_info['trial_frequency']);
						$trial_duration = $order_subscription_info['trial_duration'];

						$subscription_plan .= sprintf($this->language->get('text_subscription_trial'), $trial_price, $trial_cycle, $trial_frequency, $trial_duration);
					}

					$price = $this->currency->format($order_subscription_info['price'] + ($this->config->get('config_tax') ? $order_subscription_info['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']);
					$cycle = $order_subscription_info['cycle'];
					$frequency = $this->language->get('text_' . $order_subscription_info['frequency']);
					$duration = $order_subscription_info['duration'];

					if ($order_subscription_info['duration']) {
						$subscription_plan .= sprintf($this->language->get('text_subscription_duration'), $price, $cycle, $frequency, $duration);
					} else {
						$subscription_plan .= sprintf($this->language->get('text_subscription_cancel'), $price, $cycle, $frequency);
					}

					$subscription_plan_id = $order_subscription_info['subscription_plan_id'];
				} else {
					$subscription_plan_id = 0;
				}

				$subscription_info = $this->model_account_subscription->getProductByOrderProductId($order_id, $product['order_product_id']);

				if ($subscription_info) {
					$subscription = $this->url->link('account/subscription.info', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&subscription_id=' . $subscription_info['subscription_id']);
				} else {
					$subscription = '';
				}

				$data['products'][] = [
					'thumb'                => $product_thumb,
					'option'               => $option_data,
					'subscription_plan_id' => $subscription_plan_id,
					'subscription_plan'    => $subscription_plan,
					'subscription'         => $subscription,
					'price'                => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
					'total'                => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']),
					'view'                 => $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $product['product_id']),
					'return'               => $this->url->link('account/returns.add', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&order_id=' . $order_info['order_id'] . '&product_id=' . $product['product_id'])
				] + $product;
			}

			// Totals
			$data['totals'] = [];

			$totals = $this->model_account_order->getTotals($order_id);

			foreach ($totals as $total) {
				$data['totals'][] = ['text' => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value'])] + $total;
			}

			$data['comment'] = $order_info['comment'];

			// History
			$data['history'] = $this->getHistory();

			$data['continue'] = $this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);

			$data['language'] = ($this->request->get['language'] ?? $this->config->get('config_language'));

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('account/order_info', $data));

			return null;
		} else {
			return new \Opencart\System\Engine\Action('error/not_found');
		}
	}

	/** Cancel an order while it is still awaiting the shop's confirmation. */
	public function cancel(): void {
		$this->load->language('account/order');

		$json = [];
		$request_method = strtoupper(trim((string)($_SERVER['REQUEST_METHOD'] ?? '')));

		// Some local PHP/Apache setups expose server keys with different casing
		// or omit them from OpenCart's sanitized server array.
		if ($request_method === '') {
			foreach ($this->request->server as $key => $value) {
				if (strcasecmp((string)$key, 'REQUEST_METHOD') === 0) {
					$request_method = strtoupper(trim((string)$value));
					break;
				}
			}
		}

		$post_marker = $this->request->post['cancel_order'] ?? ($_POST['cancel_order'] ?? null);
		$has_post_marker = is_scalar($post_marker) && (string)$post_marker === '1';
		$is_cancel_request = $request_method === 'POST' && $has_post_marker;

		if (!$this->load->controller('account/login.validate')) {
			$json['error'] = $this->language->get('error_cancel_order_session');
		} elseif (!$is_cancel_request) {
			$json['error'] = $this->language->get('error_cancel_order_method');
		} else {
			$order_id = (int)($this->request->get['order_id'] ?? 0);
			$this->load->model('account/order');
			$order_info = $this->model_account_order->getOrder($order_id);

			if (!$order_info) {
				$json['error'] = $this->language->get('error_cancel_order_not_found');
			} elseif (!$this->isOrderAwaitingShopConfirmation($order_info)) {
				$json['error'] = sprintf($this->language->get('error_cancel_order_status'), (int)$order_info['order_status_id']);
			} else {
				$cancel_status_id = $this->getCancellationOrderStatusId();

				if (!$cancel_status_id || $cancel_status_id === (int)$order_info['order_status_id']) {
					$json['error'] = $this->language->get('error_cancel_order_target');
				} else {
					$this->load->model('checkout/order');
					$this->model_checkout_order->addHistory($order_id, $cancel_status_id, $this->language->get('text_customer_cancelled_order'));
					$json['success'] = $this->language->get('text_cancel_order_success');
					$json['redirect'] = html_entity_decode($this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']), ENT_QUOTES, 'UTF-8');
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/** Keep the former endpoint safe for stale pages: it now cancels, never deletes. */
	public function delete(): void {
		$this->cancel();
	}

	/** Permanently remove a customer's already-cancelled order from the store. */
	public function remove(): void {
		$this->load->language('account/order');

		$json = [];
		$request_method = strtoupper(trim((string)($_SERVER['REQUEST_METHOD'] ?? '')));
		$delete_marker = $this->request->post['delete_cancelled_order'] ?? ($_POST['delete_cancelled_order'] ?? null);
		$has_delete_marker = is_scalar($delete_marker) && (string)$delete_marker === '1';
		$is_mutation_request = $request_method === 'POST' || ($request_method === '' && $has_delete_marker);

		if (!$this->load->controller('account/login.validate')) {
			$json['error'] = $this->language->get('error_cancel_order_session');
		} elseif (!$is_mutation_request) {
			$json['error'] = $this->language->get('error_delete_cancelled_order');
		} else {
			$order_id = (int)($this->request->get['order_id'] ?? 0);
			$cancellation_status_id = $this->getCancellationOrderStatusId();
			$customer_id = (int)$this->customer->getId();
			$order_query = $this->db->query("SELECT `order_id`, `order_status_id` FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . $order_id . "' AND `customer_id` = '" . $customer_id . "' AND `customer_id` != '0'");

			if (!$order_query->num_rows || !$cancellation_status_id || (int)$order_query->row['order_status_id'] !== $cancellation_status_id) {
				$json['error'] = $this->language->get('error_delete_cancelled_order');
			} else {
				$this->load->model('checkout/order');
				if (!$this->model_checkout_order->deleteOrder($order_id, $customer_id, $cancellation_status_id)) {
					$json['error'] = $this->language->get('error_delete_cancelled_order');
				} else {
					$json['success'] = $this->language->get('text_delete_cancelled_order_success');
					$json['redirect'] = html_entity_decode($this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']), ENT_QUOTES, 'UTF-8');
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Require an explicit pending/unconfirmed status and reject completed orders
	 * before allowing the customer to cancel.
	 */
	private function isOrderAwaitingShopConfirmation(array $order_info): bool {
		$order_status_id = (int)($order_info['order_status_id'] ?? 0);
		$this->load->model('localisation/order_status');
		$status_info = $this->model_localisation_order_status->getOrderStatus($order_status_id);

		// This installation's Vietnamese workflow uses ID 17 specifically for
		// "Chờ xử lý". Keep it cancellable even if transliteration/config differs.
		$status_name = $status_info['name'] ?? '';
		$status_name = function_exists('mb_strtolower') ? mb_strtolower($status_name, 'UTF-8') : strtolower($status_name);
		if ($order_status_id === 17 && $status_info && preg_match('/ch\\x{1edd}\s*x\\x{1eed}\s*l\\x{fd}/u', $status_name)) {
			return true;
		}

		$cancel_status_id = $this->getCancellationOrderStatusId();

		if (!$order_status_id || !$cancel_status_id || $cancel_status_id === $order_status_id) {
			return false;
		}

		if (!$status_info || empty($status_info['name'])) {
			return false;
		}

		$status = function_exists('mb_strtolower') ? mb_strtolower(trim($status_info['name']), 'UTF-8') : strtolower(trim($status_info['name']));
		$status_ascii = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $status) : $status;
		$status_ascii = $status_ascii !== false ? $status_ascii : $status;
		$pending_status_ids = array_map('intval', (array)$this->config->get('config_pending_status'));

		// A status explicitly named as pending takes precedence over a stale/misconfigured
		// complete-status list. This covers localized labels such as "Chờ xử lý".
		$is_pending = in_array($order_status_id, $pending_status_ids, true)
			|| (bool)preg_match('/pending|awaiting|new order|received|cho\s*xu\s*ly|cho\s*xac\s*nhan|moi\s*dat|cho\s*duyet/u', $status_ascii)
			|| (bool)preg_match('/ch\\x{1edd}\s*x\\x{1eed}\s*l\\x{fd}|ch\\x{1edd}\s*x\\x{e1c}\s*nh\\x{1ead}n|m\\x{1edb}i\s*\\x{111}\\x{1eb7}t|ch\\x{1edd}\s*duy\\x{1ec7}t/u', $status);

		if (!$is_pending) {
			return false;
		}

		return true;
	}

	/** Ensure mutation endpoints do not lose POST bodies to Apache redirects. */
	private function normalizeActionUrl(string $url): string {
		$url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
		$query_position = strpos($url, '?');

		if ($query_position !== false) {
			$path = substr($url, 0, $query_position);
			if ($path !== '' && substr($path, -1) !== '/') {
				$url = $path . '/' . substr($url, $query_position);
			}
		}

		return $url;
	}

	/** Resolve the cancellation status in the active language. */
	private function getCancellationOrderStatusId(): int {
		$this->load->model('localisation/order_status');

		$configured_status_id = (int)$this->config->get('config_void_status_id');
		$void_status_id = 0;
		$order_statuses = $this->model_localisation_order_status->getOrderStatuses();

		// Only treat an actual cancelled status as deletable. Avoid matching
		// labels such as "cancellation requested" (not yet cancelled).
		foreach ($order_statuses as $status_info) {
			$status_name = trim((string)$status_info['name']);

			if (preg_match('/^(?:\\x{0111}\\x{00e3}\\s+)?h\\x{1ee7}y(?:\\s+\\x{0111}\\x{01a1}n(?:\\s+h\\x{00e0}ng)?)?$/iu', $status_name)) {
				return (int)$status_info['order_status_id'];
			}
		}

		foreach ($order_statuses as $status_info) {
			$status = function_exists('mb_strtolower') ? mb_strtolower($status_info['name'], 'UTF-8') : strtolower($status_info['name']);
			$status = function_exists('iconv') ? (iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $status) ?: $status) : $status;

			if (preg_match('/^(cancel|canceled|cancelled)( order)?$/u', trim($status))) {
				return (int)$status_info['order_status_id'];
			}

			if (!$void_status_id && preg_match('/void/u', $status)) {
				$void_status_id = (int)$status_info['order_status_id'];
			}
		}

		// Prefer a localized cancellation status (for example Vietnamese "Đã hủy").
		// Otherwise use the configured global void status even if it lacks a
		// translation in the active storefront language.
		return $configured_status_id > 0 ? $configured_status_id : $void_status_id;
	}

	/**
	 * History
	 *
	 * @return void
	 */
	public function history(): void {
		$this->load->language('account/order');

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			$this->response->redirect($this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
		}

		$this->response->setOutput($this->getHistory());
	}

	/**
	 * Get History
	 *
	 * @return string
	 */
	protected function getHistory(): string {
		if (isset($this->request->get['order_id'])) {
			$order_id = (int)$this->request->get['order_id'];
		} else {
			$order_id = 0;
		}

		if (isset($this->request->get['page']) && $this->request->get['route'] == 'account/order.history') {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$limit = 10;

		if (!$this->load->controller('account/login.validate')) {
			return '';
		}

		// Order
		$this->load->model('account/order');

		$order_info = $this->model_account_order->getOrder($order_id);

		if (!$order_info) {
			return '';
		}

		$data['histories'] = [];

		$results = $this->model_account_order->getHistories($order_id);

		foreach ($results as $result) {
			$data['histories'][] = [
				'comment'    => $result['notify'] ? $result['comment'] : '',
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			] + $result;
		}

		$history_total = $this->model_account_order->getTotalHistories($order_id);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $history_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('account/order.history', 'customer_token=' . $this->session->data['customer_token'] . '&order_id=' . $order_id . '&page={page}')
		]);

		$history_start = $history_total ? (($page - 1) * $limit) + 1 : 0;
		$history_end = min($page * $limit, $history_total);
		$data['results'] = sprintf($this->language->get('text_history_pagination'), $history_start, $history_end, $history_total);

		return $this->load->view('account/order_history', $data);
	}
}
