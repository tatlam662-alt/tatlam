<?php
namespace Opencart\System\Library\PanDaShop;

class ProductQuestion {
	public static function sendNotification($config, $log, string $email, string $subject, string $message): bool {
		$mail_engine = (string)$config->get('config_mail_engine');

		if ($mail_engine === 'log') {
			$log->write('Product question notification was not delivered because the configured mail engine only logs messages locally.');

			return false;
		}

		if (!oc_validate_email($email) || !$mail_engine) {
			$log->write('Product question notification was not sent because the recipient or mail configuration is invalid.');

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
			$mail->setSender($config->get('config_name'));
			$mail->setSubject($subject);
			$mail->setText($message);
			$mail->setHtml('<p>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>');

			return $mail->send();
		} catch (\Throwable $exception) {
			$log->write('Product question notification could not be sent.');

			return false;
		}
	}
}
