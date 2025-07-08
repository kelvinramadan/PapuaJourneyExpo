<?php
// admin/index.php
session_start();
require_once '../config/database.php';

$error_message = '';
$success_message = '';

// Handle admin login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['admin_login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Get database connection
    $db = getDbConnection();
    
    // Query admin from database
    $stmt = $db->prepare("SELECT id, username, password, full_name FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
        } else {
            $error_message = 'Username atau password admin salah!';
        }
    } else {
        $error_message = 'Username atau password admin salah!';
    }
    
    $stmt->close();
    $db->close();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Handle UMKM status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status']) && isset($_SESSION['admin_logged_in'])) {
    $umkm_id = (int)$_POST['umkm_id'];
    $new_status = $_POST['status'];
    
    if (in_array($new_status, ['pending', 'active', 'inactive'])) {
        $db = getDbConnection();
        $stmt = $db->prepare("UPDATE umkm SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("si", $new_status, $umkm_id);
        
        if ($stmt->execute()) {
            $success_message = 'Status UMKM berhasil diperbarui!'; // Already in Indonesian
        } else {
            $error_message = 'Gagal memperbarui status UMKM!'; // Already in Indonesian
        }
        $stmt->close();
        $db->close();
    }
}

// Handle UMKM deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_umkm']) && isset($_SESSION['admin_logged_in'])) {
    $umkm_id = (int)$_POST['umkm_id'];
    
    $db = getDbConnection();
    $stmt = $db->prepare("DELETE FROM umkm WHERE id = ?");
    $stmt->bind_param("i", $umkm_id);
    
    if ($stmt->execute()) {
        $success_message = 'UMKM berhasil dihapus!'; // Already in Indonesian
    } else {
        $error_message = 'Gagal menghapus UMKM!'; // Already in Indonesian
    }
    $stmt->close();
    $db->close();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    // Show login form
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Papua Journey</title>
        <link rel="stylesheet" href="admin.css">
        <style>
            body {
                background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .login-container {
                animation: fadeIn 0.5s ease-out;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .login-logo {
                font-size: 3rem;
                margin-bottom: 1rem;
            }
        </style>
    </head>
    <body>
        <div class="login-container card" style="max-width: 400px; width: 90%;">
            <div class="card-body" style="padding: 2.5rem;">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <div class="login-logo">🏝️</div>
                    <h1 style="font-size: 1.75rem; margin-bottom: 0.5rem;">Login Admin</h1>
                    <p style="color: var(--text-secondary);">Masuk ke panel admin Papua Journey</p>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" id="username" class="form-control" required autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    
                    <button type="submit" name="admin_login" class="btn btn-primary btn-lg" style="width: 100%;">
                        Masuk
                    </button>
                </form>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Get dashboard data
$db = getDbConnection();

// Get UMKM statistics
$stats_stmt = $db->prepare("SELECT status, COUNT(*) as count FROM umkm GROUP BY status");
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$umkm_stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $umkm_stats[$row['status']] = $row['count'];
}

// Get total users
$users_stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
$users_stmt->execute();
$users_result = $users_stmt->get_result();
$total_users = $users_result->fetch_assoc()['total'];

// Get total destinations
$destinations_stmt = $db->prepare("SELECT COUNT(*) as total FROM wisata");
$destinations_stmt->execute();
$destinations_result = $destinations_stmt->get_result();
$total_destinations = $destinations_result->fetch_assoc()['total'];

// Get total lodgings
$lodgings_stmt = $db->prepare("SELECT COUNT(*) as total FROM penginapan");
$lodgings_stmt->execute();
$lodgings_result = $lodgings_stmt->get_result();
$total_lodgings = $lodgings_result->fetch_assoc()['total'];

// Get tourism analytics statistics
$analytics_stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT wv.id) as total_views_today,
        COUNT(DISTINCT wv.session_id) as unique_visitors_today
    FROM wisata_views wv
    WHERE DATE(wv.view_date) = CURDATE()
");
$analytics_stmt->execute();
$analytics_today = $analytics_stmt->get_result()->fetch_assoc();

// Get accommodation analytics statistics
$acc_analytics_stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT pv.id) as total_views_today,
        COUNT(DISTINCT pv.session_id) as unique_visitors_today
    FROM penginapan_views pv
    WHERE DATE(pv.view_date) = CURDATE()
