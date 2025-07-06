<!-- navbar.php -->
<?php
// Determine base path based on current directory
$current_dir = dirname($_SERVER['PHP_SELF']);
$in_dashboard = strpos($current_dir, '/users/dashboard') !== false;
$in_wisata = strpos($current_dir, '/users/wisata') !== false;
$in_penginapan = strpos($current_dir, '/users/penginapan') !== false;
$in_chatbot = strpos($current_dir, '/users/chatbot') !== false;
$in_components = strpos($current_dir, '/users/components') !== false;
$in_cart = strpos($current_dir, '/users/cart') !== false;
$in_checkout = strpos($current_dir, '/users/checkout') !== false;
$in_transaksi = strpos($current_dir, '/users/transaksi') !== false;
$in_account = strpos($current_dir, '/users/account') !== false;

// Set up path prefixes based on location
if ($in_dashboard || $in_wisata || $in_penginapan || $in_chatbot || $in_components || $in_cart || $in_checkout || $in_transaksi || $in_account) {
    // We're in a subfolder within users
    $base_path = '';
    $users_path = '';
    $config_path = 'config/';
    $uploads_path = 'uploads/';
    $logout_path = 'logout.php';
    $login_path = 'login.php';
} else {
    // Default paths if navbar is included from root or users folder
    $base_path = '';
    $users_path = '';
    $config_path = 'config/';
    $uploads_path = 'uploads/';
    $logout_path = 'logout.php';
    $login_path = 'login.php';
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$user_name = $is_logged_in ? $_SESSION['user_name'] : null;
$user_email = $is_logged_in ? $_SESSION['user_email'] : null;

// Get cart count only if logged in
$cart_count = 0;
if ($is_logged_in) {
    require_once $config_path . 'database.php';
    $db = getDbConnection();
    
    $cart_stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    $cart_count = $cart_result->fetch_assoc()['count'];
    $cart_stmt->close();
    $db->close();
}
?>

<!-- Include Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
/* Root Variables to match index.php */
:root {
    --primary-color: #536245;
    --secondary-color: #d9d9d9;
    --button-color: #DC9B11;
    --button-hover-color: #f4b63b;
    --text-color: #FFFCF7;
    --text-color-secondary: #191919;
    --background-color: #EBE7E4;
    --transition: all 0.3s ease-in-out;
    --shadow: #333333b2;
    --success-color: #4CAF50;
    --error-color: #f44336;
    --disabled-color: #cccccc;
}

/* Papua Journey Navbar Specific Styles - Using unique class prefix to avoid conflicts */
.pj-navbar-wrapper * {
    box-sizing: border-box;
}

/* Header - Exact copy from index.php */
.pj-navbar-wrapper .pj-navbar-header {
    position: fixed;
    top: 0;
    width: 100%;
    background-color: rgba(255, 255, 255, 0);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.05);
    padding: 1rem 2rem;
    z-index: 1000;
    transition: all 0.3s ease;
}

.pj-navbar-wrapper .pj-navbar-header.scrolled {
    background-color: rgba(255, 255, 255, 0.98);
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
}

/* Navigation */
.pj-navbar-wrapper .navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 1200px;
    margin: 0 auto;
}

.pj-navbar-wrapper .logo {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: transform 0.3s ease;
    text-decoration: none;
}

.pj-navbar-wrapper .logo:hover {
    transform: scale(1.05);
}

.pj-navbar-wrapper .logo img {
    height: 45px;
    width: auto;
}

.pj-navbar-wrapper .logo p {
    font-size: 1.5rem;
    color: var(--button-color);
    font-weight: 600;
    margin: 0;
}

/* Mobile Menu Toggle */
.pj-navbar-wrapper .mobile-menu-toggle {
    display: none;
    flex-direction: column;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
}

.pj-navbar-wrapper .mobile-menu-toggle span {
    width: 25px;
    height: 3px;
    background: var(--text-color);
    border-radius: 2px;
    transition: all 0.3s ease;
}

.pj-navbar-wrapper .pj-navbar-header.scrolled .mobile-menu-toggle span {
    background: var(--text-color-secondary);
}

.pj-navbar-wrapper .mobile-menu-toggle.active span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.pj-navbar-wrapper .mobile-menu-toggle.active span:nth-child(2) {
    opacity: 0;
}

.pj-navbar-wrapper .mobile-menu-toggle.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}

