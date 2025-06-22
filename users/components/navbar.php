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

<style>
/* Global reset for consistent scrollbar behavior */
html {
    /* Removed overflow-y: scroll to prevent phantom scrollbar space */
    /* Use scrollbar-gutter for modern browsers if layout shift is a concern */
    scrollbar-gutter: stable;
}

/* Reset any conflicting chatbot styles */
.navbar-header .user-avatar,
.navbar-header .user-avatar * {
    all: unset;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

/* Reset styles for navbar to ensure consistency */
.navbar-header,
.navbar-header * {
    box-sizing: border-box;
}

/* Scroll Progress Bar */
.scroll-progress-bar {
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    background: linear-gradient(90deg, #ffd700, #ffed4e);
    z-index: 1001;
    transition: width 0.3s ease;
    width: 0%;
}

/* Navbar Styles - Using specific class to avoid conflicts */
.navbar-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 0 !important;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: fixed; /* Changed from sticky to fixed */
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    margin: 0 !important;
    width: 100%;
    box-sizing: border-box;
    font-size: 16px !important;
    line-height: 1.5 !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}

/* Add padding to body to account for fixed navbar */
body {
    padding-top: 80px !important; /* Default, adjusted by JS */
    margin: 0 !important;
    min-height: 100vh;
}

.navbar-header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.navbar-header .logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    transition: transform 0.3s ease;
}

.navbar-header .logo:hover {
    transform: translateY(-2px);
}

.navbar-header .logo img {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.navbar-header .logo p {
    margin: 0;
    font-size: 1.8rem;
    font-weight: bold;
    background: linear-gradient(45deg, #ffd700, #ffed4e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.navbar-header .nav-container {
    display: flex;
    align-items: center;
    gap: 2rem;
    flex: 1;
}

.navbar-header .nav-links {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin: 0 auto;
}

.navbar-header .nav-links a {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    padding: 0.5rem 1.2rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar-header .nav-links a::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.1);
    transition: left 0.3s ease;
    z-index: -1;
    border-radius: 25px;
}

.navbar-header .nav-links a:hover::before {
    left: 0;
}

.navbar-header .nav-links a:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.4);
}

.navbar-header .nav-links a.active {
    background: white;
    color: #667eea;
}

