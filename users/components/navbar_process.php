<?php
// navbar_process.php - Handles all form processing logic for navbar
// This file should be included at the very beginning of pages before any HTML output

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

// Get database connection
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

// Store user data in variables for use in navbar_display.php
$navbar_user_data = $user_data;
?>