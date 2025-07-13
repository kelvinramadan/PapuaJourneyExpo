<?php
//login.php
ini_set('session.gc_maxlifetime', 28800); 
ini_set('session.cookie_lifetime', 28800); 
session_set_cookie_params(28800); 

session_start();
require_once 'config/database.php';

$error_message = '';
$success_message = '';

// Check for success message from registration
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after displaying
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type']; // 'user' or 'umkm'
    
    if (empty($email) || empty($password)) {
        $error_message = 'Email dan password harus diisi!';
    } else {
        $db = getDbConnection();
        
        if ($user_type == 'user') {
            $stmt = $db->prepare("SELECT id, email, password, full_name FROM users WHERE email = ?");
        } else {
            $stmt = $db->prepare("SELECT id, email, password, business_name, status FROM umkm WHERE email = ?");
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            
            if (password_verify($password, $row['password'])) {
                if ($user_type == 'user') {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_email'] = $row['email'];
                    $_SESSION['user_name'] = $row['full_name'];
                    $_SESSION['user_type'] = 'user';
                    header('Location: index.php');
                    exit();
                } else {
                    // Enhanced status checking for UMKM
                    switch ($row['status']) {
                        case 'active':
                            $_SESSION['umkm_id'] = $row['id'];
                            $_SESSION['umkm_email'] = $row['email'];
                            $_SESSION['umkm_name'] = $row['business_name'];
                            $_SESSION['user_type'] = 'umkm';
                            header('Location: umkm/umkm_dashboard.php');
                            exit();
                            break;
                        
                        case 'pending':
                            $error_message = 'Akun UMKM Anda masih dalam proses verifikasi. Silakan tunggu persetujuan dari administrator. Anda akan menerima notifikasi melalui email setelah akun disetujui.';
                            break;
                        
                        case 'inactive':
                            $error_message = 'Akun UMKM Anda telah dinonaktifkan oleh administrator. Silakan hubungi administrator untuk informasi lebih lanjut.';
                            break;
                        
                        default:
                            $error_message = 'Status akun UMKM Anda tidak valid. Silakan hubungi administrator.';
                            break;
                    }
                }
            } else {
                $error_message = 'Email, password salah!';
            }
        } else {
            $error_message = 'Email atau password salah!';
        }
        
        $stmt->close();
        $db->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Papua Journey</title>
    
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
            padding: 2rem;
        }
        
        .login-container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            animation: fadeInUp 0.8s ease-out;
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
        
        .login-header {
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
        
        .login-header h1 {
            color: var(--text-color-secondary);
            margin-bottom: 0.5rem;
            font-size: 2rem;
            font-weight: 600;
        }
        
        .login-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color-secondary);
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            height: 48px;
            border: 2px solid var(--secondary-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background-color: #ffffff;
        }
        
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            height: 48px;
            border: 2px solid var(--secondary-color);
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: var(--text-color-secondary);
            background-color: #ffffff;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .form-group input:hover {
            border-color: var(--button-color);
            background-color: #fafafa;
        }
        
        .form-group select:hover {
            border-color: var(--button-color);
            background-color: #fafafa;
        }
        
        .form-group input:focus,
        .form-group select:focus {
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
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .warning-message {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #ffc107;
            line-height: 1.5;
            font-size: 0.9rem;
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
            animation: fadeInDown 0.5s ease-out;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .register-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #f0f0f0;
        }
        
        .register-link p {
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .register-link a {
            color: var(--button-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .register-link a:hover {
            color: var(--button-hover-color);
            text-decoration: underline;
        }
        
        .status-info {
            background: rgba(83, 98, 69, 0.1);
            color: var(--primary-color);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
            font-size: 0.9rem;
            display: none;
            line-height: 1.5;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
            color: #999;
            font-size: 0.9rem;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--secondary-color);
        }
        
        .divider span {
            padding: 0 1rem;
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
        
        @media (max-width: 768px) {
            .login-container {
                padding: 2rem;
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
    
    <div class="login-container">
        <div class="login-header">
            <div class="logo-section">
                <img src="assets/logo.png" alt="Papua Journey Logo">
                <p>Journey</p>
            </div>
            <h1>Selamat Datang</h1>
            <p>Masuk ke akun Papua Journey Anda</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <?php if (strpos($error_message, 'verifikasi') !== false): ?>
                <div class="warning-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php else: ?>
                <div class="error-message">
                    <i class="fas fa-times-circle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="status-info" id="umkm-info">
            <i class="fas fa-info-circle"></i>
            <strong>Catatan untuk UMKM:</strong><br>
            Akun UMKM harus disetujui oleh administrator sebelum dapat digunakan untuk login.
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="user_type">Tipe Akun</label>
                <div class="form-icon">
                    <select name="user_type" id="user_type" required>
                        <option value="user" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'user') ? 'selected' : ''; ?>>Wisatawan</option>
                        <option value="umkm" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'umkm') ? 'selected' : ''; ?>>UMKM</option>
                    </select>
                    <i class="fas fa-chevron-down"></i>
                </div>
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
                    <input type="password" name="password" id="password" placeholder="Masukkan password Anda" required>
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sign-in-alt"></i>
                Login
            </button>
        </form>
        
        <div class="register-link">
            <p>Belum punya akun?</p>
            <a href="register.php">Daftar Sekarang</a>
        </div>
    </div>
    
    <script>
        function toggleUMKMInfo() {
            const userType = document.getElementById('user_type').value;
            const umkmInfo = document.getElementById('umkm-info');
            
            if (userType === 'umkm') {
                umkmInfo.style.display = 'block';
            } else {
                umkmInfo.style.display = 'none';
            }
        }
        
        // Show/hide status info based on user type selection
        document.getElementById('user_type').addEventListener('change', toggleUMKMInfo);
        
        // Set initial state on page load
        document.addEventListener('DOMContentLoaded', toggleUMKMInfo);
    </script>
</body>
</html>