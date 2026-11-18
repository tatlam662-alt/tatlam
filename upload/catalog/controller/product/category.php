<?php
namespace Opencart\Catalog\Controller\Product;
/**
 * Class Category
 *
 * @package Opencart\Catalog\Controller\Product
 */
class Category extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return \Opencart\System\Engine\Action|null
	 */
	public function index(): ?\Opencart\System\Engine\Action {
		$this->load->language('product/category');

		if (isset($this->request->get['path'])) {
			$path = (string)$this->request->get['path'];
		} else {
			$path = '';
		}

		$filter = isset($this->request->get['filter']) && is_scalar($this->request->get['filter']) ? (string)$this->request->get['filter'] : '';
		$filter_ids = array_unique(array_filter(array_map('intval', explode(',', $filter)), static fn (int $filter_id): bool => $filter_id > 0));
		$filter = implode(',', $filter_ids);

		$manufacturer_id = isset($this->request->get['manufacturer_id']) && is_scalar($this->request->get['manufacturer_id']) ? (int)$this->request->get['manufacturer_id'] : 0;
		$in_stock = isset($this->request->get['in_stock']) && is_scalar($this->request->get['in_stock']) && (string)$this->request->get['in_stock'] === '1';

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'p.sort_order';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		$page = isset($this->request->get['page']) && is_scalar($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;

		$default_limit = 12;
		$allowed_limits = [12, 20, 40];
		$requested_limit = isset($this->request->get['limit']) && is_scalar($this->request->get['limit']) ? (int)$this->request->get['limit'] : $default_limit;
		$limit = in_array($requested_limit, $allowed_limits, true) ? $requested_limit : $default_limit;

		$price_ranges = [
			'0-5000000' => [0, 5000000],
			'5000000-15000000' => [5000000, 15000000],
			'15000000-999999999999' => [15000000, 999999999999]
		];
		$price_range = isset($this->request->get['price_range']) && is_scalar($this->request->get['price_range']) ? (string)$this->request->get['price_range'] : '';
		$price_range = isset($price_ranges[$price_range]) ? $price_range : '';
		$price_min = $price_range !== '' ? $price_ranges[$price_range][0] : null;
		$price_max = $price_range !== '' ? $price_ranges[$price_range][1] : null;
		$price_url = ($price_range !== '' ? '&price_range=' . rawurlencode($price_range) : '') . ($in_stock ? '&in_stock=1' : '');

		// Category
		$parts = explode('_', $path);

		$category_id = (int)array_pop($parts);

		$this->load->model('catalog/category');

		$category_info = $this->model_catalog_category->getCategory($category_id);

		if ($category_info) {
			$this->document->setTitle($category_info['meta_title']);
			$this->document->setDescription($category_info['meta_description']);
			$this->document->setKeywords($category_info['meta_keyword']);

			$data['breadcrumbs'] = [];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
			];

			$url = $price_url;

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			if ($manufacturer_id) {
				$url .= '&manufacturer_id=' . $manufacturer_id;
			}

			$path = '';

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = (int)$path_id;
				} else {
					$path .= '_' . (int)$path_id;
				}

				$parent_info = $this->model_catalog_category->getCategory((int)$path_id);

				if ($parent_info) {
					$data['breadcrumbs'][] = [
						'text' => $parent_info['name'],
						'href' => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . $path . $url)
					];
				}
			}

			$url = $price_url;

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			// Set the last category breadcrumb
			$data['breadcrumbs'][] = [
				'text' => $category_info['name'],
				'href' => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $url)
			];

			$data['heading_title'] = $category_info['name'];
			$data['category_id'] = $category_id;
			$data['category_href'] = $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . (string)$this->request->get['path']);
			$data['filter'] = $filter;
			$data['price_range'] = $price_range;
			$data['in_stock'] = $in_stock;
			$data['manufacturer_id'] = $manufacturer_id;
			$data['clear_filters'] = $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . (string)$this->request->get['path']);

			$data['text_compare'] = sprintf($this->language->get('text_compare'), isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0);
		$data['compare_count'] = isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0;

			// Image
			$this->load->model('tool/image');

			if (!empty($category_info['image']) && is_file(DIR_IMAGE . html_entity_decode($category_info['image'], ENT_QUOTES, 'UTF-8'))) {
				$data['image'] = $this->model_tool_image->resize($category_info['image'], $this->config->get('config_image_category_width'), $this->config->get('config_image_category_height'));
			} else {
				$data['image'] = '';
			}

			$data['description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');
			$data['compare'] = $this->url->link('product/compare', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			// Only show manufacturers that currently have enabled products in this category tree.
			$data['manufacturers'] = $this->getCategoryManufacturers($category_id);
			$data['manufacturers'] = array_values(array_filter($data['manufacturers'], static function (array $manufacturer): bool {
				$name = (string)($manufacturer['name'] ?? '');
				return stripos($name, 'pandashop') === false && stripos($name, 'panda') === false;
			}));
			$filter_query = '&path=' . rawurlencode((string)$this->request->get['path']) . ($in_stock ? '&in_stock=1' : '');

			if ($filter !== '') {
				$filter_query .= '&filter=' . rawurlencode($filter);
			}

			foreach (['sort', 'order', 'limit'] as $query_key) {
				if (isset($this->request->get[$query_key]) && is_scalar($this->request->get[$query_key])) {
					$filter_query .= '&' . $query_key . '=' . rawurlencode((string)$this->request->get[$query_key]);
				}
			}

			foreach ($data['manufacturers'] as &$manufacturer) {
				$manufacturer['selected'] = (int)$manufacturer['manufacturer_id'] === $manufacturer_id;
				$manufacturer['href'] = $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $filter_query . ($price_range !== '' ? '&price_range=' . rawurlencode($price_range) : '') . '&manufacturer_id=' . (int)$manufacturer['manufacturer_id']);
			}
			unset($manufacturer);

			$data['text_filter_heading'] = $this->language->get('text_filter_heading');
			$data['text_filter_in_stock'] = $this->language->get('text_filter_in_stock');
			$data['text_filter_manufacturer'] = $this->language->get('text_filter_manufacturer');
			$data['text_filter_all_manufacturers'] = $this->language->get('text_filter_all_manufacturers');
			$data['text_filter_price'] = $this->language->get('text_filter_price');
			$data['text_filter_all_prices'] = $this->language->get('text_filter_all_prices');
			$data['text_clear_filters'] = $this->language->get('text_clear_filters');
			$data['all_manufacturers_href'] = $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $filter_query . ($price_range !== '' ? '&price_range=' . rawurlencode($price_range) : ''));
			$data['all_prices_href'] = $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $filter_query . ($manufacturer_id ? '&manufacturer_id=' . $manufacturer_id : ''));

			$data['price_ranges'] = [];

			foreach ($price_ranges as $range => $bounds) {
				$label_key = match ($range) {
					'0-5000000' => 'text_price_under_5',
					'5000000-15000000' => 'text_price_5_15',
					default => 'text_price_over_15'
				};

				$data['price_ranges'][] = [
					'value' => $range,
					'name' => $this->language->get($label_key),
					'text' => $this->language->get($label_key),
					'selected' => $price_range === $range,
					'href' => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $filter_query . '&price_range=' . rawurlencode($range) . ($manufacturer_id ? '&manufacturer_id=' . $manufacturer_id : ''))
				];
			}

			$data['text_active_filters'] = $this->language->get('text_active_filters');
			$data['active_filters'] = [];
			$active_filter_path = (string)($this->request->get['path'] ?? '');
			$make_active_filter_url = function (array $remaining_filter_ids = [], int $selected_manufacturer_id = 0, string $selected_price_range = '', bool $selected_in_stock = true) use ($active_filter_path): string {
				$query = 'language=' . rawurlencode((string)($this->request->get['language'] ?? $this->config->get('config_language'))) . '&path=' . rawurlencode($active_filter_path);

				if ($remaining_filter_ids) {
					$query .= '&filter=' . rawurlencode(implode(',', $remaining_filter_ids));
				}

				if ($selected_manufacturer_id) {
					$query .= '&manufacturer_id=' . $selected_manufacturer_id;
				}

				if ($selected_price_range !== '') {
					$query .= '&price_range=' . rawurlencode($selected_price_range);
				}

				if ($selected_in_stock) {
					$query .= '&in_stock=1';
				}

				foreach (['sort', 'order', 'limit'] as $query_key) {
					if (isset($this->request->get[$query_key]) && is_scalar($this->request->get[$query_key])) {
						$query .= '&' . $query_key . '=' . rawurlencode((string)$this->request->get[$query_key]);
					}
				}

				return $this->url->link('product/category', $query);
			};

			$data['stock_filter_href'] = $make_active_filter_url($filter_ids, $manufacturer_id, $price_range, !$in_stock);

			if ($in_stock) {
				$data['active_filters'][] = [
					'name' => $this->language->get('text_filter_in_stock'),
					'href' => $make_active_filter_url($filter_ids, $manufacturer_id, $price_range, false)
				];
			}

			if ($manufacturer_id) {
				foreach ($data['manufacturers'] as $manufacturer) {
					if ((int)$manufacturer['manufacturer_id'] === $manufacturer_id) {
						$data['active_filters'][] = [
							'name' => $manufacturer['name'],
							'href' => $make_active_filter_url($filter_ids, 0, $price_range)
						];
						break;
					}
				}
			}

			foreach ($data['price_ranges'] as $price_option) {
				if ($price_option['selected']) {
					$data['active_filters'][] = [
						'name' => $price_option['text'],
						'href' => $make_active_filter_url($filter_ids, $manufacturer_id, '')
					];
					break;
				}
			}

			if ($filter_ids) {
				foreach ($this->model_catalog_category->getFilters($category_id) as $filter_group) {
					foreach ($filter_group['filter'] as $filter_option) {
						$selected_filter_id = (int)$filter_option['filter_id'];

						if (in_array($selected_filter_id, $filter_ids, true)) {
							$data['active_filters'][] = [
								'name' => $filter_option['name'],
								'href' => $make_active_filter_url(array_values(array_diff($filter_ids, [$selected_filter_id])), $manufacturer_id, $price_range)
							];
						}
					}
				}
			}

			$url = $price_url;

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			if ($manufacturer_id) {
				$url .= '&manufacturer_id=' . $manufacturer_id;
			}

			// Product
			$this->load->model('catalog/product');

			$url = $price_url;

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			// Product
			$data['products'] = [];

			$filter_data = [
				'filter_category_id'  => $category_id,
				'filter_sub_category' => true,
				'filter_filter'       => $filter,
				'filter_manufacturer_id' => $manufacturer_id,
				'filter_stock'        => $in_stock,
				'sort'                => $sort,
				'order'               => $order,
				'start'               => ($page - 1) * $limit,
				'limit'               => $limit
			];

			if ($price_min !== null && $price_max !== null) {
				$filter_data['filter_price_min'] = $price_min;
				$filter_data['filter_price_max'] = $price_max;
			}

			$results = $this->model_catalog_product->getProducts($filter_data);

			foreach ($results as $result) {
				$description = trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')));

				if (oc_strlen($description) > $this->config->get('config_product_description_length')) {
					$description = oc_substr($description, 0, $this->config->get('config_product_description_length')) . '..';
				}

				if ($result['image'] && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8'))) {
					$image = $result['image'];
				} else {
					$image = 'placeholder.png';
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				$product_data = [
					'description' => $description,
					'thumb'       => $this->model_tool_image->resize($image, $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height')),
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'href'        => $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $result['product_id'] . $url)
				] + $result;

				$data['products'][] = $this->load->controller('product/thumb', $product_data);
			}

			$url = $price_url;

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			if ($manufacturer_id) {
				$url .= '&manufacturer_id=' . $manufacturer_id;
			}

			$data['sorts'] = [];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.sort_order&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_date_desc'),
				'value' => 'p.date_added-DESC',
				'href'  => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.date_added&order=DESC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=pd.name&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=pd.name&order=DESC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.price&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.price&order=DESC' . $url)
			];

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = [
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=rating&order=DESC' . $url)
				];

				$data['sorts'][] = [
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=rating&order=ASC' . $url)
				];
			}

			$data['sorts'][] = [
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.model&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.model&order=DESC' . $url)
			];

			$url = $price_url;

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if ($manufacturer_id) {
				$url .= '&manufacturer_id=' . $manufacturer_id;
			}

			$data['limits'] = [];

			$limits = [12, 20, 40];

			sort($limits);

			foreach ($limits as $value) {
				$data['limits'][] = [
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $url . '&limit=' . $value)
				];
			}

			$url = $price_url;

			if (isset($this->request->get['path'])) {
				$url .= '&path=' . $this->request->get['path'];
			}

			if (isset($this->request->get['filter'])) {
				$url .= '&filter=' . $this->request->get['filter'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}
			if ($manufacturer_id) {
				$url .= '&manufacturer_id=' . $manufacturer_id;
			}

			$product_total = $this->model_catalog_product->getTotalProducts($filter_data);
			$data['product_count'] = $product_total;

			$data['pagination'] = $this->load->controller('common/pagination', [
				'total' => $product_total,
				'page'  => $page,
				'limit' => $limit,
				'url'   => $this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $url . '&page={page}')
			]);

			$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $limit) + 1 : 0, ((($page - 1) * $limit) > ($product_total - $limit)) ? $product_total : ((($page - 1) * $limit) + $limit), $product_total, ceil($product_total / $limit));

			// https://developers.google.com/search/blog/2011/09/pagination-with-relnext-and-relprev
			if ($page == 1) {
				$this->document->addLink($this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . $this->request->get['path']), 'canonical');
			} else {
				$this->document->addLink($this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . $this->request->get['path'] . '&page=' . $page), 'canonical');
			}

			if ($page > 1) {
				$this->document->addLink($this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . $this->request->get['path'] . (($page - 2) ? '&page=' . ($page - 1) : '')), 'prev');
			}

			if ($limit && ceil($product_total / $limit) > $page) {
				$this->document->addLink($this->url->link('product/category', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&path=' . $this->request->get['path'] . '&page=' . ($page + 1)), 'next');
			}

			$data['sort'] = $sort;
			$data['order'] = $order;
			$data['limit'] = $limit;

			$data['continue'] = $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			// Return only the product results for the filter bar's asynchronous updates.
			// This avoids rebuilding the header, side modules and their per-option counts.
			if (($this->request->server['HTTP_X_PANDA_FILTER'] ?? '') === '1') {
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode([
					'html' => $this->load->view('product/category_results', $data),
					'count' => $product_total
				], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

				return null;
			}

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('product/category', $data));
		} else {
			return new \Opencart\System\Engine\Action('error/not_found');
		}

		return null;
	}

	/** Return manufacturers with enabled products assigned in this category subtree. */
	private function getCategoryManufacturers(int $category_id): array {
		$query = $this->db->query("SELECT DISTINCT `m`.`manufacturer_id`, `m`.`name`, `m`.`image`
			FROM `" . DB_PREFIX . "manufacturer` `m`
			INNER JOIN `" . DB_PREFIX . "manufacturer_to_store` `m2s` ON (`m2s`.`manufacturer_id` = `m`.`manufacturer_id` AND `m2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
			INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`manufacturer_id` = `m`.`manufacturer_id` AND `p`.`status` = '1' AND `p`.`date_available` <= NOW())
			INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
			INNER JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`product_id` = `p`.`product_id`)
			INNER JOIN `" . DB_PREFIX . "category_path` `cp` ON (`cp`.`category_id` = `p2c`.`category_id` AND `cp`.`path_id` = '" . $category_id . "')
			INNER JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c2s`.`category_id` = `p2c`.`category_id` AND `c2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')
			ORDER BY `m`.`name` ASC");

		return $query->rows;
	}
}
