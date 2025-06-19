<?php
// users/userwisata.php
// Start session first before any output
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../config/database.php';

$db = getDbConnection();

// Get session data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Handle ticket booking
$booking_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_ticket'])) {
    $wisata_id = (int)$_POST['wisata_id'];
    $jumlah_tiket = (int)$_POST['jumlah_tiket'];
    $tanggal_kunjungan = $_POST['tanggal_kunjungan'];
    $catatan = $_POST['catatan'];
    
    // Get wisata details
    $stmt = $db->prepare("SELECT judul, harga FROM wisata WHERE id = ?");
    $stmt->bind_param("i", $wisata_id);
    $stmt->execute();
    $wisata_result = $stmt->get_result();
    $wisata_data = $wisata_result->fetch_assoc();
    $stmt->close();
    
    if ($wisata_data && $jumlah_tiket > 0) {
        $harga_satuan = $wisata_data['harga'];
        $total_harga = $harga_satuan * $jumlah_tiket;
        $wisata_judul = $wisata_data['judul'];
        
        // Insert booking
        $stmt = $db->prepare("INSERT INTO pemesanan (user_id, user_name, user_email, wisata_id, wisata_judul, jumlah_tiket, harga_satuan, total_harga, tanggal_kunjungan, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issisiidss", $user_id, $user_name, $user_email, $wisata_id, $wisata_judul, $jumlah_tiket, $harga_satuan, $total_harga, $tanggal_kunjungan, $catatan);
        
        if ($stmt->execute()) {
            $booking_message = '<div class="alert alert-success">Pemesanan tiket berhasil! Total: ' . formatPrice($total_harga) . '</div>';
        } else {
            $booking_message = '<div class="alert alert-error">Gagal melakukan pemesanan. Silakan coba lagi.</div>';
        }
        $stmt->close();
    }
}

// Get filter parameters
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'list';
$wisata_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function truncateText($text, $limit) {
    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
}

