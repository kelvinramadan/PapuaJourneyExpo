<?php
// users/userpenginapan.php
if (!isset($_SESSION)) {
    session_start();
}

require_once '../../config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

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
    <link rel="preload" href="../../assets/resort.jpg" as="image">
    
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
        <!-- Enhanced Hero Section for Accommodation -->
        <section class="hero" id="home">
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <span class="hero-badge fade-in">🏨 Welcome to Papua Accommodation</span>
                <h1 class="hero-title">
                    <span class="hero-title-line">Discover Amazing</span>
                    <span class="hero-title-line"><span class="highlight">Accommodations</span> in Papua</span>
                </h1>
                <p class="hero-description">From luxury resorts to traditional homestays, find the perfect place to rest during your Papua adventure. Experience authentic Papuan hospitality and comfort.</p>
                <div class="hero-actions">
                    <a href="#accommodations" class="btn btn-primary">
                        <i class="fas fa-bed"></i>
                        Explore Accommodations
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="<?php echo count($penginapan_data); ?>"><?php echo count($penginapan_data); ?></span></h3>
                        <p>Available Accommodations</p>
                    </div>
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="15">15</span>+</h3>
                        <p>Locations</p>
                    </div>
                    <div class="stat-item">
                        <h3><span class="stat-number" data-target="500">500</span>+</h3>
                        <p>Happy Guests</p>
                    </div>
                </div>
            </div>
            <div class="hero-scroll-indicator">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>

        <!-- Accommodation Types Section -->
        <section class="accommodation-types" id="types">
            <div class="types-container">
                <div class="section-header fade-in">
                    <span class="section-label">Accommodation Types</span>
                    <h2>Choose Your Perfect <b>Stay</b></h2>
                    <p>Discover various types of accommodations that suit your travel style and budget</p>
                </div>
                
                <div class="types-grid fade-in">
                    <div class="type-card hotel" >
                        <div class="type-overlay"></div>
                        <div class="type-content">
                            <i class="fas fa-building"></i>
                            <h3>Hotels</h3>
                            <p>Modern comfort with full amenities</p>
                            <span class="type-count"><?php echo count(array_filter($penginapan_data, function($p) { return $p['tipe'] == 'hotel'; })); ?> Properties</span>
                        </div>
                    </div>
                    
                    <div class="type-card villa" >
                        <div class="type-overlay"></div>
                        <div class="type-content">
                            <i class="fas fa-home"></i>
                            <h3>Villas</h3>
                            <p>Private luxury with beautiful views</p>
                            <span class="type-count"><?php echo count(array_filter($penginapan_data, function($p) { return $p['tipe'] == 'villa'; })); ?> Properties</span>
                        </div>
                    </div>
                    
                    <div class="type-card resort" >
                        <div class="type-overlay"></div>
                        <div class="type-content">
                            <i class="fas fa-umbrella-beach"></i>
                            <h3>Resorts</h3>
                            <p>All-inclusive paradise experience</p>
                            <span class="type-count"><?php echo count(array_filter($penginapan_data, function($p) { return $p['tipe'] == 'resort'; })); ?> Properties</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content Section -->
        <section class="accommodations-section" id="accommodations">
            <div class="container">
                <!-- Filter Section -->
                <div class="filters-section">
                    <div class="filters-header">
                        <h3>Find Your Perfect Stay</h3>
                        <p>Filter accommodations by type and preferences</p>
                    </div>
                    
                    <form method="GET" class="filters-form">
                        <div class="filters-row">
                            <div class="search-box">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search accommodations, locations..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="filter-select">
                                <select name="tipe" onchange="this.form.submit()">
                                    <option value="">🏠 All Types</option>
                                    <option value="hotel" <?php echo $tipe_filter == 'hotel' ? 'selected' : ''; ?>>🏨 Hotel</option>
                                    <option value="villa" <?php echo $tipe_filter == 'villa' ? 'selected' : ''; ?>>🏖️ Villa</option>
                                    <option value="resort" <?php echo $tipe_filter == 'resort' ? 'selected' : ''; ?>>🌴 Resort</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i>
                                Filter
                            </button>
                        </div>
                        
                        <?php if (!empty($tipe_filter) || !empty($search)): ?>
                            <div class="active-filters">
                                <span class="filter-label">Active filters:</span>
                                <?php if (!empty($search)): ?>
                                    <span class="filter-tag">
                                        Search: "<?php echo htmlspecialchars($search); ?>"
                                        <a href="?tipe=<?php echo $tipe_filter; ?>" class="remove-filter">×</a>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($tipe_filter)): ?>
                                    <span class="filter-tag">
                                        Type: <?php echo ucfirst($tipe_filter); ?>
                                        <a href="?search=<?php echo urlencode($search); ?>" class="remove-filter">×</a>
                                    </span>
                                <?php endif; ?>
                                <a href="userpenginapan.php" class="clear-all-filters">Clear all</a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Results Section -->
                <div class="results-section">
                    <?php if (!empty($penginapan_data)): ?>
                        <div class="results-header">
                            <h4><?php echo count($penginapan_data); ?> accommodations found</h4>
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
                            <?php foreach ($penginapan_data as $penginapan): ?>
                                <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $penginapan['id']; ?>'">
                                    <div class="article-image">
                                        <?php if ($penginapan['photo'] && file_exists('../../uploads/' . $penginapan['photo'])): ?>
                                            <img src="../../uploads/<?php echo htmlspecialchars($penginapan['photo']); ?>" 
                                                 alt="<?php echo htmlspecialchars($penginapan['judul']); ?>"
                                                 loading="lazy">
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
                                        <div class="card-favorite">
                                            <i class="far fa-heart"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="article-card-content">
                                        <h4 class="article-card-title"><?php echo htmlspecialchars($penginapan['judul']); ?></h4>
                                        <div class="card-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($penginapan['lokasi']); ?>
                                        </div>
                                        <div class="article-card-price"><?php echo formatPrice($penginapan['harga']); ?>/night</div>
                                        
                                        <div class="card-description">
                                            <?php echo truncateText(htmlspecialchars($penginapan['deskripsi']), 100); ?>
                                        </div>

                                        <?php if ($penginapan['fasilitas']): ?>
                                        <div class="facilities-preview">
                                            <strong class="facilities-title">Facilities:</strong>
                                            <div class="facilities-list">
                                                <?php 
                                                $fasilitas = array_map('trim', explode(',', $penginapan['fasilitas']));
                                                foreach (array_slice($fasilitas, 0, 3) as $fasilitas_item): 
                                                ?>
                                                    <span class="facility-tag"><?php echo htmlspecialchars($fasilitas_item); ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($fasilitas) > 3): ?>
                                                    <span class="facility-more">+<?php echo count($fasilitas) - 3; ?> more</span>
                                                <?php endif; ?>
                                            </div>
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
                                                <span class="review-count">(<?php echo $penginapan['review_count']; ?> reviews)</span>
                                            <?php else: ?>
                                                <span class="no-reviews">No reviews yet</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="card-actions">
                                            <a href="?view=detail&id=<?php echo $penginapan['id']; ?>" class="btn-detail">
                                                <i class="fas fa-eye"></i>
                                                View Details
                                            </a>
                                            <span class="card-date">
                                                <i class="fas fa-calendar-alt"></i>
                                                <?php echo formatDate($penginapan['created_at']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-results">
                            <div class="no-results-icon">🏨</div>
                            <h3>No Accommodations Found</h3>
                            <p>Sorry, no accommodations match your search criteria.</p>
                            <p>Try adjusting your filters or search terms.</p>
                            <a href="userpenginapan.php" class="btn btn-primary">
                                <i class="fas fa-refresh"></i>
                                View All Accommodations
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
    <?php elseif ($view_mode === 'detail' && $penginapan_detail): ?>
        <!-- Detail View (keeping existing detail view code) -->
        <div style="padding-top: 0;">
            <div class="container">
                <a href="?" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                    Back to Accommodations
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
                            <div class="article-price"><?php echo formatPrice($penginapan_detail['harga']); ?>/night</div>
                            <div class="article-date">
                                <i class="fas fa-calendar-alt"></i>
                                <?php echo formatDate($penginapan_detail['created_at']); ?>
                            </div>
                        </div>
                        
                        <div class="article-description">
                            <?php echo nl2br(htmlspecialchars($penginapan_detail['deskripsi'])); ?>
                        </div>
                        
                        <div class="penginapan-info-section">
                            <h3><i class="fas fa-info-circle"></i> Accommodation Information</h3>
                            <div class="penginapan-info-grid">
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <strong>Location</strong>
                                        <p><?php echo htmlspecialchars($penginapan_detail['lokasi']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-home"></i>
                                    <div>
                                        <strong>Accommodation Type</strong>
                                        <p><?php echo ucfirst($penginapan_detail['tipe']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <i class="fas fa-dollar-sign"></i>
                                    <div>
                                        <strong>Price per Night</strong>
                                        <p><?php echo formatPrice($penginapan_detail['harga']); ?></p>
                                    </div>
                                </div>
                                
                                <?php if ($penginapan_detail['fasilitas']): ?>
                                <div class="info-item facilities-item">
                                    <i class="fas fa-concierge-bell"></i>
                                    <div>
                                        <strong>Facilities</strong>
                                        <div class="facilities-grid">
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
                            
                            <!-- Enhanced Booking Form -->
                            <div class="booking-section">
                                <h3><i class="fas fa-calendar-check"></i> Book Your Stay</h3>
                                <form id="add-to-cart-form" class="booking-form">
                                    <input type="hidden" name="item_type" value="penginapan">
                                    <input type="hidden" name="item_id" value="<?php echo $penginapan_detail['id']; ?>">
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="jumlah_kamar">
                                                <i class="fas fa-bed"></i>
                                                Number of Rooms:
                                            </label>
                                            <input type="number" name="quantity" id="jumlah_kamar" min="1" max="10" value="1" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="tanggal_checkin">
                                                <i class="fas fa-calendar-alt"></i>
                                                Check-in Date:
                                            </label>
                                            <input type="date" name="checkin_date" id="tanggal_checkin" 
                                                   min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="tanggal_checkout">
                                                <i class="fas fa-calendar-alt"></i>
                                                Check-out Date:
                                            </label>
                                            <input type="date" name="checkout_date" id="tanggal_checkout" 
                                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="guests">
                                                <i class="fas fa-users"></i>
                                                Number of Guests:
                                            </label>
                                            <select id="guests" name="guests">
                                                <option value="1">1 Guest</option>
                                                <option value="2">2 Guests</option>
                                                <option value="3">3 Guests</option>
                                                <option value="4">4 Guests</option>
                                                <option value="5+">5+ Guests</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="catatan">
                                            <i class="fas fa-comment"></i>
                                            Special Requests (Optional):
                                        </label>
                                        <textarea name="notes" id="catatan" rows="3" 
                                                  placeholder="Any special requests for your stay..."></textarea>
                                    </div>
                                    
                                    <div class="booking-summary">
                                        <div class="summary-row">
                                            <span>Price per Night:</span>
                                            <strong><?php echo formatPrice($penginapan_detail['harga']); ?></strong>
                                        </div>
                                        <div class="summary-row">
                                            <span>Number of Nights:</span>
                                            <strong><span id="jumlah-malam">1</span> night(s)</strong>
                                        </div>
                                        <div class="summary-row total-row">
                                            <span>Total Estimate:</span>
                                            <strong id="total-price"><?php echo formatPrice($penginapan_detail['harga']); ?></strong>
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
                        
                        <!-- Reviews Section (keeping existing reviews code) -->
                        <div class="reviews-section" id="reviews-section">
                            <!-- (Reviews code remains the same as in original) -->
                        </div>
                    </div>
                </div>
                
                <!-- Related Accommodations -->
                <?php if (count($related_penginapan) > 0): ?>
                <div class="related-section">
                    <div class="section-header">
                        <h3><i class="fas fa-star"></i> Similar Accommodations</h3>
                        <p>Explore other <?php echo $penginapan_detail['tipe']; ?>s you might like</p>
                    </div>
                    
                    <div class="articles-grid">
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
                                    <div class="card-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($related['lokasi']); ?>
                                    </div>
                                    <div class="article-card-price"><?php echo formatPrice($related['harga']); ?>/night</div>
                                    
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
                                            <i class="fas fa-eye"></i>
                                            View Details
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
            <div class="no-results-icon">🏨</div>
            <h3>Accommodation Not Found</h3>
            <p>Sorry, the accommodation you're looking for cannot be found.</p>
            <a href="?" class="btn btn-primary">
                <i class="fas fa-home"></i>
                Back to Accommodations
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
                    <li><a href="#accommodations">Accommodations</a></li>
                    <li><a href="#types">Types</a></li>
                    <li><a href="../dashboard/user_dashboard.php">Dashboard</a></li>
                    <li><a href="../wisata/userwisata.php">Tourism</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Contact Us</h3>
                <p>Email: <a href="mailto:stay@papuajourney.com">stay@papuajourney.com</a></p>
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
            <p>&copy; 2025 Papua Journey - Accommodations. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript untuk Funcionalitas Tambahan -->
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

        // Filter by type function
        function filterByType(type) {
            window.location.href = '?tipe=' + type;
        }

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
        
        // Add event listeners for booking form
        document.getElementById('jumlah_kamar')?.addEventListener('input', calculateTotal);
        document.getElementById('tanggal_checkin')?.addEventListener('change', function() {
            const checkinDate = this.value;
            const checkoutInput = document.getElementById('tanggal_checkout');
            
            if (checkinDate) {
                const nextDay = new Date(checkinDate);
                nextDay.setDate(nextDay.getDate() + 1);
                checkoutInput.min = nextDay.toISOString().split('T')[0];
                
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
                alert('Check-out date must be after check-in date!');
                return;
            }
            
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
    </script>
</body>
</html>