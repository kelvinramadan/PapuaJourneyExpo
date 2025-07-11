<?php
//user_dashboard.php
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

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating']);
    $review_text = trim($_POST['review_text']);
    $destination = trim($_POST['destination']);
    $visit_date = $_POST['visit_date'];
    
    // Validation
    if ($rating >= 1 && $rating <= 5 && !empty($review_text) && !empty($destination)) {
        $db = getDbConnection();
        $stmt = $db->prepare("INSERT INTO reviewuser (user_id, rating, review_text, destination, visit_date, is_approved) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("iisss", $user_id, $rating, $review_text, $destination, $visit_date);
        
        if ($stmt->execute()) {
            $message = "Review berhasil ditambahkan!";
        } else {
            $error_message = "Gagal menambahkan review.";
        }
        $stmt->close();
        $db->close();
    } else {
        $error_message = "Mohon lengkapi semua field dengan benar.";
    }
}

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

// Get approved reviews for testimonials
$reviews_query = "SELECT r.*, u.full_name 
                  FROM reviewuser r 
                  JOIN users u ON r.user_id = u.id 
                  WHERE r.is_approved = 1 
                  ORDER BY r.created_at DESC";
$reviews_result = $db->query($reviews_query);
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);

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

function getInitial($name) {
    $words = explode(' ', $name);
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
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
    <style>
        /* CSS tambahan untuk review functionality */
        .review-actions {
            margin-top: 2rem;
            text-align: center;
        }

        .testimonials-slider-wrapper {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 60px;
        }

        .testimonials-slider {
            display: flex;
            gap: 2rem;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            padding: 2rem 0 3rem;
            -webkit-overflow-scrolling: touch;
        }

        .testimonials-slider::-webkit-scrollbar {
            height: 8px;
        }

        .testimonials-slider::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .testimonials-slider::-webkit-scrollbar-thumb {
            background: var(--button-color);
            border-radius: 10px;
        }

        .testimonial-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 10;
        }

        .testimonial-nav:hover {
            background: var(--button-color);
            color: white;
            transform: translateY(-50%) scale(1.1);
        }

        .testimonial-nav-prev {
            left: 10px;
        }

        .testimonial-nav-next {
            right: 10px;
        }

        .testimonial-card {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            transition: all 0.3s ease;
            min-width: 320px;
            max-width: 320px;
            scroll-snap-align: start;
            flex-shrink: 0;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .testimonial-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(220, 155, 17, 0.05), transparent);
            transition: left 0.5s;
        }

        .testimonial-card:hover::before {
            left: 100%;
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
            border-color: var(--button-color);
        }

        .testimonial-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .testimonial-rating {
            display: flex;
            gap: 2px;
        }

        .testimonial-rating i {
            color: #ddd;
            font-size: 1rem;
            transition: color 0.2s ease;
        }

        .testimonial-rating i.filled {
            color: var(--button-color);
        }

        .testimonial-destination {
            font-size: 0.85rem;
            color: var(--button-color);
            font-weight: 600;
            background: rgba(220, 155, 17, 0.1);
            padding: 4px 12px;
            border-radius: 15px;
        }

        .testimonial-text {
            color: #555;
            font-style: italic;
            margin-bottom: 1.5rem;
            line-height: 1.6;
            font-size: 0.95rem;
            position: relative;
            z-index: 2;
        }

        .testimonial-text::before {
            content: '"';
            font-size: 3rem;
            color: var(--button-color);
            opacity: 0.3;
            position: absolute;
            top: -10px;
            left: -10px;
            font-family: serif;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
            z-index: 2;
        }

        .testimonial-author .avatar-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            font-weight: 600;
            flex-shrink: 0;
            border: 3px solid white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .author-info h4 {
            margin: 0;
            color: var(--text-color-secondary);
            font-size: 1rem;
            font-weight: 600;
        }

        .author-info span {
            color: #999;
            font-size: 0.85rem;
        }

        .no-reviews {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        .no-reviews i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ddd;
        }

        .no-reviews h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #666;
        }

        .review-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease;
            padding: 20px;
            box-sizing: border-box;
        }

        .review-modal.show {
            opacity: 1;
        }

        .review-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            cursor: pointer;
        }

        .review-modal-content {
            background: white;
            border-radius: 20px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            transform: scale(0.8);
            transition: transform 0.3s ease;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
            z-index: 10001;
        }

        .review-modal.show .review-modal-content {
            transform: scale(1);
        }

        .review-modal-header {
            padding: 2rem 2rem 1rem;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            border-radius: 20px 20px 0 0;
        }

        .review-modal-header h2 {
            color: var(--text-color-secondary);
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
        }

        .review-modal-body {
            padding: 2rem;
        }

        .review-form .form-group {
            margin-bottom: 1.5rem;
        }

        .review-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color-secondary);
        }

        .review-form input,
        .review-form textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
            box-sizing: border-box;
        }

        .review-form input:focus,
        .review-form textarea:focus {
            outline: none;
            border-color: var(--button-color);
            box-shadow: 0 0 0 3px rgba(220, 155, 17, 0.1);
        }

        .review-form textarea {
            resize: vertical;
            min-height: 120px;
        }

        .rating-input {
            display: flex;
            flex-direction: row-reverse;
            gap: 5px;
            justify-content: flex-end;
        }

        .rating-input input[type="radio"] {
            display: none;
        }

        .rating-input label {
            cursor: pointer;
            font-size: 2rem;
            color: #ddd;
            transition: all 0.2s ease;
            margin: 0;
        }

        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: var(--button-color);
            transform: scale(1.1);
        }

        .rating-input input[type="radio"]:checked ~ label {
            color: var(--button-color);
        }

        .review-modal-footer {
            padding: 1rem 2rem 2rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            position: sticky;
            bottom: 0;
            background: white;
            border-radius: 0 0 20px 20px;
        }

        .review-modal-footer .btn {
            padding: 12px 24px;
            font-size: 1rem;
        }

        .review-modal-footer .btn-secondary {
            background: #f5f5f5;
            color: #666;
            border: 2px solid #e0e0e0;
        }

        .review-modal-footer .btn-secondary:hover {
            background: #e8e8e8;
            color: #333;
        }

        @media (max-width: 768px) {
            .testimonials-slider-wrapper {
                padding: 0 20px;
            }
            
            .testimonial-nav {
                width: 40px;
                height: 40px;
            }
            
            .testimonial-nav-prev {
                left: 0;
            }
            
            .testimonial-nav-next {
                right: 0;
            }
            
            .testimonial-card {
                min-width: 280px;
                max-width: 280px;
                padding: 1.5rem;
            }
            
            .review-modal-content {
                width: 95%;
                margin: 1rem auto;
                max-height: 95vh;
            }
            
            .review-modal-footer {
                flex-direction: column;
            }
            
            .review-modal-footer .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    
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
                    <!-- Article detail content remains the same -->
                    <!-- ... (keeping all the original article detail code) ... -->
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
                    <div class="interest-card food" data-category="culinary" onclick="navigateToCategory('culinary')">
                        <div class="interest-overlay"></div>
                        <div class="interest-content">
                            <i class="fi fi-rr-hamburger-soda"></i>
                            <h3>Food & Drink</h3>
                            <p>Taste authentic Papuan cuisine</p>
                            <span class="interest-tag">20+ Restaurants</span>
                        </div>
                    </div>
                    <div class="interest-card culture" data-category="cultural" onclick="navigateToCategory('cultural')">
                        <div class="interest-overlay"></div>
                        <div class="interest-content">
                            <i class="fi fi-rr-people"></i>
                            <h3>Culture & Heritage</h3>
                            <p>Experience traditional ceremonies</p>
                            <span class="interest-tag">15+ Villages</span>
                        </div>
                    </div>
                    <div class="interest-card adventures" data-category="marine" onclick="navigateToCategory('marine')">
                        <div class="interest-overlay"></div>
                        <div class="interest-content">
                            <i class="fi fi-rr-dolphin"></i>
                            <h3>Underwater Adventures</h3>
                            <p>Dive in pristine coral reefs</p>
                            <span class="interest-tag">30+ Dive Sites</span>
                        </div>
                    </div>
                    <div class="interest-card tracking" data-category="hiking" onclick="navigateToCategory('hiking')">
                        <div class="interest-overlay"></div>
                        <div class="interest-content">
                            <i class="fi fi-br-mountain"></i>
                            <h3>Trekking Tours</h3>
                            <p>Hike through rainforests</p>
                            <span class="interest-tag">25+ Trails</span>
                        </div>
                    </div>
                    <div class="interest-card wildlife" data-category="wildlife" onclick="navigateToCategory('wildlife')">
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

        <!-- Modified Plan Trip Section with Interactive Cards -->
        <section class="plan-trip" id="plan">
            <div class="plan-container">
                <h2>Plan Your <b>Trip</b></h2>
                <div class="plan-content fade-in">
                    <div class="plan-card" onclick="openPlanModal('before-go')">
                        <i class="fas fa-suitcase-rolling"></i>
                        <h3>Before you go</h3>
                        <p>Get ready for your adventure with essential tips and information.</p>
                        <div class="card-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="plan-card" onclick="openPlanModal('transportation')">
                        <i class="fas fa-car"></i>
                        <h3>Transportation</h3>
                        <p>Navigate Papua with ease using our transportation guides.</p>
                        <div class="card-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="plan-card" onclick="openPlanModal('accommodation')">
                        <i class="fas fa-hotel"></i>
                        <h3>Accommodation</h3>
                        <p>Find the perfect place to stay during your journey.</p>
                        <div class="card-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="plan-card" onclick="openPlanModal('itinerary')">
                        <i class="fas fa-map-marked-alt"></i>
                        <h3>Itinerary Ideas</h3>
                        <p>Explore suggested itineraries for a memorable trip.</p>
                        <div class="card-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="plan-card" onclick="openPlanModal('tour-guide')">
                        <i class="fas fa-user-tie"></i>
                        <h3>Tour guide</h3>
                        <p>Connect with local guides for an authentic experience.</p>
                        <div class="card-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="plan-card" onclick="openPlanModal('etiquette')">
                        <i class="fas fa-hands-helping"></i>
                        <h3>Etiquette</h3>
                        <p>Learn about local customs and etiquette for a respectful visit.</p>
                        <div class="card-arrow">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Plan Modal -->
        <div id="planModal" class="plan-modal">
            <div class="plan-modal-overlay" onclick="closePlanModal()"></div>
            <div class="plan-modal-content">
                <div class="plan-modal-header">
                    <h2 id="modalTitle">Plan Title</h2>
                    <span class="close-modal" onclick="closePlanModal()">&times;</span>
                </div>
                <div class="plan-modal-body">
                    <div class="modal-image-container">
                        <img id="modalImage" src="" alt="" loading="lazy" />
                    </div>
                    <div class="modal-description">
                        <p id="modalDescription">Description will be loaded here...</p>
                    </div>
                </div>
                <div class="plan-modal-footer">
                    <button class="btn btn-primary" onclick="closePlanModal()">
                        <i class="fas fa-check"></i>
                        Understood
                    </button>
                </div>
            </div>
        </div>

        <!-- Enhanced Testimonials Section with User Reviews -->
        <section class="testimonials" id="testimonials">
            <div class="testimonials-container">
                <div class="testimonials-header fade-in">
                    <span class="section-label">What Travelers Say</span>
                    <h2>Real Stories from <b>Real Adventurers</b></h2>
                    <div class="review-actions">
                        <button class="btn btn-primary" onclick="openReviewModal()">
                            <i class="fas fa-plus"></i>
                            Share Your Experience
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($reviews)): ?>
                <div class="testimonials-slider-wrapper">
                    <button class="testimonial-nav testimonial-nav-prev" onclick="slideTestimonials('prev')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div class="testimonials-slider" id="testimonialsSlider">
                        <?php foreach ($reviews as $review): ?>
                        <div class="testimonial-card fade-in">
                            <div class="testimonial-header">
                                <div class="testimonial-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="testimonial-destination"><?php echo htmlspecialchars($review['destination']); ?></div>
                            </div>
                            <p class="testimonial-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                            <div class="testimonial-author">
                                <div class="avatar-icon" style="background-color: <?php echo '#' . substr(md5($review['user_id']), 0, 6); ?>;">
                                    <?php echo getInitial($review['full_name']); ?>
                                </div>
                                <div class="author-info">
                                    <h4><?php echo htmlspecialchars($review['full_name']); ?></h4>
                                    <span>Visited on <?php echo formatDate($review['visit_date']); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button class="testimonial-nav testimonial-nav-next" onclick="slideTestimonials('next')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                <?php else: ?>
                <div class="no-reviews">
                    <i class="fas fa-comment-slash"></i>
                    <h3>No reviews yet</h3>
                    <p>Be the first to share your Papua adventure experience!</p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Review Modal -->
        <div id="reviewModal" class="review-modal">
            <div class="review-modal-overlay" onclick="closeReviewModal()"></div>
            <div class="review-modal-content">
                <div class="review-modal-header">
                    <h2>Share Your Experience</h2>
                    <span class="close-modal" onclick="closeReviewModal()">&times;</span>
                </div>
                <form method="POST" class="review-form">
                    <div class="review-modal-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                        <?php if ($error_message): ?>
                            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="destination">Destination</label>
                            <input type="text" id="destination" name="destination" required placeholder="e.g., Raja Ampat, Wamena, Jayapura">
                        </div>
                        
                        <div class="form-group">
                            <label for="visit_date">Visit Date</label>
                            <input type="date" id="visit_date" name="visit_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="rating">Rating</label>
                            <div class="rating-input">
                                <input type="radio" id="star5" name="rating" value="5" required>
                                <label for="star5"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1"><i class="fas fa-star"></i></label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="review_text">Your Review</label>
                            <textarea id="review_text" name="review_text" required placeholder="Share your experience in Papua..." rows="6"></textarea>
                        </div>
                    </div>
                    <div class="review-modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeReviewModal()">Cancel</button>
                        <button type="submit" name="submit_review" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            Submit Review
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
        // Plan Modal Data with Real Images
        const planData = {
            'before-go': {
                title: '🧳 Before You Go (Sebelum Berangkat)',
                description: 'Sebelum memulai petualangan Anda ke Papua, pastikan Anda telah mempersiapkan segala kebutuhan dengan baik. Bawa pakaian yang sesuai dengan iklim tropis, obat-obatan pribadi, dan perlengkapan untuk aktivitas outdoor. Jangan lupa membawa sunscreen, topi, dan kacamata hitam. Pastikan juga dokumen perjalanan lengkap seperti KTP, tiket pesawat, dan booking hotel. Untuk kegiatan khusus seperti diving atau trekking, persiapkan peralatan yang sesuai atau konfirmasi penyewaan equipment di lokasi.',
                image: '../../assets/before-go.jpeg'
            },
            'transportation': {
                title: '🚗 Transportation',
                description: 'Papua memiliki medan yang unik dan menantang. Transportasi utama antar kota adalah pesawat kecil yang menghubungkan berbagai bandara di Papua. Untuk perjalanan darat, tersedia angkutan umum, ojek, dan rental mobil. Di beberapa daerah, transportasi air seperti speedboat dan kapal tradisional menjadi pilihan utama. Pastikan untuk merencanakan transportasi dengan baik karena cuaca dapat mempengaruhi jadwal penerbangan. Disarankan untuk memiliki rencana alternatif dan buffer waktu yang cukup.',
                image: '../../assets/transportation.jpg'
            },
            'accommodation': {
                title: '🏨 Accommodation',
                description: 'Papua menawarkan berbagai pilihan akomodasi yang sesuai dengan berbagai budget dan preferensi. Mulai dari resort mewah di Raja Ampat, hotel bisnis di Jayapura, hingga homestay tradisional di pedalaman. Untuk pengalaman yang lebih autentik, Anda bisa menginap di rumah-rumah tradisional masyarakat lokal. Pastikan untuk memesan akomodasi jauh-jauh hari, terutama pada musim peak atau saat ada event khusus. Beberapa daerah terpencil mungkin memiliki fasilitas terbatas, jadi sesuaikan ekspektasi dengan kondisi lokal.',
                image: '../../assets/accommodation.jpg'
            },
            'itinerary': {
                title: '🗺️ Itinerary Ideas',
                description: 'Rencanakan perjalanan Anda berdasarkan minat dan durasi kunjungan. Untuk wisata bahari, alokasikan 4-5 hari di Raja Ampat untuk diving dan snorkeling. Wisata budaya bisa dimulai dari Jayapura kemudian ke Wamena untuk bertemu suku Dani. Untuk petualangan alam, jelajahi Taman Nasional Lorentz atau pendakian ke Puncak Jaya Wijaya. Kombinasikan aktivitas sesuai lokasi untuk efisiensi waktu dan biaya. Selalu sertakan waktu istirahat dan fleksibilitas untuk perubahan cuaca atau kondisi tak terduga.',
                image: '../../assets/itinerary.jpg'
            },
            'tour-guide': {
                title: '🎟️ Tour Guide',
                description: 'Pemandu wisata lokal akan memberikan pengalaman yang lebih mendalam dan otentik. Mereka menguasai bahasa daerah, mengetahui jalur terbaik, dan memahami budaya setempat. Pilih guide yang berpengalaman dan memiliki sertifikat resmi. Untuk aktivitas khusus seperti diving, pastikan guide memiliki lisensi yang sesuai. Guide lokal juga dapat membantu komunikasi dengan masyarakat setempat dan memberikan insight tentang kehidupan sehari-hari. Diskusikan itinerary dan ekspektasi sebelum perjalanan dimulai.',
                image: '../../assets/tour-guide.jpg'
            },
            'etiquette': {
                title: '📜 Etiquette',
                description: 'Papua memiliki keberagaman budaya yang sangat kaya dengan adat istiadat yang harus dihormati. Berpakaianlah dengan sopan, terutama saat mengunjungi desa-desa tradisional. Selalu minta izin sebelum mengambil foto orang atau tempat suci. Hormati tradisi dan kepercayaan lokal, jangan sentuh atau ambil benda-benda yang dianggap sakral. Belajarlah beberapa kata dalam bahasa daerah sebagai bentuk penghargaan. Bawa oleh-oleh atau sumbangan kecil saat berkunjung ke desa. Selalu bersikap ramah, sabar, dan terbuka terhadap cara hidup yang berbeda.',
                image: '../../assets/etiquette.jpg'
            }
        };

        // Initialize modal when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializePlanModal();
            initializeReviewModal();
            
            // Close modals on successful submission
            <?php if ($message): ?>
                setTimeout(() => {
                    closeReviewModal();
                }, 2000);
            <?php endif; ?>
        });

        // Initialize Plan Modal functionality
        function initializePlanModal() {
            const modal = document.getElementById('planModal');
            if (!modal) {
                console.error('Plan modal not found!');
                return;
            }

            // Close modal when clicking outside content
            modal.addEventListener('click', function(e) {
                if (e.target === modal || e.target.classList.contains('plan-modal-overlay')) {
                    closePlanModal();
                }
            });

            // Close modal with ESC key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closePlanModal();
                    closeReviewModal();
                }
            });

            // Prevent body scroll when modal is open
            modal.addEventListener('transitionend', function() {
                if (modal.classList.contains('show')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = 'auto';
                }
            });

            console.log('Plan modal initialized successfully');
        }

        // Initialize Review Modal functionality
        function initializeReviewModal() {
            const modal = document.getElementById('reviewModal');
            if (!modal) {
                console.error('Review modal not found!');
                return;
            }

            // Close modal when clicking outside content
            modal.addEventListener('click', function(e) {
                if (e.target === modal || e.target.classList.contains('review-modal-overlay')) {
                    closeReviewModal();
                }
            });

            console.log('Review modal initialized successfully');
        }

        // Open Plan Modal
        function openPlanModal(planType) {
            console.log('Opening modal for:', planType);
            
            const modal = document.getElementById('planModal');
            const data = planData[planType];
            
            if (!modal || !data) {
                console.error('Modal or data not found:', { modal: !!modal, data: !!data });
                return;
            }
            
            // Set modal content
            const titleElement = document.getElementById('modalTitle');
            const descElement = document.getElementById('modalDescription');
            const imageElement = document.getElementById('modalImage');
            
            if (titleElement) titleElement.textContent = data.title;
            if (descElement) descElement.textContent = data.description;
            if (imageElement) {
                imageElement.src = data.image;
                imageElement.alt = data.title;
                
                // Add error handling for images
                imageElement.onerror = function() {
                    console.warn('Image failed to load:', data.image);
                    this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjBmMGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIyNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlIE5vdCBGb3VuZDwvdGV4dD48L3N2Zz4=';
                    this.alt = 'Image not available';
                };
            }
            
            // Show modal with animation
            modal.style.display = 'flex';
            modal.offsetHeight; // Force reflow
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            console.log('Modal opened successfully');
        }

        // Close Plan Modal
        function closePlanModal() {
            console.log('Closing modal');
            
            const modal = document.getElementById('planModal');
            if (!modal) return;
            
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
            
            // Hide modal after animation
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Open Review Modal
        function openReviewModal() {
            const modal = document.getElementById('reviewModal');
            if (!modal) return;
            
            modal.style.display = 'flex';
            modal.offsetHeight; // Force reflow
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        // Close Review Modal
        function closeReviewModal() {
            const modal = document.getElementById('reviewModal');
            if (!modal) return;
            
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
            
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Testimonials slider functionality
        function slideTestimonials(direction) {
            const slider = document.getElementById('testimonialsSlider');
            const cardWidth = 350; // card width + gap
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

        // Auto submit search form on Enter
        document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
        
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
            if (header) {
                if (scrollTop > 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
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

        function navigateToCategory(category) {
            const categoryUrls = {
                'culinary': 'http://localhost/PapuaJourneyExpo/users/dashboard/allumkm.php?kategori=kuliner',
                'cultural': 'http://localhost/PapuaJourneyExpo/users/dashboard/allumkm.php?kategori=event',
                'marine': 'http://localhost/PapuaJourneyExpo/users/dashboard/allumkm.php?kategori=wisata',
                'hiking': 'http://localhost/PapuaJourneyExpo/users/dashboard/allumkm.php?kategori=wisata',
                'wildlife': 'http://localhost/PapuaJourneyExpo/users/dashboard/allumkm.php?kategori=kerajinan'
            };
            
            const targetUrl = categoryUrls[category];
            if (targetUrl) {
                window.location.href = targetUrl;
            }
        }

        // Video sound toggle function
        function toggleVideoSound(button) {
            const video = button.parentElement.querySelector('video');
            const icon = button.querySelector('i');
            
            if (video.muted) {
                video.muted = false;
                icon.className = 'fas fa-volume-up';
            } else {
                video.muted = true;
                icon.className = 'fas fa-volume-mute';
            }
        }

        // Show destination modal function (placeholder)
        function showDestinationModal() {
            alert('Learn More modal would open here. This is a placeholder function.');
        }

        // Make functions globally available
        window.openPlanModal = openPlanModal;
        window.closePlanModal = closePlanModal;
        window.openReviewModal = openReviewModal;
        window.closeReviewModal = closeReviewModal;
        window.slideTestimonials = slideTestimonials;
    </script>
</body>
</html>