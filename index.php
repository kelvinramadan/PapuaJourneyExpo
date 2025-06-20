<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papua Journey - Discover Authentic Adventures in Papua</title>
    <meta name="description" content="Explore Papua's breathtaking landscapes, rich cultures, and unforgettable adventures. Connect with local businesses and plan your perfect journey.">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="frontend/assets/logo.png" as="image">
    <link rel="preload" href="frontend/assets/banner.jpg" as="image">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="frontend/style-enhanced.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-solid-rounded/css/uicons-solid-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
    
    <!-- Scripts -->
    <script src="frontend/script-enhanced.js" defer></script>
</head>
<body>
    <!-- Loading Screen -->
    <div id="loadingScreen" class="loading-screen">
        <div class="loader">
            <div class="loader-circle"></div>
            <p>Discovering Papua...</p>
        </div>
    </div>

    <!-- Scroll Progress Indicator -->
    <div class="scroll-progress-bar"></div>

    <header class="header">
        <nav class="navbar">
            <div class="logo">
                <img src="frontend/assets/logo.png" alt="Papua Journey Logo"> 
                <p>Journey</p>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" aria-label="Toggle mobile menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <ul class="nav-links">
                <li><a href="#destinations" data-section="destinations">Destinations</a></li>
                <li><a href="#experiences" data-section="experiences">Experiences</a></li>
                <li><a href="#plan" data-section="plan">Plan your trip</a></li>
                <li><a href="#book" data-section="book">Book your trip</a></li>
                <li><a href="#testimonials" data-section="testimonials">Reviews</a></li>
            </ul>
            
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search destinations..." id="searchInput">
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </div>
                <?php if(isset($_SESSION['username'])): ?>
                    <div class="user-menu">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></span>
                        </div>
                        <div class="user-dropdown">
                            <span class="user-greeting">Hi, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                            <a href="users/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                            <a href="users/profile.php"><i class="fas fa-user"></i> Profile</a>
                            <a href="users/bookings.php"><i class="fas fa-list"></i> My Bookings</a>
                            <hr>
                            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <button class="login-btn" onclick="window.location.href='login.php'">
                        <i class="fas fa-user"></i>
                        <span>Sign In</span>
                    </button>
                <?php endif; ?>
            </div>
        </nav>
        
        <!-- Mobile Navigation -->
        <div class="mobile-nav">
            <div class="mobile-nav-header">
                <div class="logo">
                    <img src="frontend/assets/logo.png" alt="Papua Journey Logo"> 
                    <p>Journey</p>
                </div>
                <button class="mobile-nav-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <ul class="mobile-nav-links">
                <li><a href="#destinations">Destinations</a></li>
                <li><a href="#experiences">Experiences</a></li>
                <li><a href="#plan">Plan your trip</a></li>
                <li><a href="#book">Book your trip</a></li>
                <li><a href="#testimonials">Reviews</a></li>
            </ul>
            <?php if(!isset($_SESSION['username'])): ?>
                <button class="mobile-login-btn" onclick="window.location.href='login.php'">
                    <i class="fas fa-user"></i> Sign In
                </button>
            <?php else: ?>
                <div class="mobile-user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <a href="logout.php" class="mobile-logout">Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <section class="hero" id="home">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <span class="hero-badge fade-in">Indonesia's Hidden Paradise</span>
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
                        <source src="frontend/assets/destination-video.mp4" type="video/mp4">
                    </video>
                    <div class="video-play-button" onclick="toggleVideoSound(this)">
                        <i class="fas fa-volume-mute"></i>
                    </div>
                </div>
                <div class="destination-cards">
                    <div class="mini-card fade-in">
                        <img src="frontend/assets/rajaAmpat.jpg" alt="Raja Ampat">
                        <span>Raja Ampat</span>
                    </div>
                    <div class="mini-card fade-in">
                        <img src="frontend/assets/TamanNasionalTelukCendrawasih.jpg" alt="Jayapura">
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

    <section class="book-trip" id="book">
        <div class="book-container">
            <h2>Book Your <b>Trip</b></h2>
            
            <div class="trip-options fade-in">
                <button class="tab-btn active" data-tab="stays">
                    <i class="fi fi-sr-bed"></i>
                    <span>Stays</span>
                </button>
                <button class="tab-btn" data-tab="flights">
                    <i class="fi fi-sr-plane"></i>
                    <span>Flights</span>
                </button>
                <button class="tab-btn" data-tab="car">
                    <i class="fi fi-sr-car"></i>
                    <span>Car Rentals</span>
                </button>
                <button class="tab-btn" data-tab="activities">
                    <i class="fi fi-sr-ticket"></i>
                    <span>Activities</span>
                </button>
                <button class="tab-btn" data-tab="food">
                    <i class="fi fi-sr-utensils"></i>
                    <span>Food & Drink</span>
                </button>
                <button class="tab-btn" data-tab="tours">
                    <i class="fi fi-sr-flag-alt"></i>
                    <span>Tours</span>
                </button>
            </div>

            <div class="trip-form">
                <form id="bookingForm">
            </div>
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
                    <li><a href="#community">Community</a></li>
                    <li><a href="#experiences">Experiences</a></li>
                    <li><a href="#plan">Plan your trip</a></li>
                    <li><a href="#book">Book your trip</a></li>
                </ul>
            </div>
            <div class="footer-contact">
                <h3>Contact Us</h3>
                <p>Email: <a href="mailto: "></a></p>
                <p>Phone: <a href="tel: "> </a></p>
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
</body>
</html>