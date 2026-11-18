<?php
namespace Opencart\System\Library\PanDaShop;

class StockAlert {
	private static bool $schema_installed = false;

	public static function install($db): void {
		if (self::$schema_installed) {
			return;
		}

		$db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_stock_alert` (
			`subscription_id` INT(11) NOT NULL AUTO_INCREMENT,
			`product_id` INT(11) NOT NULL,
			`customer_id` INT(11) NOT NULL DEFAULT '0',
			`email` VARCHAR(96) NOT NULL,
			`language_code` VARCHAR(5) NOT NULL DEFAULT 'vi-vn',
			`status` VARCHAR(16) NOT NULL DEFAULT 'pending',
			`confirm_token_hash` CHAR(64) DEFAULT NULL,
			`unsubscribe_token_hash` CHAR(64) NOT NULL,
			`request_ip_hash` CHAR(64) DEFAULT NULL,
			`consented_at` DATETIME NOT NULL,
			`date_added` DATETIME NOT NULL,
			`date_modified` DATETIME NOT NULL,
			PRIMARY KEY (`subscription_id`),
			UNIQUE KEY `uq_stock_alert_product_email` (`product_id`, `email`),
			UNIQUE KEY `uq_stock_alert_confirm_token` (`confirm_token_hash`),
			UNIQUE KEY `uq_stock_alert_unsubscribe_token` (`unsubscribe_token_hash`),
			KEY `idx_stock_alert_product_status` (`product_id`, `status`),
			KEY `idx_stock_alert_request_ip` (`request_ip_hash`, `status`, `date_added`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		$db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "product_stock_alert_unsubscribe` (
			`token_id` INT(11) NOT NULL AUTO_INCREMENT,
			`subscription_id` INT(11) NOT NULL,
			`token_hash` CHAR(64) NOT NULL,
			`date_added` DATETIME NOT NULL,
			PRIMARY KEY (`token_id`),
			UNIQUE KEY `uq_stock_alert_unsubscribe_hash` (`token_hash`),
			KEY `idx_stock_alert_unsubscribe_subscription` (`subscription_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
		self::$schema_installed = true;
	}

	public static function subscribe($db, $config, $log, int $product_id, int $customer_id, string $email, string $language_code, string $ip): string {
		self::install($db);
		$db->query("DELETE FROM `" . DB_PREFIX . "product_stock_alert` WHERE `status` = 'pending' AND `date_added` < DATE_SUB(NOW(), INTERVAL 24 HOUR)");

		$email = strtolower(trim($email));
		$language_code = in_array($language_code, ['vi-vn', 'en-gb', 'fr-fr'], true) ? $language_code : 'vi-vn';
		$email_sql = $db->escape($email);
		$existing = $db->query("SELECT `subscription_id`, `status`, `date_added` FROM `" . DB_PREFIX . "product_stock_alert` WHERE `product_id` = '" . $product_id . "' AND `email` = '" . $email_sql . "' LIMIT 1");

		if ($existing->num_rows && in_array($existing->row['status'], ['active', 'sending'], true)) {
			return 'already';
		}

		$request_ip_hash = '';

		if (!$customer_id && $ip !== '') {
			$secret = (string)$config->get('config_encryption');
			$request_ip_hash = hash_hmac('sha256', $ip, $secret);
			$rate_query = $db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "product_stock_alert` WHERE `request_ip_hash` = '" . $db->escape($request_ip_hash) . "' AND `status` = 'pending' AND `date_added` > DATE_SUB(NOW(), INTERVAL 1 HOUR)");

			if ((int)$rate_query->row['total'] >= 5) {
				return 'rate_limited';
			}
		} elseif ($customer_id) {
			$rate_query = $db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "product_stock_alert` WHERE `customer_id` = '" . $customer_id . "' AND `status` IN ('active', 'notified') AND `date_added` > DATE_SUB(NOW(), INTERVAL 1 HOUR)");

			if ((int)$rate_query->row['total'] >= 10) {
				return 'rate_limited';
			}
		}

		$confirm_token = $customer_id ? '' : bin2hex(random_bytes(32));
		$unsubscribe_token = bin2hex(random_bytes(32));
		$confirm_hash = $confirm_token ? "'" . $db->escape(hash('sha256', $confirm_token)) . "'" : 'NULL';
		$request_hash = $request_ip_hash ? "'" . $db->escape($request_ip_hash) . "'" : 'NULL';
		$status = $customer_id ? 'active' : 'pending';

		if ($existing->num_rows) {
			$subscription_id = (int)$existing->row['subscription_id'];
			$db->query("UPDATE `" . DB_PREFIX . "product_stock_alert` SET `customer_id` = '" . $customer_id . "', `language_code` = '" . $db->escape($language_code) . "', `status` = '" . $status . "', `confirm_token_hash` = " . $confirm_hash . ", `unsubscribe_token_hash` = '" . $db->escape(hash('sha256', $unsubscribe_token)) . "', `request_ip_hash` = " . $request_hash . ", `consented_at` = NOW(), `date_added` = NOW(), `date_modified` = NOW() WHERE `subscription_id` = '" . $subscription_id . "'");
		} else {
			$db->query("INSERT INTO `" . DB_PREFIX . "product_stock_alert` SET `product_id` = '" . $product_id . "', `customer_id` = '" . $customer_id . "', `email` = '" . $email_sql . "', `language_code` = '" . $db->escape($language_code) . "', `status` = '" . $status . "', `confirm_token_hash` = " . $confirm_hash . ", `unsubscribe_token_hash` = '" . $db->escape(hash('sha256', $unsubscribe_token)) . "', `request_ip_hash` = " . $request_hash . ", `consented_at` = NOW(), `date_added` = NOW(), `date_modified` = NOW()");
			$subscription_id = (int)$db->getLastId();
		}

		$product_name = self::getProductName($db, $config, $product_id, $language_code);
		$base_url = self::getBaseUrl($config);
		$product_url = $base_url . '?route=product/product&language=' . rawurlencode($language_code) . '&product_id=' . $product_id;
		$unsubscribe_url = $base_url . '?route=product/stock.unsubscribe&language=' . rawurlencode($language_code) . '&token=' . rawurlencode($unsubscribe_token);

		if ($customer_id) {
			if ($language_code === 'en-gb') {
				$subject = 'Back-in-stock alert registration';
				$message = "You have opted in to one availability email for {$product_name}. We will not use this email for marketing.\n\nCancel this alert: {$unsubscribe_url}";
			} elseif ($language_code === 'fr-fr') {
				$subject = 'Inscription à l’alerte de disponibilité';
				$message = "Vous avez accepté de recevoir un seul e-mail lorsque {$product_name} sera de nouveau disponible. Votre adresse ne sera pas utilisée à des fins publicitaires.\n\nAnnuler cette alerte : {$unsubscribe_url}";
			} else {
				$subject = 'Đăng ký báo hàng thành công';
				$message = "Bạn đã đồng ý nhận một email thông báo khi sản phẩm {$product_name} có hàng trở lại. Email này không được dùng cho quảng cáo.\n\nHủy đăng ký: {$unsubscribe_url}";
			}
		} else {
			$confirm_url = $base_url . '?route=product/stock.confirm&language=' . rawurlencode($language_code) . '&token=' . rawurlencode($confirm_token);
			if ($language_code === 'en-gb') {
				$subject = 'Confirm your back-in-stock alert';
				$message = "Confirm your request for one availability email about {$product_name}:\n{$confirm_url}\n\nCancel this request: {$unsubscribe_url}\n\nWe will not use this email for marketing. If you did not make this request, ignore this message.";
			} elseif ($language_code === 'fr-fr') {
				$subject = 'Confirmez votre alerte de disponibilité';
				$message = "Confirmez votre demande de recevoir un seul e-mail lorsque {$product_name} sera disponible :\n{$confirm_url}\n\nAnnuler cette demande : {$unsubscribe_url}\n\nVotre adresse ne sera pas utilisée à des fins publicitaires. Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.";
			} else {
				$subject = 'Xác nhận đăng ký báo hàng';
				$message = "Xác nhận yêu cầu nhận một email thông báo khi sản phẩm {$product_name} có hàng:\n{$confirm_url}\n\nHủy yêu cầu này: {$unsubscribe_url}\n\nEmail này không được dùng cho quảng cáo. Nếu bạn không yêu cầu, hãy bỏ qua thư này.";
			}
		}

		if (!self::sendEmail($config, $log, $email, $subject, $message)) {
			$db->query("DELETE FROM `" . DB_PREFIX . "product_stock_alert_unsubscribe` WHERE `subscription_id` = '" . $subscription_id . "'");
			$db->query("DELETE FROM `" . DB_PREFIX . "product_stock_alert` WHERE `subscription_id` = '" . $subscription_id . "'");

			return 'mail_failed';
		}

		return $customer_id ? 'active' : 'pending';
	}