");
$acc_analytics_stmt->execute();
$acc_analytics_today = $acc_analytics_stmt->get_result()->fetch_assoc();

// Get top viewed destinations
$top_destinations_stmt = $db->prepare("
    SELECT 
        w.id,
        w.judul,
        COUNT(wv.id) as view_count
    FROM wisata w
    LEFT JOIN wisata_views wv ON w.id = wv.wisata_id
    WHERE wv.view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY w.id
    ORDER BY view_count DESC
    LIMIT 5
");
$top_destinations_stmt->execute();
$top_destinations = $top_destinations_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get top viewed accommodations
$top_accommodations_stmt = $db->prepare("
    SELECT 
        p.id,
        p.judul,
        COUNT(pv.id) as view_count
    FROM penginapan p
    LEFT JOIN penginapan_views pv ON p.id = pv.penginapan_id
    WHERE pv.view_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY p.id
    ORDER BY view_count DESC
    LIMIT 5
");
$top_accommodations_stmt->execute();
$top_accommodations = $top_accommodations_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent UMKM registrations
$recent_umkm_stmt = $db->prepare("SELECT * FROM umkm ORDER BY created_at DESC LIMIT 5");
$recent_umkm_stmt->execute();
$recent_umkm = $recent_umkm_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all UMKM for management
$umkm_stmt = $db->prepare("SELECT * FROM umkm ORDER BY created_at DESC");
$umkm_stmt->execute();
$umkm_list = $umkm_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Close all statements
$stats_stmt->close();
$users_stmt->close();
$destinations_stmt->close();
$lodgings_stmt->close();
$analytics_stmt->close();
$acc_analytics_stmt->close();
$top_destinations_stmt->close();
$top_accommodations_stmt->close();
$recent_umkm_stmt->close();
$umkm_stmt->close();
$db->close();

// Set page title for header
$page_title = 'Dashboard';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Papua Journey</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        /* Prevent auto-scrolling issues */
        html, body {
            overflow-x: hidden;
            overflow-y: auto;
            scroll-behavior: auto !important;
            position: relative;
        }
        
        /* Fix for UMKM status card */
        .umkm-status-card {
            overflow: hidden !important;
            position: relative;
        }
        
        .umkm-status-card .card-body {
            overflow: hidden !important;
            max-height: 450px;
        }
        
        /* Ensure chart container is properly sized */
        #umkmChart {
            position: relative !important;
            height: 150px !important;
            width: 100% !important;
            max-width: 300px !important;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="main-content" id="mainContent">
            <?php include 'components/header.php'; ?>
            
            <div class="content-wrapper">
                <?php if ($success_message): ?>
                    <div class="alert alert-success fade-in">
                        <span>✓</span>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error fade-in">
                        <span>✕</span>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Dashboard Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($total_users); ?></div>
                                <div class="stat-label">Total Pengguna</div>
                                <div class="stat-trend trend-up">
                                    <span>↑</span> 12% dari bulan lalu
                                </div>
                            </div>
                            <div class="stat-icon primary">👥</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($total_destinations); ?></div>
                                <div class="stat-label">Destinasi Wisata</div>
                                <div class="stat-trend trend-up">
                                    <span>↑</span> 8 destinasi baru
                                </div>
                            </div>
                            <div class="stat-icon success">🏖️</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($total_lodgings); ?></div>
                                <div class="stat-label">Penginapan</div>
                                <div class="stat-trend trend-up">
                                    <span>↑</span> 5 penginapan baru
                                </div>
                            </div>
                            <div class="stat-icon warning">🏨</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format(array_sum($umkm_stats)); ?></div>
                                <div class="stat-label">Total UMKM</div>
                                <div class="stat-trend trend-up">
                                    <span>↑</span> <?php echo $umkm_stats['pending'] ?? 0; ?> pending
                                </div>
                            </div>
                            <div class="stat-icon info">🏪</div>
                        </div>
                    </div>
                </div>
                
                <!-- Tourism Analytics Summary -->
                <div class="grid grid-2" style="margin-bottom: 2rem;">
                    <!-- Today's Analytics -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📊 Analytics Hari Ini</h3>
                            <a href="wisata_analytics.php#tourism" class="btn btn-sm btn-primary">Lihat Detail</a>
                        </div>
                        <div class="card-body">
                            <h4 style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">🏖️ Tourism</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                                <div style="text-align: center; padding: 0.75rem; background: #F0F9FF; border-radius: 0.5rem;">
                                    <div style="font-size: 1.5rem; font-weight: bold; color: #0369A1;">
                                        <?php echo number_format($analytics_today['total_views_today'] ?? 0); ?>
                                    </div>
                                    <div style="color: #64748B; font-size: 0.75rem;">Tampilan</div>
                                </div>
                                <div style="text-align: center; padding: 0.75rem; background: #F0FDF4; border-radius: 0.5rem;">
                                    <div style="font-size: 1.5rem; font-weight: bold; color: #16A34A;">
                                        <?php echo number_format($analytics_today['unique_visitors_today'] ?? 0); ?>
                                    </div>
                                    <div style="color: #64748B; font-size: 0.75rem;">Pengunjung</div>
                                </div>
                            </div>
                            <h4 style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">🏨 Accommodation</h4>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div style="text-align: center; padding: 0.75rem; background: #FFF5E6; border-radius: 0.5rem;">
                                    <div style="font-size: 1.5rem; font-weight: bold; color: #FF6B35;">
                                        <?php echo number_format($acc_analytics_today['total_views_today'] ?? 0); ?>
                                    </div>
                                    <div style="color: #64748B; font-size: 0.75rem;">Tampilan</div>
                                </div>
                                <div style="text-align: center; padding: 0.75rem; background: #F3E5F5; border-radius: 0.5rem;">
                                    <div style="font-size: 1.5rem; font-weight: bold; color: #7B1FA2;">
                                        <?php echo number_format($acc_analytics_today['unique_visitors_today'] ?? 0); ?>
                                    </div>
                                    <div style="color: #64748B; font-size: 0.75rem;">Pengunjung</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Top Destinations -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🌟 Top Destinasi (7 Hari)</h3>
                            <a href="adminwisata.php" class="btn btn-sm btn-outline">Kelola Wisata</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_destinations)): ?>
                                <p style="color: var(--text-secondary); text-align: center; padding: 2rem 0;">
                                    Belum ada data views dalam 7 hari terakhir
                                </p>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                    <?php foreach ($top_destinations as $index => $dest): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border-radius: 0.375rem; background: #F9FAFB;">
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 24px; height: 24px; background: <?php echo $index === 0 ? '#FFD700' : ($index === 1 ? '#C0C0C0' : ($index === 2 ? '#CD7F32' : '#E5E7EB')); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; color: <?php echo $index < 3 ? '#000' : '#6B7280'; ?>;">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; font-size: 0.875rem;"><?php echo htmlspecialchars($dest['judul']); ?></div>
                                                </div>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <span style="font-size: 0.875rem; color: #6B7280;">👁️</span>
                                                <span style="font-weight: 600; font-size: 0.875rem;"><?php echo number_format($dest['view_count']); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Accommodation Analytics Summary -->
                <div class="grid grid-2" style="margin-bottom: 2rem;">
                    <!-- Top Accommodations -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🏨 Top Penginapan (7 Hari)</h3>
                            <a href="adminpenginapan.php" class="btn btn-sm btn-outline">Kelola Penginapan</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_accommodations)): ?>
                                <p style="color: var(--text-secondary); text-align: center; padding: 2rem 0;">
                                    Belum ada data views dalam 7 hari terakhir
                                </p>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                    <?php foreach ($top_accommodations as $index => $acc): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border-radius: 0.375rem; background: #F9FAFB;">
                                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                <div style="width: 24px; height: 24px; background: <?php echo $index === 0 ? '#FFD700' : ($index === 1 ? '#C0C0C0' : ($index === 2 ? '#CD7F32' : '#E5E7EB')); ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold; color: <?php echo $index < 3 ? '#000' : '#6B7280'; ?>;">
                                                    <?php echo $index + 1; ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; font-size: 0.875rem;"><?php echo htmlspecialchars($acc['judul']); ?></div>
                                                </div>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <span style="font-size: 0.875rem; color: #6B7280;">👁️</span>
                                                <span style="font-weight: 600; font-size: 0.875rem;"><?php echo number_format($acc['view_count']); ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Analytics Overview -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">📈 Ringkasan Analitik</h3>
                        </div>
                        <div class="card-body">
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div style="padding: 1rem; background: #EFF6FF; border-radius: 0.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <div style="font-weight: 600; color: #1E40AF;">Total Tampilan Pariwisata Hari Ini</div>
                                            <div style="font-size: 1.25rem; font-weight: bold; color: #1E40AF;">
                                                <?php echo number_format(($analytics_today['total_views_today'] ?? 0)); ?>
                                            </div>
                                        </div>
                                        <div style="font-size: 2rem;">🏖️</div>
                                    </div>
                                </div>
                                <div style="padding: 1rem; background: #FFF7ED; border-radius: 0.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <div style="font-weight: 600; color: #C2410C;">Total Tampilan Penginapan Hari Ini</div>
                                            <div style="font-size: 1.25rem; font-weight: bold; color: #C2410C;">
                                                <?php echo number_format(($acc_analytics_today['total_views_today'] ?? 0)); ?>
                                            </div>
                                        </div>
                                        <div style="font-size: 2rem;">🏨</div>
                                    </div>
                                </div>
                                <div style="text-align: center; margin-top: 0.5rem;">
                                    <a href="wisata_analytics.php" class="btn btn-primary btn-sm" style="width: 100%;">
                                        📊 Lihat Dashboard Analitik Lengkap
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="grid grid-2" style="margin-bottom: 2rem;">
                    <!-- Recent UMKM -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">UMKM Terbaru</h3>
                            <a href="#" class="btn btn-sm btn-outline">Lihat Semua</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_umkm)): ?>
                                <p style="color: var(--text-secondary); text-align: center; padding: 2rem 0;">
                                    Belum ada UMKM terdaftar
                                </p>
                            <?php else: ?>
                                <div style="display: flex; flex-direction: column; gap: 1rem;">
                                    <?php foreach ($recent_umkm as $umkm): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; border-radius: 0.375rem; background: #F9FAFB;">
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($umkm['business_name']); ?></div>
                                                <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                                    <?php echo htmlspecialchars($umkm['owner_name']); ?> • <?php echo date('d M Y', strtotime($umkm['created_at'])); ?>
                                                </div>
                                            </div>
                                            <span class="badge badge-<?php echo $umkm['status'] == 'active' ? 'success' : ($umkm['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($umkm['status']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="card umkm-status-card" style="height: auto; min-height: 400px;">
                        <div class="card-header">
                            <h3 class="card-title">Status UMKM</h3>
                        </div>
                        <div class="card-body" style="overflow: hidden; position: relative; padding-bottom: 1.5rem;">
                            <div style="width: 100%; height: 150px; position: relative; display: flex; justify-content: center; align-items: center;">
                                <canvas id="umkmChart"></canvas>
                            </div>
                            
                            <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 0.75rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="width: 12px; height: 12px; background: #10B981; border-radius: 2px;"></div>
                                        <span>Active</span>
                                    </div>
                                    <span style="font-weight: 600;"><?php echo $umkm_stats['active'] ?? 0; ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="width: 12px; height: 12px; background: #F59E0B; border-radius: 2px;"></div>
                                        <span>Pending</span>
                                    </div>
                                    <span style="font-weight: 600;"><?php echo $umkm_stats['pending'] ?? 0; ?></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="width: 12px; height: 12px; background: #EF4444; border-radius: 2px;"></div>
                                        <span>Inactive</span>
                                    </div>
                                    <span style="font-weight: 600;"><?php echo $umkm_stats['inactive'] ?? 0; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Business Intelligence Quick Access -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h3 class="card-title">🧠 Business Intelligence Suite</h3>
                        <span style="font-size: 0.85rem; color: #6B7280;">Advanced analytics and decision support</span>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                            
                            <a href="executive_dashboard.php" style="text-decoration: none; color: inherit;">
                                <div style="padding: 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white; transition: transform 0.3s ease;" 
                                     onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="font-size: 2rem;">📊</div>
                                        <div>
                                            <div style="font-weight: 600; font-size: 1.1rem;">Executive Dashboard</div>
                                            <div style="font-size: 0.85rem; opacity: 0.9;">High-level KPIs & metrics</div>
                                        </div>
                                    </div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">
                                        Strategic overview with real-time business metrics and comparative analytics
                                    </div>
                                </div>
                            </a>
                            
                            <a href="predictive_analytics.php" style="text-decoration: none; color: inherit;">
                                <div style="padding: 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white; transition: transform 0.3s ease;" 
                                     onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="font-size: 2rem;">🔮</div>
                                        <div>
                                            <div style="font-weight: 600; font-size: 1.1rem;">Predictive Analytics</div>
                                            <div style="font-size: 0.85rem; opacity: 0.9;">AI-powered forecasting</div>
                                        </div>
                                    </div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">
                                        Revenue forecasting, churn prediction, and behavioral analytics
                                    </div>
                                </div>
                            </a>
                            
                            <a href="business_intelligence.php" style="text-decoration: none; color: inherit;">
                                <div style="padding: 1.5rem; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); border-radius: 8px; color: white; transition: transform 0.3s ease;" 
                                     onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="font-size: 2rem;">🧠</div>
                                        <div>
                                            <div style="font-weight: 600; font-size: 1.1rem;">Business Intelligence</div>
                                            <div style="font-size: 0.85rem; opacity: 0.9;">Deep insights & analysis</div>
                                        </div>
                                    </div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">
                                        Customer segmentation, market analysis, and performance insights
                                    </div>
                                </div>
                            </a>
                            
                            <a href="recommendation_system.php" style="text-decoration: none; color: inherit;">
                                <div style="padding: 1.5rem; background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); border-radius: 8px; color: white; transition: transform 0.3s ease;" 
                                     onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="font-size: 2rem;">🎯</div>
                                        <div>
                                            <div style="font-weight: 600; font-size: 1.1rem;">AI Recommendations</div>
                                            <div style="font-size: 0.85rem; opacity: 0.9;">Smart suggestions</div>
                                        </div>
                                    </div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">
                                        Product recommendations, user targeting, and marketing optimization
                                    </div>
                                </div>
                            </a>
                            
                            <a href="data_mining.php" style="text-decoration: none; color: inherit;">
                                <div style="padding: 1.5rem; background: linear-gradient(135deg, #1f2937 0%, #374151 100%); border-radius: 8px; color: white; transition: transform 0.3s ease;" 
                                     onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="font-size: 2rem;">⛏️</div>
                                        <div>
                                            <div style="font-weight: 600; font-size: 1.1rem;">Data Mining</div>
                                            <div style="font-size: 0.85rem; opacity: 0.9;">Pattern discovery</div>
                                        </div>
                                    </div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">
                                        Advanced pattern analysis, anomaly detection, and behavioral insights
                                    </div>
                                </div>
                            </a>
                            
                            <a href="integrated_reports.php" style="text-decoration: none; color: inherit;">
                                <div style="padding: 1.5rem; background: linear-gradient(135deg, #065f46 0%, #10b981 100%); border-radius: 8px; color: white; transition: transform 0.3s ease;" 
                                     onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                        <div style="font-size: 2rem;">📋</div>
                                        <div>
                                            <div style="font-weight: 600; font-size: 1.1rem;">Business Reports</div>
                                            <div style="font-size: 0.85rem; opacity: 0.9;">Comprehensive reporting</div>
                                        </div>
                                    </div>
                                    <div style="font-size: 0.8rem; opacity: 0.8;">
                                        Integrated business reports with export capabilities and insights
                                    </div>
                                </div>
                            </a>
                            
                        </div>
                    </div>
                </div>
                
                <!-- UMKM Management Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Manajemen UMKM</h3>
                        <div style="display: flex; gap: 0.75rem;">
                            <button class="btn btn-outline btn-sm" onclick="exportData()">
                                <span>📥</span> Export
                            </button>
                            <button class="btn btn-primary btn-sm">
                                <span>➕</span> Tambah UMKM
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($umkm_list)): ?>
                            <p style="text-align: center; padding: 3rem 0; color: var(--text-secondary);">
                                Belum ada UMKM yang terdaftar.
                            </p>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th data-sortable>ID</th>
                                            <th data-sortable>Nama Usaha</th>
                                            <th data-sortable>Pemilik</th>
                                            <th data-sortable>Email</th>
                                            <th data-sortable>Jenis Usaha</th>
                                            <th data-sortable>Status</th>
                                            <th data-sortable>Terdaftar</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($umkm_list as $umkm): ?>
                                            <tr>
                                                <td>#<?php echo $umkm['id']; ?></td>
                                                <td>
                                                    <div>
                                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($umkm['business_name']); ?></div>
                                                        <?php if ($umkm['description']): ?>
                                                            <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                                                <?php echo htmlspecialchars(substr($umkm['description'], 0, 50)) . '...'; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($umkm['owner_name']); ?></td>
                                                <td><?php echo htmlspecialchars($umkm['email']); ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo ucfirst($umkm['business_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $umkm['status'] == 'active' ? 'success' : ($umkm['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($umkm['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($umkm['created_at'])); ?></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <?php if ($umkm['status'] == 'pending'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="umkm_id" value="<?php echo $umkm['id']; ?>">
                                                                <input type="hidden" name="status" value="active">
                                                                <button type="submit" name="update_status" class="btn btn-success btn-sm" 
                                                                        onclick="return confirmDelete('Setujui UMKM ini?')">
                                                                    Setujui
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="umkm_id" value="<?php echo $umkm['id']; ?>">
                                                                <input type="hidden" name="status" value="inactive">
                                                                <button type="submit" name="update_status" class="btn btn-danger btn-sm" 
                                                                        onclick="return confirmDelete('Tolak UMKM ini?')">
                                                                    Tolak
                                                                </button>
                                                            </form>
                                                        <?php elseif ($umkm['status'] == 'active'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="umkm_id" value="<?php echo $umkm['id']; ?>">
                                                                <input type="hidden" name="status" value="inactive">
                                                                <button type="submit" name="update_status" class="btn btn-secondary btn-sm" 
                                                                        onclick="return confirmDelete('Nonaktifkan UMKM ini?')">
                                                                    Nonaktifkan
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="umkm_id" value="<?php echo $umkm['id']; ?>">
                                                                <input type="hidden" name="status" value="active">
                                                                <button type="submit" name="update_status" class="btn btn-success btn-sm" 
                                                                        onclick="return confirmDelete('Aktifkan UMKM ini?')">
                                                                    Aktifkan
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <button class="btn btn-outline btn-sm" onclick="viewDetails(<?php echo $umkm['id']; ?>)" data-tooltip="View Details">
                                                            👁️
                                                        </button>
                                                        
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="umkm_id" value="<?php echo $umkm['id']; ?>">
                                                            <button type="submit" name="delete_umkm" class="btn btn-danger btn-sm" 
                                                                    onclick="return confirmDelete('Hapus UMKM ini? Tindakan ini tidak dapat dibatalkan!')"
                                                                    data-tooltip="Delete">
                                                                🗑️
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Prevent any auto-scrolling behavior
        document.addEventListener('DOMContentLoaded', function() {
            // Store initial scroll position
            const initialScrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Reset scroll position if it changes unexpectedly
            let scrollCheckInterval = setInterval(function() {
                const currentScrollTop = window.pageYOffset || document.documentElement.scrollTop;
                if (Math.abs(currentScrollTop - initialScrollTop) > 100 && !window.userScrolling) {
                    window.scrollTo(0, initialScrollTop);
                }
            }, 100);
            
            // Track user scrolling
            let scrollTimeout;
            window.addEventListener('wheel', function() {
                window.userScrolling = true;
                clearTimeout(scrollTimeout);
                clearInterval(scrollCheckInterval);
                scrollTimeout = setTimeout(() => {
                    window.userScrolling = false;
                }, 1000);
            });
            
            window.addEventListener('touchmove', function() {
                window.userScrolling = true;
                clearTimeout(scrollTimeout);
                clearInterval(scrollCheckInterval);
                scrollTimeout = setTimeout(() => {
                    window.userScrolling = false;
                }, 1000);
            });
        });
        
        // Initialize Chart
        function initializeCharts() {
            const ctx = document.getElementById('umkmChart');
            if (ctx) {
                // Get parent container dimensions
                const container = ctx.parentElement;
                const containerWidth = container.offsetWidth;
                
                // Set canvas dimensions
                ctx.style.width = '100%';
                ctx.style.height = '150px';
                
                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Active', 'Pending', 'Inactive'],
                        datasets: [{
                            data: [
                                <?php echo $umkm_stats['active'] ?? 0; ?>,
                                <?php echo $umkm_stats['pending'] ?? 0; ?>,
                                <?php echo $umkm_stats['inactive'] ?? 0; ?>
                            ],
                            backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: 1000,
                            onComplete: function() {
                                // Ensure no scrolling after animation
                                window.scrollTo(0, 0);
                            }
                        },
                        layout: {
                            padding: 10
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        }
        
        // View UMKM Details
        function viewDetails(id) {
            showToast('Opening UMKM details...', 'info');
            // Implement view details functionality
        }
        
        // Export Data
        function exportData() {
            showToast('Exporting data...', 'info');
            // Implement export functionality
        }
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>