/* Search Container */
.navbar-header .search-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.navbar-header .search-box {
    position: relative;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 25px;
    padding: 0.5rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.navbar-header .search-box:hover,
.navbar-header .search-box:focus-within {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.4);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.navbar-header .search-box i {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

.navbar-header .search-box input {
    background: none;
    border: none;
    outline: none;
    color: white;
    width: 200px;
    font-size: 0.9rem;
    font-family: inherit;
}

.navbar-header .search-box input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

.navbar-header .search-suggestions {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    right: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    max-height: 300px;
    overflow-y: auto;
    display: none;
    z-index: 100;
}

.navbar-header .search-suggestions.active {
    display: block;
}

.navbar-header .search-suggestion-item {
    padding: 0.75rem 1rem;
    color: #333;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.navbar-header .search-suggestion-item:hover {
    background: #f5f5f5;
}

.navbar-header .search-suggestion-item i {
    color: #667eea;
    font-size: 0.9rem;
}

/* Profile Dropdown */
.navbar-header .profile-dropdown {
    position: relative;
}

.navbar-header .profile-trigger {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.navbar-header .profile-trigger:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(255, 255, 255, 0.2);
}

.navbar-header .user-avatar {
    width: 40px !important;
    height: 40px !important;
    border-radius: 50% !important;
    background: linear-gradient(45deg, #ffd700, #ffed4e) !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: bold !important;
    color: #333 !important;
    overflow: hidden !important;
    font-size: 1.2rem !important;
    line-height: 1 !important;
    text-align: center !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
}

.navbar-header .user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.navbar-header .user-info {
    display: flex;
    flex-direction: column;
}

.navbar-header .user-info .user-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.navbar-header .user-info .user-email {
    font-size: 0.75rem;
    opacity: 0.8;
}

.navbar-header .dropdown-arrow {
    font-size: 0.7rem;
    transition: transform 0.3s ease;
}

.navbar-header .profile-dropdown.active .dropdown-arrow {
    transform: rotate(180deg);
}

.navbar-header .dropdown-menu {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    min-width: 220px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.1);
    overflow: hidden;
}

.navbar-header .profile-dropdown.active .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.navbar-header .user-greeting {
    display: block;
    padding: 1rem;
    font-weight: 600;
    color: #333;
    border-bottom: 1px solid #eee;
    font-size: 0.95rem;
}

.navbar-header .dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.75rem 1rem;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    background: none;
    text-align: left;
    font-size: 0.9rem;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.navbar-header .dropdown-item i {
    width: 20px;
    text-align: center;
    color: #667eea;
}

.navbar-header .dropdown-item.logout-link {
    color: #dc3545;
}

.navbar-header .dropdown-item.logout-link i {
    color: #dc3545;
}


.navbar-header .dropdown-item:hover {
    background: #f8f9fa;
    padding-left: 1.5rem;
}

.navbar-header .dropdown-item.logout-link:hover {
    background: #fff5f5;
}

.navbar-header .dropdown-separator {
    height: 1px;
    background: linear-gradient(90deg, transparent, #eee, transparent);
    margin: 0.5rem 0;
}

/* Mobile Menu Toggle */
.navbar-header .mobile-menu-toggle {
    display: none;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0.5rem;
    flex-direction: column;
    gap: 4px;
}

.navbar-header .mobile-menu-toggle span {
    display: block;
    width: 25px;
    height: 3px;
    background: white;
    border-radius: 3px;
    transition: all 0.3s ease;
}

.navbar-header .mobile-menu-toggle.active span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.navbar-header .mobile-menu-toggle.active span:nth-child(2) {
    opacity: 0;
}

.navbar-header .mobile-menu-toggle.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}

/* Mobile Navigation */
.mobile-nav {
    position: fixed;
    top: 0;
    right: -100%;
    width: 80%;
    max-width: 400px;
    height: 100vh;
    background: white;
    box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1);
    transition: right 0.3s ease;
    z-index: 2000;
    overflow-y: auto;
}

.mobile-nav.active {
    right: 0;
}

.mobile-nav-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.mobile-nav-header .logo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.mobile-nav-header .logo img {
    width: 35px;
    height: 35px;
}

.mobile-nav-header .logo p {
    margin: 0;
    color: white;
    font-size: 1.5rem;
    font-weight: bold;
}

.mobile-nav-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
}

.mobile-nav-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.mobile-nav-links li {
    border-bottom: 1px solid #eee;
}

.mobile-nav-links a {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
}

.mobile-nav-links a:hover {
    background: #f5f5f5;
    color: #667eea;
    padding-left: 2rem;
}

.mobile-nav-links a i {
    font-size: 1.2rem;
    width: 25px;
    text-align: center;
}

.mobile-user-info {
    padding: 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #eee;
}

.mobile-user-info span {
    display: block;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #333;
}

.mobile-profile-btn, .mobile-logout {
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
    color: #333;
    font-size: 0.9rem;
    cursor: pointer;
}

.mobile-profile-btn:hover, .mobile-logout:hover {
    background: #667eea;
    color: white;
}

/* Updated nav-links styling */
.navbar-header .nav-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.navbar-header .nav-links li {
    list-style: none;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .navbar-header .mobile-menu-toggle {
        display: flex;
    }
    
    .navbar-header .nav-container,
    .navbar-header .search-box {
        display: none;
    }
    
    .navbar-header-content {
        justify-content: space-between;
        padding: 0 1rem;
    }
    
    .navbar-header .logo p {
        font-size: 1.5rem;
    }
    
    .navbar-header .profile-trigger {
        padding: 0.5rem 0.75rem;
    }
    
    .navbar-header .user-info {
        display: none;
    }
    
    .navbar-header .dropdown-menu {
        right: 0;
        min-width: 200px;
    }
}

