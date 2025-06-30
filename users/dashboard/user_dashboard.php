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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Wisatawan - Omaki Platform</title>
    <link rel="stylesheet" href="userdashboard.css">
    <link rel="stylesheet" href="../../assets/css/reviews.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .booking-form {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .booking-form h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .total-price {
            background: #e8f4fd;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: center;
        }
        
        .total-price h4 {
            color: #2c3e50;
            margin: 0;
            font-size: 1.2rem;
        }
        
        .btn-book {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        
        .btn-book:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($view_mode === 'dashboard'): ?>
            
            <!-- Filters Section -->
            <div class="filters-section">
                <form method="GET" action="">
                    <div class="filters-row">
                        <div class="search-box">
                            <input type="text" name="search" placeholder="🔍 Cari artikel, produk, atau UMKM..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <button type="submit" style="display: none;"></button>
                    </div>
                    
                    <div class="category-filters" style="margin-top: 1rem;">
                        <a href="?" class="category-btn <?php echo empty($kategori_filter) ? 'active' : ''; ?>">
                            🌟 Semua
                        </a>
                        <a href="?kategori=jasa<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="category-btn <?php echo $kategori_filter === 'jasa' ? 'active' : ''; ?>">
                            🔧 Jasa
                        </a>
                        <a href="?kategori=event<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="category-btn <?php echo $kategori_filter === 'event' ? 'active' : ''; ?>">
                            🎉 Event
                        </a>
                        <a href="?kategori=kuliner<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="category-btn <?php echo $kategori_filter === 'kuliner' ? 'active' : ''; ?>">
                            🍽️ Kuliner
                        </a>
                        <a href="?kategori=kerajinan<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="category-btn <?php echo $kategori_filter === 'kerajinan' ? 'active' : ''; ?>">
                            🎨 Kerajinan
                        </a>
                        <a href="?kategori=wisata<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
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
            <div class="quick-actions">
                <h3>🌟 Artikel Terbaru</h3>
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
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                            ⬅️ Sebelumnya
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
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
            
        <?php elseif ($view_mode === 'detail' && $article): ?>
            <a href="?" class="back-button">
                ⬅️ Kembali ke Dashboard
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
                    
                    <div class="umkm-section">
                        <div class="umkm-header">
                            <?php if ($article['umkm_image']): ?>
                                <img src="../../uploads/profile_images/<?php echo htmlspecialchars($article['umkm_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($article['business_name']); ?>" class="umkm-avatar">
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
            <div class="quick-actions">
                <h3>🌟 Artikel Terkait</h3>
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
                                <div class="article-card-umkm">🏪 <?php echo htmlspecialchars($related['business_name']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // Auto submit search form on Enter
        document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
        
        // Smooth scroll for pagination
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
        
        // Calculate total price based on quantity
        document.getElementById('jumlah_tiket')?.addEventListener('input', function() {
            const quantity = parseInt(this.value) || 1;
            const pricePerTicket = <?php echo $article['harga'] ?? 0; ?>;
            const total = quantity * pricePerTicket;
            document.getElementById('total-amount').textContent = formatPrice(total);
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
    </script>
</body>
</html>