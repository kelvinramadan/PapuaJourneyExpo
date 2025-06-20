<?php
// admin/adminpenginapan.php
session_start();
require_once '../config/database.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_username'])) {
    header('Location: index.php');
    exit();
}

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
            header("Location: adminpenginapan.php?success=deleted");
            exit();
        } else {
            $error = "Gagal menghapus data penginapan.";
        }
        mysqli_stmt_close($delete_stmt);
    }
    mysqli_stmt_close($stmt);
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($db, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($db, $_POST['deskripsi']);
    $harga = mysqli_real_escape_string($db, $_POST['harga']);
    $lokasi = mysqli_real_escape_string($db, $_POST['lokasi']);
    $tipe = mysqli_real_escape_string($db, $_POST['tipe']);
    $fasilitas = mysqli_real_escape_string($db, $_POST['fasilitas']);
    
    // Handle file upload
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed_types)) {
            $photo = time() . '_' . $_FILES['photo']['name'];
            $upload_path = $upload_dir . $photo;
            
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
    
    // Insert data if no errors
    if (!isset($error) && !empty($photo)) {
        $query = "INSERT INTO penginapan (judul, deskripsi, harga, lokasi, tipe, fasilitas, photo) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($db, $query);
        mysqli_stmt_bind_param($stmt, "ssdssss", $judul, $deskripsi, $harga, $lokasi, $tipe, $fasilitas, $photo);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Data penginapan berhasil ditambahkan!";
        } else {
            $error = "Gagal menambahkan data penginapan.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch existing data
$query = "SELECT * FROM penginapan ORDER BY created_at DESC";
$result = mysqli_query($db, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Penginapan Papua</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="sidebar.css">
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
            <a href="adminwisata.php" class="btn">🏞️ Admin Wisata</a>
            <a href="adminpenginapan.php" class="btn active">🏨 Admin Penginapan</a>
            <a href="adminpemesanan.php" class="btn">🏨 Admin Pemesanan</a>
            <a href="pesanpenginapan.php" class="btn">🏨 Admin Pemesanan</a>
        </nav>
        
        <div class="user-section">
            <span class="user-greeting">Halo, admin</span>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <!-- Alert Messages -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> Data berhasil diproses!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Form Input -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Tambah Penginapan Baru</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="judul" class="form-label">Judul Penginapan</label>
                                    <input type="text" class="form-control" id="judul" name="judul" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="photo" class="form-label">Foto Penginapan</label>
                                    <input type="file" class="form-control" id="photo" name="photo" accept="image/*" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="deskripsi" class="form-label">Deskripsi</label>
                                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4" required></textarea>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="harga" class="form-label">Harga (Rp)</label>
                                            <input type="number" class="form-control" id="harga" name="harga" min="0" step="1000" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="tipe" class="form-label">Tipe</label>
                                            <select class="form-select" id="tipe" name="tipe" required>
                                                <option value="">Pilih Tipe</option>
                                                <option value="hotel">Hotel</option>
                                                <option value="villa">Villa</option>
                                                <option value="resort">Resort</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="lokasi" class="form-label">Lokasi</label>
                                    <input type="text" class="form-control" id="lokasi" name="lokasi" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="fasilitas" class="form-label">Fasilitas</label>
                                    <textarea class="form-control" id="fasilitas" name="fasilitas" rows="3" placeholder="Pisahkan dengan koma (,)" required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save"></i> Simpan Penginapan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Data List -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list"></i> Daftar Penginapan</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Foto</th>
                                                <th>Judul</th>
                                                <th>Tipe</th>
                                                <th>Harga</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                                <tr>
                                                    <td>
                                                        <img src="../uploads/<?php echo htmlspecialchars($row['photo']); ?>" 
                                                             alt="<?php echo htmlspecialchars($row['judul']); ?>" 
                                                             width="50" height="50" class="img-thumbnail">
                                                    </td>
                                                    <td class="text-truncate"><?php echo htmlspecialchars($row['judul']); ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo strtoupper($row['tipe']); ?></span>
                                                    </td>
                                                    <td>Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-danger" onclick="hapusPenginapan(<?php echo $row['id']; ?>)">
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
                                    <i class="fas fa-bed fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Belum ada data penginapan.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function hapusPenginapan(id) {
            if (confirm('Apakah Anda yakin ingin menghapus penginapan ini?')) {
                window.location.href = '?delete=' + id;
            }
        }

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
    </script>
</body>
</html>

<?php
// Close database connection
mysqli_close($db);
?>