@media (max-width: 480px) {
    .navbar-header .logo p {
        font-size: 1.3rem;
    }
    
    .navbar-header .logo img {
        width: 35px;
        height: 35px;
    }
    
    .navbar-header .user-avatar {
        width: 35px !important;
        height: 35px !important;
    }
}

/* Modal Styles - with higher specificity to override any page styles */
.modal {
    display: none !important;
    position: fixed !important;
    z-index: 2000 !important;
    left: 0 !important;
    top: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
    backdrop-filter: blur(5px) !important;
    padding: 0 !important;
    margin: 0 !important;
}

.modal[style*="display: block"] {
    display: block !important;
}

.modal-content {
    background: white !important;
    margin: 5% auto !important;
    padding: 2rem !important;
    border-radius: 20px !important;
    width: 90% !important;
    max-width: 500px !important;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important;
    animation: modalSlideIn 0.3s ease !important;
    position: relative !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    font-size: 16px !important;
    line-height: 1.5 !important;
    color: #333 !important;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.3s;
}

.close:hover,
.close:focus {
    color: #000;
}

.modal h3 {
    color: #333;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
}

.modal .form-group {
    margin-bottom: 1.5rem !important;
    width: 100% !important;
}

.modal .form-group label {
    display: block !important;
    margin-bottom: 0.5rem !important;
    font-weight: 600 !important;
    color: #555 !important;
    font-size: 1rem !important;
    line-height: 1.5 !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}

.modal .form-group input,
.modal .form-group textarea {
    width: 100% !important;
    padding: 0.75rem !important;
    border: 2px solid #e0e0e0 !important;
    border-radius: 8px !important;
    font-size: 1rem !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    line-height: 1.5 !important;
    transition: border-color 0.3s !important;
    background: white !important;
    color: #333 !important;
    box-sizing: border-box !important;
}

.modal .form-group input:focus,
.modal .form-group textarea:focus {
    outline: none !important;
    border-color: #667eea !important;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
}

.modal .form-group small {
    display: block !important;
    margin-top: 0.25rem !important;
    color: #666 !important;
    font-size: 0.875rem !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}

.modal .btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
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

.modal .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.modal .btn-secondary {
    background: #6c757d;
}

.modal .btn-secondary:hover {
    background: #5a6268;
    box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
}
</style>

<!-- Scroll Progress Bar -->
<div class="scroll-progress-bar"></div>

