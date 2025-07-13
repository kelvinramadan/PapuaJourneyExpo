<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Font Awesome Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" 
      rel="stylesheet" 
      integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" 
      crossorigin="anonymous"
      referrerpolicy="no-referrer">

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="../assets/logo.png" alt="Papua Journey Logo" class="logo-image">
            <p class="logo-text">Journey</p>
        </div>
    </div>
    
    <nav class="nav-menu" role="navigation" aria-label="Main navigation">
        <a href="index.php" class="btn <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" data-tooltip="Dashboard">
            <span class="btn-icon"><i class="fas fa-home"></i></span>
            <span class="btn-text">Dashboard</span>
        </a>
        
        <a href="adminwisata.php" class="btn <?php echo $current_page == 'adminwisata.php' ? 'active' : ''; ?>" data-tooltip="Wisata">
            <span class="btn-icon"><i class="fas fa-map-marked-alt"></i></span>
            <span class="btn-text">Wisata</span>
        </a>
        
        <a href="wisata_analytics.php" class="btn <?php echo $current_page == 'wisata_analytics.php' ? 'active' : ''; ?>" data-tooltip="Analytics">
            <span class="btn-icon"><i class="fas fa-chart-bar"></i></span>
            <span class="btn-text">Analitik</span>
        </a>
        
        <a href="adminpenginapan.php" class="btn <?php echo $current_page == 'adminpenginapan.php' ? 'active' : ''; ?>" data-tooltip="Penginapan">
            <span class="btn-icon"><i class="fas fa-bed"></i></span>
            <span class="btn-text">Penginapan</span>
        </a>
        
        <a href="payment_confirmation.php" class="btn <?php echo $current_page == 'payment_confirmation.php' ? 'active' : ''; ?>" data-tooltip="Konfirmasi Pembayaran">
            <span class="btn-icon"><i class="fas fa-money-check-alt"></i></span>
            <span class="btn-text">Konfirmasi Pembayaran</span>
        </a>
        
        <a href="financial_reports.php" class="btn <?php echo $current_page == 'financial_reports.php' ? 'active' : ''; ?>" data-tooltip="Laporan Keuangan">
            <span class="btn-icon"><i class="fas fa-chart-line"></i></span>
            <span class="btn-text">Laporan Keuangan</span>
        </a>
        
        <a href="abandoned_cart.php" class="btn <?php echo $current_page == 'abandoned_cart.php' ? 'active' : ''; ?>" data-tooltip="Keranjang Ditinggalkan">
            <span class="btn-icon"><i class="fas fa-shopping-cart"></i></span>
            <span class="btn-text">Keranjang Ditinggalkan</span>
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
        <a href="?logout=1" class="logout-btn" data-tooltip="Keluar">
            <span class="btn-icon"><i class="fas fa-sign-out-alt"></i></span>
            <span class="btn-text">Keluar</span>
        </a>
    </div>
</aside>

<script>
// Mobile menu functionality only
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
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

