<?php
session_start();

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header('Location: ../../login.php');
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
    <link rel="stylesheet" href="chatbot.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>
    
    <div class="chat-app-wrapper">
        <!-- Backdrop for mobile sidebar -->
        <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
        
        <!-- Conversation Sidebar -->
        <aside class="conversation-sidebar" id="conversation-sidebar">
            <div class="sidebar-content">
                <div class="sidebar-header">
                    <button class="sidebar-collapse-btn" id="sidebar-collapse-btn" title="Toggle sidebar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <div class="sidebar-title">
                        <h3>Chat History</h3>
                    </div>
                    <button class="new-chat-btn" id="new-chat-btn" title="New chat">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                    </button>
                </div>
                <div class="conversation-search">
                    <input type="text" id="conversation-search" placeholder="Search chats..." />
                </div>
                <div class="conversation-list-wrapper">
                    <div class="conversation-list" id="conversation-list">
                        <!-- Conversations will be loaded here -->
                        <div class="loading-conversations">
                            <div class="spinner"></div>
                            <span>Loading chats...</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Floating Sidebar Toggle (visible when collapsed) -->
        <button class="sidebar-toggle-floating" id="sidebar-toggle-floating" title="Open sidebar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </button>
        
        <!-- Main Chat Area -->
        <main class="chat-main">
            <!-- Mobile Header with Menu Button -->
            <div class="mobile-header">
                <button class="mobile-menu-btn" id="mobile-menu-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h2>AI Assistant Papua</h2>
            </div>
            
            <!-- Chat Messages Area -->
            <div class="chat-messages-container">
                <div class="chat-messages" id="chat-box">
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
            
            <!-- Chat Input Area -->
            <div class="chat-input-wrapper">
                <div class="chat-input-container">
                    <div class="input-group">
                        <textarea 
                            id="user-input" 
                            placeholder="Ketik pertanyaan Anda tentang Papua..." 
                            autocomplete="off"
                            rows="1"
                        ></textarea>
                        <button id="send-btn" type="button">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </button>
                    </div>
                    <div class="input-helper-text">
                        <span>Tanyakan tentang wisata, kuliner, dan budaya Papua</span>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="chatbot.js"></script>
</body>
</html>
