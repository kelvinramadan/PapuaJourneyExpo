<?php
session_start();

// Check if user is logged in and has completed checkout
if (!isset($_SESSION['user_id']) || !isset($_SESSION['checkout_success'])) {
    header('Location: ../cart/cart.php');
    exit();
}

// Get transaction details from session
$transaction_code = $_SESSION['transaction_code'];
$total_amount = $_SESSION['total_amount'];
$payment_method = $_SESSION['payment_method'];

// Clear checkout success flag to prevent revisiting
unset($_SESSION['checkout_success']);

// Helper function
function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

// Payment account details
$bank_accounts = [
    'BCA' => [
        'number' => '1234567890',
        'name' => 'Papua Journey'
    ],
    'Mandiri' => [
        'number' => '0987654321',
        'name' => 'Papua Journey'
    ],
    'BNI' => [
        'number' => '1122334455',
        'name' => 'Papua Journey'
    ],
    'BRI' => [
        'number' => '5544332211',
        'name' => 'Papua Journey'
    ]
];

$ewallet_accounts = [
    'GoPay' => '081234567890',
    'OVO' => '081234567890',
    'DANA' => '081234567890',
    'ShopeePay' => '081234567890'
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instruksi Pembayaran - Papua Journey</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
        }
        
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        
        .payment-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .payment-header {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .payment-header h1 {
            margin-bottom: 10px;
            font-size: 2rem;
        }
        
        .payment-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .payment-content {
            padding: 40px;
        }
        
        .transaction-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            font-weight: 600;
            color: #333;
        }
        
        .amount-value {
            color: #ff9800;
            font-size: 1.3rem;
        }
        
        .payment-instructions {
            margin-top: 30px;
        }
        
        .instructions-title {
            font-size: 1.3rem;
            margin-bottom: 20px;
            color: #333;
        }
        
        .account-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .account-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .account-item:last-child {
            margin-bottom: 0;
        }
        
        .bank-logo {
            width: 60px;
            height: 40px;
            background: #f0f0f0;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 20px;
            color: #666;
        }
        
        .account-details {
            flex: 1;
        }
        
        .account-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            font-family: monospace;
        }
        
        .account-name {
            color: #666;
            font-size: 0.9rem;
        }
        
        .copy-btn {
            background: #ff9800;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .copy-btn:hover {
            background: #f57c00;
            transform: translateY(-2px);
        }
        
        .important-notes {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .important-notes h3 {
            color: #856404;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .important-notes ul {
            list-style: none;
            color: #856404;
        }
        
        .important-notes li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
        }
        
        .important-notes li::before {
            content: "•";
            position: absolute;
            left: 10px;
            font-weight: bold;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }
        
        .btn {
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-primary {
            background: #28a745;
            color: white;
        }
        
        .btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #545b62;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .payment-content {
                padding: 20px;
            }
            
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .account-item {
                flex-direction: column;
                text-align: center;
            }
            
            .bank-logo {
                margin: 0 0 15px 0;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-card">
            <div class="payment-header">
                <h1>⏳ Menunggu Pembayaran</h1>
                <p>Silakan lakukan pembayaran sesuai instruksi di bawah ini</p>
            </div>
            
            <div class="payment-content">
                <!-- Transaction Info -->
                <div class="transaction-info">
                    <div class="info-row">
                        <span class="info-label">Kode Transaksi</span>
                        <span class="info-value"><?php echo $transaction_code; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total Pembayaran</span>
                        <span class="info-value amount-value"><?php echo formatPrice($total_amount); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Metode Pembayaran</span>
                        <span class="info-value">
                            <?php echo $payment_method == 'bank_transfer' ? 'Transfer Bank' : 'E-Wallet'; ?>
                        </span>
                    </div>
                </div>
                
                <!-- Payment Instructions -->
                <div class="payment-instructions">
                    <h2 class="instructions-title">
                        <?php if ($payment_method == 'bank_transfer'): ?>
                            💳 Rekening Tujuan Transfer
                        <?php else: ?>
                            📱 Nomor E-Wallet Tujuan
                        <?php endif; ?>
                    </h2>
                    
                    <div class="account-list">
                        <?php if ($payment_method == 'bank_transfer'): ?>
                            <?php foreach ($bank_accounts as $bank => $account): ?>
                                <div class="account-item">
                                    <div class="bank-logo"><?php echo $bank; ?></div>
                                    <div class="account-details">
                                        <div class="account-number"><?php echo $account['number']; ?></div>
                                        <div class="account-name">a.n. <?php echo $account['name']; ?></div>
                                    </div>
                                    <button class="copy-btn" onclick="copyToClipboard('<?php echo $account['number']; ?>')">
                                        📋 Salin
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach ($ewallet_accounts as $wallet => $number): ?>
                                <div class="account-item">
                                    <div class="bank-logo"><?php echo $wallet; ?></div>
                                    <div class="account-details">
                                        <div class="account-number"><?php echo $number; ?></div>
                                        <div class="account-name">a.n. Papua Journey</div>
                                    </div>
                                    <button class="copy-btn" onclick="copyToClipboard('<?php echo $number; ?>')">
                                        📋 Salin
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Important Notes -->
                <div class="important-notes">
                    <h3>⚠️ Penting!</h3>
                    <ul>
                        <li>Lakukan pembayaran dalam waktu 24 jam</li>
                        <li>Transfer sesuai dengan jumlah yang tertera (hingga digit terakhir)</li>
                        <li>Simpan bukti pembayaran untuk konfirmasi</li>
                        <li>Pesanan akan diproses setelah pembayaran dikonfirmasi</li>
                        <?php if ($payment_method == 'bank_transfer'): ?>
                            <li>Gunakan kode transaksi sebagai berita transfer</li>
                        <?php else: ?>
                            <li>Kirim screenshot pembayaran ke WhatsApp kami</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Payment Confirmation Section -->
                <div class="payment-confirmation" style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #e0e0e0;">
                    <h2 class="instructions-title">
                        ✅ Konfirmasi Pembayaran
                    </h2>
                    
                    <form id="payment-proof-form" action="upload_payment_proof.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="transaction_id" value="<?php echo $_SESSION['transaction_id'] ?? ''; ?>">
                        <input type="hidden" name="transaction_code" value="<?php echo $transaction_code; ?>">
                        
                        <div class="upload-section" style="background: #f8f9fa; border-radius: 10px; padding: 25px; margin-bottom: 20px;">
                            <h3 style="margin-bottom: 15px; color: #333;">Upload Bukti Pembayaran</h3>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="payment_proof" style="display: block; margin-bottom: 10px; font-weight: 600;">
                                    Pilih file bukti pembayaran (JPG, PNG, max 5MB):
                                </label>
                                <input type="file" id="payment_proof" name="payment_proof" accept="image/*" required 
                                       style="display: none;" onchange="previewImage(this)">
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('payment_proof').click()">
                                    📷 Pilih File
                                </button>
                            </div>
                            
                            <div id="image-preview" style="display: none; margin-bottom: 20px;">
                                <p style="margin-bottom: 10px; font-weight: 600;">Preview:</p>
                                <img id="preview-img" src="" alt="Preview" style="max-width: 300px; max-height: 300px; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                            </div>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="payment_date" style="display: block; margin-bottom: 10px; font-weight: 600;">
                                    Tanggal Pembayaran:
                                </label>
                                <input type="datetime-local" id="payment_date" name="payment_date" required 
                                       style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; width: 100%; max-width: 300px;">
                            </div>
                            
                            <div class="form-group">
                                <label for="notes" style="display: block; margin-bottom: 10px; font-weight: 600;">
                                    Catatan (opsional):
                                </label>
                                <textarea id="notes" name="notes" rows="3" placeholder="Tambahkan catatan jika diperlukan..."
                                          style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; resize: vertical;"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions" style="text-align: center;">
                            <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">
                                ✅ Saya Sudah Bayar
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="../transaksi/transaksi.php" class="btn btn-secondary">
                        📋 Lihat Riwayat Transaksi
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    alert('Nomor rekening berhasil disalin!');
                }).catch(() => {
                    fallbackCopy(text);
                });
            } else {
                fallbackCopy(text);
            }
        }
        
        function fallbackCopy(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                alert('Nomor rekening berhasil disalin!');
            } catch (err) {
                alert('Gagal menyalin. Silakan salin manual.');
            }
            
            document.body.removeChild(textArea);
        }
        
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                // Check file size (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File terlalu besar! Maksimal 5MB.');
                    input.value = '';
                    return;
                }
                
                // Check file type
                if (!file.type.match('image.*')) {
                    alert('Hanya file gambar yang diperbolehkan!');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('image-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>