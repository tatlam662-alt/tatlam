<?php
namespace Opencart\Admin\Model\Report;

class SearchNoResult extends \Opencart\System\Engine\Model {
	public function getSearches(int $start, int $limit): array {
		$start = max(0, $start);
		$limit = max(1, min(100, $limit));
		$query = $this->db->query("SELECT `keyword`, `language_id`, `search_count`, `date_modified` FROM `" . DB_PREFIX . "search_no_result` WHERE `store_id` = '" . (int)$this->config->get('config_store_id') . "' ORDER BY `search_count` DESC, `date_modified` DESC LIMIT " . $start . "," . $limit);

		return $query->rows;
	}

	public function getTotalSearches(): int {
		$query = $this->db->query("SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "search_no_result` WHERE `store_id` = '" . (int)$this->config->get('config_store_id') . "'");

		return (int)$query->row['total'];
	}
}
