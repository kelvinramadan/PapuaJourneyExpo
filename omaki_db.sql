-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 11, 2025 at 06:00 AM
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
(3, 'naura@gmail.com', '$2y$10$gc6vW85ACp4YDdg8aHSqY.rN51jbEdWSwZLivNI/.P8eAZIwHAYY2', 'Naura Tsani Maya', '082324096996', 'Sragen Jawa Tengah', 'user_3_1748709699.jpg', '2025-05-31 15:59:00', '2025-05-31 16:42:05');

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `artikel`
--
ALTER TABLE `artikel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `umkm`
--
ALTER TABLE `umkm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `artikel`
--
ALTER TABLE `artikel`
  ADD CONSTRAINT `artikel_ibfk_1` FOREIGN KEY (`umkm_id`) REFERENCES `umkm` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
