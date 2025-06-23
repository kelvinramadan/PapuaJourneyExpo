# 🤖 RAG Pipeline untuk Chatbot PapuaJourneyExpo (Versi Sederhana)

Pipeline ini menggunakan teknologi Retrieval-Augmented Generation (RAG) untuk membuat chatbot yang cerdas tentang Papua. Setup ini menggunakan Docker hanya untuk ChromaDB, sementara script Python dijalankan di local.

## 📋 Persyaratan

Sebelum memulai, pastikan Anda sudah menginstall:
- 🐳 Docker
- 🐍 Python 3.9 atau lebih baru
- 🔑 API Key dari Google Gemini

## ⚙️ Persiapan Awal

### 1. Install Dependencies Python
Di folder `RAG_PY/`, jalankan:
```bash
pip install -r requirements.txt
```

### 2. Buat file `.env`
Buat file `.env` di folder `RAG_PY/` dan masukkan API key Anda:
```env
GEMINI_API_KEY=masukkan_api_key_gemini_anda_disini
```

## 🚀 Cara Menjalankan

### 1. Build Docker Image untuk ChromaDB
Di folder yang berisi Dockerfile, jalankan:
```bash
docker build -t chromadb-server .
```

### 2. Jalankan ChromaDB Container
```bash
docker run -d --name chromadb -p 8000:8000 chromadb-server
```

**Penjelasan:**
- `-d` → Menjalankan di background
- `--name chromadb` → Memberi nama container
- `-p 8000:8000` → Mapping port 8000

### 3. Muat Data ke ChromaDB
Buka terminal di folder `RAG_PY/` dan jalankan:
```bash
python embed.py
```

✅ Jika berhasil, akan muncul pesan: **"Embeddings saved to ChromaDB."**

### 4. Test Query Chatbot
Untuk test, jalankan:
```bash
python rag_query.py "Apa saja tempat wisata di Jayapura?"
```

## 🔄 Update Data RAG

⚠️ **PENTING**: Setiap kali Anda mengupdate data di folder `chatbot/data/jayapura`, Anda HARUS:

1. Pastikan ChromaDB container sedang berjalan
2. Jalankan ulang proses embedding:
   ```bash
   python embed.py
   ```

**Mengapa?** Karena ChromaDB perlu memproses dan menyimpan embedding (representasi vektor) dari data terbaru agar chatbot bisa memberikan informasi yang akurat.

## 🏗️ Cara Kerja Sistem

### Alur Proses:
1. **ChromaDB berjalan di Docker** → Database vektor yang menyimpan embedding
2. **embed.py** → Membaca data JSON dan menyimpannya ke ChromaDB
3. **rag_query.py** → Menerima pertanyaan user dan mencari di ChromaDB
4. **Gemini AI** → Menghasilkan respons berdasarkan data yang ditemukan

### Koneksi ke ChromaDB:
Script Python terhubung ke ChromaDB melalui `http://localhost:8000`

## 🔧 Commands Penting

### Mengelola ChromaDB Container

**Cek status container:**
```bash
docker ps
```

**Stop container:**
```bash
docker stop chromadb
```

**Start container (jika sudah ada):**
```bash
docker start chromadb
```

**Hapus container:**
```bash
docker rm chromadb
```

**Cek log ChromaDB:**
```bash
docker logs chromadb
```

## 🔍 Troubleshooting

### Error: Connection refused to localhost:8000
- Pastikan container ChromaDB sedang berjalan: `docker ps`
- Cek apakah port 8000 tidak digunakan aplikasi lain

### Error: API Key not found
- Pastikan file `.env` ada di folder `RAG_PY/`
- Cek format API key sudah benar

### Error saat embed.py
- Pastikan ChromaDB container berjalan
- Cek struktur data JSON di folder `chatbot/data/jayapura`

### ChromaDB container tidak bisa start
- Port 8000 mungkin sudah digunakan, ganti port:
  ```bash
  docker run -d --name chromadb -p 8001:8000 chromadb-server
  ```
  Dan update port di script Python

## 📁 Struktur Folder

```
PapuaJourneyExpo/
├── Dockerfile             # Docker image untuk ChromaDB
├── RAG_PY/
│   ├── .env              # API Key (jangan di-commit!)
│   ├── requirements.txt  # Dependencies Python
│   ├── embed.py         # Script untuk embedding data
│   └── rag_query.py     # Script untuk query chatbot
└── chatbot/
    ├── chatbot_process.php
    └── data/
        └── jayapura/     # Data JSON tentang Papua
```

## 💡 Tips Penggunaan

1. **Persistent Data**: Data ChromaDB hilang jika container dihapus. Untuk persistent storage, tambahkan volume:
   ```bash
   docker run -d --name chromadb -p 8000:8000 -v chromadb_data:/chroma/chroma chromadb-server
   ```

2. **Multiple Collections**: Anda bisa membuat collection berbeda untuk setiap kategori (wisata, kuliner, budaya) di `embed.py`

3. **Monitoring**: Gunakan `docker stats chromadb` untuk monitor penggunaan resource

---

📝 **Catatan**: Setup ini lebih sederhana karena hanya ChromaDB yang di-containerize. Script Python berjalan di environment local Anda.