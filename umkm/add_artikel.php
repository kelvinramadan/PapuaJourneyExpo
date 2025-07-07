<?php
// umkm/add_artikel.php
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga = floatval($_POST['harga']);
    $kategori = $_POST['kategori'];
    
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
            $stmt = $db->prepare("INSERT INTO artikel (umkm_id, judul, deskripsi, harga, kategori, gambar) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issdss", $umkm_id, $judul, $deskripsi, $harga, $kategori, $gambar_name);
            
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
    <link rel="stylesheet" href="add_artikel.css">
    <title>Tambah Artikel - UMKM Papua</title>
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="header">
        <div class="header-content">
            <h1>🌺 UMKM Papua - Tambah Artikel</h1>
            <div class="header-right">
                <span><?php echo htmlspecialchars($umkm_data['business_name']); ?></span>
            </div>
        </div>
    </div>
    
    <div class="container">
        <a href="umkm_dashboard.php" class="back-link">← Kembali ke Dashboard</a>
        
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
        
        <div class="form-container">
            <div class="form-header">
                <h1>✨ Tambah Artikel Baru</h1>
                <p>Promosikan produk atau jasa UMKM Anda kepada masyarakat Papua</p>
                
                <div class="kategori-badges">
                    <span class="kategori-badge badge-jasa">🔧 Jasa</span>
                    <span class="kategori-badge badge-event">🎉 Event</span>
                    <span class="kategori-badge badge-kuliner">🍽️ Kuliner</span>
                    <span class="kategori-badge badge-kerajinan">🎨 Kerajinan</span>
                    <span class="kategori-badge badge-wisata">🏝️ Wisata</span>
                </div>
            </div>
            
            <form method="POST" enctype="multipart/form-data" id="artikelForm">
                <div class="form-group">
                    <label for="judul">📝 Judul Artikel</label>
                    <input type="text" name="judul" id="judul" required 
                           placeholder="Masukkan judul yang menarik untuk artikel Anda"
                           value="<?php echo isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="kategori">🏷️ Kategori</label>
                    <select name="kategori" id="kategori" required>
                        <option value="">Pilih Kategori</option>
                        <option value="jasa" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'jasa') ? 'selected' : ''; ?>>🔧 Jasa</option>
                        <option value="event" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'event') ? 'selected' : ''; ?>>🎉 Event</option>
                        <option value="kuliner" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'kuliner') ? 'selected' : ''; ?>>🍽️ Kuliner</option>
                        <option value="kerajinan" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'kerajinan') ? 'selected' : ''; ?>>🎨 Kerajinan</option>
                        <option value="wisata" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'wisata') ? 'selected' : ''; ?>>🏝️ Wisata</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="harga">💰 Harga (Rp)</label>
                    <input type="number" name="harga" id="harga" required min="0" step="0.01"
                           placeholder="Masukkan harga dalam Rupiah"
                           value="<?php echo isset($_POST['harga']) ? htmlspecialchars($_POST['harga']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="deskripsi">📄 Deskripsi Lengkap</label>
                    <textarea name="deskripsi" id="deskripsi" required 
                              placeholder="Jelaskan detail produk/jasa Anda, keunggulan, dan informasi penting lainnya"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="gambar">🖼️ Gambar Artikel</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="gambar" id="gambar" class="file-input" accept="image/*" onchange="previewImage(this)">
                        <div class="file-input-btn" onclick="document.getElementById('gambar').click()">
                            📁 Pilih Gambar (JPG, PNG, GIF - Max 5MB)
                        </div>
                    </div>
                    <div class="image-preview" id="imagePreview" style="display: none;">
                        <img id="previewImg" src="" alt="Preview">
                    </div>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">✨ Publikasikan Artikel</button>
                    <a href="umkm_dashboard.php" class="btn btn-secondary">❌ Batal</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('imagePreview').style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Format harga dengan titik sebagai pemisah ribuan
        document.getElementById('harga').addEventListener('input', function() {
            let value = this.value.replace(/\./g, '');
            if (value !== '') {
                this.value = value;
            }
        });
        
        // Validasi form
        document.getElementById('artikelForm').addEventListener('submit', function(e) {
            const judul = document.getElementById('judul').value.trim();
            const deskripsi = document.getElementById('deskripsi').value.trim();
            const harga = document.getElementById('harga').value;
            const kategori = document.getElementById('kategori').value;
            
            if (judul.length < 10) {
                e.preventDefault();
                alert('Judul artikel minimal 10 karakter!');
                return;
            }
            
            if (deskripsi.length < 50) {
                e.preventDefault();
                alert('Deskripsi artikel minimal 50 karakter!');
                return;
            }
            
            if (parseFloat(harga) <= 0) {
                e.preventDefault();
                alert('Harga harus lebih dari 0!');
                return;
            }
            
            if (!kategori) {
                e.preventDefault();
                alert('Pilih kategori artikel!');
                return;
            }
        });
        
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
    </script>
</body>
</html>