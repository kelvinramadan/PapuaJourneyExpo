<?php
// umkm/umkm_dashboard.php
session_start();

require_once '../config/database.php';

// Check if user is logged in and is UMKM
if (!isset($_SESSION['umkm_id']) || $_SESSION['user_type'] != 'umkm') {
    header('Location: ../login.php');
    exit();
}

// Include sidebar
include 'sidebar.php'; 

$db = getDbConnection();
$umkm_id = $_SESSION['umkm_id'];

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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard UMKM - UMKM Papua</title>
    <link rel="stylesheet" href="umkmm.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Main Content -->
        <div class="main-content">
            <!-- Articles Section -->
            <div class="articles-section">
                <?php if (empty($articles)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📝</div>
                        <h3>Belum Ada Artikel</h3>
                        <p>Mulai promosikan produk/jasa Anda dengan membuat artikel pertama!</p>
                        <a href="add_artikel.php" class="btn-primary">
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
                                    
                                    <div class="article-kategori">
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
                                </div>
                                
                                <div class="article-content">
                                    <h3 class="article-title"><?php echo htmlspecialchars($article['judul']); ?></h3>
                                    
                                    <p class="article-description">
                                        <?php echo htmlspecialchars(substr($article['deskripsi'], 0, 120)) . '...'; ?>
                                    </p>
                                    
                                    <div class="article-meta">
                                        <div class="article-price">
                                            Rp <?php echo number_format($article['harga'], 0, ',', '.'); ?>
                                        </div>
                                        <div class="article-date">
                                            <?php echo date('d M Y', strtotime($article['created_at'])); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="article-actions">
                                        <a href="edit_artikel.php?id=<?php echo $article['id']; ?>" class="btn-edit">
                                            ✏️ Edit
                                        </a>
                                        <a href="?delete=1&id=<?php echo $article['id']; ?>" 
                                           class="btn-delete"
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