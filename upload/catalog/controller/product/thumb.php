<?php
namespace Opencart\Catalog\Controller\Product;
/**
 * Class Thumb
 *
 * Can be loaded using $this->load->controller('product/thumb', $product_data);
 *
 * @example
 *
 * $product_data = [
 *     'description' => '',
 *     'thumb'       => '',
 *     'price'       => 1.00,
 *     'special'     => 0.00,
 *     'tax'         => 0.00,
 *     'minimum'     => 1,
 *     'href'        => ''
 * ];
 *
 * @package Opencart\Catalog\Controller\Product
 */
class Thumb extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @param array<string, mixed> $data array of data
	 *
	 * @return string
	 */
	public function index(array $data): string {
		$this->load->language('product/thumb');

		// These presentation labels use the actual product row when the module provides it.
		$data['badge'] = $data['badge'] ?? '';
		$data['stock_status'] = '';

		if (isset($data['quantity'])) {
			$data['stock_status'] = (int)$data['quantity'] > 0 ? $this->language->get('text_in_stock') : $this->language->get('text_out_of_stock');
			$data['stock_available'] = (int)$data['quantity'] > 0;
		}

		if (!$data['badge'] && !empty($data['date_added'])) {
			$date_added = strtotime((string)$data['date_added']);

			if ($date_added && $date_added >= strtotime('-30 days')) {
				$data['badge'] = $this->language->get('text_badge_new');
			}
		}

		// Show the reward points actually configured for the current customer group.
		$data['coins'] = max(0, (int)($data['reward'] ?? 0));

		$language = 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'));
		$data['cart'] = $this->url->link('common/cart.info', $language);

		$data['cart_add'] = $this->url->link('checkout/cart.add', $language);
		$data['cart_info'] = $data['cart'] ?? $this->url->link('common/cart.info', $language);
		$data['checkout'] = $this->url->link('checkout/checkout', $language);
		$data['text_cart_added'] = $this->language->get('text_cart_added');
		$data['button_buy_now'] = $this->language->get('button_buy_now');
		$data['wishlist_add'] = $this->url->link('account/wishlist.add', $language);
		$wishlist_remove_query = $language . '&product_id=' . (int)($data['product_id'] ?? 0);

		if (isset($this->session->data['customer_token'])) {
			$wishlist_remove_query .= '&customer_token=' . $this->session->data['customer_token'];
		}

		$data['wishlist_remove'] = $this->url->link('account/wishlist.remove', $wishlist_remove_query);
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['compare_add'] = $this->url->link('product/compare.add', $language);
		$data['text_wishlist_added'] = $this->language->get('text_wishlist_added');
		$data['is_wishlist_added'] = !empty($data['is_wishlist']);

		if (!$data['is_wishlist_added'] && isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist'])) {
			$product_id = (int)($data['product_id'] ?? 0);
			$data['is_wishlist_added'] = $product_id > 0 && in_array($product_id, array_map('intval', $this->session->data['wishlist']), true);
		}

		$data['review_status'] = (int)$this->config->get('config_review_status');

		return $this->load->view('product/thumb', $data);
	}
}
