document.addEventListener('DOMContentLoaded', () => {
    const chatBox = document.getElementById('chat-box');
    const userInput = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');
    const conversationList = document.getElementById('conversation-list');
    const newChatBtn = document.getElementById('new-chat-btn');
    const conversationSidebar = document.getElementById('conversation-sidebar');
    const conversationSearch = document.getElementById('conversation-search');
    const sidebarCollapseBtn = document.getElementById('sidebar-collapse-btn');
    const sidebarToggleFloating = document.getElementById('sidebar-toggle-floating');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebarBackdrop = document.getElementById('sidebar-backdrop');
    
    let isTyping = false;
    let currentConversationId = null;
    let conversations = [];

    // Initialize sidebar state from localStorage
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (sidebarCollapsed && window.innerWidth > 768) {
        conversationSidebar.classList.add('collapsed');
    }

    // Initialize
    loadConversationList();
    loadConversationHistory();

    // Event listeners
    newChatBtn.addEventListener('click', startNewConversation);
    sidebarCollapseBtn.addEventListener('click', toggleSidebarCollapse);
    sidebarToggleFloating.addEventListener('click', toggleSidebarCollapse);
    mobileMenuBtn.addEventListener('click', toggleMobileSidebar);
    sidebarBackdrop.addEventListener('click', closeMobileSidebar);
    conversationSearch.addEventListener('input', filterConversations);

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

    // Auto-resize textarea
    userInput.addEventListener('input', () => {
        userInput.style.height = 'auto';
        userInput.style.height = Math.min(userInput.scrollHeight, 120) + 'px';
    });
    
    // Handle Enter key in textarea (without Shift)
    userInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
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
                    loadConversationList(); // Refresh conversation list
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
            const messagesContainer = document.querySelector('.chat-messages-container');
            if (messagesContainer) {
                messagesContainer.scrollTo({
                    top: messagesContainer.scrollHeight,
                    behavior: 'smooth'
                });
            }
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
        const messagesContainer = document.querySelector('.chat-messages-container');
        if (messagesContainer) {
            messagesContainer.scrollTo({
                top: messagesContainer.scrollHeight,
                behavior: 'smooth'
            });
        }
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
    function loadConversationHistory(conversationId = null) {
        const url = conversationId 
            ? `load_chat_history.php?conversation_id=${conversationId}`
            : 'load_chat_history.php';
            
        fetch(url)
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
    function startNewConversation() {
        if (currentConversationId && chatBox.querySelectorAll('.message-container').length > 1) {
            if (!confirm('Apakah Anda yakin ingin memulai percakapan baru?')) {
                return;
            }
        }
        
        fetch('chatbot_process.php?clear_history=1')
            .then(response => response.json())
            .then(() => {
                currentConversationId = null;
                clearChatDisplay();
                loadConversationList();
            })
            .catch(error => {
                console.error('Error starting new conversation:', error);
            });
    }
    
    window.startNewConversation = startNewConversation;

    // Function to show conversation info
    function showConversationInfo(conversationId, messageCount) {
        // Info is now integrated in the UI, no need to add dynamically
    }

    // Function to load conversation list
    function loadConversationList() {
        fetch('load_chat_history.php?list_conversations=1')
            .then(response => response.json())
            .then(data => {
                if (data.conversations) {
                    conversations = data.conversations;
                    displayConversationList(conversations);
                }
            })
            .catch(error => {
                console.error('Error loading conversation list:', error);
                conversationList.innerHTML = '<div class="error-message">Failed to load conversations</div>';
            });
    }
    
    // Display conversations in sidebar
    function displayConversationList(convs) {
        if (convs.length === 0) {
            conversationList.innerHTML = `
                <div class="no-conversations">
                    <p>No conversations yet</p>
                    <p>Start a new chat to begin!</p>
                </div>
            `;
            return;
        }
        
        conversationList.innerHTML = convs.map(conv => `
            <div class="conversation-item ${conv.conversation_id === currentConversationId ? 'active' : ''}" 
                 data-conversation-id="${conv.conversation_id}">
                <div class="conversation-item-header">
                    <span class="conversation-date">${formatDate(conv.last_message_at)}</span>
                </div>
                <div class="conversation-preview">${escapeHtml(conv.preview)}</div>
                <div class="conversation-message-count">${conv.message_count} messages</div>
                <div class="conversation-actions">
                    <button class="delete-conversation-btn" onclick="deleteConversation('${conv.conversation_id}')">Delete</button>
                </div>
            </div>
        `).join('');
        
        // Add click handlers
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (!e.target.classList.contains('delete-conversation-btn')) {
                    const conversationId = item.dataset.conversationId;
                    switchConversation(conversationId);
                }
            });
        });
    }
    
    // Switch to a different conversation
    function switchConversation(conversationId) {
        if (conversationId === currentConversationId) return;
        
        fetch(`chatbot_process.php?switch_conversation=${conversationId}`)
            .then(response => response.json())
            .then(data => {
                currentConversationId = conversationId;
                clearChatDisplay();
                loadConversationHistory(conversationId);
                
                // Update active state in sidebar
                document.querySelectorAll('.conversation-item').forEach(item => {
                    item.classList.toggle('active', item.dataset.conversationId === conversationId);
                });
                
                // Close mobile sidebar after selection
                if (window.innerWidth <= 768) {
                    closeMobileSidebar();
                }
            })
            .catch(error => {
                console.error('Error switching conversation:', error);
            });
    }
    
    // Delete a conversation
    window.deleteConversation = function(conversationId) {
        if (!confirm('Apakah Anda yakin ingin menghapus percakapan ini?')) return;
        
        fetch('load_chat_history.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', conversation_id: conversationId})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (conversationId === currentConversationId) {
                    startNewConversation();
                } else {
                    loadConversationList();
                }
            }
        })
        .catch(error => {
            console.error('Error deleting conversation:', error);
        });
    };
    
    // Filter conversations based on search
    function filterConversations() {
        const searchTerm = conversationSearch.value.toLowerCase();
        const filtered = conversations.filter(conv => 
            conv.preview.toLowerCase().includes(searchTerm)
        );
        displayConversationList(filtered);
    }
    
    // Toggle sidebar collapse on desktop
    function toggleSidebarCollapse() {
        conversationSidebar.classList.toggle('collapsed');
        const isCollapsed = conversationSidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }
    
    // Toggle sidebar on mobile
    function toggleMobileSidebar() {
        conversationSidebar.classList.toggle('active');
        sidebarBackdrop.classList.toggle('active');
    }
    
    // Close mobile sidebar
    function closeMobileSidebar() {
        conversationSidebar.classList.remove('active');
        sidebarBackdrop.classList.remove('active');
    }
    
    // Clear chat display
    function clearChatDisplay() {
        chatBox.innerHTML = '';
        showWelcomeMessage();
    }
    
    // Show welcome message
    function showWelcomeMessage() {
        if (!chatBox.querySelector('.welcome-message')) {
            chatBox.innerHTML = `
                <div class="welcome-message">
                    <div class="bot-avatar">🤖</div>
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
            `;
        }
    }
    
    // Helper functions
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 3600000) { // Less than 1 hour
            return Math.floor(diff / 60000) + ' menit lalu';
        } else if (diff < 86400000) { // Less than 1 day
            return Math.floor(diff / 3600000) + ' jam lalu';
        } else if (diff < 604800000) { // Less than 1 week
            return Math.floor(diff / 86400000) + ' hari lalu';
        } else {
            return date.toLocaleDateString('id-ID');
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});