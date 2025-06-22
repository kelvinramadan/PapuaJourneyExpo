<?php
// navbar.php - Komponen Navbar yang dapat digunakan ulang dengan fungsi profile
if (!isset($_SESSION)) {
    session_start();
}

// Determine base path based on current directory
$current_dir = dirname($_SERVER['PHP_SELF']);
$in_dashboard = strpos($current_dir, '/users/dashboard') !== false;
$in_wisata = strpos($current_dir, '/users/wisata') !== false;
$in_penginapan = strpos($current_dir, '/users/penginapan') !== false;
$in_chatbot = strpos($current_dir, '/users/chatbot') !== false;
$in_components = strpos($current_dir, '/users/components') !== false;

// Set up path prefixes based on location
if ($in_dashboard || $in_wisata || $in_penginapan || $in_chatbot || $in_components) {
    // We're in a subfolder within users
    $base_path = '../../';
    $users_path = '../';
    $config_path = '../../config/';
    $uploads_path = '../../uploads/';
    $logout_path = '../../logout.php';
} else {
    // Default paths if navbar is included from root or users folder
    $base_path = '../';
    $users_path = '';
    $config_path = '../config/';
    $uploads_path = '../uploads/';
    $logout_path = '../logout.php';
}

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $base_path . 'login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get user data untuk navbar
require_once $config_path . 'database.php';
$db = getDbConnection();

// Handle form submissions for profile functions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $new_name = trim($_POST['full_name']);
        $new_email = trim($_POST['email']);
        $new_phone = trim($_POST['phone']);
        $new_address = trim($_POST['address']);
        
        if (empty($new_name) || empty($new_email)) {
            $_SESSION['error_message'] = 'Nama dan email harus diisi!';
        } else {
            // Check if email already exists (excluding current user)
            $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->bind_param("si", $new_email, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $_SESSION['error_message'] = 'Email sudah digunakan oleh user lain!';
            } else {
                $update_stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $update_stmt->bind_param("ssssi", $new_name, $new_email, $new_phone, $new_address, $user_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['user_name'] = $new_name;
                    $_SESSION['user_email'] = $new_email;
                    $_SESSION['message'] = 'Profil berhasil diperbarui!';
                } else {
                    $_SESSION['error_message'] = 'Gagal memperbarui profil!';
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        }
        
        // Redirect back to the current page
        $redirect_url = $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect_url);
        exit();
    }
    
    if (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error_message'] = 'Semua field password harus diisi!';
        } elseif ($new_password !== $confirm_password) {
            $_SESSION['error_message'] = 'Konfirmasi password tidak cocok!';
        } elseif (strlen($new_password) < 6) {
            $_SESSION['error_message'] = 'Password baru minimal 6 karakter!';
        } else {
            // Verify current password
            $pass_stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
            $pass_stmt->bind_param("i", $user_id);
            $pass_stmt->execute();
            $pass_result = $pass_stmt->get_result();
            $pass_data = $pass_result->fetch_assoc();
            
            if (password_verify($current_password, $pass_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pass_stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_pass_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_pass_stmt->execute()) {
                    $_SESSION['message'] = 'Password berhasil diubah!';
                } else {
                    $_SESSION['error_message'] = 'Gagal mengubah password!';
                }
                $update_pass_stmt->close();
            } else {
                $_SESSION['error_message'] = 'Password lama tidak benar!';
            }
            $pass_stmt->close();
        }
        
        // Redirect back to the current page
        $redirect_url = $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect_url);
        exit();
    }
    
    if (isset($_POST['upload_photo'])) {
        // Handle photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = $_FILES['profile_photo']['type'];
            $file_size = $_FILES['profile_photo']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                $_SESSION['error_message'] = 'Format file tidak didukung! Gunakan JPG, PNG, atau GIF.';
            } elseif ($file_size > $max_size) {
                $_SESSION['error_message'] = 'Ukuran file terlalu besar! Maksimal 5MB.';
            } else {
                $upload_dir = $uploads_path . 'profile_images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    // Get current profile image to delete old one
                    $current_stmt = $db->prepare("SELECT profile_image FROM users WHERE id = ?");
                    $current_stmt->bind_param("i", $user_id);
                    $current_stmt->execute();
                    $current_result = $current_stmt->get_result();
                    $current_data = $current_result->fetch_assoc();
                    $current_stmt->close();
                    
                    // Delete old profile image if exists and not default
                    if ($current_data['profile_image'] && $current_data['profile_image'] !== 'default-user.jpg') {
                        $old_file = $upload_dir . $current_data['profile_image'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    
                    // Update database
                    $photo_stmt = $db->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $photo_stmt->bind_param("si", $new_filename, $user_id);
                    
                    if ($photo_stmt->execute()) {
                        $_SESSION['message'] = 'Foto profil berhasil diperbarui!';
                    } else {
                        $_SESSION['error_message'] = 'Gagal menyimpan foto profil!';
                    }
                    $photo_stmt->close();
                } else {
                    $_SESSION['error_message'] = 'Gagal mengupload foto!';
                }
            }
        } else {
            $_SESSION['error_message'] = 'Pilih file foto terlebih dahulu!';
        }
        
        // Redirect back to the current page
        $redirect_url = $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirect_url);
        exit();
    }
}

