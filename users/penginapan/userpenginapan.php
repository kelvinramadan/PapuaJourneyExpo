<?php
// users/userpenginapan.php
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Include navbar processing logic before any output
require_once '../components/navbar_process.php';

require_once '../../config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$tipe_filter = isset($_GET['tipe']) ? $_GET['tipe'] : '';
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'list';
$detail_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle detail view
$penginapan_detail = null;
if ($view_mode === 'detail' && $detail_id > 0) {
    $query = "SELECT * FROM penginapan WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $detail_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $penginapan_detail = $result->fetch_assoc();
    }
    $stmt->close();
}

// Fetch filtered penginapan data
$penginapan_data = [];
$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(judul LIKE ? OR lokasi LIKE ? OR deskripsi LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($tipe_filter)) {
    $where_conditions[] = "tipe = ?";
    $params[] = $tipe_filter;
    $types .= 's';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

$query = "SELECT * FROM penginapan {$where_clause} ORDER BY created_at DESC";
$stmt = $db->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $penginapan_data[] = $row;
}
$stmt->close();

// Get related penginapan (same type, excluding current)
$related_penginapan = [];
if ($penginapan_detail) {
    $query = "SELECT * FROM penginapan WHERE tipe = ? AND id != ? ORDER BY created_at DESC LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->bind_param("si", $penginapan_detail['tipe'], $penginapan_detail['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $related_penginapan[] = $row;
    }
    $stmt->close();
}

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDate($date) {
    return date('d F Y', strtotime($date));
}

