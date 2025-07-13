<?php
// admin/api/get-summary-data.php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../helpers/SummaryCalculator.php';

header('Content-Type: application/json');

try {
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    $calculator = new SummaryCalculator();
    $summary = $calculator->getComprehensiveSummary();
    
    // Format the response for better frontend consumption
    $response = [
        'success' => true,
        'data' => [
            'trend_analysis' => $summary['trend_analysis'],
            'performance_highlights' => $summary['performance_highlights'],
            'user_engagement' => $summary['user_engagement'],
            'revenue_insights' => $summary['revenue_insights'],
            'alert_indicators' => $summary['alert_indicators'],
            'generated_at' => date('Y-m-d H:i:s')
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Gagal mengambil data summary: ' . $e->getMessage()
    ]);
}