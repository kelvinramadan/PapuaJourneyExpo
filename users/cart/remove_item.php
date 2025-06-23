<?php
session_start();

// Set header for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

require_once '../../config/database.php';

$db = getDbConnection();
$user_id = $_SESSION['user_id'];

// Check if cart_id is provided
if (!isset($_POST['cart_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID item tidak valid']);
    exit();
}

$cart_id = (int)$_POST['cart_id'];

// Delete item from cart
$stmt = $db->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $cart_id, $user_id);

if ($stmt->execute()) {
    // Get updated cart count
    $count_stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $result = $count_stmt->get_result();
    $cart_count = $result->fetch_assoc()['count'];
    $count_stmt->close();
    
    // Calculate new total
    $total_stmt = $db->prepare("SELECT SUM(subtotal) as total FROM cart_items WHERE user_id = ?");
    $total_stmt->bind_param("i", $user_id);
    $total_stmt->execute();
    $result = $total_stmt->get_result();
    $total = $result->fetch_assoc()['total'] ?? 0;
    $total_stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Item berhasil dihapus dari keranjang',
        'cart_count' => $cart_count,
        'cart_total' => $total,
        'is_empty' => $cart_count == 0
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus item']);
}

$stmt->close();
$db->close();
?>