// Get updated user data after any profile operations
$stmt = $db->prepare("SELECT full_name, email, phone, address, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();
$db->close();
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
}

/* Papua Journey Navbar Specific Styles - Using unique class prefix to avoid conflicts */
.pj-navbar-wrapper * {
    box-sizing: border-box;
}

/* Scroll Progress Bar */
.pj-navbar-wrapper .scroll-progress-bar {
    position: fixed;
    top: 0;
    left: 0;
    width: 0%;
    height: 3px;
    background: linear-gradient(to right, var(--button-color), var(--button-hover-color));
    z-index: 10000;
    transition: width 0.2s ease-out;
}

/* Header - Exact copy from index.php */
.pj-navbar-wrapper .pj-navbar-header {
    position: fixed;
    top: 0;
    width: 100%;
    background-color: rgba(255, 255, 255, 0);
    backdrop-filter: blur(10px);
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

/* Search Container */
.pj-navbar-wrapper .search-container {
    display: flex;
    align-items: center;
    gap: 1rem;
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

.pj-navbar-wrapper .mobile-nav-links a i {
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
    color: var(--button-color);
}

.pj-navbar-wrapper .mobile-user-info {
    padding: 1.5rem;
    background: var(--background-color);
    border-top: 1px solid rgba(0, 0, 0, 0.05);
}

.pj-navbar-wrapper .mobile-user-info span {
    display: block;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--text-color-secondary);
}

.pj-navbar-wrapper .mobile-profile-btn,
.pj-navbar-wrapper .mobile-logout {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    margin-bottom: 0.5rem;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
    width: 100%;
    border: none;
    background: white;
    color: var(--text-color-secondary);
    font-size: 0.9rem;
    cursor: pointer;
    font-family: inherit;
}

.pj-navbar-wrapper .mobile-profile-btn:hover,
.pj-navbar-wrapper .mobile-logout:hover {
    background: var(--button-color);
    color: white;
}

/* Adjust body padding for fixed header */
body {
    padding-top: 80px;
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

/* Notification Messages */
.pj-navbar-wrapper .notification-message {
    position: fixed;
    top: 90px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 1500;
    animation: pjSlideInRight 0.3s ease;
}

@keyframes pjSlideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.pj-navbar-wrapper .notification-success {
    background: var(--success-color);
    color: white;
}

.pj-navbar-wrapper .notification-error {
    background: var(--error-color);
    color: white;
}

/* Modal Styles */
.pj-navbar-wrapper .modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
}

.pj-navbar-wrapper .modal-content {
    background: white;
    margin: 5% auto;
    padding: 2rem;
    border-radius: 20px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: pjModalSlideIn 0.3s ease;
    position: relative;
}

@keyframes pjModalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.pj-navbar-wrapper .close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s;
}

