<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Get parameters
$type = $_GET['type'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$interval = $_GET['interval'] ?? 'daily'; // daily, weekly, monthly

// Set response header
header('Content-Type: application/json');

switch ($type) {
    case 'revenue_trend':
        $data = getRevenueTrend($conn, $start_date, $end_date, $interval);
        break;
        
    case 'product_performance':
        $data = getProductPerformance($conn, $start_date, $end_date);
        break;
        
    case 'transaction_status':
        $data = getTransactionStatus($conn, $start_date, $end_date);
        break;
        
    case 'umkm_revenue':
        $data = getUmkmRevenue($conn, $start_date, $end_date);
        break;
        
    case 'payment_methods':
        $data = getPaymentMethods($conn, $start_date, $end_date);
        break;
        
    case 'hourly_distribution':
        $data = getHourlyDistribution($conn, $start_date, $end_date);
        break;
        
    case 'conversion_rates':
        $data = getConversionRates($conn, $start_date, $end_date);
        break;
        
    default:
        $data = ['error' => 'Invalid request type'];
}

echo json_encode($data);
$conn->close();

// Function to get revenue trend
function getRevenueTrend($conn, $start_date, $end_date, $interval) {
    $groupBy = match($interval) {
        'weekly' => "DATE_FORMAT(created_at, '%Y-%u')",
        'monthly' => "DATE_FORMAT(created_at, '%Y-%m')",
        default => "DATE(created_at)"
    };
    
    $query = "
        SELECT 
            $groupBy as period,
            SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as revenue,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 ELSE NULL END) as paid_count,
            COUNT(*) as total_count,
            AVG(CASE WHEN payment_status = 'paid' THEN total_amount ELSE NULL END) as avg_amount
        FROM transaksi
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY period
        ORDER BY period ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Function to get product performance
function getProductPerformance($conn, $start_date, $end_date) {
    $query = "
        SELECT 
            ti.item_type,
            ti.item_name,
            ti.item_id,
            COUNT(DISTINCT ti.transaksi_id) as order_count,
            SUM(ti.quantity) as total_quantity,
            SUM(ti.subtotal) as total_revenue,
            AVG(ti.price_per_unit) as avg_price,
            MAX(ti.price_per_unit) as max_price,
            MIN(ti.price_per_unit) as min_price
        FROM transaksi_items ti
        JOIN transaksi t ON ti.transaksi_id = t.id
        WHERE t.payment_status = 'paid'
        AND DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY ti.item_type, ti.item_name, ti.item_id
        ORDER BY total_revenue DESC
        LIMIT 20
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Function to get transaction status distribution
function getTransactionStatus($conn, $start_date, $end_date) {
    $query = "
        SELECT 
            payment_status,
            COUNT(*) as count,
            SUM(total_amount) as total_amount,
            AVG(total_amount) as avg_amount
        FROM transaksi
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY payment_status
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Function to get UMKM revenue
function getUmkmRevenue($conn, $start_date, $end_date) {
    $query = "
        SELECT 
            u.id as umkm_id,
            u.business_name as nama_umkm,
            u.phone,
            u.address as alamat,
            COUNT(DISTINCT t.id) as transaction_count,
            SUM(ti.quantity) as items_sold,
            SUM(ti.subtotal) as total_revenue,
            AVG(ti.subtotal) as avg_order_value
        FROM umkm u
        JOIN artikel a ON u.id = a.umkm_id
        JOIN transaksi_items ti ON ti.item_id = a.id AND ti.item_type = 'artikel'
        JOIN transaksi t ON ti.transaksi_id = t.id
        WHERE t.payment_status = 'paid'
        AND DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY u.id
        ORDER BY total_revenue DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Function to get payment method distribution
function getPaymentMethods($conn, $start_date, $end_date) {
    $query = "
        SELECT 
            COALESCE(payment_method, 'Not Specified') as payment_method,
            COUNT(*) as count,
            SUM(total_amount) as total_amount,
            AVG(total_amount) as avg_amount,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 ELSE NULL END) as successful_count
        FROM transaksi
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY payment_method
        ORDER BY count DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Function to get hourly distribution of transactions
function getHourlyDistribution($conn, $start_date, $end_date) {
    $query = "
        SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as transaction_count,
            SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as revenue,
            AVG(CASE WHEN payment_status = 'paid' THEN total_amount ELSE NULL END) as avg_amount
        FROM transaksi
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY hour
        ORDER BY hour ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Function to get conversion rates
function getConversionRates($conn, $start_date, $end_date) {
    // Get view to purchase conversion for wisata
    $wisata_query = "
        SELECT 
            w.nama as destination_name,
            COALESCE(vs.view_count, 0) as views,
            COUNT(DISTINCT t.id) as purchases,
            SUM(ti.subtotal) as revenue,
            CASE WHEN COALESCE(vs.view_count, 0) > 0 
                THEN (COUNT(DISTINCT t.id) * 100.0 / vs.view_count) 
                ELSE 0 
            END as conversion_rate
        FROM wisata w
        LEFT JOIN (
            SELECT destination_id, SUM(view_count) as view_count
            FROM wisata_statistics
            WHERE date BETWEEN ? AND ?
            GROUP BY destination_id
        ) vs ON w.id = vs.destination_id
        LEFT JOIN transaksi_items ti ON ti.item_id = w.id AND ti.item_type = 'wisata'
        LEFT JOIN transaksi t ON ti.transaksi_id = t.id AND t.payment_status = 'paid' 
            AND DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY w.id
        HAVING views > 0 OR purchases > 0
        ORDER BY conversion_rate DESC
        LIMIT 20
    ";
    
    $stmt = $conn->prepare($wisata_query);
    $stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $wisata_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get view to purchase conversion for penginapan
    $penginapan_query = "
        SELECT 
            p.nama as accommodation_name,
            COALESCE(ps.view_count, 0) as views,
            COUNT(DISTINCT t.id) as purchases,
            SUM(ti.subtotal) as revenue,
            CASE WHEN COALESCE(ps.view_count, 0) > 0 
                THEN (COUNT(DISTINCT t.id) * 100.0 / ps.view_count) 
                ELSE 0 
            END as conversion_rate
        FROM penginapan p
        LEFT JOIN (
            SELECT accommodation_id, SUM(view_count) as view_count
            FROM penginapan_statistics
            WHERE date BETWEEN ? AND ?
            GROUP BY accommodation_id
        ) ps ON p.id = ps.accommodation_id
        LEFT JOIN transaksi_items ti ON ti.item_id = p.id AND ti.item_type = 'penginapan'
        LEFT JOIN transaksi t ON ti.transaksi_id = t.id AND t.payment_status = 'paid'
            AND DATE(t.created_at) BETWEEN ? AND ?
        GROUP BY p.id
        HAVING views > 0 OR purchases > 0
        ORDER BY conversion_rate DESC
        LIMIT 20
    ";
    
    $stmt = $conn->prepare($penginapan_query);
    $stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $penginapan_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'wisata' => $wisata_result,
        'penginapan' => $penginapan_result
    ];
}
?>