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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transaksi_id']) && isset($_POST['action'])) {
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
            $stmt = $db->prepare("UPDATE transaksi SET payment_status = ?, payment_confirmed_at = NOW(), payment_confirmed_by = ?, payment_date = NOW() WHERE id = ?");
            $stmt->bind_param("sii", $new_status, $admin_id, $transaksi_id);
        } else {
            $new_status = 'rejected';
            $stmt = $db->prepare("UPDATE transaksi SET payment_status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $transaksi_id);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update payment status: " . $stmt->error);
        }
        $stmt->close();
        
        // Log admin action
        $log_action = $action == 'confirm' ? 'confirmed' : 'rejected';
        $log_stmt = $db->prepare("INSERT INTO admin_payment_logs (admin_id, transaksi_id, action, notes) VALUES (?, ?, ?, ?)");
        $log_stmt->bind_param("iiss", $admin_id, $transaksi_id, $log_action, $notes);
        
        if (!$log_stmt->execute()) {
            throw new Exception("Failed to log admin action: " . $log_stmt->error);
        }
        $log_stmt->close();
        
        $db->commit();
        $success_message = "Pembayaran berhasil " . ($action == 'confirm' ? 'dikonfirmasi' : 'ditolak') . "!";
        
    } catch (Exception $e) {
        $db->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
    
    $db->close();
}

// Get all payments by status
$db = getDbConnection();

// Base query for fetching payments
$base_query = "
    SELECT 
        t.*,
        u.full_name as user_name,
        u.email as user_email,
        u.phone as user_phone,
        COUNT(ti.id) as item_count
    FROM transaksi t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN transaksi_items ti ON t.id = ti.transaksi_id
    WHERE t.payment_status = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC
";

// Get pending payments
$stmt = $db->prepare($base_query);
$status = 'awaiting_confirmation';
$stmt->bind_param("s", $status);
$stmt->execute();
$pending_payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get confirmed payments
$stmt = $db->prepare($base_query);
$status = 'paid';
$stmt->bind_param("s", $status);
$stmt->execute();
$confirmed_payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get rejected payments
$stmt = $db->prepare($base_query);
$status = 'rejected';
$stmt->bind_param("s", $status);
$stmt->execute();
$rejected_payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
$page_title = 'Konfirmasi Pembayaran';

