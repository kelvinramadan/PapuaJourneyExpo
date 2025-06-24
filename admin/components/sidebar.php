<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Font Awesome Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="../assets/logo.png" alt="Papua Journey Logo" class="logo-image">
            <div class="logo-text">
                <span class="logo-papua">Papua</span>
                <span class="logo-journey">Journey</span>
            </div>
        </div>
    </div>
    
    <nav class="nav-menu">
        <a href="index.php" class="btn <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" data-tooltip="Dashboard">
            <span class="btn-icon"><i class="fas fa-home"></i></span>
            <span class="btn-text">Dashboard</span>
        </a>
        
        <a href="adminwisata.php" class="btn <?php echo $current_page == 'adminwisata.php' ? 'active' : ''; ?>" data-tooltip="Wisata">
            <span class="btn-icon"><i class="fas fa-map-marked-alt"></i></span>
            <span class="btn-text">Wisata</span>
        </a>
        
        <a href="adminpenginapan.php" class="btn <?php echo $current_page == 'adminpenginapan.php' ? 'active' : ''; ?>" data-tooltip="Penginapan">
            <span class="btn-icon"><i class="fas fa-bed"></i></span>
            <span class="btn-text">Penginapan</span>
        </a>
        
        <div class="nav-divider"></div>
        
        <a href="adminumkm.php" class="btn <?php echo $current_page == 'adminumkm.php' ? 'active' : ''; ?>" data-tooltip="UMKM">
            <span class="btn-icon"><i class="fas fa-store"></i></span>
            <span class="btn-text">UMKM</span>
        </a>
        
        <a href="adminusers.php" class="btn <?php echo $current_page == 'adminusers.php' ? 'active' : ''; ?>" data-tooltip="Users">
            <span class="btn-icon"><i class="fas fa-users"></i></span>
            <span class="btn-text">Users</span>
        </a>
        
        <a href="admintransactions.php" class="btn <?php echo $current_page == 'admintransactions.php' ? 'active' : ''; ?>" data-tooltip="Transaksi">
            <span class="btn-icon"><i class="fas fa-receipt"></i></span>
            <span class="btn-text">Transaksi</span>
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
        <a href="?logout=1" class="logout-btn" data-tooltip="Logout">
            <span class="btn-icon"><i class="fas fa-sign-out-alt"></i></span>
            <span class="btn-text">Logout</span>
        </a>
    </div>
    
    <!-- Toggle Button -->
    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
        <i class="fas fa-chevron-left"></i>
    </button>
</aside>

<script>
// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const toggleIcon = sidebarToggle.querySelector('i');
    
    // Check if sidebar state is saved in localStorage
    const sidebarState = localStorage.getItem('sidebarState');
    if (sidebarState === 'collapsed') {
        sidebar.classList.add('collapsed');
        toggleIcon.classList.remove('fa-chevron-left');
        toggleIcon.classList.add('fa-chevron-right');
    }
    
    // Toggle sidebar
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        
        // Update toggle icon
        if (sidebar.classList.contains('collapsed')) {
            toggleIcon.classList.remove('fa-chevron-left');
            toggleIcon.classList.add('fa-chevron-right');
            localStorage.setItem('sidebarState', 'collapsed');
        } else {
            toggleIcon.classList.remove('fa-chevron-right');
            toggleIcon.classList.add('fa-chevron-left');
            localStorage.setItem('sidebarState', 'expanded');
        }
    });
    
    // Mobile menu functionality
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    }
});
</script>

