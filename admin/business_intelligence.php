<?php
// admin/business_intelligence.php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Business Intelligence';
$db = getDbConnection();

// Business Intelligence Functions
function getCustomerSegmentation($db) {
    $segmentation_query = "
        SELECT 
            CASE 
                WHEN total_spent >= 5000000 THEN 'VIP Customers'
                WHEN total_spent >= 2000000 THEN 'Premium Customers'
                WHEN total_spent >= 500000 THEN 'Regular Customers'
                WHEN total_spent > 0 THEN 'Occasional Customers'
                ELSE 'Potential Customers'
            END as segment,
            COUNT(*) as customer_count,
            AVG(total_spent) as avg_spending,
            SUM(total_spent) as total_revenue,
            AVG(transaction_count) as avg_transactions
        FROM (
            SELECT 
                u.id,
                u.username,
                COALESCE(SUM(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE 0 END), 0) as total_spent,
                COUNT(t.id) as transaction_count
            FROM users u
            LEFT JOIN transaksi t ON u.id = t.user_id
            GROUP BY u.id
        ) user_stats
        GROUP BY segment
        ORDER BY avg_spending DESC
    ";
    
    return $db->query($segmentation_query)->fetch_all(MYSQLI_ASSOC);
}

function getProductPerformance($db) {
    $performance_query = "
        SELECT 
            'Tourism' as category,
            COUNT(DISTINCT w.id) as total_products,
            COALESCE(SUM(booking_count), 0) as total_bookings,
            COALESCE(AVG(booking_count), 0) as avg_bookings_per_product,
            COALESCE(SUM(revenue), 0) as total_revenue
        FROM wisata w
        LEFT JOIN (
            SELECT 
                item_id,
                COUNT(*) as booking_count,
                SUM(w.harga) as revenue
            FROM cart_items ci
            JOIN wisata w ON ci.item_id = w.id
            WHERE ci.item_type = 'wisata'
            GROUP BY item_id
        ) stats ON w.id = stats.item_id
        
        UNION ALL
        
        SELECT 
            'Accommodation' as category,
            COUNT(DISTINCT p.id) as total_products,
            COALESCE(SUM(booking_count), 0) as total_bookings,
            COALESCE(AVG(booking_count), 0) as avg_bookings_per_product,
            COALESCE(SUM(revenue), 0) as total_revenue
        FROM penginapan p
        LEFT JOIN (
            SELECT 
                item_id,
                COUNT(*) as booking_count,
                SUM(p.harga) as revenue
            FROM cart_items ci
            JOIN penginapan p ON ci.item_id = p.id
            WHERE ci.item_type = 'penginapan'
            GROUP BY item_id
        ) stats ON p.id = stats.item_id
        
        UNION ALL
        
        SELECT 
            'UMKM Products' as category,
            COUNT(DISTINCT a.id) as total_products,
            COALESCE(SUM(booking_count), 0) as total_bookings,
            COALESCE(AVG(booking_count), 0) as avg_bookings_per_product,
            COALESCE(SUM(revenue), 0) as total_revenue
        FROM artikel a
        LEFT JOIN (
            SELECT 
                item_id,
                COUNT(*) as booking_count,
                SUM(a.harga) as revenue
            FROM cart_items ci
            JOIN artikel a ON ci.item_id = a.id
            WHERE ci.item_type = 'umkm'
            GROUP BY item_id
        ) stats ON a.id = stats.item_id
    ";
    
    return $db->query($performance_query)->fetch_all(MYSQLI_ASSOC);
}

function getGeographicAnalysis($db) {
    // Analyze user distribution and revenue by region (if location data available)
    $geographic_query = "
        SELECT 
            COALESCE(u.location, 'Unknown') as region,
            COUNT(DISTINCT u.id) as user_count,
            COUNT(t.id) as total_transactions,
            SUM(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE 0 END) as total_revenue,
            AVG(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE NULL END) as avg_order_value
        FROM users u
        LEFT JOIN transaksi t ON u.id = t.user_id
        GROUP BY COALESCE(u.location, 'Unknown')
        ORDER BY total_revenue DESC
        LIMIT 10
    ";
    
    return $db->query($geographic_query)->fetch_all(MYSQLI_ASSOC);
}

