<?php
// admin/executive_dashboard.php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Executive Dashboard';
$db = getDbConnection();

// Time period selection
$period = $_GET['period'] ?? '30';
$valid_periods = ['7', '30', '90', '365'];
if (!in_array($period, $valid_periods)) {
    $period = '30';
}

// Calculate date ranges for comparison
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime("-{$period} days"));
$prev_end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
$prev_start_date = date('Y-m-d', strtotime($start_date . " -{$period} days"));

// Key Performance Indicators (KPIs)
function getKPIs($db, $start_date, $end_date, $prev_start_date, $prev_end_date) {
    $kpis = [];
    
    // Revenue KPIs
    $revenue_query = "
        SELECT 
            SUM(CASE WHEN t.payment_status = 'paid' AND DATE(t.created_at) BETWEEN ? AND ? THEN t.total_amount ELSE 0 END) as current_revenue,
            SUM(CASE WHEN t.payment_status = 'paid' AND DATE(t.created_at) BETWEEN ? AND ? THEN t.total_amount ELSE 0 END) as prev_revenue,
            COUNT(CASE WHEN t.payment_status = 'paid' AND DATE(t.created_at) BETWEEN ? AND ? THEN 1 END) as current_transactions,
            COUNT(CASE WHEN t.payment_status = 'paid' AND DATE(t.created_at) BETWEEN ? AND ? THEN 1 END) as prev_transactions
        FROM transaksi t
    ";
    
    $stmt = $db->prepare($revenue_query);
    $stmt->bind_param("ssssssss", $start_date, $end_date, $prev_start_date, $prev_end_date, 
                                  $start_date, $end_date, $prev_start_date, $prev_end_date);
    $stmt->execute();
    $revenue_data = $stmt->get_result()->fetch_assoc();
    
    $kpis['revenue'] = [
        'current' => $revenue_data['current_revenue'] ?? 0,
        'previous' => $revenue_data['prev_revenue'] ?? 0,
        'growth' => calculateGrowthRate($revenue_data['prev_revenue'], $revenue_data['current_revenue'])
    ];
    
    $kpis['transactions'] = [
        'current' => $revenue_data['current_transactions'] ?? 0,
        'previous' => $revenue_data['prev_transactions'] ?? 0,
        'growth' => calculateGrowthRate($revenue_data['prev_transactions'], $revenue_data['current_transactions'])
    ];
    
    // User engagement KPIs
    $engagement_query = "
        SELECT 
            COUNT(DISTINCT CASE WHEN DATE(created_at) BETWEEN ? AND ? THEN id END) as current_new_users,
            COUNT(DISTINCT CASE WHEN DATE(created_at) BETWEEN ? AND ? THEN id END) as prev_new_users,
            COUNT(DISTINCT CASE WHEN DATE(last_login) BETWEEN ? AND ? THEN id END) as current_active_users,
            COUNT(DISTINCT CASE WHEN DATE(last_login) BETWEEN ? AND ? THEN id END) as prev_active_users
        FROM users
    ";
    
    $stmt = $db->prepare($engagement_query);
    $stmt->bind_param("ssssssss", $start_date, $end_date, $prev_start_date, $prev_end_date,
                                  $start_date, $end_date, $prev_start_date, $prev_end_date);
    $stmt->execute();
    $engagement_data = $stmt->get_result()->fetch_assoc();
    
    $kpis['new_users'] = [
        'current' => $engagement_data['current_new_users'] ?? 0,
        'previous' => $engagement_data['prev_new_users'] ?? 0,
        'growth' => calculateGrowthRate($engagement_data['prev_new_users'], $engagement_data['current_new_users'])
    ];
    
    $kpis['active_users'] = [
        'current' => $engagement_data['current_active_users'] ?? 0,
        'previous' => $engagement_data['prev_active_users'] ?? 0,
        'growth' => calculateGrowthRate($engagement_data['prev_active_users'], $engagement_data['current_active_users'])
    ];
    
    // Conversion rates
    $cart_query = "
        SELECT 
            COUNT(DISTINCT user_id) as total_carts,
            COUNT(DISTINCT CASE WHEN EXISTS(
                SELECT 1 FROM transaksi t 
                WHERE t.user_id = cart_items.user_id 
                AND t.payment_status = 'paid'
                AND DATE(t.created_at) BETWEEN ? AND ?
            ) THEN user_id END) as converted_carts
        FROM cart_items 
        WHERE DATE(updated_at) BETWEEN ? AND ?
    ";
    
    $stmt = $db->prepare($cart_query);
    $stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $cart_data = $stmt->get_result()->fetch_assoc();
    
    $kpis['conversion_rate'] = [
        'current' => $cart_data['total_carts'] > 0 ? 
            round(($cart_data['converted_carts'] / $cart_data['total_carts']) * 100, 2) : 0,
        'previous' => 0, // Calculate if needed
        'growth' => 0
    ];
    
    return $kpis;
}

