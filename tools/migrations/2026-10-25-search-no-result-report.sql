CREATE TABLE IF NOT EXISTS `oc_search_no_result` (
  `search_no_result_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL DEFAULT 0,
  `language_id` int(11) NOT NULL DEFAULT 0,
  `keyword` varchar(100) NOT NULL,
  `normalized_keyword` varchar(100) NOT NULL,
  `search_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL,
  `date_modified` datetime NOT NULL,
  PRIMARY KEY (`search_no_result_id`),
  UNIQUE KEY `store_language_keyword` (`store_id`,`language_id`,`normalized_keyword`),
  KEY `search_count` (`store_id`,`search_count`,`date_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE `oc_user_group`
SET `permission` = JSON_SET(
  `permission`,
  '$.access', JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(`permission`, '$.access')), '$'),
  '$.modify',
  JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(`permission`, '$.modify')), '$')
)
WHERE `user_group_id` = 1
  AND JSON_VALID(`permission`)
  AND (JSON_TYPE(JSON_EXTRACT(`permission`, '$.access')) = 'STRING' OR JSON_TYPE(JSON_EXTRACT(`permission`, '$.modify')) = 'STRING');

UPDATE `oc_user_group`
SET `permission` = JSON_SET(
  `permission`,
  '$.access',
  JSON_ARRAY_APPEND(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(`permission`, '$.access')), '$'), '$', 'report/search_no_result')
)
WHERE `user_group_id` = 1
  AND JSON_VALID(`permission`)
  AND NOT JSON_CONTAINS(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(`permission`, '$.access')), '$'), JSON_QUOTE('report/search_no_result'));
