<?php
// api/payment_callback.php
// Payment callback endpoint for automated payment gateway integration

require_once '../config/database.php';

// Set headers
header('Content-Type: application/json');

// Log all incoming requests for debugging
$log_file = '../logs/payment_callbacks.log';
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'body' => file_get_contents('php://input'),
    'ip' => $_SERVER['REMOTE_ADDR']
];

// Create logs directory if it doesn't exist
if (!file_exists(dirname($log_file))) {
    mkdir(dirname($log_file), 0777, true);
}

// Log the request
file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND);

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON payload
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = ['transaction_code', 'status', 'payment_method', 'amount', 'signature'];
foreach ($required_fields as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

// Verify signature (implement your payment gateway's signature verification)
// For now, we'll use a simple shared secret approach
$secret_key = 'your_payment_gateway_secret_key'; // Should be stored in environment variable
$expected_signature = hash_hmac('sha256', $input['transaction_code'] . $input['amount'], $secret_key);

if ($input['signature'] !== $expected_signature) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

$db = getDbConnection();

try {
    // Start transaction
    $db->begin_transaction();
    
    // Find the transaction
    $trans_query = "SELECT id, total_amount, payment_status FROM transaksi WHERE transaction_code = ?";
    $trans_stmt = $db->prepare($trans_query);
    $trans_stmt->bind_param("s", $input['transaction_code']);
    $trans_stmt->execute();
    $trans_result = $trans_stmt->get_result();
    
    if ($trans_result->num_rows === 0) {
        throw new Exception('Transaction not found');
    }
    
    $transaction = $trans_result->fetch_assoc();
    $trans_stmt->close();
    
    // Verify amount matches
    if (abs($transaction['total_amount'] - $input['amount']) > 0.01) {
        throw new Exception('Amount mismatch');
    }
    
    // Update transaction status based on payment status
    if ($input['status'] === 'success') {
        $new_status = 'paid';
        $update_query = "UPDATE transaksi SET 
                        payment_status = ?, 
                        payment_method = ?,
                        payment_date = NOW(),
                        payment_confirmed_at = NOW(),
                        payment_confirmed_by = 0
                        WHERE id = ?";
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bind_param("ssi", $new_status, $input['payment_method'], $transaction['id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Log the automatic confirmation
        $log_stmt = $db->prepare("INSERT INTO admin_payment_logs (admin_id, transaksi_id, action, notes) VALUES (0, ?, 'confirmed', 'Automatic confirmation via payment gateway')");
        $log_stmt->bind_param("i", $transaction['id']);
        $log_stmt->execute();
        $log_stmt->close();
        
        // Create notifications for UMKM
        $umkm_query = "SELECT DISTINCT a.umkm_id, a.judul, ti.quantity, ti.subtotal
                      FROM transaksi_items ti
                      JOIN artikel a ON ti.item_id = a.id AND ti.item_type = 'artikel'
                      WHERE ti.transaksi_id = ?";
        
        $umkm_stmt = $db->prepare($umkm_query);
        $umkm_stmt->bind_param("i", $transaction['id']);
        $umkm_stmt->execute();
        $umkm_result = $umkm_stmt->get_result();
        
        while ($umkm = $umkm_result->fetch_assoc()) {
            $notif_title = "Pembayaran Otomatis Dikonfirmasi!";
            $notif_message = "Pesanan untuk '{$umkm['judul']}' (Qty: {$umkm['quantity']}, Total: Rp " . number_format($umkm['subtotal']) . ") telah dikonfirmasi secara otomatis. Kode transaksi: {$input['transaction_code']}";
            
            $notif_stmt = $db->prepare("INSERT INTO umkm_notifications (umkm_id, type, title, message, transaction_code) VALUES (?, 'payment_confirmed', ?, ?, ?)");
            $notif_stmt->bind_param("isss", $umkm['umkm_id'], $notif_title, $notif_message, $input['transaction_code']);
            $notif_stmt->execute();
            $notif_stmt->close();
        }
        $umkm_stmt->close();
        
    } else if ($input['status'] === 'failed') {
        $new_status = 'rejected';
        $update_query = "UPDATE transaksi SET payment_status = ? WHERE id = ?";
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $transaction['id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Log the automatic rejection
        $log_stmt = $db->prepare("INSERT INTO admin_payment_logs (admin_id, transaksi_id, action, notes) VALUES (0, ?, 'rejected', 'Automatic rejection: Payment failed at gateway')");
        $log_stmt->bind_param("i", $transaction['id']);
        $log_stmt->execute();
        $log_stmt->close();
    }
    
    $db->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Payment status updated successfully',
        'transaction_code' => $input['transaction_code']
    ]);
    
} catch (Exception $e) {
    $db->rollback();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

$db->close();
?>