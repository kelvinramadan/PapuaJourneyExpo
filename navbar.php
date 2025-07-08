<?php
// This file now includes the unified navbar component
// All navbar functionality is centralized in users/components/navbar.php

// Detect if we're in the root directory or a subdirectory
$current_path = $_SERVER['PHP_SELF'];
$is_root = (dirname($current_path) === '/' || dirname($current_path) === '/PapuaJourneyExpo');

// Include the unified navbar component
if ($is_root) {
    require_once __DIR__ . '/users/components/navbar.php';
} else {
    // For non-root locations, calculate the relative path
    $depth = substr_count(dirname($current_path), '/') - substr_count('/PapuaJourneyExpo', '/');
    $relative_path = str_repeat('../', $depth) . 'users/components/navbar.php';
    require_once $relative_path;
}
?>