<?php
// admin/integrated_reports.php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Integrated Business Reports';
$db = getDbConnection();

// Report generation configuration
$report_type = $_GET['type'] ?? 'comprehensive';
$date_range = $_GET['range'] ?? '30';
$export_format = $_GET['format'] ?? 'html';

// Calculate date ranges
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime("-{$date_range} days"));

// Comprehensive Business Report Function
function generateComprehensiveReport($db, $start_date, $end_date) {
    $report = [];
    
    // Executive Summary
    $summary_query = "
        SELECT 
            COUNT(DISTINCT t.id) as total_transactions,
            SUM(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE 0 END) as total_revenue,
            COUNT(DISTINCT t.user_id) as total_customers,
            AVG(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE NULL END) as avg_order_value,
            
            COUNT(DISTINCT CASE WHEN DATE(u.created_at) BETWEEN ? AND ? THEN u.id END) as new_customers,
            COUNT(DISTINCT CASE WHEN DATE(u.last_login) BETWEEN ? AND ? THEN u.id END) as active_customers,
            
            COUNT(DISTINCT w.id) as total_destinations,
            COUNT(DISTINCT p.id) as total_accommodations,
            COUNT(DISTINCT a.id) as total_umkm_products,
            
            (SELECT COUNT(*) FROM cart_items WHERE DATE(updated_at) BETWEEN ? AND ?) as cart_activities,
            (SELECT COUNT(DISTINCT user_id) FROM cart_items WHERE 
                DATE_ADD(updated_at, INTERVAL 1 MINUTE) < NOW() 
                AND DATE(updated_at) BETWEEN ? AND ?
            ) as abandoned_carts
        FROM transaksi t
        CROSS JOIN users u
        CROSS JOIN wisata w
        CROSS JOIN penginapan p
        CROSS JOIN artikel a
        WHERE DATE(t.created_at) BETWEEN ? AND ?
    ";
    
    $stmt = $db->prepare($summary_query);
    $stmt->bind_param("ssssssssss", 
        $start_date, $end_date, $start_date, $end_date,
        $start_date, $end_date, $start_date, $end_date,
        $start_date, $end_date
    );
    $stmt->execute();
    $report['summary'] = $stmt->get_result()->fetch_assoc();
    
    // Revenue Breakdown by Category
    $revenue_breakdown_query = "
        SELECT 
            ci.item_type as category,
            COUNT(*) as total_bookings,
            SUM(
                CASE ci.item_type
                    WHEN 'wisata' THEN (SELECT harga FROM wisata WHERE id = ci.item_id)
                    WHEN 'penginapan' THEN (SELECT harga FROM penginapan WHERE id = ci.item_id)
                    WHEN 'umkm' THEN (SELECT harga FROM artikel WHERE id = ci.item_id)
                    ELSE 0
                END
            ) as total_revenue,
            AVG(
                CASE ci.item_type
                    WHEN 'wisata' THEN (SELECT harga FROM wisata WHERE id = ci.item_id)
                    WHEN 'penginapan' THEN (SELECT harga FROM penginapan WHERE id = ci.item_id)
                    WHEN 'umkm' THEN (SELECT harga FROM artikel WHERE id = ci.item_id)
                    ELSE 0
                END
            ) as avg_price
        FROM cart_items ci
        WHERE EXISTS (
            SELECT 1 FROM transaksi t 
            WHERE t.user_id = ci.user_id 
            AND t.payment_status = 'paid'
            AND DATE(t.created_at) BETWEEN ? AND ?
        )
        GROUP BY ci.item_type
        ORDER BY total_revenue DESC
    ";
    
    $stmt = $db->prepare($revenue_breakdown_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $report['revenue_breakdown'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Top Performing Products
    $top_products_query = "
        (SELECT 
            'Tourism' as category,
            w.judul as product_name,
            w.harga as price,
            COUNT(ci.id) as bookings,
            COUNT(wv.id) as views,
            COUNT(ci.id) * w.harga as revenue
        FROM wisata w
        LEFT JOIN cart_items ci ON w.id = ci.item_id AND ci.item_type = 'wisata'
        LEFT JOIN wisata_views wv ON w.id = wv.wisata_id AND DATE(wv.view_date) BETWEEN ? AND ?
        LEFT JOIN transaksi t ON ci.user_id = t.user_id AND t.payment_status = 'paid' AND DATE(t.created_at) BETWEEN ? AND ?
        WHERE t.id IS NOT NULL
        GROUP BY w.id
        ORDER BY revenue DESC
        LIMIT 5)
        
        UNION ALL
        
        (SELECT 
            'Accommodation' as category,
            p.judul as product_name,
            p.harga as price,
            COUNT(ci.id) as bookings,
            COUNT(pv.id) as views,
            COUNT(ci.id) * p.harga as revenue
        FROM penginapan p
        LEFT JOIN cart_items ci ON p.id = ci.item_id AND ci.item_type = 'penginapan'
        LEFT JOIN penginapan_views pv ON p.id = pv.penginapan_id AND DATE(pv.view_date) BETWEEN ? AND ?
        LEFT JOIN transaksi t ON ci.user_id = t.user_id AND t.payment_status = 'paid' AND DATE(t.created_at) BETWEEN ? AND ?
        WHERE t.id IS NOT NULL
        GROUP BY p.id
        ORDER BY revenue DESC
        LIMIT 5)
        
        ORDER BY revenue DESC
    ";
    
    $stmt = $db->prepare($top_products_query);
    $stmt->bind_param("ssssss", $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $report['top_products'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Customer Analysis
    $customer_analysis_query = "
        SELECT 
            CASE 
                WHEN total_spent >= 5000000 THEN 'VIP'
                WHEN total_spent >= 2000000 THEN 'Premium'
                WHEN total_spent >= 500000 THEN 'Regular'
                ELSE 'Basic'
            END as customer_tier,
            COUNT(*) as customer_count,
            AVG(total_spent) as avg_spent,
            SUM(total_spent) as total_revenue,
            AVG(transaction_count) as avg_transactions
        FROM (
            SELECT 
                t.user_id,
                SUM(t.total_amount) as total_spent,
                COUNT(t.id) as transaction_count
            FROM transaksi t
            WHERE t.payment_status = 'paid'
            AND DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY t.user_id
        ) customer_stats
        GROUP BY customer_tier
        ORDER BY avg_spent DESC
    ";
    
    $stmt = $db->prepare($customer_analysis_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $report['customer_analysis'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Operational Metrics
    $operational_query = "
        SELECT 
            COUNT(DISTINCT umkm.id) as total_umkm,
            COUNT(DISTINCT CASE WHEN umkm.status = 'active' THEN umkm.id END) as active_umkm,
            COUNT(DISTINCT CASE WHEN umkm.status = 'pending' THEN umkm.id END) as pending_umkm,
            
            (SELECT COUNT(*) FROM admin_payment_logs WHERE DATE(created_at) BETWEEN ? AND ?) as payment_confirmations,
            
            (SELECT COUNT(*) FROM wisata_views WHERE DATE(view_date) BETWEEN ? AND ?) as tourism_views,
            (SELECT COUNT(*) FROM penginapan_views WHERE DATE(view_date) BETWEEN ? AND ?) as accommodation_views
        FROM umkm
    ";
    
    $stmt = $db->prepare($operational_query);
    $stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $report['operational'] = $stmt->get_result()->fetch_assoc();
    
    return $report;
}

// Generate Performance Insights
function generatePerformanceInsights($report_data) {
    $insights = [];
    
    $summary = $report_data['summary'];
    
    // Revenue insights
    if ($summary['total_revenue'] > 0) {
        $insights[] = [
            'type' => 'success',
            'title' => 'Revenue Performance',
            'message' => 'Generated Rp ' . number_format($summary['total_revenue'], 0, ',', '.') . 
                        ' from ' . $summary['total_transactions'] . ' transactions',
            'recommendation' => 'Continue current strategies and explore upselling opportunities'
        ];
    }
    
    // Customer acquisition insights
    if ($summary['new_customers'] > 0) {
        $acquisition_rate = ($summary['new_customers'] / $summary['total_customers']) * 100;
        $insights[] = [
            'type' => $acquisition_rate > 20 ? 'success' : 'warning',
            'title' => 'Customer Acquisition',
            'message' => $summary['new_customers'] . ' new customers acquired (' . 
                        number_format($acquisition_rate, 1) . '% of total)',
            'recommendation' => $acquisition_rate > 20 ? 
                'Excellent acquisition rate. Focus on retention.' :
                'Consider increasing marketing efforts to acquire new customers'
        ];
    }
    
    // Cart abandonment insights
    if ($summary['abandoned_carts'] > 0) {
        $abandonment_rate = ($summary['abandoned_carts'] / $summary['cart_activities']) * 100;
        $insights[] = [
            'type' => $abandonment_rate > 50 ? 'danger' : 'warning',
            'title' => 'Cart Abandonment',
            'message' => $summary['abandoned_carts'] . ' abandoned carts (' . 
                        number_format($abandonment_rate, 1) . '% abandonment rate)',
            'recommendation' => 'Implement cart recovery campaigns and checkout optimization'
        ];
    }
    
    return $insights;
}

// Generate the report
$report_data = generateComprehensiveReport($db, $start_date, $end_date);
$insights = generatePerformanceInsights($report_data);

$db->close();

// Handle CSV export
if ($export_format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="business_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Executive Summary
    fputcsv($output, ['EXECUTIVE SUMMARY']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Revenue', 'Rp ' . number_format($report_data['summary']['total_revenue'], 0, ',', '.')]);
    fputcsv($output, ['Total Transactions', $report_data['summary']['total_transactions']]);
    fputcsv($output, ['Total Customers', $report_data['summary']['total_customers']]);
    fputcsv($output, ['Average Order Value', 'Rp ' . number_format($report_data['summary']['avg_order_value'], 0, ',', '.')]);
    fputcsv($output, ['New Customers', $report_data['summary']['new_customers']]);
    fputcsv($output, ['Active Customers', $report_data['summary']['active_customers']]);
    fputcsv($output, []);
    
    // Revenue Breakdown
    fputcsv($output, ['REVENUE BREAKDOWN BY CATEGORY']);
    fputcsv($output, ['Category', 'Bookings', 'Revenue', 'Average Price']);
    foreach ($report_data['revenue_breakdown'] as $category) {
        fputcsv($output, [
            ucfirst($category['category']),
            $category['total_bookings'],
            'Rp ' . number_format($category['total_revenue'], 0, ',', '.'),
            'Rp ' . number_format($category['avg_price'], 0, ',', '.')
        ]);
    }
    fputcsv($output, []);
    
    // Top Products
    fputcsv($output, ['TOP PERFORMING PRODUCTS']);
    fputcsv($output, ['Category', 'Product Name', 'Price', 'Bookings', 'Views', 'Revenue']);
    foreach ($report_data['top_products'] as $product) {
        fputcsv($output, [
            $product['category'],
            $product['product_name'],
            'Rp ' . number_format($product['price'], 0, ',', '.'),
            $product['bookings'],
            $product['views'],
            'Rp ' . number_format($product['revenue'], 0, ',', '.')
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Integrated Business Reports - Papua Journey</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .report-header {
            background: linear-gradient(135deg, #065f46 0%, #10b981 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        
        .report-controls {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .control-group {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .report-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #F3F4F6;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            padding: 1.5rem;
            background: #F9FAFB;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #10B981;
        }
        
        .summary-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }
        
        .summary-label {
            color: #6B7280;
            font-size: 0.9rem;
        }
        
        .insight-alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }
        
        .insight-alert.success {
            background: #ECFDF5;
            border-left-color: #10B981;
            color: #047857;
        }
        
        .insight-alert.warning {
            background: #FFFBEB;
            border-left-color: #F59E0B;
            color: #92400E;
        }
        
        .insight-alert.danger {
            background: #FEF2F2;
            border-left-color: #EF4444;
            color: #B91C1C;
        }
        
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
        
        .btn-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .export-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .export-btn.csv {
            background: #10B981;
            color: white;
        }
        
        .export-btn.pdf {
            background: #EF4444;
            color: white;
        }
        
        @media print {
            .report-controls, .sidebar, .nav-menu, .export-btn {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="main-content" id="mainContent">
            <?php include 'components/header.php'; ?>
            
            <div class="content-wrapper">
                <!-- Report Header -->
                <div class="report-header">
                    <div style="font-size: 2.5rem; font-weight: 700; margin-bottom: 0.5rem;">
                        📋 Integrated Business Reports
                    </div>
                    <div style="font-size: 1.1rem; opacity: 0.9;">
                        Comprehensive business intelligence reporting and analytics
                    </div>
                    <div style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.8;">
                        Report Period: <?php echo date('M j, Y', strtotime($start_date)); ?> - 
                        <?php echo date('M j, Y', strtotime($end_date)); ?>
                    </div>
                </div>
                
                <!-- Report Controls -->
                <div class="report-controls">
                    <div class="control-group">
                        <label for="dateRange">Date Range:</label>
                        <select id="dateRange" onchange="changeTimeframe(this.value)">
                            <option value="7" <?php echo $date_range == '7' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="30" <?php echo $date_range == '30' ? 'selected' : ''; ?>>Last 30 Days</option>
                            <option value="90" <?php echo $date_range == '90' ? 'selected' : ''; ?>>Last 90 Days</option>
                            <option value="365" <?php echo $date_range == '365' ? 'selected' : ''; ?>>Last Year</option>
                        </select>
                    </div>
                    
                    <div class="btn-group">
                        <a href="?range=<?php echo $date_range; ?>&format=csv" class="export-btn csv">
                            📊 Export CSV
                        </a>
                        <button onclick="window.print()" class="export-btn pdf">
                            🖨️ Print Report
                        </button>
                    </div>
                </div>
                
                <!-- Performance Insights -->
                <?php if (!empty($insights)): ?>
                <div class="report-section">
                    <div class="section-title">🎯 Key Performance Insights</div>
                    <?php foreach ($insights as $insight): ?>
                    <div class="insight-alert <?php echo $insight['type']; ?>">
                        <strong><?php echo $insight['title']; ?>:</strong> <?php echo $insight['message']; ?>
                        <br><small><strong>Recommendation:</strong> <?php echo $insight['recommendation']; ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- Executive Summary -->
                <div class="report-section">
                    <div class="section-title">📈 Executive Summary</div>
                    
                    <div class="summary-grid">
                        <div class="summary-card">
                            <div class="summary-value">Rp <?php echo number_format($report_data['summary']['total_revenue'], 0, ',', '.'); ?></div>
                            <div class="summary-label">Total Revenue</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value"><?php echo number_format($report_data['summary']['total_transactions']); ?></div>
                            <div class="summary-label">Total Transactions</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value"><?php echo number_format($report_data['summary']['total_customers']); ?></div>
                            <div class="summary-label">Total Customers</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value">Rp <?php echo number_format($report_data['summary']['avg_order_value'], 0, ',', '.'); ?></div>
                            <div class="summary-label">Average Order Value</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value"><?php echo number_format($report_data['summary']['new_customers']); ?></div>
                            <div class="summary-label">New Customers</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value"><?php echo number_format($report_data['summary']['active_customers']); ?></div>
                            <div class="summary-label">Active Customers</div>
                        </div>
                    </div>
                </div>
                
                <!-- Revenue Breakdown -->
                <div class="report-section">
                    <div class="section-title">💰 Revenue Breakdown by Category</div>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Total Bookings</th>
                                    <th>Total Revenue</th>
                                    <th>Average Price</th>
                                    <th>Revenue Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_category_revenue = array_sum(array_column($report_data['revenue_breakdown'], 'total_revenue'));
                                foreach ($report_data['revenue_breakdown'] as $category): 
                                    $revenue_share = $total_category_revenue > 0 ? 
                                        ($category['total_revenue'] / $total_category_revenue) * 100 : 0;
                                ?>
                                <tr>
                                    <td><strong><?php echo ucfirst($category['category']); ?></strong></td>
                                    <td><?php echo number_format($category['total_bookings']); ?></td>
                                    <td>Rp <?php echo number_format($category['total_revenue'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($category['avg_price'], 0, ',', '.'); ?></td>
                                    <td><?php echo number_format($revenue_share, 1); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Top Performing Products -->
                <div class="report-section">
                    <div class="section-title">⭐ Top Performing Products</div>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Product Name</th>
                                    <th>Price</th>
                                    <th>Bookings</th>
                                    <th>Views</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['top_products'] as $product): ?>
                                <tr>
                                    <td><?php echo $product['category']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                    <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo number_format($product['bookings']); ?></td>
                                    <td><?php echo number_format($product['views']); ?></td>
                                    <td>Rp <?php echo number_format($product['revenue'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Customer Analysis -->
                <div class="report-section">
                    <div class="section-title">👥 Customer Tier Analysis</div>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Customer Tier</th>
                                    <th>Customer Count</th>
                                    <th>Average Spending</th>
                                    <th>Total Revenue</th>
                                    <th>Average Transactions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['customer_analysis'] as $tier): ?>
                                <tr>
                                    <td><strong><?php echo $tier['customer_tier']; ?></strong></td>
                                    <td><?php echo number_format($tier['customer_count']); ?></td>
                                    <td>Rp <?php echo number_format($tier['avg_spent'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($tier['total_revenue'], 0, ',', '.'); ?></td>
                                    <td><?php echo number_format($tier['avg_transactions'], 1); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Operational Metrics -->
                <div class="report-section">
                    <div class="section-title">⚙️ Operational Performance</div>
                    
                    <div class="summary-grid">
                        <div class="summary-card">
                            <div class="summary-value"><?php echo number_format($report_data['operational']['total_umkm']); ?></div>
                            <div class="summary-label">Total UMKM Partners</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value"><?php echo number_format($report_data['operational']['active_umkm']); ?></div>
                            <div class="summary-label">Active UMKM</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value"><?php echo number_format($report_data['operational']['pending_umkm']); ?></div>
                            <div class="summary-label">Pending UMKM</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value"><?php echo number_format($report_data['operational']['payment_confirmations']); ?></div>
                            <div class="summary-label">Payment Confirmations</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value"><?php echo number_format($report_data['operational']['tourism_views']); ?></div>
                            <div class="summary-label">Tourism Views</div>
                        </div>
                        <div class="summary-card">
                            <div class="summary-value"><?php echo number_format($report_data['operational']['accommodation_views']); ?></div>
                            <div class="summary-label">Accommodation Views</div>
                        </div>
                    </div>
                </div>
                
                <!-- Report Footer -->
                <div style="text-align: center; padding: 2rem; color: #6B7280; border-top: 1px solid #E5E7EB;">
                    <p>Report generated on <?php echo date('F j, Y \a\t g:i A'); ?> | Papua Journey Business Intelligence System</p>
                </div>
            </div>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>
    
    <script>
        function changeTimeframe(days) {
            window.location.href = `integrated_reports.php?range=${days}`;
        }
    </script>
</body>
</html>