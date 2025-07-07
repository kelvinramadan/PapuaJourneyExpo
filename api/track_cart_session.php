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
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data || !isset($data['action'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }
    
    $session_id = session_id();
    $action = $data['action'];
    
    switch ($action) {
        case 'session_start':
            trackSessionStart($db, $user_id, $session_id, $data);
            break;
            
        case 'activity_update':
            updateSessionActivity($db, $user_id, $session_id, $data);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            exit();
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function trackSessionStart($db, $user_id, $session_id, $data) {
    // Check if session already exists
    $check_stmt = $db->prepare("SELECT id FROM user_cart_sessions WHERE user_id = ? AND session_id = ?");
    $check_stmt->bind_param("is", $user_id, $session_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing session
        $update_stmt = $db->prepare("UPDATE user_cart_sessions SET last_cart_activity = NOW(), is_active = 1 WHERE user_id = ? AND session_id = ?");
        $update_stmt->bind_param("is", $user_id, $session_id);
        $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Create new session
        $pages_visited = json_encode([
            ['page' => $data['page'], 'timestamp' => $data['timestamp'], 'referrer' => $data['referrer'] ?? null]
        ]);
        
        $insert_stmt = $db->prepare("INSERT INTO user_cart_sessions (user_id, session_id, pages_visited) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iss", $user_id, $session_id, $pages_visited);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    
    $check_stmt->close();
    echo json_encode(['success' => true, 'message' => 'Session tracked']);
}

function updateSessionActivity($db, $user_id, $session_id, $data) {
    $stmt = $db->prepare("UPDATE user_cart_sessions SET last_cart_activity = NOW() WHERE user_id = ? AND session_id = ?");
    $stmt->bind_param("is", $user_id, $session_id);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Activity updated']);
}
?>