<?php
// admin/adminpemesanan.php

require_once '../config/database.php';

$db = getDbConnection();

// Handle delete booking
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM pemesanan WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Pemesanan berhasil dihapus!</div>';
    } else {
        $message = '<div class="alert alert-error">Gagal menghapus pemesanan!</div>';
    }
    $stmt->close();
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query with filters
$sql = "SELECT p.*, w.kategori FROM pemesanan p 
        LEFT JOIN wisata w ON p.wisata_id = w.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.user_name LIKE ? OR p.user_email LIKE ? OR p.wisata_judul LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($date_from)) {
    $sql .= " AND p.tanggal_kunjungan >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $sql .= " AND p.tanggal_kunjungan <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY p.created_at DESC";

// Execute query
$stmt = $db->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_pemesanan,
    SUM(total_harga) as total_pendapatan,
    SUM(jumlah_tiket) as total_tiket
    FROM pemesanan";
$stats_result = $db->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Data Pemesanan Tiket</title>
    <link rel="stylesheet" href="sidebar.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .page-header p {
            color: #666;
            margin-bottom: 30px;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            font-size: 2rem;
        }
        .stat-content h3 {
            margin: 0;
            font-size: 1.5rem;
        }
        .stat-content p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .filter-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-weight: bold;
            color: #333;
        }
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .filter-actions {
            display: flex;
            gap: 10px;
        }
        .filter-actions button, .btn-reset {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        .filter-actions button {
            background: #007bff;
            color: white;
        }
        .btn-reset {
            background: #6c757d;
            color: white;
        }
        .table-container {
            overflow-x: auto;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        .data-table tr:hover {
            background-color: #f5f5f5;
        }
        .category-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .category-budaya {
            background-color: #e1f5fe;
            color: #0277bd;
        }
        .category-alam {
            background-color: #e8f5e8;
            color: #2e7d32;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        .no-data h3 {
            margin-bottom: 10px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-actions {
                justify-content: center;
            }
            .data-table {
                font-size: 12px;
            }
            .data-table th, .data-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>Admin Dashboard</h1>
        </div>
        
        <nav class="nav-menu">
            <a href="index.php" class="btn">🏠 Admin Homepage</a>
            <a href="adminwisata.php" class="btn">🏞️ Admin Wisata</a>
            <a href="adminpenginapan.php" class="btn">🏨 Admin Penginapan</a>
            <a href="adminpemesanan.php" class="btn active">🏨 Admin Pemesanan</a>
        </nav>
        
        <div class="user-section">
            <span class="user-greeting">Halo, admin</span>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>📋 Data Pemesanan Tiket</h1>
                <p>Kelola semua pemesanan tiket wisata dari pengguna</p>
            </div>
            
            <?php if (isset($message)) echo $message; ?>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">🎫</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_pemesanan']); ?></h3>
                        <p>Total Pemesanan</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🎟️</div>
                    <div class="stat-content">
                        <h3><?php echo number_format($stats['total_tiket']); ?></h3>
                        <p>Total Tiket</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-content">
                        <h3><?php echo formatPrice($stats['total_pendapatan']); ?></h3>
                        <p>Total Pendapatan</p>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>Cari Data:</label>
                        <input type="text" name="search" placeholder="🔍 Cari nama, email, atau wisata..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Tanggal Kunjungan Dari:</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label>Sampai:</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit">🔍 Filter</button>
                        <?php if (!empty($search) || !empty($date_from) || !empty($date_to)): ?>
                            <a href="adminpemesanan.php" class="btn-reset">🔄 Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Bookings Table -->
            <div class="table-container">
                <?php if ($result->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tanggal Pesan</th>
                                <th>Nama Pemesan</th>
                                <th>Email</th>
                                <th>Wisata</th>
                                <th>Kategori</th>
                                <th>Tanggal Kunjungan</th>
                                <th>Jumlah Tiket</th>
                                <th>Harga Satuan</th>
                                <th>Total Harga</th>
                                <th>Catatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo formatDateTime($row['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user_email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['wisata_judul']); ?></td>
                                    <td>
                                        <?php if ($row['kategori']): ?>
                                            <span class="category-badge category-<?php echo $row['kategori']; ?>">
                                                <?php echo ucfirst($row['kategori']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="category-badge">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDate($row['tanggal_kunjungan']); ?></td>
                                    <td><?php echo number_format($row['jumlah_tiket']); ?></td>
                                    <td><?php echo formatPrice($row['harga_satuan']); ?></td>
                                    <td><strong><?php echo formatPrice($row['total_harga']); ?></strong></td>
                                    <td>
                                        <?php if (!empty($row['catatan'])): ?>
                                            <?php echo htmlspecialchars(substr($row['catatan'], 0, 50)); ?>
                                            <?php if (strlen($row['catatan']) > 50): ?>...<?php endif; ?>
                                        <?php else: ?>
                                            <em>Tidak ada catatan</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?delete=<?php echo $row['id']; ?>" 
                                           class="btn-delete"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus pemesanan ini?')">
                                            🗑️ Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <div style="font-size: 4rem; margin-bottom: 20px;">😔</div>
                        <h3>Belum Ada Data Pemesanan</h3>
                        <p>Tidak ada pemesanan yang ditemukan berdasarkan filter yang dipilih.</p>
                        <?php if (!empty($search) || !empty($date_from) || !empty($date_to)): ?>
                            <p>Coba ubah filter pencarian atau <a href="adminpemesanan.php">reset filter</a>.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Konfirmasi sebelum menghapus
        function confirmDelete(id) {
            if (confirm('Apakah Anda yakin ingin menghapus pemesanan ini? Tindakan ini tidak dapat dibatalkan.')) {
                window.location.href = '?delete=' + id;
            }
        }
        
        // Auto-hide alert messages
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>

<?php
// Close database connection
$stmt->close();
$db->close();
?>