<header class="header navbar-header">
    <nav class="navbar-header-content">
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
        
        <div class="nav-container">
            <ul class="nav-links">
                <li><a href="<?php echo $users_path; ?>dashboard/user_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a></li>
                <li><a href="<?php echo $users_path; ?>wisata/userwisata.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'userwisata.php' ? 'active' : ''; ?>">
                    <i class="fas fa-map-marked-alt"></i> Wisata
                </a></li>    
                <li><a href="<?php echo $users_path; ?>penginapan/userpenginapan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'userpenginapan.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bed"></i> Penginapan
                </a></li>
                <li><a href="<?php echo $users_path; ?>transaksi/transaksi.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'transaksi.php' ? 'active' : ''; ?>">
                    <i class="fas fa-receipt"></i> Transaksi
                </a></li>
                <li><a href="<?php echo $users_path; ?>chatbot/user_chatbot.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user_chatbot.php' || (basename($_SERVER['PHP_SELF']) == 'index.php' && $in_chatbot) ? 'active' : ''; ?>">
                    <i class="fas fa-robot"></i> AI Assistant
                </a></li>
            </ul>
        </div>
        
        <div class="search-container">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search destinations..." id="searchInput">
                <div class="search-suggestions" id="searchSuggestions"></div>
            </div>
            
            <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-trigger" onclick="toggleDropdown()">
                <div class="user-avatar">
                    <?php if ($user_data['profile_image'] && file_exists($uploads_path . 'profile_images/' . $user_data['profile_image'])): ?>
                        <img src="<?php echo $uploads_path; ?>profile_images/<?php echo htmlspecialchars($user_data['profile_image']); ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <small class="user-email"><?php echo htmlspecialchars($user_email); ?></small>
                </div>
                <span class="dropdown-arrow">▼</span>
            </div>
            
            <div class="dropdown-menu">
                <span class="user-greeting">Hi, <?php echo htmlspecialchars($user_name); ?>!</span>
                <button class="dropdown-item" onclick="openModal('photoModal')">
                    <i class="fas fa-camera"></i> Ubah Foto Profil
                </button>
                <button class="dropdown-item" onclick="openModal('profileModal')">
                    <i class="fas fa-user"></i> Edit Profil
                </button>
                <button class="dropdown-item" onclick="openModal('passwordModal')">
                    <i class="fas fa-lock"></i> Ubah Password
                </button>
                <a href="<?php echo $users_path; ?>dashboard/user_dashboard.php" class="dropdown-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <hr style="margin: 0.5rem 0; border: none; border-top: 1px solid #eee;">
                <a href="<?php echo $logout_path; ?>" class="dropdown-item logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
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
                <i class="fas fa-home"></i> Home
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
<div style="position: fixed; top: 90px; right: 20px; background: #28a745; color: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1500;">
    <?php 
    echo htmlspecialchars($_SESSION['message']); 
    unset($_SESSION['message']); 
    ?>
</div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<div style="position: fixed; top: 90px; right: 20px; background: #dc3545; color: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1500;">
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
        <h3>Upload Foto Profil</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_photo">Pilih Foto:</label>
                <input type="file" name="profile_photo" id="profile_photo" accept="image/*" required>
                <small>Format: JPG, PNG, GIF. Maksimal 5MB.</small>
            </div>
            <button type="submit" name="upload_photo" class="btn">Upload</button>
            <button type="button" onclick="closeModal('photoModal')" class="btn btn-secondary">Batal</button>
        </form>
    </div>
</div>

<!-- Profile Edit Modal -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('profileModal')">&times;</span>
        <h3>Edit Profil</h3>
        <form method="POST" action="<?php echo $users_path; ?>dashboard/user_dashboard.php">
            <div class="form-group">
                <label for="full_name">Nama Lengkap:</label>
                <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Nomor Telepon:</label>
                <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="address">Alamat:</label>
                <textarea name="address" id="address" rows="3"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
            </div>
            <button type="submit" name="update_profile" class="btn">Simpan</button>
            <button type="button" onclick="closeModal('profileModal')" class="btn btn-secondary">Batal</button>
        </form>
    </div>
</div>

<!-- Password Change Modal -->
<div id="passwordModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('passwordModal')">&times;</span>
        <h3>Ubah Password</h3>
        <form method="POST" action="<?php echo $users_path; ?>dashboard/user_dashboard.php">
            <div class="form-group">
                <label for="current_password">Password Lama:</label>
                <input type="password" name="current_password" id="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">Password Baru:</label>
                <input type="password" name="new_password" id="new_password" required>
                <small>Minimal 6 karakter</small>
            </div>
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password Baru:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            <button type="submit" name="change_password" class="btn">Ubah Password</button>
            <button type="button" onclick="closeModal('passwordModal')" class="btn btn-secondary">Batal</button>
        </form>
    </div>
</div>

<script>
// Adjust body padding based on actual navbar height
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar-header');
    if (navbar) {
        const navbarHeight = navbar.offsetHeight;
        document.body.style.paddingTop = navbarHeight + 'px';
    }
    
    // Initialize scroll progress
    updateScrollProgress();
    
    // Initialize search functionality
    initializeSearch();
});