// Function to render payment cards
function renderPaymentCard($payment, $showActions = true) {
    ?>
    <div class="card" style="margin-bottom: 1.5rem; border: 1px solid #E5E7EB;">
        <div class="card-body">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                <div>
                    <h4 style="margin: 0; font-size: 1.25rem;">
                        Transaksi #<?php echo htmlspecialchars($payment['transaction_code']); ?>
                    </h4>
                    <p style="margin: 0.25rem 0; color: var(--text-secondary);">
                        <?php echo date('d M Y H:i', strtotime($payment['created_at'])); ?>
                    </p>
                </div>
                <span class="badge badge-<?php 
                    echo $payment['payment_status'] == 'awaiting_confirmation' ? 'warning' : 
                         ($payment['payment_status'] == 'paid' ? 'success' : 'danger'); 
                ?>">
                    <?php 
                    echo $payment['payment_status'] == 'awaiting_confirmation' ? 'Menunggu Konfirmasi' : 
                         ($payment['payment_status'] == 'paid' ? 'Terkonfirmasi' : 'Ditolak'); 
                    ?>
                </span>
            </div>
            
            <div class="payment-details">
                <div class="payment-detail-item">
                    <span class="payment-detail-label">Pelanggan</span>
                    <span class="payment-detail-value"><?php echo htmlspecialchars($payment['user_name']); ?></span>
                </div>
                <div class="payment-detail-item">
                    <span class="payment-detail-label">Email</span>
                    <span class="payment-detail-value"><?php echo htmlspecialchars($payment['user_email']); ?></span>
                </div>
                <div class="payment-detail-item">
                    <span class="payment-detail-label">Telepon</span>
                    <span class="payment-detail-value"><?php echo htmlspecialchars($payment['user_phone'] ?? 'N/A'); ?></span>
                </div>
                <div class="payment-detail-item">
                    <span class="payment-detail-label">Total Pembayaran</span>
                    <span class="payment-detail-value" style="color: #059669; font-size: 1.125rem;">
                        Rp <?php echo number_format($payment['total_amount']); ?>
                    </span>
                </div>
                <div class="payment-detail-item">
                    <span class="payment-detail-label">Metode Pembayaran</span>
                    <span class="payment-detail-value">
                        <?php echo $payment['payment_method'] == 'bank_transfer' ? 'Transfer Bank' : 'E-Wallet'; ?>
                    </span>
                </div>
                <div class="payment-detail-item">
                    <span class="payment-detail-label">Tanggal Pembayaran User</span>
                    <span class="payment-detail-value">
                        <?php echo $payment['user_payment_date'] ? date('d M Y H:i', strtotime($payment['user_payment_date'])) : 'N/A'; ?>
                    </span>
                </div>
                <div class="payment-detail-item">
                    <span class="payment-detail-label">Item</span>
                    <span class="payment-detail-value"><?php echo $payment['item_count']; ?> item</span>
                </div>
                <?php if ($payment['payment_status'] == 'paid' && $payment['payment_confirmed_at']): ?>
                <div class="payment-detail-item">
                    <span class="payment-detail-label">Dikonfirmasi Pada</span>
                    <span class="payment-detail-value">
                        <?php echo date('d M Y H:i', strtotime($payment['payment_confirmed_at'])); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($payment['payment_proof']): ?>
                <div class="payment-proof-container">
                    <h5 style="margin-bottom: 0.5rem;">Bukti Pembayaran:</h5>
                    <img src="../uploads/payment_proofs/<?php echo htmlspecialchars($payment['payment_proof']); ?>" 
                         alt="Bukti Pembayaran" 
                         class="payment-proof-img"
                         onclick="openModal('<?php echo htmlspecialchars($payment['payment_proof']); ?>')">
                    <p style="font-size: 0.875rem; color: var(--text-secondary); margin-top: 0.5rem;">
                        Klik gambar untuk memperbesar
                    </p>
                </div>
            <?php else: ?>
                <p style="color: var(--text-secondary);">Tidak ada bukti pembayaran yang diunggah</p>
            <?php endif; ?>
            
            <?php if ($showActions && $payment['payment_status'] == 'awaiting_confirmation'): ?>
                <form method="POST" class="action-form" id="payment-form-<?php echo $payment['id']; ?>">
                    <input type="hidden" name="transaksi_id" value="<?php echo $payment['id']; ?>">
                    <input type="hidden" name="action" id="action-<?php echo $payment['id']; ?>" value="">
                    
                    <div class="form-group notes-input">
                        <label for="notes_<?php echo $payment['id']; ?>" class="form-label">Catatan Admin (Opsional)</label>
                        <input type="text" 
                               name="notes" 
                               id="notes_<?php echo $payment['id']; ?>" 
                               class="form-control" 
                               placeholder="Tambahkan catatan tentang pembayaran ini...">
                    </div>
                    
                    <button type="button" 
                            onclick="submitPaymentAction('<?php echo $payment['id']; ?>', 'confirm')"
                            class="btn btn-success">
                        <span>✓</span> Konfirmasi Pembayaran
                    </button>
                    
                    <button type="button" 
                            onclick="submitPaymentAction('<?php echo $payment['id']; ?>', 'reject')"
                            class="btn btn-danger">
                        <span>✕</span> Tolak
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran - Papua Journey Admin</title>
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
                    <div class="stat-card active" data-filter="awaiting_confirmation" onclick="filterPayments('awaiting_confirmation')">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($payment_stats['awaiting_confirmation']['count'] ?? 0); ?></div>
                                <div class="stat-label">Menunggu Konfirmasi</div>
                                <div class="stat-trend">
                                    <span style="font-weight: 600;">Rp <?php echo number_format($payment_stats['awaiting_confirmation']['total_amount'] ?? 0); ?></span>
                                </div>
                            </div>
                            <div class="stat-icon warning">⏳</div>
                        </div>
                    </div>
                    
                    <div class="stat-card" data-filter="paid" onclick="filterPayments('paid')">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($payment_stats['paid']['count'] ?? 0); ?></div>
                                <div class="stat-label">Pembayaran Terkonfirmasi</div>
                                <div class="stat-trend trend-up">
                                    <span style="font-weight: 600;">Rp <?php echo number_format($payment_stats['paid']['total_amount'] ?? 0); ?></span>
                                </div>
                            </div>
                            <div class="stat-icon success">✅</div>
                        </div>
                    </div>
                    
                    <div class="stat-card" data-filter="rejected" onclick="filterPayments('rejected')">
                        <div class="stat-header">
                            <div>
                                <div class="stat-value"><?php echo number_format($payment_stats['rejected']['count'] ?? 0); ?></div>
                                <div class="stat-label">Pembayaran Ditolak</div>
                                <div class="stat-trend">
                                    <span style="font-weight: 600;">Rp <?php echo number_format($payment_stats['rejected']['total_amount'] ?? 0); ?></span>
                                </div>
                            </div>
                            <div class="stat-icon danger">❌</div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Sections -->
                <!-- Pending Payments Section -->
                <div class="payment-section" id="awaiting_confirmation_section" style="display: block;">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Pembayaran Menunggu Konfirmasi</h3>
                            <span class="badge badge-warning"><?php echo count($pending_payments); ?> Menunggu</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pending_payments)): ?>
                                <p style="text-align: center; padding: 3rem 0; color: var(--text-secondary);">
                                    Tidak ada pembayaran yang menunggu konfirmasi saat ini.
                                </p>
                            <?php else: ?>
                                <?php foreach ($pending_payments as $payment): ?>
                                    <?php renderPaymentCard($payment, true); ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Confirmed Payments Section -->
                <div class="payment-section" id="paid_section" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Pembayaran Terkonfirmasi</h3>
                            <span class="badge badge-success"><?php echo count($confirmed_payments); ?> Terkonfirmasi</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($confirmed_payments)): ?>
                                <p style="text-align: center; padding: 3rem 0; color: var(--text-secondary);">
                                    Tidak ada pembayaran yang terkonfirmasi saat ini.
                                </p>
                            <?php else: ?>
                                <?php foreach ($confirmed_payments as $payment): ?>
                                    <?php renderPaymentCard($payment, false); ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Rejected Payments Section -->
                <div class="payment-section" id="rejected_section" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Pembayaran Ditolak</h3>
                            <span class="badge badge-danger"><?php echo count($rejected_payments); ?> Ditolak</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($rejected_payments)): ?>
                                <p style="text-align: center; padding: 3rem 0; color: var(--text-secondary);">
                                    Tidak ada pembayaran yang ditolak saat ini.
                                </p>
                            <?php else: ?>
                                <?php foreach ($rejected_payments as $payment): ?>
                                    <?php renderPaymentCard($payment, false); ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
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
            <img id="modalImage" src="" alt="Bukti Pembayaran">
        </div>
    </div>
    
    <script src="assets/js/admin.js"></script>
    <script>
        // Current active filter
        let currentFilter = 'awaiting_confirmation';
        
        // Function to filter payments
        function filterPayments(status) {
            // Update active card styling
            document.querySelectorAll('.stat-card').forEach(card => {
                card.classList.remove('active');
            });
            document.querySelector(`[data-filter="${status}"]`).classList.add('active');
            
            // Hide all payment sections
            document.querySelectorAll('.payment-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Show selected payment section
            const selectedSection = document.getElementById(`${status}_section`);
            if (selectedSection) {
                selectedSection.style.display = 'block';
            }
            
            // Update current filter
            currentFilter = status;
        }
        
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
        
        // Function to submit payment action
        function submitPaymentAction(paymentId, action) {
            console.log('submitPaymentAction called with:', paymentId, action);
            
            if (action === 'reject') {
                if (!confirm('Apakah Anda yakin ingin menolak pembayaran ini?')) {
                    return;
                }
            }
            
            // Set the action value
            const actionInput = document.getElementById('action-' + paymentId);
            const form = document.getElementById('payment-form-' + paymentId);
            
            if (!actionInput || !form) {
                console.error('Form elements not found for payment ID:', paymentId);
                alert('Error: Elemen form tidak ditemukan. Silakan refresh halaman dan coba lagi.');
                return;
            }
            
            actionInput.value = action;
            console.log('Submitting form with action:', action);
            
            // Submit the form
            form.submit();
        }
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
        
        // Initialize with default filter
        document.addEventListener('DOMContentLoaded', function() {
            filterPayments('awaiting_confirmation');
        });
    </script>
</body>
</html>