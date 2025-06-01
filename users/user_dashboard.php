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
    <link rel="stylesheet" href="userdashboard.css">
    <title>Dashboard Wisatawan - Omaki Platform</title>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>Omaki Platform</h1>
            </div>
            <div class="profile-dropdown" id="profileDropdown">
                <div class="profile-trigger" onclick="toggleDropdown()">
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
                    <span class="dropdown-arrow">▼</span>
                </div>
                <div class="dropdown-menu">
                    <button class="dropdown-item" onclick="openModal('photoModal')">
                        📷 Ubah Foto Profil
                    </button>
                    <button class="dropdown-item" onclick="openModal('profileModal')">
                        ✏️ Edit Profil
                    </button>
                    <button class="dropdown-item" onclick="openModal('passwordModal')">
                        🔒 Ubah Password
                    </button>
                    <div class="dropdown-separator"></div>
                    <a href="../logout.php" class="dropdown-item">
                        🚪 Logout
                    </a>
                </div>
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

        <div class="quick-actions">
            <h3>Aksi Cepat</h3>
            <div class="actions-grid">
                <a href="#" class="action-btn">🏝️ Jelajahi Destinasi</a>
                <a href="#" class="action-btn">🏨 Cari Akomodasi</a>
                <a href="#" class="action-btn">🍽️ Kuliner Lokal</a>
                <a href="#" class="action-btn">🎯 Tour & Aktivitas</a>
                <a href="#" class="action-btn">📱 Booking Saya</a>
                <a href="#" class="action-btn">💬 Bantuan & Support</a>
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
        function toggleDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('active');
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            // Close dropdown when opening modal
            document.getElementById('profileDropdown').classList.remove('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Close messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.opacity = '0';
                message.style.transition = 'opacity 0.5s';
                setTimeout(() => message.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>