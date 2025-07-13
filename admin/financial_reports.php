<?php
session_start();
require_once '../config/database.php';
require_once 'helpers/FinancialReportsHelper.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();
$helper = new FinancialReportsHelper($conn);

// Get date range from query parameters or default to last 30 days
$end_date = date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $end_date;

// Fetch overview statistics using helper
$overview = $helper->getFinancialOverview($start_date, $end_date);

// Calculate growth rate (compare with previous period)
$prev_end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
$prev_start_date = date('Y-m-d', strtotime($start_date . ' -' . (strtotime($end_date) - strtotime($start_date)) / 86400 . ' days'));

$prev_data = $helper->getPreviousPeriodRevenue($prev_start_date, $prev_end_date);
$growth_rate = $helper->calculateGrowthRate($overview['total_revenue'], $prev_data['prev_revenue']);

// Fetch consolidated analytics data (product types, top products, daily revenue)
$analytics = $helper->getAnalyticsData($start_date, $end_date);
$product_types = $analytics['product_types'];
$top_products = $analytics['top_products'];
$daily_revenue = $analytics['daily_revenue'];

// Fetch UMKM revenue using optimized helper method
$umkm_revenue = $helper->getUMKMRevenue($start_date, $end_date);

// Fetch payment method distribution using helper
$payment_methods = $helper->getPaymentMethodsDistribution($start_date, $end_date);

// Daily revenue already fetched in analytics consolidation above

$conn->close();

