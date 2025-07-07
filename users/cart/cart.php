<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../config/database.php';

$db = getDbConnection();
$user_id = $_SESSION['user_id'];
$message = '';

// Handle remove item
if (isset($_POST['remove_item']) && isset($_POST['cart_id'])) {
    $cart_id = (int)$_POST['cart_id'];
    $stmt = $db->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Item berhasil dihapus dari keranjang!</div>';
    }
    $stmt->close();
}

// Handle update quantity
if (isset($_POST['update_quantity']) && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
    $cart_id = (int)$_POST['cart_id'];
    $quantity = max(1, (int)$_POST['quantity']);
    
    // Get item details to recalculate subtotal
    $stmt = $db->prepare("SELECT price_per_unit FROM cart_items WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();
    
    if ($item) {
        $subtotal = $item['price_per_unit'] * $quantity;
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ?, subtotal = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("idii", $quantity, $subtotal, $cart_id, $user_id);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Jumlah item berhasil diperbarui!</div>';
        }
        $stmt->close();
    }
}

// Get cart items with details
$cart_query = "
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
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
";

$stmt = $db->prepare($cart_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate total
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['subtotal'];
}

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

function getItemTypeLabel($type) {
    $labels = [
        'wisata' => 'Wisata',
        'penginapan' => 'Penginapan',
        'artikel' => 'Produk/Jasa'
    ];
    return $labels[$type] ?? 'Item';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Papua Journey</title>
    <link rel="stylesheet" href="cart.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>
    
    <div class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>🛒 Keranjang Belanja</h1>
                <p>Kelola item yang ingin Anda pesan</p>
            </div>
            
            <!-- Confirmation Modal -->
            <div id="confirm-delete-overlay" class="notification-overlay">
                <div class="notification-modal confirm-modal">
                    <div class="warning-icon-container">
                        <div class="warning-icon-circle">
                            <div class="question-mark">?</div>
                        </div>
                    </div>
                    <div class="confirm-message">Apakah Anda yakin?</div>
                    <div class="confirm-submessage">Item "<span id="item-name-to-delete"></span>" akan dihapus dari keranjang</div>
                    <div class="confirm-actions">
                        <button type="button" class="btn-confirm-delete" onclick="confirmDelete()">Hapus</button>
                        <button type="button" class="btn-cancel-delete" onclick="cancelDelete()">Batal</button>
                    </div>
                </div>
            </div>
            
            <!-- Success Notification Modal for Deletion -->
            <div id="delete-notification-overlay" class="notification-overlay">
                <div class="notification-modal delete-modal">
                    <div class="delete-icon-container">
                        <div class="delete-icon-circle">
                            <svg class="trash-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="notification-message">Item berhasil dihapus!</div>
                    <div class="notification-submessage">Item telah dihapus dari keranjang</div>
                </div>
            </div>
            
            <?php echo $message; ?>
            
            <?php if (empty($cart_items)): ?>
                <div class="empty-cart">
                    <div class="empty-icon">🛒</div>
                    <h3>Keranjang Anda Kosong</h3>
                    <p>Belum ada item dalam keranjang belanja Anda.</p>
                    <div class="empty-actions">
                        <a href="../wisata/userwisata.php" class="btn btn-primary">🏝️ Jelajahi Wisata</a>
                        <a href="../penginapan/userpenginapan.php" class="btn btn-secondary">🏨 Cari Penginapan</a>
                    </div>
                </div>
            <?php else: ?>
                <form id="checkout-form" method="POST" action="../checkout/checkout.php">
                    <div class="cart-container">
                        <div class="cart-items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="cart-item" data-cart-id="<?php echo $item['id']; ?>">
                                    <div class="item-checkbox">
                                        <input type="checkbox" 
                                               name="selected_items[]" 
                                               value="<?php echo $item['id']; ?>"
                                               class="item-select"
                                               data-price="<?php echo $item['subtotal']; ?>">
                                    </div>
                                    
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
                                            <span class="item-type"><?php echo getItemTypeIcon($item['item_type']) . ' ' . getItemTypeLabel($item['item_type']); ?></span>
                                            <?php if ($item['item_location']): ?>
                                                <span class="item-location">📍 <?php echo htmlspecialchars($item['item_location']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($item['booking_date']): ?>
                                            <div class="booking-info">
                                                📅 Tanggal Kunjungan: <?php echo formatDate($item['booking_date']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($item['checkin_date'] && $item['checkout_date']): ?>
                                            <div class="booking-info">
                                                📅 Check-in: <?php echo formatDate($item['checkin_date']); ?> - 
                                                Check-out: <?php echo formatDate($item['checkout_date']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($item['notes']): ?>
                                            <div class="item-notes">
                                                📝 <?php echo htmlspecialchars($item['notes']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="item-quantity">
                                        <label>Jumlah:</label>
                                        <div class="quantity-control">
                                            <button type="button" class="qty-btn minus" data-cart-id="<?php echo $item['id']; ?>">-</button>
                                            <input type="number" 
                                                   class="qty-input" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" 
                                                   data-cart-id="<?php echo $item['id']; ?>"
                                                   data-price="<?php echo $item['price_per_unit']; ?>">
                                            <button type="button" class="qty-btn plus" data-cart-id="<?php echo $item['id']; ?>">+</button>
                                        </div>
                                    </div>
                                    
                                    <div class="item-price">
                                        <div class="price-label">Subtotal:</div>
                                        <div class="price-amount" data-cart-id="<?php echo $item['id']; ?>">
                                            <?php echo formatPrice($item['subtotal']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="item-actions">
                                        <button type="button" class="btn-remove" 
                                                data-cart-id="<?php echo $item['id']; ?>"
                                                data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>">
                                            🗑️
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="cart-summary">
                            <div class="summary-card">
                                <h3>Ringkasan Belanja</h3>
                                
                                <div class="select-all">
                                    <label>
                                        <input type="checkbox" id="select-all">
                                        Pilih Semua Item
                                    </label>
                                </div>
                                
                                <div class="summary-details">
                                    <div class="summary-row">
                                        <span>Total Item:</span>
                                        <span id="total-items"><?php echo count($cart_items); ?></span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Item Dipilih:</span>
                                        <span id="selected-items">0</span>
                                    </div>
                                    <div class="summary-row total">
                                        <span>Total Pembayaran:</span>
                                        <span id="total-payment"><?php echo formatPrice(0); ?></span>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn-checkout" id="checkout-btn" disabled>
                                    💳 Lanjut ke Pembayaran
                                </button>
                                
                                <div class="summary-actions">
                                    <a href="../wisata/userwisata.php" class="btn-continue">
                                        🛍️ Lanjut Belanja
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Update quantity via AJAX
        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const cartId = this.dataset.cartId;
                const input = document.querySelector(`.qty-input[data-cart-id="${cartId}"]`);
                let quantity = parseInt(input.value);
                
                if (this.classList.contains('plus')) {
                    quantity++;
                } else if (this.classList.contains('minus') && quantity > 1) {
                    quantity--;
                }
                
                input.value = quantity;
                updateQuantity(cartId, quantity);
            });
        });
        
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', function() {
                const cartId = this.dataset.cartId;
                const quantity = Math.max(1, parseInt(this.value) || 1);
                this.value = quantity;
                updateQuantity(cartId, quantity);
            });
        });
        
        function updateQuantity(cartId, quantity) {
            const formData = new FormData();
            formData.append('update_quantity', '1');
            formData.append('cart_id', cartId);
            formData.append('quantity', quantity);
            
            fetch('update_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update subtotal display
                    const priceElement = document.querySelector(`.price-amount[data-cart-id="${cartId}"]`);
                    priceElement.textContent = formatPrice(data.subtotal);
                    
                    // Update checkbox data-price
                    const checkbox = document.querySelector(`.cart-item[data-cart-id="${cartId}"] .item-select`);
                    checkbox.dataset.price = data.subtotal;
                    
                    // Recalculate total if item is selected
                    calculateTotal();
                }
            });
        }
        
        // Handle checkbox selection
        document.querySelectorAll('.item-select').forEach(checkbox => {
            checkbox.addEventListener('change', calculateTotal);
        });
        
        // Select all functionality
        document.getElementById('select-all')?.addEventListener('change', function() {
            document.querySelectorAll('.item-select').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            calculateTotal();
        });
        
        function calculateTotal() {
            let total = 0;
            let selectedCount = 0;
            
            document.querySelectorAll('.item-select:checked').forEach(checkbox => {
                total += parseFloat(checkbox.dataset.price);
                selectedCount++;
            });
            
            document.getElementById('selected-items').textContent = selectedCount;
            document.getElementById('total-payment').textContent = formatPrice(total);
            
            // Enable/disable checkout button
            const checkoutBtn = document.getElementById('checkout-btn');
            checkoutBtn.disabled = selectedCount === 0;
        }
        
        function formatPrice(price) {
            return 'Rp ' + price.toLocaleString('id-ID');
        }
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 3000);
        
        // Variable to track which item is being deleted
        let itemToDelete = null;
        
        // Handle item removal
        document.querySelectorAll('.btn-remove').forEach(btn => {
            btn.addEventListener('click', function() {
                const cartId = this.dataset.cartId;
                const itemName = this.dataset.itemName;
                
                // Store the item to delete
                itemToDelete = cartId;
                
                // Update modal with item name
                document.getElementById('item-name-to-delete').textContent = itemName;
                
                // Show confirmation modal
                document.getElementById('confirm-delete-overlay').classList.add('show');
            });
        });
        
        function removeItem(cartId) {
            const formData = new FormData();
            formData.append('cart_id', cartId);
            
            fetch('remove_item.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success notification
                    showDeleteNotification();
                    
                    // Remove item row with animation
                    const itemRow = document.querySelector(`.cart-item[data-cart-id="${cartId}"]`);
                    if (itemRow) {
                        itemRow.style.transition = 'all 0.3s ease';
                        itemRow.style.opacity = '0';
                        itemRow.style.transform = 'translateX(-100%)';
                        
                        setTimeout(() => {
                            itemRow.remove();
                            
                            // Update totals
                            updateCartUI(data);
                        }, 300);
                    }
                    
                    // Update cart badge
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.cart_count;
                    }
                } else {
                    alert('Gagal menghapus item: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus item');
            });
        }
        
        function showDeleteNotification() {
            const overlay = document.getElementById('delete-notification-overlay');
            overlay.classList.add('show');
            
            setTimeout(() => {
                overlay.classList.remove('show');
            }, 2000);
        }
        
        function updateCartUI(data) {
            // Update total items
            document.getElementById('total-items').textContent = data.cart_count;
            
            // If cart is empty, show empty cart message
            if (data.is_empty) {
                location.reload(); // Reload to show empty cart state
            }
            
            // Recalculate selected items total
            calculateTotal();
        }
        
        // Confirm delete function
        function confirmDelete() {
            if (itemToDelete) {
                // Hide confirmation modal
                document.getElementById('confirm-delete-overlay').classList.remove('show');
                
                // Proceed with deletion
                removeItem(itemToDelete);
                
                // Reset itemToDelete
                itemToDelete = null;
            }
        }
        
        // Cancel delete function
        function cancelDelete() {
            // Hide confirmation modal
            document.getElementById('confirm-delete-overlay').classList.remove('show');
            
            // Reset itemToDelete
            itemToDelete = null;
        }
    </script>
    
    <!-- Abandoned Cart Tracking -->
    <link rel="stylesheet" href="../../assets/css/abandoned-cart-modal.css">
    <script src="../../assets/js/abandoned-cart-tracker.js"></script>
</body>
</html>