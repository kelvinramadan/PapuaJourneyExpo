<?php
// admin/adminwisata.php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_wisata'])) {
    $db = getDbConnection();
    $judul = mysqli_real_escape_string($db, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($db, $_POST['deskripsi']);
    $harga = floatval($_POST['harga']);
    $kategori = mysqli_real_escape_string($db, $_POST['kategori']);
    $alamat = mysqli_real_escape_string($db, $_POST['alamat']);
    $jam_buka = mysqli_real_escape_string($db, $_POST['jam_buka']);
    
    // Handle file upload
    $photo_name = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed_types)) {
            $photo_name = 'wisata_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $photo_name;
            
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $error_message = "Gagal mengupload foto.";
            }
        } else {
            $error_message = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, GIF, atau WebP.";
        }
    } else {
        $error_message = "Silakan pilih foto untuk diupload.";
    }
    
    // Insert data if no error
    if (empty($error_message)) {
        $sql = "INSERT INTO wisata (judul, deskripsi, harga, kategori, alamat, jam_buka, photo) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "ssdssss", $judul, $deskripsi, $harga, $kategori, $alamat, $jam_buka, $photo_name);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Data wisata berhasil ditambahkan!";
            // Clear form data after successful submission
            $_POST = array();
        } else {
            $error_message = "Error: " . mysqli_error($db);
            // Delete uploaded file if database insert fails
            if (file_exists($upload_dir . $photo_name)) {
                unlink($upload_dir . $photo_name);
            }
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($db);
}

// Handle delete request
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db = getDbConnection();
    
    // Get photo filename first
    $query = "SELECT photo FROM wisata WHERE id = ?";
    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Delete the file
        $photo_path = '../uploads/' . $row['photo'];
        if (file_exists($photo_path)) {
            unlink($photo_path);
        }
        
        // Delete from database
        $delete_query = "DELETE FROM wisata WHERE id = ?";
        $delete_stmt = mysqli_prepare($db, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $success_message = "Data wisata berhasil dihapus!";
        } else {
            $error_message = "Gagal menghapus data wisata.";
        }
        mysqli_stmt_close($delete_stmt);
    }
    mysqli_stmt_close($stmt);
    mysqli_close($db);
}

