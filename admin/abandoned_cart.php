<?php
// admin/abandoned_cart.php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Keranjang Ditinggalkan';

// Configuration for abandoned cart definition (in minutes for testing)
$abandoned_minutes = isset($_GET['minutes']) ? max(1, (int)$_GET['minutes']) : 1;

// Filters
$date_filter = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$category_filter = $_GET['category'] ?? '';
$min_value = $_GET['min_value'] ?? '';
$max_value = $_GET['max_value'] ?? '';

$db = getDbConnection();

// Build WHERE clause for filters
$where_conditions = ["DATE_ADD(c.updated_at, INTERVAL {$abandoned_minutes} MINUTE) < NOW()"];
$params = [];
$types = "";

if ($date_filter) {
    $where_conditions[] = "DATE(c.updated_at) >= ?";
    $params[] = $date_filter;
    $types .= "s";
}

if ($date_to) {
    $where_conditions[] = "DATE(c.updated_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

if ($category_filter) {
    $where_conditions[] = "c.item_type = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$having_conditions = [];
if ($min_value) {
    $having_conditions[] = "cart_total >= ?";
    $params[] = $min_value;
    $types .= "d";
}

if ($max_value) {
    $having_conditions[] = "cart_total <= ?";
    $params[] = $max_value;
    $types .= "d";
}

$where_clause = implode(' AND ', $where_conditions);
$having_clause = !empty($having_conditions) ? 'HAVING ' . implode(' AND ', $having_conditions) : '';

// Query for abandoned carts
$abandoned_carts_query = "
    SELECT 
        c.user_id,
        u.full_name as user_name,
        u.email as user_email,
        u.phone as user_phone,
        COUNT(c.id) as items_count,
        SUM(c.subtotal) as cart_total,
        MAX(c.updated_at) as last_activity,
        TIMESTAMPDIFF(MINUTE, MAX(c.updated_at), NOW()) as minutes_abandoned,
        GROUP_CONCAT(
            CONCAT(
                CASE 
                    WHEN c.item_type = 'wisata' THEN w.judul
                    WHEN c.item_type = 'penginapan' THEN p.judul
                    WHEN c.item_type = 'artikel' THEN a.judul
                END,
                ' (', c.quantity, 'x)'
            ) SEPARATOR ', '
        ) as items_list,
        GROUP_CONCAT(DISTINCT c.item_type) as item_types
    FROM cart_items c
    LEFT JOIN users u ON c.user_id = u.id
    LEFT JOIN wisata w ON c.item_type = 'wisata' AND c.item_id = w.id
    LEFT JOIN penginapan p ON c.item_type = 'penginapan' AND c.item_id = p.id
    LEFT JOIN artikel a ON c.item_type = 'artikel' AND c.item_id = a.id
    WHERE {$where_clause}
    GROUP BY c.user_id, u.full_name, u.email, u.phone
    {$having_clause}
    ORDER BY last_activity DESC
";

$stmt = $db->prepare($abandoned_carts_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$abandoned_carts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Analytics queries
// Most abandoned products
$abandoned_products_query = "
    SELECT 
        c.item_type,
        c.item_id,
        CASE 
            WHEN c.item_type = 'wisata' THEN w.judul
            WHEN c.item_type = 'penginapan' THEN p.judul
            WHEN c.item_type = 'artikel' THEN a.judul
        END as item_name,
        COUNT(*) as abandon_count,
        SUM(c.quantity) as total_quantity,
        SUM(c.subtotal) as total_value
    FROM cart_items c
    LEFT JOIN wisata w ON c.item_type = 'wisata' AND c.item_id = w.id
    LEFT JOIN penginapan p ON c.item_type = 'penginapan' AND c.item_id = p.id
    LEFT JOIN artikel a ON c.item_type = 'artikel' AND c.item_id = a.id
    WHERE DATE_ADD(c.updated_at, INTERVAL {$abandoned_minutes} MINUTE) < NOW()
    GROUP BY c.item_type, c.item_id
    ORDER BY abandon_count DESC
    LIMIT 10
";

$abandoned_products = $db->query($abandoned_products_query)->fetch_all(MYSQLI_ASSOC);

// Summary statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT c.user_id) as total_abandoned_users,
        COUNT(c.id) as total_abandoned_items,
        SUM(c.subtotal) as total_abandoned_value,
        AVG(c.subtotal) as avg_item_value
    FROM cart_items c
    WHERE DATE_ADD(c.updated_at, INTERVAL {$abandoned_minutes} MINUTE) < NOW()
";

$stats = $db->query($stats_query)->fetch_assoc();

// Category breakdown
$category_stats_query = "
    SELECT 
        c.item_type,
        COUNT(DISTINCT c.user_id) as users_count,
        COUNT(c.id) as items_count,
        SUM(c.subtotal) as total_value
    FROM cart_items c
    WHERE DATE_ADD(c.updated_at, INTERVAL {$abandoned_minutes} MINUTE) < NOW()
    GROUP BY c.item_type
    ORDER BY total_value DESC
";

$category_stats = $db->query($category_stats_query)->fetch_all(MYSQLI_ASSOC);

// Format functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function formatDuration($minutes) {
    if ($minutes < 60) {
        return $minutes . ' menit';
    } elseif ($minutes < 1440) { // 24 hours
        return round($minutes / 60, 1) . ' jam';
    } elseif ($minutes < 10080) { // 7 days
        return round($minutes / 1440, 1) . ' hari';
    } else {
        return round($minutes / 10080, 1) . ' minggu';
    }
}

function getItemTypeLabel($type) {
    switch ($type) {
        case 'wisata': return 'Wisata';
        case 'penginapan': return 'Penginapan';
        case 'artikel': return 'Produk UMKM';
        default: return ucfirst($type);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Papua Journey Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="sidebar.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .filters-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .filter-group input, .filter-group select {
            padding: 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card.danger {
            border-left-color: var(--danger-color);
        }
        
        .stat-card.warning {
            border-left-color: var(--warning-color);
        }
        
        .stat-card.success {
            border-left-color: var(--secondary-color);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .abandoned-carts-table {
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
        }
        
        .table-header {
            background: #F9FAFB;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table-header h3 {
            margin: 0;
            color: var(--text-primary);
        }
        
        .cart-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            transition: background 0.2s;
        }
        
        .cart-item:hover {
            background: #F9FAFB;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .user-info h4 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1rem;
        }
        
        .user-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .cart-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 0.75rem;
        }
        
        .cart-items-list {
            font-size: 0.875rem;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        
        .cart-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .cart-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .duration-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .duration-badge.recent {
            background: #FEF3C7;
            color: #92400E;
        }
        
        .duration-badge.old {
            background: #FEE2E2;
            color: #991B1B;
        }
        
        .products-analytics {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .btn-filter {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-filter:hover {
            background: var(--primary-hover);
        }
        
        .btn-export {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.2s;
        }
        
        .btn-export:hover {
            background: #059669;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .cart-details {
                grid-template-columns: 1fr;
            }
            
            .cart-meta {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'components/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'components/header.php'; ?>
            
            <div class="content-area">
                <!-- Filters -->
                <div class="filters-card">
                    <h3 style="margin-bottom: 1rem;">Filter Keranjang Ditinggalkan</h3>
                    <form method="GET" style="margin: 0;">
                        <div class="filters-grid">
                            <div class="filter-group">
                                <label>Ditinggalkan selama (menit):</label>
                                <input type="number" name="minutes" value="<?php echo $abandoned_minutes; ?>" min="1" max="525600">
                            </div>
                            <div class="filter-group">
                                <label>Tanggal Dari:</label>
                                <input type="date" name="date_from" value="<?php echo $date_filter; ?>">
                            </div>
                            <div class="filter-group">
                                <label>Tanggal Sampai:</label>
                                <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="filter-group">
                                <label>Kategori:</label>
                                <select name="category">
                                    <option value="">Semua Kategori</option>
                                    <option value="wisata" <?php echo $category_filter === 'wisata' ? 'selected' : ''; ?>>Wisata</option>
                                    <option value="penginapan" <?php echo $category_filter === 'penginapan' ? 'selected' : ''; ?>>Penginapan</option>
                                    <option value="artikel" <?php echo $category_filter === 'artikel' ? 'selected' : ''; ?>>Produk UMKM</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label>Nilai Min (Rp):</label>
                                <input type="number" name="min_value" value="<?php echo $min_value; ?>" min="0">
                            </div>
                            <div class="filter-group">
                                <label>Nilai Maks (Rp):</label>
                                <input type="number" name="max_value" value="<?php echo $max_value; ?>" min="0">
                            </div>
                        </div>
                        <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="abandoned_cart.php" class="btn-filter" style="background: var(--text-secondary); text-decoration: none;">
                                <i class="fas fa-refresh"></i> Reset
                            </a>
                            <a href="export_abandoned_cart.php?<?php echo http_build_query($_GET); ?>" class="btn-export">
                                <i class="fas fa-download"></i> Export CSV
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card danger">
                        <div class="stat-value"><?php echo number_format($stats['total_abandoned_users'] ?? 0); ?></div>
                        <div class="stat-label">Pengguna dengan Keranjang Ditinggalkan</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-value"><?php echo number_format($stats['total_abandoned_items'] ?? 0); ?></div>
                        <div class="stat-label">Total Item Ditinggalkan</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-value"><?php echo formatPrice($stats['total_abandoned_value'] ?? 0); ?></div>
                        <div class="stat-label">Nilai Total Keranjang Ditinggalkan</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-value"><?php echo formatPrice($stats['avg_item_value'] ?? 0); ?></div>
                        <div class="stat-label">Rata-rata Nilai per Item</div>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <?php if (!empty($category_stats)): ?>
                <div class="products-analytics">
                    <h3 style="margin-bottom: 1rem;">Breakdown per Kategori</h3>
                    <?php foreach ($category_stats as $category): ?>
                    <div class="product-item">
                        <div class="product-info">
                            <h4><?php echo getItemTypeLabel($category['item_type']); ?></h4>
                        </div>
                        <div class="product-stats">
                            <span><?php echo $category['users_count']; ?> pengguna</span>
                            <span><?php echo $category['items_count']; ?> item</span>
                            <span><?php echo formatPrice($category['total_value']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Abandoned Carts List -->
                <div class="abandoned-carts-table">
                    <div class="table-header">
                        <h3>Daftar Keranjang Ditinggalkan (<?php echo count($abandoned_carts); ?> keranjang)</h3>
                    </div>
                    
                    <?php if (empty($abandoned_carts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Tidak ada keranjang ditinggalkan</h3>
                        <p>Belum ada keranjang yang sesuai dengan kriteria filter yang dipilih.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($abandoned_carts as $cart): ?>
                        <div class="cart-item">
                            <div class="cart-user">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($cart['user_name'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($cart['user_name'] ?? 'Nama tidak tersedia'); ?></h4>
                                    <p><?php echo htmlspecialchars($cart['user_email'] ?? 'Email tidak tersedia'); ?></p>
                                    <?php if ($cart['user_phone']): ?>
                                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($cart['user_phone']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="cart-details">
                                <div>
                                    <strong>Items dalam keranjang:</strong>
                                    <div class="cart-items-list">
                                        <?php echo htmlspecialchars($cart['items_list']); ?>
                                    </div>
                                </div>
                                <div>
                                    <strong>Kategori:</strong>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                        <?php 
                                        $types = explode(',', $cart['item_types']);
                                        echo implode(', ', array_map('getItemTypeLabel', $types));
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="cart-meta">
                                <div class="cart-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-shopping-basket"></i>
                                        <span><?php echo $cart['items_count']; ?> item</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-money-bill"></i>
                                        <span><?php echo formatPrice($cart['cart_total']); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo $cart['last_activity']; ?></span>
                                    </div>
                                </div>
                                <div class="duration-badge <?php echo $cart['minutes_abandoned'] > 10080 ? 'old' : 'recent'; ?>">
                                    Ditinggalkan <?php echo formatDuration($cart['minutes_abandoned']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Most Abandoned Products -->
                <?php if (!empty($abandoned_products)): ?>
                <div class="products-analytics">
                    <h3 style="margin-bottom: 1rem;">Produk Paling Sering Ditinggalkan</h3>
                    <?php foreach ($abandoned_products as $product): ?>
                    <div class="product-item">
                        <div class="product-info">
                            <h4><?php echo htmlspecialchars($product['item_name']); ?></h4>
                            <p style="margin: 0; color: var(--text-secondary); font-size: 0.875rem;">
                                <?php echo getItemTypeLabel($product['item_type']); ?>
                            </p>
                        </div>
                        <div class="product-stats">
                            <span><?php echo $product['abandon_count']; ?> kali</span>
                            <span><?php echo $product['total_quantity']; ?> qty</span>
                            <span><?php echo formatPrice($product['total_value']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="assets/js/admin.js"></script>
</body>
</html>

<?php
$db->close();
?>