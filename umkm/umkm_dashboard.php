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
    <title>Dashboard UMKM - <?php echo htmlspecialchars($umkm_data['business_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.8rem;
        }
        
        .header-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-active {
            background: #4CAF50;
            color: white;
        }
        
        .status-pending {
            background: #FF9800;
            color: white;
        }
        
        .status-inactive {
            background: #f44336;
            color: white;
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
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .card h2 {
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
        }
        
        .profile-section {
            text-align: center;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #667eea;
            margin-bottom: 1rem;
        }
        
        .upload-btn {
            background: #667eea;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0.5rem;
        }
        
        .upload-btn:hover {
            background: #5a67d8;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        
        .info-item strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }
        
        .close {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }
        
        .close:hover {
            color: #333;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Dashboard UMKM</h1>
            <div class="header-info">
                <span class="status-badge status-<?php echo $umkm_data['status']; ?>">
                    <?php echo ucfirst($umkm_data['status']); ?>
                </span>
                <span>Halo, <?php echo htmlspecialchars($umkm_data['business_name']); ?></span>
                <a href="../logout.php" class="logout-btn">Logout</a>
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
        
        <div class="dashboard-grid">
            <!-- Profile Section -->
            <div class="card profile-section">
                <h2>Foto Profil</h2>
                <img src="<?php echo htmlspecialchars($profile_image_path); ?>" alt="Profile" class="profile-image" id="profileImage">
                
                <form method="POST" enctype="multipart/form-data" id="uploadForm" style="display: none;">
                    <input type="file" name="profile_image" id="imageInput" accept="image/*" onchange="previewImage(this)">
                    <button type="submit" name="upload_image" class="upload-btn">Upload</button>
                </form>
                
                <div>
                    <button class="upload-btn" onclick="document.getElementById('imageInput').click()">Pilih Foto</button>
                    <button class="upload-btn btn-secondary" onclick="changePassword()">Ubah Password</button>
                </div>
                
                <div class="info-grid" style="margin-top: 2rem; text-align: left;">
                    <div class="info-item">
                        <strong>Status Akun</strong>
                        <span class="status-badge status-<?php echo $umkm_data['status']; ?>">
                            <?php echo ucfirst($umkm_data['status']); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <strong>Terdaftar Sejak</strong>
                        <?php echo date('d M Y', strtotime($umkm_data['created_at'])); ?>
                    </div>
                </div>
            </div>
            
            <!-- Business Info -->
            <div class="card">
                <h2>Informasi Usaha</h2>
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
                </form>
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
    
    <!-- Password Change Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePasswordModal()">&times;</span>
            <h2>Ubah Password</h2>
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
    
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                    document.getElementById('uploadForm').style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function changePassword() {
            document.getElementById('passwordModal').style.display = 'block';
        }
        
        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('passwordModal');
            if (event.target == modal) {
                modal.style.display = 'none';
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
    </script>
</body>
</html>