<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Module;
/**
 * Class Category
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Module
 */
class Category extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		$this->load->language('extension/opencart/module/category');

		foreach ([
			'text_catalog_categories', 'text_subcategories', 'text_view_all', 'text_featured_brands',
			'text_updating_brands', 'text_new_products', 'text_no_new_products', 'text_product_lines',
			'text_reference_prices', 'text_under_5m', 'text_5m_15m', 'text_over_15m', 'text_budget_help'
		] as $key) {
			$data[$key] = $this->language->get($key);
		}

		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
		} else {
			$parts = [];
		}

		if (isset($parts[0])) {
			$data['category_id'] = $parts[0];
		} else {
			$data['category_id'] = 0;
		}

		if (isset($parts[1])) {
			$data['child_id'] = $parts[1];
		} else {
			$data['child_id'] = 0;
		}

		// Category
		$this->load->model('catalog/category');

		// Product
		$this->load->model('catalog/product');

		$data['categories'] = [];

		$categories = $this->model_catalog_category->getCategories(0);
		$category_ids = array_map(static fn (array $category): int => (int)$category['category_id'], $categories);
		$manufacturer_map = [];

		if ($category_ids) {
			$manufacturer_query = $this->db->query("SELECT DISTINCT `cp`.`path_id` AS `root_category_id`, `m`.`manufacturer_id`, `m`.`name`
				FROM `" . DB_PREFIX . "manufacturer` `m`
				INNER JOIN `" . DB_PREFIX . "manufacturer_to_store` `m2s` ON (`m2s`.`manufacturer_id` = `m`.`manufacturer_id` AND `m2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
				INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`manufacturer_id` = `m`.`manufacturer_id` AND `p`.`status` = '1' AND `p`.`date_available` <= NOW())
				INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
				INNER JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`product_id` = `p`.`product_id`)
				INNER JOIN `" . DB_PREFIX . "category_path` `cp` ON (`cp`.`category_id` = `p2c`.`category_id` AND `cp`.`path_id` IN (" . implode(',', $category_ids) . "))
				INNER JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c2s`.`category_id` = `p2c`.`category_id` AND `c2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
				ORDER BY `cp`.`path_id`, `m`.`name` ASC");

			foreach ($manufacturer_query->rows as $manufacturer) {
				$root_id = (int)$manufacturer['root_category_id'];
				$manufacturer_map[$root_id][] = [
					'name' => $manufacturer['name'],
					'href' => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . $root_id . '&manufacturer_id=' . (int)$manufacturer['manufacturer_id'])
				];
			}
		}

		foreach ($categories as $category) {
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
			$children = $this->model_catalog_category->getCategories($category_id);

			foreach ($children as $child) {
				$filter_data = [
					'filter_category_id'  => (int)$child['category_id'],
					'filter_sub_category' => true
				];

				$children_data[] = [
					'category_id'  => $child['category_id'],
					'name'         => $child['name'],
					'product_count' => $this->config->get('config_product_count') ? $this->model_catalog_product->getTotalProducts($filter_data) : null,
					'href'         => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . $category['category_id'] . '_' . $child['category_id'])
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

			$filter_data = [
				'filter_category_id'  => $category['category_id'],
				'filter_sub_category' => true
			];

			$data['categories'][] = [
				'category_id'  => $category['category_id'],
				'name'         => $category['name'],
				'product_count' => $this->config->get('config_product_count') ? $this->model_catalog_product->getTotalProducts($filter_data) : null,
				'children'     => $children_data,
				'reference_prices' => $reference_prices,
				'brands'       => $manufacturer_map[(int)$category['category_id']] ?? [],
				'new_products' => $new_products_data,
				'href'         => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . $category['category_id'])
			];
		}

		return $this->load->view('extension/opencart/module/category', $data);
	}
}
