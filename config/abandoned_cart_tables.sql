-- Abandoned Cart Tracking Tables

-- Table to track cart abandonment events
CREATE TABLE `abandoned_carts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `cart_items_snapshot` JSON NOT NULL COMMENT 'Snapshot of cart items at abandonment',
  `total_value` decimal(15,2) NOT NULL,
  `item_count` int(11) NOT NULL,
  `abandonment_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_start_time` timestamp NULL,
  `session_duration_minutes` int(11) DEFAULT NULL,
  `page_before_abandonment` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_recovered` tinyint(1) DEFAULT 0,
  `recovered_at` timestamp NULL,
  `recovery_method` enum('direct_return','email_reminder','push_notification','other') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_abandonment_timestamp` (`abandonment_timestamp`),
  KEY `idx_is_recovered` (`is_recovered`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table to track abandonment reasons
CREATE TABLE `cart_abandonment_reasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `abandoned_cart_id` int(11) NOT NULL,
  `reason_code` enum('price_too_high','shipping_cost','not_sure','payment_issues','found_better_deal','changed_mind','technical_issues','other') NOT NULL,
  `reason_text` text DEFAULT NULL COMMENT 'Custom reason if other is selected',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_abandoned_cart_id` (`abandoned_cart_id`),
  KEY `idx_reason_code` (`reason_code`),
  FOREIGN KEY (`abandoned_cart_id`) REFERENCES `abandoned_carts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table to track recovery attempts and their effectiveness
CREATE TABLE `cart_recovery_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `abandoned_cart_id` int(11) NOT NULL,
  `attempt_type` enum('email_reminder','push_notification','sms','in_app_notification') NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `opened_at` timestamp NULL,
  `clicked_at` timestamp NULL,
  `converted_at` timestamp NULL,
  `template_used` varchar(100) DEFAULT NULL,
  `subject_line` varchar(255) DEFAULT NULL COMMENT 'For A/B testing email subjects',
  PRIMARY KEY (`id`),
  KEY `idx_abandoned_cart_id` (`abandoned_cart_id`),
  KEY `idx_attempt_type` (`attempt_type`),
  KEY `idx_sent_at` (`sent_at`),
  FOREIGN KEY (`abandoned_cart_id`) REFERENCES `abandoned_carts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table to track user sessions for better abandonment detection
CREATE TABLE `user_cart_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `first_cart_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_cart_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `pages_visited` JSON DEFAULT NULL COMMENT 'Array of pages visited during session',
  `cart_actions` JSON DEFAULT NULL COMMENT 'Array of cart actions (add, remove, update)',
  `is_active` tinyint(1) DEFAULT 1,
  `checkout_started` tinyint(1) DEFAULT 0,
  `checkout_completed` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_session` (`user_id`, `session_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_session_id` (`session_id`),
  KEY `idx_last_activity` (`last_cart_activity`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes for performance optimization
CREATE INDEX idx_abandoned_carts_timestamp_value ON abandoned_carts(abandonment_timestamp, total_value);
CREATE INDEX idx_abandoned_carts_user_date ON abandoned_carts(user_id, abandonment_timestamp);
CREATE INDEX idx_recovery_attempts_success ON cart_recovery_attempts(abandoned_cart_id, converted_at);