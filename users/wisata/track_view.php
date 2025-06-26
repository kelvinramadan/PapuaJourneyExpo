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

if (!isset($data['wisata_id']) || !is_numeric($data['wisata_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid wisata ID']);
    exit;
}

$wisata_id = intval($data['wisata_id']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$ip_address = $_SERVER['REMOTE_ADDR'];
$session_id = session_id();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if wisata exists
    $check_wisata = $conn->prepare("SELECT id FROM wisata WHERE id = ?");
    $check_wisata->bind_param("i", $wisata_id);
    $check_wisata->execute();
    if ($check_wisata->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tourist destination not found']);
        exit;
    }
    
    // Check if this session has already viewed this wisata in the last hour
    $check_recent = $conn->prepare("
        SELECT id FROM wisata_views 
        WHERE wisata_id = ? 
        AND session_id = ? 
        AND view_date > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $check_recent->bind_param("is", $wisata_id, $session_id);
    $check_recent->execute();
    
    if ($check_recent->get_result()->num_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'View already recorded recently']);
        exit;
    }
    
    // Record the view
    $insert_view = $conn->prepare("
        INSERT INTO wisata_views (wisata_id, user_id, ip_address, session_id, user_agent) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $insert_view->bind_param("iisss", $wisata_id, $user_id, $ip_address, $session_id, $user_agent);
    
    if ($insert_view->execute()) {
        // Update today's statistics
        $update_stats = $conn->prepare("
            INSERT INTO wisata_statistics (wisata_id, stat_date, view_count, unique_visitors)
            VALUES (?, CURDATE(), 1, 1)
            ON DUPLICATE KEY UPDATE 
                view_count = view_count + 1,
                unique_visitors = (
                    SELECT COUNT(DISTINCT session_id) 
                    FROM wisata_views 
                    WHERE wisata_id = ? 
                    AND DATE(view_date) = CURDATE()
                )
        ");
        $update_stats->bind_param("ii", $wisata_id, $wisata_id);
        $update_stats->execute();
        
        echo json_encode(['success' => true, 'message' => 'View recorded successfully']);
    } else {
        throw new Exception('Failed to record view');
    }
    
} catch (Exception $e) {
    error_log('View tracking error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}