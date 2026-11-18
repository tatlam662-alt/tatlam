<?php
namespace Opencart\Catalog\Controller\Information;
/**
 * Class Information
 *
 * @package Opencart\Catalog\Controller\Information
 */
class Information extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return ?\Opencart\System\Engine\Action
	 */
	public function index(): ?\Opencart\System\Engine\Action {
		$this->load->language('information/information');

		if (isset($this->request->get['information_id'])) {
			$information_id = (int)$this->request->get['information_id'];
		} else {
			$information_id = 0;
		}

		$this->load->model('catalog/information');

		$information_info = $this->model_catalog_information->getInformation($information_id);

		if ($information_info) {
			$this->document->setTitle($information_info['meta_title']);
			$this->document->setDescription($information_info['meta_description']);
			$this->document->setKeywords($information_info['meta_keyword']);

			$data['breadcrumbs'] = [];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
			];

			$data['breadcrumbs'][] = [
				'text' => $information_info['title'],
				'href' => $this->url->link('information/information', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&information_id=' . $information_id)
			];

			$data['heading_title'] = $information_info['title'];
			$data['button_back'] = $this->language->get('button_back');

			$data['description'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');
			$information_description_text = trim(strip_tags($data['description']));

			if ($information_id === 4 && ($this->request->get['language'] ?? $this->config->get('config_language')) === 'en-gb' && in_array($information_description_text, ['', trim((string)$information_info['title'])], true)) {
				$data['description'] = '<p>PanDaShop confirms the shipping method, cost and estimated delivery time during checkout.</p><ul><li>Standard shipping: VND 30,000 per order.</li><li>Free shipping on merchandise orders of VND 5,000,000 or more.</li><li>Estimated delivery: 1–3 business days in inner-city areas and 2–5 business days to other provinces.</li><li>Remote areas, islands and special-size parcels may require a shipping-cost confirmation before dispatch.</li><li>Actual delivery time depends on the carrier, order time and delivery conditions.</li></ul>';
			}

			$data['continue'] = $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('information/information', $data));
		} else {
			return new \Opencart\System\Engine\Action('error/not_found');
		}

		return null;
	}

	/**
	 * Info
	 *
	 * @return void
	 */
	public function info(): void {
		if (isset($this->request->get['information_id'])) {
			$information_id = (int)$this->request->get['information_id'];
		} else {
			$information_id = 0;
		}

		$this->load->model('catalog/information');

		$information_info = $this->model_catalog_information->getInformation($information_id);

		if ($information_info) {
			// The .info route is intended for AJAX modal requests. If opened directly,
			// send visitors to the full information page so they get the site layout.
			$is_ajax = !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

			if (!$is_ajax) {
				$this->response->redirect($this->url->link('information/information', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&information_id=' . $information_id, true));

				return;
			}

			$data['title'] = $information_info['title'];
			$data['description'] = html_entity_decode($information_info['description'], ENT_QUOTES, 'UTF-8');

			$this->response->addHeader('X-Robots-Tag: noindex');
			$this->response->setOutput($this->load->view('information/information_info', $data));
		}
	}
}