.pj-navbar-wrapper .close:hover,
.pj-navbar-wrapper .close:focus {
    color: #000;
}

.pj-navbar-wrapper .modal h3 {
    color: var(--text-color-secondary);
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
}

.pj-navbar-wrapper .modal .form-group {
    margin-bottom: 1.5rem;
}

.pj-navbar-wrapper .modal .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-color-secondary);
}

.pj-navbar-wrapper .modal .form-group input,
.pj-navbar-wrapper .modal .form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s;
    font-family: inherit;
}

.pj-navbar-wrapper .modal .form-group input:focus,
.pj-navbar-wrapper .modal .form-group textarea:focus {
    outline: none;
    border-color: var(--button-color);
    box-shadow: 0 0 0 3px rgba(220, 155, 17, 0.1);
}

.pj-navbar-wrapper .modal .form-group small {
    display: block;
    margin-top: 0.25rem;
    color: #666;
}

.pj-navbar-wrapper .modal .btn {
    background: var(--button-color);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-right: 1rem;
}

.pj-navbar-wrapper .modal .btn:hover {
    background: var(--button-hover-color);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(220, 155, 17, 0.3);
}

.pj-navbar-wrapper .modal .btn-secondary {
    background: #6c757d;
}

.pj-navbar-wrapper .modal .btn-secondary:hover {
    background: #5a6268;
    box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
}
</style>

<!-- Papua Journey Navbar Component -->
<div class="pj-navbar-wrapper">
    <!-- Scroll Progress Bar -->
    <div class="scroll-progress-bar"></div>
    
    <header class="pj-navbar-header" id="header">
    <nav class="navbar">
        <a href="<?php echo $base_path; ?>index.php" class="logo">
            <img src="<?php echo $base_path; ?>assets/logo.png" alt="Papua Journey Logo">
            <p>Journey</p>
        </a>
        
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" aria-label="Toggle mobile menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        
        <ul class="nav-links">
            <li><a href="<?php echo $users_path; ?>dashboard/user_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="<?php echo $users_path; ?>wisata/userwisata.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'userwisata.php' ? 'active' : ''; ?>">Wisata</a></li>
            <li><a href="<?php echo $users_path; ?>penginapan/userpenginapan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'userpenginapan.php' ? 'active' : ''; ?>">Penginapan</a></li>
            <li><a href="<?php echo $users_path; ?>transaksi/transaksi.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active' : ''; ?>">Transaksi</a></li>
            <li><a href="<?php echo $users_path; ?>chatbot/user_chatbot.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user_chatbot.php' || (basename($_SERVER['PHP_SELF']) == 'index.php' && $in_chatbot) ? 'active' : ''; ?>">AI Assistant</a></li>
        </ul>
        
        <div class="search-container">
            <div class="user-menu">
                <div class="user-avatar">
                    <?php if ($user_data['profile_image'] && file_exists($uploads_path . 'profile_images/' . $user_data['profile_image'])): ?>
                        <img src="<?php echo $uploads_path; ?>profile_images/<?php echo htmlspecialchars($user_data['profile_image']); ?>" alt="Profile">
                    <?php else: ?>
                        <span><?php echo strtoupper(substr($user_name, 0, 1)); ?></span>
                    <?php endif; ?>
                </div>
                <div class="user-dropdown">
                    <span class="user-greeting">Hi, <?php echo htmlspecialchars($user_name); ?>!</span>
                    <a href="<?php echo $users_path; ?>dashboard/user_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <button onclick="openModal('profileModal')"><i class="fas fa-user"></i> Edit Profile</button>
                    <button onclick="openModal('photoModal')"><i class="fas fa-camera"></i> Change Photo</button>
                    <button onclick="openModal('passwordModal')"><i class="fas fa-lock"></i> Change Password</button>
                    <hr>
                    <a href="<?php echo $logout_path; ?>" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Navigation -->
    <div class="mobile-nav">
        <div class="mobile-nav-header">
            <div class="logo">
                <img src="<?php echo $base_path; ?>assets/logo.png" alt="Papua Journey Logo">
                <p>Journey</p>
            </div>
            <button class="mobile-nav-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="mobile-nav-links">
            <li><a href="<?php echo $users_path; ?>dashboard/user_dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a></li>
            <li><a href="<?php echo $users_path; ?>wisata/userwisata.php">
                <i class="fas fa-map-marked-alt"></i> Wisata
            </a></li>
            <li><a href="<?php echo $users_path; ?>penginapan/userpenginapan.php">
                <i class="fas fa-bed"></i> Penginapan
            </a></li>
            <li><a href="<?php echo $users_path; ?>transaksi/transaksi.php">
                <i class="fas fa-receipt"></i> Transaksi
            </a></li>
            <li><a href="<?php echo $users_path; ?>chatbot/user_chatbot.php">
                <i class="fas fa-robot"></i> AI Assistant
            </a></li>
        </ul>
        <div class="mobile-user-info">
            <span>Welcome, <?php echo htmlspecialchars($user_name); ?></span>
            <button class="mobile-profile-btn" onclick="openModal('profileModal')">
                <i class="fas fa-user"></i> Edit Profile
            </button>
            <a href="<?php echo $logout_path; ?>" class="mobile-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</header>

    <!-- Display Messages if any -->
    <?php if (isset($_SESSION['message'])): ?>
    <div class="notification-message notification-success">
        <?php 
        echo htmlspecialchars($_SESSION['message']); 
        unset($_SESSION['message']); 
        ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
    <div class="notification-message notification-error">
        <?php 
        echo htmlspecialchars($_SESSION['error_message']); 
        unset($_SESSION['error_message']); 
        ?>
    </div>
    <?php endif; ?>

    <!-- Modals -->
    <!-- Photo Upload Modal -->
    <div id="photoModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('photoModal')">&times;</span>
        <h3>Upload Profile Photo</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_photo">Choose Photo:</label>
                <input type="file" name="profile_photo" id="profile_photo" accept="image/*" required>
                <small>Format: JPG, PNG, GIF. Max 5MB.</small>
            </div>
            <button type="submit" name="upload_photo" class="btn">Upload</button>
            <button type="button" onclick="closeModal('photoModal')" class="btn btn-secondary">Cancel</button>
        </form>
    </div>
