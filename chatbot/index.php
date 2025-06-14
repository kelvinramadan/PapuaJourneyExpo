<?php
session_start();

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
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

// Handle Profile Photo Upload
if (isset($_POST['upload_photo'])) {
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (in_array($_FILES['profile_photo']['type'], $allowed_types) && $_FILES['profile_photo']['size'] <= $max_size) {
            $upload_dir = '../uploads/profile_images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $db = getDbConnection();
                $stmt = $db->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $stmt->bind_param("si", $filename, $user_id);
                
                if ($stmt->execute()) {
                    $message = "Foto profil berhasil diupload!";
                } else {
                    $error_message = "Gagal mengupdate foto profil di database.";
                }
                $stmt->close();
                $db->close();
            } else {
                $error_message = "Gagal mengupload foto.";
            }
        } else {
            $error_message = "File tidak valid. Pastikan format JPG/PNG/GIF dan ukuran maksimal 5MB.";
        }
    } else {
        $error_message = "Pilih file foto terlebih dahulu.";
    }
}

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if (!empty($full_name) && !empty($email)) {
        $db = getDbConnection();
        $stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
            $user_name = $full_name;
            $user_email = $email;
            $message = "Profil berhasil diupdate!";
        } else {
            $error_message = "Gagal mengupdate profil.";
        }
        $stmt->close();
        $db->close();
    } else {
        $error_message = "Nama lengkap dan email wajib diisi.";
    }
}

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();

        if ($user_data && password_verify($current_password, $user_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($update_stmt->execute()) {
                $message = "Password berhasil diubah!";
            } else {
                $error_message = "Gagal mengubah password.";
            }
            $update_stmt->close();
        } else {
            $error_message = "Password lama tidak benar.";
        }
        $stmt->close();
        $db->close();
    } else {
        $error_message = "Konfirmasi password tidak cocok.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant - Omaki Platform</title>
    <link rel="stylesheet" href="chatbot.css">
</head>
<body>    <?php include '../users/navbar.php'; ?>
    
    <?php if ($message): ?>
        <div class="message success" style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 1rem; text-align: center; margin: 1rem; border-radius: 10px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="message error" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 1rem; text-align: center; margin: 1rem; border-radius: 10px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <div class="container">
        <div class="chat-header">
            <div class="chat-header-content">
                <div class="chat-title">
                    <h2>🤖 AI Assistant Papua</h2>
                    <p>Tanyakan apapun tentang wisata, kuliner, dan budaya Papua</p>
                </div>
                <div class="chat-status">
                    <div class="status-indicator online"></div>
                    <span>Online</span>
                </div>
            </div>
        </div>
        
        <div class="chat-container">
            <div class="chat-box" id="chat-box">
                <div class="welcome-message">
                    <div class="bot-avatar">
                        🤖
                    </div>
                    <div class="message-content">
                        <div class="message-bubble bot-message">
                            <p>Selamat datang di AI Assistant Papua! 👋</p>
                            <p>Saya siap membantu Anda menjelajahi keindahan Papua. Anda bisa bertanya tentang:</p>
                            <ul>
                                <li>🏝️ Destinasi wisata menarik</li>
                                <li>🍽️ Kuliner khas Papua</li>
                                <li>🎭 Budaya dan tradisi lokal</li>
                                <li>🚗 Transportasi dan akomodasi</li>
                            </ul>
                            <p>Silakan ajukan pertanyaan Anda!</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="chat-input-container">
                <div class="chat-input">
                    <input type="text" id="user-input" placeholder="Ketik pertanyaan Anda tentang Papua..." autocomplete="off">
                    <button id="send-btn" type="button">
                        <span class="send-icon">📤</span>
                    </button>
                </div>
                <div class="quick-suggestions">
                    <button class="suggestion-btn" onclick="sendQuickMessage('Apa saja tempat wisata populer di Jayapura?')">
                        🏝️ Wisata Jayapura
                    </button>
                    <button class="suggestion-btn" onclick="sendQuickMessage('Rekomendasi kuliner khas Papua')">
                        🍽️ Kuliner Papua
                    </button>
                    <button class="suggestion-btn" onclick="sendQuickMessage('Bagaimana transportasi di Papua?')">
                        🚗 Transportasi
                    </button>
                </div>
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
                    <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Nomor Telepon:</label>
                    <input type="text" name="phone" id="phone" value="">
                </div>
                <div class="form-group">
                    <label for="address">Alamat:</label>
                    <textarea name="address" id="address" rows="3"></textarea>
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

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
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
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #667eea;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group small {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .btn {
            padding: 1rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 0.5rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 5px 20px rgba(16, 185, 129, 0.3);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .modal h3 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #374151;
            font-size: 1.5rem;
        }
    </style>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            // Close dropdown when opening modal
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) {
                dropdown.classList.remove('active');
            }
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

        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.opacity = '0';
                message.style.transition = 'opacity 0.5s';
                setTimeout(() => message.remove(), 500);
            });
        }, 5000);
    </script>

    <script src="chatbot.js"></script>
</body>
</html>
