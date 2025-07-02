<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get date range from query parameters or default to last 30 days
$end_date = date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $end_date;

// Fetch overview statistics
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

$stmt = $conn->prepare($overview_query);
if (!$stmt) {
    error_log("Overview Query Error: " . $conn->error);
    $overview = [
        'total_transactions' => 0,
        'total_revenue' => 0,
        'successful_transactions' => 0,
        'failed_transactions' => 0,
        'avg_order_value' => 0
    ];
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $overview = $stmt->get_result()->fetch_assoc();
}

// Calculate growth rate (compare with previous period)
$prev_end_date = date('Y-m-d', strtotime($start_date . ' -1 day'));
$prev_start_date = date('Y-m-d', strtotime($start_date . ' -' . (strtotime($end_date) - strtotime($start_date)) / 86400 . ' days'));

$growth_query = "
    SELECT SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as prev_revenue
    FROM transaksi
    WHERE DATE(created_at) BETWEEN ? AND ?
";

$stmt = $conn->prepare($growth_query);
if (!$stmt) {
    error_log("Growth Query Error: " . $conn->error);
    $prev_data = ['prev_revenue' => 0];
} else {
    $stmt->bind_param("ss", $prev_start_date, $prev_end_date);
    $stmt->execute();
    $prev_data = $stmt->get_result()->fetch_assoc();
}

$growth_rate = 0;
if ($prev_data['prev_revenue'] > 0) {
    $growth_rate = (($overview['total_revenue'] - $prev_data['prev_revenue']) / $prev_data['prev_revenue']) * 100;
}

// Fetch revenue by product type
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

$stmt = $conn->prepare($product_type_query);
if (!$stmt) {
    error_log("Product Type Query Error: " . $conn->error);
    $product_types = [];
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $product_types = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch top revenue products
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

$stmt = $conn->prepare($top_products_query);
if (!$stmt) {
    error_log("Top Products Query Error: " . $conn->error);
    $top_products = [];
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $top_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch UMKM revenue - fixed column name
$umkm_revenue_query = "
    SELECT 
        u.business_name as nama_umkm,
        u.id as umkm_id,
        COUNT(DISTINCT t.id) as transaction_count,
        SUM(ti.subtotal) as total_revenue
    FROM umkm u
    LEFT JOIN artikel a ON u.id = a.umkm_id
    LEFT JOIN transaksi_items ti ON ti.item_id = a.id AND ti.item_type = 'artikel'
    LEFT JOIN transaksi t ON ti.transaksi_id = t.id AND t.payment_status = 'paid'
    WHERE (t.created_at IS NULL OR DATE(t.created_at) BETWEEN ? AND ?)
    GROUP BY u.id, u.business_name
    HAVING total_revenue > 0
    ORDER BY total_revenue DESC
    LIMIT 10
";

$stmt = $conn->prepare($umkm_revenue_query);
if (!$stmt) {
    // If prepare fails, log the error
    error_log("UMKM Revenue Query Error: " . $conn->error);
    $umkm_revenue = []; // Set empty array as fallback
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $umkm_revenue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch payment method distribution
$payment_methods_query = "
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(total_amount) as total_amount
    FROM transaksi
    WHERE payment_status = 'paid'
    AND DATE(created_at) BETWEEN ? AND ?
    GROUP BY payment_method
";

$stmt = $conn->prepare($payment_methods_query);
if (!$stmt) {
    error_log("Payment Methods Query Error: " . $conn->error);
    $payment_methods = [];
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $payment_methods = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch daily revenue for chart
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

$stmt = $conn->prepare($daily_revenue_query);
if (!$stmt) {
    error_log("Daily Revenue Query Error: " . $conn->error);
    $daily_revenue = [];
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $daily_revenue = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

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
    <link rel="stylesheet" href="financial_reports.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            // Calculate success rate before using it
            $success_rate = $overview['total_transactions'] > 0 
                ? round(($overview['successful_transactions'] / $overview['total_transactions']) * 100, 1)
                : 0;
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
                                $total_umkm_revenue = array_sum(array_column($umkm_revenue, 'total_revenue'));
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