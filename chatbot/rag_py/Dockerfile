# Use an official Python runtime as a parent image
FROM python:3.9-slim

# Set the working directory in the container
WORKDIR /app

# Install ChromaDB and other dependencies
RUN pip install chromadb

# Expose the port ChromaDB runs on
EXPOSE 8000

# Command to run ChromaDB
CMD ["chroma", "run", "--host", "0.0.0.0", "--port", "8000"]
