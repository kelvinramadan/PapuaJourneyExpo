## 1. AI Chatbot dengan RAG (Retrieval-Augmented Generation)

### a. Deskripsi
Fitur AI Chatbot yang berfungsi sebagai tour guide virtual untuk memberikan informasi pariwisata Papua kepada pengguna. Chatbot ini menggunakan teknologi RAG (Retrieval-Augmented Generation) yang mengkombinasikan sistem temu balik informasi (retrieval) dengan model bahasa generatif untuk memberikan respons yang akurat dan kontekstual tentang destinasi wisata, kuliner, budaya, dan transportasi di Papua.

Chatbot berperan sebagai "Papua Journey" atau "PJ", seorang tour guide ramah yang mengenal berbagai destinasi wisata di Papua. Saat ini database lengkap baru tersedia untuk area Jayapura, namun chatbot dapat memberikan informasi umum untuk daerah Papua lainnya.

### b. Input
Chatbot menerima beberapa jenis input:

1. **Pertanyaan Pengguna (User Query)**
   - Format: Teks dalam bahasa Indonesia
   - Contoh: "Apa saja tempat wisata di Jayapura?", "Bagaimana cara ke Danau Sentani?"
   - Validasi: Sistem memeriksa apakah pertanyaan berkaitan dengan Papua menggunakan keyword matching

2. **Riwayat Percakapan (Conversation History)**
   - Format: Array JSON berisi maksimal 5 turn percakapan terakhir
   - Struktur: `[{"user": "pertanyaan", "assistant": "jawaban"}, ...]`
   - Encoding: Base64 untuk keamanan saat passing ke command line

3. **Session Data**
   - User ID dari session PHP
   - Conversation ID (format UUID) untuk tracking percakapan
   - Message count untuk statistik

### c. Processing
Proses pemrosesan menggunakan pipeline RAG dengan langkah-langkah berikut:

1. **Pre-processing & Validasi**
   - Cek apakah user sudah login (session validation)
   - Generate conversation ID jika belum ada
   - Simpan pesan user ke database (tabel `chat_conversations`)

2. **Intent Detection**
   - Deteksi sapaan awal menggunakan pattern matching
   - Validasi apakah pertanyaan berkaitan dengan Papua menggunakan keyword list
   - Redirect halus untuk pertanyaan di luar topik

3. **Embedding & Retrieval**
   - **Embedding Generation**: Menggunakan Google Gemini embedding model (`models/embedding-001`)
   - **Vector Search**: Query ke ChromaDB (berjalan di Docker port 8000) untuk mencari dokumen relevan
   - **Retrieval**: Mengambil 3 dokumen paling relevan berdasarkan similarity score

4. **Context Building**
   - Memformat dokumen yang ditemukan sebagai knowledge context
   - Menambahkan riwayat percakapan untuk konteks
   - Membangun prompt dengan persona tour guide Papua

5. **Response Generation**
   - Model: Google Gemini 2.5 Flash dengan konfigurasi:
     - Temperature: 0.9 (respons lebih natural)
     - Top-k: 50 (variasi kata lebih banyak)
     - Top-p: 0.95 (probabilitas kumulatif)
     - Max tokens: 2048
   - Prompt engineering dengan karakter tour guide yang ramah dan informatif

6. **Post-processing**
   - Simpan respons bot ke database
   - Update conversation history (keep last 5 turns)
   - Handle Unicode/emoji dengan safe printing
   - Return JSON response dengan conversation ID

### d. Output
Chatbot menghasilkan beberapa jenis output:

1. **Respons Utama**
   - Format: JSON dengan struktur `{"reply": "teks_respons", "conversation_id": "uuid"}`
   - Konten: Jawaban dalam bahasa Indonesia yang natural dan informatif
   - Gaya: Ramah seperti tour guide, menggunakan kata lokal (pace/mace), emoji secukupnya
   - Markdown: Digunakan untuk formatting (bold, italic, list) agar mudah dibaca

2. **Tipe Respons Berdasarkan Konteks**
   - **Sapaan**: Respons hangat dengan tawaran eksplor Papua
   - **Info Jayapura**: Detail dari database + tips personal
   - **Info Papua Lain**: Penjelasan jujur tentang keterbatasan data + info umum
   - **Di Luar Topik**: Redirect halus ke topik wisata Papua

3. **Data Persistence**
   - Penyimpanan ke tabel `chat_conversations` dengan fields:
     - conversation_id (UUID)
     - user_id (integer)
     - message_type (user/bot)
     - message (text)
     - created_at (timestamp)
   - Update tabel `chat_conversation_sessions` untuk tracking

4. **Error Handling**
   - Error message dalam format JSON untuk konsistensi
   - Fallback message: "Maaf, terjadi sedikit kendala di sistem. Coba tanyakan lagi ya."
   - Logging ke file `chatbot_debug.log` untuk debugging

### e. Data Sources & Infrastructure

1. **Knowledge Base**
   - Lokasi: `users/chatbot/data/jayapura/`
   - Format: JSON files (destinations.json, cuisine.json, culture.json, transportation.json)
   - Konten: Informasi detail tentang wisata, kuliner, budaya, dan transportasi Jayapura

2. **Vector Database**
   - ChromaDB berjalan dalam Docker container
   - Collection: "papua_journey_expo"
   - Embedding dimension: Sesuai Google Gemini embedding model
   - Indexing: Dilakukan via `embed.py` setelah update data

3. **Dependencies**
   - google-generativeai: Untuk embedding dan text generation
   - chromadb: Vector database untuk similarity search
   - python-dotenv: Environment variable management
   - PHP 8.0.30: Backend integration
   - MariaDB: Persistent storage untuk chat history
