<?php
namespace Opencart\Catalog\Controller\Common;
/**
 * Class Column Left
 *
 * Can be called from $this->load->controller('common/column_left');
 *
 * @package Opencart\Catalog\Controller\Common
 */
class ColumnLeft extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		// Layout
		$this->load->model('design/layout');

		if (isset($this->request->get['route'])) {
			$route = (string)$this->request->get['route'];
		} else {
			$route = 'common/home';
		}

		// Use one consistent member navigation on every signed-in account page.
		$data['account_sidebar'] = false;
		$data['is_category_page'] = $route === 'product/category';

		if (str_starts_with($route, 'account/') && $this->customer->isLogged()) {
			$this->load->language('account/account');
			$customer_token = '&customer_token=' . ($this->session->data['customer_token'] ?? '');
			$language = 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'));

			$data['account_sidebar'] = true;
			$data['account_sidebar_content'] = $this->load->controller('extension/opencart/module/account', ['panda_account_sidebar' => true]);
			$data['text_account'] = $this->language->get('text_account');
			$data['customer_name'] = trim($this->customer->getLastName() . ' ' . $this->customer->getFirstName());
			$data['account_links'] = [
				['route' => 'account/account', 'label' => 'Bảng điều khiển', 'icon' => 'fa-solid fa-chart-pie', 'href' => $this->url->link('account/account', $language . $customer_token)],
				['route' => 'account/order', 'label' => 'Đơn hàng của tôi', 'icon' => 'fa-solid fa-box-archive', 'href' => $this->url->link('account/order', $language . $customer_token)],
				['route' => 'account/wishlist', 'label' => 'Sản phẩm yêu thích', 'icon' => 'fa-solid fa-heart', 'href' => $this->url->link('account/wishlist', $language . $customer_token)],
				['route' => 'account/edit', 'label' => 'Th�ng tin c� nh�n', 'icon' => 'fa-solid fa-user-gear', 'href' => $this->url->link('account/edit', $language . $customer_token)],
				['route' => 'account/password', 'label' => 'Đổi mật khẩu', 'icon' => 'fa-solid fa-lock', 'href' => $this->url->link('account/password', $language . $customer_token)],
				['route' => 'account/address', 'label' => 'Sổ địa chỉ', 'icon' => 'fa-solid fa-location-dot', 'href' => $this->url->link('account/address', $language . $customer_token)],
				['route' => 'account/reward', 'label' => 'Điểm PanDaCoins', 'icon' => 'fa-solid fa-coins', 'href' => $this->url->link('account/reward', $language . $customer_token)],
				['route' => 'account/returns', 'label' => 'Đổi trả & bảo hành', 'icon' => 'fa-solid fa-shield-halved', 'href' => $this->url->link('account/returns', $language . $customer_token)],
				['route' => 'account/logout', 'label' => 'Đăng xuất', 'icon' => 'fa-solid fa-right-from-bracket', 'href' => $this->url->link('account/logout', $language)]
			];

			$navigation_labels = ['text_nav_dashboard', 'text_nav_orders', 'text_nav_wishlist', 'text_nav_profile', 'text_nav_password', 'text_nav_address', 'text_nav_reward', 'text_nav_returns', 'text_nav_logout'];

			foreach ($navigation_labels as $index => $key) {
				$data['account_links'][$index]['label'] = $this->language->get($key);
			}

			$data['current_route'] = $route;
		}

		$layout_id = 0;

		// Category
		if ($route == 'product/category' && isset($this->request->get['path'])) {
			$this->load->model('catalog/category');

			$path = explode('_', (string)$this->request->get['path']);

			$layout_id = $this->model_catalog_category->getLayoutId((int)end($path));
		}

		// Product
		if ($route == 'product/product' && isset($this->request->get['product_id'])) {
			$this->load->model('catalog/product');

			$layout_id = $this->model_catalog_product->getLayoutId((int)$this->request->get['product_id']);
		}

		// Manufacturer
		if ($route == 'product/manufacturer.info' && isset($this->request->get['manufacturer_id'])) {
			$this->load->model('catalog/manufacturer');

			$layout_id = $this->model_catalog_manufacturer->getLayoutId((int)$this->request->get['manufacturer_id']);
		}

		// Information
		if ($route == 'information/information' && isset($this->request->get['information_id'])) {
			$this->load->model('catalog/information');

			$layout_id = $this->model_catalog_information->getLayoutId((int)$this->request->get['information_id']);
		}

		// Blog
		if ($route == 'cms/blog' && isset($this->request->get['topic_id'])) {
			$this->load->model('cms/topic');

			$layout_id = $this->model_cms_topic->getLayoutId((int)$this->request->get['topic_id']);
		}

		if ($route == 'cms/blog.info' && isset($this->request->get['article_id'])) {
			$this->load->model('cms/article');

			$layout_id = $this->model_cms_article->getLayoutId((int)$this->request->get['article_id']);
		}

		if (!$layout_id) {
			$layout_id = $this->model_design_layout->getLayout($route);
		}

		if (!$layout_id) {
			$layout_id = $this->config->get('config_layout_id');
		}

		// Extension
		$this->load->model('setting/module');

		$data['modules'] = [];

		$modules = $this->model_design_layout->getModules($layout_id, 'column_left');

		foreach ($modules as $module) {
			$part = explode('.', $module['code']);

			if (isset($part[1]) && $this->config->get('module_' . $part[1] . '_status')) {
				$module_data = $this->load->controller('extension/' . $part[0] . '/module/' . $part[1]);

				if ($module_data) {
					$data['modules'][] = $module_data;
				}
			}

			if (isset($part[2])) {
				$setting_info = $this->model_setting_module->getModule((int)$part[2]);

				if ($setting_info && $setting_info['status']) {
					$output = $this->load->controller('extension/' . $part[0] . '/module/' . $part[1], $setting_info);

					if ($output) {
						$data['modules'][] = $output;
					}
				}
			}
		}

		if ($data['account_sidebar'] || $data['modules']) {
			return $this->load->view('common/column_left', $data);
		}

		return '';
	}
}
