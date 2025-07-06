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

$message = '';
$error_message = '';

// Get user details from database
$db = getDbConnection();
$stmt = $db->prepare("SELECT full_name, email, phone, address, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Get cart count for navbar
$cart_count = 0;
$cart_stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$cart_count = $cart_result->fetch_assoc()['count'];
$cart_stmt->close();

// Get filters and search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 12;
$offset = ($current_page - 1) * $items_per_page;

// Check if viewing article detail
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$article = null;
$related_articles = [];

if ($view_mode === 'detail' && $article_id > 0) {
    // Get article details with UMKM information
    $query = "SELECT a.*, u.business_name, u.owner_name, u.phone, u.address, u.business_type, u.profile_image as umkm_image, u.description as umkm_description 
              FROM artikel a 
              JOIN umkm u ON a.umkm_id = u.id 
              WHERE a.id = ? AND a.status = 'active'";

    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $article = $result->fetch_assoc();
        
        // Get related articles from same category
        $related_query = "SELECT a.*, u.business_name 
                          FROM artikel a 
                          JOIN umkm u ON a.umkm_id = u.id 
                          WHERE a.kategori = ? AND a.id != ? AND a.status = 'active' 
                          ORDER BY a.created_at DESC 
                          LIMIT 4";

        $related_stmt = $db->prepare($related_query);
        $related_stmt->bind_param("si", $article['kategori'], $article_id);
        $related_stmt->execute();
        $related_articles = $related_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $related_stmt->close();
    } else {
        $view_mode = 'dashboard'; // Reset to dashboard if article not found
    }
    $stmt->close();
}

// Get articles for dashboard view with filtering
$articles = [];
$total_articles = 0;
$total_pages = 1;