// Set page title for header
$page_title = 'Financial Reports';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - Admin Dashboard</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="sidebar.css">
    <!-- Modular CSS Architecture -->
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/summary.css">
    <link rel="stylesheet" href="css/recommendations.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" 
          rel="stylesheet" 
          integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" 
          crossorigin="anonymous"
          referrerpolicy="no-referrer">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="main-content" id="mainContent">
            <?php include 'components/header.php'; ?>
        <!-- Enhanced Page Header -->
        <div class="page-header">
            <div class="page-header-content">
                <div class="page-header-top">
                    <div class="page-title-section">
                        <h1>Financial Reports</h1>
                        <p class="page-description">Monitor revenue, transactions, and financial performance</p>
                    </div>
                    <div class="header-actions">
                        <a href="export_financial_report.php?type=csv&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="export-btn">
                            <i class="fas fa-file-csv"></i> Export CSV
                        </a>
                        <a href="export_financial_report.php?type=pdf&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="export-btn">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>
                <div class="date-range-display">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo date('M d, Y', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?></span>
                </div>
            </div>
        </div>
        
        <div class="content-wrapper">
            <!-- Enhanced Filter Section -->
            <div class="filter-section">
                <div class="filter-header">
                    <h3 style="margin: 0; color: #2d3748;">Filter Data</h3>
                    <button class="filter-toggle" onclick="toggleFilter()">
                        <i class="fas fa-filter"></i> 
                        <span id="filter-text">Hide Filters</span>
                    </button>
                </div>
                <div class="filter-content" id="filter-content">
                    <form method="GET" action="" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; width: 100%;">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Apply Filter
                        </button>
                    </form>
                </div>
            </div>

            <?php
            // Calculate success rate using helper with validation
            $success_rate = $helper->calculateSuccessRate(
                $overview['successful_transactions'], 
                $overview['total_transactions']
            );
            ?>

            <!-- Enhanced KPI Cards -->
            <div class="kpi-section">
                <div class="kpi-grid">
                    <div class="kpi-card <?php echo $growth_rate >= 0 ? 'success' : 'danger'; ?>">
                        <div class="kpi-header">
                            <div>
                                <div class="kpi-title">Total Revenue</div>
                                <div class="kpi-value">Rp <?php echo number_format($overview['total_revenue'] ?? 0, 0, ',', '.'); ?></div>
                                <div class="kpi-change <?php echo $growth_rate >= 0 ? 'positive' : 'negative'; ?>">
                                    <i class="fas fa-<?php echo $growth_rate >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                    <?php echo abs(round($growth_rate, 1)); ?>% from previous period
                                </div>
                            </div>
                            <div class="kpi-icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-header">
                            <div>
                                <div class="kpi-title">Total Transactions</div>
                                <div class="kpi-value"><?php echo number_format($overview['total_transactions'] ?? 0); ?></div>
                                <div class="kpi-change">
                                    <i class="fas fa-check-circle" style="color: #48bb78;"></i>
                                    <?php echo number_format($overview['successful_transactions'] ?? 0); ?> successful
                                </div>
                            </div>
                            <div class="kpi-icon" style="background: var(--info-gradient);">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="kpi-card warning">
                        <div class="kpi-header">
                            <div>
                                <div class="kpi-title">Average Order Value</div>
                                <div class="kpi-value">Rp <?php echo number_format($overview['avg_order_value'] ?? 0, 0, ',', '.'); ?></div>
                                <div class="kpi-change">Per transaction</div>
                            </div>
                            <div class="kpi-icon">
                                <i class="fas fa-coins"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="kpi-card <?php echo $success_rate >= 90 ? 'success' : ($success_rate >= 70 ? 'warning' : 'danger'); ?>">
                        <div class="kpi-header">
                            <div>
                                <div class="kpi-title">Success Rate</div>
                                <div class="kpi-value"><?php echo $success_rate; ?>%</div>
                                <div class="kpi-change">
                                    <i class="fas fa-times-circle" style="color: #f56565;"></i>
                                    <?php echo number_format($overview['failed_transactions'] ?? 0); ?> failed
                                </div>
                            </div>
                            <div class="kpi-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="summary-section">
                <h2 class="section-title">Financial Summary</h2>
                <div class="summary-content">
                    <div class="summary-cards">
                        <div class="summary-card primary">
                            <div class="summary-header">
                                <h3>Overall Performance</h3>
                                <i class="fas fa-chart-pie"></i>
                            </div>
                            <div class="summary-body">
                                <?php
                                $performance_status = 'excellent';
                                $performance_message = '';
                                
                                if ($success_rate >= 90) {
                                    $performance_status = 'excellent';
                                    $performance_message = 'Outstanding performance with high success rate and steady revenue growth.';
                                } elseif ($success_rate >= 75) {
                                    $performance_status = 'good';
                                    $performance_message = 'Good performance with room for improvement in transaction success rates.';
                                } elseif ($success_rate >= 60) {
                                    $performance_status = 'fair';
                                    $performance_message = 'Fair performance. Focus needed on reducing failed transactions.';
                                } else {
                                    $performance_status = 'needs-attention';
                                    $performance_message = 'Performance needs immediate attention. High failure rate detected.';
                                }
                                ?>
                                <div class="performance-indicator <?php echo $performance_status; ?>">
                                    <span class="indicator-dot"></span>
                                    <?php echo ucfirst(str_replace('-', ' ', $performance_status)); ?>
                                </div>
                                <p><?php echo $performance_message; ?></p>
                                <div class="quick-stats">
                                    <div class="stat">
                                        <span class="stat-label">Success Rate:</span>
                                        <span class="stat-value"><?php echo $success_rate; ?>%</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-label">Growth:</span>
                                        <span class="stat-value <?php echo $growth_rate >= 0 ? 'positive' : 'negative'; ?>">
                                            <?php echo ($growth_rate >= 0 ? '+' : '') . round($growth_rate, 1); ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="summary-card info">
                            <div class="summary-header">
                                <h3>Revenue Insights</h3>
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="summary-body">
                                <?php
                                $total_revenue = $overview['total_revenue'] ?? 0;
                                $avg_daily_revenue = count($daily_revenue) > 0 ? $total_revenue / count($daily_revenue) : 0;
                                $best_product_type = '';
                                $max_revenue = 0;
                                
                                // Safe iteration with validation
                                if (is_array($product_types) && !empty($product_types)) {
                                    foreach ($product_types as $type) {
                                        if (isset($type['revenue']) && is_numeric($type['revenue']) && $type['revenue'] > $max_revenue) {
                                            $max_revenue = $type['revenue'];
                                            $best_product_type = $type['item_type'] ?? 'unknown';
                                        }
                                    }
                                }
                                ?>
                                <div class="insight-item">
                                    <strong>Average Daily Revenue:</strong>
                                    <span>Rp <?php echo number_format($avg_daily_revenue, 0, ',', '.'); ?></span>
                                </div>
                                <div class="insight-item">
                                    <strong>Top Performing Category:</strong>
                                    <span class="badge badge-<?php echo $best_product_type; ?>">
                                        <?php echo ucfirst($best_product_type); ?>
                                    </span>
                                </div>
                                <div class="insight-item">
                                    <strong>Revenue Distribution:</strong>
                                    <span><?php echo count($product_types); ?> active product categories</span>
                                </div>
                            </div>
                        </div>

                        <div class="summary-card warning">
                            <div class="summary-header">
                                <h3>Key Highlights</h3>
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="summary-body">
                                <ul class="highlights-list">
                                    <?php if ($growth_rate > 10): ?>
                                    <li class="highlight-positive">
                                        <i class="fas fa-arrow-up"></i>
                                        Strong revenue growth of <?php echo round($growth_rate, 1); ?>%
                                    </li>
                                    <?php elseif ($growth_rate < -10): ?>
                                    <li class="highlight-negative">
                                        <i class="fas fa-arrow-down"></i>
                                        Revenue decline of <?php echo abs(round($growth_rate, 1)); ?>% needs attention
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if ($success_rate >= 90): ?>
                                    <li class="highlight-positive">
                                        <i class="fas fa-check-circle"></i>
                                        Excellent transaction success rate
                                    </li>
                                    <?php elseif ($success_rate < 70): ?>
                                    <li class="highlight-negative">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        High transaction failure rate detected
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($top_products)): ?>
                                    <li class="highlight-neutral">
                                        <i class="fas fa-trophy"></i>
                                        Top product: <?php echo htmlspecialchars($top_products[0]['item_name']); ?>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php if (count($umkm_revenue) > 0): ?>
                                    <li class="highlight-neutral">
                                        <i class="fas fa-store"></i>
                                        <?php echo count($umkm_revenue); ?> active UMKM partners contributing revenue
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Charts Section -->
            <div class="charts-section">
                <h2 class="section-title">Revenue Analytics</h2>
                
                <!-- Main Revenue Chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">Revenue Trend</h3>
                        <i class="fas fa-info-circle chart-info" title="Daily revenue over selected period"></i>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Secondary Charts Grid -->
                <div class="charts-row">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3 class="chart-title">Revenue by Product Type</h3>
                            <i class="fas fa-info-circle chart-info" title="Revenue distribution across product categories"></i>
                        </div>
                        <div class="chart-container small">
                            <canvas id="productTypeChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <div class="chart-header">
                            <h3 class="chart-title">Payment Methods</h3>
                            <i class="fas fa-info-circle chart-info" title="Payment method usage distribution"></i>
                        </div>
                        <div class="chart-container small">
                            <canvas id="paymentMethodChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Tables Section -->
            <div class="tables-section">
                <h2 class="section-title">Detailed Reports</h2>
                
                <!-- Top Products Table -->
                <div class="table-card">
                    <div class="table-header">
                        <h3 class="table-title">Top Revenue Products</h3>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Type</th>
                                    <th>Sales Count</th>
                                    <th>Total Quantity</th>
                                    <th>Average Price</th>
                                    <th>Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?php echo htmlspecialchars($product['item_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $product['item_type']; ?>">
                                            <?php echo ucfirst($product['item_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($product['sales_count']); ?></td>
                                    <td><?php echo number_format($product['total_quantity']); ?></td>
                                    <td>Rp <?php echo number_format($product['avg_price'], 0, ',', '.'); ?></td>
                                    <td>
                                        <strong style="color: #2d3748; font-size: 1rem;">
                                            Rp <?php echo number_format($product['total_revenue'], 0, ',', '.'); ?>
                                        </strong>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- UMKM Revenue Table -->
                <div class="table-card">
                    <div class="table-header">
                        <h3 class="table-title">Top UMKM by Revenue</h3>
                    </div>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>UMKM Name</th>
                                    <th>Transaction Count</th>
                                    <th>Total Revenue</th>
                                    <th>Revenue Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_umkm_revenue = $helper->safeArraySum($umkm_revenue, 'total_revenue');
                                foreach ($umkm_revenue as $umkm): 
                                    $share = $total_umkm_revenue > 0 ? ($umkm['total_revenue'] / $total_umkm_revenue) * 100 : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 500;">
                                            <?php echo htmlspecialchars($umkm['nama_umkm']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="color: #718096;">
                                            <?php echo number_format($umkm['transaction_count']); ?> orders
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: #2d3748; font-size: 1rem;">
                                            Rp <?php echo number_format($umkm['total_revenue'], 0, ',', '.'); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <div class="progress-cell">
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $share; ?>%;"></div>
                                            </div>
                                            <span style="min-width: 45px; text-align: right; font-weight: 500;">
                                                <?php echo round($share, 1); ?>%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($umkm_revenue)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #718096; padding: 2rem;">
                                        No UMKM revenue data available for the selected period
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recommendations Section -->
            <div class="recommendations-section">
                <h2 class="section-title">Strategic Recommendations</h2>
                <div class="recommendations-content">
                    <div class="recommendations-grid">
                        
                        <!-- Performance Recommendations -->
                        <div class="recommendation-card priority-high">
                            <div class="recommendation-header">
                                <div class="priority-indicator high">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span>High Priority</span>
                                </div>
                                <h3>Performance Optimization</h3>
                            </div>
                            <div class="recommendation-body">
                                <?php if ($success_rate < 80): ?>
                                <div class="recommendation-item urgent">
                                    <i class="fas fa-chart-line"></i>
                                    <div>
                                        <strong>Improve Transaction Success Rate</strong>
                                        <p>Current success rate of <?php echo $success_rate; ?>% is below optimal. Consider reviewing payment processes and user experience.</p>
                                        <div class="action-steps">
                                            <span class="action-title">Action Steps:</span>
                                            <ul>
                                                <li>Analyze failed transaction patterns</li>
                                                <li>Optimize payment gateway integration</li>
                                                <li>Improve checkout flow user experience</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($growth_rate < 0): ?>
                                <div class="recommendation-item urgent">
                                    <i class="fas fa-arrow-down"></i>
                                    <div>
                                        <strong>Address Revenue Decline</strong>
                                        <p>Revenue has decreased by <?php echo abs(round($growth_rate, 1)); ?>% compared to the previous period.</p>
                                        <div class="action-steps">
                                            <span class="action-title">Action Steps:</span>
                                            <ul>
                                                <li>Analyze market trends and customer behavior</li>
                                                <li>Review pricing strategy</li>
                                                <li>Enhance marketing and promotional efforts</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($success_rate >= 80 && $growth_rate >= 0): ?>
                                <div class="recommendation-item positive">
                                    <i class="fas fa-thumbs-up"></i>
                                    <div>
                                        <strong>Maintain Current Performance</strong>
                                        <p>Your financial performance is on track. Continue current strategies while exploring growth opportunities.</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Revenue Growth Recommendations -->
                        <div class="recommendation-card priority-medium">
                            <div class="recommendation-header">
                                <div class="priority-indicator medium">
                                    <i class="fas fa-chart-bar"></i>
                                    <span>Medium Priority</span>
                                </div>
                                <h3>Revenue Growth</h3>
                            </div>
                            <div class="recommendation-body">
                                <?php if (!empty($product_types)): ?>
                                <div class="recommendation-item">
                                    <i class="fas fa-rocket"></i>
                                    <div>
                                        <strong>Expand High-Performing Categories</strong>
                                        <p>Focus on promoting and expanding your top-performing product category: <strong><?php echo ucfirst($best_product_type); ?></strong></p>
                                        <div class="action-steps">
                                            <span class="action-title">Suggestions:</span>
                                            <ul>
                                                <li>Increase inventory for popular products</li>
                                                <li>Create targeted marketing campaigns</li>
                                                <li>Partner with more UMKM in successful categories</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php 
                                $avg_order_value = $overview['avg_order_value'] ?? 0;
                                if ($avg_order_value < 100000): // Less than 100k IDR
                                ?>
                                <div class="recommendation-item">
                                    <i class="fas fa-arrow-up"></i>
                                    <div>
                                        <strong>Increase Average Order Value</strong>
                                        <p>Current AOV is Rp <?php echo number_format($avg_order_value, 0, ',', '.'); ?>. Consider upselling strategies.</p>
                                        <div class="action-steps">
                                            <span class="action-title">Strategies:</span>
                                            <ul>
                                                <li>Implement bundle offers</li>
                                                <li>Add "Frequently Bought Together" suggestions</li>
                                                <li>Offer volume discounts</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- UMKM & Partnership Recommendations -->
                        <div class="recommendation-card priority-low">
                            <div class="recommendation-header">
                                <div class="priority-indicator low">
                                    <i class="fas fa-handshake"></i>
                                    <span>Strategic</span>
                                </div>
                                <h3>UMKM & Partnerships</h3>
                            </div>
                            <div class="recommendation-body">
                                <?php if (count($umkm_revenue) < 5): ?>
                                <div class="recommendation-item">
                                    <i class="fas fa-users"></i>
                                    <div>
                                        <strong>Expand UMKM Network</strong>
                                        <p>Currently working with <?php echo count($umkm_revenue); ?> active UMKM partners. Consider expanding the network.</p>
                                        <div class="action-steps">
                                            <span class="action-title">Growth Ideas:</span>
                                            <ul>
                                                <li>Recruit more local UMKM partners</li>
                                                <li>Provide better onboarding and support</li>
                                                <li>Create incentive programs for top performers</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($umkm_revenue)): ?>
                                <div class="recommendation-item">
                                    <i class="fas fa-award"></i>
                                    <div>
                                        <strong>Support Top Performers</strong>
                                        <p>Top UMKM: <strong><?php echo htmlspecialchars($umkm_revenue[0]['nama_umkm']); ?></strong> with <?php echo $umkm_revenue[0]['transaction_count']; ?> transactions.</p>
                                        <div class="action-steps">
                                            <span class="action-title">Support Actions:</span>
                                            <ul>
                                                <li>Provide marketing assistance to top performers</li>
                                                <li>Create case studies for successful partnerships</li>
                                                <li>Offer exclusive promotional opportunities</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Operational Recommendations -->
                        <div class="recommendation-card priority-medium">
                            <div class="recommendation-header">
                                <div class="priority-indicator medium">
                                    <i class="fas fa-cogs"></i>
                                    <span>Operational</span>
                                </div>
                                <h3>Operational Excellence</h3>
                            </div>
                            <div class="recommendation-body">
                                <div class="recommendation-item">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <strong>Monitor Key Metrics</strong>
                                        <p>Set up regular monitoring and alerts for critical performance indicators.</p>
                                        <div class="action-steps">
                                            <span class="action-title">Implementation:</span>
                                            <ul>
                                                <li>Weekly revenue and transaction reviews</li>
                                                <li>Monthly UMKM performance assessments</li>
                                                <li>Quarterly strategic planning sessions</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="recommendation-item">
                                    <i class="fas fa-database"></i>
                                    <div>
                                        <strong>Data-Driven Decisions</strong>
                                        <p>Leverage the financial reports data for strategic planning and operational improvements.</p>
                                        <div class="action-steps">
                                            <span class="action-title">Best Practices:</span>
                                            <ul>
                                                <li>Regular export and analysis of financial data</li>
                                                <li>Trend analysis for seasonal planning</li>
                                                <li>Customer behavior pattern identification</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Action Panel -->
                    <div class="quick-actions-panel">
                        <h3>Quick Actions</h3>
                        <div class="quick-actions-grid">
                            <a href="payment_confirmation.php" class="quick-action-btn">
                                <i class="fas fa-credit-card"></i>
                                <span>Review Payments</span>
                            </a>
                            <a href="export_financial_report.php?type=csv&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="quick-action-btn">
                                <i class="fas fa-download"></i>
                                <span>Export Data</span>
                            </a>
                            <a href="wisata_analytics.php" class="quick-action-btn">
                                <i class="fas fa-chart-area"></i>
                                <span>View Analytics</span>
                            </a>
                            <a href="#" onclick="window.print()" class="quick-action-btn">
                                <i class="fas fa-print"></i>
                                <span>Print Report</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        // Enhanced Chart Configuration
        Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
        
        // Prepare data for charts
        const dailyRevenue = <?php echo json_encode($daily_revenue); ?>;
        const productTypes = <?php echo json_encode($product_types); ?>;
        const paymentMethods = <?php echo json_encode($payment_methods); ?>;

        // UI Design System color palette
        const colors = {
            primary: ['#F9B705', '#FFC82C'],
            success: ['#363c2b', '#4a5238'],
            info: ['#FFC82C', '#FFE082'],
            warning: ['#F9B705', '#FFD54F'],
            danger: ['#f093fb', '#f5576c'],
            chart: [
                '#F9B705',
                '#FFC82C',
                '#FFE082',
                '#FFD54F',
                '#FFEB9C',
                '#FFF4C4'
            ]
        };

        // Revenue Trend Chart with gradient
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const gradient = revenueCtx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(249, 183, 5, 0.3)');
        gradient.addColorStop(1, 'rgba(249, 183, 5, 0.01)');

        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: dailyRevenue.map(d => {
                    const date = new Date(d.date);
                    return date.toLocaleDateString('id-ID', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Daily Revenue',
                    data: dailyRevenue.map(d => d.revenue),
                    borderColor: '#F9B705',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#F9B705',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: {
                            size: 14,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            color: '#718096'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            color: '#718096',
                            callback: function(value) {
                                if (value >= 1000000) {
                                    return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                                } else if (value >= 1000) {
                                    return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                                }
                                return 'Rp ' + value;
                            }
                        }
                    }
                }
            }
        });

        // Product Type Chart with enhanced colors
        const productTypeCtx = document.getElementById('productTypeChart').getContext('2d');
        new Chart(productTypeCtx, {
            type: 'doughnut',
            data: {
                labels: productTypes.map(p => p.item_type.charAt(0).toUpperCase() + p.item_type.slice(1)),
                datasets: [{
                    data: productTypes.map(p => p.revenue),
                    backgroundColor: colors.chart,
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 13
                            },
                            color: '#4a5568'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': Rp ' + context.parsed.toLocaleString('id-ID') + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });

        // Payment Method Chart with gradient bars
        const paymentMethodCtx = document.getElementById('paymentMethodChart').getContext('2d');
        new Chart(paymentMethodCtx, {
            type: 'bar',
            data: {
                labels: paymentMethods.map(p => p.payment_method || 'Not Specified'),
                datasets: [{
                    label: 'Transactions',
                    data: paymentMethods.map(p => p.count),
                    backgroundColor: '#F9B705',
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return 'Transactions: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            color: '#718096'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            },
                            color: '#718096'
                        }
                    }
                }
            }
        });

        // Filter toggle function
        function toggleFilter() {
            const filterContent = document.getElementById('filter-content');
            const filterText = document.getElementById('filter-text');
            
            if (filterContent.style.display === 'none') {
                filterContent.style.display = 'flex';
                filterText.textContent = 'Hide Filters';
            } else {
                filterContent.style.display = 'none';
                filterText.textContent = 'Show Filters';
            }
        }

        // Add smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Add loading animation on filter submit
        document.querySelector('.filter-section form').addEventListener('submit', function() {
            const button = this.querySelector('button[type="submit"]');
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Applying...';
            button.disabled = true;
        });
    </script>
    
    <!-- Include admin.js for sidebar toggle functionality -->
    <script src="assets/js/admin.js"></script>
</body>
</html>