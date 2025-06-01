<?php
// umkm/umkm_dashboard.php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is UMKM
if (!isset($_SESSION['umkm_id']) || $_SESSION['user_type'] != 'umkm') {
    header('Location: ../login.php');
    exit();
}

$db = getDbConnection();
$umkm_id = $_SESSION['umkm_id'];
$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update basic profile information
        $business_name = trim($_POST['business_name']);
        $owner_name = trim($_POST['owner_name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $business_type = $_POST['business_type'];
        $description = trim($_POST['description']);
        
        if (empty($business_name) || empty($owner_name) || empty($phone) || empty($address)) {
            $error_message = 'Semua field wajib diisi!';
        } else {
            $stmt = $db->prepare("UPDATE umkm SET business_name = ?, owner_name = ?, phone = ?, address = ?, business_type = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->bind_param("ssssssi", $business_name, $owner_name, $phone, $address, $business_type, $description, $umkm_id);
            
            if ($stmt->execute()) {
                $_SESSION['umkm_name'] = $business_name; // Update session
                $success_message = 'Profil berhasil diperbarui!';
            } else {
                $error_message = 'Terjadi kesalahan saat memperbarui profil!';
            }
            $stmt->close();
        }
    }
    
    elseif (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Semua field password harus diisi!';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Password baru dan konfirmasi tidak cocok!';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'Password baru minimal 6 karakter!';
        } else {
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM umkm WHERE id = ?");
            $stmt->bind_param("i", $umkm_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if (password_verify($current_password, $row['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $db->prepare("UPDATE umkm SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_password, $umkm_id);
                
                if ($update_stmt->execute()) {
                    $success_message = 'Password berhasil diubah!';
                } else {
                    $error_message = 'Terjadi kesalahan saat mengubah password!';
                }
                $update_stmt->close();
            } else {
                $error_message = 'Password saat ini salah!';
            }
            $stmt->close();
        }
    }
    
    elseif (isset($_POST['upload_image'])) {
        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = $_FILES['profile_image']['type'];
            $file_size = $_FILES['profile_image']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error_message = 'Hanya file JPG, PNG, dan GIF yang diperbolehkan!';
            } elseif ($file_size > $max_size) {
                $error_message = 'Ukuran file maksimal 5MB!';
            } else {
                $upload_dir = '../uploads/profile_images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                $file_name = 'umkm_' . $umkm_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Delete old profile image if exists
                    $stmt = $db->prepare("SELECT profile_image FROM umkm WHERE id = ?");
                    $stmt->bind_param("i", $umkm_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    
                    if ($row['profile_image'] && $row['profile_image'] != 'default-umkm.jpg') {
                        $old_image_path = $upload_dir . $row['profile_image'];
                        if (file_exists($old_image_path)) {
                            unlink($old_image_path);
                        }
                    }
                    
                    // Update database
                    $update_stmt = $db->prepare("UPDATE umkm SET profile_image = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $update_stmt->bind_param("si", $file_name, $umkm_id);
                    
                    if ($update_stmt->execute()) {
                        $success_message = 'Foto profil berhasil diperbarui!';
                    } else {
                        $error_message = 'Terjadi kesalahan saat memperbarui foto profil!';
                    }
                    $update_stmt->close();
                    $stmt->close();
                } else {
                    $error_message = 'Gagal mengupload file!';
                }
            }
        } else {
            $error_message = 'Pilih file gambar terlebih dahulu!';
        }
    }
}

// Get UMKM data
$stmt = $db->prepare("SELECT * FROM umkm WHERE id = ?");
$stmt->bind_param("i", $umkm_id);
$stmt->execute();
$result = $stmt->get_result();
$umkm_data = $result->fetch_assoc();
$stmt->close();
$db->close();

// Set default profile image if not exists
$profile_image = $umkm_data['profile_image'] ? $umkm_data['profile_image'] : 'default-umkm.jpg';
$profile_image_path = '../uploads/profile_images/' . $profile_image;
if (!file_exists($profile_image_path)) {
    $profile_image_path = '../uploads/profile_images/default-umkm.jpg';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="umkm.css">
    <title>Dashboard UMKM - <?php echo htmlspecialchars($umkm_data['business_name']); ?></title>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Dashboard UMKM</h1>
            <div class="header-right">
                <span class="status-badge status-<?php echo $umkm_data['status']; ?>">
                    <?php echo ucfirst($umkm_data['status']); ?>
                </span>
                
                <!-- Profile Dropdown -->
                <div class="profile-dropdown" id="profileDropdown">
                    <button class="profile-toggle" onclick="toggleDropdown()">
                        <img src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="Profile" class="profile-avatar">
                        <span><?php echo htmlspecialchars($umkm_data['business_name']); ?></span>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    
                    <div class="dropdown-menu">
                        <div class="dropdown-header">
                            <img src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="Profile">
                            <h4><?php echo htmlspecialchars($umkm_data['business_name']); ?></h4>
                            <p><?php echo htmlspecialchars($umkm_data['email']); ?></p>
                        </div>
                        
                        <button class="dropdown-item" onclick="openImageModal()">
                            <i>📷</i> Ubah Foto Profil
                        </button>
                        
                        <button class="dropdown-item" onclick="openProfileModal()">
                            <i>👤</i> Edit Profil
                        </button>
                        
                        <button class="dropdown-item" onclick="openPasswordModal()">
                            <i>🔒</i> Ubah Password
                        </button>
                        
                        <div class="dropdown-divider"></div>
                        
                        <a href="../logout.php" class="dropdown-item logout-btn">
                            <i>🚪</i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-content">
            <!-- Business Information Card -->
            <div class="card">
                <h2>Informasi Usaha</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Nama Usaha</strong>
                        <?php echo htmlspecialchars($umkm_data['business_name']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Pemilik</strong>
                        <?php echo htmlspecialchars($umkm_data['owner_name']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Telepon</strong>
                        <?php echo htmlspecialchars($umkm_data['phone']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Jenis Usaha</strong>
                        <?php echo ucfirst($umkm_data['business_type']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Alamat</strong>
                        <?php echo htmlspecialchars($umkm_data['address']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Deskripsi</strong>
                        <?php echo $umkm_data['description'] ? htmlspecialchars($umkm_data['description']) : 'Belum ada deskripsi'; ?>
                    </div>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="card">
                <h2>Informasi Akun</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Email</strong>
                        <?php echo htmlspecialchars($umkm_data['email']); ?>
                    </div>
                    <div class="info-item">
                        <strong>ID UMKM</strong>
                        #<?php echo $umkm_data['id']; ?>
                    </div>
                    <div class="info-item">
                        <strong>Terdaftar Sejak</strong>
                        <?php echo date('d M Y', strtotime($umkm_data['created_at'])); ?>
                    </div>
                    <div class="info-item">
                        <strong>Terakhir Diperbarui</strong>
                        <?php echo $umkm_data['updated_at'] ? date('d M Y H:i', strtotime($umkm_data['updated_at'])) : 'Belum pernah'; ?>
                    </div>
                    <div class="info-item">
                        <strong>Status Verifikasi</strong>
                        <?php if ($umkm_data['status'] == 'pending'): ?>
                            <span style="color: #FF9800;">Menunggu persetujuan admin</span>
                        <?php elseif ($umkm_data['status'] == 'active'): ?>
                            <span style="color: #4CAF50;">Akun aktif dan terverifikasi</span>
                        <?php else: ?>
                            <span style="color: #f44336;">Akun tidak aktif</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Image Upload Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ubah Foto Profil</h3>
                <button class="close" onclick="closeImageModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="imageForm">
                    <div class="image-upload-section">
                        <img src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="Current Profile" class="current-image" id="previewImage">
                        
                        <div class="file-input-wrapper">
                            <input type="file" name="profile_image" id="imageInput" class="file-input" accept="image/*" onchange="previewImageFile(this)">
                            <div class="file-input-btn">
                                📁 Pilih Foto Baru
                            </div>
                        </div>
                        
                        <p style="color: #666; font-size: 0.9rem; margin-top: 0.5rem;">
                            Format: JPG, PNG, GIF. Maksimal 5MB
                        </p>
                    </div>
                    
                    <button type="submit" name="upload_image" class="btn">Upload Foto</button>
                    <button type="button" class="btn btn-secondary" onclick="closeImageModal()">Batal</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Profile Edit Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Profil</h3>
                <button class="close" onclick="closeProfileModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="business_name">Nama Usaha:</label>
                        <input type="text" name="business_name" id="business_name" required 
                               value="<?php echo htmlspecialchars($umkm_data['business_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="owner_name">Nama Pemilik:</label>
                        <input type="text" name="owner_name" id="owner_name" required 
                               value="<?php echo htmlspecialchars($umkm_data['owner_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Nomor Telepon:</label>
                        <input type="text" name="phone" id="phone" required 
                               value="<?php echo htmlspecialchars($umkm_data['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Alamat Usaha:</label>
                        <textarea name="address" id="address" required><?php echo htmlspecialchars($umkm_data['address']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="business_type">Jenis Usaha:</label>
                        <select name="business_type" id="business_type" required>
                            <option value="jasa" <?php echo ($umkm_data['business_type'] == 'jasa') ? 'selected' : ''; ?>>Jasa</option>
                            <option value="event" <?php echo ($umkm_data['business_type'] == 'event') ? 'selected' : ''; ?>>Event</option>
                            <option value="kuliner" <?php echo ($umkm_data['business_type'] == 'kuliner') ? 'selected' : ''; ?>>Kuliner</option>
                            <option value="kerajinan" <?php echo ($umkm_data['business_type'] == 'kerajinan') ? 'selected' : ''; ?>>Kerajinan</option>
                            <option value="wisata" <?php echo ($umkm_data['business_type'] == 'wisata') ? 'selected' : ''; ?>>Wisata</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Deskripsi Usaha:</label>
                        <textarea name="description" id="description"><?php echo htmlspecialchars($umkm_data['description']); ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn">Perbarui Profil</button>
                    <button type="button" class="btn btn-secondary" onclick="closeProfileModal()">Batal</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Password Change Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ubah Password</h3>
                <button class="close" onclick="closePasswordModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Password Saat Ini:</label>
                        <input type="password" name="current_password" id="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Password Baru:</label>
                        <input type="password" name="new_password" id="new_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Konfirmasi Password Baru:</label>
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
                    </div>
                    
                    <button type="submit" name="change_password" class="btn">Ubah Password</button>
                    <button type="button" class="btn btn-secondary" onclick="closePasswordModal()">Batal</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Dropdown functionality
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
        
        // Modal functions
        function openImageModal() {
            document.getElementById('imageModal').style.display = 'block';
            setTimeout(() => {
                document.getElementById('imageModal').classList.add('active');
            }, 10);
            document.getElementById('profileDropdown').classList.remove('active');
        }
        
        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
        
        function openProfileModal() {
            document.getElementById('profileModal').style.display = 'block';
            setTimeout(() => {
                document.getElementById('profileModal').classList.add('active');
            }, 10);
            document.getElementById('profileDropdown').classList.remove('active');
        }
        
        function closeProfileModal() {
            const modal = document.getElementById('profileModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
        
        function openPasswordModal() {
            document.getElementById('passwordModal').style.display = 'block';
            setTimeout(() => {
                document.getElementById('passwordModal').classList.add('active');
            }, 10);
            document.getElementById('profileDropdown').classList.remove('active');
        }
        
        function closePasswordModal() {
            const modal = document.getElementById('passwordModal');
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
        
        // Image preview functionality
        function previewImageFile(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImage').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Password tidak cocok');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const imageModal = document.getElementById('imageModal');
            const profileModal = document.getElementById('profileModal');
            const passwordModal = document.getElementById('passwordModal');
            
            if (event.target == imageModal) {
                closeImageModal();
            } else if (event.target == profileModal) {
                closeProfileModal();
            } else if (event.target == passwordModal) {
                closePasswordModal();
            }
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            // Phone number validation
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    // Remove non-digit characters except + and -
                    this.value = this.value.replace(/[^\d+\-\s]/g, '');
                });
            }
            
            // Business name validation
            const businessNameInput = document.getElementById('business_name');
            if (businessNameInput) {
                businessNameInput.addEventListener('input', function() {
                    if (this.value.length < 3) {
                        this.setCustomValidity('Nama usaha minimal 3 karakter');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        });
        
        // Auto-close alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 300);
                }, 5000);
            });
        });
        
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>