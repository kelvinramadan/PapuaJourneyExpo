<?php
// admin/data_mining.php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Data Mining & Insights';
$db = getDbConnection();

// Data Mining Functions
function getPatternAnalysis($db) {
    $patterns = [];
    
    // User behavior patterns
    $behavior_query = "
        SELECT 
            HOUR(created_at) as hour_of_day,
            DAYOFWEEK(created_at) as day_of_week,
            COUNT(*) as transaction_count,
            AVG(total_amount) as avg_transaction_value,
            CASE 
                WHEN DAYOFWEEK(created_at) IN (1, 7) THEN 'Weekend'
                ELSE 'Weekday'
            END as day_type
        FROM transaksi 
        WHERE payment_status = 'paid'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        GROUP BY HOUR(created_at), DAYOFWEEK(created_at)
        ORDER BY transaction_count DESC
    ";
    
    $patterns['behavior'] = $db->query($behavior_query)->fetch_all(MYSQLI_ASSOC);
    
    // Seasonal booking patterns
    $seasonal_query = "
        SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as bookings,
            SUM(total_amount) as revenue,
            AVG(total_amount) as avg_order_value,
            CASE 
                WHEN MONTH(created_at) IN (12, 1, 2) THEN 'High Season'
                WHEN MONTH(created_at) IN (6, 7, 8) THEN 'Holiday Season'
                ELSE 'Regular Season'
            END as season_type
        FROM transaksi 
        WHERE payment_status = 'paid'
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY year DESC, month DESC
        LIMIT 24
    ";
    
    $patterns['seasonal'] = $db->query($seasonal_query)->fetch_all(MYSQLI_ASSOC);
    
    return $patterns;
}

function getCohortAnalysis($db) {
    // Customer cohort analysis
    $cohort_query = "
        SELECT 
            DATE_FORMAT(first_purchase, '%Y-%m') as cohort_month,
            TIMESTAMPDIFF(MONTH, first_purchase, purchase_date) as period_number,
            COUNT(DISTINCT user_id) as customers,
            SUM(total_amount) as revenue
        FROM (
            SELECT 
                t.user_id,
                t.total_amount,
                DATE(t.created_at) as purchase_date,
                (
                    SELECT DATE(MIN(created_at)) 
                    FROM transaksi t2 
                    WHERE t2.user_id = t.user_id 
                    AND t2.payment_status = 'paid'
                ) as first_purchase
            FROM transaksi t
            WHERE t.payment_status = 'paid'
            AND t.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        ) cohort_data
        WHERE first_purchase IS NOT NULL
        GROUP BY cohort_month, period_number
        ORDER BY cohort_month, period_number
    ";
    
    return $db->query($cohort_query)->fetch_all(MYSQLI_ASSOC);
}

function getCustomerSegmentAnalysis($db) {
    $segments = [];
    
    // RFM Analysis (Recency, Frequency, Monetary)
    $rfm_query = "
        SELECT 
            user_id,
            DATEDIFF(NOW(), MAX(created_at)) as recency,
            COUNT(*) as frequency,
            SUM(total_amount) as monetary,
            CASE 
                WHEN DATEDIFF(NOW(), MAX(created_at)) <= 30 AND COUNT(*) >= 3 AND SUM(total_amount) >= 2000000 THEN 'Champions'
                WHEN DATEDIFF(NOW(), MAX(created_at)) <= 60 AND COUNT(*) >= 2 AND SUM(total_amount) >= 1000000 THEN 'Loyal Customers'
                WHEN DATEDIFF(NOW(), MAX(created_at)) <= 90 AND COUNT(*) >= 2 THEN 'Potential Loyalists'
                WHEN DATEDIFF(NOW(), MAX(created_at)) <= 30 AND COUNT(*) = 1 THEN 'New Customers'
                WHEN DATEDIFF(NOW(), MAX(created_at)) > 90 AND SUM(total_amount) >= 1000000 THEN 'At Risk'
                WHEN DATEDIFF(NOW(), MAX(created_at)) > 180 THEN 'Lost Customers'
                ELSE 'Regular Customers'
            END as segment
        FROM transaksi 
        WHERE payment_status = 'paid'
        GROUP BY user_id
    ";
    
    $result = $db->query($rfm_query);
    $rfm_data = $result->fetch_all(MYSQLI_ASSOC);
    
    // Aggregate by segment
    $segment_summary = [];
    foreach ($rfm_data as $customer) {
        $segment = $customer['segment'];
        if (!isset($segment_summary[$segment])) {
            $segment_summary[$segment] = [
                'count' => 0,
                'total_monetary' => 0,
                'avg_recency' => 0,
                'avg_frequency' => 0
            ];
        }
        
        $segment_summary[$segment]['count']++;
        $segment_summary[$segment]['total_monetary'] += $customer['monetary'];
        $segment_summary[$segment]['avg_recency'] += $customer['recency'];
        $segment_summary[$segment]['avg_frequency'] += $customer['frequency'];
    }
    
    // Calculate averages
    foreach ($segment_summary as $segment => &$data) {
        $data['avg_recency'] = $data['avg_recency'] / $data['count'];
        $data['avg_frequency'] = $data['avg_frequency'] / $data['count'];
        $data['avg_monetary'] = $data['total_monetary'] / $data['count'];
    }
    
    $segments['rfm'] = $segment_summary;
    
    return $segments;
}

