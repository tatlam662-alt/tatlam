<?php
declare(strict_types=1);
namespace Opencart\Admin\Controller\Extension\VietnamEcommerce\Module;

class Settings extends \Opencart\System\Engine\Controller {
	private array $error = [];

	public function index(): void {
		$data = [];
		$this->load->language('extension/vietnam_ecommerce/module/settings');
		$this->document->setTitle($this->language->get('heading_title'));
		foreach ($this->language->all() as $key => $value) $data[$key] = $value;
		$this->load->model('setting/setting');
		if (($this->request->server['REQUEST_METHOD'] ?? '') === 'POST') {
			if (!$this->user->hasPermission('modify', 'extension/vietnam_ecommerce/module/settings')) {
				$this->response->addHeader('HTTP/1.1 403 Forbidden');
				$this->error['warning'] = $this->language->get('error_permission');
			} elseif ($this->validate()) {
			$fields = ['shipping_ghtk_status','shipping_ghtk_token','shipping_ghtk_pick_province','shipping_ghtk_pick_district','shipping_ghtk_default_weight','shipping_ghtk_fallback_enabled','shipping_ghtk_fallback_fee','shipping_ghtk_tax_class_id','shipping_ghtk_sort_order','payment_vietqr_status','payment_vietqr_bank_id','payment_vietqr_bank_name','payment_vietqr_account_no','payment_vietqr_account_name','payment_vietqr_webhook_secret','payment_vietqr_pending_status_id','payment_vietqr_paid_status_id','payment_vietqr_sort_order','vietnam_ecommerce_telegram_bot_token','vietnam_ecommerce_telegram_chat_id'];
			$settings = [];
			foreach ($fields as $field) {
				$posted = $this->request->post[$field] ?? '';
				$posted = is_scalar($posted) ? trim((string)$posted) : '';
				if (in_array($field, ['shipping_ghtk_token','payment_vietqr_webhook_secret','vietnam_ecommerce_telegram_bot_token'], true) && $posted === '') {
					$settings[$field] = $this->config->get($field);
				} else {
					$settings[$field] = $posted;
				}
			}
			$this->model_setting_setting->editSetting('vietnam_ecommerce', $settings);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/vietnam_ecommerce/module/settings', 'user_token=' . $this->session->data['user_token']));
			}
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['save'] = $this->url->link('extension/vietnam_ecommerce/module/settings', 'user_token=' . $this->session->data['user_token']);
		$data['install'] = $this->url->link('extension/vietnam_ecommerce/module/settings.install', 'user_token=' . $this->session->data['user_token']);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
		$data['error_warning'] = $this->error['warning'] ?? '';
		$data['success'] = $this->session->data['success'] ?? '';
		unset($this->session->data['success']);
		foreach (['shipping_ghtk_status','shipping_ghtk_pick_province','shipping_ghtk_pick_district','shipping_ghtk_default_weight','shipping_ghtk_fallback_enabled','shipping_ghtk_fallback_fee','shipping_ghtk_tax_class_id','shipping_ghtk_sort_order','payment_vietqr_status','payment_vietqr_bank_id','payment_vietqr_bank_name','payment_vietqr_account_no','payment_vietqr_account_name','payment_vietqr_pending_status_id','payment_vietqr_paid_status_id','payment_vietqr_sort_order','vietnam_ecommerce_telegram_chat_id'] as $key) {
			$posted = $this->request->post[$key] ?? null;
			$data[$key] = is_scalar($posted) ? $posted : $this->config->get($key);
		}
		$data['ghtk_token_configured'] = (string)$this->config->get('shipping_ghtk_token') !== '';
		$data['webhook_secret_configured'] = (string)$this->config->get('payment_vietqr_webhook_secret') !== '';
		$data['telegram_token_configured'] = (string)$this->config->get('vietnam_ecommerce_telegram_bot_token') !== '';
		$data['user_token'] = $this->session->data['user_token'];
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/vietnam_ecommerce/module/settings', $data));
	}

	public function install(): void {
		if (!$this->user->hasPermission('modify', 'extension/vietnam_ecommerce/module/settings')) {
			$this->response->addHeader('HTTP/1.1 403 Forbidden');
			return;
		}
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "zone_district` (`district_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `zone_id` INT UNSIGNED NOT NULL, `name` VARCHAR(128) NOT NULL, `status` TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (`district_id`), UNIQUE KEY `uq_zone_district_name` (`zone_id`,`name`), KEY `idx_zone_status_name` (`zone_id`,`status`,`name`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		$this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "zone_ward` (`ward_id` INT UNSIGNED NOT NULL AUTO_INCREMENT, `district_id` INT UNSIGNED NOT NULL, `name` VARCHAR(128) NOT NULL, `status` TINYINT(1) NOT NULL DEFAULT 1, PRIMARY KEY (`ward_id`), UNIQUE KEY `uq_district_ward_name` (`district_id`,`name`), KEY `idx_district_status_name` (`district_id`,`status`,`name`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		foreach (['zone_district', 'zone_ward'] as $table) {
			$column = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . $table . "` LIKE 'status'");
			if (!$column->num_rows) $this->db->query("ALTER TABLE `" . DB_PREFIX . $table . "` ADD `status` TINYINT(1) NOT NULL DEFAULT 1");
		}
		$this->load->model('setting/event');
		$this->load->model('setting/extension');
		$this->model_setting_extension->install('shipping', 'vietnam_ecommerce', 'ghtk');
		$this->model_setting_extension->install('payment', 'vietnam_ecommerce', 'vietqr');
		$this->model_setting_extension->install('module', 'vietnam_ecommerce', 'vietnam_ecommerce');
		$events = [
			['code' => 'vietnam_ecommerce_order_history_telegram', 'description' => 'PanDaShop Telegram notification after order history', 'trigger' => 'catalog/model/checkout/order/addHistory/after', 'action' => 'extension/vietnam_ecommerce/event/order.notify'],
			['code' => 'vietnam_ecommerce_shipping_address_fields', 'description' => 'PanDaShop district and ward fields in shipping address', 'trigger' => 'view/checkout/shipping_address/after', 'action' => 'extension/vietnam_ecommerce/event/checkout.shippingAddress'],
			['code' => 'vietnam_ecommerce_payment_address_fields', 'description' => 'PanDaShop compact payment address fields', 'trigger' => 'view/checkout/payment_address/after', 'action' => 'extension/vietnam_ecommerce/event/checkout.paymentAddress'],
			['code' => 'vietnam_ecommerce_vietqr_success', 'description' => 'PanDaShop VietQR on order success page', 'trigger' => 'view/common/success/before', 'action' => 'extension/vietnam_ecommerce/event/checkout.success'],
			['code' => 'vietnam_ecommerce_single_page_checkout', 'description' => 'PanDaShop single-page checkout template', 'trigger' => 'view/checkout/checkout/before', 'action' => 'extension/vietnam_ecommerce/event/checkout.singlePage']
		];
		foreach ($events as $event) {
			$this->model_setting_event->deleteEventByCode($event['code']);
			$event['status'] = 1;
			$event['sort_order'] = 0;
			$this->model_setting_event->addEvent($event);
		}
		$this->response->redirect($this->url->link('extension/vietnam_ecommerce/module/settings', 'user_token=' . $this->session->data['user_token']));
	}