function calculateGrowthRate($previous, $current) {
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}

// Get business metrics
function getBusinessMetrics($db, $start_date, $end_date) {
    $metrics = [];
    
    // Tourism performance
    $tourism_query = "
        SELECT 
            COUNT(DISTINCT w.id) as total_destinations,
            COALESCE(SUM(wv.view_count), 0) as total_views,
            COALESCE(AVG(wv.view_count), 0) as avg_views_per_destination
        FROM wisata w
        LEFT JOIN (
            SELECT wisata_id, COUNT(*) as view_count
            FROM wisata_views 
            WHERE DATE(view_date) BETWEEN ? AND ?
            GROUP BY wisata_id
        ) wv ON w.id = wv.wisata_id
    ";
    
    $stmt = $db->prepare($tourism_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $metrics['tourism'] = $stmt->get_result()->fetch_assoc();
    
    // Accommodation performance
    $accommodation_query = "
        SELECT 
            COUNT(DISTINCT p.id) as total_accommodations,
            COALESCE(SUM(pv.view_count), 0) as total_views,
            COALESCE(AVG(pv.view_count), 0) as avg_views_per_accommodation
        FROM penginapan p
        LEFT JOIN (
            SELECT penginapan_id, COUNT(*) as view_count
            FROM penginapan_views 
            WHERE DATE(view_date) BETWEEN ? AND ?
            GROUP BY penginapan_id
        ) pv ON p.id = pv.penginapan_id
    ";
    
    $stmt = $db->prepare($accommodation_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $metrics['accommodation'] = $stmt->get_result()->fetch_assoc();
    
    // UMKM performance
    $umkm_query = "
        SELECT 
            COUNT(*) as total_umkm,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_umkm,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_umkm,
            SUM(CASE WHEN DATE(created_at) BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_registrations
        FROM umkm
    ";
    
    $stmt = $db->prepare($umkm_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $metrics['umkm'] = $stmt->get_result()->fetch_assoc();
    
    return $metrics;
}

// Get trend data for charts
function getTrendData($db, $start_date, $end_date) {
    $trends = [];
    
    // Daily revenue trend
    $revenue_trend_query = "
        SELECT 
            DATE(created_at) as date,
            SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as revenue,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as transactions
        FROM transaksi 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ";
    
    $stmt = $db->prepare($revenue_trend_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trends['revenue'] = [];
    $trends['transactions'] = [];
    $trends['dates'] = [];
    
    while ($row = $result->fetch_assoc()) {
        $trends['dates'][] = date('M j', strtotime($row['date']));
        $trends['revenue'][] = floatval($row['revenue']);
        $trends['transactions'][] = intval($row['transactions']);
    }
    
    return $trends;
}

$kpis = getKPIs($db, $start_date, $end_date, $prev_start_date, $prev_end_date);
$metrics = getBusinessMetrics($db, $start_date, $end_date);
$trends = getTrendData($db, $start_date, $end_date);

$db->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Dashboard - Papua Journey</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .executive-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .executive-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .executive-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .period-selector {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .period-btn {
            padding: 0.5rem 1rem;
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 6px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .period-btn.active,
        .period-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .kpi-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-left: 4px solid;
        }
        
        .kpi-card.revenue { border-left-color: #10B981; }
        .kpi-card.transactions { border-left-color: #3B82F6; }
        .kpi-card.users { border-left-color: #8B5CF6; }
        .kpi-card.conversion { border-left-color: #F59E0B; }
        
        .kpi-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .kpi-label {
            color: #6B7280;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .kpi-growth {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }
        
        .growth-positive { color: #10B981; }
        .growth-negative { color: #EF4444; }
        .growth-neutral { color: #6B7280; }
        
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            height: 400px;
        }
        
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .insight-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .insight-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .insight-icon.tourism { background: #DBEAFE; color: #1D4ED8; }
        .insight-icon.accommodation { background: #FEF3C7; color: #D97706; }
        .insight-icon.umkm { background: #E0E7FF; color: #6366F1; }
        
        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #F3F4F6;
        }
        
        .metric-row:last-child {
            border-bottom: none;
        }
        
        .metric-value {
            font-weight: 600;
            color: #1F2937;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="main-content" id="mainContent">
            <?php include 'components/header.php'; ?>
            
            <div class="content-wrapper">
                <!-- Executive Header -->
                <div class="executive-header">
                    <div class="executive-title">📊 Executive Dashboard</div>
                    <div class="executive-subtitle">
                        Strategic insights and key performance indicators for Papua Journey
                    </div>
                    
                    <div class="period-selector">
                        <button class="period-btn <?php echo $period == '7' ? 'active' : ''; ?>" 
                                onclick="changePeriod('7')">7 Days</button>
                        <button class="period-btn <?php echo $period == '30' ? 'active' : ''; ?>" 
                                onclick="changePeriod('30')">30 Days</button>
                        <button class="period-btn <?php echo $period == '90' ? 'active' : ''; ?>" 
                                onclick="changePeriod('90')">90 Days</button>
                        <button class="period-btn <?php echo $period == '365' ? 'active' : ''; ?>" 
                                onclick="changePeriod('365')">1 Year</button>
                    </div>
                </div>
                
                <!-- Key Performance Indicators -->
                <div class="kpi-grid">
                    <div class="kpi-card revenue">
                        <div class="kpi-value">Rp <?php echo number_format($kpis['revenue']['current'], 0, ',', '.'); ?></div>
                        <div class="kpi-label">Total Revenue</div>
                        <div class="kpi-growth">
                            <span class="<?php echo $kpis['revenue']['growth'] >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                <?php echo $kpis['revenue']['growth'] >= 0 ? '↗' : '↘'; ?> 
                                <?php echo abs($kpis['revenue']['growth']); ?>%
                            </span>
                            <span>vs previous period</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card transactions">
                        <div class="kpi-value"><?php echo number_format($kpis['transactions']['current']); ?></div>
                        <div class="kpi-label">Successful Transactions</div>
                        <div class="kpi-growth">
                            <span class="<?php echo $kpis['transactions']['growth'] >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                <?php echo $kpis['transactions']['growth'] >= 0 ? '↗' : '↘'; ?> 
                                <?php echo abs($kpis['transactions']['growth']); ?>%
                            </span>
                            <span>vs previous period</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card users">
                        <div class="kpi-value"><?php echo number_format($kpis['new_users']['current']); ?></div>
                        <div class="kpi-label">New Users</div>
                        <div class="kpi-growth">
                            <span class="<?php echo $kpis['new_users']['growth'] >= 0 ? 'growth-positive' : 'growth-negative'; ?>">
                                <?php echo $kpis['new_users']['growth'] >= 0 ? '↗' : '↘'; ?> 
                                <?php echo abs($kpis['new_users']['growth']); ?>%
                            </span>
                            <span>vs previous period</span>
                        </div>
                    </div>
                    
                    <div class="kpi-card conversion">
                        <div class="kpi-value"><?php echo $kpis['conversion_rate']['current']; ?>%</div>
                        <div class="kpi-label">Conversion Rate</div>
                        <div class="kpi-growth">
                            <span class="growth-neutral">
                                📈 Cart to Purchase
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Revenue Trend Chart -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h3 class="card-title">📈 Revenue & Transaction Trends</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="revenueTrendChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Business Insights -->
                <div class="insights-grid">
                    <!-- Tourism Insights -->
                    <div class="insight-card">
                        <div class="insight-icon tourism">🏖️</div>
                        <h3 style="margin-bottom: 1rem;">Tourism Performance</h3>
                        
                        <div class="metric-row">
                            <span>Total Destinations</span>
                            <span class="metric-value"><?php echo number_format($metrics['tourism']['total_destinations']); ?></span>
                        </div>
                        <div class="metric-row">
                            <span>Total Views</span>
                            <span class="metric-value"><?php echo number_format($metrics['tourism']['total_views']); ?></span>
                        </div>
                        <div class="metric-row">
                            <span>Avg Views/Destination</span>
                            <span class="metric-value"><?php echo number_format($metrics['tourism']['avg_views_per_destination'], 1); ?></span>
                        </div>
                    </div>
                    
                    <!-- Accommodation Insights -->
                    <div class="insight-card">
                        <div class="insight-icon accommodation">🏨</div>
                        <h3 style="margin-bottom: 1rem;">Accommodation Performance</h3>
                        
                        <div class="metric-row">
                            <span>Total Accommodations</span>
                            <span class="metric-value"><?php echo number_format($metrics['accommodation']['total_accommodations']); ?></span>
                        </div>
                        <div class="metric-row">
                            <span>Total Views</span>
                            <span class="metric-value"><?php echo number_format($metrics['accommodation']['total_views']); ?></span>
                        </div>
                        <div class="metric-row">
                            <span>Avg Views/Accommodation</span>
                            <span class="metric-value"><?php echo number_format($metrics['accommodation']['avg_views_per_accommodation'], 1); ?></span>
                        </div>
                    </div>
                    
                    <!-- UMKM Insights -->
                    <div class="insight-card">
                        <div class="insight-icon umkm">🏪</div>
                        <h3 style="margin-bottom: 1rem;">UMKM Performance</h3>
                        
                        <div class="metric-row">
                            <span>Total UMKM</span>
                            <span class="metric-value"><?php echo number_format($metrics['umkm']['total_umkm']); ?></span>
                        </div>
                        <div class="metric-row">
                            <span>Active UMKM</span>
                            <span class="metric-value"><?php echo number_format($metrics['umkm']['active_umkm']); ?></span>
                        </div>
                        <div class="metric-row">
                            <span>New Registrations</span>
                            <span class="metric-value"><?php echo number_format($metrics['umkm']['new_registrations']); ?></span>
                        </div>
                        <div class="metric-row">
                            <span>Pending Approval</span>
                            <span class="metric-value"><?php echo number_format($metrics['umkm']['pending_umkm']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Period selector
        function changePeriod(period) {
            window.location.href = `executive_dashboard.php?period=${period}`;
        }
        
        // Initialize revenue trend chart
        const ctx = document.getElementById('revenueTrendChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trends['dates']); ?>,
                datasets: [{
                    label: 'Revenue (Rp)',
                    data: <?php echo json_encode($trends['revenue']); ?>,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'Transactions',
                    data: <?php echo json_encode($trends['transactions']); ?>,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Revenue (Rp)'
                        },
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString();
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Transactions'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.datasetIndex === 0) {
                                    label += 'Rp ' + context.parsed.y.toLocaleString();
                                } else {
                                    label += context.parsed.y.toLocaleString();
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>