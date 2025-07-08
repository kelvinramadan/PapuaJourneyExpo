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
        /* Design System Variables */
        :root {
            --forest-green: #363c2b;
            --mustard-yellow: #F9B705;
            --relaxed-yellow: #FFC82C;
            --dark-gray: #2E2E2E;
            --pale-yellow: #FFF6F7;
            --white: #FFFFFF;
            --text-primary: var(--dark-gray);
            --text-secondary: #6B7280;
            --border-color: #E5E7EB;
            --error-color: #EF4444;
        }

        /* Enhanced page styling with new design system */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .content-area {
            background: var(--pale-yellow);
            min-height: 100vh;
            padding: 2rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
        }

        .page-header h1 {
            font-size: 2.75rem;
            font-weight: bold;
            color: var(--forest-green);
            margin-bottom: 0.5rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 1.125rem;
            font-weight: 500;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .filters-card {
            background: var(--white);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 12px rgba(54, 60, 43, 0.1);
            border: 2px solid var(--relaxed-yellow);
        }

        .filters-card h3 {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .filters-card h3::before {
            content: "🔍";
            font-size: 1.25rem;
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .filter-group label {
            font-weight: bold;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .filter-group input, .filter-group select {
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: var(--white);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: var(--mustard-yellow);
            box-shadow: 0 0 0 3px rgba(249, 183, 5, 0.2);
            transform: translateY(-1px);
        }

        .filter-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: var(--white);
            padding: 2rem;
            border-radius: 1.25rem;
            box-shadow: 0 4px 12px rgba(54, 60, 43, 0.1);
            border: 2px solid var(--relaxed-yellow);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--mustard-yellow);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        }
        
        .stat-card.danger::before {
            background: var(--error-color);
        }
        
        .stat-card.warning::before {
            background: var(--relaxed-yellow);
        }
        
        .stat-card.success::before {
            background: var(--forest-green);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--white);
        }

        .stat-card.danger .stat-icon {
            background: var(--error-color);
        }

        .stat-card.warning .stat-icon {
            background: var(--relaxed-yellow);
            color: var(--dark-gray);
        }

        .stat-card.success .stat-icon {
            background: var(--forest-green);
        }

        .stat-card .stat-icon {
            background: var(--mustard-yellow);
            color: var(--dark-gray);
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            line-height: 1;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            line-height: 1.4;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .abandoned-carts-table {
            background: var(--white);
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(54, 60, 43, 0.1);
            margin-bottom: 3rem;
            border: 2px solid var(--relaxed-yellow);
        }
        
        .table-header {
            background: var(--forest-green);
            padding: 1.5rem 2rem;
            border-bottom: none;
        }
        
        .table-header h3 {
            margin: 0;
            color: var(--white);
            font-size: 1.375rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .table-header h3::before {
            content: "🛒";
            font-size: 1.25rem;
        }
        
        .cart-item {
            padding: 2rem;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.3s ease;
            position: relative;
        }

        .cart-item::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--mustard-yellow);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }
        
        .cart-item:hover {
            background: var(--pale-yellow);
            transform: translateX(5px);
        }

        .cart-item:hover::after {
            transform: scaleY(1);
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-user {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.25rem;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 1rem;
            background: var(--mustard-yellow);
            color: var(--dark-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.25rem;
            box-shadow: 0 4px 8px rgba(249, 183, 5, 0.3);
            position: relative;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .user-avatar::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: var(--relaxed-yellow);
            border-radius: 1.125rem;
            z-index: -1;
            opacity: 0.5;
        }
        
        .user-info h4 {
            margin: 0 0 0.25rem 0;
            color: var(--text-primary);
            font-size: 1.125rem;
            font-weight: bold;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .user-info p {
            margin: 0.125rem 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .user-info p i {
            color: var(--mustard-yellow);
            width: 16px;
        }
        
        .cart-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 1.5rem;
            background: var(--pale-yellow);
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--relaxed-yellow);
        }

        .cart-details > div {
            position: relative;
        }

        .cart-details strong {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--text-primary);
            font-weight: bold;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .cart-items-list {
            font-size: 0.95rem;
            color: var(--text-secondary);
            line-height: 1.6;
            background: var(--white);
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid var(--relaxed-yellow);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .cart-category {
            font-size: 0.95rem;
            color: var(--text-secondary);
            background: var(--white);
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid var(--relaxed-yellow);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .cart-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1.5rem;
            flex-wrap: wrap;
            background: var(--white);
            padding: 1.5rem;
            border-radius: 1rem;
            border: 1px solid var(--relaxed-yellow);
        }
        
        .cart-stats {
            display: flex;
            gap: 2rem;
            font-size: 0.9rem;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: var(--pale-yellow);
            border-radius: 0.75rem;
            border: 1px solid var(--relaxed-yellow);
            font-weight: 500;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .stat-item i {
            color: var(--mustard-yellow);
            font-size: 1rem;
        }
        
        .duration-badge {
            padding: 0.75rem 1.25rem;
            border-radius: 2rem;
            font-size: 0.85rem;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .duration-badge.recent {
            background: var(--relaxed-yellow);
            color: var(--dark-gray);
            border: 1px solid var(--mustard-yellow);
        }
        
        .duration-badge.old {
            background: var(--error-color);
            color: var(--white);
            border: 1px solid var(--error-color);
        }
        
        .products-analytics {
            background: var(--white);
            border-radius: 1.25rem;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(54, 60, 43, 0.1);
            border: 2px solid var(--relaxed-yellow);
            margin-bottom: 2rem;
        }

        .products-analytics h3 {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .products-analytics h3::before {
            content: "📊";
            font-size: 1.25rem;
        }
        
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }

        .product-item:hover {
            transform: translateX(5px);
            background: var(--pale-yellow);
            padding-left: 1rem;
            padding-right: 1rem;
            border-radius: 0.75rem;
            border-bottom: 1px solid transparent;
            margin: 0 -1rem;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-info {
            flex: 1;
        }

        .product-info h4 {
            margin: 0 0 0.25rem 0;
            color: var(--text-primary);
            font-weight: bold;
            font-size: 1.1rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .product-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .product-stats {
            display: flex;
            gap: 1.5rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .product-stats span {
            background: var(--pale-yellow);
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            border: 1px solid var(--relaxed-yellow);
            font-weight: 600;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .btn-filter {
            background: var(--mustard-yellow);
            color: var(--dark-gray);
            border: 2px solid var(--mustard-yellow);
            padding: 0.875rem 1.5rem;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 2px 4px rgba(249, 183, 5, 0.2);
            text-decoration: none;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .btn-filter:hover {
            background: transparent;
            color: var(--mustard-yellow);
            border-color: var(--mustard-yellow);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(249, 183, 5, 0.3);
        }

        .btn-filter.secondary {
            background: var(--dark-gray);
            color: var(--white);
            border: 2px solid var(--dark-gray);
            box-shadow: 0 2px 4px rgba(46, 46, 46, 0.2);
        }

        .btn-filter.secondary:hover {
            background: transparent;
            color: var(--dark-gray);
            border-color: var(--dark-gray);
            box-shadow: 0 4px 8px rgba(46, 46, 46, 0.3);
        }
        
        .btn-export {
            background: var(--forest-green);
            color: var(--white);
            border: 2px solid var(--forest-green);
            padding: 0.875rem 1.5rem;
            border-radius: 0.75rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: bold;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(54, 60, 43, 0.2);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .btn-export:hover {
            background: transparent;
            color: var(--forest-green);
            border-color: var(--forest-green);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(54, 60, 43, 0.3);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.6;
            color: var(--mustard-yellow);
        }

        .empty-state h3 {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .empty-state p {
            font-size: 1rem;
            line-height: 1.6;
            max-width: 400px;
            margin: 0 auto;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Enhanced responsive design */
        @media (max-width: 1024px) {
            .content-area {
                padding: 1.5rem;
            }

            .page-header h1 {
                font-size: 2.25rem;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
            }

            .user-avatar {
                width: 50px;
                height: 50px;
                font-size: 1.1rem;
            }
        }

        @media (max-width: 768px) {
            .content-area {
                padding: 1rem;
            }

            .page-header {
                padding: 1rem 0;
                margin-bottom: 2rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-value {
                font-size: 2rem;
            }
            
            .cart-details {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .cart-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .cart-stats {
                gap: 1rem;
                flex-wrap: wrap;
            }

            .filter-actions {
                flex-direction: column;
            }

            .btn-filter, .btn-export {
                width: 100%;
                justify-content: center;
            }

            .user-avatar {
                width: 45px;
                height: 45px;
                font-size: 1rem;
            }

            .cart-item {
                padding: 1.5rem;
            }

            .product-stats {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .page-header h1 {
                font-size: 1.75rem;
            }

            .filters-card, .products-analytics {
                padding: 1.5rem;
            }

            .cart-item {
                padding: 1rem;
            }

            .cart-user {
                gap: 1rem;
            }

            .user-info h4 {
                font-size: 1rem;
            }

            .user-info p {
                font-size: 0.85rem;
            }
        }

        /* Loading animation for enhanced UX */
        @keyframes shimmer {
            0% {
                background-position: -468px 0;
            }
            100% {
                background-position: 468px 0;
            }
        }

        .loading-shimmer {
            background: linear-gradient(to right, #f6f7f8 0%, #edeef1 20%, #f6f7f8 40%, #f6f7f8 100%);
            background-size: 800px 104px;
            animation: shimmer 1s linear infinite;
        }

        /* Custom scrollbar */
        .content-area::-webkit-scrollbar {
            width: 8px;
        }

        .content-area::-webkit-scrollbar-track {
            background: var(--pale-yellow);
            border-radius: 4px;
        }

        .content-area::-webkit-scrollbar-thumb {
            background: var(--mustard-yellow);
            border-radius: 4px;
        }

        .content-area::-webkit-scrollbar-thumb:hover {
            background: var(--relaxed-yellow);
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'components/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'components/header.php'; ?>
            
            <div class="content-area">
                <div class="page-header">
                    <h1><?php echo $page_title; ?></h1>
                    <p>Kelola dan analisis keranjang yang ditinggalkan pengguna</p>
                </div>

                <!-- Filters -->
                <div class="filters-card">
                    <h3>Filter Keranjang Ditinggalkan</h3>
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
                        <div class="filter-actions">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="abandoned_cart.php" class="btn-filter secondary">
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
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_abandoned_users'] ?? 0); ?></div>
                        <div class="stat-label">Pengguna dengan Keranjang Ditinggalkan</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-basket"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_abandoned_items'] ?? 0); ?></div>
                        <div class="stat-label">Total Item Ditinggalkan</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-value"><?php echo formatPrice($stats['total_abandoned_value'] ?? 0); ?></div>
                        <div class="stat-label">Nilai Total Keranjang Ditinggalkan</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-value"><?php echo formatPrice($stats['avg_item_value'] ?? 0); ?></div>
                        <div class="stat-label">Rata-rata Nilai per Item</div>
                    </div>
                </div>

                <!-- Category Breakdown -->
                <?php if (!empty($category_stats)): ?>
                <div class="products-analytics">
                    <h3>Breakdown per Kategori</h3>
                    <?php foreach ($category_stats as $category): ?>
                    <div class="product-item">
                        <div class="product-info">
                            <h4><?php echo getItemTypeLabel($category['item_type']); ?></h4>
                            <p>Kategori <?php echo strtolower(getItemTypeLabel($category['item_type'])); ?></p>
                        </div>
                        <div class="product-stats">
                            <span><i class="fas fa-users"></i> <?php echo $category['users_count']; ?> pengguna</span>
                            <span><i class="fas fa-box"></i> <?php echo $category['items_count']; ?> item</span>
                            <span><i class="fas fa-money-bill"></i> <?php echo formatPrice($category['total_value']); ?></span>
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
                                    <strong><i class="fas fa-shopping-bag"></i> Items dalam keranjang:</strong>
                                    <div class="cart-items-list">
                                        <?php echo htmlspecialchars($cart['items_list']); ?>
                                    </div>
                                </div>
                                <div>
                                    <strong><i class="fas fa-tags"></i> Kategori:</strong>
                                    <div class="cart-category">
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
                    <h3>Produk Paling Sering Ditinggalkan</h3>
                    <?php foreach ($abandoned_products as $product): ?>
                    <div class="product-item">
                        <div class="product-info">
                            <h4><?php echo htmlspecialchars($product['item_name']); ?></h4>
                            <p><?php echo getItemTypeLabel($product['item_type']); ?></p>
                        </div>
                        <div class="product-stats">
                            <span><i class="fas fa-exclamation-triangle"></i> <?php echo $product['abandon_count']; ?> kali</span>
                            <span><i class="fas fa-cubes"></i> <?php echo $product['total_quantity']; ?> qty</span>
                            <span><i class="fas fa-money-bill"></i> <?php echo formatPrice($product['total_value']); ?></span>
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