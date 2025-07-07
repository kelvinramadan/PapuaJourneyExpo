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
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    // Get current cart items to create snapshot
    $cart_snapshot = getCurrentCartSnapshot($db, $user_id);
    
    if (empty($cart_snapshot['items'])) {
        echo json_encode(['success' => false, 'message' => 'No items in cart to abandon']);
        exit();
    }
    
    // Record abandonment
    $abandonment_id = recordAbandonment($db, $user_id, $session_id, $data, $cart_snapshot);
    
    // Record abandonment reason if provided
    if (isset($data['reason']) && $data['reason']) {
        recordAbandonmentReason($db, $abandonment_id, $data['reason']);
    }
    
    // Mark session as abandoned
    markSessionAbandoned($db, $user_id, $session_id);
    
    echo json_encode(['success' => true, 'message' => 'Abandonment tracked', 'abandonment_id' => $abandonment_id]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function getCurrentCartSnapshot($db, $user_id) {
    $query = "
        SELECT 
            c.*,
            CASE 
                WHEN c.item_type = 'wisata' THEN w.judul
                WHEN c.item_type = 'penginapan' THEN p.judul
                WHEN c.item_type = 'artikel' THEN a.judul
            END as item_name,
            CASE 
                WHEN c.item_type = 'wisata' THEN w.kategori
                WHEN c.item_type = 'penginapan' THEN 'penginapan'
                WHEN c.item_type = 'artikel' THEN a.kategori
            END as item_category
        FROM cart_items c
        LEFT JOIN wisata w ON c.item_type = 'wisata' AND c.item_id = w.id
        LEFT JOIN penginapan p ON c.item_type = 'penginapan' AND c.item_id = p.id
        LEFT JOIN artikel a ON c.item_type = 'artikel' AND c.item_id = a.id
        WHERE c.user_id = ?
        ORDER BY c.added_at DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $total_value = 0;
    $item_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'cart_id' => $row['id'],
            'item_type' => $row['item_type'],
            'item_id' => $row['item_id'],
            'item_name' => $row['item_name'],
            'item_category' => $row['item_category'],
            'quantity' => $row['quantity'],
            'price_per_unit' => $row['price_per_unit'],
            'subtotal' => $row['subtotal'],
            'added_at' => $row['added_at']
        ];
        
        $total_value += $row['subtotal'];
        $item_count += $row['quantity'];
    }
    
    $stmt->close();
    
    return [
        'items' => $items,
        'total_value' => $total_value,
        'item_count' => $item_count
    ];
}

function recordAbandonment($db, $user_id, $session_id, $data, $cart_snapshot) {
    // Get session info
    $session_stmt = $db->prepare("SELECT first_cart_activity FROM user_cart_sessions WHERE user_id = ? AND session_id = ?");
    $session_stmt->bind_param("is", $user_id, $session_id);
    $session_stmt->execute();
    $session_result = $session_stmt->get_result();
    $session_data = $session_result->fetch_assoc();
    $session_stmt->close();
    
    $session_start = $session_data['first_cart_activity'] ?? null;
    $session_duration = isset($data['session_duration']) ? round($data['session_duration'] / (1000 * 60)) : null; // Convert to minutes
    
    $cart_items_json = json_encode($cart_snapshot['items']);
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $page_before = $data['page'] ?? '';
    
    $stmt = $db->prepare("
        INSERT INTO abandoned_carts 
        (user_id, session_id, cart_items_snapshot, total_value, item_count, session_start_time, session_duration_minutes, page_before_abandonment, user_agent, ip_address) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("issddiisss", 
        $user_id, 
        $session_id, 
        $cart_items_json, 
        $cart_snapshot['total_value'], 
        $cart_snapshot['item_count'],
        $session_start,
        $session_duration,
        $page_before,
        $user_agent,
        $ip_address
    );
    
    $stmt->execute();
    $abandonment_id = $db->insert_id;
    $stmt->close();
    
    return $abandonment_id;
}

function recordAbandonmentReason($db, $abandonment_id, $reason) {
    $reason_code = $reason['code'] ?? 'other';
    $reason_text = $reason['text'] ?? null;
    
    $stmt = $db->prepare("INSERT INTO cart_abandonment_reasons (abandoned_cart_id, reason_code, reason_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $abandonment_id, $reason_code, $reason_text);
    $stmt->execute();
    $stmt->close();
}

function markSessionAbandoned($db, $user_id, $session_id) {
    $stmt = $db->prepare("UPDATE user_cart_sessions SET is_active = 0 WHERE user_id = ? AND session_id = ?");
    $stmt->bind_param("is", $user_id, $session_id);
    $stmt->execute();
    $stmt->close();
}
?>