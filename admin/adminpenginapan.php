<?php
// admin/adminpenginapan.php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$error_message = '';
$success_message = '';

// Initialize database connection
$db = getDbConnection();

// Handle delete request first (before any output)
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get photo filename first
    $query = "SELECT photo FROM penginapan WHERE id = ?";
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
        $delete_query = "DELETE FROM penginapan WHERE id = ?";
        $delete_stmt = mysqli_prepare($db, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, "i", $id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $success_message = "Data penginapan berhasil dihapus!";
        } else {
            $error_message = "Gagal menghapus data penginapan.";
        }
        mysqli_stmt_close($delete_stmt);
    }
    mysqli_stmt_close($stmt);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_penginapan'])) {
    $judul = mysqli_real_escape_string($db, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($db, $_POST['deskripsi']);
    $harga = floatval($_POST['harga']);
    $lokasi = mysqli_real_escape_string($db, $_POST['lokasi']);
    $tipe = mysqli_real_escape_string($db, $_POST['tipe']);
    $fasilitas = mysqli_real_escape_string($db, $_POST['fasilitas']);
    $kapasitas = intval($_POST['kapasitas']);
    $rating = floatval($_POST['rating']);
    
    // Handle file upload
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed_types)) {
            $photo = 'penginapan_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $photo;
            
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                $error_message = "Gagal mengupload foto.";
            }
        } else {
            $error_message = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, GIF, atau WebP.";
        }
    } else {
        $error_message = "Silakan pilih foto untuk diupload.";
    }
    
    // Insert data if no errors
    if (empty($error_message) && !empty($photo)) {
        // Add kapasitas and rating to the table if not exists
        $alter_query = "ALTER TABLE penginapan 
                       ADD COLUMN IF NOT EXISTS kapasitas INT DEFAULT 2,
                       ADD COLUMN IF NOT EXISTS rating DECIMAL(3,2) DEFAULT 0.00";
        mysqli_query($db, $alter_query);
        
        $query = "INSERT INTO penginapan (judul, deskripsi, harga, lokasi, tipe, fasilitas, photo, kapasitas, rating) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ssdssssis", $judul, $deskripsi, $harga, $lokasi, $tipe, $fasilitas, $photo, $kapasitas, $rating);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Data penginapan berhasil ditambahkan!";
            // Clear form data after successful submission
            $_POST = array();
        } else {
            $error_message = "Gagal menambahkan data penginapan: " . mysqli_error($db);
            // Delete uploaded file if database insert fails
            if (file_exists($upload_dir . $photo)) {
                unlink($upload_dir . $photo);
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Get lodging data with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get total count
$count_query = "SELECT COUNT(*) as total FROM penginapan";
$count_result = mysqli_query($db, $count_query);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);

// Get lodging data with filters
$search = isset($_GET['search']) ? mysqli_real_escape_string($db, $_GET['search']) : '';
$type_filter = isset($_GET['type']) ? mysqli_real_escape_string($db, $_GET['type']) : '';
$price_filter = isset($_GET['price']) ? mysqli_real_escape_string($db, $_GET['price']) : '';

$where_conditions = [];
if (!empty($search)) {
    $where_conditions[] = "(judul LIKE '%$search%' OR deskripsi LIKE '%$search%' OR lokasi LIKE '%$search%')";
}
if (!empty($type_filter)) {
    $where_conditions[] = "tipe = '$type_filter'";
}
if (!empty($price_filter)) {
    switch ($price_filter) {
        case 'low':
            $where_conditions[] = "harga < 500000";
            break;
        case 'medium':
            $where_conditions[] = "harga BETWEEN 500000 AND 1000000";
            break;
        case 'high':
            $where_conditions[] = "harga > 1000000";
            break;
    }
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$query = "SELECT * FROM penginapan $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($db, $query);

// Get types for filter
$types_query = "SELECT DISTINCT tipe FROM penginapan ORDER BY tipe";
$types_result = mysqli_query($db, $types_query);

// Set page title for header
$page_title = 'Penginapan Management';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Penginapan - Papua Journey</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .penginapan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .penginapan-card {
            background: var(--card-bg);
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
        }
        
        .penginapan-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }
        
        .penginapan-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            position: relative;
        }
        
        .penginapan-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .penginapan-content {
            padding: 1.25rem;
        }
        
        .penginapan-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .penginapan-location {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
        }
        
        .penginapan-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
        }
        
        .penginapan-features {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            background: #F3F4F6;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        .rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #F59E0B;
            font-size: 0.875rem;
        }
        
        .facilities-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        
        .facility-tag {
            padding: 0.25rem 0.5rem;
            background: #E0F2FE;
            color: #0369A1;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        
        @media (max-width: 768px) {
            .penginapan-grid {
                grid-template-columns: 1fr;
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
                
                <!-- Add New Penginapan Section -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h3 class="card-title">Tambah Penginapan Baru</h3>
                        <button class="btn btn-outline btn-sm" onclick="toggleForm()" id="toggleBtn">
                            <span>▼</span> Expand
                        </button>
                    </div>
                    <div class="card-body" id="addForm" style="display: none;">
                        <form action="" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                            <div class="grid grid-3">
                                <div class="form-group">
                                    <label for="judul" class="form-label">Nama Penginapan</label>
                                    <input type="text" class="form-control" id="judul" name="judul" 
                                           value="<?php echo isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="tipe" class="form-label">Tipe Penginapan</label>
                                    <select class="form-control form-select" id="tipe" name="tipe" required>
                                        <option value="">Pilih Tipe</option>
                                        <option value="hotel" <?php echo (isset($_POST['tipe']) && $_POST['tipe'] == 'hotel') ? 'selected' : ''; ?>>Hotel</option>
                                        <option value="villa" <?php echo (isset($_POST['tipe']) && $_POST['tipe'] == 'villa') ? 'selected' : ''; ?>>Villa</option>
                                        <option value="resort" <?php echo (isset($_POST['tipe']) && $_POST['tipe'] == 'resort') ? 'selected' : ''; ?>>Resort</option>
                                        <option value="homestay" <?php echo (isset($_POST['tipe']) && $_POST['tipe'] == 'homestay') ? 'selected' : ''; ?>>Homestay</option>
                                        <option value="guest_house" <?php echo (isset($_POST['tipe']) && $_POST['tipe'] == 'guest_house') ? 'selected' : ''; ?>>Guest House</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="lokasi" class="form-label">Lokasi</label>
                                    <input type="text" class="form-control" id="lokasi" name="lokasi" 
                                           value="<?php echo isset($_POST['lokasi']) ? htmlspecialchars($_POST['lokasi']) : ''; ?>" 
                                           placeholder="Contoh: Jayapura, Papua" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea class="form-control form-textarea" id="deskripsi" name="deskripsi" 
                                          rows="4" required><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
                            </div>
                            
                            <div class="grid grid-4">
                                <div class="form-group">
                                    <label for="harga" class="form-label">Harga per Malam (Rp)</label>
                                    <input type="number" class="form-control" id="harga" name="harga" min="0" step="1000" 
                                           value="<?php echo isset($_POST['harga']) ? $_POST['harga'] : ''; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="kapasitas" class="form-label">Kapasitas Tamu</label>
                                    <input type="number" class="form-control" id="kapasitas" name="kapasitas" 
                                           min="1" max="20" value="<?php echo isset($_POST['kapasitas']) ? $_POST['kapasitas'] : '2'; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="rating" class="form-label">Rating (0-5)</label>
                                    <input type="number" class="form-control" id="rating" name="rating" 
                                           min="0" max="5" step="0.1" value="<?php echo isset($_POST['rating']) ? $_POST['rating'] : '0'; ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="photo" class="form-label">Foto Utama</label>
                                    <input type="file" class="form-control form-control-file" id="photo" name="photo" 
                                           accept="image/*" required onchange="previewImage(this, 'imagePreview')">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="fasilitas" class="form-label">Fasilitas</label>
                                <textarea class="form-control" id="fasilitas" name="fasilitas" rows="3" 
                                          placeholder="Pisahkan dengan koma. Contoh: WiFi, AC, Kolam Renang, Parkir, Breakfast" 
                                          required><?php echo isset($_POST['fasilitas']) ? htmlspecialchars($_POST['fasilitas']) : ''; ?></textarea>
                            </div>
                            
                            <div class="grid grid-2">
                                <div id="imagePreview" class="image-preview">
                                    <div class="image-preview-placeholder">
                                        <span>📷</span>
                                        <p>Preview akan muncul di sini</p>
                                    </div>
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 1rem;">
                                    <button type="submit" name="add_penginapan" class="btn btn-primary btn-lg">
                                        <span>➕</span> Tambah Penginapan
                                    </button>
                                    <button type="reset" class="btn btn-outline btn-lg" onclick="document.getElementById('imagePreview').innerHTML = '<div class=\'image-preview-placeholder\'><span>📷</span><p>Preview akan muncul di sini</p></div>'">
                                        <span>🔄</span> Reset Form
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Penginapan List Section -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Daftar Penginapan (<?php echo $total_records; ?> Total)</h3>
                        <div style="display: flex; gap: 0.75rem;">
                            <button class="btn btn-outline btn-sm" onclick="window.print()">
                                <span>🖨️</span> Print
                            </button>
                            <button class="btn btn-outline btn-sm" onclick="exportData()">
                                <span>📥</span> Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="filter-section">
                            <div class="search-box">
                                <input type="text" class="form-control" placeholder="Cari penginapan..." 
                                       id="searchInput" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <select class="form-control form-select" id="typeFilter">
                                <option value="">Semua Tipe</option>
                                <?php while ($type = mysqli_fetch_assoc($types_result)): ?>
                                    <option value="<?php echo $type['tipe']; ?>" 
                                            <?php echo $type_filter == $type['tipe'] ? 'selected' : ''; ?>>
                                        <?php echo ucfirst(str_replace('_', ' ', $type['tipe'])); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <select class="form-control form-select" id="priceFilter">
                                <option value="">Semua Harga</option>
                                <option value="low" <?php echo $price_filter == 'low' ? 'selected' : ''; ?>>< Rp 500rb</option>
                                <option value="medium" <?php echo $price_filter == 'medium' ? 'selected' : ''; ?>>Rp 500rb - 1jt</option>
                                <option value="high" <?php echo $price_filter == 'high' ? 'selected' : ''; ?>>> Rp 1jt</option>
                            </select>
                            <button class="btn btn-primary" onclick="applyFilters()">
                                <span>🔍</span> Filter
                            </button>
                        </div>
                        
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <div class="penginapan-grid">
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <div class="penginapan-card">
                                        <div style="position: relative;">
                                            <img src="../uploads/<?php echo htmlspecialchars($row['photo']); ?>" 
                                                 alt="<?php echo htmlspecialchars($row['judul']); ?>" 
                                                 class="penginapan-image">
                                            <span class="penginapan-badge"><?php echo strtoupper($row['tipe']); ?></span>
                                        </div>
                                        <div class="penginapan-content">
                                            <h4 class="penginapan-title"><?php echo htmlspecialchars($row['judul']); ?></h4>
                                            <div class="penginapan-location">
                                                <span>📍</span>
                                                <span><?php echo htmlspecialchars($row['lokasi']); ?></span>
                                            </div>
                                            
                                            <div class="penginapan-price">
                                                Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?>
                                                <span style="font-size: 0.875rem; font-weight: 400; color: var(--text-secondary);">/malam</span>
                                            </div>
                                            
                                            <div class="penginapan-features">
                                                <div class="feature-item">
                                                    <span>👥</span>
                                                    <span><?php echo isset($row['kapasitas']) ? $row['kapasitas'] : '2'; ?> Tamu</span>
                                                </div>
                                                <div class="rating">
                                                    <span>⭐</span>
                                                    <span><?php echo isset($row['rating']) ? number_format($row['rating'], 1) : '0.0'; ?></span>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($row['fasilitas'])): ?>
                                                <div class="facilities-tags">
                                                    <?php 
                                                    $facilities = array_slice(explode(',', $row['fasilitas']), 0, 3);
                                                    foreach ($facilities as $facility): 
                                                    ?>
                                                        <span class="facility-tag"><?php echo trim($facility); ?></span>
                                                    <?php endforeach; ?>
                                                    <?php if (count(explode(',', $row['fasilitas'])) > 3): ?>
                                                        <span class="facility-tag">+<?php echo count(explode(',', $row['fasilitas'])) - 3; ?> lainnya</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="action-buttons" style="margin-top: 1rem;">
                                                <button class="btn btn-outline btn-sm" onclick="viewDetails(<?php echo $row['id']; ?>)">
                                                    <span>👁️</span> Detail
                                                </button>
                                                <button class="btn btn-outline btn-sm" onclick="editPenginapan(<?php echo $row['id']; ?>)">
                                                    <span>✏️</span> Edit
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="deletePenginapan(<?php echo $row['id']; ?>)">
                                                    <span>🗑️</span> Hapus
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&price=<?php echo urlencode($price_filter); ?>" 
                                           class="btn btn-outline btn-sm">Previous</a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&price=<?php echo urlencode($price_filter); ?>" 
                                           class="btn btn-<?php echo $i == $page ? 'primary' : 'outline'; ?> btn-sm"><?php echo $i; ?></a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type_filter); ?>&price=<?php echo urlencode($price_filter); ?>" 
                                           class="btn btn-outline btn-sm">Next</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 3rem 0;">
                                <div style="font-size: 4rem; margin-bottom: 1rem;">🏨</div>
                                <p style="color: var(--text-secondary);">Belum ada data penginapan.</p>
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
        
        // Delete penginapan
        function deletePenginapan(id) {
            if (confirmDelete('Apakah Anda yakin ingin menghapus penginapan ini?')) {
                window.location.href = '?delete=' + id;
            }
        }
        
        // View details (placeholder)
        function viewDetails(id) {
            showToast('Membuka detail penginapan...', 'info');
        }
        
        // Edit penginapan (placeholder)
        function editPenginapan(id) {
            showToast('Fitur edit akan segera tersedia', 'info');
        }
        
        // Export data (placeholder)
        function exportData() {
            showToast('Mengekspor data...', 'info');
        }
        
        // Apply filters
        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const type = document.getElementById('typeFilter').value;
            const price = document.getElementById('priceFilter').value;
            window.location.href = `?search=${encodeURIComponent(search)}&type=${encodeURIComponent(type)}&price=${encodeURIComponent(price)}`;
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
// Close database connection
mysqli_close($db);
?>