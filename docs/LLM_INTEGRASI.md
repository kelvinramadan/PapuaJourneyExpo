# BAB VIII
# INTEGRASI LARGE LANGUAGE MODEL (LLM)

## A. Justifikasi Integrasi LLM

### 1. Alasan penggunaan: Meningkatkan Pengalaman Pengguna dalam Eksplorasi Pariwisata Papua

PapuaJourneyExpo mengintegrasikan LLM untuk mengatasi beberapa tantangan kritis:
- **Aksesibilitas Informasi**: Menyediakan asisten virtual yang dapat menjawab pertanyaan wisatawan tentang destinasi, kuliner, budaya, dan transportasi di Papua secara real-time
- **Personalisasi Interaksi**: Memberikan respons yang disesuaikan dengan kebutuhan spesifik setiap pengguna
- **Scalability**: Melayani banyak pengguna secara bersamaan tanpa mengurangi kualitas layanan
- **Edukasi Budaya**: Membantu wisatawan memahami konteks budaya dan adat istiadat Papua dengan cara yang mudah dipahami

### 2. Value yang ditambahkan: Transformasi Pengalaman Perencanaan Wisata

- **Asisten Wisata 24/7**: Chatbot tersedia kapan saja untuk membantu perencanaan perjalanan
- **Informasi Lokal yang Kaya**: Mengakses database komprehensif tentang destinasi wisata, kuliner, budaya, dan transportasi Jayapura
- **Interaksi Natural**: Menggunakan bahasa Indonesia yang natural dan ramah, dengan sentuhan lokal Papua
- **Rekomendasi Kontekstual**: Memberikan saran berdasarkan preferensi dan pertanyaan pengguna
- **Kontinuitas Percakapan**: Menyimpan riwayat chat untuk pengalaman yang lebih personal

### 3. Inovasi yang dihasilkan: RAG (Retrieval-Augmented Generation) untuk Pariwisata Lokal

- **Hybrid AI System**: Menggabungkan kekuatan LLM dengan database lokal yang terstruktur
- **Akurasi Informasi Tinggi**: RAG memastikan informasi yang diberikan akurat dan up-to-date dari database lokal
- **Multilingual Support**: Potensi untuk ekspansi ke bahasa daerah Papua dan bahasa asing
- **Scalable Architecture**: Mudah diperluas untuk mencakup destinasi Papua lainnya
- **Cultural Preservation**: Membantu melestarikan dan mempromosikan budaya Papua melalui teknologi modern

## B. Pemilihan LLM

### 1. Model yang digunakan: Google Gemini 2.5 Flash

Sistem menggunakan Google Gemini 2.5 Flash sebagai model generasi teks utama dengan konfigurasi:
- Temperature: 0.9 (untuk respons lebih natural dan bervariasi)
- Top-k: 50 (variasi kata yang lebih luas)
- Top-p: 0.95 (probabilitas kumulatif pemilihan token)
- Max output tokens: 2048

### 2. Justifikasi pemilihan: Keseimbangan Performa dan Biaya

- **Kecepatan Respons**: Gemini 2.5 Flash dioptimalkan untuk respons cepat, cocok untuk chatbot real-time
- **Bahasa Indonesia**: Performa sangat baik dalam memahami dan menghasilkan teks Bahasa Indonesia
- **Cost-Effective**: Biaya per token yang kompetitif untuk aplikasi komersial
- **Embedding Model**: Menggunakan 'models/embedding-001' untuk vector search yang efisien
- **API Stability**: Google AI Platform memberikan uptime dan reliability yang tinggi

### 3. API/Service: Google AI (Generative AI) API

- **Primary API**: Google Generative AI untuk text generation
- **Embedding API**: Google embedding-001 untuk vector embeddings
- **Vector Database**: ChromaDB (self-hosted via Docker) untuk similarity search
- **Integration Method**: Python SDK (google-generativeai)

## C. Fitur yang Menggunakan LLM

