<?php
declare(strict_types=1);

namespace Opencart\Catalog\Controller\Extension\VietnamEcommerce\Payment;

class Vietqr extends \Opencart\System\Engine\Controller {
	public function index(): string {
		$this->load->language('extension/vietnam_ecommerce/payment/vietqr');
		$data = [
			'qr_url' => '',
			'bank_name' => (string)$this->config->get('payment_vietqr_bank_name'),
			'account_name' => (string)$this->config->get('payment_vietqr_account_name'),
			'account_no' => (string)$this->config->get('payment_vietqr_account_no'),
			'order_id' => (int)($this->session->data['order_id'] ?? 0)
		];
		foreach (['text_transfer_note', 'text_qr_unavailable', 'text_order_reference', 'text_checkout_hint', 'text_bank', 'text_account_name', 'text_account_no', 'text_qr_alt'] as $key) {
			$data[$key] = $this->language->get($key);
		}
		$bankId = trim((string)$this->config->get('payment_vietqr_bank_id'));
		if ($data['order_id'] > 0 && $data['account_no'] !== '' && $bankId !== '') {
			$this->load->model('checkout/order');
			$order = $this->model_checkout_order->getOrder($data['order_id']);
			if ($order && strtoupper((string)$order['currency_code']) === 'VND') {
				$amount = (int)round((float)$order['total']);
				$data['qr_url'] = 'https://img.vietqr.io/image/' . rawurlencode($bankId) . '-' . rawurlencode($data['account_no']) . '-compact2.png?amount=' . $amount . '&addInfo=' . rawurlencode('PANDA' . $data['order_id']);
			}
		}
		return $this->load->view('extension/vietnam_ecommerce/payment/vietqr', $data);
	}

	public function confirm(): void {
		$this->load->language('extension/vietnam_ecommerce/payment/vietqr');
		$orderId = (int)($this->session->data['order_id'] ?? 0);
		$method = (string)($this->session->data['payment_method']['code'] ?? '');
		if ($orderId < 1 || $method !== 'vietqr.vietqr') {
			$this->sendJson(['error' => $this->language->get('error_order')], 400);
			return;
		}
		$this->load->model('checkout/order');
		$order = $this->model_checkout_order->getOrder($orderId);
		if (!$order) {
			$this->sendJson(['error' => $this->language->get('error_order')], 404);
			return;
		}
		if ((int)$order['order_status_id'] !== (int)$this->config->get('payment_vietqr_paid_status_id')) {
			$this->session->data['vietnam_ecommerce_vietqr_order_id'] = $orderId;
			$pendingStatusId = (int)$this->config->get('payment_vietqr_pending_status_id');
			if ($pendingStatusId > 0 && (int)$order['order_status_id'] !== $pendingStatusId) {
				$this->model_checkout_order->addHistory($orderId, $pendingStatusId, $this->language->get('text_waiting_payment'), false);
			}
		}
		$this->sendJson(['redirect' => $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'), true)]);
	}

	public function webhook(): void {
		if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST') {
			$this->sendJson(['ok' => false, 'error' => 'method_not_allowed'], 405);
			return;
		}
		$secret = (string)$this->config->get('payment_vietqr_webhook_secret');
		$provided = (string)($this->request->server['HTTP_X_VIETQR_TOKEN'] ?? '');
		if ($secret === '' || $provided === '' || !hash_equals($secret, $provided)) {
			$this->sendJson(['ok' => false, 'error' => 'unauthorized'], 401);
			return;
		}
		$contentLength = (int)($this->request->server['CONTENT_LENGTH'] ?? 0);
		if ($contentLength > 65536) {
			$this->sendJson(['ok' => false, 'error' => 'payload_too_large'], 413);
			return;
		}
		$rawBody = (string)file_get_contents('php://input');
		if (strlen($rawBody) > 65536) {
			$this->sendJson(['ok' => false, 'error' => 'payload_too_large'], 413);
			return;
		}
		$payload = json_decode($rawBody, true);
		if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
			$this->sendJson(['ok' => false, 'error' => 'invalid_json'], 400);
			return;
		}
		$orderId = filter_var($payload['order_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
		$rawAmount = $payload['amount'] ?? null;
		if (is_int($rawAmount)) {
			$amount = $rawAmount;
		} elseif (is_string($rawAmount) && preg_match('/\\A[0-9]+\\z/D', $rawAmount) === 1 && strlen($rawAmount) <= 15) {
			$amount = (int)$rawAmount;
		} else {
			$amount = false;
		}
		if ($orderId === false || $amount === false || $amount < 0) {
			$this->sendJson(['ok' => false, 'error' => 'invalid_payload'], 400);
			return;
		}
		$paidStatusId = (int)$this->config->get('payment_vietqr_paid_status_id');
		if ($paidStatusId < 1) {
			$this->sendJson(['ok' => false, 'error' => 'payment_status_not_configured'], 503);
			return;
		}
		$this->load->model('checkout/order');
		$order = $this->model_checkout_order->getOrder((int)$orderId);
		if (!$order || strtoupper((string)$order['currency_code']) !== 'VND') {
			$this->sendJson(['ok' => false, 'error' => 'order_not_found'], 404);
			return;
		}
		if ((string)($order['payment_code'] ?? '') !== 'vietqr.vietqr') {
			$this->sendJson(['ok' => false, 'error' => 'wrong_payment_method'], 409);
			return;
		}
		$paidHistory = $this->db->query("SELECT `order_history_id` FROM `" . DB_PREFIX . "order_history` WHERE `order_id` = '" . (int)$orderId . "' AND `order_status_id` = '" . $paidStatusId . "' LIMIT 1");
		if ((int)$order['order_status_id'] === $paidStatusId || $paidHistory->num_rows > 0) {
			$this->sendJson(['ok' => true, 'duplicate' => true]);
			return;
		}
		if ((int)round((float)$order['total']) !== $amount) {
			$this->sendJson(['ok' => false, 'error' => 'amount_mismatch'], 409);
			return;
		}
		$this->load->language('extension/vietnam_ecommerce/payment/vietqr');
		$this->model_checkout_order->addHistory((int)$orderId, $paidStatusId, $this->language->get('text_paid_via_webhook'), true, true);
		$this->sendJson(['ok' => true]);
	}

	private function sendJson(array $payload, int $status = 200): void {
		$reasons = [200 => 'OK', 400 => 'Bad Request', 401 => 'Unauthorized', 404 => 'Not Found', 405 => 'Method Not Allowed', 409 => 'Conflict', 413 => 'Content Too Large', 503 => 'Service Unavailable'];
		$this->response->addHeader('HTTP/1.1 ' . $status . ' ' . ($reasons[$status] ?? 'Error'));
		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput((string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
	}
}
