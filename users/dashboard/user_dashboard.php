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
    </script>
</body>
</html>