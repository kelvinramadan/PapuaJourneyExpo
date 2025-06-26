<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['penginapan_id']) || !is_numeric($data['penginapan_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid accommodation ID']);
    exit;
}

$penginapan_id = intval($data['penginapan_id']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$session_id = session_id();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if penginapan exists
    $check_penginapan = $conn->prepare("SELECT id FROM penginapan WHERE id = ?");
    $check_penginapan->bind_param("i", $penginapan_id);
    $check_penginapan->execute();
    if ($check_penginapan->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Accommodation not found']);
        exit;
    }
    
    // Check if this session has already viewed this penginapan in the last hour
    $check_recent = $conn->prepare("
        SELECT id FROM penginapan_views 
        WHERE penginapan_id = ? 
        AND session_id = ? 
        AND view_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $check_recent->bind_param("is", $penginapan_id, $session_id);
    $check_recent->execute();
    
    if ($check_recent->get_result()->num_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'View already recorded recently']);
        exit;
    }
    
    // Record the view
    $insert_view = $conn->prepare("
        INSERT INTO penginapan_views (penginapan_id, user_id, ip_address, session_id, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $insert_view->bind_param("iisss", $penginapan_id, $user_id, $ip_address, $session_id, $user_agent);
    
    if ($insert_view->execute()) {
        // Update today's statistics
        $update_stats = $conn->prepare("
            INSERT INTO penginapan_statistics (penginapan_id, stat_date, view_count, unique_visitors)
            VALUES (?, CURDATE(), 1, 1)
            ON DUPLICATE KEY UPDATE 
                view_count = view_count + 1,
                unique_visitors = (
                    SELECT COUNT(DISTINCT session_id) 
                    FROM penginapan_views 
                    WHERE penginapan_id = ? 
                    AND DATE(view_date) = CURDATE()
                )
        ");
        $update_stats->bind_param("ii", $penginapan_id, $penginapan_id);
        $update_stats->execute();
        
        echo json_encode(['success' => true, 'message' => 'View recorded successfully']);
    } else {
        throw new Exception('Failed to record view');
    }
    
} catch (Exception $e) {
    error_log('Accommodation view tracking error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}