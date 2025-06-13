<?php
// navbar.php - Komponen Navbar yang dapat digunakan ulang
if (!isset($_SESSION)) {
    session_start();
}

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

// Get user data untuk navbar
require_once '../config/database.php';
$db = getDbConnection();
$stmt = $db->prepare("SELECT full_name, email, phone, address, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();
$db->close();
?>

<style>
/* Navbar Styles */
.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo h1 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: bold;
    background: linear-gradient(45deg, #ffd700, #ffed4e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.nav-links {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.nav-links a {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.nav-links a:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.nav-links a.active {
    background: white;
    color: #667eea;
}

.profile-dropdown {
    position: relative;
}

.profile-trigger {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: rgba(255, 255, 255, 0.1);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.profile-trigger:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-1px);
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(45deg, #ffd700, #ffed4e);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: #333;
    overflow: hidden;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-info .user-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.user-info .user-email {
    font-size: 0.75rem;
    opacity: 0.8;
}

.dropdown-arrow {
    font-size: 0.7rem;
    transition: transform 0.3s ease;
}

.profile-dropdown.active .dropdown-arrow {
    transform: rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: calc(100% + 0.5rem);
    right: 0;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    min-width: 220px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.1);
    overflow: hidden;
}

.profile-dropdown.active .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: block;
    width: 100%;
    padding: 0.75rem 1rem;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    background: none;
    text-align: left;
    font-size: 0.9rem;
    cursor: pointer;
}

.dropdown-item:hover {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.dropdown-separator {
    height: 1px;
    background: linear-gradient(90deg, transparent, #eee, transparent);
    margin: 0.5rem 0;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 1rem;
    }
    
    .nav-links {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .nav-links a {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .profile-trigger {
        padding: 0.5rem 0.75rem;
    }
    
    .user-info {
        display: none;
    }
    
    .dropdown-menu {
        right: auto;
        left: 50%;
        transform: translateX(-50%) translateY(-10px);
    }
    
    .profile-dropdown.active .dropdown-menu {
        transform: translateX(-50%) translateY(0);
    }
}

@media (max-width: 480px) {
    .logo h1 {
        font-size: 1.5rem;
    }
    
    .nav-links {
        gap: 0.5rem;
    }
    
    .nav-links a {
        padding: 0.4rem 0.8rem;
        font-size: 0.8rem;
    }
}
</style>

<header class="header">
    <div class="header-content">
        <div class="logo">
            <h1>Omaki Platform</h1>
        </div>
        
        <div class="nav-links">
            <a href="user_dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'user_dashboard.php' ? 'active' : ''; ?>">
                🏠 Home
            </a>
            <a href="userwisata.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'userwisata.php' ? 'active' : ''; ?>">
                🏝️ Wisata
            </a>
            <a href="penginapan.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'penginapan.php' ? 'active' : ''; ?>">
                🏨 Penginapan
            </a>
            <a href="../chatbot" class="<?php echo basename($_SERVER['PHP_SELF']) == 'chatbot' ? 'active' : ''; ?>">
                🤖 Chatbot
            </a>
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
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <small class="user-email"><?php echo htmlspecialchars($user_email); ?></small>
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

<script>
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

// Close dropdown when opening modal
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    document.getElementById('profileDropdown').classList.remove('active');
}
</script>
