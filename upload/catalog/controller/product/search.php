<?php
namespace Opencart\Catalog\Controller\Product;
/**
 * Class Search
 *
 * @package Opencart\Catalog\Controller\Product
 */
class Search extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		foreach (['new_arrivals', 'search', 'description', 'tag', 'category_id', 'sub_category', 'in_stock', 'sort', 'order', 'page', 'limit', 'language'] as $parameter) {
			if (isset($this->request->get[$parameter]) && !is_scalar($this->request->get[$parameter])) {
				unset($this->request->get[$parameter]);
			}
		}

		$this->load->language('product/search');
		$is_new_arrivals = isset($this->request->get['new_arrivals']) && (string)$this->request->get['new_arrivals'] === '1';
		$arrivals_limit = 8;

		if (isset($this->request->get['search'])) {
			$filter_search = (string)$this->request->get['search'];
		} else {
			$filter_search = '';
		}

		if (isset($this->request->get['description'])) {
			$filter_description = (int)$this->request->get['description'];
		} else {
			$filter_description = 0;
		}

		if (isset($this->request->get['tag'])) {
			$filter_tag = (string)$this->request->get['tag'];
		} else {
			$filter_tag = '';
		}

		if (isset($this->request->get['category_id'])) {
			$filter_category_id = (int)$this->request->get['category_id'];
		} else {
			$filter_category_id = 0;
		}

		if (isset($this->request->get['sub_category'])) {
			$filter_sub_category = (int)$this->request->get['sub_category'];
		} else {
			$filter_sub_category = 0;
		}

			$in_stock = isset($this->request->get['in_stock']) && is_scalar($this->request->get['in_stock']) && (string)$this->request->get['in_stock'] === '1';

		if (isset($this->request->get['sort'])) {
			$sort = (string)$this->request->get['sort'];
		} else {
			$sort = $is_new_arrivals ? 'panda_arrival_random' : ($filter_search !== '' ? 'p.date_added' : 'p.sort_order');
		}

		if (isset($this->request->get['order'])) {
			$order = (string)$this->request->get['order'];
		} else {
			$order = $is_new_arrivals ? 'ASC' : ($filter_search !== '' ? 'DESC' : 'ASC');
		}

		if (isset($this->request->get['page'])) {
			$page = max(1, (int)$this->request->get['page']);
		} else {
			$page = 1;
		}

		$allowed_limits = [12, 20, 40];
		$requested_limit = isset($this->request->get['limit']) && is_scalar($this->request->get['limit']) ? (int)$this->request->get['limit'] : 12;
		$limit = in_array($requested_limit, $allowed_limits, true) ? $requested_limit : 12;

		if ($is_new_arrivals && $filter_search === '' && $filter_tag === '') {
			$this->document->setTitle($this->language->get('text_new_arrivals_title'));
		} elseif ($filter_search !== '') {
			$this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->request->get['search']);
		} elseif (isset($this->request->get['tag'])) {
			$this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('heading_tag') . $this->request->get['tag']);
		} else {
			$this->document->setTitle($this->language->get('heading_title'));
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		$url = ($is_new_arrivals ? '&new_arrivals=1' : '') . ($in_stock ? '&in_stock=1' : '');

		if (isset($this->request->get['search'])) {
			$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['description'])) {
			$url .= '&description=' . $this->request->get['description'];
		}

		if (isset($this->request->get['tag'])) {
			$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['category_id'])) {
			$url .= '&category_id=' . $this->request->get['category_id'];
		}

		if (isset($this->request->get['sub_category'])) {
			$url .= '&sub_category=' . $this->request->get['sub_category'];
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

		$data['breadcrumbs'][] = [
			'text' => $is_new_arrivals && $filter_search === '' ? $this->language->get('text_new_arrivals_title') : $this->language->get('heading_title'),
			'href' => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $url)
		];

		if ($is_new_arrivals && $filter_search === '' && $filter_tag === '') {
			$data['heading_title'] = $this->language->get('text_new_arrivals_title');
		} elseif ($filter_search !== '') {
			$data['heading_title'] = $this->language->get('heading_title') . ' - ' . $this->request->get['search'];
		} else {
			$data['heading_title'] = $this->language->get('heading_title');
		}

		$data['text_compare'] = sprintf($this->language->get('text_compare'), isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0);
		$data['compare_count'] = isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0;

		$data['compare'] = $this->url->link('product/compare', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		$this->load->model('catalog/category');

		$data['categories'] = $this->model_catalog_category->getCategoryTree();

		$data['new_arrivals_categories'] = [];

		foreach ($data['categories'] as $category) {
			if ($is_new_arrivals) {
				$data['new_arrivals_categories'][] = [
					'name' => $category['name'],
					'href' => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&new_arrivals=1&category_id=' . (int)$category['category_id'] . '&sub_category=1'),
					'active' => $filter_category_id === (int)$category['category_id']
				];
			}
		}

		$data['products'] = [];

		if ($filter_search || $filter_tag || $filter_category_id || $is_new_arrivals || $in_stock) {
			$filter_data = [
				'filter_search'       => $filter_search,
				'filter_description'  => $filter_description,
				'filter_tag'          => $filter_tag ? $filter_tag : $filter_search,
				'filter_category_id'  => $filter_category_id,
				'filter_sub_category' => $filter_sub_category,
				'filter_stock'        => $in_stock,
				'sort'                => $sort,
				'order'               => $order,
				'start'               => $is_new_arrivals ? 0 : ($page - 1) * $limit,
				'limit'               => $is_new_arrivals ? $arrivals_limit : $limit
			];

			// Product
			$this->load->model('catalog/product');

			// Image
			$this->load->model('tool/image');

			if ($is_new_arrivals) {
				$arrivals_cache_key = 'product.new_arrivals.' . (int)$this->config->get('config_store_id') . '.' . (int)$this->config->get('config_language_id') . '.' . $filter_category_id . '.' . $filter_sub_category . '.' . (int)$in_stock;
				$arrival_product_ids = $this->cache->get($arrivals_cache_key);

				if (!is_array($arrival_product_ids) || !$arrival_product_ids) {
					$arrivals_sql = "SELECT `p`.`product_id`
						FROM `" . DB_PREFIX . "product` `p`
						INNER JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "')
						INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')";

					if ($filter_category_id) {
						$arrivals_sql .= " INNER JOIN `" . DB_PREFIX . "product_to_category` `p2c` ON (`p2c`.`product_id` = `p`.`product_id`)";
						$arrivals_sql .= " INNER JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c2s`.`category_id` = `p2c`.`category_id` AND `c2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "')";

						if ($filter_sub_category) {
							$arrivals_sql .= " INNER JOIN `" . DB_PREFIX . "category_path` `cp` ON (`cp`.`category_id` = `p2c`.`category_id` AND `cp`.`path_id` = '" . $filter_category_id . "')";
						}
					}

					$arrivals_sql .= " WHERE `p`.`status` = '1' AND `p`.`date_available` <= NOW()";

					if ($in_stock) {
						$arrivals_sql .= " AND `p`.`quantity` > '0'";
					}

					if ($filter_category_id && !$filter_sub_category) {
						$arrivals_sql .= " AND `p2c`.`category_id` = '" . $filter_category_id . "'";
					}

					$arrivals_sql .= " GROUP BY `p`.`product_id` ORDER BY RAND() LIMIT " . $arrivals_limit;
					$arrivals_query = $this->db->query($arrivals_sql);
					$arrival_product_ids = array_map('intval', array_column($arrivals_query->rows, 'product_id'));

					if ($arrival_product_ids) {
						$this->cache->set($arrivals_cache_key, $arrival_product_ids, 2 * 24 * 60 * 60);
					}
				}

				$filter_data['filter_product_ids'] = array_slice(array_values(array_filter(array_map('intval', $arrival_product_ids))), 0, $arrivals_limit);
				$filter_data['arrival_order_ids'] = $filter_data['filter_product_ids'];
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
					'thumb'       => $this->model_tool_image->resize($image, $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height')),
					'description' => $description,
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'badge'       => (
						$is_new_arrivals &&
						!empty($result['date_added']) &&
						strtotime($result['date_added']) >= (time() - 30 * 86400)
					) ? $this->language->get('text_arrivals_badge') : '',
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'href'        => $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $result['product_id'] . $url)
				] + $result;

				$data['products'][] = $this->load->controller('product/thumb', $product_data);
			}

			$url = ($is_new_arrivals ? '&new_arrivals=1' : '') . ($in_stock ? '&in_stock=1' : '');

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}

			$data['sorts'] = [];

			if ($is_new_arrivals) {
				$data['sorts'][] = [
					'text'  => $this->language->get('text_arrivals_random_sort'),
					'value' => 'panda_arrival_random-ASC',
					'href'  => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=panda_arrival_random&order=ASC' . $url)
				];
			}

			$data['sorts'][] = [
				'text'  => $this->language->get('text_default'),
				'value' => 'p.sort_order-ASC',
				'href'  => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.sort_order&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_date_desc'),
				'value' => 'p.date_added-DESC',
				'href'  => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.date_added&order=DESC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=pd.name&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=pd.name&order=DESC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.price&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.price&order=DESC' . $url)
			];

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = [
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=rating&order=DESC' . $url)
				];

				$data['sorts'][] = [
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=rating&order=ASC' . $url)
				];
			}

			$data['sorts'][] = [
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.model&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.model&order=DESC' . $url)
			];

			$url = ($is_new_arrivals ? '&new_arrivals=1' : '') . ($in_stock ? '&in_stock=1' : '');

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$data['limits'] = [];

			$limits = [12, 20, 40];

			sort($limits);

			foreach ($limits as $value) {
				$data['limits'][] = [
					'text'  => $value,
					'value' => $value,
					'href'  => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $url . '&limit=' . $value)
				];
			}

			$url = ($is_new_arrivals ? '&new_arrivals=1' : '') . ($in_stock ? '&in_stock=1' : '');

			if (isset($this->request->get['search'])) {
				$url .= '&search=' . urlencode(html_entity_decode($this->request->get['search'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['description'])) {
				$url .= '&description=' . $this->request->get['description'];
			}

			if (isset($this->request->get['tag'])) {
				$url .= '&tag=' . urlencode(html_entity_decode($this->request->get['tag'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['category_id'])) {
				$url .= '&category_id=' . $this->request->get['category_id'];
			}

			if (isset($this->request->get['sub_category'])) {
				$url .= '&sub_category=' . $this->request->get['sub_category'];
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

			$product_total = $is_new_arrivals ? count($results) : $this->model_catalog_product->getTotalProducts($filter_data);
			$data['product_count'] = $product_total;

			if ($filter_search !== '' && (int)$product_total === 0) {
				$this->load->model('catalog/search');
				$this->model_catalog_search->addNoResultSearch($filter_search);
			}

			$data['pagination'] = $is_new_arrivals ? '' : $this->load->controller('common/pagination', [
				'total' => $product_total,
				'page'  => $page,
				'limit' => $limit,
				'url'   => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $url . '&page={page}')
			]);

			$data['results'] = sprintf($this->language->get('text_pagination'), $product_total ? 1 : 0, $product_total, $product_total, $product_total ? 1 : 0);

			if (isset($this->request->get['search']) && $this->config->get('config_customer_search')) {
				$this->load->model('account/search');

				if ($this->customer->isLogged()) {
					$customer_id = $this->customer->getId();
				} else {
					$customer_id = 0;
				}

				$search_data = [
					'keyword'      => $filter_tag ? $filter_tag : $filter_search,
					'description'  => $filter_description,
					'category_id'  => $filter_category_id,
					'sub_category' => $filter_sub_category,
					'products'     => $product_total,
					'customer_id'  => $customer_id,
					'ip'           => oc_get_ip()
				];

				$this->model_account_search->addSearch($search_data);
			}
		}

		$data['search'] = $filter_search;
		$data['has_search_filter'] = ($filter_search !== '' || $filter_category_id > 0 || $filter_tag !== '' || $is_new_arrivals);
		$data['is_new_arrivals'] = $is_new_arrivals;
		foreach ([
			'text_new_arrivals_title', 'text_arrivals_eyebrow', 'text_arrivals_intro',
			'text_arrivals_updated', 'text_arrivals_authentic', 'text_arrivals_categories',
			'text_arrivals_all', 'text_arrivals_collection', 'text_arrivals_empty_title',
			'text_arrivals_empty_description', 'text_products', 'text_filter_in_stock',
			'text_suggestion_loading', 'text_suggestion_empty', 'text_suggestion_error',
			'text_suggestion_products', 'text_suggestion_categories'
		] as $key) {
			$data[$key] = $this->language->get($key);
		}
		$data['search_home'] = $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . ($is_new_arrivals ? '&new_arrivals=1' : ''));
		$data['search_suggest_url'] = $this->url->link('extension/pandashop/search.autocomplete', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));
		$data['quick_searches'] = [];
		foreach (['DJI Mini 4', 'Osmo Pocket 3', 'RTX 4060', 'Akko'] as $term) {
			$data['quick_searches'][] = [
				'name' => $term,
				'href' => $this->url->link('product/search', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&search=' . rawurlencode($term))
			];
		}
		$data['product_count'] = $data['product_count'] ?? 0;
		$data['description'] = $filter_description;
		$data['in_stock'] = $in_stock;
		$data['category_id'] = $filter_category_id;
		$data['sub_category'] = $filter_sub_category;

		$data['sort'] = $sort;
		$data['order'] = $order;
		$data['limit'] = $limit;

		$data['language'] = ($this->request->get['language'] ?? $this->config->get('config_language'));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('product/search', $data));
	}
}
