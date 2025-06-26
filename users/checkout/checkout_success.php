<?php
session_start();

// Check if user is logged in and coming from checkout
if (!isset($_SESSION['user_id']) || !isset($_SESSION['checkout_success'])) {
    header('Location: ../../login.php');
    exit();
}

// Get checkout data
$transaction_code = $_SESSION['transaction_code'];
$total_amount = $_SESSION['total_amount'];
$payment_method = $_SESSION['payment_method'];

// Clear checkout session data
unset($_SESSION['checkout_success']);
unset($_SESSION['transaction_code']);
unset($_SESSION['total_amount']);
unset($_SESSION['payment_method']);

// Helper functions
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function getPaymentMethodName($method) {
    $methods = [
        'bank_transfer' => 'Transfer Bank',
        'e_wallet' => 'E-Wallet',
        'credit_card' => 'Kartu Kredit/Debit'
    ];
    return $methods[$method] ?? $method;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - Papua Journey</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .main-content {
            margin-top: 70px;
            min-height: calc(100vh - 70px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .success-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: pulse 1s ease infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(39, 174, 96, 0.4);
            }
            70% {
                box-shadow: 0 0 0 30px rgba(39, 174, 96, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(39, 174, 96, 0);
            }
        }
        
        .success-icon i {
            font-size: 50px;
            color: white;
        }
        
        .success-title {
            font-size: 2.5rem;
            color: #27ae60;
            margin-bottom: 10px;
        }
        
        .success-message {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 30px;
        }
        
        .transaction-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #666;
        }
        
        .detail-value {
            font-weight: 600;
            color: #333;
        }
        
        .transaction-code {
            font-family: monospace;
            background: #e8f4fd;
            padding: 2px 8px;
            border-radius: 4px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-secondary {
            background: white;
            color: #3498db;
            border: 2px solid #3498db;
        }
        
        .btn-secondary:hover {
            background: #3498db;
            color: white;
        }
        
        .payment-instructions {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .payment-instructions h4 {
            color: #856404;
            margin-bottom: 10px;
        }
        
        .payment-instructions p {
            color: #856404;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .success-container {
                padding: 30px 20px;
            }
            
            .success-title {
                font-size: 2rem;
            }
            
            .success-message {
                font-size: 1.1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../components/navbar.php'; ?>
    
    <div class="main-content">
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            
            <h1 class="success-title">Pembayaran Berhasil!</h1>
            <p class="success-message">Terima kasih atas pemesanan Anda</p>
            
            <div class="transaction-details">
                <div class="detail-row">
                    <span class="detail-label">Kode Transaksi</span>
                    <span class="detail-value transaction-code"><?php echo htmlspecialchars($transaction_code); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Pembayaran</span>
                    <span class="detail-value"><?php echo formatPrice($total_amount); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Metode Pembayaran</span>
                    <span class="detail-value"><?php echo getPaymentMethodName($payment_method); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" style="color: #f39c12;">Menunggu Pembayaran</span>
                </div>
            </div>
            
            <div class="payment-instructions">
                <h4>📌 Instruksi Pembayaran</h4>
                <p>
                    Silakan lakukan pembayaran sesuai dengan metode yang Anda pilih. 
                    Detail pembayaran telah dikirim ke email Anda. 
                    Konfirmasi pembayaran akan dilakukan otomatis dalam 1x24 jam.
                </p>
            </div>
            
            <div class="action-buttons">
                <a href="../account/my_orders.php" class="btn btn-primary">
                    <i class="fas fa-receipt"></i> Lihat Transaksi
                </a>
                <a href="../dashboard/user_dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Print transaction code for user
        console.log('Transaction Code:', '<?php echo $transaction_code; ?>');
        
        // Auto redirect after 10 seconds
        setTimeout(() => {
            window.location.href = '../account/my_orders.php';
        }, 10000);
    </script>
</body>
</html>