<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

require_once '../../config/database.php';

$db = getDbConnection();
$user_id = $_SESSION['user_id'];

if (isset($_POST['update_quantity']) && isset($_POST['cart_id']) && isset($_POST['quantity'])) {
    $cart_id = (int)$_POST['cart_id'];
    $quantity = max(1, (int)$_POST['quantity']);
    
    // Get item details to recalculate subtotal
    $stmt = $db->prepare("SELECT price_per_unit FROM cart_items WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();
    
    if ($item) {
        $subtotal = $item['price_per_unit'] * $quantity;
        
        // Update quantity and subtotal
        $stmt = $db->prepare("UPDATE cart_items SET quantity = ?, subtotal = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("idii", $quantity, $subtotal, $cart_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'subtotal' => $subtotal,
                'quantity' => $quantity
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$db->close();