-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 26, 2025 at 11:48 PM
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
(8, 1, 11, 'confirmed', '', '2025-06-26 00:44:30');

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
(3, 4, 'Tour Guide', 'Kami menyediakan layanan tour guide profesional yang siap menemani Anda menjelajahi keindahan dan keunikan destinasi wisata dengan cara yang lebih personal dan berkesan. Dengan pengalaman, keramahan, dan pengetahuan lokal yang mendalam, kami tidak hanya menjadi pemandu, tapi juga sahabat perjalanan Anda. Setiap rute kami rancang fleksibel sesuai keinginan Anda, menghadirkan pengalaman wisata yang otentik—mulai dari menikmati alam yang memukau, mengenal budaya dan tradisi lokal, hingga mencicipi kuliner khas yang menggugah selera. Keamanan dan kenyamanan Anda adalah prioritas kami, sehingga Anda bisa menikmati liburan tanpa khawatir. Jadikan setiap perjalanan lebih dari sekadar kunjungan, tetapi petualangan yang meninggalkan kesan mendalam bersama kami.', 200000.00, 'jasa', 'artikel_4_1749613163.jpg', 'active', '2025-06-11 03:39:23', '2025-06-19 05:54:51');

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
(4, 'Papua Paradise Eco Resort', 'Terletak di pulau tak berpenghuni di Raja Ampat, resort ini menyajikan bungalow di atas laut yang dibangun dari kayu lokal. Suasana tenang dipadu alam: hutan tropis, laguna, dan terumbu karang langsung di halaman. Ada spa eksklusif dengan pemandangan laut, satu-satunya di wilayah ini .', 3500000.00, 'Birie Island, Arefi, Selat Sagawin, Kabupaten Raja Ampat, Papua Barat, Pulau Birie', 'resort', 'bungalow terapung , Restoran “Seaview” , Spa over‑water , Shuttle bandara Sorong , Bar  ', '1750058523_429742622.jpg', '2025-06-16 07:22:03', '2025-06-16 07:22:03');

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
(4, 4, '2025-06-27', 0, 0, 0, 0.00, '2025-06-26 21:21:47', '2025-06-26 21:21:47');

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
(1, 2, 1, '::1', '2025-06-27 04:39:07', '6f02u1hb506npcldu15372j7qn', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');

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
(11, 8, 'TRX202506260238008', 50000.00, 'paid', 'bank_transfer', 'payment_TRX202506260238008_1750898415.jpg', '2025-06-27 07:40:00', '2025-06-26 07:44:30', 1, '2025-06-26 07:44:30', '2025-06-26 00:38:00', '2025-06-26 00:44:30');

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
(6, 11, 'wisata', 6, 'Pantai Base-G', 1, 50000.00, 50000.00, '2025-06-27', NULL, NULL, '');

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
(1, 'brian@gmail.com', '$2y$10$b6zQG9GgQaVK7JhYiPoY1eW6tkIo6kc5J5tSMhj8MKZUAPj/Kvk4O', 'Brian Domanii', '082133871850', 'Banjarsari Surakarta Jawa Tengah', 'user_1_1750312287.jpg', '2025-05-31 15:34:52', '2025-06-19 05:51:27'),
(3, 'naura@gmail.com', '$2y$10$gc6vW85ACp4YDdg8aHSqY.rN51jbEdWSwZLivNI/.P8eAZIwHAYY2', 'Naura Tsani Maya', '082324096996', 'Sragen Jawa Tengah', 'user_3_1748709699.jpg', '2025-05-31 15:59:00', '2025-05-31 16:42:05'),
(8, 'slemandanpapua@gmail.com', '$2y$10$8J8g9PhSbxSBwf6bplaYY.GeWgq.7x1NtxKboIsWwJpUNpkgtf0z2', 'Trendo', '081357426645', 'furia puskopad block a', 'user_8_1750800567.jpg', '2025-06-23 13:29:50', '2025-06-24 21:29:27'),
(9, 'samuelrobail@gmail.com', '$2y$10$.Q72hHPSWfvri1e9281fw.RCSfsJ83c8BXQRwA1oStWz3Mytibw6G', 'Ibnu', '081357427945', 'furia puskopad block a', 'default-user.jpg', '2025-06-24 20:46:37', '2025-06-26 19:59:19');

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
(6, 'Pantai Base-G', 'Pantai Base G atau juga dikenal sebagai Tanjung Ria terletak disebelah utara Kota Jayapura, Papua. Pantai Base G berlokasi sekitar 10 km dari Kota Jayapura di Distrik Jayapura Utara. Pantai Base G dapat dikunjungi dengan menggunakan berbagai jenis kendaraan dengan waktu tempuh kurang lebih 20 menit dari kota, dengan akses jalan beraspal. Apabila pengunjung mengambil patokan Bandara Sentani, waktu tempuh darat sekitar 1,5 jam.\\r\\n\\r\\nNama Base G berasal dari sejarahnya yang dahulu merupakan basis militer dengan nama Base G Camp pada masa Perang Dunia II. Kawasan Pantai Base G mempunyai luas sekitar 90 ha, panjang garis pantai 6-15 meter, dengan lebar pantai belakang 15-40 meter, lebar perairan 150-400 meter.\\r\\n\\r\\nPantai Base G Jayapura merupakan salah satu tujuan wisata unggulan di kota Jayapura, Papua. Meskipun masih berada dalam wilayah kota, pantai ini menyuguhkan keindahan alam yang jarang dimiliki oleh pantai-pantai lain di Jayapura. Lokasinya yang terletak di sebelah barat kota Jayapura membuat pantai ini mudah diakses.', 50000.00, 'alam', 'Tj. Ria, Kec. Jayapura Utara, Kota Jayapura, Papua', '06.00 - 21.00 ', 'wisata_685aa4c3aae6c.jpg', '2025-06-24 13:14:43', '2025-06-24 13:14:43');

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
(3, 6, '2025-06-27', 4, 4, 1, 50000.00, '2025-06-26 19:50:26', '2025-06-26 20:06:26');

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
(7, 6, 8, '::1', '2025-06-27 03:06:25', '3aj0j4ohfec3ltu9c18ncnki20', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36');

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
  ADD KEY `idx_umkm_id` (`umkm_id`);

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
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_code` (`transaction_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_user_transactions` (`user_id`,`payment_status`),
  ADD KEY `idx_payment_status_proof` (`payment_status`,`payment_proof`);

--
-- Indexes for table `transaksi_items`
--
ALTER TABLE `transaksi_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
  ADD KEY `idx_transaction_items` (`transaksi_id`,`item_type`),
  ADD KEY `idx_item_type_id` (`item_type`,`item_id`);

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
-- AUTO_INCREMENT for table `admin_payment_logs`
--
ALTER TABLE `admin_payment_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `artikel`
--
ALTER TABLE `artikel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `chat_conversations`
--
ALTER TABLE `chat_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `penginapan_statistics`
--
ALTER TABLE `penginapan_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `penginapan_views`
--
ALTER TABLE `penginapan_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pesanpenginapan`
--
ALTER TABLE `pesanpenginapan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `transaksi_items`
--
ALTER TABLE `transaksi_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `umkm`
--
ALTER TABLE `umkm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `wisata`
--
ALTER TABLE `wisata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wisata_statistics`
--
ALTER TABLE `wisata_statistics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `wisata_views`
--
ALTER TABLE `wisata_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Constraints for table `transaksi_items`
--
ALTER TABLE `transaksi_items`
  ADD CONSTRAINT `fk_transaksi_items` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE;

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
