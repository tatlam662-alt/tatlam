<?php
declare(strict_types=1);
namespace Opencart\Admin\Controller\Extension\VietnamEcommerce\Payment;

class Vietqr extends \Opencart\System\Engine\Controller {
	private array $error = [];

	public function index(): void {
		$this->load->language('extension/vietnam_ecommerce/payment/vietqr');
		$this->load->model('setting/setting');
		if (($this->request->server['REQUEST_METHOD'] ?? '') === 'POST') {
			if (!$this->user->hasPermission('modify', 'extension/vietnam_ecommerce/payment/vietqr')) {
				$this->response->addHeader('HTTP/1.1 403 Forbidden');
				return;
			}
			if ($this->validatePost()) {
			$fields = ['status', 'bank_id', 'bank_name', 'account_no', 'account_name', 'webhook_secret', 'pending_status_id', 'paid_status_id', 'sort_order'];
			$settings = $this->model_setting_setting->getSetting('vietnam_ecommerce');
			foreach ($fields as $field) {
				$key = 'payment_vietqr_' . $field;
				$settings[$key] = $field === 'webhook_secret' && trim((string)($this->request->post[$key] ?? '')) === '' ? $this->config->get($key) : ($this->request->post[$key] ?? '');
			}
			$this->model_setting_setting->editSetting('vietnam_ecommerce', $settings);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/vietnam_ecommerce/payment/vietqr', 'user_token=' . $this->session->data['user_token']));
			}
		}
		$data = $this->language->all();
		$data['heading_title'] = $this->language->get('heading_title');
		$data['action'] = $this->url->link('extension/vietnam_ecommerce/payment/vietqr', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment');
		$data['user_token'] = $this->session->data['user_token'];
		$data['success'] = $this->session->data['success'] ?? '';
		$data['error_warning'] = $this->error['warning'] ?? '';
		unset($this->session->data['success']);
		foreach (['status','bank_id','bank_name','account_no','account_name','pending_status_id','paid_status_id','sort_order'] as $field) {
			$key = 'payment_vietqr_' . $field;
			$posted = $this->request->post[$key] ?? null;
			$data[$key] = is_scalar($posted) ? $posted : $this->config->get($key);
		}
		$data['secret_configured'] = (string)$this->config->get('payment_vietqr_webhook_secret') !== '';
		$data['fields'] = [
			['name' => 'status', 'label' => $data['entry_status'], 'value' => $data['payment_vietqr_status'], 'type' => 'toggle'],
			['name' => 'bank_id', 'label' => $data['entry_bank_id'], 'value' => $data['payment_vietqr_bank_id'], 'type' => 'text'],
			['name' => 'bank_name', 'label' => $data['entry_bank_name'], 'value' => $data['payment_vietqr_bank_name'], 'type' => 'text'],
			['name' => 'account_no', 'label' => $data['entry_account_no'], 'value' => $data['payment_vietqr_account_no'], 'type' => 'text'],
			['name' => 'account_name', 'label' => $data['entry_account_name'], 'value' => $data['payment_vietqr_account_name'], 'type' => 'text'],
			['name' => 'webhook_secret', 'label' => $data['entry_webhook_secret'], 'value' => '', 'type' => 'secret'],
			['name' => 'pending_status_id', 'label' => $data['entry_pending_status_id'], 'value' => $data['payment_vietqr_pending_status_id'], 'type' => 'number'],
			['name' => 'paid_status_id', 'label' => $data['entry_paid_status_id'], 'value' => $data['payment_vietqr_paid_status_id'], 'type' => 'number'],
			['name' => 'sort_order', 'label' => $data['entry_sort_order'], 'value' => $data['payment_vietqr_sort_order'], 'type' => 'number']
		];
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/vietnam_ecommerce/payment/vietqr', $data));
	}

	public function install(): void {
		if (!$this->user->hasPermission('modify', 'extension/vietnam_ecommerce/payment/vietqr')) return;
		$this->load->model('setting/extension');
		$this->model_setting_extension->install('payment', 'vietnam_ecommerce', 'vietqr');
		$this->response->redirect($this->url->link('extension/vietnam_ecommerce/payment/vietqr', 'user_token=' . $this->session->data['user_token']));
	}

	public function uninstall(): void {
		if (!$this->user->hasPermission('modify', 'extension/vietnam_ecommerce/payment/vietqr')) return;
		$this->load->model('setting/extension');
		$this->model_setting_extension->uninstall('payment', 'vietqr');
		$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment'));
	}

	private function validatePost(): bool {
		foreach ($this->request->post as $value) {
			if (!is_scalar($value)) {
				$this->error['warning'] = $this->language->get('error_invalid');
				return false;
			}
		}
		$status = (string)($this->request->post['payment_vietqr_status'] ?? '0');
		if (!in_array($status, ['0', '1'], true)) $this->error['warning'] = $this->language->get('error_invalid');
		$bankId = trim((string)($this->request->post['payment_vietqr_bank_id'] ?? ''));
		$accountNo = trim((string)($this->request->post['payment_vietqr_account_no'] ?? ''));
		$secret = trim((string)($this->request->post['payment_vietqr_webhook_secret'] ?? ''));
		if ($secret === '') $secret = (string)$this->config->get('payment_vietqr_webhook_secret');
		if ($status === '1' && ($bankId === '' || $accountNo === '' || strlen($secret) < 32)) $this->error['warning'] = $this->language->get('error_configuration');
		if ($bankId !== '' && preg_match('/\A[A-Za-z0-9_-]{2,32}\z/D', $bankId) !== 1) $this->error['warning'] = $this->language->get('error_invalid');
		if ($accountNo !== '' && preg_match('/\A[0-9]{5,30}\z/D', $accountNo) !== 1) $this->error['warning'] = $this->language->get('error_invalid');
		foreach (['payment_vietqr_pending_status_id', 'payment_vietqr_paid_status_id', 'payment_vietqr_sort_order'] as $key) {
			$value = $this->request->post[$key] ?? '';
			if ($value !== '' && (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < 0)) $this->error['warning'] = $this->language->get('error_invalid');
		}
		if ($status === '1' && (int)($this->request->post['payment_vietqr_paid_status_id'] ?? 0) < 1) $this->error['warning'] = $this->language->get('error_configuration');
		return !$this->error;
	}
}