/* Navigation Links */
.pj-navbar-wrapper .nav-links {
    display: flex;
    list-style: none;
    gap: 2rem;
    margin: 0;
    padding: 0;
}

.pj-navbar-wrapper .nav-links a {
    text-decoration: none;
    color: var(--text-color-secondary);
    font-weight: 500;
    transition: var(--transition);
    position: relative;
    padding: 5px 0;
}

.pj-navbar-wrapper .nav-links a:hover {
    color: var(--button-color);
}

.pj-navbar-wrapper .nav-links a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--button-color);
    transition: width 0.3s ease;
}

.pj-navbar-wrapper .nav-links a:hover::after,
.pj-navbar-wrapper .nav-links a.active::after {
    width: 100%;
}

/* Disabled Links */
.pj-navbar-wrapper .nav-links a.disabled {
    color: var(--disabled-color);
    cursor: not-allowed;
    position: relative;
}

.pj-navbar-wrapper .nav-links a.disabled:hover {
    color: var(--disabled-color);
}

.pj-navbar-wrapper .nav-links a.disabled::after {
    display: none;
}

.pj-navbar-wrapper .nav-links a.disabled i {
    color: var(--disabled-color);
}

/* Search Container */
.pj-navbar-wrapper .search-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Login Button */
.pj-navbar-wrapper .login-btn {
    background: var(--button-color);
    color: white;
    border: none;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pj-navbar-wrapper .login-btn:hover {
    background: var(--button-hover-color);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(220, 155, 17, 0.3);
}

/* User Menu */
.pj-navbar-wrapper .user-menu {
    position: relative;
}

.pj-navbar-wrapper .user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--button-color), var(--button-hover-color));
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-weight: bold;
    color: white;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    overflow: hidden;
}

.pj-navbar-wrapper .user-avatar:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(220, 155, 17, 0.3);
}

.pj-navbar-wrapper .user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* User Dropdown */
.pj-navbar-wrapper .user-dropdown {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    min-width: 220px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    overflow: hidden;
}

.pj-navbar-wrapper .user-menu:hover .user-dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.pj-navbar-wrapper .user-greeting {
    display: block;
    padding: 1rem;
    font-weight: 600;
    color: var(--text-color-secondary);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    background: var(--background-color);
}

.pj-navbar-wrapper .user-dropdown a,
.pj-navbar-wrapper .user-dropdown button {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    color: var(--text-color-secondary);
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    font-size: 0.9rem;
    cursor: pointer;
    font-family: inherit;
}

.pj-navbar-wrapper .user-dropdown a:hover,
.pj-navbar-wrapper .user-dropdown button:hover {
    background: var(--background-color);
    padding-left: 1.5rem;
}

.pj-navbar-wrapper .user-dropdown i {
    color: var(--button-color);
    width: 16px;
    text-align: center;
}

