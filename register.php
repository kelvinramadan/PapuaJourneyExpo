<?php
//register.php
session_start();
require_once 'config/database.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_type = $_POST['user_type'];
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = 'Semua field wajib diisi!';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Password dan konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password minimal 6 karakter!';
    } else {
        $db = getDbConnection();
        
        // Check if email already exists
        if ($user_type == 'user') {
            $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        } else {
            $check_stmt = $db->prepare("SELECT id FROM umkm WHERE email = ?");
        }
        
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = 'Email sudah terdaftar!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            if ($user_type == 'user') {
                $full_name = trim($_POST['full_name']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                
                if (empty($full_name)) {
                    $error_message = 'Nama lengkap harus diisi!';
                } else {
                    $stmt = $db->prepare("INSERT INTO users (email, password, full_name, phone, address) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $email, $hashed_password, $full_name, $phone, $address);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Registrasi berhasil! Silakan login.';
                    } else {
                        $error_message = 'Terjadi kesalahan saat registrasi!';
                    }
                }
            } else {
                $business_name = trim($_POST['business_name']);
                $owner_name = trim($_POST['owner_name']);
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                $business_type = $_POST['business_type'];
                $description = trim($_POST['description']);
                
                if (empty($business_name) || empty($owner_name) || empty($phone) || empty($address)) {
                    $error_message = 'Semua field wajib diisi!';
                } else {
                    $stmt = $db->prepare("INSERT INTO umkm (email, password, business_name, owner_name, phone, address, business_type, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssss", $email, $hashed_password, $business_name, $owner_name, $phone, $address, $business_type, $description);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Registrasi UMKM berhasil! Akun Anda akan diaktivasi setelah verifikasi admin.';
                    } else {
                        $error_message = 'Terjadi kesalahan saat registrasi!';
                    }
                }
            }
        }
        
        $check_stmt->close();
        if (isset($stmt)) $stmt->close();
        $db->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Papua Journey</title>
    
    <!-- Stylesheets -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
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
            --info-color: #2196F3;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
            background-color: var(--background-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .register-container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 550px;
            animation: fadeInUp 0.8s ease-out;
            margin: 2rem;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 1.5rem;
        }
        
        .logo-section img {
            height: 50px;
            width: auto;
        }
        
        .logo-section p {
            font-size: 1.8rem;
            color: var(--button-color);
            font-weight: 600;
            margin: 0;
        }
        
        .register-header h1 {
            color: var(--text-color-secondary);
            margin-bottom: 0.5rem;
            font-size: 2rem;
            font-weight: 600;
        }
        
        .register-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.25rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color-secondary);
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 2px solid var(--secondary-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: #f9f9f9;
            font-family: 'Segoe UI', Tahoma, Verdana, sans-serif;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--button-color);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(220, 155, 17, 0.1);
        }
        
        .form-icon {
            position: relative;
        }
        
        .form-icon i {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
            transition: color 0.3s ease;
            pointer-events: none;
        }
        
        .form-icon input:focus + i,
        .form-icon select:focus + i {
            color: var(--button-color);
        }
        
        .form-icon select {
            padding-right: 2.5rem;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-color: #f9f9f9;
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            background-color: var(--button-color);
            color: var(--text-color);
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 2rem;
        }
        
        .btn:hover {
            background-color: var(--button-hover-color);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(220, 155, 17, 0.3);
        }
        
        .error-message {
            background: #fee;
            color: var(--error-color);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--error-color);
            line-height: 1.5;
            font-size: 0.9rem;
            animation: shake 0.5s ease-in-out;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .success-message {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--success-color);
            line-height: 1.5;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #f0f0f0;
        }
        
        .login-link p {
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .login-link a {
            color: var(--button-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .login-link a:hover {
            color: var(--button-hover-color);
            text-decoration: underline;
        }
        
        .user-type-fields {
            display: none;
        }
        
        .user-type-fields.active {
            display: block;
            animation: fadeIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .section-divider {
            text-align: center;
            margin: 1.5rem 0;
            color: #999;
            font-size: 0.9rem;
            position: relative;
        }
        
        .section-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--secondary-color);
            z-index: 0;
        }
        
        .section-divider span {
            background: white;
            padding: 0 1rem;
            position: relative;
            z-index: 1;
        }
        
        .home-link {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: var(--text-color-secondary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .home-link:hover {
            color: var(--button-color);
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .password-strength.weak {
            color: var(--error-color);
        }
        
        .password-strength.medium {
            color: #ff9800;
        }
        
        .password-strength.strong {
            color: var(--success-color);
        }
        
        @media (max-width: 768px) {
            .register-container {
                padding: 2rem;
                margin: 1rem;
            }
            
            .home-link {
                top: 1rem;
                left: 1rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="home-link">
        <i class="fas fa-arrow-left"></i>
        Kembali ke Beranda
    </a>
    
    <div class="register-container">
        <div class="register-header">
            <div class="logo-section">
                <img src="assets/logo.png" alt="Papua Journey Logo">
                <p>Journey</p>
            </div>
            <h1>Bergabunglah</h1>
            <p>Buat akun Papua Journey baru</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-times-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="user_type">Tipe Akun</label>
                <div class="form-icon">
                    <select name="user_type" id="user_type" required onchange="toggleUserFields()">
                        <option value="">Pilih Tipe Akun</option>
                        <option value="user" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'user') ? 'selected' : ''; ?>>Wisatawan</option>
                        <option value="umkm" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'umkm') ? 'selected' : ''; ?>>UMKM</option>
                    </select>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            
            <div class="section-divider">
                <span>Informasi Akun</span>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <div class="form-icon">
                    <input type="email" name="email" id="email" placeholder="Masukkan email Anda" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="form-icon">
                    <input type="password" name="password" id="password" placeholder="Minimal 6 karakter" required onkeyup="checkPasswordStrength()">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="password-strength" id="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <div class="form-icon">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Ulangi password" required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <!-- User Fields -->
            <div id="user-fields" class="user-type-fields <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'user') ? 'active' : ''; ?>">
                <div class="section-divider">
                    <span>Informasi Pribadi</span>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Nama Lengkap</label>
                    <div class="form-icon">
                        <input type="text" name="full_name" id="full_name" placeholder="Masukkan nama lengkap"
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                        <i class="fas fa-user"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="user_phone">Nomor Telepon</label>
                    <div class="form-icon">
                        <input type="text" name="phone" id="user_phone" placeholder="Contoh: 081234567890"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        <i class="fas fa-phone"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="user_address">Alamat</label>
                    <textarea name="address" id="user_address" placeholder="Masukkan alamat lengkap"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
            </div>
            
            <!-- UMKM Fields -->
            <div id="umkm-fields" class="user-type-fields <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'umkm') ? 'active' : ''; ?>">
                <div class="section-divider">
                    <span>Informasi Usaha</span>
                </div>
                
                <div class="form-group">
                    <label for="business_name">Nama Usaha</label>
                    <div class="form-icon">
                        <input type="text" name="business_name" id="business_name" placeholder="Masukkan nama usaha"
                               value="<?php echo isset($_POST['business_name']) ? htmlspecialchars($_POST['business_name']) : ''; ?>">
                        <i class="fas fa-store"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="owner_name">Nama Pemilik</label>
                    <div class="form-icon">
                        <input type="text" name="owner_name" id="owner_name" placeholder="Masukkan nama pemilik"
                               value="<?php echo isset($_POST['owner_name']) ? htmlspecialchars($_POST['owner_name']) : ''; ?>">
                        <i class="fas fa-user-tie"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="umkm_phone">Nomor Telepon</label>
                    <div class="form-icon">
                        <input type="text" name="phone" id="umkm_phone" placeholder="Contoh: 081234567890"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                        <i class="fas fa-phone"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="umkm_address">Alamat Usaha</label>
                    <textarea name="address" id="umkm_address" placeholder="Masukkan alamat lengkap usaha"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="business_type">Jenis Usaha</label>
                    <div class="form-icon">
                        <select name="business_type" id="business_type">
                            <option value="jasa" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'jasa') ? 'selected' : ''; ?>>Jasa</option>
                            <option value="event" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'event') ? 'selected' : ''; ?>>Event</option>
                            <option value="kuliner" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'kuliner') ? 'selected' : ''; ?>>Kuliner</option>
                            <option value="kerajinan" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'kerajinan') ? 'selected' : ''; ?>>Kerajinan</option>
                            <option value="wisata" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'wisata') ? 'selected' : ''; ?>>Wisata</option>
                        </select>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Deskripsi Usaha</label>
                    <textarea name="description" id="description" placeholder="Ceritakan tentang usaha Anda"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-user-plus"></i>
                Daftar Sekarang
            </button>
        </form>
        
        <div class="login-link">
            <p>Sudah punya akun?</p>
            <a href="login.php">Login di sini</a>
        </div>
    </div>
    
    <script>
        function toggleUserFields() {
            const userType = document.getElementById('user_type').value;
            const userFields = document.getElementById('user-fields');
            const umkmFields = document.getElementById('umkm-fields');
            
            if (userType === 'user') {
                userFields.classList.add('active');
                umkmFields.classList.remove('active');
                
                // Enable/disable fields based on visibility
                document.querySelectorAll('#user-fields input, #user-fields textarea').forEach(el => {
                    el.disabled = false;
                    if (el.name === 'full_name') {
                        el.required = true;
                    }
                });
                
                document.querySelectorAll('#umkm-fields input, #umkm-fields select, #umkm-fields textarea').forEach(el => {
                    el.disabled = true;
                    el.required = false;
                });
                
            } else if (userType === 'umkm') {
                userFields.classList.remove('active');
                umkmFields.classList.add('active');
                
                // Enable/disable fields based on visibility
                document.querySelectorAll('#user-fields input, #user-fields textarea').forEach(el => {
                    el.disabled = true;
                    el.required = false;
                });
                
                document.querySelectorAll('#umkm-fields input, #umkm-fields select, #umkm-fields textarea').forEach(el => {
                    el.disabled = false;
                    if (el.name !== 'description') {
                        el.required = true;
                    }
                });
                
            } else {
                userFields.classList.remove('active');
                umkmFields.classList.remove('active');
                
                // Disable all fields when no type selected
                document.querySelectorAll('#user-fields input, #user-fields textarea, #umkm-fields input, #umkm-fields select, #umkm-fields textarea').forEach(el => {
                    el.disabled = true;
                    el.required = false;
                });
            }
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthElement = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthElement.innerHTML = '';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            if (strength < 2) {
                strengthElement.className = 'password-strength weak';
                strengthElement.innerHTML = '<i class="fas fa-times-circle"></i> Password lemah';
            } else if (strength < 4) {
                strengthElement.className = 'password-strength medium';
                strengthElement.innerHTML = '<i class="fas fa-exclamation-circle"></i> Password sedang';
            } else {
                strengthElement.className = 'password-strength strong';
                strengthElement.innerHTML = '<i class="fas fa-check-circle"></i> Password kuat';
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleUserFields();
        });
    </script>
</body>
</html>