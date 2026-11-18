<?php
namespace Opencart\Catalog\Controller\Common;

/**
 * Homepage catalog sections populated from the active store catalog.
 */
class Home extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$description = $this->config->get('config_description');
		$language_id = (int)$this->config->get('config_language_id');

		if (isset($description[$language_id])) {
			$this->document->setTitle($description[$language_id]['meta_title']);
			$this->document->setDescription($description[$language_id]['meta_description']);
			$this->document->setKeywords($description[$language_id]['meta_keyword']);
		}

		$this->load->language('extension/opencart/module/banner');
		$this->load->language('common/home');
		$this->load->model('catalog/category');
		$this->load->model('catalog/manufacturer');
		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$language = 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'));

		$data['home_brands'] = [];
		foreach ($this->model_catalog_manufacturer->getManufacturers(['sort' => 'name', 'order' => 'ASC', 'start' => 0, 'limit' => 12]) as $brand) {
			$image = !empty($brand['image']) ? html_entity_decode($brand['image'], ENT_QUOTES, 'UTF-8') : '';
			$data['home_brands'][] = [
				'name' => $brand['name'],
				'image' => $image && is_file(DIR_IMAGE . $image) ? $this->model_tool_image->resize($image, 150, 56) : '',
				'href' => $this->url->link('product/manufacturer.info', $language . '&manufacturer_id=' . (int)$brand['manufacturer_id'])
			];
		}

		$flash_sale = $this->getFlashSaleProducts($language);
		$data['home_flash_products'] = $flash_sale['products'];
		$data['remaining_seconds'] = $flash_sale['remaining_seconds'];
		$data['home_has_specials'] = (bool)$this->model_catalog_product->getSpecials(['start' => 0, 'limit' => 1]);
		$data['home_flash_title'] = $this->language->get('text_flash_sale');
		foreach ([
			'text_home_explore', 'text_brand', 'text_flash_eyebrow', 'text_view_all',
			'text_refresh_in', 'text_category_aria_label', 'text_category_eyebrow',
			'text_category_description', 'text_explore_now'
		] as $key) {
			$data[$key] = $this->language->get($key);
		}
		$data['home_flash_href'] = $this->url->link($data['home_has_specials'] ? 'product/special' : 'product/search', $language . ($data['home_has_specials'] ? '' : '&sort=p.date_added&order=DESC'));
		$data['home_catalog_sections'] = [];

		foreach ($this->model_catalog_category->getCategories(0) as $category) {
			$category_id = (int)$category['category_id'];
			$category_info = $this->model_catalog_category->getCategory($category_id);
			$results = $this->model_catalog_product->getProducts([
				'filter_category_id' => $category_id,
				'filter_sub_category' => true,
				'sort' => 'p.sort_order',
				'order' => 'ASC',
				'start' => 0,
				'limit' => 6
			]);

			if (!$results) {
				continue;
			}

			$image = !empty($category_info['image']) ? html_entity_decode($category_info['image'], ENT_QUOTES, 'UTF-8') : '';

			if (!$image || !is_file(DIR_IMAGE . $image)) {
				$image = !empty($results[0]['image']) ? html_entity_decode($results[0]['image'], ENT_QUOTES, 'UTF-8') : '';
			}

			$description = trim(strip_tags(html_entity_decode($category_info['description'] ?? '', ENT_QUOTES, 'UTF-8')));

			$data['home_catalog_sections'][] = [
				'name' => $category['name'],
				'href' => $this->url->link('product/category', $language . '&path=' . $category_id),
				'image' => $image && is_file(DIR_IMAGE . $image) ? $this->model_tool_image->resize($image, 720, 480) : '',
				'description' => oc_substr($description, 0, 180),
				'products' => $this->formatHomeProducts($results, $language)
			];
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('common/home', $data));
	}

	private function formatHomeProducts(array $results, string $language): array {
		$products = [];

		foreach ($results as $product) {
			$image = !empty($product['image']) ? html_entity_decode($product['image'], ENT_QUOTES, 'UTF-8') : '';

			if (!$image || !is_file(DIR_IMAGE . $image)) {
				$image = 'placeholder.png';
			}

			$price = false;
			$special = false;

			if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
				$price = $this->currency->format(
					$this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')),
					$this->session->data['currency']
				);
			}

			if ((float)$product['special']) {
				$special = $this->currency->format(
					$this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')),
					$this->session->data['currency']
				);
			}

			$products[] = $this->load->controller('product/thumb', [
				'product_id' => (int)$product['product_id'],
				'thumb' => $this->model_tool_image->resize($image, 360, 360),
				'name' => $product['name'],
				'description' => oc_substr(trim(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'))), 0, (int)$this->config->get('config_product_description_length')),
				'price' => $price,
				'special' => $special,
				'tax' => $this->config->get('config_tax') ? $this->currency->format((float)$product['special'] ? $product['special'] : $product['price'], $this->session->data['currency']) : false,
				'quantity' => $product['quantity'] ?? null,
				'date_added' => $product['date_added'] ?? '',
				'reward' => $product['reward'] ?? 0,
				'minimum' => !empty($product['minimum']) && (int)$product['minimum'] > 0 ? (int)$product['minimum'] : 1,
				'rating' => $product['rating'] ?? 0,
				'href' => $this->url->link('product/product', $language . '&product_id=' . (int)$product['product_id'])
			]);
		}

		return $products;
	}

	/**
	 * Keep a randomized set of four storefront products for twelve hours.
	 * The cached expiry timestamp is also used by the client-side countdown.
	 *
	 * @return array{products: array, remaining_seconds: int}
	 */
	private function getFlashSaleProducts(string $language): array {
		$now = time();
		$store_id = (int)$this->config->get('config_store_id');
		$language_id = (int)$this->config->get('config_language_id');
		$cache_key = 'home.flash_sale.' . $store_id . '.' . $language_id;
		$cache_data = $this->cache->get($cache_key);

		if (!is_array($cache_data) || !isset($cache_data['expire_time'], $cache_data['product_ids']) || (int)$cache_data['expire_time'] <= $now || !is_array($cache_data['product_ids'])) {
			$query = $this->db->query("SELECT `p`.`product_id` FROM `" . DB_PREFIX . "product` `p` INNER JOIN `" . DB_PREFIX . "product_to_store` `p2s` ON (`p2s`.`product_id` = `p`.`product_id` AND `p2s`.`store_id` = '" . $store_id . "') INNER JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = '" . $language_id . "') WHERE `p`.`status` = '1' AND `p`.`date_available` <= NOW() ORDER BY RAND() LIMIT 4");
			$product_ids = array_map('intval', array_column($query->rows, 'product_id'));
			$expire_time = $now + (12 * 3600);

			$cache_data = [
				'product_ids' => $product_ids,
				'expire_time' => $expire_time
			];

			$this->cache->set($cache_key, $cache_data, 12 * 3600);
		}

		$products = [];

		foreach ($cache_data['product_ids'] as $product_id) {
			$product = $this->model_catalog_product->getProduct((int)$product_id);

			if ($product) {
				$products[] = $product;
			}
		}

		if (!$products) {
			$products = $this->model_catalog_product->getProducts(['sort' => 'p.date_added', 'order' => 'DESC', 'start' => 0, 'limit' => 4]);
		}

		return [
			'products' => $this->formatHomeProducts($products, $language),
			'remaining_seconds' => max(0, (int)$cache_data['expire_time'] - time())
		];
	}
}
