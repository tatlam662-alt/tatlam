<?php
namespace Opencart\Catalog\Controller\Account;
/**
 * Class Account
 *
 * @package Opencart\Catalog\Controller\Account
 */
class Account extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('account/account');
		$data = $this->language->all();

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

			$this->response->redirect($this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
		}

		$this->document->setTitle($this->language->get('heading_title'));
		$data['customer_firstname'] = $this->customer->getFirstName();
		$data['customer_email'] = $this->customer->getEmail();
		$data['customer_telephone'] = $this->customer->getTelephone();
		$data['reward_points'] = (int)$this->customer->getRewardPoints();
		$data['balance'] = $this->currency->format($this->customer->getBalance(), $this->session->data['currency']);

		$this->load->model('account/wishlist');
		$data['total_wishlist'] = $this->model_account_wishlist->getTotalWishlist($this->customer->getId());
		$data['wishlist_total'] = $data['total_wishlist'];

		$this->load->model('account/download');
		$data['total_downloads'] = $this->model_account_download->getTotalDownloads();

		// Load the related account models used by the dashboard's feature routes.
		$this->load->model('account/returns');
		$this->load->model('account/reward');
		$this->load->model('account/transaction');

		$this->load->model('account/order');
		$this->load->model('localisation/order_status');
		$data['total_orders'] = $this->model_account_order->getTotalOrders();
		$data['orders_total'] = $data['total_orders'];

		require_once DIR_SYSTEM . 'library/pandashop/membership.php';
		$complete_status_ids = array_values(array_unique(array_filter(array_map('intval', (array)$this->config->get('config_complete_status')), static fn(int $status_id): bool => $status_id > 0)));
		$completed_orders = $complete_status_ids ? $this->model_account_order->getTotalOrders($complete_status_ids) : 0;
		$member_progress = \Opencart\System\Library\PanDaShop\Membership::getProgress($completed_orders);
		$data['member_completed_orders'] = $completed_orders;
		$data['member_tier_current_name'] = $this->language->get('text_member_tier_' . $member_progress['current']['key']);
		$data['customer_group_name'] = $data['member_tier_current_name'];
		$data['member_tier_progress_percent'] = $member_progress['progress_percent'];
		$data['member_tier_progress_text'] = $member_progress['next']
			? sprintf($this->language->get('text_member_tier_progress'), $completed_orders, $member_progress['next']['orders'])
			: sprintf($this->language->get('text_member_tier_progress_max'), $completed_orders);
		$data['member_tier_remaining_text'] = $member_progress['next']
			? sprintf($this->language->get('text_member_tier_remaining'), $member_progress['orders_to_next'], $this->language->get('text_member_tier_' . $member_progress['next']['key']))
			: $this->language->get('text_member_tier_max_reached');
		$data['member_tier_levels'] = [];

		foreach ($member_progress['tiers'] as $tier) {
			$data['member_tier_levels'][] = [
				'name' => $this->language->get('text_member_tier_' . $tier['key']),
				'threshold' => $tier['orders'] > 0
					? sprintf($this->language->get('text_member_tier_orders'), $tier['orders'])
					: $this->language->get('text_member_tier_start'),
				'is_current' => $tier['key'] === $member_progress['current']['key'],
				'benefit' => $tier['bonus_percent'] > 0
					? sprintf($this->language->get('text_member_tier_bonus'), $tier['bonus_percent'])
					: $this->language->get('text_member_tier_base_benefit')
			];
		}

		$data['recent_order'] = [];

		$recent_orders = $this->model_account_order->getOrders(0, 1);

		if ($recent_orders) {
			$order = $recent_orders[0];
			$order_status_info = $this->model_localisation_order_status->getOrderStatus((int)$order['order_status_id']);

			$data['recent_order'] = [
				'order_id' => (int)$order['order_id'],
				'status' => $order_status_info['name'] ?? '',
				'date_added' => date($this->language->get('date_format_short'), strtotime($order['date_added'])),
				'total' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']),
				'view' => $this->url->link('account/order.info', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&order_id=' . (int)$order['order_id'])
			];
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_account'),
			'href' => $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'])
		];

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$data['edit'] = $this->url->link('account/edit', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['password'] = $this->url->link('account/password', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['address'] = $this->url->link('account/address', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['payment_method'] = $this->url->link('account/payment_method', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['wishlist'] = $this->url->link('account/wishlist', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['order'] = $this->url->link('account/order', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);

		$data['subscription'] = $this->url->link('account/subscription', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['download'] = $this->url->link('account/download', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);

		if ($this->config->get('total_reward_status')) {
			$data['reward'] = $this->url->link('account/reward', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		} else {
			$data['reward'] = '';
		}

		$data['return'] = $this->url->link('account/returns', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['transaction'] = $this->url->link('account/transaction', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['questions'] = $this->url->link('account/questions', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['questions_unread'] = (int)$this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "product_question_message` `m` INNER JOIN `" . DB_PREFIX . "product_question` `q` ON (`q`.`question_id` = `m`.`question_id`) INNER JOIN `" . DB_PREFIX . "product_question_contact` `c` ON (`c`.`question_id` = `q`.`question_id`) WHERE `q`.`customer_id` = '" . (int)$this->customer->getId() . "' AND `c`.`customer_hidden_at` IS NULL AND `m`.`sender_type` = 'admin' AND `m`.`read_by_customer` = '0'")->row['total'];
		$data['newsletter'] = $this->url->link('account/newsletter', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['continue'] = $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')));

		if ($this->config->get('config_affiliate_status')) {
			$data['affiliate'] = $this->url->link('account/affiliate', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);

			// Affiliate
			$this->load->model('account/affiliate');

			$affiliate_info = $this->model_account_affiliate->getAffiliate($this->customer->getId());
			$data['affiliate_exists'] = !empty($affiliate_info);

			if ($affiliate_info && !empty($affiliate_info['status'])) {
				$data['tracking'] = $this->url->link('account/tracking', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
				$data['affiliate_status_text'] = $this->language->get('text_affiliate_status_active');
			} elseif ($affiliate_info) {
				$data['tracking'] = '';
				$data['affiliate_status_text'] = $this->language->get('text_affiliate_status_pending');
			} else {
				$data['tracking'] = '';
				$data['affiliate_status_text'] = $this->language->get('text_affiliate_status_new');
			}
		} else {
			$data['affiliate'] = '';
		}

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('account/account', $data));
	}
}