// Handle detail view
$wisata_detail = null;
if ($view_mode === 'detail' && $wisata_id > 0) {
    $stmt = $db->prepare("SELECT * FROM wisata WHERE id = ?");
    $stmt->bind_param("i", $wisata_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $wisata_detail = $result->fetch_assoc();
    $stmt->close();
}

// Build query with filters for list view
if ($view_mode === 'list') {
    $sql = "SELECT * FROM wisata WHERE 1=1";
    $params = [];

    if (!empty($kategori_filter)) {
        $sql .= " AND kategori = ?";
        $params[] = $kategori_filter;
    }

    if (!empty($search)) {
        $sql .= " AND (judul LIKE ? OR deskripsi LIKE ? OR alamat LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $sql .= " ORDER BY created_at DESC";

    // Prepare and execute query
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Simpan data yang diperlukan sebelum output HTML
    $wisata_data = [];
    while ($row = $result->fetch_assoc()) {
        $wisata_data[] = $row;
    }
    $stmt->close();
}

// Get related wisata for detail view
$related_wisata = [];
if ($view_mode === 'detail' && $wisata_detail) {
    $stmt = $db->prepare("SELECT * FROM wisata WHERE kategori = ? AND id != ? ORDER BY created_at DESC LIMIT 3");
    $stmt->bind_param("si", $wisata_detail['kategori'], $wisata_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $related_wisata[] = $row;
    }
    $stmt->close();
}

mysqli_close($db);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wisata Papua - Jelajahi Keindahan Papua</title>
    <link rel="stylesheet" href="userwisata.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <?php if ($view_mode === 'list'): ?>
                <div class="page-header">
                    <h1>🏝️ Wisata Papua</h1>
                    <p>Jelajahi keindahan alam dan budaya Papua yang memukau</p>
                </div>
                
                <div class="filters">
                    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                        <input type="text" name="search" placeholder="🔍 Cari wisata..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="kategori">
                            <option value="">📋 Semua Kategori</option>
                            <option value="budaya" <?php echo $kategori_filter == 'budaya' ? 'selected' : ''; ?>>🎭 Budaya</option>
                            <option value="alam" <?php echo $kategori_filter == 'alam' ? 'selected' : ''; ?>>🌿 Alam</option>
                        </select>
                        <button type="submit">🔍 Filter</button>
                        <?php if (!empty($kategori_filter) || !empty($search)): ?>
                            <a href="userwisata.php" style="text-decoration: none;">
                                <button type="button" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">🔄 Reset</button>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <?php if (!empty($wisata_data)): ?>
                    <div class="articles-grid">
                        <?php foreach ($wisata_data as $wisata): ?>
                            <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $wisata['id']; ?>'">
                                <div class="article-image">
                                    <?php if ($wisata['photo']): ?>
                                        <img src="../../uploads/<?php echo htmlspecialchars($wisata['photo']); ?>" 
                                             alt="<?php echo htmlspecialchars($wisata['judul']); ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image">
                                            🏝️
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-category category-<?php echo $wisata['kategori']; ?>">
                                        <?php
                                        $kategori_icons = [
                                            'budaya' => '🎭 Budaya',
                                            'alam' => '🌿 Alam'
                                        ];
                                        echo $kategori_icons[$wisata['kategori']] ?? ucfirst($wisata['kategori']);
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="article-card-content">
                                    <h4 class="article-card-title"><?php echo htmlspecialchars($wisata['judul']); ?></h4>
                                    <div class="article-card-price"><?php echo formatPrice($wisata['harga']); ?></div>
                                    
                                    <div class="card-description">
                                        <?php echo truncateText(htmlspecialchars($wisata['deskripsi']), 100); ?>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <a href="?view=detail&id=<?php echo $wisata['id']; ?>" class="btn-detail">
                                            📖 Lihat Selengkapnya
                                        </a>
                                        <span class="card-date">
                                            <?php echo formatDate($wisata['created_at']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <div style="font-size: 5rem; margin-bottom: 1rem;">😔</div>
                        <h3>Tidak Ada Wisata Ditemukan</h3>
                        <p>Maaf, tidak ada wisata yang sesuai dengan pencarian Anda.</p>
                        <p>Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($view_mode === 'detail' && $wisata_detail): ?>
                <a href="?" class="back-button">
                    ⬅️ Kembali ke Daftar Wisata
                </a>
                
                <?php echo $booking_message; ?>
                
                <div class="article-detail">
                    <div class="article-header">
                        <?php if ($wisata_detail['photo']): ?>
                            <img src="../../uploads/<?php echo htmlspecialchars($wisata_detail['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($wisata_detail['judul']); ?>">
                        <?php else: ?>
                            <div class="placeholder-image" style="height: 400px;">
                                🏝️
                            </div>
                        <?php endif; ?>
                        
                        <div class="article-category category-<?php echo $wisata_detail['kategori']; ?>">
                            <?php
                            $kategori_icons = [
                                'budaya' => '🎭 Budaya',
                                'alam' => '🌿 Alam'
                            ];
                            echo $kategori_icons[$wisata_detail['kategori']] ?? ucfirst($wisata_detail['kategori']);
                            ?>
                        </div>
                    </div>
                    
                    <div class="article-content">
                        <h1 class="article-title"><?php echo htmlspecialchars($wisata_detail['judul']); ?></h1>
                        
                        <div class="article-meta">
                            <div class="article-price"><?php echo formatPrice($wisata_detail['harga']); ?></div>
                            <div class="article-date">
                                📅 <?php echo formatDate($wisata_detail['created_at']); ?>
                            </div>
                        </div>
                        
                        <div class="article-description">
                            <?php echo nl2br(htmlspecialchars($wisata_detail['deskripsi'])); ?>
                        </div>
                        
                        <div class="wisata-info-section">
                            <h3 style="margin-bottom: 25px; color: #333; font-size: 1.5rem;">ℹ️ Informasi Wisata</h3>
                            <div class="wisata-info-grid">
                                <div class="info-item">
                                    <span>📍</span>
                                    <div>
                                        <strong>Alamat</strong>
                                        <?php echo htmlspecialchars($wisata_detail['alamat']); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <span>🕒</span>
                                    <div>
                                        <strong>Jam Buka</strong>
                                        <?php echo htmlspecialchars($wisata_detail['jam_buka']); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <span>🎫</span>
                                    <div>
                                        <strong>Harga Tiket</strong>
                                        <?php echo formatPrice($wisata_detail['harga']); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <span>📂</span>
                                    <div>
                                        <strong>Kategori</strong>
                                        <?php echo ucfirst($wisata_detail['kategori']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Booking Form -->
                            <div class="booking-section">
                                <h3 style="margin-bottom: 25px; color: #333; font-size: 1.5rem;">🎫 Pesan Tiket</h3>
                                <form method="POST" class="booking-form">
                                    <input type="hidden" name="wisata_id" value="<?php echo $wisata_detail['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="jumlah_tiket">Jumlah Tiket:</label>
                                        <input type="number" name="jumlah_tiket" id="jumlah_tiket" min="1" max="10" value="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="tanggal_kunjungan">Tanggal Kunjungan:</label>
                                        <input type="date" name="tanggal_kunjungan" id="tanggal_kunjungan" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="catatan">Catatan (Opsional):</label>
                                        <textarea name="catatan" id="catatan" rows="3" 
                                                  placeholder="Tambahkan catatan khusus untuk kunjungan Anda"></textarea>
                                    </div>
                                    
                                    <div class="booking-summary">
                                        <p><strong>Nama:</strong> <?php echo htmlspecialchars($user_name); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
                                        <p><strong>Harga per Tiket:</strong> <?php echo formatPrice($wisata_detail['harga']); ?></p>
                                        <p><strong>Total Estimasi:</strong> <span id="total-price"><?php echo formatPrice($wisata_detail['harga']); ?></span></p>
                                    </div>
                                    
                                    <button type="submit" name="book_ticket" class="btn btn-primary">
                                        🎫 Pesan Tiket Sekarang
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (count($related_wisata) > 0): ?>
                <div class="articles-grid" style="margin-top: 50px;">
                    <div style="grid-column: 1 / -1; text-align: center; margin-bottom: 20px;">
                        <h3 style="color: white; font-size: 2rem;">🌟 Wisata Terkait</h3>
                        <p style="color: rgba(255,255,255,0.9); margin-top: 10px;">Jelajahi wisata lainnya dengan kategori yang sama</p>
                    </div>
                    <?php foreach ($related_wisata as $related): ?>
                        <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $related['id']; ?>'">
                            <div class="article-image">
                                <?php if ($related['photo']): ?>
                                    <img src="../../uploads/<?php echo htmlspecialchars($related['photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['judul']); ?>">
                                <?php else: ?>
                                    <div class="placeholder-image">
                                        🏝️
                                    </div>
                                <?php endif; ?>
                                <div class="card-category category-<?php echo $related['kategori']; ?>">
                                    <?php
                                    $kategori_icons = [
                                        'budaya' => '🎭 Budaya',
                                        'alam' => '🌿 Alam'
                                    ];
                                    echo $kategori_icons[$related['kategori']] ?? ucfirst($related['kategori']);
                                    ?>
                                </div>
                            </div>
                            
                            <div class="article-card-content">
                                <h4 class="article-card-title"><?php echo htmlspecialchars($related['judul']); ?></h4>
                                <div class="article-card-price"><?php echo formatPrice($related['harga']); ?></div>
                                <div class="card-description">
                                    <?php echo truncateText(htmlspecialchars($related['deskripsi']), 80); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Calculate total price based on ticket quantity
        document.getElementById('jumlah_tiket')?.addEventListener('input', function() {
            const quantity = parseInt(this.value) || 1;
            const pricePerTicket = <?php echo $wisata_detail['harga'] ?? 0; ?>;
            const total = quantity * pricePerTicket;
            document.getElementById('total-price').textContent = formatPrice(total);
        });
        
        function formatPrice(price) {
            return 'Rp ' + price.toLocaleString('id-ID');
        }
        
        // Smooth scroll animation for back button
        document.querySelector('.back-button')?.addEventListener('click', function(e) {
            e.preventDefault();
            window.history.back();
        });
        
        // Add loading animation for cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.article-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>

