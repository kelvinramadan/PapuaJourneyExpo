<?php
session_start();

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../login.php');
    exit();
}

// Get user information from session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Assistant - Omaki Platform</title>
    <link rel="stylesheet" href="users_chatbot.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="chat-main-container">
        <div class="chat-header">
            <div class="chat-header-content">
                <div class="chat-title">
                    <h2>🤖 AI Assistant Papua</h2>
                    <p>Tanyakan apapun tentang wisata, kuliner, dan budaya Papua</p>
                </div>
                <div class="chat-status">
                    <div class="status-indicator online"></div>
                    <span>Online</span>
                </div>
            </div>
        </div>
        
        <div class="chat-container">
            <div class="chat-box" id="chat-box">
                <div class="welcome-message">
                    <div class="bot-avatar">
                        🤖
                    </div>
                    <div class="message-content">
                        <div class="message-bubble bot-message">
                            <p>Selamat datang di AI Assistant Papua! 👋</p>
                            <p>Saya siap membantu Anda menjelajahi keindahan Papua. Anda bisa bertanya tentang:</p>
                            <ul>
                                <li>🏝️ Destinasi wisata menarik</li>
                                <li>🍽️ Kuliner khas Papua</li>
                                <li>🎭 Budaya dan tradisi lokal</li>
                                <li>🚗 Transportasi dan akomodasi</li>
                            </ul>
                            <p>Silakan ajukan pertanyaan Anda!</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="chat-input-container">
                <div class="chat-input">
                    <input type="text" id="user-input" placeholder="Ketik pertanyaan Anda tentang Papua..." autocomplete="off">
                    <button id="send-btn" type="button">
                        <span class="send-icon">📤</span>
                    </button>
                </div>
                <div class="quick-suggestions">
                    <button class="suggestion-btn" onclick="sendQuickMessage('Apa saja tempat wisata populer di Jayapura?')">
                        🏝️ Wisata Jayapura
                    </button>
                    <button class="suggestion-btn" onclick="sendQuickMessage('Rekomendasi kuliner khas Papua')">
                        🍽️ Kuliner Papua
                    </button>
                    <button class="suggestion-btn" onclick="sendQuickMessage('Bagaimana transportasi di Papua?')">
                        🚗 Transportasi
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="users_chatbot.js"></script>
</body>
</html>
