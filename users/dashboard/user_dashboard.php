<?php
session_start();

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../../login.php');
    exit();
}

// Include navbar processing logic before any output
require_once '../components/navbar_process.php';

// Get user information from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

require_once '../../config/database.php';

$message = '';
$error_message = '';

// Get user details from database
$db = getDbConnection();
$stmt = $db->prepare("SELECT full_name, email, phone, address, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

// Get filters and search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$items_per_page = 12;
$offset = ($current_page - 1) * $items_per_page;

// Check if viewing article detail
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$article = null;
$related_articles = [];

if ($view_mode === 'detail' && $article_id > 0) {
    // Get article details with UMKM information
    $query = "SELECT a.*, u.business_name, u.owner_name, u.phone, u.address, u.business_type, u.profile_image as umkm_image, u.description as umkm_description 
              FROM artikel a 
              JOIN umkm u ON a.umkm_id = u.id 
              WHERE a.id = ? AND a.status = 'active'";

    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $article_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $article = $result->fetch_assoc();
        
        // Get related articles from same category
        $related_query = "SELECT a.*, u.business_name 
                          FROM artikel a 
                          JOIN umkm u ON a.umkm_id = u.id 
                          WHERE a.kategori = ? AND a.id != ? AND a.status = 'active' 
                          ORDER BY a.created_at DESC 
                          LIMIT 4";

        $related_stmt = $db->prepare($related_query);
        $related_stmt->bind_param("si", $article['kategori'], $article_id);
        $related_stmt->execute();
        $related_articles = $related_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $related_stmt->close();
    } else {
        $view_mode = 'dashboard'; // Reset to dashboard if article not found
    }
    $stmt->close();
}

// Get articles for dashboard view with filtering
$articles = [];
$total_articles = 0;
$total_pages = 1;

