<?php
// umkm/umkm_analytics.php
session_start();

require_once '../config/database.php';
include 'sidebar.php';

// Check if user is logged in and is UMKM
if (!isset($_SESSION['umkm_id']) || $_SESSION['user_type'] != 'umkm') {
    header('Location: ../login.php');
    exit();
}

$db = getDbConnection();
$umkm_id = $_SESSION['umkm_id'];

// Get UMKM data for header
$stmt = $db->prepare("SELECT business_name, profile_image, email, phone FROM umkm WHERE id = ?");
$stmt->bind_param("i", $umkm_id);
$stmt->execute();
$result = $stmt->get_result();
$umkm_data = $result->fetch_assoc();
$stmt->close();

// 1. Data untuk Line Chart - Perkembangan pemesanan dari waktu ke waktu
$time_range = isset($_GET['range']) ? $_GET['range'] : '30'; // Default 30 days

$date_format = '%Y-%m-%d';
$date_interval = 'DAY';
if ($time_range == '365') {
    $date_format = '%Y-%m';
    $date_interval = 'MONTH';
} elseif ($time_range == '90') {
    $date_format = '%Y-%m-%d';
    $date_interval = 'DAY';
}

$orders_trend_query = "SELECT 
                        DATE_FORMAT(t.payment_confirmed_at, '$date_format') as period,
                        COUNT(DISTINCT ti.id) as total_orders,
                        SUM(ti.subtotal) as total_revenue
                    FROM transaksi_items ti
                    JOIN transaksi t ON ti.transaksi_id = t.id
                    JOIN artikel a ON ti.item_id = a.id AND ti.item_type = 'artikel'
                    WHERE a.umkm_id = ? 
                    AND t.payment_status = 'paid'
                    AND t.payment_confirmed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY DATE_FORMAT(t.payment_confirmed_at, '$date_format')
                    ORDER BY period ASC";

$orders_stmt = $db->prepare($orders_trend_query);
$orders_stmt->bind_param("ii", $umkm_id, $time_range);
$orders_stmt->execute();
$orders_trend_result = $orders_stmt->get_result();
$orders_trend_data = $orders_trend_result->fetch_all(MYSQLI_ASSOC);
$orders_stmt->close();

// 2. Data untuk Horizontal Bar/Pie Chart - Produk yang paling sering dipesan
$popular_products_query = "SELECT 
                            a.judul as product_name,
                            a.kategori,
                            COUNT(ti.id) as total_orders,
                            SUM(ti.quantity) as total_quantity,
                            SUM(ti.subtotal) as total_revenue
                        FROM transaksi_items ti
                        JOIN transaksi t ON ti.transaksi_id = t.id
                        JOIN artikel a ON ti.item_id = a.id AND ti.item_type = 'artikel'
                        WHERE a.umkm_id = ? AND t.payment_status = 'paid'
                        GROUP BY a.id, a.judul, a.kategori
                        ORDER BY total_orders DESC
                        LIMIT 10";

$popular_stmt = $db->prepare($popular_products_query);
$popular_stmt->bind_param("i", $umkm_id);
$popular_stmt->execute();
$popular_products_result = $popular_stmt->get_result();
$popular_products_data = $popular_products_result->fetch_all(MYSQLI_ASSOC);
$popular_stmt->close();

// 3. Data untuk Bar Chart - Total pendapatan per produk
$revenue_by_product_query = "SELECT 
                                a.judul as product_name,
                                a.harga as product_price,
                                a.kategori,
                                COUNT(ti.id) as total_orders,
                                SUM(ti.quantity) as total_sold,
                                SUM(ti.subtotal) as total_revenue
                            FROM transaksi_items ti
                            JOIN transaksi t ON ti.transaksi_id = t.id
                            JOIN artikel a ON ti.item_id = a.id AND ti.item_type = 'artikel'
                            WHERE a.umkm_id = ? AND t.payment_status = 'paid'
                            GROUP BY a.id, a.judul, a.harga, a.kategori
                            ORDER BY total_revenue DESC";

$revenue_stmt = $db->prepare($revenue_by_product_query);
$revenue_stmt->bind_param("i", $umkm_id);
$revenue_stmt->execute();
$revenue_result = $revenue_stmt->get_result();
$revenue_data = $revenue_result->fetch_all(MYSQLI_ASSOC);
$revenue_stmt->close();

