<?php
// admin/predictive_analytics.php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Predictive Analytics';
$db = getDbConnection();

// Predictive Models and Forecasting Functions
function getRevenueForecast($db, $days_ahead = 30) {
    // Get historical revenue data for the last 90 days
    $history_query = "
        SELECT 
            DATE(created_at) as date,
            SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as revenue
        FROM transaksi 
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date
    ";
    
    $result = $db->query($history_query);
    $historical_data = [];
    
    while ($row = $result->fetch_assoc()) {
        $historical_data[] = [
            'date' => $row['date'],
            'revenue' => floatval($row['revenue'])
        ];
    }
    
    // Simple moving average prediction
    $forecast = [];
    $recent_revenues = array_slice(array_column($historical_data, 'revenue'), -14); // Last 14 days
    $avg_revenue = count($recent_revenues) > 0 ? array_sum($recent_revenues) / count($recent_revenues) : 0;
    
    // Generate forecast for next 30 days with trend adjustment
    $trend_factor = 1.02; // Assuming 2% growth trend
    for ($i = 1; $i <= $days_ahead; $i++) {
        $date = date('Y-m-d', strtotime("+{$i} days"));
        $forecasted_revenue = $avg_revenue * pow($trend_factor, $i / 30);
        
        $forecast[] = [
            'date' => $date,
            'revenue' => $forecasted_revenue,
            'confidence' => max(90 - ($i * 2), 50) // Decreasing confidence over time
        ];
    }
    
    return $forecast;
}