	public static function confirm($db, $config, $log, string $token): int {
		if (strlen($token) !== 64 || !ctype_xdigit($token)) {
			return 0;
		}

		self::install($db);
		$db->query("DELETE FROM `" . DB_PREFIX . "product_stock_alert` WHERE `status` = 'pending' AND `date_added` < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
		$token_hash = $db->escape(hash('sha256', $token));
		$query = $db->query("SELECT `subscription_id`, `product_id` FROM `" . DB_PREFIX . "product_stock_alert` WHERE `confirm_token_hash` = '" . $token_hash . "' AND `status` = 'pending' AND `date_added` > DATE_SUB(NOW(), INTERVAL 24 HOUR) LIMIT 1");

		if (!$query->num_rows) {
			return 0;
		}

		$subscription_id = (int)$query->row['subscription_id'];
		$product_id = (int)$query->row['product_id'];
		$db->query("UPDATE `" . DB_PREFIX . "product_stock_alert` SET `status` = 'active', `confirm_token_hash` = NULL, `request_ip_hash` = NULL, `date_modified` = NOW() WHERE `subscription_id` = '" . $subscription_id . "' AND `status` = 'pending'");

		if ($db->countAffected()) {
			self::notifyRestocked($db, $config, $log, $product_id);

			return $product_id;
		}

		return 0;
	}

	public static function unsubscribe($db, string $token): int {
		if (strlen($token) !== 64 || !ctype_xdigit($token)) {
			return 0;
		}

		self::install($db);
		$token_hash = $db->escape(hash('sha256', $token));
		$query = $db->query("SELECT `subscription_id`, `product_id` FROM `" . DB_PREFIX . "product_stock_alert` WHERE `unsubscribe_token_hash` = '" . $token_hash . "' LIMIT 1");

		if (!$query->num_rows) {
			$query = $db->query("SELECT `s`.`subscription_id`, `s`.`product_id` FROM `" . DB_PREFIX . "product_stock_alert_unsubscribe` `u` INNER JOIN `" . DB_PREFIX . "product_stock_alert` `s` ON (`s`.`subscription_id` = `u`.`subscription_id`) WHERE `u`.`token_hash` = '" . $token_hash . "' LIMIT 1");
		}

		if (!$query->num_rows) {
			return 0;
		}

		$subscription_id = (int)$query->row['subscription_id'];
		$product_id = (int)$query->row['product_id'];
		$db->query("DELETE FROM `" . DB_PREFIX . "product_stock_alert_unsubscribe` WHERE `subscription_id` = '" . $subscription_id . "'");
		$db->query("DELETE FROM `" . DB_PREFIX . "product_stock_alert` WHERE `subscription_id` = '" . $subscription_id . "'");

		return $product_id;
	}

	public static function notifyRestocked($db, $config, $log, int $product_id): void {
		try {
			self::processRestockNotifications($db, $config, $log, $product_id);
		} catch (\Throwable $exception) {
			$log->write('Stock alert dispatch failed for product ID ' . $product_id . '; the stock update was kept. Check the database and mail logs.');
		}
	}

	private static function processRestockNotifications($db, $config, $log, int $product_id): void {
		self::install($db);
		$db->query("UPDATE `" . DB_PREFIX . "product_stock_alert` SET `status` = 'active', `date_modified` = NOW() WHERE `product_id` = '" . $product_id . "' AND `status` = 'sending' AND `date_modified` < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");

		$subscriptions = $db->query("SELECT `s`.*, COALESCE(`pd`.`name`, `pdd`.`name`) AS `product_name` FROM `" . DB_PREFIX . "product_stock_alert` `s` INNER JOIN `" . DB_PREFIX . "product` `p` ON (`p`.`product_id` = `s`.`product_id`) LEFT JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`code` = `s`.`language_code`) LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = `l`.`language_id`) LEFT JOIN `" . DB_PREFIX . "product_description` `pdd` ON (`pdd`.`product_id` = `p`.`product_id` AND `pdd`.`language_id` = '" . (int)$config->get('config_language_id') . "') WHERE `s`.`product_id` = '" . $product_id . "' AND `s`.`status` = 'active' AND `p`.`quantity` > '0' AND `p`.`status` = '1' AND `p`.`date_available` <= NOW()");

		foreach ($subscriptions->rows as $subscription) {
			$subscription_id = (int)$subscription['subscription_id'];
			$db->query("UPDATE `" . DB_PREFIX . "product_stock_alert` SET `status` = 'sending', `date_modified` = NOW() WHERE `subscription_id` = '" . $subscription_id . "' AND `status` = 'active'");

			if (!$db->countAffected()) {
				continue;
			}

			$language_code = in_array($subscription['language_code'], ['vi-vn', 'en-gb', 'fr-fr'], true) ? $subscription['language_code'] : 'vi-vn';
			$base_url = self::getBaseUrl($config);
			$product_url = $base_url . '?route=product/product&language=' . rawurlencode($language_code) . '&product_id=' . $product_id;
			$unsubscribe_token = bin2hex(random_bytes(32));
			$unsubscribe_hash = $db->escape(hash('sha256', $unsubscribe_token));
			$db->query("INSERT INTO `" . DB_PREFIX . "product_stock_alert_unsubscribe` SET `subscription_id` = '" . $subscription_id . "', `token_hash` = '" . $unsubscribe_hash . "', `date_added` = NOW()");
			$unsubscribe_url = $base_url . '?route=product/stock.unsubscribe&language=' . rawurlencode($language_code) . '&token=' . rawurlencode($unsubscribe_token);
			$product_name = html_entity_decode((string)$subscription['product_name'], ENT_QUOTES, 'UTF-8');

			if ($language_code === 'en-gb') {
				$subject = 'Your product is back in stock';
				$message = "{$product_name} is currently available. View the product: {$product_url}\n\nCancel this alert: {$unsubscribe_url}";
			} elseif ($language_code === 'fr-fr') {
				$subject = 'Votre produit est de nouveau disponible';
				$message = "{$product_name} est actuellement disponible. Voir le produit : {$product_url}\n\nAnnuler cette alerte : {$unsubscribe_url}";
			} else {
				$subject = 'Sản phẩm đã có hàng trở lại';
				$message = "Sản phẩm {$product_name} hiện đã có hàng. Xem sản phẩm: {$product_url}\n\nHủy đăng ký: {$unsubscribe_url}";
			}

			$sent = self::sendEmail($config, $log, (string)$subscription['email'], $subject, $message);
			if (!$sent) {
				$db->query("DELETE FROM `" . DB_PREFIX . "product_stock_alert_unsubscribe` WHERE `token_hash` = '" . $unsubscribe_hash . "'");
			}
			$status = $sent ? 'notified' : 'active';
			$db->query("UPDATE `" . DB_PREFIX . "product_stock_alert` SET `status` = '" . $status . "', `date_modified` = NOW() WHERE `subscription_id` = '" . $subscription_id . "' AND `status` = 'sending'");
		}
	}

	private static function getProductName($db, $config, int $product_id, string $language_code): string {
		$query = $db->query("SELECT COALESCE(`pd`.`name`, `pdd`.`name`) AS `name` FROM `" . DB_PREFIX . "product` `p` LEFT JOIN `" . DB_PREFIX . "language` `l` ON (`l`.`code` = '" . $db->escape($language_code) . "') LEFT JOIN `" . DB_PREFIX . "product_description` `pd` ON (`pd`.`product_id` = `p`.`product_id` AND `pd`.`language_id` = `l`.`language_id`) LEFT JOIN `" . DB_PREFIX . "product_description` `pdd` ON (`pdd`.`product_id` = `p`.`product_id` AND `pdd`.`language_id` = '" . (int)$config->get('config_language_id') . "') WHERE `p`.`product_id` = '" . $product_id . "'");

		return html_entity_decode((string)($query->row['name'] ?? ''), ENT_QUOTES, 'UTF-8');
	}

	private static function getBaseUrl($config): string {
		$base = (string)($config->get('config_ssl') ?: $config->get('config_url'));

		return rtrim($base, '/') . '/index.php';
	}

	private static function sendEmail($config, $log, string $email, string $subject, string $message): bool {
		$mail_engine = (string)$config->get('config_mail_engine');

		if ($mail_engine === 'log') {
			$log->write('Stock alert email was not delivered because the configured mail engine only logs messages locally.');

			return false;
		}

		if (!oc_validate_email($email) || !$mail_engine || !oc_validate_email((string)$config->get('config_email'))) {
			$log->write('Stock alert email was not sent because the recipient or mail configuration is invalid.');

			return false;
		}

		try {
			$mail_option = [
				'parameter'     => $config->get('config_mail_parameter'),
				'smtp_hostname' => $config->get('config_mail_smtp_hostname'),
				'smtp_username' => $config->get('config_mail_smtp_username'),
				'smtp_password' => html_entity_decode($config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8'),
				'smtp_port'     => $config->get('config_mail_smtp_port'),
				'smtp_timeout'  => $config->get('config_mail_smtp_timeout')
			];

			$mail = new \Opencart\System\Library\Mail($mail_engine, $mail_option);
			$mail->setTo($email);
			$mail->setFrom($config->get('config_email'));
			$mail->setSender(html_entity_decode((string)$config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject($subject);
			$mail->setText($message);
			$mail->setHtml('<p>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>');

			return (bool)$mail->send();
		} catch (\Throwable $exception) {
			$log->write('Stock alert email could not be sent. Review the configured mail transport.');

			return false;
		}
	}
}