function getProductAffinityAnalysis($db) {
    // Market basket analysis
    $affinity_query = "
        WITH cart_combinations AS (
            SELECT 
                c1.user_id,
                c1.item_type as product_a,
                c2.item_type as product_b,
                COUNT(*) as co_occurrence
            FROM cart_items c1
            JOIN cart_items c2 ON c1.user_id = c2.user_id
            WHERE c1.item_type != c2.item_type
            AND c1.updated_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            AND c2.updated_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY c1.user_id, c1.item_type, c2.item_type
        )
        SELECT 
            product_a,
            product_b,
            COUNT(*) as frequency,
            COUNT(*) * 100.0 / (
                SELECT COUNT(DISTINCT user_id) 
                FROM cart_items 
                WHERE item_type = product_a
                AND updated_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            ) as support_percentage
        FROM cart_combinations
        GROUP BY product_a, product_b
        HAVING frequency >= 2
        ORDER BY frequency DESC, support_percentage DESC
    ";
    
    return $db->query($affinity_query)->fetch_all(MYSQLI_ASSOC);
}

function getAnomalyDetection($db) {
    $anomalies = [];
    
    // Detect unusual transaction patterns
    $transaction_anomalies_query = "
        SELECT 
            DATE(created_at) as transaction_date,
            COUNT(*) as daily_transactions,
            SUM(total_amount) as daily_revenue,
            AVG(total_amount) as avg_transaction_value,
            (
                SELECT AVG(daily_count) 
                FROM (
                    SELECT COUNT(*) as daily_count
                    FROM transaksi 
                    WHERE payment_status = 'paid'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at)
                ) avg_calc
            ) as avg_daily_transactions,
            CASE 
                WHEN COUNT(*) > (
                    SELECT AVG(daily_count) * 2
                    FROM (
                        SELECT COUNT(*) as daily_count
                        FROM transaksi 
                        WHERE payment_status = 'paid'
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY DATE(created_at)
                    ) avg_calc
                ) THEN 'High Activity'
                WHEN COUNT(*) < (
                    SELECT AVG(daily_count) * 0.5
                    FROM (
                        SELECT COUNT(*) as daily_count
                        FROM transaksi 
                        WHERE payment_status = 'paid'
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                        GROUP BY DATE(created_at)
                    ) avg_calc
                ) THEN 'Low Activity'
                ELSE 'Normal'
            END as anomaly_type
        FROM transaksi 
        WHERE payment_status = 'paid'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        HAVING anomaly_type != 'Normal'
        ORDER BY transaction_date DESC
    ";
    
    $anomalies['transactions'] = $db->query($transaction_anomalies_query)->fetch_all(MYSQLI_ASSOC);
    
    // Detect price anomalies
    $price_anomalies_query = "
        SELECT 
            t.id,
            t.user_id,
            t.total_amount,
            t.created_at,
            (
                SELECT AVG(total_amount) 
                FROM transaksi 
                WHERE payment_status = 'paid'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ) as avg_transaction_amount,
            CASE 
                WHEN t.total_amount > (
                    SELECT AVG(total_amount) * 3
                    FROM transaksi 
                    WHERE payment_status = 'paid'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ) THEN 'Unusually High'
                WHEN t.total_amount < (
                    SELECT AVG(total_amount) * 0.1
                    FROM transaksi 
                    WHERE payment_status = 'paid'
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ) THEN 'Unusually Low'
                ELSE 'Normal'
            END as price_anomaly
        FROM transaksi t
        WHERE t.payment_status = 'paid'
        AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        HAVING price_anomaly != 'Normal'
        ORDER BY t.created_at DESC
        LIMIT 10
    ";
    
    $anomalies['prices'] = $db->query($price_anomalies_query)->fetch_all(MYSQLI_ASSOC);
    
    return $anomalies;
}