function getUserBehaviorPrediction($db) {
    // Predict user churn risk based on activity patterns
    $churn_query = "
        SELECT 
            u.id,
            u.username,
            u.email,
            DATEDIFF(NOW(), u.last_login) as days_since_login,
            COUNT(t.id) as total_transactions,
            MAX(t.created_at) as last_transaction,
            COUNT(CASE WHEN DATE(t.created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_transactions,
            CASE 
                WHEN DATEDIFF(NOW(), u.last_login) > 30 THEN 'High Risk'
                WHEN DATEDIFF(NOW(), u.last_login) > 14 THEN 'Medium Risk'
                WHEN DATEDIFF(NOW(), u.last_login) > 7 THEN 'Low Risk'
                ELSE 'Active'
            END as churn_risk
        FROM users u
        LEFT JOIN transaksi t ON u.id = t.user_id
        WHERE u.last_login IS NOT NULL
        GROUP BY u.id
        HAVING days_since_login > 7
        ORDER BY days_since_login DESC
        LIMIT 50
    ";
    
    return $db->query($churn_query)->fetch_all(MYSQLI_ASSOC);
}

function getSeasonalTrends($db) {
    // Analyze seasonal patterns in bookings
    $seasonal_query = "
        SELECT 
            MONTH(created_at) as month,
            YEAR(created_at) as year,
            COUNT(*) as bookings,
            SUM(total_amount) as revenue,
            AVG(total_amount) as avg_order_value
        FROM transaksi 
        WHERE payment_status = 'paid'
        AND DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 2 YEAR)
        GROUP BY YEAR(created_at), MONTH(created_at)
        ORDER BY year, month
    ";
    
    return $db->query($seasonal_query)->fetch_all(MYSQLI_ASSOC);
}

function getProductRecommendations($db) {
    // Recommend products based on co-occurrence patterns
    $recommendation_query = "
        SELECT 
            ci1.item_type as product_a,
            ci2.item_type as product_b,
            COUNT(*) as co_occurrence,
            COUNT(*) * 100.0 / (
                SELECT COUNT(DISTINCT user_id) FROM cart_items 
                WHERE item_type = ci1.item_type
            ) as recommendation_strength
        FROM cart_items ci1
        JOIN cart_items ci2 ON ci1.user_id = ci2.user_id 
        WHERE ci1.item_type != ci2.item_type
        AND ci1.item_type IN ('wisata', 'penginapan', 'umkm')
        AND ci2.item_type IN ('wisata', 'penginapan', 'umkm')
        GROUP BY ci1.item_type, ci2.item_type
        HAVING co_occurrence >= 3
        ORDER BY recommendation_strength DESC
    ";
    
    return $db->query($recommendation_query)->fetch_all(MYSQLI_ASSOC);
}

function getAbandonmentPrediction($db) {
    // Predict cart abandonment risk
    $abandonment_query = "
        SELECT 
            user_id,
            COUNT(*) as cart_items,
            MAX(updated_at) as last_activity,
            TIMESTAMPDIFF(HOUR, MAX(updated_at), NOW()) as hours_since_activity,
            SUM(
                CASE 
                    WHEN item_type = 'wisata' THEN (
                        SELECT harga FROM wisata WHERE id = cart_items.item_id
                    )
                    WHEN item_type = 'penginapan' THEN (
                        SELECT harga FROM penginapan WHERE id = cart_items.item_id
                    )
                    WHEN item_type = 'umkm' THEN (
                        SELECT harga FROM artikel WHERE id = cart_items.item_id
                    )
                    ELSE 0
                END
            ) as cart_value,
            CASE 
                WHEN TIMESTAMPDIFF(HOUR, MAX(updated_at), NOW()) >= 24 THEN 'Abandoned'
                WHEN TIMESTAMPDIFF(HOUR, MAX(updated_at), NOW()) >= 6 THEN 'High Risk'
                WHEN TIMESTAMPDIFF(HOUR, MAX(updated_at), NOW()) >= 2 THEN 'Medium Risk'
                ELSE 'Active'
            END as abandonment_risk
        FROM cart_items
        WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY user_id
        HAVING cart_items > 0
        ORDER BY hours_since_activity DESC
    ";
    
    return $db->query($abandonment_query)->fetch_all(MYSQLI_ASSOC);
}

// Get predictive analytics data
$revenue_forecast = getRevenueForecast($db);
$churn_predictions = getUserBehaviorPrediction($db);
$seasonal_trends = getSeasonalTrends($db);
$product_recommendations = getProductRecommendations($db);
$abandonment_predictions = getAbandonmentPrediction($db);

// Calculate key insights
$total_forecast_revenue = array_sum(array_column($revenue_forecast, 'revenue'));
$high_churn_risk = array_filter($churn_predictions, function($user) {
    return $user['churn_risk'] === 'High Risk';
});
$high_abandonment_risk = array_filter($abandonment_predictions, function($cart) {
    return $cart['abandonment_risk'] === 'High Risk';
});

$db->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predictive Analytics - Papua Journey</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .predictive-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .prediction-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .prediction-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .prediction-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .forecast-icon { background: #DBEAFE; color: #1D4ED8; }
        .churn-icon { background: #FEE2E2; color: #DC2626; }
        .seasonal-icon { background: #D1FAE5; color: #059669; }
        .recommendation-icon { background: #FEF3C7; color: #D97706; }
        .abandonment-icon { background: #E0E7FF; color: #6366F1; }
        
        .insight-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .insight-metric {
            text-align: center;
            padding: 1rem;
            background: #F9FAFB;
            border-radius: 8px;
        }
        
        .insight-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .insight-label {
            color: #6B7280;
            font-size: 0.85rem;
        }
        
        .risk-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .risk-high { background: #FEE2E2; color: #DC2626; }
        .risk-medium { background: #FEF3C7; color: #D97706; }
        .risk-low { background: #D1FAE5; color: #059669; }
        .risk-active { background: #DBEAFE; color: #1D4ED8; }
        
        .chart-container {
            height: 300px;
            margin-top: 1rem;
        }
        
        .recommendation-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .recommendation-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: #F9FAFB;
            border-radius: 8px;
        }
        
        .recommendation-strength {
            background: #10B981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .data-table {
            width: 100%;
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
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="main-content" id="mainContent">
            <?php include 'components/header.php'; ?>
            
            <div class="content-wrapper">
                <!-- Predictive Analytics Header -->
                <div class="predictive-header">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                        🔮 Predictive Analytics
                    </div>
                    <div style="font-size: 1.1rem; opacity: 0.9;">
                        AI-powered insights and forecasting for strategic decision making
                    </div>
                </div>
                
                <!-- Key Insights Summary -->
                <div class="insight-summary">
                    <div class="insight-metric">
                        <div class="insight-value" style="color: #10B981;">
                            Rp <?php echo number_format($total_forecast_revenue, 0, ',', '.'); ?>
                        </div>
                        <div class="insight-label">30-Day Revenue Forecast</div>
                    </div>
                    <div class="insight-metric">
                        <div class="insight-value" style="color: #DC2626;">
                            <?php echo count($high_churn_risk); ?>
                        </div>
                        <div class="insight-label">High Churn Risk Users</div>
                    </div>
                    <div class="insight-metric">
                        <div class="insight-value" style="color: #D97706;">
                            <?php echo count($product_recommendations); ?>
                        </div>
                        <div class="insight-label">Product Associations</div>
                    </div>
                    <div class="insight-metric">
                        <div class="insight-value" style="color: #6366F1;">
                            <?php echo count($high_abandonment_risk); ?>
                        </div>
                        <div class="insight-label">High Abandonment Risk</div>
                    </div>
                </div>
                
                <!-- Revenue Forecast -->
                <div class="prediction-card">
                    <div class="prediction-header">
                        <div class="prediction-icon forecast-icon">📈</div>
                        <div>
                            <h3>Revenue Forecasting</h3>
                            <p style="color: #6B7280; margin: 0;">30-day revenue prediction based on historical trends</p>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="forecastChart"></canvas>
                    </div>
                </div>
                
                <!-- User Churn Prediction -->
                <div class="prediction-card">
                    <div class="prediction-header">
                        <div class="prediction-icon churn-icon">⚠️</div>
                        <div>
                            <h3>User Churn Risk Analysis</h3>
                            <p style="color: #6B7280; margin: 0;">Identify users at risk of churning based on activity patterns</p>
                        </div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Last Login</th>
                                    <th>Total Transactions</th>
                                    <th>Recent Activity</th>
                                    <th>Risk Level</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($churn_predictions, 0, 10) as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong><br>
                                        <small style="color: #6B7280;"><?php echo htmlspecialchars($user['email']); ?></small>
                                    </td>
                                    <td><?php echo $user['days_since_login']; ?> days ago</td>
                                    <td><?php echo $user['total_transactions']; ?></td>
                                    <td><?php echo $user['recent_transactions']; ?> (30 days)</td>
                                    <td>
                                        <span class="risk-badge risk-<?php echo strtolower(str_replace(' ', '-', $user['churn_risk'])); ?>">
                                            <?php echo $user['churn_risk']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="engageUser(<?php echo $user['id']; ?>)">
                                            Re-engage
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Product Recommendations -->
                <div class="prediction-card">
                    <div class="prediction-header">
                        <div class="prediction-icon recommendation-icon">🎯</div>
                        <div>
                            <h3>Product Recommendation Engine</h3>
                            <p style="color: #6B7280; margin: 0;">Products frequently purchased together</p>
                        </div>
                    </div>
                    
                    <div class="recommendation-list">
                        <?php foreach (array_slice($product_recommendations, 0, 8) as $rec): ?>
                        <div class="recommendation-item">
                            <div>
                                <strong><?php echo ucfirst($rec['product_a']); ?></strong> → 
                                <strong><?php echo ucfirst($rec['product_b']); ?></strong>
                                <br>
                                <small style="color: #6B7280;">
                                    <?php echo $rec['co_occurrence']; ?> co-occurrences
                                </small>
                            </div>
                            <div class="recommendation-strength">
                                <?php echo number_format($rec['recommendation_strength'], 1); ?>%
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Cart Abandonment Prediction -->
                <div class="prediction-card">
                    <div class="prediction-header">
                        <div class="prediction-icon abandonment-icon">🛒</div>
                        <div>
                            <h3>Cart Abandonment Risk</h3>
                            <p style="color: #6B7280; margin: 0;">Real-time abandonment risk assessment</p>
                        </div>
                    </div>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Items in Cart</th>
                                    <th>Cart Value</th>
                                    <th>Last Activity</th>
                                    <th>Risk Level</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($abandonment_predictions, 0, 10) as $cart): ?>
                                <tr>
                                    <td>User #<?php echo $cart['user_id']; ?></td>
                                    <td><?php echo $cart['cart_items']; ?> items</td>
                                    <td>Rp <?php echo number_format($cart['cart_value'], 0, ',', '.'); ?></td>
                                    <td><?php echo $cart['hours_since_activity']; ?> hours ago</td>
                                    <td>
                                        <span class="risk-badge risk-<?php echo strtolower(str_replace(' ', '-', $cart['abandonment_risk'])); ?>">
                                            <?php echo $cart['abandonment_risk']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($cart['abandonment_risk'] !== 'Active'): ?>
                                        <button class="btn btn-sm btn-warning" onclick="sendReminder(<?php echo $cart['user_id']; ?>)">
                                            Send Reminder
                                        </button>
                                        <?php else: ?>
                                        <span style="color: #10B981;">Active</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Seasonal Trends -->
                <div class="prediction-card">
                    <div class="prediction-header">
                        <div class="prediction-icon seasonal-icon">📅</div>
                        <div>
                            <h3>Seasonal Trend Analysis</h3>
                            <p style="color: #6B7280; margin: 0;">Historical seasonal patterns for business planning</p>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <canvas id="seasonalChart"></canvas>
                    </div>
                </div>
            </div>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Revenue Forecast Chart
        const forecastCtx = document.getElementById('forecastChart').getContext('2d');
        
        new Chart(forecastCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($revenue_forecast, 'date')); ?>,
                datasets: [{
                    label: 'Forecasted Revenue',
                    data: <?php echo json_encode(array_column($revenue_forecast, 'revenue')); ?>,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    borderDash: [5, 5]
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
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Forecasted: Rp ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Seasonal Trends Chart
        const seasonalCtx = document.getElementById('seasonalChart').getContext('2d');
        
        const seasonalData = <?php echo json_encode($seasonal_trends); ?>;
        const monthlyRevenue = {};
        
        seasonalData.forEach(item => {
            const month = item.month;
            if (!monthlyRevenue[month]) {
                monthlyRevenue[month] = [];
            }
            monthlyRevenue[month].push(parseFloat(item.revenue));
        });
        
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const avgRevenue = months.map((_, index) => {
            const month = index + 1;
            const revenues = monthlyRevenue[month] || [0];
            return revenues.reduce((a, b) => a + b, 0) / revenues.length;
        });
        
        new Chart(seasonalCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Average Monthly Revenue',
                    data: avgRevenue,
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
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Avg Revenue: Rp ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Action functions
        function engageUser(userId) {
            if (confirm('Send re-engagement email to this user?')) {
                showToast('Re-engagement campaign initiated for User #' + userId, 'success');
                // Implement actual re-engagement logic
            }
        }
        
        function sendReminder(userId) {
            if (confirm('Send cart abandonment reminder to this user?')) {
                showToast('Reminder sent to User #' + userId, 'success');
                // Implement actual reminder logic
            }
        }
        
        function showToast(message, type) {
            // Simple toast notification
            const toast = document.createElement('div');
            toast.className = `alert alert-${type}`;
            toast.style.position = 'fixed';
            toast.style.top = '20px';
            toast.style.right = '20px';
            toast.style.zIndex = '9999';
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
</body>
</html>