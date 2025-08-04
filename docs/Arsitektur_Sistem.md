# Dokumentasi Arsitektur Sistem - PapuaJourneyExpo

## 1. Arsitektur Sistem

### Gambaran Umum
PapuaJourneyExpo adalah aplikasi web pariwisata terpadu untuk Papua yang menggunakan arsitektur **Monolithic MVC (Model-View-Controller)** dengan pendekatan **Multi-User Portal**. Sistem ini dirancang untuk mengelola destinasi wisata, penginapan, marketplace UMKM, dan dilengkapi dengan AI chatbot berbasis RAG (Retrieval-Augmented Generation).

### Diagram Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────────────┐
│                            CLIENT LAYER                              │
├─────────────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐             │
│  │ User Portal  │  │ Admin Portal │  │ UMKM Portal  │             │
│  │  (index.php) │  │   (admin/)   │  │   (umkm/)    │             │
│  └──────────────┘  └──────────────┘  └──────────────┘             │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         APPLICATION LAYER                            │
├─────────────────────────────────────────────────────────────────────┤
│  ┌────────────────────────────────────────────────────────────┐    │
│  │                      PHP Application                         │    │
│  │  ┌─────────────┐  ┌──────────────┐  ┌──────────────────┐  │    │
│  │  │ Controllers │  │   Services   │  │     Helpers      │  │    │
│  │  │    (PHP)    │  │    (PHP)     │  │     (PHP)        │  │    │
│  │  └─────────────┘  └──────────────┘  └──────────────────┘  │    │
│  │  ┌────────────────────────────────────────────────────┐   │    │
│  │  │              Session Management                     │   │    │
│  │  │         (PHP Native Sessions)                      │   │    │
│  │  └────────────────────────────────────────────────────┘   │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                     │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │                    AI Chatbot Service                       │    │
│  │  ┌─────────────┐  ┌──────────────┐  ┌──────────────────┐  │    │
│  │  │  RAG Query  │  │  Gemini API  │  │    ChromaDB      │  │    │
│  │  │  (Python)   │  │  Integration │  │  (Vector Store)  │  │    │
│  │  └─────────────┘  └──────────────┘  └──────────────────┘  │    │
│  └────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────────┐
│                           DATA LAYER                                 │
├─────────────────────────────────────────────────────────────────────┤
│  ┌────────────────────────────────────────────────────────────┐    │
│  │                    MariaDB (MySQL)                          │    │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │    │
│  │  │  Users   │  │  Wisata  │  │   UMKM   │  │ Transaksi│  │    │
│  │  │  Tables  │  │  Tables  │  │  Tables  │  │  Tables  │  │    │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │    │
│  └────────────────────────────────────────────────────────────┘    │
│                                                                     │
│  ┌────────────────────────────────────────────────────────────┐    │
│  │                    File Storage                             │    │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │    │
│  │  │   Images     │  │   Payment    │  │   Review     │    │    │
│  │  │  (uploads/)  │  │   Proofs     │  │   Media      │    │    │
│  │  └──────────────┘  └──────────────┘  └──────────────┘    │    │
│  └────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────┘
```

### Komponen Arsitektur

#### 1. **Client Layer (Presentation Layer)**
   - **User Portal**: Interface publik untuk pengunjung dan pengguna terdaftar
   - **Admin Portal**: Dashboard administratif untuk pengelolaan konten dan sistem
   - **UMKM Portal**: Interface khusus untuk pelaku UMKM mengelola produk dan pesanan

#### 2. **Application Layer (Business Logic)**
   - **PHP Application Core**: Logika bisnis utama menggunakan PHP native
   - **Session Management**: Autentikasi dan otorisasi berbasis session PHP
   - **AI Chatbot Service**: Layanan chatbot terintegrasi dengan RAG pipeline

#### 3. **Data Layer (Persistence)**
   - **MariaDB Database**: Penyimpanan data terstruktur
   - **File Storage**: Penyimpanan file media dan dokumen

### Alur Data Sistem

1. **Request Flow**:
   ```
   Client → PHP Router → Controller → Service/Model → Database
   ```

2. **AI Chatbot Flow**:
   ```
   User Query → PHP Handler → Python RAG → ChromaDB/Gemini → Response
   ```

3. **Transaction Flow**:
   ```
   Cart → Checkout → Payment Upload → Admin Verification → Order Complete
   ```

## 2. Technology Stack

### a. Frontend
- **Core Technologies**:
  - **HTML5**: Struktur markup semantic
  - **CSS3**: Styling dengan custom properties untuk theming
  - **JavaScript (Vanilla)**: Interaktivitas client-side tanpa framework
  - **AJAX**: Komunikasi asinkron untuk fitur real-time

- **UI Components**:
  - Custom CSS dengan design system konsisten
  - Responsive design dengan mobile-first approach
  - CSS Grid dan Flexbox untuk layout
  - Custom properties untuk tema (warna primer: #536245, #DC9B11)

### b. Backend
- **Primary Language**: **PHP 8.0.30**
  - PHP Native (tanpa framework)
  - MySQLi extension untuk database connectivity
  - Session-based authentication
  - File upload handling native PHP

- **Secondary Language**: **Python 3.x**
  - Khusus untuk AI/ML pipeline
  - Integration dengan Google Gemini API
  - Vector embedding processing
  - RAG (Retrieval-Augmented Generation) implementation

### c. Database
- **Primary Database**: **MariaDB 10.4** (MySQL Compatible)
  - Database Name: `omaki_db`
  - Character Set: `utf8mb4` (mendukung emoji)
  - Storage Engine: InnoDB (default)
  
- **Vector Database**: **ChromaDB**
  - Digunakan untuk menyimpan embeddings
  - Berjalan dalam Docker container
  - Port: 8000

### d. Infrastructure & Hosting
- **Development Environment**: 
  - **XAMPP** (Apache + MariaDB + PHP)
  - Windows dengan WSL2 support
  - Local development setup

- **Container Technology**:
  - **Docker**: Untuk ChromaDB service
  - Container orchestration untuk AI services

### e. Other Tools & Services

#### AI/ML Stack:
- **Google Gemini API**: 
  - Model: `gemini-2.5-flash`
  - Digunakan untuk natural language processing
  - Text generation dan conversation handling

- **Embedding Model**: 
  - Google's `models/embedding-001`
  - Untuk konversi text ke vector embeddings

#### Development Tools:
- **Version Control**: Git (dengan GitHub integration)
- **Package Management**:
  - Python: pip dengan requirements.txt
  - JavaScript: Native modules (tanpa npm dalam produksi)

#### Third-party Services:
- **Payment Gateway**: Manual verification system (tidak terintegrasi dengan payment gateway eksternal)
- **Email Service**: PHP mail() function (native)
- **File Storage**: Local filesystem dengan organized directory structure

#### Security & Authentication:
- **Password Hashing**: PHP `password_hash()` dengan bcrypt
- **Session Management**: PHP native sessions dengan regeneration
- **CSRF Protection**: Token-based (implementasi manual)
- **XSS Prevention**: Output escaping dengan `htmlspecialchars()`

#### Monitoring & Logging:
- **Error Logging**: Custom PHP error handlers
- **Chatbot Logging**: File-based logging (`chatbot_debug.log`)
- **Analytics**: Custom implementation untuk tracking views dan usage

### Struktur Direktori & Organisasi Kode

```
PapuaJourneyExpo/
├── admin/              # Admin portal
│   ├── analytics/      # Modul analitik
│   ├── components/     # Reusable components
│   └── helpers/        # Helper classes
├── users/              # User portal
│   ├── chatbot/        # AI chatbot module
│   │   ├── rag_py/     # Python RAG implementation
│   │   └── data/       # JSON data untuk training
│   ├── cart/           # Shopping cart
│   ├── checkout/       # Payment processing
│   └── reviews/        # Review system
├── umkm/               # UMKM portal
├── config/             # Database configuration
├── api/                # API endpoints
├── assets/             # Static assets
└── uploads/            # User uploads
    ├── profile_images/
    ├── artikel_images/
    ├── payment_proofs/
    └── review_media/
```

### Keunggulan Arsitektur

1. **Modular Structure**: Pemisahan yang jelas antara user, admin, dan UMKM portal
2. **Scalable AI Integration**: Chatbot service dapat di-scale independently
3. **Simple Deployment**: Monolithic architecture memudahkan deployment
4. **Cost Effective**: Menggunakan teknologi open-source
5. **Maintainable**: Struktur folder yang terorganisir dengan baik

### Keterbatasan & Pertimbangan

1. **Monolithic Architecture**: Seluruh aplikasi dalam satu codebase
2. **Manual Processes**: Payment verification masih manual
3. **Local File Storage**: Belum menggunakan cloud storage
4. **Limited Caching**: Belum ada implementation caching layer
5. **Basic Security**: Perlu enhancement untuk production environment
