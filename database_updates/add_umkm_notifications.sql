-- Add UMKM notifications table for order updates
CREATE TABLE IF NOT EXISTS `umkm_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `umkm_id` int(11) NOT NULL,
  `type` enum('new_order','payment_confirmed','payment_rejected') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `transaction_code` varchar(20) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `umkm_id` (`umkm_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add foreign key constraint
ALTER TABLE `umkm_notifications`
  ADD CONSTRAINT `umkm_notifications_ibfk_1` FOREIGN KEY (`umkm_id`) REFERENCES `umkm` (`id`) ON DELETE CASCADE;