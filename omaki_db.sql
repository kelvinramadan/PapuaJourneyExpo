-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 19, 2025 at 08:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
(3, 4, 'Tour Guide', 'Jasa pemandu Tour Guide Harian aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 200000.00, 'jasa', 'artikel_4_1749613163.jpg', 'active', '2025-06-11 03:39:23', '2025-06-11 03:39:23');

-- --------------------------------------------------------

--
-- Table structure for table `chat_conversations`
--

CREATE TABLE `chat_conversations` (
  `id` int(11) NOT NULL,
  `conversation_id` varchar(36) NOT NULL COMMENT 'Unique ID for each conversation session',
  `user_id` int(11) NOT NULL,
  `message_type` enum('user','bot') NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_conversations`
--

INSERT INTO `chat_conversations` (`id`, `conversation_id`, `user_id`, `message_type`, `message`, `created_at`) VALUES
(7, 'b99f874f-3eb0-4609-a951-874696a24217', 8, 'user', 'Halo', '2025-06-18 13:07:08'),
(8, 'b99f874f-3eb0-4609-a951-874696a24217', 8, 'bot', '👋 **Halo! Selamat datang di Papua Journey!** Saya Papua Journey, siap memandu perjalanan seru Anda di Jayapura.\n\nAda yang bisa saya bantu atau ingin Anda ketahui tentang destinasi wisata, transportasi, budaya, atau kuliner khas Jayapura hari ini? 😊', '2025-06-18 13:07:23'),
(9, 'b99f874f-3eb0-4609-a951-874696a24217', 8, 'user', 'Apa saja rekomendasi wisata di jayapura ya?', '2025-06-18 13:08:09'),
(10, 'b99f874f-3eb0-4609-a951-874696a24217', 8, 'bot', 'Tentu saja! Saya senang sekali bisa berbagi rekomendasi wisata menarik di Jayapura. Papua punya banyak sekali pesona yang siap Anda jelajahi. ✨\n\nBerikut beberapa rekomendasi tempat yang wajib Anda kunjungi di Jayapura:\n\n## 🏝️ **Rekomendasi Wisata Jayapura**\n\n### 1. **Taman Wisata Teluk Youtefa** 📍\n- **Deskripsi**: Kawasan konservasi mangrove yang sangat indah dan lestari. Anda bisa berjalan-jalan di atas jembatan kayu sepanjang 1,2 km sambil menikmati pemandangan Teluk Youtefa yang memukau.\n- **Daya Tarik Utama**:\n    - Hutan mangrove yang hijau dan rimbun.\n    - Jembatan kayu ikonik yang cocok untuk berfoto.\n    - Pemandangan teluk yang menenangkan.\n    - Berbagai jenis burung yang bisa diamati (bird watching).\n- **Aktivitas**: Berjalan santai di jembatan, fotografi alam, edukasi konservasi, dan menikmati **sunset** yang luar biasa indah.\n- **Waktu Terbaik**: Sore hari, terutama saat matahari terbenam untuk pemandangan yang spektakuler.\n\n### 2. **Food Tour Pasar Hamadi** 🍽️🛍️\n- **Deskripsi**: Pengalaman kuliner unik di pasar tradisional terbesar di Jayapura. Ini adalah cara terbaik untuk mencicipi langsung kekayaan rasa Papua dan berinteraksi dengan kehidupan lokal!\n- **Yang Bisa Dicoba**:\n    - Aneka **keripik pisang** khas.\n    - Manisan buah pala yang unik.\n    - Ikan asap segar, kuliner khas pesisir.\n    - Berbagai buah-buahan tropis lokal.\n- **Highlight Tur**: Belajar tentang bahan-bahan lokal, berinteraksi langsung dengan para pedagang, dan mencari oleh-oleh khas Papua.\n- **Perkiraan Biaya**: Rp 75.000 - Rp 100.000 per orang.\n- **Durasi**: Sekitar 3-4 jam.\n- **Waktu Terbaik**: Pagi hari (sekitar pukul 07:00 - 10:00) saat pasar sedang ramai dan produk masih sangat segar.\n\n### 3. **Danau Sentani** 🏞️🛶\n- **Deskripsi**: Salah satu danau terbesar dan paling terkenal di Papua, menawarkan keindahan alam yang memukau serta budaya lokal yang kental. Sangat cocok untuk perjalanan sehari penuh dari Jayapura.\n- **Akses**: Bisa ditempuh dengan Angkot, Ojek, atau Taksi dari Jayapura.\n    - Angkot: Sekitar Rp 15.000 (60 menit)\n    - Ojek: Sekitar Rp 80.000 - Rp 100.000 (45 menit)\n    - Taksi: Sekitar Rp 120.000 - Rp 150.000 (45 menit, paling nyaman)\n\n### 4. **Pantai Base G** 🏖️☀️\n- **Deskripsi**: Pantai yang sangat populer di Jayapura, terkenal dengan pasir putihnya dan ombak yang tenang, cocok untuk berenang dan bersantai.\n- **Akses**: Mudah dijangkau dari pusat kota Jayapura.\n    - Angkot: Sekitar Rp 10.000 (30 menit)\n    - Ojek: Sekitar Rp 30.000 - Rp 40.000 (20 menit)\n\n### 5. **Bukit Teletubbies** 💚📸\n- **Deskripsi**: Perbukitan hijau yang menawarkan pemandangan padang rumput dan perbukitan yang menyerupai latar di serial Teletubbies. Destinasi yang sangat fotogenik!\n- **Akses**: Perjalanan menuju Bukit Teletubbies memerlukan sedikit usaha karena akses jalan yang mungkin sulit.\n- **Rekomendasi Transportasi**:\n    - **Sewa Mobil + Sopir**: Sangat direkomendasikan! Biaya sekitar Rp 400.000 - Rp 500.000 per hari (durasi sekitar 60 menit dari Jayapura).\n    - **Tour Package**: Anda juga bisa mengikuti paket tur setengah hari yang biasanya sudah termasuk transportasi dan pemandu, dengan biaya sekitar Rp 300.000 - Rp 400.000 per orang.\n\n> 💡 **Tips Perjalanan**: Selalu siapkan kamera Anda, karena setiap sudut Jayapura memiliki keindahan yang layak diabadikan!\n\nApakah ada destinasi spesifik yang ingin Anda ketahui lebih detail atau ada pertanyaan lain seputar wisata di Jayapura? Saya siap membantu! 😊', '2025-06-18 13:08:46'),
(11, 'b99f874f-3eb0-4609-a951-874696a24217', 8, 'user', 'kalau makanannya apa saja?', '2025-06-18 13:09:25'),
(12, 'b99f874f-3eb0-4609-a951-874696a24217', 8, 'bot', 'Wah, tentu saja! Kuliner di Jayapura itu sangat kaya dan unik, siap menggoyang lidah Anda dengan cita rasa khas Papua yang otentik. 😋 Mari kita jelajahi satu per satu!\n\n## 🍽️ **Kuliner Khas Jayapura yang Wajib Dicoba!**\n\n### 1. **Makanan Pokok Khas Papua**\n\n#### **Papeda** 🍚\n- **Deskripsi**: Ini dia ikon kuliner Papua! Papeda adalah makanan pokok yang terbuat dari sagu, bertekstur seperti lem bening yang kenyal dan lengket. Rasanya hambar, sehingga sangat cocok dinikmati dengan berbagai lauk pauk berkuah dan kaya rasa.\n- **Cara menikmati**: Biasanya dimakan dengan cara diseruput langsung dari piring atau digulung menggunakan sumpit.\n- **Penyajian**: Umumnya disajikan bersama **Ikan Kuah Kuning** yang segar atau sayuran seperti Sayur Ganemo.\n- **Cita Rasa**: Kenyal dan lengket, memberikan sensasi unik di mulut.\n- **Dimana Mencoba**:\n    - **Rumah Makan Papua Asli**\n    - **Warung Mama Papua**\n    - Beberapa hotel seperti **Swiss-Belhotel Jayapura** juga menyajikan.\n- **Kisaran Harga**: Rp 25.000 - Rp 50.000 per porsi.\n\n#### **Ikan Kuah Kuning** 🐟🍜\n- **Deskripsi**: Pendamping sempurna untuk Papeda! Ini adalah hidangan ikan segar yang dimasak dengan kuah berwarna kuning cerah dari kunyit dan rempah-rempah khas Papua.\n- **Cita Rasa**: Gurih, segar, dan harum rempah-rempah. Kuahnya yang hangat sangat nikmat.\n- **Jenis Ikan**: Umumnya menggunakan ikan laut segar seperti ikan kakap, baronang, atau kerapu.\n- **Dimana Mencoba**:\n    - **Warung Ikan Bakar Hamadi**\n    - **RM Sari Laut**\n    - Warung-warung pinggir pantai di **Pantai Base G**.\n- **Kisaran Harga**: Rp 35.000 - Rp 75.000 (tergantung jenis dan ukuran ikan).\n\n#### **Udang Selingkuh** 🦐🔥\n- **Deskripsi**: Nama yang unik, rasanya pun unik! Ini adalah udang air tawar berukuran jumbo yang memiliki capit besar menyerupai kepiting. Dagingnya manis, kenyal, dan lezat. Konon, disebut \"selingkuh\" karena bentuknya seperti udang namun memiliki capit kepiting. 😉\n- **Metode Masak**: Bisa dibakar dengan bumbu rempah khas, dimasak kuah santan, atau digoreng tepung.\n- **Musim Terbaik**: Paling nikmat saat musim kemarau (April-Oktober) karena udangnya lebih besar dan segar.\n- **Dimana Mencoba**:\n    - Warung seafood di sekitar **Danau Sentani** (bisa langsung dari nelayan!).\n    - **Pasar Hamadi**.\n- **Kisaran Harga**: Rp 50.000 - Rp 100.000 per porsi (tergantung ukuran).\n\n### 2. **Sayuran dan Lauk Unik Khas Papua**\n\n#### **Sayur Ganemo** 🥬🥥\n- **Deskripsi**: Sayuran hijau khas Papua, sejenis bayam, yang dimasak dengan santan kental dan ikan teri.\n- **Cita Rasa**: Gurih, segar, dan sedikit pedas, sangat menggugah selera.\n- **Manfaat**: Tinggi vitamin A dan zat besi, sangat sehat!\n- **Dimana Mencoba**: Warung-warung tradisional di sekitar **Danau Sentani**.\n- **Kisaran Harga**: Rp 15.000 - Rp 25.000.\n\n#### **Ulat Sagu Bakar** 🐛🔥\n- **Deskripsi**: Bagi Anda yang berjiwa petualang kuliner, ini patut dicoba! Ulat sagu adalah larva yang hidup di batang pohon sagu, kaya akan protein.\n- **Penyajian**: Umumnya dibakar atau digoreng dengan sedikit garam.\n- **Cita Rasa**: Gurih dengan bagian dalam yang creamy.\n- **Catatan Budaya**: Merupakan makanan tradisional yang dipercaya memiliki khasiat kesehatan dan menjadi sumber protein penting bagi masyarakat lokal.\n- **Dimana Mencoba**:\n    - **Kampung Asei** (dekat Danau Sentani)\n    - **Pasar Tradisional Sentani**\n    - Kadang tersedia di festival kuliner Papua.\n- **Kisaran Harga**: Rp 30.000 - Rp 50.000 per porsi.\n\n### 3. **Camilan & Oleh-oleh Khas Jayapura**\n\n#### **Keripik Pisang** 🍌\n- **Deskripsi**: Cemilan populer yang terbuat dari pisang lokal (seperti pisang raja, kepok, atau tanduk) yang diiris tipis lalu digoreng hingga kering dan renyah.\n- **Varian Rasa**: Tersedia dalam rasa original, manis, atau pedas.\n- **Dimana Membeli**:\n    - **Pasar Hamadi**\n    - Toko oleh-oleh di Jayapura\n    - Bandara Sentani.\n- **Kisaran Harga**: Rp 10.000 - Rp 25.000 per bungkus.\n\n#### **Manisan Pala** 🍈🍬\n- **Deskripsi**: Buah pala yang diawetkan dengan gula, menghasilkan manisan dengan rasa manis dan aroma rempah yang harum.\n- **Manfaat**: Dipercaya baik untuk pencernaan dan kesehatan.\n- **Dimana Membeli**:\n    - **Pasar Hamadi**\n    - Toko spesialis rempah\n    - Pusat oleh-oleh Jayapura.\n- **Kisaran Harga**: Rp 15.000 - Rp 30.000 per bungkus.\n\n> 💡 **Tips Kuliner**: Jangan ragu untuk mencoba kuliner lokal, ini adalah bagian tak terpisahkan dari petualangan Anda di Jayapura! Selalu tanyakan kepada penduduk lokal untuk rekomendasi warung terbaik.\n\nApakah ada kuliner tertentu yang menarik perhatian Anda atau ada hal lain yang ingin Anda ketahui lebih lanjut tentang Jayapura? Saya siap membantu! 😊', '2025-06-18 13:09:44'),
(13, 'fcb7d54c-1991-4936-91e7-7c8fadcc25d6', 8, 'user', 'Halo', '2025-06-18 14:41:04'),
(14, 'fcb7d54c-1991-4936-91e7-7c8fadcc25d6', 8, 'bot', '👋 **Halo! Selamat datang di Papua Journey!** Senang sekali bisa menyambut Anda di sini. 😊\n\nSaya **Papua Journey**, siap membantu Anda menjelajahi keindahan dan pesona **Jayapura**! 🏝️\n\nAda yang bisa saya bantu terkait destinasi wisata, transportasi, kuliner, atau budaya di Jayapura? Jangan sungkan bertanya ya! Saya siap memberikan informasi terbaik untuk perjalanan Anda. ✨', '2025-06-18 14:41:12'),
(15, '2cfa0e75-a052-4efb-8dc7-9042a5296545', 8, 'user', 'Halo', '2025-06-19 06:36:08'),
(16, '2cfa0e75-a052-4efb-8dc7-9042a5296545', 8, 'bot', '👋 **Halo! Selamat datang di Papua Journey!** Senang sekali bisa menyapa Anda di sini. 😊\n\nSaya adalah tour guide virtual Anda yang siap membantu menjelajahi keindahan Jayapura. Ada apa yang bisa saya bantu hari ini terkait rencana perjalanan Anda di Jayapura? Apakah Anda mencari informasi tentang destinasi wisata, transportasi, budaya, atau kuliner yang menggoda?\n\nJangan ragu untuk bertanya, saya siap berbagi info menarik! 🏝️✨', '2025-06-19 06:36:34');

-- --------------------------------------------------------

--
-- Table structure for table `chat_conversation_sessions`
--

CREATE TABLE `chat_conversation_sessions` (
  `conversation_id` varchar(36) NOT NULL,
  `user_id` int(11) NOT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_message_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `message_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_conversation_sessions`
--

INSERT INTO `chat_conversation_sessions` (`conversation_id`, `user_id`, `started_at`, `last_message_at`, `message_count`, `is_active`) VALUES
('2cfa0e75-a052-4efb-8dc7-9042a5296545', 8, '2025-06-19 06:36:08', '2025-06-19 06:36:34', 2, 1),
('b99f874f-3eb0-4609-a951-874696a24217', 8, '2025-06-18 13:07:08', '2025-06-18 13:09:44', 6, 1),
('fcb7d54c-1991-4936-91e7-7c8fadcc25d6', 8, '2025-06-18 14:41:04', '2025-06-18 14:41:12', 2, 1);

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
(4, 'trenguide@gmail.com', '$2y$10$Q1N04h86zSwRnGlDP1CSfOau1Jg6Mrk57Yyt6QlcE/5O52092Tdda', 'Tren Tour Guide', 'Trendo', '09277246729', 'Jl. Soa Siu Dok 2 Bawah Jayapura, Papua.', 'jasa', 'Jasa pemandu Tour Guide', 'default-umkm.jpg', 'active', '2025-06-11 03:36:41', '2025-06-11 03:37:08');

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
(1, 'brian@gmail.com', '$2y$10$3a0kZlwNH07iK/qrZZgKH.QVOrXgodwxrSSzeXHf/t.lQMH2wa3.y', 'Brian Domani', '082133871850', 'Surakarta Jawa Tengah', 'user_1_1748709563.jpg', '2025-05-31 15:34:52', '2025-05-31 16:39:34'),
(3, 'naura@gmail.com', '$2y$10$gc6vW85ACp4YDdg8aHSqY.rN51jbEdWSwZLivNI/.P8eAZIwHAYY2', 'Naura Tsani Maya', '082324096996', 'Sragen Jawa Tengah', 'user_3_1748709699.jpg', '2025-05-31 15:59:00', '2025-05-31 16:42:05'),
(8, 'slemandanpapua@gmail.com', '$2y$10$gjRuv9s6ayJchwic7e2Aa.OuPncvZwFx8zgCDKjjc3F2cuZ2igqbG', 'Trendo', '081357427930', 'furia puskopad block a', 'user_8_1750234146.png', '2025-06-17 16:00:42', '2025-06-18 08:11:39');

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
(1, 'Lembah Baliem', 'Lembah yang terkenal dengan Festival Budaya Suku Dani dan pemandangan alam pegunungan yang menakjubkan', 30000.00, 'budaya', 'Distrik Wamena, Kabupaten Jayawijaya, Papua Pegunungan', '08:00 - 17:00', 'lembah_baliem.jpg', '2025-06-13 10:36:49', '2025-06-13 10:36:49'),
(2, 'Raja Ampat', 'Surga bawah laut dengan keanekaragaman hayati laut tertinggi di dunia', 150000.00, 'alam', 'Kepulauan Raja Ampat, Papua Barat Daya', '24 Jam', 'raja_ampat.jpg', '2025-06-13 10:36:49', '2025-06-13 10:36:49'),
(3, 'Taman Nasional Lorentz', 'Satu-satunya taman nasional di Asia Tenggara yang mencakup area salju tropis, hutan hujan, dan pegunungan tinggi', 50000.00, 'alam', 'Kabupaten Mimika, Jayawijaya, Papua', '06:00 - 18:00', 'lorentz.jpg', '2025-06-13 10:36:49', '2025-06-13 10:36:49'),
(4, 'Karmon Waterfall', 'Air Terjun Karmon adalah salah satu destinasi wisata alam yang menakjubkan di Kabupaten Biak Numfor, Provinsi Papua. Terletak di tengah-tengah hutan tropis yang lebat, air terjun ini memiliki ketinggian sekitar 40 meter. Keindahan Air Terjun Karmon terletak pada aliran airnya yang jernih dan suasana sekitarnya yang alami dan menawan.', 10000.00, 'alam', 'Kampung Karmon, Distrik Warsa, Biak bagian utara', '08:00 - 17:00', '684c2e7cb31d0.jpg', '2025-06-13 13:58:20', '2025-06-13 13:58:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `artikel`
--
ALTER TABLE `artikel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kategori` (`kategori`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_umkm_id` (`umkm_id`);

--
-- Indexes for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation_id` (`conversation_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_conversation_created` (`user_id`,`conversation_id`,`created_at`);

--
-- Indexes for table `chat_conversation_sessions`
--
ALTER TABLE `chat_conversation_sessions`
  ADD PRIMARY KEY (`conversation_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_last_message` (`last_message_at`),
  ADD KEY `idx_user_active` (`user_id`,`is_active`,`last_message_at`);

--
-- Indexes for table `umkm`
--
ALTER TABLE `umkm`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `artikel`
--
ALTER TABLE `artikel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `umkm`
--
ALTER TABLE `umkm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `wisata`
--
ALTER TABLE `wisata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  ADD CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_conversation_sessions`
--
ALTER TABLE `chat_conversation_sessions`
  ADD CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
