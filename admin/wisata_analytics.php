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
    $setup_message = 'Tabel analitik tidak ditemukan. Silakan jalankan migrasi database terlebih dahulu.';
}

// Initialize data arrays for tourism
$trending_labels = [];
$trending_data = [];
$category_labels = [];
$category_data = [];

// Initialize data arrays for accommodation
$acc_trending_labels = [];
$acc_trending_data = [];
$acc_type_labels = [];
$acc_type_data = [];

if ($tables_exist) {
    // Get trending destinations (last 7 days)
    $trending_query = "
        SELECT 
            w.judul,
            COUNT(wv.id) as view_count
        FROM wisata w
        INNER JOIN wisata_views wv ON w.id = wv.wisata_id
        WHERE wv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
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
        WHERE wv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
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
    
    // Check if accommodation analytics tables exist
    $check_acc_tables = $conn->query("SHOW TABLES LIKE 'penginapan_views'");
    $acc_tables_exist = $check_acc_tables->num_rows > 0;
    
    if ($acc_tables_exist) {
        // Get trending accommodations (last 7 days)
        $acc_trending_query = "
            SELECT 
                p.judul,
                COUNT(pv.id) as view_count
            FROM penginapan p
            INNER JOIN penginapan_views pv ON p.id = pv.penginapan_id
            WHERE pv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY p.id, p.judul
            ORDER BY view_count DESC
            LIMIT 10
        ";
        
        $result = $conn->query($acc_trending_query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $acc_trending_labels[] = $row['judul'];
                $acc_trending_data[] = $row['view_count'];
            }
        }
        
        // Get views by accommodation type
        $acc_type_query = "
            SELECT 
                p.tipe,
                COUNT(pv.id) as view_count
            FROM penginapan p
            INNER JOIN penginapan_views pv ON p.id = pv.penginapan_id
            WHERE pv.view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY p.tipe
            ORDER BY view_count DESC
        ";
        
        $result = $conn->query($acc_type_query);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $acc_type_labels[] = ucfirst($row['tipe'] ?: 'Lainnya');
                $acc_type_data[] = $row['view_count'];
            }
        }
    }
}