| **Fitur** | **Fungsi LLM** | **Input** | **Output** | **Benefit** |
|-----------|----------------|-----------|------------|-------------|
| AI Tour Guide Chatbot | Natural language understanding & generation dengan konteks lokal Papua | Pertanyaan pengguna dalam Bahasa Indonesia tentang wisata Papua | Respons informatif dengan rekomendasi destinasi, kuliner, budaya, transportasi | Pengalaman tour guide virtual yang personal dan informatif |
| RAG-based Information Retrieval | Semantic search dan context-aware response generation | Query pengguna + embedded knowledge base (JSON data) | Informasi akurat dari database lokal yang diperkaya dengan penjelasan natural | Akurasi tinggi dengan informasi terkini dari database lokal |
| Conversation Memory | Context retention untuk multi-turn conversation | Riwayat percakapan (5 turn terakhir) + pertanyaan baru | Respons yang mempertimbangkan konteks percakapan sebelumnya | Pengalaman percakapan yang koheren dan personal |
| Cultural Context Integration | Penyampaian informasi dengan nuansa budaya lokal | Pertanyaan umum wisata | Respons dengan sentuhan lokal (kata sapaan Papua, referensi budaya) | Memperkenalkan budaya Papua secara natural |

## D. Implementasi LLM

### 1. Arsitektur Integrasi

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   User Browser  │────▶│  PHP Backend     │────▶│  Python RAG     │
│  (chatbot.js)   │◀────│(chatbot_process) │◀────│  (rag_query.py) │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                │                           │
                                ▼                           ▼
                        ┌───────────────┐          ┌─────────────────┐
                        │  MySQL DB     │          │   ChromaDB      │
                        │ (chat_history)│          │ (Vector Store)  │
                        └───────────────┘          └────────┬────────┘
                                                            │
                                                            ▼
                                                   ┌─────────────────┐
                                                   │  Gemini API     │
                                                   │ (Generation &   │
                                                   │  Embedding)     │
                                                   └─────────────────┘
```

**Alur Proses:**
1. User mengirim pertanyaan melalui interface web (chatbot.js)
2. PHP backend (chatbot_process.php) menerima request dan menyimpan ke database
3. PHP memanggil Python script (rag_query.py) dengan shell_exec
4. Python script melakukan:
   - Embedding generation untuk query user
   - Similarity search di ChromaDB
   - Context retrieval dari matched documents
   - Response generation dengan Gemini API
5. Respons dikembalikan ke user dan disimpan di database

### 2. Prompt Engineering

```
Contoh prompt yang digunakan:

Role: Tour guide Papua yang ramah dan berpengalaman
- Nama: "Papua Journey" atau "PJ"
- Karakteristik: Ramah seperti teman ngobrol, tapi tetap informatif
- Gaya bahasa: Sesekali pakai kata lokal (pace/mace), antusias, responsif

Context: 
- Database coverage: SELURUH PAPUA (saat ini data lengkap untuk JAYAPURA)
- Riwayat percakapan: 5 turn terakhir untuk kontinuitas
- Knowledge base: Data JSON tentang destinasi, kuliner, budaya, transportasi

Task:
- Sapaan hangat untuk greeting
- Jawab detail untuk pertanyaan Jayapura (dari database)
- Jelaskan keterbatasan data untuk kota Papua lain
- Redirect halus untuk topik di luar Papua
- Gunakan Markdown untuk keterbacaan

Format:
- Natural dan conversational
- Emoji seperlunya (😊🌊🏔️🍜)
- Variasi respons (hindari template kaku)
- Panjang jawaban disesuaikan dengan pertanyaan
```

**Teknik Prompt Engineering yang Diterapkan:**

1. **Role Definition**: Mendefinisikan persona chatbot sebagai tour guide lokal yang ramah
2. **Context Injection**: Menyertakan data relevan dari vector search dan conversation history
3. **Output Formatting**: Instruksi spesifik untuk gaya bahasa dan struktur respons
4. **Guardrails**: Batasan topik dan handling untuk out-of-scope queries
5. **Cultural Nuancing**: Integrasi kata-kata lokal dan referensi budaya Papua
6. **Dynamic Adaptation**: Respons disesuaikan dengan mood dan konteks pertanyaan user