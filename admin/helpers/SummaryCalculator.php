<?php
// admin/helpers/SummaryCalculator.php
require_once __DIR__ . '/../../config/database.php';

class SummaryCalculator {
    private $db;
    
    public function __construct() {
        $this->db = getDbConnection();
    }
    
    public function getComprehensiveSummary() {
        return [
            'trend_analysis' => $this->getTrendAnalysis(),
            'performance_highlights' => $this->getPerformanceHighlights(),
            'user_engagement' => $this->getUserEngagementMetrics(),
            'revenue_insights' => $this->getRevenueInsights(),
            'alert_indicators' => $this->getAlertIndicators()
        ];
    }
    
    private function getTrendAnalysis() {
        // Compare current week vs previous week
        $current_week_views = $this->getViewsForPeriod('WEEK', 0);
        $previous_week_views = $this->getViewsForPeriod('WEEK', 1);
        
        $current_month_transactions = $this->getTransactionsForPeriod('MONTH', 0);
        $previous_month_transactions = $this->getTransactionsForPeriod('MONTH', 1);
        
        $views_change = $this->calculatePercentageChange(
            $previous_week_views['total'], 
            $current_week_views['total']
        );
        
        $transaction_change = $this->calculatePercentageChange(
            $previous_month_transactions['count'], 
            $current_month_transactions['count']
        );
        
        return [
            'views' => [
                'current_week' => $current_week_views,
                'previous_week' => $previous_week_views,
                'change_percentage' => $views_change,
                'trend' => $views_change > 0 ? 'up' : ($views_change < 0 ? 'down' : 'stable')
            ],
            'transactions' => [
                'current_month' => $current_month_transactions,
                'previous_month' => $previous_month_transactions,
                'change_percentage' => $transaction_change,
                'trend' => $transaction_change > 0 ? 'up' : ($transaction_change < 0 ? 'down' : 'stable')
            ]
        ];
    }
    
    private function getPerformanceHighlights() {
        // Best performing destinations (last 30 days)
        $query = "
            SELECT w.id, w.judul, w.harga, w.alamat as lokasi,
                   COUNT(wv.id) as views,
                   COUNT(DISTINCT wv.ip_address) as unique_visitors
            FROM wisata w
            LEFT JOIN wisata_views wv ON w.id = wv.wisata_id 
            WHERE wv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY w.id, w.judul, w.harga, w.alamat
            ORDER BY views DESC
            LIMIT 3
        ";
        $result = $this->db->query($query);
        $best_destinations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Worst performing destinations
        $query = "
            SELECT w.id, w.judul, w.harga, w.alamat as lokasi,
                   COALESCE(COUNT(wv.id), 0) as views
            FROM wisata w
            LEFT JOIN wisata_views wv ON w.id = wv.wisata_id 
                AND wv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY w.id, w.judul, w.harga, w.alamat
            ORDER BY views ASC
            LIMIT 3
        ";
        $result = $this->db->query($query);
        $worst_destinations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Best performing accommodations
        $query = "
            SELECT p.id, p.judul, p.harga as harga_per_malam, p.lokasi,
                   COUNT(pv.id) as views,
                   COUNT(DISTINCT pv.ip_address) as unique_visitors
            FROM penginapan p
            LEFT JOIN penginapan_views pv ON p.id = pv.penginapan_id 
            WHERE pv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY p.id, p.judul, p.harga, p.lokasi
            ORDER BY views DESC
            LIMIT 3
        ";
        $result = $this->db->query($query);
        $best_accommodations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        return [
            'best_destinations' => $best_destinations,
            'worst_destinations' => $worst_destinations,
            'best_accommodations' => $best_accommodations
        ];
    }
    
    private function getUserEngagementMetrics() {
        // Active users (logged activity in last 7 days)
        $query = "
            SELECT COUNT(DISTINCT user_id) as active_users
            FROM (
                SELECT user_id FROM wisata_views WHERE view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND user_id IS NOT NULL
                UNION
                SELECT user_id FROM penginapan_views WHERE view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND user_id IS NOT NULL
            ) as combined_activity
        ";
        $result = $this->db->query($query);
        $active_users = $result ? $result->fetch_assoc()['active_users'] : 0;
        
        // Peak viewing hours
        $query = "
            SELECT HOUR(view_date) as hour, COUNT(*) as views
            FROM (
                SELECT view_date FROM wisata_views WHERE view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                UNION ALL
                SELECT view_date FROM penginapan_views WHERE view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ) as all_views
            GROUP BY HOUR(view_date)
            ORDER BY views DESC
            LIMIT 3
        ";
        $result = $this->db->query($query);
        $peak_hours = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Booking patterns (from transactions)
        $query = "
            SELECT 
                DAYNAME(created_at) as day_name,
                COUNT(*) as bookings
            FROM transaksi 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DAYOFWEEK(created_at), DAYNAME(created_at)
            ORDER BY bookings DESC
            LIMIT 3
        ";
        $result = $this->db->query($query);
        $booking_patterns = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        return [
            'active_users' => $active_users,
            'peak_hours' => $peak_hours,
            'booking_patterns' => $booking_patterns
        ];
    }
    