.pj-navbar-wrapper .user-dropdown hr {
    margin: 0;
    border: none;
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.pj-navbar-wrapper .logout-link {
    color: var(--error-color) !important;
}

.pj-navbar-wrapper .logout-link i {
    color: var(--error-color) !important;
}

/* Cart Icon Styles */
.pj-navbar-wrapper .cart-icon {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pj-navbar-wrapper .cart-badge {
    position: absolute;
    top: -8px;
    right: -12px;
    background: var(--button-color);
    color: white;
    font-size: 0.75rem;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 20px;
    text-align: center;
    animation: pjBadgePulse 0.5s ease;
}

@keyframes pjBadgePulse {
    0% {
        transform: scale(0);
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
    }
}

/* Mobile Navigation */
.pj-navbar-wrapper .mobile-nav {
    position: fixed;
    top: 0;
    right: -100%;
    width: 80%;
    max-width: 300px;
    height: 100vh;
    background: white;
    box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1);
    transition: right 0.3s ease;
    z-index: 2000;
    overflow-y: auto;
}

.pj-navbar-wrapper .mobile-nav.active {
    right: 0;
}

.pj-navbar-wrapper .mobile-nav-header {
    background: var(--primary-color);
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pj-navbar-wrapper .mobile-nav-header .logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pj-navbar-wrapper .mobile-nav-header .logo img {
    width: 35px;
    height: 35px;
}

.pj-navbar-wrapper .mobile-nav-header .logo p {
    color: white;
    margin: 0;
    font-size: 1.3rem;
}

.pj-navbar-wrapper .mobile-nav-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
}

.pj-navbar-wrapper .mobile-nav-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.pj-navbar-wrapper .mobile-nav-links li {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.pj-navbar-wrapper .mobile-nav-links a {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    color: var(--text-color-secondary);
    text-decoration: none;
    transition: all 0.3s ease;
}

.pj-navbar-wrapper .mobile-nav-links a:hover {
    background: var(--background-color);
    color: var(--button-color);
    padding-left: 2rem;
}

.pj-navbar-wrapper .mobile-nav-links a.disabled {
    color: var(--disabled-color);
    cursor: not-allowed;
}

.pj-navbar-wrapper .mobile-nav-links a.disabled:hover {
    background: none;
    color: var(--disabled-color);
    padding-left: 1.5rem;
}

.pj-navbar-wrapper .mobile-nav-links a i {
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
    color: var(--button-color);
}

.pj-navbar-wrapper .mobile-nav-links a.disabled i {
    color: var(--disabled-color);
}

.pj-navbar-wrapper .mobile-user-info {
    padding: 1.5rem;
    background: var(--background-color);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.pj-navbar-wrapper .mobile-login-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: var(--button-color);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    width: 100%;
    font-weight: 600;
}

.pj-navbar-wrapper .mobile-login-btn:hover {
    background: var(--button-hover-color);
}

.pj-navbar-wrapper .mobile-user-info span {
    display: block;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-color-secondary);
}

.pj-navbar-wrapper .mobile-logout {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    margin-top: 0.5rem;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    width: 100%;
    border: none;
    background: var(--error-color);
    color: white;
    font-size: 0.9rem;
    cursor: pointer;
    font-family: inherit;
    font-weight: 600;
}

.pj-navbar-wrapper .mobile-logout:hover {
    background: #d32f2f;
}

/* Login Required Tooltip */
.pj-navbar-wrapper .login-required-tooltip {
    position: absolute;
    bottom: -40px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--text-color-secondary);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.8rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1001;
}

.pj-navbar-wrapper .login-required-tooltip::before {
    content: '';
    position: absolute;
    top: -5px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-bottom: 5px solid var(--text-color-secondary);
}

.pj-navbar-wrapper .nav-links a.disabled:hover .login-required-tooltip {
    opacity: 1;
    visibility: visible;
}

/* Media Queries */
@media (max-width: 768px) {
    .pj-navbar-wrapper .pj-navbar-header {
        padding: 1rem;
    }
    
    .pj-navbar-wrapper .nav-links {
        display: none;
    }
    
    .pj-navbar-wrapper .mobile-menu-toggle {
        display: flex;
    }
    
    .pj-navbar-wrapper .user-avatar {
        width: 35px;
        height: 35px;
        font-size: 1rem;
    }
}
</style>
<script src="script.js" defer></script>

