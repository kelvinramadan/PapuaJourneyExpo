<?php
// umkm/umkm_dashboard.php - Enhanced version for integrated article upload
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
    $harga = floatval(str_replace([',', '.'], '', $_POST['harga'])); // Remove formatting before conversion
    $kategori = $_POST['kategori'];
    $status = 'active'; // Set status as active by default
    
    // Validation
    if (empty($judul) || empty($deskripsi) || $harga <= 0 || empty($kategori)) {
        $error_message = 'Semua field wajib diisi dan harga harus lebih dari 0!';
    } else {
        $gambar_name = null;
        
        // Handle image upload
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
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
            // Insert with current timestamp
            $stmt = $db->prepare("INSERT INTO artikel (umkm_id, judul, deskripsi, harga, kategori, gambar, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->bind_param("issdsss", $umkm_id, $judul, $deskripsi, $harga, $kategori, $gambar_name, $status);
            
            if ($stmt->execute()) {
                $success_message = 'Artikel berhasil ditambahkan dan sudah dapat dilihat di halaman UMKM!';
                // Reset form
                $_POST = array();
            } else {
                $error_message = 'Terjadi kesalahan saat menyimpan artikel: ' . $stmt->error;
                // Delete uploaded image if database insert failed
                if ($gambar_name && file_exists($upload_dir . $gambar_name)) {
                    unlink($upload_dir . $gambar_name);
                }
            }
            $stmt->close();
        }
    }
}

// Handle update artikel status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $artikel_id = intval($_POST['artikel_id']);
    $new_status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE artikel SET status = ?, updated_at = NOW() WHERE id = ? AND umkm_id = ?");
    $stmt->bind_param("sii", $new_status, $artikel_id, $umkm_id);
    
    if ($stmt->execute()) {
        $success_message = 'Status artikel berhasil diperbarui!';
    } else {
        $error_message = 'Gagal mengupdate status artikel!';
    }
    $stmt->close();
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

// Get search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get articles with pagination and filters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Build WHERE clause for filtering
$where_conditions = ["umkm_id = ?"];
$params = [$umkm_id];
$param_types = "i";

