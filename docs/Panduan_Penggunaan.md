# Panduan Penggunaan PapuaJourneyExpo

## Daftar Isi
1. [Pendahuluan](#pendahuluan)
2. [Panduan untuk User/Pengunjung](#panduan-untuk-userpengunjung)
   - [Registrasi dan Login](#registrasi-dan-login)
   - [Menjelajahi Destinasi Wisata](#menjelajahi-destinasi-wisata)
   - [Melihat Penginapan](#melihat-penginapan)
   - [Berbelanja Produk UMKM](#berbelanja-produk-umkm)
   - [Menggunakan Chatbot AI](#menggunakan-chatbot-ai)
   - [Memberikan Review](#memberikan-review)
   - [Proses Checkout dan Pembayaran](#proses-checkout-dan-pembayaran)
3. [Panduan untuk UMKM](#panduan-untuk-umkm)
   - [Registrasi UMKM](#registrasi-umkm)
   - [Dashboard UMKM](#dashboard-umkm)
   - [Mengelola Produk/Artikel](#mengelola-produkartikel)
   - [Melihat Pesanan](#melihat-pesanan)
   - [Analytics UMKM](#analytics-umkm)
4. [Panduan untuk Admin](#panduan-untuk-admin)
   - [Login Admin](#login-admin)
   - [Dashboard Admin](#dashboard-admin)
   - [Mengelola Wisata](#mengelola-wisata)
   - [Mengelola Penginapan](#mengelola-penginapan)
   - [Konfirmasi Pembayaran](#konfirmasi-pembayaran)
   - [Laporan dan Analytics](#laporan-dan-analytics)
5. [FAQ dan Troubleshooting](#faq-dan-troubleshooting)

---

## Pendahuluan

PapuaJourneyExpo adalah platform pariwisata terpadu untuk Papua yang menghubungkan wisatawan dengan destinasi wisata, penginapan, dan produk UMKM lokal. Platform ini dilengkapi dengan AI chatbot untuk membantu pengguna mendapatkan informasi seputar pariwisata Papua.

### Persyaratan Sistem
- Browser modern (Chrome, Firefox, Safari, Edge)
- Koneksi internet yang stabil
- JavaScript harus diaktifkan

### Akses Platform
Platform dapat diakses melalui browser dengan mengunjungi URL yang telah disediakan.

---

## Panduan untuk User/Pengunjung

### Registrasi dan Login

#### Registrasi Akun Baru
1. Klik tombol "Login" di navbar
2. Pada halaman login, klik link "Belum punya akun? Daftar di sini"
3. Isi formulir registrasi:
   - Nama Lengkap
   - Email (akan digunakan untuk login)
   - Password (minimal 8 karakter)
   - Konfirmasi Password
4. Klik tombol "Daftar"
5. Jika berhasil, Anda akan diarahkan ke halaman login

Setelah mengisi semua field dengan benar, sistem akan memvalidasi data Anda. Pastikan email belum terdaftar sebelumnya dan password memenuhi kriteria keamanan minimal.

#### Login ke Sistem
1. Klik tombol "Login" di navbar
2. Pilih tipe login "User Biasa"
3. Masukkan email dan password
4. Klik tombol "Login"
5. Jika berhasil, Anda akan diarahkan ke halaman utama

Halaman login menampilkan form sederhana dengan dropdown untuk memilih tipe user (User Biasa, UMKM, atau Admin), field email, field password, dan tombol login berwarna hijau di bagian bawah form.

### Menjelajahi Destinasi Wisata

#### Melihat Daftar Wisata
1. Dari halaman utama, klik menu "Wisata" di navbar
2. Anda akan melihat daftar destinasi wisata di Papua
3. Gunakan fitur filter untuk menyaring berdasarkan:
   - Kategori (Alam, Budaya, Sejarah, dll)
   - Pencarian nama wisata

Halaman wisata menampilkan grid kartu-kartu destinasi dengan foto thumbnail, nama wisata, kategori, harga tiket, dan rating. Filter pencarian terletak di bagian atas halaman dengan dropdown kategori dan search box.

#### Melihat Detail Wisata
1. Klik pada kartu wisata yang ingin dilihat
2. Halaman detail akan menampilkan:
   - Foto wisata
   - Deskripsi lengkap
   - Harga tiket masuk
   - Lokasi
   - Jam operasional
   - Review dari pengunjung lain

Halaman detail wisata memiliki layout dengan foto besar di bagian atas, diikuti informasi dasar (harga, jam buka, lokasi) di sebelah kanan foto. Deskripsi lengkap ditampilkan di bawahnya, dan section review berada di bagian paling bawah halaman.

### Melihat Penginapan

#### Browsing Penginapan
1. Klik menu "Penginapan" di navbar
2. Daftar penginapan akan ditampilkan dengan informasi:
   - Nama penginapan
   - Foto
   - Harga per malam
   - Rating
   - Lokasi

Halaman penginapan menampilkan daftar dalam format grid dengan kartu yang berisi foto penginapan, nama, tipe (Hotel/Villa/Homestay), harga per malam yang ditampilkan prominent, rating bintang, dan lokasi singkat.

#### Detail Penginapan
1. Klik pada penginapan yang diminati
2. Informasi yang ditampilkan:
   - Galeri foto
   - Deskripsi lengkap
   - Fasilitas
   - Harga per malam
   - Kontak
   - Review pengunjung

### Berbelanja Produk UMKM

#### Melihat Produk UMKM
1. Dari halaman utama, produk UMKM ditampilkan dalam bentuk artikel
2. Gunakan filter kategori:
   - Makanan & Minuman
   - Kerajinan Tangan
   - Fashion
   - Souvenir
   - Lainnya

Produk UMKM ditampilkan dalam layout masonry dengan kartu artikel yang memiliki gambar produk, judul, harga dalam format rupiah, nama UMKM penjual, dan tombol 'Lihat Detail' di bagian bawah setiap kartu.

#### Menambah ke Keranjang
1. Klik produk untuk melihat detail
2. Pada halaman detail produk:
   - Lihat foto produk
   - Baca deskripsi
   - Cek harga
   - Lihat informasi penjual
3. Klik tombol "Tambah ke Keranjang"
4. Notifikasi akan muncul jika berhasil

Halaman detail produk menampilkan galeri foto di sebelah kiri, informasi produk di sebelah kanan termasuk nama, harga, deskripsi, informasi penjual dengan foto profil UMKM, dan tombol 'Tambah ke Keranjang' berwarna hijau yang prominent.

#### Mengelola Keranjang
1. Klik ikon keranjang di navbar (menampilkan jumlah item)
2. Di halaman keranjang, Anda dapat:
   - Mengubah jumlah item
   - Menghapus item
   - Melihat total harga
3. Klik "Lanjut ke Checkout" untuk pembayaran

Halaman keranjang menampilkan tabel dengan kolom: foto produk (thumbnail), nama produk, harga satuan, field quantity yang bisa diedit, subtotal, dan tombol hapus. Total belanja ditampilkan di bagian bawah kanan dengan tombol 'Lanjut ke Checkout'.

### Menggunakan Chatbot AI

#### Akses Chatbot
1. Login terlebih dahulu (fitur chatbot hanya untuk user terdaftar)
2. Klik menu "AI Assistant" di navbar
3. Interface chat akan terbuka

Interface chatbot berupa jendela chat dengan area percakapan di bagian tengah menampilkan bubble chat user (kanan, warna hijau) dan bot (kiri, warna abu-abu). Input field terletak di bagian bawah dengan tombol kirim di sebelah kanannya.

#### Cara Menggunakan Chatbot
1. Ketik pertanyaan seputar pariwisata Papua di kolom input
2. Contoh pertanyaan:
   - "Apa saja tempat wisata di Jayapura?"
   - "Rekomendasi penginapan murah di Raja Ampat"
   - "Makanan khas Papua apa yang wajib dicoba?"
   - "Bagaimana transportasi ke Wamena?"
3. Tekan Enter atau klik tombol kirim
4. Chatbot akan memberikan respons berdasarkan database informasi Papua

#### Tips Menggunakan Chatbot
- Gunakan bahasa Indonesia yang jelas
- Ajukan pertanyaan spesifik untuk hasil yang lebih baik
- Chatbot dapat membantu tentang:
  - Destinasi wisata
  - Kuliner khas
  - Budaya dan tradisi
  - Transportasi
  - Tips perjalanan

### Memberikan Review

#### Menulis Review
1. Login ke akun Anda
2. Kunjungi halaman detail wisata/penginapan yang pernah dikunjungi
3. Scroll ke bagian review
4. Klik tombol "Tulis Review"
5. Isi formulir review:
   - Rating (1-5 bintang)
   - Judul review
   - Deskripsi pengalaman
   - Upload foto (opsional)
6. Klik "Kirim Review"

Form review terdiri dari: rating bintang yang bisa diklik (1-5), field input untuk judul review, textarea untuk deskripsi pengalaman, tombol upload foto (opsional) dengan preview gambar, dan tombol 'Kirim Review' di bagian bawah.

#### Status Review
- Review akan melalui proses moderasi admin
- Status review dapat dilihat di "Akun Saya"
- Review yang disetujui akan tampil di halaman publik

### Proses Checkout dan Pembayaran

#### Checkout
1. Dari halaman keranjang, klik "Lanjut ke Checkout"
2. Isi informasi pengiriman:
   - Nama penerima
   - Nomor telepon
   - Alamat lengkap
3. Pilih metode pembayaran (Transfer Bank)
4. Review pesanan Anda
5. Klik "Buat Pesanan"

Halaman checkout dibagi dua kolom: kiri untuk form data pengiriman (nama, telepon, alamat), kanan untuk ringkasan pesanan dengan daftar item, subtotal, dan total. Pilihan metode pembayaran (Transfer Bank) ditampilkan dengan radio button.

#### Pembayaran
1. Setelah checkout, Anda akan melihat instruksi pembayaran
2. Transfer ke rekening yang tertera:
   - Bank BCA: 1234567890 a.n. PapuaJourneyExpo
   - Bank Mandiri: 0987654321 a.n. PapuaJourneyExpo
3. Catat nomor transaksi: TRX[timestamp]
4. Upload bukti pembayaran:
   - Klik "Upload Bukti Pembayaran"
   - Pilih file foto/screenshot transfer
   - Klik "Upload"

Halaman upload bukti pembayaran menampilkan informasi rekening tujuan dalam kotak highlight, nomor transaksi Anda, form upload file dengan drag-and-drop area atau tombol browse, preview gambar yang diupload, dan tombol 'Upload Bukti' berwarna hijau.

#### Tracking Pesanan
1. Masuk ke "Akun Saya" > "Pesanan Saya"
2. Status pesanan:
   - Menunggu Pembayaran
   - Menunggu Konfirmasi
   - Pembayaran Dikonfirmasi
   - Diproses
   - Selesai

---

## Panduan untuk UMKM

### Registrasi UMKM

1. Klik "Login" di navbar
2. Pilih "Daftar sebagai UMKM"
3. Isi formulir pendaftaran:
   - Nama Bisnis
   - Nama Pemilik
   - Email (untuk login)
   - Password
   - Nomor Telepon
   - Alamat Bisnis
   - Jenis Bisnis
   - Deskripsi Bisnis
4. Upload dokumen pendukung (KTP, SIUP jika ada)
5. Klik "Daftar"
6. Tunggu verifikasi dari admin

Form registrasi UMKM lebih lengkap dari user biasa, dengan field tambahan: nama bisnis, jenis bisnis (dropdown), deskripsi bisnis (textarea), alamat lengkap bisnis, dan area upload untuk dokumen pendukung (KTP/SIUP) di bagian bawah form.

### Dashboard UMKM

#### Login UMKM
1. Pilih "UMKM" pada tipe login
2. Masukkan email dan password
3. Setelah login, Anda akan masuk ke dashboard UMKM

#### Navigasi Dashboard
Dashboard UMKM memiliki menu:
- Dashboard (ringkasan)
- Kelola Produk
- Pesanan
- Analytics
- Profil

Dashboard UMKM memiliki sidebar menu di kiri dengan ikon untuk setiap menu. Area utama menampilkan widget statistik: total produk, pesanan hari ini, pendapatan bulan ini, dan rating toko dalam bentuk kartu berwarna berbeda.

### Mengelola Produk/Artikel

#### Menambah Produk Baru
1. Klik menu "Kelola Produk"
2. Klik tombol "Tambah Produk Baru"
3. Isi formulir:
   - Judul Produk
   - Deskripsi
   - Harga
   - Kategori
   - Upload Foto Produk (maks 5MB)
4. Klik "Simpan Produk"

Form tambah produk berisi: field judul produk di atas, dropdown kategori, field harga dengan format rupiah, textarea deskripsi dengan text editor, area upload foto dengan preview (maksimal 5 foto), dan tombol 'Simpan Produk' di bagian bawah.

#### Edit/Hapus Produk
1. Di halaman kelola produk, lihat daftar produk Anda
2. Untuk edit: Klik ikon pensil
3. Untuk hapus: Klik ikon tempat sampah
4. Untuk menonaktifkan sementara: Toggle status aktif/nonaktif

### Melihat Pesanan

1. Klik menu "Pesanan"
2. Lihat daftar pesanan masuk dengan status:
   - Baru (perlu diproses)
   - Diproses
   - Selesai
3. Klik pesanan untuk melihat detail:
   - Informasi pembeli
   - Produk yang dipesan
   - Total pembayaran
   - Status pembayaran

Halaman pesanan UMKM menampilkan tabel dengan kolom: ID pesanan, tanggal, nama pembeli, produk (bisa multiple), total, status pesanan (badge berwarna), dan aksi (tombol lihat detail). Filter status tersedia di atas tabel.

### Analytics UMKM

1. Klik menu "Analytics"
2. Lihat statistik bisnis Anda:
   - Total penjualan
   - Jumlah pesanan
   - Produk terlaris
   - Grafik penjualan bulanan
3. Filter berdasarkan periode waktu

Halaman analytics menampilkan: row kartu statistik di atas (total penjualan, jumlah pesanan, produk terlaris, conversion rate), grafik line chart penjualan bulanan di tengah, dan tabel produk dengan performa di bagian bawah.

---

## Panduan untuk Admin

### Login Admin

1. Akses halaman admin: `/admin`
2. Masukkan username dan password admin
3. Klik "Login"

Halaman login admin memiliki desain minimalis dengan logo di atas, form login di tengah layar berisi field username dan password, serta tombol login. Background menggunakan warna gelap untuk membedakan dari login user biasa.

### Dashboard Admin

Dashboard admin menampilkan:
- Statistik pengguna
- Total transaksi
- Pesanan pending
- Grafik aktivitas

Menu admin meliputi:
- Dashboard
- Kelola Wisata
- Kelola Penginapan
- Konfirmasi Pembayaran
- Kelola UMKM
- Kelola Review
- Laporan

Dashboard admin menampilkan grid widget statistik berwarna-warni di bagian atas (total user, transaksi, pending payment, dll), grafik aktivitas harian/mingguan di tengah, dan tabel aktivitas terbaru di bagian bawah halaman.

### Mengelola Wisata

#### Menambah Wisata Baru
1. Klik menu "Kelola Wisata"
2. Klik "Tambah Wisata"
3. Isi formulir:
   - Nama wisata
   - Deskripsi
   - Kategori
   - Harga tiket
   - Lokasi
   - Jam operasional
   - Upload foto
4. Klik "Simpan"

Form wisata admin berisi field lengkap: nama wisata, dropdown kategori, deskripsi dengan rich text editor, harga tiket (bisa multiple untuk WNA/WNI), field lokasi dengan map picker, jam operasional (time picker), dan multiple image upload.

#### Edit/Hapus Wisata
1. Lihat daftar wisata dalam tabel
2. Klik ikon edit untuk mengubah
3. Klik ikon hapus untuk menghapus
4. Konfirmasi aksi

### Mengelola Penginapan

Proses serupa dengan mengelola wisata:
1. Klik menu "Kelola Penginapan"
2. Tambah/Edit/Hapus penginapan
3. Informasi yang dikelola:
   - Nama penginapan
   - Tipe (Hotel, Villa, Homestay)
   - Harga per malam
   - Fasilitas
   - Kontak
   - Foto

### Konfirmasi Pembayaran

1. Klik menu "Konfirmasi Pembayaran"
2. Lihat daftar pembayaran pending
3. Untuk setiap pembayaran:
   - Lihat detail transaksi
   - Lihat bukti pembayaran yang diupload
   - Verifikasi dengan rekening bank
4. Aksi:
   - Klik "Konfirmasi" jika valid
   - Klik "Tolak" jika tidak valid
5. Status akan otomatis terupdate

Halaman konfirmasi pembayaran menampilkan daftar transaksi pending dalam tabel. Klik pada transaksi membuka modal dengan: detail pesanan di kiri, bukti transfer yang diupload user di tengah (bisa di-zoom), dan tombol Konfirmasi/Tolak di bagian bawah modal.

### Laporan dan Analytics

#### Laporan Keuangan
1. Klik menu "Laporan Keuangan"
2. Pilih periode (bulanan/tahunan)
3. Lihat:
   - Total pendapatan
   - Breakdown per kategori
   - Grafik trend
4. Export ke Excel/PDF

Laporan keuangan menampilkan: filter periode di atas (date picker), summary cards untuk total pendapatan dan breakdown kategori, grafik batang pendapatan per kategori, line chart trend bulanan, dan tombol export Excel/PDF di pojok kanan atas.

#### Analytics Wisata
1. Klik menu "Analytics Wisata"
2. Lihat statistik:
   - Wisata paling populer
   - View count
   - Conversion rate
   - Review rating

---

## FAQ dan Troubleshooting

### Pertanyaan Umum

**Q: Apakah bisa browsing tanpa login?**
A: Ya, Anda bisa melihat wisata, penginapan, dan produk UMKM tanpa login. Namun untuk berbelanja, review, dan chatbot perlu login.

**Q: Bagaimana cara reset password?**
A: Hubungi admin melalui kontak yang tersedia untuk reset password.

**Q: Berapa lama konfirmasi pembayaran?**
A: Pembayaran biasanya dikonfirmasi dalam 1x24 jam pada hari kerja.

**Q: Apakah bisa cancel pesanan?**
A: Pesanan bisa dibatalkan selama status masih "Menunggu Pembayaran". Hubungi admin untuk pembatalan setelah pembayaran.

**Q: Bagaimana cara menjadi UMKM verified?**
A: Setelah registrasi, admin akan memverifikasi data Anda dalam 2-3 hari kerja.

### Troubleshooting

**Masalah: Tidak bisa login**
- Pastikan email dan password benar
- Pilih tipe user yang sesuai
- Clear cache browser
- Coba browser lain

**Masalah: Upload foto gagal**
- Pastikan ukuran file < 5MB
- Format harus JPG, PNG, atau GIF
- Cek koneksi internet
- Coba compress foto terlebih dahulu

**Masalah: Chatbot tidak merespons**
- Pastikan sudah login
- Refresh halaman
- Cek koneksi internet
- Coba pertanyaan yang lebih spesifik

**Masalah: Pembayaran tidak terkonfirmasi**
- Pastikan bukti transfer jelas
- Nominal harus sesuai
- Upload ulang jika perlu
- Hubungi admin jika > 24 jam

### Kontak Support

Jika mengalami kendala, hubungi:
- Email: support@papuajourneyexpo.com
- WhatsApp: +62 812-3456-7890
- Jam operasional: Senin-Jumat 09:00-17:00 WIT

---

## Tips Penggunaan Optimal

### Untuk User
- Gunakan filter untuk pencarian lebih cepat
- Baca review sebelum booking
- Manfaatkan chatbot untuk rekomendasi
- Simpan bukti pembayaran

### Untuk UMKM
- Upload foto produk yang menarik
- Deskripsikan produk dengan detail
- Respons pesanan dengan cepat
- Pantau analytics untuk strategi

### Untuk Admin
- Lakukan backup data rutin
- Monitor transaksi suspicious
- Update konten secara berkala
- Respons laporan user dengan cepat

---

