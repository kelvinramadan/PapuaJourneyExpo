<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

require_once '../config/database.php';

$db = getDbConnection();

// Get date filter from query params
$date_filter = $_GET['filter'] ?? '7days';
$start_date = '';
$end_date = date('Y-m-d');

switch($date_filter) {
    case '1day':
        $start_date = date('Y-m-d', strtotime('-1 day'));
        break;
    case '7days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case '30days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case '90days':
        $start_date = date('Y-m-d', strtotime('-90 days'));
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-7 days'));
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="abandoned_cart_report_' . $start_date . '_to_' . $end_date . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, [
    'Abandonment ID',
    'User ID',
    'Email',
    'Nama User',
    'Tanggal Abandonment',
    'Total Nilai',
    'Jumlah Item',
    'Durasi Sesi (menit)',
    'Status Recovery',
    'Metode Recovery',
    'Alasan Abandonment',
    'Halaman Sebelum Abandonment',
    'Detail Produk'
]);

// Get abandoned cart data
$query = "
    SELECT 
        ac.*,
        u.email,
        u.full_name,
        car.reason_code,
        car.reason_text
    FROM abandoned_carts ac
    LEFT JOIN users u ON ac.user_id = u.id
    LEFT JOIN cart_abandonment_reasons car ON ac.id = car.abandoned_cart_id
    WHERE DATE(ac.abandonment_timestamp) BETWEEN ? AND ?
    ORDER BY ac.abandonment_timestamp DESC
";

$stmt = $db->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Parse cart items for product details
    $cart_items = json_decode($row['cart_items_snapshot'], true);
    $product_details = [];
    
    foreach ($cart_items as $item) {
        $product_details[] = $item['item_name'] . ' (' . $item['item_type'] . ') - Qty: ' . $item['quantity'] . ' - ' . number_format($item['subtotal'], 0);
    }
    
    $product_details_str = implode('; ', $product_details);
    
    // Format recovery status
    $recovery_status = $row['is_recovered'] ? 'Recovered' : 'Not Recovered';
    $recovery_method = $row['recovery_method'] ?? '-';
    
    // Format reason
    $reason = '';
    if ($row['reason_code']) {
        $reason_labels = [
            'price_too_high' => 'Harga Terlalu Mahal',
            'shipping_cost' => 'Biaya Pengiriman',
            'not_sure' => 'Belum Yakin',
            'payment_issues' => 'Masalah Pembayaran',
            'found_better_deal' => 'Penawaran Lebih Baik',
            'changed_mind' => 'Berubah Pikiran',
            'technical_issues' => 'Masalah Teknis',
            'other' => 'Lainnya'
        ];
        $reason = $reason_labels[$row['reason_code']] ?? $row['reason_code'];
        if ($row['reason_text']) {
            $reason .= ': ' . $row['reason_text'];
        }
    }
    
    // Write data row
    fputcsv($output, [
        $row['id'],
        $row['user_id'],
        $row['email'] ?? '-',
        $row['full_name'] ?? '-',
        $row['abandonment_timestamp'],
        number_format($row['total_value'], 2),
        $row['item_count'],
        $row['session_duration_minutes'] ?? '-',
        $recovery_status,
        $recovery_method,
        $reason,
        $row['page_before_abandonment'] ?? '-',
        $product_details_str
    ]);
}

$stmt->close();
$db->close();

fclose($output);
?>