</div>

<!-- Profile Edit Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('profileModal')">&times;</span>
        <h3>Edit Profile</h3>
        <form method="POST">
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea name="address" id="address" rows="3"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
            </div>
            <button type="submit" name="update_profile" class="btn">Save</button>
            <button type="button" onclick="closeModal('profileModal')" class="btn btn-secondary">Cancel</button>
        </form>
    </div>
</div>

<!-- Password Change Modal -->
<div id="passwordModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('passwordModal')">&times;</span>
        <h3>Change Password</h3>
        <form method="POST">
            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" name="current_password" id="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required>
                <small>Minimum 6 characters</small>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <button type="submit" name="change_password" class="btn">Change Password</button>
            <button type="button" onclick="closeModal('passwordModal')" class="btn btn-secondary">Cancel</button>
        </form>
    </div>
</div>

<script>
// Get header element
const header = document.getElementById('header');
// Ensure we're working with the Papua Journey navbar
if (header && header.classList.contains('pj-navbar-header')) {
let lastScroll = 0;

// Scroll Progress Bar
function updateScrollProgress() {
    const scrollProgress = document.querySelector('.pj-navbar-wrapper .scroll-progress-bar');
    if (scrollProgress) {
        window.addEventListener('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            scrollProgress.style.width = scrolled + '%';
        });
    }
}

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


// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    document.body.style.overflow = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = '';
    }
}

} // Close the if statement for header check

// Auto-hide notification messages
document.addEventListener('DOMContentLoaded', function() {
    updateScrollProgress();
    
    const messages = document.querySelectorAll('.pj-navbar-wrapper .notification-message');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s, transform 0.5s';
            message.style.opacity = '0';
            message.style.transform = 'translateX(100%)';
            setTimeout(() => message.remove(), 500);
        }, 5000);
    });
});
</script>
</div> <!-- End of pj-navbar-wrapper -->