function getCompetitiveAnalysis($db) {
    // Analyze market position and trends
    $analysis = [];
    
    // Market share by category
    $market_share_query = "
        SELECT 
            ci.item_type as category,
            COUNT(*) as demand_count,
            COUNT(*) * 100.0 / (SELECT COUNT(*) FROM cart_items) as market_share
        FROM cart_items ci
        GROUP BY ci.item_type
        ORDER BY demand_count DESC
    ";
    
    $analysis['market_share'] = $db->query($market_share_query)->fetch_all(MYSQLI_ASSOC);
    
    // Growth rates by month
    $growth_query = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as transactions,
            SUM(total_amount) as revenue,
            LAG(COUNT(*)) OVER (ORDER BY DATE_FORMAT(created_at, '%Y-%m')) as prev_transactions,
            LAG(SUM(total_amount)) OVER (ORDER BY DATE_FORMAT(created_at, '%Y-%m')) as prev_revenue
        FROM transaksi 
        WHERE payment_status = 'paid'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ";
    
    $analysis['growth_trends'] = $db->query($growth_query)->fetch_all(MYSQLI_ASSOC);
    
    return $analysis;
}

function getOperationalMetrics($db) {
    $metrics_query = "
        SELECT 
            COUNT(DISTINCT t.id) as total_transactions,
            COUNT(DISTINCT CASE WHEN t.payment_status = 'paid' THEN t.id END) as successful_transactions,
            COUNT(DISTINCT CASE WHEN t.payment_status = 'pending' THEN t.id END) as pending_transactions,
            COUNT(DISTINCT CASE WHEN t.payment_status IN ('rejected', 'cancelled') THEN t.id END) as failed_transactions,
            
            COUNT(DISTINCT u.id) as total_users,
            COUNT(DISTINCT CASE WHEN u.last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN u.id END) as active_users,
            COUNT(DISTINCT CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN u.id END) as new_users,
            
            COUNT(DISTINCT ci.user_id) as users_with_carts,
            AVG(cart_size.items) as avg_cart_size,
            
            COUNT(DISTINCT umkm.id) as total_umkm,
            COUNT(DISTINCT CASE WHEN umkm.status = 'active' THEN umkm.id END) as active_umkm
            
        FROM transaksi t
        CROSS JOIN users u
        CROSS JOIN cart_items ci
        CROSS JOIN umkm
        LEFT JOIN (
            SELECT user_id, COUNT(*) as items
            FROM cart_items
            GROUP BY user_id
        ) cart_size ON ci.user_id = cart_size.user_id
    ";
    
    return $db->query($metrics_query)->fetch_assoc();
}

function getAdvancedInsights($db) {
    $insights = [];
    
    // Customer Lifetime Value
    $clv_query = "
        SELECT 
            AVG(customer_value) as avg_clv,
            MAX(customer_value) as max_clv,
            MIN(customer_value) as min_clv
        FROM (
            SELECT 
                user_id,
                SUM(total_amount) as customer_value
            FROM transaksi 
            WHERE payment_status = 'paid'
            GROUP BY user_id
        ) clv_data
    ";
    
    $insights['clv'] = $db->query($clv_query)->fetch_assoc();
    
    // Retention Rate
    $retention_query = "
        SELECT 
            COUNT(DISTINCT repeat_customers.user_id) as returning_customers,
            COUNT(DISTINCT all_customers.user_id) as total_customers,
            COUNT(DISTINCT repeat_customers.user_id) * 100.0 / COUNT(DISTINCT all_customers.user_id) as retention_rate
        FROM (
            SELECT DISTINCT user_id FROM transaksi WHERE payment_status = 'paid'
        ) all_customers
        LEFT JOIN (
            SELECT user_id
            FROM transaksi 
            WHERE payment_status = 'paid'
            GROUP BY user_id
            HAVING COUNT(*) > 1
        ) repeat_customers ON all_customers.user_id = repeat_customers.user_id
    ";
    
    $insights['retention'] = $db->query($retention_query)->fetch_assoc();
    
    return $insights;
}

