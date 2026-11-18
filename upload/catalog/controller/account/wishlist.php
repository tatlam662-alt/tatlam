<?php
namespace Opencart\Catalog\Controller\Account;
/**
 * Class Wish List
 *
 * @package Opencart\Catalog\Controller\Account
 */
class WishList extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('account/wishlist');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		if ($this->customer->isLogged()) {
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_account'),
				'href' => $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''))
			];
		}

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('account/wishlist', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''))
		];

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['list'] = $this->getList();
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');
			$data['wishlist_total'] = $this->model_account_wishlist->getTotalWishlist($this->customer->getId());
		} else {
			$data['wishlist_total'] = isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0;
		}

		$data['customer_token'] = $this->session->data['customer_token'] ?? '';

		$data['continue'] = $this->customer->isLogged()
			? $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'])
			: $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		$data['language'] = ($this->request->get['language'] ?? $this->config->get('config_language'));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/wishlist', $data));
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('account/wishlist');

		$this->response->setOutput($this->getList());
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	protected function getList(): string {
		$data['cart'] = $this->url->link('common/cart.info', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['cart_add'] = $this->url->link('checkout/cart.add', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['continue'] = $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['text_wishlist_empty_hint'] = $this->language->get('text_wishlist_empty_hint');
		$data['button_explore_products'] = $this->language->get('button_explore_products');

		$data['products'] = [];

		// Wishlist
		$this->load->model('account/wishlist');

		// Product
		$this->load->model('catalog/product');

		// Image
		$this->load->model('tool/image');

		// Stock Status
		$this->load->model('localisation/stock_status');

		if ($this->customer->isLogged()) {
			$results = $this->model_account_wishlist->getWishlist($this->customer->getId());
		} else {
			$results = [];
			$wishlist = isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist']) ? $this->session->data['wishlist'] : [];

			foreach ($wishlist as $product_id) {
				$results[] = ['product_id' => (int)$product_id];
			}
		}

		foreach ($results as $result) {
			$product_info = $this->model_catalog_product->getProduct((int)$result['product_id']);

			if ($product_info) {
				if ($product_info['image'] && is_file(DIR_IMAGE . html_entity_decode($product_info['image'], ENT_QUOTES, 'UTF-8'))) {
					$image = $this->model_tool_image->resize($product_info['image'], $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'));
				} else {
					$image = '';
				}

				if ($product_info['quantity'] <= 0) {
					$stock_status_id = $product_info['stock_status_id'];
				} elseif (!$this->config->get('config_stock_display')) {
					$stock_status_id = (int)$this->config->get('stock_status_id');
				} else {
					$stock_status_id = 0;
				}

				$stock_status_info = $this->model_localisation_stock_status->getStockStatus($stock_status_id);

				if ($stock_status_info) {
					$stock = $stock_status_info['name'];
				} else {
					$stock = $product_info['quantity'];
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$product_info['special']) {
					$special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				$product_data = $product_info;
				$product_data['thumb'] = $image;
				$product_data['price'] = $price;
				$product_data['special'] = $special;
				$product_data['minimum'] = $product_info['minimum'] > 0 ? $product_info['minimum'] : 1;
				$product_data['href'] = $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $product_info['product_id']);
				$product_data['tax'] = false;
				$product_data['is_wishlist'] = true;
				$product_data['wishlist_remove'] = $this->url->link('account/wishlist.remove', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $product_info['product_id'] . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
				$product_data['button_wishlist_remove'] = $this->language->get('button_remove');

				$data['products'][] = $this->load->controller('product/thumb', $product_data);
			} else {
				$this->session->data['wishlist'] = array_values(array_diff(
					isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist']) ? array_map('intval', $this->session->data['wishlist']) : [],
					[(int)$result['product_id']]
				));

				if ($this->customer->isLogged()) {
					$this->model_account_wishlist->deleteWishlists($this->customer->getId(), $result['product_id']);
				}
			}
		}

		return $this->load->view('account/wishlist_list', $data);
	}

	/**
	 * Add
	 *
	 * @return void
	 */
	public function add(): void {
		$this->load->language('account/wishlist');

		$json = [];

		if (isset($this->request->post['product_id'])) {
			$product_id = (int)$this->request->post['product_id'];
		} else {
			$product_id = 0;
		}

		if (!$product_id) {
			$json['error'] = $this->language->get('error_product');
		}

		// Product
		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if (!$product_info) {
			$json['error'] = $this->language->get('error_product');
		}

		if (!$json) {
			if (!isset($this->session->data['wishlist']) || !is_array($this->session->data['wishlist'])) {
				$this->session->data['wishlist'] = [];
			}

			$this->session->data['wishlist'][] = $product_id;
			$this->session->data['wishlist'] = array_values(array_unique(array_map('intval', $this->session->data['wishlist'])));

			if ($this->customer->isLogged()) {
				$this->load->model('account/wishlist');
				$this->model_account_wishlist->addWishlist($this->customer->getId(), $product_id);

				$json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $product_id), $product_info['name'], $this->url->link('account/wishlist', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : '')));
				$json['total'] = sprintf($this->language->get('text_wishlist'), $this->model_account_wishlist->getTotalWishlist($this->customer->getId()));
			} else {
				$json['success'] = $this->language->get('text_guest_success');
				$json['total'] = sprintf($this->language->get('text_wishlist'), count($this->session->data['wishlist']));
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Remove
	 *
	 * @return void
	 */
	public function remove(): void {
		$this->load->language('account/wishlist');

		$json = [];

		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		if (!$product_id) {
			$json['error'] = $this->language->get('error_product');
		}

		if (!$json) {
			if (!isset($this->session->data['wishlist']) || !is_array($this->session->data['wishlist'])) {
				$this->session->data['wishlist'] = [];
			}

			$this->session->data['wishlist'] = array_values(array_diff(array_map('intval', $this->session->data['wishlist']), [$product_id]));

			if ($this->customer->isLogged()) {
				$this->load->model('account/wishlist');
				$this->model_account_wishlist->deleteWishlists($this->customer->getId(), $product_id);
				$wishlist_count = $this->model_account_wishlist->getTotalWishlist($this->customer->getId());
			} else {
				$wishlist_count = count($this->session->data['wishlist']);
			}

			$json['success'] = $this->language->get('text_remove');
			$json['total'] = sprintf($this->language->get('text_wishlist'), $wishlist_count);
			$json['wishlist_count'] = $wishlist_count;
		}

		$json['customer_token'] = $this->session->data['customer_token'] ?? null;

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

}