<!-- Papua Journey Navbar Component -->
<div class="pj-navbar-wrapper">
    <header class="pj-navbar-header" id="header">
        <nav class="navbar">
            <div class="logo">
                <img src="assets/logo.png" alt="Papua Journey Logo"> 
                <p>Journey</p>
            </div>
            
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" aria-label="Toggle mobile menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <ul class="nav-links">
                <li><a href="<?php echo $users_path; ?>index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="<?php echo $users_path; ?>wisata/userwisata.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'userwisata.php' ? 'active' : ''; ?>">Wisata</a></li>
                <li><a href="<?php echo $users_path; ?>penginapan/userpenginapan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'userpenginapan.php' ? 'active' : ''; ?>">Penginapan</a></li>
                <li>
                    <?php if ($is_logged_in): ?>
                        <a href="<?php echo $users_path; ?>chatbot/user_chatbot.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user_chatbot.php' || (basename($_SERVER['PHP_SELF']) == 'index.php' && $in_chatbot) ? 'active' : ''; ?>">AI Assistant</a>
                    <?php else: ?>
                        <a href="login.php" class="disabled" onclick="showLoginAlert(); return false;">
                            AI Assistant
                            <span class="login-required-tooltip">Login required</span>
                        </a>
                    <?php endif; ?>
                </li>
                <li>
                    <?php if ($is_logged_in): ?>
                        <a href="<?php echo $users_path; ?>cart/cart.php" class="cart-icon <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : ''; ?>">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="cart-icon disabled" onclick="showLoginAlert(); return false;">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="login-required-tooltip">Login required</span>
                        </a>
                    <?php endif; ?>
                </li>
            </ul>
            
            <div class="search-container">
                <?php if ($is_logged_in): ?>
                    <div class="user-menu">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
                        </div>
                        <div class="user-dropdown">
                            <span class="user-greeting">Hi, <?php echo htmlspecialchars($user_name); ?>!</span>
                            <hr>
                            <a href="<?php echo $logout_path; ?>" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo $login_path; ?>" class="login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </nav>
        
        <!-- Mobile Navigation -->
        <div class="mobile-nav">
            <div class="mobile-nav-header">
                <div class="logo">
                    <img src="assets/logo.png" alt="Papua Journey Logo"> 
                    <p>Journey</p>
                </div>
                <button class="mobile-nav-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <ul class="mobile-nav-links">
                <?php if ($is_logged_in): ?>
                    <li><a href="<?php echo $users_path; ?>index.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a></li>
                <?php endif; ?>
                <li><a href="<?php echo $users_path; ?>wisata/userwisata.php">
                    <i class="fas fa-map-marked-alt"></i> Wisata
                </a></li>
                <li><a href="<?php echo $users_path; ?>penginapan/userpenginapan.php">
                    <i class="fas fa-bed"></i> Penginapan
                </a></li>
                <li>
                    <?php if ($is_logged_in): ?>
                        <a href="<?php echo $users_path; ?>chatbot/user_chatbot.php">
                            <i class="fas fa-robot"></i> AI Assistant
                        </a>
                    <?php else: ?>
                        <a href="#" class="disabled" onclick="showLoginAlert(); return false;">
                            <i class="fas fa-robot"></i> AI Assistant
                        </a>
                    <?php endif; ?>
                </li>
                <li>
                    <?php if ($is_logged_in): ?>
                        <a href="<?php echo $users_path; ?>cart/cart.php">
                            <i class="fas fa-shopping-cart"></i> Keranjang
                            <?php if ($cart_count > 0): ?>
                                <span style="background: var(--button-color); color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.8rem; margin-left: 5px;"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <a href="#" class="disabled" onclick="showLoginAlert(); return false;">
                            <i class="fas fa-shopping-cart"></i> Keranjang
                        </a>
                    <?php endif; ?>
                </li>
            </ul>
            <div class="mobile-user-info">
                <?php if ($is_logged_in): ?>
                    <span>Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="<?php echo $logout_path; ?>" class="mobile-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="<?php echo $login_path; ?>" class="mobile-login-btn">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
</div>

<script>
// Get header element
const header = document.getElementById('header');
// Ensure we're working with the Papua Journey navbar
if (header && header.classList.contains('pj-navbar-header')) {
    let lastScroll = 0;

    // Header scroll behavior
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        // Add/remove scrolled class
        if (currentScroll > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        // Hide/show header on scroll
        if (currentScroll > lastScroll && currentScroll > 100) {
            header.classList.add('header-hidden');
        } else {
            header.classList.remove('header-hidden');
        }
        
        lastScroll = currentScroll;
    });

    // Mobile Menu Toggle
    const mobileMenuToggle = document.querySelector('.pj-navbar-wrapper .mobile-menu-toggle');
    const mobileNav = document.querySelector('.pj-navbar-wrapper .mobile-nav');
    const mobileNavClose = document.querySelector('.pj-navbar-wrapper .mobile-nav-close');

    if (mobileMenuToggle && mobileNav) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenuToggle.classList.toggle('active');
            mobileNav.classList.toggle('active');
            document.body.style.overflow = mobileNav.classList.contains('active') ? 'hidden' : '';
        });
    }

    if (mobileNavClose && mobileNav) {
        mobileNavClose.addEventListener('click', function() {
            mobileMenuToggle.classList.remove('active');
            mobileNav.classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // Close mobile menu on outside click
    document.addEventListener('click', function(event) {
        if (mobileNav && mobileNav.classList.contains('active') && 
            !mobileNav.contains(event.target) && 
            !mobileMenuToggle.contains(event.target)) {
            mobileMenuToggle.classList.remove('active');
            mobileNav.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
}

// Show login alert for protected features
function showLoginAlert() {
    alert('Silakan login terlebih dahulu untuk mengakses fitur ini!');
    window.location.href = '<?php echo $login_path; ?>';
}
</script>