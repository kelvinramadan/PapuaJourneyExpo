<?php
// users/userpenginapan.php
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get session data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Handle ticket booking
$booking_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_ticket'])) {
    $penginapan_id = (int)$_POST['penginapan_id'];
    $jumlah_kamar = (int)$_POST['jumlah_kamar'];
    $tanggal_checkin = $_POST['tanggal_checkin'];
    $tanggal_checkout = $_POST['tanggal_checkout'];
    $catatan = $_POST['catatan'];
    
    // Get penginapan details
    $stmt = $db->prepare("SELECT judul, harga FROM penginapan WHERE id = ?");
    $stmt->bind_param("i", $penginapan_id);
    $stmt->execute();
    $penginapan_result = $stmt->get_result();
    $penginapan_data = $penginapan_result->fetch_assoc();
    $stmt->close();
    
    if ($penginapan_data && $jumlah_kamar > 0) {
        // Calculate number of nights
        $checkin_date = new DateTime($tanggal_checkin);
        $checkout_date = new DateTime($tanggal_checkout);
        $interval = $checkin_date->diff($checkout_date);
        $jumlah_malam = $interval->days;
        
        if ($jumlah_malam <= 0) {
            $booking_message = '<div class="alert alert-error">Tanggal checkout harus setelah tanggal checkin!</div>';
        } else {
            $harga_per_malam = $penginapan_data['harga'];
            $total_harga = $harga_per_malam * $jumlah_kamar * $jumlah_malam;
            $penginapan_judul = $penginapan_data['judul'];
            
            // Insert booking
            $stmt = $db->prepare("INSERT INTO pesanpenginapan (user_id, user_name, user_email, penginapan_id, penginapan_judul, jumlah_kamar, jumlah_malam, harga_per_malam, total_harga, tanggal_checkin, tanggal_checkout, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issisiididss", $user_id, $user_name, $user_email, $penginapan_id, $penginapan_judul, $jumlah_kamar, $jumlah_malam, $harga_per_malam, $total_harga, $tanggal_checkin, $tanggal_checkout, $catatan);
            
            if ($stmt->execute()) {
                $booking_message = '<div class="alert alert-success">Pemesanan kamar berhasil! Total: ' . formatPrice($total_harga) . ' untuk ' . $jumlah_malam . ' malam</div>';
            } else {
                $booking_message = '<div class="alert alert-error">Gagal melakukan pemesanan. Silakan coba lagi.</div>';
            }
            $stmt->close();
        }
    }
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$tipe_filter = isset($_GET['tipe']) ? $_GET['tipe'] : '';
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'list';
$detail_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle detail view
$penginapan_detail = null;
if ($view_mode === 'detail' && $detail_id > 0) {
    $query = "SELECT * FROM penginapan WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $detail_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $penginapan_detail = $result->fetch_assoc();
    }
    $stmt->close();
}

// Fetch filtered penginapan data
$penginapan_data = [];
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(judul LIKE ? OR lokasi LIKE ? OR deskripsi LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($tipe_filter)) {
    $where_conditions[] = "tipe = ?";
    $params[] = $tipe_filter;
    $types .= 's';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$query = "SELECT * FROM penginapan {$where_clause} ORDER BY created_at DESC";
$stmt = $db->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $penginapan_data[] = $row;
}
$stmt->close();

// Get related penginapan (same type, excluding current)
$related_penginapan = [];
if ($penginapan_detail) {
    $query = "SELECT * FROM penginapan WHERE tipe = ? AND id != ? ORDER BY created_at DESC LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $penginapan_detail['tipe'], $penginapan_detail['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $related_penginapan[] = $row;
    }
    $stmt->close();
}

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDate($date) {
    return date('d F Y', strtotime($date));
}

