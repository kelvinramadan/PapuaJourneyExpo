<?php
// Set error reporting to catch all errors
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors as HTML

// Custom error handler to return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $errstr,
        'debug' => [
            'file' => basename($errfile),
            'line' => $errline
        ]
    ]);
    exit;
});

session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Get parameters
$item_type = isset($_GET['item_type']) ? $_GET['item_type'] : '';
$item_id = isset($_GET['item_id']) ? intval($_GET['item_id']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = isset($_GET['per_page']) ? min(50, max(1, intval($_GET['per_page']))) : 10;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'newest';
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Validate parameters
if (!$item_type || !$item_id) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
    exit;
}

if (!in_array($item_type, ['wisata', 'penginapan', 'artikel'])) {
    echo json_encode(['success' => false, 'message' => 'Tipe item tidak valid']);
    exit;
}

$db = getDbConnection();

try {
    // Check if review tables exist
    $tables_check = $db->query("SHOW TABLES LIKE 'reviews'");
    if ($tables_check->num_rows == 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Review system not installed. Please run database migration.',
            'help' => 'Run: mysql -u root -p omaki_db < database_updates/add_review_system.sql'
        ]);
        exit;
    }
    // Get total review count and summary
    $where_clause = "item_type = ? AND item_id = ? AND is_visible = 1";
    $params = [$item_type, $item_id];
    $types = "si";
    
    if ($rating_filter > 0 && $rating_filter <= 5) {
        $where_clause .= " AND rating = ?";
        $params[] = $rating_filter;
        $types .= "i";
    }
    
    // Get review summary from cache
    $stmt = $db->prepare("
        SELECT total_reviews, average_rating, rating_1_count, rating_2_count, 
               rating_3_count, rating_4_count, rating_5_count
        FROM review_summary_cache
        WHERE item_type = ? AND item_id = ?
    ");
    $stmt->bind_param("si", $item_type, $item_id);
    $stmt->execute();
    $summary_result = $stmt->get_result();
    $summary = $summary_result->fetch_assoc();
    
    if (!$summary) {
        // If no cache, calculate on the fly
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1_count,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2_count,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3_count,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4_count,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5_count
            FROM reviews
            WHERE item_type = ? AND item_id = ? AND is_visible = 1
        ");
        $stmt->bind_param("si", $item_type, $item_id);
        $stmt->execute();
        $summary_result = $stmt->get_result();
        $summary = $summary_result->fetch_assoc();
    }
    
    // Get total count for pagination (with filter if applied)
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM reviews WHERE $where_clause");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $count_result = $stmt->get_result();
    $total_filtered = $count_result->fetch_assoc()['total'];
    
    // Calculate pagination
    $total_pages = ceil($total_filtered / $per_page);
    $offset = ($page - 1) * $per_page;
    
    // Determine sort order
    $order_by = "r.created_at DESC"; // Default: newest
    switch ($sort_by) {
        case 'oldest':
            $order_by = "r.created_at ASC";
            break;
        case 'highest':
            $order_by = "r.rating DESC, r.created_at DESC";
            break;
        case 'lowest':
            $order_by = "r.rating ASC, r.created_at DESC";
            break;
        case 'helpful':
            $order_by = "helpful_count DESC, r.created_at DESC";
            break;
    }
    
    // Get reviews with user info and media
    $current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    
    $query = "
        SELECT 
            r.id,
            r.user_id,
            r.rating,
            r.review_text,
            r.is_verified,
            r.created_at,
            u.full_name as user_name,
            u.profile_image as user_avatar,
            (SELECT COUNT(*) FROM review_helpfulness WHERE review_id = r.id AND is_helpful = 1) as helpful_count,
            (SELECT COUNT(*) FROM review_helpfulness WHERE review_id = r.id AND is_helpful = 0) as not_helpful_count,
            (SELECT is_helpful FROM review_helpfulness WHERE review_id = r.id AND user_id = ?) as user_vote
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE $where_clause
        ORDER BY $order_by
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $db->prepare($query);
    $all_params = array_merge([$current_user_id], $params, [$per_page, $offset]);
    $types_with_pagination = "i" . $types . "ii";
    $stmt->bind_param($types_with_pagination, ...$all_params);
    $stmt->execute();
    $reviews_result = $stmt->get_result();
    
    $reviews = [];
    while ($review = $reviews_result->fetch_assoc()) {
        // Get media for this review
        $stmt = $db->prepare("
            SELECT id, media_type, file_path, upload_order
            FROM review_media
            WHERE review_id = ?
            ORDER BY upload_order ASC
        ");
        $stmt->bind_param("i", $review['id']);
        $stmt->execute();
        $media_result = $stmt->get_result();
        
        $media = [];
        while ($media_item = $media_result->fetch_assoc()) {
            $media[] = [
                'id' => $media_item['id'],
                'type' => $media_item['media_type'],
                'url' => '/' . $media_item['file_path'],
                'order' => $media_item['upload_order']
            ];
        }
        
        // Format review data
        $reviews[] = [
            'id' => $review['id'],
            'user' => [
                'id' => $review['user_id'],
                'name' => $review['user_name'],
                'avatar' => $review['user_avatar'] ? '/uploads/profile_images/' . $review['user_avatar'] : '/assets/images/default-avatar.png'
            ],
            'rating' => intval($review['rating']),
            'text' => $review['review_text'],
            'is_verified' => (bool)$review['is_verified'],
            'created_at' => $review['created_at'],
            'formatted_date' => date('d M Y', strtotime($review['created_at'])),
            'media' => $media,
            'helpful_count' => intval($review['helpful_count']),
            'not_helpful_count' => intval($review['not_helpful_count']),
            'user_vote' => $review['user_vote']
        ];
    }
    
    // Format summary data
    $rating_distribution = [
        5 => intval($summary['rating_5_count'] ?? 0),
        4 => intval($summary['rating_4_count'] ?? 0),
        3 => intval($summary['rating_3_count'] ?? 0),
        2 => intval($summary['rating_2_count'] ?? 0),
        1 => intval($summary['rating_1_count'] ?? 0)
    ];
    
    // Calculate percentage for each rating
    $total_reviews = intval($summary['total_reviews'] ?? 0);
    $rating_percentages = [];
    foreach ($rating_distribution as $rating => $count) {
        $rating_percentages[$rating] = $total_reviews > 0 ? round(($count / $total_reviews) * 100) : 0;
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'summary' => [
            'total_reviews' => $total_reviews,
            'average_rating' => round(floatval($summary['average_rating'] ?? 0), 1),
            'rating_distribution' => $rating_distribution,
            'rating_percentages' => $rating_percentages
        ],
        'reviews' => $reviews,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'per_page' => $per_page,
            'total_filtered' => $total_filtered,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // In development, show detailed error
    if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage(),
            'error_details' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat memuat review']);
    }
} finally {
    $db->close();
}
?>