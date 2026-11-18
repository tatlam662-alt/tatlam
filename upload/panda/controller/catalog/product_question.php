<?php
namespace Opencart\Admin\Controller\Catalog;

class ProductQuestion extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('catalog/product_question');
		$this->document->setTitle($this->language->get('heading_title'));
		$language_value = $this->request->get['language'] ?? $this->config->get('config_language');
		$language_code = is_string($language_value) ? $language_value : (string)$this->config->get('config_language');

		if (($this->request->server['REQUEST_METHOD'] ?? '') === 'POST') {
			if (!$this->user->hasPermission('modify', 'catalog/product_question')) {
				$this->session->data['error'] = $this->language->get('error_permission');
			} else {
				$question_id_value = $this->request->post['question_id'] ?? null;
				$question_id = is_scalar($question_id_value) ? max(0, (int)$question_id_value) : 0;
				$action_value = $this->request->post['action'] ?? 'reply';
				$action = is_string($action_value) ? $action_value : '';
				$conversation = $this->db->query("SELECT `c`.`email`, `c`.`customer_hidden_at`, `q`.`customer_id` FROM `" . DB_PREFIX . "product_question_contact` `c` INNER JOIN `" . DB_PREFIX . "product_question` `q` ON (`q`.`question_id` = `c`.`question_id`) WHERE `c`.`question_id` = '" . $question_id . "'")->row;

				if ($question_id > 0 && $conversation && $action === 'restore') {
					if ($conversation['customer_hidden_at']) {
						$access_token = bin2hex(random_bytes(32));
						$store_url = $this->config->get('config_ssl') ?: $this->config->get('config_url');
						$conversation_url = rtrim((string)$store_url, '/') . '/index.php?route=extension/pandashop/questions.conversation&language=' . rawurlencode($language_code) . '&question_id=' . $question_id . '&access_token=' . rawurlencode($access_token);
						require_once DIR_SYSTEM . 'library/pandashop/product_question.php';
						$email_sent = \Opencart\System\Library\PanDaShop\ProductQuestion::sendNotification(
							$this->config,
							$this->log,
							$conversation['email'],
							sprintf($this->language->get('mail_reply_subject'), $this->config->get('config_name')),
							sprintf($this->language->get('mail_reply'), $this->config->get('config_name'), $conversation_url)
						);
						$this->db->query("UPDATE `" . DB_PREFIX . "product_question_contact` SET `customer_hidden_at` = NULL, `access_token_hash` = '" . $this->db->escape(hash('sha256', $access_token)) . "' WHERE `question_id` = '" . $question_id . "'");
						$this->db->query("INSERT INTO `" . DB_PREFIX . "product_question_audit` SET `question_id` = '" . $question_id . "', `actor_type` = 'admin', `actor_id` = '" . (int)$this->user->getId() . "', `action` = 'admin_restore', `date_added` = NOW()");
						$this->session->data['success'] = $email_sent ? $this->language->get('text_customer_restored') : $this->language->get('text_customer_restored_email_failed');
						$this->session->data['conversation_link'] = $email_sent ? '' : $conversation_url;
					} else {
						$this->session->data['success'] = $this->language->get('text_customer_restored');
					}
				} elseif ($question_id > 0 && $conversation && $action === 'reply') {
					$message_value = $this->request->post['message'] ?? null;
					$message = is_scalar($message_value) ? trim(strip_tags((string)$message_value)) : '';
					if ($message === '' || oc_strlen($message) > 2000) {
						$this->session->data['error'] = $this->language->get('error_message');
					} else {
						$this->db->query("INSERT INTO `" . DB_PREFIX . "product_question_message` SET `question_id` = '" . $question_id . "', `sender_type` = 'admin', `sender_id` = '" . (int)$this->user->getId() . "', `message` = '" . $this->db->escape($message) . "', `date_added` = NOW()");
						$access_token = bin2hex(random_bytes(32));
						$recipient = $conversation['email'];

						require_once DIR_SYSTEM . 'library/pandashop/product_question.php';
						$store_url = $this->config->get('config_ssl') ?: $this->config->get('config_url');
						$conversation_url = rtrim((string)$store_url, '/') . '/index.php?route=extension/pandashop/questions.conversation&language=' . rawurlencode($language_code) . '&question_id=' . $question_id . '&access_token=' . rawurlencode($access_token);
						$email_sent = \Opencart\System\Library\PanDaShop\ProductQuestion::sendNotification(
							$this->config,
							$this->log,
							$recipient,
							sprintf($this->language->get('mail_reply_subject'), $this->config->get('config_name')),
							sprintf($this->language->get('mail_reply'), $this->config->get('config_name'), $conversation_url)
						);
						$this->db->query("UPDATE `" . DB_PREFIX . "product_question_contact` SET `access_token_hash` = '" . $this->db->escape(hash('sha256', $access_token)) . "' WHERE `question_id` = '" . $question_id . "'");
						$this->session->data['success'] = $email_sent ? $this->language->get('text_success') : $this->language->get('text_success_email_failed');
						$this->session->data['conversation_link'] = $email_sent ? '' : $conversation_url;
					}
				} elseif ($question_id > 0 && !$conversation) {
					$status_value = $this->request->post['status'] ?? 0;
					$answer_value = $this->request->post['answer'] ?? null;
					if (is_scalar($status_value) && is_scalar($answer_value)) {
						$status = (int)$status_value === 1 ? 1 : 0;
						$answer = trim((string)$answer_value);
						$this->db->query("UPDATE `" . DB_PREFIX . "product_question` SET `answer` = '" . $this->db->escape($answer) . "', `status` = '" . $status . "' WHERE `question_id` = '" . $question_id . "'");
						$this->session->data['success'] = $this->language->get('text_legacy_success');
					} else {
						$this->session->data['error'] = $this->language->get('error_message');
					}
				} else {
					$this->session->data['error'] = $this->language->get('error_message');
				}
			}

			$redirect_search_value = $this->request->post['search'] ?? null;
			$redirect_search = is_scalar($redirect_search_value) ? trim((string)$redirect_search_value) : '';
			$this->response->redirect($this->url->link('catalog/product_question', 'user_token=' . $this->session->data['user_token'] . ($redirect_search !== '' ? '&search=' . rawurlencode($redirect_search) : '')));
		}

		$page_value = $this->request->get['page'] ?? 1;
		$page = max(1, is_scalar($page_value) ? (int)$page_value : 1);
		$limit = max(1, (int)$this->config->get('config_pagination_admin'));
		$search_value = $this->request->get['search'] ?? '';
		$search = is_scalar($search_value) ? trim((string)$search_value) : '';
		$where = '';
		if ($search !== '') {
			$search_escaped = $this->db->escape($search);
			$where = ctype_digit($search)
				? " AND (`q`.`question_id` = '" . (int)$search . "' OR `c`.`email` = '" . $search_escaped . "')"
				: " AND `c`.`email` = '" . $search_escaped . "'";
		}
		$question_total = (int)$this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "product_question` `q` LEFT JOIN `" . DB_PREFIX . "product_question_contact` `c` ON (`c`.`question_id` = `q`.`question_id`) WHERE 1 = 1" . $where)->row['total'];
		$page = min($page, max(1, (int)ceil($question_total / $limit)));
		$data['questions'] = $this->db->query("SELECT `q`.*, `pd`.`name` AS `product_name`, `c`.`email`, `c`.`customer_hidden_at`, `c`.`question_id` IS NOT NULL AS `is_conversation` FROM `" . DB_PREFIX . "product_question` `q` LEFT JOIN `" . DB_PREFIX . "product_question_contact` `c` ON (`c`.`question_id` = `q`.`question_id`) LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`q`.`product_id` = `pd`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE 1 = 1" . $where . " ORDER BY `q`.`status` ASC, COALESCE((SELECT MAX(`m`.`date_added`) FROM `" . DB_PREFIX . "product_question_message` `m` WHERE `m`.`question_id` = `q`.`question_id`), `q`.`date_added`) DESC LIMIT " . (($page - 1) * $limit) . "," . $limit)->rows;
		foreach ($data['questions'] as &$question) {
			$question['is_conversation'] = (bool)$question['is_conversation'];
			if ($question['is_conversation']) {
				$question['unread'] = (int)$this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "product_question_message` WHERE `question_id` = '" . (int)$question['question_id'] . "' AND `sender_type` = 'customer' AND `read_by_admin` = '0'")->row['total'];
				$this->db->query("UPDATE `" . DB_PREFIX . "product_question_message` SET `read_by_admin` = '1' WHERE `question_id` = '" . (int)$question['question_id'] . "' AND `sender_type` = 'customer'");
				$question['messages'] = $this->db->query("SELECT `sender_type`, `message`, `date_added` FROM `" . DB_PREFIX . "product_question_message` WHERE `question_id` = '" . (int)$question['question_id'] . "' ORDER BY `date_added` ASC")->rows;
				$question['audit'] = $this->db->query("SELECT `actor_type`, `actor_id`, `action`, `date_added` FROM `" . DB_PREFIX . "product_question_audit` WHERE `question_id` = '" . (int)$question['question_id'] . "' ORDER BY `audit_id` DESC")->rows;
			}
		}
		unset($question);
		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $question_total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('catalog/product_question', 'user_token=' . $this->session->data['user_token'] . '&search=' . rawurlencode($search) . '&page={page}')
		]);
		$data['results'] = sprintf('%d–%d / %d', $question_total ? (($page - 1) * $limit) + 1 : 0, min($page * $limit, $question_total), $question_total);
		$data['search'] = $search;
		$data['search_url'] = $this->url->link('catalog/product_question', 'user_token=' . $this->session->data['user_token']);
		$data['user_token'] = $this->session->data['user_token'];
		$data['can_modify'] = $this->user->hasPermission('modify', 'catalog/product_question');
		$data['success'] = $this->session->data['success'] ?? '';
		$data['error_warning'] = $this->session->data['error'] ?? '';
		$data['conversation_link'] = $this->session->data['conversation_link'] ?? '';
		unset($this->session->data['success'], $this->session->data['error'], $this->session->data['conversation_link']);

		foreach (['heading_title', 'text_empty', 'text_pending', 'text_approved', 'text_answer', 'text_save', 'text_question', 'text_author', 'text_product', 'text_date_added', 'text_status', 'text_conversation', 'text_unread', 'text_customer_message', 'text_admin_message', 'text_reply', 'text_send', 'text_private', 'text_email', 'text_success_email_failed', 'text_conversation_link', 'text_legacy_success', 'text_customer_hidden', 'text_restore', 'text_customer_restored', 'text_search', 'text_search_placeholder', 'text_search_button', 'text_clear_search', 'text_archive_audit', 'text_customer_hide_action', 'text_admin_restore_action', 'error_message'] as $key) {
			$data[$key] = $this->language->get($key);
		}
		$data['save'] = $this->url->link('catalog/product_question', 'user_token=' . $this->session->data['user_token']);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('catalog/product_question', $data));
	}
}
