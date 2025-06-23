<?php
// Check if session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';
$page_title = $page_title ?? 'Dashboard';
?>

<header class="admin-header">
    <div class="header-left">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <span>☰</span>
        </button>
        <h1 class="page-title"><?php echo htmlspecialchars($page_title); ?></h1>
    </div>
    
    <div class="header-right">
        <div class="search-box">
            <input type="text" placeholder="Search..." id="globalSearch">
        </div>
        
        <button class="btn btn-outline btn-sm">
            <span>🔔</span>
            <span class="badge badge-danger" style="position: absolute; top: -5px; right: -5px; font-size: 0.625rem;">3</span>
        </button>
        
        <div class="user-menu">
            <button class="user-button" onclick="toggleUserMenu()">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($admin_username, 0, 1)); ?>
                </div>
                <span><?php echo htmlspecialchars($admin_username); ?></span>
                <span>▼</span>
            </button>
            
            <div class="dropdown-menu" id="userDropdown" style="display: none;">
                <a href="#" class="dropdown-item">Profile</a>
                <a href="#" class="dropdown-item">Settings</a>
                <div class="dropdown-divider"></div>
                <a href="?logout=1" class="dropdown-item text-danger">Logout</a>
            </div>
        </div>
    </div>
</header>

<style>
.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid var(--border-color);
    border-radius: 0.375rem;
    box-shadow: var(--shadow-lg);
    min-width: 200px;
    margin-top: 0.5rem;
    z-index: 1000;
}

.dropdown-item {
    display: block;
    padding: 0.5rem 1rem;
    color: var(--text-primary);
    text-decoration: none;
    transition: background 0.2s;
}

.dropdown-item:hover {
    background: #F3F4F6;
}

.dropdown-divider {
    height: 1px;
    background: var(--border-color);
    margin: 0.5rem 0;
}

.text-danger {
    color: var(--danger-color) !important;
}
</style>