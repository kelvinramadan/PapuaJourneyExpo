<?php
session_start();

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../../login.php');
    exit();
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];

require_once '../../config/database.php';

// Get active tab from URL parameter
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending';
$valid_tabs = ['pending', 'awaiting_confirmation', 'paid', 'rejected', 'cancelled'];
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'pending';
}

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDate($date) {
    return date('d M Y, H:i', strtotime($date));
}

function getStatusBadge($status) {
    $badges = [
        'pending' => ['color' => 'warning', 'icon' => 'clock', 'text' => 'Pending'],
        'awaiting_confirmation' => ['color' => 'info', 'icon' => 'hourglass-half', 'text' => 'Awaiting Confirmation'],
        'paid' => ['color' => 'success', 'icon' => 'check-circle', 'text' => 'Paid'],
        'rejected' => ['color' => 'danger', 'icon' => 'times-circle', 'text' => 'Rejected'],
        'cancelled' => ['color' => 'secondary', 'icon' => 'ban', 'text' => 'Cancelled']
    ];
    
    $badge = $badges[$status] ?? $badges['pending'];
    return sprintf(
        '<span class="status-badge badge-%s"><i class="fas fa-%s"></i> %s</span>',
        $badge['color'],
        $badge['icon'],
        $badge['text']
    );
}

// Get database connection
$db = getDbConnection();

