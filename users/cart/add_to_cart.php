<?php
session_start();

// Disable error display to prevent JSON corruption
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set header for JSON response
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

try {
    require_once '../../config/database.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]);
    exit();
}

try {
    $db = getDbConnection();
    if (!$db) {
        throw new Exception("Failed to connect to database");
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Validate input
if (!isset($_POST['item_type']) || !isset($_POST['item_id']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit();
}

$item_type = $_POST['item_type'];
$item_id = (int)$_POST['item_id'];
$quantity = max(1, (int)$_POST['quantity']);
$booking_date = $_POST['booking_date'] ?? null;
$checkin_date = $_POST['checkin_date'] ?? null;
$checkout_date = $_POST['checkout_date'] ?? null;
$notes = $_POST['notes'] ?? '';

// Validate item type
if (!in_array($item_type, ['wisata', 'penginapan', 'artikel'])) {
    echo json_encode(['success' => false, 'message' => 'Tipe item tidak valid']);
    exit();
}

// Get item details and price
$price_per_unit = 0;
$item_exists = false;

if ($item_type == 'wisata') {
    $stmt = $db->prepare("SELECT harga FROM wisata WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $price_per_unit = $row['harga'];
        $item_exists = true;
    }
    $stmt->close();
} elseif ($item_type == 'penginapan') {
    $stmt = $db->prepare("SELECT harga FROM penginapan WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $price_per_unit = $row['harga'];
        $item_exists = true;
        
        // Calculate nights for penginapan
        if ($checkin_date && $checkout_date) {
            $checkin = new DateTime($checkin_date);
            $checkout = new DateTime($checkout_date);
            $nights = $checkin->diff($checkout)->days;
            if ($nights > 0) {
                $quantity = $quantity * $nights; // quantity is rooms * nights
            }
        }
    }
    $stmt->close();
} elseif ($item_type == 'artikel') {
    $stmt = $db->prepare("SELECT harga FROM artikel WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $price_per_unit = $row['harga'];
        $item_exists = true;
    }
    $stmt->close();
}

if (!$item_exists) {
    echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan']);
    exit();
}

// Calculate subtotal
$subtotal = $price_per_unit * $quantity;

// Check if item already exists in cart
$stmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND item_type = ? AND item_id = ?");
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Database error (cart_items table may not exist): ' . $db->error]);
    exit();
}
$stmt->bind_param("isi", $user_id, $item_type, $item_id);
$stmt->execute();
$result = $stmt->get_result();
$existing_item = $result->fetch_assoc();
$stmt->close();

// Initialize response variables
$success = false;
$message = '';

if ($existing_item) {
    // Update existing item
    $new_quantity = $existing_item['quantity'] + $quantity;
    $new_subtotal = $price_per_unit * $new_quantity;
    
    $stmt = $db->prepare("UPDATE cart_items SET quantity = ?, subtotal = ?, booking_date = ?, checkin_date = ?, checkout_date = ?, notes = ? WHERE id = ?");
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
        exit();
    }
    $stmt->bind_param("idssssi", $new_quantity, $new_subtotal, $booking_date, $checkin_date, $checkout_date, $notes, $existing_item['id']);
    
    if ($stmt->execute()) {
        $success = true;
        $message = 'Item berhasil ditambahkan ke keranjang';
    } else {
        $success = false;
        $message = 'Gagal memperbarui keranjang: ' . $stmt->error;
    }
    $stmt->close();
} else {
    // Insert new item
    $stmt = $db->prepare("INSERT INTO cart_items (user_id, item_type, item_id, quantity, price_per_unit, subtotal, booking_date, checkin_date, checkout_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Database error (cart_items table may not exist): ' . $db->error]);
        exit();
    }
    $stmt->bind_param("isiiddssss", $user_id, $item_type, $item_id, $quantity, $price_per_unit, $subtotal, $booking_date, $checkin_date, $checkout_date, $notes);
    
    if ($stmt->execute()) {
        $success = true;
        $message = 'Item berhasil ditambahkan ke keranjang';
    } else {
        $success = false;
        $message = 'Gagal menambahkan ke keranjang: ' . $stmt->error;
    }
    $stmt->close();
}

// Only proceed with cart count if operation was successful
if ($success) {
    // Get cart count for updating navbar
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_count = $result->fetch_assoc()['count'];
    $stmt->close();
    
    // Send single JSON response with all data
    echo json_encode([
        'success' => true,
        'message' => $message,
        'cart_count' => $cart_count
    ]);
} else {
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
}

$db->close();