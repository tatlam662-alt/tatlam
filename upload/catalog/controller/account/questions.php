<?php
namespace Opencart\Catalog\Controller\Account;

class Questions extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('account/questions');

		if (!$this->load->controller('account/login.validate')) {
			$this->session->data['redirect'] = $this->url->link('account/questions', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&question_id=' . (int)($this->request->get['question_id'] ?? 0));
			$this->response->redirect($this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
			return;
		}

		$this->document->setTitle($this->language->get('heading_title'));
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_home'] = $this->language->get('text_home');
		$data['text_account'] = $this->language->get('text_account');
		$data['breadcrumbs'] = [
			['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/home', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')))],
			['text' => $this->language->get('text_account'), 'href' => $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'])],
			['text' => $this->language->get('heading_title'), 'href' => $this->url->link('account/questions', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'])]
		];
		$customer_id = (int)$this->customer->getId();
		$question_id_value = $this->request->get['question_id'] ?? null;
		$question_id = is_scalar($question_id_value) ? max(0, (int)$question_id_value) : 0;
		$page_value = $this->request->get['page'] ?? 1;
		$page = max(1, is_scalar($page_value) ? (int)$page_value : 1);
		$data['page'] = $page;
		$limit = 20;
		$total = (int)$this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "product_question` `q` INNER JOIN `" . DB_PREFIX . "product_question_contact` `c` ON (`c`.`question_id` = `q`.`question_id`) WHERE `q`.`customer_id` = '" . $customer_id . "' AND `c`.`customer_hidden_at` IS NULL")->row['total'];
		$page = min($page, max(1, (int)ceil($total / $limit)));
		$data['page'] = $page;
		$data['conversations'] = $this->db->query("SELECT `q`.`question_id`, `q`.`product_id`, `q`.`author`, `q`.`question`, `q`.`date_added`, `pd`.`name` AS `product_name`, COALESCE((SELECT `m`.`message` FROM `" . DB_PREFIX . "product_question_message` `m` WHERE `m`.`question_id` = `q`.`question_id` ORDER BY `m`.`message_id` DESC LIMIT 1), `q`.`question`) AS `last_message`, COALESCE((SELECT `m`.`date_added` FROM `" . DB_PREFIX . "product_question_message` `m` WHERE `m`.`question_id` = `q`.`question_id` ORDER BY `m`.`message_id` DESC LIMIT 1), `q`.`date_added`) AS `last_activity`, (SELECT COUNT(*) FROM `" . DB_PREFIX . "product_question_message` `m` WHERE `m`.`question_id` = `q`.`question_id` AND `m`.`sender_type` = 'admin' AND `m`.`read_by_customer` = '0') AS `unread` FROM `" . DB_PREFIX . "product_question` `q` INNER JOIN `" . DB_PREFIX . "product_question_contact` `c` ON (`c`.`question_id` = `q`.`question_id`) LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `q`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `q`.`customer_id` = '" . $customer_id . "' AND `c`.`customer_hidden_at` IS NULL ORDER BY `last_activity` DESC LIMIT " . (($page - 1) * $limit) . "," . $limit)->rows;

		if ($data['conversations'] && !$question_id) {
			$question_id = (int)$data['conversations'][0]['question_id'];
		} elseif ($data['conversations'] && !in_array($question_id, array_map('intval', array_column($data['conversations'], 'question_id')), true)) {
			$question_id = (int)$data['conversations'][0]['question_id'];
		}

		$data['selected_conversation'] = [];
		foreach ($data['conversations'] as &$conversation) {
			$id = (int)$conversation['question_id'];
			$conversation['product_url'] = $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . (int)$conversation['product_id']);
			$conversation['select_url'] = $this->url->link('account/questions', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&page=' . $page . '&question_id=' . $id);
			$conversation['delete_url'] = $this->url->link('account/questions.delete', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
			$conversation['reply_url'] = $this->url->link('account/questions.reply', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&question_id=' . $id . '&page=' . $page);
			$conversation['selected'] = $id === $question_id;
			if ($conversation['selected']) {
				$this->db->query("UPDATE `" . DB_PREFIX . "product_question_message` SET `read_by_customer` = '1' WHERE `question_id` = '" . $id . "' AND `sender_type` = 'admin'");
				$conversation['unread'] = 0;
				$conversation['messages'] = $this->db->query("SELECT `sender_type`, `message`, `date_added` FROM `" . DB_PREFIX . "product_question_message` WHERE `question_id` = '" . $id . "' ORDER BY `message_id` ASC")->rows;
				$data['selected_conversation'] = $conversation;
			}
		}
		unset($conversation);

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page' => $page,
			'limit' => $limit,
			'url' => $this->url->link('account/questions', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&page={page}')
		]);

		$data['success'] = $this->session->data['question_chat_success'] ?? '';
		$data['error'] = $this->session->data['question_chat_error'] ?? '';
		unset($this->session->data['question_chat_success'], $this->session->data['question_chat_error']);
		$data['text_intro'] = $this->language->get('text_intro');
		$data['text_empty'] = $this->language->get('text_empty');
		$data['text_product'] = $this->language->get('text_product');
		$data['text_initial_question'] = $this->language->get('text_initial_question');
		$data['text_you'] = $this->language->get('text_you');
		$data['text_shop'] = $this->config->get('config_name');
		$data['text_reply'] = $this->language->get('text_reply');
		$data['text_send'] = $this->language->get('text_send');
		$data['text_inbox'] = $this->language->get('text_inbox');
		$data['text_last_activity'] = $this->language->get('text_last_activity');
		$data['text_unread'] = $this->language->get('text_unread');
		$data['text_history_retention'] = $this->language->get('text_history_retention');
		$data['text_delete'] = $this->language->get('text_delete');
		$data['text_delete_confirm'] = $this->language->get('text_delete_confirm');
		$data['refresh'] = $this->url->link('account/questions', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&page=' . $page . '&question_id=' . $question_id);
		$data['back'] = $this->url->link('account/account', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token']);
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('account/questions', $data));
	}

	public function delete(): void {
		$this->load->language('account/questions');

		if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST' || !$this->load->controller('account/login.validate')) {
			$this->response->redirect($this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
			return;
		}

		$question_id_value = $this->request->post['question_id'] ?? null;
		$question_id = is_scalar($question_id_value) ? max(0, (int)$question_id_value) : 0;
		$customer_id = (int)$this->customer->getId();
		$conversation = $this->db->query("SELECT `c`.`customer_hidden_at` FROM `" . DB_PREFIX . "product_question_contact` `c` INNER JOIN `" . DB_PREFIX . "product_question` `q` ON (`q`.`question_id` = `c`.`question_id`) WHERE `q`.`question_id` = '" . $question_id . "' AND `q`.`customer_id` = '" . $customer_id . "'")->row;

		if ($conversation && !$conversation['customer_hidden_at']) {
			$this->db->query("UPDATE `" . DB_PREFIX . "product_question_contact` SET `customer_hidden_at` = NOW(), `access_token_hash` = NULL WHERE `question_id` = '" . $question_id . "' AND `customer_hidden_at` IS NULL");
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product_question_audit` SET `question_id` = '" . $question_id . "', `actor_type` = 'customer', `actor_id` = '" . $customer_id . "', `action` = 'customer_hide', `date_added` = NOW()");
			$this->session->data['question_chat_success'] = $this->language->get('text_deleted');
		}

		$this->response->redirect($this->url->link('account/questions', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'], true));
	}

	public function reply(): void {
		$this->load->language('account/questions');
		$question_id_value = $this->request->get['question_id'] ?? $this->request->post['question_id'] ?? null;
		$question_id = is_scalar($question_id_value) ? max(0, (int)$question_id_value) : 0;
		$page_value = $this->request->get['page'] ?? $this->request->post['page'] ?? 1;
		$page = max(1, is_scalar($page_value) ? (int)$page_value : 1);
		$message_value = $this->request->post['message'] ?? null;
		$message = is_scalar($message_value) ? trim(strip_tags((string)$message_value)) : '';

		if (!$this->load->controller('account/login.validate')) {
			$this->response->redirect($this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')), true));
			return;
		}

		$question = $this->db->query("SELECT `q`.`question_id` FROM `" . DB_PREFIX . "product_question` `q` INNER JOIN `" . DB_PREFIX . "product_question_contact` `c` ON (`c`.`question_id` = `q`.`question_id`) WHERE `q`.`question_id` = '" . $question_id . "' AND `q`.`customer_id` = '" . (int)$this->customer->getId() . "'")->row;
		if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST' || !$question || oc_strlen($message) < 1 || oc_strlen($message) > 2000) {
			$this->session->data['question_chat_error'] = $this->language->get('error_message');
		} else {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product_question_message` SET `question_id` = '" . $question_id . "', `sender_type` = 'customer', `sender_id` = '" . (int)$this->customer->getId() . "', `message` = '" . $this->db->escape($message) . "', `date_added` = NOW()");

			require_once DIR_SYSTEM . 'library/pandashop/product_question.php';
			$admin_notified = \Opencart\System\Library\PanDaShop\ProductQuestion::sendNotification(
				$this->config,
				$this->log,
				(string)$this->config->get('config_email'),
				$this->language->get('mail_admin_subject'),
				sprintf($this->language->get('mail_admin_reply'), $question_id)
			);
			$this->session->data['question_chat_success'] = $admin_notified ? $this->language->get('text_reply_sent') : $this->language->get('text_reply_saved_notification_failed');
		}

		$this->response->redirect($this->url->link('account/questions', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . $this->session->data['customer_token'] . '&page=' . $page . '&question_id=' . $question_id, true));
	}
}
