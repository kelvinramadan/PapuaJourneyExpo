<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

header('Content-Type: application/json');

// Include the reminder script
require_once 'abandoned_cart_reminder.php';

try {
    $reminder = new AbandonedCartEmailReminder();
    $sent_count = $reminder->sendReminders();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully sent {$sent_count} reminder emails",
        'sent_count' => $sent_count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error sending reminders: ' . $e->getMessage()
    ]);
}
?>