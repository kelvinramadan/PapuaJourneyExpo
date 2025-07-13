<?php

class FinancialReportsHelper {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Execute a prepared statement with standardized error handling
     */
    private function executeQuery($query, $params = []) {
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("Query preparation error: " . $this->conn->error . " | Query: " . $query);
            return false;
        }
        
        if (!empty($params)) {
            $types = str_repeat('s', count($params)); // Assume all strings for simplicity
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            error_log("Query execution error: " . $stmt->error . " | Query: " . $query);
            return false;
        }
        
        return $stmt;
    }
    
    /**
     * Get comprehensive financial overview (consolidates multiple queries)
     */
    public function getFinancialOverview($start_date, $end_date) {
        $overview_query = "
            SELECT 
                COUNT(DISTINCT t.id) as total_transactions,
                SUM(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN t.payment_status = 'paid' THEN 1 ELSE 0 END) as successful_transactions,
                SUM(CASE WHEN t.payment_status IN ('rejected', 'cancelled') THEN 1 ELSE 0 END) as failed_transactions,
                AVG(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE NULL END) as avg_order_value
            FROM transaksi t
            WHERE DATE(t.created_at) BETWEEN ? AND ?
        ";
        
        $stmt = $this->executeQuery($overview_query, [$start_date, $end_date]);
        if (!$stmt) {
            return [
                'total_transactions' => 0,
                'total_revenue' => 0,
                'successful_transactions' => 0,
                'failed_transactions' => 0,
                'avg_order_value' => 0
            ];
        }
        
        $result = $stmt->get_result()->fetch_assoc();
        
        // Ensure all values are properly set with defaults
        return [
            'total_transactions' => $result['total_transactions'] ?? 0,
            'total_revenue' => $result['total_revenue'] ?? 0,
            'successful_transactions' => $result['successful_transactions'] ?? 0,
            'failed_transactions' => $result['failed_transactions'] ?? 0,
            'avg_order_value' => $result['avg_order_value'] ?? 0
        ];
    }
    
    /**
     * Get previous period revenue for growth calculation
     */
    public function getPreviousPeriodRevenue($prev_start_date, $prev_end_date) {
        $growth_query = "
            SELECT SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as prev_revenue
            FROM transaksi
            WHERE DATE(created_at) BETWEEN ? AND ?
        ";
        
        $stmt = $this->executeQuery($growth_query, [$prev_start_date, $prev_end_date]);
        if (!$stmt) {
            return ['prev_revenue' => 0];
        }
        
        $result = $stmt->get_result()->fetch_assoc();
        return ['prev_revenue' => $result['prev_revenue'] ?? 0];
    }
    
    /**
     * Get consolidated analytics data (combines product types, top products, and daily revenue)
     */
    public function getAnalyticsData($start_date, $end_date) {
        $analytics = [];
        
        // Product types revenue
        $product_type_query = "
            SELECT 
                ti.item_type,
                COUNT(DISTINCT ti.id) as item_count,
                SUM(ti.subtotal) as revenue
            FROM transaksi_items ti
            JOIN transaksi t ON ti.transaksi_id = t.id
            WHERE t.payment_status = 'paid' 
            AND DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY ti.item_type
        ";
        
        $stmt = $this->executeQuery($product_type_query, [$start_date, $end_date]);
        $analytics['product_types'] = $stmt ? $stmt->get_result()->fetch_all(MYSQLI_ASSOC) : [];
        
        // Top products
        $top_products_query = "
            SELECT 
                ti.item_name,
                ti.item_type,
                COUNT(ti.id) as sales_count,
                SUM(ti.quantity) as total_quantity,
                SUM(ti.subtotal) as total_revenue,
                AVG(ti.price_per_unit) as avg_price
            FROM transaksi_items ti
            JOIN transaksi t ON ti.transaksi_id = t.id
            WHERE t.payment_status = 'paid'
            AND DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY ti.item_name, ti.item_type
            ORDER BY total_revenue DESC
            LIMIT 10
        ";
        
        $stmt = $this->executeQuery($top_products_query, [$start_date, $end_date]);
        $analytics['top_products'] = $stmt ? $stmt->get_result()->fetch_all(MYSQLI_ASSOC) : [];
        
        // Daily revenue
        $daily_revenue_query = "
            SELECT 
                DATE(created_at) as date,
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as revenue,
                COUNT(CASE WHEN payment_status = 'paid' THEN 1 ELSE NULL END) as paid_count,
                COUNT(*) as total_count
            FROM transaksi
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ";
        
        $stmt = $this->executeQuery($daily_revenue_query, [$start_date, $end_date]);
        $analytics['daily_revenue'] = $stmt ? $stmt->get_result()->fetch_all(MYSQLI_ASSOC) : [];
        
        return $analytics;
    }
    
    /**
     * Get optimized UMKM revenue data
     */
    public function getUMKMRevenue($start_date, $end_date) {
        $umkm_revenue_query = "
            SELECT 
                u.business_name as nama_umkm,
                u.id as umkm_id,
                COUNT(DISTINCT t.id) as transaction_count,
                COALESCE(SUM(ti.subtotal), 0) as total_revenue
            FROM umkm u
            LEFT JOIN artikel a ON u.id = a.umkm_id
            LEFT JOIN transaksi_items ti ON ti.item_id = a.id AND ti.item_type = 'artikel'
            LEFT JOIN transaksi t ON ti.transaksi_id = t.id 
                AND t.payment_status = 'paid'
                AND DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY u.id, u.business_name
            HAVING total_revenue > 0
            ORDER BY total_revenue DESC
            LIMIT 10
        ";
        
        $stmt = $this->executeQuery($umkm_revenue_query, [$start_date, $end_date]);
        return $stmt ? $stmt->get_result()->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    /**
     * Get payment methods distribution
     */
    public function getPaymentMethodsDistribution($start_date, $end_date) {
        $payment_methods_query = "
            SELECT 
                COALESCE(payment_method, 'Not Specified') as payment_method,
                COUNT(*) as count,
                SUM(total_amount) as total_amount
            FROM transaksi
            WHERE payment_status = 'paid'
            AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY payment_method
        ";
        
        $stmt = $this->executeQuery($payment_methods_query, [$start_date, $end_date]);
        return $stmt ? $stmt->get_result()->fetch_all(MYSQLI_ASSOC) : [];
    }
    
    /**
     * Calculate growth rate with proper validation
     */
    public function calculateGrowthRate($current_revenue, $previous_revenue) {
        if (!is_numeric($current_revenue) || !is_numeric($previous_revenue)) {
            return 0;
        }
        
        $current_revenue = (float) $current_revenue;
        $previous_revenue = (float) $previous_revenue;
        
        if ($previous_revenue <= 0) {
            return $current_revenue > 0 ? 100 : 0; // 100% growth if we had no revenue before
        }
        
        return (($current_revenue - $previous_revenue) / $previous_revenue) * 100;
    }
    
    /**
     * Safely calculate total from array with validation
     */
    public function safeArraySum($array, $column) {
        if (!is_array($array) || empty($array)) {
            return 0;
        }
        
        $values = array_column($array, $column);
        if (empty($values)) {
            return 0;
        }
        
        // Filter out non-numeric values
        $numeric_values = array_filter($values, 'is_numeric');
        return !empty($numeric_values) ? array_sum($numeric_values) : 0;
    }
    
    /**
     * Get success rate with validation
     */
    public function calculateSuccessRate($successful_transactions, $total_transactions) {
        if (!is_numeric($successful_transactions) || !is_numeric($total_transactions)) {
            return 0;
        }
        
        $successful = (int) $successful_transactions;
        $total = (int) $total_transactions;
        
        return $total > 0 ? round(($successful / $total) * 100, 1) : 0;
    }
}