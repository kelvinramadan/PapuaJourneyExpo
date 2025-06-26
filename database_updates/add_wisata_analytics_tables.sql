-- Migration: Add analytics tables for tourist destination tracking
-- Date: 2025-06-26
-- Description: Creates tables to track views and statistics for tourist destinations

-- Table to track individual page views
CREATE TABLE IF NOT EXISTS wisata_views (
  id INT AUTO_INCREMENT PRIMARY KEY,
  wisata_id INT NOT NULL,
  user_id INT DEFAULT NULL,
  ip_address VARCHAR(45),
  view_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  session_id VARCHAR(128),
  user_agent VARCHAR(255),
  FOREIGN KEY (wisata_id) REFERENCES wisata(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_wisata_views (wisata_id, view_date),
  INDEX idx_session (session_id),
  INDEX idx_view_date (view_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for aggregated daily statistics
CREATE TABLE IF NOT EXISTS wisata_statistics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  wisata_id INT NOT NULL,
  stat_date DATE NOT NULL,
  view_count INT DEFAULT 0,
  unique_visitors INT DEFAULT 0,
  booking_count INT DEFAULT 0,
  revenue DECIMAL(15,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (wisata_id) REFERENCES wisata(id) ON DELETE CASCADE,
  UNIQUE KEY unique_stat (wisata_id, stat_date),
  INDEX idx_stat_date (stat_date),
  INDEX idx_wisata_stat (wisata_id, stat_date DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial statistics for existing tourist destinations
INSERT INTO wisata_statistics (wisata_id, stat_date, view_count, unique_visitors, booking_count, revenue)
SELECT 
  w.id as wisata_id,
  CURDATE() as stat_date,
  0 as view_count,
  0 as unique_visitors,
  COUNT(DISTINCT CASE WHEN t.payment_status = 'paid' THEN ti.transaksi_id END) as booking_count,
  COALESCE(SUM(CASE WHEN t.payment_status = 'paid' THEN ti.subtotal ELSE 0 END), 0) as revenue
FROM wisata w
LEFT JOIN transaksi_items ti ON w.id = ti.item_id AND ti.item_type = 'wisata'
LEFT JOIN transaksi t ON ti.transaksi_id = t.id
GROUP BY w.id;

-- Create a view for easy access to tourist destination popularity metrics
CREATE OR REPLACE VIEW wisata_popularity AS
SELECT 
  w.id,
  w.judul,
  w.kategori,
  w.harga,
  w.photo,
  COALESCE(ws_today.view_count, 0) as views_today,
  COALESCE(ws_week.total_views, 0) as views_this_week,
  COALESCE(ws_month.total_views, 0) as views_this_month,
  COALESCE(ws_week.total_bookings, 0) as bookings_this_week,
  COALESCE(ws_month.total_bookings, 0) as bookings_this_month,
  COALESCE(ws_month.total_revenue, 0) as revenue_this_month,
  CASE 
    WHEN COALESCE(ws_week.total_views, 0) > 0 
    THEN ROUND((COALESCE(ws_week.total_bookings, 0) / ws_week.total_views) * 100, 2)
    ELSE 0 
  END as conversion_rate_week
FROM wisata w
LEFT JOIN wisata_statistics ws_today ON w.id = ws_today.wisata_id AND ws_today.stat_date = CURDATE()
LEFT JOIN (
  SELECT 
    wisata_id, 
    SUM(view_count) as total_views,
    SUM(booking_count) as total_bookings
  FROM wisata_statistics 
  WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
  GROUP BY wisata_id
) ws_week ON w.id = ws_week.wisata_id
LEFT JOIN (
  SELECT 
    wisata_id, 
    SUM(view_count) as total_views,
    SUM(booking_count) as total_bookings,
    SUM(revenue) as total_revenue
  FROM wisata_statistics 
  WHERE stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
  GROUP BY wisata_id
) ws_month ON w.id = ws_month.wisata_id
ORDER BY views_this_month DESC;

-- Add index to transaksi_items for better performance
ALTER TABLE transaksi_items ADD INDEX idx_item_type_id (item_type, item_id);