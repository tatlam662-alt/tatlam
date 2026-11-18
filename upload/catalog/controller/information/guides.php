<?php
namespace Opencart\Catalog\Controller\Information;

class Guides extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('information/guides');

		$this->document->setTitle($this->language->get('heading_title'));
		$data['heading_title'] = $this->language->get('heading_title');
		$data['intro'] = $this->language->get('text_intro');
		$data['sections'] = [];

		foreach (['flycam', 'accessories', 'pc', 'support', 'orders'] as $section) {
			$data['sections'][] = [
				'title' => $this->language->get('text_' . $section . '_title'),
				'items' => [
					$this->language->get('text_' . $section . '_item_1'),
					$this->language->get('text_' . $section . '_item_2'),
					$this->language->get('text_' . $section . '_item_3')
				]
			];
		}

		$language = 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language'));
		foreach (['text_shipping_link', 'text_returns_link', 'text_warranty_link', 'text_contact_link'] as $key) {
			$data[$key] = $this->language->get($key);
		}

		$data['shipping_policy'] = $this->url->link('information/information', $language . '&information_id=4');
		$data['returns_policy'] = $this->url->link('information/information', $language . '&information_id=5');
		$data['warranty_policy'] = $this->url->link('information/information', $language . '&information_id=6');
		$data['contact'] = $this->url->link('information/contact', $language);
		$data['order_history'] = $this->url->link('information/order_lookup', $language);
		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', $language)
			],
			[
				'text' => $data['heading_title'],
				'href' => $this->url->link('information/guides', $language)
			]
		];

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('information/guides', $data));
	}
}
