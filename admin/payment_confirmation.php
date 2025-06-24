<?php
// admin/payment_confirmation.php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle payment confirmation/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_payment'])) {
    $transaksi_id = (int)$_POST['transaksi_id'];
    $action = $_POST['action']; // 'confirm' or 'reject'
    $notes = $_POST['notes'] ?? '';
    $admin_id = 1; // For now, we'll use 1 as the admin ID since there's no admin table
    
    $db = getDbConnection();
    
    // Start transaction
    $db->begin_transaction();
    
    try {
        // Update payment status
        if ($action == 'confirm') {
            $new_status = 'paid';
            $stmt = $db->prepare("UPDATE transaksi SET payment_status = ?, payment_confirmed_at = NOW(), payment_confirmed_by = ? WHERE id = ?");
            $stmt->bind_param("sii", $new_status, $admin_id, $transaksi_id);
        } else {
            $new_status = 'rejected';
            $stmt = $db->prepare("UPDATE transaksi SET payment_status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $transaksi_id);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update payment status");
        }
        
        // Log admin action
        $log_action = $action == 'confirm' ? 'confirmed' : 'rejected';
        $log_stmt = $db->prepare("INSERT INTO admin_payment_logs (admin_id, transaksi_id, action, notes) VALUES (?, ?, ?, ?)");
        $log_stmt->bind_param("iiss", $admin_id, $transaksi_id, $log_action, $notes);
        
        if (!$log_stmt->execute()) {
            throw new Exception("Failed to log admin action");
        }
        
        $db->commit();
        $success_message = "Payment has been " . ($action == 'confirm' ? 'confirmed' : 'rejected') . " successfully!";
        
    } catch (Exception $e) {
        $db->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
    
    $db->close();
}

// Get pending payments
$db = getDbConnection();
$query = "
    SELECT 
        t.*,
        u.full_name as user_name,
        u.email as user_email,
        u.phone as user_phone,
        COUNT(ti.id) as item_count
    FROM transaksi t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN transaksi_items ti ON t.id = ti.transaksi_id
    WHERE t.payment_status = 'awaiting_confirmation'
    GROUP BY t.id
    ORDER BY t.created_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute();
$pending_payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get payment statistics
$stats_query = "
    SELECT 
        payment_status,
        COUNT(*) as count,
        SUM(total_amount) as total_amount
    FROM transaksi
    WHERE payment_status IN ('awaiting_confirmation', 'paid', 'rejected')
    GROUP BY payment_status
";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$payment_stats = [];
while ($row = $stats_result->fetch_assoc()) {
    $payment_stats[$row['payment_status']] = $row;
}
$stats_stmt->close();

$db->close();