// Handle window resize
window.addEventListener('resize', function() {
    const navbar = document.querySelector('.navbar-header');
    if (navbar) {
        const navbarHeight = navbar.offsetHeight;
        document.body.style.paddingTop = navbarHeight + 'px';
    }
});

// Scroll Progress Bar
function updateScrollProgress() {
    const scrollProgress = document.querySelector('.scroll-progress-bar');
    if (scrollProgress) {
        window.addEventListener('scroll', () => {
            const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
            const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
            const scrolled = (winScroll / height) * 100;
            scrollProgress.style.width = scrolled + '%';
        });
    }
}

// Mobile Menu Toggle
const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
const mobileNav = document.querySelector('.mobile-nav');
const mobileNavClose = document.querySelector('.mobile-nav-close');

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

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.getElementById('searchSuggestions');
    
    if (searchInput && searchSuggestions) {
        // Sample search data
        const searchData = [
            { icon: 'fas fa-map-marker-alt', text: 'Raja Ampat', type: 'Destination' },
            { icon: 'fas fa-map-marker-alt', text: 'Jayapura', type: 'Destination' },
            { icon: 'fas fa-map-marker-alt', text: 'Wamena', type: 'Destination' },
            { icon: 'fas fa-hotel', text: 'Swiss-Belhotel Papua', type: 'Hotel' },
            { icon: 'fas fa-hotel', text: 'Aston Jayapura', type: 'Hotel' },
            { icon: 'fas fa-utensils', text: 'Papuan Cuisine', type: 'Food' },
            { icon: 'fas fa-hiking', text: 'Trekking Tours', type: 'Activity' }
        ];
        
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            
            if (query.length > 0) {
                const filtered = searchData.filter(item => 
                    item.text.toLowerCase().includes(query)
                );
                
                if (filtered.length > 0) {
                    searchSuggestions.innerHTML = filtered.map(item => `
                        <div class="search-suggestion-item">
                            <i class="${item.icon}"></i>
                            <div>
                                <strong>${item.text}</strong>
                                <small style="display: block; color: #666;">${item.type}</small>
                            </div>
                        </div>
                    `).join('');
                    searchSuggestions.classList.add('active');
                } else {
                    searchSuggestions.innerHTML = `
                        <div class="search-suggestion-item">
                            <i class="fas fa-search"></i>
                            <span>No results found for "${query}"</span>
                        </div>
                    `;
                    searchSuggestions.classList.add('active');
                }
            } else {
                searchSuggestions.classList.remove('active');
            }
        });
        
        // Close suggestions on outside click
        document.addEventListener('click', function(event) {
            if (!searchInput.contains(event.target) && !searchSuggestions.contains(event.target)) {
                searchSuggestions.classList.remove('active');
            }
        });
    }
}

function toggleDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('active');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    if (!dropdown.contains(event.target)) {
        dropdown.classList.remove('active');
    }
});

// Close dropdown when opening modal
// Global flag to track modal state
window.isModalOpen = false;

function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.getElementById('profileDropdown').classList.remove('active');
    window.isModalOpen = true;
    
    // Dispatch custom event for chatbot to listen
    window.dispatchEvent(new CustomEvent('modalStateChanged', { detail: { isOpen: true } }));
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
    window.isModalOpen = false;
    
    // Dispatch custom event for chatbot to listen
    window.dispatchEvent(new CustomEvent('modalStateChanged', { detail: { isOpen: false } }));
}

// Close modal when clicking outside of it
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        window.isModalOpen = false;
        window.dispatchEvent(new CustomEvent('modalStateChanged', { detail: { isOpen: false } }));
    }
}
// Auto-hide success/error messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const messages = document.querySelectorAll('[style*="position: fixed"][style*="top: 90px"]');
    messages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }, 5000);
    });
});
</script>
