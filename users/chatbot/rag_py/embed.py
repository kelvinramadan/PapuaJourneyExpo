import google.generativeai as genai
from dotenv import load_dotenv
import os
import json
import chromadb

load_dotenv()

genai.configure(api_key=os.getenv("GEMINI_API_KEY"))

model = 'models/embedding-001'

def get_all_texts_from_json(directory):
    texts = []
    for filename in os.listdir(directory):
        if filename.endswith(".json"):
            filepath = os.path.join(directory, filename)
            with open(filepath, 'r', encoding='utf-8') as f:
                data = json.load(f)
                for key in data:
                    if isinstance(data[key], list):
                        for item in data[key]:
                            if isinstance(item, dict):
                                texts.append(json.dumps(item))
    return texts

# Use absolute path when running in Docker, relative path otherwise
if os.path.exists('/var/www/html'):
    # Running in Docker container
    data_directory = '/var/www/html/users/chatbot/data/jayapura'
else:
    # Running locally
    data_directory = '../data/jayapura'

documents = get_all_texts_from_json(data_directory)

if documents:
    result = genai.embed_content(
        model=model,
        content=documents,
        task_type="retrieval_document")

    # Use environment variables for ChromaDB connection
    chromadb_host = os.getenv('CHROMADB_HOST', 'localhost')
    chromadb_port = int(os.getenv('CHROMADB_PORT', '8000'))
    client = chromadb.HttpClient(host=chromadb_host, port=chromadb_port)
    collection = client.get_or_create_collection("papua_journey_expo")

    collection.add(
        embeddings=result['embedding'],
        documents=documents,
        ids=[f"doc_{i}" for i in range(len(documents))]
    )
    print("Embeddings saved to ChromaDB.")
else:
    print("No documents found to embed.")