if (!empty($search)) {
    $where_conditions[] = "(judul LIKE ? OR deskripsi LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

if (!empty($kategori_filter)) {
    $where_conditions[] = "kategori = ?";
    $params[] = $kategori_filter;
    $param_types .= "s";
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total articles
$count_query = "SELECT COUNT(*) as total FROM artikel WHERE $where_clause";
$count_stmt = $db->prepare($count_query);
$count_stmt->bind_param($param_types, ...$params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_articles = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_articles / $limit);
$count_stmt->close();

// Get articles for current page
$articles_query = "SELECT id, judul, deskripsi, harga, kategori, gambar, status, created_at, updated_at FROM artikel WHERE $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$articles_stmt = $db->prepare($articles_query);
$articles_stmt->bind_param($param_types, ...$params);
$articles_stmt->execute();
$result = $articles_stmt->get_result();
$articles = $result->fetch_all(MYSQLI_ASSOC);
$articles_stmt->close();

// Get UMKM data for header
$stmt = $db->prepare("SELECT business_name, profile_image, email FROM umkm WHERE id = ?");
$stmt->bind_param("i", $umkm_id);
$stmt->execute();
$result = $stmt->get_result();
$umkm_data = $result->fetch_assoc();
$stmt->close();

// Get statistics
$stats_query = "SELECT 
    COUNT(*) as total_articles,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_articles,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_articles,
    AVG(harga) as avg_price
    FROM artikel WHERE umkm_id = ?";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->bind_param("i", $umkm_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

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
    <title>Dashboard UMKM - UMKM Papua</title>
    <link rel="stylesheet" href="umkmm.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Enhanced styles for better UI */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5rem;
        }

        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        /* Alert styles */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Tabs */
        .tabs {
            display: flex;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .tab {
            flex: 1;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            border-bottom: 3px solid transparent;
        }

        .tab:hover {
            background: #f8f9fa;
        }

        .tab.active {
            background: #007bff;
            color: white;
            border-bottom-color: #0056b3;
        }

        /* Tab content */
        .tab-content {
            display: none;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        /* Form styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        /* Fixed price input styling */
        .price-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .price-input-wrapper .currency-symbol {
            position: absolute;
            left: 15px;
            color: #666;
            font-weight: bold;
            z-index: 2;
        }

        .price-input-wrapper input {
            padding-left: 35px;
            text-align: right;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .file-input {
            position: relative;
        }

        .file-input input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: block;
            padding: 12px 15px;
            border: 2px dashed #007bff;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9ff;
        }

        .file-input-label:hover {
            background: #e7f3ff;
            border-color: #0056b3;
        }

        /* Enhanced Photo Upload Styles */
        .photo-upload-container {
            border: 2px dashed #007bff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            background: #f8f9ff;
            transition: all 0.3s ease;
        }

        .photo-upload-container:hover {
            border-color: #0056b3;
            background: #e7f3ff;
        }

        .image-preview {
            position: relative;
            width: 100%;
            max-width: 300px;
            height: 200px;
            margin: 0 auto 20px;
            border-radius: 8px;
            overflow: hidden;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .preview-placeholder {
            text-align: center;
            color: #666;
        }

        .preview-placeholder i {
            font-size: 3rem;
            margin-bottom: 10px;
            color: #007bff;
        }

        .preview-placeholder p {
            margin: 10px 0 5px 0;
            font-weight: 600;
            color: #333;
        }

        .preview-placeholder small {
            color: #666;
            font-size: 12px;
        }

        #previewImage {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }

        .preview-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .image-preview:hover .preview-overlay {
            opacity: 1;
        }

        .btn-remove-image,
        .btn-change-image {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #333;
            font-size: 14px;
        }

        .btn-remove-image {
            background: rgba(220, 53, 69, 0.9);
            color: white;
        }

        .btn-change-image {
            background: rgba(40, 167, 69, 0.9);
            color: white;
        }

        .btn-remove-image:hover,
        .btn-change-image:hover {
            transform: scale(1.05);
        }

        .upload-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .btn-upload {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-upload:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .upload-info {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #666;
            font-size: 12px;
        }

        .upload-info i {
            color: #007bff;
        }

        /* Image selected state */
        .photo-upload-container.has-image {
            border-color: #28a745;
            background: #f8fff8;
        }

        .photo-upload-container.has-image:hover {
            border-color: #1e7e34;
            background: #e8f5e8;
        }

        /* Buttons */
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-edit {
            background: #28a745;
            color: white;
            font-size: 12px;
            padding: 8px 15px;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            font-size: 12px;
            padding: 8px 15px;
        }

        /* Filter section */
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 200px 200px 120px;
            gap: 15px;
            align-items: end;
        }

        /* Articles grid */
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .article-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }

        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .article-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            font-size: 3rem;
            color: #dee2e6;
        }

        .article-kategori {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #28a745;
            color: white;
        }

        .status-inactive {
            background: #dc3545;
            color: white;
        }

        .article-content {
            padding: 20px;
        }

        .article-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .article-description {
            color: #666;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .article-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: #007bff;
        }

        .article-date {
            font-size: 0.9rem;
            color: #666;
        }

        .article-actions {
            display: flex;
            gap: 10px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .empty-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 30px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination .current {
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination a {
            background: white;
            color: #007bff;
            border: 2px solid #007bff;
        }

        .pagination a:hover {
            background: #007bff;
            color: white;
        }

        .pagination .current {
            background: #007bff;
            color: white;
            border: 2px solid #007bff;
        }

        /* Status update form */
        .status-update {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }

        .status-update select {
            padding: 5px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
        }

        .status-update button {
            padding: 5px 10px;
            font-size: 11px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row,
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .articles-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-direction: column;
            }
        }

        /* Animations */
        @keyframes slideIn {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Price formatting styles */
        .formatted-price {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-store"></i> Dashboard UMKM - <?php echo htmlspecialchars($umkm_data['business_name']); ?></h1>
            <p>Kelola artikel dan promosi bisnis Anda dengan mudah</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #007bff;">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_articles'] ?? 0; ?></div>
                <div class="stat-label">Total Artikel</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #28a745;">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-number"><?php echo $stats['active_articles'] ?? 0; ?></div>
                <div class="stat-label">Artikel Aktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #dc3545;">
                    <i class="fas fa-eye-slash"></i>
                </div>
                <div class="stat-number"><?php echo $stats['inactive_articles'] ?? 0; ?></div>
                <div class="stat-label">Artikel Nonaktif</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #ffc107;">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-number"><?php echo formatPrice($stats['avg_price'] ?? 0); ?></div>
                <div class="stat-label">Rata-rata Harga</div>
            </div>
        </div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" onclick="showTab('add')">
                <i class="fas fa-plus"></i> Tambah Artikel Baru
            </div>
            <div class="tab" onclick="showTab('list')">
                <i class="fas fa-list"></i> Daftar Artikel (<?php echo $total_articles; ?>)
            </div>
        </div>
        
        <!-- Add Article Tab -->
        <div id="add-tab" class="tab-content active">
            <h2><i class="fas fa-plus-circle"></i> Tambah Artikel Baru</h2>
            <p>Buat artikel baru untuk mempromosikan produk atau layanan UMKM Anda</p>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_article" value="1">
                
                <div class="form-group">
                    <label for="judul"><i class="fas fa-heading"></i> Judul Artikel *</label>
                    <input type="text" name="judul" id="judul" required 
                           placeholder="Masukkan judul artikel yang menarik"
                           value="<?php echo isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="kategori"><i class="fas fa-tags"></i> Kategori *</label>
                        <select name="kategori" id="kategori" required>
                            <option value="">Pilih kategori</option>
                            <option value="jasa" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'jasa') ? 'selected' : ''; ?>>🔧 Jasa</option>
                            <option value="event" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'event') ? 'selected' : ''; ?>>🎉 Event</option>
                            <option value="kuliner" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'kuliner') ? 'selected' : ''; ?>>🍽️ Kuliner</option>
                            <option value="kerajinan" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'kerajinan') ? 'selected' : ''; ?>>🎨 Kerajinan</option>
                            <option value="wisata" <?php echo (isset($_POST['kategori']) && $_POST['kategori'] == 'wisata') ? 'selected' : ''; ?>>🏝️ Wisata</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="harga"><i class="fas fa-money-bill-wave"></i> Harga (Rp) *</label>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">Rp</span>
                            <input type="text" name="harga" id="harga" required
                                   placeholder="0" 
                                   value="<?php echo isset($_POST['harga']) ? htmlspecialchars($_POST['harga']) : ''; ?>"
                                   oninput="formatPriceInput(this)">
                        </div>
                        <small style="color: #666; font-size: 12px;">Contoh: 150000 atau 1500000</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="deskripsi"><i class="fas fa-align-left"></i> Deskripsi *</label>
                    <textarea name="deskripsi" id="deskripsi" required rows="6"
                              placeholder="Deskripsikan produk/layanan secara detail. Jelaskan keunggulan, manfaat, dan informasi penting lainnya..."><?php echo isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="gambar"><i class="fas fa-image"></i> Foto Produk/Layanan</label>
                    <div class="photo-upload-container">
                        <!-- Image Preview Area -->
                        <div class="image-preview" id="imagePreview">
                            <div class="preview-placeholder">
                                <i class="fas fa-camera"></i>
                                <p>Klik untuk upload foto</p>
                                <small>JPG, PNG, GIF - Max 5MB</small>
                            </div>
                            <img id="previewImage" style="display: none;" alt="Preview">
                            <div class="preview-overlay" style="display: none;">
                                <button type="button" class="btn-remove-image" onclick="removeImage()">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button type="button" class="btn-change-image" onclick="document.getElementById('gambar').click()">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Hidden File Input -->
                        <input type="file" name="gambar" id="gambar" accept="image/*" style="display: none;">
                        
                        <!-- Upload Button -->
                        <div class="upload-actions">
                            <button type="button" class="btn-upload" onclick="document.getElementById('gambar').click()">
                                <i class="fas fa-upload"></i>
                                Pilih Foto
                            </button>
                            <div class="upload-info">
                                <i class="fas fa-info-circle"></i>
                                <span>Ukuran maksimal 5MB. Format: JPG, PNG, GIF</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Artikel
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetForm()">
                        <i class="fas fa-undo"></i> Reset Form
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Articles List Tab -->
        <div id="list-tab" class="tab-content">
            <h2><i class="fas fa-list"></i> Daftar Artikel</h2>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET">
                    <div class="filter-row">
                        <div class="form-group">
                            <label for="search">Cari Artikel</label>
                            <input type="text" name="search" id="search" placeholder="Cari berdasarkan judul atau deskripsi..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label for="kategori_filter">Kategori</label>
                            <select name="kategori" id="kategori_filter">
                                <option value="">Semua Kategori</option>
                                <option value="jasa" <?php echo $kategori_filter == 'jasa' ? 'selected' : ''; ?>>🔧 Jasa</option>
                                <option value="event" <?php echo $kategori_filter == 'event' ? 'selected' : ''; ?>>🎉 Event</option>
                                <option value="kuliner" <?php echo $kategori_filter == 'kuliner' ? 'selected' : ''; ?>>🍽️ Kuliner</option>
                                <option value="kerajinan" <?php echo $kategori_filter == 'kerajinan' ? 'selected' : ''; ?>>🎨 Kerajinan</option>
                                <option value="wisata" <?php echo $kategori_filter == 'wisata' ? 'selected' : ''; ?>>🏝️ Wisata</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status_filter">Status</label>
                            <select name="status" id="status_filter">
                                <option value="">Semua Status</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>✅ Aktif</option>
                                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>❌ Nonaktif</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <?php if (empty($articles)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📝</div>
                    <h3>Tidak Ada Artikel Ditemukan</h3>
                    <?php if (!empty($search) || !empty($kategori_filter) || !empty($status_filter)): ?>
                        <p>Tidak ada artikel yang sesuai dengan filter yang dipilih.</p>
                        <a href="?" class="btn btn-primary">
                            <i class="fas fa-refresh"></i> Reset Filter
                        </a>
                    <?php else: ?>
                        <p>Mulai promosikan produk/jasa Anda dengan membuat artikel pertama!</p>
                        <button class="btn btn-primary" onclick="showTab('add')">
                            <i class="fas fa-plus"></i> Buat Artikel Pertama
                        </button>
                    <?php endif; ?>
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
                                    <div class="no-image"><i class="fas fa-image"></i></div>
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
                                
                                <div class="status-badge status-<?php echo $article['status']; ?>">
                                    <?php echo $article['status'] == 'active' ? '✅ Aktif' : '❌ Nonaktif'; ?>
                                </div>
                            </div>
                            
                            <div class="article-content">
                                <h3 class="article-title"><?php echo htmlspecialchars($article['judul']); ?></h3>
                                
                                <p class="article-description">
                                    <?php echo truncateText(htmlspecialchars($article['deskripsi']), 120); ?>
                                </p>
                                
                                <div class="article-meta">
                                    <div class="article-price">
                                        <?php echo formatPrice($article['harga']); ?>
                                    </div>
                                    <div class="article-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo formatDate($article['created_at']); ?>
                                    </div>
                                </div>
                                
                                <!-- Status Update Form -->
                                <form method="POST" class="status-update">
                                    <input type="hidden" name="update_status" value="1">
                                    <input type="hidden" name="artikel_id" value="<?php echo $article['id']; ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="active" <?php echo $article['status'] == 'active' ? 'selected' : ''; ?>>✅ Aktif</option>
                                        <option value="inactive" <?php echo $article['status'] == 'inactive' ? 'selected' : ''; ?>>❌ Nonaktif</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </form>
                                
                                <div class="article-actions">
                                    <a href="edit_artikel.php?id=<?php echo $article['id']; ?>" class="btn btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?delete=1&id=<?php echo $article['id']; ?>" 
                                       class="btn btn-delete"
                                       onclick="return confirm('Yakin ingin menghapus artikel ini? Tindakan ini tidak dapat dibatalkan.')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                    <a href="../users/dashboard/allumkm.php?view=detail&id=<?php echo $article['id']; ?>" 
                                       class="btn btn-primary" target="_blank">
                                        <i class="fas fa-external-link-alt"></i> Lihat
                                    </a>
                                </div>
                                
                                <?php if ($article['updated_at'] != $article['created_at']): ?>
                                    <div style="margin-top: 10px; font-size: 0.8rem; color: #666;">
                                        <i class="fas fa-edit"></i> Diupdate: <?php echo formatDate($article['updated_at']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>">
                                <i class="fas fa-chevron-left"></i> Prev
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $kategori_filter ? '&kategori=' . $kategori_filter : ''; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px; color: #666;">
                        Menampilkan <?php echo count($articles); ?> dari <?php echo $total_articles; ?> artikel
                        (Halaman <?php echo $page; ?> dari <?php echo $total_pages; ?>)
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tabs and contents
            const tabs = document.querySelectorAll('.tab');
            const contents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            contents.forEach(content => content.classList.remove('active'));
            
            // Show selected tab
            document.querySelector(`#${tabName}-tab`).classList.add('active');
            event.target.classList.add('active');
            
            // Update URL without page reload
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
        }
        
        function resetForm() {
            if (confirm('Yakin ingin mengosongkan semua field form?')) {
                document.querySelector('form').reset();
                // Reset price input
                document.getElementById('harga').value = '';
                // Reset image upload
                removeImage();
                // Remove price preview
                const pricePreview = document.getElementById('price-preview');
                if (pricePreview) {
                    pricePreview.remove();
                }
            }
        }
        
        function updateFileLabel() {
            // This function is kept for backward compatibility but not used with new upload system
        }
        
        // Enhanced image upload functions
        function handleImageUpload(input) {
            const file = input.files[0];
            const container = document.querySelector('.photo-upload-container');
            const preview = document.getElementById('imagePreview');
            const previewImage = document.getElementById('previewImage');
            const placeholder = document.querySelector('.preview-placeholder');
            const overlay = document.querySelector('.preview-overlay');
            const uploadActions = document.querySelector('.upload-actions');
            
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Hanya file JPG, PNG, dan GIF yang diperbolehkan!');
                    input.value = '';
                    return;
                }
                
                // Validate file size (5MB)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    alert('Ukuran file maksimal 5MB!');
                    input.value = '';
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    previewImage.style.display = 'block';
                    placeholder.style.display = 'none';
                    overlay.style.display = 'flex';
                    uploadActions.style.display = 'none';
                    container.classList.add('has-image');
                    
                    // Add click handler to preview for changing image
                    preview.onclick = function() {
                        document.getElementById('gambar').click();
                    };
                };
                reader.readAsDataURL(file);
            }
        }
        
        function removeImage() {
            const input = document.getElementById('gambar');
            const container = document.querySelector('.photo-upload-container');
            const preview = document.getElementById('imagePreview');
            const previewImage = document.getElementById('previewImage');
            const placeholder = document.querySelector('.preview-placeholder');
            const overlay = document.querySelector('.preview-overlay');
            const uploadActions = document.querySelector('.upload-actions');
            
            // Reset input
            input.value = '';
            
            // Reset preview
            previewImage.style.display = 'none';
            previewImage.src = '';
            placeholder.style.display = 'block';
            overlay.style.display = 'none';
            uploadActions.style.display = 'flex';
            container.classList.remove('has-image');
            
            // Remove click handler
            preview.onclick = function() {
                document.getElementById('gambar').click();
            };
        }
        
        // Show file info tooltip
        function showFileInfo(file) {
            const info = document.querySelector('.upload-info span');
            if (file) {
                const size = (file.size / 1024 / 1024).toFixed(2);
                info.textContent = `${file.name} (${size} MB)`;
            } else {
                info.textContent = 'Ukuran maksimal 5MB. Format: JPG, PNG, GIF';
            }
        }
        
        // Fixed price formatting function
        function formatPriceInput(input) {
            // Remove non-numeric characters except for existing numbers
            let value = input.value.replace(/[^0-9]/g, '');
            
            // Prevent empty value
            if (value === '') {
                input.value = '';
                return;
            }
            
            // Convert to number and format with thousand separators
            let numericValue = parseInt(value);
            
            // Format with dots as thousand separators for display
            let formattedValue = numericValue.toLocaleString('id-ID');
            
            // Update the input value
            input.value = formattedValue;
            
            // Show real-time preview
            const previewElement = document.getElementById('price-preview');
            if (!previewElement) {
                const preview = document.createElement('div');
                preview.id = 'price-preview';
                preview.className = 'formatted-price';
                preview.style.fontSize = '14px';
                preview.style.marginTop = '5px';
                input.parentNode.appendChild(preview);
            }
            document.getElementById('price-preview').textContent = 'Rp ' + formattedValue;
        }
        
        // Form validation with improved price handling
        function validateForm() {
            const judul = document.getElementById('judul').value.trim();
            const deskripsi = document.getElementById('deskripsi').value.trim();
            const hargaInput = document.getElementById('harga').value;
            const kategori = document.getElementById('kategori').value;
            
            if (!judul || !deskripsi || !hargaInput || !kategori) {
                alert('Mohon lengkapi semua field yang wajib diisi!');
                return false;
            }
            
            // Convert formatted price back to numeric value for validation
            const hargaNumeric = parseInt(hargaInput.replace(/[^0-9]/g, ''));
            
            if (isNaN(hargaNumeric) || hargaNumeric <= 0) {
                alert('Harga harus berupa angka dan lebih dari 0!');
                return false;
            }
            
            if (hargaNumeric > 999999999) {
                alert('Harga terlalu besar! Maksimal Rp 999.999.999');
                return false;
            }
            
            const fileInput = document.getElementById('gambar');
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                const maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Hanya file JPG, PNG, dan GIF yang diperbolehkan!');
                    return false;
                }
                
                if (file.size > maxSize) {
                    alert('Ukuran file maksimal 5MB!');
                    return false;
                }
            }
            
            return true;
        }
        
        // Enhanced search functionality
        function setupSearch() {
            const searchInput = document.getElementById('search');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (this.value.length >= 3 || this.value.length === 0) {
                        // Auto-submit search after 500ms delay
                        this.form.submit();
                    }
                }, 500);
            });
        }
        
        // Auto-hide alerts
        function setupAlerts() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 300);
                }, 5000);
                
                // Add close button
                const closeBtn = document.createElement('button');
                closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                closeBtn.style.cssText = 'background: none; border: none; color: inherit; margin-left: auto; cursor: pointer; padding: 0; font-size: 16px;';
                closeBtn.onclick = function() {
                    alert.style.display = 'none';
                };
                alert.appendChild(closeBtn);
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced file input change handler
            document.getElementById('gambar').addEventListener('change', function(e) {
                handleImageUpload(this);
                showFileInfo(this.files[0]);
            });
            
            // Make image preview clickable for upload
            document.getElementById('imagePreview').addEventListener('click', function() {
                if (!document.querySelector('.photo-upload-container').classList.contains('has-image')) {
                    document.getElementById('gambar').click();
                }
            });
            
            // Form submission handler
            const form = document.querySelector('form[method="POST"]');
            if (form && form.querySelector('input[name="add_article"]')) {
                form.addEventListener('submit', function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
                    
                    // Restore button after 10 seconds (fallback)
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }, 10000);
                });
            }
            
            // Price input formatting
            const hargaInput = document.getElementById('harga');
            if (hargaInput) {
                hargaInput.addEventListener('input', function() {
                    formatPriceInput(this);
                });
                
                // Format existing value on page load
                if (hargaInput.value) {
                    formatPriceInput(hargaInput);
                }
            }
            
            // Setup other features
            setupSearch();
            setupAlerts();
            
            // Check URL for tab parameter
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab === 'list') {
                showTab('list');
            }
            
            // Auto-focus first input in active tab
            const activeTab = document.querySelector('.tab-content.active');
            const firstInput = activeTab.querySelector('input, select, textarea');
            if (firstInput && activeTab.id === 'add-tab') {
                firstInput.focus();
            }
        });
        
        // Confirm delete with detailed info
        function confirmDelete(articleTitle) {
            return confirm(`Yakin ingin menghapus artikel "${articleTitle}"?\n\nTindakan ini akan:\n- Menghapus artikel dari database\n- Menghapus file gambar terkait\n- Tidak dapat dibatalkan\n\nKlik OK untuk melanjutkan.`);
        }
        
        // Preview image before upload
        document.getElementById('gambar')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You can add image preview functionality here if needed
                    console.log('Image selected:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+N for new article
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                showTab('add');
                document.getElementById('judul').focus();
            }
            
            // Ctrl+L for article list
            if (e.ctrlKey && e.key === 'l') {
                e.preventDefault();
                showTab('list');
            }
        });
        
        // Enhanced price input with paste support
        document.getElementById('harga')?.addEventListener('paste', function(e) {
            setTimeout(() => {
                formatPriceInput(this);
            }, 10);
        });
        
        // Add currency symbol hover effect
        document.querySelector('.price-input-wrapper')?.addEventListener('mouseenter', function() {
            this.querySelector('.currency-symbol').style.color = '#007bff';
        });
        
        document.querySelector('.price-input-wrapper')?.addEventListener('mouseleave', function() {
            this.querySelector('.currency-symbol').style.color = '#666';
        });
    </script>
</body>
</html>