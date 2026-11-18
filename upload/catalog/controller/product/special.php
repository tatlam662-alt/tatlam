<?php
namespace Opencart\Catalog\Controller\Product;
/**
 * Class Special
 *
 * @package Opencart\Catalog\Controller\Product
 */
class Special extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('product/special');
		$data['heading_title'] = $this->language->get('heading_title');

		$rotation_bucket = intdiv(time() + (7 * 3600), 12 * 3600);
		$next_rotation_at = (($rotation_bucket + 1) * 12 * 3600) - (7 * 3600);

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'panda_random';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		$page = 1;

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . $url)
		];

		$data['text_compare'] = sprintf($this->language->get('text_compare'), isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0);

		$data['compare'] = $this->url->link('product/compare', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		// Product
		$data['products'] = [];
		$this->load->model('catalog/product');
		$product_total = $this->model_catalog_product->getTotalSpecials();
		$limit = max(1, $product_total);

		$filter_data = [
			'sort'        => $sort,
			'order'       => $order,
			'random_seed' => $rotation_bucket,
			'start'       => ($page - 1) * $limit,
			'limit'       => $limit
		];

		// Image
		$this->load->model('tool/image');

		$results = $this->model_catalog_product->getSpecials($filter_data);

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
				'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
				'href'        => $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $result['product_id'] . $url)
			] + $result;

			$data['products'][] = $this->load->controller('product/thumb', $product_data);
		}

		$url = '';

		$data['sorts'] = [];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_random_12_hours'),
			'value' => 'panda_random-ASC',
			'href'  => $this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=panda_random&order=ASC' . $url)
		];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_name_asc'),
			'value' => 'pd.name-ASC',
			'href'  => $this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=pd.name&order=ASC' . $url)
		];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_name_desc'),
			'value' => 'pd.name-DESC',
			'href'  => $this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=pd.name&order=DESC' . $url)
		];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_price_asc'),
			'value' => 'ps.price-ASC',
			'href'  => $this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=ps.price&order=ASC' . $url)
		];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_price_desc'),
			'value' => 'ps.price-DESC',
			'href'  => $this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=ps.price&order=DESC' . $url)
		];

		if ($this->config->get('config_review_status')) {
			$data['sorts'][] = [
				'text'  => $this->language->get('text_rating_desc'),
				'value' => 'rating-DESC',
				'href'  => $this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=rating&order=DESC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_rating_asc'),
				'value' => 'rating-ASC',
				'href'  => $this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=rating&order=ASC' . $url)
			];
		}

		$data['sorts'][] = [
			'text'  => $this->language->get('text_model_asc'),
			'value' => 'p.model-ASC',
			'href'  => $this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.model&order=ASC' . $url)
		];

		$data['sorts'][] = [
			'text'  => $this->language->get('text_model_desc'),
			'value' => 'p.model-DESC',
			'href'  => $this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&sort=p.model&order=DESC' . $url)
		];

		$data['product_count'] = $product_total;
		$data['next_rotation_at'] = $next_rotation_at;
		$data['next_rotation_in'] = max(0, $next_rotation_at - time());
		$this->document->addLink($this->url->link('product/special', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'))), 'canonical');

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['continue'] = $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('product/special', $data));
	}
}
