<?php
// users/userwisata.php
// Start session first before any output
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../config/database.php';

$db = getDbConnection();

// Get filter parameters
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'list';
$wisata_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function truncateText($text, $limit) {
    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
}

// Handle detail view
$wisata_detail = null;
if ($view_mode === 'detail' && $wisata_id > 0) {
    $stmt = $db->prepare("SELECT * FROM wisata WHERE id = ?");
    $stmt->bind_param("i", $wisata_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $wisata_detail = $result->fetch_assoc();
    $stmt->close();
}

// Build query with filters for list view
if ($view_mode === 'list') {
    $sql = "SELECT * FROM wisata WHERE 1=1";
    $params = [];

    if (!empty($kategori_filter)) {
        $sql .= " AND kategori = ?";
        $params[] = $kategori_filter;
    }

    if (!empty($search)) {
        $sql .= " AND (judul LIKE ? OR deskripsi LIKE ? OR alamat LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $sql .= " ORDER BY created_at DESC";

    // Prepare and execute query
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Simpan data yang diperlukan sebelum output HTML
    $wisata_data = [];
    while ($row = $result->fetch_assoc()) {
        $wisata_data[] = $row;
    }
    $stmt->close();
}

// Get related wisata for detail view
$related_wisata = [];
if ($view_mode === 'detail' && $wisata_detail) {
    $stmt = $db->prepare("SELECT * FROM wisata WHERE kategori = ? AND id != ? ORDER BY created_at DESC LIMIT 3");
    $stmt->bind_param("si", $wisata_detail['kategori'], $wisata_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $related_wisata[] = $row;
    }
    $stmt->close();
}

mysqli_close($db);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wisata Papua - Jelajahi Keindahan Papua</title>
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
        
        .main-content {
            padding-top: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .filters {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filters input,
        .filters select {
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            background: rgba(255,255,255,0.9);
            font-size: 14px;
            min-width: 180px;
            transition: all 0.3s ease;
        }
        
        .filters input:focus,
        .filters select:focus {
            outline: none;
            background: white;
            box-shadow: 0 0 15px rgba(255,255,255,0.3);
        }
        
        .filters button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .filters button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 12px 20px;
            border-radius: 25px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .back-button:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        /* Article Grid Styles */
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .article-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.4s ease;
            cursor: pointer;
            position: relative;
        }
        
        .article-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.25);
        }
        
        .article-image {
            position: relative;
            height: 220px;
            overflow: hidden;
        }
        
        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        
        .article-card:hover .article-image img {
            transform: scale(1.1);
        }
        
        .placeholder-image {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #90caf9;
        }
        
        .card-category {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .category-budaya {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        }
        
        .category-alam {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }
        
        .article-card-content {
            padding: 25px;
        }
        
        .article-card-title {
            font-size: 1.4rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 12px;
            line-height: 1.3;
        }
        
        .article-card-price {
            font-size: 1.6rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 15px;
        }
        
        .card-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        
        .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .btn-detail {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .card-date {
            color: #999;
            font-size: 0.85rem;
        }
        
        /* Article Detail Styles */
        .article-detail {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            margin-bottom: 40px;
        }
        
        .article-header {
            position: relative;
            height: 400px;
            overflow: hidden;
        }
        
        .article-header img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .article-category {
            position: absolute;
            top: 25px;
            left: 25px;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .article-content {
            padding: 40px;
        }
        
        .article-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .article-price {
            font-size: 2.2rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .article-date {
            color: #666;
            font-size: 1.1rem;
        }
        
        .article-description {
            font-size: 1.15rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 40px;
        }
        
        .wisata-info-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        
        .wisata-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .info-item span {
            font-size: 1.5rem;
            width: 40px;
            text-align: center;
        }
        
        .info-item div strong {
            color: #667eea;
            font-size: 1.1rem;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-item div {
            flex: 1;
        }
        
        .contact-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
        }
        
        .btn {
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(37, 211, 102, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.4);
        }
        
        .no-results {
            text-align: center;
            color: white;
            padding: 80px 20px;
        }
        
        .no-results h3 {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .no-results p {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filters input,
            .filters select,
            .filters button {
                width: 100%;
                min-width: auto;
            }
            
            .articles-grid {
                grid-template-columns: 1fr;
            }
            
            .article-title {
                font-size: 1.8rem;
            }
            
            .article-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .wisata-info-grid {
                grid-template-columns: 1fr;
            }
            
            .contact-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include '../components/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <?php if ($view_mode === 'list'): ?>
                <div class="page-header">
                    <h1>🏝️ Wisata Papua</h1>
                    <p>Jelajahi keindahan alam dan budaya Papua yang memukau</p>
                </div>
                
                <div class="filters">
                    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                        <input type="text" name="search" placeholder="🔍 Cari wisata..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="kategori">
                            <option value="">📋 Semua Kategori</option>
                            <option value="budaya" <?php echo $kategori_filter == 'budaya' ? 'selected' : ''; ?>>🎭 Budaya</option>
                            <option value="alam" <?php echo $kategori_filter == 'alam' ? 'selected' : ''; ?>>🌿 Alam</option>
                        </select>
                        <button type="submit">🔍 Filter</button>
                        <?php if (!empty($kategori_filter) || !empty($search)): ?>
                            <a href="userwisata.php" style="text-decoration: none;">
                                <button type="button" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">🔄 Reset</button>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <?php if (!empty($wisata_data)): ?>
                    <div class="articles-grid">
                        <?php foreach ($wisata_data as $wisata): ?>
                            <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $wisata['id']; ?>'">
                                <div class="article-image">
                                    <?php if ($wisata['photo']): ?>
                                        <img src="../../uploads/<?php echo htmlspecialchars($wisata['photo']); ?>" 
                                             alt="<?php echo htmlspecialchars($wisata['judul']); ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image">
                                            🏝️
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-category category-<?php echo $wisata['kategori']; ?>">
                                        <?php
                                        $kategori_icons = [
                                            'budaya' => '🎭 Budaya',
                                            'alam' => '🌿 Alam'
                                        ];
                                        echo $kategori_icons[$wisata['kategori']] ?? ucfirst($wisata['kategori']);
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="article-card-content">
                                    <h4 class="article-card-title"><?php echo htmlspecialchars($wisata['judul']); ?></h4>
                                    <div class="article-card-price"><?php echo formatPrice($wisata['harga']); ?></div>
                                    
                                    <div class="card-description">
                                        <?php echo truncateText(htmlspecialchars($wisata['deskripsi']), 100); ?>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <a href="?view=detail&id=<?php echo $wisata['id']; ?>" class="btn-detail">
                                            📖 Lihat Selengkapnya
                                        </a>
                                        <span class="card-date">
                                            <?php echo formatDate($wisata['created_at']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <div style="font-size: 5rem; margin-bottom: 1rem;">😔</div>
                        <h3>Tidak Ada Wisata Ditemukan</h3>
                        <p>Maaf, tidak ada wisata yang sesuai dengan pencarian Anda.</p>
                        <p>Coba ubah kata kunci pencarian atau pilih kategori lain.</p>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($view_mode === 'detail' && $wisata_detail): ?>
                <a href="?" class="back-button">
                    ⬅️ Kembali ke Daftar Wisata
                </a>
                
                <div class="article-detail">
                    <div class="article-header">
                        <?php if ($wisata_detail['photo']): ?>
                            <img src="../../uploads/<?php echo htmlspecialchars($wisata_detail['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($wisata_detail['judul']); ?>">
                        <?php else: ?>
                            <div class="placeholder-image" style="height: 400px;">
                                🏝️
                            </div>
                        <?php endif; ?>
                        
                        <div class="article-category category-<?php echo $wisata_detail['kategori']; ?>">
                            <?php
                            $kategori_icons = [
                                'budaya' => '🎭 Budaya',
                                'alam' => '🌿 Alam'
                            ];
                            echo $kategori_icons[$wisata_detail['kategori']] ?? ucfirst($wisata_detail['kategori']);
                            ?>
                        </div>
                    </div>
                    
                    <div class="article-content">
                        <h1 class="article-title"><?php echo htmlspecialchars($wisata_detail['judul']); ?></h1>
                        
                        <div class="article-meta">
                            <div class="article-price"><?php echo formatPrice($wisata_detail['harga']); ?></div>
                            <div class="article-date">
                                📅 <?php echo formatDate($wisata_detail['created_at']); ?>
                            </div>
                        </div>
                        
                        <div class="article-description">
                            <?php echo nl2br(htmlspecialchars($wisata_detail['deskripsi'])); ?>
                        </div>
                        
                        <div class="wisata-info-section">
                            <h3 style="margin-bottom: 25px; color: #333; font-size: 1.5rem;">ℹ️ Informasi Wisata</h3>
                            <div class="wisata-info-grid">
                                <div class="info-item">
                                    <span>📍</span>
                                    <div>
                                        <strong>Alamat</strong>
                                        <?php echo htmlspecialchars($wisata_detail['alamat']); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <span>🕒</span>
                                    <div>
                                        <strong>Jam Buka</strong>
                                        <?php echo htmlspecialchars($wisata_detail['jam_buka']); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <span>🎫</span>
                                    <div>
                                        <strong>Harga Tiket</strong>
                                        <?php echo formatPrice($wisata_detail['harga']); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <span>📂</span>
                                    <div>
                                        <strong>Kategori</strong>
                                        <?php echo ucfirst($wisata_detail['kategori']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="contact-actions">
                                <a href="https://www.google.com/maps?q=<?php echo urlencode($wisata_detail['alamat']); ?>" 
                                   target="_blank" class="btn btn-primary">
                                    🗺️ Lihat di Google Maps
                                </a>
                                
                                <button onclick="sharePage()" class="btn btn-secondary">
                                    📤 Bagikan Wisata
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (count($related_wisata) > 0): ?>
                <div class="articles-grid" style="margin-top: 50px;">
                    <div style="grid-column: 1 / -1; text-align: center; margin-bottom: 20px;">
                        <h3 style="color: white; font-size: 2rem;">🌟 Wisata Terkait</h3>
                        <p style="color: rgba(255,255,255,0.9); margin-top: 10px;">Jelajahi wisata lainnya dengan kategori yang sama</p>
                    </div>
                    <?php foreach ($related_wisata as $related): ?>
                        <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $related['id']; ?>'">
                            <div class="article-image">
                                <?php if ($related['photo']): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($related['photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['judul']); ?>">
                                <?php else: ?>
                                    <div class="placeholder-image">
                                        🏝️
                                    </div>
                                <?php endif; ?>
                                <div class="card-category category-<?php echo $related['kategori']; ?>">
                                    <?php
                                    $kategori_icons = [
                                        'budaya' => '🎭 Budaya',
                                        'alam' => '🌿 Alam'
                                    ];
                                    echo $kategori_icons[$related['kategori']] ?? ucfirst($related['kategori']);
                                    ?>
                                </div>
                            </div>
                            
                            <div class="article-card-content">
                                <h4 class="article-card-title"><?php echo htmlspecialchars($related['judul']); ?></h4>
                                <div class="article-card-price"><?php echo formatPrice($related['harga']); ?></div>
                                <div class="card-description">
                                    <?php echo truncateText(htmlspecialchars($related['deskripsi']), 80); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function sharePage() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo htmlspecialchars($wisata_detail['judul'] ?? ''); ?>',
                    text: 'Lihat wisata menarik ini di Papua!',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Link berhasil disalin ke clipboard!');
                });
            }
        }
        
        // Smooth scroll animation for back button
        document.querySelector('.back-button')?.addEventListener('click', function(e) {
            e.preventDefault();
            window.history.back();
        });
        
        // Add loading animation for cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.article-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>