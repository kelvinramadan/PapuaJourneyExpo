import google.generativeai as genai
from dotenv import load_dotenv
import os
import chromadb
import sys
import json

# Load environment variables from .env file
load_dotenv()

# Configure the generative AI model
try:
    genai.configure(api_key=os.getenv("GEMINI_API_KEY"))
    text_embedding_model = 'models/embedding-001'
    generation_model = genai.GenerativeModel('gemini-1.5-flash-latest')
except Exception as e:
    print(f"Error configuring Generative AI: {e}", file=sys.stderr)
    sys.exit(1)

def get_embedding(text):
    """Generates an embedding for the given text."""
    try:
        result = genai.embed_content(
            model=text_embedding_model,
            content=text,
            task_type="retrieval_query"
        )
        return result['embedding']
    except Exception as e:
        print(f"Error generating embedding: {e}", file=sys.stderr)
        return None

def find_best_passages(query_embedding, collection, n_results=3):
    """Finds the most relevant passages in the collection."""
    try:
        results = collection.query(
            query_embeddings=[query_embedding],
            n_results=n_results
        )
        return results['documents'][0] if results and results['documents'] else []
    except Exception as e:
        print(f"Error querying ChromaDB: {e}", file=sys.stderr)
        return []

def is_initial_greeting(query):
    """Checks for common greetings."""
    greetings = ['halo', 'hai', 'hi', 'selamat pagi', 'selamat siang', 'selamat sore', 'selamat malam', 'apa kabar']
    lower_query = query.lower().strip()
    return lower_query in greetings

def is_jayapura_related(query):
    """Checks if the query is related to Jayapura."""
    jayapura_keywords = [
      'jayapura', 'papua', 'sentani', 'danau sentani', 'base g', 'youtefa',
      'teletubbies', 'hamadi', 'abepura', 'waena', 'entrop',
      'wisata', 'destinasi', 'kuliner', 'makanan', 'transport', 'budaya', 'adat'
    ]
    lower_query = query.lower()
    return any(keyword in lower_query for keyword in jayapura_keywords)

def generate_response(query, passages):
    """Generates a response using the retrieved passages."""
    if not passages:
        passages = []

    knowledge_context = '\n\n'.join([f"[{i+1}] {doc}" for i, doc in enumerate(passages)]) if passages else 'Tidak ada informasi spesifik ditemukan dalam database.'

    prompt = f"""Anda adalah "PapuaJourneyExpo", seorang tour guide virtual yang ramah dan sangat informatif untuk wilayah Jayapura, Papua.

ATURAN PENTING:
1. SELALU jawab dalam Bahasa Indonesia dengan gaya yang ramah dan interaktif.
2. Jika pengguna menyapa (misal: "Halo"), balas sapaan itu dengan hangat dan tanyakan apa yang bisa Anda bantu terkait wisata di Jayapura.
3. FOKUS UTAMA Anda adalah memberikan informasi tentang destinasi wisata, transportasi, budaya, dan kuliner di Jayapura.
4. Jika ditanya tentang topik di luar wisata Jayapura, jawab dengan sopan bahwa Anda tidak memiliki informasi tersebut dan arahkan kembali percakapan ke wisata Jayapura. Contoh: "Maaf, saya adalah pemandu wisata khusus untuk Jayapura dan tidak punya informasi tentang itu. Apakah ada yang bisa saya bantu seputar destinasi atau kuliner di Jayapura?"
5. Gunakan informasi dari "KONTEKS PENGETAHUAN" di bawah ini sebagai sumber utama.
6. Jika konteks tidak menyediakan jawaban, katakan dengan jujur bahwa Anda belum memiliki informasi detailnya.
7. Jaga agar jawaban tetap ringkas dan padat informasi untuk menghemat penggunaan API, namun tetap jelas dan bermanfaat.

KONTEKS PENGETAHUAN:
{knowledge_context}

Berdasarkan aturan di atas, jawab pertanyaan pengguna berikut:
{query}
"""
    
    try:
        response = generation_model.generate_content(prompt)
        return response.text
    except Exception as e:
        return f"Error generating response: {e}"

def main():
    """Main function to handle the RAG query process."""
    if len(sys.argv) < 2:
        print("Usage: python rag_query.py \"<your_question>\"", file=sys.stderr)
        sys.exit(1)
    
    user_query = sys.argv[1]

    if is_initial_greeting(user_query):
        final_answer = generate_response(user_query, [])
        print(final_answer)
        sys.exit(0)

    if not is_jayapura_related(user_query):
        print("Maaf, saya adalah pemandu wisata khusus untuk Jayapura dan tidak punya informasi tentang itu. Apakah ada yang bisa saya bantu seputar destinasi atau kuliner di Jayapura?")
        sys.exit(0)

    try:
        client = chromadb.HttpClient(host='localhost', port=8000)
        collection = client.get_collection("papua_journey_expo")
    except Exception as e:
        print(f"Error connecting to ChromaDB: {e}", file=sys.stderr)
        sys.exit(1)

    query_embedding = get_embedding(user_query)
    if not query_embedding:
        print("Could not generate query embedding.", file=sys.stderr)
        sys.exit(1)

    passages = find_best_passages(query_embedding, collection)
    final_answer = generate_response(user_query, passages)
    print(final_answer)

if __name__ == "__main__":
    main()
