<?php
/**
 * Database initialization script for Abandoned Cart Tracking System
 * Run this script once to create all required tables
 */

require_once '../config/database.php';

try {
    $db = getDbConnection();
    
    echo "Initializing Abandoned Cart Tracking Database...\n\n";
    
    // Read SQL file
    $sql_file = __DIR__ . '/abandoned_cart_tables.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: " . $sql_file);
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL statements (simple approach)
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->query($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            $success_count++;
        } catch (Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
            echo "  Statement: " . substr($statement, 0, 50) . "...\n";
            $error_count++;
        }
    }
    
    echo "\n=== Database Initialization Complete ===\n";
    echo "Successful statements: {$success_count}\n";
    echo "Failed statements: {$error_count}\n";
    
    if ($error_count > 0) {
        echo "\nSome errors occurred. Please check if tables already exist or review the SQL statements.\n";
    } else {
        echo "\n🎉 All tables created successfully!\n";
        echo "\nNext steps:\n";
        echo "1. Update domain in admin/scripts/abandoned_cart_reminder.php\n";
        echo "2. Set up cron job for automated email reminders\n";
        echo "3. Access admin dashboard at /admin/abandoned_cart_analytics.php\n";
    }
    
    // Verify tables were created
    echo "\nVerifying table creation:\n";
    $tables = ['abandoned_carts', 'cart_abandonment_reasons', 'cart_recovery_attempts', 'user_cart_sessions'];
    
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($result->num_rows > 0) {
            echo "✓ Table '{$table}' exists\n";
        } else {
            echo "✗ Table '{$table}' not found\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} finally {
    if (isset($db)) {
        $db->close();
    }
}
?>