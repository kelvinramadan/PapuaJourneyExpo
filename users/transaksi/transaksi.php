<?php
session_start();

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../../login.php');
    exit();
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

require_once '../../config/database.php';

// Get user details from database
$db = getDbConnection();

// Get bookings
$bookings_query = "SELECT pt.*, a.judul, a.gambar, a.kategori, u.business_name
                   FROM pemesanan_tiket pt 
                   JOIN artikel a ON pt.artikel_id = a.id
                   JOIN umkm u ON a.umkm_id = u.id
                   WHERE pt.user_id = ?
                   ORDER BY pt.created_at DESC";

$bookings_stmt = $db->prepare($bookings_query);
$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();
$bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
$bookings_stmt->close();

$db->close();

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('d M Y H:i', strtotime($datetime));
}

function getCategoryIcon($kategori) {
    $icons = [
        'jasa' => '🔧',
        'event' => '🎉',
        'kuliner' => '🍽️',
        'kerajinan' => '🎨',
        'wisata' => '🏝️'
    ];
    return $icons[$kategori] ?? '📄';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Omaki Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .content {
            padding: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-back {
            background: #95a5a6;
            color: white;
            margin-bottom: 1rem;
        }

        .btn-back:hover {
            background: #7f8c8d;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .article-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .article-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }

        .article-image-placeholder {
            width: 60px;
            height: 60px;
            background: #e0e0e0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .article-details h4 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .article-details .category {
            background: #e8f4fd;
            color: #3498db;
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .price {
            font-weight: 600;
            color: #27ae60;
            font-size: 1.1rem;
        }

        .no-data {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .no-data h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .no-data p {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.5rem;
            }

            .content {
                padding: 1rem;
            }

            th, td {
                padding: 0.5rem;
                font-size: 0.9rem;
            }

            .article-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .article-image,
            .article-image-placeholder {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    
    <div class="container">
        <div class="header">
            <h1>🎫 Riwayat Transaksi</h1>
            <p>Kelola dan pantau semua pemesanan tiket Anda</p>
        </div>

        <div class="content">
            <?php if (count($bookings) > 0): ?>
                <!-- Bookings Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Artikel</th>
                                <th>UMKM</th>
                                <th>Jumlah Tiket</th>
                                <th>Total Harga</th>
                                <th>Tanggal Kunjungan</th>
                                <th>Tanggal Pesan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $index => $booking): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="article-info">
                                            <?php if ($booking['gambar']): ?>
                                                <img src="../../uploads/artikel_images/<?php echo htmlspecialchars($booking['gambar']); ?>" 
                                                     alt="<?php echo htmlspecialchars($booking['judul']); ?>" 
                                                     class="article-image">
                                            <?php else: ?>
                                                <div class="article-image-placeholder">
                                                    <?php echo getCategoryIcon($booking['kategori']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="article-details">
                                                <h4><?php echo htmlspecialchars($booking['judul']); ?></h4>
                                                <div class="category">
                                                    <?php echo getCategoryIcon($booking['kategori']) . ' ' . ucfirst($booking['kategori']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($booking['business_name']); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo $booking['jumlah_tiket']; ?></strong> tiket
                                    </td>
                                    <td>
                                        <div class="price"><?php echo formatPrice($booking['total_harga']); ?></div>
                                    </td>
                                    <td>
                                        📅 <?php echo formatDate($booking['tanggal_kunjungan']); ?>
                                    </td>
                                    <td>
                                        🕒 <?php echo formatDateTime($booking['created_at']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <div class="no-data">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📋</div>
                    <h3>Belum Ada Transaksi</h3>
                    <p>Anda belum memiliki riwayat pemesanan tiket.</p>
                    <p>Mulai jelajahi dan pesan tiket untuk berbagai aktivitas menarik!</p>
                    <a href="user_dashboard.php" class="btn btn-primary" style="margin-top: 1rem;">
                        🎫 Mulai Pesan Tiket
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>