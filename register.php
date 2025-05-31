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
    <title>Register - Omaki Platform</title>
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
            padding: 2rem 0;
        }
        
        .register-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
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
            height: 80px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
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
        }
        
        .success-message {
            background: #efe;
            color: #3c3;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #3c3;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .user-type-fields {
            display: none;
        }
        
        .user-type-fields.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Register</h1>
            <p>Buat akun Omaki baru</p>
        </div>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="user_type">Tipe Akun:</label>
                <select name="user_type" id="user_type" required onchange="toggleUserFields()">
                    <option value="">Pilih Tipe Akun</option>
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
            
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            
            <!-- User Fields -->
            <div id="user-fields" class="user-type-fields <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'user') ? 'active' : ''; ?>">
                <div class="form-group">
                    <label for="full_name">Nama Lengkap:</label>
                    <input type="text" name="full_name" id="full_name" 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="user_phone">Nomor Telepon:</label>
                    <input type="text" name="phone" id="user_phone" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="user_address">Alamat:</label>
                    <textarea name="address" id="user_address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
            </div>
            
            <!-- UMKM Fields -->
            <div id="umkm-fields" class="user-type-fields <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'umkm') ? 'active' : ''; ?>">
                <div class="form-group">
                    <label for="business_name">Nama Usaha:</label>
                    <input type="text" name="business_name" id="business_name" 
                           value="<?php echo isset($_POST['business_name']) ? htmlspecialchars($_POST['business_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="owner_name">Nama Pemilik:</label>
                    <input type="text" name="owner_name" id="owner_name" 
                           value="<?php echo isset($_POST['owner_name']) ? htmlspecialchars($_POST['owner_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="umkm_phone">Nomor Telepon:</label>
                    <input type="text" name="phone" id="umkm_phone" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="umkm_address">Alamat Usaha:</label>
                    <textarea name="address" id="umkm_address"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="business_type">Jenis Usaha:</label>
                    <select name="business_type" id="business_type">
                        <option value="jasa" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'jasa') ? 'selected' : ''; ?>>Jasa</option>
                        <option value="event" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'event') ? 'selected' : ''; ?>>Event</option>
                        <option value="kuliner" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'kuliner') ? 'selected' : ''; ?>>Kuliner</option>
                        <option value="kerajinan" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'kerajinan') ? 'selected' : ''; ?>>Kerajinan</option>
                        <option value="wisata" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'wisata') ? 'selected' : ''; ?>>Wisata</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Deskripsi Usaha:</label>
                    <textarea name="description" id="description"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
            </div>
            
            <button type="submit" class="btn">Register</button>
        </form>
        
        <div class="login-link">
            <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
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
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleUserFields();
        });
    </script>
</body>
</html>