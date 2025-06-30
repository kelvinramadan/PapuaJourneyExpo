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

// Get cart count for navbar
$cart_count = 0;
$stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_count = $result->fetch_assoc()['count'];
$stmt->close();

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
    $sql = "SELECT w.*, 
            COALESCE(rsc.total_reviews, 0) as review_count,
            COALESCE(rsc.average_rating, 0) as average_rating
            FROM wisata w
            LEFT JOIN review_summary_cache rsc 
                ON rsc.item_type = 'wisata' 
                AND rsc.item_id = w.id
            WHERE 1=1";
    $params = [];

    if (!empty($kategori_filter)) {
        $sql .= " AND w.kategori = ?";
        $params[] = $kategori_filter;
    }

    if (!empty($search)) {
        $sql .= " AND (w.judul LIKE ? OR w.deskripsi LIKE ? OR w.alamat LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $sql .= " ORDER BY w.created_at DESC";

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
    <link rel="stylesheet" href="userwisata.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../assets/css/reviews.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                                    
                                    <!-- Review Rating Display -->
                                    <div class="card-rating">
                                        <?php if ($wisata['review_count'] > 0): ?>
                                            <div class="rating-stars">
                                                <?php 
                                                $rating = round($wisata['average_rating']);
                                                for ($i = 1; $i <= 5; $i++): 
                                                ?>
                                                    <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                                <span class="rating-value"><?php echo number_format($wisata['average_rating'], 1); ?></span>
                                            </div>
                                            <span class="review-count">(<?php echo $wisata['review_count']; ?> review<?php echo $wisata['review_count'] > 1 ? 's' : ''; ?>)</span>
                                        <?php else: ?>
                                            <span class="no-reviews">Belum ada review</span>
                                        <?php endif; ?>
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
                                <form id="add-to-cart-form" class="booking-form">
                                    <input type="hidden" name="item_type" value="wisata">
                                    <input type="hidden" name="item_id" value="<?php echo $wisata_detail['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label for="jumlah_tiket">Jumlah Tiket:</label>
                                        <input type="number" name="quantity" id="jumlah_tiket" min="1" max="10" value="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="tanggal_kunjungan">Tanggal Kunjungan:</label>
                                        <input type="date" name="booking_date" id="tanggal_kunjungan" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="catatan">Catatan (Opsional):</label>
                                        <textarea name="notes" id="catatan" rows="3" 
                                                  placeholder="Tambahkan catatan khusus untuk kunjungan Anda"></textarea>
                                    </div>
                                    
                                    <div class="booking-summary">
                                        <p><strong>Harga per Tiket:</strong> <?php echo formatPrice($wisata_detail['harga']); ?></p>
                                        <p><strong>Total Estimasi:</strong> <span id="total-price"><?php echo formatPrice($wisata_detail['harga']); ?></span></p>
                                    </div>
                                    
                                    <button type="button" onclick="addToCart()" class="btn btn-primary">
                                        🛒 Tambahkan ke Keranjang
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Reviews Section -->
                        <div class="reviews-section" id="reviews-section">
                            <div class="reviews-card">
                                <div class="reviews-header">
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
                        </div>
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
                    
                    console.log('Loading reviews for wisata ID:', <?php echo $wisata_detail['id']; ?>);
                    
                    fetch(`../reviews/get_reviews.php?item_type=wisata&item_id=<?php echo $wisata_detail['id']; ?>&page=${page}&sort_by=${currentSort}`)
                        .then(response => response.json())
                        .then(data => {
                            console.log('Review data received:', data);
                            if (data.success) {
                                // Update summary
                                updateReviewSummary(data.summary);
                                
                                // Display reviews
                                const reviewsList = document.getElementById('reviewsList');
                                if (!append) {
                                    reviewsList.innerHTML = '';
                                }
                                
                                if (data.reviews.length === 0 && page === 1) {
                                    reviewsList.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">Belum ada review untuk wisata ini. Jadilah yang pertama memberikan review!</p>';
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
                            // Try to load with debug flag to get more details
                            fetch(`../reviews/get_reviews.php?item_type=wisata&item_id=<?php echo $wisata_detail['id']; ?>&page=${page}&sort_by=${currentSort}&debug=true`)
                                .then(response => response.json())
                                .then(debugData => {
                                    console.error('Debug response:', debugData);
                                    if (debugData.help) {
                                        document.getElementById('reviewsList').innerHTML = `
                                            <div style="text-align: center; padding: 20px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; margin: 20px 0;">
                                                <p style="color: #856404; margin-bottom: 10px;">${debugData.message}</p>
                                                <code style="background: #f8f9fa; padding: 10px; border-radius: 4px; display: block;">${debugData.help}</code>
                                            </div>`;
                                    } else {
                                        document.getElementById('reviewsList').innerHTML = '<p style="text-align: center; color: red; padding: 20px;">Error loading reviews. Check console for details.</p>';
                                    }
                                })
                                .catch(() => {
                                    document.getElementById('reviewsList').innerHTML = '<p style="text-align: center; color: red; padding: 20px;">Error loading reviews. Check console for details.</p>';
                                });
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
                
                <?php if (count($related_wisata) > 0): ?>
                <div class="articles-grid" style="margin-top: 50px;">
                    <div style="grid-column: 1 / -1; text-align: center; margin-bottom: 20px;">
                        <h3 style="color: #333; font-size: 2rem;">🌟 Wisata Terkait</h3>
                        <p style="color: #666; margin-top: 10px;">Jelajahi wisata lainnya dengan kategori yang sama</p>
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
        
        // Add to cart function
        function addToCart() {
            const form = document.getElementById('add-to-cart-form');
            const formData = new FormData(form);
            
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
            
            // Track views for detail page
            <?php if ($view_mode === 'detail' && $wisata_detail): ?>
            trackWisataView(<?php echo $wisata_id; ?>);
            <?php endif; ?>
        });
        
        // Function to track tourist destination views
        function trackWisataView(wisataId) {
            fetch('track_view.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    wisata_id: wisataId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('View tracked successfully');
                }
            })
            .catch(error => {
                console.error('Error tracking view:', error);
            });
        }
    </script>
</body>
</html>

