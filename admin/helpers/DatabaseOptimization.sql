-- Database Optimization Recommendations for Financial Reports
-- Run these commands to improve query performance

-- 1. Index for transaksi table filtering by date and payment status
CREATE INDEX IF NOT EXISTS idx_transaksi_date_status 
ON transaksi (created_at, payment_status);

-- 2. Composite index for transaksi items joins
CREATE INDEX IF NOT EXISTS idx_transaksi_items_lookup 
ON transaksi_items (transaksi_id, item_type, item_id);

-- 3. Index for UMKM queries
CREATE INDEX IF NOT EXISTS idx_artikel_umkm 
ON artikel (umkm_id, id);

-- 4. Index for improved performance on UMKM revenue calculation
CREATE INDEX IF NOT EXISTS idx_transaksi_items_revenue 
ON transaksi_items (item_id, item_type, subtotal);

-- 5. Index for daily revenue aggregation
CREATE INDEX IF NOT EXISTS idx_transaksi_created_at 
ON transaksi (created_at);

-- 6. Index for payment method analysis
CREATE INDEX IF NOT EXISTS idx_transaksi_payment_method 
ON transaksi (payment_method, payment_status);

-- Performance Analysis Queries
-- Run these to check if indexes are being used:

-- 1. Check index usage for main overview query
EXPLAIN SELECT 
    COUNT(DISTINCT t.id) as total_transactions,
    SUM(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE 0 END) as total_revenue
FROM transaksi t
WHERE DATE(t.created_at) BETWEEN '2024-01-01' AND '2024-12-31';

-- 2. Check UMKM query performance
EXPLAIN SELECT 
    u.business_name as nama_umkm,
    COUNT(DISTINCT t.id) as transaction_count,
    COALESCE(SUM(ti.subtotal), 0) as total_revenue
FROM umkm u
LEFT JOIN artikel a ON u.id = a.umkm_id
LEFT JOIN transaksi_items ti ON ti.item_id = a.id AND ti.item_type = 'artikel'
LEFT JOIN transaksi t ON ti.transaksi_id = t.id 
    AND t.payment_status = 'paid'
GROUP BY u.id, u.business_name
HAVING total_revenue > 0
ORDER BY total_revenue DESC
LIMIT 10;

-- Additional Performance Tips:
-- 1. Consider partitioning transaksi table by date if it grows very large
-- 2. Regular ANALYZE TABLE statements to update statistics
-- 3. Monitor slow query log for queries taking > 1 second