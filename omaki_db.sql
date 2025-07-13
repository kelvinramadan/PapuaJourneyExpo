-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 13, 2025 at 10:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `omaki_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `full_name`, `email`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$s4/pWQOh1Ux41e9c5vgzPuD2E1SP6mXIAxFZ9TSQ4l9BHpYsYwqTi', 'Administrator', 'admin@papuajourney.com', '2025-07-01 16:56:28', '2025-07-01 17:01:22');

-- --------------------------------------------------------

--
-- Table structure for table `admin_payment_logs`
--

CREATE TABLE `admin_payment_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `action` enum('confirmed','rejected') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_payment_logs`
--

INSERT INTO `admin_payment_logs` (`id`, `admin_id`, `transaksi_id`, `action`, `notes`, `created_at`) VALUES
(1, 1, 7, 'confirmed', '', '2025-06-24 13:02:21'),
(2, 1, 7, 'confirmed', '', '2025-06-24 13:02:28'),
(3, 1, 6, 'confirmed', '', '2025-06-24 13:02:36'),
(4, 1, 6, 'confirmed', '', '2025-06-24 13:04:04'),
(5, 1, 8, 'confirmed', '', '2025-06-24 13:04:15'),
(6, 1, 9, 'confirmed', 'bagus', '2025-06-24 13:06:01'),
(7, 1, 11, 'confirmed', '', '2025-06-26 00:40:50'),
(8, 1, 11, 'confirmed', '', '2025-06-26 00:44:30'),
(9, 1, 12, 'confirmed', '', '2025-06-30 15:43:23'),
(10, 1, 12, 'confirmed', '', '2025-06-30 16:07:32'),
(11, 1, 13, 'confirmed', '', '2025-06-30 16:07:36'),
(12, 1, 13, 'confirmed', '', '2025-06-30 16:29:21'),
(13, 1, 14, 'confirmed', '', '2025-06-30 16:29:25'),
(14, 1, 14, 'confirmed', '', '2025-06-30 16:43:07'),
(15, 1, 15, 'confirmed', '', '2025-06-30 16:43:12'),
(16, 1, 15, 'confirmed', '', '2025-06-30 16:44:24'),
(17, 1, 16, 'confirmed', '', '2025-06-30 16:44:30'),
(18, 1, 17, 'confirmed', '', '2025-06-30 20:13:28'),
(19, 1, 18, 'confirmed', '', '2025-07-06 06:00:58');

-- --------------------------------------------------------

--
-- Table structure for table `artikel`
--

CREATE TABLE `artikel` (
  `id` int(11) NOT NULL,
  `umkm_id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `deskripsi` text NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `kategori` enum('jasa','event','kuliner','kerajinan','wisata') NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `artikel`
--

