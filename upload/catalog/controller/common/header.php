<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Header
 *
 * Can be called from $this->load->controller('common/header');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class Header extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		// Analytics
		$data['analytics'] = [];

		if (!$this->config->get('config_cookie_id') || (isset($this->request->cookie['policy']) && $this->request->cookie['policy'])) {
			// Extension
			$this->load->model('setting/extension');

			$analytics = $this->model_setting_extension->getExtensionsByType('analytics');

			foreach ($analytics as $analytic) {
				if ($this->config->get('analytics_' . $analytic['code'] . '_status')) {
					$data['analytics'][] = $this->load->controller('extension/' . $analytic['extension'] . '/analytics/' . $analytic['code'], $this->config->get('analytics_' . $analytic['code'] . '_status'));
				}
			}
		}

		$data['lang'] = ($this->request->get['language'] ?? $this->config->get('config_language'));
		$data['is_checkout_page'] = (($this->request->get['route'] ?? '') === 'checkout/checkout');
		$data['direction'] = $this->language->get('direction');

		$data['title'] = $this->document->getTitle();
		$data['base'] = $this->config->get('config_url');
		$data['description'] = $this->document->getDescription();
		$data['keywords'] = $this->document->getKeywords();

		// Hard coding css, so they can be replaced via the event's system.
		$data['bootstrap'] = 'catalog/view/stylesheet/bootstrap.css';
		$data['icons'] = 'catalog/view/stylesheet/fonts/fontawesome/css/all.min.css';
		$data['stylesheet'] = 'catalog/view/stylesheet/stylesheet.css?v=' . filemtime(DIR_APPLICATION . 'view/stylesheet/stylesheet.css');
		$data['panda_stylesheet'] = 'catalog/view/stylesheet/panda.css?v=' . filemtime(DIR_APPLICATION . 'view/stylesheet/panda.css');
		$data['pandashop_ui_stylesheet'] = 'catalog/view/stylesheet/pandashop-ui.css?v=' . filemtime(DIR_APPLICATION . 'view/stylesheet/pandashop-ui.css');
		$data['pandashop_checkout_stylesheet'] = 'catalog/view/stylesheet/pandashop-checkout.css?v=' . filemtime(DIR_APPLICATION . 'view/stylesheet/pandashop-checkout.css');

		// Hard coding scripts, so they can be replaced via the event's system.
		$data['jquery'] = 'catalog/view/javascript/jquery/jquery-3.7.1.min.js';
		$data['pd_dialog_script'] = 'catalog/view/javascript/common/pd-dialog.js?v=' . filemtime(DIR_APPLICATION . 'view/javascript/common/pd-dialog.js');
		$data['common_script'] = 'catalog/view/javascript/common.js?v=' . filemtime(DIR_APPLICATION . 'view/javascript/common.js');
		$data['panda_script'] = 'catalog/view/javascript/panda.js?v=' . filemtime(DIR_APPLICATION . 'view/javascript/panda.js');

		$data['links'] = $this->document->getLinks();
		$data['styles'] = $this->document->getStyles();
		$data['scripts'] = $this->document->getScripts('header');

		$data['name'] = $this->config->get('config_name');

		// Fav icon
		if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
			$data['icon'] = $this->config->get('config_url') . 'image/' . $this->config->get('config_icon');
		} else {
			$data['icon'] = '';
		}

		$data['logo'] = is_file(DIR_IMAGE . 'catalog/pandashop-panda-logo.svg') ? $this->config->get('config_url') . 'image/catalog/pandashop-panda-logo.svg' : '';

		$this->load->language('common/header');

		foreach ([
			'text_header_notice', 'text_support', 'text_store_system', 'text_order_lookup',
			'text_technology_news', 'text_catalog_categories', 'text_open_menu', 'text_close_menu', 'text_utilities',
			'text_home', 'text_categories', 'text_deals', 'text_cart_label', 'text_account_label', 'text_product_questions',
			'text_wishlist_label', 'text_compare_label', 'text_smember_close',
			'text_smember_description', 'text_open_assistant', 'text_service_links'
		] as $key) {
			$data[$key] = $this->language->get($key);
		}

		// Wishlist
		if ($this->customer->isLogged()) {
			$this->load->model('account/wishlist');

			$data['wishlist_count'] = $this->model_account_wishlist->getTotalWishlist($this->customer->getId());
		} else {
			$data['wishlist_count'] = isset($this->session->data['wishlist']) && is_array($this->session->data['wishlist']) ? count($this->session->data['wishlist']) : 0;
		}

		$data['text_wishlist'] = sprintf($this->language->get('text_wishlist'), $data['wishlist_count']);
		$data['text_wishlist_label'] = $this->language->get('text_wishlist_label');
		$data['compare_count'] = isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0;
		$data['text_compare'] = sprintf($this->language->get('text_compare'), $data['compare_count']);

		$data['home'] = $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['wishlist'] = $this->url->link('account/wishlist', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . (isset($this->session->data['customer_token']) ? '&customer_token=' . $this->session->data['customer_token'] : ''));
		$data['compare'] = $this->url->link('product/compare', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['logged'] = $this->customer->isLogged();
		$data['smember_checkout_login_required'] = !$data['logged'] && (
			!$this->config->get('config_checkout_guest')
			|| $this->config->get('config_customer_price')
			|| $this->cart->hasDownload()
			|| $this->cart->hasSubscription()
		);
		$data['checkin_login_prompt'] = '';

		if ($data['logged'] && !empty($this->session->data['pandashop_checkin_prompt']) && ($this->request->get['route'] ?? '') !== 'account/reward') {
			unset($this->session->data['pandashop_checkin_prompt']);
			$data['checkin_login_prompt'] = $this->load->controller('account/reward.loginPrompt');
		}

		if (!$this->customer->isLogged()) {
			$data['register'] = $this->url->link('account/register', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
			$data['login'] = $this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		} else {
			$data['account'] = $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
			$data['questions'] = $this->url->link('account/questions', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
			$data['questions_unread'] = (int)$this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "product_question_message` `m` INNER JOIN `" . DB_PREFIX . "product_question` `q` ON (`q`.`question_id` = `m`.`question_id`) INNER JOIN `" . DB_PREFIX . "product_question_contact` `c` ON (`c`.`question_id` = `q`.`question_id`) WHERE `q`.`customer_id` = '" . (int)$this->customer->getId() . "' AND `c`.`customer_hidden_at` IS NULL AND `m`.`sender_type` = 'admin' AND `m`.`read_by_customer` = '0'")->row['total'];
			$data['order'] = $this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
			$data['transaction'] = $this->url->link('account/transaction', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
			$data['download'] = $this->url->link('account/download', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
			$data['logout'] = $this->url->link('account/logout', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
			$language_code = $this->request->get['language'] ?? $this->config->get('config_language');
			$data['auto_logout_url'] = rtrim((string)$this->config->get('config_url'), '/') . '/index.php?route=account/logout.beaconLogout&language=' . rawurlencode((string)$language_code);
			if (empty($this->session->data['auto_logout_token'])) {
				$this->session->data['auto_logout_token'] = bin2hex(random_bytes(32));
			}
			$data['auto_logout_token'] = $this->session->data['auto_logout_token'];
		}

		$data['shopping_cart'] = $this->url->link('checkout/cart', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['checkout'] = $this->url->link('checkout/checkout', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['contact'] = $this->url->link('information/contact', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['telephone'] = $this->config->get('config_telephone');
		$data['topbar_store'] = $this->url->link('information/contact', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['topbar_order'] = $this->url->link('information/order_lookup', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['topbar_news'] = $this->url->link('cms/blog', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		$data['language'] = $this->load->controller('common/language');
		$data['currency'] = $this->load->controller('common/currency');
		$data['search'] = $this->load->controller('common/search');
		$data['cart'] = $this->load->controller('common/cart');
		$data['menu'] = $this->load->controller('common/menu');

		// Dynamic category tree for the catalog shortcut in the storefront header.
		$this->load->model('catalog/category');
		$data['header_categories'] = [];

		foreach ($this->model_catalog_category->getCategories(0) as $category) {
			$children_data = [];

			foreach ($this->model_catalog_category->getCategories($category['category_id']) as $child) {
				$children_data[] = [
					'name' => $child['name'],
					'href' => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . (int)$category['category_id'] . '_' . (int)$child['category_id'])
				];
			}

			$data['header_categories'][] = [
				'children' => $children_data,
				'href'     => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . (int)$category['category_id'])
				] + $category;
		}

		return $this->load->view('common/header', $data);
	}
}
