import google.generativeai as genai
from dotenv import load_dotenv
import os
import chromadb
import sys
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
    generation_config = {
        "temperature": 0.9,  # Dinaikkan untuk respons lebih natural dan bervariasi
        "top_k": 50,        # Dinaikkan untuk lebih banyak variasi kata
        "top_p": 0.95,      # Probabilitas kumulatif untuk pemilihan token (default 0.95)
        "max_output_tokens": 2048,  # Maksimal token output
    }
    generation_model = genai.GenerativeModel('gemini-2.5-flash', generation_config=generation_config)
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
    greetings = ['halo', 'hai', 'hi', 'hello', 'hey', 'selamat pagi', 'selamat siang', 'selamat sore', 'selamat malam', 'apa kabar', 'pagi', 'siang', 'sore', 'malam', 'bro', 'sis', 'kak', 'min', 'gan']
    lower_query = query.lower().strip()
    # Check exact match or if query starts with greeting
    return lower_query in greetings or any(lower_query.startswith(g) for g in greetings)

def is_papua_related(query):
    """Checks if the query is related to Papua tourism."""
    papua_keywords = [
      # General Papua
      'papua', 'wisata', 'destinasi', 'kuliner', 'makanan', 'transport', 'budaya', 'adat',
      'pantai', 'gunung', 'danau', 'tour', 'hotel', 'penginapan', 'jalan-jalan',
      # Jayapura specific
      'jayapura', 'sentani', 'danau sentani', 'base g', 'youtefa',
      'teletubbies', 'hamadi', 'abepura', 'waena', 'entrop',
      # Other Papua cities (for future expansion)
      'wamena', 'merauke', 'sorong', 'manokwari', 'biak', 'nabire', 'timika',
      'raja ampat', 'baliem', 'korowai'
    ]
    lower_query = query.lower()
    return any(keyword in lower_query for keyword in papua_keywords)

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

    prompt = f"""Kamu adalah seorang tour guide Papua yang ramah dan berpengalaman. Namamu bisa dipanggil "Papua Journey" atau "PJ". Kamu sangat mengenal berbagai destinasi wisata di Papua dan suka berbagi cerita menarik.

KARAKTER & GAYA BICARA:
- Ramah seperti teman ngobrol, tapi tetap informatif
- Sesekali pakai kata-kata lokal yang umum (misal: "pace" untuk kakak laki-laki, "mace" untuk kakak perempuan)
- Antusias saat cerita tempat favorit
- Kadang share pengalaman pribadi atau cerita wisatawan lain
- Responsif dengan mood pengguna
- HINDARI jawaban yang terlalu formal atau kaku seperti template

AREA COVERAGE:
- Kamu tour guide untuk SELURUH PAPUA, bukan hanya satu kota
- SAAT INI database kamu baru punya info lengkap untuk: JAYAPURA
- Untuk kota lain (Wamena, Merauke, Sorong, Raja Ampat, dll), jelaskan dengan jujur kalau info detailnya belum ada di database, tapi tetap bisa share info umum yang kamu tahu

CARA MENJAWAB YANG NATURAL:
1. Sapaan → Balas hangat & personal, bisa tanya mau eksplor Papua bagian mana
2. Pertanyaan Jayapura → Jawab detail dari database + tips personal
3. Pertanyaan kota Papua lain → Jelaskan kalau database detail belum ada, tapi share info umum yang helpful
4. Di luar topik → Redirect halus ke wisata Papua
5. Pakai Markdown secukupnya untuk keterbacaan

CONTOH RESPONS:
Query: "Ada info wisata di Wamena?"
Jawab: "Wamena? Wah, lembah Baliem memang indah banget! Sayangnya database saya belum lengkap untuk Wamena, tapi yang saya tau di sana ada Festival Lembah Baliem yang terkenal, pemandangan pegunungan yang spektakuler, dan budaya suku Dani yang unik. Untuk sekarang, saya punya info lengkap wisata Jayapura nih, mau saya ceritain?"

VARIASI PENTING:
- Jangan selalu mulai dengan pola yang sama
- Sesuaikan panjang jawaban dengan pertanyaan
- Tetap helpful meski database terbatas

EMOJI: Pakai seperlunya aja (😊🌊🏔️🍜 dll), jangan berlebihan

{history_context}
INFO DATABASE:
{knowledge_context}

PERTANYAAN: {query}

Jawab dengan natural seperti tour guide Papua beneran yang lagi ngobrol santai!"""
    
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

    if not is_papua_related(user_query):
        # Check if previous conversation context makes it related
        if conversation_history:
            # If we have history, it might be a follow-up question
            # Let the model decide based on context
            pass
        else:
            # Variasi respons untuk topik di luar Papua
            out_of_topic_responses = [
                "Wah, kalau soal itu saya kurang paham. Tapi kalau mau tau tempat wisata keren di Papua, saya jagonya! Mau eksplor Papua bagian mana?",
                "Hmm, saya tour guide Papua nih. Btw, sudah pernah ke Raja Ampat atau Danau Sentani belum? Dua-duanya surga banget!",
                "Aduh maaf, saya spesialisnya wisata Papua aja. Mau saya ceritain destinasi hits di Papua? Ada pantai, gunung, danau, semuanya lengkap!",
                "Sori ya, fokus saya di wisata Papua. Tapi percaya deh, Papua punya segudang tempat amazing yang wajib dikunjungi!"
            ]
            import random
            safe_print(random.choice(out_of_topic_responses))
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
    final_answer = generate_response(user_query, passages, conversation_history)
    safe_print(final_answer)

if __name__ == "__main__":
    main()
