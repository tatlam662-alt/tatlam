<?php
namespace Opencart\Catalog\Model\Catalog;

class Search extends \Opencart\System\Engine\Model {
	public function addNoResultSearch(string $keyword): void {
		$keyword = trim(preg_replace('/\s+/u', ' ', strip_tags($keyword)) ?? '');

		if (function_exists('mb_substr')) {
			$keyword = mb_substr($keyword, 0, 100, 'UTF-8');
		} else {
			$keyword = substr($keyword, 0, 100);
		}

		if (function_exists('mb_strlen') ? mb_strlen($keyword, 'UTF-8') < 2 : strlen($keyword) < 2) {
			return;
		}

		if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $keyword) || preg_match('/(?:\+?\d[\d\s().-]{6,}\d)/', $keyword)) {
			return;
		}

		$normalized = function_exists('mb_strtolower') ? mb_strtolower($keyword, 'UTF-8') : strtolower($keyword);

		$this->db->query("INSERT INTO `" . DB_PREFIX . "search_no_result` SET `store_id` = '" . (int)$this->config->get('config_store_id') . "', `language_id` = '" . (int)$this->config->get('config_language_id') . "', `keyword` = '" . $this->db->escape($keyword) . "', `normalized_keyword` = '" . $this->db->escape($normalized) . "', `search_count` = '1', `date_added` = NOW(), `date_modified` = NOW() ON DUPLICATE KEY UPDATE `search_count` = `search_count` + 1, `date_modified` = NOW()");
	}
}
