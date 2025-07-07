<?php
/**
 * Abandoned Cart Email Reminder System
 * This script should be run via cron job to send email reminders
 * Suggested cron: */30 * * * * (every 30 minutes)
 */

require_once '../config/database.php';

class AbandonedCartEmailReminder {
    private $db;
    private $base_url;
    
    public function __construct() {
        $this->db = getDbConnection();
        $this->base_url = 'http://localhost/PapuaJourneyExpo'; // Update with actual domain
    }
    
    public function sendReminders() {
        $reminders_sent = 0;
        
        // Find abandoned carts that need reminders
        $candidates = $this->findReminderCandidates();
        
        foreach ($candidates as $cart) {
            if ($this->sendReminderEmail($cart)) {
                $this->recordReminderAttempt($cart['id'], 'email_reminder');
                $reminders_sent++;
            }
        }
        
        echo "Sent {$reminders_sent} reminder emails\n";
        return $reminders_sent;
    }
    
    private function findReminderCandidates() {
        // Find abandoned carts from 1-24 hours ago that haven't been recovered
        // and haven't received a reminder yet
        $query = "
            SELECT 
                ac.*,
                u.email,
                u.full_name
            FROM abandoned_carts ac
            JOIN users u ON ac.user_id = u.id
            LEFT JOIN cart_recovery_attempts cra ON ac.id = cra.abandoned_cart_id
            WHERE ac.is_recovered = 0
            AND ac.abandonment_timestamp BETWEEN DATE_SUB(NOW(), INTERVAL 24 HOUR) AND DATE_SUB(NOW(), INTERVAL 1 HOUR)
            AND cra.id IS NULL  -- No previous attempts
            AND u.email IS NOT NULL
            ORDER BY ac.total_value DESC
            LIMIT 50  -- Limit to prevent overwhelming email server
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    private function sendReminderEmail($cart) {
        $to = $cart['email'];
        $name = $cart['full_name'] ?? 'Pelanggan';
        $cart_value = number_format($cart['total_value'], 0, ',', '.');
        $item_count = $cart['item_count'];
        
        // Parse cart items for email content
        $cart_items = json_decode($cart['cart_items_snapshot'], true);
        $items_html = '';
        
        foreach (array_slice($cart_items, 0, 3) as $item) { // Show max 3 items
            $items_html .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['item_name']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>Rp " . number_format($item['subtotal'], 0, ',', '.') . "</td>
                </tr>
            ";
        }
        
        if (count($cart_items) > 3) {
            $remaining = count($cart_items) - 3;
            $items_html .= "<tr><td colspan='3' style='padding: 10px; text-align: center; font-style: italic;'>+ {$remaining} item lainnya</td></tr>";
        }
        
        $cart_url = $this->base_url . '/users/cart/cart.php';
        
        $subject = "🛒 Keranjang belanja Anda menunggu - Papua Journey";
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: white; padding: 30px 20px; border: 1px solid #eee; }
                .cart-items { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .cart-items th { background: #f8f9fa; padding: 10px; text-align: left; border-bottom: 2px solid #dee2e6; }
                .btn { display: inline-block; padding: 15px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🛒 Keranjang Anda Menunggu!</h1>
                    <p>Jangan sampai terlewat pengalaman amazing di Papua</p>
                </div>
                
                <div class='content'>
                    <p>Halo <strong>{$name}</strong>,</p>
                    
                    <p>Kami melihat Anda telah menambahkan beberapa item menarik ke keranjang belanja, tapi belum menyelesaikan pemesanan. Jangan sampai kehabisan!</p>
                    
                    <h3>Item di Keranjang Anda:</h3>
                    <table class='cart-items'>
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style='text-align: center;'>Qty</th>
                                <th style='text-align: right;'>Harga</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$items_html}
                        </tbody>
                        <tfoot>
                            <tr style='font-weight: bold; background: #f8f9fa;'>
                                <td style='padding: 15px;' colspan='2'>Total ({$item_count} item)</td>
                                <td style='padding: 15px; text-align: right;'>Rp {$cart_value}</td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <p><strong>Selesaikan pemesanan Anda sekarang dan nikmati petualangan tak terlupakan di Papua!</strong></p>
                    
                    <div style='text-align: center;'>
                        <a href='{$cart_url}' class='btn'>Lanjutkan Pemesanan</a>
                    </div>
                    
                    <p style='font-size: 14px; color: #666; margin-top: 30px;'>
                        <em>Email ini dikirim karena Anda memiliki item di keranjang belanja. Jika Anda tidak ingin menerima email pengingat lagi, silakan <a href='{$this->base_url}/unsubscribe.php?email={$to}'>klik di sini</a>.</em>
                    </p>
                </div>
                
                <div class='footer'>
                    <p>&copy; 2024 Papua Journey. Jelajahi keindahan Papua bersama kami.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Papua Journey <noreply@papuajourney.com>" . "\r\n";
        
        // In production, you might want to use a proper email service like PHPMailer or SendGrid
        return mail($to, $subject, $message, $headers);
    }
    
    private function recordReminderAttempt($abandoned_cart_id, $attempt_type) {
        $stmt = $this->db->prepare("
            INSERT INTO cart_recovery_attempts (abandoned_cart_id, attempt_type, template_used, subject_line) 
            VALUES (?, ?, 'default_reminder', 'Keranjang belanja Anda menunggu - Papua Journey')
        ");
        $stmt->bind_param("is", $abandoned_cart_id, $attempt_type);
        $stmt->execute();
        $stmt->close();
    }
    
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
}

// Run the reminder system
if (php_sapi_name() === 'cli' || !empty($_GET['run_reminders'])) {
    $reminder = new AbandonedCartEmailReminder();
    $reminder->sendReminders();
} else {
    echo "This script should be run via command line or with ?run_reminders=1 parameter.";
}
?>