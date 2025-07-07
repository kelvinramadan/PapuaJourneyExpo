<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

require_once '../config/database.php';

$db = getDbConnection();

// Get date filter from query params
$date_filter = $_GET['filter'] ?? '7days';
$start_date = '';
$end_date = date('Y-m-d');

switch($date_filter) {
    case '1day':
        $start_date = date('Y-m-d', strtotime('-1 day'));
        break;
    case '7days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90days':
        $start_date = date('Y-m-d', strtotime('-90 days'));
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-7 days'));
}

// Get abandoned cart statistics
$stats = getAbandonedCartStats($db, $start_date, $end_date);
$top_abandoned_products = getTopAbandonedProducts($db, $start_date, $end_date);
$abandonment_reasons = getAbandonmentReasons($db, $start_date, $end_date);
$timeline_data = getAbandonmentTimeline($db, $start_date, $end_date);
$recovery_stats = getRecoveryStats($db, $start_date, $end_date);

$db->close();

function getAbandonedCartStats($db, $start_date, $end_date) {
    $query = "
        SELECT 
            COUNT(*) as total_abandonments,
            AVG(total_value) as avg_cart_value,
            SUM(total_value) as total_abandoned_value,
            AVG(item_count) as avg_items_per_cart,
            AVG(session_duration_minutes) as avg_session_duration,
            SUM(CASE WHEN is_recovered = 1 THEN 1 ELSE 0 END) as recovered_carts,
            (SUM(CASE WHEN is_recovered = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100) as recovery_rate
        FROM abandoned_carts 
        WHERE DATE(abandonment_timestamp) BETWEEN ? AND ?
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getTopAbandonedProducts($db, $start_date, $end_date) {
    // Get abandoned cart items using a simpler approach for broader MySQL compatibility
    $query = "
        SELECT 
            ac.id,
            ac.cart_items_snapshot,
            ac.abandonment_timestamp
        FROM abandoned_carts ac
        WHERE DATE(ac.abandonment_timestamp) BETWEEN ? AND ?
        ORDER BY ac.abandonment_timestamp DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $product_stats = [];
    
    while ($row = $result->fetch_assoc()) {
        $cart_items = json_decode($row['cart_items_snapshot'], true);
        
        if ($cart_items) {
            foreach ($cart_items as $item) {
                $key = $item['item_name'] . '|' . $item['item_type'] . '|' . $item['item_category'];
                
                if (!isset($product_stats[$key])) {
                    $product_stats[$key] = [
                        'product_name' => $item['item_name'],
                        'product_type' => $item['item_type'],
                        'product_category' => $item['item_category'],
                        'abandonment_count' => 0,
                        'total_lost_value' => 0,
                        'price_sum' => 0,
                        'price_count' => 0
                    ];
                }
                
                $product_stats[$key]['abandonment_count']++;
                $product_stats[$key]['total_lost_value'] += $item['subtotal'];
                $product_stats[$key]['price_sum'] += $item['price_per_unit'];
                $product_stats[$key]['price_count']++;
            }
        }
    }
    
    // Calculate average prices and sort by abandonment count
    foreach ($product_stats as &$stat) {
        $stat['avg_price'] = $stat['price_count'] > 0 ? $stat['price_sum'] / $stat['price_count'] : 0;
    }
    
    // Sort by abandonment count and take top 10
    uasort($product_stats, function($a, $b) {
        return $b['abandonment_count'] - $a['abandonment_count'];
    });
    
    return array_slice(array_values($product_stats), 0, 10);
}

function getAbandonmentReasons($db, $start_date, $end_date) {
    $query = "
        SELECT 
            car.reason_code,
            COUNT(*) as count,
            (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM cart_abandonment_reasons car2 
                                JOIN abandoned_carts ac2 ON car2.abandoned_cart_id = ac2.id 
                                WHERE DATE(ac2.abandonment_timestamp) BETWEEN ? AND ?)) as percentage
        FROM cart_abandonment_reasons car
        JOIN abandoned_carts ac ON car.abandoned_cart_id = ac.id
        WHERE DATE(ac.abandonment_timestamp) BETWEEN ? AND ?
        GROUP BY car.reason_code
        ORDER BY count DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getAbandonmentTimeline($db, $start_date, $end_date) {
    $query = "
        SELECT 
            DATE(abandonment_timestamp) as date,
            COUNT(*) as abandonment_count,
            AVG(total_value) as avg_cart_value,
            HOUR(abandonment_timestamp) as hour,
            COUNT(*) as hourly_count
        FROM abandoned_carts 
        WHERE DATE(abandonment_timestamp) BETWEEN ? AND ?
        GROUP BY DATE(abandonment_timestamp)
        ORDER BY date ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getRecoveryStats($db, $start_date, $end_date) {
    $query = "
        SELECT 
            recovery_method,
            COUNT(*) as count,
            AVG(TIMESTAMPDIFF(HOUR, abandonment_timestamp, recovered_at)) as avg_recovery_hours
        FROM abandoned_carts 
        WHERE is_recovered = 1 
        AND DATE(abandonment_timestamp) BETWEEN ? AND ?
        GROUP BY recovery_method
        ORDER BY count DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Format currency
function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

// Format percentage
function formatPercentage($value) {
    return number_format($value, 1) . '%';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analitik Abandoned Cart - Papua Journey Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="sidebar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .filter-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .filter-btn.active,
        .filter-btn:hover {
            background: #667eea;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-change {
            font-size: 12px;
            color: #666;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .chart-container {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .chart-container h3 {
            margin: 0 0 20px 0;
            color: #333;
        }
        
        .data-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #667eea;
            color: white;
            padding: 20px 25px;
        }
        
        .table-header h3 {
            margin: 0;
        }
        
        .table-content {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        .export-btn {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
        }
        
        .export-btn:hover {
            background: #218838;
        }
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-controls {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="analytics-container">
            <div class="page-header">
                <h1><i class="fas fa-chart-line"></i> Analitik Abandoned Cart</h1>
                <p>Analisis mendalam tentang keranjang yang ditinggalkan dan strategi recovery</p>
            </div>
            
            <!-- Filter Controls -->
            <div class="filter-controls">
                <a href="?filter=1day" class="filter-btn <?php echo $date_filter == '1day' ? 'active' : ''; ?>">1 Hari</a>
                <a href="?filter=7days" class="filter-btn <?php echo $date_filter == '7days' ? 'active' : ''; ?>">7 Hari</a>
                <a href="?filter=30days" class="filter-btn <?php echo $date_filter == '30days' ? 'active' : ''; ?>">30 Hari</a>
                <a href="?filter=90days" class="filter-btn <?php echo $date_filter == '90days' ? 'active' : ''; ?>">90 Hari</a>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Abandonment</h3>
                    <div class="stat-value"><?php echo number_format($stats['total_abandonments']); ?></div>
                    <div class="stat-change">Keranjang ditinggalkan</div>
                </div>
                
                <div class="stat-card">
                    <h3>Nilai Rata-rata Cart</h3>
                    <div class="stat-value"><?php echo formatRupiah($stats['avg_cart_value'] ?? 0); ?></div>
                    <div class="stat-change">Per keranjang yang ditinggalkan</div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Nilai Hilang</h3>
                    <div class="stat-value"><?php echo formatRupiah($stats['total_abandoned_value'] ?? 0); ?></div>
                    <div class="stat-change">Potensi pendapatan hilang</div>
                </div>
                
                <div class="stat-card">
                    <h3>Recovery Rate</h3>
                    <div class="stat-value"><?php echo formatPercentage($stats['recovery_rate'] ?? 0); ?></div>
                    <div class="stat-change"><?php echo $stats['recovered_carts']; ?> dari <?php echo $stats['total_abandonments']; ?> berhasil di-recover</div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="charts-grid">
                <div class="chart-container">
                    <h3>Alasan Abandonment</h3>
                    <canvas id="reasonsChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <h3>Timeline Abandonment</h3>
                    <canvas id="timelineChart"></canvas>
                </div>
            </div>
            
            <!-- Top Abandoned Products Table -->
            <div class="data-table">
                <div class="table-header">
                    <h3>Produk Paling Sering Ditinggalkan</h3>
                </div>
                <div class="table-content">
                    <table>
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Jenis</th>
                                <th>Jumlah Abandonment</th>
                                <th>Rata-rata Harga</th>
                                <th>Total Nilai Hilang</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_abandoned_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['product_category']); ?></td>
                                <td><?php echo htmlspecialchars($product['product_type']); ?></td>
                                <td><?php echo $product['abandonment_count']; ?></td>
                                <td><?php echo formatRupiah($product['avg_price']); ?></td>
                                <td><?php echo formatRupiah($product['total_lost_value']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Export Button -->
            <div style="display: flex; gap: 15px; align-items: center;">
                <a href="export_abandoned_cart_data.php?filter=<?php echo $date_filter; ?>" class="export-btn">
                    <i class="fas fa-download"></i> Export Data
                </a>
                
                <button onclick="sendReminders()" class="export-btn" style="background: #007bff;">
                    <i class="fas fa-envelope"></i> Kirim Email Reminder
                </button>
            </div>
        </div>
    </main>
    
    <script>
        // Reasons Chart
        const reasonsCtx = document.getElementById('reasonsChart').getContext('2d');
        const reasonsData = <?php echo json_encode($abandonment_reasons); ?>;
        
        new Chart(reasonsCtx, {
            type: 'doughnut',
            data: {
                labels: reasonsData.map(item => {
                    const labels = {
                        'price_too_high': 'Harga Terlalu Mahal',
                        'shipping_cost': 'Biaya Pengiriman',
                        'not_sure': 'Belum Yakin',
                        'payment_issues': 'Masalah Pembayaran',
                        'found_better_deal': 'Penawaran Lebih Baik',
                        'changed_mind': 'Berubah Pikiran',
                        'technical_issues': 'Masalah Teknis',
                        'other': 'Lainnya'
                    };
                    return labels[item.reason_code] || item.reason_code;
                }),
                datasets: [{
                    data: reasonsData.map(item => item.count),
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                        '#FF6384',
                        '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Timeline Chart
        const timelineCtx = document.getElementById('timelineChart').getContext('2d');
        const timelineData = <?php echo json_encode($timeline_data); ?>;
        
        new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: timelineData.map(item => item.date),
                datasets: [{
                    label: 'Abandonment Count',
                    data: timelineData.map(item => item.abandonment_count),
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Function to send cart reminders
        function sendReminders() {
            if (confirm('Apakah Anda yakin ingin mengirim email reminder ke semua pengguna dengan keranjang yang ditinggalkan?')) {
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';
                btn.disabled = true;
                
                fetch('send_cart_reminders.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Berhasil mengirim ' + data.sent_count + ' email reminder!');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        alert('Terjadi kesalahan: ' + error.message);
                    })
                    .finally(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });
            }
        }
    </script>
</body>
</html>