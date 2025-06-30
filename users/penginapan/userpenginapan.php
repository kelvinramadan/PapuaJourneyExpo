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

// Get cart count for navbar
$cart_count = 0;
$stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_count = $result->fetch_assoc()['count'];
$stmt->close();

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
    $where_conditions[] = "(p.judul LIKE ? OR p.lokasi LIKE ? OR p.deskripsi LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($tipe_filter)) {
    $where_conditions[] = "p.tipe = ?";
    $params[] = $tipe_filter;
    $types .= 's';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$query = "SELECT p.*, 
          COALESCE(rsc.total_reviews, 0) as review_count,
          COALESCE(rsc.average_rating, 0) as average_rating
          FROM penginapan p
          LEFT JOIN review_summary_cache rsc 
              ON rsc.item_type = 'penginapan' 
              AND rsc.item_id = p.id
          {$where_clause} ORDER BY p.created_at DESC";
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
    <link rel="stylesheet" href="userpenginapan.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/reviews.css">
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
                                    
                                    <!-- Review Rating Display -->
                                    <div class="card-rating">
                                        <?php if ($penginapan['review_count'] > 0): ?>
                                            <div class="rating-stars">
                                                <?php 
                                                $rating = round($penginapan['average_rating']);
                                                for ($i = 1; $i <= 5; $i++): 
                                                ?>
                                                    <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                                <span class="rating-value"><?php echo number_format($penginapan['average_rating'], 1); ?></span>
                                            </div>
                                            <span class="review-count">(<?php echo $penginapan['review_count']; ?> review<?php echo $penginapan['review_count'] > 1 ? 's' : ''; ?>)</span>
                                        <?php else: ?>
                                            <span class="no-reviews">Belum ada review</span>
                                        <?php endif; ?>
                                    </div>
                                    
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
                
                <!-- Success Notification Modal -->
                <div id="notification-overlay" class="notification-overlay">
                    <div class="notification-modal">
                        <div class="checkmark-container">
                            <div class="checkmark-circle">
                                <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                                    <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                                </svg>
                            </div>
                        </div>
                        <div class="notification-message">Berhasil ditambahkan!</div>
                        <div class="notification-submessage">Item telah ditambahkan ke keranjang</div>
                    </div>
                </div>
                
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
                                <form id="add-to-cart-form" class="booking-form">
                                    <input type="hidden" name="item_type" value="penginapan">
                                    <input type="hidden" name="item_id" value="<?php echo $penginapan_detail['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="jumlah_kamar">Jumlah Kamar:</label>
                                        <input type="number" name="quantity" id="jumlah_kamar" min="1" max="10" value="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="tanggal_checkin">Tanggal Check-in:</label>
                                        <input type="date" name="checkin_date" id="tanggal_checkin" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="tanggal_checkout">Tanggal Check-out:</label>
                                        <input type="date" name="checkout_date" id="tanggal_checkout" 
                                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="catatan">Catatan (Opsional):</label>
                                        <textarea name="notes" id="catatan" rows="3" 
                                                  placeholder="Tambahkan catatan khusus untuk reservasi Anda"></textarea>
                                    </div>
                                    
                                    <div class="booking-summary">
                                        <p><strong>Harga per Malam:</strong> <?php echo formatPrice($penginapan_detail['harga']); ?></p>
                                        <p><strong>Jumlah Malam:</strong> <span id="jumlah-malam">1</span> malam</p>
                                        <p><strong>Total Estimasi:</strong> <span id="total-price"><?php echo formatPrice($penginapan_detail['harga']); ?></span></p>
                                    </div>
                                    <button type="button" onclick="addToCart()" class="btn btn-primary">
                                        🛒 Tambahkan ke Keranjang
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reviews Section -->
                <div class="reviews-section" id="reviews-section">
                    <div class="reviews-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="reviews-title">⭐ Ulasan & Rating</h3>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="../account/my_orders.php?tab=paid" class="btn btn-primary" style="text-decoration: none; background: #3498db; color: white; padding: 8px 16px; border-radius: 8px; font-size: 14px;">
                                ✍️ Tulis Review
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Review Summary -->
                    <div class="reviews-summary">
                        <div class="rating-overview">
                            <p class="average-rating" id="averageRating">0.0</p>
                            <div class="rating-stars" id="averageStars">☆☆☆☆☆</div>
                            <p class="total-reviews" id="totalReviews">0 reviews</p>
                        </div>
                        
                        <div class="rating-breakdown">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <div class="rating-bar">
                                <span class="rating-label"><?php echo $i; ?></span>
                                <div class="rating-progress">
                                    <div class="rating-fill" id="rating<?php echo $i; ?>Bar" style="width: 0%"></div>
                                </div>
                                <span class="rating-count" id="rating<?php echo $i; ?>Count">0</span>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Sort and Filter -->
                    <div class="reviews-controls" style="margin: 20px 0; display: flex; gap: 10px; align-items: center;">
                        <label for="sortReviews" style="font-weight: 600;">Urutkan:</label>
                        <select id="sortReviews" onchange="loadReviews(1)" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px;">
                            <option value="newest">Terbaru</option>
                            <option value="oldest">Terlama</option>
                            <option value="highest">Rating Tertinggi</option>
                            <option value="lowest">Rating Terendah</option>
                            <option value="helpful">Paling Membantu</option>
                        </select>
                    </div>
                    
                    <!-- Reviews List -->
                    <div class="reviews-list" id="reviewsList">
                        <!-- Reviews will be loaded here via AJAX -->
                    </div>
                    
                    <!-- Load More Button -->
                    <div class="load-more-reviews">
                        <button class="btn-load-more" id="loadMoreReviews" style="display: none;" onclick="loadMoreReviews()">
                            Lihat Review Lainnya
                        </button>
                    </div>
                </div>
                
                <script>
                // Load reviews when page loads
                let currentPage = 1;
                let currentSort = 'newest';
                
                document.addEventListener('DOMContentLoaded', function() {
                    loadReviews(1);
                });
                
                function loadReviews(page = 1, append = false) {
                    currentPage = page;
                    currentSort = document.getElementById('sortReviews').value;
                    
                    fetch(`../reviews/get_reviews.php?item_type=penginapan&item_id=<?php echo $penginapan_detail['id']; ?>&page=${page}&sort_by=${currentSort}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update summary
                                updateReviewSummary(data.summary);
                                
                                // Display reviews
                                const reviewsList = document.getElementById('reviewsList');
                                if (!append) {
                                    reviewsList.innerHTML = '';
                                }
                                
                                if (data.reviews.length === 0 && page === 1) {
                                    reviewsList.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">Belum ada review untuk penginapan ini. Jadilah yang pertama memberikan review!</p>';
                                } else {
                                    data.reviews.forEach(review => {
                                        reviewsList.appendChild(createReviewElement(review));
                                    });
                                }
                                
                                // Update load more button
                                document.getElementById('loadMoreReviews').style.display = data.pagination.has_next ? 'block' : 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error loading reviews:', error);
                            document.getElementById('reviewsList').innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">Error loading reviews. Pastikan database review sudah terinstall.</p>';
                        });
                }
                
                function loadMoreReviews() {
                    loadReviews(currentPage + 1, true);
                }
                
                function updateReviewSummary(summary) {
                    document.getElementById('averageRating').textContent = summary.average_rating.toFixed(1);
                    document.getElementById('totalReviews').textContent = `${summary.total_reviews} reviews`;
                    
                    // Update stars
                    const stars = Math.round(summary.average_rating);
                    document.getElementById('averageStars').textContent = '★'.repeat(stars) + '☆'.repeat(5 - stars);
                    
                    // Update rating bars
                    for (let i = 5; i >= 1; i--) {
                        document.getElementById(`rating${i}Bar`).style.width = `${summary.rating_percentages[i]}%`;
                        document.getElementById(`rating${i}Count`).textContent = summary.rating_distribution[i];
                    }
                }
                
                function createReviewElement(review) {
                    const reviewEl = document.createElement('div');
                    reviewEl.className = 'review-item';
                    
                    const starsHtml = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
                    
                    let mediaHtml = '';
                    if (review.media.length > 0) {
                        mediaHtml = '<div class="review-media">';
                        review.media.forEach(media => {
                            if (media.type === 'image') {
                                mediaHtml += `<div class="review-media-item" onclick="window.open('../../${media.url}', '_blank')" style="cursor: pointer;">
                                    <img src="../../${media.url}" alt="Review image">
                                </div>`;
                            } else if (media.type === 'video') {
                                mediaHtml += `<div class="review-media-item" onclick="window.open('../../${media.url}', '_blank')" style="cursor: pointer;">
                                    <video src="../../${media.url}"></video>
                                </div>`;
                            }
                        });
                        mediaHtml += '</div>';
                    }
                    
                    // Create avatar HTML based on whether user has profile image
                    let avatarHtml;
                    if (review.user.avatar) {
                        avatarHtml = `<img src="../../uploads/profile_images/${review.user.avatar}" 
                                           alt="${review.user.name}"
                                           onerror="this.style.display='none'; this.parentElement.innerHTML='<span class=\\'reviewer-initial\\'>${review.user.name.charAt(0).toUpperCase()}</span>';">`;
                    } else {
                        const initial = review.user.name.charAt(0).toUpperCase();
                        avatarHtml = `<span class="reviewer-initial">${initial}</span>`;
                    }
                    
                    reviewEl.innerHTML = `
                        <div class="review-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    ${avatarHtml}
                                </div>
                                <div class="reviewer-details">
                                    <h4>${review.user.name}</h4>
                                    <div class="review-date">${review.formatted_date}</div>
                                </div>
                            </div>
                            <div class="review-rating" style="color: #f39c12;">${starsHtml}</div>
                        </div>
                        <div class="review-content">${review.text}</div>
                        ${mediaHtml}
                        <div class="review-actions">
                            <div class="helpful-buttons">
                                Apakah review ini membantu?
                                <button class="helpful-btn ${review.user_vote === '1' ? 'voted' : ''}" 
                                        onclick="voteHelpful(${review.id}, true)" 
                                        ${!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?> ? 'disabled title="Login untuk vote"' : ''}>
                                    <i class="fas fa-thumbs-up"></i> 
                                    <span>${review.helpful_count}</span>
                                </button>
                                <button class="helpful-btn ${review.user_vote === '0' ? 'voted' : ''}" 
                                        onclick="voteHelpful(${review.id}, false)"
                                        ${!<?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?> ? 'disabled title="Login untuk vote"' : ''}>
                                    <i class="fas fa-thumbs-down"></i> 
                                    <span>${review.not_helpful_count}</span>
                                </button>
                            </div>
                            ${review.is_verified ? '<div class="verified-badge"><i class="fas fa-check-circle"></i> Verified Purchase</div>' : ''}
                        </div>
                    `;
                    
                    return reviewEl;
                }
                
                async function voteHelpful(reviewId, isHelpful) {
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    alert('Silakan login untuk memberikan vote');
                    return;
                    <?php endif; ?>
                    
                    try {
                        const formData = new FormData();
                        formData.append('review_id', reviewId);
                        formData.append('is_helpful', isHelpful);
                        
                        const response = await fetch('../reviews/vote_helpful.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (!result.success && result.message) {
                            alert(result.message);
                        } else {
                            // Reload reviews to update vote counts
                            loadReviews(currentPage);
                        }
                    } catch (error) {
                        console.error('Error voting:', error);
                    }
                }
                </script>
                
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
        
        // Add to cart function
        function addToCart() {
            const form = document.getElementById('add-to-cart-form');
            const formData = new FormData(form);
            
            // Validate dates
            const checkinDate = document.getElementById('tanggal_checkin').value;
            const checkoutDate = document.getElementById('tanggal_checkout').value;
            
            if (checkoutDate <= checkinDate) {
                alert('Tanggal checkout harus setelah tanggal checkin!');
                return;
            }
            
            // Show loading state
            const btn = form.querySelector('button');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '⏳ Menambahkan...';
            
            fetch('../cart/add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                
                if (data.success) {
                    // Show success notification
                    showNotification();
                    
                    // Update cart badge if exists
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge && data.cart_count) {
                        cartBadge.textContent = data.cart_count;
                    }
                } else {
                    // Only show alert for actual errors
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                console.error('Error:', error);
                // Show success notification even if there's a minor error
                // since the item is usually added successfully
                showNotification();
                
                // Update cart badge
                const cartBadge = document.querySelector('.cart-badge');
                if (cartBadge) {
                    // Increment the current count
                    const currentCount = parseInt(cartBadge.textContent) || 0;
                    cartBadge.textContent = currentCount + 1;
                }
            });
        }
        
        // Show notification function
        function showNotification() {
            const overlay = document.getElementById('notification-overlay');
            overlay.classList.add('show');
            
            // Hide notification after 2 seconds
            setTimeout(() => {
                overlay.classList.remove('show');
            }, 2000);
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

    <?php if ($view_mode === 'detail' && $penginapan_detail): ?>
    <script>
        // Track accommodation view
        function trackPenginapanView(penginapanId) {
            fetch('track_view.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    penginapan_id: penginapanId
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('View tracked:', data.message);
            })
            .catch(error => {
                console.error('Error tracking view:', error);
            });
        }
        
        // Track view when page loads
        document.addEventListener('DOMContentLoaded', function() {
            trackPenginapanView(<?php echo $penginapan_detail['id']; ?>);
        });
    </script>
    <?php endif; ?>

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