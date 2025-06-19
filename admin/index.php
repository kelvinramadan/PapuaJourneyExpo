<?php
// admin/index.php
session_start();
require_once '../config/database.php';

// Simple admin authentication
$admin_username = 'admin';
$admin_password = 'admin123';

$error_message = '';
$success_message = '';

// Handle admin login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['admin_login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
    } else {
        $error_message = 'Username atau password admin salah!';
    }
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
            $success_message = 'Status UMKM berhasil diperbarui!';
        } else {
            $error_message = 'Gagal memperbarui status UMKM!';
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
        $success_message = 'UMKM berhasil dihapus!';
    } else {
        $error_message = 'Gagal menghapus UMKM!';
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
        <title>Admin Login - Omaki Platform</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .login-container {
                background: white;
                padding: 2rem;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                width: 100%;
                max-width: 400px;
            }
            
            .login-header {
                text-align: center;
                margin-bottom: 2rem;
            }
            
            .login-header h1 {
                color: #333;
                margin-bottom: 0.5rem;
            }
            
            .form-group {
                margin-bottom: 1rem;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 0.5rem;
                color: #333;
                font-weight: 500;
            }
            
            .form-group input {
                width: 100%;
                padding: 0.75rem;
                border: 2px solid #ddd;
                border-radius: 5px;
                font-size: 1rem;
            }
            
            .form-group input:focus {
                outline: none;
                border-color: #667eea;
            }
            
            .btn {
                width: 100%;
                padding: 0.75rem;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 1rem;
                cursor: pointer;
            }
            
            .btn:hover {
                opacity: 0.9;
            }
            
            .error-message {
                background: #ffebee;
                color: #c62828;
                padding: 0.75rem;
                border-radius: 5px;
                margin-bottom: 1rem;
                border-left: 4px solid #c62828;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <h1>Admin Login</h1>
                <p>Masuk ke panel admin Omaki</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" required>
                </div>
                
                <button type="submit" name="admin_login" class="btn">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Get UMKM data for admin dashboard
$db = getDbConnection();
$stmt = $db->prepare("SELECT * FROM umkm ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$umkm_list = $result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$stats_stmt = $db->prepare("SELECT status, COUNT(*) as count FROM umkm GROUP BY status");
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
}

$stmt->close();
$stats_stmt->close();
$db->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Omaki Platform</title>
    <link rel="stylesheet" href="sidebar.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            overflow-x: hidden;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-pending { color: #FF9800; }
        .stat-active { color: #4CAF50; }
        .stat-inactive { color: #f44336; }

        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
        }

        /* Card */
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        /* Alert */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        /* Status Badge */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 2px;
            transition: background-color 0.3s;
        }

        .btn-approve {
            background: #28a745;
            color: white;
        }

        .btn-approve:hover {
            background: #218838;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
        }

        .btn-reject:hover {
            background: #c82333;
        }

        .btn-deactivate {
            background: #ffc107;
            color: #212529;
        }

        .btn-deactivate:hover {
            background: #e0a800;
        }

        .btn-delete {
            background: #6c757d;
            color: white;
        }

        .btn-delete:hover {
            background: #5a6268;
        }

        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .table {
                font-size: 14px;
            }

            .table th,
            .table td {
                padding: 8px;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin: 2px 0;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }

            .stat-number {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <button class="mobile-toggle" onclick="toggleSidebar()">☰</button>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>Admin Dashboard</h1>
        </div>
        
        <nav class="nav-menu">
            <a href="index.php" class="btn active">🏠 Admin Homepage</a>
            <a href="adminwisata.php" class="btn">🏞️ Admin Wisata</a>
            <a href="adminpenginapan.php" class="btn">🏨 Admin Penginapan</a>
            <a href="adminpemesanan.php" class="btn">🏨 Admin Pemesanan</a>
            <a href="pesanpenginapan.php" class="btn">🏨 Admin Pemesanan</a>
        </nav>
        
        <div class="user-section">
            <div class="user-greeting">Halo, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number stat-pending"><?php echo isset($stats['pending']) ? $stats['pending'] : 0; ?></div>
                <div class="stat-label">Menunggu Persetujuan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-active"><?php echo isset($stats['active']) ? $stats['active'] : 0; ?></div>
                <div class="stat-label">UMKM Aktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-number stat-inactive"><?php echo isset($stats['inactive']) ? $stats['inactive'] : 0; ?></div>
                <div class="stat-label">UMKM Tidak Aktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($umkm_list); ?></div>
                <div class="stat-label">Total UMKM</div>
            </div>
        </div>
        
        <!-- UMKM List -->
        <div class="card">
            <h2>Manajemen UMKM</h2>
            
            <?php if (empty($umkm_list)): ?>
                <p>Belum ada UMKM yang terdaftar.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Usaha</th>
                            <th>Pemilik</th>
                            <th>Email</th>
                            <th>Jenis Usaha</th>
                            <th>Status</th>
                            <th>Terdaftar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($umkm_list as $umkm): ?>
                            <tr>
                                <td>#<?php echo $umkm['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($umkm['business_name']); ?></strong>
                                    <?php if ($umkm['description']): ?>
                                        <br><small><?php echo htmlspecialchars(substr($umkm['description'], 0, 50)) . '...'; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($umkm['owner_name']); ?></td>
                                <td><?php echo htmlspecialchars($umkm['email']); ?></td>
                                <td><?php echo ucfirst($umkm['business_type']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $umkm['status']; ?>">
                                        <?php echo ucfirst($umkm['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($umkm['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <?php if ($umkm['status'] == 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="umkm_id" value="<?php echo $umkm['id']; ?>">
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" name="update_status" class="btn btn-approve" 
                                                        onclick="return confirm('Setujui UMKM ini?')">
                                                    Setujui
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="umkm_id" value="<?php echo $umkm['id']; ?>">
                                                <input type="hidden" name="status" value="inactive">
                                                <button type="submit" name="update_status" class="btn btn-reject" 
                                                        onclick="return confirm('Tolak UMKM ini?')">
                                                    Tolak
                                                </button>
                                            </form>
                                        <?php elseif ($umkm['status'] == 'active'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="umkm_id" value="<?php echo $umkm['id']; ?>">
                                                <input type="hidden" name="status" value="inactive">
                                                <button type="submit" name="update_status" class="btn btn-deactivate" 
                                                        onclick="return confirm('Nonaktifkan UMKM ini?')">
                                                    Nonaktifkan
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="umkm_id" value="<?php echo $umkm['id']; ?>">
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" name="update_status" class="btn btn-approve" 
                                                        onclick="return confirm('Aktifkan UMKM ini?')">
                                                    Aktifkan
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="umkm_id" value="<?php echo $umkm['id']; ?>">
                                            <button type="submit" name="delete_umkm" class="btn btn-delete" 
                                                    onclick="return confirm('Hapus UMKM ini? Tindakan ini tidak dapat dibatalkan!')">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle sidebar for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        // Add active state to navigation buttons
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-menu .btn').forEach(btn => {
            if (btn.getAttribute('href') === currentPage) {
                btn.classList.add('active');
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html>