// Get count of orders by status
$count_stmt = $db->prepare("
    SELECT 
        payment_status,
        COUNT(*) as count
    FROM transaksi
    WHERE user_id = ?
    GROUP BY payment_status
");
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$count_result = $count_stmt->get_result();

$status_counts = [
    'pending' => 0,
    'awaiting_confirmation' => 0,
    'paid' => 0,
    'rejected' => 0,
    'cancelled' => 0
];

while ($row = $count_result->fetch_assoc()) {
    $status_counts[$row['payment_status']] = $row['count'];
}
$count_stmt->close();

// Get cart count for navbar
$cart_count = 0;
$cart_stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$cart_count = $cart_result->fetch_assoc()['count'];
$cart_stmt->close();

// Get orders based on active tab
$orders_stmt = $db->prepare("
    SELECT 
        t.id,
        t.transaction_code,
        t.total_amount,
        t.payment_status,
        t.payment_method,
        t.created_at,
        t.payment_proof,
        t.user_payment_date,
        t.payment_confirmed_at
    FROM transaksi t
    WHERE t.user_id = ? AND t.payment_status = ?
    ORDER BY t.created_at DESC
");
$orders_stmt->bind_param("is", $user_id, $active_tab);
$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

$orders = [];
while ($order = $orders_result->fetch_assoc()) {
    // Get items for this order
    $items_stmt = $db->prepare("
        SELECT 
            ti.*,
            COALESCE(
                ti.item_name,
                CASE 
                    WHEN ti.item_type = 'wisata' THEN w.judul
                    WHEN ti.item_type = 'penginapan' THEN p.judul
                    WHEN ti.item_type = 'artikel' THEN a.judul
                END
            ) as item_title,
            CASE 
                WHEN ti.item_type = 'wisata' THEN w.photo
                WHEN ti.item_type = 'penginapan' THEN p.photo
                WHEN ti.item_type = 'artikel' THEN a.gambar
            END as item_image,
            CASE 
                WHEN ti.item_type = 'wisata' THEN 'Tourism'
                WHEN ti.item_type = 'penginapan' THEN p.tipe
                WHEN ti.item_type = 'artikel' THEN a.kategori
            END as item_category,
            r.id as review_id,
            r.rating as review_rating
        FROM transaksi_items ti
        LEFT JOIN wisata w ON ti.item_type = 'wisata' AND ti.item_id = w.id
        LEFT JOIN penginapan p ON ti.item_type = 'penginapan' AND ti.item_id = p.id
        LEFT JOIN artikel a ON ti.item_type = 'artikel' AND ti.item_id = a.id
        LEFT JOIN reviews r ON r.transaksi_id = ti.transaksi_id 
            AND r.item_type = ti.item_type 
            AND r.item_id = ti.item_id 
            AND r.user_id = ?
        WHERE ti.transaksi_id = ?
    ");
    $items_stmt->bind_param("ii", $user_id, $order['id']);
    $items_stmt->execute();
    $items_result = $items_stmt->get_result();
    
    $order['items'] = [];
    while ($item = $items_result->fetch_assoc()) {
        $order['items'][] = $item;
    }
    $items_stmt->close();
    
    $orders[] = $order;
}
$orders_stmt->close();

$db->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Papua Journey</title>
    <link rel="stylesheet" href="my_orders.css">
    <link rel="stylesheet" href="../../assets/css/reviews.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <div class="container">
        <div class="account-wrapper">
            <!-- Sidebar Navigation -->
            <div class="sidebar">
                <div class="sidebar-section">
                    <div class="sidebar-header">
                        <i class="fas fa-user-circle"></i>
                        <span>Akun Saya</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <div class="sidebar-submenu show">
                        <a href="my_account.php?section=profile" class="submenu-item">
                            <i class="fas fa-user"></i> Profil
                        </a>
                        <a href="my_account.php?section=password" class="submenu-item">
                            <i class="fas fa-lock"></i> Ubah Kata Sandi
                        </a>
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <a href="my_orders.php" class="sidebar-header active">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Pesanan Saya</span>
                    </a>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="content-area">
                <h2 class="section-title">
                    <i class="fas fa-shopping-bag"></i> Pesanan Saya
                </h2>

                <!-- Order Tabs -->
                <div class="order-tabs">
                    <a href="?tab=pending" class="tab-item <?php echo $active_tab == 'pending' ? 'active' : ''; ?>">
                        <span>Tertunda</span>
                        <?php if ($status_counts['pending'] > 0): ?>
                            <span class="tab-badge"><?php echo $status_counts['pending']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=awaiting_confirmation" class="tab-item <?php echo $active_tab == 'awaiting_confirmation' ? 'active' : ''; ?>">
                        <span>Menunggu Konfirmasi</span>
                        <?php if ($status_counts['awaiting_confirmation'] > 0): ?>
                            <span class="tab-badge"><?php echo $status_counts['awaiting_confirmation']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=paid" class="tab-item <?php echo $active_tab == 'paid' ? 'active' : ''; ?>">
                        <span>Dibayar</span>
                        <?php if ($status_counts['paid'] > 0): ?>
                            <span class="tab-badge"><?php echo $status_counts['paid']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=rejected" class="tab-item <?php echo $active_tab == 'rejected' ? 'active' : ''; ?>">
                        <span>Ditolak</span>
                        <?php if ($status_counts['rejected'] > 0): ?>
                            <span class="tab-badge"><?php echo $status_counts['rejected']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?tab=cancelled" class="tab-item <?php echo $active_tab == 'cancelled' ? 'active' : ''; ?>">
                        <span>Dibatalkan</span>
                        <?php if ($status_counts['cancelled'] > 0): ?>
                            <span class="tab-badge"><?php echo $status_counts['cancelled']; ?></span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Search Box -->
                <div class="search-container">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Cari berdasarkan kode transaksi atau nama item...">
                    </div>
                </div>

                <!-- Orders List -->
                <div class="orders-container" id="ordersContainer">
                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-basket"></i>
                            <h3>Tidak ada pesanan</h3>
                            <p>Anda tidak memiliki pesanan dengan status <?php echo str_replace('_', ' ', $active_tab); ?>.</p>
                            <a href="../wisata/userwisata.php" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i><span>Mulai Belanja</span>
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card" data-search="<?php echo strtolower($order['transaction_code']); ?>">
                                <!-- Order Header -->
                                <div class="order-header">
                                    <div class="order-info">
                                        <div class="transaction-code">
                                            <i class="fas fa-receipt"></i>
                                            <strong><?php echo htmlspecialchars($order['transaction_code']); ?></strong>
                                        </div>
                                        <div class="order-date">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo formatDate($order['created_at']); ?>
                                        </div>
                                    </div>
                                    <div class="order-status">
                                        <?php echo getStatusBadge($order['payment_status']); ?>
                                    </div>
                                </div>

                                <!-- Order Items -->
                                <div class="order-items">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="order-item" data-search="<?php echo strtolower($item['item_title'] ?? ''); ?>">
                                            <div class="item-image">
                                                <?php 
                                                $image_path = '';
                                                $default_image = '../../assets/images/placeholder.svg';
                                                
                                                if ($item['item_type'] == 'wisata' && $item['item_image']) {
                                                    $image_path = '../../uploads/' . $item['item_image'];
                                                } elseif ($item['item_type'] == 'penginapan' && $item['item_image']) {
                                                    $image_path = '../../uploads/' . $item['item_image'];
                                                } elseif ($item['item_type'] == 'artikel' && $item['item_image']) {
                                                    $image_path = '../../uploads/' . $item['item_image'];
                                                }
                                                
                                                if (!file_exists($image_path) || empty($image_path)) {
                                                    $image_path = $default_image;
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($image_path); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['item_title'] ?? 'Product'); ?>"
                                                     onerror="this.src='../../assets/images/placeholder.svg'">
                                            </div>
                                            <div class="item-details">
                                                <h4><?php echo htmlspecialchars($item['item_title'] ?? 'Unknown Item'); ?></h4>
                                                <p class="item-category">
                                                    <i class="fas fa-tag"></i>
                                                    <?php echo ucfirst(htmlspecialchars($item['item_category'] ?? $item['item_type'])); ?>
                                                </p>
                                                <?php if ($item['booking_date'] && $item['booking_date'] != '0000-00-00'): ?>
                                                <p class="item-date">
                                                        <i class="fas fa-calendar-check"></i>
                                                        Pemesanan: <?php echo date('d M Y', strtotime($item['booking_date'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($item['checkin_date'] && $item['checkout_date']): ?>
                                                    <p class="item-date">
                                                        <i class="fas fa-bed"></i>
                                                        Check-in: <?php echo date('d M Y', strtotime($item['checkin_date'])); ?> - 
                                                        Check-out: <?php echo date('d M Y', strtotime($item['checkout_date'])); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="item-pricing">
                                                <p class="quantity">Jml: <?php echo $item['quantity']; ?></p>
                                                <p class="price"><?php echo formatPrice($item['subtotal']); ?></p>
                                                <?php 
                                                // Show review button for paid orders
                                                if ($order['payment_status'] == 'paid') {
                                                    $can_review = false;
                                                    $current_date = date('Y-m-d');
                                                    
                                                    // Check if event/stay date has passed
                                                    if ($item['item_type'] == 'wisata' && $item['booking_date']) {
                                                        $can_review = $current_date >= $item['booking_date'];
                                                    } elseif ($item['item_type'] == 'penginapan' && $item['checkout_date']) {
                                                        $can_review = $current_date >= $item['checkout_date'];
                                                    } elseif ($item['item_type'] == 'artikel') {
                                                        // Artikel can be reviewed immediately after payment
                                                        $can_review = true;
                                                    }
                                                    
                                                    if ($can_review) {
                                                        if ($item['review_id']) {
                                                            // Already reviewed
                                                            echo '<button class="review-btn reviewed" disabled>';
                                                            echo '<i class="fas fa-check"></i> Sudah Direview';
                                                            echo '</button>';
                                                        } else {
                                                            // Can write review
                                                            $item_image = $image_path; // Use the image path from above
                                                            echo '<button class="review-btn" onclick="openReviewModal(';
                                                            echo $order['id'] . ', ';
                                                            echo "'" . $item['item_type'] . "', ";
                                                            echo $item['item_id'] . ', ';
                                                            echo "'" . htmlspecialchars($item['item_title'] ?? 'Unknown Item', ENT_QUOTES) . "', ";
                                                            echo "'" . htmlspecialchars($item_image, ENT_QUOTES) . "'";
                                                            echo ')">';
                                                            echo '<i class="fas fa-star"></i> Tulis Review';
                                                            echo '</button>';
                                                        }
                                                    }
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Order Footer -->
                                <div class="order-footer">
                                    <div class="order-summary">
                                        <span class="total-items">
                                            <i class="fas fa-cube"></i>
                                            <?php echo count($order['items']); ?> Item
                                        </span>
                                        <span class="total-amount">
                                            Total: <strong><?php echo formatPrice($order['total_amount']); ?></strong>
                                        </span>
                                    </div>
                                    <div class="order-actions">
                                        <?php if ($order['payment_status'] == 'pending'): ?>
                                            <form action="../checkout/payment_instructions.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="transaction_code" value="<?php echo htmlspecialchars($order['transaction_code']); ?>">
                                                <input type="hidden" name="total_amount" value="<?php echo $order['total_amount']; ?>">
                                                <input type="hidden" name="payment_method" value="<?php echo htmlspecialchars($order['payment_method'] ?? 'bank_transfer'); ?>">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-credit-card"></i><span>Bayar Sekarang</span>
                                                </button>
                                            </form>
                                        <?php elseif ($order['payment_status'] == 'rejected'): ?>
                                            <form action="../checkout/reupload_payment.php" method="POST" style="display: inline;">
                                                <input type="hidden" name="transaction_code" value="<?php echo htmlspecialchars($order['transaction_code']); ?>">
                                                <button type="submit" class="btn btn-warning">
                                                    <i class="fas fa-redo"></i><span>Unggah Ulang Pembayaran</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <button class="btn btn-outline" onclick="viewOrderDetails('<?php echo htmlspecialchars($order['transaction_code']); ?>')">
                                            <i class="fas fa-eye"></i><span>Lihat Detail</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar submenu
        document.querySelectorAll('.sidebar-header').forEach(header => {
            if (!header.classList.contains('active')) {
                header.addEventListener('click', function(e) {
                    if (this.getAttribute('href') !== '#') return;
                    e.preventDefault();
                    
                    const submenu = this.nextElementSibling;
                    const toggleIcon = this.querySelector('.toggle-icon');
                    
                    if (submenu && submenu.classList.contains('sidebar-submenu')) {
                        submenu.classList.toggle('show');
                        toggleIcon.classList.toggle('rotate');
                    }
                });
            }
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const ordersContainer = document.getElementById('ordersContainer');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const orderCards = ordersContainer.querySelectorAll('.order-card');
            
            orderCards.forEach(card => {
                const cardText = card.getAttribute('data-search');
                const items = card.querySelectorAll('.order-item');
                let hasMatch = false;
                
                // Check transaction code
                if (cardText.includes(searchTerm)) {
                    hasMatch = true;
                }
                
                // Check item names
                items.forEach(item => {
                    const itemText = item.getAttribute('data-search');
                    if (itemText && itemText.includes(searchTerm)) {
                        hasMatch = true;
                    }
                });
                
                card.style.display = hasMatch ? 'block' : 'none';
            });
            
            // Show empty state if no results
            const visibleCards = ordersContainer.querySelectorAll('.order-card[style="display: block;"], .order-card:not([style])');
            const emptyState = ordersContainer.querySelector('.empty-state');
            
            if (visibleCards.length === 0 && !emptyState) {
                // Add search-specific empty state
                const searchEmpty = document.createElement('div');
                searchEmpty.className = 'empty-state search-empty';
                searchEmpty.innerHTML = `
                    <i class="fas fa-search"></i>
                    <h3>No results found</h3>
                    <p>No orders match your search term "${searchTerm}"</p>
                `;
                ordersContainer.appendChild(searchEmpty);
            } else if (visibleCards.length > 0) {
                // Remove search empty state if exists
                const searchEmpty = ordersContainer.querySelector('.search-empty');
                if (searchEmpty) {
                    searchEmpty.remove();
                }
            }
        });

        // View order details (placeholder function)
        function viewOrderDetails(transactionCode) {
            // This is a placeholder for future enhancement
            alert('Order details view will be implemented soon for: ' + transactionCode);
        }

        // Auto-hide alerts after 5 seconds
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>

    <!-- Review Modal -->
    <div id="reviewModal" class="review-modal">
        <div class="review-modal-content">
            <div class="review-modal-header">
                <h3>Tulis Review</h3>
                <button class="close-modal" onclick="closeReviewModal()">&times;</button>
            </div>
            
            <!-- Product Info -->
            <div class="review-product-info">
                <div class="review-product-image">
                    <img id="reviewProductImage" src="" alt="Product">
                </div>
                <div class="review-product-details">
                    <h4 id="reviewProductName"></h4>
                    <p>Berikan penilaian dan review Anda</p>
                </div>
            </div>
            
            <!-- Hidden inputs -->
            <input type="hidden" id="reviewTransaksiId">
            <input type="hidden" id="reviewItemType">
            <input type="hidden" id="reviewItemId">
            
            <!-- Star Rating -->
            <div class="star-rating-input">
                <label>Rating</label>
                <div class="stars-container">
                    <span class="star" data-rating="1">★</span>
                    <span class="star" data-rating="2">★</span>
                    <span class="star" data-rating="3">★</span>
                    <span class="star" data-rating="4">★</span>
                    <span class="star" data-rating="5">★</span>
                </div>
            </div>
            
            <!-- Review Text -->
            <div class="review-text-input">
                <label>Review Anda</label>
                <textarea id="reviewText" placeholder="Bagaimana pengalaman Anda dengan produk/layanan ini?"></textarea>
                <div class="char-count">
                    <span id="charCount">0/1000</span>
                </div>
            </div>
            
            <!-- Media Upload -->
            <div class="media-upload-section">
                <label>Tambahkan Foto/Video (Opsional)</label>
                <div class="upload-area">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text">Klik atau drag & drop untuk upload</div>
                    <div class="upload-info">Maksimal 5 file (Gambar: 5MB, Video: 50MB, maks 10 detik)</div>
                    <input type="file" id="mediaInput" multiple accept="image/*,video/*" style="display: none;">
                </div>
                <div id="mediaPreview" class="media-preview"></div>
            </div>
            
            <!-- Submit Button -->
            <div class="review-actions">
                <button class="btn-cancel" onclick="closeReviewModal()">Batal</button>
                <button class="btn-submit" id="submitReviewBtn">
                    <i class="fas fa-paper-plane"></i> Kirim Review
                </button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/reviews.js"></script>
</body>
</html>
