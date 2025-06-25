<?php
session_start();

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../../login.php');
    exit();
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

require_once '../../config/database.php';

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

// Get active section from URL parameter
$active_section = isset($_GET['section']) ? $_GET['section'] : 'profile';

// Check if this is an AJAX request
$is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    if (empty($full_name) || empty($email)) {
        $error_message = 'Full name and email are required!';
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        }
    } else {
        // Check if email already exists (excluding current user)
        $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = 'Email is already used by another user!';
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error_message]);
                exit();
            }
        } else {
            $update_stmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            $update_stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $user_id);
            
            if ($update_stmt->execute()) {
                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_email'] = $email;
                $message = 'Profile updated successfully!';
                
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $message]);
                    exit();
                }
                
                // Refresh user data
                $stmt = $db->prepare("SELECT full_name, email, phone, address, profile_image FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_data = $result->fetch_assoc();
                $stmt->close();
            } else {
                $error_message = 'Failed to update profile!';
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error_message]);
                    exit();
                }
            }
            $update_stmt->close();
        }
        $check_stmt->close();
    }
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_photo'])) {
    $upload_error_messages = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
    ];
    
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] == UPLOAD_ERR_NO_FILE) {
        $error_message = 'Please select a photo file!';
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        }
    } elseif ($_FILES['profile_photo']['error'] != 0) {
        $error_message = isset($upload_error_messages[$_FILES['profile_photo']['error']]) 
                        ? $upload_error_messages[$_FILES['profile_photo']['error']] 
                        : 'Unknown upload error occurred!';
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        }
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        // Get file info
        $file_type = $_FILES['profile_photo']['type'];
        $file_size = $_FILES['profile_photo']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $error_message = 'Only JPG, PNG, and GIF files are allowed!';
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error_message]);
                exit();
            }
        } elseif ($file_size > $max_size) {
            $error_message = 'File size must not exceed 5MB!';
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error_message]);
                exit();
            }
        } else {
            $upload_dir = '../../uploads/profile_images/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                // Delete old profile image if exists
                if ($user_data['profile_image'] && $user_data['profile_image'] != 'default-user.jpg') {
                    $old_image_path = $upload_dir . $user_data['profile_image'];
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                
                // Update database
                $update_photo_stmt = $db->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $update_photo_stmt->bind_param("si", $new_filename, $user_id);
                
                if ($update_photo_stmt->execute()) {
                    $message = 'Profile photo updated successfully!';
                    $user_data['profile_image'] = $new_filename;
                    
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => $message]);
                        exit();
                    }
                } else {
                    $error_message = 'Failed to update profile photo in database!';
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $error_message]);
                        exit();
                    }
                }
                $update_photo_stmt->close();
            } else {
                $error_message = 'Failed to upload photo! Please check file permissions.';
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error_message]);
                    exit();
                }
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'All password fields are required!';
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        }
    } elseif (strlen($new_password) < 6) {
        $error_message = 'New password must be at least 6 characters!';
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        }
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New password and confirmation do not match!';
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        }
    } else {
        // Verify current password
        $pass_stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $pass_stmt->bind_param("i", $user_id);
        $pass_stmt->execute();
        $pass_result = $pass_stmt->get_result();
        
        if ($pass_result->num_rows == 0) {
            $error_message = 'User not found!';
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error_message]);
                exit();
            }
        } else {
            $user_pass = $pass_result->fetch_assoc();
            $pass_stmt->close();
            
            // Verify the current password
            if (!password_verify($current_password, $user_pass['password'])) {
                $error_message = 'Current password is incorrect!';
                if ($is_ajax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $error_message]);
                    exit();
                }
            } else {
                // Current password is correct, proceed with update
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_pass_stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_pass_stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($update_pass_stmt->execute()) {
                    $message = 'Password changed successfully!';
                    $active_section = 'password'; // Stay on password section
                    
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => $message]);
                        exit();
                    }
                } else {
                    $error_message = 'Failed to change password!';
                    if ($is_ajax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $error_message]);
                        exit();
                    }
                }
                $update_pass_stmt->close();
            }
        }
    }
}

// Get cart count for navbar
$cart_count = 0;
$cart_stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$cart_count = $cart_result->fetch_assoc()['count'];
$cart_stmt->close();