function getPerformanceMetrics($db) {
    $metrics = [];
    
    // Conversion funnel analysis
    $funnel_query = "
        SELECT 
            'Product Views' as stage,
            (
                SELECT COUNT(*) 
                FROM wisata_views 
                WHERE view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ) + (
                SELECT COUNT(*) 
                FROM penginapan_views 
                WHERE view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ) as count,
            1 as stage_order
        
        UNION ALL
        
        SELECT 
            'Cart Additions' as stage,
            COUNT(*) as count,
            2 as stage_order
        FROM cart_items 
        WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        
        UNION ALL
        
        SELECT 
            'Purchases' as stage,
            COUNT(*) as count,
            3 as stage_order
        FROM transaksi 
        WHERE payment_status = 'paid'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        
        ORDER BY stage_order
    ";
    
    $metrics['funnel'] = $db->query($funnel_query)->fetch_all(MYSQLI_ASSOC);
    
    return $metrics;
}

// Get all data mining insights
$patterns = getPatternAnalysis($db);
$cohorts = getCohortAnalysis($db);
$segments = getCustomerSegmentAnalysis($db);
$affinity = getProductAffinityAnalysis($db);
$anomalies = getAnomalyDetection($db);
$performance = getPerformanceMetrics($db);

$db->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mining & Insights - Papua Journey</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .mining-header {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .insight-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #F3F4F6;
        }
        
        .section-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .pattern-icon { background: #EFF6FF; color: #1D4ED8; }
        .segment-icon { background: #F0FDF4; color: #16A34A; }
        .affinity-icon { background: #FEF3C7; color: #D97706; }
        .anomaly-icon { background: #FEE2E2; color: #DC2626; }
        .funnel-icon { background: #F3E8FF; color: #7C3AED; }
        
        .insight-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .insight-card {
            padding: 1.5rem;
            background: #F9FAFB;
            border-radius: 8px;
            border-left: 4px solid #3B82F6;
        }
        
        .insight-card.warning {
            border-left-color: #F59E0B;
            background: #FFFBEB;
        }
        
        .insight-card.danger {
            border-left-color: #EF4444;
            background: #FEF2F2;
        }
        
        .insight-card.success {
            border-left-color: #10B981;
            background: #ECFDF5;
        }
        
        .chart-container {
            height: 300px;
            margin-top: 1rem;
        }
        
        .heatmap-container {
            display: grid;
            grid-template-columns: repeat(24, 1fr);
            gap: 2px;
            margin-top: 1rem;
        }
        
        .heatmap-cell {
            aspect-ratio: 1;
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            color: white;
            font-weight: 600;
        }
        
        .segment-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .champions { background: #10B981; color: white; }
        .loyal { background: #3B82F6; color: white; }
        .potential { background: #F59E0B; color: white; }
        .new { background: #8B5CF6; color: white; }
        .at-risk { background: #EF4444; color: white; }
        .lost { background: #6B7280; color: white; }
        .regular { background: #E5E7EB; color: #374151; }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .data-table th {
            background: #F9FAFB;
            font-weight: 600;
        }
        
        .anomaly-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .anomaly-high { background: #FEE2E2; color: #DC2626; }
        .anomaly-low { background: #DBEAFE; color: #1D4ED8; }
        
        .funnel-stage {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            margin-bottom: 0.5rem;
            background: #F9FAFB;
            border-radius: 8px;
            position: relative;
        }
        
        .funnel-stage::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-top: 10px solid #F9FAFB;
        }
        
        .funnel-stage:last-child::after {
            display: none;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="main-content" id="mainContent">
            <?php include 'components/header.php'; ?>
            
            <div class="content-wrapper">
                <!-- Data Mining Header -->
                <div class="mining-header">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                        ⛏️ Data Mining & Advanced Insights
                    </div>
                    <div style="font-size: 1.1rem; opacity: 0.9;">
                        Deep data analysis, pattern recognition, and behavioral insights
                    </div>
                </div>
                
                <!-- Conversion Funnel Analysis -->
                <div class="insight-section">
                    <div class="section-header">
                        <div class="section-icon funnel-icon">🔄</div>
                        <div>
                            <h3>Conversion Funnel Analysis</h3>
                            <p style="margin: 0; color: #6B7280;">Track user journey from view to purchase</p>
                        </div>
                    </div>
                    
                    <div style="max-width: 600px; margin: 0 auto;">
                        <?php 
                        $total_views = 0;
                        foreach ($performance['funnel'] as $index => $stage): 
                            if ($index === 0) $total_views = $stage['count'];
                            $conversion_rate = $total_views > 0 ? ($stage['count'] / $total_views) * 100 : 0;
                        ?>
                        <div class="funnel-stage">
                            <div>
                                <strong><?php echo $stage['stage']; ?></strong>
                                <div style="font-size: 0.85rem; color: #6B7280;">
                                    <?php echo number_format($stage['count']); ?> users
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 1.25rem; font-weight: 700; color: #1D4ED8;">
                                    <?php echo number_format($conversion_rate, 1); ?>%
                                </div>
                                <div style="font-size: 0.75rem; color: #6B7280;">conversion</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Customer Segmentation (RFM Analysis) -->
                <div class="insight-section">
                    <div class="section-header">
                        <div class="section-icon segment-icon">👥</div>
                        <div>
                            <h3>Customer Segmentation (RFM Analysis)</h3>
                            <p style="margin: 0; color: #6B7280;">Recency, Frequency, Monetary value segmentation</p>
                        </div>
                    </div>
                    
                    <div class="insight-grid">
                        <?php foreach ($segments['rfm'] as $segment => $data): 
                            $segment_class = strtolower(str_replace(' ', '-', $segment));
                        ?>
                        <div class="insight-card">
                            <div class="segment-badge <?php echo $segment_class; ?>">
                                <?php echo $segment; ?>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <div style="font-size: 0.85rem; color: #6B7280;">Customers</div>
                                    <div style="font-size: 1.25rem; font-weight: 700;"><?php echo number_format($data['count']); ?></div>
                                </div>
                                <div>
                                    <div style="font-size: 0.85rem; color: #6B7280;">Avg Value</div>
                                    <div style="font-size: 1rem; font-weight: 600;">Rp <?php echo number_format($data['avg_monetary'], 0, ',', '.'); ?></div>
                                </div>
                                <div>
                                    <div style="font-size: 0.85rem; color: #6B7280;">Avg Recency</div>
                                    <div style="font-size: 1rem; font-weight: 600;"><?php echo number_format($data['avg_recency'], 0); ?> days</div>
                                </div>
                                <div>
                                    <div style="font-size: 0.85rem; color: #6B7280;">Avg Frequency</div>
                                    <div style="font-size: 1rem; font-weight: 600;"><?php echo number_format($data['avg_frequency'], 1); ?> orders</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Behavioral Patterns -->
                <div class="insight-section">
                    <div class="section-header">
                        <div class="section-icon pattern-icon">📊</div>
                        <div>
                            <h3>Behavioral Pattern Analysis</h3>
                            <p style="margin: 0; color: #6B7280;">Transaction patterns by time and day</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <h4>Peak Transaction Hours</h4>
                            <div class="chart-container">
                                <canvas id="hourlyPatternChart"></canvas>
                            </div>
                        </div>
                        
                        <div>
                            <h4>Seasonal Trends</h4>
                            <div class="chart-container">
                                <canvas id="seasonalTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Product Affinity Analysis -->
                <div class="insight-section">
                    <div class="section-header">
                        <div class="section-icon affinity-icon">🔗</div>
                        <div>
                            <h3>Product Affinity Analysis</h3>
                            <p style="margin: 0; color: #6B7280;">Market basket analysis and product associations</p>
                        </div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product A</th>
                                    <th>Product B</th>
                                    <th>Co-occurrence</th>
                                    <th>Support %</th>
                                    <th>Insight</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($affinity, 0, 10) as $association): ?>
                                <tr>
                                    <td><strong><?php echo ucfirst($association['product_a']); ?></strong></td>
                                    <td><strong><?php echo ucfirst($association['product_b']); ?></strong></td>
                                    <td><?php echo $association['frequency']; ?> times</td>
                                    <td><?php echo number_format($association['support_percentage'], 1); ?>%</td>
                                    <td>
                                        <?php if ($association['support_percentage'] > 20): ?>
                                            <span style="color: #10B981;">Strong association</span>
                                        <?php elseif ($association['support_percentage'] > 10): ?>
                                            <span style="color: #F59E0B;">Moderate association</span>
                                        <?php else: ?>
                                            <span style="color: #6B7280;">Weak association</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Anomaly Detection -->
                <div class="insight-section">
                    <div class="section-header">
                        <div class="section-icon anomaly-icon">⚠️</div>
                        <div>
                            <h3>Anomaly Detection</h3>
                            <p style="margin: 0; color: #6B7280;">Unusual patterns and outliers in business data</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Transaction Anomalies -->
                        <div class="insight-card warning">
                            <h4>📈 Transaction Volume Anomalies</h4>
                            <?php if (empty($anomalies['transactions'])): ?>
                                <p style="color: #10B981; text-align: center; padding: 2rem 0;">
                                    ✅ No transaction anomalies detected in the last 30 days
                                </p>
                            <?php else: ?>
                                <?php foreach ($anomalies['transactions'] as $anomaly): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #F3F4F6;">
                                    <div>
                                        <strong><?php echo date('M j, Y', strtotime($anomaly['transaction_date'])); ?></strong>
                                        <div style="font-size: 0.85rem; color: #6B7280;">
                                            <?php echo $anomaly['daily_transactions']; ?> transactions
                                        </div>
                                    </div>
                                    <span class="anomaly-badge anomaly-<?php echo strtolower(str_replace(' ', '-', $anomaly['anomaly_type'])); ?>">
                                        <?php echo $anomaly['anomaly_type']; ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Price Anomalies -->
                        <div class="insight-card danger">
                            <h4>💰 Price Anomalies</h4>
                            <?php if (empty($anomalies['prices'])): ?>
                                <p style="color: #10B981; text-align: center; padding: 2rem 0;">
                                    ✅ No price anomalies detected
                                </p>
                            <?php else: ?>
                                <?php foreach ($anomalies['prices'] as $price): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #F3F4F6;">
                                    <div>
                                        <strong>Transaction #<?php echo $price['id']; ?></strong>
                                        <div style="font-size: 0.85rem; color: #6B7280;">
                                            Rp <?php echo number_format($price['total_amount'], 0, ',', '.'); ?>
                                        </div>
                                    </div>
                                    <span class="anomaly-badge anomaly-<?php echo $price['price_anomaly'] === 'Unusually High' ? 'high' : 'low'; ?>">
                                        <?php echo $price['price_anomaly']; ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Hourly pattern chart
        const hourlyCtx = document.getElementById('hourlyPatternChart').getContext('2d');
        
        // Process hourly data
        const hourlyData = <?php echo json_encode($patterns['behavior']); ?>;
        const hourlyLabels = [];
        const hourlyValues = [];
        
        // Initialize 24 hours
        for (let i = 0; i < 24; i++) {
            hourlyLabels.push(i + ':00');
            hourlyValues.push(0);
        }
        
        // Fill with actual data
        hourlyData.forEach(item => {
            if (item.hour_of_day >= 0 && item.hour_of_day < 24) {
                hourlyValues[item.hour_of_day] += parseInt(item.transaction_count);
            }
        });
        
        new Chart(hourlyCtx, {
            type: 'line',
            data: {
                labels: hourlyLabels,
                datasets: [{
                    label: 'Transactions',
                    data: hourlyValues,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Seasonal trend chart
        const seasonalCtx = document.getElementById('seasonalTrendChart').getContext('2d');
        
        const seasonalData = <?php echo json_encode($patterns['seasonal']); ?>;
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        const seasonalLabels = seasonalData.map(item => monthNames[item.month - 1] + ' ' + item.year);
        const seasonalRevenue = seasonalData.map(item => parseFloat(item.revenue));
        
        new Chart(seasonalCtx, {
            type: 'bar',
            data: {
                labels: seasonalLabels.slice(-12), // Last 12 months
                datasets: [{
                    label: 'Revenue',
                    data: seasonalRevenue.slice(-12),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: '#10B981',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: Rp ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>