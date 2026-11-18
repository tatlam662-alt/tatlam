<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Module;
/**
 * Class Banner
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Module
 */
class Banner extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @param array<string, mixed> $setting array of filters
	 *
	 * @return string
	 */
	public function index(array $setting): string {
		static $module = 0;

		$this->load->language('extension/opencart/module/banner');
		foreach ([
			'text_explore_store', 'text_featured_categories', 'text_catalog_categories', 'text_view_all',
			'text_featured_brands', 'text_updating_brands', 'text_new_products', 'text_no_new_products',
			'text_product_lines', 'text_reference_prices', 'text_under_5m', 'text_5m_15m', 'text_over_15m', 'text_budget_help',
			'text_previous_slide', 'text_next_slide', 'text_promotions', 'text_latest_arrivals', 'text_explore', 'text_technology_deals',
			'text_just_updated', 'text_daily_offer', 'text_hunt_deals', 'text_service_commitment',
			'text_authentic', 'text_replacement', 'text_express_delivery', 'text_installment',
			'text_shop_by_interest', 'text_explore_departments', 'text_department_intro',
			'text_explore_products', 'text_brands_heading', 'text_explore_brands'
		] as $key) {
			$data[$key] = $this->language->get($key);
		}

		// Banner
		$this->load->model('design/banner');

		// Image
		$this->load->model('tool/image');

		$data['banners'] = [];

		$results = $this->model_design_banner->getBanner($setting['banner_id']);

		foreach ($results as $result) {
			if (is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
				$data['banners'][] = [
					'title' => $result['title'],
					'link'  => $result['link'],
					'image' => $this->model_tool_image->resize(html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'), $setting['width'], $setting['height'])
				];
			}
		}

		// Build the hero sidebar from the same live category tree used by the main menu.
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/manufacturer');

		$data['categories'] = [];
		$data['home_brands'] = [];
		foreach ($this->model_catalog_manufacturer->getManufacturers(['sort' => 'name', 'order' => 'ASC', 'start' => 0, 'limit' => 8]) as $manufacturer) {
			$manufacturer_image = !empty($manufacturer['image']) ? html_entity_decode($manufacturer['image'], ENT_QUOTES, 'UTF-8') : '';
			$brand = [
				'name' => $manufacturer['name'],
				'href' => $this->url->link('product/manufacturer.info', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&manufacturer_id=' . (int)$manufacturer['manufacturer_id']),
				'image' => ''
			];

			if ($manufacturer_image && is_file(DIR_IMAGE . $manufacturer_image)) {
				$brand['image'] = $this->model_tool_image->resize($manufacturer_image, 150, 56);
			}

			$data['home_brands'][] = $brand;
		}

		$root_categories = $this->model_catalog_category->getCategories(0);
		$root_ids = array_map(static fn (array $category): int => (int)$category['category_id'], $root_categories);
		$manufacturer_map = [];

		if ($root_ids) {
			$manufacturer_query = $this->db->query("SELECT `cp`.`path_id` AS `root_category_id`, `m`.`manufacturer_id`, `m`.`name`, `m`.`image`
				FROM `" . DB_PREFIX . "manufacturer` `m`
				INNER JOIN `" . DB_PREFIX . "manufacturer_to_store` `m2s` ON (`m2s`.`manufacturer_id` = `m`.`manufacturer_id` AND `m2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
				INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`manufacturer_id` = `m`.`manufacturer_id` AND `p`.`status` = '1' AND `p`.`date_available` <= NOW())
				INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
				INNER JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`product_id` = `p`.`product_id`)
				INNER JOIN `" . DB_PREFIX . "category_path` `cp` ON (`cp`.`category_id` = `p2c`.`category_id` AND `cp`.`path_id` IN (" . implode(',', $root_ids) . "))
				INNER JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c2s`.`category_id` = `p2c`.`category_id` AND `c2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
				GROUP BY `cp`.`path_id`, `m`.`manufacturer_id`
				ORDER BY `cp`.`path_id`, `m`.`name` ASC");

			foreach ($manufacturer_query->rows as $manufacturer) {
				$root_id = (int)$manufacturer['root_category_id'];
				$manufacturer_map[$root_id][] = [
					'name' => $manufacturer['name'],
					'href' => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . $root_id . '&manufacturer_id=' . (int)$manufacturer['manufacturer_id'])
				];
			}
		}

		foreach ($root_categories as $category) {
			$category_id = (int)$category['category_id'];
			$reference_prices = [];

			foreach ([
				['value' => '0-5000000', 'name' => $data['text_under_5m']],
				['value' => '5000000-15000000', 'name' => $data['text_5m_15m']],
				['value' => '15000000-999999999999', 'name' => $data['text_over_15m']]
			] as $price) {
				$reference_prices[] = [
					'name' => $price['name'],
					'href' => $this->url->link('product/category', 'language=' . rawurlencode((string)($this->request->get['language'] ?? $this->config->get('config_language'))) . '&path=' . $category_id . '&price_range=' . rawurlencode($price['value']))
				];
			}

			$children_data = [];

			foreach ($this->model_catalog_category->getCategories($category_id) as $child) {
				$children_data[] = [
					'name' => $child['name'],
					'href' => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . (int)$category['category_id'] . '_' . (int)$child['category_id'])
				];
			}

			$new_products_data = [];
			$new_product_query = $this->db->query("SELECT DISTINCT `p`.`product_id`, `pd`.`name`, `p`.`price`, `p`.`image`
				FROM `" . DB_PREFIX . "product` `p`
				INNER JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "')
				INNER JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`product_id` = `p`.`product_id`)
				INNER JOIN `" . DB_PREFIX . "category_path` `cp` ON (`cp`.`category_id` = `p2c`.`category_id` AND `cp`.`path_id` = '" . (int)$category['category_id'] . "')
				INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
				INNER JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c2s`.`category_id` = `p2c`.`category_id` AND `c2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
				WHERE `p`.`status` = '1' AND `p`.`date_available` <= NOW()
				ORDER BY `p`.`date_added` DESC, `p`.`product_id` DESC
				LIMIT 4");

			foreach ($new_product_query->rows as $product) {
				$new_products_data[] = [
					'name' => $product['name'],
					'href' => $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . (int)$product['product_id'])
				];
			}

			$data['categories'][] = [
				'children'     => $children_data,
				'reference_prices' => $reference_prices,
				'brands'       => $manufacturer_map[(int)$category['category_id']] ?? [],
				'new_products' => $new_products_data,
				'href'         => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . (int)$category['category_id'])
				] + $category;
		}

		$data['mega_new_products'] = [];
		foreach ($this->model_catalog_product->getProducts(['sort' => 'p.date_added', 'order' => 'DESC', 'start' => 0, 'limit' => 4]) as $product) {
			$data['mega_new_products'][] = [
				'name' => $product['name'],
				'href' => $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . (int)$product['product_id'])
			];
		}
		$data['new_product_href'] = $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&new_arrivals=1');
		$data['special_href'] = $this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		if ($data['banners']) {
			$data['module'] = $module++;

			$data['effect'] = $setting['effect'];
			$data['controls'] = $setting['controls'];
			$data['indicators'] = $setting['indicators'];
			$data['items'] = $setting['items'];
			$data['interval'] = $setting['interval'];
			$data['width'] = $setting['width'];
			$data['height'] = $setting['height'];

			return $this->load->view('extension/opencart/module/banner', $data);
		} else {
			return '';
		}
	}
}
