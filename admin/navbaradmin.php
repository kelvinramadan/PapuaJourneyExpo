<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>

<div class="header">
    <div class="header-content">
        <h1>Admin Dashboard</h1>
        <div>
            <a href="adminwisata.php" class="btn">Ke Admin Wisata</a>
        </div>
        <div>
            <span>Halo, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>
</div>