	public function uninstall(): void {
		if (!$this->user->hasPermission('modify', 'extension/vietnam_ecommerce/module/settings')) {
			$this->response->addHeader('HTTP/1.1 403 Forbidden');
			return;
		}
		$this->load->model('setting/event');
		$this->load->model('setting/extension');
		$this->model_setting_extension->uninstall('shipping', 'ghtk');
		$this->model_setting_extension->uninstall('payment', 'vietqr');
		$this->model_setting_extension->uninstall('module', 'vietnam_ecommerce');
		foreach (['vietnam_ecommerce_order_history_telegram', 'vietnam_ecommerce_shipping_address_fields', 'vietnam_ecommerce_payment_address_fields', 'vietnam_ecommerce_vietqr_success', 'vietnam_ecommerce_single_page_checkout'] as $code) $this->model_setting_event->deleteEventByCode($code);
		$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module'));
	}

	protected function validate(): bool {
		foreach ($this->request->post as $value) {
			if (!is_scalar($value)) {
				$this->error['warning'] = $this->language->get('error_invalid');
				return false;
			}
		}
		foreach (['shipping_ghtk_status', 'shipping_ghtk_fallback_enabled', 'payment_vietqr_status'] as $key) {
			if (isset($this->request->post[$key]) && !in_array((string)$this->request->post[$key], ['0', '1'], true)) $this->error['warning'] = $this->language->get('error_invalid');
		}
		foreach (['shipping_ghtk_default_weight', 'shipping_ghtk_fallback_fee', 'shipping_ghtk_tax_class_id', 'shipping_ghtk_sort_order', 'payment_vietqr_pending_status_id', 'payment_vietqr_paid_status_id', 'payment_vietqr_sort_order'] as $key) {
			$value = $this->request->post[$key] ?? '';
			if ($value !== '' && (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < 0)) $this->error['warning'] = $this->language->get('error_invalid');
		}
		$enabled = (int)($this->request->post['payment_vietqr_status'] ?? 0) === 1;
		$bankId = trim((string)($this->request->post['payment_vietqr_bank_id'] ?? ''));
		$accountNo = trim((string)($this->request->post['payment_vietqr_account_no'] ?? ''));
		$webhookSecret = trim((string)($this->request->post['payment_vietqr_webhook_secret'] ?? ''));
		$effectiveSecret = $webhookSecret !== '' ? $webhookSecret : (string)$this->config->get('payment_vietqr_webhook_secret');
		if ($bankId !== '' && $accountNo === '') $this->error['warning'] = $this->language->get('error_account');
		if ($enabled && ($bankId === '' || $accountNo === '' || strlen($effectiveSecret) < 32 || (int)($this->request->post['payment_vietqr_paid_status_id'] ?? 0) < 1)) $this->error['warning'] = $this->language->get('error_configuration');
		if ($bankId !== '' && preg_match('/\A[A-Za-z0-9_-]{2,32}\z/D', $bankId) !== 1) $this->error['warning'] = $this->language->get('error_invalid');
		if ($accountNo !== '' && preg_match('/\A[0-9]{5,30}\z/D', $accountNo) !== 1) $this->error['warning'] = $this->language->get('error_invalid');
		return !$this->error;
	}
}
