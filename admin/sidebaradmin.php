<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="sidebar.css">
</head>
<body>
    <button class="mobile-toggle" onclick="toggleSidebar()">☰</button>
    
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>Admin Dashboard</h1>
        </div>
        
        <nav class="nav-menu">
            <a href="index.php" class="btn active">🏠 Admin Homepage</a>
            <a href="adminwisata.php" class="btn">🏞️ Admin Wisata</a>
            <a href="adminpenginapan.php" class="btn">🏨 Admin Penginapan</a>
        </nav>
        
        <div class="user-section">
            <span class="user-greeting">Halo, admin</span>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>

    <script>
        // Toggle sidebar for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        // Add active state to navigation buttons
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-menu .btn').forEach(btn => {
            if (btn.getAttribute('href') === currentPage) {
                btn.classList.add('active');
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>