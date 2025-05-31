<?php
session_start();
//user_dashboard
// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    // Redirect to login page if not logged in or not a regular user
    header('Location: ../login.php');
    exit();
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

require_once '../config/database.php';

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $new_name = trim($_POST['full_name']);
        $new_email = trim($_POST['email']);
        $new_phone = trim($_POST['phone']);
        $new_address = trim($_POST['address']);
        
        if (empty($new_name) || empty($new_email)) {
            $error_message = 'Nama dan email harus diisi!';
        } else {
            // Check if email already exists (excluding current user)
            $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->bind_param("si", $new_email, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_message = 'Email sudah digunakan oleh user lain!';
            } else {
                $update_stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $update_stmt->bind_param("ssssi", $new_name, $new_email, $new_phone, $new_address, $user_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['user_name'] = $new_name;
                    $_SESSION['user_email'] = $new_email;
                    $user_data['full_name'] = $new_name;
                    $user_data['email'] = $new_email;
                    $user_data['phone'] = $new_phone;
                    $user_data['address'] = $new_address;
                    $message = 'Profil berhasil diperbarui!';
                } else {
                    $error_message = 'Gagal memperbarui profil!';
                }
                $update_stmt->close();
            }
            $check_stmt->close();
        }
    }
    
    if (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Semua field password harus diisi!';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Konfirmasi password tidak cocok!';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'Password baru minimal 6 karakter!';
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
                    $message = 'Password berhasil diubah!';
                } else {
                    $error_message = 'Gagal mengubah password!';
                }
                $update_pass_stmt->close();
            } else {
                $error_message = 'Password lama tidak benar!';
            }
            $pass_stmt->close();
        }
    }
    
    if (isset($_POST['upload_photo'])) {
        // Handle photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = $_FILES['profile_photo']['type'];
            $file_size = $_FILES['profile_photo']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error_message = 'Format file tidak didukung! Gunakan JPG, PNG, atau GIF.';
            } elseif ($file_size > $max_size) {
                $error_message = 'Ukuran file terlalu besar! Maksimal 5MB.';
            } else {
                $upload_dir = '../uploads/profile_images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    // Delete old profile image if exists and not default
                    if ($user_data['profile_image'] && $user_data['profile_image'] !== 'default-user.jpg') {
                        $old_file = $upload_dir . $user_data['profile_image'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }
                    
                    // Update database
                    $photo_stmt = $db->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                    $photo_stmt->bind_param("si", $new_filename, $user_id);
                    
                    if ($photo_stmt->execute()) {
                        $user_data['profile_image'] = $new_filename;
                        $message = 'Foto profil berhasil diperbarui!';
                    } else {
                        $error_message = 'Gagal menyimpan foto profil!';
                    }
                    $photo_stmt->close();
                } else {
                    $error_message = 'Gagal mengupload foto!';
                }
            }
        } else {
            $error_message = 'Pilih file foto terlebih dahulu!';
        }
    }
}

$db->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Wisatawan - Omaki Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo h1 {
            font-size: 1.8rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            overflow: hidden;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .welcome-section h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .welcome-section p {
            color: #666;
        }
        
        .quick-actions {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .quick-actions h3 {
            color: #333;
            margin-bottom: 1.5rem;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .action-btn {
            display: block;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            transition: transform 0.3s;
            cursor: pointer;
            border: none;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .profile-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #667eea;
        }
        
        .profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-image-placeholder {
            width: 100%;
            height: 100%;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #999;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .message {
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>Omaki Platform</h1>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?php if ($user_data['profile_image'] && file_exists('../uploads/profile_images/' . $user_data['profile_image'])): ?>
                        <img src="../uploads/profile_images/<?php echo htmlspecialchars($user_data['profile_image']); ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div><?php echo htmlspecialchars($user_name); ?></div>
                    <small><?php echo htmlspecialchars($user_email); ?></small>
                </div>
                <a href="../logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="welcome-section">
            <h2>Selamat Datang, <?php echo htmlspecialchars($user_name); ?>!</h2>
            <p>Jelajahi keindahan Papua dan nikmati pengalaman wisata yang tak terlupakan.</p>
        </div>

        <div class="profile-section">
            <h3>Profil Saya</h3>
            <div class="profile-header">
                <div class="profile-image">
                    <?php if ($user_data['profile_image'] && file_exists('../uploads/profile_images/' . $user_data['profile_image'])): ?>
                        <img src="../uploads/profile_images/<?php echo htmlspecialchars($user_data['profile_image']); ?>" alt="Profile">
                    <?php else: ?>
                        <div class="profile-image-placeholder">
                            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <h4><?php echo htmlspecialchars($user_data['full_name']); ?></h4>
                    <p><?php echo htmlspecialchars($user_data['email']); ?></p>
                    <button onclick="openModal('photoModal')" class="btn">Ubah Foto</button>
                    <button onclick="openModal('profileModal')" class="btn">Edit Profil</button>
                    <button onclick="openModal('passwordModal')" class="btn btn-secondary">Ubah Password</button>
                </div>
            </div>
        </div>

        <div class="quick-actions">
            <h3>Aksi Cepat</h3>
            <div class="actions-grid">
                <a href="#" class="action-btn">🏝️ Jelajahi Destinasi</a>
                <a href="#" class="action-btn">🏨 Cari Akomodasi</a>
                <a href="#" class="action-btn">🍽️ Kuliner Lokal</a>
                <a href="#" class="action-btn">🎯 Tour & Aktivitas</a>
                <a href="#" class="action-btn">📱 Booking Saya</a>
                <a href="#" class="action-btn">⚙️ Pengaturan Profil</a>
            </div>
        </div>
    </div>

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
            <form method="POST">
                <div class="form-group">
                    <label for="full_name">Nama Lengkap:</label>
                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Nomor Telepon:</label>
                    <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
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
            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Password Lama:</label>
                    <input type="password" name="current_password" id="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Password Baru:</label>
                    <input type="password" name="new_password" id="new_password" required>
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
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>