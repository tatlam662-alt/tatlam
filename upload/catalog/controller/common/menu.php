<?php
namespace Opencart\Catalog\Controller\Common;

class Menu extends \Opencart\System\Engine\Controller {
	public function index(): string {
		$this->load->language('common/menu');
		$data['text_service_links'] = $this->language->get('text_service_links');
		$this->load->model('catalog/category');
		$this->load->model('catalog/product');

		$language = 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'));
		$categories = $this->model_catalog_category->getCategories(0);
		$pc_category_path = '';

		foreach ($categories as $category) {
			$category_name = $this->normalizeName((string)$category['name']);

			if (str_contains($category_name, 'pc') || str_contains($category_name, 'linh kiện') || str_contains($category_name, 'component')) {
				$pc_category_path = (string)(int)$category['category_id'];

				foreach ($this->model_catalog_category->getCategories((int)$category['category_id']) as $child) {
					$child_name = $this->normalizeName((string)$child['name']);

					if (str_contains($child_name, 'pc') || str_contains($child_name, 'linh kiện') || str_contains($child_name, 'component')) {
						$pc_category_path .= '_' . (int)$child['category_id'];
						break;
					}
				}

				break;
			}
		}

		$data['service_links'] = [
			['name' => $this->language->get('text_flash_deals'), 'icon' => 'fa-solid fa-fire', 'href' => $this->url->link('product/special', $language)],
			['name' => $this->language->get('text_track_order'), 'icon' => 'fa-solid fa-box-open', 'href' => $this->url->link('account/order', $language)],
			['name' => $this->language->get('text_technology_news'), 'icon' => 'fa-regular fa-newspaper', 'href' => $this->url->link('cms/blog', $language)]
		];

		if ($pc_category_path) {
			array_splice($data['service_links'], 1, 0, [[
				'name' => $this->language->get('text_pc_category'),
				'icon' => 'fa-solid fa-microchip',
				'href' => $this->url->link('product/category', $language . '&path=' . $pc_category_path)
			]]);
		}

		$this->load->model('catalog/information');
		foreach ($this->model_catalog_information->getInformations() as $information) {
			$title = mb_strtolower((string)($information['title'] ?? ''));
			if (str_contains($this->normalizeName($title), 'bao hanh') || str_contains($title, 'warranty')) {
				$data['service_links'][] = [
					'name' => $this->language->get('text_warranty_policy'),
					'icon' => 'fa-solid fa-shield-halved',
					'href' => $this->url->link('information/information', $language . '&information_id=' . (int)$information['information_id'])
				];
				break;
			}
		}

		$data['categories'] = [];

		foreach ($categories as $category) {
			$children_data = [];

			foreach ($this->model_catalog_category->getCategories((int)$category['category_id']) as $child) {
				$filter_data = ['filter_category_id' => (int)$child['category_id'], 'filter_sub_category' => true];
				$children_data[] = [
					'name' => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
					'href' => $this->url->link('product/category', $language . '&path=' . (int)$category['category_id'] . '_' . (int)$child['category_id'])
				];
			}

			$data['categories'][] = [
				'children' => $children_data,
				'href' => $this->url->link('product/category', $language . '&path=' . (int)$category['category_id'])
				] + $category;
		}

		return $this->load->view('common/menu', $data);
	}

	private function normalizeName(string $value): string {
		$value = mb_strtolower($value, 'UTF-8');
		$normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

		return $normalized !== false ? strtolower($normalized) : $value;
	}
}
