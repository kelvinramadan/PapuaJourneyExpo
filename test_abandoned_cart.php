<?php
// Test version of abandoned cart page
$page_title = 'Keranjang Ditinggalkan';
$abandoned_minutes = 1;
$date_filter = '';
$date_to = '';
$category_filter = '';
$min_value = '';
$max_value = '';

// Mock data for testing
$abandoned_carts = [
    [
        'user_name' => 'John Doe',
        'user_email' => 'john@example.com',
        'user_phone' => '+62812345678',
        'items_count' => 3,
        'cart_total' => 1250000,
        'last_activity' => '2024-01-15 14:30:00',
        'minutes_abandoned' => 120,
        'items_list' => 'Wisata Raja Ampat (2x), Hotel Manokwari (1x)',
        'item_types' => 'wisata,penginapan'
    ],
    [
        'user_name' => 'Jane Smith',
        'user_email' => 'jane@example.com',
        'user_phone' => '+62823456789',
        'items_count' => 1,
        'cart_total' => 450000,
        'last_activity' => '2024-01-15 10:15:00',
        'minutes_abandoned' => 300,
        'items_list' => 'Kerajinan Papuan (1x)',
        'item_types' => 'artikel'
    ]
];

$stats = [
    'total_abandoned_users' => 25,
    'total_abandoned_items' => 48,
    'total_abandoned_value' => 12500000,
    'avg_item_value' => 260416
];

$category_stats = [
    ['item_type' => 'wisata', 'users_count' => 15, 'items_count' => 28, 'total_value' => 8500000],
    ['item_type' => 'penginapan', 'users_count' => 8, 'items_count' => 12, 'total_value' => 3200000],
    ['item_type' => 'artikel', 'users_count' => 5, 'items_count' => 8, 'total_value' => 800000]
];

$abandoned_products = [
    ['item_name' => 'Wisata Raja Ampat', 'item_type' => 'wisata', 'abandon_count' => 8, 'total_quantity' => 15, 'total_value' => 3500000],
    ['item_name' => 'Hotel Manokwari', 'item_type' => 'penginapan', 'abandon_count' => 5, 'total_quantity' => 5, 'total_value' => 1800000]
];

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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4F46E5;
            --primary-hover: #4338CA;
            --secondary-color: #10B981;
            --danger-color: #EF4444;
            --warning-color: #F59E0B;
            --info-color: #3B82F6;
            --dark-bg: #1F2937;
            --sidebar-bg: #111827;
            --card-bg: #FFFFFF;
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --border-color: #E5E7EB;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #F9FAFB;
            color: var(--text-primary);
            line-height: 1.6;
            padding: 20px;
        }

        .content-area {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            font-size: 2rem;
            margin-bottom: 2rem;
            color: var(--text-primary);
        }

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
    <div class="content-area">
        <h1 class="page-title"><?php echo $page_title; ?></h1>
        
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
                    <a href="#" class="btn-export">
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
</body>
</html>