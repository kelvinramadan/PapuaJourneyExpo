-- Add Review and Rating System Tables
-- Date: 2025-06-30
-- Description: This migration adds support for user reviews and ratings for wisata, penginapan, and artikel after paid transactions

-- Table: reviews
-- Main table for storing user reviews and ratings
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `item_type` enum('wisata','penginapan','artikel') NOT NULL,
  `item_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `review_text` text NOT NULL,
  `is_verified` tinyint(1) DEFAULT 1 COMMENT 'Auto-verified for paid transactions',
  `is_visible` tinyint(1) DEFAULT 1 COMMENT 'Admin can hide inappropriate reviews',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_item` (`item_type`, `item_id`),
  KEY `idx_transaksi` (`transaksi_id`),
  KEY `idx_rating` (`rating`),
  KEY `idx_created` (`created_at`),
  UNIQUE KEY `unique_user_transaksi_item` (`user_id`, `transaksi_id`, `item_type`, `item_id`),
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_transaksi` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: review_media
-- Stores images and videos associated with reviews
CREATE TABLE `review_media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL COMMENT 'File size in bytes',
  `duration` int(11) DEFAULT NULL COMMENT 'Video duration in seconds (max 10)',
  `upload_order` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Order of upload (1-5)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_review_id` (`review_id`),
  CONSTRAINT `fk_review_media` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: review_helpfulness
-- Tracks helpful/not helpful votes on reviews
CREATE TABLE `review_helpfulness` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_helpful` tinyint(1) NOT NULL COMMENT '1 for helpful, 0 for not helpful',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`review_id`, `user_id`),
  KEY `idx_review_id` (`review_id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `fk_helpfulness_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_helpfulness_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: review_summary_cache
-- Caches aggregated review data for performance
CREATE TABLE `review_summary_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_type` enum('wisata','penginapan','artikel') NOT NULL,
  `item_id` int(11) NOT NULL,
  `total_reviews` int(11) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `rating_1_count` int(11) DEFAULT 0,
  `rating_2_count` int(11) DEFAULT 0,
  `rating_3_count` int(11) DEFAULT 0,
  `rating_4_count` int(11) DEFAULT 0,
  `rating_5_count` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_item` (`item_type`, `item_id`),
  KEY `idx_item` (`item_type`, `item_id`),
  KEY `idx_average_rating` (`average_rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create directory for review media uploads
-- Note: Execute this in PHP or manually create the directory
-- mkdir -p uploads/review_media/images
-- mkdir -p uploads/review_media/videos

-- Indexes for performance
CREATE INDEX idx_reviews_visible_rating ON reviews(is_visible, rating, created_at DESC);
CREATE INDEX idx_reviews_item_visible ON reviews(item_type, item_id, is_visible, created_at DESC);

-- Sample trigger to update review cache (optional, can be done in PHP)
DELIMITER $$
CREATE TRIGGER update_review_cache_insert
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    IF NEW.is_visible = 1 THEN
        INSERT INTO review_summary_cache (item_type, item_id, total_reviews, average_rating,
            rating_1_count, rating_2_count, rating_3_count, rating_4_count, rating_5_count)
        VALUES (NEW.item_type, NEW.item_id, 1, NEW.rating,
            IF(NEW.rating = 1, 1, 0),
            IF(NEW.rating = 2, 1, 0),
            IF(NEW.rating = 3, 1, 0),
            IF(NEW.rating = 4, 1, 0),
            IF(NEW.rating = 5, 1, 0))
        ON DUPLICATE KEY UPDATE
            total_reviews = total_reviews + 1,
            average_rating = (average_rating * (total_reviews - 1) + NEW.rating) / total_reviews,
            rating_1_count = rating_1_count + IF(NEW.rating = 1, 1, 0),
            rating_2_count = rating_2_count + IF(NEW.rating = 2, 1, 0),
            rating_3_count = rating_3_count + IF(NEW.rating = 3, 1, 0),
            rating_4_count = rating_4_count + IF(NEW.rating = 4, 1, 0),
            rating_5_count = rating_5_count + IF(NEW.rating = 5, 1, 0);
    END IF;
END$$

CREATE TRIGGER update_review_cache_update
AFTER UPDATE ON reviews
FOR EACH ROW
BEGIN
    -- If visibility changed or rating changed
    IF OLD.is_visible != NEW.is_visible OR OLD.rating != NEW.rating THEN
        -- Recalculate from scratch (simpler than tracking all changes)
        INSERT INTO review_summary_cache (item_type, item_id, total_reviews, average_rating,
            rating_1_count, rating_2_count, rating_3_count, rating_4_count, rating_5_count)
        SELECT 
            NEW.item_type,
            NEW.item_id,
            COUNT(*),
            AVG(rating),
            SUM(IF(rating = 1, 1, 0)),
            SUM(IF(rating = 2, 1, 0)),
            SUM(IF(rating = 3, 1, 0)),
            SUM(IF(rating = 4, 1, 0)),
            SUM(IF(rating = 5, 1, 0))
        FROM reviews
        WHERE item_type = NEW.item_type 
            AND item_id = NEW.item_id 
            AND is_visible = 1
        ON DUPLICATE KEY UPDATE
            total_reviews = VALUES(total_reviews),
            average_rating = VALUES(average_rating),
            rating_1_count = VALUES(rating_1_count),
            rating_2_count = VALUES(rating_2_count),
            rating_3_count = VALUES(rating_3_count),
            rating_4_count = VALUES(rating_4_count),
            rating_5_count = VALUES(rating_5_count);
    END IF;
END$$

CREATE TRIGGER update_review_cache_delete
AFTER DELETE ON reviews
FOR EACH ROW
BEGIN
    IF OLD.is_visible = 1 THEN
        -- Recalculate from scratch
        INSERT INTO review_summary_cache (item_type, item_id, total_reviews, average_rating,
            rating_1_count, rating_2_count, rating_3_count, rating_4_count, rating_5_count)
        SELECT 
            OLD.item_type,
            OLD.item_id,
            COUNT(*),
            IFNULL(AVG(rating), 0),
            SUM(IF(rating = 1, 1, 0)),
            SUM(IF(rating = 2, 1, 0)),
            SUM(IF(rating = 3, 1, 0)),
            SUM(IF(rating = 4, 1, 0)),
            SUM(IF(rating = 5, 1, 0))
        FROM reviews
        WHERE item_type = OLD.item_type 
            AND item_id = OLD.item_id 
            AND is_visible = 1
        ON DUPLICATE KEY UPDATE
            total_reviews = VALUES(total_reviews),
            average_rating = VALUES(average_rating),
            rating_1_count = VALUES(rating_1_count),
            rating_2_count = VALUES(rating_2_count),
            rating_3_count = VALUES(rating_3_count),
            rating_4_count = VALUES(rating_4_count),
            rating_5_count = VALUES(rating_5_count);
        
        -- Delete cache entry if no reviews left
        DELETE FROM review_summary_cache 
        WHERE item_type = OLD.item_type 
            AND item_id = OLD.item_id 
            AND total_reviews = 0;
    END IF;
END$$
DELIMITER ;

-- Grant necessary permissions (adjust as needed)
-- GRANT SELECT, INSERT, UPDATE ON omaki_db.reviews TO 'your_app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON omaki_db.review_media TO 'your_app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON omaki_db.review_helpfulness TO 'your_app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON omaki_db.review_summary_cache TO 'your_app_user'@'localhost';