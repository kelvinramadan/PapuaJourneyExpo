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

// Get new unified transactions
$transaksi_query = "SELECT t.*, 
                    (SELECT COUNT(*) FROM transaksi_items WHERE transaksi_id = t.id) as item_count
                    FROM transaksi t
                    WHERE t.user_id = ?
                    ORDER BY t.created_at DESC";

$transaksi_stmt = $db->prepare($transaksi_query);
$transaksi_stmt->bind_param("i", $user_id);
$transaksi_stmt->execute();
$transaksi_result = $transaksi_stmt->get_result();
$transactions = $transaksi_result->fetch_all(MYSQLI_ASSOC);
$transaksi_stmt->close();

// Get old bookings (for backward compatibility)
$old_bookings_query = "SELECT 'wisata' as type, 
                              p.id, 
                              p.total_harga, 
                              p.created_at,
                              w.judul as item_name, 
                              w.photo as item_image, 
                              w.kategori,
                              p.jumlah_tiket,
                              p.tanggal_kunjungan,
                              NULL as tanggal_checkin,
                              NULL as jumlah_kamar
                       FROM pemesanan p
                       JOIN wisata w ON p.wisata_id = w.id
                       WHERE p.user_id = ?
                       
                       UNION ALL
                       
                       SELECT 'penginapan' as type, 
                              pp.id, 
                              pp.total_harga, 
                              pp.created_at,
                              p.judul as item_name, 
                              p.photo as item_image, 
                              p.tipe as kategori,
                              NULL as jumlah_tiket,
                              NULL as tanggal_kunjungan,
                              pp.tanggal_checkin,
                              pp.jumlah_kamar
                       FROM pesanpenginapan pp
                       JOIN penginapan p ON pp.penginapan_id = p.id
                       WHERE pp.user_id = ?
                       
                       UNION ALL
                       
                       SELECT 'artikel' as type, 
                              pt.id, 
                              pt.total_harga, 
                              pt.created_at,
                              a.judul as item_name, 
                              a.gambar as item_image, 
                              a.kategori,
                              pt.jumlah_tiket,
                              pt.tanggal_kunjungan,
                              NULL as tanggal_checkin,
                              NULL as jumlah_kamar
                       FROM pemesanan_tiket pt
                       JOIN artikel a ON pt.artikel_id = a.id
                       WHERE pt.user_id = ?
                       
                       ORDER BY created_at DESC";

$old_stmt = $db->prepare($old_bookings_query);
if ($old_stmt === false) {
    // If prepare failed, show the error
    die("Prepare failed: " . $db->error);
}
$old_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$old_stmt->execute();
$old_result = $old_stmt->get_result();
$old_bookings = $old_result->fetch_all(MYSQLI_ASSOC);
$old_stmt->close();

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
        'wisata' => '🏝️',
        'budaya' => '🎭',
        'alam' => '🌿',
        'hotel' => '🏨',
        'villa' => '🏖️',
        'resort' => '🌴'
    ];
    return $icons[$kategori] ?? '📄';
}

function getPaymentStatusBadge($status) {
    $badges = [
        'pending' => '<span style="background: #f39c12; color: white; padding: 4px 12px; border-radius: 15px; font-size: 0.85rem;">⏳ Menunggu Pembayaran</span>',
        'awaiting_confirmation' => '<span style="background: #17a2b8; color: white; padding: 4px 12px; border-radius: 15px; font-size: 0.85rem;">🔍 Menunggu Konfirmasi</span>',
        'paid' => '<span style="background: #27ae60; color: white; padding: 4px 12px; border-radius: 15px; font-size: 0.85rem;">✅ Dibayar</span>',
        'rejected' => '<span style="background: #e74c3c; color: white; padding: 4px 12px; border-radius: 15px; font-size: 0.85rem;">❌ Pembayaran Ditolak</span>',
        'cancelled' => '<span style="background: #6c757d; color: white; padding: 4px 12px; border-radius: 15px; font-size: 0.85rem;">🚫 Dibatalkan</span>'
    ];
    return $badges[$status] ?? $status;
}

