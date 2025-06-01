<?php
// users/detail.php
require_once '../config/database.php';

$db = getDbConnection();

// Get article ID from URL
$article_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($article_id <= 0) {
    header('Location: artikel.php');
    exit();
}

// Get article details with UMKM information
$query = "SELECT a.*, u.business_name, u.owner_name, u.phone, u.address, u.business_type, u.profile_image as umkm_image, u.description as umkm_description 
          FROM artikel a 
          JOIN umkm u ON a.umkm_id = u.id 
          WHERE a.id = ? AND a.status = 'active'";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $article_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: artikel.php');
    exit();
}

$article = $result->fetch_assoc();
$stmt->close();

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

$db->close();

// Function to format price
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

// Function to format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['judul']); ?> - UMKM Papua</title>
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
            line-height: 1.6;
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
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
            margin-bottom: 2rem;
        }
        
        .back-button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }
        
        .article-detail {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 45px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
        }
        
        .article-header {
            position: relative;
            height: 400px;
            background: linear-gradient(45deg, #f0f2f5, #e1e8ed);
            overflow: hidden;
        }
        
        .article-header img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .article-category {
            position: absolute;
            top: 2rem;
            left: 2rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            color: white;
            backdrop-filter: blur(10px);
        }
        
        .category-jasa { background: rgba(25, 118, 210, 0.9); }
        .category-event { background: rgba(123, 31, 162, 0.9); }
        .category-kuliner { background: rgba(56, 142, 60, 0.9); }
        .category-kerajinan { background: rgba(245, 124, 0, 0.9); }
        .category-wisata { background: rgba(194, 24, 91, 0.9); }
        
        .article-content {
            padding: 3rem;
        }
        
        .article-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .article-meta {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            color: #666;
            flex-wrap: wrap;
        }
        
        .article-price {
            font-size: 2rem;
            font-weight: bold;
            color: #D2691E;
            margin-right: auto;
        }
        
        .article-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .article-description {
            font-size: 1.1rem;
            color: #444;
            line-height: 1.8;
            margin-bottom: 3rem;
            white-space: pre-line;
        }
        
        .umkm-section {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .umkm-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .umkm-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #D2691E;
        }
        
        .umkm-avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #D2691E;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .umkm-info h3 {
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .umkm-info p {
            color: #666;
            margin-bottom: 0.25rem;
        }
        
        .umkm-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .umkm-detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            background: white;
            border-radius: 10px;
            border-left: 4px solid #D2691E;
        }
        
        .contact-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #25D366;
            color: white;
        }
        
        .btn-primary:hover {
            background: #128C7E;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #007bff;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .related-section {
            margin-top: 4rem;
        }
        
        .related-title {
            text-align: center;
            color: white;
            font-size: 2rem;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .related-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 45px rgba(0,0,0,0.2);
        }
        
        .related-image {
            width: 100%;
            height: 150px;
            background: linear-gradient(45deg, #f0f2f5, #e1e8ed);
            position: relative;
        }
        
        .related-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .related-content {
            padding: 1.5rem;
        }
        
        .related-title-text {
            font-size: 1.1rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .related-price {
            color: #D2691E;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .related-umkm {
            color: #666;
            font-size: 0.9rem;
        }
        
        .placeholder-image {
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(45deg, #f0f2f5, #e1e8ed);
            color: #666;
            font-size: 4rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .article-title {
                font-size: 1.8rem;
            }
            
            .article-content {
                padding: 2rem;
            }
            
            .article-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .article-price {
                margin-right: 0;
            }
            
            .umkm-header {
                flex-direction: column;
                text-align: center;
            }
            
            .contact-actions {
                flex-direction: column;
            }
            
            .related-grid {
                grid-template-columns: 1fr;
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
        <a href="artikel.php" class="back-button">
            ⬅️ Kembali ke Artikel
        </a>
        
        <div class="article-detail">
            <div class="article-header">
                <?php if ($article['gambar']): ?>
                    <img src="../uploads/artikel_images/<?php echo htmlspecialchars($article['gambar']); ?>" 
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
                            <img src="../uploads/profile_images/<?php echo htmlspecialchars($article['umkm_image']); ?>" 
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
        <div class="related-section">
            <h2 class="related-title">🌟 Artikel Terkait</h2>
            
            <div class="related-grid">
                <?php foreach ($related_articles as $related): ?>
                    <div class="related-card" onclick="location.href='detail.php?id=<?php echo $related['id']; ?>'">
                        <div class="related-image">
                            <?php if ($related['gambar']): ?>
                                <img src="../uploads/artikel_images/<?php echo htmlspecialchars($related['gambar']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['judul']); ?>">
                            <?php else: ?>
                                <div class="placeholder-image" style="font-size: 2rem;">
                                    📷
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="related-content">
                            <h4 class="related-title-text"><?php echo htmlspecialchars($related['judul']); ?></h4>
                            <div class="related-price"><?php echo formatPrice($related['harga']); ?></div>
                            <div class="related-umkm">🏪 <?php echo htmlspecialchars($related['business_name']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Smooth scroll for back button
        document.querySelector('.back-button').addEventListener('click', function(e) {
            // Let the default behavior happen, but add smooth transition
            setTimeout(() => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, 100);
        });
        
        // Add loading animation for images
        document.querySelectorAll('img').forEach(img => {
            img.addEventListener('load', function() {
                this.style.opacity = '1';
            });
            img.style.opacity = '0';
            img.style.transition = 'opacity 0.3s ease';
        });
        
        // Format phone number for WhatsApp link
        function formatPhoneForWhatsApp(phone) {
            // Remove leading zero and add 62 for Indonesia
            return '62' + phone.substring(1);
        }
    </script>
</body>
</html>