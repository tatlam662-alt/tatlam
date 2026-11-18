<?php
declare(strict_types=1);
namespace Opencart\Catalog\Model\Extension\VietnamEcommerce\Event;

class Telegram extends \Opencart\System\Engine\Model {
	public function notifyOrderHistory(int $orderId, int $statusId): void {
		$token = trim((string)$this->config->get('vietnam_ecommerce_telegram_bot_token'));
		$chatId = trim((string)$this->config->get('vietnam_ecommerce_telegram_chat_id'));
		if ($token === '' || $chatId === '' || $orderId < 1 || !function_exists('curl_init')) return;
		$this->load->model('checkout/order');
		$this->load->language('extension/vietnam_ecommerce/event/order');
		$order = $this->model_checkout_order->getOrder($orderId);
		if (!$order) return;
		$products = $this->model_checkout_order->getProducts($orderId);
		$items = [];
		foreach ($products as $product) {
			$name = htmlspecialchars((string)$product['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			$items[] = '• ' . $name . ' × ' . (int)$product['quantity'];
		}
		$customer = htmlspecialchars(trim((string)$order['firstname'] . ' ' . (string)$order['lastname']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		$phone = htmlspecialchars((string)$order['telephone'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		$addressParts = [(string)$order['address_1'], (string)$order['city'], (string)$order['zone']];
		$address = htmlspecialchars(trim(implode(', ', array_filter($addressParts))), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		$currency = htmlspecialchars((string)$order['currency_code'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		$amount = number_format((float)$order['total'], 0, ',', '.') . ' ' . $currency;
		$statusQuery = $this->db->query("SELECT `name` FROM `" . DB_PREFIX . "order_status` WHERE `order_status_id` = '" . $statusId . "' AND `language_id` = '" . (int)$this->config->get('config_language_id') . "'");
		$status = htmlspecialchars((string)($statusQuery->row['name'] ?? (string)$statusId), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
		$body = '<b>' . $this->language->get('text_title') . '</b>' . "\n" . '<b>' . $this->language->get('text_order') . ':</b> #' . $orderId . "\n" . '<b>' . $this->language->get('text_customer') . ':</b> ' . $customer . "\n" . '<b>' . $this->language->get('text_telephone') . ':</b> ' . $phone . "\n" . '<b>' . $this->language->get('text_address') . ':</b> ' . $address . "\n" . '<b>' . $this->language->get('text_products') . ':</b>' . "\n" . implode("\n", $items) . "\n" . '<b>' . $this->language->get('text_total') . ':</b> ' . $amount . "\n" . '<b>' . $this->language->get('text_status') . ':</b> ' . $status;
		$handle = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
		curl_setopt_array($handle, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['chat_id' => $chatId, 'text' => $body, 'parse_mode' => 'HTML', 'disable_web_page_preview' => 'true']), CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'], CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 3, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2]);
		$response = curl_exec($handle);
		$httpStatus = (int)curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
		curl_close($handle);
		if (!is_string($response) || $httpStatus < 200 || $httpStatus >= 300) $this->log->write('PanDaShop Telegram notification failed for order #' . $orderId . ' (HTTP ' . $httpStatus . ').');
	}
}
