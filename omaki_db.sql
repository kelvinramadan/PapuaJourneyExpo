-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 31, 2025 at 06:00 PM
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
(1, 'papuabluedive@gmail.com', '$2y$10$YlaHkKRycMeWJHaibTo6HOsY335duiKFrLsSTwXnKdLw6v6kS.l2G', 'Papua Blue Dive', 'Kepin Marhaban', '082166384920', 'Jln. Wisata Laut No. 7, Distrik Waisai, Kabupaten Raja Ampat, Papua Barat Daya', 'wisata', '“Papua Blue Dive adalah layanan snorkeling profesional yang menghadirkan pengalaman eksplorasi terumbu karang dan keindahan laut Papua, khususnya di wilayah Raja Ampat.”', 'umkm_1_1748706894.png', 'active', '2025-05-31 15:35:59', '2025-05-31 15:54:54');

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
(1, 'brian@gmail.com', '$2y$10$3a0kZlwNH07iK/qrZZgKH.QVOrXgodwxrSSzeXHf/t.lQMH2wa3.y', 'Brian Domani Kelvin', '082133871850', 'Surakarta Jateng', 'user_1_1748707027.jpg', '2025-05-31 15:34:52', '2025-05-31 16:00:20'),
(3, 'naura@gmail.com', '$2y$10$gc6vW85ACp4YDdg8aHSqY.rN51jbEdWSwZLivNI/.P8eAZIwHAYY2', 'Naura Tsani Maya', '082324096996', 'Sragen Jateng', 'default-user.jpg', '2025-05-31 15:59:00', '2025-05-31 16:00:03');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `umkm`
--
ALTER TABLE `umkm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
