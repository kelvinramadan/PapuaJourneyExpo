<?php
require_once 'config/database.php';

$db = getDbConnection();

// Tables to check
$tables = ['reviews', 'review_media', 'review_helpfulness', 'review_summary_cache'];
$missing_tables = [];

echo "<h2>Checking Review System Database Tables...</h2>";

foreach ($tables as $table) {
    $result = $db->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $missing_tables[] = $table;
        echo "❌ Table <b>$table</b> is missing<br>";
    } else {
        echo "✅ Table <b>$table</b> exists<br>";
    }
}

echo "<br>";

if (count($missing_tables) > 0) {
    echo "<h3 style='color: red;'>⚠️ Missing Tables Detected!</h3>";
    echo "<p>Please run the following steps to create the missing tables:</p>";
    echo "<ol>";
    echo "<li>Open phpMyAdmin</li>";
    echo "<li>Select the <b>omaki_db</b> database</li>";
    echo "<li>Click on the SQL tab</li>";
    echo "<li>Copy and paste the contents of <b>/database_updates/add_review_system.sql</b></li>";
    echo "<li>Click 'Go' to execute the script</li>";
    echo "</ol>";
    echo "<p>Or run from command line:</p>";
    echo "<pre>mysql -u root -p omaki_db < database_updates/add_review_system.sql</pre>";
} else {
    echo "<h3 style='color: green;'>✅ All review tables are present!</h3>";
    
    // Check if there are any reviews
    $review_count = $db->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];
    echo "<p>Total reviews in database: <b>$review_count</b></p>";
    
    // Check review summary cache
    $cache_count = $db->query("SELECT COUNT(*) as count FROM review_summary_cache")->fetch_assoc()['count'];
    echo "<p>Review cache entries: <b>$cache_count</b></p>";
}

$db->close();
?>