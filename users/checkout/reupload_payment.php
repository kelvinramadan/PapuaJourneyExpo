<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if transaction code is provided
if (!isset($_POST['transaction_code'])) {
    header('Location: ../account/my_orders.php');
    exit();
}

$transaction_code = $_POST['transaction_code'];
$user_id = $_SESSION['user_id'];

require_once '../../config/database.php';
$db = getDbConnection();

// Verify transaction belongs to user and is rejected
$stmt = $db->prepare("
    SELECT id, total_amount, payment_method, payment_status 
    FROM transaksi 
    WHERE transaction_code = ? AND user_id = ? AND payment_status = 'rejected'
");
$stmt->bind_param("si", $transaction_code, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../account/my_orders.php');
    exit();
}

$transaction = $result->fetch_assoc();
$stmt->close();

// Pass to payment instructions page
$_POST['total_amount'] = $transaction['total_amount'];
$_POST['payment_method'] = $transaction['payment_method'];

// Forward to payment instructions
include 'payment_instructions.php';
?>