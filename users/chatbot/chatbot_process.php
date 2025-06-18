<?php
session_start(); // Start the session to store conversation history

// Include database configuration
require_once '../../config/database.php';

// Basic logging function
function log_message($message) {
    file_put_contents('chatbot_debug.log', date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
}

// Function to generate unique conversation ID
function generateConversationId() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Function to save message to database
function saveMessageToDatabase($conn, $conversationId, $userId, $messageType, $message) {
    $stmt = $conn->prepare("INSERT INTO chat_conversations (conversation_id, user_id, message_type, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $conversationId, $userId, $messageType, $message);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        // Update conversation session
        $updateStmt = $conn->prepare("
            INSERT INTO chat_conversation_sessions (conversation_id, user_id, message_count) 
            VALUES (?, ?, 1) 
            ON DUPLICATE KEY UPDATE 
            last_message_at = CURRENT_TIMESTAMP, 
            message_count = message_count + 1
        ");
        $updateStmt->bind_param("si", $conversationId, $userId);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    return $result;
}

log_message("Received request.");

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Handle clear history request
if (isset($_GET['clear_history'])) {
    $_SESSION['conversation_history'] = [];
    $_SESSION['conversation_id'] = null;
    echo json_encode(['reply' => 'Riwayat percakapan telah dihapus.']);
    exit;
}

// Initialize or clear conversation history if needed
if (!isset($_SESSION['conversation_history'])) {
    $_SESSION['conversation_history'] = [];
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    log_message("Error: Invalid JSON input.");
    echo json_encode(['reply' => 'Error: Invalid input.']);
    exit;
}
$userMessage = $input['message'];
log_message("User message: " . $userMessage);

if (empty($userMessage)) {
    log_message("Error: Empty message.");
    echo json_encode(['reply' => 'Please enter a message.']);
    exit;
}

// Generate conversation ID if this is the first message
if (!isset($_SESSION['conversation_id']) || empty($_SESSION['conversation_id'])) {
    $_SESSION['conversation_id'] = generateConversationId();
    log_message("Generated new conversation ID: " . $_SESSION['conversation_id']);
}

$conversationId = $_SESSION['conversation_id'];

// Save user message to database
if (!saveMessageToDatabase($conn, $conversationId, $userId, 'user', $userMessage)) {
    log_message("Failed to save user message to database");
}

// Base64 encode the history to safely pass it as a command line argument
$history_json = json_encode($_SESSION['conversation_history']);
$history_b64 = base64_encode($history_json);

$escaped_message = escapeshellarg($userMessage);
$escaped_history = escapeshellarg($history_b64);

// Update path to point to rag_py folder in chatbot directory
$command = "python " . __DIR__ . "/rag_py/rag_query.py " . $escaped_message . " " . $escaped_history;
// Redirect stderr to stdout to capture Python errors
$command .= " 2>&1";

$reply = shell_exec($command);

log_message("RAG Query Command: " . $command);
log_message("RAG Query Reply: " . $reply);

// Check for null reply or if the reply contains a known error signature from the Python script
if ($reply === null || strpos($reply, "Error") === 0) {
    log_message("Error: Failed to execute RAG query script or script returned an error. Reply: " . $reply);
    echo json_encode(['reply' => 'Maaf, terjadi sedikit kendala di sistem. Coba tanyakan lagi ya.']);
    exit;
}

// Update conversation history
$clean_reply = trim($reply); // Clean up whitespace
$_SESSION['conversation_history'][] = ['user' => $userMessage, 'assistant' => $clean_reply];

// Keep only the last 5 turns
if (count($_SESSION['conversation_history']) > 5) {
    $_SESSION['conversation_history'] = array_slice($_SESSION['conversation_history'], -5);
}

// Save bot reply to database
if (!saveMessageToDatabase($conn, $conversationId, $userId, 'bot', $clean_reply)) {
    log_message("Failed to save bot message to database");
}

// Close database connection
$conn->close();

echo json_encode([
    'reply' => $clean_reply,
    'conversation_id' => $conversationId
]);

?>
