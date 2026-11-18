<?php
namespace Opencart\Catalog\Controller\Extension\Pandashop;

/**
 * Product suggestions for the PanDaShop storefront search.
 *
 * @package Opencart\Catalog\Controller\Extension\Pandashop
 */
class Search extends \Opencart\System\Engine\Controller {
	/** Return a compact product and category suggestion list as JSON. */
	public function autocomplete(): void {
		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		$this->load->model('tool/image');

		$search_value = $this->request->get['search'] ?? '';
		$search = is_scalar($search_value) ? trim((string)$search_value) : '';
		$search = oc_substr($search, 0, 100);
		$language_value = $this->request->get['language'] ?? '';
		$language = is_scalar($language_value) ? (string)$language_value : (string)$this->config->get('config_language');
		$suggestions = ['products' => [], 'categories' => []];

		if (oc_strlen($search) >= 2) {
			$product_results = $this->model_catalog_product->getProducts([
				'filter_search' => $search,
				'sort'          => 'p.date_added',
				'order'         => 'DESC',
				'start'         => 0,
				'limit'         => 5
			]);

			foreach ($product_results as $result) {
				$image = !empty($result['image']) && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')) ? $result['image'] : 'placeholder.png';
				$amount = !empty($result['special']) ? (float)$result['special'] : (float)$result['price'];

				$price = '';

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($amount, $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				}

				$suggestions['products'][] = [
					'name'  => html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'),
					'image' => $this->model_tool_image->resize($image, 96, 96),
					'price' => $price,
					'href'  => html_entity_decode($this->url->link('product/product', 'language=' . $language . '&product_id=' . (int)$result['product_id']), ENT_QUOTES, 'UTF-8')
				];
			}

			$like = '%' . $this->db->escape($search) . '%';
			$query = $this->db->query("SELECT DISTINCT `c`.`category_id`, `c`.`parent_id`, `c`.`image`, `cd`.`name` FROM `" . DB_PREFIX . "category` `c` LEFT JOIN `" . DB_PREFIX . "category_description` `cd` ON (`c`.`category_id` = `cd`.`category_id`) LEFT JOIN `" . DB_PREFIX . "category_to_store` `c2s` ON (`c`.`category_id` = `c2s`.`category_id`) WHERE `c`.`status` = '1' AND `cd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "' AND `c2s`.`store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `cd`.`name` LIKE '" . $like . "' ORDER BY `c`.`sort_order`, LCASE(`cd`.`name`) LIMIT 3");

			foreach ($query->rows as $category) {
				$image = !empty($category['image']) && is_file(DIR_IMAGE . html_entity_decode($category['image'], ENT_QUOTES, 'UTF-8')) ? $this->model_tool_image->resize($category['image'], 72, 72) : '';
				$path = $this->getCategoryPath((int)$category['category_id']);

				$suggestions['categories'][] = [
					'name'  => html_entity_decode($category['name'], ENT_QUOTES, 'UTF-8'),
					'image' => $image,
					'href'  => html_entity_decode($this->url->link('product/category', 'language=' . $language . '&path=' . $path), ENT_QUOTES, 'UTF-8')
				];
			}
		}

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($suggestions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	/** Build the native category path for nested categories. */
	private function getCategoryPath(int $category_id): string {
		$parts = [];
		$current_id = $category_id;

		while ($current_id > 0) {
			$category = $this->model_catalog_category->getCategory($current_id);

			if (!$category) {
				break;
			}

			array_unshift($parts, $current_id);
			$current_id = (int)$category['parent_id'];
		}

		return implode('_', $parts);
	}
}
