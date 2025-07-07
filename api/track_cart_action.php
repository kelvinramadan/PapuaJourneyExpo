<?php
session_start();

// Set content type for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

require_once '../../config/database.php';

try {
    $db = getDbConnection();
    $user_id = $_SESSION['user_id'];
    $session_id = session_id();
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['action'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    // Update session with cart action
    updateCartActions($db, $user_id, $session_id, $data);
    
    echo json_encode(['success' => true, 'message' => 'Cart action tracked']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function updateCartActions($db, $user_id, $session_id, $data) {
    // Get current cart actions from session
    $stmt = $db->prepare("SELECT cart_actions FROM user_cart_sessions WHERE user_id = ? AND session_id = ?");
    $stmt->bind_param("is", $user_id, $session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $current_actions = json_decode($row['cart_actions'] ?? '[]', true);
        $current_actions[] = $data;
        
        // Keep only last 50 actions to prevent data bloat
        if (count($current_actions) > 50) {
            $current_actions = array_slice($current_actions, -50);
        }
        
        $new_actions = json_encode($current_actions);
        
        // Update session
        $update_stmt = $db->prepare("UPDATE user_cart_sessions SET cart_actions = ?, last_cart_activity = NOW() WHERE user_id = ? AND session_id = ?");
        $update_stmt->bind_param("sis", $new_actions, $user_id, $session_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Special handling for checkout attempts
        if ($data['action'] === 'checkout_attempted') {
            $checkout_stmt = $db->prepare("UPDATE user_cart_sessions SET checkout_started = 1 WHERE user_id = ? AND session_id = ?");
            $checkout_stmt->bind_param("is", $user_id, $session_id);
            $checkout_stmt->execute();
            $checkout_stmt->close();
        }
    }
    
    $stmt->close();
}
?>