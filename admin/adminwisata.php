<?php
// admin/adminwisata.php
require_once '../config/database.php';

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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .wisata-list {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .wisata-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .wisata-item:last-child {
            border-bottom: none;
        }
        
        .wisata-item img {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }
        
        .wisata-info h4 {
            margin-bottom: 5px;
            color: #333;
        }
        
        .wisata-info p {
            color: #666;
            font-size: 14px;
        }
        
        .price {
            color: #28a745;
            font-weight: bold;
        }
        
        .category {
            background: #667eea;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <?php include 'navbaradmin.php'; ?>
    <div class="container">
        <div class="header">
            <h1>🏛️ Admin Wisata</h1>
            <p>Kelola data tempat wisata dengan mudah</p>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <h2>Tambah Wisata Baru</h2>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="judul">Judul Wisata</label>
                    <input type="text" id="judul" name="judul" required>
                </div>
                
                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" required placeholder="Deskripsikan tempat wisata secara detail..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="harga">Harga Tiket (Rp)</label>
                    <input type="number" id="harga" name="harga" min="0" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="kategori">Kategori</label>
                    <select id="kategori" name="kategori" required>
                        <option value="">Pilih kategori</option>
                        <option value="budaya">Budaya</option>
                        <option value="alam">Alam</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="alamat">Alamat</label>
                    <textarea id="alamat" name="alamat" required placeholder="Alamat lengkap tempat wisata"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="jam_buka">Jam Buka</label>
                    <input type="text" id="jam_buka" name="jam_buka" required placeholder="Contoh: 08:00 - 17:00">
                </div>
                
                <div class="form-group">
                    <label for="photo">Foto Wisata</label>
                    <input type="file" id="photo" name="photo" accept="image/*" required>
                </div>
                
                <button type="submit" class="btn">Tambah Wisata</button>
            </form>
        </div>
        
        <div class="wisata-list">
            <h2>Daftar Wisata</h2>
            <?php if (mysqli_num_rows($wisata_list) > 0): ?>
                <?php while ($wisata = mysqli_fetch_assoc($wisata_list)): ?>
                    <div class="wisata-item">
                        <img src="../uploads/<?php echo $wisata['photo']; ?>" alt="<?php echo htmlspecialchars($wisata['judul']); ?>">
                        <div class="wisata-info">
                            <h4><?php echo htmlspecialchars($wisata['judul']); ?></h4>
                            <p><?php echo substr(htmlspecialchars($wisata['deskripsi']), 0, 100); ?>...</p>
                            <p class="price">Rp <?php echo number_format($wisata['harga'], 0, ',', '.'); ?></p>
                        </div>
                        <span class="category"><?php echo ucfirst($wisata['kategori']); ?></span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Belum ada data wisata.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php mysqli_close($db); ?>