$db->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - Papua Journey</title>
    <link rel="stylesheet" href="my_account.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <div class="container">
        <div class="account-wrapper">
            <!-- Sidebar Navigation -->
            <div class="sidebar">
                <div class="sidebar-section">
                    <div class="sidebar-header">
                        <i class="fas fa-user-circle"></i>
                        <span>My Account</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <div class="sidebar-submenu show">
                        <a href="?section=profile" class="submenu-item <?php echo $active_section == 'profile' ? 'active' : ''; ?>">
                            <i class="fas fa-user"></i> Profile
                        </a>
                        <a href="?section=password" class="submenu-item <?php echo $active_section == 'password' ? 'active' : ''; ?>">
                            <i class="fas fa-lock"></i> Change Password
                        </a>
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <a href="my_orders.php" class="sidebar-header">
                        <i class="fas fa-shopping-bag"></i>
                        <span>My Orders</span>
                    </a>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="content-area">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Section -->
                <?php if ($active_section == 'profile'): ?>
                <div class="content-card">
                    <h2 class="section-title">
                        <i class="fas fa-user"></i> Profile Information
                    </h2>
                    
                    <!-- Profile Image Section -->
                    <div class="profile-image-section">
                        <div class="current-image">
                            <?php if ($user_data['profile_image'] && file_exists('../../uploads/profile_images/' . $user_data['profile_image'])): ?>
                                <img src="../../uploads/profile_images/<?php echo htmlspecialchars($user_data['profile_image']); ?>" alt="Profile">
                            <?php else: ?>
                                <div class="default-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="image-upload-form">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="upload-wrapper">
                                    <input type="file" name="profile_photo" id="profile_photo" accept="image/*" class="file-input">
                                    <label for="profile_photo" class="file-label">
                                        <i class="fas fa-camera"></i> Choose Photo
                                    </label>
                                    <span class="file-name">No file chosen</span>
                                </div>
                                <button type="submit" name="upload_photo" class="btn btn-secondary">
                                    <i class="fas fa-upload"></i> Upload
                                </button>
                            </form>
                            <small class="form-text">Format: JPG, PNG, GIF. Max 5MB.</small>
                        </div>
                    </div>
                    
                    <!-- Profile Form -->
                    <form method="POST" class="profile-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" name="full_name" id="full_name" 
                                       value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" 
                                       value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" name="phone" id="phone" 
                                   value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>"
                                   placeholder="e.g., 081234567890">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea name="address" id="address" rows="3" 
                                      placeholder="Enter your complete address"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Password Section -->
                <?php if ($active_section == 'password'): ?>
                <div class="content-card">
                    <h2 class="section-title">
                        <i class="fas fa-lock"></i> Change Password
                    </h2>
                    
                    <form method="POST" class="password-form">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" name="current_password" id="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password" id="new_password" required>
                            <small class="form-text">Minimum 6 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Notification Modals -->
    <!-- Success Notification Modal -->
    <div id="notification-overlay" class="notification-overlay">
        <div class="notification-modal">
            <div class="checkmark-container">
                <div class="checkmark-circle">
                    <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                        <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                    </svg>
                </div>
            </div>
            <div class="notification-message">Success!</div>
            <div class="notification-submessage">Your changes have been saved</div>
        </div>
    </div>

    <!-- Error Notification Modal -->
    <div id="error-overlay" class="notification-overlay error-overlay">
        <div class="notification-modal error-modal">
            <div class="error-icon-container">
                <div class="error-circle">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="notification-message">Error!</div>
            <div class="notification-submessage">Something went wrong</div>
        </div>
    </div>

    <!-- Confirmation Dialog Modal -->
    <div id="confirmation-overlay" class="notification-overlay">
        <div class="confirmation-modal">
            <div class="confirmation-icon">
                <i class="fas fa-question-circle"></i>
            </div>
            <div class="confirmation-message">Are you sure?</div>
            <div class="confirmation-submessage">This action cannot be undone</div>
            <div class="confirmation-buttons">
                <button class="btn btn-secondary" onclick="closeConfirmation()">Cancel</button>
                <button class="btn btn-primary" id="confirm-action">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar submenu
        document.querySelectorAll('.sidebar-header').forEach(header => {
            if (!header.classList.contains('disabled')) {
                header.addEventListener('click', function() {
                    const submenu = this.nextElementSibling;
                    const toggleIcon = this.querySelector('.toggle-icon');
                    
                    if (submenu) {
                        submenu.classList.toggle('show');
                        toggleIcon.classList.toggle('rotate');
                    }
                });
            }
        });

        // File input handling
        const fileInput = document.getElementById('profile_photo');
        const fileName = document.querySelector('.file-name');
        
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    fileName.textContent = this.files[0].name;
                } else {
                    fileName.textContent = 'No file chosen';
                }
            });
        }

        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });

        // Notification functions
        function showSuccessNotification(message, submessage = 'Your changes have been saved') {
            const overlay = document.getElementById('notification-overlay');
            const messageEl = overlay.querySelector('.notification-message');
            const submessageEl = overlay.querySelector('.notification-submessage');
            
            messageEl.textContent = message;
            submessageEl.textContent = submessage;
            
            overlay.classList.add('show');
            
            setTimeout(() => {
                overlay.classList.remove('show');
            }, 3000);
        }

        function showErrorNotification(message, submessage = 'Please try again') {
            const overlay = document.getElementById('error-overlay');
            const messageEl = overlay.querySelector('.notification-message');
            const submessageEl = overlay.querySelector('.notification-submessage');
            
            messageEl.textContent = message;
            submessageEl.textContent = submessage;
            
            overlay.classList.add('show');
            
            setTimeout(() => {
                overlay.classList.remove('show');
            }, 3000);
        }

        function showConfirmation(message, submessage, onConfirm) {
            const overlay = document.getElementById('confirmation-overlay');
            const messageEl = overlay.querySelector('.confirmation-message');
            const submessageEl = overlay.querySelector('.confirmation-submessage');
            const confirmBtn = document.getElementById('confirm-action');
            
            messageEl.textContent = message;
            submessageEl.textContent = submessage;
            
            // Remove any existing event listeners
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            
            newConfirmBtn.addEventListener('click', function() {
                onConfirm();
                closeConfirmation();
            });
            
            overlay.classList.add('show');
        }

        function closeConfirmation() {
            document.getElementById('confirmation-overlay').classList.remove('show');
        }

        // Handle form submissions with AJAX
        // Profile update form
        const profileForm = document.querySelector('.profile-form');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                showConfirmation(
                    'Update Profile?',
                    'Are you sure you want to update your profile information?',
                    () => {
                        submitProfileForm();
                    }
                );
            });
        }

        function submitProfileForm() {
            const formData = new FormData(profileForm);
            formData.append('update_profile', '1');
            formData.append('ajax', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessNotification('Profile Updated!', data.message);
                } else {
                    showErrorNotification('Update Failed', data.message);
                }
            })
            .catch(error => {
                showErrorNotification('Error', 'An unexpected error occurred');
            });
        }

        // Photo upload form
        const photoForm = document.querySelector('.image-upload-form form');
        if (photoForm) {
            photoForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Use getElementById for more reliable access to the file input
                const fileInput = document.getElementById('profile_photo');
                
                // Debug logging
                console.log('File input element:', fileInput);
                console.log('Files:', fileInput ? fileInput.files : 'No input found');
                
                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    showErrorNotification('No File Selected', 'Please choose a photo first');
                    return;
                }
                
                // Additional validation for file size
                const file = fileInput.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (file.size > maxSize) {
                    showErrorNotification('File Too Large', 'File size must not exceed 5MB');
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    showErrorNotification('Invalid File Type', 'Only JPG, PNG, and GIF files are allowed');
                    return;
                }
                
                showConfirmation(
                    'Upload Photo?',
                    'Are you sure you want to change your profile photo?',
                    () => {
                        submitPhotoForm();
                    }
                );
            });
        }

        function submitPhotoForm() {
            // Get the file input directly
            const fileInput = document.getElementById('profile_photo');
            
            // Create new FormData and manually append the file
            const formData = new FormData();
            
            // Check if file exists and append it
            if (fileInput && fileInput.files && fileInput.files.length > 0) {
                formData.append('profile_photo', fileInput.files[0]);
                console.log('File appended:', fileInput.files[0].name, fileInput.files[0].size);
            } else {
                console.error('No file found in submitPhotoForm');
                showErrorNotification('Error', 'No file selected');
                return;
            }
            
            // Append other necessary fields
            formData.append('upload_photo', '1');
            formData.append('ajax', '1');
            
            // Debug: Log FormData contents
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ', pair[1]);
            }
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    showSuccessNotification('Photo Uploaded!', data.message);
                    // Reload page to show new image
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showErrorNotification('Upload Failed', data.message);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                showErrorNotification('Error', 'An unexpected error occurred');
            });
        }

        // Password change form
        const passwordForm = document.querySelector('.password-form');
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const newPassword = this.querySelector('#new_password').value;
                const confirmPassword = this.querySelector('#confirm_password').value;
                
                if (newPassword !== confirmPassword) {
                    showErrorNotification('Password Mismatch', 'New passwords do not match');
                    return;
                }
                
                showConfirmation(
                    'Change Password?',
                    'Are you sure you want to change your password?',
                    () => {
                        submitPasswordForm();
                    }
                );
            });
        }

        function submitPasswordForm() {
            const formData = new FormData(passwordForm);
            formData.append('change_password', '1');
            formData.append('ajax', '1');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccessNotification('Password Changed!', data.message);
                    passwordForm.reset();
                } else {
                    showErrorNotification('Change Failed', data.message);
                }
            })
            .catch(error => {
                showErrorNotification('Error', 'An unexpected error occurred');
            });
        }
    </script>
</body>
</html>