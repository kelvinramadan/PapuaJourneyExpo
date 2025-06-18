<?php
//login.php
ini_set('session.gc_maxlifetime', 28800); 
ini_set('session.cookie_lifetime', 28800); 
session_set_cookie_params(28800); 

session_start();
require_once 'config/database.php';

$error_message = '';

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
                    header('Location: users/dashboard/user_dashboard.php');
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
    <title>Login - Omaki Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
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
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #c33;
            line-height: 1.4;
        }
        
        .warning-message {
            background: #fff3cd;
            color: #856404;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #ffc107;
            line-height: 1.4;
        }
        
        .register-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .status-info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #17a2b8;
            font-size: 0.9rem;
            display: none; /* Hidden by default, shown only for UMKM */
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Login</h1>
            <p>Masuk ke akun Omaki Anda</p>
        </div>
        
        <?php if ($error_message): ?>
            <?php if (strpos($error_message, 'verifikasi') !== false): ?>
                <div class="warning-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php else: ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="status-info" id="umkm-info">
            <strong>Catatan untuk UMKM:</strong><br>
            Akun UMKM harus disetujui oleh administrator sebelum dapat digunakan untuk login.
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="user_type">Tipe Akun:</label>
                <select name="user_type" id="user_type" required>
                    <option value="user" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'user') ? 'selected' : ''; ?>>Wisatawan</option>
                    <option value="umkm" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'umkm') ? 'selected' : ''; ?>>UMKM</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="register-link">
            <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
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