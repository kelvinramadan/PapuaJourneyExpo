<?php
session_start();

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Include database configuration
require_once '../../config/database.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Check if we need to load a specific conversation or the current one
$conversationId = isset($_GET['conversation_id']) ? $_GET['conversation_id'] : ($_SESSION['conversation_id'] ?? null);

// If requesting conversation list
if (isset($_GET['list_conversations'])) {
    $stmt = $conn->prepare("
        SELECT cs.conversation_id, cs.started_at, cs.last_message_at, cs.message_count,
               (SELECT message FROM chat_conversations 
                WHERE conversation_id = cs.conversation_id 
                ORDER BY created_at DESC LIMIT 1) as last_message
        FROM chat_conversation_sessions cs
        WHERE cs.user_id = ?
        ORDER BY cs.last_message_at DESC
        LIMIT 20
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $conversations = [];
    while ($row = $result->fetch_assoc()) {
        $conversations[] = [
            'conversation_id' => $row['conversation_id'],
            'started_at' => $row['started_at'],
            'last_message_at' => $row['last_message_at'],
            'message_count' => $row['message_count'],
            'preview' => mb_substr($row['last_message'], 0, 100) . (mb_strlen($row['last_message']) > 100 ? '...' : '')
        ];
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode(['conversations' => $conversations]);
    exit();
}

// Load messages for a specific conversation
if ($conversationId) {
    $stmt = $conn->prepare("
        SELECT message_type, message, created_at
        FROM chat_conversations
        WHERE conversation_id = ? AND user_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->bind_param("si", $conversationId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    $conversation_history = [];
    
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'type' => $row['message_type'],
            'message' => $row['message'],
            'timestamp' => $row['created_at']
        ];
        
        // Build conversation history in the format expected by the RAG system
        if ($row['message_type'] === 'user') {
            $conversation_history[] = ['user' => $row['message'], 'assistant' => ''];
        } else if (!empty($conversation_history)) {
            $conversation_history[count($conversation_history) - 1]['assistant'] = $row['message'];
        }
    }
    
    // Update session with loaded conversation
    if ($conversationId === ($_SESSION['conversation_id'] ?? null)) {
        $_SESSION['conversation_history'] = array_slice($conversation_history, -5); // Keep last 5 turns
    }
    
    $stmt->close();
    $conn->close();
    
    echo json_encode([
        'conversation_id' => $conversationId,
        'messages' => $messages,
        'total_messages' => count($messages)
    ]);
} else {
    echo json_encode([
        'conversation_id' => null,
        'messages' => [],
        'total_messages' => 0
    ]);
}
?>