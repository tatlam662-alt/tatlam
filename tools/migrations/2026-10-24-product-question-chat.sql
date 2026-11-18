CREATE TABLE IF NOT EXISTS `oc_product_question_contact` (
  `question_id` int(11) NOT NULL,
  `email` varchar(96) NOT NULL,
  `access_token_hash` char(64) DEFAULT NULL,
  PRIMARY KEY (`question_id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `oc_product_question_message` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `sender_type` enum('customer','admin') NOT NULL,
  `sender_id` int(11) NOT NULL DEFAULT 0,
  `message` text NOT NULL,
  `read_by_admin` tinyint(1) NOT NULL DEFAULT 0,
  `read_by_customer` tinyint(1) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL,
  PRIMARY KEY (`message_id`),
  KEY `question_date` (`question_id`,`date_added`),
  KEY `admin_unread` (`sender_type`,`read_by_admin`),
  KEY `customer_unread` (`sender_type`,`read_by_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
