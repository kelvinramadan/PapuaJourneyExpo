<?php
//allumkm.php - Modified for Guest Mode Support
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in - MODIFIED FOR GUEST MODE
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user';
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$user_name = $is_logged_in ? $_SESSION['user_name'] : 'Guest';
$user_email = $is_logged_in ? $_SESSION['user_email'] : null;

require_once '../../config/database.php';

$message = '';
$error_message = '';

// Get user details from database (only if logged in)
$user_data = [];
$cart_count = 0;

$db = getDbConnection();

if ($is_logged_in) {
    $stmt = $db->prepare("SELECT full_name, email, phone, address, profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();

    // Get cart count for navbar
    $cart_stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    $cart_count = $cart_result->fetch_assoc()['count'];
    $cart_stmt->close();
}

// Get filters and search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 12;
$offset = ($current_page - 1) * $items_per_page;

// Check if viewing article detail
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'list';
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
        $view_mode = 'list'; // Reset to list if article not found
    }
    $stmt->close();
}

// Get articles for list view with filtering
$articles = [];
$total_articles = 0;
$total_pages = 1;

if ($view_mode === 'list') {
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

// Get category counts for categories section
$category_counts = [
    'jasa' => 0,
    'event' => 0,
    'kuliner' => 0,
    'kerajinan' => 0,
    'wisata' => 0
];

$category_query = "SELECT kategori, COUNT(*) as count FROM artikel WHERE status = 'active' GROUP BY kategori";
$category_result = $db->query($category_query);
while ($row = $category_result->fetch_assoc()) {
    if (isset($category_counts[$row['kategori']])) {
        $category_counts[$row['kategori']] = $row['count'];
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
    <title>Papua Journey - Semua UMKM</title>
    <meta name="description" content="Jelajahi semua UMKM dan produk lokal Papua. Temukan kerajinan, kuliner, jasa, dan wisata terbaik dari usaha mikro, kecil, dan menengah di Papua.">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="../../assets/logo.png" as="image">
    <link rel="preload" href="../../assets/umkm_hero.jpg" as="image">
    
    <!-- Stylesheets -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-solid-rounded/css/uicons-solid-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    <link rel="stylesheet" href="../../assets/css/reviews.css">
    <link rel="stylesheet" href="allumkm.css">
    
    <!-- Scripts -->
    <script src="../../script.js" defer></script>
    
    <style>
        /* Body styling to account for navbar */
        body {
            font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
            background-color: #EBE7E4;
            color: #FFFCF7;
            scroll-behavior: smooth;
            line-height: 1.6;
            overflow-x: hidden;
            padding-top: 80px; /* Add padding for fixed navbar */
        }
        
        /* Enhanced Categories Grid Styles */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            padding: 20px 0;
        }

        .category-card {
            background: linear-gradient(135deg, var(--card-color-start), var(--card-color-end));
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transition: all 0.3s ease;
        }

        .category-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .category-card:hover::before {
            top: -30%;
            right: -30%;
        }

        /* Category Card Colors */
        .category-card.jasa {
            --card-color-start: #4a90e2;
            --card-color-end: #357abd;
        }

        .category-card.event {
            --card-color-start: #d2691e;
            --card-color-end: #8b4513;
        }

        .category-card.kuliner {
            --card-color-start: #a0522d;
            --card-color-end: #8b4513;
        }

        .category-card.kerajinan {
            --card-color-start: #2e7d32;
            --card-color-end: #1b5e20;
        }

        .category-card.wisata {
            --card-color-start: #5e35b1;
            --card-color-end: #4527a0;
        }

        .category-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }

        .category-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .category-description {
            font-size: 1rem;
            line-height: 1.4;
            opacity: 0.9;
            margin-bottom: 25px;
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .umkm-count {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .category-card:hover .umkm-count {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.05);
        }

        .category-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s ease;
        }

        .category-card:hover::after {
            left: 100%;
        }

        /* Categories Grid Animation */
        .category-card {
            animation: fadeInUp 0.6s ease forwards;
        }

        .category-card:nth-child(1) { animation-delay: 0.1s; }
        .category-card:nth-child(2) { animation-delay: 0.2s; }
        .category-card:nth-child(3) { animation-delay: 0.3s; }
        .category-card:nth-child(4) { animation-delay: 0.4s; }
        .category-card:nth-child(5) { animation-delay: 0.5s; }

        /* Responsive Categories Grid */
        @media (max-width: 1200px) {
            .categories-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 15px;
            }
            
            .category-card {
                padding: 30px 20px;
                min-height: 250px;
            }
        }

        @media (max-width: 768px) {
            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                padding: 15px 0;
            }
            
            .category-card {
                padding: 25px 15px;
                min-height: 220px;
            }
            
            .category-icon {
                font-size: 2.5rem;
                margin-bottom: 15px;
            }
            
            .category-title {
                font-size: 1.4rem;
                margin-bottom: 10px;
            }
            
            .category-description {
                font-size: 0.9rem;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 480px) {
            .categories-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .category-card {
                min-height: 200px;
            }
        }

        /* Scroll Progress Bar */
        .scroll-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(to right, #DC9B11, #f4b63b);
            z-index: 10000;
            transition: width 0.2s ease-out;
        }

        /* Back button for detail view */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #DC9B11;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            padding: 0.75rem 1.5rem;
            border: 2px solid #DC9B11;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: #DC9B11;
            color: white;
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; ?>
    
    <!-- Scroll Progress Indicator -->
    <div class="scroll-progress-bar"></div>

    <?php if ($view_mode === 'detail' && $article): ?>
        <!-- Article Detail View -->
        <div class="detail-container">
            <div class="container">
                <a href="?" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Kembali ke UMKM
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
                        <div class="notification-message">
                            <?php if ($is_logged_in): ?>
                                Berhasil ditambahkan!
                            <?php else: ?>
                                Silakan login terlebih dahulu!
                            <?php endif; ?>
                        </div>
                        <div class="notification-submessage">
                            <?php if ($is_logged_in): ?>
                                Item telah ditambahkan ke keranjang
                            <?php else: ?>
                                Anda perlu login untuk menambahkan item ke keranjang
                            <?php endif; ?>
                        </div>
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
                            <div class="article-price"><?php echo formatPrice($article['harga']); ?> / item</div>
                            <div class="article-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo formatDate($article['created_at']); ?>
                            </div>
                        </div>
                        
                        <div class="article-description">
                            <?php echo nl2br(htmlspecialchars($article['deskripsi'])); ?>
                        </div>
                        
                        <!-- UMKM Info Section -->
                        <div class="umkm-info-section">
                            <h3><i class="fas fa-store"></i> Informasi UMKM</h3>
                            <div class="umkm-info-grid">
                                <div class="info-item">
                                    <i class="fas fa-user"></i>
                                    <div>
                                        <strong>Pemilik UMKM</strong>
                                        <p><?php echo htmlspecialchars($article['owner_name']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-store-alt"></i>
                                    <div>
                                        <strong>Jenis Usaha</strong>
                                        <p><?php echo ucfirst(htmlspecialchars($article['business_type'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-phone-alt"></i>
                                    <div>
                                        <strong>Telepon</strong>
                                        <p><?php echo htmlspecialchars($article['phone']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <strong>Alamat</strong>
                                        <p><?php echo htmlspecialchars($article['address']); ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($article['umkm_description']): ?>
                                <div class="info-item description-item">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <strong>Tentang UMKM</strong>
                                        <p><?php echo nl2br(htmlspecialchars($article['umkm_description'])); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Enhanced Booking Form -->
                            <div class="booking-section">
                                <h3><i class="fas fa-ticket-alt"></i> Pesan Produk</h3>
                                <div id="cart-message" style="display: none;"></div>
                                <form id="add-to-cart-form" class="booking-form">
                                    <input type="hidden" name="item_type" value="artikel">
                                    <input type="hidden" name="item_id" value="<?php echo $article['id']; ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="jumlah_tiket">
                                                <i class="fas fa-shopping-bag"></i>
                                                Jumlah Item *
                                            </label>
                                            <input type="number" name="quantity" id="jumlah_tiket" min="1" max="10" value="1" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="tanggal_kunjungan">
                                                <i class="fas fa-calendar-alt"></i>
                                                Tanggal Pemesanan *
                                            </label>
                                            <input type="date" name="booking_date" id="tanggal_kunjungan" 
                                                   min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="visitors">
                                                <i class="fas fa-users"></i>
                                                Untuk Berapa Orang:
                                            </label>
                                            <select id="visitors" name="visitors">
                                                <option value="1">1 Orang</option>
                                                <option value="2">2 Orang</option>
                                                <option value="3">3 Orang</option>
                                                <option value="4">4 Orang</option>
                                                <option value="5+">5+ Orang</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="visit_time">
                                                <i class="fas fa-clock"></i>
                                                Waktu Pengambilan:
                                            </label>
                                            <select id="visit_time" name="visit_time">
                                                <option value="morning">Pagi (08:00 - 12:00)</option>
                                                <option value="afternoon">Siang (12:00 - 16:00)</option>
                                                <option value="evening">Sore (16:00 - 18:00)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="catatan">
                                            <i class="fas fa-comment"></i>
                                            Catatan Khusus (Opsional):
                                        </label>
                                        <textarea name="notes" id="catatan" rows="3" 
                                                  placeholder="Catatan khusus untuk pesanan Anda..."></textarea>
                                    </div>
                                    
                                    <div class="booking-summary">
                                        <div class="summary-row">
                                            <span>Harga per Item:</span>
                                            <strong><?php echo formatPrice($article['harga']); ?></strong>
                                        </div>
                                        <div class="summary-row">
                                            <span>Jumlah Item:</span>
                                            <strong><span id="jumlah-tiket">1</span> item</strong>
                                        </div>
                                        <div class="summary-row total-row">
                                            <span>Total Estimasi:</span>
                                            <strong id="total-amount"><?php echo formatPrice($article['harga']); ?></strong>
                                        </div>
                                    </div>
                                    
                                    <?php if ($is_logged_in): ?>
                                        <button type="button" onclick="addToCart()" class="btn btn-primary btn-book">
                                            <i class="fas fa-shopping-cart"></i>
                                            Tambahkan ke Keranjang
                                        </button>
                                    <?php else: ?>
                                        <a href="../../login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary btn-book" style="text-decoration: none; display: inline-block; text-align: center;">
                                            <i class="fas fa-lock"></i>
                                            Login untuk Memesan
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Reviews Section -->
                        <div class="reviews-section" id="reviews-section">
                            <div class="reviews-card">
                                <div class="reviews-header">
                                    <h3 class="reviews-title">⭐ Ulasan & Rating</h3>
                                    <?php if ($is_logged_in): ?>
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
                
                <?php if (count($related_articles) > 0): ?>
                <div class="related-section">
                    <div class="section-header">
                        <h3><i class="fas fa-star"></i> Artikel Terkait</h3>
                        <p>Jelajahi artikel lain dalam kategori <?php echo $kategori_icons[$article['kategori']] ?? ucfirst($article['kategori']); ?></p>
                    </div>
                    <div class="articles-grid">
                        <?php foreach ($related_articles as $related): ?>
                            <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $related['id']; ?>'">
                                <div class="article-image">
                                    <?php if ($related['gambar']): ?>
                                        <img src="../../uploads/artikel_images/<?php echo htmlspecialchars($related['gambar']); ?>" 
                                             alt="<?php echo htmlspecialchars($related['judul']); ?>"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div class="placeholder-image">
                                            📷
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-category category-<?php echo $related['kategori']; ?>">
                                        <?php
                                        echo $kategori_icons[$related['kategori']] ?? ucfirst($related['kategori']);
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="article-card-content">
                                    <h4 class="article-card-title"><?php echo htmlspecialchars($related['judul']); ?></h4>
                                    <div class="article-card-price"><?php echo formatPrice($related['harga']); ?></div>
                                    <div class="card-umkm">
                                        <i class="fas fa-store"></i>
                                        <?php echo htmlspecialchars($related['business_name']); ?>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <a href="?view=detail&id=<?php echo $related['id']; ?>" class="btn-detail">
                                            <i class="fas fa-eye"></i>
                                            Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- All UMKM List View -->
        <!-- Hero Section -->
        <section class="hero" id="home">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <span class="hero-badge fade-in">🏪 Selamat Datang di UMKM Papua</span>
                <h1 class="hero-title">
                    <span class="hero-title-line">Temukan Produk dan Layanan</span>
                    <span class="hero-title-line">UMKM <span class="highlight">Papua</span></span>
                </h1>
                <p class="hero-description">Jelajahi keberagaman usaha mikro, kecil, dan menengah di Papua. Dari kuliner tradisional hingga kerajinan tangan, temukan keunikan produk dan layanan lokal.</p>
                <div class="hero-actions">
                    <a href="#umkm-categories" class="btn btn-primary">
                        <i class="fas fa-store"></i>
                        Jelajahi UMKM
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="<?php echo count($articles); ?>"><?php echo count($articles); ?></span></h3>
                        <p>UMKM Tersedia</p>
                    </div>
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="5">5</span></h3>
                        <p>Kategori</p>
                    </div>
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="500">500</span>+</h3>
                        <p>Pelanggan Puas</p>
                    </div>
                </div>
            </div>
            <div class="hero-scroll-indicator">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>

        <!-- UMKM Categories Section -->
        <section class="umkm-categories" id="umkm-categories">
            <div class="types-container">
                <div class="section-header fade-in">
                    <span class="section-label">Kategori UMKM</span>
                    <h2>Pilih Jenis <b>Produk & Layanan</b></h2>
                    <p>Temukan berbagai kategori UMKM sesuai dengan kebutuhan dan minat Anda</p>
                </div>
                
                <div class="categories-grid fade-in">
                    <!-- Jasa Card -->
                    <div class="category-card jasa" onclick="filterByCategory('jasa')">
                        <div>
                            <div class="category-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <h3 class="category-title">Jasa</h3>
                            <p class="category-description">Layanan dan bantuan profesional</p>
                        </div>
                        <div class="umkm-count"><?php echo $category_counts['jasa']; ?> UMKM</div>
                    </div>

                    <!-- Event Card -->
                    <div class="category-card event" onclick="filterByCategory('event')">
                        <div>
                            <div class="category-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h3 class="category-title">Event</h3>
                            <p class="category-description">Acara dan kegiatan spesial</p>
                        </div>
                        <div class="umkm-count"><?php echo $category_counts['event']; ?> UMKM</div>
                    </div>

                    <!-- Kuliner Card -->
                    <div class="category-card kuliner" onclick="filterByCategory('kuliner')">
                        <div>
                            <div class="category-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <h3 class="category-title">Kuliner</h3>
                            <p class="category-description">Makanan dan minuman khas</p>
                        </div>
                        <div class="umkm-count"><?php echo $category_counts['kuliner']; ?> UMKM</div>
                    </div>

                    <!-- Kerajinan Card -->
                    <div class="category-card kerajinan" onclick="filterByCategory('kerajinan')">
                        <div>
                            <div class="category-icon">
                                <i class="fas fa-palette"></i>
                            </div>
                            <h3 class="category-title">Kerajinan</h3>
                            <p class="category-description">Produk seni dan kerajinan tangan</p>
                        </div>
                        <div class="umkm-count"><?php echo $category_counts['kerajinan']; ?> UMKM</div>
                    </div>

                    <!-- Wisata Card -->
                    <div class="category-card wisata" onclick="filterByCategory('wisata')">
                        <div>
                            <div class="category-icon">
                                <i class="fas fa-mountain"></i>
                            </div>
                            <h3 class="category-title">Wisata</h3>
                            <p class="category-description">Destinasi dan pengalaman wisata</p>
                        </div>
                        <div class="umkm-count"><?php echo $category_counts['wisata']; ?> UMKM</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content Section -->
        <section class="umkm-section" id="umkm-list">
            <div class="container">
                <!-- Filter Section -->
                <div class="filters-section">
                    <div class="filters-header">
                        <h3>Temukan UMKM Sesuai Kebutuhan</h3>
                        <p>Filter berdasarkan kategori dan kata kunci</p>
                    </div>
                    
                    <form method="GET" class="filters-form">
                        <div class="filters-row">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Cari artikel, produk, atau UMKM..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="filter-select">
                                <select name="kategori" onchange="this.form.submit()">
                                    <option value="">🌟 Semua Kategori</option>
                                    <option value="jasa" <?php echo $kategori_filter == 'jasa' ? 'selected' : ''; ?>>🔧 Jasa</option>
                                    <option value="event" <?php echo $kategori_filter == 'event' ? 'selected' : ''; ?>>🎉 Event</option>
                                    <option value="kuliner" <?php echo $kategori_filter == 'kuliner' ? 'selected' : ''; ?>>🍽️ Kuliner</option>
                                    <option value="kerajinan" <?php echo $kategori_filter == 'kerajinan' ? 'selected' : ''; ?>>🎨 Kerajinan</option>
                                    <option value="wisata" <?php echo $kategori_filter == 'wisata' ? 'selected' : ''; ?>>🏝️ Wisata</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i>
                                Filter
                            </button>
                        </div>
                        
                        <?php if (!empty($kategori_filter) || !empty($search)): ?>
                            <div class="active-filters">
                                <span class="filter-label">Filter aktif:</span>
                                <?php if (!empty($search)): ?>
                                    <span class="filter-tag">
                                        Pencarian: "<?php echo htmlspecialchars($search); ?>"
                                        <a href="?kategori=<?php echo $kategori_filter; ?>" class="remove-filter">×</a>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($kategori_filter)): ?>
                                    <span class="filter-tag">
                                        Kategori: <?php echo ucfirst($kategori_filter); ?>
                                        <a href="?search=<?php echo urlencode($search); ?>" class="remove-filter">×</a>
                                    </span>
                                <?php endif; ?>
                                <a href="allumkm.php" class="clear-all-filters">Hapus semua filter</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Results Section -->
                <div class="results-section">
                    <?php if (!empty($articles)): ?>
                        <div class="results-header">
                            <h4>Menampilkan <?php echo count($articles); ?> dari <?php echo $total_articles; ?> artikel</h4>
                            <div class="view-options">
                                <button class="view-btn active" data-view="grid">
                                    <i class="fas fa-th-large"></i>
                                </button>
                                <button class="view-btn" data-view="list">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="articles-grid fade-in">
                            <?php foreach ($articles as $artikel): ?>
                                <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $artikel['id']; ?>'">
                                    <div class="article-image">
                                        <?php if ($artikel['gambar']): ?>
                                            <img src="../../uploads/artikel_images/<?php echo htmlspecialchars($artikel['gambar']); ?>" 
                                                 alt="<?php echo htmlspecialchars($artikel['judul']); ?>"
                                                 loading="lazy">
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
                                        <div class="card-favorite">
                                            <i class="far fa-heart"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="article-card-content">
                                        <h4 class="article-card-title"><?php echo htmlspecialchars($artikel['judul']); ?></h4>
                                        <div class="article-card-price"><?php echo formatPrice($artikel['harga']); ?></div>
                                        
                                        <div class="card-description">
                                            <?php echo truncateText(htmlspecialchars($artikel['deskripsi']), 100); ?>
                                        </div>
                                        
                                        <div class="card-umkm">
                                            <?php if ($artikel['umkm_image']): ?>
                                                <img src="../../uploads/profile_images/<?php echo htmlspecialchars($artikel['umkm_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($artikel['business_name']); ?>" class="umkm-avatar">
                                            <?php else: ?>
                                                <div class="umkm-avatar">
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
                                                <i class="fas fa-eye"></i>
                                                Lihat Detail
                                            </a>
                                            <span class="card-date">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?php echo formatDate($artikel['created_at']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Enhanced Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination-container">
                                <div class="pagination-info">
                                    Halaman <span class="highlight"><?php echo $current_page; ?></span> dari <span class="highlight"><?php echo $total_pages; ?></span>
                                    (<?php echo $total_articles; ?> total artikel)
                                </div>
                                
                                <div class="pagination">
                                    <?php if ($current_page > 1): ?>
                                        <a href="?page=1<?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                           class="pagination-btn first-page" title="Halaman Pertama">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                        <a href="?page=<?php echo $current_page - 1; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                           class="pagination-btn prev-page" title="Halaman Sebelumnya">
                                            <i class="fas fa-angle-left"></i> Sebelumnya
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <?php if ($i == $current_page): ?>
                                            <span class="current"><?php echo $i; ?></span>
                                        <?php else: ?>
                                            <a href="?page=<?php echo $i; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($current_page < $total_pages): ?>
                                        <a href="?page=<?php echo $current_page + 1; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                           class="pagination-btn next-page" title="Halaman Selanjutnya">
                                            Selanjutnya <i class="fas fa-angle-right"></i>
                                        </a>
                                        <a href="?page=<?php echo $total_pages; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                           class="pagination-btn last-page" title="Halaman Terakhir">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <!-- No Results -->
                        <div class="no-results">
                            <div class="no-results-icon">😔</div>
                            <h3>Tidak Ada Artikel Ditemukan</h3>
                            <p>Maaf, tidak ada artikel yang sesuai dengan pencarian Anda.</p>
                            <div class="no-results-suggestions">
                                <h4>Coba hal berikut:</h4>
                                <ul>
                                    <li>Ubah kata kunci pencarian</li>
                                    <li>Pilih kategori yang berbeda</li>
                                    <li>Hapus filter pencarian</li>
                                    <li>Periksa ejaan kata kunci</li>
                                </ul>
                            </div>
                            <a href="allumkm.php" class="btn btn-primary">
                                <i class="fas fa-refresh"></i>
                                Lihat Semua Artikel
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Back to Top Button -->
    <button class="scroll-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#umkm-categories">Kategori</a></li>
                    <li><a href="#umkm-list">UMKM</a></li>
                    <li><a href="../dashboard/user_dashboard.php">Dashboard</a></li>
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
                    <a href="https://www.instagram.com/kelvinoktabrian/"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Papua Journey. All rights reserved.</p>
        </div>
    </footer>
    
    <script>
        // Reviews functionality
        let currentPage = 1;
        let currentSort = 'newest';
        
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('reviews-section')) {
                loadReviews(1);
            }
            
            // Calculate total on input change for booking form
            document.getElementById('jumlah_tiket')?.addEventListener('input', function() {
                const quantity = parseInt(this.value) || 1;
                const pricePerTicket = <?php echo isset($article['harga']) ? $article['harga'] : 0; ?>;
                const total = quantity * pricePerTicket;
                
                document.getElementById('jumlah-tiket').textContent = quantity;
                document.getElementById('total-amount').textContent = formatPrice(total);
            });
        });
        
        function loadReviews(page = 1, append = false) {
            currentPage = page;
            currentSort = document.getElementById('sortReviews').value;
            
            fetch(`../reviews/get_reviews.php?item_type=artikel&item_id=<?php echo isset($article['id']) ? $article['id'] : 0; ?>&page=${page}&sort_by=${currentSort}`)
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
            if (review.media && review.media.length > 0) {
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
                                ${!<?php echo $is_logged_in ? 'true' : 'false'; ?> ? 'disabled title="Login untuk vote"' : ''}>
                            <i class="fas fa-thumbs-up"></i> 
                            <span>${review.helpful_count}</span>
                        </button>
                        <button class="helpful-btn ${review.user_vote === '0' ? 'voted' : ''}" 
                                onclick="voteHelpful(${review.id}, false)"
                                ${!<?php echo $is_logged_in ? 'true' : 'false'; ?> ? 'disabled title="Login untuk vote"' : ''}>
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
            <?php if (!$is_logged_in): ?>
            alert('Silakan login untuk memberikan vote');
            window.location.href = '../../login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
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
        
        // Add to cart function
        function addToCart() {
            <?php if (!$is_logged_in): ?>
            // Show guest notification
            showNotification();
            return;
            <?php endif; ?>
            
            const form = document.getElementById('add-to-cart-form');
            const formData = new FormData(form);
            
            // Show loading state
            const btn = form.querySelector('button');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
            
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
                alert('❌ Failed to add item to cart. Please try again.');
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
        
        // Filter by category function
        function filterByCategory(category) {
            window.location.href = '?kategori=' + category;
        }
        
        // Format price function
        function formatPrice(price) {
            return 'Rp ' + price.toLocaleString('id-ID');
        }
        
        // Category card click handlers
        function handleCategoryClick(category) {
            // Add click effect
            event.target.closest('.category-card').style.transform = 'scale(0.95)';
            setTimeout(() => {
                event.target.closest('.category-card').style.transform = '';
            }, 150);

            // Filter by category
            filterByCategory(category);
        }
        
        // Make category cards clickable with accessibility
        document.querySelectorAll('.category-card').forEach((card, index) => {
            card.setAttribute('tabindex', '0');
            card.setAttribute('role', 'button');
            card.setAttribute('aria-label', `Kategori ${card.querySelector('.category-title').textContent}`);
            
            // Add keyboard support
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    card.click();
                }
            });
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
        });
        
        // View toggle functionality
        document.querySelectorAll('.view-btn')?.forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const view = this.getAttribute('data-view');
                const grid = document.querySelector('.articles-grid');
                
                if (view === 'list') {
                    grid.classList.add('list-view');
                } else {
                    grid.classList.remove('list-view');
                }
            });
        });
        
        // Favorite toggle function
        document.querySelectorAll('.card-favorite')?.forEach(favoriteBtn => {
            favoriteBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const icon = this.querySelector('i');
                if (icon.classList.contains('far')) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    this.style.color = '#e74c3c';
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    this.style.color = '';
                }
            });
        });
        
        // Auto submit search form on Enter
        document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
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
        
        // Smooth scroll for navigation
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
        
        // Animate stats in hero section
        function animateStats() {
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.getAttribute('data-target'));
                const duration = 2000; // 2 seconds
                const step = target / (duration / 30); // Update every 30ms
                let current = 0;
                
                const counter = setInterval(() => {
                    current += step;
                    if (current >= target) {
                        clearInterval(counter);
                        stat.textContent = target;
                    } else {
                        stat.textContent = Math.floor(current);
                    }
                }, 30);
            });
        }
        
        // Run animation when page loads
        window.addEventListener('load', animateStats);
        
        // Add entrance animations for category cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.category-card');
            
            // Intersection Observer for scroll animations
            const cardObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, {
                threshold: 0.1
            });

            cards.forEach(card => {
                cardObserver.observe(card);
            });
        });
    </script>
</body>
</html>