// Get today's stats
$today_views = 0;
$today_visitors = 0;
$today_acc_views = 0;
$today_acc_visitors = 0;

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
    
    // Get accommodation stats for today
    if (isset($acc_tables_exist) && $acc_tables_exist) {
        $today_acc_query = "
            SELECT 
                COUNT(*) as views,
                COUNT(DISTINCT session_id) as visitors
            FROM penginapan_views
            WHERE DATE(view_date) = CURDATE()
        ";
        $result = $conn->query($today_acc_query);
        if ($result) {
            $today_acc_stats = $result->fetch_assoc();
            $today_acc_views = $today_acc_stats['views'] ?? 0;
            $today_acc_visitors = $today_acc_stats['visitors'] ?? 0;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analitik Pariwisata & Penginapan - Panel Admin</title>
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
        
        /* Tab Navigation Styles */
        .analytics-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #E5E7EB;
            padding-bottom: 0;
        }
        
        .tab-button {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: #6B7280;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: -2px;
        }
        
        .tab-button:hover {
            color: #374151;
            background: #F9FAFB;
        }
        
        .tab-button.active {
            color: #007BFF;
            border-bottom-color: #007BFF;
            background: #EFF6FF;
        }
        
        .tab-button span {
            font-size: 1.2rem;
        }
        
        .section-container {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }
        
        .section-container.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .section-title {
            font-size: 1.5rem;
            color: #1F2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .analytics-tabs {
                flex-direction: column;
                border-bottom: none;
                gap: 5px;
            }
            
            .tab-button {
                width: 100%;
                border-bottom: none;
                border-left: 3px solid transparent;
                margin-bottom: 0;
            }
            
            .tab-button.active {
                border-left-color: #007BFF;
                border-bottom-color: transparent;
            }
            
            .stats-row {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="analytics-container">
            <h1 style="margin-bottom: 10px;">📊 Dashboard Analitik</h1>
            
            <?php if ($tables_exist): ?>
                <!-- Quick Overview -->
                <div style="background: #F3F4F6; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                        <div>
                            <span style="color: #6B7280; font-size: 0.875rem;">Total Tampilan Hari Ini</span>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1F2937;">
                                <?php echo number_format($today_views + $today_acc_views); ?>
                            </div>
                        </div>
                        <div>
                            <span style="color: #6B7280; font-size: 0.875rem;">Total Pengunjung Hari Ini</span>
                            <div style="font-size: 1.5rem; font-weight: 700; color: #1F2937;">
                                <?php echo number_format($today_visitors + $today_acc_visitors); ?>
                            </div>
                        </div>
                    </div>
                    <div style="font-size: 0.875rem; color: #6B7280;">
                        Terakhir diperbarui: <?php echo date('H:i:s'); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (!$tables_exist): ?>
                <div class="alert">
                    ⚠️ <?php echo $setup_message; ?>
                    <br><br>
                    Jalankan perintah SQL berikut:
                    <br>
                    <code>mysql -u root -p omaki_db < database_updates/add_wisata_analytics_tables.sql</code>
                    <br>
                    <code>mysql -u root -p omaki_db < database_updates/add_penginapan_analytics_tables.sql</code>
                </div>
            <?php else: ?>
                <!-- Tab Navigation -->
                <div class="analytics-tabs">
                    <button class="tab-button active" onclick="switchTab('tourism')" id="tourism-tab">
                        <span>🏖️</span>
                        Analitik Pariwisata
                        <?php if ($today_views > 0): ?>
                            <span style="background: #10B981; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; margin-left: 8px;">
                                <?php echo $today_views; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    <button class="tab-button" onclick="switchTab('accommodation')" id="accommodation-tab">
                        <span>🏨</span>
                        Analitik Penginapan
                        <?php if ($today_acc_views > 0): ?>
                            <span style="background: #10B981; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; margin-left: 8px;">
                                <?php echo $today_acc_views; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                </div>
                
                <!-- Tourism Section -->
                <div id="tourism-section" class="section-container active">
                    <!-- Tourism Stats -->
                    <div class="stats-row">
                        <div class="stat-box">
                            <p>Views Hari Ini</p>
                            <h3><?php echo number_format($today_views); ?></h3>
                        </div>
                        <div class="stat-box">
                            <p>Pengunjung Hari Ini</p>
                            <h3><?php echo number_format($today_visitors); ?></h3>
                        </div>
                        <div class="stat-box">
                            <p>Total Destinasi</p>
                            <h3><?php 
                                $total_dest = $conn->query("SELECT COUNT(*) as total FROM wisata")->fetch_assoc()['total'];
                                echo number_format($total_dest);
                            ?></h3>
                        </div>
                        <div class="stat-box">
                            <p>Aktif Bulan Ini</p>
                            <h3><?php 
                                $active_dest = $conn->query("SELECT COUNT(DISTINCT wisata_id) as total FROM wisata_views WHERE view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['total'];
                                echo number_format($active_dest);
                            ?></h3>
                        </div>
                    </div>
                    
                    <!-- Trending Destinations Chart -->
                    <div class="chart-container">
                        <h2>🔥 Top 10 Destinasi Wisata (30 Hari Terakhir)</h2>
                        <?php if (empty($trending_data)): ?>
                            <div class="no-data">
                                <p>Belum ada data views dalam 30 hari terakhir.</p>
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
                </div>
                
                <!-- Accommodation Section -->
                <?php if (isset($acc_tables_exist) && $acc_tables_exist): ?>
                <div id="accommodation-section" class="section-container">
                    <!-- Accommodation Stats -->
                    <div class="stats-row">
                        <div class="stat-box">
                            <p>Views Hari Ini</p>
                            <h3><?php echo number_format($today_acc_views); ?></h3>
                        </div>
                        <div class="stat-box">
                            <p>Pengunjung Hari Ini</p>
                            <h3><?php echo number_format($today_acc_visitors); ?></h3>
                        </div>
                        <div class="stat-box">
                            <p>Total Penginapan</p>
                            <h3><?php 
                                $total_acc = $conn->query("SELECT COUNT(*) as total FROM penginapan")->fetch_assoc()['total'];
                                echo number_format($total_acc);
                            ?></h3>
                        </div>
                        <div class="stat-box">
                            <p>Aktif Bulan Ini</p>
                            <h3><?php 
                                $active_acc = $conn->query("SELECT COUNT(DISTINCT penginapan_id) as total FROM penginapan_views WHERE view_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['total'];
                                echo number_format($active_acc);
                            ?></h3>
                        </div>
                    </div>
                    
                    <!-- Trending Accommodations Chart -->
                    <div class="chart-container">
                        <h2>🔥 Top 10 Penginapan (30 Hari Terakhir)</h2>
                        <?php if (empty($acc_trending_data)): ?>
                            <div class="no-data">
                                <p>Belum ada data views penginapan dalam 30 hari terakhir.</p>
                                <p>Data akan muncul setelah pengunjung membuka halaman detail penginapan.</p>
                            </div>
                        <?php else: ?>
                            <div class="chart-wrapper">
                                <canvas id="accTrendingChart"></canvas>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Accommodation Type Chart -->
                    <?php if (!empty($acc_type_data)): ?>
                    <div class="chart-container">
                        <h2>📊 Views per Tipe Penginapan</h2>
                        <div class="chart-wrapper" style="height: 300px;">
                            <canvas id="accTypeChart"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <!-- No Accommodation Analytics Message -->
                <div id="accommodation-section" class="section-container">
                    <div class="alert">
                        ⚠️ Tabel analitik penginapan tidak ditemukan.
                        <br><br>
                        Jalankan perintah SQL berikut:
                        <code>mysql -u root -p omaki_db < database_updates/add_penginapan_analytics_tables.sql</code>
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
        
        <?php if (isset($acc_tables_exist) && $acc_tables_exist && !empty($acc_trending_data)): ?>
        // Trending Accommodations Bar Chart
        const accTrendingCtx = document.getElementById('accTrendingChart').getContext('2d');
        new Chart(accTrendingCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($acc_trending_labels); ?>,
                datasets: [{
                    label: 'Views',
                    data: <?php echo json_encode($acc_trending_data); ?>,
                    backgroundColor: 'rgba(255, 159, 64, 0.8)',
                    borderColor: 'rgba(255, 159, 64, 1)',
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
        <?php endif; ?>
        
        <?php if (isset($acc_tables_exist) && $acc_tables_exist && !empty($acc_type_data)): ?>
        // Accommodation Type Pie Chart
        const accTypeCtx = document.getElementById('accTypeChart').getContext('2d');
        new Chart(accTypeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($acc_type_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($acc_type_data); ?>,
                    backgroundColor: [
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 205, 86, 0.8)'
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
    
    <!-- Tab Switching JavaScript -->
    <script>
        // Initialize charts storage
        let tourismChartsInitialized = false;
        let accommodationChartsInitialized = false;
        
        // Get the active tab from URL hash or localStorage or default to tourism
        function getActiveTab() {
            const hash = window.location.hash.substring(1);
            if (hash === 'tourism' || hash === 'accommodation') {
                return hash;
            }
            return localStorage.getItem('analytics_active_tab') || 'tourism';
        }
        
        // Set initial tab on page load
        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = getActiveTab();
            if (activeTab !== 'tourism') {
                switchTab(activeTab, false);
            }
            updatePageTitle(activeTab);
        });
        
        // Handle browser back/forward buttons
        window.addEventListener('hashchange', function() {
            const hash = window.location.hash.substring(1);
            if (hash === 'tourism' || hash === 'accommodation') {
                switchTab(hash, false);
            }
        });
        
        function switchTab(section, saveState = true) {
            // Get all tab buttons and sections
            const tabButtons = document.querySelectorAll('.tab-button');
            const sections = document.querySelectorAll('.section-container');
            
            // Update tab buttons
            tabButtons.forEach(btn => {
                if (btn.id === `${section}-tab`) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
            
            // Show/hide sections with animation
            sections.forEach(sec => {
                if (sec.id === `${section}-section`) {
                    // Small delay to ensure smooth animation
                    setTimeout(() => {
                        sec.classList.add('active');
                    }, 10);
                } else {
                    sec.classList.remove('active');
                }
            });
            
            // Update URL hash and localStorage
            if (saveState) {
                window.location.hash = section;
                localStorage.setItem('analytics_active_tab', section);
            }
            
            // Update page title
            updatePageTitle(section);
            
            // Initialize charts if needed
            if (section === 'accommodation' && !accommodationChartsInitialized) {
                // Charts are already initialized on page load
                accommodationChartsInitialized = true;
            }
        }
        
        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                const currentTab = document.querySelector('.tab-button.active');
                const tabs = Array.from(document.querySelectorAll('.tab-button'));
                const currentIndex = tabs.indexOf(currentTab);
                
                if (e.key === 'ArrowLeft' && currentIndex > 0) {
                    tabs[currentIndex - 1].click();
                } else if (e.key === 'ArrowRight' && currentIndex < tabs.length - 1) {
                    tabs[currentIndex + 1].click();
                }
            }
        });
        
        // Update page title based on active section
        function updatePageTitle(section) {
            const baseTitle = 'Dashboard Analitik - Panel Admin';
            const sectionName = section === 'tourism' ? 'Pariwisata' : 'Penginapan';
            document.title = `Analitik ${sectionName} - Panel Admin`;
        }
    </script>
</body>
</html>