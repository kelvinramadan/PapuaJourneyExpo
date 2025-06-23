<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if there are selected items
if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) {
    header('Location: ../cart/cart.php');
    exit();
}

require_once '../../config/database.php';

$db = getDbConnection();
$user_id = $_SESSION['user_id'];
$selected_items = $_POST['selected_items'];

// Validate selected items belong to user
$placeholders = implode(',', array_fill(0, count($selected_items), '?'));
$types = str_repeat('i', count($selected_items) + 1);
$params = array_merge([$user_id], $selected_items);

$query = "
    SELECT 
        c.*,
        CASE 
            WHEN c.item_type = 'wisata' THEN w.judul
            WHEN c.item_type = 'penginapan' THEN p.judul
            WHEN c.item_type = 'artikel' THEN a.judul
        END as item_name,
        CASE 
            WHEN c.item_type = 'wisata' THEN w.photo
            WHEN c.item_type = 'penginapan' THEN p.photo
            WHEN c.item_type = 'artikel' THEN a.gambar
        END as item_image,
        CASE 
            WHEN c.item_type = 'wisata' THEN w.alamat
            WHEN c.item_type = 'penginapan' THEN p.lokasi
            WHEN c.item_type = 'artikel' THEN u.address
        END as item_location
    FROM cart_items c
    LEFT JOIN wisata w ON c.item_type = 'wisata' AND c.item_id = w.id
    LEFT JOIN penginapan p ON c.item_type = 'penginapan' AND c.item_id = p.id
    LEFT JOIN artikel a ON c.item_type = 'artikel' AND c.item_id = a.id
    LEFT JOIN umkm u ON a.umkm_id = u.id
    WHERE c.user_id = ? AND c.id IN ($placeholders)
";

$stmt = $db->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$checkout_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate total
$total_amount = 0;
foreach ($checkout_items as $item) {
    $total_amount += $item['subtotal'];
}

// Get user details
$stmt = $db->prepare("SELECT full_name, email, phone, address FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

$db->close();

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDate($date) {
    return date('d M Y', strtotime($date));
}

function getItemTypeIcon($type) {
    $icons = [
        'wisata' => '🏝️',
        'penginapan' => '🏨',
        'artikel' => '📦'
    ];
    return $icons[$type] ?? '📋';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Papua Journey</title>
    <link rel="stylesheet" href="checkout.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>💳 Checkout</h1>
                <p>Selesaikan pembayaran untuk item yang dipilih</p>
            </div>
            
            <div class="checkout-container">
                <div class="checkout-main">
                    <!-- Order Summary -->
                    <div class="order-summary">
                        <h2>Ringkasan Pesanan</h2>
                        <?php foreach ($checkout_items as $item): ?>
                            <div class="order-item">
                                <div class="item-image">
                                    <?php if ($item['item_image']): ?>
                                        <?php 
                                        $image_path = ($item['item_type'] == 'artikel') 
                                            ? '../../uploads/artikel_images/' . $item['item_image']
                                            : '../../uploads/' . $item['item_image'];
                                        ?>
                                        <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                                    <?php else: ?>
                                        <div class="placeholder-image">
                                            <?php echo getItemTypeIcon($item['item_type']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                    <div class="item-meta">
                                        <?php if ($item['booking_date']): ?>
                                            <span>📅 <?php echo formatDate($item['booking_date']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($item['checkin_date'] && $item['checkout_date']): ?>
                                            <span>📅 <?php echo formatDate($item['checkin_date']); ?> - <?php echo formatDate($item['checkout_date']); ?></span>
                                        <?php endif; ?>
                                        <span>Qty: <?php echo $item['quantity']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="item-price">
                                    <?php echo formatPrice($item['subtotal']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Customer Information -->
                    <div class="customer-info">
                        <h2>Informasi Pelanggan</h2>
                        <form id="checkout-form" method="POST" action="process_checkout.php">
                            <?php foreach ($selected_items as $item_id): ?>
                                <input type="hidden" name="selected_items[]" value="<?php echo $item_id; ?>">
                            <?php endforeach; ?>
                            
                            <div class="form-group">
                                <label for="full_name">Nama Lengkap</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Nomor Telepon</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Alamat</label>
                                <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">Catatan Tambahan (Opsional)</label>
                                <textarea id="notes" name="notes" rows="2" placeholder="Tambahkan catatan khusus untuk pesanan Anda"></textarea>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="payment-method">
                        <h2>Metode Pembayaran</h2>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="bank_transfer" checked>
                                <div class="option-content">
                                    <i class="fas fa-university"></i>
                                    <div>
                                        <strong>Transfer Bank</strong>
                                        <small>BCA, Mandiri, BNI, BRI</small>
                                    </div>
                                </div>
                            </label>
                            
                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="e_wallet">
                                <div class="option-content">
                                    <i class="fas fa-mobile-alt"></i>
                                    <div>
                                        <strong>E-Wallet</strong>
                                        <small>GoPay, OVO, DANA, ShopeePay</small>
                                    </div>
                                </div>
                            </label>
                            
                        </div>
                    </div>
                </div>
                
                <!-- Checkout Sidebar -->
                <div class="checkout-sidebar">
                    <div class="summary-card">
                        <h3>Total Pembayaran</h3>
                        
                        <div class="summary-details">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span><?php echo formatPrice($total_amount); ?></span>
                            </div>
                            <div class="summary-row">
                                <span>Biaya Layanan</span>
                                <span><?php echo formatPrice(0); ?></span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span><?php echo formatPrice($total_amount); ?></span>
                            </div>
                        </div>
                        
                        <button type="submit" form="checkout-form" class="btn-pay">
                            💳 Bayar Sekarang
                        </button>
                        
                        <div class="security-note">
                            <i class="fas fa-lock"></i>
                            <small>Pembayaran Anda aman dan terenkripsi</small>
                        </div>
                        
                        <a href="../cart/cart.php" class="btn-back">
                            ← Kembali ke Keranjang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Handle payment method selection
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const selectedMethod = this.value;
                const form = document.getElementById('checkout-form');
                
                // Add payment method to form
                let methodInput = form.querySelector('input[name="payment_method"]');
                if (!methodInput) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = 'payment_method';
                    form.appendChild(methodInput);
                }
                methodInput.value = selectedMethod;
            });
        });
        
        // Set initial payment method
        const initialMethod = document.querySelector('input[name="payment_method"]:checked').value;
        const form = document.getElementById('checkout-form');
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = 'payment_method';
        methodInput.value = initialMethod;
        form.appendChild(methodInput);
        
        // Form validation
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const phone = document.getElementById('phone').value;
            if (!/^[0-9+\-\s]+$/.test(phone)) {
                alert('Nomor telepon tidak valid');
                return;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('.btn-pay');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '⏳ Memproses...';
            
            // Submit form
            this.submit();
        });
    </script>
</body>
</html>