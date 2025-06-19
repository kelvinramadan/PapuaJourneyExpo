#!/bin/bash
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
while ! mysqladmin ping -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" --silent; do
    sleep 1
done
echo "MySQL is ready!"

# Wait for ChromaDB to be ready
echo "Waiting for ChromaDB to be ready..."
CHROMADB_HOST="${CHROMADB_HOST:-chromadb}"
CHROMADB_PORT="${CHROMADB_PORT:-8000}"

max_attempts=30
attempt=0
while [ $attempt -lt $max_attempts ]; do
    if curl -s "http://${CHROMADB_HOST}:${CHROMADB_PORT}/api/v1/heartbeat" > /dev/null 2>&1; then
        echo "ChromaDB is ready!"
        break
    fi
    echo "Waiting for ChromaDB... (attempt $((attempt+1))/$max_attempts)"
    sleep 2
    attempt=$((attempt+1))
done

if [ $attempt -eq $max_attempts ]; then
    echo "Warning: ChromaDB did not become ready in time, continuing anyway..."
fi

# Database is configured via environment variables in config/database.php

# Initialize ChromaDB embeddings if needed
if [ ! -f "/var/www/html/.chromadb_initialized" ]; then
    echo "Initializing ChromaDB embeddings..."
    
    # Activate Python virtual environment
    source /opt/venv/bin/activate
    
    # Change to the rag_py directory using absolute path
    cd /var/www/html/users/chatbot/rag_py
    
    # Set environment variables for ChromaDB connection
    export CHROMADB_HOST="${CHROMADB_HOST}"
    export CHROMADB_PORT="${CHROMADB_PORT}"
    
    # Run embed.py with error handling
    if python3 /var/www/html/users/chatbot/rag_py/embed.py; then
        touch /var/www/html/.chromadb_initialized
        echo "ChromaDB embeddings initialized successfully!"
    else
        echo "Warning: Failed to initialize ChromaDB embeddings. The chatbot may not work properly."
    fi
fi

# Execute the main command
exec "$@"