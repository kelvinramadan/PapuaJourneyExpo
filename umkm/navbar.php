<?php
// UMKM Navigation Bar
// This file should be included after session_start()

// Ensure user is logged in
if (!isset($_SESSION['umkm_id'])) {
    // Don't use header() here since output may have started
    echo '<script>window.location.href = "../login.php";</script>';
    exit();
}
?>

<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <h3>🌺 UMKM Papua</h3>
        </div>
        <ul class="nav-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="dashboard.php">Kelola Artikel</a></li>
            <li><a href="add_artikel.php">Tambah Artikel</a></li>
            <li><a href="dashboard.php#profile">Profil</a></li>
            <li><a href="../logout.php" onclick="return confirm('Yakin ingin logout?')">Logout</a></li>
        </ul>
    </div>
</nav>

<style>
.navbar {
    background-color: #8B4513;
    color: white;
    padding: 1rem 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nav-brand h3 {
    margin: 0;
    color: white;
}

.nav-menu {
    list-style: none;
    display: flex;
    gap: 2rem;
    margin: 0;
    padding: 0;
}

.nav-menu a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: opacity 0.3s;
}

.nav-menu a:hover {
    opacity: 0.8;
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