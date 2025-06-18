-- Add indexes to improve chatbot conversation performance

-- Composite index for faster conversation loading
ALTER TABLE chat_conversations 
ADD INDEX idx_user_conversation_created (user_id, conversation_id, created_at);

-- Index for faster conversation session lookups
ALTER TABLE chat_conversation_sessions 
ADD INDEX idx_user_active (user_id, is_active, last_message_at);

-- Full-text index for message search (if needed in future)
-- Note: This requires the table to use MyISAM or InnoDB with fulltext support
-- ALTER TABLE chat_conversations ADD FULLTEXT(message);