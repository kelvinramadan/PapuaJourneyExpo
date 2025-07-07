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
$success_message = '';
$error_message = '';

// Handle form submission for adding new article
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_article'])) {
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga = floatval($_POST['harga']);
    $kategori = $_POST['kategori'];
    $jam_buka = trim($_POST['jam_buka']);
    $alamat = trim($_POST['alamat']);
    
    // Validation
    if (empty($judul) || empty($deskripsi) || $harga <= 0 || empty($kategori)) {
        $error_message = 'Semua field wajib diisi dan harga harus lebih dari 0!';
    } else {
        $gambar_name = null;
        
        // Handle image upload
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $file_type = $_FILES['gambar']['type'];
            $file_size = $_FILES['gambar']['size'];
            
            if (!in_array($file_type, $allowed_types)) {
                $error_message = 'Hanya file JPG, PNG, dan GIF yang diperbolehkan!';
            } elseif ($file_size > $max_size) {
                $error_message = 'Ukuran file maksimal 5MB!';
            } else {
                $upload_dir = '../uploads/artikel_images/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                $gambar_name = 'artikel_' . $umkm_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $gambar_name;
                
                if (!move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_path)) {
                    $error_message = 'Gagal mengupload gambar!';
                    $gambar_name = null;
                }
            }
        }
        
        // Insert artikel if no error
        if (empty($error_message)) {
            $stmt = $db->prepare("INSERT INTO artikel (umkm_id, judul, deskripsi, harga, kategori, gambar, jam_buka, alamat) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdssss", $umkm_id, $judul, $deskripsi, $harga, $kategori, $gambar_name, $jam_buka, $alamat);
            
            if ($stmt->execute()) {
                $success_message = 'Artikel berhasil ditambahkan!';
                // Reset form
                $_POST = array();
            } else {
                $error_message = 'Terjadi kesalahan saat menyimpan artikel!';
                // Delete uploaded image if database insert failed
                if ($gambar_name && file_exists($upload_dir . $gambar_name)) {
                    unlink($upload_dir . $gambar_name);
                }
            }
            $stmt->close();
        }
    }
}

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

// Get UMKM data for header
$stmt = $db->prepare("SELECT business_name, profile_image, email FROM umkm WHERE id = ?");
$stmt->bind_param("i", $umkm_id);
$stmt->execute();
$result = $stmt->get_result();
$umkm_data = $result->fetch_assoc();
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
    <div class="container">
        <div class="header">
            <h1>Dashboard UMKM - <?php echo htmlspecialchars($umkm_data['business_name']); ?></h1>
            <p>Kelola artikel dan promosi bisnis Anda</p>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('add')">Tambah Wisata Baru</div>
            <div class="tab" onclick="showTab('list')">Daftar Artikel</div>
        </div>
        
        <!-- Add Article Tab -->
        <div id="add-tab" class="tab-content active">
            <h2>Tambah Wisata Baru</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_article" value="1">
                
                <div class="form-group">
                    <label for="judul">Judul Wisata</label>
                    <input type="text" name="judul" id="judul" required 
                           placeholder="Masukkan judul wisata">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="kategori">Kategori</label>
                        <select name="kategori" id="kategori" required>
                            <option value="">Pilih kategori</option>
                            <option value="jasa">🔧 Jasa</option>
                            <option value="event">🎉 Event</option>
                            <option value="kuliner">🍽️ Kuliner</option>
                            <option value="kerajinan">🎨 Kerajinan</option>
                            <option value="wisata">🏝️ Wisata</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="harga">Harga Tiket (Rp)</label>
                        <input type="number" name="harga" id="harga" required min="0" 
                               placeholder="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea name="deskripsi" id="deskripsi" required 
                              placeholder="Deskripsikan tempat wisata secara detail..."></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="jam_buka">Jam Buka</label>
                        <input type="text" name="jam_buka" id="jam_buka" 
                               placeholder="Contoh: 08:00 - 17:00">
                    </div>
                    
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <input type="text" name="alamat" id="alamat" 
                               placeholder="Alamat lengkap tempat wisata">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="gambar">Foto Wisata</label>
                    <div class="file-input">
                        <input type="file" name="gambar" id="gambar" accept="image/*">
                        <label for="gambar" class="file-input-label">
                            📁 Pilih File (JPG, PNG, GIF - Max 5MB)
                        </label>
                    </div>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">+ Tambah Wisata</button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">Tutup</button>
                </div>
            </form>
        </div>
        
        <!-- Articles List Tab -->
        <div id="list-tab" class="tab-content">
            <h2>Daftar Artikel</h2>
            
            <?php if (empty($articles)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>Belum Ada Artikel</h3>
                    <p>Mulai promosikan produk/jasa Anda dengan membuat artikel pertama!</p>
                    <button class="btn btn-primary" onclick="showTab('add')">
                        ➕ Buat Artikel Pertama
                    </button>
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
                            <a href="?page=<?php echo $page - 1; ?>">‹ Prev</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>">Next ›</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));
            
            // Show selected tab
            document.querySelector(`#${tabName}-tab`).classList.add('active');
            event.target.classList.add('active');
        }
        
        function resetForm() {
            document.querySelector('form').reset();
            updatePreview();
        }
        
        function updatePreview() {
            const judul = document.getElementById('judul').value || 'Judul Wisata';
            const deskripsi = document.getElementById('deskripsi').value || 'Deskripsi wisata akan muncul di sini...';
            const harga = document.getElementById('harga').value || '0';
            const kategori = document.getElementById('kategori').value || 'KATEGORI';
            
            document.querySelector('.sidebar-title').textContent = judul;
            document.querySelector('.sidebar-desc').textContent = deskripsi.substring(0, 100) + '...';
            document.querySelector('.sidebar-price').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(harga);
            document.querySelector('.sidebar-category').textContent = kategori.toUpperCase();
        }
        
        // Add event listeners for live preview
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = ['judul', 'deskripsi', 'harga', 'kategori'];
            inputs.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('input', updatePreview);
                }
            });
            
            // Auto-close alerts
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
        
        // File input label update
        document.getElementById('gambar').addEventListener('change', function() {
            const label = document.querySelector('.file-input-label');
            if (this.files.length > 0) {
                label.textContent = '📁 ' + this.files[0].name;
            } else {
                label.textContent = '📁 Pilih File (JPG, PNG, GIF - Max 5MB)';
            }
        });
    </script>
</body>
</html>