function truncateText($text, $limit) {
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . '...';
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penginapan Papua - Wisata Indonesia</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="userpenginapan.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>
    <!-- Page Header -->
    <div class="page-header">
        <h1>🏨 Penginapan Papua</h1>
        <p>Temukan penginapan terbaik untuk petualangan Anda di tanah surga Indonesia</p>
    </div>

    <div class="container">
        <div class="content-wrapper">
            <?php if ($view_mode === 'list'): ?>
                <!-- Filter Section -->
                <div class="filters">
                    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                        <input type="text" name="search" placeholder="🔍 Cari penginapan..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="tipe">
                            <option value="">🏠 Semua Tipe</option>
                            <option value="hotel" <?php echo $tipe_filter == 'hotel' ? 'selected' : ''; ?>>🏨 Hotel</option>
                            <option value="villa" <?php echo $tipe_filter == 'villa' ? 'selected' : ''; ?>>🏖️ Villa</option>
                            <option value="resort" <?php echo $tipe_filter == 'resort' ? 'selected' : ''; ?>>🌴 Resort</option>
                        </select>
                        <button type="submit">🔍 Filter</button>
                        <?php if (!empty($tipe_filter) || !empty($search)): ?>
                            <a href="userpenginapan.php" style="text-decoration: none;">
                                <button type="button" class="reset-btn">🔄 Reset</button>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Results Section -->
                <?php if (!empty($penginapan_data)): ?>
                    <div class="articles-grid">
                        <?php foreach ($penginapan_data as $penginapan): ?>
                            <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $penginapan['id']; ?>'">
                                <div class="article-image">
                                    <?php if ($penginapan['photo'] && file_exists('../../uploads/' . $penginapan['photo'])): ?>
                                        <img src="../../uploads/<?php echo htmlspecialchars($penginapan['photo']); ?>" 
                                             alt="<?php echo htmlspecialchars($penginapan['judul']); ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image">
                                            🏨
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-category category-<?php echo $penginapan['tipe']; ?>">
                                        <?php
                                        $tipe_icons = [
                                            'hotel' => '🏨 Hotel',
                                            'villa' => '🏖️ Villa',
                                            'resort' => '🌴 Resort'
                                        ];
                                        echo $tipe_icons[$penginapan['tipe']] ?? ucfirst($penginapan['tipe']);
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="article-card-content">
                                    <h4 class="article-card-title"><?php echo htmlspecialchars($penginapan['judul']); ?></h4>
                                    <div class="article-card-price"><?php echo formatPrice($penginapan['harga']); ?>/malam</div>
                                    
                                    <div class="card-description">
                                        <?php echo truncateText(htmlspecialchars($penginapan['deskripsi']), 100); ?>
                                    </div>

                                    <?php if ($penginapan['fasilitas']): ?>
                                    <div class="facilities-preview">
                                        <strong style="font-size: 0.9rem; color: #666;">Fasilitas:</strong><br>
                                        <?php 
                                        $fasilitas = array_map('trim', explode(',', $penginapan['fasilitas']));
                                        foreach (array_slice($fasilitas, 0, 3) as $fasilitas_item): 
                                        ?>
                                            <span class="facility-tag"><?php echo htmlspecialchars($fasilitas_item); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($fasilitas) > 3): ?>
                                            <small style="color: #667eea; font-weight: bold;">+<?php echo count($fasilitas) - 3; ?> lainnya</small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-actions">
                                        <a href="?view=detail&id=<?php echo $penginapan['id']; ?>" class="btn-detail">
                                            📖 Lihat Selengkapnya
                                        </a>
                                        <span class="card-date">
                                            <?php echo formatDate($penginapan['created_at']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <div style="font-size: 5rem; margin-bottom: 1rem;">😔</div>
                        <h3>Tidak Ada Penginapan Ditemukan</h3>
                        <p>Maaf, tidak ada penginapan yang sesuai dengan pencarian Anda.</p>
                        <p>Coba ubah kata kunci pencarian atau pilih tipe lain.</p>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($view_mode === 'detail' && $penginapan_detail): ?>
                <!-- Detail View -->
                <a href="?" class="back-button">
                    ⬅️ Kembali ke Daftar Penginapan
                </a>
                
                <?php echo $booking_message; ?>
                
                <div class="article-detail">
                    <div class="article-header">
                        <?php if ($penginapan_detail['photo'] && file_exists('../../uploads/' . $penginapan_detail['photo'])): ?>
                            <img src="../../uploads/<?php echo htmlspecialchars($penginapan_detail['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($penginapan_detail['judul']); ?>">
                        <?php else: ?>
                            <div class="placeholder-image" style="height: 400px;">
                                🏨
                            </div>
                        <?php endif; ?>
                        
                        <div class="article-category category-<?php echo $penginapan_detail['tipe']; ?>">
                            <?php
                            $tipe_icons = [
                                'hotel' => '🏨 Hotel',
                                'villa' => '🏖️ Villa',
                                'resort' => '🌴 Resort'
                            ];
                            echo $tipe_icons[$penginapan_detail['tipe']] ?? ucfirst($penginapan_detail['tipe']);
                            ?>
                        </div>
                    </div>
                    
                    <div class="article-content">
                        <h1 class="article-title"><?php echo htmlspecialchars($penginapan_detail['judul']); ?></h1>
                        
                        <div class="article-meta">
                            <div class="article-price"><?php echo formatPrice($penginapan_detail['harga']); ?>/malam</div>
                            <div class="article-date">
                                📅 <?php echo formatDate($penginapan_detail['created_at']); ?>
                            </div>
                        </div>
                        
                        <div class="article-description">
                            <?php echo nl2br(htmlspecialchars($penginapan_detail['deskripsi'])); ?>
                        </div>
                        
                        <div class="penginapan-info-section">
                            <h3 style="margin-bottom: 25px; color: #333; font-size: 1.5rem;">ℹ️ Informasi Penginapan</h3>
                            <div class="penginapan-info-grid">
                                <div class="info-item">
                                    <span>📍</span>
                                    <div>
                                        <strong>Lokasi</strong>
                                        <?php echo htmlspecialchars($penginapan_detail['lokasi']); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <span>🏠</span>
                                    <div>
                                        <strong>Tipe Penginapan</strong>
                                        <?php echo ucfirst($penginapan_detail['tipe']); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <span>💰</span>
                                    <div>
                                        <strong>Harga per Malam</strong>
                                        <?php echo formatPrice($penginapan_detail['harga']); ?>
                                    </div>
                                </div>
                                
                                <?php if ($penginapan_detail['fasilitas']): ?>
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span>🛎️</span>
                                    <div>
                                        <strong>Fasilitas</strong>
                                        <div style="margin-top: 10px;">
                                            <?php 
                                            $fasilitas = array_map('trim', explode(',', $penginapan_detail['fasilitas']));
                                            foreach ($fasilitas as $fasilitas_item): 
                                            ?>
                                                <span class="facility-tag"><?php echo htmlspecialchars($fasilitas_item); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Booking Form -->
                            <div class="booking-section">
                                <h3 style="margin-bottom: 25px; color: #333; font-size: 1.5rem;">🏨 Pesan Kamar</h3>
                                <form method="POST" class="booking-form">
                                    <input type="hidden" name="penginapan_id" value="<?php echo $penginapan_detail['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="jumlah_kamar">Jumlah Kamar:</label>
                                        <input type="number" name="jumlah_kamar" id="jumlah_kamar" min="1" max="10" value="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="tanggal_checkin">Tanggal Check-in:</label>
                                        <input type="date" name="tanggal_checkin" id="tanggal_checkin" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="tanggal_checkout">Tanggal Check-out:</label>
                                        <input type="date" name="tanggal_checkout" id="tanggal_checkout" 
                                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="catatan">Catatan (Opsional):</label>
                                        <textarea name="catatan" id="catatan" rows="3" 
                                                  placeholder="Tambahkan catatan khusus untuk reservasi Anda"></textarea>
                                    </div>
                                    
                                    <div class="booking-summary">
                                        <p><strong>Nama:</strong> <?php echo htmlspecialchars($user_name); ?></p>
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></p>
                                        <p><strong>Harga per Malam:</strong> <?php echo formatPrice($penginapan_detail['harga']); ?></p>
                                        <p><strong>Jumlah Malam:</strong> <span id="jumlah-malam">1</span> malam</p>
                                        <p><strong>Total Estimasi:</strong> <span id="total-price"><?php echo formatPrice($penginapan_detail['harga']); ?></span></p>
                                    </div>
                                    <button type="submit" name="book_ticket" class="btn btn-primary">
                                        🏨 Pesan Kamar Sekarang
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Related Penginapan -->
                <?php if (count($related_penginapan) > 0): ?>
                <div class="related-section">
                    <div class="articles-grid">
                        <div class="related-header">
                            <h3>🌟 Penginapan Terkait</h3>
                            <p>Jelajahi penginapan lainnya dengan tipe yang sama</p>
                        </div>
                        <?php foreach ($related_penginapan as $related): ?>
                            <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $related['id']; ?>'">
                                <div class="article-image">
                                    <?php if ($related['photo'] && file_exists('../../uploads/' . $related['photo'])): ?>
                                        <img src="../../uploads/<?php echo htmlspecialchars($related['photo']); ?>" 
                                             alt="<?php echo htmlspecialchars($related['judul']); ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image">
                                            🏨
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-category category-<?php echo $related['tipe']; ?>">
                                        <?php
                                        $tipe_icons = [
                                            'hotel' => '🏨 Hotel',
                                            'villa' => '🏖️ Villa',
                                            'resort' => '🌴 Resort'
                                        ];
                                        echo $tipe_icons[$related['tipe']] ?? ucfirst($related['tipe']);
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="article-card-content">
                                    <h4 class="article-card-title"><?php echo htmlspecialchars($related['judul']); ?></h4>
                                    <div class="article-card-price"><?php echo formatPrice($related['harga']); ?>/malam</div>
                                    
                                    <div class="card-description">
                                        <?php echo truncateText(htmlspecialchars($related['deskripsi']), 80); ?>
                                    </div>

                                    <?php if ($related['fasilitas']): ?>
                                    <div class="facilities-preview">
                                        <?php 
                                        $fasilitas = array_map('trim', explode(',', $related['fasilitas']));
                                        foreach (array_slice($fasilitas, 0, 2) as $fasilitas_item): 
                                        ?>
                                            <span class="facility-tag"><?php echo htmlspecialchars($fasilitas_item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-actions">
                                        <a href="?view=detail&id=<?php echo $related['id']; ?>" class="btn-detail">
                                            📖 Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- 404 Not Found -->
                <div class="no-results">
                    <div style="font-size: 5rem; margin-bottom: 1rem;">🏨</div>
                    <h3>Penginapan Tidak Ditemukan</h3>
                    <p>Maaf, penginapan yang Anda cari tidak dapat ditemukan.</p>
                    <a href="?" class="btn-detail" style="display: inline-block; margin-top: 20px;">
                        🏠 Kembali ke Beranda
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript untuk Funcionalitas Tambahan -->
    <script>
        // Calculate total price based on room quantity and dates
        function calculateTotal() {
            const jumlahKamar = parseInt(document.getElementById('jumlah_kamar').value) || 1;
            const checkinDate = document.getElementById('tanggal_checkin').value;
            const checkoutDate = document.getElementById('tanggal_checkout').value;
            const pricePerNight = <?php echo $penginapan_detail['harga'] ?? 0; ?>;
            
            if (checkinDate && checkoutDate) {
                const checkin = new Date(checkinDate);
                const checkout = new Date(checkoutDate);
                const timeDiff = checkout.getTime() - checkin.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
                
                if (daysDiff > 0) {
                    const jumlahMalam = daysDiff;
                    const total = jumlahKamar * pricePerNight * jumlahMalam;
                    
                    document.getElementById('jumlah-malam').textContent = jumlahMalam;
                    document.getElementById('total-price').textContent = formatPrice(total);
                } else {
                    document.getElementById('jumlah-malam').textContent = '0';
                    document.getElementById('total-price').textContent = formatPrice(0);
                }
            }
        }
        
        // Add event listeners
        document.getElementById('jumlah_kamar')?.addEventListener('input', calculateTotal);
        document.getElementById('tanggal_checkin')?.addEventListener('change', function() {
            const checkinDate = this.value;
            const checkoutInput = document.getElementById('tanggal_checkout');
            
            // Set minimum checkout date to next day after checkin
            if (checkinDate) {
                const nextDay = new Date(checkinDate);
                nextDay.setDate(nextDay.getDate() + 1);
                checkoutInput.min = nextDay.toISOString().split('T')[0];
                
                // If current checkout is before new minimum, reset it
                if (checkoutInput.value && checkoutInput.value <= checkinDate) {
                    checkoutInput.value = nextDay.toISOString().split('T')[0];
                }
            }
            calculateTotal();
        });
        document.getElementById('tanggal_checkout')?.addEventListener('change', calculateTotal);
        
        function formatPrice(price) {
            return 'Rp ' + price.toLocaleString('id-ID');
        }
        
        // Fungsi untuk share page
        function sharePage() {
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    text: 'Lihat penginapan ini di Papua!',
                    url: window.location.href
                }).then(() => {
                    console.log('Berhasil dibagikan');
                }).catch((error) => {
                    console.log('Error sharing:', error);
                    fallbackShare();
                });
            } else {
                fallbackShare();
            }
        }

        // Fallback share function
        function fallbackShare() {
            const url = window.location.href;
            const title = document.title;
            
            // Copy to clipboard
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link berhasil disalin ke clipboard!');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    alert('Link berhasil disalin ke clipboard!');
                } catch (err) {
                    console.error('Failed to copy: ', err);
                    alert('Gagal menyalin link. Silakan salin manual: ' + url);
                }
                document.body.removeChild(textArea);
            }
        }

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

        // Smooth scroll untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Loading animation untuk card clicks
        document.querySelectorAll('.article-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.opacity = '0.7';
                this.style.transform = 'scale(0.98)';
            });
        });

        // Search form enhancement
        const searchForm = document.querySelector('.filters form');
        const searchInput = document.querySelector('input[name="search"]');
        
        if (searchForm && searchInput) {
            // Auto-submit on Enter
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchForm.submit();
                }
            });

            // Clear search button
            if (searchInput.value.length > 0) {
                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.className = 'clear-search-btn';
                clearBtn.innerHTML = '✕';
                clearBtn.style.cssText = `
                    position: absolute;
                    right: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: none;
                    border: none;
                    font-size: 1.2rem;
                    color: #666;
                    cursor: pointer;
                    padding: 5px;
                `;
                
                searchInput.parentNode.style.position = 'relative';
                searchInput.parentNode.appendChild(clearBtn);
                
                clearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    searchForm.submit();
                });
            }
        }
    </script>

    <!-- Additional CSS untuk perbaikan responsif -->
    <style>
        /* Perbaikan untuk mobile */
        @media (max-width: 576px) {
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .page-header p {
                font-size: 1rem;
            }
            
            .container {
                padding: 20px 10px;
            }
            
            .filters {
                padding: 20px;
            }
            
            .article-card-content {
                padding: 15px;
            }
            
            .article-content {
                padding: 15px;
            }
            
            .penginapan-info-section {
                padding: 20px;
            }
        }

        /* Loading state */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Hover effects untuk mobile */
        @media (hover: none) {
            .article-card:hover {
                transform: none;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            }
            
            .btn-detail:hover,
            .btn:hover {
                transform: none;
            }
        }

        /* Print styles */
        @media print {
            .filters,
            .back-button,
            .contact-actions,
            .related-section {
                display: none !important;
            }
            
            body {
                background: white !important;
            }
            
            .article-detail,
            .card {
                background: white !important;
                box-shadow: none !important;
            }
        }
    </style>
</body>
</html>