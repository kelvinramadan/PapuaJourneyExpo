
<?php
// umkm/umkm_dashboard.php
session_start();

require_once '../config/database.php';


// Check if user is logged in and is UMKM
if (!isset($_SESSION['umkm_id']) || $_SESSION['user_type'] != 'umkm') {
    header('Location: ../login.php');
    exit();
}

$db = getDbConnection();
$umkm_id = $_SESSION['umkm_id'];
$success_message = '';
$error_message = '';

// Handle delete artikel
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $artikel_id = intval($_GET['id']);
    
    // Get artikel data first to delete image
    $stmt = $db->prepare("SELECT gambar FROM artikel WHERE id = ? AND umkm_id = ?");
    $stmt->bind_param("ii", $artikel_id, $umkm_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $artikel = $result->fetch_assoc();
    $stmt->close();
    
    if ($artikel) {
        // Delete artikel from database
        $stmt = $db->prepare("DELETE FROM artikel WHERE id = ? AND umkm_id = ?");
        $stmt->bind_param("ii", $artikel_id, $umkm_id);
        
        if ($stmt->execute()) {
            // Delete image file if exists
            if ($artikel['gambar'] && file_exists('../uploads/artikel_images/' . $artikel['gambar'])) {
                unlink('../uploads/artikel_images/' . $artikel['gambar']);
            }
            $success_message = 'Artikel berhasil dihapus!';
        } else {
            $error_message = 'Gagal menghapus artikel!';
        }
        $stmt->close();
    }
}

// Get UMKM data for header
$stmt = $db->prepare("SELECT business_name, profile_image, email, phone FROM umkm WHERE id = ?");
$stmt->bind_param("i", $umkm_id);
$stmt->execute();
$result = $stmt->get_result();
$umkm_data = $result->fetch_assoc();
$stmt->close();

// Get articles with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Count total articles
$stmt = $db->prepare("SELECT COUNT(*) as total FROM artikel WHERE umkm_id = ?");
$stmt->bind_param("i", $umkm_id);
$stmt->execute();
$result = $stmt->get_result();
$total_articles = $result->fetch_assoc()['total'];
$total_pages = ceil($total_articles / $limit);
$stmt->close();

