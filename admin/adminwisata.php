<?php
// admin/adminwisata.php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_username'])) {
    header('Location: index.php');
    exit();
}

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed_types)) {
            $photo_name = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $photo_name;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                // File uploaded successfully
            } else {
                $error = "Gagal mengupload foto.";
            }
        } else {
            $error = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.";
        }
    } else {
        $error = "Silakan pilih foto untuk diupload.";
    }
    
    // Insert data if no error
    if (!isset($error)) {
        $sql = "INSERT INTO wisata (judul, deskripsi, harga, kategori, alamat, jam_buka, photo) 
                VALUES ('$judul', '$deskripsi', $harga, '$kategori', '$alamat', '$jam_buka', '$photo_name')";
        
        if (mysqli_query($db, $sql)) {
            $success = "Data wisata berhasil ditambahkan!";
        } else {
            $error = "Error: " . mysqli_error($db);
        }
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
            header("Location: adminwisata.php?success=deleted");
            exit();
        } else {
            $error = "Gagal menghapus data wisata.";
        }
        mysqli_stmt_close($delete_stmt);
    }
    mysqli_stmt_close($stmt);
    mysqli_close($db);
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Ambil data wisata untuk ditampilkan
$db = getDbConnection();
$wisata_list = mysqli_query($db, "SELECT * FROM wisata ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Wisata - Tambah Wisata Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Alert */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .table tr:hover {
            background: #f8f9fa;
        }

        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 2px;
            transition: background-color 0.3s;
        }

        /* Card */
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border: none;
        }

        .card h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        /* Bootstrap overrides */
        .card-header {
            background-color: transparent;
            color: #333;
            border-bottom: 2px solid #667eea;
            font-weight: 600;
        }

        .form-control {
            border: 2px solid #ddd;
            border-radius: 5px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .btn-danger {
            background: #dc3545;
            border: none;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 500;
        }

        .bg-info {
            background-color: #17a2b8 !important;
        }

        .img-thumbnail {
            border-radius: 5px;
            object-fit: cover;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .table {
                font-size: 14px;
            }

            .table th,
            .table td {
                padding: 8px;
            }

            .btn {
                width: 100%;
                margin: 2px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="mobile-toggle" onclick="toggleSidebar()">☰</button>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1>Admin Dashboard</h1>
        </div>

        <nav class="nav-menu">
            <a href="index.php" class="btn">🏠 Admin Homepage</a>
            <a href="adminwisata.php" class="btn active">🏞️ Admin Wisata</a>
            <a href="adminpenginapan.php" class="btn">🏨 Admin Penginapan</a>
        </nav>

        <div class="user-section">
            <span class="user-greeting">Halo, admin</span>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> Data berhasil diproses!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Tambah Wisata Baru</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="judul" class="form-label">Judul Wisata</label>
                                <input type="text" class="form-control" id="judul" name="judul" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="deskripsi" class="form-label">Deskripsi</label>
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required placeholder="Deskripsikan tempat wisata secara detail..."></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="harga" class="form-label">Harga Tiket (Rp)</label>
                                <input type="number" class="form-control" id="harga" name="harga" min="0" step="0.01" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="kategori" class="form-label">Kategori</label>
                                <select class="form-select" id="kategori" name="kategori" required>
                                    <option value="">Pilih kategori</option>
                                    <option value="budaya">Budaya</option>
                                    <option value="alam">Alam</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="alamat" class="form-label">Alamat</label>
                                <textarea class="form-control" id="alamat" name="alamat" rows="3" required placeholder="Alamat lengkap tempat wisata"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="jam_buka" class="form-label">Jam Buka</label>
                                <input type="text" class="form-control" id="jam_buka" name="jam_buka" required placeholder="Contoh: 08:00 - 17:00">
                            </div>
                            
                            <div class="mb-3">
                                <label for="photo" class="form-label">Foto Wisata</label>
                                <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save"></i> Tambah Wisata
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Daftar Wisata</h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($wisata_list) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Foto</th>
                                            <th>Judul</th>
                                            <th>Kategori</th>
                                            <th>Harga</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($wisata = mysqli_fetch_assoc($wisata_list)): ?>
                                            <tr>
                                                <td>
                                                    <img src="../uploads/<?php echo htmlspecialchars($wisata['photo']); ?>" 
                                                         alt="<?php echo htmlspecialchars($wisata['judul']); ?>" 
                                                         width="50" height="50" class="img-thumbnail">
                                                </td>
                                                <td><?php echo htmlspecialchars($wisata['judul']); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo ucfirst($wisata['kategori']); ?></span>
                                                </td>
                                                <td>Rp <?php echo number_format($wisata['harga'], 0, ',', '.'); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-danger" onclick="hapusWisata(<?php echo $wisata['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Belum ada data wisata.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar for mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        // Add active state to navigation buttons
        const currentPage = window.location.pathname.split('/').pop();
        document.querySelectorAll('.nav-menu .btn').forEach(btn => {
            if (btn.getAttribute('href') === currentPage) {
                btn.classList.add('active');
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-toggle');

            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
            }
        });

        // Delete wisata function
        function hapusWisata(id) {
            if (confirm('Apakah Anda yakin ingin menghapus wisata ini?')) {
                window.location.href = '?delete=' + id;
            }
        }
    </script>
</body>
</html>

<?php 
mysqli_close($db); 
?>