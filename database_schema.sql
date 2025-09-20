-- Drop tables if they exist to prevent errors on re-creation
DROP TABLE IF EXISTS `requests`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `request_statuses`;

-- Create the roles table with a unique index
CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create the users table with foreign key constraint to roles
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create the request_statuses table
CREATE TABLE `request_statuses` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `status_name` varchar(50) NOT NULL,
  PRIMARY KEY (`status_id`),
  UNIQUE KEY `status_name` (`status_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create the requests table with a multi-step approval workflow
CREATE TABLE `requests` (
  `request_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `request_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status_id` int(11) NOT NULL DEFAULT 1, -- Default to 'Pending'
  
  -- Workflow status fields
  `hod_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `hod_remark` text DEFAULT NULL,
  `hod_approved_at` timestamp NULL DEFAULT NULL,
  
  `hrm_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `hrm_remark` text DEFAULT NULL,
  `hrm_approved_at` timestamp NULL DEFAULT NULL,
  
  `auditor_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `auditor_remark` text DEFAULT NULL,
  `auditor_approved_at` timestamp NULL DEFAULT NULL,
  
  `finance_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `finance_remark` text DEFAULT NULL,
  `finance_approved_at` timestamp NULL DEFAULT NULL,
  
  `ed_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `ed_remark` text DEFAULT NULL,
  `ed_approved_at` timestamp NULL DEFAULT NULL,
  
  PRIMARY KEY (`request_id`),
  KEY `user_id` (`user_id`),
  KEY `status_id` (`status_id`),
  CONSTRAINT `requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `requests_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `request_statuses` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert initial roles
INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'Employee'),
(2, 'HRM'),
(3, 'HOD'),
(4, 'ED'),
(5, 'Finance'),
(6, 'Internal Auditor'),
(7, 'Admin');

-- Insert initial request statuses
INSERT INTO `request_statuses` (`status_id`, `status_name`) VALUES
(1, 'Pending'),
(2, 'Approved'),
(3, 'Rejected');

-- Insert a default user (Admin) with a hashed password 'admin123'
-- To create more users, you should use the new_user.php script or a user management interface.
-- Password: admin123
INSERT INTO `users` (`fullname`, `username`, `password`, `role_id`, `department`) VALUES
('Admin User', 'admin', '$2y$10$tWbI45zH61Tq6oGqg.R1u.hJ.E8y2uX/T4o1t7/R6.zH.i/V9t', 7, 'IT');
