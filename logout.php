<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Start a new session for flash message
session_start();
$_SESSION['logout_message'] = 'Anda telah berhasil logout.';

// Redirect to login page
header('Location: login.php');
exit();
?>

