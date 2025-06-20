<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <h2>UMKM Dashboard</h2>
        </div>
        
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="icon">🏠</i> Dashboard
            </a>
            <a href="umkm_pemesanan.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'umkm_pemesanan.php' ? 'active' : ''; ?>">
                <i class="icon">📦</i> Pemesanan
            </a>
            <a href="add_artikel.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add_artikel.php' ? 'active' : ''; ?>">
                <i class="icon">➕</i> Tambah Artikel
            </a>
            <a href="edit_artikel.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'edit_artikel.php' ? 'active' : ''; ?>">
                <i class="icon">✏️</i> Edit Artikel
            </a>
            <a href="../logout.php" class="nav-link logout">
                <i class="icon">🚪</i> Logout
            </a>
        </div>
    </div>
</nav>

<style>
.navbar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1rem 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nav-brand h2 {
    color: white;
    margin: 0;
    font-size: 1.5rem;
}

.nav-menu {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.nav-link {
    color: white;
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-link:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

.nav-link.active {
    background: rgba(255,255,255,0.3);
}

.nav-link.logout {
    background: rgba(231, 76, 60, 0.8);
}

.nav-link.logout:hover {
    background: rgba(231, 76, 60, 1);
}

@media (max-width: 768px) {
    .nav-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .nav-menu {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>