<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get parameters
$export_type = $_GET['type'] ?? 'csv';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch financial data
$financial_data = getFinancialData($conn, $start_date, $end_date);

if ($export_type === 'csv') {
    exportCSV($financial_data, $start_date, $end_date);
} else {
    exportPDF($financial_data, $start_date, $end_date);
}

$conn->close();

function getFinancialData($conn, $start_date, $end_date) {
    $data = [];
    
    // Overview statistics
    $overview_query = "
        SELECT 
            COUNT(DISTINCT t.id) as total_transactions,
            SUM(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE 0 END) as total_revenue,
            SUM(CASE WHEN t.payment_status = 'paid' THEN 1 ELSE 0 END) as successful_transactions,
            SUM(CASE WHEN t.payment_status IN ('rejected', 'cancelled') THEN 1 ELSE 0 END) as failed_transactions,
            AVG(CASE WHEN t.payment_status = 'paid' THEN t.total_amount ELSE NULL END) as avg_order_value
        FROM transaksi t
        WHERE DATE(t.created_at) BETWEEN ? AND ?
    ";
    
    $stmt = $conn->prepare($overview_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $data['overview'] = $stmt->get_result()->fetch_assoc();
    
    // Daily revenue
    $daily_query = "
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as transaction_count,
            SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as revenue,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 ELSE NULL END) as paid_count,
            COUNT(CASE WHEN payment_status IN ('rejected', 'cancelled') THEN 1 ELSE NULL END) as failed_count
        FROM transaksi
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ";
    
    $stmt = $conn->prepare($daily_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $data['daily_revenue'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Revenue by product type
    $product_type_query = "
        SELECT 
            ti.item_type,
            COUNT(DISTINCT ti.transaksi_id) as transaction_count,
            SUM(ti.quantity) as total_quantity,
            SUM(ti.subtotal) as revenue
        FROM transaksi_items ti
        JOIN transaksi t ON ti.transaksi_id = t.id
        WHERE t.payment_status = 'paid' 
        AND DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY ti.item_type
    ";
    
    $stmt = $conn->prepare($product_type_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $data['product_types'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Top products
    $top_products_query = "
        SELECT 
            ti.item_name,
            ti.item_type,
            COUNT(ti.id) as sales_count,
            SUM(ti.quantity) as total_quantity,
            SUM(ti.subtotal) as total_revenue
        FROM transaksi_items ti
        JOIN transaksi t ON ti.transaksi_id = t.id
        WHERE t.payment_status = 'paid'
        AND DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY ti.item_name, ti.item_type
        ORDER BY total_revenue DESC
        LIMIT 20
    ";
    
    $stmt = $conn->prepare($top_products_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $data['top_products'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // UMKM revenue
    $umkm_query = "
        SELECT 
            u.business_name as nama_umkm,
            COUNT(DISTINCT t.id) as transaction_count,
            SUM(ti.quantity) as items_sold,
            SUM(ti.subtotal) as total_revenue
        FROM umkm u
        JOIN artikel a ON u.id = a.umkm_id
        JOIN transaksi_items ti ON ti.item_id = a.id AND ti.item_type = 'artikel'
        JOIN transaksi t ON ti.transaksi_id = t.id
        WHERE t.payment_status = 'paid'
        AND DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY u.id
        ORDER BY total_revenue DESC
    ";
    
    $stmt = $conn->prepare($umkm_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $data['umkm_revenue'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $data;
}

function exportCSV($data, $start_date, $end_date) {
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="financial_report_' . $start_date . '_to_' . $end_date . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Overview Section
    fputcsv($output, ['FINANCIAL REPORT']);
    fputcsv($output, ['Period:', $start_date . ' to ' . $end_date]);
    fputcsv($output, []);
    
    fputcsv($output, ['OVERVIEW']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Revenue', 'Rp ' . number_format($data['overview']['total_revenue'], 0, ',', '.')]);
    fputcsv($output, ['Total Transactions', number_format($data['overview']['total_transactions'])]);
    fputcsv($output, ['Successful Transactions', number_format($data['overview']['successful_transactions'])]);
    fputcsv($output, ['Failed Transactions', number_format($data['overview']['failed_transactions'])]);
    fputcsv($output, ['Average Order Value', 'Rp ' . number_format($data['overview']['avg_order_value'], 0, ',', '.')]);
    fputcsv($output, []);
    
    // Daily Revenue
    fputcsv($output, ['DAILY REVENUE']);
    fputcsv($output, ['Date', 'Total Transactions', 'Successful', 'Failed', 'Revenue']);
    foreach ($data['daily_revenue'] as $day) {
        fputcsv($output, [
            $day['date'],
            $day['transaction_count'],
            $day['paid_count'],
            $day['failed_count'],
            'Rp ' . number_format($day['revenue'], 0, ',', '.')
        ]);
    }
    fputcsv($output, []);
    
    // Revenue by Product Type
    fputcsv($output, ['REVENUE BY PRODUCT TYPE']);
    fputcsv($output, ['Product Type', 'Transactions', 'Quantity Sold', 'Revenue']);
    foreach ($data['product_types'] as $type) {
        fputcsv($output, [
            ucfirst($type['item_type']),
            $type['transaction_count'],
            $type['total_quantity'],
            'Rp ' . number_format($type['revenue'], 0, ',', '.')
        ]);
    }
    fputcsv($output, []);
    
    // Top Products
    fputcsv($output, ['TOP PRODUCTS']);
    fputcsv($output, ['Product Name', 'Type', 'Sales Count', 'Quantity Sold', 'Revenue']);
    foreach ($data['top_products'] as $product) {
        fputcsv($output, [
            $product['item_name'],
            ucfirst($product['item_type']),
            $product['sales_count'],
            $product['total_quantity'],
            'Rp ' . number_format($product['total_revenue'], 0, ',', '.')
        ]);
    }
    fputcsv($output, []);
    
    // UMKM Revenue
    fputcsv($output, ['UMKM REVENUE']);
    fputcsv($output, ['UMKM Name', 'Transactions', 'Items Sold', 'Revenue']);
    foreach ($data['umkm_revenue'] as $umkm) {
        fputcsv($output, [
            $umkm['nama_umkm'],
            $umkm['transaction_count'],
            $umkm['items_sold'],
            'Rp ' . number_format($umkm['total_revenue'], 0, ',', '.')
        ]);
    }
    
    fclose($output);
    exit();
}

function exportPDF($data, $start_date, $end_date) {
    // For PDF export, we'll use a simple HTML-to-PDF approach
    // In a production environment, you'd use a library like TCPDF or DOMPDF
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Financial Report</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; color: #333; }
            h1 { color: #4F46E5; font-size: 24px; }
            h2 { color: #333; font-size: 18px; margin-top: 20px; }
            table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f5f5f5; font-weight: bold; }
            .overview-stat { margin: 10px 0; }
            .stat-label { font-weight: bold; }
            .stat-value { font-size: 16px; color: #4F46E5; }
            .page-break { page-break-after: always; }
        </style>
    </head>
    <body>';
    
    $html .= '<h1>Financial Report</h1>';
    $html .= '<p>Period: ' . $start_date . ' to ' . $end_date . '</p>';
    
    // Overview
    $html .= '<h2>Overview</h2>';
    $html .= '<div class="overview-stat"><span class="stat-label">Total Revenue:</span> <span class="stat-value">Rp ' . number_format($data['overview']['total_revenue'], 0, ',', '.') . '</span></div>';
    $html .= '<div class="overview-stat"><span class="stat-label">Total Transactions:</span> <span class="stat-value">' . number_format($data['overview']['total_transactions']) . '</span></div>';
    $html .= '<div class="overview-stat"><span class="stat-label">Successful Transactions:</span> <span class="stat-value">' . number_format($data['overview']['successful_transactions']) . '</span></div>';
    $html .= '<div class="overview-stat"><span class="stat-label">Failed Transactions:</span> <span class="stat-value">' . number_format($data['overview']['failed_transactions']) . '</span></div>';
    $html .= '<div class="overview-stat"><span class="stat-label">Average Order Value:</span> <span class="stat-value">Rp ' . number_format($data['overview']['avg_order_value'], 0, ',', '.') . '</span></div>';
    
    // Revenue by Product Type
    $html .= '<h2>Revenue by Product Type</h2>';
    $html .= '<table>';
    $html .= '<tr><th>Product Type</th><th>Transactions</th><th>Quantity</th><th>Revenue</th></tr>';
    foreach ($data['product_types'] as $type) {
        $html .= '<tr>';
        $html .= '<td>' . ucfirst($type['item_type']) . '</td>';
        $html .= '<td>' . number_format($type['transaction_count']) . '</td>';
        $html .= '<td>' . number_format($type['total_quantity']) . '</td>';
        $html .= '<td>Rp ' . number_format($type['revenue'], 0, ',', '.') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    
    // Top Products
    $html .= '<div class="page-break"></div>';
    $html .= '<h2>Top Products</h2>';
    $html .= '<table>';
    $html .= '<tr><th>Product Name</th><th>Type</th><th>Sales</th><th>Quantity</th><th>Revenue</th></tr>';
    foreach (array_slice($data['top_products'], 0, 10) as $product) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($product['item_name']) . '</td>';
        $html .= '<td>' . ucfirst($product['item_type']) . '</td>';
        $html .= '<td>' . number_format($product['sales_count']) . '</td>';
        $html .= '<td>' . number_format($product['total_quantity']) . '</td>';
        $html .= '<td>Rp ' . number_format($product['total_revenue'], 0, ',', '.') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    
    // UMKM Revenue
    $html .= '<h2>UMKM Revenue</h2>';
    $html .= '<table>';
    $html .= '<tr><th>UMKM Name</th><th>Transactions</th><th>Items Sold</th><th>Revenue</th></tr>';
    foreach (array_slice($data['umkm_revenue'], 0, 10) as $umkm) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($umkm['nama_umkm']) . '</td>';
        $html .= '<td>' . number_format($umkm['transaction_count']) . '</td>';
        $html .= '<td>' . number_format($umkm['items_sold']) . '</td>';
        $html .= '<td>Rp ' . number_format($umkm['total_revenue'], 0, ',', '.') . '</td>';
        $html .= '</tr>';
    }
    $html .= '</table>';
    
    $html .= '</body></html>';
    
    // For simple PDF export, we'll output as HTML with print styling
    // In production, use a proper PDF library
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="financial_report_' . $start_date . '_to_' . $end_date . '.html"');
    echo $html;
    exit();
}
?>