<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot</title>
    <link rel="stylesheet" href="chatbot.css">
</head>
<body>
    <a href="../users/user_dashboard.php" class="back-button">← Back to Dashboard</a>
    <div class="chat-container">
        <div class="chat-box" id="chat-box">
            <!-- Chat messages will be appended here -->
        </div>
        <div class="chat-input">
            <input type="text" id="user-input" placeholder="Type a message...">
            <button id="send-btn">Send</button>
        </div>
    </div>
    <script src="chatbot.js"></script>
</body>
</html>
