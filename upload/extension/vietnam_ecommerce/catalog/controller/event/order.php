<?php
declare(strict_types=1);
namespace Opencart\Catalog\Controller\Extension\VietnamEcommerce\Event;

class Order extends \Opencart\System\Engine\Controller {
	public function notify(string &$route, array &$args, mixed &$output): void {
		$orderId = (int)($args[0] ?? 0);
		$statusId = (int)($args[1] ?? 0);
		if ($orderId < 1) return;
		$this->load->model('extension/vietnam_ecommerce/event/telegram');
		$this->model_extension_vietnam_ecommerce_event_telegram->notifyOrderHistory($orderId, $statusId);
	}
}
