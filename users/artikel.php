<?php
// users/artikel.php
require_once '../config/database.php';

$db = getDbConnection();

// Get filter parameters
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where_conditions = ["a.status = 'active'"];
$params = [];
$param_types = '';

if (!empty($kategori_filter)) {
    $where_conditions[] = "a.kategori = ?";
    $params[] = $kategori_filter;
    $param_types .= 's';
}

if (!empty($search)) {
    $where_conditions[] = "(a.judul LIKE ? OR a.deskripsi LIKE ? OR u.business_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $param_types .= 'sss';
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM artikel a 
                JOIN umkm u ON a.umkm_id = u.id 
                WHERE $where_clause";

$count_stmt = $db->prepare($count_query);
if (!empty($params)) {
    $count_stmt->bind_param($param_types, ...$params);
}
$count_stmt->execute();
$total_articles = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

// Pagination
$articles_per_page = 12;
$total_pages = ceil($total_articles / $articles_per_page);
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $articles_per_page;

// Get articles
$query = "SELECT a.*, u.business_name, u.profile_image as umkm_image 
          FROM artikel a 
          JOIN umkm u ON a.umkm_id = u.id 
          WHERE $where_clause 
          ORDER BY a.created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);
$params[] = $articles_per_page;
$params[] = $offset;
$param_types .= 'ii';

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$db->close();

// Function to format price
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

// Function to truncate text
function truncateText($text, $length = 100) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artikel UMKM Papua - Temukan Produk & Jasa Lokal</title>
    <link rel="stylesheet" href="userdashboard.css">
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
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #8B4513;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #2c3e50;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #D2691E;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            color: white;
        }
        
        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .filters-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .filters-row {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e1e8ed;
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .search-box input:focus {
            border-color: #D2691E;
        }
        
        .category-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .category-btn {
            padding: 0.5rem 1rem;
            border: 2px solid #e1e8ed;
            background: white;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #2c3e50;
            font-size: 0.9rem;
        }
        
        .category-btn:hover,
        .category-btn.active {
            background: #D2691E;
            color: white;
            border-color: #D2691E;
        }
        
        .results-info {
            margin-bottom: 1.5rem;
            color: white;
            text-align: center;
        }
        
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .article-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(0,0,0,0.2);
        }
        
        .card-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, #f0f2f5, #e1e8ed);
            position: relative;
            overflow: hidden;
        }
        
        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .article-card:hover .card-image img {
            transform: scale(1.05);
        }
        
        .card-category {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
            color: white;
        }
        
        .category-jasa { background: #1976d2; }
        .category-event { background: #7b1fa2; }
        .category-kuliner { background: #388e3c; }
        .category-kerajinan { background: #f57c00; }
        .category-wisata { background: #c2185b; }
        
        .card-content {
            padding: 1.5rem;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .card-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: #D2691E;
            margin-bottom: 1rem;
        }
        
        .card-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .card-umkm {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .umkm-avatar {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-detail {
            background: #D2691E;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s;
        }
        
        .btn-detail:hover {
            background: #B8611A;
        }
        
        .card-date {
            font-size: 0.8rem;
            color: #999;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            background: white;
            color: #2c3e50;
            text-decoration: none;
            border-radius: 5px;
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
        
        .no-results {
            text-align: center;
            color: white;
            padding: 3rem;
        }
        
        .no-results img {
            width: 150px;
            margin-bottom: 1rem;
            opacity: 0.7;
        }
        
        .no-results h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .no-results p {
            font-size: 1.1rem;
            opacity: 0.8;
        }
        
        .placeholder-image {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #f0f2f5, #e1e8ed);
            color: #666;
            font-size: 3rem;
        }

        @media (max-width: 768px) {
            .filters-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            .category-filters {
                justify-content: center;
            }
            
            .articles-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <span>🌺</span>
                UMKM Papua
            </div>
            <nav class="nav-links">
                <a href="user_dashboard.php">🏠 Beranda</a>
                <a href="artikel.php">📰 Artikel</a>
                <a href="../login.php">🔑 Login</a>
            </nav>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h1>🌟 Artikel UMKM Papua</h1>
            <p>Temukan produk dan jasa terbaik dari UMKM lokal Papua</p>
        </div>
        
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
                    <a href="artikel.php" class="category-btn <?php echo empty($kategori_filter) ? 'active' : ''; ?>">
                        🌟 Semua
                    </a>
                    <a href="artikel.php?kategori=jasa<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="category-btn <?php echo $kategori_filter === 'jasa' ? 'active' : ''; ?>">
                        🔧 Jasa
                    </a>
                    <a href="artikel.php?kategori=event<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="category-btn <?php echo $kategori_filter === 'event' ? 'active' : ''; ?>">
                        🎉 Event
                    </a>
                    <a href="artikel.php?kategori=kuliner<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="category-btn <?php echo $kategori_filter === 'kuliner' ? 'active' : ''; ?>">
                        🍽️ Kuliner
                    </a>
                    <a href="artikel.php?kategori=kerajinan<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="category-btn <?php echo $kategori_filter === 'kerajinan' ? 'active' : ''; ?>">
                        🎨 Kerajinan
                    </a>
                    <a href="artikel.php?kategori=wisata<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                       class="category-btn <?php echo $kategori_filter === 'wisata' ? 'active' : ''; ?>">
                        🏝️ Wisata
                    </a>
                </div>
            </form>
        </div>
        
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
            <div class="articles-grid">
                <?php foreach ($articles as $article): ?>
                    <div class="article-card" onclick="location.href='detail.php?id=<?php echo $article['id']; ?>'">
                        <div class="card-image">
                            <?php if ($article['gambar']): ?>
                                <img src="../uploads/artikel_images/<?php echo htmlspecialchars($article['gambar']); ?>" 
                                     alt="<?php echo htmlspecialchars($article['judul']); ?>">
                            <?php else: ?>
                                <div class="placeholder-image">
                                    📷
                                </div>
                            <?php endif; ?>
                            <div class="card-category category-<?php echo $article['kategori']; ?>">
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
                        
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($article['judul']); ?></h3>
                            
                            <div class="card-price"><?php echo formatPrice($article['harga']); ?></div>
                            
                            <div class="card-description">
                                <?php echo truncateText(htmlspecialchars($article['deskripsi']), 80); ?>
                            </div>
                            
                            <div class="card-umkm">
                                <?php if ($article['umkm_image']): ?>
                                    <img src="../uploads/profile_images/<?php echo htmlspecialchars($article['umkm_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($article['business_name']); ?>" class="umkm-avatar">
                                <?php else: ?>
                                    <div class="umkm-avatar" style="background: #D2691E; color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem;">
                                        🏪
                                    </div>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($article['business_name']); ?></span>
                            </div>
                            
                            <div class="card-actions">
                                <a href="detail.php?id=<?php echo $article['id']; ?>" class="btn-detail">
                                    📖 Lihat Selengkapnya
                                </a>
                                <span class="card-date">
                                    <?php echo date('d M Y', strtotime($article['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
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
    </div>
    
    <script>
        // Auto submit search form on Enter
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
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