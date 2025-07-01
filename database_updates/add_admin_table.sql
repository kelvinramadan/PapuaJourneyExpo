-- Migration script to add admin table for database-based authentication
-- Created: 2025-07-01

-- Create admin table
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default admin user
-- Username: admin
-- Password: admin123
-- Note: This hash was generated using password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `admin` (`username`, `password`, `full_name`, `email`) 
VALUES ('admin', '$2y$10$xJMh2Y5K8zQJzP9hVxWKhOU0QzGUzpMlGxD8FfKDPqX6C2cUhLAHi', 'Administrator', 'admin@papuajourney.com');

-- Update admin_payment_logs foreign key if needed (optional)
-- This ensures referential integrity with the new admin table
-- ALTER TABLE `admin_payment_logs` 
-- ADD CONSTRAINT `fk_admin_payment_logs_admin` 
-- FOREIGN KEY (`admin_id`) REFERENCES `admin`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;