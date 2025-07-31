# Dokumentasi API PapuaJourneyExpo

**URL Dasar:** `http://localhost/PapuaJourneyExpo`  
**Lingkungan:** XAMPP pada Windows  
**Database:** MariaDB (omaki_db)  
**Encoding:** UTF-8 dengan dukungan emoji

---

# Daftar Isi

1. [Pendahuluan](#1-pendahuluan)
   - 1.1 [Gambaran Umum](#11-gambaran-umum)
   - 1.2 [Fitur Utama](#12-fitur-utama)
   - 1.3 [Arsitektur Sistem](#13-arsitektur-sistem)

2. [Panduan Memulai](#2-panduan-memulai)
   - 2.1 [Prasyarat Sistem](#21-prasyarat-sistem)
   - 2.2 [Konfigurasi Lingkungan](#22-konfigurasi-lingkungan)
   - 2.3 [Autentikasi](#23-autentikasi)
   - 2.4 [Format Request dan Response](#24-format-request-dan-response)
   - 2.5 [Penanganan Error](#25-penanganan-error)

3. [Endpoint API](#3-endpoint-api)
   - 3.1 [Autentikasi](#31-autentikasi)
   - 3.2 [Manajemen Pengguna](#32-manajemen-pengguna)
   - 3.3 [Konten Pariwisata](#33-konten-pariwisata)
   - 3.4 [E-commerce](#34-e-commerce)
   - 3.5 [Pemrosesan Pembayaran](#35-pemrosesan-pembayaran)
   - 3.6 [Sistem Review](#36-sistem-review)
   - 3.7 [Fungsi Admin](#37-fungsi-admin)
   - 3.8 [Fungsi UMKM](#38-fungsi-umkm)
   - 3.9 [AI Chatbot](#39-ai-chatbot)
   - 3.10 [Analitik dan Laporan](#310-analitik-dan-laporan)
   - 3.11 [Notifikasi](#311-notifikasi)

4. [Model Data](#4-model-data)
   - 4.1 [Tipe Pengguna](#41-tipe-pengguna)
   - 4.2 [Entitas Utama](#42-entitas-utama)
   - 4.3 [Alur Transaksi](#43-alur-transaksi)

5. [Keamanan](#5-keamanan)
   - 5.1 [Metode Autentikasi](#51-metode-autentikasi)
   - 5.2 [Otorisasi](#52-otorisasi)
   - 5.3 [Perlindungan Data](#53-perlindungan-data)

6. [Lampiran](#6-lampiran)
   - 6.1 [Kode Status HTTP](#61-kode-status-http)
   - 6.2 [Kode Error](#62-kode-error)
   - 6.3 [Enumerasi](#63-enumerasi)
   - 6.4 [Spesifikasi Upload File](#64-spesifikasi-upload-file)

---

# 1. Pendahuluan

## 1.1 Gambaran Umum

PapuaJourneyExpo adalah platform pariwisata komprehensif untuk Papua, Indonesia, yang dibangun dengan PHP dan MariaDB. Platform ini menyediakan solusi lengkap untuk promosi pariwisata, marketplace UMKM, dan manajemen konten wisata.

## 1.2 Fitur Utama

- **Sistem Autentikasi Multi-pengguna**: Mendukung wisatawan, vendor UMKM, dan administrator
- **Manajemen Konten Pariwisata**: Daftar destinasi dan akomodasi dengan informasi lengkap
- **Fungsi E-commerce**: Keranjang belanja, checkout, dan manajemen pesanan
- **Pemrosesan Pembayaran**: Sistem konfirmasi pembayaran manual dengan upload bukti
- **Sistem Review dan Rating**: Konten yang dibuat pengguna dengan kemampuan moderasi
- **AI Chatbot**: Asisten percakapan berbasis RAG untuk informasi pariwisata
- **Dashboard Analitik**: Laporan komprehensif untuk admin dan vendor UMKM

## 1.3 Arsitektur Sistem

Platform menggunakan arsitektur tiga lapis tradisional dengan endpoint API berbasis PHP yang menangani semua logika bisnis.

```
┌─────────────────────────────────────────────────┐
│           Frontend (HTML/CSS/JS)                 │
│  ┌─────────┬─────────┬────────┬─────────────┐  │
│  │  Users  │  UMKM   │ Admin  │   Chatbot   │  │
│  └────┬────┴────┬────┴────┬───┴──────┬──────┘  │
└───────┼─────────┼─────────┼──────────┼─────────┘
        │         │         │          │
┌───────▼─────────▼─────────▼──────────▼─────────┐
│              PHP API Layer                      │
│  ┌──────────────────────────────────────────┐  │
│  │  Autentikasi Berbasis Sesi & Routing     │  │
│  └──────────────────────────────────────────┘  │
└───────────────────┬─────────────────────────────┘
                    │
┌───────────────────▼─────────────────────────────┐
│          Data Layer (MariaDB + Files)           │
└─────────────────────────────────────────────────┘
```

---

# 2. Panduan Memulai

## 2.1 Prasyarat Sistem

### Perangkat Lunak yang Diperlukan
- **PHP** 8.0.30 atau lebih tinggi
- **MariaDB** 10.4 atau lebih tinggi
- **Apache** web server (melalui XAMPP)
- **Python** 3.8+ (untuk AI chatbot)
- **Docker** (untuk container ChromaDB)

### Ekstensi PHP yang Diperlukan
- mysqli
- gd
- mbstring
- curl
- fileinfo

## 2.2 Konfigurasi Lingkungan

```ini
# Konfigurasi Database
Nama Database: omaki_db
Character Set: utf8mb4
Collation: utf8mb4_unicode_ci

# Konfigurasi Sesi
Masa Hidup Sesi: 8 jam (28800 detik)
Cookie HTTP Only: Aktif
Secure Cookie: Disarankan untuk produksi
```

## 2.3 Autentikasi

Semua endpoint API menggunakan autentikasi berbasis sesi. Sistem mendukung tiga tipe pengguna:

### Tipe Pengguna

| Tipe | Variabel Sesi | Endpoint Login | Deskripsi |
|------|---------------|----------------|-----------|
| **Pengguna Reguler** | `$_SESSION['user_id']` | `/login.php` | Wisatawan/pelanggan |
| **Vendor UMKM** | `$_SESSION['umkm_id']` | `/login.php` | Pemilik usaha kecil |
| **Administrator** | `$_SESSION['admin_logged_in']` | `/admin/index.php` | Administrator sistem |

### Manajemen Sesi

```php
// Konfigurasi sesi
ini_set('session.gc_maxlifetime', 28800);  // 8 jam
ini_set('session.cookie_lifetime', 28800);
session_set_cookie_params(28800);

// Contoh pemeriksaan autentikasi
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}
```

## 2.4 Format Request dan Response

### Metode Request
- **GET**: Digunakan untuk pengambilan data (tampilan halaman, daftar)
- **POST**: Digunakan untuk pengiriman data (formulir, pembaruan)

### Tipe Konten
- **Form HTML**: `application/x-www-form-urlencoded`
- **Upload File**: `multipart/form-data`
- **Request AJAX**: `application/json`

### Format Response
- **Navigasi Halaman**: Render halaman HTML atau redirect
- **Panggilan AJAX**: Response JSON
- **Download File**: Data biner dengan header yang sesuai

## 2.5 Penanganan Error

### Format Response Error
```json
{
    "success": false,
    "error": "Pesan error dalam Bahasa Indonesia",
    "code": "KODE_ERROR"
}
```

### Pesan Error Umum
| Pesan | Arti |
|-------|------|
| "Email dan password harus diisi!" | Field login kosong |
| "Email atau password salah!" | Kredensial tidak valid |
| "Akun tidak ditemukan" | Akun tidak ada |
| "Sesi telah berakhir" | Sesi kadaluarsa |
| "Akses ditolak" | Akses tidak diotorisasi |

---

# 3. Endpoint API

## 3.1 Autentikasi

### Login Pengguna
**Endpoint:** `POST /login.php`

**Deskripsi:** Mengautentikasi pengguna dan vendor UMKM

**Request Body:**
```
email: string (wajib)
password: string (wajib)
user_type: "user" | "umkm" (wajib)
```

**Response Sukses:**
- Redirect ke `/index.php` (pengguna) atau `/umkm/umkm_dashboard.php` (UMKM)
- Mengatur variabel sesi

**Response Error:**
- Kembali ke halaman login dengan pesan error

**Contoh Implementasi:**
```html
<form method="POST" action="/login.php">
    <select name="user_type">
        <option value="user">Wisatawan</option>
        <option value="umkm">UMKM</option>
    </select>
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
```

---

### Registrasi Pengguna
**Endpoint:** `POST /register.php`

**Deskripsi:** Membuat akun pengguna atau UMKM baru

**Request Body (Pengguna):**
```
user_type: "user" (wajib)
email: string (wajib)
password: string (wajib, min 6 karakter)
confirm_password: string (wajib)
full_name: string (wajib)
phone: string (opsional)
address: text (opsional)
```

**Request Body (UMKM):**
```
user_type: "umkm" (wajib)
email: string (wajib)
password: string (wajib, min 6 karakter)
confirm_password: string (wajib)
business_name: string (wajib)
owner_name: string (wajib)
phone: string (wajib)
address: text (wajib)
business_type: "jasa" | "event" | "kuliner" | "kerajinan" | "wisata" (wajib)
description: text (opsional)
```

**Response Sukses:**
- Redirect ke halaman login dengan pesan sukses

**Response Error:**
- Kembali ke halaman registrasi dengan error validasi

---

### Login Admin
**Endpoint:** `POST /admin/index.php`

**Deskripsi:** Mengautentikasi administrator

**Request Body:**
```
username: string (wajib)
password: string (wajib)
```

**Response Sukses:**
- Redirect ke dashboard admin
- Mengatur `$_SESSION['admin_logged_in']` dan `$_SESSION['admin_id']`

---

### Logout
**Endpoint:** `GET /logout.php`

**Deskripsi:** Mengakhiri sesi saat ini

**Response:**
- Menghancurkan sesi dan redirect ke homepage

## 3.2 Manajemen Pengguna

### Lihat Profil Pengguna
**Endpoint:** `GET /users/account/my_account.php`

**Deskripsi:** Mengambil informasi profil pengguna saat ini

**Autentikasi:** Wajib (`$_SESSION['user_id']`)

**Response:** Halaman HTML dengan data profil pengguna

---

### Update Profil Pengguna
**Endpoint:** `POST /users/account/my_account.php`

**Deskripsi:** Memperbarui informasi profil dan gambar profil pengguna

**Autentikasi:** Wajib (`$_SESSION['user_id']`)

**Request Body:**
```
full_name: string (wajib)
phone: string (opsional)
address: text (opsional)
profile_image: file (opsional, maks 5MB, JPG/PNG)
```

**Response Sukses:**
- Redirect dengan pesan sukses

---

### Lihat Riwayat Pesanan
**Endpoint:** `GET /users/account/my_orders.php`

**Deskripsi:** Menampilkan riwayat transaksi pengguna

**Autentikasi:** Wajib (`$_SESSION['user_id']`)

**Response:** Halaman HTML dengan daftar dan detail pesanan

## 3.3 Konten Pariwisata

### Daftar Destinasi Wisata
**Endpoint:** `GET /users/wisata/userwisata.php`

**Deskripsi:** Mengambil semua destinasi wisata dengan filtering

**Query Parameters:**
```
kategori: "budaya" | "alam" (opsional)
search: string (opsional)
sort: "price_asc" | "price_desc" (opsional)
```

**Response:** Halaman HTML dengan daftar destinasi

---

### Detail Destinasi
**Endpoint:** `GET /users/wisata/detail.php`

**Deskripsi:** Menampilkan informasi detail tentang destinasi

**Query Parameters:**
```
id: integer (wajib)
```

**Response:** Halaman HTML dengan detail destinasi, review, dan opsi pemesanan

---

### Track View Destinasi
**Endpoint:** `POST /users/wisata/track_view.php`

**Deskripsi:** Mencatat analitik untuk tampilan destinasi

**Request Body:**
```
wisata_id: integer (wajib)
```

**Response:** JSON
```json
{
    "success": true
}
```

---

### Daftar Akomodasi
**Endpoint:** `GET /users/penginapan/userpenginapan.php`

**Deskripsi:** Mengambil semua akomodasi dengan filtering

**Query Parameters:**
```
tipe: "hotel" | "villa" | "resort" (opsional)
location: string (opsional)
price_min: decimal (opsional)
price_max: decimal (opsional)
```

**Response:** Halaman HTML dengan daftar akomodasi

---

### Detail Akomodasi
**Endpoint:** `GET /users/penginapan/detail.php`

**Deskripsi:** Menampilkan informasi detail tentang akomodasi

**Query Parameters:**
```
id: integer (wajib)
```

**Response:** Halaman HTML dengan detail akomodasi dan opsi pemesanan

---

### Track View Akomodasi
**Endpoint:** `POST /users/penginapan/track_view.php`

**Deskripsi:** Mencatat analitik untuk tampilan akomodasi

**Request Body:**
```
penginapan_id: integer (wajib)
```

**Response:** JSON
```json
{
    "success": true
}
```

## 3.4 E-commerce

### Tambah ke Keranjang
**Endpoint:** `POST /users/cart/add_to_cart.php`

**Deskripsi:** Menambahkan item ke keranjang belanja

**Autentikasi:** Wajib (`$_SESSION['user_id']`)

**Request Body:**
```
item_type: "wisata" | "penginapan" | "artikel" (wajib)
item_id: integer (wajib)
quantity: integer (wajib)
booking_date: date (wajib untuk wisata)
checkin_date: date (wajib untuk penginapan)
checkout_date: date (wajib untuk penginapan)
notes: text (opsional)
```

**Response:** JSON
```json
{
    "success": true,
    "message": "Item berhasil ditambahkan ke keranjang",
    "cart_count": 5
}
```

---

### Lihat Keranjang
**Endpoint:** `GET /users/cart/cart.php`

**Deskripsi:** Menampilkan isi keranjang belanja

**Autentikasi:** Wajib (`$_SESSION['user_id']`)

**Response:** Halaman HTML dengan item keranjang

---

### Update Item Keranjang
**Endpoint:** `POST /users/cart/update_cart.php`

**Deskripsi:** Memperbarui kuantitas item keranjang

**Autentikasi:** Wajib (`$_SESSION['user_id']`)

**Request Body:**
```
cart_id: integer (wajib)
quantity: integer (wajib)
```

**Response:** JSON
```json
{
    "success": true,
    "new_subtotal": 150000,
    "cart_total": 500000
}
```

---

### Hapus dari Keranjang
**Endpoint:** `POST /users/cart/remove_item.php`

**Deskripsi:** Menghapus item dari keranjang

**Autentikasi:** Wajib (`$_SESSION['user_id']`)

**Request Body:**
```
cart_id: integer (wajib)
```

**Response:** JSON
```json
{
    "success": true,
    "message": "Item berhasil dihapus"
}
```

---

### Checkout
**Endpoint:** `POST /users/checkout/process_checkout.php`

**Deskripsi:** Memproses item keranjang menjadi transaksi

**Autentikasi:** Wajib (`$_SESSION['user_id']`)

**Request Body:**
```
selected_items[]: array of cart_ids (wajib)
full_name: string (wajib)
email: string (wajib)
phone: string (wajib)
address: text (wajib)
payment_method: "bank_transfer" | "e_wallet" (wajib)
notes: text (opsional)
```

**Response Sukses:**
- Membuat transaksi dengan kode unik (format: TRX + timestamp + user_id)
- Redirect ke halaman instruksi pembayaran

**Contoh Kode Transaksi:** `TRX202507220517208`

## 3.5 Pemrosesan Pembayaran

### Upload Bukti Pembayaran
**Endpoint:** `POST /users/checkout/upload_payment_proof.php`

**Deskripsi:** Mengupload bukti pembayaran untuk konfirmasi manual

**Autentikasi:** Wajib (`$_SESSION['user_id']`)

**Request Body:**
```
transaction_id: integer (wajib)
payment_proof: file (wajib, maks 10MB, JPG/PNG/PDF)
user_payment_date: datetime (wajib)
```

**Response Sukses:**
- Memperbarui status transaksi ke "awaiting_confirmation"
- Redirect dengan pesan sukses

**Lokasi Penyimpanan File:** `/uploads/payment_proofs/payment_{transaction_code}_{timestamp}.{ext}`

---

### Upload Ulang Bukti Pembayaran
**Endpoint:** `POST /users/checkout/reupload_payment.php`

**Deskripsi:** Mengupload ulang bukti pembayaran jika ditolak

**Autentikasi:** Wajib (`$_SESSION['user_id']`)

**Request Body:**
```
transaction_id: integer (wajib)
payment_proof: file (wajib, maks 10MB, JPG/PNG/PDF)
user_payment_date: datetime (wajib)
```

**Response Sukses:**
- Memperbarui status transaksi ke "awaiting_confirmation"
- Redirect dengan pesan sukses

---

### Payment Callback (Eksternal)
**Endpoint:** `POST /api/payment_callback.php`

**Deskripsi:** Webhook endpoint untuk notifikasi payment gateway

**Headers:**
```
Content-Type: application/json
```

**Request Body:**
```json
{
    "transaction_code": "TRX202507220517208",
    "status": "success",
    "payment_method": "bank_transfer",
    "amount": 500000,
    "signature": "sha256_hmac_signature"
}
```

**Response Codes:**
- `200`: Sukses
- `400`: Request tidak valid
- `401`: Signature tidak valid
- `404`: Transaksi tidak ditemukan
- `405`: Method tidak diizinkan

**Verifikasi Signature:**
```php
$expected_signature = hash_hmac('sha256', 
    $transaction_code . $amount, 
    $secret_key
);
```

## 3.6 Sistem Review

### Submit Review
**Endpoint:** `POST /users/reviews/submit_review.php`

**Deskripsi:** Mengirimkan review untuk item yang dibeli

**Autentikasi:** Wajib (`$_SESSION['user_id']`)

**Request Body:**
```
transaksi_id: integer (wajib)
item_type: "wisata" | "penginapan" | "artikel" (wajib)
item_id: integer (wajib)
rating: integer 1-5 (wajib)
review_text: text (wajib)
media_files[]: array of files (opsional, maks 5 file, 5MB per file)
```

**Response:** JSON
```json
{
    "success": true,
    "message": "Review berhasil dikirim",
    "review_id": 123
}
```

---

### Ambil Review
**Endpoint:** `GET /users/reviews/get_reviews.php`

**Deskripsi:** Mengambil review untuk item tertentu

**Query Parameters:**
```
item_type: "wisata" | "penginapan" | "artikel" (wajib)
item_id: integer (wajib)
sort: "newest" | "oldest" | "highest" | "lowest" (opsional)
page: integer (opsional, default: 1)
limit: integer (opsional, default: 10)
```

**Response:** JSON
```json
{
    "success": true,
    "reviews": [
        {
            "id": 123,
            "user_name": "John Doe",
            "rating": 5,
            "review_text": "Sangat bagus!",
            "created_at": "2025-07-22 10:30:00",
            "is_verified": true,
            "helpful_count": 5,
            "not_helpful_count": 1,
            "media": [
                {
                    "type": "image",
                    "url": "/uploads/review_media/review_123_1.jpg"
                }
            ]
        }
    ],
    "total_count": 50,
    "current_page": 1,
    "total_pages": 5
}
```

---

### Vote Review Helpful
**Endpoint:** `POST /users/reviews/vote_helpful.php`

**Deskripsi:** Memberikan vote apakah review bermanfaat

**Autentikasi:** Wajib (`$_SESSION['user_id']`)

**Request Body:**
```
review_id: integer (wajib)
is_helpful: boolean (wajib)
```

**Response:** JSON
```json
{
    "success": true,
    "message": "Vote berhasil disimpan",
    "action": "added",
    "helpful_count": 6,
    "not_helpful_count": 1
}
```

## 3.7 Fungsi Admin

### Dashboard Admin
**Endpoint:** `GET /admin/dashboard.php`

**Deskripsi:** Halaman dashboard utama admin

**Autentikasi:** Wajib (`$_SESSION['admin_logged_in']`)

**Response:** Halaman HTML dengan ringkasan statistik

---

### Ambil Data Summary
**Endpoint:** `GET /admin/api/get-summary-data.php`

**Deskripsi:** Mengambil data ringkasan komprehensif untuk dashboard

**Autentikasi:** Wajib (`$_SESSION['admin_logged_in']`)

**Response:** JSON
```json
{
    "success": true,
    "data": {
        "trend_analysis": {
            "revenue_trend": "increasing",
            "user_growth": "stable",
            "transaction_volume": "high"
        },
        "performance_highlights": {
            "top_destinations": [],
            "top_accommodations": [],
            "top_umkm_products": []
        },
        "user_engagement": {
            "active_users": 1500,
            "new_registrations": 50,
            "repeat_customers": 300
        },
        "revenue_insights": {
            "total_revenue": 150000000,
            "average_transaction": 500000,
            "payment_success_rate": 95.5
        },
        "alert_indicators": {
            "pending_payments": 10,
            "low_stock_items": 5,
            "unmoderated_reviews": 15
        },
        "generated_at": "2025-07-22 10:00:00"
    }
}
```

---

### Ambil Rekomendasi
**Endpoint:** `GET /admin/api/get-recommendations.php`

**Deskripsi:** Menghasilkan rekomendasi berbasis AI untuk optimasi bisnis

**Autentikasi:** Wajib (`$_SESSION['admin_logged_in']`)

**Response:** JSON
```json
{
    "success": true,
    "data": {
        "recommendations": [
            {
                "id": "rec_1",
                "category": "revenue",
                "priority": "high",
                "title": "Optimasi Harga Tiket Wisata",
                "description": "Beberapa destinasi memiliki tingkat konversi rendah",
                "action": "Pertimbangkan penyesuaian harga atau promo",
                "impact": "Potensi peningkatan revenue 15%"
            }
        ],
        "total_count": 5,
        "high_priority_count": 2,
        "generated_at": "2025-07-22 10:00:00"
    }
}
```

---

### Konfirmasi Pembayaran
**Endpoint:** `POST /admin/payment_confirmation.php`

**Deskripsi:** Admin mengonfirmasi atau menolak pembayaran

**Autentikasi:** Wajib (`$_SESSION['admin_logged_in']`)

**Request Body:**
```
transaction_id: integer (wajib)
action: "confirm" | "reject" (wajib)
notes: text (opsional)
```

**Response:**
- Redirect dengan pesan sukses
- Mengirim notifikasi ke UMKM terkait

---

### Analitik Wisata
**Endpoint:** `GET /admin/wisata_analytics.php`

**Deskripsi:** Menampilkan analitik detail untuk destinasi wisata

**Autentikasi:** Wajib (`$_SESSION['admin_logged_in']`)

**Query Parameters:**
```
date_range: "7days" | "30days" | "90days" | "custom" (opsional)
start_date: date (wajib jika date_range="custom")
end_date: date (wajib jika date_range="custom")
```

**Response:** Halaman HTML dengan grafik dan tabel analitik

---

### Laporan Keuangan
**Endpoint:** `GET /admin/financial_reports.php`

**Deskripsi:** Menampilkan laporan keuangan komprehensif

**Autentikasi:** Wajib (`$_SESSION['admin_logged_in']`)

**Query Parameters:**
```
month: integer 1-12 (opsional)
year: integer (opsional)
```

**Response:** Halaman HTML dengan laporan keuangan

---

### Export Laporan Keuangan
**Endpoint:** `GET /admin/export_financial_report.php`

**Deskripsi:** Export laporan keuangan ke format CSV

**Autentikasi:** Wajib (`$_SESSION['admin_logged_in']`)

**Query Parameters:**
```
month: integer 1-12 (wajib)
year: integer (wajib)
```

**Response:** File CSV untuk download

---

### Abandoned Cart Report
**Endpoint:** `GET /admin/abandoned_cart.php`

**Deskripsi:** Menampilkan laporan keranjang yang ditinggalkan

**Autentikasi:** Wajib (`$_SESSION['admin_logged_in']`)

**Response:** Halaman HTML dengan data abandoned cart

---

### Export Abandoned Cart
**Endpoint:** `GET /admin/export_abandoned_cart.php`

**Deskripsi:** Export data abandoned cart ke CSV

**Autentikasi:** Wajib (`$_SESSION['admin_logged_in']`)

**Response:** File CSV untuk download

## 3.8 Fungsi UMKM

### Dashboard UMKM
**Endpoint:** `GET /umkm/umkm_dashboard.php`

**Deskripsi:** Dashboard utama untuk vendor UMKM

**Autentikasi:** Wajib (`$_SESSION['umkm_id']`)

**Response:** Halaman HTML dengan ringkasan bisnis

---

### Analitik UMKM
**Endpoint:** `GET /umkm/umkm_analytics.php`

**Deskripsi:** Menampilkan analitik penjualan UMKM

**Autentikasi:** Wajib (`$_SESSION['umkm_id']`)

**Query Parameters:**
```
period: "daily" | "weekly" | "monthly" (opsional)
start_date: date (opsional)
end_date: date (opsional)
```

**Response:** Halaman HTML dengan grafik dan statistik

---

### Daftar Pemesanan UMKM
**Endpoint:** `GET /umkm/umkm_pemesanan.php`

**Deskripsi:** Menampilkan daftar pesanan untuk UMKM

**Autentikasi:** Wajib (`$_SESSION['umkm_id']`)

**Query Parameters:**
```
status: "all" | "pending" | "paid" | "completed" (opsional)
search: string (opsional)
```

**Response:** Halaman HTML dengan daftar pesanan

---

### Ambil Notifikasi UMKM
**Endpoint:** `GET /umkm/get_notifications.php`

**Deskripsi:** Mengambil notifikasi terbaru untuk UMKM

**Autentikasi:** Wajib (`$_SESSION['umkm_id']`)

**Response:** JSON
```json
{
    "success": true,
    "notifications": [
        {
            "id": 1,
            "type": "new_order",
            "title": "Pesanan Baru",
            "message": "Anda memiliki pesanan baru dari John Doe",
            "transaction_code": "TRX202507220517208",
            "is_read": false,
            "created_at": "2025-07-22 10:00:00"
        }
    ],
    "unread_count": 3
}
```

## 3.9 AI Chatbot

### Proses Chat
**Endpoint:** `POST /users/chatbot/chatbot_process.php`

**Deskripsi:** Memproses pesan chat dan menghasilkan respons AI

**Request Body:**
```
message: string (wajib)
conversation_id: string UUID (opsional)
```

**Response:** JSON
```json
{
    "success": true,
    "response": "Jayapura memiliki banyak destinasi wisata menarik...",
    "conversation_id": "550e8400-e29b-41d4-a716-446655440000",
    "sources": [
        {
            "title": "Danau Sentani",
            "excerpt": "Danau terbesar di Papua...",
            "url": "/users/wisata/detail.php?id=1"
        }
    ]
}
```

---

### Load Chat History
**Endpoint:** `GET /users/chatbot/load_chat_history.php`

**Deskripsi:** Memuat riwayat percakapan chat

**Autentikasi:** Opsional (`$_SESSION['user_id']`)

**Query Parameters:**
```
conversation_id: string UUID (opsional)
limit: integer (opsional, default: 50)
```

**Response:** JSON
```json
{
    "success": true,
    "messages": [
        {
            "id": 1,
            "message_type": "user",
            "message": "Apa saja tempat wisata di Jayapura?",
            "created_at": "2025-07-22 09:30:00"
        },
        {
            "id": 2,
            "message_type": "bot",
            "message": "Jayapura memiliki banyak destinasi wisata...",
            "created_at": "2025-07-22 09:30:05"
        }
    ],
    "conversation_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

## 3.10 Analitik dan Laporan

### Update Statistik Wisata
**Endpoint:** `POST /admin/scripts/update_statistics.php`

**Deskripsi:** Script untuk update statistik wisata (biasanya dijalankan via cron)

**Autentikasi:** Wajib (admin atau sistem)

**Response:** JSON
```json
{
    "success": true,
    "message": "Statistik berhasil diperbarui",
    "updated_records": 25
}
```

---

### Data Laporan Keuangan API
**Endpoint:** `GET /admin/get_financial_data.php`

**Deskripsi:** Mengambil data keuangan untuk visualisasi

**Autentikasi:** Wajib (`$_SESSION['admin_logged_in']`)

**Query Parameters:**
```
type: "revenue" | "transactions" | "categories" (wajib)
period: "daily" | "monthly" | "yearly" (opsional)
start_date: date (opsional)
end_date: date (opsional)
```

**Response:** JSON
```json
{
    "success": true,
    "data": {
        "labels": ["Jan", "Feb", "Mar"],
        "datasets": [{
            "label": "Revenue",
            "data": [15000000, 18000000, 20000000]
        }]
    }
}
```

## 3.11 Notifikasi

### Kirim Notifikasi UMKM
**Endpoint:** `POST /admin/send_umkm_notification.php`

**Deskripsi:** Admin mengirim notifikasi ke UMKM

**Autentikasi:** Wajib (`$_SESSION['admin_logged_in']`)

**Request Body:**
```
umkm_id: integer (wajib)
type: "general" | "warning" | "promotion" (wajib)
title: string (wajib)
message: text (wajib)
```

**Response:** JSON
```json
{
    "success": true,
    "message": "Notifikasi berhasil dikirim"
}
```

---

# 4. Model Data

## 4.1 Tipe Pengguna

### Struktur Tabel Pengguna

#### Tabel `users` (Pengguna Reguler)
| Kolom | Tipe | Constraint | Deskripsi |
|-------|------|------------|-----------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik pengguna |
| `email` | VARCHAR(100) | UNIQUE, NOT NULL | Email login |
| `password` | VARCHAR(255) | NOT NULL | Password ter-hash (bcrypt) |
| `full_name` | VARCHAR(100) | NOT NULL | Nama lengkap |
| `phone` | VARCHAR(20) | NULL | Nomor telepon |
| `address` | TEXT | NULL | Alamat pengiriman |
| `profile_image` | VARCHAR(255) | DEFAULT 'default-user.jpg' | Nama file foto profil |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Tanggal registrasi |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Terakhir dimodifikasi |

#### Tabel `umkm` (Vendor UMKM)
| Kolom | Tipe | Constraint | Deskripsi |
|-------|------|------------|-----------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID unik UMKM |
| `email` | VARCHAR(100) | UNIQUE, NOT NULL | Email bisnis |
| `password` | VARCHAR(255) | NOT NULL | Password ter-hash (bcrypt) |
| `business_name` | VARCHAR(100) | NOT NULL | Nama bisnis |
| `owner_name` | VARCHAR(100) | NOT NULL | Nama pemilik |
| `phone` | VARCHAR(20) | NOT NULL | Kontak bisnis |
| `address` | TEXT | NOT NULL | Lokasi bisnis |
| `business_type` | ENUM | NOT NULL | jasa/event/kuliner/kerajinan/wisata |
| `description` | TEXT | NULL | Deskripsi bisnis |
| `profile_image` | VARCHAR(255) | DEFAULT 'default-umkm.jpg' | Logo bisnis |
| `status` | ENUM | DEFAULT 'pending' | pending/active/inactive |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Tanggal registrasi |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Terakhir dimodifikasi |

#### Tabel `admin`
| Kolom | Tipe | Constraint | Deskripsi |
|-------|------|------------|-----------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID admin |
| `username` | VARCHAR(50) | UNIQUE, NOT NULL | Username admin |
| `password` | VARCHAR(255) | NOT NULL | Password ter-hash (bcrypt) |
| `full_name` | VARCHAR(100) | NOT NULL | Nama lengkap admin |
| `email` | VARCHAR(100) | NULL | Email admin |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Tanggal pembuatan |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Terakhir dimodifikasi |

## 4.2 Entitas Utama

### Tabel Konten

#### Tabel `wisata` (Destinasi Wisata)
| Kolom | Tipe | Constraint | Deskripsi |
|-------|------|------------|-----------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID destinasi |
| `judul` | VARCHAR(255) | NOT NULL | Nama destinasi |
| `deskripsi` | TEXT | NOT NULL | Deskripsi lengkap |
| `harga` | DECIMAL(10,2) | NOT NULL | Harga tiket masuk |
| `kategori` | ENUM | NOT NULL | budaya/alam |
| `alamat` | TEXT | NOT NULL | Alamat fisik |
| `jam_buka` | VARCHAR(100) | NOT NULL | Jam operasional |
| `photo` | VARCHAR(255) | NOT NULL | Nama file gambar utama |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Tanggal dibuat |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Terakhir diupdate |

#### Tabel `penginapan` (Akomodasi)
| Kolom | Tipe | Constraint | Deskripsi |
|-------|------|------------|-----------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID akomodasi |
| `judul` | VARCHAR(255) | NOT NULL | Nama properti |
| `deskripsi` | TEXT | NOT NULL | Deskripsi lengkap |
| `harga` | DECIMAL(10,2) | NOT NULL | Harga per malam |
| `lokasi` | VARCHAR(255) | NOT NULL | Lokasi |
| `tipe` | ENUM | NOT NULL | hotel/villa/resort |
| `fasilitas` | TEXT | NOT NULL | Daftar fasilitas |
| `photo` | VARCHAR(255) | NOT NULL | Gambar utama |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Tanggal dibuat |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Terakhir diupdate |

#### Tabel `artikel` (Produk/Jasa UMKM)
| Kolom | Tipe | Constraint | Deskripsi |
|-------|------|------------|-----------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID artikel |
| `umkm_id` | INT(11) | FOREIGN KEY | Pemilik UMKM |
| `judul` | VARCHAR(200) | NOT NULL | Nama produk/jasa |
| `deskripsi` | TEXT | NOT NULL | Deskripsi lengkap |
| `harga` | DECIMAL(15,2) | NOT NULL | Harga |
| `kategori` | ENUM | NOT NULL | jasa/event/kuliner/kerajinan/wisata |
| `gambar` | VARCHAR(255) | NULL | Gambar produk |
| `status` | ENUM | DEFAULT 'active' | active/inactive |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Tanggal dibuat |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Terakhir diupdate |

### Tabel Transaksi

#### Tabel `transaksi`
| Kolom | Tipe | Constraint | Deskripsi |
|-------|------|------------|-----------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID transaksi |
| `user_id` | INT(11) | FOREIGN KEY | ID pembeli |
| `transaction_code` | VARCHAR(20) | UNIQUE, NOT NULL | Kode transaksi unik |
| `total_amount` | DECIMAL(10,2) | NOT NULL | Total nilai transaksi |
| `payment_status` | ENUM | DEFAULT 'pending' | pending/awaiting_confirmation/paid/rejected/cancelled |
| `payment_method` | VARCHAR(50) | NULL | bank_transfer/e_wallet |
| `payment_proof` | VARCHAR(255) | NULL | Nama file bukti upload |
| `user_payment_date` | DATETIME | NULL | Waktu pembayaran user |
| `payment_confirmed_at` | DATETIME | NULL | Waktu konfirmasi admin |
| `payment_confirmed_by` | INT(11) | NULL | ID admin yang konfirmasi |
| `payment_date` | DATETIME | NULL | Waktu pembayaran selesai |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Tanggal order |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Terakhir diupdate |

#### Tabel `transaksi_items`
| Kolom | Tipe | Constraint | Deskripsi |
|-------|------|------------|-----------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID item |
| `transaksi_id` | INT(11) | FOREIGN KEY | Transaksi induk |
| `item_type` | ENUM | NOT NULL | wisata/penginapan/artikel |
| `item_id` | INT(11) | NOT NULL | Referensi ke item |
| `item_name` | VARCHAR(255) | NOT NULL | Nama item |
| `quantity` | INT(11) | NOT NULL | Jumlah unit |
| `price_per_unit` | DECIMAL(10,2) | NOT NULL | Harga per unit |
| `subtotal` | DECIMAL(10,2) | NOT NULL | Total baris |
| `booking_date` | DATE | NULL | Untuk tiket wisata |
| `checkin_date` | DATE | NULL | Untuk penginapan |
| `checkout_date` | DATE | NULL | Untuk penginapan |
| `notes` | TEXT | NULL | Instruksi khusus |

#### Tabel `cart_items`
| Kolom | Tipe | Constraint | Deskripsi |
|-------|------|------------|-----------|
| `id` | INT(11) | PRIMARY KEY, AUTO_INCREMENT | ID item keranjang |
| `user_id` | INT(11) | FOREIGN KEY | Pemilik keranjang |
| `item_type` | ENUM | NOT NULL | wisata/penginapan/artikel |
| `item_id` | INT(11) | NOT NULL | Referensi ke item |
| `quantity` | INT(11) | DEFAULT 1 | Jumlah unit |
| `price_per_unit` | DECIMAL(10,2) | NOT NULL | Harga per unit saat ini |
| `subtotal` | DECIMAL(10,2) | NOT NULL | Total baris |
| `booking_date` | DATE | NULL | Untuk tiket wisata |
| `checkin_date` | DATE | NULL | Untuk penginapan |
| `checkout_date` | DATE | NULL | Untuk penginapan |
| `notes` | TEXT | NULL | Permintaan khusus |
| `added_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Ditambahkan ke keranjang |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP | Terakhir dimodifikasi |

## 4.3 Alur Transaksi

### Diagram Alur Pembayaran

```
┌──────────────┐     ┌────────────────┐     ┌─────────────────┐
│   Checkout   │────▶│ Create Order   │────▶│ Payment Page    │
└──────────────┘     └────────────────┘     └─────────────────┘
                                                      │
                                                      ▼
┌──────────────┐     ┌────────────────┐     ┌─────────────────┐
│   Rejected   │◀────│ Admin Review   │◀────│ Upload Proof    │
└──────────────┘     └────────────────┘     └─────────────────┘
                              │
                              ▼
                     ┌────────────────┐     ┌─────────────────┐
                     │   Confirmed    │────▶│ Send to UMKM    │
                     └────────────────┘     └─────────────────┘
```

### Status Pembayaran

| Status | Deskripsi |
|--------|-----------|
| `pending` | Menunggu pembayaran |
| `awaiting_confirmation` | Bukti diupload, menunggu konfirmasi |
| `paid` | Pembayaran dikonfirmasi |
| `rejected` | Pembayaran ditolak |
| `cancelled` | Dibatalkan oleh sistem/user |

---

# 5. Keamanan

## 5.1 Metode Autentikasi

### Session-Based Authentication
- Menggunakan PHP sessions dengan konfigurasi aman
- Session timeout: 8 jam
- HTTP-only cookies untuk mencegah XSS
- Session regeneration saat login

### Password Security
- Hashing menggunakan `password_hash()` dengan algoritma bcrypt
- Verifikasi dengan `password_verify()`
- Minimal 6 karakter untuk password

## 5.2 Otorisasi

### Role-Based Access Control
```php
// Contoh middleware untuk endpoint admin
if (!isset($_SESSION['admin_logged_in'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Akses ditolak');
}

// Contoh middleware untuk endpoint UMKM
if (!isset($_SESSION['umkm_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Akses ditolak');
}
```

### Resource-Level Authorization
```php
// Memastikan user hanya bisa akses resource miliknya
$user_id = $_SESSION['user_id'];
$transaction = getTransaction($transaction_id);
if ($transaction['user_id'] != $user_id) {
    header('HTTP/1.1 403 Forbidden');
    exit('Anda tidak memiliki akses ke resource ini');
}
```

## 5.3 Perlindungan Data

### Input Validation
- Semua input disanitasi menggunakan `mysqli_real_escape_string()`
- Validasi tipe data sebelum diproses
- Pembatasan ukuran file upload

### SQL Injection Prevention
```php
// Menggunakan prepared statements
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
```

### XSS Prevention
```php
// Output encoding
echo htmlspecialchars($user_input, ENT_QUOTES, 'UTF-8');
```

### File Upload Security
- Validasi tipe file (whitelist)
- Pembatasan ukuran file
- Random filename generation
- Penyimpanan di luar document root (jika memungkinkan)

---

# 6. Lampiran

## 6.1 Kode Status HTTP

| Kode | Status | Penggunaan |
|------|--------|------------|
| 200 | OK | Request berhasil |
| 201 | Created | Resource berhasil dibuat |
| 204 | No Content | Request berhasil tanpa response body |
| 400 | Bad Request | Request tidak valid |
| 401 | Unauthorized | Autentikasi diperlukan |
| 403 | Forbidden | Akses ditolak |
| 404 | Not Found | Resource tidak ditemukan |
| 405 | Method Not Allowed | HTTP method tidak diizinkan |
| 409 | Conflict | Konflik dengan state saat ini |
| 422 | Unprocessable Entity | Validasi gagal |
| 500 | Internal Server Error | Error server |

## 6.2 Kode Error

### Error Autentikasi
| Kode | Pesan | Deskripsi |
|------|-------|-----------|
| AUTH_001 | Email dan password harus diisi | Field login kosong |
| AUTH_002 | Email atau password salah | Kredensial tidak valid |
| AUTH_003 | Akun tidak ditemukan | User tidak terdaftar |
| AUTH_004 | Sesi telah berakhir | Session timeout |
| AUTH_005 | Akses ditolak | Unauthorized access |

### Error Validasi
| Kode | Pesan | Deskripsi |
|------|-------|-----------|
| VAL_001 | Data tidak lengkap | Required field kosong |
| VAL_002 | Format email tidak valid | Email format error |
| VAL_003 | Password minimal 6 karakter | Password terlalu pendek |
| VAL_004 | Password tidak cocok | Konfirmasi password gagal |
| VAL_005 | File terlalu besar | Melebihi batas ukuran |

### Error Bisnis
| Kode | Pesan | Deskripsi |
|------|-------|-----------|
| BIZ_001 | Item tidak tersedia | Stok habis |
| BIZ_002 | Transaksi tidak ditemukan | Invalid transaction ID |
| BIZ_003 | Pembayaran sudah dikonfirmasi | Tidak bisa diubah |
| BIZ_004 | Keranjang kosong | Tidak ada item |
| BIZ_005 | Tanggal booking tidak valid | Tanggal sudah lewat |

## 6.3 Enumerasi

### Tipe Bisnis UMKM
```
jasa     - Layanan jasa
event    - Event organizer
kuliner  - Makanan & minuman
kerajinan - Kerajinan tangan
wisata   - Paket wisata
```

### Kategori Wisata
```
budaya - Wisata budaya
alam   - Wisata alam
```

### Tipe Akomodasi
```
hotel  - Hotel
villa  - Villa
resort - Resort
```

### Status Pembayaran
```
pending              - Menunggu pembayaran
awaiting_confirmation - Menunggu konfirmasi admin
paid                 - Terbayar
rejected             - Ditolak
cancelled            - Dibatalkan
```

### Tipe Notifikasi UMKM
```
new_order         - Pesanan baru
payment_confirmed - Pembayaran dikonfirmasi
payment_rejected  - Pembayaran ditolak
```

## 6.4 Spesifikasi Upload File

### Foto Profil
- **Lokasi:** `/uploads/profile_images/`
- **Format:** JPG, PNG
- **Ukuran Maks:** 5MB
- **Dimensi Rekomendasi:** 300x300px

### Bukti Pembayaran
- **Lokasi:** `/uploads/payment_proofs/`
- **Format:** JPG, PNG, PDF
- **Ukuran Maks:** 10MB
- **Penamaan:** `payment_{transaction_code}_{timestamp}.{ext}`

### Gambar Produk/Artikel
- **Lokasi:** `/uploads/artikel_images/`
- **Format:** JPG, PNG
- **Ukuran Maks:** 5MB
- **Dimensi Rekomendasi:** 800x600px

### Media Review
- **Lokasi:** `/uploads/review_media/`
- **Format:** JPG, PNG (gambar), MP4 (video)
- **Ukuran Maks:** 5MB per file
- **Limit:** 5 file per review

### Gambar Wisata/Penginapan
- **Lokasi:** `/uploads/wisata/` atau `/uploads/penginapan/`
- **Format:** JPG, PNG
- **Ukuran Maks:** 10MB
- **Dimensi Rekomendasi:** 1200x800px

---

## Catatan Penting

1. **Encoding Database**: Semua tabel menggunakan `utf8mb4_unicode_ci` untuk mendukung emoji dan karakter khusus
2. **Timezone**: Server menggunakan timezone Asia/Jayapura (UTC+9)
3. **Rate Limiting**: Tidak ada rate limiting built-in, disarankan untuk implementasi di level web server
4. **CORS**: Tidak dikonfigurasi karena semua endpoint diakses dari domain yang sama
5. **Backup**: Disarankan backup database harian dengan retention 30 hari

---

*Dokumentasi ini merupakan referensi lengkap untuk semua endpoint API dan fungsi sistem PapuaJourneyExpo.*