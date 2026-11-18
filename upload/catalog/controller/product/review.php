<?php
namespace Opencart\Catalog\Controller\Product;
/**
 * Class Review
 *
 * Can be loaded using $this->load->controller('product/review');
 *
 * @package Opencart\Catalog\Controller\Product
 */
class Review extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('product/review');

		if (isset($this->request->get['product_id'])) {
			$data['product_id'] = (int)$this->request->get['product_id'];
		} else {
			$data['product_id'] = 0;
		}

		$data['review_logged'] = $this->customer->isLogged();
		$data['review_can_write'] = false;

		$data['list'] = $this->getList();

		if ($data['review_logged']) {
			$this->load->model('account/order');
			$this->load->model('catalog/review');

			$data['review_can_write'] = (bool)$this->model_account_order->getTotalCompletedOrdersByProductId($data['product_id'], $this->model_catalog_review->getReviewEligibleOrderStatusIds());
		} else {
			$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'))));
		}

		$data['text_purchase_required'] = $this->language->get('text_purchase_required');

		if ($this->customer->isLogged()) {
			$data['customer'] = $this->customer->getFirstName() . ' ' . $this->customer->getLastName();
		} else {
			$data['customer'] = '';
		}

		// Create a login token to prevent brute force attacks
		$data['review_token'] = $this->session->data['review_token'] = oc_token(32);

		// Captcha
		$this->load->model('setting/extension');

		$extension_info = $this->model_setting_extension->getExtensionByCode('captcha', $this->config->get('config_captcha'));

		if ($extension_info && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
			$data['captcha'] = $this->load->controller('extension/' . $extension_info['extension'] . '/captcha/' . $extension_info['code']);
		} else {
			$data['captcha'] = '';
		}

		$data['language'] = ($this->request->get['language'] ?? $this->config->get('config_language'));

		return $this->load->view('product/review', $data);
	}

	/**
	 * List
	 *
	 * @return void
	 */
	public function list(): void {
		$this->load->language('product/review');

		$this->response->setOutput($this->getList());
	}

	/**
	 * Get List
	 *
	 * @return string
	 */
	public function getList(): string {
		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		$limit = 5;

		// Review
		$data['reviews'] = [];
		$data['text_verified_purchase'] = $this->language->get('text_verified_purchase');

		$this->load->model('catalog/review');

		$review_total = $this->model_catalog_review->getTotalReviewsByProductId($product_id);
		$page_value = $this->request->get['page'] ?? 1;
		$page = max(1, is_scalar($page_value) ? (int)$page_value : 1);
		$page = min($page, max(1, (int)ceil($review_total / $limit)));

		$results = $this->model_catalog_review->getReviewsByProductId($product_id, ($page - 1) * $limit, $limit);

		foreach ($results as $result) {
			$data['reviews'][] = [
				'text'       => $result['text'],
				'rating'     => (int)$result['rating'],
				'verified'   => !empty($result['verified']),
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			] + $result;
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $review_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('product/review.list', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $product_id . '&page={page}')
		]);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($review_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($review_total - $limit)) ? $review_total : ((($page - 1) * $limit) + $limit), $review_total, ceil($review_total / $limit));

		return $this->load->view('product/review_list', $data);
	}

	/**
	 * Write
	 *
	 * @return void
	 */
	public function write(): void {
		$this->load->language('product/review');

		$json = [];

		if (isset($this->request->get['product_id'])) {
			$product_id = (int)$this->request->get['product_id'];
		} else {
			$product_id = 0;
		}

		$review_token = $this->request->get['review_token'] ?? null;
		$session_review_token = $this->session->data['review_token'] ?? null;

		if (!is_string($review_token) || !is_string($session_review_token) || !hash_equals($session_review_token, $review_token)) {
			$json['error']['warning'] = $this->language->get('error_token');
		}

		$author_value = $this->request->post['author'] ?? null;
		$text_value = $this->request->post['text'] ?? null;
		$rating_value = $this->request->post['rating'] ?? null;
		$author = is_scalar($author_value) ? trim((string)$author_value) : '';
		$text = is_scalar($text_value) ? trim((string)$text_value) : '';
		$rating_string = is_scalar($rating_value) ? (string)$rating_value : '';
		$rating = preg_match('/^[1-5]$/D', $rating_string) ? (int)$rating_string : 0;

		if (!$this->config->get('config_review_status')) {
			$json['error']['warning'] = $this->language->get('error_status');
		}

		// Product
		$this->load->model('catalog/product');

		$product_info = $this->model_catalog_product->getProduct($product_id);

		if (!$product_info) {
			$json['error']['warning'] = $this->language->get('error_product');
		}

		if (!oc_validate_length($author, 3, 25)) {
			$json['error']['author'] = $this->language->get('error_author');
		}

		if (!oc_validate_length($text, 25, 1000)) {
			$json['error']['text'] = $this->language->get('error_text');
		}

		if ($rating < 1 || $rating > 5) {
			$json['error']['rating'] = $this->language->get('error_rating');
		}

		if (!$this->customer->isLogged()) {
			$json['error']['warning'] = $this->language->get('error_login');
		} elseif ($product_info) {
			// Order
			$this->load->model('account/order');
			$this->load->model('catalog/review');

			if (!$this->model_account_order->getTotalCompletedOrdersByProductId($product_id, $this->model_catalog_review->getReviewEligibleOrderStatusIds())) {
				$json['error']['warning'] = $this->language->get('error_purchased');
			}
		}

		// Captcha
		$this->load->model('setting/extension');

		$extension_info = $this->model_setting_extension->getExtensionByCode('captcha', $this->config->get('config_captcha'));

		if ($extension_info && $this->config->get('captcha_' . $this->config->get('config_captcha') . '_status') && in_array('review', (array)$this->config->get('config_captcha_page'))) {
			$captcha = $this->load->controller('extension/' . $extension_info['extension'] . '/captcha/' . $extension_info['code'] . '.validate');

			if ($captcha) {
				$json['error']['captcha'] = $captcha;
			}
		}

		if (!$json) {
			$this->load->model('catalog/review');

			$this->model_catalog_review->addReview($product_id, [
				'author' => $author,
				'text'   => $text,
				'rating' => $rating
			]);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
