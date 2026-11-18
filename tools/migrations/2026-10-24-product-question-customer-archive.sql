ALTER TABLE `oc_product_question_contact`
  ADD COLUMN `customer_hidden_at` datetime DEFAULT NULL AFTER `access_token_hash`,
  ADD KEY `customer_hidden_at` (`customer_hidden_at`);

CREATE TABLE IF NOT EXISTS `oc_product_question_audit` (
  `audit_id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `actor_type` enum('customer','admin') NOT NULL,
  `actor_id` int(11) NOT NULL DEFAULT 0,
  `action` enum('customer_hide','admin_restore') NOT NULL,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `question_date` (`question_id`,`date_added`),
  KEY `actor_date` (`actor_type`,`actor_id`,`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
