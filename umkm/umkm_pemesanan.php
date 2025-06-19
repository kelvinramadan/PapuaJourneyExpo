<?php
// umkm/umkm_pemesanan.php
session_start();

require_once '../config/database.php';
include 'navbar.php';

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

// Get filter parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Count total pemesanan
$count_query = "SELECT COUNT(*) as total 
                FROM pemesanan_tiket pt 
                JOIN artikel a ON pt.artikel_id = a.id 
                WHERE a.umkm_id = ?";

$count_stmt = $db->prepare($count_query);
$count_stmt->bind_param("i", $umkm_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_pemesanan = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_pemesanan / $limit);
$count_stmt->close();

// Get pemesanan with pagination
$pemesanan_query = "SELECT pt.*, a.judul as artikel_judul, a.harga as artikel_harga, a.kategori, u.full_name as user_name
                    FROM pemesanan_tiket pt 
                    JOIN artikel a ON pt.artikel_id = a.id 
                    JOIN users u ON pt.user_id = u.id
                    WHERE a.umkm_id = ?
                    ORDER BY pt.created_at DESC 
                    LIMIT ? OFFSET ?";

$pemesanan_stmt = $db->prepare($pemesanan_query);
$pemesanan_stmt->bind_param("iii", $umkm_id, $limit, $offset);
$pemesanan_stmt->execute();
$pemesanan_result = $pemesanan_stmt->get_result();
$pemesanan_list = $pemesanan_result->fetch_all(MYSQLI_ASSOC);
$pemesanan_stmt->close();

// Get statistics
$stats_query = "SELECT 
                    COUNT(*) as total_pemesanan,
                    SUM(pt.total_harga) as total_pendapatan
                FROM pemesanan_tiket pt 
                JOIN artikel a ON pt.artikel_id = a.id 
                WHERE a.umkm_id = ?";

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
    return date('d M Y H:i', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pemesanan - UMKM Papua</title>
    <link rel="stylesheet" href="umkm.css">
    <style>
        .pemesanan-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .header-section h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 4px solid #667eea;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .pemesanan-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .table-header {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .table-header h3 {
            margin: 0;
            color: #333;
        }
        
        .pemesanan-item {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.3s;
        }
        
        .pemesanan-item:hover {
            background-color: #f8f9fa;
        }
        
        .pemesanan-item:last-child {
            border-bottom: none;
        }
        
        .pemesanan-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .pemesanan-info {
            flex: 1;
        }
        
        .pemesanan-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .pemesanan-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }
        
        .pemesanan-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .detail-group {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
        }
        
        .detail-group h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-group p {
            margin: 0.25rem 0;
            color: #666;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #d4edda;
            color: #155724;
        }
        
        .price-highlight {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 5px;
            color: #667eea;
        }
        
        .pagination a:hover {
            background: #f0f0f0;
        }
        
        .pagination .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .pemesanan-container {
                padding: 1rem;
            }
            
            .pemesanan-header {
                flex-direction: column;
            }
            
            .pemesanan-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="pemesanan-container">
        <!-- Header -->
        <div class="header-section">
            <h1>📋 Data Pemesanan Tiket</h1>
            <p>Pantau semua pemesanan tiket dari pelanggan</p>
            <div style="margin-top: 1rem;">
                <strong>🏪 <?php echo htmlspecialchars($umkm_data['business_name']); ?></strong>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_pemesanan']; ?></div>
                <div class="stat-label">Total Pemesanan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo formatPrice($stats['total_pendapatan']); ?></div>
                <div class="stat-label">Total Pendapatan</div>
            </div>
        </div>

        <!-- Pemesanan List -->
        <div class="pemesanan-table">
            <div class="table-header">
                <h3>📋 Daftar Pemesanan (<?php echo count($pemesanan_list); ?> dari <?php echo $total_pemesanan; ?>)</h3>
            </div>

            <?php if (empty($pemesanan_list)): ?>
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <h3>Belum Ada Pemesanan</h3>
                    <p>Pemesanan tiket dari pelanggan akan muncul di sini.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pemesanan_list as $pemesanan): ?>
                    <div class="pemesanan-item">
                        <div class="pemesanan-header">
                            <div class="pemesanan-info">
                                <div class="pemesanan-title">
                                    🎫 <?php echo htmlspecialchars($pemesanan['artikel_judul']); ?>
                                </div>
                                <div class="pemesanan-meta">
                                    <span>📅 <?php echo formatDate($pemesanan['created_at']); ?></span>
                                    <span>👤 <?php echo htmlspecialchars($pemesanan['user_name']); ?></span>
                                    <span>🏷️ <?php echo ucfirst($pemesanan['kategori']); ?></span>
                                </div>
                            </div>
                            <div class="status-badge">
                                ✅ Berhasil Dipesan
                            </div>
                        </div>

                        <div class="pemesanan-details">
                            <div class="detail-group">
                                <h4>📋 Detail Pemesanan</h4>
                                <p><strong>Jumlah Tiket:</strong> <?php echo $pemesanan['jumlah_tiket']; ?> tiket</p>
                                <p><strong>Tanggal Kunjungan:</strong> <?php echo formatDate($pemesanan['tanggal_kunjungan']); ?></p>
                                <p class="price-highlight"><strong>Total Harga:</strong> <?php echo formatPrice($pemesanan['total_harga']); ?></p>
                            </div>

                            <div class="detail-group">
                                <h4>👤 Data Pemesan</h4>
                                <p><strong>Nama:</strong> <?php echo htmlspecialchars($pemesanan['nama_pemesan']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($pemesanan['email_pemesan']); ?></p>
                                <p><strong>Telepon:</strong> <?php echo htmlspecialchars($pemesanan['phone_pemesan']); ?></p>
                            </div>

                            <?php if ($pemesanan['catatan']): ?>
                            <div class="detail-group">
                                <h4>📝 Catatan Tambahan</h4>
                                <p><?php echo nl2br(htmlspecialchars($pemesanan['catatan'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>">‹ Prev</a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>">Next ›</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Back to Dashboard -->
        <div style="text-align: center; margin-top: 2rem;">
            <a href="umkm_dashboard.php" style="color: #667eea; text-decoration: none; font-weight: bold;">
                ⬅️ Kembali ke Dashboard UMKM
            </a>
        </div>
    </div>
</body>
</html>