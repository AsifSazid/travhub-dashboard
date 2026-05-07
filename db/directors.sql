-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 29, 2026 at 09:04 PM
-- Server version: 10.11.10-MariaDB-log
-- PHP Version: 8.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `travhub_dashboard`
--

-- --------------------------------------------------------

--
-- Table structure for table `directors`
--

CREATE TABLE `directors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `type` varchar(128) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` longtext DEFAULT NULL,
  `phone` longtext DEFAULT NULL,
  `address` text DEFAULT NULL,
  `basic_info` longtext DEFAULT NULL COMMENT 'basic info, detailed address, banking info',
  `emergency_contact` longtext DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `director_path` text DEFAULT NULL,
  `image_name` text DEFAULT NULL,
  `profile_photo` text DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL COMMENT '{\r\n    "created_by_date": "demo_name; 12-10-2025 10:11",\r\n    "updated_by_date": [\r\n        {\r\n            "1": "demo_name; 12-10-2025 10:11",\r\n            "2": "demo_name; 12-10-2025 10:11",\r\n            "3": "demo_name; 12-10-2025 10:11",\r\n        }\r\n        .....\r\n\r\n        not more than 20\r\n    ]\r\n}'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `directors`
--
ALTER TABLE `directors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `client_id` (`sys_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `directors`
--
ALTER TABLE `directors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
