<?php
namespace Opencart\Catalog\Controller\Common;

class Search extends \Opencart\System\Engine\Controller {
	public function index(): string {
		$this->load->language('common/search');
		foreach (['text_search', 'text_search_placeholder', 'text_search_suggestions', 'text_trending_searches', 'text_suggestion_loading', 'text_suggestion_empty', 'text_suggestion_error', 'text_suggestion_products', 'text_suggestion_categories'] as $key) {
			$data[$key] = $this->language->get($key);
		}
		$language_value = $this->request->get['language'] ?? '';
		$language = is_scalar($language_value) ? (string)$language_value : (string)$this->config->get('config_language');
		$search_value = $this->request->get['search'] ?? '';
		$data['action'] = $this->url->link('common/search.redirect', 'language=' . $language);
		$data['search'] = is_scalar($search_value) ? (string)$search_value : '';
		$data['trending_links'] = [];
		foreach (['DJI Mini 4', 'Osmo Pocket 3', 'RTX 4060', 'Akko'] as $term) {
			$data['trending_links'][] = $this->url->link('product/search', 'language=' . $language . '&search=' . rawurlencode($term));
		}
		return $this->load->view('common/search', $data);
	}

	public function redirect(): void {
		$search_value = $this->request->post['search'] ?? '';
		$search = is_scalar($search_value) ? urlencode(html_entity_decode((string)$search_value, ENT_QUOTES, 'UTF-8')) : '';
		$language_value = $this->request->get['language'] ?? '';
		$language = is_scalar($language_value) ? (string)$language_value : (string)$this->config->get('config_language');
		$this->response->redirect($this->url->link('product/search', 'language=' . $language . '&search=' . $search, true));
	}
}