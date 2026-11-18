<?php
namespace Opencart\Catalog\Controller\Extension\Pandashop;

/** Session-based product recommendations for the NEXORA storefront. */
class RecentlyViewed extends \Opencart\System\Engine\Controller {
	public function index(int $current_product_id = 0): string {
		$product_ids = array_slice(array_values(array_filter(array_map('intval', (array)($this->session->data['nexora_recent_products'] ?? [])), static fn(int $id): bool => $id > 0 && $id !== $current_product_id)), 0, 4);

		if (!$product_ids) {
			return '';
		}

		$this->load->model('catalog/product');
		$this->load->model('tool/image');
		$data['heading_title'] = 'Bạn đã xem';
		$data['products'] = [];

		foreach ($product_ids as $product_id) {
			$product = $this->model_catalog_product->getProduct($product_id);

			if (!$product) {
				continue;
			}

			$image = $product['image'] ? html_entity_decode($product['image'], ENT_QUOTES, 'UTF-8') : 'placeholder.png';
			$price = $this->customer->isLogged() || !$this->config->get('config_customer_price')
				? $this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
				: false;
			$special = (float)$product['special']
				? $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency'])
				: false;

			$data['products'][] = $this->load->controller('product/thumb', [
				'product_id' => $product_id,
				'quantity' => (int)$product['quantity'],
				'thumb' => $this->model_tool_image->resize($image, 320, 320),
				'name' => $product['name'],
				'description' => oc_substr(trim(strip_tags(html_entity_decode($product['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('config_product_description_length')) . '..',
				'price' => $price,
				'special' => $special,
				'reward' => $product['reward'] ?? 0,
				'tax' => false,
				'minimum' => $product['minimum'] > 0 ? $product['minimum'] : 1,
				'rating' => $product['rating'],
				'href' => $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $product_id)
			]);
		}

		return $data['products'] ? $this->load->view('extension/opencart/module/featured', $data) : '';
	}
}
