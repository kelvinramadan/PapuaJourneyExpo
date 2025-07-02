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
    
    logMessage("=== Daily Summary for Tourism Destinations - {$date} ===");
    logMessage("Destinations with views: " . $summary['destinations_with_views']);
    logMessage("Total views: " . $summary['total_views']);
    logMessage("Unique visitors: " . $summary['total_unique_visitors']);
    logMessage("Total bookings: " . $summary['total_bookings']);
    logMessage("Total revenue: Rp " . number_format($summary['total_revenue'], 0, ',', '.'));
    
    // Aggregate accommodation statistics
    logMessage("\nProcessing accommodation statistics...");
    
    $acc_query = "
        INSERT INTO penginapan_statistics (penginapan_id, stat_date, view_count, unique_visitors, booking_count, revenue)
        SELECT 
            p.id as penginapan_id,
            ? as stat_date,
            COALESCE(v.view_count, 0) as view_count,
            COALESCE(v.unique_visitors, 0) as unique_visitors,
            COALESCE(b.booking_count, 0) as booking_count,
            COALESCE(b.revenue, 0) as revenue
        FROM penginapan p
        LEFT JOIN (
            SELECT 
                penginapan_id,
                COUNT(*) as view_count,
                COUNT(DISTINCT session_id) as unique_visitors
            FROM penginapan_views
            WHERE DATE(view_date) = ?
            GROUP BY penginapan_id
        ) v ON p.id = v.penginapan_id
        LEFT JOIN (
            SELECT 
                ti.item_id as penginapan_id,
                COUNT(DISTINCT ti.transaksi_id) as booking_count,
                SUM(ti.subtotal) as revenue
            FROM transaksi_items ti
            JOIN transaksi t ON ti.transaksi_id = t.id
            WHERE ti.item_type = 'penginapan' 
                AND t.payment_status = 'paid'
                AND DATE(t.created_at) = ?
            GROUP BY ti.item_id
        ) b ON p.id = b.penginapan_id
        ON DUPLICATE KEY UPDATE
            view_count = VALUES(view_count),
            unique_visitors = VALUES(unique_visitors),
            booking_count = VALUES(booking_count),
            revenue = VALUES(revenue),
            updated_at = CURRENT_TIMESTAMP
    ";
    
    $acc_stmt = $conn->prepare($acc_query);
    $acc_stmt->bind_param("sss", $date, $date, $date);
    
    if ($acc_stmt->execute()) {
        $acc_affected = $acc_stmt->affected_rows;
        logMessage("Successfully updated statistics for {$acc_affected} accommodations");
    } else {
        logMessage("Failed to update accommodation statistics: " . $acc_stmt->error, 'ERROR');
    }
    
    // Get accommodation summary
    $acc_summary_query = "
        SELECT 
            COUNT(DISTINCT penginapan_id) as accommodations_with_views,
            SUM(view_count) as total_views,
            SUM(unique_visitors) as total_unique_visitors,
            SUM(booking_count) as total_bookings,
            SUM(revenue) as total_revenue
        FROM penginapan_statistics
        WHERE stat_date = ?
    ";
    
    $acc_summary_stmt = $conn->prepare($acc_summary_query);
    $acc_summary_stmt->bind_param("s", $date);
    $acc_summary_stmt->execute();
    $acc_summary = $acc_summary_stmt->get_result()->fetch_assoc();
    
    logMessage("\n=== Daily Summary for Accommodations - {$date} ===");
    logMessage("Accommodations with views: " . $acc_summary['accommodations_with_views']);
    logMessage("Total views: " . $acc_summary['total_views']);
    logMessage("Unique visitors: " . $acc_summary['total_unique_visitors']);
    logMessage("Total bookings: " . $acc_summary['total_bookings']);
    logMessage("Total revenue: Rp " . number_format($acc_summary['total_revenue'], 0, ',', '.'));
    
    // Clean up old accommodation view records
    $acc_cleanup_query = "DELETE FROM penginapan_views WHERE DATE(view_date) < ?";
    $acc_cleanup_stmt = $conn->prepare($acc_cleanup_query);
    $acc_cleanup_stmt->bind_param("s", $cleanup_date);
    
    if ($acc_cleanup_stmt->execute()) {
        $acc_deleted = $acc_cleanup_stmt->affected_rows;
        if ($acc_deleted > 0) {
            logMessage("Cleaned up {$acc_deleted} old accommodation view records");
        }
    }
    
    // Aggregate UMKM/artikel financial statistics
    logMessage("\nProcessing UMKM financial statistics...");
    
    $umkm_query = "
        INSERT INTO umkm_financial_statistics (umkm_id, stat_date, item_count, order_count, quantity_sold, revenue, avg_order_value)
        SELECT 
            u.id as umkm_id,
            ? as stat_date,
            COUNT(DISTINCT a.id) as item_count,
            COUNT(DISTINCT t.id) as order_count,
            COALESCE(SUM(ti.quantity), 0) as quantity_sold,
            COALESCE(SUM(ti.subtotal), 0) as revenue,
            CASE 
                WHEN COUNT(DISTINCT t.id) > 0 
                THEN SUM(ti.subtotal) / COUNT(DISTINCT t.id)
                ELSE 0 
            END as avg_order_value
        FROM umkm u
        LEFT JOIN artikel a ON u.id = a.umkm_id
        LEFT JOIN transaksi_items ti ON ti.item_id = a.id AND ti.item_type = 'artikel'
        LEFT JOIN transaksi t ON ti.transaksi_id = t.id 
            AND t.payment_status = 'paid'
            AND DATE(t.created_at) = ?
        WHERE u.status = 'active'
        GROUP BY u.id
        ON DUPLICATE KEY UPDATE
            item_count = VALUES(item_count),
            order_count = VALUES(order_count),
            quantity_sold = VALUES(quantity_sold),
            revenue = VALUES(revenue),
            avg_order_value = VALUES(avg_order_value),
            updated_at = CURRENT_TIMESTAMP
    ";
    
    $umkm_stmt = $conn->prepare($umkm_query);
    $umkm_stmt->bind_param("ss", $date, $date);
    
    if ($umkm_stmt->execute()) {
        $umkm_affected = $umkm_stmt->affected_rows;
        logMessage("Successfully updated statistics for {$umkm_affected} UMKM");
    } else {
        logMessage("Failed to update UMKM statistics: " . $umkm_stmt->error, 'ERROR');
    }
    
    // Get UMKM summary
    $umkm_summary_query = "
        SELECT 
            COUNT(DISTINCT umkm_id) as active_umkm,
            SUM(order_count) as total_orders,
            SUM(quantity_sold) as total_items_sold,
            SUM(revenue) as total_revenue,
            AVG(avg_order_value) as overall_avg_order_value
        FROM umkm_financial_statistics
        WHERE stat_date = ?
        AND revenue > 0
    ";
    
    $umkm_summary_stmt = $conn->prepare($umkm_summary_query);
    $umkm_summary_stmt->bind_param("s", $date);
    $umkm_summary_stmt->execute();
    $umkm_summary = $umkm_summary_stmt->get_result()->fetch_assoc();
    
    logMessage("\n=== Daily Summary for UMKM - {$date} ===");
    logMessage("Active UMKM with sales: " . $umkm_summary['active_umkm']);
    logMessage("Total orders: " . $umkm_summary['total_orders']);
    logMessage("Total items sold: " . $umkm_summary['total_items_sold']);
    logMessage("Total revenue: Rp " . number_format($umkm_summary['total_revenue'], 0, ',', '.'));
    logMessage("Average order value: Rp " . number_format($umkm_summary['overall_avg_order_value'], 0, ',', '.'));
    
    // Aggregate overall platform financial statistics
    logMessage("\nProcessing overall platform financial statistics...");
    
    $platform_query = "
        INSERT INTO platform_financial_statistics (stat_date, total_transactions, successful_transactions, 
            failed_transactions, total_revenue, wisata_revenue, penginapan_revenue, artikel_revenue, 
            avg_transaction_value, unique_customers)
        SELECT 
            ? as stat_date,
            COUNT(*) as total_transactions,
            COUNT(CASE WHEN payment_status = 'paid' THEN 1 ELSE NULL END) as successful_transactions,
            COUNT(CASE WHEN payment_status IN ('rejected', 'cancelled') THEN 1 ELSE NULL END) as failed_transactions,
            SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue,
            (SELECT COALESCE(SUM(revenue), 0) FROM wisata_statistics WHERE stat_date = ?) as wisata_revenue,
            (SELECT COALESCE(SUM(revenue), 0) FROM penginapan_statistics WHERE stat_date = ?) as penginapan_revenue,
            (SELECT COALESCE(SUM(revenue), 0) FROM umkm_financial_statistics WHERE stat_date = ?) as artikel_revenue,
            AVG(CASE WHEN payment_status = 'paid' THEN total_amount ELSE NULL END) as avg_transaction_value,
            COUNT(DISTINCT user_id) as unique_customers
        FROM transaksi
        WHERE DATE(created_at) = ?
        ON DUPLICATE KEY UPDATE
            total_transactions = VALUES(total_transactions),
            successful_transactions = VALUES(successful_transactions),
            failed_transactions = VALUES(failed_transactions),
            total_revenue = VALUES(total_revenue),
            wisata_revenue = VALUES(wisata_revenue),
            penginapan_revenue = VALUES(penginapan_revenue),
            artikel_revenue = VALUES(artikel_revenue),
            avg_transaction_value = VALUES(avg_transaction_value),
            unique_customers = VALUES(unique_customers),
            updated_at = CURRENT_TIMESTAMP
    ";
    
    $platform_stmt = $conn->prepare($platform_query);
    $platform_stmt->bind_param("sssss", $date, $date, $date, $date, $date);
    
    if ($platform_stmt->execute()) {
        logMessage("Successfully updated platform financial statistics");
    } else {
        logMessage("Failed to update platform statistics: " . $platform_stmt->error, 'ERROR');
    }
    
    // Close statements
    $stmt->close();
    $cleanup_stmt->close();
    $summary_stmt->close();
    $acc_stmt->close();
    $acc_summary_stmt->close();
    $acc_cleanup_stmt->close();
    $umkm_stmt->close();
    $umkm_summary_stmt->close();
    $platform_stmt->close();
    
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
    $subject = "Daily Tourism & Accommodation Statistics - " . $date;
    $message = "Statistics aggregation completed for {$date}\n\n";
    
    $message .= "TOURISM DESTINATIONS:\n";
    $message .= "Total views: " . $summary['total_views'] . "\n";
    $message .= "Unique visitors: " . $summary['total_unique_visitors'] . "\n";
    $message .= "Total bookings: " . $summary['total_bookings'] . "\n";
    $message .= "Total revenue: Rp " . number_format($summary['total_revenue'], 0, ',', '.') . "\n\n";
    
    $message .= "ACCOMMODATIONS:\n";
    $message .= "Total views: " . $acc_summary['total_views'] . "\n";
    $message .= "Unique visitors: " . $acc_summary['total_unique_visitors'] . "\n";
    $message .= "Total bookings: " . $acc_summary['total_bookings'] . "\n";
    $message .= "Total revenue: Rp " . number_format($acc_summary['total_revenue'], 0, ',', '.') . "\n\n";
    
    $message .= "UMKM:\n";
    $message .= "Active UMKM with sales: " . $umkm_summary['active_umkm'] . "\n";
    $message .= "Total orders: " . $umkm_summary['total_orders'] . "\n";
    $message .= "Total items sold: " . $umkm_summary['total_items_sold'] . "\n";
    $message .= "Total revenue: Rp " . number_format($umkm_summary['total_revenue'], 0, ',', '.') . "\n";
    
    mail(ADMIN_EMAIL, $subject, $message);
}

logMessage("Script execution completed");
exit(0);