if ($view_mode === 'dashboard') {
    // Build WHERE clause for filtering
    $where_conditions = ["a.status = 'active'"];
    $params = [];
    $param_types = "";

    if (!empty($search)) {
        $where_conditions[] = "(a.judul LIKE ? OR a.deskripsi LIKE ? OR u.business_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $param_types .= "sss";
    }

    if (!empty($kategori_filter)) {
        $where_conditions[] = "a.kategori = ?";
        $params[] = $kategori_filter;
        $param_types .= "s";
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Count total articles for pagination
    $count_query = "SELECT COUNT(*) as total 
                    FROM artikel a 
                    JOIN umkm u ON a.umkm_id = u.id 
                    WHERE $where_clause";

    if (!empty($params)) {
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bind_param($param_types, ...$params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total_articles = $count_result->fetch_assoc()['total'];
        $count_stmt->close();
    } else {
        $count_result = $db->query($count_query);
        $total_articles = $count_result->fetch_assoc()['total'];
    }

    $total_pages = ceil($total_articles / $items_per_page);

    // Get articles with pagination
    $articles_query = "SELECT a.*, u.business_name, u.profile_image as umkm_image
                       FROM artikel a 
                       JOIN umkm u ON a.umkm_id = u.id 
                       WHERE $where_clause
                       ORDER BY a.created_at DESC 
                       LIMIT ? OFFSET ?";

    $params[] = $items_per_page;
    $params[] = $offset;
    $param_types .= "ii";

    if (!empty($params)) {
        $articles_stmt = $db->prepare($articles_query);
        $articles_stmt->bind_param($param_types, ...$params);
        $articles_stmt->execute();
        $articles_result = $articles_stmt->get_result();
        $articles = $articles_result->fetch_all(MYSQLI_ASSOC);
        $articles_stmt->close();
    }
}

$db->close();

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function truncateText($text, $length) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Wisatawan - Omaki Platform</title>
    <link rel="stylesheet" href="userdashboard.css">
</head>
<body>
    <?php include '../components/navbar_display.php'; ?>

    <div class="container">
        <?php if ($view_mode === 'dashboard'): ?>
            
            <!-- Filters Section -->
            <div class="filters-section">
                <form method="GET" action="">
                    <div class="filters-row">
                        <div class="search-box">
                            <input type="text" name="search" placeholder="🔍 Cari artikel, produk, atau UMKM..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <button type="submit" style="display: none;"></button>
                    </div>
                    
                    <div class="category-filters" style="margin-top: 1rem;">
                        <a href="?" class="category-btn <?php echo empty($kategori_filter) ? 'active' : ''; ?>">
                            🌟 Semua
                        </a>
                        <a href="?kategori=jasa<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="category-btn <?php echo $kategori_filter === 'jasa' ? 'active' : ''; ?>">
                            🔧 Jasa
                        </a>
                        <a href="?kategori=event<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="category-btn <?php echo $kategori_filter === 'event' ? 'active' : ''; ?>">
                            🎉 Event
                        </a>
                        <a href="?kategori=kuliner<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="category-btn <?php echo $kategori_filter === 'kuliner' ? 'active' : ''; ?>">
                            🍽️ Kuliner
                        </a>
                        <a href="?kategori=kerajinan<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="category-btn <?php echo $kategori_filter === 'kerajinan' ? 'active' : ''; ?>">
                            🎨 Kerajinan
                        </a>
                        <a href="?kategori=wisata<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="category-btn <?php echo $kategori_filter === 'wisata' ? 'active' : ''; ?>">
                            🏝️ Wisata
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Results Info -->
            <div class="results-info">
                <p>Menampilkan <?php echo count($articles); ?> dari <?php echo $total_articles; ?> artikel
                   <?php if ($kategori_filter): ?>
                       dalam kategori <strong><?php echo ucfirst($kategori_filter); ?></strong>
                   <?php endif; ?>
                   <?php if ($search): ?>
                       untuk pencarian "<strong><?php echo htmlspecialchars($search); ?></strong>"
                   <?php endif; ?>
                </p>
            </div>
            
            <?php if (count($articles) > 0): ?>
            <div class="quick-actions">
                <h3>🌟 Artikel Terbaru</h3>
                <div class="articles-grid">
                    <?php foreach ($articles as $artikel): ?>
                        <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $artikel['id']; ?>'">
                            <div class="article-image">
                                <?php if ($artikel['gambar']): ?>
                                    <img src="../../uploads/artikel_images/<?php echo htmlspecialchars($artikel['gambar']); ?>" 
                                         alt="<?php echo htmlspecialchars($artikel['judul']); ?>">
                                <?php else: ?>
                                    <div class="placeholder-image">
                                        📷
                                    </div>
                                <?php endif; ?>
                                <div class="card-category category-<?php echo $artikel['kategori']; ?>">
                                    <?php
                                    $kategori_icons = [
                                        'jasa' => '🔧 Jasa',
                                        'event' => '🎉 Event',
                                        'kuliner' => '🍽️ Kuliner',
                                        'kerajinan' => '🎨 Kerajinan',
                                        'wisata' => '🏝️ Wisata'
                                    ];
                                    echo $kategori_icons[$artikel['kategori']] ?? ucfirst($artikel['kategori']);
                                    ?>
                                </div>
                            </div>
                            
                            <div class="article-card-content">
                                <h4 class="article-card-title"><?php echo htmlspecialchars($artikel['judul']); ?></h4>
                                <div class="article-card-price"><?php echo formatPrice($artikel['harga']); ?></div>
                                
                                <div class="card-description">
                                    <?php echo truncateText(htmlspecialchars($artikel['deskripsi']), 80); ?>
                                </div>
                                
                                <div class="card-umkm">
                                    <?php if ($artikel['umkm_image']): ?>
                                        <img src="../../uploads/profile_images/<?php echo htmlspecialchars($artikel['umkm_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($artikel['business_name']); ?>" class="umkm-avatar">
                                    <?php else: ?>
                                        <div class="umkm-avatar" style="background: #D2691E; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                            🏪
                                        </div>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($artikel['business_name']); ?></span>
                                </div>
                                
                                <div class="card-actions">
                                    <a href="?view=detail&id=<?php echo $artikel['id']; ?>" class="btn-detail">
                                        📖 Lihat Selengkapnya
                                    </a>
                                    <span class="card-date">
                                        <?php echo formatDate($artikel['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                            ⬅️ Sebelumnya
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                            Selanjutnya ➡️
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php else: ?>
                <div class="no-results">
                    <div style="font-size: 5rem; margin-bottom: 1rem;">😔</div>
                    <h3>Tidak Ada Artikel Ditemukan</h3>
                    <p>Maaf, tidak ada artikel yang sesuai dengan pencarian Anda.</p>
                    <p>Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
                </div>
            <?php endif; ?>
            
        <?php elseif ($view_mode === 'detail' && $article): ?>
            <a href="?" class="back-button">
                ⬅️ Kembali ke Dashboard
            </a>
            
            <div class="article-detail">
                <div class="article-header">
                    <?php if ($article['gambar']): ?>
                        <img src="../../uploads/artikel_images/<?php echo htmlspecialchars($article['gambar']); ?>" 
                             alt="<?php echo htmlspecialchars($article['judul']); ?>">
                    <?php else: ?>
                        <div class="placeholder-image">
                            📷
                        </div>
                    <?php endif; ?>
                    
                    <div class="article-category category-<?php echo $article['kategori']; ?>">
                        <?php
                        $kategori_icons = [
                            'jasa' => '🔧 Jasa',
                            'event' => '🎉 Event',
                            'kuliner' => '🍽️ Kuliner',
                            'kerajinan' => '🎨 Kerajinan',
                            'wisata' => '🏝️ Wisata'
                        ];
                        echo $kategori_icons[$article['kategori']] ?? ucfirst($article['kategori']);
                        ?>
                    </div>
                </div>
                
                <div class="article-content">
                    <h1 class="article-title"><?php echo htmlspecialchars($article['judul']); ?></h1>
                    
                    <div class="article-meta">
                        <div class="article-price"><?php echo formatPrice($article['harga']); ?></div>
                        <div class="article-date">
                            📅 <?php echo formatDate($article['created_at']); ?>
                        </div>
                    </div>
                    
                    <div class="article-description">
                        <?php echo nl2br(htmlspecialchars($article['deskripsi'])); ?>
                    </div>
                    
                    <div class="umkm-section">
                        <div class="umkm-header">
                            <?php if ($article['umkm_image']): ?>
                                <img src="../../uploads/profile_images/<?php echo htmlspecialchars($article['umkm_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($article['business_name']); ?>" class="umkm-avatar">
                            <?php else: ?>
                                <div class="umkm-avatar-placeholder">
                                    🏪
                                </div>
                            <?php endif; ?>
                            
                            <div class="umkm-info">
                                <h3><?php echo htmlspecialchars($article['business_name']); ?></h3>
                                <p><strong>Pemilik:</strong> <?php echo htmlspecialchars($article['owner_name']); ?></p>
                                <p><strong>Jenis Usaha:</strong> <?php echo ucfirst(htmlspecialchars($article['business_type'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="umkm-details">
                            <div class="umkm-detail-item">
                                <span>📞</span>
                                <div>
                                    <strong>Telepon</strong><br>
                                    <?php echo htmlspecialchars($article['phone']); ?>
                                </div>
                            </div>
                            
                            <div class="umkm-detail-item">
                                <span>📍</span>
                                <div>
                                    <strong>Alamat</strong><br>
                                    <?php echo htmlspecialchars($article['address']); ?>
                                </div>
                            </div>
                            
                            <?php if ($article['umkm_description']): ?>
                            <div class="umkm-detail-item" style="grid-column: 1 / -1;">
                                <span>📝</span>
                                <div>
                                    <strong>Tentang UMKM</strong><br>
                                    <?php echo nl2br(htmlspecialchars($article['umkm_description'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="contact-actions">
                            <a href="https://wa.me/62<?php echo ltrim($article['phone'], '0'); ?>?text=Halo,%20saya%20tertarik%20dengan%20<?php echo urlencode($article['judul']); ?>" 
                               target="_blank" class="btn btn-primary">
                                📱 Hubungi via WhatsApp
                            </a>
                            
                            <a href="tel:<?php echo $article['phone']; ?>" class="btn btn-secondary">
                                📞 Telepon Langsung
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (count($related_articles) > 0): ?>
            <div class="quick-actions">
                <h3>🌟 Artikel Terkait</h3>
                <div class="articles-grid">
                    <?php foreach ($related_articles as $related): ?>
                        <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $related['id']; ?>'">
                            <div class="article-image">
                                <?php if ($related['gambar']): ?>
                                    <img src="../../uploads/artikel_images/<?php echo htmlspecialchars($related['gambar']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['judul']); ?>">
                                <?php else: ?>
                                    <div class="placeholder-image">
                                        📷
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="article-card-content">
                                <h4 class="article-card-title"><?php echo htmlspecialchars($related['judul']); ?></h4>
                                <div class="article-card-price"><?php echo formatPrice($related['harga']); ?></div>
                                <div class="article-card-umkm">🏪 <?php echo htmlspecialchars($related['business_name']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // Auto submit search form on Enter
        document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
        
        // Smooth scroll for pagination
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>