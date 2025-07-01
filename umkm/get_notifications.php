<?php
// umkm/get_notifications.php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is UMKM
if (!isset($_SESSION['umkm_id']) || $_SESSION['user_type'] != 'umkm') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$umkm_id = $_SESSION['umkm_id'];
$db = getDbConnection();

// Get action from request
$action = $_GET['action'] ?? 'get';

if ($action === 'get') {
    // Get unread notifications count and recent notifications
    $count_query = "SELECT COUNT(*) as unread_count FROM umkm_notifications WHERE umkm_id = ? AND is_read = 0";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bind_param("i", $umkm_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $unread_count = $count_result->fetch_assoc()['unread_count'];
    $count_stmt->close();
    
    // Get recent notifications
    $notif_query = "SELECT * FROM umkm_notifications WHERE umkm_id = ? ORDER BY created_at DESC LIMIT 10";
    $notif_stmt = $db->prepare($notif_query);
    $notif_stmt->bind_param("i", $umkm_id);
    $notif_stmt->execute();
    $notif_result = $notif_stmt->get_result();
    $notifications = $notif_result->fetch_all(MYSQLI_ASSOC);
    $notif_stmt->close();
    
    echo json_encode([
        'unread_count' => $unread_count,
        'notifications' => $notifications
    ]);
    
} else if ($action === 'mark_read' && isset($_GET['id'])) {
    // Mark notification as read
    $notif_id = (int)$_GET['id'];
    
    $update_query = "UPDATE umkm_notifications SET is_read = 1 WHERE id = ? AND umkm_id = ?";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bind_param("ii", $notif_id, $umkm_id);
    $success = $update_stmt->execute();
    $update_stmt->close();
    
    echo json_encode(['success' => $success]);
    
} else if ($action === 'mark_all_read') {
    // Mark all notifications as read
    $update_query = "UPDATE umkm_notifications SET is_read = 1 WHERE umkm_id = ? AND is_read = 0";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bind_param("i", $umkm_id);
    $success = $update_stmt->execute();
    $update_stmt->close();
    
    echo json_encode(['success' => $success]);
}

$db->close();
?>