<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get and validate input
$user_id = $_SESSION['user_id'];
$transaksi_id = isset($_POST['transaksi_id']) ? intval($_POST['transaksi_id']) : 0;
$item_type = isset($_POST['item_type']) ? $_POST['item_type'] : '';
$item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';

// Validate required fields
if (!$transaksi_id || !$item_type || !$item_id || !$rating || empty($review_text)) {
    echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
    exit;
}

// Validate item type
if (!in_array($item_type, ['wisata', 'penginapan', 'artikel'])) {
    echo json_encode(['success' => false, 'message' => 'Tipe item tidak valid']);
    exit;
}

// Validate rating
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating harus antara 1-5']);
    exit;
}

// Validate review text length
if (strlen($review_text) < 10) {
    echo json_encode(['success' => false, 'message' => 'Review minimal 10 karakter']);
    exit;
}

if (strlen($review_text) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Review maksimal 1000 karakter']);
    exit;
}

$db = getDbConnection();

try {
    // Check if transaction exists and is paid
    $stmt = $db->prepare("
        SELECT t.payment_status, t.created_at, ti.item_type, ti.item_id, ti.booking_date, ti.checkin_date, ti.checkout_date
        FROM transaksi t
        JOIN transaksi_items ti ON t.id = ti.transaksi_id
        WHERE t.id = ? AND t.user_id = ? AND ti.item_type = ? AND ti.item_id = ?
    ");
    $stmt->bind_param("iisi", $transaksi_id, $user_id, $item_type, $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan']);
        exit;
    }
    
    $transaction = $result->fetch_assoc();
    
    // Check if payment is confirmed
    if ($transaction['payment_status'] !== 'paid') {
        echo json_encode(['success' => false, 'message' => 'Anda hanya dapat memberikan review setelah pembayaran dikonfirmasi']);
        exit;
    }
    
    // Check if the event/stay date has passed
    $current_date = date('Y-m-d');
    $event_date = null;
    
    if ($item_type === 'wisata' && $transaction['booking_date']) {
        $event_date = $transaction['booking_date'];
    } elseif ($item_type === 'penginapan' && $transaction['checkout_date']) {
        $event_date = $transaction['checkout_date'];
    } elseif ($item_type === 'artikel') {
        // For artikel, allow review after payment (no specific date)
        $event_date = $transaction['created_at'];
    }
    
    if ($event_date && $current_date < $event_date) {
        echo json_encode(['success' => false, 'message' => 'Anda dapat memberikan review setelah tanggal kunjungan/checkout']);
        exit;
    }
    
    // Check if user has already reviewed this item in this transaction
    $stmt = $db->prepare("
        SELECT id FROM reviews 
        WHERE user_id = ? AND transaksi_id = ? AND item_type = ? AND item_id = ?
    ");
    $stmt->bind_param("iisi", $user_id, $transaksi_id, $item_type, $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Anda sudah memberikan review untuk item ini']);
        exit;
    }
    
    // Start transaction
    $db->begin_transaction();
    
    // Insert review
    $stmt = $db->prepare("
        INSERT INTO reviews (user_id, transaksi_id, item_type, item_id, rating, review_text, is_verified) 
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->bind_param("iisiis", $user_id, $transaksi_id, $item_type, $item_id, $rating, $review_text);
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal menyimpan review');
    }
    
    $review_id = $db->insert_id;
    
    // Handle media uploads
    $uploaded_media_count = 0;
    $max_media_files = 5;
    $allowed_image_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $allowed_video_types = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo'];
    $max_image_size = 5 * 1024 * 1024; // 5MB
    $max_video_size = 50 * 1024 * 1024; // 50MB
    $max_video_duration = 10; // seconds
    
    // Create upload directories if they don't exist
    $image_upload_dir = '../../uploads/review_media/images/';
    $video_upload_dir = '../../uploads/review_media/videos/';
    
    if (!file_exists($image_upload_dir)) {
        mkdir($image_upload_dir, 0777, true);
    }
    if (!file_exists($video_upload_dir)) {
        mkdir($video_upload_dir, 0777, true);
    }
    
    // Process uploaded files
    if (isset($_FILES['media']) && is_array($_FILES['media']['name'])) {
        $file_count = count($_FILES['media']['name']);
        
        for ($i = 0; $i < $file_count && $uploaded_media_count < $max_media_files; $i++) {
            if ($_FILES['media']['error'][$i] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['media']['name'][$i];
                $file_tmp = $_FILES['media']['tmp_name'][$i];
                $file_size = $_FILES['media']['size'][$i];
                $file_type = $_FILES['media']['type'][$i];
                
                // Determine media type
                $media_type = null;
                $upload_dir = null;
                $max_size = null;
                
                if (in_array($file_type, $allowed_image_types)) {
                    $media_type = 'image';
                    $upload_dir = $image_upload_dir;
                    $max_size = $max_image_size;
                } elseif (in_array($file_type, $allowed_video_types)) {
                    $media_type = 'video';
                    $upload_dir = $video_upload_dir;
                    $max_size = $max_video_size;
                } else {
                    continue; // Skip unsupported file types
                }
                
                // Check file size
                if ($file_size > $max_size) {
                    continue; // Skip files that are too large
                }
                
                // Generate unique filename
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_filename = 'review_' . $review_id . '_' . ($uploaded_media_count + 1) . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $new_filename;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $file_path)) {
                    // For videos, we should check duration (simplified - in production use FFmpeg)
                    $duration = null;
                    if ($media_type === 'video') {
                        // In production, use FFmpeg to check actual duration
                        // For now, we'll trust the client
                        $duration = 10; // Default to max allowed
                    }
                    
                    // Save media record to database
                    $relative_path = 'uploads/review_media/' . ($media_type === 'image' ? 'images/' : 'videos/') . $new_filename;
                    $stmt = $db->prepare("
                        INSERT INTO review_media (review_id, media_type, file_path, file_size, duration, upload_order) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $upload_order = $uploaded_media_count + 1;
                    $stmt->bind_param("issiii", $review_id, $media_type, $relative_path, $file_size, $duration, $upload_order);
                    
                    if ($stmt->execute()) {
                        $uploaded_media_count++;
                    } else {
                        // Delete the uploaded file if database insert fails
                        unlink($file_path);
                    }
                }
            }
        }
    }
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Review berhasil disimpan',
        'review_id' => $review_id,
        'uploaded_media' => $uploaded_media_count
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $db->rollback();
    
    // Clean up any uploaded files
    if (isset($review_id)) {
        $stmt = $db->prepare("SELECT file_path FROM review_media WHERE review_id = ?");
        $stmt->bind_param("i", $review_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $file_to_delete = '../../' . $row['file_path'];
            if (file_exists($file_to_delete)) {
                unlink($file_to_delete);
            }
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
} finally {
    $db->close();
}
?>