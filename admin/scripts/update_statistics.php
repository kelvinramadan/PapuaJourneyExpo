<?php
/**
 * Statistics Aggregation Script
 * 
 * This script aggregates view data from wisata_views table into wisata_statistics table.
 * It should be run daily via cron job to maintain up-to-date statistics.
 * 
 * Usage: php update_statistics.php
 * Cron example: 0 2 * * * php /path/to/admin/scripts/update_statistics.php
 */

// Include database configuration
require_once __DIR__ . '/../../config/database.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Log function
function logMessage($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] [{$type}] {$message}\n";
}

try {
    logMessage("Starting statistics aggregation...");
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get date range for aggregation
    $date = isset($argv[1]) ? $argv[1] : date('Y-m-d'); // Allow passing specific date as argument
    logMessage("Processing statistics for date: {$date}");
    
    // Begin transaction
    $conn->begin_transaction();
    
    // Aggregate view statistics for each wisata
    $query = "
        INSERT INTO wisata_statistics (wisata_id, stat_date, view_count, unique_visitors, booking_count, revenue)
        SELECT 
            w.id as wisata_id,
            ? as stat_date,
            COALESCE(v.view_count, 0) as view_count,
            COALESCE(v.unique_visitors, 0) as unique_visitors,
            COALESCE(b.booking_count, 0) as booking_count,
            COALESCE(b.revenue, 0) as revenue
        FROM wisata w
        LEFT JOIN (
            SELECT 
                wisata_id,
                COUNT(*) as view_count,
                COUNT(DISTINCT session_id) as unique_visitors
            FROM wisata_views
            WHERE DATE(view_date) = ?
            GROUP BY wisata_id
        ) v ON w.id = v.wisata_id
        LEFT JOIN (
            SELECT 
                ti.item_id as wisata_id,
                COUNT(DISTINCT ti.transaksi_id) as booking_count,
                SUM(ti.subtotal) as revenue
            FROM transaksi_items ti
            JOIN transaksi t ON ti.transaksi_id = t.id
            WHERE ti.item_type = 'wisata' 
                AND t.payment_status = 'paid'
                AND DATE(t.created_at) = ?
            GROUP BY ti.item_id
        ) b ON w.id = b.wisata_id
        ON DUPLICATE KEY UPDATE
            view_count = VALUES(view_count),
            unique_visitors = VALUES(unique_visitors),
            booking_count = VALUES(booking_count),
            revenue = VALUES(revenue),
            updated_at = CURRENT_TIMESTAMP
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $date, $date, $date);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        logMessage("Successfully updated statistics for {$affected_rows} destinations");
    } else {
        throw new Exception("Failed to update statistics: " . $stmt->error);
    }
    
    // Clean up old detailed view records (optional - keep last 90 days)
    $cleanup_date = date('Y-m-d', strtotime('-90 days'));
    $cleanup_query = "DELETE FROM wisata_views WHERE DATE(view_date) < ?";
    $cleanup_stmt = $conn->prepare($cleanup_query);
    $cleanup_stmt->bind_param("s", $cleanup_date);
    
    if ($cleanup_stmt->execute()) {
        $deleted_rows = $cleanup_stmt->affected_rows;
        if ($deleted_rows > 0) {
            logMessage("Cleaned up {$deleted_rows} old view records");
        }
    }
    
    // Update destinations with no recent activity
    $inactive_query = "
        UPDATE wisata w
        LEFT JOIN wisata_statistics ws ON w.id = ws.wisata_id 
            AND ws.stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        SET w.updated_at = w.updated_at
        WHERE ws.wisata_id IS NULL OR SUM(ws.view_count) = 0
    ";
    
    // Commit transaction
    $conn->commit();
    logMessage("Statistics aggregation completed successfully!");
    
    // Generate summary report
    $summary_query = "
        SELECT 
            COUNT(DISTINCT wisata_id) as destinations_with_views,
            SUM(view_count) as total_views,
            SUM(unique_visitors) as total_unique_visitors,
            SUM(booking_count) as total_bookings,
            SUM(revenue) as total_revenue
        FROM wisata_statistics
        WHERE stat_date = ?
    ";
    
    $summary_stmt = $conn->prepare($summary_query);
    $summary_stmt->bind_param("s", $date);
    $summary_stmt->execute();
    $summary = $summary_stmt->get_result()->fetch_assoc();
    
    logMessage("=== Daily Summary for {$date} ===");
    logMessage("Destinations with views: " . $summary['destinations_with_views']);
    logMessage("Total views: " . $summary['total_views']);
    logMessage("Unique visitors: " . $summary['total_unique_visitors']);
    logMessage("Total bookings: " . $summary['total_bookings']);
    logMessage("Total revenue: Rp " . number_format($summary['total_revenue'], 0, ',', '.'));
    
    // Close statements
    $stmt->close();
    $cleanup_stmt->close();
    $summary_stmt->close();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    logMessage("Error: " . $e->getMessage(), 'ERROR');
    exit(1);
}

// Send email notification (optional)
if (defined('ADMIN_EMAIL') && ADMIN_EMAIL) {
    $subject = "Daily Tourism Statistics - " . $date;
    $message = "Statistics aggregation completed for {$date}\n\n";
    $message .= "Total views: " . $summary['total_views'] . "\n";
    $message .= "Unique visitors: " . $summary['total_unique_visitors'] . "\n";
    $message .= "Total bookings: " . $summary['total_bookings'] . "\n";
    $message .= "Total revenue: Rp " . number_format($summary['total_revenue'], 0, ',', '.') . "\n";
    
    mail(ADMIN_EMAIL, $subject, $message);
}

logMessage("Script execution completed");
exit(0);