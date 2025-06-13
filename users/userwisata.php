<?php
// users/userwisata.php
// Start session first before any output
if (!isset($_SESSION)) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

require_once '../config/database.php';

$db = getDbConnection();

// Get filter parameters
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$sql = "SELECT * FROM wisata WHERE 1=1";
$params = [];

if (!empty($kategori_filter)) {
    $sql .= " AND kategori = ?";
    $params[] = $kategori_filter;
}

if (!empty($search)) {
    $sql .= " AND (judul LIKE ? OR deskripsi LIKE ? OR alamat LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY created_at DESC";

// Prepare and execute query
$stmt = $db->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Simpan data yang diperlukan sebelum output HTML
$wisata_data = [];
while ($row = $result->fetch_assoc()) {
    $wisata_data[] = $row;
}
$stmt->close();
mysqli_close($db);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wisata Papua - Jelajahi Keindahan Papua</title>
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
        }
        
        .main-content {
            padding-top: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            font-size: 3rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .page-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .filters {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filters input,
        .filters select {
            padding: 10px 15px;
            border: none;
            border-radius: 25px;
            background: rgba(255,255,255,0.9);
            font-size: 14px;
            min-width: 150px;
        }
        
        .filters button {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .filters button:hover {
            background: #218838;
        }
        
        .wisata-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .wisata-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        
        .wisata-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        
        .wisata-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .card-content {
            padding: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .card-category {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .card-price {
            font-size: 1.4rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .no-data {
            text-align: center;
            color: white;
            font-size: 1.2rem;
            margin-top: 50px;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        
        .modal-content {
            background: white;
            margin: 3% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            position: relative;
            height: 300px;
            overflow: hidden;
            border-radius: 20px 20px 0 0;
        }
        
        .modal-header img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            background: rgba(0,0,0,0.5);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }
        
        .close:hover {
            background: rgba(0,0,0,0.7);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .modal-title {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }
        
        .modal-category {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .modal-price {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .modal-description {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #555;
            margin-bottom: 25px;
        }
        
        .modal-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
        }
        
        .info-item h4 {
            color: #667eea;
            margin-bottom: 8px;
            font-size: 1rem;
        }
        
        .info-item p {
            color: #666;
            line-height: 1.5;
        }
        
        /* Profile Modal Styles */
        .profile-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        
        .profile-modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }
        
        .profile-modal-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-modal-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filters input,
            .filters select,
            .filters button {
                width: 100%;
            }
            
            .wisata-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>🏝️ Wisata Papua</h1>
                <p>Jelajahi keindahan alam dan budaya Papua yang memukau</p>
            </div>
            
            <div class="filters">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                    <input type="text" name="search" placeholder="Cari wisata..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="kategori">
                        <option value="">Semua Kategori</option>
                        <option value="budaya" <?php echo $kategori_filter == 'budaya' ? 'selected' : ''; ?>>Budaya</option>
                        <option value="alam" <?php echo $kategori_filter == 'alam' ? 'selected' : ''; ?>>Alam</option>
                    </select>
                    <button type="submit">Filter</button>
                    <?php if (!empty($kategori_filter) || !empty($search)): ?>
                        <a href="userwisata.php" style="text-decoration: none;">
                            <button type="button" style="background: #dc3545;">Reset</button>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="wisata-grid">
                <?php if (!empty($wisata_data)): ?>
                    <?php foreach ($wisata_data as $wisata): ?>
                        <div class="wisata-card" onclick="openWisataModal(<?php echo htmlspecialchars(json_encode($wisata)); ?>)">
                            <img src="../uploads/<?php echo $wisata['photo']; ?>" alt="<?php echo htmlspecialchars($wisata['judul']); ?>">
                            <div class="card-content">
                                <div class="card-category"><?php echo ucfirst($wisata['kategori']); ?></div>
                                <div class="card-title"><?php echo htmlspecialchars($wisata['judul']); ?></div>
                                <div class="card-price">Rp <?php echo number_format($wisata['harga'], 0, ',', '.'); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">
                        <p>Tidak ada data wisata yang ditemukan.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Wisata Modal -->
    <div id="wisataModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <img id="modalImage" src="" alt="">
                <span class="close" onclick="closeModal('wisataModal')">&times;</span>
            </div>
            <div class="modal-body">
                <h2 id="modalTitle" class="modal-title"></h2>
                <span id="modalCategory" class="modal-category"></span>
                <div id="modalPrice" class="modal-price"></div>
                <p id="modalDescription" class="modal-description"></p>
                <div class="modal-info">
                    <div class="info-item">
                        <h4>📍 Alamat</h4>
                        <p id="modalAlamat"></p>
                    </div>
                    <div class="info-item">
                        <h4>🕒 Jam Buka</h4>
                        <p id="modalJamBuka"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Profile Modals -->
    <div id="profileModal" class="profile-modal">
        <div class="profile-modal-content">
            <div class="profile-modal-header">
                <h2>✏️ Edit Profil</h2>
            </div>
            <form id="profileForm">
                <div class="form-group">
                    <label for="full_name">Nama Lengkap</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Nomor Telepon</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-secondary" onclick="closeModal('profileModal')">Batal</button>
                    <button type="submit" class="btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="passwordModal" class="profile-modal">
        <div class="profile-modal-content">
            <div class="profile-modal-header">
                <h2>🔒 Ubah Password</h2>
            </div>
            <form id="passwordForm">
                <div class="form-group">
                    <label for="current_password">Password Saat Ini</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Password Baru</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-secondary" onclick="closeModal('passwordModal')">Batal</button>
                    <button type="submit" class="btn-primary">Ubah Password</button>
                </div>
            </form>
        </div>
    </div>
    
    <div id="photoModal" class="profile-modal">
        <div class="profile-modal-content">
            <div class="profile-modal-header">
                <h2>📷 Ubah Foto Profil</h2>
            </div>
            <form id="photoForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="profile_image">Pilih Foto</label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" required>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn-secondary" onclick="closeModal('photoModal')">Batal</button>
                    <button type="submit" class="btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openWisataModal(wisata) {
            document.getElementById('modalImage').src = '../uploads/' + wisata.photo;
            document.getElementById('modalTitle').textContent = wisata.judul;
            document.getElementById('modalCategory').textContent = wisata.kategori.toUpperCase();
            document.getElementById('modalPrice').textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(wisata.harga);
            document.getElementById('modalDescription').textContent = wisata.deskripsi;
            document.getElementById('modalAlamat').textContent = wisata.alamat;
            document.getElementById('modalJamBuka').textContent = wisata.jam_buka;
            
            document.getElementById('wisataModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = ['wisataModal', 'profileModal', 'passwordModal', 'photoModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = ['wisataModal', 'profileModal', 'passwordModal', 'photoModal'];
                modals.forEach(modalId => {
                    document.getElementById(modalId).style.display = 'none';
                });
            }
        });
        
        // Profile form submission
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Add your profile update logic here
            alert('Profil berhasil diperbarui!');
            closeModal('profileModal');
        });
        
        // Password form submission
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Password baru dan konfirmasi password tidak cocok!');
                return;
            }
            
            // Add your password update logic here
            alert('Password berhasil diubah!');
            closeModal('passwordModal');
        });
        
        // Photo form submission
        document.getElementById('photoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Add your photo upload logic here
            alert('Foto profil berhasil diperbarui!');
            closeModal('photoModal');
        });
    </script>
</body>
</html>