// Get articles for current page
$stmt = $db->prepare("SELECT id, judul, deskripsi, harga, kategori, gambar, created_at FROM artikel WHERE umkm_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $umkm_id, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$articles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$db->close();

// Get current section
$section = isset($_GET['section']) ? $_GET['section'] : 'kelola';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard UMKM - UMKM Papua</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            text-align: center;
            padding: 0 1.5rem 2rem;
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 2rem;
        }
        
        .sidebar-header h2 {
            color: #8B4513;
            margin-bottom: 0.5rem;
            font-size: 1.4rem;
        }
        
        .business-info {
            background: linear-gradient(135deg, #D2691E, #CD853F);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .business-name {
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .business-details {
            font-size: 0.85rem;
            opacity: 0.9;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0 1rem;
        }
        
        .sidebar-nav li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 1rem;
            color: #333;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .sidebar-nav a:hover,
        .sidebar-nav a.active {
            background: linear-gradient(135deg, #D2691E, #CD853F);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar-nav a .icon {
            font-size: 1.2rem;
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        .content-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .content-header h1 {
            color: #8B4513;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .content-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #8B4513;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-weight: 500;
        }
        
        .articles-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-title {
            color: #8B4513;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .add-article-btn {
            background: linear-gradient(135deg, #D2691E, #CD853F);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .add-article-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(210, 105, 30, 0.4);
        }
        
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .article-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .article-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-image {
            color: #999;
            font-size: 3rem;
        }
        
        .article-content {
            padding: 1.5rem;
        }
        
        .article-kategori {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }
        
        .kategori-jasa { background: #e3f2fd; color: #1976d2; }
        .kategori-event { background: #f3e5f5; color: #7b1fa2; }
        .kategori-kuliner { background: #e8f5e8; color: #388e3c; }
        .kategori-kerajinan { background: #fff3e0; color: #f57c00; }
        .kategori-wisata { background: #fce4ec; color: #c2185b; }
        
        .article-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }
        
        .article-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .article-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #D2691E;
            margin-bottom: 1rem;
        }
        
        .article-date {
            color: #999;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .article-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-edit {
            background: #28a745;
            color: white;
        }
        
        .btn-edit:hover {
            background: #218838;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
        }
        
        .pagination a {
            background: #f8f9fa;
            color: #6c757d;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background: #D2691E;
            color: white;
        }
        
        .pagination .current {
            background: #D2691E;
            color: white;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            margin-bottom: 1rem;
            color: #8B4513;
        }
        
        .logout-link {
            margin-top: 2rem;
            padding: 0 1rem;
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 1rem;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .logout-btn:hover {
            background: #c82333;
            transform: translateX(5px);
        }
        
        .logout-btn .icon {
            margin-right: 0.75rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .dashboard-container {
                flex-direction: column;
            }
            
            .articles-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🌺 UMKM Papua</h2>
                <div class="business-info">
                    <div class="business-name"><?php echo htmlspecialchars($umkm_data['business_name']); ?></div>
                    <div class="business-details">
                        <?php if ($umkm_data['email']): ?>
                            📧 <?php echo htmlspecialchars($umkm_data['email']); ?><br>
                        <?php endif; ?>
                        <?php if ($umkm_data['phone']): ?>
                            📞 <?php echo htmlspecialchars($umkm_data['phone']); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <ul class="sidebar-nav">
                <li>
                    <a href="?section=kelola" class="<?php echo $section == 'kelola' ? 'active' : ''; ?>">
                        <span class="icon">📋</span>
                        Kelola Artikel
                    </a>
                </li>
                <li>
                    <a href="add_artikel.php">
                        <span class="icon">➕</span>
                        Tambah Artikel
                    </a>
                </li>
            </ul>
            
            <div class="logout-link">
                <a href="../logout.php" class="logout-btn" onclick="return confirm('Yakin ingin logout?')">
                    <span class="icon">🚀</span>
                    Logout
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="content-header">
                <h1>Dashboard UMKM</h1>
                <p>Kelola artikel dan promosikan produk/jasa Anda</p>
            </div>
            
            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-number"><?php echo $total_articles; ?></div>
                    <div class="stat-label">Total Artikel</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👀</div>
                    <div class="stat-number"><?php echo rand(50, 500); ?></div>
                    <div class="stat-label">Total Views</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-number"><?php echo date('d'); ?></div>
                    <div class="stat-label">Hari Ini</div>
                </div>
            </div>
            
            <!-- Alerts -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Articles Section -->
            <div class="articles-section">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">📋 Kelola Artikel</h2>
                        <p>Kelola semua artikel yang telah Anda buat</p>
                    </div>
                    <a href="add_artikel.php" class="add-article-btn">
                        ➕ Tambah Artikel Baru
                    </a>
                </div>
                
                <?php if (empty($articles)): ?>
                    <div class="empty-state">
                        <div class="icon">📝</div>
                        <h3>Belum Ada Artikel</h3>
                        <p>Mulai promosikan produk/jasa Anda dengan membuat artikel pertama!</p>
                        <br>
                        <a href="add_artikel.php" class="add-article-btn">
                            ➕ Buat Artikel Pertama
                        </a>
                    </div>
                <?php else: ?>
                    <div class="articles-grid">
                        <?php foreach ($articles as $article): ?>
                            <div class="article-card">
                                <div class="article-image">
                                    <?php if ($article['gambar'] && file_exists('../uploads/artikel_images/' . $article['gambar'])): ?>
                                        <img src="../uploads/artikel_images/<?php echo htmlspecialchars($article['gambar']); ?>" 
                                             alt="<?php echo htmlspecialchars($article['judul']); ?>">
                                    <?php else: ?>
                                        <div class="no-image">🖼️</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="article-content">
                                    <div class="article-kategori kategori-<?php echo htmlspecialchars($article['kategori']); ?>">
                                        <?php
                                        $kategori_icons = [
                                            'jasa' => '🔧',
                                            'event' => '🎉',
                                            'kuliner' => '🍽️',
                                            'kerajinan' => '🎨',
                                            'wisata' => '🏝️'
                                        ];
                                        echo $kategori_icons[$article['kategori']] . ' ' . ucfirst($article['kategori']);
                                        ?>
                                    </div>
                                    
                                    <h3 class="article-title"><?php echo htmlspecialchars($article['judul']); ?></h3>
                                    
                                    <p class="article-description">
                                        <?php echo htmlspecialchars(substr($article['deskripsi'], 0, 150)) . '...'; ?>
                                    </p>
                                    
                                    <div class="article-price">
                                        Rp <?php echo number_format($article['harga'], 0, ',', '.'); ?>
                                    </div>
                                    
                                    <div class="article-date">
                                        📅 <?php echo date('d M Y', strtotime($article['created_at'])); ?>
                                    </div>
                                    
                                    <div class="article-actions">
                                        <a href="edit_artikel.php?id=<?php echo $article['id']; ?>" class="btn btn-edit">
                                            ✏️ Edit
                                        </a>
                                        <a href="?delete=1&id=<?php echo $article['id']; ?>" 
                                           class="btn btn-delete"
                                           onclick="return confirm('Yakin ingin menghapus artikel ini?')">
                                            🗑️ Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&section=<?php echo $section; ?>">‹ Prev</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>&section=<?php echo $section; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&section=<?php echo $section; ?>">Next ›</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-close alerts
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 300);
                }, 5000);
            });
        });
        
        // Smooth scrolling for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>