// Set page title for header
$page_title = 'Payment Confirmation';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Confirmation - Papua Journey Admin</title>
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .payment-proof-container {
            position: relative;
            max-width: 400px;
            margin: 1rem 0;
        }
        
        .payment-proof-img {
            width: 100%;
            height: auto;
            border-radius: 0.5rem;
            border: 1px solid #E5E7EB;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .payment-proof-img:hover {
            transform: scale(1.02);
        }
        
        .payment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background: #F9FAFB;
            border-radius: 0.5rem;
        }
        
        .payment-detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .payment-detail-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .payment-detail-value {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .action-form {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            margin-top: 1rem;
        }
        
        .notes-input {
            flex: 1;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            max-width: 90%;
            max-height: 90%;
        }
        
        .modal-content img {
            width: 100%;
            height: auto;
        }
        
        .close-modal {
            position: absolute;
            top: 20px;
            right: 40px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close-modal:hover {
            color: #f1f1f1;
        }
        
        .stats-card {
            text-align: center;
            padding: 1.5rem;
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .stats-amount {
            font-size: 1.125rem;
            margin-top: 0.5rem;
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'components/sidebar.php'; ?>
        
        <div class="main-content" id="mainContent">
            <?php include 'components/header.php'; ?>
            
            <div class="content-wrapper">
                <?php if ($success_message): ?>
                    <div class="alert alert-success fade-in">
                        <span>✓</span>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error fade-in">
                        <span>✕</span>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Payment Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($payment_stats['awaiting_confirmation']['count'] ?? 0); ?></div>
                                <div class="stat-label">Awaiting Confirmation</div>
                                <div class="stat-trend">
                                    <span style="font-weight: 600;">Rp <?php echo number_format($payment_stats['awaiting_confirmation']['total_amount'] ?? 0); ?></span>
                                </div>
                            </div>
                            <div class="stat-icon warning">⏳</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($payment_stats['paid']['count'] ?? 0); ?></div>
                                <div class="stat-label">Confirmed Payments</div>
                                <div class="stat-trend trend-up">
                                    <span style="font-weight: 600;">Rp <?php echo number_format($payment_stats['paid']['total_amount'] ?? 0); ?></span>
                                </div>
                            </div>
                            <div class="stat-icon success">✅</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($payment_stats['rejected']['count'] ?? 0); ?></div>
                                <div class="stat-label">Rejected Payments</div>
                                <div class="stat-trend">
                                    <span style="font-weight: 600;">Rp <?php echo number_format($payment_stats['rejected']['total_amount'] ?? 0); ?></span>
                                </div>
                            </div>
                            <div class="stat-icon danger">❌</div>
                        </div>
                    </div>
                </div>
                
                <!-- Pending Payments -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pending Payment Confirmations</h3>
                        <span class="badge badge-warning"><?php echo count($pending_payments); ?> Pending</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_payments)): ?>
                            <p style="text-align: center; padding: 3rem 0; color: var(--text-secondary);">
                                No pending payment confirmations at the moment.
                            </p>
                        <?php else: ?>
                            <?php foreach ($pending_payments as $payment): ?>
                                <div class="card" style="margin-bottom: 1.5rem; border: 1px solid #E5E7EB;">
                                    <div class="card-body">
                                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                            <div>
                                                <h4 style="margin: 0; font-size: 1.25rem;">
                                                    Transaction #<?php echo htmlspecialchars($payment['transaction_code']); ?>
                                                </h4>
                                                <p style="margin: 0.25rem 0; color: var(--text-secondary);">
                                                    <?php echo date('d M Y H:i', strtotime($payment['created_at'])); ?>
                                                </p>
                                            </div>
                                            <span class="badge badge-warning">Awaiting Confirmation</span>
                                        </div>
                                        
                                        <div class="payment-details">
                                            <div class="payment-detail-item">
                                                <span class="payment-detail-label">Customer</span>
                                                <span class="payment-detail-value"><?php echo htmlspecialchars($payment['user_name']); ?></span>
                                            </div>
                                            <div class="payment-detail-item">
                                                <span class="payment-detail-label">Email</span>
                                                <span class="payment-detail-value"><?php echo htmlspecialchars($payment['user_email']); ?></span>
                                            </div>
                                            <div class="payment-detail-item">
                                                <span class="payment-detail-label">Phone</span>
                                                <span class="payment-detail-value"><?php echo htmlspecialchars($payment['user_phone'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="payment-detail-item">
                                                <span class="payment-detail-label">Total Amount</span>
                                                <span class="payment-detail-value" style="color: #059669; font-size: 1.125rem;">
                                                    Rp <?php echo number_format($payment['total_amount']); ?>
                                                </span>
                                            </div>
                                            <div class="payment-detail-item">
                                                <span class="payment-detail-label">Payment Method</span>
                                                <span class="payment-detail-value">
                                                    <?php echo $payment['payment_method'] == 'bank_transfer' ? 'Bank Transfer' : 'E-Wallet'; ?>
                                                </span>
                                            </div>
                                            <div class="payment-detail-item">
                                                <span class="payment-detail-label">User Payment Date</span>
                                                <span class="payment-detail-value">
                                                    <?php echo $payment['user_payment_date'] ? date('d M Y H:i', strtotime($payment['user_payment_date'])) : 'N/A'; ?>
                                                </span>
                                            </div>
                                            <div class="payment-detail-item">
                                                <span class="payment-detail-label">Items</span>
                                                <span class="payment-detail-value"><?php echo $payment['item_count']; ?> item(s)</span>
                                            </div>
                                        </div>
                                        
                                        <?php if ($payment['payment_proof']): ?>
                                            <div class="payment-proof-container">
                                                <h5 style="margin-bottom: 0.5rem;">Payment Proof:</h5>
                                                <img src="../uploads/payment_proofs/<?php echo htmlspecialchars($payment['payment_proof']); ?>" 
                                                     alt="Payment Proof" 
                                                     class="payment-proof-img"
                                                     onclick="openModal('<?php echo htmlspecialchars($payment['payment_proof']); ?>')">
                                                <p style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.5rem;">
                                                    Click image to enlarge
                                                </p>
                                            </div>
                                        <?php else: ?>
                                            <p style="color: var(--text-secondary);">No payment proof uploaded</p>
                                        <?php endif; ?>
                                        
                                        <form method="POST" class="action-form">
                                            <input type="hidden" name="transaksi_id" value="<?php echo $payment['id']; ?>">
                                            
                                            <div class="form-group notes-input">
                                                <label for="notes_<?php echo $payment['id']; ?>" class="form-label">Admin Notes (Optional)</label>
                                                <input type="text" 
                                                       name="notes" 
                                                       id="notes_<?php echo $payment['id']; ?>" 
                                                       class="form-control" 
                                                       placeholder="Add any notes about this payment...">
                                            </div>
                                            
                                            <button type="submit" 
                                                    name="update_payment" 
                                                    value="confirm"
                                                    onclick="this.form.action.value='confirm'"
                                                    class="btn btn-success">
                                                <span>✓</span> Confirm Payment
                                            </button>
                                            
                                            <button type="submit" 
                                                    name="update_payment" 
                                                    value="reject"
                                                    onclick="this.form.action.value='reject'; return confirm('Are you sure you want to reject this payment?');"
                                                    class="btn btn-danger">
                                                <span>✕</span> Reject
                                            </button>
                                            
                                            <input type="hidden" name="action" value="">
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php include 'components/footer.php'; ?>
        </div>
    </div>
    
    <!-- Modal for enlarged image -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <span class="close-modal">&times;</span>
        <div class="modal-content">
            <img id="modalImage" src="" alt="Payment Proof">
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        // Function to open modal with image
        function openModal(imageName) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'flex';
            modalImg.src = '../uploads/payment_proofs/' + imageName;
        }
        
        // Function to close modal
        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }
        
        // Close modal when pressing Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
        
        // Fix form submission
        document.querySelectorAll('button[name="update_payment"]').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                const action = this.value;
                form.querySelector('input[name="action"]').value = action;
                
                if (action === 'reject') {
                    if (!confirm('Are you sure you want to reject this payment?')) {
                        return;
                    }
                }
                
                form.submit();
            });
        });
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>