<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../cart/cart.php');
    exit();
}

require_once '../../config/database.php';

$db = getDbConnection();
$user_id = $_SESSION['user_id'];

// Get form data
$transaction_id = $_POST['transaction_id'] ?? '';
$transaction_code = $_POST['transaction_code'] ?? '';
$payment_date = $_POST['payment_date'] ?? '';
$notes = $_POST['notes'] ?? '';

// Validate transaction ownership
$stmt = $db->prepare("SELECT id FROM transaksi WHERE id = ? AND user_id = ? AND transaction_code = ?");
$stmt->bind_param("iis", $transaction_id, $user_id, $transaction_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Transaksi tidak ditemukan!';
    header('Location: ../transaksi/transaksi.php');
    exit();
}
$stmt->close();

// Handle file upload
if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error_message'] = 'Gagal mengupload file!';
    header('Location: payment_instructions.php');
    exit();
}

$uploadedFile = $_FILES['payment_proof'];
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($uploadedFile['type'], $allowedTypes)) {
    $_SESSION['error_message'] = 'Hanya file JPG dan PNG yang diperbolehkan!';
    header('Location: payment_instructions.php');
    exit();
}

// Validate file size
if ($uploadedFile['size'] > $maxSize) {
    $_SESSION['error_message'] = 'File terlalu besar! Maksimal 5MB.';
    header('Location: payment_instructions.php');
    exit();
}

// Generate unique filename
$extension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
$filename = 'payment_' . $transaction_code . '_' . time() . '.' . $extension;
$uploadPath = '../../uploads/payment_proofs/' . $filename;

// Move uploaded file
if (!move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
    $_SESSION['error_message'] = 'Gagal menyimpan file!';
    header('Location: payment_instructions.php');
    exit();
}

// Update transaction with payment proof
$stmt = $db->prepare("
    UPDATE transaksi 
    SET payment_proof = ?, 
        user_payment_date = ?, 
        payment_status = 'awaiting_confirmation',
        updated_at = CURRENT_TIMESTAMP
    WHERE id = ? AND user_id = ?
");
$stmt->bind_param("ssii", $filename, $payment_date, $transaction_id, $user_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Bukti pembayaran berhasil diupload! Menunggu konfirmasi admin.';
} else {
    $_SESSION['error_message'] = 'Gagal menyimpan data pembayaran!';
    // Delete uploaded file if database update fails
    unlink($uploadPath);
}
$stmt->close();
$db->close();

// Redirect to transaction history
header('Location: ../transaksi/transaksi.php');
exit();
?>