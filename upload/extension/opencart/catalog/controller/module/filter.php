<?php
namespace Opencart\Catalog\Controller\Extension\Opencart\Module;
/**
 * Class Filter
 *
 * @package Opencart\Catalog\Controller\Extension\Opencart\Module
 */
class Filter extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return string
	 */
	public function index(): string {
		// Category
		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
		} else {
			$parts = [];
		}

		$category_id = (int)end($parts);

		$this->load->model('catalog/category');

		$category_info = $this->model_catalog_category->getCategory($category_id);

		if ($category_info) {
			$this->load->language('extension/opencart/module/filter');
			$this->load->language('product/category');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['action'] = str_replace('&amp;', '&', $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . $this->request->get['path'] . $url));

			if (isset($this->request->get['filter'])) {
				$data['filter_category'] = explode(',', $this->request->get['filter']);
			} else {
				$data['filter_category'] = [];
			}

			$this->load->model('catalog/product');

			$data['filter_groups'] = [];
			$filter_counts = $this->model_catalog_product->getCategoryFilterCounts($category_id);

			$filter_groups = $this->model_catalog_category->getFilters($category_id);

			if ($filter_groups) {
				foreach ($filter_groups as $filter_group) {
					$children_data = [];

					foreach ($filter_group['filter'] as $filter) {
						$total = $filter_counts[(int)$filter['filter_id']] ?? 0;

						if ($total > 0) {
							$children_data[] = [
								'filter_id' => $filter['filter_id'],
								'name'      => $filter['name'] . ($this->config->get('config_product_count') ? ' (' . $total . ')' : '')
							];
						}
					}

					if ($children_data) {
						$data['filter_groups'][] = [
							'filter_group_id' => $filter_group['filter_group_id'],
							'name'            => $filter_group['name'],
							'filter'          => $children_data
						];
					}
				}

			}

			$language = (string)($this->request->get['language'] ?? $this->config->get('config_language'));
			$filter_ids = isset($this->request->get['filter']) && is_scalar($this->request->get['filter']) ? array_values(array_unique(array_filter(array_map('intval', explode(',', (string)$this->request->get['filter']))))) : [];
			$manufacturer_id = isset($this->request->get['manufacturer_id']) && is_scalar($this->request->get['manufacturer_id']) ? (int)$this->request->get['manufacturer_id'] : 0;
			$price_range = isset($this->request->get['price_range']) && is_scalar($this->request->get['price_range']) ? (string)$this->request->get['price_range'] : '';
			$price_ranges = [
				'0-5000000' => 'text_price_under_5m',
				'5000000-15000000' => 'text_price_5m_15m',
				'15000000-999999999999' => 'text_price_over_15m'
			];
			if (!isset($price_ranges[$price_range])) {
				$price_range = '';
			}
			$data['price_range'] = $price_range;
			$data['manufacturer_id'] = $manufacturer_id;
			$data['text_manufacturer'] = $this->language->get('text_filter_manufacturer');
			$data['text_price'] = $this->language->get('text_filter_price');
			$data['text_all_manufacturers'] = $this->language->get('text_filter_all_manufacturers');
			$data['text_all_prices'] = $this->language->get('text_filter_all_prices');
			$data['text_filter_title'] = $this->language->get('text_filter_title');
			$data['text_reset'] = $this->language->get('text_reset');
			$data['text_apply'] = $this->language->get('text_apply');
			$data['text_clear_all_filters'] = $this->language->get('text_clear_all_filters');
			$data['text_clear_all'] = $this->language->get('text_clear_all');
			$data['text_apply_filters'] = $this->language->get('text_apply_filters');
			$base_query = 'language=' . rawurlencode($language) . '&path=' . rawurlencode((string)$this->request->get['path']);

			if ($filter_ids) {
				$base_query .= '&filter=' . rawurlencode(implode(',', $filter_ids));
			}

			foreach (['sort', 'order', 'limit'] as $query_key) {
				if (isset($this->request->get[$query_key]) && is_scalar($this->request->get[$query_key])) {
					$base_query .= '&' . $query_key . '=' . rawurlencode((string)$this->request->get[$query_key]);
				}
			}

			$data['manufacturers'] = $this->getCategoryManufacturers($category_id);
			$data['manufacturers'] = array_values(array_filter($data['manufacturers'], static function (array $manufacturer): bool {
				$name = (string)($manufacturer['name'] ?? '');
				return stripos($name, 'pandashop') === false && stripos($name, 'panda') === false;
			}));

			foreach ($data['manufacturers'] as &$manufacturer) {
				$manufacturer['selected'] = (int)$manufacturer['manufacturer_id'] === $manufacturer_id;
				$manufacturer['href'] = $this->url->link('product/category', $base_query . ($price_range !== '' ? '&price_range=' . rawurlencode($price_range) : '') . '&manufacturer_id=' . (int)$manufacturer['manufacturer_id']);
			}
			unset($manufacturer);

			$data['all_manufacturers_href'] = $this->url->link('product/category', $base_query . ($price_range !== '' ? '&price_range=' . rawurlencode($price_range) : ''));
			$clear_query = 'language=' . rawurlencode($language) . '&path=' . rawurlencode((string)$this->request->get['path']);
			foreach (['sort', 'order', 'limit'] as $query_key) {
				if (isset($this->request->get[$query_key]) && is_scalar($this->request->get[$query_key])) {
					$clear_query .= '&' . $query_key . '=' . rawurlencode((string)$this->request->get[$query_key]);
				}
			}
			$data['clear_filters_href'] = $this->url->link('product/category', $clear_query);
			$data['price_ranges'] = [];

			foreach ($price_ranges as $range => $label_key) {
				$data['price_ranges'][] = [
					'value' => $range,
					'name' => $this->language->get($label_key),
					'selected' => $price_range === $range,
					'href' => $this->url->link('product/category', $base_query . '&price_range=' . rawurlencode($range) . ($manufacturer_id ? '&manufacturer_id=' . $manufacturer_id : ''))
				];
			}

			$data['all_prices_href'] = $this->url->link('product/category', $base_query . ($manufacturer_id ? '&manufacturer_id=' . $manufacturer_id : ''));

			return $this->load->view('extension/opencart/module/filter', $data);
		}

		return '';
	}

	/** @return array<int, array<string, mixed>> */
	private function getCategoryManufacturers(int $category_id): array {
		$query = $this->db->query("SELECT DISTINCT `m`.`manufacturer_id`, `m`.`name`
			FROM `" . DB_PREFIX . "manufacturer` `m`
			INNER JOIN `" . DB_PREFIX . "manufacturer_to_store` `m2s` ON (`m2s`.`manufacturer_id` = `m`.`manufacturer_id` AND `m2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
			INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`manufacturer_id` = `m`.`manufacturer_id` AND `p`.`status` = '1' AND `p`.`date_available` <= NOW())
			INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
			INNER JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`product_id` = `p`.`product_id`)
			INNER JOIN `" . DB_PREFIX . "category_path` `cp` ON (`cp`.`category_id` = `p2c`.`category_id` AND `cp`.`path_id` = '" . (int)$category_id . "')
			INNER JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c2s`.`category_id` = `p2c`.`category_id` AND `c2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
			ORDER BY `m`.`name` ASC");

		return $query->rows;
	}
}
