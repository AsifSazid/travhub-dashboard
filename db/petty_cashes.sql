-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 09, 2026 at 03:32 AM
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
-- Table structure for table `petty_cashes`
--

CREATE TABLE `petty_cashes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `user_sys_id` varchar(16) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `to_user_sys_id` varchar(16) DEFAULT NULL,
  `to_user_name` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `purpose` longtext DEFAULT NULL,
  `details` longtext NOT NULL,
  `type` enum('conveyance_bill','other_bill','loan','petty_cash') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `ref` longtext DEFAULT NULL,
  `meta_data` longtext NOT NULL COMMENT '{ "created_by_date": "demo_name; 12-10-2025 10:11", "updated_by_date": [ { "1": "demo_name; 12-10-2025 10:11", "2": "demo_name; 12-10-2025 10:11", "3": "demo_name; 12-10-2025 10:11", } ..... not more than 20 ] }'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `petty_cashes`
--
ALTER TABLE `petty_cashes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `petty_cashes`
--
ALTER TABLE `petty_cashes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
