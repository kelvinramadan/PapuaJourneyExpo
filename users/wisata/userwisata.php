<?php
// users/userwisata.php
// Start session first before any output
if (!isset($_SESSION)) {
    session_start();
}

require_once '../../config/database.php';

$db = getDbConnection();

// Get session data if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$user_name = $is_logged_in ? $_SESSION['user_name'] : null;
$user_email = $is_logged_in ? $_SESSION['user_email'] : null;

// Get cart count for navbar (only if logged in)
$cart_count = 0;
if ($is_logged_in) {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_count = $result->fetch_assoc()['count'];
    $stmt->close();
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
    <link rel="preload" href="../../assets/papuapantai.jpg" as="image">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Additional CSS for enhanced UI -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
            background-color: #EBE7E4;
            color: #FFFCF7;
            scroll-behavior: smooth;
            line-height: 1.6;
            overflow-x: hidden;
            padding-top: 80px;
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
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; ?>
    
    <!-- Scroll Progress Indicator -->
    <div class="scroll-progress-bar"></div>
    
    <?php if ($view_mode === 'list'): ?>
        <!-- Enhanced Hero Section for Tourism -->
        <section class="hero" id="home">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <span class="hero-badge fade-in">🏝️ Welcome to Papua Tourism</span>
                <h1 class="hero-title">
                    <span class="hero-title-line">Explore Paradise</span>
                    <span class="hero-title-line">Adventures in <span class="highlight">Papua</span></span>
                </h1>
                <p class="hero-description">Discover breathtaking natural wonders, rich cultural heritage, and unforgettable experiences in the land of paradise. From pristine beaches to mystical mountains, Papua offers adventures beyond imagination.</p>
                <div class="hero-actions">
                    <a href="#tourism" class="btn btn-primary">
                        <i class="fas fa-map-marked-alt"></i>
                        Explore Destinations
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="<?php echo count($wisata_data); ?>"><?php echo count($wisata_data); ?></span></h3>
                        <p>Tourist Destinations</p>
                    </div>
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="25">25</span>+</h3>
                        <p>Unique Locations</p>
                    </div>
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="1000">1000</span>+</h3>
                        <p>Happy Visitors</p>
                    </div>
                </div>
            </div>
            <div class="hero-scroll-indicator">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>

        <!-- Tourism Categories Section -->
        <section class="tourism-categories" id="categories">
            <div class="categories-container">
                <div class="section-header fade-in">
                    <span class="section-label">Tourism Categories</span>
                    <h2>Discover Papua's <b>Wonders</b></h2>
                    <p>Choose your adventure based on what interests you most about Papua's natural and cultural treasures</p>
                </div>
                
                <div class="categories-grid fade-in">
                    <div class="category-card culture">
                        <div class="category-overlay"></div>
                        <div class="category-content">
                            <i class="fas fa-theater-masks"></i>
                            <h3>Cultural Heritage</h3>
                            <p>Traditional ceremonies and local wisdom</p>
                            <span class="category-count"><?php echo count(array_filter($wisata_data, function($w) { return $w['kategori'] == 'budaya'; })); ?> Destinations</span>
                        </div>
                    </div>
                    
                    <div class="category-card nature">
                        <div class="category-overlay"></div>
                        <div class="category-content">
                            <i class="fas fa-leaf"></i>
                            <h3>Natural Wonders</h3>
                            <p>Pristine forests and stunning landscapes</p>
                            <span class="category-count">
                                <?php echo count(array_filter($wisata_data, function($w) { return $w['kategori'] == 'alam'; })); ?> Destinations
                            </span>
                        </div>
                    </div>
                    
                    <div class="category-card adventure">
                        <div class="category-overlay"></div>
                        <div class="category-content">
                            <i class="fas fa-mountain"></i>
                            <h3>Adventure Tours</h3>
                            <p>Thrilling outdoor experiences</p>
                            <span class="category-count"><?php echo count($wisata_data); ?> Total Destinations</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tourism Highlights Section -->
        <section class="tourism-highlights" id="highlights">
            <div class="highlights-container">
                <div class="section-header fade-in">
                    <span class="section-label">Papua Highlights</span>
                    <h2>Why Choose <b>Papua</b></h2>
                </div>
                
                <div class="highlights-grid fade-in">
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-water"></i>
                        </div>
                        <h3>Crystal Clear Waters</h3>
                        <p>Experience some of the world's clearest waters with incredible marine biodiversity</p>
                    </div>
                    
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-tree"></i>
                        </div>
                        <h3>Pristine Rainforests</h3>
                        <p>Explore untouched tropical rainforests with unique flora and fauna</p>
                    </div>
                    
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Rich Culture</h3>
                        <p>Meet friendly local communities and learn about ancient traditions</p>
                    </div>
                    
                    <div class="highlight-item">
                        <div class="highlight-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <h3>Unforgettable Views</h3>
                        <p>Capture breathtaking landscapes that exist nowhere else on Earth</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Tourism Section -->
        <section class="tourism-section" id="tourism">
            <div class="container">
                <!-- Filter Section -->
                <div class="filters-section">
                    <div class="filters-header">
                        <h3>Find Your Perfect Adventure</h3>
                        <p>Discover amazing destinations based on your interests</p>
                    </div>
                    
                    <form method="GET" class="filters-form">
                        <div class="filters-row">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search destinations, activities..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="filter-select">
                                <select name="kategori" onchange="this.form.submit()">
                                    <option value="">🌟 All Categories</option>
                                    <option value="budaya" <?php echo $kategori_filter == 'budaya' ? 'selected' : ''; ?>>🎭 Cultural</option>
                                    <option value="alam" <?php echo $kategori_filter == 'alam' ? 'selected' : ''; ?>>🌿 Nature</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i>
                                Filter
                            </button>
                        </div>
                        
                        <?php if (!empty($kategori_filter) || !empty($search)): ?>
                            <div class="active-filters">
                                <span class="filter-label">Active filters:</span>
                                <?php if (!empty($search)): ?>
                                    <span class="filter-tag">
                                        Search: "<?php echo htmlspecialchars($search); ?>"
                                        <a href="?kategori=<?php echo $kategori_filter; ?>" class="remove-filter">×</a>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($kategori_filter)): ?>
                                    <span class="filter-tag">
                                        Category: <?php echo ucfirst($kategori_filter); ?>
                                        <a href="?search=<?php echo urlencode($search); ?>" class="remove-filter">×</a>
                                    </span>
                                <?php endif; ?>
                                <a href="userwisata.php" class="clear-all-filters">Clear all</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Results Section -->
                <div class="results-section">
                    <?php if (!empty($wisata_data)): ?>
                        <div class="results-header">
                            <h4><?php echo count($wisata_data); ?> destinations found</h4>
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
                            <?php foreach ($wisata_data as $wisata): ?>
                                <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $wisata['id']; ?>'">
                                    <div class="article-image">
                                        <?php if ($wisata['photo']): ?>
                                            <img src="../../uploads/<?php echo htmlspecialchars($wisata['photo']); ?>" 
                                                 alt="<?php echo htmlspecialchars($wisata['judul']); ?>"
                                                 loading="lazy">
                                        <?php else: ?>
                                            <div class="placeholder-image">
                                                🏝️
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-category category-<?php echo $wisata['kategori']; ?>">
                                            <?php
                                            $kategori_icons = [
                                                'budaya' => '🎭 Cultural',
                                                'alam' => '🌿 Nature'
                                            ];
                                            echo $kategori_icons[$wisata['kategori']] ?? ucfirst($wisata['kategori']);
                                            ?>
                                        </div>
                                        <div class="card-favorite">
                                            <i class="far fa-heart"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="article-card-content">
                                        <h4 class="article-card-title"><?php echo htmlspecialchars($wisata['judul']); ?></h4>
                                        <div class="card-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($wisata['alamat']); ?>
                                        </div>
                                        <div class="card-hours">
                                            <i class="fas fa-clock"></i>
                                            <?php echo htmlspecialchars($wisata['jam_buka']); ?>
                                        </div>
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
                                                <span class="review-count">(<?php echo $wisata['review_count']; ?> reviews)</span>
                                            <?php else: ?>
                                                <span class="no-reviews">No reviews yet</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="card-actions">
                                            <a href="?view=detail&id=<?php echo $wisata['id']; ?>" class="btn-detail">
                                                <i class="fas fa-eye"></i>
                                                Explore
                                            </a>
                                            <span class="card-date">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?php echo formatDate($wisata['created_at']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-results">
                            <div class="no-results-icon">🏝️</div>
                            <h3>No Destinations Found</h3>
                            <p>Sorry, no destinations match your search criteria.</p>
                            <p>Try adjusting your filters or search terms.</p>
                            <a href="userwisata.php" class="btn btn-primary">
                                <i class="fas fa-refresh"></i>
                                View All Destinations
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
    <?php elseif ($view_mode === 'detail' && $wisata_detail): ?>
        <!-- Detail View -->
        <div style="padding-top: 0;">
            <div class="container">
                <a href="?" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Destinations
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
                        <div class="notification-message">Successfully Added!</div>
                        <div class="notification-submessage">Item has been added to cart</div>
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
                                'budaya' => '🎭 Cultural',
                                'alam' => '🌿 Nature'
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
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo formatDate($wisata_detail['created_at']); ?>
                            </div>
                        </div>
                        
                        <div class="article-description">
                            <?php echo nl2br(htmlspecialchars($wisata_detail['deskripsi'])); ?>
                        </div>
                        
                        <div class="wisata-info-section">
                            <h3><i class="fas fa-info-circle"></i> Destination Information</h3>
                            <div class="wisata-info-grid">
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <strong>Address</strong>
                                        <p><?php echo htmlspecialchars($wisata_detail['alamat']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <strong>Opening Hours</strong>
                                        <p><?php echo htmlspecialchars($wisata_detail['jam_buka']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-ticket-alt"></i>
                                    <div>
                                        <strong>Ticket Price</strong>
                                        <p><?php echo formatPrice($wisata_detail['harga']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-tag"></i>
                                    <div>
                                        <strong>Category</strong>
                                        <p><?php echo ucfirst($wisata_detail['kategori']); ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Enhanced Booking Form -->
                            <div class="booking-section">
                                <h3><i class="fas fa-ticket-alt"></i> Book Your Tickets</h3>
                                <form id="add-to-cart-form" class="booking-form">
                                    <input type="hidden" name="item_type" value="wisata">
                                    <input type="hidden" name="item_id" value="<?php echo $wisata_detail['id']; ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="jumlah_tiket">
                                                <i class="fas fa-ticket-alt"></i>
                                                Number of Tickets:
                                            </label>
                                            <input type="number" name="quantity" id="jumlah_tiket" min="1" max="10" value="1" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="tanggal_kunjungan">
                                                <i class="fas fa-calendar-alt"></i>
                                                Visit Date:
                                            </label>
                                            <input type="date" name="booking_date" id="tanggal_kunjungan" 
                                                   min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="visitors">
                                                <i class="fas fa-users"></i>
                                                Number of Visitors:
                                            </label>
                                            <select id="visitors" name="visitors">
                                                <option value="1">1 Person</option>
                                                <option value="2">2 People</option>
                                                <option value="3">3 People</option>
                                                <option value="4">4 People</option>
                                                <option value="5+">5+ People</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="visit_time">
                                                <i class="fas fa-clock"></i>
                                                Preferred Time:
                                            </label>
                                            <select id="visit_time" name="visit_time">
                                                <option value="morning">Morning (08:00 - 12:00)</option>
                                                <option value="afternoon">Afternoon (12:00 - 16:00)</option>
                                                <option value="evening">Evening (16:00 - 18:00)</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="catatan">
                                            <i class="fas fa-comment"></i>
                                            Special Requests (Optional):
                                        </label>
                                        <textarea name="notes" id="catatan" rows="3" 
                                                  placeholder="Any special requests for your visit..."></textarea>
                                    </div>
                                    
                                    <div class="booking-summary">
                                        <div class="summary-row">
                                            <span>Price per Ticket:</span>
                                            <strong><?php echo formatPrice($wisata_detail['harga']); ?></strong>
                                        </div>
                                        <div class="summary-row total-row">
                                            <span>Total Estimate:</span>
                                            <strong id="total-price"><?php echo formatPrice($wisata_detail['harga']); ?></strong>
                                        </div>
                                    </div>
                                    
                                    <?php if ($is_logged_in): ?>
                                        <button type="button" onclick="addToCart()" class="btn btn-primary btn-book">
                                            <i class="fas fa-shopping-cart"></i>
                                            Add to Cart
                                        </button>
                                    <?php else: ?>
                                        <a href="../../login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn btn-primary btn-book" style="text-decoration: none; display: inline-block; text-align: center;">
                                            <i class="fas fa-lock"></i>
                                            Login to Book
                                        </a>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Reviews Section -->
                        <div class="reviews-section" id="reviews-section">
                            <div class="reviews-card">
                                <div class="reviews-header">
                                    <h3 class="reviews-title">⭐ Reviews & Ratings</h3>
                                    <?php if ($is_logged_in): ?>
                                        <a href="../account/my_orders.php?tab=paid" class="btn btn-primary" style="text-decoration: none; background: #3498db; color: white; padding: 8px 16px; border-radius: 8px; font-size: 14px;">
                                            ✍️ Write Review
                                        </a>
                                    <?php else: ?>
                                        <a href="../../login.php" class="btn btn-primary" style="text-decoration: none; background: #3498db; color: white; padding: 8px 16px; border-radius: 8px; font-size: 14px;">
                                            🔐 Login to Review
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
                                    <label for="sortReviews" style="font-weight: 600;">Sort by:</label>
                                    <select id="sortReviews" onchange="loadReviews(1)" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 8px;">
                                        <option value="newest">Newest</option>
                                        <option value="oldest">Oldest</option>
                                        <option value="highest">Highest Rating</option>
                                        <option value="lowest">Lowest Rating</option>
                                        <option value="helpful">Most Helpful</option>
                                    </select>
                                </div>
                                
                                <!-- Reviews List -->
                                <div class="reviews-list" id="reviewsList">
                                    <!-- Reviews will be loaded here via AJAX -->
                                </div>
                                
                                <!-- Load More Button -->
                                <div class="load-more-reviews">
                                    <button class="btn-load-more" id="loadMoreReviews" style="display: none;" onclick="loadMoreReviews()">
                                        Load More Reviews
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Related Destinations -->
                <?php if (count($related_wisata) > 0): ?>
                <div class="related-section">
                    <div class="section-header">
                        <h3><i class="fas fa-star"></i> Similar Destinations</h3>
                        <p>Explore other <?php echo $wisata_detail['kategori']; ?> destinations you might enjoy</p>
                    </div>
                    
                    <div class="articles-grid">
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
                                            'budaya' => '🎭 Cultural',
                                            'alam' => '🌿 Nature'
                                        ];
                                        echo $kategori_icons[$related['kategori']] ?? ucfirst($related['kategori']);
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="article-card-content">
                                    <h4 class="article-card-title"><?php echo htmlspecialchars($related['judul']); ?></h4>
                                    <div class="card-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($related['alamat']); ?>
                                    </div>
                                    <div class="article-card-price"><?php echo formatPrice($related['harga']); ?></div>
                                    
                                    <div class="card-description">
                                        <?php echo truncateText(htmlspecialchars($related['deskripsi']), 80); ?>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <a href="?view=detail&id=<?php echo $related['id']; ?>" class="btn-detail">
                                            <i class="fas fa-eye"></i>
                                            Explore
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
        <!-- 404 Not Found -->
        <div class="no-results">
            <div class="no-results-icon">🏝️</div>
            <h3>Destination Not Found</h3>
            <p>Sorry, the destination you're looking for cannot be found.</p>
            <a href="?" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Back to Destinations
            </a>
        </div>
    <?php endif; ?>

    <!-- Scroll to Top Button -->
    <button class="scroll-to-top" onclick="window.scrollTo({top: 0, behavior: 'smooth'})" aria-label="Scroll to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#tourism">Destinations</a></li>
                    <li><a href="#categories">Categories</a></li>
                    <li><a href="../dashboard/user_dashboard.php">Dashboard</a></li>
                    <li><a href="../penginapan/userpenginapan.php">Accommodation</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Contact Us</h3>
                <p>Email: <a href="mailto:explore@papuajourney.com">explore@papuajourney.com</a></p>
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
            <p>&copy; 2025 Papua Journey - Tourism. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
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

        // Filter by category function
        function filterByCategory(category) {
            window.location.href = '?kategori=' + category;
        }

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
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            
            fetch('../cart/add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                
                if (data.success) {
                    showNotification();
                    
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
                
                setTimeout(() => {
                    overlay.classList.remove('show');
                }, 2000);
            }
        }

        // Favorite toggle function
        document.querySelectorAll('.card-favorite').forEach(favoriteBtn => {
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

        // View toggle functionality
        document.querySelectorAll('.view-btn').forEach(btn => {
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

        // Auto-submit search form
        document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
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

        // Loading animation for card clicks
        document.querySelectorAll('.article-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.opacity = '0.7';
                this.style.transform = 'scale(0.98)';
            });
        });

        // Load reviews when page loads (if on detail page)
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
            
            console.log('Loading reviews for wisata ID:', <?php echo $wisata_detail['id'] ?? 0; ?>);
            
            fetch(`../reviews/get_reviews.php?item_type=wisata&item_id=<?php echo $wisata_detail['id'] ?? 0; ?>&page=${page}&sort_by=${currentSort}`)
                .then(response => response.json())
                .then(data => {
                    console.log('Review data received:', data);
                    if (data.success) {
                        updateReviewSummary(data.summary);
                        
                        const reviewsList = document.getElementById('reviewsList');
                        if (!append) {
                            reviewsList.innerHTML = '';
                        }
                        
                        if (data.reviews.length === 0 && page === 1) {
                            reviewsList.innerHTML = '<p style="text-align: center; color: #666; padding: 40px;">No reviews for this destination yet. Be the first to leave a review!</p>';
                        } else {
                            data.reviews.forEach(review => {
                                reviewsList.appendChild(createReviewElement(review));
                            });
                        }
                        
                        document.getElementById('loadMoreReviews').style.display = data.pagination.has_next ? 'block' : 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading reviews:', error);
                    document.getElementById('reviewsList').innerHTML = '<p style="text-align: center; color: red; padding: 20px;">Error loading reviews. Check console for details.</p>';
                });
        }
        
        function loadMoreReviews() {
            loadReviews(currentPage + 1, true);
        }
        
        function updateReviewSummary(summary) {
            document.getElementById('averageRating').textContent = summary.average_rating.toFixed(1);
            document.getElementById('totalReviews').textContent = `${summary.total_reviews} reviews`;
            
            const stars = Math.round(summary.average_rating);
            document.getElementById('averageStars').textContent = '★'.repeat(stars) + '☆'.repeat(5 - stars);
            
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
                        Was this review helpful?
                        <button class="helpful-btn ${review.user_vote === '1' ? 'voted' : ''}" 
                                onclick="voteHelpful(${review.id}, true)" 
                                ${!<?php echo $is_logged_in ? 'true' : 'false'; ?> ? 'disabled title="Login to vote"' : ''}>
                            <i class="fas fa-thumbs-up"></i> 
                            <span>${review.helpful_count}</span>
                        </button>
                        <button class="helpful-btn ${review.user_vote === '0' ? 'voted' : ''}" 
                                onclick="voteHelpful(${review.id}, false)"
                                ${!<?php echo $is_logged_in ? 'true' : 'false'; ?> ? 'disabled title="Login to vote"' : ''}>
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
            alert('Please login to vote');
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
                    loadReviews(currentPage);
                }
            } catch (error) {
                console.error('Error voting:', error);
            }
        }

        // Track wisata view function
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

        // Track views for detail page
        <?php if ($view_mode === 'detail' && $wisata_detail): ?>
        document.addEventListener('DOMContentLoaded', function() {
            trackWisataView(<?php echo $wisata_id; ?>);
        });
        <?php endif; ?>
    </script>
</body>
</html>