<?php
namespace Opencart\Admin\Controller\Report;

class SearchNoResult extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('report/search_no_result');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [
			[
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
			],
			[
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('report/search_no_result', 'user_token=' . $this->session->data['user_token'])
			]
		];

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_list'] = $this->language->get('text_list');
		$data['column_keyword'] = $this->language->get('column_keyword');
		$data['column_count'] = $this->language->get('column_count');
		$data['column_language'] = $this->language->get('column_language');
		$data['column_date_modified'] = $this->language->get('column_date_modified');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_error_loading'] = $this->language->get('text_error_loading');
		$data['user_token'] = $this->session->data['user_token'];
		$data['list'] = $this->getList();
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('report/search_no_result', $data));
	}

	public function list(): void {
		$this->load->language('report/search_no_result');
		$this->response->setOutput($this->getList());
	}

	protected function getList(): string {
		$page = max(1, (int)($this->request->get['page'] ?? 1));
		$limit = max(1, min(100, (int)$this->config->get('config_pagination_admin')));

		$this->load->model('report/search_no_result');
		$this->load->model('localisation/language');

		$languages = [];
		foreach ($this->model_localisation_language->getLanguages() as $language) {
			$languages[(int)$language['language_id']] = $language['name'];
		}

		$total = $this->model_report_search_no_result->getTotalSearches();
		$page = min($page, max(1, (int)ceil($total / $limit)));
		$data['searches'] = [];
		$results = $this->model_report_search_no_result->getSearches(($page - 1) * $limit, $limit);

		foreach ($results as $result) {
			$data['searches'][] = [
				'keyword' => $result['keyword'],
				'count' => (int)$result['search_count'],
				'language' => $languages[(int)$result['language_id']] ?? '',
				'date_modified' => date($this->language->get('datetime_format'), strtotime($result['date_modified']))
			];
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
			'url' => $this->url->link('report/search_no_result.list', 'user_token=' . $this->session->data['user_token'] . '&page={page}')
		]);
		$data['results'] = sprintf($this->language->get('text_pagination'), $total ? (($page - 1) * $limit) + 1 : 0, min($page * $limit, $total), $total, ceil($total / $limit));
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_error_loading'] = $this->language->get('text_error_loading');
		$data['column_keyword'] = $this->language->get('column_keyword');
		$data['column_count'] = $this->language->get('column_count');
		$data['column_language'] = $this->language->get('column_language');
		$data['column_date_modified'] = $this->language->get('column_date_modified');

		return $this->load->view('report/search_no_result_list', $data);
	}
}
