<?php
// reviews_handler.php
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Please login to submit a review']);
        exit();
    }
    
    $user_id = $_SESSION['user_id'];
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
    
    // Validation
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
        exit();
    }
    
    if (empty($review_text) || strlen($review_text) < 10) {
        echo json_encode(['success' => false, 'message' => 'Review text must be at least 10 characters long']);
        exit();
    }
    
    if (strlen($review_text) > 1000) {
        echo json_encode(['success' => false, 'message' => 'Review text cannot exceed 1000 characters']);
        exit();
    }
    
    try {
        $db = getDbConnection();
        
        // Check if user already submitted a review
        $check_stmt = $db->prepare("SELECT id FROM reviews WHERE user_id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $existing_review = $check_stmt->get_result();
        
        if ($existing_review->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'You have already submitted a review']);
            exit();
        }
        
        // Insert new review
        $stmt = $db->prepare("INSERT INTO reviews (user_id, rating, review_text) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $rating, $review_text);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Thank you for your review! It will be published after approval.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
        }
        
        $stmt->close();
        $check_stmt->close();
        $db->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'An error occurred while submitting your review']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>