<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <span style="font-size: 2rem;">🏝️</span>
            <h1>Papua Journey</h1>
        </div>
    </div>
    
    <nav class="nav-menu">
        <a href="index.php" class="btn <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <span class="btn-icon">🏠</span>
            <span>Dashboard</span>
        </a>
        
        <a href="adminwisata.php" class="btn <?php echo $current_page == 'adminwisata.php' ? 'active' : ''; ?>">
            <span class="btn-icon">🏖️</span>
            <span>Wisata</span>
        </a>
        
        <a href="adminpenginapan.php" class="btn <?php echo $current_page == 'adminpenginapan.php' ? 'active' : ''; ?>">
            <span class="btn-icon">🏨</span>
            <span>Penginapan</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <a href="#" class="btn">
            <span class="btn-icon">👥</span>
            <span>Users</span>
        </a>
        
        <a href="#" class="btn">
            <span class="btn-icon">🏪</span>
            <span>UMKM</span>
        </a>
        
        <a href="#" class="btn">
            <span class="btn-icon">📊</span>
            <span>Reports</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <a href="#" class="btn">
            <span class="btn-icon">⚙️</span>
            <span>Settings</span>
        </a>
    </nav>
    
    <div class="user-section">
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1)); ?>
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
                <span class="user-role">Administrator</span>
            </div>
        </div>
        <a href="?logout=1" class="logout-btn">
            <span class="btn-icon">🚪</span>
            <span>Logout</span>
        </a>
    </div>
</aside>

<button class="mobile-toggle" onclick="toggleSidebar()">☰</button>