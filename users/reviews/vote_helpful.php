<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate input
$user_id = $_SESSION['user_id'];
$review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
$is_helpful = isset($_POST['is_helpful']) ? $_POST['is_helpful'] : null;

// Validate parameters
if (!$review_id) {
    echo json_encode(['success' => false, 'message' => 'Review ID tidak valid']);
    exit;
}

if ($is_helpful === null || !in_array($is_helpful, ['true', 'false', '1', '0'])) {
    echo json_encode(['success' => false, 'message' => 'Vote tidak valid']);
    exit;
}

// Convert to boolean
$is_helpful = in_array($is_helpful, ['true', '1']) ? 1 : 0;

$db = getDbConnection();

try {
    // Check if review exists
    $stmt = $db->prepare("SELECT id, user_id FROM reviews WHERE id = ? AND is_visible = 1");
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Review tidak ditemukan']);
        exit;
    }
    
    $review = $result->fetch_assoc();
    
    // Check if user is trying to vote on their own review
    if ($review['user_id'] == $user_id) {
        echo json_encode(['success' => false, 'message' => 'Anda tidak dapat memberikan vote pada review sendiri']);
        exit;
    }
    
    // Check if user has already voted
    $stmt = $db->prepare("SELECT id, is_helpful FROM review_helpfulness WHERE review_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $review_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing vote
        $existing_vote = $result->fetch_assoc();
        
        if ($existing_vote['is_helpful'] == $is_helpful) {
            // Same vote, remove it (toggle off)
            $stmt = $db->prepare("DELETE FROM review_helpfulness WHERE review_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $review_id, $user_id);
            $action = 'removed';
        } else {
            // Different vote, update it
            $stmt = $db->prepare("UPDATE review_helpfulness SET is_helpful = ? WHERE review_id = ? AND user_id = ?");
            $stmt->bind_param("iii", $is_helpful, $review_id, $user_id);
            $action = 'updated';
        }
    } else {
        // Insert new vote
        $stmt = $db->prepare("INSERT INTO review_helpfulness (review_id, user_id, is_helpful) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $review_id, $user_id, $is_helpful);
        $action = 'added';
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal menyimpan vote');
    }
    
    // Get updated counts
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN is_helpful = 1 THEN 1 ELSE 0 END) as helpful_count,
            SUM(CASE WHEN is_helpful = 0 THEN 1 ELSE 0 END) as not_helpful_count
        FROM review_helpfulness
        WHERE review_id = ?
    ");
    $stmt->bind_param("i", $review_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $counts = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Vote berhasil disimpan',
        'action' => $action,
        'helpful_count' => intval($counts['helpful_count'] ?? 0),
        'not_helpful_count' => intval($counts['not_helpful_count'] ?? 0)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
} finally {
    $db->close();
}
?>