// Get all business intelligence data
$customer_segments = getCustomerSegmentation($db);
$product_performance = getProductPerformance($db);
$geographic_analysis = getGeographicAnalysis($db);
$competitive_analysis = getCompetitiveAnalysis($db);
$operational_metrics = getOperationalMetrics($db);
$advanced_insights = getAdvancedInsights($db);

$db->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Intelligence - Papua Journey</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .bi-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .bi-section {
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
        
        .customers-icon { background: #EFF6FF; color: #1D4ED8; }
        .products-icon { background: #F0FDF4; color: #16A34A; }
        .geographic-icon { background: #FEF3C7; color: #D97706; }
        .competitive-icon { background: #F3E8FF; color: #7C3AED; }
        .insights-icon { background: #FEE2E2; color: #DC2626; }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .metric-card {
            text-align: center;
            padding: 1.5rem;
            background: #F9FAFB;
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .metric-card.primary { border-left-color: #3B82F6; }
        .metric-card.success { border-left-color: #10B981; }
        .metric-card.warning { border-left-color: #F59E0B; }
        .metric-card.danger { border-left-color: #EF4444; }
        
        .metric-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .metric-label {
            color: #6B7280;
            font-size: 0.9rem;
        }
        
        .segment-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .segment-table th,
        .segment-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #E5E7EB;
        }
        
        .segment-table th {
            background: #F9FAFB;
            font-weight: 600;
        }
        
        .performance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .performance-card {
            padding: 1.5rem;
            background: #F9FAFB;
            border-radius: 8px;
            border-left: 4px solid #3B82F6;
        }
        
        .chart-container {
            height: 300px;
            margin-top: 1rem;
        }
        
        .insight-highlight {
            background: #EFF6FF;
            border: 1px solid #DBEAFE;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .insight-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1D4ED8;
        }
        
        .growth-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .growth-positive {
            background: #D1FAE5;
            color: #059669;
        }
        
        .growth-negative {
            background: #FEE2E2;
            color: #DC2626;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="main-content" id="mainContent">
            <?php include 'components/header.php'; ?>
            
            <div class="content-wrapper">
                <!-- Business Intelligence Header -->
                <div class="bi-header">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                        🧠 Business Intelligence
                    </div>
                    <div style="font-size: 1.1rem; opacity: 0.9;">
                        Comprehensive business insights and data-driven decision support
                    </div>
                </div>
                
                <!-- Key Operational Metrics -->
                <div class="metrics-grid">
                    <div class="metric-card primary">
                        <div class="metric-value"><?php echo number_format($operational_metrics['total_transactions']); ?></div>
                        <div class="metric-label">Total Transactions</div>
                    </div>
                    <div class="metric-card success">
                        <div class="metric-value"><?php echo number_format($operational_metrics['active_users']); ?></div>
                        <div class="metric-label">Active Users (30d)</div>
                    </div>
                    <div class="metric-card warning">
                        <div class="metric-value"><?php echo number_format($operational_metrics['pending_transactions']); ?></div>
                        <div class="metric-label">Pending Payments</div>
                    </div>
                    <div class="metric-card danger">
                        <div class="metric-value"><?php echo number_format($operational_metrics['failed_transactions']); ?></div>
                        <div class="metric-label">Failed Transactions</div>
                    </div>
                </div>
                
                <!-- Customer Segmentation -->
                <div class="bi-section">
                    <div class="section-header">
                        <div class="section-icon customers-icon">👥</div>
                        <div>
                            <h3>Customer Segmentation Analysis</h3>
                            <p style="margin: 0; color: #6B7280;">Customer distribution by spending behavior</p>
                        </div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="segment-table">
                            <thead>
                                <tr>
                                    <th>Customer Segment</th>
                                    <th>Customer Count</th>
                                    <th>Average Spending</th>
                                    <th>Total Revenue</th>
                                    <th>Avg Transactions</th>
                                    <th>Revenue Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_revenue = array_sum(array_column($customer_segments, 'total_revenue'));
                                foreach ($customer_segments as $segment): 
                                    $revenue_share = $total_revenue > 0 ? ($segment['total_revenue'] / $total_revenue) * 100 : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo $segment['segment']; ?></strong></td>
                                    <td><?php echo number_format($segment['customer_count']); ?></td>
                                    <td>Rp <?php echo number_format($segment['avg_spending'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($segment['total_revenue'], 0, ',', '.'); ?></td>
                                    <td><?php echo number_format($segment['avg_transactions'], 1); ?></td>
                                    <td><?php echo number_format($revenue_share, 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Product Performance -->
                <div class="bi-section">
                    <div class="section-header">
                        <div class="section-icon products-icon">📦</div>
                        <div>
                            <h3>Product Performance Analysis</h3>
                            <p style="margin: 0; color: #6B7280;">Performance metrics across all product categories</p>
                        </div>
                    </div>
                    
                    <div class="performance-grid">
                        <?php foreach ($product_performance as $category): ?>
                        <div class="performance-card">
                            <h4 style="margin-bottom: 1rem;"><?php echo $category['category']; ?></h4>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Total Products:</span>
                                    <strong><?php echo number_format($category['total_products']); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Total Bookings:</span>
                                    <strong><?php echo number_format($category['total_bookings']); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Avg Bookings/Product:</span>
                                    <strong><?php echo number_format($category['avg_bookings_per_product'], 1); ?></strong>
                                </div>
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Total Revenue:</span>
                                    <strong>Rp <?php echo number_format($category['total_revenue'], 0, ',', '.'); ?></strong>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Market Share Analysis -->
                <div class="bi-section">
                    <div class="section-header">
                        <div class="section-icon competitive-icon">📊</div>
                        <div>
                            <h3>Market Share & Competitive Analysis</h3>
                            <p style="margin: 0; color: #6B7280;">Market position and demand distribution</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            <h4>Market Share by Category</h4>
                            <div class="chart-container">
                                <canvas id="marketShareChart"></canvas>
                            </div>
                        </div>
                        
                        <div>
                            <h4>Demand Distribution</h4>
                            <?php foreach ($competitive_analysis['market_share'] as $market): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #E5E7EB;">
                                <span style="font-weight: 600;"><?php echo ucfirst($market['category']); ?></span>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <span><?php echo number_format($market['demand_count']); ?> items</span>
                                    <span style="background: #3B82F6; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem;">
                                        <?php echo number_format($market['market_share'], 1); ?>%
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Geographic Analysis -->
                <div class="bi-section">
                    <div class="section-header">
                        <div class="section-icon geographic-icon">🌍</div>
                        <div>
                            <h3>Geographic Distribution</h3>
                            <p style="margin: 0; color: #6B7280;">Revenue and user distribution by region</p>
                        </div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="segment-table">
                            <thead>
                                <tr>
                                    <th>Region</th>
                                    <th>Users</th>
                                    <th>Transactions</th>
                                    <th>Total Revenue</th>
                                    <th>Avg Order Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($geographic_analysis as $region): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($region['region']); ?></strong></td>
                                    <td><?php echo number_format($region['user_count']); ?></td>
                                    <td><?php echo number_format($region['total_transactions']); ?></td>
                                    <td>Rp <?php echo number_format($region['total_revenue'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($region['avg_order_value'] ?? 0, 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Advanced Business Insights -->
                <div class="bi-section">
                    <div class="section-header">
                        <div class="section-icon insights-icon">💡</div>
                        <div>
                            <h3>Advanced Business Insights</h3>
                            <p style="margin: 0; color: #6B7280;">Key business intelligence metrics and recommendations</p>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <div class="insight-highlight">
                            <h4 style="margin-bottom: 1rem;">Customer Lifetime Value (CLV)</h4>
                            <div class="insight-value">Rp <?php echo number_format($advanced_insights['clv']['avg_clv'], 0, ',', '.'); ?></div>
                            <p style="margin: 0.5rem 0 0 0; color: #6B7280;">Average customer value</p>
                            <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #6B7280;">
                                Range: Rp <?php echo number_format($advanced_insights['clv']['min_clv'], 0, ',', '.'); ?> - 
                                Rp <?php echo number_format($advanced_insights['clv']['max_clv'], 0, ',', '.'); ?>
                            </div>
                        </div>
                        
                        <div class="insight-highlight">
                            <h4 style="margin-bottom: 1rem;">Customer Retention Rate</h4>
                            <div class="insight-value"><?php echo number_format($advanced_insights['retention']['retention_rate'], 1); ?>%</div>
                            <p style="margin: 0.5rem 0 0 0; color: #6B7280;">Customers making repeat purchases</p>
                            <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #6B7280;">
                                <?php echo number_format($advanced_insights['retention']['returning_customers']); ?> of 
                                <?php echo number_format($advanced_insights['retention']['total_customers']); ?> customers
                            </div>
                        </div>
                        
                        <div class="insight-highlight">
                            <h4 style="margin-bottom: 1rem;">Average Cart Size</h4>
                            <div class="insight-value"><?php echo number_format($operational_metrics['avg_cart_size'], 1); ?></div>
                            <p style="margin: 0.5rem 0 0 0; color: #6B7280;">Items per shopping cart</p>
                            <div style="margin-top: 0.5rem; font-size: 0.85rem; color: #6B7280;">
                                <?php echo number_format($operational_metrics['users_with_carts']); ?> active carts
                            </div>
                        </div>
                    </div>
                    
                    <!-- Business Recommendations -->
                    <div style="margin-top: 2rem; padding: 1.5rem; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px;">
                        <h4 style="color: #16A34A; margin-bottom: 1rem;">🎯 Strategic Recommendations</h4>
                        <ul style="margin: 0; padding-left: 1.5rem; color: #166534;">
                            <li style="margin-bottom: 0.5rem;">Focus on VIP customer retention programs to maintain high-value segments</li>
                            <li style="margin-bottom: 0.5rem;">Implement cross-selling strategies between tourism and accommodation categories</li>
                            <li style="margin-bottom: 0.5rem;">Develop regional marketing campaigns for high-performing geographic areas</li>
                            <li style="margin-bottom: 0.5rem;">Create loyalty programs to improve the <?php echo number_format($advanced_insights['retention']['retention_rate'], 1); ?>% retention rate</li>
                            <li>Optimize cart abandonment recovery to increase conversion rates</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Market Share Chart
        const marketShareCtx = document.getElementById('marketShareChart').getContext('2d');
        
        const marketShareData = <?php echo json_encode($competitive_analysis['market_share']); ?>;
        
        new Chart(marketShareCtx, {
            type: 'doughnut',
            data: {
                labels: marketShareData.map(item => item.category.charAt(0).toUpperCase() + item.category.slice(1)),
                datasets: [{
                    data: marketShareData.map(item => parseFloat(item.market_share)),
                    backgroundColor: [
                        '#3B82F6',
                        '#10B981',
                        '#F59E0B',
                        '#EF4444',
                        '#8B5CF6'
                    ],
                    borderWidth: 2,
                    borderColor: '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed.toFixed(1) + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>