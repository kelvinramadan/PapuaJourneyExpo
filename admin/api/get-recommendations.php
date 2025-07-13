<?php
// admin/api/get-recommendations.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../helpers/RecommendationEngine.php';

header('Content-Type: application/json');

try {
    $engine = new RecommendationEngine();
    $recommendations = $engine->generateRecommendations();
    
    // Format the response
    $response = [
        'success' => true,
        'data' => [
            'recommendations' => $recommendations,
            'total_count' => count($recommendations),
            'high_priority_count' => count(array_filter($recommendations, function($r) { 
                return $r['priority'] === 'high'; 
            })),
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Gagal mengambil rekomendasi: ' . $e->getMessage()
    ]);
}