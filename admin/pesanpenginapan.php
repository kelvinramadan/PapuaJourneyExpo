<?php
// admin/pesanpenginapan.php
if (!isset($_SESSION)) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Handle delete action
if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $db->prepare("DELETE FROM pesanpenginapan WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Data pemesanan berhasil dihapus!</div>';
    } else {
        $message = '<div class="alert alert-error">Gagal menghapus data pemesanan!</div>';
    }
    $stmt->close();
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Fetch filtered data
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(user_name LIKE ? OR user_email LIKE ? OR penginapan_judul LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($start_date)) {
    $where_conditions[] = "tanggal_checkin >= ?";
    $params[] = $start_date;
    $types .= 's';
}

if (!empty($end_date)) {
    $where_conditions[] = "tanggal_checkout <= ?";
    $params[] = $end_date;
    $types .= 's';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$query = "SELECT * FROM pesanpenginapan {$where_clause} ORDER BY created_at DESC";
$stmt = $db->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$pesanan_data = [];
while ($row = $result->fetch_assoc()) {
    $pesanan_data[] = $row;
}
$stmt->close();

// Calculate statistics
$total_pesanan = count($pesanan_data);
$total_pendapatan = array_sum(array_column($pesanan_data, 'total_harga'));
$total_kamar = array_sum(array_column($pesanan_data, 'jumlah_kamar'));

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

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pemesanan Penginapan - Admin Papua Journey</title>
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
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: #667eea;
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #666;
            font-size: 0.9rem;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .filters form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background-color: #5a67d8;
        }

        .btn-danger {
            background-color: #e53e3e;
            color: white;
            font-size: 0.8rem;
            padding: 5px 10px;
        }

        .btn-danger:hover {
            background-color: #c53030;
        }

        .btn-reset {
            background-color: #718096;
            color: white;
        }

        .btn-reset:hover {
            background-color: #4a5568;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background-color: #667eea;
            color: white;
            padding: 15px 10px;
            text-align: left;
            font-weight: bold;
        }

        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
        }

        .table tr:hover {
            background-color: #f8f9ff;
        }

        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .table tr:nth-child(even):hover {
            background-color: #f0f0ff;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
            display: block;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .catatan {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .catatan:hover {
            white-space: normal;
            word-wrap: break-word;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .filters form {
                flex-direction: column;
            }

            .form-group {
                min-width: 100%;
            }

            .table-container {
                overflow-x: auto;
            }

            .table {
                min-width: 800px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Kembali ke Dashboard</a>
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h1>Admin Dashboard</h1>
            </div>
            
            <nav class="nav-menu">
                <a href="index.php" class="btn">🏠 Admin Homepage</a>
                <a href="adminwisata.php" class="btn">🏞️ Admin Wisata</a>
                <a href="adminpenginapan.php" class="btn">🏨 Admin Penginapan</a>
                <a href="adminpemesanan.php" class="btn">🏨 Admin Pemesanan</a>
                <a href="pesanpenginapan.php" class="btn active">🏨 Admin Pemesanan</a>
            </nav>
            
            <div class="user-section">
                <span class="user-greeting">Halo, admin</span>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>
        <div class="header">
            <h1>📋 Data Pemesanan Penginapan</h1>
            <p>Kelola dan pantau semua pemesanan penginapan</p>
        </div>

        <?php if (isset($message)) echo $message; ?>


        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_pesanan; ?></h3>
                <p>Total Pemesanan</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_kamar; ?></h3>
                <p>Total Kamar Dipesan</p>
            </div>
            <div class="stat-card">
                <h3><?php echo formatPrice($total_pendapatan); ?></h3>
                <p>Total Pendapatan</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="form-group">
                    <label for="search">Cari Pemesanan:</label>
                    <input type="text" name="search" id="search" 
                           placeholder="Nama, email, atau penginapan..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="form-group">
                    <label for="start_date">Dari Tanggal:</label>
                    <input type="date" name="start_date" id="start_date" 
                           value="<?php echo htmlspecialchars($start_date); ?>">
                </div>
                
                <div class="form-group">
                    <label for="end_date">Sampai Tanggal:</label>
                    <input type="date" name="end_date" id="end_date" 
                           value="<?php echo htmlspecialchars($end_date); ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                    <?php if (!empty($search) || !empty($start_date) || !empty($end_date)): ?>
                        <a href="pesanpenginapan.php" class="btn btn-reset">🔄 Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="table-container">
            <?php if (!empty($pesanan_data)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pemesan</th>
                            <th>Email</th>
                            <th>Penginapan</th>
                            <th>Kamar</th>
                            <th>Malam</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Harga/Malam</th>
                            <th>Total</th>
                            <th>Catatan</th>
                            <th>Tanggal Pesan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pesanan_data as $pesanan): ?>
                            <tr>
                                <td><?php echo $pesanan['id']; ?></td>
                                <td><?php echo htmlspecialchars($pesanan['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($pesanan['user_email']); ?></td>
                                <td><?php echo htmlspecialchars($pesanan['penginapan_judul']); ?></td>
                                <td><?php echo $pesanan['jumlah_kamar']; ?></td>
                                <td><?php echo $pesanan['jumlah_malam']; ?></td>
                                <td><?php echo formatDate($pesanan['tanggal_checkin']); ?></td>
                                <td><?php echo formatDate($pesanan['tanggal_checkout']); ?></td>
                                <td><?php echo formatPrice($pesanan['harga_per_malam']); ?></td>
                                <td><strong><?php echo formatPrice($pesanan['total_harga']); ?></strong></td>
                                <td class="catatan" title="<?php echo htmlspecialchars($pesanan['catatan']); ?>">
                                    <?php echo $pesanan['catatan'] ? htmlspecialchars($pesanan['catatan']) : '-'; ?>
                                </td>
                                <td><?php echo formatDateTime($pesanan['created_at']); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Yakin ingin menghapus pemesanan ini?')">
                                        <input type="hidden" name="delete_id" value="<?php echo $pesanan['id']; ?>">
                                        <button type="submit" class="btn btn-danger">🗑️ Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i>📋</i>
                    <h3>Tidak Ada Data Pemesanan</h3>
                    <p>Belum ada pemesanan penginapan yang masuk atau sesuai dengan filter yang dipilih.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Set default end date when start date is selected
        document.getElementById('start_date').addEventListener('change', function() {
            const startDate = this.value;
            const endDateInput = document.getElementById('end_date');
            
            if (startDate && !endDateInput.value) {
                const start = new Date(startDate);
                const end = new Date(start);
                end.setMonth(end.getMonth() + 1); // Default to 1 month later
                endDateInput.value = end.toISOString().split('T')[0];
            }
        });

        // Confirm before delete
        document.querySelectorAll('form[onsubmit*="confirm"]').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Yakin ingin menghapus pemesanan ini? Data yang dihapus tidak dapat dikembalikan!')) {
                    e.preventDefault();
                }
            });
        });

        // Search form enhancement - submit on Enter
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    </script>
</body>
</html>