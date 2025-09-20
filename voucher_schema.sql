-- Create vouchers table
CREATE TABLE IF NOT EXISTS `vouchers` (
  `voucher_id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `voucher_type` enum('petty_cash','payment') NOT NULL,
  `pv_no` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `activity` varchar(255) NOT NULL,
  `payee_name` varchar(100) NOT NULL,
  `budget_code` varchar(50) NOT NULL,
  `particulars` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `amount_words` text DEFAULT NULL,
  `prepared_by` int(11) NOT NULL,
  `finance_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `finance_remark` text DEFAULT NULL,
  `finance_approved_at` timestamp NULL DEFAULT NULL,
  `ed_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `ed_remark` text DEFAULT NULL,
  `ed_approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`voucher_id`),
  KEY `request_id` (`request_id`),
  KEY `prepared_by` (`prepared_by`),
  CONSTRAINT `vouchers_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `requests` (`request_id`),
  CONSTRAINT `vouchers_ibfk_2` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create voucher_messages table for communication between Finance and ED
CREATE TABLE IF NOT EXISTS `voucher_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `voucher_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `voucher_id` (`voucher_id`),
  KEY `sender_id` (`sender_id`),
  KEY `recipient_id` (`recipient_id`),
  CONSTRAINT `voucher_messages_ibfk_1` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`voucher_id`),
  CONSTRAINT `voucher_messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `voucher_messages_ibfk_3` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;