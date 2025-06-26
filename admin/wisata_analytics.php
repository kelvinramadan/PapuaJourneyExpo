<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in - FIXED: using correct session variable
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Check if analytics tables exist
$tables_exist = true;
$setup_message = '';

$check_tables = $conn->query("SHOW TABLES LIKE 'wisata_views'");
if ($check_tables->num_rows == 0) {
    $tables_exist = false;
    $setup_message = 'Analytics tables not found. Please run the database migration first.';
}

// Initialize data arrays
$trending_labels = [];
$trending_data = [];
$category_labels = [];
$category_data = [];

if ($tables_exist) {
    // Get trending destinations (last 7 days)
    $trending_query = "
        SELECT 
            w.judul,
            COUNT(wv.id) as view_count
        FROM wisata w
        INNER JOIN wisata_views wv ON w.id = wv.wisata_id
        WHERE wv.view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY w.id, w.judul
        ORDER BY view_count DESC
        LIMIT 10
    ";
    
    $result = $conn->query($trending_query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $trending_labels[] = $row['judul'];
            $trending_data[] = $row['view_count'];
        }
    }
    
    // Get views by category
    $category_query = "
        SELECT 
            w.kategori,
            COUNT(wv.id) as view_count
        FROM wisata w
        INNER JOIN wisata_views wv ON w.id = wv.wisata_id
        WHERE wv.view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY w.kategori
        ORDER BY view_count DESC
    ";
    
    $result = $conn->query($category_query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $category_labels[] = ucfirst($row['kategori'] ?: 'Lainnya');
            $category_data[] = $row['view_count'];
        }
    }
}

// Get today's stats
$today_views = 0;
$today_visitors = 0;

if ($tables_exist) {
    $today_query = "
        SELECT 
            COUNT(*) as views,
            COUNT(DISTINCT session_id) as visitors
        FROM wisata_views
        WHERE DATE(view_date) = CURDATE()
    ";
    $result = $conn->query($today_query);
    if ($result) {
        $today_stats = $result->fetch_assoc();
        $today_views = $today_stats['views'] ?? 0;
        $today_visitors = $today_stats['visitors'] ?? 0;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Wisata - Admin Panel</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-box h3 {
            font-size: 2rem;
            color: #007bff;
            margin: 10px 0;
        }
        
        .stat-box p {
            color: #666;
            margin: 0;
        }
        
        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .chart-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .chart-wrapper {
            position: relative;
            height: 400px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="analytics-container">
            <h1 style="margin-bottom: 30px;">📊 Analytics Wisata</h1>
            
            <?php if (!$tables_exist): ?>
                <div class="alert">
                    ⚠️ <?php echo $setup_message; ?>
                    <br><br>
                    Run this SQL command:
                    <code>mysql -u root -p omaki_db < database_updates/add_wisata_analytics_tables.sql</code>
                </div>
            <?php else: ?>
                <!-- Today's Stats -->
                <div class="stats-row">
                    <div class="stat-box">
                        <p>Views Hari Ini</p>
                        <h3><?php echo number_format($today_views); ?></h3>
                    </div>
                    <div class="stat-box">
                        <p>Pengunjung Hari Ini</p>
                        <h3><?php echo number_format($today_visitors); ?></h3>
                    </div>
                </div>
                
                <!-- Trending Destinations Chart -->
                <div class="chart-container">
                    <h2>🔥 Top 10 Destinasi Wisata (7 Hari Terakhir)</h2>
                    <?php if (empty($trending_data)): ?>
                        <div class="no-data">
                            <p>Belum ada data views dalam 7 hari terakhir.</p>
                            <p>Data akan muncul setelah pengunjung membuka halaman detail wisata.</p>
                        </div>
                    <?php else: ?>
                        <div class="chart-wrapper">
                            <canvas id="trendingChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Category Performance Chart -->
                <?php if (!empty($category_data)): ?>
                <div class="chart-container">
                    <h2>📊 Views per Kategori</h2>
                    <div class="chart-wrapper" style="height: 300px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($tables_exist && !empty($trending_data)): ?>
    <script>
        // Trending Destinations Bar Chart
        const trendingCtx = document.getElementById('trendingChart').getContext('2d');
        new Chart(trendingCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($trending_labels); ?>,
                datasets: [{
                    label: 'Views',
                    data: <?php echo json_encode($trending_data); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
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
                            stepSize: 1
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
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
                                return context.parsed.y + ' views';
                            }
                        }
                    }
                }
            }
        });
        
        <?php if (!empty($category_data)): ?>
        // Category Pie Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($category_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($category_data); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
    <?php endif; ?>
</body>
</html>