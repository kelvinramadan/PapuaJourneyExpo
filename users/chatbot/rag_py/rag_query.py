import sys
# Patch SQLite before importing chromadb
try:
    __import__('pysqlite3')
    sys.modules['sqlite3'] = sys.modules.pop('pysqlite3')
except ImportError:
    pass  # If pysqlite3 is not available, continue with default sqlite3

import google.generativeai as genai
from dotenv import load_dotenv
import os
import chromadb
import json

# Set UTF-8 encoding for output to handle emojis
if sys.platform.startswith('win'):
    import codecs
    sys.stdout = codecs.getwriter('utf-8')(sys.stdout.buffer, 'strict')

# Load environment variables from .env file
load_dotenv()

# Configure the generative AI model
try:
    genai.configure(api_key=os.getenv("GEMINI_API_KEY"))
    text_embedding_model = 'models/embedding-001'
    generation_model = genai.GenerativeModel('gemini-2.5-flash')
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

def generate_response(query, passages, conversation_history=None):
    """Generates a response using the retrieved passages and conversation history."""
    if not passages:
        passages = []

    knowledge_context = '\n\n'.join([f"[{i+1}] {doc}" for i, doc in enumerate(passages)]) if passages else 'Tidak ada informasi spesifik ditemukan dalam database.'
    
    # Format conversation history if available
    history_context = ""
    if conversation_history:
        history_context = "\n\nRIWAYAT PERCAKAPAN SEBELUMNYA:\n"
        for turn in conversation_history:
            if turn.get('user'):
                history_context += f"Pengguna: {turn['user']}\n"
            if turn.get('assistant'):
                history_context += f"Anda: {turn['assistant']}\n"
        history_context += "\n"

    prompt = f"""Anda adalah "Papua Journey", seorang tour guide virtual yang ramah dan sangat informatif untuk wilayah Jayapura, Papua.

ATURAN PENTING:
1. SELALU jawab dalam Bahasa Indonesia dengan gaya yang ramah dan interaktif.
2. Jika pengguna menyapa (misal: "Halo"), balas sapaan itu dengan hangat dan tanyakan apa yang bisa Anda bantu terkait wisata di Jayapura.
3. FOKUS UTAMA Anda adalah memberikan informasi tentang destinasi wisata, transportasi, budaya, dan kuliner di Jayapura.
4. Jika ditanya tentang topik di luar wisata Jayapura, jawaban harus tetap sopan dan arahkan kembali ke topik wisata Jayapura.
5. Gunakan informasi dari "KONTEKS PENGETAHUAN" di bawah ini sebagai sumber utama.
6. Jika konteks tidak menyediakan jawaban, katakan dengan jujur bahwa Anda belum memiliki informasi detailnya.

FORMAT JAWABAN - GUNAKAN MARKDOWN:
- Gunakan format Markdown untuk membuat jawaban lebih menarik dan terstruktur
- Gunakan **bold** untuk judul/nama tempat penting
- Gunakan emoji yang relevan (🏝️, 🍽️, 🚗, 📍, ⭐, 🎯, 💡, etc.)
- Gunakan bullet points (-) atau numbering (1.) untuk daftar
- Gunakan > untuk highlight informasi penting
- Gunakan ## untuk judul utama dan ### untuk sub judul
- Pisahkan informasi dalam section yang jelas

CONTOH FORMAT MARKDOWN:
👋 **Halo! Selamat datang di Papua Journey!**

## 🏝️ **Destinasi Wisata Jayapura**

### **Pantai Base G** 📍
- **Lokasi**: Jayapura
- **Aktivitas**: Berenang, snorkeling, foto sunset
- **Waktu terbaik**: Pagi hari (06:00-10:00)

## 🚗 **Transportasi**

### **Mobil Sewaan**
- **Biaya**: Rp 300.000/hari
- **Durasi**: 30 menit dari pusat kota
- **Keterangan**: Paling nyaman dan fleksibel

> 💡 **Tips**: Datang saat pagi untuk pemandangan terbaik dan hindari keramaian!

---

Apakah ada yang ingin ditanyakan lagi tentang wisata Jayapura? 😊
{history_context}
KONTEKS PENGETAHUAN:
{knowledge_context}

Berdasarkan aturan di atas dan dengan memperhatikan riwayat percakapan sebelumnya, jawab pertanyaan pengguna berikut dengan format Markdown yang menarik:
{query}
"""
    
    try:
        response = generation_model.generate_content(prompt)
        return response.text.strip()
            
    except Exception as e:
        return f"Maaf, terjadi kesalahan sistem: {str(e)}"

def safe_print(text):
    """Safely print text with Unicode support, fallback to ASCII if needed."""
    try:
        print(text)
    except UnicodeEncodeError:
        # Remove emojis and special Unicode characters if encoding fails
        import re
        # Remove emojis and other non-ASCII characters
        clean_text = re.sub(r'[^\x00-\x7F]+', '', text)
        print(clean_text)
    except Exception as e:
        print(f"Error printing response: {e}", file=sys.stderr)

def main():
    """Main function to handle the RAG query process."""
    if len(sys.argv) < 2:
        print("Usage: python rag_query.py \"<your_question>\" [base64_encoded_history]", file=sys.stderr)
        sys.exit(1)
    
    user_query = sys.argv[1]
    
    # Parse conversation history if provided
    conversation_history = None
    if len(sys.argv) > 2:
        try:
            import base64
            history_b64 = sys.argv[2]
            history_json = base64.b64decode(history_b64).decode('utf-8')
            conversation_history = json.loads(history_json)
        except Exception as e:
            print(f"Error parsing conversation history: {e}", file=sys.stderr)
            conversation_history = None

    if is_initial_greeting(user_query):
        final_answer = generate_response(user_query, [], conversation_history)
        safe_print(final_answer)
        sys.exit(0)

    if not is_jayapura_related(user_query):
        # Check if previous conversation context makes it related
        if conversation_history:
            # If we have history, it might be a follow-up question
            # Let the model decide based on context
            pass
        else:
            safe_print("Maaf, saya adalah pemandu wisata khusus untuk Jayapura dan tidak punya informasi tentang itu. Apakah ada yang bisa saya bantu seputar destinasi atau kuliner di Jayapura?")
            sys.exit(0)

    try:
        # Use environment variable for ChromaDB host, default to localhost
        chromadb_host = os.getenv('CHROMADB_HOST', 'localhost')
        chromadb_port = int(os.getenv('CHROMADB_PORT', '8000'))
        client = chromadb.HttpClient(host=chromadb_host, port=chromadb_port)
        collection = client.get_collection("papua_journey_expo")
    except Exception as e:
        print(f"Error connecting to ChromaDB: {e}", file=sys.stderr)
        sys.exit(1)

    query_embedding = get_embedding(user_query)
    if not query_embedding:
        print("Could not generate query embedding.", file=sys.stderr)
        sys.exit(1)

    passages = find_best_passages(query_embedding, collection)
    final_answer = generate_response(user_query, passages, conversation_history)
    safe_print(final_answer)

if __name__ == "__main__":
    main()