INSERT INTO `artikel` (`id`, `umkm_id`, `judul`, `deskripsi`, `harga`, `kategori`, `gambar`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Snokeling Blue', 'kegiatan rekreasi di perairan dangkal yang memungkinkan Anda menikmati keindahan bawah laut tanpa harus menyelam terlalu dalam atau menggunakan peralatan selam skuba. Anda berenang di permukaan atau dekat permukaan sambil mengamati kehidupan laut, seperti ikan, terumbu karang, dan berbagai biota laut lainnya.', 75000.00, 'wisata', 'artikel_1_1748761560.jpg', 'active', '2025-06-01 07:06:00', '2025-06-01 07:06:00'),
(2, 1, 'Hiu Blue Sky', 'Orang lain berlibur hanya ingin menikmati keindahan pantai dengan pasir putih sambil duduk berjemur dan menikmati sejuknya angin dan suara ombak. Tapi kamu wajib mencoba kegiatan berenang dengan hiu, yang bisa dilakukan di Wayag.\r\n\r\nBerenang dengan segerombolan hiu adalah hal yang langkah bagi banyak orang yang belum pernah liburan ke Wayag Raja Ampat. Karena saat ke Wayag kamu akan kaget dengan banyak hiu yang berenang di sepanjang pinggiran pantai.', 100000.00, 'wisata', 'artikel_1_1748764972.jpg', 'active', '2025-06-01 08:02:52', '2025-06-01 08:02:52'),
(3, 4, 'Tour Guide', 'Kami menyediakan layanan tour guide profesional yang siap menemani Anda menjelajahi keindahan dan keunikan destinasi wisata dengan cara yang lebih personal dan berkesan. Dengan pengalaman, keramahan, dan pengetahuan lokal yang mendalam, kami tidak hanya menjadi pemandu, tapi juga sahabat perjalanan Anda. Setiap rute kami rancang fleksibel sesuai keinginan Anda, menghadirkan pengalaman wisata yang otentik—mulai dari menikmati alam yang memukau, mengenal budaya dan tradisi lokal, hingga mencicipi kuliner khas yang menggugah selera. Keamanan dan kenyamanan Anda adalah prioritas kami, sehingga Anda bisa menikmati liburan tanpa khawatir. Jadikan setiap perjalanan lebih dari sekadar kunjungan, tetapi petualangan yang meninggalkan kesan mendalam bersama kami.', 200000.00, 'jasa', 'artikel_4_1749613163.jpg', 'active', '2025-06-11 03:39:23', '2025-06-19 05:54:51'),
(4, 5, 'Udang Selingkuh Bakar', 'Udang air tawar besar yang berasal dari Wamena, disebut “selingkuh” karena bentuknya mirip lobster. Dibakar dengan bumbu khas Papua dan disajikan dengan sambal colo-colo.', 120000.00, 'kuliner', 'artikel_5_1751775180.jpeg', 'active', '2025-07-06 04:13:00', '2025-07-06 04:14:27'),
(5, 5, 'Papeda Ikan Kuah Kuning', 'ubur sagu kenyal khas Papua, disajikan bersama ikan tongkol dalam kuah kuning gurih berbumbu kunyit dan jeruk nipis.', 70000.00, 'kuliner', 'artikel_5_1751775225.jpg', 'active', '2025-07-06 04:13:45', '2025-07-06 04:20:12'),
(6, 5, 'Sate Ulat Sagu', 'Ulat sagu pilihan dibakar dan diberi bumbu rempah. Camilan berprotein tinggi dan populer di daerah pedalaman.', 45000.00, 'kuliner', 'artikel_5_1751775363.jpeg', 'active', '2025-07-06 04:16:03', '2025-07-06 04:16:03'),
(7, 6, 'Nasi Goreng Ikan Asar', 'Nasi goreng khas Papua dengan taburan ikan asar (ikan asap) dan daun bawang lokal, menciptakan rasa smoky yang menggoda.', 30000.00, 'kuliner', 'artikel_6_1751782538.jpeg', 'active', '2025-07-06 06:15:38', '2025-07-06 06:15:38'),
(8, 6, 'Ikan Bakar Sambal Colo', 'Ikan laut segar khas Danau Sentani yang dibakar dengan bumbu rempah Papua, disajikan bersama sambal colo-colo dan nasi panas.', 70000.00, 'kuliner', 'artikel_6_1751782581.jpg', 'active', '2025-07-06 06:16:21', '2025-07-06 06:16:21'),
(9, 6, 'Udang Saus Sagu Pedas', 'Udang segar dimasak dengan saus khas dari olahan sagu dan cabai lokal, menghasilkan perpaduan rasa gurih dan pedas.', 85000.00, 'kuliner', 'artikel_6_1751782649.jpeg', 'active', '2025-07-06 06:17:29', '2025-07-06 06:17:29'),
(10, 7, 'Paket Pernikahan Adat Papua', 'Lengkap dengan dekorasi, pakaian adat, dan musik tradisional Papua.', 8000000.00, 'event', 'artikel_7_1751783353.jpg', 'active', '2025-07-06 06:29:13', '2025-07-06 06:29:13'),
(11, 8, 'Noken Serat Anggrek Hutan', 'Noken eksklusif dari bahan alami anggrek liar khas Papua.', 230000.00, 'kerajinan', 'artikel_8_1751783601.jpeg', 'active', '2025-07-06 06:33:21', '2025-07-06 06:33:21'),
(12, 8, 'Noken Custom Warna', 'Cocok untuk oleh-oleh dan souvenir resmi dan bisa pilih warna dan ukuran sesuai permintaan.', 150000.00, 'kerajinan', 'artikel_8_1751783678.jpg', 'active', '2025-07-06 06:34:38', '2025-07-06 06:34:38');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_type` enum('wisata','penginapan','artikel') NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_per_unit` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `booking_date` date DEFAULT NULL COMMENT 'For wisata tickets',
  `checkin_date` date DEFAULT NULL COMMENT 'For penginapan',
  `checkout_date` date DEFAULT NULL COMMENT 'For penginapan',
  `notes` text DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `id` int(11) NOT NULL,
  `conversation_id` varchar(36) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message_type` enum('user','bot') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_conversations`
--

INSERT INTO `chat_conversations` (`id`, `conversation_id`, `user_id`, `message_type`, `message`, `created_at`) VALUES
(1, 'aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, 'user', 'Haloo', '2025-07-02 02:48:28'),
(2, 'aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, 'bot', 'Maaf, saya adalah pemandu wisata khusus untuk Jayapura dan tidak punya informasi tentang itu. Apakah ada yang bisa saya bantu seputar destinasi atau kuliner di Jayapura?', '2025-07-02 02:48:52'),
(3, 'aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, 'user', 'Apa saja wisata di jayapura?', '2025-07-02 02:49:16'),
(4, 'aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, 'bot', '👋 **Halo! Selamat datang kembali di Papua Journey!** Saya siap membantu Anda menjelajahi keindahan dan keunikan Jayapura. 😊\n\nAnda bertanya tentang apa saja wisata di Jayapura, ya? Tentu saja! Jayapura punya banyak sekali tempat menarik yang bisa Anda kunjungi, mulai dari keindahan alam, pengalaman budaya, hingga kuliner yang menggoda. Yuk, kita lihat beberapa di antaranya:\n\n## 🏝️ **Destinasi Wisata Jayapura & Sekitarnya**\n\n### **1. Taman Wisata Teluk Youtefa** 📍\n- **Lokasi**: Jayapura, sekitar 8 km dari pusat kota.\n- **Deskripsi**: Ini adalah surga konservasi mangrove yang memukau! Anda bisa berjalan di atas jembatan kayu sepanjang 1,2 km yang membentang di atas hutan mangrove yang lestari.\n- **Daya Tarik**:\n    - Hutan mangrove yang asri dan hijau.\n    - Pemandangan Teluk Youtefa yang indah dan menenangkan.\n    - Habitat berbagai jenis burung, cocok untuk pecinta *bird watching*.\n    - Titik terbaik untuk menikmati matahari terbenam (sunset point) yang menawan! 🌅\n- **Aktivitas**: Berjalan-jalan santai di jembatan kayu, fotografi alam, edukasi tentang konservasi mangrove, atau sekadar menikmati keindahan alam saat sunset.\n- **Waktu Terbaik**: Sore hari, terutama untuk menikmati keindahan sunset.\n- **Penting**: Tempat ini berperan vital dalam menjaga ekosistem mangrove di Jayapura.\n\n### **2. Food Tour Pasar Hamadi** 🍽️\n- **Lokasi**: Pasar tradisional terbesar di Jayapura.\n- **Deskripsi**: Ini bukan sekadar pasar, tapi pengalaman kuliner dan budaya yang tak terlupakan! Anda bisa berkeliling pasar, mencoba berbagai makanan lokal, dan berinteraksi langsung dengan penduduk setempat.\n- **Yang Wajib Dicoba**:\n    - **Keripik pisang** khas Papua.\n    - **Manisan pala** yang unik dan lezat.\n    - **Ikan asap** segar yang menggugah selera.\n    - Berbagai **buah-buahan tropis** yang mungkin belum pernah Anda lihat sebelumnya! 🥭🍍\n- **Biaya**: Sekitar Rp 75.000 - Rp 100.000 per orang untuk tur.\n- **Durasi**: Umumnya 3-4 jam.\n- **Waktu Terbaik**: Pagi hari (sekitar pukul 07:00 - 10:00) saat pasar masih ramai dan barang dagangan masih lengkap.\n\n### **3. Destinasi Populer Lainnya**\nSelain dua tempat di atas, Jayapura dan sekitarnya juga memiliki beberapa destinasi populer lain yang sangat direkomendasikan untuk Anda kunjungi. Meskipun saya belum memiliki detail spesifik tentang aktivitas di sana, saya bisa informasikan lokasinya yang menarik:\n\n*   **Danau Sentani** 🛶\n    > Danau terbesar di Papua yang menawarkan pemandangan alam yang indah dan budaya lokal yang kaya di sekitarnya.\n*   **Pantai Base G** 🏖️\n    > Salah satu pantai ikonik di Jayapura yang terkenal dengan pasir putih dan air birunya.\n*   **Bukit Teletubbies** ⛰️\n    > Bukit dengan pemandangan perbukitan hijau yang unik dan indah, mirip seperti latar tempat di acara Teletubbies. Akses menuju ke sini memang sedikit menantang, seringkali disarankan untuk menggunakan sewa mobil dengan sopir atau paket tur.\n\n---\n\nSemoga informasi ini membantu Anda merencanakan petualangan di Jayapura! Apakah ada destinasi tertentu yang ingin Anda ketahui lebih lanjut, atau mungkin Anda ingin tahu tentang transportasi dan kuliner lainnya? Jangan sungkan bertanya ya! 😊', '2025-07-02 02:49:43'),
(5, 'aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, 'user', 'apa wisata di sorong?', '2025-07-02 02:51:45'),
(6, 'aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, 'bot', 'Mohon maaf sekali, sepertinya ada sedikit kesalahpahaman. Saya adalah **Papua Journey**, pemandu wisata virtual yang fokus utamanya adalah memberikan informasi seputar destinasi wisata, transportasi, budaya, dan kuliner di **Jayapura** dan sekitarnya. 📍\n\nJadi, untuk informasi mengenai wisata di Sorong, saya belum memiliki data detailnya. 🙏\n\nNamun, jika Anda tertarik untuk mengetahui lebih banyak tentang keindahan **Jayapura**, saya siap membantu! Apakah Anda ingin tahu lebih banyak tentang destinasi populer lainnya di Jayapura, rekomendasi kuliner yang menggugah selera, atau mungkin tips penting seputar budaya lokal? 😊 Jangan ragu untuk bertanya ya!', '2025-07-02 02:51:57'),
(7, 'aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, 'user', 'apa wisata di jakarta?', '2025-07-02 02:52:13'),
(8, 'aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, 'bot', 'Mohon maaf sekali, sepertinya ada sedikit kesalahpahaman. Saya adalah **Papua Journey**, pemandu wisata virtual yang fokus utamanya adalah memberikan informasi seputar destinasi wisata, transportasi, budaya, dan kuliner di **Jayapura** dan sekitarnya. 📍\n\nJadi, untuk informasi mengenai wisata di Jakarta, saya belum memiliki data detailnya. 🙏\n\nNamun, jika Anda tertarik untuk mengetahui lebih banyak tentang keindahan **Jayapura**, saya siap membantu! Misalnya, apakah Anda ingin tahu lebih banyak tentang destinasi populer seperti **Danau Sentani** atau **Pantai Base G**, rekomendasi kuliner lokal yang menggugah selera, atau mungkin tips penting seputar transportasi dan budaya lokal? 😊 Jangan ragu untuk bertanya ya!', '2025-07-02 02:52:24'),
(9, 'aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, 'user', 'Berapa harga biaya masuk pantai base g?', '2025-07-02 02:52:37'),
(10, 'aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, 'bot', 'Mohon maaf sekali, sebagai **Papua Journey**, saya belum memiliki informasi detail mengenai **harga tiket masuk** ke **Pantai Base G** saat ini. 🙏\n\nInformasi yang saya miliki lebih berfokus pada deskripsi keindahan pantainya, aktivitas yang bisa dilakukan, serta cara transportasinya.\n\nNamun, jika Anda tertarik, saya bisa berikan informasi lebih lanjut tentang **Pantai Base G** seperti lokasinya, apa saja yang menarik di sana, atau bagaimana cara menuju ke sana.\n\nApakah ada hal lain yang ingin Anda ketahui seputar wisata di **Jayapura**? Mungkin tentang transportasi ke destinasi lain, atau rekomendasi kuliner yang menggugah selera? 😊 Jangan sungkan bertanya ya!', '2025-07-02 02:52:49'),
(11, 'aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, 'user', 'Kalau dari kota jayapura ke pantai base g itu berapa jauh?', '2025-07-02 02:53:29'),
(12, 'aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, 'bot', 'Tentu saja! Saya siap membantu Anda mengetahui lebih banyak tentang perjalanan ke **Pantai Base G** dari pusat kota Jayapura. 😊\n\n## 🚗 **Perjalanan ke Pantai Base G dari Kota Jayapura**\n\n### **Jarak dan Waktu Tempuh** 📍\n- **Jarak**: **Pantai Base G** terletak sekitar **15 km** dari pusat kota Jayapura.\n- **Waktu Tempuh**: Tergantung pada moda transportasi dan kondisi lalu lintas, perjalanan biasanya memakan waktu antara **20 hingga 30 menit**.\n\n### **Pilihan Transportasi**\nUntuk mencapai **Pantai Base G** dari pusat kota Jayapura, Anda memiliki beberapa pilihan transportasi yang nyaman:\n\n*   **1. Menggunakan Ojek** 🏍️\n    - **Perkiraan Biaya**: Rp 30.000 - Rp 40.000\n    - **Perkiraan Durasi**: Sekitar **20 menit**\n    - **Keterangan**: Ini adalah pilihan yang paling cepat dan langsung. Anda bisa menemukan ojek dengan mudah di berbagai titik di Jayapura.\n\n*   **2. Menggunakan Angkot** 🚐\n    - **Perkiraan Biaya**: Rp 10.000\n    - **Perkiraan Durasi**: Sekitar **30 menit**\n    - **Rute**: Anda bisa naik angkot dengan tujuan \"Base G\" dari pusat kota Jayapura. Ini pilihan yang lebih ekonomis.\n\n> 💡 **Tips Tambahan**:\n> Pastikan Anda menanyakan harga terlebih dahulu saat menggunakan ojek atau taksi untuk menghindari kesalahpahaman. Untuk pengalaman yang lebih fleksibel, Anda juga bisa menyewa kendaraan pribadi jika bepergian dalam kelompok.\n\n---\n\n**Pantai Base G** sendiri terkenal dengan pasir putihnya yang bersih dan air lautnya yang jernih. Sangat cocok untuk berenang, snorkeling, atau sekadar menikmati pemandangan sunset yang indah! 🌅\n\nApakah ada hal lain yang ingin Anda tanyakan seputar **Pantai Base G** atau destinasi wisata menarik lainnya di Jayapura? Jangan ragu untuk bertanya ya! 😊', '2025-07-02 02:53:44'),
(13, '78e1a2e9-2ae6-4f8b-9a4b-d3f572e7fac8', 1, 'user', 'Apa wisata di jayapura', '2025-07-02 04:01:08'),
(14, '78e1a2e9-2ae6-4f8b-9a4b-d3f572e7fac8', 1, 'bot', '👋 Halo! Selamat datang di Papua Journey! Saya siap membantu Anda menjelajahi keindahan Jayapura.\n\nAnda bertanya tentang wisata di Jayapura, ya? Tentu saja! Jayapura punya banyak tempat menarik yang siap memukau Anda. Yuk, kita lihat beberapa di antaranya:\n\n## 🏝️ **Destinasi Wisata Jayapura yang Wajib Dikunjungi**\n\n### 1. **Taman Wisata Teluk Youtefa** 📍\n- **Deskripsi**: Sebuah kawasan konservasi mangrove yang sangat indah, dilengkapi dengan jembatan kayu sepanjang 1,2 km yang membentang di atas hutan mangrove.\n- **Aktivitas**:\n    - Berjalan santai di jembatan kayu sambil menikmati pemandangan Teluk Youtefa yang menawan.\n    - Mengamati berbagai jenis burung (bird watching) yang menjadikan mangrove sebagai habitatnya.\n    - Berfotografi dengan latar belakang alam yang memukau.\n    - Menikmati pemandangan matahari terbenam (sunset) yang spektakuler.\n- **Lokasi**: Sekitar 8 km dari pusat kota Jayapura.\n- **Waktu Terbaik**: Sore hari, terutama untuk menikmati keindahan *sunset*.\n\n### 2. **Pantai Base G** 🏖️\n- **Deskripsi**: Salah satu pantai paling terkenal di Jayapura dengan pasir putih dan air laut yang jernih. Cocok untuk bersantai dan menikmati suasana pantai.\n- **Aktivitas**: Berenang, bersantai di tepi pantai, atau sekadar menikmati pemandangan laut yang indah.\n\n### 3. **Danau Sentani** 🏞️\n- **Deskripsi**: Danau terbesar di Papua yang dikelilingi oleh pegunungan hijau dan perkampungan adat. Menawarkan pemandangan alam yang asri dan budaya lokal yang kaya.\n- **Aktivitas**:\n    - Menyusuri danau dengan perahu.\n    - Mengunjungi pulau-pulau kecil di tengah danau.\n    - Menikmati keindahan alam dan budaya masyarakat adat Sentani.\n\n### 4. **Bukit Teletubbies** ⛰️\n- **Deskripsi**: Julukan untuk deretan bukit bergelombang yang menyerupai latar tempat tinggal karakter Teletubbies, dengan padang rumput hijau yang luas.\n- **Aktivitas**: Menikmati pemandangan alam yang menakjubkan, berfoto dengan latar perbukitan hijau.\n- **Catatan**: Akses ke sini sedikit menantang, disarankan menggunakan kendaraan sewaan atau paket tur.\n\n## 🍽️ **Wisata Kuliner di Jayapura**\n\n### **Food Tour Pasar Hamadi** 🍍🦐\n- **Deskripsi**: Pengalaman tur kuliner yang mengajak Anda berkeliling pasar tradisional terbesar di Jayapura.\n- **Highlights**:\n    - Mencicipi berbagai makanan lokal khas Papua.\n    - Belajar tentang bahan-bahan tradisional.\n    - Berinteraksi langsung dengan pedagang lokal.\n    - Membeli oleh-oleh khas Papua.\n- **Wajib Dicoba**: Keripik pisang, manisan pala, ikan asap, dan berbagai buah-buahan tropis segar.\n- **Waktu Terbaik**: Pagi hari (07:00 - 10:00) agar Anda bisa merasakan hiruk pikuk pasar dan mendapatkan makanan segar.\n\n> 💡 **Tips Tambahan**: Jangan ragu untuk mencoba kuliner lokal yang mungkin belum pernah Anda temui sebelumnya. Aroma rempah dan rasa yang unik pasti akan jadi pengalaman tak terlupakan!\n\nApakah ada destinasi tertentu yang ingin Anda ketahui lebih lanjut, atau mungkin Anda ingin tahu tentang transportasi dan cara menuju tempat-tempat ini? 😊', '2025-07-02 04:01:48'),
(15, '78e1a2e9-2ae6-4f8b-9a4b-d3f572e7fac8', 1, 'user', 'Apa kuliner ter enak di jayapura', '2025-07-02 04:02:28'),
(16, '78e1a2e9-2ae6-4f8b-9a4b-d3f572e7fac8', 1, 'bot', 'Tentu saja! Mencari kuliner \"terenak\" memang seru sekali, apalagi di Jayapura yang punya cita rasa khas Papua! 😋\n\nMeskipun rasa itu subjektif, saya bisa rekomendasikan beberapa pilihan kuliner yang sangat populer dan wajib Anda coba untuk merasakan keaslian Jayapura. Siap-siap ngiler ya!\n\n## 🍽️ **Kuliner Jayapura yang Wajib Dicoba!**\n\n### 1. **Papeda dan Ikan Kuah Kuning** 🐟🍚\n> Ini adalah **hidangan ikonik Papua** yang wajib Anda cicipi! Papeda adalah makanan pokok berupa bubur sagu kental yang disajikan dengan ikan kuah kuning yang kaya rempah. Rasanya asam, gurih, dan segar, sangat cocok dipadukan dengan papeda yang tawar.\n\n- **Tempat Rekomendasi**:\n    - **Rumah Makan Papua Asli** 📍\n        - **Lokasi**: Jl. Ahmad Yani, Jayapura.\n        - **Spesialisasi**: Papeda, Ikan kuah kuning, Sayur ganemo.\n        - **Kisaran Harga**: Rp 25.000 - 75.000 per porsi.\n        - **Suasana**: Tradisional Papua dengan dekorasi khas, memberikan pengalaman makan yang otentik.\n        - **Jam Buka**: 10:00 - 22:00 WIT.\n\n### 2. **Seafood Bakar (Ikan, Udang, Cumi)** 🦐🦑\n> Berada di pesisir, Jayapura punya hasil laut segar yang luar biasa! Ikan, udang, atau cumi bakar dengan bumbu khas Papua akan menggoyang lidah Anda. Biasanya disajikan dengan sambal dabu-dabu atau sambal rica yang pedasnya nendang!\n\n- **Tempat Rekomendasi**:\n    - **Warung Ikan Bakar Hamadi** ⭐\n        - **Lokasi**: Pasar Hamadi, Jayapura (area pinggir laut).\n        - **Spesialisasi**: Ikan bakar, Udang bakar, Cumi bakar.\n        - **Kisaran Harga**: Rp 30.000 - 100.000 per porsi, tergantung jenis dan ukuran *seafood*.\n        - **Suasana**: Warung pinggir laut yang casual dan ramai, cocok untuk menikmati santapan sambil merasakan angin laut.\n        - **Jam Buka**: 16:00 - 23:00 WIT.\n\n### 3. **Camilan & Buah Khas Pasar Hamadi** 🍍🍌\n> Selain hidangan berat, jangan lewatkan petualangan kuliner di pasar tradisional! Di sini Anda bisa menemukan berbagai camilan dan buah-buahan unik Papua.\n\n- **Yang Wajib Dicoba**:\n    - **Keripik pisang**: Krispi dan manis, cocok untuk oleh-oleh.\n    - **Manisan pala**: Rasa manis asam yang menyegarkan dari buah pala asli Papua.\n    - **Ikan asap**: Ikan segar yang diasap dengan metode tradisional, aromanya khas dan rasanya gurih.\n    - Berbagai **buah-buahan tropis segar** yang mungkin jarang Anda temui di daerah lain.\n\n- **Pengalaman Terbaik**: Ikuti **Food Tour Pasar Hamadi** yang sudah kita bahas sebelumnya! Ini cara terbaik untuk mencicipi beragam jajanan dan berinteraksi langsung dengan budaya lokal.\n    - **Waktu Terbaik**: Pagi hari (07:00 - 10:00) untuk merasakan hiruk pikuk pasar dan mendapatkan pilihan makanan paling segar.\n\n> 💡 **Tips Kuliner**: Jangan takut untuk mencoba hal baru! Masyarakat Jayapura sangat ramah, Anda bisa bertanya rekomendasi langsung kepada penduduk lokal atau pedagang untuk pengalaman kuliner terbaik.\n\nSemoga petualangan kuliner Anda di Jayapura semakin berkesan! Ada lagi yang ingin Anda ketahui tentang wisata Jayapura? 😊', '2025-07-02 04:02:46');

-- --------------------------------------------------------

--
-- Table structure for table `chat_conversation_sessions`
--

CREATE TABLE `chat_conversation_sessions` (
  `conversation_id` varchar(36) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_message_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `message_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_conversation_sessions`
--

INSERT INTO `chat_conversation_sessions` (`conversation_id`, `user_id`, `created_at`, `last_message_at`, `message_count`) VALUES
('78e1a2e9-2ae6-4f8b-9a4b-d3f572e7fac8', 1, '2025-07-02 04:01:08', '2025-07-02 04:02:46', 4),
('aca6848f-e2cf-45cb-93d5-6a9f36e195f5', 8, '2025-07-02 02:48:28', '2025-07-02 02:53:44', 12);

-- --------------------------------------------------------

--
-- Table structure for table `pemesanan`
--

CREATE TABLE `pemesanan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `wisata_id` int(11) NOT NULL,
  `wisata_judul` varchar(255) NOT NULL,
  `jumlah_tiket` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `tanggal_kunjungan` date NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pemesanan`
--

INSERT INTO `pemesanan` (`id`, `user_id`, `user_name`, `user_email`, `wisata_id`, `wisata_judul`, `jumlah_tiket`, `harga_satuan`, `total_harga`, `tanggal_kunjungan`, `catatan`, `created_at`) VALUES
(1, 1, 'Brian Domanii', 'brian@gmail.com', 5, 'TropicSurf “Secret Papua” Tour', 2, 409938.00, 819876.00, '2025-06-19', 'ditunggu', '2025-06-19 11:46:41');

-- --------------------------------------------------------

--
-- Table structure for table `pemesanan_tiket`
--

CREATE TABLE `pemesanan_tiket` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `artikel_id` int(11) NOT NULL,
  `jumlah_tiket` int(11) NOT NULL DEFAULT 1,
  `total_harga` decimal(10,2) NOT NULL,
  `nama_pemesan` varchar(255) NOT NULL,
  `email_pemesan` varchar(255) NOT NULL,
  `phone_pemesan` varchar(20) DEFAULT NULL,
  `tanggal_kunjungan` date NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pemesanan_tiket`
--

INSERT INTO `pemesanan_tiket` (`id`, `user_id`, `artikel_id`, `jumlah_tiket`, `total_harga`, `nama_pemesan`, `email_pemesan`, `phone_pemesan`, `tanggal_kunjungan`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 1, 100000.00, 'Brian Domanii', 'brian@gmail.com', '082133871850', '2025-06-21', 'aaaa', '2025-06-19 10:21:48', '2025-06-19 10:21:48'),
(2, 1, 1, 1, 75000.00, 'Brian Domanii', 'brian@gmail.com', '082133871850', '2025-06-20', 'zzz', '2025-06-19 10:22:33', '2025-06-19 10:22:33'),
(3, 1, 3, 3, 600000.00, 'Brian Domanii', 'brian@gmail.com', '082133871850', '2025-06-20', 'qqqq', '2025-06-19 10:23:57', '2025-06-19 10:23:57'),
(4, 3, 2, 10, 1000000.00, 'Naura Tsani Maya', 'naura@gmail.com', '082324096996', '2025-06-20', 'aaaa', '2025-06-19 10:25:50', '2025-06-19 10:25:50');

-- --------------------------------------------------------

--
-- Table structure for table `penginapan`
--

CREATE TABLE `penginapan` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `lokasi` varchar(255) NOT NULL,
  `tipe` enum('hotel','villa','resort') NOT NULL,
  `fasilitas` text NOT NULL,
  `photo` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penginapan`
--

INSERT INTO `penginapan` (`id`, `judul`, `deskripsi`, `harga`, `lokasi`, `tipe`, `fasilitas`, `photo`, `created_at`, `updated_at`) VALUES
(1, 'Hotel Aston Jayapura', 'Hotel mewah di pusat kota Jayapura dengan pemandangan teluk yang menakjubkan. Terletak strategis dekat dengan pusat bisnis dan objek wisata.', 850000.00, 'Jayapura, Papua', 'hotel', 'WiFi Gratis, AC, Restaurant, Gym, Swimming Pool, Room Service', 'hotel_aston.jpg', '2025-06-15 04:36:21', '2025-06-15 04:36:21'),
(2, 'Villa Hamadi Beach', 'Villa eksklusif di tepi pantai Hamadi dengan suasana tradisional Papua yang autentik. Cocok untuk liburan keluarga yang berkesan.', 1200000.00, 'Hamadi, Jayapura', 'villa', 'Private Beach, WiFi, Kitchen, BBQ Area, Traditional Decor', 'villa_hamadi.jpg', '2025-06-15 04:36:21', '2025-06-15 04:36:21'),
(3, 'Resort Sentani Lake', 'Resort premium di tepi Danau Sentani dengan arsitektur modern yang memadukan unsur budaya Papua. Pengalaman menginap yang tak terlupakan.', 1500000.00, 'Sentani, Jayapura', 'resort', 'Lake View, Spa, Restaurant, Boat Rental, Cultural Show, WiFi', 'resort_sentani.jpg', '2025-06-15 04:36:21', '2025-06-15 04:36:21'),
(4, 'Papua Paradise Eco Resort', 'Terletak di pulau tak berpenghuni di Raja Ampat, resort ini menyajikan bungalow di atas laut yang dibangun dari kayu lokal. Suasana tenang dipadu alam: hutan tropis, laguna, dan terumbu karang langsung di halaman. Ada spa eksklusif dengan pemandangan laut, satu-satunya di wilayah ini .', 3500000.00, 'Birie Island, Arefi, Selat Sagawin, Kabupaten Raja Ampat, Papua Barat, Pulau Birie', 'resort', 'bungalow terapung , Restoran “Seaview” , Spa over‑water , Shuttle bandara Sorong , Bar  ', '1750058523_429742622.jpg', '2025-06-16 07:22:03', '2025-06-16 07:22:03'),
(6, 'Grand Papua Hotel', 'Hotel mewah bintang 5 di pusat kota Jayapura dengan pemandangan teluk yang memukau. Dilengkapi dengan fasilitas modern dan pelayanan premium untuk wisatawan bisnis dan leisure.', 950000.00, 'Jayapura, Papua', 'hotel', 'WiFi Gratis, AC, Restaurant, Gym, Swimming Pool, Room Service, Spa, Conference Room, Airport Shuttle', 'grandpapua.jpg', '2025-07-08 14:34:19', '2025-07-09 02:31:35'),
(7, 'Sentani Lake Resort', 'Resort eksklusif di tepi Danau Sentani dengan arsitektur tradisional Papua yang memukau. Nikmati keindahan alam Papua dengan fasilitas modern yang lengkap.', 1200000.00, 'Sentani, Jayapura', 'resort', 'Lake View, Spa, Restaurant, Boat Rental, Cultural Tours, WiFi, AC, Private Beach, Traditional Dance Show', 'sentanilakeresort.jpg', '2025-07-08 14:34:19', '2025-07-09 02:31:20'),
(8, 'Villa Hamadi Executive', 'Villa eksklusif dengan desain modern minimalis di kawasan elit Hamadi. Cocok untuk keluarga atau grup yang menginginkan privasi dan kenyamanan maksimal.', 1500000.00, 'Hamadi, Jayapura', 'villa', 'Private Pool, WiFi, Kitchen, BBQ Area, Garden, Parking, AC, Living Room, 3 Bedrooms', 'villahamadeexecutive.jpg', '2025-07-08 14:34:19', '2025-07-09 02:33:04'),
(9, 'Raja Ampat Paradise Resort', 'Resort ramah lingkungan di Raja Ampat dengan pemandangan laut yang spektakuler. Surga bagi pecinta diving dan snorkeling dengan keanekaragaman hayati laut yang luar biasa.', 2500000.00, 'Raja Ampat, Papua Barat', 'resort', 'Diving Center, Snorkeling, Restaurant, Bar, Spa, WiFi, Eco-friendly, Boat Tours, Marine Conservation', 'rajaampatparadiseresort.jpg', '2025-07-08 14:34:19', '2025-07-09 02:32:04'),
(10, 'Arfai Cultural Homestay', 'Homestay tradisional di Kampung Arfai yang menawarkan pengalaman budaya Papua yang autentik. Tinggal bersama keluarga lokal dan rasakan kehidupan sehari-hari masyarakat Papua.', 350000.00, 'Arfai, Manokwari', '', 'Traditional House, Local Food, Cultural Tours, Bird Watching, Trekking Guide, WiFi, Shared Bathroom', 'arfaihomestay.jpg', '2025-07-08 14:34:19', '2025-07-09 02:30:48'),
(11, 'Marina Bay Hotel Sorong', 'Hotel bisnis modern di pusat kota Sorong dengan akses mudah ke pelabuhan dan bandara. Fasilitas lengkap untuk wisatawan bisnis dan transit ke Raja Ampat.', 750000.00, 'Sorong, Papua Barat', 'hotel', 'WiFi Gratis, AC, Restaurant, Business Center, Airport Shuttle, Laundry, 24h Reception, Parking', 'marinabayhotelsorong.jpg', '2025-07-08 14:34:19', '2025-07-09 02:29:27'),
(12, 'Mansinam Beach Bungalow', 'Bungalow tepi pantai di Pulau Mansinam dengan suasana tenang dan pemandangan laut yang indah. Tempat ideal untuk bulan madu atau liburan romantis.', 650000.00, 'Pulau Mansinam, Manokwari', '', 'Beach Front, Restaurant, Snorkeling, Kayak, WiFi, AC, Private Terrace, Sunset View', 'https://images.unsplash.com/photo-1520637836862-4d197d17c43a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1486&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(13, 'Wamena Highland Guesthouse', 'Guesthouse sederhana di dataran tinggi Wamena dengan pemandangan pegunungan yang spektakuler. Basis ideal untuk eksplorasi Lembah Baliem dan budaya Dani.', 450000.00, 'Wamena, Papua', '', 'Mountain View, Trekking Guide, Local Tours, WiFi, Heater, Traditional Food, Cultural Experience', 'https://images.unsplash.com/photo-1586375300773-8384e3e4916f?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(14, 'Cenderawasih Bay Resort', 'Resort eksklusif di Teluk Cenderawasih dengan fasilitas diving world-class. Nikmati keindahan whale shark dan terumbu karang yang menakjubkan.', 1800000.00, 'Nabire, Papua', 'resort', 'Diving Center, Whale Shark Tours, Restaurant, Bar, Spa, WiFi, Boat Rental, Marine Life Tours', 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1480&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(15, 'Asmat Cultural Lodge', 'Penginapan unik di tengah hutan Asmat dengan pengalaman budaya yang mendalam. Saksikan langsung seni ukir kayu dan kehidupan tradisional suku Asmat.', 550000.00, 'Agats, Papua', '', 'Cultural Tours, Wood Carving Workshop, Traditional Food, River Tours, WiFi, Mosquito Net, Local Guide', 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1471&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(16, 'Biak Oceanview Villa', 'Villa mewah dengan pemandangan laut lepas di Pulau Biak. Fasilitas lengkap dengan private pool dan akses langsung ke pantai berpasir putih.', 1100000.00, 'Biak, Papua', 'villa', 'Ocean View, Private Pool, Beach Access, WiFi, AC, Kitchen, BBQ Area, Snorkeling Equipment', 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(17, 'Timika Grand Hotel', 'Hotel bisnis terdepan di Timika dengan fasilitas modern dan pelayanan profesional. Lokasi strategis dekat dengan area pertambangan dan perkantoran.', 850000.00, 'Timika, Papua', 'hotel', 'WiFi Gratis, AC, Restaurant, Business Center, Conference Room, Gym, Laundry, Airport Transfer', 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1632&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(18, 'Mamberamo River Lodge', 'Eco lodge di tepi Sungai Mamberamo yang menawarkan petualangan jungle trekking dan river rafting. Pengalaman nature yang tidak terlupakan.', 700000.00, 'Mamberamo, Papua', '', 'River View, Jungle Trekking, River Rafting, Bird Watching, WiFi, Eco-friendly, Local Guide, Traditional Meals', 'https://images.unsplash.com/photo-1501436513145-30f24e19fcc4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1548&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(19, 'Paniai Lake Resort', 'Resort tenang di tepi Danau Paniai dengan pemandangan danau yang memesona. Cocok untuk retreat dan aktivitas air yang menyenangkan.', 950000.00, 'Danau Paniai, Papua', 'resort', 'Lake View, Boat Rental, Fishing, Restaurant, Spa, WiFi, AC, Water Sports, Mountain Trekking', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(20, 'Merauke Frontier Guesthouse', 'Guesthouse sederhana di kota paling timur Indonesia dengan nuansa frontier yang unik. Basis ideal untuk eksplorasi perbatasan dan Taman Nasional Wasur.', 400000.00, 'Merauke, Papua', '', 'WiFi, AC, Restaurant, Tour Guide, National Park Tours, Bird Watching, Traditional Food, Bicycle Rental', 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1449&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(21, 'Kaimana Floating Resort', 'Resort terapung unik di perairan Kaimana dengan akses langsung ke spot diving terbaik. Pengalaman menginap yang tak terlupakan di atas laut.', 1600000.00, 'Kaimana, Papua Barat', 'resort', 'Floating Platform, Diving Center, Snorkeling, Restaurant, Bar, Sunset View, Marine Tours, WiFi', 'https://images.unsplash.com/photo-1540541338287-41700207dee6?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(22, 'Sawinggrai Village Homestay', 'Homestay di kampung nelayan Sawinggrai dengan pengalaman bird watching yang luar biasa. Saksikan langsung burung Cendrawasih di habitat aslinya.', 300000.00, 'Sawinggrai, Raja Ampat', '', 'Bird Watching, Local Family, Traditional Food, Snorkeling, Village Tours, WiFi, Shared Facilities', 'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1465&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(23, 'Jayawijaya Mountain Hotel', 'Hotel pegunungan dengan pemandangan Puncak Jaya Wijaya yang menakjubkan. Fasilitas lengkap untuk pendaki dan petualang yang ingin menaklukkan puncak tertinggi Indonesia.', 1000000.00, 'Jayawijaya, Papua', 'hotel', 'Mountain View, Climbing Guide, Equipment Rental, Restaurant, WiFi, Heater, Trekking Tours, Medical Facility', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(24, 'Bosnik Beach Bungalow', 'Bungalow tepi pantai di Pantai Bosnik Biak dengan pasir putih dan air laut yang jernih. Tempat ideal untuk surfing dan aktivitas pantai lainnya.', 500000.00, 'Bosnik, Biak', '', 'Beach Front, Surfing, Restaurant, WiFi, AC, Water Sports, Sunset View, Beach Volleyball', 'https://images.unsplash.com/photo-1587061949468-3d06cce5f6b6?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1470&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19'),
(25, 'Arfak Treehouse Lodge', 'Lodge pohon unik di Pegunungan Arfak dengan pengalaman menginap yang tidak biasa. Nikmati keindahan hutan hujan tropis Papua dari ketinggian.', 800000.00, 'Pegunungan Arfak, Manokwari', '', 'Treehouse, Forest View, Bird Watching, Jungle Trekking, Traditional Food, WiFi, Eco-friendly, Nature Guide', 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1471&q=80', '2025-07-08 14:34:19', '2025-07-08 14:34:19');

-- --------------------------------------------------------

--
-- Stand-in structure for view `penginapan_popularity`
-- (See below for the actual view)
--
CREATE TABLE `penginapan_popularity` (
`id` int(11)
,`judul` varchar(255)
,`tipe` enum('hotel','villa','resort')
,`harga` decimal(10,2)
,`photo` varchar(255)
,`lokasi` varchar(255)
,`views_today` int(11)
,`views_this_week` decimal(32,0)
,`views_this_month` decimal(32,0)
,`bookings_this_week` decimal(32,0)
,`bookings_this_month` decimal(32,0)
,`revenue_this_month` decimal(37,2)
,`conversion_rate_week` decimal(38,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `penginapan_statistics`
--

CREATE TABLE `penginapan_statistics` (
  `id` int(11) NOT NULL,
  `penginapan_id` int(11) NOT NULL,
  `stat_date` date NOT NULL,
  `view_count` int(11) DEFAULT 0,
  `unique_visitors` int(11) DEFAULT 0,
  `booking_count` int(11) DEFAULT 0,
  `revenue` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penginapan_statistics`
--

INSERT INTO `penginapan_statistics` (`id`, `penginapan_id`, `stat_date`, `view_count`, `unique_visitors`, `booking_count`, `revenue`, `created_at`, `updated_at`) VALUES
(1, 1, '2025-06-27', 0, 0, 0, 0.00, '2025-06-26 21:21:47', '2025-06-26 21:21:47'),
(2, 2, '2025-06-27', 1, 1, 0, 0.00, '2025-06-26 21:21:47', '2025-06-26 21:39:07'),
(3, 3, '2025-06-27', 0, 0, 0, 0.00, '2025-06-26 21:21:47', '2025-06-26 21:21:47'),
(4, 4, '2025-06-27', 0, 0, 0, 0.00, '2025-06-26 21:21:47', '2025-06-26 21:21:47'),
(9, 4, '2025-06-30', 1, 1, 0, 0.00, '2025-06-30 16:42:29', '2025-06-30 16:42:29'),
(10, 22, '2025-07-13', 1, 1, 0, 0.00, '2025-07-13 15:14:38', '2025-07-13 15:14:38');

-- --------------------------------------------------------

--
-- Table structure for table `penginapan_views`
--

CREATE TABLE `penginapan_views` (
  `id` int(11) NOT NULL,
  `penginapan_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `view_date` datetime DEFAULT current_timestamp(),
  `session_id` varchar(128) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penginapan_views`
--

INSERT INTO `penginapan_views` (`id`, `penginapan_id`, `user_id`, `ip_address`, `view_date`, `session_id`, `user_agent`) VALUES
(1, 2, 1, '::1', '2025-06-27 04:39:07', '6f02u1hb506npcldu15372j7qn', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(2, 4, 8, '::1', '2025-06-30 23:42:29', '88qdatrka6tgd4g1qdp1trb4r3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(3, 22, 1, '::1', '2025-07-13 22:14:38', '3vfn6nhtmn8nloc985svg5ce3r', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Table structure for table `pesanpenginapan`
--

CREATE TABLE `pesanpenginapan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `penginapan_id` int(11) NOT NULL,
  `penginapan_judul` varchar(255) NOT NULL,
  `jumlah_kamar` int(11) NOT NULL,
  `jumlah_malam` int(11) NOT NULL,
  `harga_per_malam` decimal(10,2) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `tanggal_checkin` date NOT NULL,
  `tanggal_checkout` date NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesanpenginapan`
--

INSERT INTO `pesanpenginapan` (`id`, `user_id`, `user_name`, `user_email`, `penginapan_id`, `penginapan_judul`, `jumlah_kamar`, `jumlah_malam`, `harga_per_malam`, `total_harga`, `tanggal_checkin`, `tanggal_checkout`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 1, 'Brian Domanii', 'brian@gmail.com', 4, 'Papua Paradise Eco Resort', 2, 1, 3500000.00, 7000000.00, '0000-00-00', '2025-06-21', 'ditunggu', '2025-06-19 19:50:07', '2025-06-19 19:50:07');

-- --------------------------------------------------------

--
-- Table structure for table `platform_financial_statistics`
--

CREATE TABLE `platform_financial_statistics` (
  `id` int(11) NOT NULL,
  `stat_date` date NOT NULL,
  `total_transactions` int(11) DEFAULT 0,
  `successful_transactions` int(11) DEFAULT 0,
  `failed_transactions` int(11) DEFAULT 0,
  `total_revenue` decimal(15,2) DEFAULT 0.00,
  `wisata_revenue` decimal(15,2) DEFAULT 0.00,
  `penginapan_revenue` decimal(15,2) DEFAULT 0.00,
  `artikel_revenue` decimal(15,2) DEFAULT 0.00,
  `avg_transaction_value` decimal(15,2) DEFAULT 0.00,
  `unique_customers` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `platform_financial_statistics`
--

INSERT INTO `platform_financial_statistics` (`id`, `stat_date`, `total_transactions`, `successful_transactions`, `failed_transactions`, `total_revenue`, `wisata_revenue`, `penginapan_revenue`, `artikel_revenue`, `avg_transaction_value`, `unique_customers`, `created_at`, `updated_at`) VALUES
(1, '2025-07-02', 0, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0, '2025-07-02 12:41:35', '2025-07-02 12:41:35'),
(2, '2025-07-01', 0, 0, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0, '2025-07-02 12:41:35', '2025-07-02 12:41:35');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `item_type` enum('wisata','penginapan','artikel') NOT NULL,
  `item_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text NOT NULL,
  `is_verified` tinyint(1) DEFAULT 1 COMMENT 'Auto-verified for paid transactions',
  `is_visible` tinyint(1) DEFAULT 1 COMMENT 'Admin can hide inappropriate reviews',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `user_id`, `transaksi_id`, `item_type`, `item_id`, `rating`, `review_text`, `is_verified`, `is_visible`, `created_at`, `updated_at`) VALUES
(1, 8, 11, 'wisata', 6, 5, 'Pantainya indah banget', 1, 1, '2025-06-30 15:44:15', '2025-06-30 15:44:15'),
(2, 8, 14, 'wisata', 4, 4, 'Sangat Indah', 1, 1, '2025-06-30 16:29:56', '2025-06-30 16:29:56'),
(3, 8, 16, 'artikel', 1, 5, 'mantap banget', 1, 1, '2025-06-30 20:10:08', '2025-06-30 20:10:08'),
(4, 8, 13, 'artikel', 3, 5, 'Adminnya memiliki wawasan yang luas tentang wisata nya!', 1, 1, '2025-06-30 20:11:23', '2025-06-30 20:11:23'),
(5, 8, 17, 'artikel', 1, 4, 'Seru bangett', 1, 1, '2025-06-30 20:13:52', '2025-06-30 20:13:52'),
(6, 1, 18, 'artikel', 4, 5, 'Awalnya kek ngerasa mahal kan, niatnya nyoba eh taunya enak bgtt, besok klo kesini mau rebuy enakkkk', 1, 1, '2025-07-06 06:06:11', '2025-07-06 06:06:11'),
(7, 1, 18, 'artikel', 6, 5, 'Underestimate menjijikan ternyata enakkk', 1, 1, '2025-07-06 06:06:32', '2025-07-06 06:06:32');

--
-- Triggers `reviews`
--
DELIMITER $$
CREATE TRIGGER `update_review_cache_delete` AFTER DELETE ON `reviews` FOR EACH ROW BEGIN
    IF OLD.is_visible = 1 THEN
        -- Recalculate from scratch
        INSERT INTO review_summary_cache (item_type, item_id, total_reviews, average_rating,
            rating_1_count, rating_2_count, rating_3_count, rating_4_count, rating_5_count)
        SELECT 
            OLD.item_type,
            OLD.item_id,
            COUNT(*),
            IFNULL(AVG(rating), 0),
            SUM(IF(rating = 1, 1, 0)),
            SUM(IF(rating = 2, 1, 0)),
            SUM(IF(rating = 3, 1, 0)),
            SUM(IF(rating = 4, 1, 0)),
            SUM(IF(rating = 5, 1, 0))
        FROM reviews
        WHERE item_type = OLD.item_type 
            AND item_id = OLD.item_id 
            AND is_visible = 1
        ON DUPLICATE KEY UPDATE
            total_reviews = VALUES(total_reviews),
            average_rating = VALUES(average_rating),
            rating_1_count = VALUES(rating_1_count),
            rating_2_count = VALUES(rating_2_count),
            rating_3_count = VALUES(rating_3_count),
            rating_4_count = VALUES(rating_4_count),
            rating_5_count = VALUES(rating_5_count);
        
        -- Delete cache entry if no reviews left
        DELETE FROM review_summary_cache 
        WHERE item_type = OLD.item_type 
            AND item_id = OLD.item_id 
            AND total_reviews = 0;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_review_cache_insert` AFTER INSERT ON `reviews` FOR EACH ROW BEGIN
    IF NEW.is_visible = 1 THEN
        INSERT INTO review_summary_cache (item_type, item_id, total_reviews, average_rating,
            rating_1_count, rating_2_count, rating_3_count, rating_4_count, rating_5_count)
        VALUES (NEW.item_type, NEW.item_id, 1, NEW.rating,
            IF(NEW.rating = 1, 1, 0),
            IF(NEW.rating = 2, 1, 0),
            IF(NEW.rating = 3, 1, 0),
            IF(NEW.rating = 4, 1, 0),
            IF(NEW.rating = 5, 1, 0))
        ON DUPLICATE KEY UPDATE
            total_reviews = total_reviews + 1,
            average_rating = (average_rating * (total_reviews - 1) + NEW.rating) / total_reviews,
            rating_1_count = rating_1_count + IF(NEW.rating = 1, 1, 0),
            rating_2_count = rating_2_count + IF(NEW.rating = 2, 1, 0),
            rating_3_count = rating_3_count + IF(NEW.rating = 3, 1, 0),
            rating_4_count = rating_4_count + IF(NEW.rating = 4, 1, 0),
            rating_5_count = rating_5_count + IF(NEW.rating = 5, 1, 0);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_review_cache_update` AFTER UPDATE ON `reviews` FOR EACH ROW BEGIN
    -- If visibility changed or rating changed
    IF OLD.is_visible != NEW.is_visible OR OLD.rating != NEW.rating THEN
        -- Recalculate from scratch (simpler than tracking all changes)
        INSERT INTO review_summary_cache (item_type, item_id, total_reviews, average_rating,
            rating_1_count, rating_2_count, rating_3_count, rating_4_count, rating_5_count)
        SELECT 
            NEW.item_type,
            NEW.item_id,
            COUNT(*),
            AVG(rating),
            SUM(IF(rating = 1, 1, 0)),
            SUM(IF(rating = 2, 1, 0)),
            SUM(IF(rating = 3, 1, 0)),
            SUM(IF(rating = 4, 1, 0)),
            SUM(IF(rating = 5, 1, 0))
        FROM reviews
        WHERE item_type = NEW.item_type 
            AND item_id = NEW.item_id 
            AND is_visible = 1
        ON DUPLICATE KEY UPDATE
            total_reviews = VALUES(total_reviews),
            average_rating = VALUES(average_rating),
            rating_1_count = VALUES(rating_1_count),
            rating_2_count = VALUES(rating_2_count),
            rating_3_count = VALUES(rating_3_count),
            rating_4_count = VALUES(rating_4_count),
            rating_5_count = VALUES(rating_5_count);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `reviewss`
--

CREATE TABLE `reviewss` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text NOT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviewuser`
--

CREATE TABLE `reviewuser` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `review_text` text NOT NULL,
  `destination` varchar(255) NOT NULL,
  `visit_date` date DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviewuser`
--

INSERT INTO `reviewuser` (`id`, `user_id`, `rating`, `review_text`, `destination`, `visit_date`, `is_approved`, `created_at`, `updated_at`) VALUES
(1, 1, 5, 'Emang bener bener menakjubkan sih raja ampat ini, suatu saat semoga bisa kesini lagii', 'Raja Ampat', '2025-07-12', 1, '2025-07-11 23:08:52', '2025-07-11 23:08:52'),
(2, 3, 5, 'Honeymoon sama suami bener bener ga mengecewakan tempatnya', 'Raja Ampat', '2025-07-10', 1, '2025-07-11 23:10:03', '2025-07-11 23:10:03'),
(3, 10, 5, 'Ikut kesini dari paket tour seruuuu', 'Wamena', '2025-07-12', 1, '2025-07-11 23:13:24', '2025-07-11 23:13:24');

-- --------------------------------------------------------

--
-- Table structure for table `review_helpfulness`
--

CREATE TABLE `review_helpfulness` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_helpful` tinyint(1) NOT NULL COMMENT '1 for helpful, 0 for not helpful',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_helpfulness`
--

INSERT INTO `review_helpfulness` (`id`, `review_id`, `user_id`, `is_helpful`, `created_at`) VALUES
(2, 3, 9, 1, '2025-06-30 20:11:58'),
(3, 4, 1, 1, '2025-07-06 06:10:14');

-- --------------------------------------------------------

--
-- Table structure for table `review_media`
--

CREATE TABLE `review_media` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `media_type` enum('image','video') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT NULL COMMENT 'File size in bytes',
  `duration` int(11) DEFAULT NULL COMMENT 'Video duration in seconds (max 10)',
  `upload_order` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Order of upload (1-5)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_media`
--

INSERT INTO `review_media` (`id`, `review_id`, `media_type`, `file_path`, `file_size`, `duration`, `upload_order`, `created_at`) VALUES
(1, 2, 'image', 'uploads/review_media/images/review_2_1_1751300996.png', 1900320, NULL, 1, '2025-06-30 16:29:56'),
(2, 6, 'image', 'uploads/review_media/images/review_6_1_1751781971.jpg', 125923, NULL, 1, '2025-07-06 06:06:11');

-- --------------------------------------------------------

--
-- Table structure for table `review_summary_cache`
--

CREATE TABLE `review_summary_cache` (
  `id` int(11) NOT NULL,
  `item_type` enum('wisata','penginapan','artikel') NOT NULL,
  `item_id` int(11) NOT NULL,
  `total_reviews` int(11) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `rating_1_count` int(11) DEFAULT 0,
  `rating_2_count` int(11) DEFAULT 0,
  `rating_3_count` int(11) DEFAULT 0,
  `rating_4_count` int(11) DEFAULT 0,
  `rating_5_count` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_summary_cache`
--

INSERT INTO `review_summary_cache` (`id`, `item_type`, `item_id`, `total_reviews`, `average_rating`, `rating_1_count`, `rating_2_count`, `rating_3_count`, `rating_4_count`, `rating_5_count`, `last_updated`) VALUES
(1, 'wisata', 6, 1, 5.00, 0, 0, 0, 0, 1, '2025-06-30 15:44:15'),
(2, 'wisata', 4, 1, 4.00, 0, 0, 0, 1, 0, '2025-06-30 16:29:56'),
(3, 'artikel', 1, 2, 4.50, 0, 0, 0, 1, 1, '2025-06-30 20:13:52'),
(4, 'artikel', 3, 1, 5.00, 0, 0, 0, 0, 1, '2025-06-30 20:11:23'),
(6, 'artikel', 4, 1, 5.00, 0, 0, 0, 0, 1, '2025-07-06 06:06:11'),
(7, 'artikel', 6, 1, 5.00, 0, 0, 0, 0, 1, '2025-07-06 06:06:32');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_code` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','awaiting_confirmation','paid','rejected','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `user_payment_date` datetime DEFAULT NULL,
  `payment_confirmed_at` datetime DEFAULT NULL,
  `payment_confirmed_by` int(11) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `user_id`, `transaction_code`, `total_amount`, `payment_status`, `payment_method`, `payment_proof`, `user_payment_date`, `payment_confirmed_at`, `payment_confirmed_by`, `payment_date`, `created_at`, `updated_at`) VALUES
(11, 8, 'TRX202506260238008', 50000.00, 'paid', 'bank_transfer', 'payment_TRX202506260238008_1750898415.jpg', '2025-06-27 07:40:00', '2025-06-26 07:44:30', 1, '2025-06-26 07:44:30', '2025-06-26 00:38:00', '2025-06-26 00:44:30'),
(12, 8, 'TRX202506301739148', 50000.00, 'paid', 'bank_transfer', 'payment_TRX202506301739148_1751298142.jpg', '2025-06-30 22:45:00', '2025-06-30 23:07:32', 1, '2025-06-30 23:07:32', '2025-06-30 15:39:14', '2025-06-30 16:07:32'),
(13, 8, 'TRX202506301807128', 200000.00, 'paid', 'bank_transfer', 'payment_TRX202506301807128_1751299642.jpg', '2025-07-01 23:07:00', '2025-06-30 23:29:21', 1, '2025-06-30 23:29:21', '2025-06-30 16:07:12', '2025-06-30 16:29:21'),
(14, 8, 'TRX202506301829048', 10000.00, 'paid', 'bank_transfer', 'payment_TRX202506301829048_1751300955.jpg', '2025-06-30 13:31:00', '2025-06-30 23:43:07', 1, '2025-06-30 23:43:07', '2025-06-30 16:29:04', '2025-06-30 16:43:07'),
(15, 8, 'TRX202506301842498', 3500000.00, 'paid', 'bank_transfer', 'payment_TRX202506301842498_1751301782.jpg', '2025-06-30 23:45:00', '2025-06-30 23:44:24', 1, '2025-06-30 23:44:24', '2025-06-30 16:42:49', '2025-06-30 16:44:24'),
(16, 8, 'TRX202506301844078', 75000.00, 'paid', 'bank_transfer', 'payment_TRX202506301844078_1751301859.jpg', '2025-06-30 23:44:00', '2025-06-30 23:44:30', 1, '2025-06-30 23:44:30', '2025-06-30 16:44:07', '2025-06-30 16:44:30'),
(17, 8, 'TRX202506302213008', 75000.00, 'paid', 'bank_transfer', 'payment_TRX202506302213008_1751314390.jpg', '2025-07-01 05:15:00', '2025-07-01 03:13:28', 1, '2025-07-01 03:13:28', '2025-06-30 20:13:00', '2025-06-30 20:13:28'),
(18, 1, 'TRX202507060800151', 285000.00, 'paid', 'bank_transfer', 'payment_TRX202507060800151_1751781634.png', '2025-07-06 13:00:00', '2025-07-06 13:00:58', 1, '2025-07-06 13:00:58', '2025-07-06 06:00:15', '2025-07-06 06:00:58');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_items`
--

CREATE TABLE `transaksi_items` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `item_type` enum('wisata','penginapan','artikel') NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `booking_date` date DEFAULT NULL,
  `checkin_date` date DEFAULT NULL,
  `checkout_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi_items`
--

INSERT INTO `transaksi_items` (`id`, `transaksi_id`, `item_type`, `item_id`, `item_name`, `quantity`, `price_per_unit`, `subtotal`, `booking_date`, `checkin_date`, `checkout_date`, `notes`) VALUES
(6, 11, 'wisata', 6, 'Pantai Base-G', 1, 50000.00, 50000.00, '2025-06-27', NULL, NULL, ''),
(7, 12, 'wisata', 6, 'Pantai Base-G', 1, 50000.00, 50000.00, '2025-07-01', NULL, NULL, ''),
(8, 13, 'artikel', 3, 'Tour Guide', 1, 200000.00, 200000.00, '2025-07-03', NULL, NULL, ''),
(9, 14, 'wisata', 4, 'Karmon Waterfall', 1, 10000.00, 10000.00, '2025-06-30', NULL, NULL, ''),
(10, 15, 'penginapan', 4, 'Papua Paradise Eco Resort', 1, 3500000.00, 3500000.00, NULL, '2025-06-30', '2025-07-01', ''),
(11, 16, 'artikel', 1, 'Snokeling Blue', 1, 75000.00, 75000.00, '2025-07-01', NULL, NULL, ''),
(12, 17, 'artikel', 1, 'Snokeling Blue', 1, 75000.00, 75000.00, '2025-07-01', NULL, NULL, ''),
(13, 18, 'artikel', 4, 'Udang Selingkuh Bakar', 2, 120000.00, 240000.00, '2025-07-07', NULL, NULL, '1 Porsi pedas 1 Porsi Tidak'),
(14, 18, 'artikel', 6, 'Sate Ulat Sagu', 1, 45000.00, 45000.00, '2025-07-07', NULL, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `umkm`
--

CREATE TABLE `umkm` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `business_name` varchar(100) NOT NULL,
  `owner_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `business_type` enum('jasa','event','kuliner','kerajinan','wisata') NOT NULL,
  `description` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default-umkm.jpg',
  `status` enum('pending','active','inactive') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `umkm`
--

INSERT INTO `umkm` (`id`, `email`, `password`, `business_name`, `owner_name`, `phone`, `address`, `business_type`, `description`, `profile_image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'papuabluedive@gmail.com', '$2y$10$YlaHkKRycMeWJHaibTo6HOsY335duiKFrLsSTwXnKdLw6v6kS.l2G', 'Papua Blue Dive', 'Kepin Marhaban', '082166384920', 'Jln. Wisata Laut No. 7, Distrik Waisai, Kabupaten Raja Ampat, Papua Barat Daya', 'wisata', '“Papua Blue Dive adalah layanan snorkeling profesional yang menghadirkan pengalaman eksplorasi terumbu karang dan keindahan laut Papua, khususnya di wilayah Raja Ampat.”', 'umkm_1_1748710091.png', 'active', '2025-05-31 15:35:59', '2025-05-31 16:48:17'),
(4, 'trenguide@gmail.com', '$2y$10$Q1N04h86zSwRnGlDP1CSfOau1Jg6Mrk57Yyt6QlcE/5O52092Tdda', 'Tren Tour Guide', 'Trendo', '09277246729', 'Jl. Soa Siu Dok 2 Bawah Jayapura, Papua.', 'jasa', 'Jasa pemandu Tour Guide', 'default-umkm.jpg', 'active', '2025-06-11 03:36:41', '2025-06-11 03:37:08'),
(5, 'honairasa@gmail.com', '$2y$10$u4wOKNfcQVUhuSzIqzJo6eBX/j6Wo5A3dtWOLFJLY3akvHak2oju6', 'Rumah Makan Honai Rasa', 'Ester Maintindom', '0810967123456', 'Jl. Trikora No.15, Distrik Abepura, Kota Jayapura, Papua 99351', 'kuliner', 'Terinspirasi dari bentuk rumah adat Honai, restoran ini mengusung konsep tradisional-modern yang menyajikan aneka kuliner khas Pegunungan Tengah Papua. Interior kayu, aroma rempah khas, dan musik etnik menciptakan suasana yang hangat dan autentik.', 'umkm_5_1751775094.png', 'active', '2025-07-06 04:06:14', '2025-07-06 04:11:34'),
(6, 'dapurlautsentani@gmail.com', '$2y$10$TOQrV3.8h2Cw1aecp8LgB.wl6Da5fbi/FND.moANzjoJWKzl/wRrO', 'Dapur Laut Sentani', 'Azwandi Kaka', '0810967123456', 'Jl. Raya Danau Sentani No.3, Kampung Harapan, Distrik Sentani Timur, Kabupaten Jayapura, Papua 99352', 'jasa', 'Dikelilingi panorama Danau Sentani, restoran ini menyajikan berbagai hidangan laut segar yang diolah dengan resep turun-temurun masyarakat Sentani. Cocok untuk wisatawan yang ingin mencicipi cita rasa laut Papua yang otentik.', 'umkm_6_1751782303.png', 'active', '2025-07-06 04:08:08', '2025-07-06 06:11:43'),
(7, 'mamarosa@gmail.com', '$2y$10$VbPs8wSTNYj/SL91danwnu1nZPxUIJB/5QdWykRwdlWAsR1qWBqpC', 'Mama Rosa Event Organizer', 'Rosa Yikwa', '085377779898', 'Jl. Kemiri Dalam, Abepura, Jayapura', 'event', 'Menyediakan layanan penyelenggaraan acara budaya, pernikahan adat Papua, dan festival lokal.', 'umkm_7_1751783229.jpg', 'active', '2025-07-06 06:23:13', '2025-07-06 06:27:09'),
(8, 'kriyanoken@gmail.com', '$2y$10$8s/1joOaRHxFsp1PpkDaKefsfW14AenTC1BecDXGHfgsl7FmVuvpO', 'Kriya Noken Papua', 'Maria Magai', '085211118899', 'Jl. Merdeka No. 45, Kota Jayapura', 'kerajinan', 'Usaha yang fokus pada pembuatan dan penjualan noken asli Papua dari serat alami.', 'umkm_8_1751783505.png', 'active', '2025-07-06 06:24:36', '2025-07-06 06:31:45');

-- --------------------------------------------------------

--
-- Table structure for table `umkm_financial_statistics`
--

CREATE TABLE `umkm_financial_statistics` (
  `id` int(11) NOT NULL,
  `umkm_id` int(11) NOT NULL,
  `stat_date` date NOT NULL,
  `item_count` int(11) DEFAULT 0,
  `order_count` int(11) DEFAULT 0,
  `quantity_sold` int(11) DEFAULT 0,
  `revenue` decimal(15,2) DEFAULT 0.00,
  `avg_order_value` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `umkm_notifications`
--

CREATE TABLE `umkm_notifications` (
  `id` int(11) NOT NULL,
  `umkm_id` int(11) NOT NULL,
  `type` enum('new_order','payment_confirmed','payment_rejected') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `transaction_code` varchar(20) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `umkm_notifications`
--

INSERT INTO `umkm_notifications` (`id`, `umkm_id`, `type`, `title`, `message`, `transaction_code`, `is_read`, `created_at`) VALUES
(1, 5, 'payment_confirmed', 'Pembayaran Dikonfirmasi!', 'Pesanan untuk \'Udang Selingkuh Bakar\' (Qty: 2, Total: Rp 240,000) telah dikonfirmasi. Kode transaksi: TRX202507060800151', 'TRX202507060800151', 1, '2025-07-06 06:00:58'),
(2, 5, 'payment_confirmed', 'Pembayaran Dikonfirmasi!', 'Pesanan untuk \'Sate Ulat Sagu\' (Qty: 1, Total: Rp 45,000) telah dikonfirmasi. Kode transaksi: TRX202507060800151', 'TRX202507060800151', 1, '2025-07-06 06:00:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT 'default-user.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `phone`, `address`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 'brian@gmail.com', '$2y$10$b6zQG9GgQaVK7JhYiPoY1eW6tkIo6kc5J5tSMhj8MKZUAPj/Kvk4O', 'Brian Domani', '082133871850', 'Banjarsari Surakarta Jawa Tengah', 'user_1_1751782135.jpg', '2025-05-31 15:34:52', '2025-07-06 06:09:18'),
(3, 'naura@gmail.com', '$2y$10$gc6vW85ACp4YDdg8aHSqY.rN51jbEdWSwZLivNI/.P8eAZIwHAYY2', 'Naura Tsani Maya', '082324096996', 'Sragen Jawa Tengah', 'user_3_1748709699.jpg', '2025-05-31 15:59:00', '2025-05-31 16:42:05'),
(8, 'slemandanpapua@gmail.com', '$2y$10$8J8g9PhSbxSBwf6bplaYY.GeWgq.7x1NtxKboIsWwJpUNpkgtf0z2', 'Trendo', '081357426645', 'furia puskopad block a', 'user_8_1750800567.jpg', '2025-06-23 13:29:50', '2025-06-24 21:29:27'),
(9, 'samuelrobail@gmail.com', '$2y$10$.Q72hHPSWfvri1e9281fw.RCSfsJ83c8BXQRwA1oStWz3Mytibw6G', 'Ibnu', '081357427945', 'furia puskopad block a', 'default-user.jpg', '2025-06-24 20:46:37', '2025-06-26 19:59:19'),
(10, 'aliya@gmail.com', '$2y$10$.pZdM0lxHINYqorwA.bLyO8B6hKUB2OOKihjGxxtG93BNrm14FwZ.', 'Aliya Hanifa Windi', '08237828212', 'Jl. Sorong No.1 Gatu, Muara Rapak, Kec. Balikpapan Utara, Kota Balikpapan Prov. Kalimantan Timur', 'default-user.jpg', '2025-07-11 23:12:40', '2025-07-11 23:12:40');

-- --------------------------------------------------------

--
-- Table structure for table `wisata`
--

CREATE TABLE `wisata` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `kategori` enum('budaya','alam') NOT NULL,
  `alamat` text NOT NULL,
  `jam_buka` varchar(100) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wisata`
--

INSERT INTO `wisata` (`id`, `judul`, `deskripsi`, `harga`, `kategori`, `alamat`, `jam_buka`, `photo`, `created_at`, `updated_at`) VALUES
(4, 'Karmon Waterfall', 'Air Terjun Karmon adalah salah satu destinasi wisata alam yang menakjubkan di Kabupaten Biak Numfor, Provinsi Papua. Terletak di tengah-tengah hutan tropis yang lebat, air terjun ini memiliki ketinggian sekitar 40 meter. Keindahan Air Terjun Karmon terletak pada aliran airnya yang jernih dan suasana sekitarnya yang alami dan menawan.', 10000.00, 'alam', 'Kampung Karmon, Distrik Warsa, Biak bagian utara', '08:00 - 17:00', '684c2e7cb31d0.jpg', '2025-06-13 13:58:20', '2025-06-13 13:58:20'),
(5, 'TropicSurf “Secret Papua” Tour', 'Paket eksklusif surf & cruise dengan kapal phinisi mewah (silolona), menelusuri spot selancar terpencil di Papua. Semua tingkat keahlian bisa ikut; didampingi instruktur top. Setelah sesi surfing, menginap di resort bintang lima atau di kapal yang nyaman di laut .', 409938.00, 'alam', 'Perairan nasional di Raja Ampat dan West Papua.', 'Reservasi', '684fc767c53d3.jpg', '2025-06-16 07:27:35', '2025-06-16 07:27:35'),
(6, 'Pantai Base-G', 'Pantai Base G atau juga dikenal sebagai Tanjung Ria terletak disebelah utara Kota Jayapura, Papua. Pantai Base G berlokasi sekitar 10 km dari Kota Jayapura di Distrik Jayapura Utara. Pantai Base G dapat dikunjungi dengan menggunakan berbagai jenis kendaraan dengan waktu tempuh kurang lebih 20 menit dari kota, dengan akses jalan beraspal. Apabila pengunjung mengambil patokan Bandara Sentani, waktu tempuh darat sekitar 1,5 jam.\\r\\n\\r\\nNama Base G berasal dari sejarahnya yang dahulu merupakan basis militer dengan nama Base G Camp pada masa Perang Dunia II. Kawasan Pantai Base G mempunyai luas sekitar 90 ha, panjang garis pantai 6-15 meter, dengan lebar pantai belakang 15-40 meter, lebar perairan 150-400 meter.\\r\\n\\r\\nPantai Base G Jayapura merupakan salah satu tujuan wisata unggulan di kota Jayapura, Papua. Meskipun masih berada dalam wilayah kota, pantai ini menyuguhkan keindahan alam yang jarang dimiliki oleh pantai-pantai lain di Jayapura. Lokasinya yang terletak di sebelah barat kota Jayapura membuat pantai ini mudah diakses.', 50000.00, 'alam', 'Tj. Ria, Kec. Jayapura Utara, Kota Jayapura, Papua', '06.00 - 21.00 ', 'wisata_685aa4c3aae6c.jpg', '2025-06-24 13:14:43', '2025-06-24 13:14:43'),
(7, 'Raja Ampat', 'Surga bawah laut dunia dengan keanekaragaman hayati laut tertinggi di dunia. Cocok untuk snorkeling, diving, dan island hopping.', 50000.00, 'alam', 'Kabupaten Raja Ampat, Papua Barat Daya', '06:00 - 20:00', 'wisata_68740bc1aa1e5.jpg', '2025-07-13 19:40:49', '2025-07-13 19:40:49'),
(8, 'Danau Sentani', 'Danau terbesar di Papua, dikelilingi oleh perbukitan hijau dan desa-desa adat dengan budaya lokal yang kental.', 20000.00, 'alam', 'Distrik Sentani, Kabupaten Jayapura, Papua', '08:00 - 17:00', 'wisata_68740c4eb44e7.jpeg', '2025-07-13 19:43:10', '2025-07-13 19:43:10'),
(9, 'Lembah Baliem', 'Lembah luas yang merupakan rumah bagi suku Dani. Terkenal dengan festival Lembah Baliem dan panorama pegunungan.', 30000.00, 'alam', 'Kabupaten Jayawijaya, Papua Pegunungan', '07:00 - 17:00', 'wisata_68740ca73a91c.jpg', '2025-07-13 19:44:39', '2025-07-13 19:44:39'),
(10, 'Taman Nasional Lorentz', 'Situs Warisan Dunia UNESCO yang merupakan taman nasional terbesar di Asia Tenggara, rumah bagi puncak salju tropis (Puncak Jaya) dan keanekaragaman hayati.', 50000.00, 'alam', 'Membentang di beberapa kabupaten: Mimika, Jayawijaya, Nduga, Yahukimo, Papua Tengah', '07:00 - 17:00', 'wisata_68740cdff0b26.jpg', '2025-07-13 19:45:35', '2025-07-13 19:45:35'),
(11, 'Pantai Harlem', 'Kampung Tablasupa, Distrik Depapre, Kabupaten Jayapura', 25000.00, 'alam', 'Kampung Tablasupa, Distrik Depapre, Kabupaten Jayapura', '07:00 - 17:00', 'wisata_68740d0c0af7f.jpg', '2025-07-13 19:46:20', '2025-07-13 19:46:20'),
(12, 'Bukit Teletubbies Jayapura', 'Deskripsi: Perbukitan hijau menyerupai lanskap serial Teletubbies, cocok untuk piknik dan menikmati panorama alam.', 10000.00, 'alam', 'Doyo Lama, Distrik Waibu, Kabupaten Jayapura', '06:00 - 18:00', 'wisata_68740d4d80845.jpeg', '2025-07-13 19:47:25', '2025-07-13 19:47:25'),
(13, 'Museum Loka Budaya UNCEN', 'Museum budaya Papua dengan koleksi artefak adat dan sejarah suku-suku Papua.', 0.00, 'budaya', 'Kampus Uncen Waena, Kota Jayapura', '08:00 - 16:00', 'wisata_68740f2d9d449.jpeg', '2025-07-13 19:55:25', '2025-07-13 19:55:25'),
(14, 'Tugu MacArthur', 'Tugu bersejarah peninggalan Perang Dunia II, dengan pemandangan Danau Sentani.', 10000.00, 'budaya', 'Bukit Ifar, Sentani, Kabupaten Jayapura', '07:00 - 17:00', 'wisata_68740fa270f93.jpg', '2025-07-13 19:57:22', '2025-07-13 19:57:22'),
(15, 'Pantai Holtekamp', 'Pantai pasir putih dengan jembatan merah ikonik dan pemandangan Teluk Youtefa.', 10000.00, 'alam', 'Distrik Muara Tami, Kota Jayapura', '06:00 - 18:00', 'wisata_68741023873a3.jpg', '2025-07-13 19:59:31', '2025-07-13 19:59:31'),
(16, 'Pantai Hamadi', 'Pantai di pusat kota dengan pasir lembut dan pemandangan laut lepas.', 10000.00, 'alam', 'Hamadi, Distrik Jayapura Selatan, Kota Jayapura', '06:00 - 18:00', 'wisata_687410a77a6a9.jpg', '2025-07-13 20:01:43', '2025-07-13 20:01:43'),
(17, 'Museum Negeri Papua', 'Museum besar yang menyimpan koleksi budaya, sejarah, dan arkeologi Papua.\\r\\n', 5000.00, 'budaya', 'Jl. Raya Abepura, Kota Jayapura', '08:00 - 15:00', 'wisata_687411053cd70.jpeg', '2025-07-13 20:03:17', '2025-07-13 20:03:17'),
(18, 'Pantai Pasir 6', 'Pantai yang cocok untuk menikmati matahari terbenam di Jayapura.', 10000.00, 'alam', 'Skouw Sae, Distrik Muara Tami, Kota Jayapura', '06:00 - 18:00', 'wisata_68741157cdf41.jpeg', '2025-07-13 20:04:39', '2025-07-13 20:04:39'),
(19, 'Bukit Jokowi', 'Bukit dengan pemandangan Teluk Youtefa dan jembatan Youtefa dari atas.', 5000.00, 'alam', 'Koya Barat, Distrik Muara Tami, Kota Jayapura', '06:00 - 18:00', 'wisata_687411b839108.jpg', '2025-07-13 20:06:16', '2025-07-13 20:06:16'),
(20, 'Air Terjun Cyclop', 'Air terjun tersembunyi di kawasan Cagar Alam Cyclop, cocok untuk hiking.', 10000.00, 'alam', 'Kawasan Pegunungan Cyclop, Kabupaten Jayapura', '07:00 - 17:00', 'wisata_68741232b1c81.jpg', '2025-07-13 20:08:18', '2025-07-13 20:08:18'),
(21, 'Bukit Salib', 'Bukit dengan salib besar di puncaknya, lokasi ziarah dan melihat Danau Sentani dari\\r\\natas.', 5000.00, 'alam', 'Sentani Tengah, Kabupaten Jayapura', '07:00 - 17:00', 'wisata_68741292a5bcb.jpg', '2025-07-13 20:09:54', '2025-07-13 20:09:54'),
(22, 'Pantai Marauw', 'Pantai alami yang tenang dengan akses jalan ke arah Skouw.', 10000.00, 'alam', 'Marauw, Distrik Muara Tami, Kota Jayapura', '06:00 - 18:00', 'wisata_687412f24daa8.jpg', '2025-07-13 20:11:30', '2025-07-13 20:11:30'),
(23, 'Kampung Kayu Batu', 'Kampung adat dengan pemandangan laut dan budaya lokal.', 0.00, 'alam', 'Distrik Jayapura Utara, Kota Jayapura', '08:00 - 17:00', 'wisata_6874134b1f84a.jpg', '2025-07-13 20:12:59', '2025-07-13 20:12:59'),
(24, 'Teluk Youtefa', 'Teluk indah yang mengelilingi kawasan konservasi mangrove dan budaya pesisir.\\r\\n', 0.00, 'alam', 'Kota Jayapura', '06:00 - 18:00', 'wisata_687413d6e5734.jpg', '2025-07-13 20:15:18', '2025-07-13 20:15:18'),
(25, 'Cagar Alam Cyclop', 'Cagar alam pegunungan yang cocok untuk trekking dan melihat flora endemik Papua.', 15000.00, 'alam', 'Kabupaten Jayapura', '07:00 - 17:00', 'wisata_6874144094698.jpg', '2025-07-13 20:17:04', '2025-07-13 20:17:04'),
(26, 'Bukit Sibei', 'Destinasi wisata alam dengan pemandangan Danau Sentani dan sekitarnya.', 5000.00, 'alam', 'Sentani Timur, Kabupaten Jayapura', '06:00 - 18:00', 'wisata_687414ecb4084.jpg', '2025-07-13 20:19:56', '2025-07-13 20:19:56'),
(27, 'Bukit Aplim', 'Bukit yang menjadi tempat pengamatan indahnya matahari terbit di Jayapura.', 5000.00, 'alam', 'Abepura, Kota Jayapura\\r\\n', '05:00 - 18:00', 'wisata_6874158c59656.jpg', '2025-07-13 20:22:36', '2025-07-13 20:22:36');

-- --------------------------------------------------------

--
-- Stand-in structure for view `wisata_popularity`
-- (See below for the actual view)
--
CREATE TABLE `wisata_popularity` (
`id` int(11)
,`judul` varchar(255)
,`kategori` enum('budaya','alam')
,`harga` decimal(10,2)
,`photo` varchar(255)
,`views_today` int(11)
,`views_this_week` decimal(32,0)
,`views_this_month` decimal(32,0)
,`bookings_this_week` decimal(32,0)
,`bookings_this_month` decimal(32,0)
,`revenue_this_month` decimal(37,2)
,`conversion_rate_week` decimal(38,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `wisata_statistics`
--

CREATE TABLE `wisata_statistics` (
  `id` int(11) NOT NULL,
  `wisata_id` int(11) NOT NULL,
  `stat_date` date NOT NULL,
  `view_count` int(11) DEFAULT 0,
  `unique_visitors` int(11) DEFAULT 0,
  `booking_count` int(11) DEFAULT 0,
  `revenue` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wisata_statistics`
--

INSERT INTO `wisata_statistics` (`id`, `wisata_id`, `stat_date`, `view_count`, `unique_visitors`, `booking_count`, `revenue`, `created_at`, `updated_at`) VALUES
(1, 4, '2025-06-27', 2, 2, 0, 0.00, '2025-06-26 19:50:26', '2025-06-26 20:00:53'),
(2, 5, '2025-06-27', 1, 1, 0, 0.00, '2025-06-26 19:50:26', '2025-06-26 19:56:37'),
(3, 6, '2025-06-27', 4, 4, 1, 50000.00, '2025-06-26 19:50:26', '2025-06-26 20:06:26'),
(11, 6, '2025-06-30', 2, 1, 0, 0.00, '2025-06-30 15:38:58', '2025-06-30 16:42:12'),
(12, 4, '2025-06-30', 1, 1, 0, 0.00, '2025-06-30 16:28:49', '2025-06-30 16:28:49'),
(14, 6, '2025-07-01', 1, 1, 0, 0.00, '2025-06-30 20:12:42', '2025-06-30 20:12:42'),
(15, 4, '2025-07-01', 1, 1, 0, 0.00, '2025-06-30 20:38:42', '2025-06-30 20:38:42'),
(16, 6, '2025-07-06', 1, 1, 0, 0.00, '2025-07-06 05:54:58', '2025-07-06 05:54:58');

-- --------------------------------------------------------

--
-- Table structure for table `wisata_views`
--

CREATE TABLE `wisata_views` (
  `id` int(11) NOT NULL,
  `wisata_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `view_date` datetime DEFAULT current_timestamp(),
  `session_id` varchar(128) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wisata_views`
--

INSERT INTO `wisata_views` (`id`, `wisata_id`, `user_id`, `ip_address`, `view_date`, `session_id`, `user_agent`) VALUES
(1, 4, 8, '::1', '2025-06-27 02:56:04', '6f02u1hb506npcldu15372j7qn', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(2, 5, 8, '::1', '2025-06-27 02:56:37', '6f02u1hb506npcldu15372j7qn', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(3, 6, 8, '::1', '2025-06-27 02:56:48', '6f02u1hb506npcldu15372j7qn', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(4, 4, 9, '::1', '2025-06-27 03:00:53', 'ucpiiubit5rdoijco6am8av0a0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(5, 6, 9, '::1', '2025-06-27 03:01:07', 'ucpiiubit5rdoijco6am8av0a0', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36 Edg/137.0.0.0'),
(6, 6, 8, '::1', '2025-06-27 03:03:25', '91e5qq1noabrc7f5v6d63rs5jm', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(7, 6, 8, '::1', '2025-06-27 03:06:25', '3aj0j4ohfec3ltu9c18ncnki20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(8, 6, 8, '::1', '2025-06-30 22:38:58', '88qdatrka6tgd4g1qdp1trb4r3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(9, 4, 8, '::1', '2025-06-30 23:28:49', '88qdatrka6tgd4g1qdp1trb4r3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(10, 6, 8, '::1', '2025-06-30 23:42:11', '88qdatrka6tgd4g1qdp1trb4r3', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(11, 6, 8, '::1', '2025-07-01 03:12:42', '59934d9knach3tfp4kqfm675sq', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(12, 4, 8, '::1', '2025-07-01 03:38:42', '59934d9knach3tfp4kqfm675sq', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36'),
(13, 6, 1, '::1', '2025-07-06 12:54:58', 'lcpoqp1fupu8798bhaahg7rtm7', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');

-- --------------------------------------------------------

--
-- Structure for view `penginapan_popularity`
--
DROP TABLE IF EXISTS `penginapan_popularity`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `penginapan_popularity`  AS SELECT `p`.`id` AS `id`, `p`.`judul` AS `judul`, `p`.`tipe` AS `tipe`, `p`.`harga` AS `harga`, `p`.`photo` AS `photo`, `p`.`lokasi` AS `lokasi`, coalesce(`ps_today`.`view_count`,0) AS `views_today`, coalesce(`ps_week`.`total_views`,0) AS `views_this_week`, coalesce(`ps_month`.`total_views`,0) AS `views_this_month`, coalesce(`ps_week`.`total_bookings`,0) AS `bookings_this_week`, coalesce(`ps_month`.`total_bookings`,0) AS `bookings_this_month`, coalesce(`ps_month`.`total_revenue`,0) AS `revenue_this_month`, CASE WHEN coalesce(`ps_week`.`total_views`,0) > 0 THEN round(coalesce(`ps_week`.`total_bookings`,0) / `ps_week`.`total_views` * 100,2) ELSE 0 END AS `conversion_rate_week` FROM (((`penginapan` `p` left join `penginapan_statistics` `ps_today` on(`p`.`id` = `ps_today`.`penginapan_id` and `ps_today`.`stat_date` = curdate())) left join (select `penginapan_statistics`.`penginapan_id` AS `penginapan_id`,sum(`penginapan_statistics`.`view_count`) AS `total_views`,sum(`penginapan_statistics`.`booking_count`) AS `total_bookings` from `penginapan_statistics` where `penginapan_statistics`.`stat_date` >= curdate() - interval 7 day group by `penginapan_statistics`.`penginapan_id`) `ps_week` on(`p`.`id` = `ps_week`.`penginapan_id`)) left join (select `penginapan_statistics`.`penginapan_id` AS `penginapan_id`,sum(`penginapan_statistics`.`view_count`) AS `total_views`,sum(`penginapan_statistics`.`booking_count`) AS `total_bookings`,sum(`penginapan_statistics`.`revenue`) AS `total_revenue` from `penginapan_statistics` where `penginapan_statistics`.`stat_date` >= curdate() - interval 30 day group by `penginapan_statistics`.`penginapan_id`) `ps_month` on(`p`.`id` = `ps_month`.`penginapan_id`)) ORDER BY coalesce(`ps_month`.`total_views`,0) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `wisata_popularity`
--
DROP TABLE IF EXISTS `wisata_popularity`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `wisata_popularity`  AS SELECT `w`.`id` AS `id`, `w`.`judul` AS `judul`, `w`.`kategori` AS `kategori`, `w`.`harga` AS `harga`, `w`.`photo` AS `photo`, coalesce(`ws_today`.`view_count`,0) AS `views_today`, coalesce(`ws_week`.`total_views`,0) AS `views_this_week`, coalesce(`ws_month`.`total_views`,0) AS `views_this_month`, coalesce(`ws_week`.`total_bookings`,0) AS `bookings_this_week`, coalesce(`ws_month`.`total_bookings`,0) AS `bookings_this_month`, coalesce(`ws_month`.`total_revenue`,0) AS `revenue_this_month`, CASE WHEN coalesce(`ws_week`.`total_views`,0) > 0 THEN round(coalesce(`ws_week`.`total_bookings`,0) / `ws_week`.`total_views` * 100,2) ELSE 0 END AS `conversion_rate_week` FROM (((`wisata` `w` left join `wisata_statistics` `ws_today` on(`w`.`id` = `ws_today`.`wisata_id` and `ws_today`.`stat_date` = curdate())) left join (select `wisata_statistics`.`wisata_id` AS `wisata_id`,sum(`wisata_statistics`.`view_count`) AS `total_views`,sum(`wisata_statistics`.`booking_count`) AS `total_bookings` from `wisata_statistics` where `wisata_statistics`.`stat_date` >= curdate() - interval 7 day group by `wisata_statistics`.`wisata_id`) `ws_week` on(`w`.`id` = `ws_week`.`wisata_id`)) left join (select `wisata_statistics`.`wisata_id` AS `wisata_id`,sum(`wisata_statistics`.`view_count`) AS `total_views`,sum(`wisata_statistics`.`booking_count`) AS `total_bookings`,sum(`wisata_statistics`.`revenue`) AS `total_revenue` from `wisata_statistics` where `wisata_statistics`.`stat_date` >= curdate() - interval 30 day group by `wisata_statistics`.`wisata_id`) `ws_month` on(`w`.`id` = `ws_month`.`wisata_id`)) ORDER BY coalesce(`ws_month`.`total_views`,0) DESC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_payment_logs`
--
ALTER TABLE `admin_payment_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `transaksi_id` (`transaksi_id`);

--
-- Indexes for table `artikel`
--
ALTER TABLE `artikel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kategori` (`kategori`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_umkm_id` (`umkm_id`),
  ADD KEY `idx_artikel_umkm` (`umkm_id`,`id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_type_id` (`item_type`,`item_id`),
  ADD KEY `idx_user_cart` (`user_id`,`item_type`,`item_id`);

--
-- Indexes for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chat_conversation_sessions`
--
ALTER TABLE `chat_conversation_sessions`
  ADD PRIMARY KEY (`conversation_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `pemesanan`
--
ALTER TABLE `pemesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `wisata_id` (`wisata_id`);

--
-- Indexes for table `pemesanan_tiket`
--
ALTER TABLE `pemesanan_tiket`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `artikel_id` (`artikel_id`),
  ADD KEY `tanggal_kunjungan` (`tanggal_kunjungan`),
  ADD KEY `idx_pemesanan_created_at` (`created_at`),
  ADD KEY `idx_pemesanan_status_created` (`created_at`);

--
-- Indexes for table `penginapan`
--
ALTER TABLE `penginapan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `penginapan_statistics`
--
ALTER TABLE `penginapan_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_stat` (`penginapan_id`,`stat_date`),
  ADD KEY `idx_stat_date` (`stat_date`),
  ADD KEY `idx_penginapan_stat` (`penginapan_id`,`stat_date`);

--
-- Indexes for table `penginapan_views`
--
ALTER TABLE `penginapan_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_penginapan_views` (`penginapan_id`,`view_date`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_view_date` (`view_date`);

--
-- Indexes for table `pesanpenginapan`
--
ALTER TABLE `pesanpenginapan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `platform_financial_statistics`
--
ALTER TABLE `platform_financial_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stat_date_unique` (`stat_date`),
  ADD KEY `idx_stat_date` (`stat_date`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_transaksi_item` (`user_id`,`transaksi_id`,`item_type`,`item_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_item` (`item_type`,`item_id`),
  ADD KEY `idx_transaksi` (`transaksi_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_reviews_visible_rating` (`is_visible`,`rating`,`created_at`),
  ADD KEY `idx_reviews_item_visible` (`item_type`,`item_id`,`is_visible`,`created_at`);

--
-- Indexes for table `reviewss`
--
ALTER TABLE `reviewss`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reviewuser`
--
ALTER TABLE `reviewuser`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_vote` (`review_id`,`user_id`),
  ADD KEY `idx_review_id` (`review_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `review_media`
--
ALTER TABLE `review_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_review_id` (`review_id`);

--
-- Indexes for table `review_summary_cache`
--
ALTER TABLE `review_summary_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_item` (`item_type`,`item_id`),
  ADD KEY `idx_item` (`item_type`,`item_id`),
  ADD KEY `idx_average_rating` (`average_rating`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user_transactions` (`user_id`,`payment_status`),
  ADD KEY `idx_payment_status_proof` (`payment_status`,`payment_proof`),
  ADD KEY `idx_payment_status_date` (`payment_status`,`created_at`),
  ADD KEY `idx_transaksi_date_status` (`created_at`,`payment_status`),
  ADD KEY `idx_transaksi_created_at` (`created_at`),
  ADD KEY `idx_transaksi_payment_method` (`payment_method`,`payment_status`);

--
-- Indexes for table `transaksi_items`
--
ALTER TABLE `transaksi_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `idx_transaction_items` (`transaksi_id`,`item_type`),
  ADD KEY `idx_item_type_id` (`item_type`,`item_id`),
  ADD KEY `idx_transaksi_items_lookup` (`transaksi_id`,`item_type`,`item_id`),
  ADD KEY `idx_transaksi_items_revenue` (`item_id`,`item_type`,`subtotal`);

--
-- Indexes for table `umkm`
--
ALTER TABLE `umkm`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `umkm_financial_statistics`
--
ALTER TABLE `umkm_financial_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `umkm_date_unique` (`umkm_id`,`stat_date`),
  ADD KEY `idx_umkm_id` (`umkm_id`),
  ADD KEY `idx_stat_date` (`stat_date`);

--
-- Indexes for table `umkm_notifications`
--
ALTER TABLE `umkm_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `umkm_id` (`umkm_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wisata`
--
ALTER TABLE `wisata`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wisata_statistics`
--
ALTER TABLE `wisata_statistics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_stat` (`wisata_id`,`stat_date`),
  ADD KEY `idx_stat_date` (`stat_date`),
  ADD KEY `idx_wisata_stat` (`wisata_id`,`stat_date`);

--
-- Indexes for table `wisata_views`
--
ALTER TABLE `wisata_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_wisata_views` (`wisata_id`,`view_date`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_view_date` (`view_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admin_payment_logs`
--
ALTER TABLE `admin_payment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `artikel`
--
ALTER TABLE `artikel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `pemesanan`
--
ALTER TABLE `pemesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pemesanan_tiket`
--
ALTER TABLE `pemesanan_tiket`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `penginapan`
--
ALTER TABLE `penginapan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `penginapan_statistics`
--
ALTER TABLE `penginapan_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `penginapan_views`
--
ALTER TABLE `penginapan_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pesanpenginapan`
--
ALTER TABLE `pesanpenginapan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `platform_financial_statistics`
--
ALTER TABLE `platform_financial_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviewss`
--
ALTER TABLE `reviewss`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviewuser`
--
ALTER TABLE `reviewuser`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `review_media`
--
ALTER TABLE `review_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `review_summary_cache`
--
ALTER TABLE `review_summary_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `transaksi_items`
--
ALTER TABLE `transaksi_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `umkm`
--
ALTER TABLE `umkm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `umkm_financial_statistics`
--
ALTER TABLE `umkm_financial_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `umkm_notifications`
--
ALTER TABLE `umkm_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wisata`
--
ALTER TABLE `wisata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `wisata_statistics`
--
ALTER TABLE `wisata_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `wisata_views`
--
ALTER TABLE `wisata_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `artikel`
--
ALTER TABLE `artikel`
  ADD CONSTRAINT `artikel_ibfk_1` FOREIGN KEY (`umkm_id`) REFERENCES `umkm` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD CONSTRAINT `chat_conversations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_conversation_sessions`
--
ALTER TABLE `chat_conversation_sessions`
  ADD CONSTRAINT `chat_conversation_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pemesanan`
--
ALTER TABLE `pemesanan`
  ADD CONSTRAINT `pemesanan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pemesanan_ibfk_2` FOREIGN KEY (`wisata_id`) REFERENCES `wisata` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pemesanan_tiket`
--
ALTER TABLE `pemesanan_tiket`
  ADD CONSTRAINT `fk_pemesanan_artikel` FOREIGN KEY (`artikel_id`) REFERENCES `artikel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pemesanan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `penginapan_statistics`
--
ALTER TABLE `penginapan_statistics`
  ADD CONSTRAINT `penginapan_statistics_ibfk_1` FOREIGN KEY (`penginapan_id`) REFERENCES `penginapan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penginapan_views`
--
ALTER TABLE `penginapan_views`
  ADD CONSTRAINT `penginapan_views_ibfk_1` FOREIGN KEY (`penginapan_id`) REFERENCES `penginapan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penginapan_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_transaksi` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviewss`
--
ALTER TABLE `reviewss`
  ADD CONSTRAINT `reviewss_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviewuser`
--
ALTER TABLE `reviewuser`
  ADD CONSTRAINT `reviewuser_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_helpfulness`
--
ALTER TABLE `review_helpfulness`
  ADD CONSTRAINT `fk_helpfulness_review` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_helpfulness_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_media`
--
ALTER TABLE `review_media`
  ADD CONSTRAINT `fk_review_media` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaksi_items`
--
ALTER TABLE `transaksi_items`
  ADD CONSTRAINT `fk_transaksi_items` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `umkm_financial_statistics`
--
ALTER TABLE `umkm_financial_statistics`
  ADD CONSTRAINT `fk_umkm_stats_umkm` FOREIGN KEY (`umkm_id`) REFERENCES `umkm` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `umkm_notifications`
--
ALTER TABLE `umkm_notifications`
  ADD CONSTRAINT `umkm_notifications_ibfk_1` FOREIGN KEY (`umkm_id`) REFERENCES `umkm` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wisata_statistics`
--
ALTER TABLE `wisata_statistics`
  ADD CONSTRAINT `wisata_statistics_ibfk_1` FOREIGN KEY (`wisata_id`) REFERENCES `wisata` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wisata_views`
--
ALTER TABLE `wisata_views`
  ADD CONSTRAINT `wisata_views_ibfk_1` FOREIGN KEY (`wisata_id`) REFERENCES `wisata` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wisata_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
