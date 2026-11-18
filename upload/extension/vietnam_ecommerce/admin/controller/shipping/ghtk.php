<?php
declare(strict_types=1);
namespace Opencart\Admin\Controller\Extension\VietnamEcommerce\Shipping;

class Ghtk extends \Opencart\System\Engine\Controller {
	private array $error = [];

	public function index(): void {
		$this->load->language('extension/vietnam_ecommerce/shipping/ghtk');
		$this->load->model('setting/setting');
		if (($this->request->server['REQUEST_METHOD'] ?? '') === 'POST') {
			if (!$this->user->hasPermission('modify', 'extension/vietnam_ecommerce/shipping/ghtk')) {
				$this->response->addHeader('HTTP/1.1 403 Forbidden');
				return;
			}
			if (!$this->validatePost()) {
				// Keep the form visible and report validation errors below.
			} else {
			$fields = ['status', 'token', 'pick_province', 'pick_district', 'default_weight', 'fallback_enabled', 'fallback_fee', 'tax_class_id', 'sort_order'];
			$settings = $this->model_setting_setting->getSetting('vietnam_ecommerce');
			foreach ($fields as $field) {
				$key = 'shipping_ghtk_' . $field;
				$settings[$key] = $field === 'token' && trim((string)($this->request->post[$key] ?? '')) === '' ? $this->config->get($key) : ($this->request->post[$key] ?? '');
			}
			$this->model_setting_setting->editSetting('vietnam_ecommerce', $settings);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/vietnam_ecommerce/shipping/ghtk', 'user_token=' . $this->session->data['user_token']));
			}
		}
		$data = $this->language->all();
		$data['heading_title'] = $this->language->get('heading_title');
		$data['action'] = $this->url->link('extension/vietnam_ecommerce/shipping/ghtk', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping');
		$data['user_token'] = $this->session->data['user_token'];
		$data['success'] = $this->session->data['success'] ?? '';
		$data['error_warning'] = $this->error['warning'] ?? '';
		unset($this->session->data['success']);
		foreach (['status','pick_province','pick_district','default_weight','fallback_enabled','fallback_fee','tax_class_id','sort_order'] as $field) {
			$key = 'shipping_ghtk_' . $field;
			$posted = $this->request->post[$key] ?? null;
			$data[$key] = is_scalar($posted) ? $posted : $this->config->get($key);
		}
		$data['token_configured'] = (string)$this->config->get('shipping_ghtk_token') !== '';
		$data['fields'] = [
			['name' => 'status', 'label' => $data['entry_status'], 'value' => $data['shipping_ghtk_status'], 'type' => 'toggle'],
			['name' => 'token', 'label' => $data['entry_token'], 'value' => '', 'type' => 'secret'],
			['name' => 'pick_province', 'label' => $data['entry_pick_province'], 'value' => $data['shipping_ghtk_pick_province'], 'type' => 'text'],
			['name' => 'pick_district', 'label' => $data['entry_pick_district'], 'value' => $data['shipping_ghtk_pick_district'], 'type' => 'text'],
			['name' => 'default_weight', 'label' => $data['entry_default_weight'], 'value' => $data['shipping_ghtk_default_weight'], 'type' => 'number'],
			['name' => 'fallback_enabled', 'label' => $data['entry_fallback_enabled'], 'value' => $data['shipping_ghtk_fallback_enabled'], 'type' => 'toggle'],
			['name' => 'fallback_fee', 'label' => $data['entry_fallback_fee'], 'value' => $data['shipping_ghtk_fallback_fee'], 'type' => 'number'],
			['name' => 'tax_class_id', 'label' => $data['entry_tax_class'], 'value' => $data['shipping_ghtk_tax_class_id'], 'type' => 'number'],
			['name' => 'sort_order', 'label' => $data['entry_sort_order'], 'value' => $data['shipping_ghtk_sort_order'], 'type' => 'number']
		];
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/vietnam_ecommerce/shipping/ghtk', $data));
	}

	public function install(): void {
		if (!$this->user->hasPermission('modify', 'extension/vietnam_ecommerce/shipping/ghtk')) return;
		$this->load->model('setting/extension');
		$this->model_setting_extension->install('shipping', 'vietnam_ecommerce', 'ghtk');
		$this->response->redirect($this->url->link('extension/vietnam_ecommerce/shipping/ghtk', 'user_token=' . $this->session->data['user_token']));
	}

	public function uninstall(): void {
		if (!$this->user->hasPermission('modify', 'extension/vietnam_ecommerce/shipping/ghtk')) return;
		$this->load->model('setting/extension');
		$this->model_setting_extension->uninstall('shipping', 'ghtk');
		$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping'));
	}

	private function validatePost(): bool {
		foreach ($this->request->post as $value) {
			if (!is_scalar($value)) {
				$this->error['warning'] = $this->language->get('error_invalid');
				return false;
			}
		}
		foreach (['shipping_ghtk_status', 'shipping_ghtk_fallback_enabled'] as $key) {
			if (!in_array((string)($this->request->post[$key] ?? '0'), ['0', '1'], true)) $this->error['warning'] = $this->language->get('error_invalid');
		}
		foreach (['shipping_ghtk_default_weight', 'shipping_ghtk_fallback_fee', 'shipping_ghtk_tax_class_id', 'shipping_ghtk_sort_order'] as $key) {
			$value = $this->request->post[$key] ?? '';
			if ($value !== '' && (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < 0)) $this->error['warning'] = $this->language->get('error_invalid');
		}
		if (isset($this->request->post['shipping_ghtk_default_weight']) && (int)$this->request->post['shipping_ghtk_default_weight'] < 1) $this->error['warning'] = $this->language->get('error_invalid');
		foreach (['shipping_ghtk_pick_province', 'shipping_ghtk_pick_district'] as $key) {
			if (strlen(trim((string)($this->request->post[$key] ?? ''))) > 128) $this->error['warning'] = $this->language->get('error_invalid');
		}
		return !$this->error;
	}
}