// Get tourism data with pagination
$db = getDbConnection();
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM wisata";
$count_result = mysqli_query($db, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Get wisata data
$search = isset($_GET['search']) ? mysqli_real_escape_string($db, $_GET['search']) : '';
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($db, $_GET['category']) : '';

$where_conditions = [];
if (!empty($search)) {
    $where_conditions[] = "(judul LIKE '%$search%' OR deskripsi LIKE '%$search%' OR alamat LIKE '%$search%')";
}
if (!empty($category_filter)) {
    $where_conditions[] = "kategori = '$category_filter'";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$wisata_query = "SELECT * FROM wisata $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$wisata_list = mysqli_query($db, $wisata_query);

// Get categories for filter
$categories_query = "SELECT DISTINCT kategori FROM wisata ORDER BY kategori";
$categories_result = mysqli_query($db, $categories_query);

// Set page title for header
$page_title = 'Wisata Management';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Wisata - Papua Journey</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .wisata-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .wisata-card {
            background: var(--card-bg);
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
        }
        
        .wisata-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .wisata-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .wisata-content {
            padding: 1rem;
        }
        
        .wisata-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .wisata-description {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .wisata-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        .wisata-detail {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: var(--text-secondary);
        }
        
        .filter-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .filter-section > * {
            flex: 1;
            min-width: 200px;
        }
        
        .preview-section {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }
        
        .form-section {
            flex: 1;
            min-width: 0;
        }
        
        .preview-card {
            flex: 0 0 300px;
            position: sticky;
            top: 2rem;
        }
        
        @media (max-width: 992px) {
            .preview-section {
                flex-direction: column;
            }
            
            .preview-card {
                position: static;
                flex: 1;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'components/header.php'; ?>
            
            <div class="content-wrapper">
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success fade-in">
                        <span>✓</span>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error fade-in">
                        <span>✕</span>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Add New Wisata Section -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h3 class="card-title">Tambah Wisata Baru</h3>
                        <button class="btn btn-outline btn-sm" onclick="toggleForm()" id="toggleBtn">
                            <span>▼</span> Expand
                        </button>
                    </div>
                    <div class="card-body" id="addForm" style="display: none;">
                        <form action="" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()" id="wisataForm">
                            <div class="preview-section">
                                <div class="form-section">
                                    <div class="grid grid-2">
                                        <div class="form-group">
                                            <label for="judul" class="form-label">Judul Wisata</label>
                                            <input type="text" class="form-control" id="judul" name="judul" 
                                                   value="<?php echo isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : ''; ?>" 
                                                   required onkeyup="updatePreview()">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="kategori" class="form-label">Kategori</label>
                                            <select class="form-control form-select" id="kategori" name="kategori" required onchange="updatePreview()">
                                                <option value="">Pilih kategori</option>
                                                <option value="budaya" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'budaya') ? 'selected' : ''; ?>>Budaya</option>
                                                <option value="alam" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'alam') ? 'selected' : ''; ?>>Alam</option>
                                                <option value="kuliner" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'kuliner') ? 'selected' : ''; ?>>Kuliner</option>
                                                <option value="sejarah" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'sejarah') ? 'selected' : ''; ?>>Sejarah</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="deskripsi" class="form-label">Deskripsi</label>
                                        <textarea class="form-control form-textarea" id="deskripsi" name="deskripsi" 
                                                  required placeholder="Deskripsikan tempat wisata secara detail..." 
                                                  onkeyup="updatePreview()"><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="grid grid-2">
                                        <div class="form-group">
                                            <label for="harga" class="form-label">Harga Tiket (Rp)</label>
                                            <input type="number" class="form-control" id="harga" name="harga" min="0" step="1000" 
                                                   value="<?php echo isset($_POST['harga']) ? $_POST['harga'] : ''; ?>" 
                                                   required onkeyup="updatePreview()">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="jam_buka" class="form-label">Jam Buka</label>
                                            <input type="text" class="form-control" id="jam_buka" name="jam_buka" 
                                                   value="<?php echo isset($_POST['jam_buka']) ? htmlspecialchars($_POST['jam_buka']) : ''; ?>" 
                                                   required placeholder="Contoh: 08:00 - 17:00" onkeyup="updatePreview()">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="alamat" class="form-label">Alamat</label>
                                        <textarea class="form-control" id="alamat" name="alamat" rows="2" 
                                                  required placeholder="Alamat lengkap tempat wisata" 
                                                  onkeyup="updatePreview()"><?php echo isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : ''; ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="photo" class="form-label">Foto Wisata</label>
                                        <input type="file" class="form-control form-control-file" id="photo" name="photo" 
                                               accept="image/*" required onchange="previewImage(this, 'imagePreview'); updatePreview();">
                                        <small class="form-text text-muted">Format: JPG, JPEG, PNG, GIF, WebP. Max: 5MB</small>
                                    </div>
                                    
                                    <button type="submit" name="add_wisata" class="btn btn-primary btn-lg">
                                        <span>➕</span> Tambah Wisata
                                    </button>
                                </div>
                                
                                <!-- Live Preview -->
                                <div class="preview-card">
                                    <h4 style="margin-bottom: 1rem;">Live Preview</h4>
                                    <div class="wisata-card">
                                        <div id="imagePreview" class="image-preview" style="height: 200px;">
                                            <div class="image-preview-placeholder">
                                                <span>📷</span>
                                                <p>Preview Gambar</p>
                                            </div>
                                        </div>
                                        <div class="wisata-content">
                                            <h5 class="wisata-title" id="previewTitle">Judul Wisata</h5>
                                            <p class="wisata-description" id="previewDescription">Deskripsi wisata akan muncul di sini...</p>
                                            <div class="wisata-details">
                                                <div class="wisata-detail">
                                                    <span>💰</span>
                                                    <span id="previewPrice">Rp 0</span>
                                                </div>
                                                <div class="wisata-detail">
                                                    <span>⏰</span>
                                                    <span id="previewTime">-</span>
                                                </div>
                                            </div>
                                            <span class="badge badge-info" id="previewCategory">Kategori</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Wisata List Section -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Daftar Wisata (<?php echo $total_records; ?> Total)</h3>
                        <button class="btn btn-outline btn-sm" onclick="window.print()">
                            <span>🖨️</span> Print
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="filter-section">
                            <div class="search-box">
                                <input type="text" class="form-control" placeholder="Cari wisata..." 
                                       id="searchInput" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <select class="form-control form-select" id="categoryFilter">
                                <option value="">Semua Kategori</option>
                                <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?php echo $cat['kategori']; ?>" 
                                            <?php echo $category_filter == $cat['kategori'] ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($cat['kategori']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <button class="btn btn-primary" onclick="applyFilters()">
                                <span>🔍</span> Filter
                            </button>
                        </div>
                        
                        <?php if (mysqli_num_rows($wisata_list) > 0): ?>
                            <div class="wisata-grid">
                                <?php while ($wisata = mysqli_fetch_assoc($wisata_list)): ?>
                                    <div class="wisata-card">
                                        <img src="../uploads/<?php echo htmlspecialchars($wisata['photo']); ?>" 
                                             alt="<?php echo htmlspecialchars($wisata['judul']); ?>" 
                                             class="wisata-image">
                                        <div class="wisata-content">
                                            <h4 class="wisata-title"><?php echo htmlspecialchars($wisata['judul']); ?></h4>
                                            <p class="wisata-description"><?php echo htmlspecialchars($wisata['deskripsi']); ?></p>
                                            
                                            <div class="wisata-details">
                                                <div class="wisata-detail">
                                                    <span>💰</span>
                                                    <span>Rp <?php echo number_format($wisata['harga'], 0, ',', '.'); ?></span>
                                                </div>
                                                <div class="wisata-detail">
                                                    <span>⏰</span>
                                                    <span><?php echo htmlspecialchars($wisata['jam_buka']); ?></span>
                                                </div>
                                                <div class="wisata-detail">
                                                    <span>📍</span>
                                                    <span><?php echo htmlspecialchars(substr($wisata['alamat'], 0, 30)) . '...'; ?></span>
                                                </div>
                                            </div>
                                            
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <span class="badge badge-<?php echo $wisata['kategori'] == 'alam' ? 'success' : ($wisata['kategori'] == 'budaya' ? 'warning' : 'info'); ?>">
                                                    <?php echo ucfirst($wisata['kategori']); ?>
                                                </span>
                                                <div class="action-buttons">
                                                    <button class="btn btn-outline btn-sm" onclick="editWisata(<?php echo $wisata['id']; ?>)">
                                                        ✏️
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteWisata(<?php echo $wisata['id']; ?>)">
                                                        🗑️
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" 
                                           class="btn btn-outline btn-sm">Previous</a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" 
                                           class="btn btn-<?php echo $i == $page ? 'primary' : 'outline'; ?> btn-sm"><?php echo $i; ?></a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>" 
                                           class="btn btn-outline btn-sm">Next</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 3rem 0;">
                                <div style="font-size: 4rem; margin-bottom: 1rem;">🏝️</div>
                                <p style="color: var(--text-secondary);">Belum ada data wisata.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        // Toggle add form
        function toggleForm() {
            const form = document.getElementById('addForm');
            const btn = document.getElementById('toggleBtn');
            if (form.style.display === 'none') {
                form.style.display = 'block';
                btn.innerHTML = '<span>▲</span> Collapse';
                // Auto-collapse sidebar on mobile or when expanding form
                if (window.innerWidth < 1200) {
                    autoCollapseSidebar();
                }
            } else {
                form.style.display = 'none';
                btn.innerHTML = '<span>▼</span> Expand';
            }
        }
        
        // Update live preview
        function updatePreview() {
            document.getElementById('previewTitle').textContent = document.getElementById('judul').value || 'Judul Wisata';
            document.getElementById('previewDescription').textContent = document.getElementById('deskripsi').value || 'Deskripsi wisata akan muncul di sini...';
            
            const price = document.getElementById('harga').value;
            document.getElementById('previewPrice').textContent = price ? 'Rp ' + new Intl.NumberFormat('id-ID').format(price) : 'Rp 0';
            
            document.getElementById('previewTime').textContent = document.getElementById('jam_buka').value || '-';
            
            const category = document.getElementById('kategori').value;
            const categoryBadge = document.getElementById('previewCategory');
            categoryBadge.textContent = category ? category.charAt(0).toUpperCase() + category.slice(1) : 'Kategori';
            
            // Update badge color based on category
            categoryBadge.className = 'badge badge-' + (category === 'alam' ? 'success' : (category === 'budaya' ? 'warning' : 'info'));
        }
        
        // Validate form
        function validateForm() {
            const fileInput = document.getElementById('photo');
            const file = fileInput.files[0];
            
            if (file && file.size > 5 * 1024 * 1024) {
                showToast('Ukuran file terlalu besar. Maksimal 5MB.', 'error');
                return false;
            }
            
            return true;
        }
        
        // Delete wisata
        function deleteWisata(id) {
            if (confirmDelete('Apakah Anda yakin ingin menghapus wisata ini?')) {
                window.location.href = '?delete=' + id;
            }
        }
        
        // Edit wisata (placeholder)
        function editWisata(id) {
            showToast('Fitur edit akan segera tersedia', 'info');
        }
        
        // Apply filters
        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            window.location.href = `?search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}`;
        }
        
        // Enter key to search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>

<?php 
mysqli_close($db); 
?>