<?php
namespace Opencart\System\Library\Mail;

/** Local-only mail transport that records messages for XAMPP testing. */
class Log {
	protected array $option = [];

	public function __construct(array $option = []) {
		$this->option = $option;
	}

	public function send(): bool {
		$record = [
			'date' => date('c'),
			'to' => $this->option['to'] ?? '',
			'from' => $this->option['from'] ?? '',
			'sender' => $this->option['sender'] ?? '',
			'subject' => $this->option['subject'] ?? '',
			'text' => $this->option['text'] ?? '',
			'html' => $this->option['html'] ?? ''
		];

		$path = DIR_LOGS . 'mail.log';
		$handle = fopen($path, 'ab');

		if (!$handle) {
			throw new \RuntimeException('Unable to open local mail log.');
		}

		try {
			if (!flock($handle, LOCK_EX)) {
				throw new \RuntimeException('Unable to lock local mail log.');
			}

			fwrite($handle, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL);
			flock($handle, LOCK_UN);
		} finally {
			fclose($handle);
		}

		return true;
	}
}
