<?php
// admin/export_abandoned_cart.php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

// Configuration for abandoned cart definition (in minutes for testing)
$abandoned_minutes = isset($_GET['minutes']) ? max(1, (int)$_GET['minutes']) : 1;

// Filters
$date_filter = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$category_filter = $_GET['category'] ?? '';
$min_value = $_GET['min_value'] ?? '';
$max_value = $_GET['max_value'] ?? '';

$db = getDbConnection();

// Build WHERE clause for filters (same as main page)
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

// Query for abandoned carts with detailed information
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
                ' (', c.quantity, 'x - Rp ', FORMAT(c.subtotal, 0), ')'
            ) SEPARATOR '; '
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

// Set headers for CSV download
$filename = 'abandoned_carts_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create file pointer
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV headers
$headers = [
    'User ID',
    'Nama Pengguna',
    'Email',
    'Nomor Telepon',
    'Jumlah Item',
    'Total Nilai (Rp)',
    'Aktivitas Terakhir',
    'Menit Ditinggalkan',
    'Kategori',
    'Daftar Item'
];

fputcsv($output, $headers);

// Format functions
function formatPrice($price) {
    return number_format($price, 0, ',', '.');
}

function getItemTypeLabel($type) {
    switch ($type) {
        case 'wisata': return 'Wisata';
        case 'penginapan': return 'Penginapan';
        case 'artikel': return 'Produk UMKM';
        default: return ucfirst($type);
    }
}

// Add data rows
foreach ($abandoned_carts as $cart) {
    $types = explode(',', $cart['item_types']);
    $typeLabels = array_map('getItemTypeLabel', $types);
    
    $row = [
        $cart['user_id'],
        $cart['user_name'] ?? 'Nama tidak tersedia',
        $cart['user_email'] ?? 'Email tidak tersedia',
        $cart['user_phone'] ?? '',
        $cart['items_count'],
        formatPrice($cart['cart_total']),
        $cart['last_activity'],
        $cart['minutes_abandoned'],
        implode(', ', $typeLabels),
        $cart['items_list']
    ];
    
    fputcsv($output, $row);
}

// Close file pointer
fclose($output);
$db->close();
exit();
?>