<?php
// Test payment confirmation
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    die("Please login as admin first");
}

// Test form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['transaksi_id']) && isset($_POST['action'])) {
        echo "<p style='color: green;'>✓ Form data is being received correctly!</p>";
        
        // Test database update
        $db = getDbConnection();
        $transaksi_id = (int)$_POST['transaksi_id'];
        $action = $_POST['action'];
        
        // Check if transaction exists
        $check_stmt = $db->prepare("SELECT * FROM transaksi WHERE id = ?");
        $check_stmt->bind_param("i", $transaksi_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $transaction = $result->fetch_assoc();
            echo "<p>Transaction found: ID " . $transaction['id'] . ", Current status: " . $transaction['payment_status'] . "</p>";
            
            // Test update
            if ($action == 'confirm') {
                $stmt = $db->prepare("UPDATE transaksi SET payment_status = 'paid' WHERE id = ?");
                $stmt->bind_param("i", $transaksi_id);
                
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>✓ Payment confirmed successfully!</p>";
                } else {
                    echo "<p style='color: red;'>✗ Error updating: " . $db->error . "</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>✗ Transaction not found!</p>";
        }
        
        $db->close();
    }
}

// Get a test transaction
$db = getDbConnection();
$test_stmt = $db->prepare("SELECT * FROM transaksi WHERE payment_status = 'awaiting_confirmation' LIMIT 1");
$test_stmt->execute();
$test_result = $test_stmt->get_result();
$test_transaction = $test_result->fetch_assoc();
$db->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Payment Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-form { border: 1px solid #ccc; padding: 20px; margin: 20px 0; background: #f5f5f5; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        .success { background: #4CAF50; color: white; }
        .danger { background: #f44336; color: white; }
    </style>
</head>
<body>
    <h1>Test Payment Confirmation System</h1>
    
    <?php if ($test_transaction): ?>
        <div class="test-form">
            <h3>Test Transaction: <?php echo $test_transaction['transaction_code']; ?></h3>
            <p>Current Status: <?php echo $test_transaction['payment_status']; ?></p>
            <p>Amount: Rp <?php echo number_format($test_transaction['total_amount']); ?></p>
            
            <form method="POST" action="">
                <input type="hidden" name="transaksi_id" value="<?php echo $test_transaction['id']; ?>">
                <input type="hidden" name="action" value="confirm">
                <button type="submit" class="success">Test Confirm Payment</button>
            </form>
            
            <form method="POST" action="" style="margin-top: 10px;">
                <input type="hidden" name="transaksi_id" value="<?php echo $test_transaction['id']; ?>">
                <input type="hidden" name="action" value="reject">
                <button type="submit" class="danger">Test Reject Payment</button>
            </form>
        </div>
    <?php else: ?>
        <p>No transactions with 'awaiting_confirmation' status found. Please create one first.</p>
    <?php endif; ?>
    
    <p><a href="payment_confirmation.php">Back to Payment Confirmation Page</a></p>
</body>
</html>