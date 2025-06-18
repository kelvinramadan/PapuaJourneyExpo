document.addEventListener('DOMContentLoaded', () => {
    const chatBox = document.getElementById('chat-box');
    const userInput = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');
    let isTyping = false;
    let currentConversationId = null;

    // Load conversation history on page load
    loadConversationHistory();

    // Listen for modal state changes
    window.addEventListener('modalStateChanged', (e) => {
        console.log('Modal state changed:', e.detail.isOpen);
    });

    // Event listeners
    sendBtn.addEventListener('click', sendMessage);
    userInput.addEventListener('keypress', (e) => {
        // Check if any modal is open using global flag
        if (window.isModalOpen) return; // Don't process if modal is open
        
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Auto-resize input
    userInput.addEventListener('input', () => {
        userInput.style.height = 'auto';
        userInput.style.height = userInput.scrollHeight + 'px';
    });

    // Quick message function for suggestion buttons
    window.sendQuickMessage = function(message) {
        userInput.value = message;
        sendMessage();
    };

    function sendMessage() {
        const messageText = userInput.value.trim();
        if (messageText === '' || isTyping) return;

        // Disable input while processing
        setInputState(false);
        
        // Add user message
        appendMessage(messageText, 'user');
        userInput.value = '';
        userInput.style.height = 'auto';
        
        // Show typing indicator
        showTypingIndicator();        // Send request
        fetch('chatbot_process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: messageText })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Remove typing indicator
            hideTypingIndicator();
            
            // Add bot response
            if (data.reply) {
                appendMessage(data.reply, 'bot');
                // Update current conversation ID if returned
                if (data.conversation_id) {
                    currentConversationId = data.conversation_id;
                }
            } else {
                appendMessage('Maaf, saya tidak dapat memahami pertanyaan Anda. Coba tanyakan tentang wisata, kuliner, atau budaya Papua.', 'bot');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            hideTypingIndicator();
            appendMessage('Maaf, terjadi kesalahan dalam koneksi. Silakan coba lagi.', 'bot');
        })
        .finally(() => {
            // Re-enable input
            setInputState(true);
        });
    }

    function appendMessage(text, sender) {
        // Remove welcome message if exists
        const welcomeMessage = chatBox.querySelector('.welcome-message');
        if (welcomeMessage && sender === 'user') {
            welcomeMessage.remove();
        }

        const messageContainer = document.createElement('div');
        messageContainer.classList.add('message-container');
        if (sender === 'user') {
            messageContainer.classList.add('user');
        }

        const avatar = document.createElement('div');
        avatar.classList.add(sender === 'user' ? 'user-avatar' : 'bot-avatar');
        
        if (sender === 'user') {
            // Get first letter of user's name or use generic icon
            const userName = document.querySelector('.user-name')?.textContent || 'U';
            avatar.textContent = userName.charAt(0).toUpperCase();
        } else {
            avatar.textContent = '🤖';
        }

        const messageContent = document.createElement('div');
        messageContent.classList.add('message-content');

        const messageBubble = document.createElement('div');
        messageBubble.classList.add('message-bubble');
        messageBubble.classList.add(sender === 'user' ? 'user-message' : 'bot-message');
        
        // Handle Markdown for bot messages, plain text for user messages
        if (sender === 'bot') {
            messageBubble.innerHTML = parseMarkdown(text);
        } else {
            const formattedText = formatMessage(text);
            messageBubble.innerHTML = formattedText;
        }

        messageContent.appendChild(messageBubble);
        messageContainer.appendChild(avatar);
        messageContainer.appendChild(messageContent);
        
        chatBox.appendChild(messageContainer);
        
        // Smooth scroll to bottom
        setTimeout(() => {
            chatBox.scrollTo({
                top: chatBox.scrollHeight,
                behavior: 'smooth'
            });
        }, 100);
    }

    function parseMarkdown(text) {
        let html = text;
        
        // Convert headers
        html = html.replace(/^### (.*$)/gm, '<h3 class="md-h3">$1</h3>');
        html = html.replace(/^## (.*$)/gm, '<h2 class="md-h2">$1</h2>');
        html = html.replace(/^# (.*$)/gm, '<h1 class="md-h1">$1</h1>');
        
        // Convert bold text
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // Convert italic text
        html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
        
        // Convert blockquotes
        html = html.replace(/^> (.*$)/gm, '<blockquote class="md-blockquote">$1</blockquote>');
        
        // Convert unordered lists
        html = html.replace(/^- (.*$)/gm, '<li class="md-li">$1</li>');
        
        // Wrap consecutive list items in ul tags
        html = html.replace(/(<li class="md-li">.*<\/li>)/gs, (match) => {
            return '<ul class="md-ul">' + match + '</ul>';
        });
        
        // Convert numbered lists
        html = html.replace(/^\d+\. (.*$)/gm, '<li class="md-li-numbered">$1</li>');
        
        // Wrap consecutive numbered list items in ol tags
        html = html.replace(/(<li class="md-li-numbered">.*<\/li>)/gs, (match) => {
            return '<ol class="md-ol">' + match + '</ol>';
        });
        
        // Convert horizontal rules
        html = html.replace(/^---$/gm, '<hr class="md-hr">');
        
        // Convert line breaks
        html = html.replace(/\n/g, '<br>');
        
        // Clean up extra br tags around block elements
        html = html.replace(/<br>\s*(<h[1-6]|<\/h[1-6]>|<ul|<\/ul>|<ol|<\/ol>|<blockquote|<\/blockquote>|<hr)/g, '$1');
        html = html.replace(/(<\/h[1-6]>|<\/ul>|<\/ol>|<\/blockquote>|<hr[^>]*>)\s*<br>/g, '$1');
        
        return html;
    }

    function formatMessage(text) {
        // Convert line breaks to HTML
        let formatted = text.replace(/\n/g, '<br>');
        
        // Convert bullet points (if any)
        formatted = formatted.replace(/• /g, '• ');
        
        // Convert numbered lists
        formatted = formatted.replace(/(\d+\. )/g, '<strong>$1</strong>');
        
        return formatted;
    }

    function showTypingIndicator() {
        if (document.querySelector('.typing-indicator')) return;
        
        isTyping = true;
        
        const messageContainer = document.createElement('div');
        messageContainer.classList.add('message-container', 'typing-indicator');

        const avatar = document.createElement('div');
        avatar.classList.add('bot-avatar');
        avatar.textContent = '🤖';

        const messageContent = document.createElement('div');
        messageContent.classList.add('message-content');

        const typingBubble = document.createElement('div');
        typingBubble.classList.add('typing-indicator');
        typingBubble.innerHTML = `
            <span>AI sedang mengetik</span>
            <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        `;

        messageContent.appendChild(typingBubble);
        messageContainer.appendChild(avatar);
        messageContainer.appendChild(messageContent);
        
        chatBox.appendChild(messageContainer);
        
        // Scroll to bottom
        chatBox.scrollTo({
            top: chatBox.scrollHeight,
            behavior: 'smooth'
        });
    }

    function hideTypingIndicator() {
        const typingIndicator = document.querySelector('.message-container.typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
        isTyping = false;
    }

    function setInputState(enabled) {
        userInput.disabled = !enabled;
        sendBtn.disabled = !enabled;
        
        if (enabled) {
            userInput.focus();
        }
    }

    // Initialize
    userInput.focus();
    
    // Add some helpful keyboard shortcuts
    document.addEventListener('keydown', (e) => {
        // Check if any modal is open using global flag
        if (window.isModalOpen) return; // Don't process keyboard shortcuts if modal is open
        
        // Focus input when typing (except when in input already or in other form fields)
        const isInFormField = e.target.tagName === 'INPUT' || 
                            e.target.tagName === 'TEXTAREA' || 
                            e.target.tagName === 'SELECT';
        
        if (!isInFormField && e.key.length === 1 && !e.ctrlKey && !e.altKey && !e.metaKey) {
            userInput.focus();
        }
        
        // Clear chat with Ctrl+L
        if (e.ctrlKey && e.key === 'l') {
            e.preventDefault();
            clearChat();
        }
    });

    function clearChat() {
        // Keep only the welcome message
        const messages = chatBox.querySelectorAll('.message-container:not(.welcome-message)');
        messages.forEach(message => message.remove());
        
        // If no welcome message exists, add it back
        if (!chatBox.querySelector('.welcome-message')) {
            location.reload();
        }
    }

    // Function to load conversation history
    function loadConversationHistory() {
        fetch('load_chat_history.php')
            .then(response => response.json())
            .then(data => {
                if (data.messages && data.messages.length > 0) {
                    // Remove welcome message if we have history
                    const welcomeMessage = chatBox.querySelector('.welcome-message');
                    if (welcomeMessage) {
                        welcomeMessage.remove();
                    }

                    // Display all messages from history
                    data.messages.forEach(msg => {
                        appendMessage(msg.message, msg.type);
                    });

                    // Update current conversation ID
                    currentConversationId = data.conversation_id;
                    
                    // Show conversation info if available
                    if (data.conversation_id) {
                        showConversationInfo(data.conversation_id, data.total_messages);
                    }
                }
            })
            .catch(error => {
                console.error('Error loading conversation history:', error);
            });
    }

    // Function to start a new conversation
    window.startNewConversation = function() {
        if (confirm('Apakah Anda yakin ingin memulai percakapan baru?')) {
            fetch('chatbot_process.php?clear_history=1')
                .then(response => response.json())
                .then(() => {
                    currentConversationId = null;
                    location.reload();
                })
                .catch(error => {
                    console.error('Error starting new conversation:', error);
                });
        }
    };

    // Function to show conversation info
    function showConversationInfo(conversationId, messageCount) {
        const existingInfo = document.querySelector('.conversation-info');
        if (existingInfo) {
            existingInfo.remove();
        }

        const infoDiv = document.createElement('div');
        infoDiv.className = 'conversation-info';
        infoDiv.innerHTML = `
            <span>ID Percakapan: ${conversationId.substring(0, 8)}...</span>
            <span>Jumlah pesan: ${messageCount}</span>
            <button onclick="startNewConversation()" class="new-conversation-btn">
                Percakapan Baru
            </button>
        `;

        const chatHeader = document.querySelector('.chat-header-content');
        if (chatHeader) {
            chatHeader.appendChild(infoDiv);
        }
    }

    // Function to load conversation list (for future use)
    window.loadConversationList = function() {
        fetch('load_chat_history.php?list_conversations=1')
            .then(response => response.json())
            .then(data => {
                if (data.conversations) {
                    console.log('Available conversations:', data.conversations);
                    // TODO: Display conversation list in UI
                }
            })
            .catch(error => {
                console.error('Error loading conversation list:', error);
            });
    };
});