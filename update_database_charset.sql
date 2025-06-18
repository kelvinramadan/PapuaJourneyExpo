-- Update database charset to support emojis
ALTER DATABASE omaki_db CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- If tables already exist, update their charset
ALTER TABLE chat_conversations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE chat_conversation_sessions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Specifically update the message column to ensure emoji support
ALTER TABLE chat_conversations MODIFY `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;