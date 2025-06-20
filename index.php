<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Papua Journey</title>
    <link rel="stylesheet" href="frontend/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="frontend/script.js" defer></script>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-regular-rounded/css/uicons-regular-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-solid-rounded/css/uicons-solid-rounded.css'>
    <link rel='stylesheet' href='https://cdn-uicons.flaticon.com/3.0.0/uicons-bold-rounded/css/uicons-bold-rounded.css'>
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo">
                <img src="frontend/assets/logo.png" alt="Papua Journey Logo"> 
                <p>Journey</p>
            </div>   
            <ul class="nav-links">
                <li><a href="#destinations">Destinations</a></li>
                <li><a href="#community">Community</a></li>
                <li><a href="#experiences">Experiences</a></li>
                <li><a href="#plan">Plan your trip</a></li>
                <li><a href="#book">Book your trip</a></li>
            </ul>
            <div class="search-container">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search destinations...">
                </div>
                <?php if(isset($_SESSION['username'])): ?>
                    <div class="user-menu">
                        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <a href="logout.php" class="logout-btn">Logout</a>
                    </div>
                <?php else: ?>
                    <button class="login-btn" onclick="window.location.href='login.php'">Sign In</button>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Discover the Beauty of Papua</h1>
            <p>Explore breathtaking landscapes, rich cultures, and unforgettable adventures.</p>
            <a href="#destinations" class="btn">Explore Now</a>
        </div>
    </section> 

    <section class="destinations" id="destinations">
        <div class="destination-container">
            <div class="destination-title fade-in">
                <h2>This Is your <b>Papua Journey</b></h2>
                <p>Discover the true essence of Papua through a personalized journey tailored exclusively to your preferences and interests, where every adventure becomes your own unique story to tell.</p>
                <button class=" btn fade-in">Get to Know More</button>
            </div>
            <div class="destination-video">
                <video autoplay muted loop>
                    <source src="frontend/assets/destination-video.mp4" type="video/mp4">
                </video>
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
                <h2>Explore your <b>Interest</b></h2>
            </div>
            <div class="interest-container fade-in">
                <div class="interest-slider" id="interestSlider">
                    <div class="interest-card food">
                        <div class="interest-content">
                            <i class="fi fi-rr-hamburger-soda"></i>
                            <h3>Food and Drink</h3>
                        </div>
                    </div>
                    <div class="interest-card culture">
                        <div class="interest-content">
                            <i class="fi fi-rr-people"></i>
                            <h3>Culture and Heritage</h3>
                        </div>
                    </div>
                    <div class="interest-card adventures">
                        <div class="interest-content">
                            <i class="fi fi-rr-dolphin"></i>
                            <h3>Underwater Adventures</h3>
                        </div>
                    </div>
                    <div class="interest-card tracking">
                        <div class="interest-content">
                            <i class="fi fi-br-mountain"></i>
                            <h3>Tracking Tour</h3>
                        </div>
                    </div>
                    <div class="interest-card wildlife">
                        <div class="interest-content">
                            <i class="fi fi-rr-bird"></i>
                            <h3>Wildlife and Nature Tours</h3>
                        </div>
                    </div>
                </div>
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
                    <h3>itinerary ideas</h3>
                    <p>Explore suggested itineraries for a memorable trip.</p>
                </div>
                <div class="plan-card">
                    <i class="fi fi-rr-bus-ticket"></i>
                    <h3>Tour guide</h3>
                    <p>Connect with local guides for an authentic experience.</p>
                </div>
                <div class="plan-card">
                    <i class="fi fi-rr-guide-alt"></i>
                    <h3>ettiquette</h3>
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