<?php
session_start(); // Start the session to store conversation history

// Basic logging function
function log_message($message) {
    file_put_contents('chatbot_debug.log', date('Y-m-d H:i:s') . ' - ' . $message . "\n", FILE_APPEND);
}

log_message("Received request.");

header('Content-Type: application/json');

// Initialize or clear conversation history if needed
if (!isset($_SESSION['conversation_history']) || isset($_GET['clear_history'])) {
    $_SESSION['conversation_history'] = [];
    if (isset($_GET['clear_history'])) {
        echo json_encode(['reply' => 'Riwayat percakapan telah dihapus.']);
        exit;
    }
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

// Base64 encode the history to safely pass it as a command line argument
$history_json = json_encode($_SESSION['conversation_history']);
$history_b64 = base64_encode($history_json);

$escaped_message = escapeshellarg($userMessage);
$escaped_history = escapeshellarg($history_b64);

$command = "python " . __DIR__ . "/../RAG_PY/rag_query.py " . $escaped_message . " " . $escaped_history;
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

echo json_encode(['reply' => $clean_reply]);

?>