    private function getRevenueInsights() {
        // Top revenue generators (last 30 days)
        $query = "
            SELECT 
                ti.item_type,
                ti.item_id,
                SUM(ti.jumlah * ti.harga) as total_revenue,
                COUNT(DISTINCT ti.transaksi_id) as transactions
            FROM transaksi_items ti
            JOIN transaksi t ON ti.transaksi_id = t.id
            WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND t.status IN ('completed', 'processing')
            GROUP BY ti.item_type, ti.item_id
            ORDER BY total_revenue DESC
            LIMIT 5
        ";
        $result = $this->db->query($query);
        $top_revenue = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        
        // Revenue growth
        $current_month_revenue = $this->getRevenueForPeriod('MONTH', 0);
        $previous_month_revenue = $this->getRevenueForPeriod('MONTH', 1);
        
        $revenue_change = $this->calculatePercentageChange(
            $previous_month_revenue, 
            $current_month_revenue
        );
        
        return [
            'top_revenue_items' => $top_revenue,
            'current_month_revenue' => $current_month_revenue,
            'previous_month_revenue' => $previous_month_revenue,
            'revenue_growth' => $revenue_change
        ];
    }
    
    private function getAlertIndicators() {
        $alerts = [];
        
        // UMKM pending approval
        $query = "SELECT COUNT(*) as count FROM umkm WHERE status = 'pending'";
        $result = $this->db->query($query);
        $pending_umkm = $result ? $result->fetch_assoc()['count'] : 0;
        
        if ($pending_umkm > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "$pending_umkm UMKM menunggu persetujuan",
                'action' => 'review_umkm'
            ];
        }
        
        // Low performing destinations (less than 5 views in 30 days)
        $query = "
            SELECT COUNT(*) as count 
            FROM wisata w
            LEFT JOIN wisata_views wv ON w.id = wv.wisata_id 
                AND wv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY w.id
            HAVING COUNT(wv.id) < 5
        ";
        $result = $this->db->query($query);
        $low_performing = $result ? $result->num_rows : 0;
        
        if ($low_performing > 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => "$low_performing destinasi perlu perhatian (views rendah)",
                'action' => 'optimize_content'
            ];
        }
        
        // Failed transactions (last 7 days)
        $query = "
            SELECT COUNT(*) as count 
            FROM transaksi 
            WHERE status = 'failed' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ";
        $result = $this->db->query($query);
        $failed_transactions = $result ? $result->fetch_assoc()['count'] : 0;
        
        if ($failed_transactions > 0) {
            $alerts[] = [
                'type' => 'error',
                'message' => "$failed_transactions transaksi gagal minggu ini",
                'action' => 'check_payments'
            ];
        }
        
        return $alerts;
    }
    
    private function getViewsForPeriod($period, $offset = 0) {
        $date_condition = $this->getDateCondition($period, $offset);
        
        // Use direct query instead of prepare for dynamic WHERE clause
        $query = "
            SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT session_id) as unique_visitors
            FROM (
                SELECT session_id FROM wisata_views WHERE $date_condition
                UNION ALL
                SELECT session_id FROM penginapan_views WHERE $date_condition
            ) as combined_views
        ";
        $result = $this->db->query($query);
        return $result->fetch_assoc();
    }
    
    private function getTransactionsForPeriod($period, $offset = 0) {
        $date_condition = str_replace('view_date', 'created_at', $this->getDateCondition($period, $offset));
        
        // Use direct query instead of prepare for dynamic WHERE clause
        $query = "
            SELECT 
                COUNT(*) as count,
                SUM(total_amount) as total_amount
            FROM transaksi 
            WHERE $date_condition AND payment_status IN ('paid', 'awaiting_confirmation')
        ";
        $result = $this->db->query($query);
        return $result->fetch_assoc();
    }
    
    private function getRevenueForPeriod($period, $offset = 0) {
        $date_condition = str_replace('view_date', 'created_at', $this->getDateCondition($period, $offset));
        
        // Use direct query instead of prepare for dynamic WHERE clause
        $query = "
            SELECT COALESCE(SUM(total_amount), 0) as revenue
            FROM transaksi 
            WHERE $date_condition AND payment_status IN ('paid', 'awaiting_confirmation')
        ";
        $result = $this->db->query($query);
        return $result->fetch_assoc()['revenue'];
    }
    
    private function getDateCondition($period, $offset) {
        switch ($period) {
            case 'WEEK':
                if ($offset == 0) {
                    return "view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                } else {
                    return "view_date >= DATE_SUB(NOW(), INTERVAL " . (7 * ($offset + 1)) . " DAY) 
                            AND view_date < DATE_SUB(NOW(), INTERVAL " . (7 * $offset) . " DAY)";
                }
            case 'MONTH':
                if ($offset == 0) {
                    return "view_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                } else {
                    return "view_date >= DATE_SUB(NOW(), INTERVAL " . ($offset + 1) . " MONTH) 
                            AND view_date < DATE_SUB(NOW(), INTERVAL $offset MONTH)";
                }
            default:
                return "view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        }
    }
    
    private function calculatePercentageChange($old_value, $new_value) {
        if ($old_value == 0) {
            return $new_value > 0 ? 100 : 0;
        }
        return round((($new_value - $old_value) / $old_value) * 100, 1);
    }
    
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
}