function truncateText($text, $limit) {
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . '...';
}

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penginapan Papua - Wisata Indonesia</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            color: #333;
        }

        .page-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 20px 0;
            text-align: center;
            color: white;
        }

        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .filters {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .filters form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            width: 100%;
        }

        .filters input,
        .filters select,
        .filters button {
            padding: 15px 25px;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .filters input,
        .filters select {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
        }

        .filters input:focus,
        .filters select:focus {
            outline: none;
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .filters button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            cursor: pointer;
            font-weight: bold;
        }

        .filters button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .reset-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
        }

        .reset-btn:hover {
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4) !important;
        }

        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }

        .article-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .article-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        .article-image {
            position: relative;
            height: 250px;
            overflow: hidden;
        }

        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .article-card:hover .article-image img {
            transform: scale(1.1);
        }

        .placeholder-image {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #adb5bd;
        }

        .card-category {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
            color: white;
        }

        .category-hotel {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .category-guesthouse {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .category-villa {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }

        .category-resort {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .article-card-content {
            padding: 25px;
        }

        .article-card-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .article-card-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 15px;
        }

        .card-description {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .card-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-detail {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-detail:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }

        .card-date {
            color: #999;
            font-size: 0.9rem;
        }

        .no-results {
            text-align: center;
            padding: 80px 20px;
            color: white;
        }

        .no-results h3 {
            margin-bottom: 20px;
            font-size: 2rem;
        }

        .no-results p {
            font-size: 1.1rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .back-button {
            display: inline-block;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            margin-bottom: 30px;
            font-weight: bold;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .back-button:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        .article-detail {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: bold;
            color: white;
        }

        .article-content {
            padding: 40px;
        }

        .article-title {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2c3e50;
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
            font-size: 2rem;
            font-weight: bold;
            color: #e74c3c;
        }

        .article-date {
            color: #666;
            font-size: 1.1rem;
        }

        .article-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 40px;
        }

        .penginapan-info-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 20px;
            padding: 40px;
            margin-top: 40px;
        }

        .penginapan-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .info-item span {
            font-size: 1.5rem;
            width: 40px;
            text-align: center;
        }

        .info-item strong {
            display: block;
            margin-bottom: 5px;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .contact-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            color: white;
        }

        .facilities-preview {
            margin-bottom: 15px;
        }

        .facility-tag {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            margin: 3px;
            display: inline-block;
        }

        .related-section {
            margin-top: 50px;
        }

        .related-header {
            grid-column: 1 / -1;
            text-align: center;
            margin-bottom: 20px;
        }

        .related-header h3 {
            color: white;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .related-header p {
            color: rgba(255,255,255,0.9);
        }

        @media (max-width: 768px) {
            .filters form {
                flex-direction: column;
            }

            .filters input,
            .filters select,
            .filters button {
                min-width: 100%;
            }

            .articles-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .article-meta {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .penginapan-info-grid {
                grid-template-columns: 1fr;
            }

            .contact-actions {
                flex-direction: column;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .article-title {
                font-size: 1.8rem;
            }

            .article-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include '../components/navbar_display.php'; ?>
    <!-- Page Header -->
    <div class="page-header">
        <h1>🏨 Penginapan Papua</h1>
        <p>Temukan penginapan terbaik untuk petualangan Anda di tanah surga Indonesia</p>
    </div>

    <div class="container">
        <div class="content-wrapper">
            <?php if ($view_mode === 'list'): ?>
                <!-- Filter Section -->
                <div class="filters">
                    <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                        <input type="text" name="search" placeholder="🔍 Cari penginapan..." value="<?php echo htmlspecialchars($search); ?>">
                        <select name="tipe">
                            <option value="">🏠 Semua Tipe</option>
                            <option value="hotel" <?php echo $tipe_filter == 'hotel' ? 'selected' : ''; ?>>🏨 Hotel</option>
                            <option value="villa" <?php echo $tipe_filter == 'villa' ? 'selected' : ''; ?>>🏖️ Villa</option>
                            <option value="resort" <?php echo $tipe_filter == 'resort' ? 'selected' : ''; ?>>🌴 Resort</option>
                        </select>
                        <button type="submit">🔍 Filter</button>
                        <?php if (!empty($tipe_filter) || !empty($search)): ?>
                            <a href="userpenginapan.php" style="text-decoration: none;">
                                <button type="button" class="reset-btn">🔄 Reset</button>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Results Section -->
                <?php if (!empty($penginapan_data)): ?>
                    <div class="articles-grid">
                        <?php foreach ($penginapan_data as $penginapan): ?>
                            <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $penginapan['id']; ?>'">
                                <div class="article-image">
                                    <?php if ($penginapan['photo'] && file_exists('../uploads/' . $penginapan['photo'])): ?>
                                        <img src="../../uploads/<?php echo htmlspecialchars($penginapan['photo']); ?>" 
                                             alt="<?php echo htmlspecialchars($penginapan['judul']); ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image">
                                            🏨
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-category category-<?php echo $penginapan['tipe']; ?>">
                                        <?php
                                        $tipe_icons = [
                                            'hotel' => '🏨 Hotel',
                                            'villa' => '🏖️ Villa',
                                            'resort' => '🌴 Resort'
                                        ];
                                        echo $tipe_icons[$penginapan['tipe']] ?? ucfirst($penginapan['tipe']);
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="article-card-content">
                                    <h4 class="article-card-title"><?php echo htmlspecialchars($penginapan['judul']); ?></h4>
                                    <div class="article-card-price"><?php echo formatPrice($penginapan['harga']); ?>/malam</div>
                                    
                                    <div class="card-description">
                                        <?php echo truncateText(htmlspecialchars($penginapan['deskripsi']), 100); ?>
                                    </div>

                                    <?php if ($penginapan['fasilitas']): ?>
                                    <div class="facilities-preview">
                                        <strong style="font-size: 0.9rem; color: #666;">Fasilitas:</strong><br>
                                        <?php 
                                        $fasilitas = array_map('trim', explode(',', $penginapan['fasilitas']));
                                        foreach (array_slice($fasilitas, 0, 3) as $fasilitas_item): 
                                        ?>
                                            <span class="facility-tag"><?php echo htmlspecialchars($fasilitas_item); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($fasilitas) > 3): ?>
                                            <small style="color: #667eea; font-weight: bold;">+<?php echo count($fasilitas) - 3; ?> lainnya</small>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-actions">
                                        <a href="?view=detail&id=<?php echo $penginapan['id']; ?>" class="btn-detail">
                                            📖 Lihat Selengkapnya
                                        </a>
                                        <span class="card-date">
                                            <?php echo formatDate($penginapan['created_at']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-results">
                        <div style="font-size: 5rem; margin-bottom: 1rem;">😔</div>
                        <h3>Tidak Ada Penginapan Ditemukan</h3>
                        <p>Maaf, tidak ada penginapan yang sesuai dengan pencarian Anda.</p>
                        <p>Coba ubah kata kunci pencarian atau pilih tipe lain.</p>
                    </div>
                <?php endif; ?>
                
            <?php elseif ($view_mode === 'detail' && $penginapan_detail): ?>
                <!-- Detail View -->
                <a href="?" class="back-button">
                    ⬅️ Kembali ke Daftar Penginapan
                </a>
                
                <div class="article-detail">
                    <div class="article-header">
                        <?php if ($penginapan_detail['photo'] && file_exists('../uploads/' . $penginapan_detail['photo'])): ?>
                            <img src="../../uploads/<?php echo htmlspecialchars($penginapan_detail['photo']); ?>" 
                                 alt="<?php echo htmlspecialchars($penginapan_detail['judul']); ?>">
                        <?php else: ?>
                            <div class="placeholder-image" style="height: 400px;">
                                🏨
                            </div>
                        <?php endif; ?>
                        
                        <div class="article-category category-<?php echo $penginapan_detail['tipe']; ?>">
                            <?php
                            $tipe_icons = [
                                'hotel' => '🏨 Hotel',
                                'villa' => '🏖️ Villa',
                                'resort' => '🌴 Resort'
                            ];
                            echo $tipe_icons[$penginapan_detail['tipe']] ?? ucfirst($penginapan_detail['tipe']);
                            ?>
                        </div>
                    </div>
                    
                    <div class="article-content">
                        <h1 class="article-title"><?php echo htmlspecialchars($penginapan_detail['judul']); ?></h1>
                        
                        <div class="article-meta">
                            <div class="article-price"><?php echo formatPrice($penginapan_detail['harga']); ?>/malam</div>
                            <div class="article-date">
                                📅 <?php echo formatDate($penginapan_detail['created_at']); ?>
                            </div>
                        </div>
                        
                        <div class="article-description">
                            <?php echo nl2br(htmlspecialchars($penginapan_detail['deskripsi'])); ?>
                        </div>
                        
                        <div class="penginapan-info-section">
                            <h3 style="margin-bottom: 25px; color: #333; font-size: 1.5rem;">ℹ️ Informasi Penginapan</h3>
                            <div class="penginapan-info-grid">
                                <div class="info-item">
                                    <span>📍</span>
                                    <div>
                                        <strong>Lokasi</strong>
                                        <?php echo htmlspecialchars($penginapan_detail['lokasi']); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <span>🏠</span>
                                    <div>
                                        <strong>Tipe Penginapan</strong>
                                        <?php echo ucfirst($penginapan_detail['tipe']); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <span>💰</span>
                                    <div>
                                        <strong>Harga per Malam</strong>
                                        <?php echo formatPrice($penginapan_detail['harga']); ?>
                                    </div>
                                </div>
                                
                                <?php if ($penginapan_detail['fasilitas']): ?>
                                <div class="info-item" style="grid-column: 1 / -1;">
                                    <span>🛎️</span>
                                    <div>
                                        <strong>Fasilitas</strong>
                                        <div style="margin-top: 10px;">
                                            <?php 
                                            $fasilitas = array_map('trim', explode(',', $penginapan_detail['fasilitas']));
                                            foreach ($fasilitas as $fasilitas_item): 
                                            ?>
                                                <span class="facility-tag"><?php echo htmlspecialchars($fasilitas_item); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="contact-actions">
                                <a href="https://www.google.com/maps?q=<?php echo urlencode($penginapan_detail['lokasi']); ?>" 
                                   target="_blank" class="btn btn-primary">
                                    🗺️ Lihat di Google Maps
                                </a>
                                
                                <button onclick="sharePage()" class="btn btn-secondary">
                                    📤 Bagikan Penginapan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Related Penginapan -->
                <?php if (count($related_penginapan) > 0): ?>
                <div class="related-section">
                    <div class="articles-grid">
                        <div class="related-header">
                            <h3>🌟 Penginapan Terkait</h3>
                            <p>Jelajahi penginapan lainnya dengan tipe yang sama</p>
                        </div>
                        <?php foreach ($related_penginapan as $related): ?>
                            <div class="article-card" onclick="location.href='?view=detail&id=<?php echo $related['id']; ?>'">
                                <div class="article-image">
                                    <?php if ($related['photo'] && file_exists('../uploads/' . $related['photo'])): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($related['photo']); ?>" 
                                             alt="<?php echo htmlspecialchars($related['judul']); ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image">
                                            🏨
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-category category-<?php echo $related['tipe']; ?>">
                                        <?php
                                        $tipe_icons = [
                                            'hotel' => '🏨 Hotel',
                                            'villa' => '🏖️ Villa',
                                            'resort' => '🌴 Resort'
                                        ];
                                        echo $tipe_icons[$related['tipe']] ?? ucfirst($related['tipe']);
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="article-card-content">
                                    <h4 class="article-card-title"><?php echo htmlspecialchars($related['judul']); ?></h4>
                                    <div class="article-card-price"><?php echo formatPrice($related['harga']); ?>/malam</div>
                                    
                                    <div class="card-description">
                                        <?php echo truncateText(htmlspecialchars($related['deskripsi']), 80); ?>
                                    </div>

                                    <?php if ($related['fasilitas']): ?>
                                    <div class="facilities-preview">
                                        <?php 
                                        $fasilitas = array_map('trim', explode(',', $related['fasilitas']));
                                        foreach (array_slice($fasilitas, 0, 2) as $fasilitas_item): 
                                        ?>
                                            <span class="facility-tag"><?php echo htmlspecialchars($fasilitas_item); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-actions">
                                        <a href="?view=detail&id=<?php echo $related['id']; ?>" class="btn-detail">
                                            📖 Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- 404 Not Found -->
                <div class="no-results">
                    <div style="font-size: 5rem; margin-bottom: 1rem;">🏨</div>
                    <h3>Penginapan Tidak Ditemukan</h3>
                    <p>Maaf, penginapan yang Anda cari tidak dapat ditemukan.</p>
                    <a href="?" class="btn-detail" style="display: inline-block; margin-top: 20px;">
                        🏠 Kembali ke Beranda
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript untuk Fungsi Tambahan -->
    <script>
        // Fungsi untuk share page
        function sharePage() {
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    text: 'Lihat penginapan ini di Papua!',
                    url: window.location.href
                }).then(() => {
                    console.log('Berhasil dibagikan');
                }).catch((error) => {
                    console.log('Error sharing:', error);
                    fallbackShare();
                });
            } else {
                fallbackShare();
            }
        }

        // Fallback share function
        function fallbackShare() {
            const url = window.location.href;
            const title = document.title;
            
            // Copy to clipboard
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link berhasil disalin ke clipboard!');
                });
            } else {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    alert('Link berhasil disalin ke clipboard!');
                } catch (err) {
                    console.error('Failed to copy: ', err);
                    alert('Gagal menyalin link. Silakan salin manual: ' + url);
                }
                document.body.removeChild(textArea);
            }
        }

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.remove();
                }, 500);
            });
        }, 5000);

        // Smooth scroll untuk anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Loading animation untuk card clicks
        document.querySelectorAll('.article-card').forEach(card => {
            card.addEventListener('click', function() {
                this.style.opacity = '0.7';
                this.style.transform = 'scale(0.98)';
            });
        });

        // Search form enhancement
        const searchForm = document.querySelector('.filters form');
        const searchInput = document.querySelector('input[name="search"]');
        
        if (searchForm && searchInput) {
            // Auto-submit on Enter
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchForm.submit();
                }
            });

            // Clear search button
            if (searchInput.value.length > 0) {
                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.className = 'clear-search-btn';
                clearBtn.innerHTML = '✕';
                clearBtn.style.cssText = `
                    position: absolute;
                    right: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: none;
                    border: none;
                    font-size: 1.2rem;
                    color: #666;
                    cursor: pointer;
                    padding: 5px;
                `;
                
                searchInput.parentNode.style.position = 'relative';
                searchInput.parentNode.appendChild(clearBtn);
                
                clearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    searchForm.submit();
                });
            }
        }
    </script>

    <!-- Additional CSS untuk perbaikan responsif -->
    <style>
        /* Perbaikan untuk mobile */
        @media (max-width: 576px) {
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .page-header p {
                font-size: 1rem;
            }
            
            .container {
                padding: 20px 10px;
            }
            
            .filters {
                padding: 20px;
            }
            
            .article-card-content {
                padding: 15px;
            }
            
            .article-content {
                padding: 15px;
            }
            
            .penginapan-info-section {
                padding: 20px;
            }
        }

        /* Loading state */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Hover effects untuk mobile */
        @media (hover: none) {
            .article-card:hover {
                transform: none;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            }
            
            .btn-detail:hover,
            .btn:hover {
                transform: none;
            }
        }

        /* Print styles */
        @media print {
            .filters,
            .back-button,
            .contact-actions,
            .related-section {
                display: none !important;
            }
            
            body {
                background: white !important;
            }
            
            .article-detail,
            .card {
                background: white !important;
                box-shadow: none !important;
            }
        }
    </style>
</body>
</html>