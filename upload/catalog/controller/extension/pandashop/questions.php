<?php
namespace Opencart\Catalog\Controller\Extension\Pandashop;

/** Product Q&A and private customer conversations for PanDaShop. */
class Questions extends \Opencart\System\Engine\Controller {
	public function index(int $product_id = 0): string {
		$this->load->language('extension/pandashop/questions');
		$this->load->model('catalog/product');
		$product = $this->model_catalog_product->getProduct($product_id);

		if (!$product) {
			return '';
		}

		$data['product_id'] = $product_id;
		$data['questions'] = $this->db->query("SELECT `author`, `question`, `answer`, `date_added` FROM `" . DB_PREFIX . "product_question` WHERE `product_id` = '" . (int)$product_id . "' AND `status` = '1' AND NOT EXISTS (SELECT 1 FROM `" . DB_PREFIX . "product_question_contact` `c` WHERE `c`.`question_id` = `" . DB_PREFIX . "product_question`.`question_id`) ORDER BY `date_added` DESC LIMIT 10")->rows;
		$data['submit_url'] = $this->url->link('extension/pandashop/questions.submit', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $product_id);
		$data['logged'] = $this->customer->isLogged();
		$data['customer_email'] = $this->customer->getEmail();
		$data['customer_firstname'] = $this->customer->getFirstName();
		$data['inbox_url'] = $this->customer->isLogged()
			? $this->url->link('account/questions', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&customer_token=' . (string)($this->session->data['customer_token'] ?? ''))
			: '';
		$data['login_url'] = $this->url->link('account/login', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&redirect=' . rawurlencode($this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . $product_id)));
		$data['text_heading'] = $this->language->get('text_heading');
		$data['text_intro'] = $this->language->get('text_intro');
		$data['text_name'] = $this->language->get('text_name');
		$data['text_email'] = $this->language->get('text_email');
		$data['text_question'] = $this->language->get('text_question');
		$data['text_submit'] = $this->language->get('text_submit');
		$data['text_empty'] = $this->language->get('text_empty');
		$data['text_answered'] = $this->language->get('text_answered');
		$data['text_login_required'] = $this->language->get('text_login_required');
		$data['text_chat_intro'] = $this->language->get('text_chat_intro');
		$data['text_open_inbox'] = $this->language->get('text_open_inbox');
		$data['error_network'] = $this->language->get('error_network');
		$data['error_email_required'] = $this->language->get('error_email_required');
		$data['error_email_delivery'] = $this->language->get('error_email_delivery');
		$data['text_open_conversation'] = $this->language->get('text_open_conversation');

		return $this->load->view('extension/pandashop/questions', $data);
	}

	public function submit(): void {
		$this->load->language('extension/pandashop/questions');
		$product_id_value = $this->request->get['product_id'] ?? null;
		$product_id = is_scalar($product_id_value) ? max(0, (int)$product_id_value) : 0;
		$author_value = $this->request->post['author'] ?? null;
		$question_value = $this->request->post['question'] ?? null;
		$email_value = $this->request->post['email'] ?? null;
		$author = is_scalar($author_value) ? trim(strip_tags((string)$author_value)) : '';
		$question = is_scalar($question_value) ? trim(strip_tags((string)$question_value)) : '';
		$email = $this->customer->isLogged() ? $this->customer->getEmail() : (is_scalar($email_value) ? trim((string)$email_value) : '');
		$json = [];

		if (($this->request->server['REQUEST_METHOD'] ?? '') !== 'POST' || !$product_id || oc_strlen($author) < 2 || oc_strlen($author) > 64 || oc_strlen($question) < 10 || oc_strlen($question) > 2000) {
			$json['error'] = $this->language->get('error_invalid');
		} elseif (!oc_validate_email($email) || oc_strlen($email) > 96) {
			$json['error'] = $this->language->get('error_email_required');
		} elseif (!empty($this->session->data['nexora_question_sent']) && $this->session->data['nexora_question_sent'] > time() - 30) {
			$json['error'] = $this->language->get('error_rate');
		} else {
			$this->load->model('catalog/product');
			if (!$this->model_catalog_product->getProduct($product_id)) {
				$json['error'] = $this->language->get('error_invalid');
			} else {
				$access_token = bin2hex(random_bytes(32));
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_question` SET `product_id` = '" . $product_id . "', `customer_id` = '" . (int)$this->customer->getId() . "', `author` = '" . $this->db->escape($author) . "', `question` = '" . $this->db->escape($question) . "', `status` = '0', `date_added` = NOW()");
				$question_id = $this->db->getLastId();
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_question_contact` SET `question_id` = '" . (int)$question_id . "', `email` = '" . $this->db->escape($email) . "', `access_token_hash` = '" . $this->db->escape(hash('sha256', $access_token)) . "'");

				$conversation_url = $this->url->link('extension/pandashop/questions.conversation', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&question_id=' . (int)$question_id . '&access_token=' . $access_token, true);

				require_once DIR_SYSTEM . 'library/pandashop/product_question.php';
				$email_sent = \Opencart\System\Library\PanDaShop\ProductQuestion::sendNotification(
					$this->config,
					$this->log,
					$email,
					sprintf($this->language->get('mail_subject'), $this->config->get('config_name')),
					sprintf($this->language->get('mail_initial'), $this->config->get('config_name'), $conversation_url)
				);
				\Opencart\System\Library\PanDaShop\ProductQuestion::sendNotification(
					$this->config,
					$this->log,
					(string)$this->config->get('config_email'),
					$this->language->get('mail_admin_subject'),
					sprintf($this->language->get('mail_admin_reply'), $question_id)
				);

				$this->session->data['nexora_question_sent'] = time();
				$json['success'] = $this->language->get('text_success');
				$json['conversation_url'] = $conversation_url;
				$json['email_sent'] = $email_sent;
			}
		}

		$this->response->addHeader('Content-Type: application/json; charset=utf-8');
		$this->response->setOutput(json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}

	public function conversation(): void {
		$this->load->language('extension/pandashop/questions');
		$question_id_value = $this->request->get['question_id'] ?? null;
		$question_id = is_scalar($question_id_value) ? max(0, (int)$question_id_value) : 0;
		$access_token_value = $this->request->get['access_token'] ?? null;
		$access_token = is_string($access_token_value) ? $access_token_value : '';
		$contact = $this->db->query("SELECT `email`, `access_token_hash` FROM `" . DB_PREFIX . "product_question_contact` WHERE `question_id` = '" . $question_id . "'")->row;

		if (!$contact || !$contact['access_token_hash'] || !hash_equals($contact['access_token_hash'], hash('sha256', $access_token))) {
			$this->response->setOutput($this->language->get('error_conversation_not_found'));
			return;
		}
		$this->response->addHeader('Referrer-Policy: no-referrer');
		$this->response->addHeader('Cache-Control: no-store, private');
		$this->response->addHeader('X-Robots-Tag: noindex, nofollow');

		if (($this->request->server['REQUEST_METHOD'] ?? '') === 'POST') {
			$message_value = $this->request->post['message'] ?? null;
			$message = is_scalar($message_value) ? trim(strip_tags((string)$message_value)) : '';
			if (oc_strlen($message) < 1 || oc_strlen($message) > 2000) {
				$this->session->data['question_chat_error'] = $this->language->get('error_message');
			} else {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_question_message` SET `question_id` = '" . $question_id . "', `sender_type` = 'customer', `message` = '" . $this->db->escape($message) . "', `date_added` = NOW()");
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

			$this->response->redirect($this->url->link('extension/pandashop/questions.conversation', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&question_id=' . $question_id . '&access_token=' . rawurlencode($access_token), true));
			return;
		}

		$question = $this->db->query("SELECT `q`.*, `pd`.`name` AS `product_name` FROM `" . DB_PREFIX . "product_question` `q` LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`q`.`product_id` = `pd`.`product_id` AND `pd`.`language_id` = '" . (int)$this->config->get('config_language_id') . "') WHERE `q`.`question_id` = '" . $question_id . "'")->row;
		if (!$question) {
			$this->response->setOutput($this->language->get('error_conversation_not_found'));
			return;
		}

		$this->document->setTitle(sprintf($this->language->get('text_conversation_title'), $question['product_name']));
		$this->db->query("UPDATE `" . DB_PREFIX . "product_question_message` SET `read_by_customer` = '1' WHERE `question_id` = '" . $question_id . "' AND `sender_type` = 'admin'");
		$data = $this->getConversationData($question, $access_token, '');
		$data['question']['product_url'] = $this->url->link('product/product', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&product_id=' . (int)$question['product_id']);
		$data['error'] = $this->session->data['question_chat_error'] ?? '';
		$data['success'] = $this->session->data['question_chat_success'] ?? '';
		$data['email'] = $contact['email'];
		unset($this->session->data['question_chat_error'], $this->session->data['question_chat_success']);
		$data['text_email'] = $this->language->get('text_email');
		$data['text_error_title'] = $this->language->get('text_error_title');
		$data['text_success_title'] = $this->language->get('text_success_title');
		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/pandashop/questions_conversation', $data));
	}

	private function getConversationData(array $question, string $access_token, string $reply_url): array {
		return [
			'heading_title' => sprintf($this->language->get('text_conversation_title'), $question['product_name']),
			'question' => $question,
			'messages' => $this->db->query("SELECT `sender_type`, `message`, `date_added` FROM `" . DB_PREFIX . "product_question_message` WHERE `question_id` = '" . (int)$question['question_id'] . "' ORDER BY `date_added` ASC")->rows,
			'initial_message' => $question['question'],
			'author' => $question['author'],
			'reply_url' => $reply_url ?: $this->url->link('extension/pandashop/questions.conversation', 'language=' . ($this->request->get['language'] ?? $this->config->get('config_language')) . '&question_id=' . (int)$question['question_id'] . '&access_token=' . rawurlencode($access_token), true),
			'text_product' => $this->language->get('text_product'),
			'text_initial_question' => $this->language->get('text_initial_question'),
			'text_reply' => $this->language->get('text_reply'),
			'text_send' => $this->language->get('text_send'),
			'text_shop' => $this->config->get('config_name'),
			'text_empty_messages' => $this->language->get('text_empty_messages')
		];
	}
}
