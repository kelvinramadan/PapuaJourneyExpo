<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['selected_items'])) {
    header('Location: ../cart/cart.php');
    exit();
}

require_once '../../config/database.php';

$db = getDbConnection();
$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $db->autocommit(FALSE);
    
    // Get form data
    $selected_items = $_POST['selected_items'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $notes = $_POST['notes'] ?? '';
    $payment_method = $_POST['payment_method'];
    
    // Generate transaction code
    $transaction_code = 'TRX' . date('YmdHis') . $user_id;
    
    // Get selected cart items
    $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
    $types = str_repeat('i', count($selected_items) + 1);
    $params = array_merge([$user_id], $selected_items);
    
    $query = "
        SELECT c.*, 
            CASE 
                WHEN c.item_type = 'wisata' THEN w.judul
                WHEN c.item_type = 'penginapan' THEN p.judul
                WHEN c.item_type = 'artikel' THEN a.judul
            END as item_name
        FROM cart_items c
        LEFT JOIN wisata w ON c.item_type = 'wisata' AND c.item_id = w.id
        LEFT JOIN penginapan p ON c.item_type = 'penginapan' AND c.item_id = p.id
        LEFT JOIN artikel a ON c.item_type = 'artikel' AND c.item_id = a.id
        WHERE c.user_id = ? AND c.id IN ($placeholders)
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_items = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Calculate total
    $total_amount = 0;
    foreach ($cart_items as $item) {
        $total_amount += $item['subtotal'];
    }
    
    // Insert transaction record
    $stmt = $db->prepare("INSERT INTO transaksi (user_id, transaction_code, total_amount, payment_status, payment_method) VALUES (?, ?, ?, 'pending', ?)");
    $stmt->bind_param("isds", $user_id, $transaction_code, $total_amount, $payment_method);
    $stmt->execute();
    $transaksi_id = $stmt->insert_id;
    $stmt->close();
    
    // Insert transaction items
    foreach ($cart_items as $item) {
        $stmt = $db->prepare("INSERT INTO transaksi_items (transaksi_id, item_type, item_id, item_name, quantity, price_per_unit, subtotal, booking_date, checkin_date, checkout_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isisiddssss", 
            $transaksi_id, 
            $item['item_type'], 
            $item['item_id'], 
            $item['item_name'], 
            $item['quantity'], 
            $item['price_per_unit'], 
            $item['subtotal'],
            $item['booking_date'],
            $item['checkin_date'],
            $item['checkout_date'],
            $item['notes']
        );
        $stmt->execute();
        $stmt->close();
    }
    
    // Delete items from cart
    $delete_query = "DELETE FROM cart_items WHERE user_id = ? AND id IN ($placeholders)";
    $stmt = $db->prepare($delete_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
    
    // Commit transaction
    $db->commit();
    
    // Set payment info in session
    $_SESSION['checkout_success'] = true;
    $_SESSION['transaction_code'] = $transaction_code;
    $_SESSION['total_amount'] = $total_amount;
    $_SESSION['payment_method'] = $payment_method;
    $_SESSION['transaction_id'] = $transaksi_id;
    
    // Redirect to payment instructions page
    header('Location: payment_instructions.php');
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollback();
    $_SESSION['error_message'] = 'Terjadi kesalahan saat memproses pembayaran. Silakan coba lagi.';
    header('Location: ../cart/cart.php');
    exit();
} finally {
    $db->close();
}