if ($view_mode === 'dashboard') {
    // Build WHERE clause for filtering
    $where_conditions = ["a.status = 'active'"];
    $params = [];
    $param_types = "";

    if (!empty($search)) {
        $where_conditions[] = "(a.judul LIKE ? OR a.deskripsi LIKE ? OR u.business_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $param_types .= "sss";
    }

    if (!empty($kategori_filter)) {
        $where_conditions[] = "a.kategori = ?";
        $params[] = $kategori_filter;
        $param_types .= "s";
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Count total articles for pagination
    $count_query = "SELECT COUNT(*) as total 
                    FROM artikel a 
                    JOIN umkm u ON a.umkm_id = u.id 
                    WHERE $where_clause";

    if (!empty($params)) {
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bind_param($param_types, ...$params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_articles = $count_result->fetch_assoc()['total'];
        $count_stmt->close();
    } else {
        $count_result = $db->query($count_query);
        $total_articles = $count_result->fetch_assoc()['total'];
    }

    $total_pages = ceil($total_articles / $items_per_page);

    // Get articles with pagination
    $articles_query = "SELECT a.*, u.business_name, u.profile_image as umkm_image,
                       COALESCE(rsc.total_reviews, 0) as review_count,
                       COALESCE(rsc.average_rating, 0) as average_rating
                       FROM artikel a 
                       JOIN umkm u ON a.umkm_id = u.id 
                       LEFT JOIN review_summary_cache rsc 
                           ON rsc.item_type = 'artikel' 
                           AND rsc.item_id = a.id
                       WHERE $where_clause
                       ORDER BY a.created_at DESC 
                       LIMIT ? OFFSET ?";

    $params[] = $items_per_page;
    $params[] = $offset;
    $param_types .= "ii";

    if (!empty($params)) {
        $articles_stmt = $db->prepare($articles_query);
        $articles_stmt->bind_param($param_types, ...$params);
        $articles_stmt->execute();
        $articles_result = $articles_stmt->get_result();
        $articles = $articles_result->fetch_all(MYSQLI_ASSOC);
        $articles_stmt->close();
    }
}

$db->close();

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function truncateText($text, $length) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papua Journey - Discover Authentic Adventures in Papua</title>
    <meta name="description" content="Explore Papua's breathtaking landscapes, rich cultures, and unforgettable adventures. Connect with local businesses and plan your perfect journey.">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="../../assets/logo.png" as="image">
    <link rel="preload" href="../../assets/banner.jpg" as="image">
    
    <!-- Stylesheets -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-solid-rounded/css/uicons-solid-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    <link rel="stylesheet" href="../../assets/css/reviews.css">
    <link rel="stylesheet" href="userdashboard.css">
    
    <!-- Scripts -->
    <script src="../../script.js" defer></script>
    
</head>
<body>
    <?php include '../components/navbar.php'; ?>
    <!-- Scroll Progress Indicator -->
    <div class="scroll-progress-bar"></div>
    <?php if ($view_mode === 'detail' && $article): ?>
        <!-- Article Detail View -->
        <div style="padding-top: 100px;">
            <div style="max-width: 1200px; margin: 0 auto; padding: 0 2rem;">
                <a href="?" class="back-button">
                    ⬅️ Kembali ke Beranda
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
                        <?php if ($article['gambar']): ?>
                            <img src="../../uploads/artikel_images/<?php echo htmlspecialchars($article['gambar']); ?>" 
                                 alt="<?php echo htmlspecialchars($article['judul']); ?>">
                        <?php else: ?>
                            <div class="placeholder-image">
                                📷
                            </div>
                        <?php endif; ?>
                        
                        <div class="article-category category-<?php echo $article['kategori']; ?>">
                            <?php
                            $kategori_icons = [
                                'jasa' => '🔧 Jasa',
                                'event' => '🎉 Event',
                                'kuliner' => '🍽️ Kuliner',
                                'kerajinan' => '🎨 Kerajinan',
                                'wisata' => '🏝️ Wisata'
                            ];
                            echo $kategori_icons[$article['kategori']] ?? ucfirst($article['kategori']);
                            ?>
                        </div>
                    </div>
                    
                    <div class="article-content">
                        <h1 class="article-title"><?php echo htmlspecialchars($article['judul']); ?></h1>
                        
                        <div class="article-meta">
                            <div class="article-price"><?php echo formatPrice($article['harga']); ?> / tiket</div>
                            <div class="article-date">
                                📅 <?php echo formatDate($article['created_at']); ?>
                            </div>
                        </div>
                        
                        <div class="article-description">
                            <?php echo nl2br(htmlspecialchars($article['deskripsi'])); ?>
                        </div>
                        
                        <!-- Booking Form -->
                        <div class="booking-form">
                            <h3>🎫 Pesan Tiket</h3>
                            <div id="cart-message" style="display: none;"></div>
                            <form id="add-to-cart-form">
                                <input type="hidden" name="item_type" value="artikel">
                                <input type="hidden" name="item_id" value="<?php echo $article['id']; ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="nama_pemesan">Nama Pemesan</label>
                                        <input type="text" id="nama_pemesan" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" readonly>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email_pemesan">Email</label>
                                        <input type="email" id="email_pemesan" value="<?php echo htmlspecialchars($user_data['email']); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="jumlah_tiket">Jumlah Tiket *</label>
                                        <input type="number" name="quantity" id="jumlah_tiket" min="1" max="10" value="1" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="tanggal_kunjungan">Tanggal Kunjungan *</label>
                                        <input type="date" name="booking_date" id="tanggal_kunjungan" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="catatan">Catatan Tambahan</label>
                                    <textarea name="notes" id="catatan" rows="3" placeholder="Catatan khusus untuk pemesanan Anda..."></textarea>
                                </div>
                                
                                <div class="total-price">
                                    <h4>Total: <span id="total-amount"><?php echo formatPrice($article['harga']); ?></span></h4>
                                </div>
                                
                                <button type="button" onclick="addToCart()" class="btn-book">
                                    🛒 Tambahkan ke Keranjang
                                </button>
                            </form>
                        </div>
                        
                        <div class="umkm-section-detail">
                            <div class="umkm-header-detail">
                                <?php if ($article['umkm_image']): ?>
                                    <img src="../../uploads/profile_images/<?php echo htmlspecialchars($article['umkm_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($article['business_name']); ?>" class="umkm-avatar-detail">
                                <?php else: ?>
                                    <div class="umkm-avatar-placeholder">
                                        🏪
                                    </div>
                                <?php endif; ?>
                                
                                <div class="umkm-info">
                                    <h3><?php echo htmlspecialchars($article['business_name']); ?></h3>
                                    <p><strong>Pemilik:</strong> <?php echo htmlspecialchars($article['owner_name']); ?></p>
                                    <p><strong>Jenis Usaha:</strong> <?php echo ucfirst(htmlspecialchars($article['business_type'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="umkm-details">
                                <div class="umkm-detail-item">
                                    <span>📞</span>
                                    <div>
                                        <strong>Telepon</strong><br>
                                        <?php echo htmlspecialchars($article['phone']); ?>
                                    </div>
                                </div>
                                
                                <div class="umkm-detail-item">
                                    <span>📍</span>
                                    <div>
                                        <strong>Alamat</strong><br>
                                        <?php echo htmlspecialchars($article['address']); ?>
                                    </div>
                                </div>
                                
                                <?php if ($article['umkm_description']): ?>
                                <div class="umkm-detail-item" style="grid-column: 1 / -1;">
                                    <span>📝</span>
                                    <div>
                                        <strong>Tentang UMKM</strong><br>
                                        <?php echo nl2br(htmlspecialchars($article['umkm_description'])); ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
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
                
                <script>
                // Load reviews when page loads
                let currentPage = 1;
                let currentSort = 'newest';
                
                document.addEventListener('DOMContentLoaded', function() {
                    if (document.getElementById('reviews-section')) {
                        loadReviews(1);
                    }
                });
                
                function loadReviews(page = 1, append = false) {
                    currentPage = page;
                    currentSort = document.getElementById('sortReviews').value;
                    
                    fetch(`../reviews/get_reviews.php?item_type=artikel&item_id=<?php echo $article['id']; ?>&page=${page}&sort_by=${currentSort}`)
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
                                    reviewsList.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">Belum ada review untuk produk ini. Jadilah yang pertama memberikan review!</p>';
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
                
                <?php if (count($related_articles) > 0): ?>
                <div style="margin-top: 3rem;">
                    <h3 style="text-align: center; margin-bottom: 2rem; color: var(--text-color-secondary);">🌟 Artikel Terkait</h3>
                    <div class="articles-grid">
                        <?php foreach ($related_articles as $related): ?>
                            <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $related['id']; ?>'">
                                <div class="article-image">
                                    <?php if ($related['gambar']): ?>
                                        <img src="../../uploads/artikel_images/<?php echo htmlspecialchars($related['gambar']); ?>" 
                                             alt="<?php echo htmlspecialchars($related['judul']); ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image">
                                            📷
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="article-card-content">
                                    <h4 class="article-card-title"><?php echo htmlspecialchars($related['judul']); ?></h4>
                                    <div class="article-card-price"><?php echo formatPrice($related['harga']); ?></div>
                                    <div class="card-umkm">🏪 <?php echo htmlspecialchars($related['business_name']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    
    <?php elseif ($view_mode === 'umkm'): ?>
        <!-- UMKM Browse View -->
        <div style="padding-top: 100px;">
            <div class="umkm-section">
                <div class="umkm-container">
                    <a href="?" class="back-button">
                        ⬅️ Kembali ke Beranda
                    </a>
                    
                    <div class="umkm-header">
                        <span class="section-label">Local Business</span>
                        <h2>Jelajahi <b>UMKM Papua</b></h2>
                        <p>Temukan produk dan layanan autentik dari usaha mikro, kecil, dan menengah di Papua</p>
                    </div>
                    
                    <!-- Filters Section -->
                    <div class="filters-section">
                        <form method="GET" action="">
                            <input type="hidden" name="view" value="umkm">
                            <div class="filters-row">
                                <div class="search-box-umkm">
                                    <input type="text" name="search" placeholder="🔍 Cari artikel, produk, atau UMKM..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <button type="submit" style="display: none;"></button>
                            </div>
                            
                            <div class="category-filters">
                                <a href="?view=umkm" class="category-btn <?php echo empty($kategori_filter) ? 'active' : ''; ?>">
                                    🌟 Semua
                                </a>
                                <a href="?view=umkm&kategori=jasa<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="category-btn <?php echo $kategori_filter === 'jasa' ? 'active' : ''; ?>">
                                    🔧 Jasa
                                </a>
                                <a href="?view=umkm&kategori=event<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="category-btn <?php echo $kategori_filter === 'event' ? 'active' : ''; ?>">
                                    🎉 Event
                                </a>
                                <a href="?view=umkm&kategori=kuliner<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="category-btn <?php echo $kategori_filter === 'kuliner' ? 'active' : ''; ?>">
                                    🍽️ Kuliner
                                </a>
                                <a href="?view=umkm&kategori=kerajinan<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="category-btn <?php echo $kategori_filter === 'kerajinan' ? 'active' : ''; ?>">
                                    🎨 Kerajinan
                                </a>
                                <a href="?view=umkm&kategori=wisata<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="category-btn <?php echo $kategori_filter === 'wisata' ? 'active' : ''; ?>">
                                    🏝️ Wisata
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Results Info -->
                    <div class="results-info">
                        <p>Menampilkan <?php echo count($articles); ?> dari <?php echo $total_articles; ?> artikel
                           <?php if ($kategori_filter): ?>
                               dalam kategori <strong><?php echo ucfirst($kategori_filter); ?></strong>
                           <?php endif; ?>
                           <?php if ($search): ?>
                               untuk pencarian "<strong><?php echo htmlspecialchars($search); ?></strong>"
                           <?php endif; ?>
                        </p>
                    </div>
                    
                    <?php if (count($articles) > 0): ?>
                    <div class="articles-grid">
                        <?php foreach ($articles as $artikel): ?>
                            <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $artikel['id']; ?>'">
                                <div class="article-image">
                                    <?php if ($artikel['gambar']): ?>
                                        <img src="../../uploads/artikel_images/<?php echo htmlspecialchars($artikel['gambar']); ?>" 
                                             alt="<?php echo htmlspecialchars($artikel['judul']); ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image">
                                            📷
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-category category-<?php echo $artikel['kategori']; ?>">
                                        <?php
                                        $kategori_icons = [
                                            'jasa' => '🔧 Jasa',
                                            'event' => '🎉 Event',
                                            'kuliner' => '🍽️ Kuliner',
                                            'kerajinan' => '🎨 Kerajinan',
                                            'wisata' => '🏝️ Wisata'
                                        ];
                                        echo $kategori_icons[$artikel['kategori']] ?? ucfirst($artikel['kategori']);
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="article-card-content">
                                    <h4 class="article-card-title"><?php echo htmlspecialchars($artikel['judul']); ?></h4>
                                    <div class="article-card-price"><?php echo formatPrice($artikel['harga']); ?></div>
                                    
                                    <div class="card-description">
                                        <?php echo truncateText(htmlspecialchars($artikel['deskripsi']), 80); ?>
                                    </div>
                                    
                                    <div class="card-umkm">
                                        <?php if ($artikel['umkm_image']): ?>
                                            <img src="../../uploads/profile_images/<?php echo htmlspecialchars($artikel['umkm_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($artikel['business_name']); ?>" class="umkm-avatar">
                                        <?php else: ?>
                                            <div class="umkm-avatar" style="background: #D2691E; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                                🏪
                                            </div>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($artikel['business_name']); ?></span>
                                    </div>
                                    
                                    <!-- Review Rating Display -->
                                    <div class="card-rating">
                                        <?php if ($artikel['review_count'] > 0): ?>
                                            <div class="rating-stars">
                                                <?php 
                                                $rating = round($artikel['average_rating']);
                                                for ($i = 1; $i <= 5; $i++): 
                                                ?>
                                                    <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                                <?php endfor; ?>
                                                <span class="rating-value"><?php echo number_format($artikel['average_rating'], 1); ?></span>
                                            </div>
                                            <span class="review-count">(<?php echo $artikel['review_count']; ?> review<?php echo $artikel['review_count'] > 1 ? 's' : ''; ?>)</span>
                                        <?php else: ?>
                                            <span class="no-reviews">Belum ada review</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <a href="?view=detail&id=<?php echo $artikel['id']; ?>" class="btn-detail">
                                            🎫 Lihat Detail
                                        </a>
                                        <span class="card-date">
                                            <?php echo formatDate($artikel['created_at']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="?view=umkm&page=<?php echo $current_page - 1; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                    ⬅️ Sebelumnya
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?view=umkm&page=<?php echo $i; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a href="?view=umkm&page=<?php echo $current_page + 1; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                    Selanjutnya ➡️
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php else: ?>
                        <div class="no-results">
                            <div style="font-size: 5rem; margin-bottom: 1rem;">😔</div>
                            <h3>Tidak Ada Artikel Ditemukan</h3>
                            <p>Maaf, tidak ada artikel yang sesuai dengan pencarian Anda.</p>
                            <p>Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Home Page -->
        <section class="hero" id="home">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <span class="hero-badge fade-in">Welcome Back, <?php echo htmlspecialchars($user_data['full_name']); ?>!</span>
                <h1 class="hero-title">
                    <span class="hero-title-line">Discover the</span>
                    <span class="hero-title-line">Beauty of <span class="highlight">Papua</span></span>
                </h1>
                <p class="hero-description">Explore breathtaking landscapes, rich cultures, and unforgettable adventures with our AI-powered travel assistant.</p>
                <div class="hero-actions">
                    <a href="#destinations" class="btn btn-primary">
                        <i class="fas fa-compass"></i>
                        Explore Now
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="500">0</span>+</h3>
                        <p>Local Partners</p>
                    </div>
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="50">0</span>+</h3>
                        <p>Destinations</p>
                    </div>
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="1000">0</span>+</h3>
                        <p>Happy Travelers</p>
                    </div>
                </div>
            </div>
            <div class="hero-scroll-indicator">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section> 

        <section class="destinations" id="destinations">
            <div class="destination-container">
                <div class="destination-title fade-in">
                    <span class="section-label">Your Adventure Awaits</span>
                    <h2>This Is Your <b>Papua Journey</b></h2>
                    <p>Discover the true essence of Papua through a personalized journey tailored exclusively to your preferences and interests, where every adventure becomes your own unique story to tell.</p>
                    <div class="destination-features">
                        <div class="feature-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Safe & Secure</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-users"></i>
                            <span>Local Guides</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-star"></i>
                            <span>Best Rated</span>
                        </div>
                    </div>
                    <button class="btn btn-primary" onclick="showDestinationModal()">
                        <i class="fas fa-info-circle"></i>
                        Learn More
                    </button>
                </div>
                <div class="destination-media">
                    <div class="video-container">
                        <video autoplay muted loop playsinline>
                            <source src="../../assets/destination-video.mp4" type="video/mp4">
                        </video>
                        <div class="video-play-button" onclick="toggleVideoSound(this)">
                            <i class="fas fa-volume-mute"></i>
                        </div>
                    </div>
                    <div class="destination-cards">
                        <div class="mini-card fade-in">
                            <img src="../../assets/rajaAmpat.jpg" alt="Raja Ampat">
                            <span>Raja Ampat</span>
                        </div>
                        <div class="mini-card fade-in">
                            <img src="../../assets/TamanNasionalTelukCendrawasih.jpg" alt="Jayapura">
                            <span>Jayapura</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="experiences" class="experiences">
            <h2>Your Gateaway to <b>Authentic Experiences</b></h2>
            <div class="experiences-icons">
                <div class="icon-item">
                    <div class="icon">
                        <i class="fi fi-rr-mosque-alt"></i>
                    </div>
                    <p>Muslim Friendly</p>
                </div>
                <div class="icon-item">
                    <div class="icon">
                        <i class="fi fi-br-wheelchair"></i>
                    </div>
                    <p>Inclusive Tourism</p>
                </div>
                <div class="icon-item">
                    <div class="icon">
                        <i class="fi fi-sr-population-globe"></i>
                    </div>
                    <p>Community Updates</p>
                </div>
                <div class="icon-item">
                    <div class="icon">
                        <i class="fi fi-sr-badge-leaf"></i>
                    </div>
                    <p>Eco-Travel</p>
                </div>
            </div>
        </section>

        <section class="interests" id="interest">
            <div class="interest-container">
                <div class="interest-title fade-in">
                    <span class="section-label">Choose Your Adventure</span>
                    <h2>Explore Your <b>Interests</b></h2>
                    <p>Select your preferred activities and we'll create a personalized itinerary just for you</p>
                </div>
                <div class="interest-wrapper fade-in">
                    <button class="slider-nav slider-nav-prev" onclick="slideInterests('prev')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="interest-slider" id="interestSlider">
                        <div class="interest-card food" data-category="culinary">
                            <div class="interest-overlay"></div>
                            <div class="interest-content">
                                <i class="fi fi-rr-hamburger-soda"></i>
                                <h3>Food & Drink</h3>
                                <p>Taste authentic Papuan cuisine</p>
                                <span class="interest-tag">20+ Restaurants</span>
                            </div>
                        </div>
                        <div class="interest-card culture" data-category="cultural">
                            <div class="interest-overlay"></div>
                            <div class="interest-content">
                                <i class="fi fi-rr-people"></i>
                                <h3>Culture & Heritage</h3>
                                <p>Experience traditional ceremonies</p>
                                <span class="interest-tag">15+ Villages</span>
                            </div>
                        </div>
                        <div class="interest-card adventures" data-category="marine">
                            <div class="interest-overlay"></div>
                            <div class="interest-content">
                                <i class="fi fi-rr-dolphin"></i>
                                <h3>Underwater Adventures</h3>
                                <p>Dive in pristine coral reefs</p>
                                <span class="interest-tag">30+ Dive Sites</span>
                            </div>
                        </div>
                        <div class="interest-card tracking" data-category="hiking">
                            <div class="interest-overlay"></div>
                            <div class="interest-content">
                                <i class="fi fi-br-mountain"></i>
                                <h3>Trekking Tours</h3>
                                <p>Hike through rainforests</p>
                                <span class="interest-tag">25+ Trails</span>
                            </div>
                        </div>
                        <div class="interest-card wildlife" data-category="wildlife">
                            <div class="interest-overlay"></div>
                            <div class="interest-content">
                                <i class="fi fi-rr-bird"></i>
                                <h3>Wildlife & Nature</h3>
                                <p>See exotic birds of paradise</p>
                                <span class="interest-tag">40+ Species</span>
                            </div>
                        </div>
                    </div>
                    <button class="slider-nav slider-nav-next" onclick="slideInterests('next')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </section>

        <section class="plan-trip" id="plan">
            <div class="plan-container">
                <h2>Plan Your <b>Trip</b></h2>
                <div class="plan-content fade-in">
                    <div class="plan-card">
                        <i class="fi fi-rr-bag-map-pin"></i>
                        <h3>Before you go</h3>
                        <p>Get ready for your adventure with essential tips and information.</p>
                    </div>
                    <div class="plan-card">
                        <i class="fi fi-rr-car-bus"></i>
                        <h3>Transportation</h3>
                        <p>Navigate Papua with ease using our transportation guides.</p>
                    </div>
                    <div class="plan-card">
                        <i class="fi fi-rr-building"></i>
                        <h3>Accommodation</h3>
                        <p>Find the perfect place to stay during your journey.</p>
                    </div>
                    <div class="plan-card">
                        <i class="fi fi-rr-salad"></i>
                        <h3>Itinerary Ideas</h3>
                        <p>Explore suggested itineraries for a memorable trip.</p>
                    </div>
                    <div class="plan-card">
                        <i class="fi fi-rr-bus-ticket"></i>
                        <h3>Tour guide</h3>
                        <p>Connect with local guides for an authentic experience.</p>
                    </div>
                    <div class="plan-card">
                        <i class="fi fi-rr-guide-alt"></i>
                        <h3>Etiquette</h3>
                        <p>Learn about local customs and etiquette for a respectful visit.</p>
                    </div>
                </div>
            </div>
        </section>


        <!-- UMKM Section -->
        <section class="umkm-section" id="umkm">
            <div class="umkm-container">
                <div class="umkm-header fade-in">
                    <span class="section-label">Local Business</span>
                    <h2>Temukan <b>UMKM Papua</b></h2>
                    <p>Dukung ekonomi lokal dengan menjelajahi produk dan layanan autentik dari usaha mikro, kecil, dan menengah di Papua</p>
                </div>
                
                <?php if (count($articles) > 0): ?>
                <div class="articles-grid fade-in">
                    <?php foreach (array_slice($articles, 0, 8) as $artikel): ?>
                        <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $artikel['id']; ?>'">
                            <div class="article-image">
                                <?php if ($artikel['gambar']): ?>
                                    <img src="../../uploads/artikel_images/<?php echo htmlspecialchars($artikel['gambar']); ?>" 
                                         alt="<?php echo htmlspecialchars($artikel['judul']); ?>">
                                <?php else: ?>
                                    <div class="placeholder-image">
                                        📷
                                    </div>
                                <?php endif; ?>
                                <div class="card-category category-<?php echo $artikel['kategori']; ?>">
                                    <?php
                                    $kategori_icons = [
                                        'jasa' => '🔧 Jasa',
                                        'event' => '🎉 Event',
                                        'kuliner' => '🍽️ Kuliner',
                                        'kerajinan' => '🎨 Kerajinan',
                                        'wisata' => '🏝️ Wisata'
                                    ];
                                    echo $kategori_icons[$artikel['kategori']] ?? ucfirst($artikel['kategori']);
                                    ?>
                                </div>
                            </div>
                            
                            <div class="article-card-content">
                                <h4 class="article-card-title"><?php echo htmlspecialchars($artikel['judul']); ?></h4>
                                <div class="article-card-price"><?php echo formatPrice($artikel['harga']); ?></div>
                                
                                <div class="card-description">
                                    <?php echo truncateText(htmlspecialchars($artikel['deskripsi']), 80); ?>
                                </div>
                                
                                <div class="card-umkm">
                                    <?php if ($artikel['umkm_image']): ?>
                                        <img src="../../uploads/profile_images/<?php echo htmlspecialchars($artikel['umkm_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($artikel['business_name']); ?>" class="umkm-avatar">
                                    <?php else: ?>
                                        <div class="umkm-avatar" style="background: #D2691E; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                            🏪
                                        </div>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($artikel['business_name']); ?></span>
                                </div>
                                
                                <!-- Review Rating Display -->
                                <div class="card-rating">
                                    <?php if ($artikel['review_count'] > 0): ?>
                                        <div class="rating-stars">
                                            <?php 
                                            $rating = round($artikel['average_rating']);
                                            for ($i = 1; $i <= 5; $i++): 
                                            ?>
                                                <span class="star <?php echo $i <= $rating ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                            <span class="rating-value"><?php echo number_format($artikel['average_rating'], 1); ?></span>
                                        </div>
                                        <span class="review-count">(<?php echo $artikel['review_count']; ?> review<?php echo $artikel['review_count'] > 1 ? 's' : ''; ?>)</span>
                                    <?php else: ?>
                                        <span class="no-reviews">Belum ada review</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-actions">
                                    <a href="?view=detail&id=<?php echo $artikel['id']; ?>" class="btn-detail">
                                        🎫 Pesan Tiket
                                    </a>
                                    <span class="card-date">
                                        <?php echo formatDate($artikel['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <a href="?view=umkm" class="view-all-btn">
                    <i class="fas fa-arrow-right"></i>
                    Lihat Semua UMKM
                </a>
                <?php else: ?>
                <div class="no-results">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🏪</div>
                    <h3>Belum Ada UMKM Terdaftar</h3>
                    <p>Saat ini belum ada UMKM yang terdaftar dalam sistem.</p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="testimonials" id="testimonials">
            <div class="testimonials-container">
                <div class="testimonials-header fade-in">
                    <span class="section-label">What Travelers Say</span>
                    <h2>Real Stories from <b>Real Adventurers</b></h2>
                </div>
                <div class="testimonials-grid">
                    <div class="testimonial-card fade-in">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"The AI chatbot helped me plan the perfect diving trip to Raja Ampat. Found hidden spots I would never have discovered on my own!"</p>
                        <div class="testimonial-author">
                            <div class="avatar-icon" style="background-color: #4A90E2;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h4>Sarah M.</h4>
                                <span>Adventure Diver</span>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card fade-in">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"Connected with amazing local guides through the platform. The cultural experiences were authentic and unforgettable."</p>
                        <div class="testimonial-author">
                            <div class="avatar-icon" style="background-color: #50C878;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h4>John D.</h4>
                                <span>Culture Enthusiast</span>
                            </div>
                        </div>
                    </div>
                    <div class="testimonial-card fade-in">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"Best platform for exploring Papua! The local business connections made our trip smooth and supported the community."</p>
                        <div class="testimonial-author">
                            <div class="avatar-icon" style="background-color: #FF6B6B;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h4>Maria L.</h4>
                                <span>Eco-Tourist</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#destinations">Destinations</a></li>
                    <li><a href="#experiences">Experiences</a></li>
                    <li><a href="#plan">Plan your trip</a></li>
                    <li><a href="#umkm">UMKM</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Contact Us</h3>
                <p>Email: <a href="mailto:info@papuajourney.com">info@papuajourney.com</a></p>
                <p>Phone: <a href="tel:+62123456789">+62 123 456 789</a></p>
            </div>
            <div class="footer-social">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Papua Journey. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Auto submit search form on Enter
        document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
        
        // Smooth scroll for pagination and navigation
        document.querySelectorAll('.pagination a, .nav-links a').forEach(link => {
            link.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });
        
        // Calculate total price based on quantity for booking form
        document.getElementById('jumlah_tiket')?.addEventListener('input', function() {
            const quantity = parseInt(this.value) || 1;
            const pricePerTicket = <?php echo isset($article['harga']) ? $article['harga'] : 0; ?>;
            const total = quantity * pricePerTicket;
            const totalElement = document.getElementById('total-amount');
            if (totalElement) {
                totalElement.textContent = formatPrice(total);
            }
        });
        
        function formatPrice(price) {
            return 'Rp ' + price.toLocaleString('id-ID');
        }
        
        // Add to cart function for logged in users
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
                    alert('❌ ' + data.message);
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                console.error('Error:', error);
                // Show success notification as fallback
                showNotification();
            });
        }
        
        // Show notification function
        function showNotification() {
            const overlay = document.getElementById('notification-overlay');
            if (overlay) {
                overlay.classList.add('show');
                
                // Hide notification after 2 seconds
                setTimeout(() => {
                    overlay.classList.remove('show');
                }, 2000);
            }
        }
        
        // Mobile menu toggle
        document.querySelector('.mobile-menu-toggle')?.addEventListener('click', function() {
            this.classList.toggle('active');
            document.querySelector('.mobile-nav')?.classList.toggle('active');
        });
        
        document.querySelector('.mobile-nav-close')?.addEventListener('click', function() {
            document.querySelector('.mobile-menu-toggle')?.classList.remove('active');
            document.querySelector('.mobile-nav')?.classList.remove('active');
        });
        
        // Scroll progress bar
        window.addEventListener('scroll', function() {
            const scrollProgress = document.querySelector('.scroll-progress-bar');
            const scrollTop = window.pageYOffset;
            const docHeight = document.body.offsetHeight - window.innerHeight;
            const scrollPercent = (scrollTop / docHeight) * 100;
            scrollProgress.style.width = scrollPercent + '%';
            
            // Show/hide scroll to top button
            const scrollBtn = document.querySelector('.scroll-to-top');
            if (scrollTop > 300) {
                scrollBtn.classList.add('show');
            } else {
                scrollBtn.classList.remove('show');
            }
            
            // Header scroll effect
            const header = document.querySelector('.header');
            if (scrollTop > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Animate stats counter
        function animateStats() {
            const stats = document.querySelectorAll('.stat-number');
            stats.forEach(stat => {
                const target = parseInt(stat.getAttribute('data-target'));
                const count = parseInt(stat.textContent);
                const increment = target / 100;
                
                if (count < target) {
                    stat.textContent = Math.ceil(count + increment);
                    setTimeout(() => animateStats(), 20);
                } else {
                    stat.textContent = target;
                }
            });
        }
        
        // Start animation when page loads
        window.addEventListener('load', () => {
            setTimeout(animateStats, 1000);
        });
        
        // Fade in animation for elements
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-visible');
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.fade-in').forEach(el => {
            observer.observe(el);
        });
        
        // Interest slider functionality
        function slideInterests(direction) {
            const slider = document.getElementById('interestSlider');
            const cardWidth = 320; // card width + gap
            const currentScroll = slider.scrollLeft;
            
            if (direction === 'next') {
                slider.scrollTo({
                    left: currentScroll + cardWidth,
                    behavior: 'smooth'
                });
            } else {
                slider.scrollTo({
                    left: currentScroll - cardWidth,
                    behavior: 'smooth'
                });
            }
        }
        
        // Tab functionality for booking form
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Here you could add logic to show different form content based on tab
                console.log('Selected tab:', this.getAttribute('data-tab'));
            });
        });
        
        // Form validation
        document.getElementById('bookingForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const destination = document.getElementById('destination').value;
            const checkin = document.getElementById('checkin').value;
            const checkout = document.getElementById('checkout').value;
            
            if (!destination || !checkin || !checkout) {
                alert('Please fill in all required fields');
                return;
            }
            
            // Check if checkout is after checkin
            if (new Date(checkout) <= new Date(checkin)) {
                alert('Check-out date must be after check-in date');
                return;
            }
            
            alert('Booking search completed! This would typically redirect to results page.');
        });
        
        // Set minimum date for booking form
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('checkin')?.setAttribute('min', today);
        document.getElementById('checkout')?.setAttribute('min', today);
        
        // Update checkout min date when checkin changes
        document.getElementById('checkin')?.addEventListener('change', function() {
            const checkinDate = new Date(this.value);
            checkinDate.setDate(checkinDate.getDate() + 1);
            const minCheckout = checkinDate.toISOString().split('T')[0];
            document.getElementById('checkout').setAttribute('min', minCheckout);
        });
    </script>
</body>
</html>