// Get overall statistics
$stats_query = "SELECT 
                    COUNT(DISTINCT ti.id) as total_pemesanan,
                    SUM(ti.subtotal) as total_pendapatan,
                    COUNT(DISTINCT a.id) as total_products,
                    AVG(ti.subtotal) as avg_order_value
                FROM transaksi_items ti
                JOIN transaksi t ON ti.transaksi_id = t.id
                JOIN artikel a ON ti.item_id = a.id AND ti.item_type = 'artikel'
                WHERE a.umkm_id = ? AND t.payment_status = 'paid'";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bind_param("i", $umkm_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

$db->close();

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Convert PHP data to JavaScript format
$orders_trend_js = json_encode($orders_trend_data);
$popular_products_js = json_encode($popular_products_data);
$revenue_data_js = json_encode($revenue_data);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - UMKM Papua</title>
    <link rel="stylesheet" href="umkm.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .header-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .header-section h1 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .header-section p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .charts-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #ecf0f1;
        }

        .chart-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .chart-subtitle {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .time-filter {
            display: flex;
            gap: 0.5rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #667eea;
            background: transparent;
            color: #667eea;
            border-radius: 25px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #667eea;
            color: white;
        }

        .chart-canvas {
            max-height: 400px;
            margin: 1rem 0;
        }

        .two-column {
            grid-template-columns: 1fr 1fr;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.95);
            color: #667eea;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            margin-bottom: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            background: white;
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .no-data-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .analytics-container {
                padding: 1rem;
            }
            
            .charts-grid.two-column {
                grid-template-columns: 1fr;
            }
            
            .time-filter {
                flex-direction: column;
            }
            
            .chart-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <div class="analytics-container">
        <!-- Header -->
        <a href="umkm_dashboard.php" class="back-button">
            ⬅️ Kembali ke Dashboard
        </a>

        <div class="header-section">
            <h1>📊 Analytics Dashboard</h1>
            <p>Analisis mendalam tentang performa bisnis Anda</p>
            <div style="margin-top: 1rem;">
                <strong>🏪 <?php echo htmlspecialchars($umkm_data['business_name']); ?></strong>
            </div>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_pemesanan'] ?: 0; ?></div>
                <div class="stat-label">Total Pemesanan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo formatPrice($stats['total_pendapatan'] ?: 0); ?></div>
                <div class="stat-label">Total Pendapatan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_products'] ?: 0; ?></div>
                <div class="stat-label">Total Produk</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo formatPrice($stats['avg_order_value'] ?: 0); ?></div>
                <div class="stat-label">Rata-rata Nilai Pesanan</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <!-- 1. Line Chart - Perkembangan Pemesanan -->
            <div class="chart-container">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">📈 Perkembangan Pemesanan</h3>
                        <p class="chart-subtitle">Tren pemesanan dan pendapatan dari waktu ke waktu</p>
                    </div>
                    <div class="time-filter">
                        <button class="filter-btn <?php echo $time_range == '30' ? 'active' : ''; ?>" 
                                onclick="changeTimeRange('30')">30 Hari</button>
                        <button class="filter-btn <?php echo $time_range == '90' ? 'active' : ''; ?>" 
                                onclick="changeTimeRange('90')">90 Hari</button>
                        <button class="filter-btn <?php echo $time_range == '365' ? 'active' : ''; ?>" 
                                onclick="changeTimeRange('365')">1 Tahun</button>
                    </div>
                </div>
                <?php if (count($orders_trend_data) > 0): ?>
                    <canvas id="ordersLineChart" class="chart-canvas"></canvas>
                <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">📊</div>
                        <h3>Belum Ada Data</h3>
                        <p>Data perkembangan pemesanan akan muncul setelah ada transaksi</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Two Column Charts -->
        <div class="charts-grid two-column">
            <!-- 2. Pie Chart - Produk Terpopuler -->
            <div class="chart-container">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">🏆 Produk Terpopuler</h3>
                        <p class="chart-subtitle">Produk yang paling sering dipesan</p>
                    </div>
                </div>
                <?php if (count($popular_products_data) > 0): ?>
                    <canvas id="popularProductsChart" class="chart-canvas"></canvas>
                <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">🏆</div>
                        <h3>Belum Ada Data</h3>
                        <p>Data produk populer akan muncul setelah ada pemesanan</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 3. Bar Chart - Pendapatan per Produk -->
            <div class="chart-container">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">💰 Pendapatan per Produk</h3>
                        <p class="chart-subtitle">Total pendapatan yang diperoleh dari setiap produk</p>
                    </div>
                </div>
                <?php if (count($revenue_data) > 0): ?>
                    <canvas id="revenueBarChart" class="chart-canvas"></canvas>
                <?php else: ?>
                    <div class="no-data">
                        <div class="no-data-icon">💰</div>
                        <h3>Belum Ada Data</h3>
                        <p>Data pendapatan akan muncul setelah ada transaksi</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Data from PHP
        const ordersTrendData = <?php echo $orders_trend_js; ?>;
        const popularProductsData = <?php echo $popular_products_js; ?>;
        const revenueData = <?php echo $revenue_data_js; ?>;

        // Color schemes
        const colors = {
            primary: '#667eea',
            secondary: '#764ba2',
            success: '#27ae60',
            warning: '#f39c12',
            danger: '#e74c3c',
            info: '#3498db'
        };

        // Chart color palettes
        const chartColors = [
            '#667eea', '#764ba2', '#27ae60', '#f39c12', '#e74c3c',
            '#3498db', '#9b59b6', '#1abc9c', '#e67e22', '#34495e'
        ];

        // 1. Line Chart - Orders Trend
        if (ordersTrendData.length > 0) {
            const ctx1 = document.getElementById('ordersLineChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: ordersTrendData.map(item => {
                        const date = new Date(item.period);
                        return date.toLocaleDateString('id-ID', { 
                            month: 'short', 
                            day: 'numeric' 
                        });
                    }),
                    datasets: [{
                        label: 'Jumlah Pemesanan',
                        data: ordersTrendData.map(item => item.total_orders),
                        borderColor: colors.primary,
                        backgroundColor: colors.primary + '20',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: colors.primary,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        yAxisID: 'y'
                    }, {
                        label: 'Pendapatan (Rp)',
                        data: ordersTrendData.map(item => parseInt(item.total_revenue)),
                        borderColor: colors.success,
                        backgroundColor: colors.success + '20',
                        borderWidth: 3,
                        fill: false,
                        tension: 0.4,
                        pointBackgroundColor: colors.success,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: colors.primary,
                            borderWidth: 1,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    if (context.datasetIndex === 1) {
                                        return 'Pendapatan: Rp ' + context.parsed.y.toLocaleString('id-ID');
                                    }
                                    return context.dataset.label + ': ' + context.parsed.y;
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
                                    size: 11
                                }
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Jumlah Pemesanan',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Pendapatan (Rp)',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });
        }

        // 2. Pie Chart - Popular Products
        if (popularProductsData.length > 0) {
            const ctx2 = document.getElementById('popularProductsChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: popularProductsData.map(item => item.product_name),
                    datasets: [{
                        data: popularProductsData.map(item => item.total_orders),
                        backgroundColor: chartColors.slice(0, popularProductsData.length),
                        borderColor: '#fff',
                        borderWidth: 3,
                        hoverBorderWidth: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 11
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: colors.primary,
                            borderWidth: 1,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed * 100) / total).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' pesanan (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }

        // 3. Bar Chart - Revenue per Product
        if (revenueData.length > 0) {
            const ctx3 = document.getElementById('revenueBarChart').getContext('2d');
            new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: revenueData.map(item => item.product_name.length > 20 ? 
                        item.product_name.substring(0, 20) + '...' : item.product_name),
                    datasets: [{
                        label: 'Total Pendapatan',
                        data: revenueData.map(item => parseInt(item.total_revenue)),
                        backgroundColor: revenueData.map((item, index) => chartColors[index % chartColors.length] + '80'),
                        borderColor: revenueData.map((item, index) => chartColors[index % chartColors.length]),
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false
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
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: colors.primary,
                            borderWidth: 1,
                            cornerRadius: 8,
                            callbacks: {
                                title: function(context) {
                                    const fullName = revenueData[context[0].dataIndex].product_name;
                                    return fullName;
                                },
                                label: function(context) {
                                    return 'Pendapatan: Rp ' + context.parsed.y.toLocaleString('id-ID');
                                },
                                afterLabel: function(context) {
                                    const item = revenueData[context.dataIndex];
                                    return [
                                        'Total Terjual: ' + item.total_sold + ' tiket',
                                        'Jumlah Pesanan: ' + item.total_orders
                                    ];
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
                                maxRotation: 45,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Pendapatan (Rp)',
                                font: {
                                    size: 12,
                                    weight: '600'
                                }
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    }
                }
            });
        }

        // Function to change time range
        function changeTimeRange(range) {
            window.location.href = '?range=' + range;
        }

        // Add loading animation
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
        });
    </script>
</body>
</html>