function getPaymentMethodName($method) {
    $methods = [
        'bank_transfer' => 'Transfer Bank',
        'e_wallet' => 'E-Wallet',
        'credit_card' => 'Kartu Kredit/Debit'
    ];
    return $methods[$method] ?? $method;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Omaki Platform</title>
    <link rel="stylesheet" href="transaksi.css">
    <style>

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
    <?php include '../components/navbar.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1>🎫 Riwayat Transaksi</h1>
            <p>Kelola dan pantau semua pemesanan tiket Anda</p>
        </div>

        <div class="content">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    ✅ <?php echo $_SESSION['success_message']; ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    ❌ <?php echo $_SESSION['error_message']; ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <?php if (count($transactions) > 0 || count($old_bookings) > 0): ?>
                
                <?php if (count($transactions) > 0): ?>
                <!-- New Transactions -->
                <h2 style="margin-bottom: 20px; color: #333;">📋 Transaksi Terbaru</h2>
                <?php 
                // Re-establish database connection once for all transaction items
                $db = getDbConnection();
                
                foreach ($transactions as $transaction): ?>
                    <?php
                    // Get transaction items
                    $items_query = "SELECT * FROM transaksi_items WHERE transaksi_id = ?";
                    $items_stmt = $db->prepare($items_query);
                    $items_stmt->bind_param("i", $transaction['id']);
                    $items_stmt->execute();
                    $items_result = $items_stmt->get_result();
                    $items = $items_result->fetch_all(MYSQLI_ASSOC);
                    $items_stmt->close();
                    ?>
                    
                    <div style="background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <h3 style="margin: 0; color: #333;">Kode: <?php echo htmlspecialchars($transaction['transaction_code']); ?></h3>
                                <p style="margin: 5px 0; color: #666;">🕒 <?php echo formatDateTime($transaction['created_at']); ?></p>
                            </div>
                            <div style="text-align: right;">
                                <?php echo getPaymentStatusBadge($transaction['payment_status']); ?>
                                <p style="margin: 5px 0; color: #666; font-size: 0.9rem;"><?php echo getPaymentMethodName($transaction['payment_method']); ?></p>
                            </div>
                        </div>
                        
                        <div style="border-top: 1px solid #eee; padding-top: 15px;">
                            <?php foreach ($items as $item): ?>
                                <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f5f5f5;">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                        <span style="color: #666; font-size: 0.9rem;">
                                            (<?php echo ucfirst($item['item_type']); ?>)
                                        </span>
                                        <?php if ($item['booking_date']): ?>
                                            <br><small>📅 <?php echo formatDate($item['booking_date']); ?></small>
                                        <?php endif; ?>
                                        <?php if ($item['checkin_date'] && $item['checkout_date']): ?>
                                            <br><small>📅 <?php echo formatDate($item['checkin_date']); ?> - <?php echo formatDate($item['checkout_date']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div style="text-align: right;">
                                        <div>Qty: <?php echo $item['quantity']; ?></div>
                                        <div style="color: #27ae60; font-weight: 600;"><?php echo formatPrice($item['subtotal']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #eee; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <strong style="font-size: 1.2rem;">Total</strong>
                                <strong style="font-size: 1.3rem; color: #27ae60; margin-left: 10px;"><?php echo formatPrice($transaction['total_amount']); ?></strong>
                            </div>
                            
                            <?php if ($transaction['payment_status'] === 'pending'): ?>
                            <form action="../checkout/payment_instructions.php" method="POST" style="display: inline;">
                                <input type="hidden" name="transaction_code" value="<?php echo $transaction['transaction_code']; ?>">
                                <input type="hidden" name="total_amount" value="<?php echo $transaction['total_amount']; ?>">
                                <input type="hidden" name="payment_method" value="<?php echo $transaction['payment_method']; ?>">
                                <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                <?php 
                                $_SESSION['transaction_code'] = $transaction['transaction_code'];
                                $_SESSION['total_amount'] = $transaction['total_amount'];
                                $_SESSION['payment_method'] = $transaction['payment_method'];
                                $_SESSION['transaction_id'] = $transaction['id'];
                                ?>
                                <button type="submit" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.9rem;">
                                    💳 Bayar Sekarang
                                </button>
                            </form>
                            <?php elseif ($transaction['payment_status'] === 'rejected'): ?>
                            <form action="../checkout/payment_instructions.php" method="POST" style="display: inline;">
                                <input type="hidden" name="transaction_code" value="<?php echo $transaction['transaction_code']; ?>">
                                <input type="hidden" name="total_amount" value="<?php echo $transaction['total_amount']; ?>">
                                <input type="hidden" name="payment_method" value="<?php echo $transaction['payment_method']; ?>">
                                <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                <?php 
                                $_SESSION['transaction_code'] = $transaction['transaction_code'];
                                $_SESSION['total_amount'] = $transaction['total_amount'];
                                $_SESSION['payment_method'] = $transaction['payment_method'];
                                $_SESSION['transaction_id'] = $transaction['id'];
                                ?>
                                <button type="submit" class="btn btn-primary" style="padding: 8px 20px; font-size: 0.9rem;">
                                    🔄 Upload Ulang Bukti
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (count($old_bookings) > 0): ?>
                <!-- Old Bookings Table -->
                <h2 style="margin: 30px 0 20px; color: #333;">📋 Riwayat Pemesanan Lama</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Item</th>
                                <th>Tipe</th>
                                <th>Jumlah</th>
                                <th>Total Harga</th>
                                <th>Tanggal</th>
                                <th>Waktu Pesan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($old_bookings as $index => $booking): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <div class="article-info">
                                            <?php if ($booking['item_image']): ?>
                                                <?php
                                                $image_path = ($booking['type'] == 'artikel') 
                                                    ? '../../uploads/artikel_images/' . $booking['item_image']
                                                    : '../../uploads/' . $booking['item_image'];
                                                ?>
                                                <img src="<?php echo $image_path; ?>" 
                                                     alt="<?php echo htmlspecialchars($booking['item_name']); ?>" 
                                                     class="article-image">
                                            <?php else: ?>
                                                <div class="article-image-placeholder">
                                                    <?php echo getCategoryIcon($booking['kategori'] ?? $booking['type']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="article-details">
                                                <h4><?php echo htmlspecialchars($booking['item_name']); ?></h4>
                                                <div class="category">
                                                    <?php echo getCategoryIcon($booking['kategori'] ?? $booking['type']) . ' ' . ucfirst($booking['kategori'] ?? $booking['type']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?php echo ucfirst($booking['type']); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo $booking['jumlah_tiket'] ?? $booking['jumlah_kamar'] ?? 1; ?></strong>
                                        <?php echo $booking['type'] == 'penginapan' ? 'kamar' : 'tiket'; ?>
                                    </td>
                                    <td>
                                        <div class="price"><?php echo formatPrice($booking['total_harga']); ?></div>
                                    </td>
                                    <td>
                                        📅 <?php echo formatDate($booking['tanggal_kunjungan'] ?? $booking['tanggal_checkin'] ?? $booking['created_at']); ?>
                                    </td>
                                    <td>
                                        🕒 <?php echo formatDateTime($booking['created_at']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-data">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📋</div>
                    <h3>Belum Ada Transaksi</h3>
                    <p>Anda belum memiliki riwayat pemesanan tiket.</p>
                    <p>Mulai jelajahi dan pesan tiket untuk berbagai aktivitas menarik!</p>
                    <a href="../dashboard/user_dashboard.php" class="btn btn-primary" style="margin-top: 1rem;">
                        🎫 Mulai Pesan Tiket
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>