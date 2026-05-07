-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 07, 2026 at 04:25 PM
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
-- Database: `travhub_dev`
--

-- --------------------------------------------------------

--
-- Table structure for table `travelers`
--

CREATE TABLE `travelers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `type` varchar(128) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` longtext DEFAULT NULL,
  `phone` longtext DEFAULT NULL,
  `address` text DEFAULT NULL,
  `basic_info` longtext DEFAULT NULL COMMENT 'application form information',
  `nid_info` longtext DEFAULT NULL COMMENT 'nid extracted data in JSON format',
  `passport_info` longtext DEFAULT NULL COMMENT 'passport extracted data in JSON format',
  `travel_history` longtext DEFAULT NULL COMMENT 'single history structure->\r\n{\r\n  "country": "country_name",\r\n  "entry": {\r\n    "date": "entry_date",\r\n    "city": "entry_city",\r\n    "purpose": "purpose"\r\n  },\r\n  "exit": {\r\n    "date": "exit_date",\r\n    "city": "exit_city",\r\n    "purpose": "purpose"\r\n  },\r\n  "total_stay": 10\r\n}',
  `status` varchar(50) DEFAULT NULL,
  `smb_path` text DEFAULT NULL,
  `server_path` text DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL COMMENT '{\r\n    "created_by_date": "demo_name; 12-10-2025 10:11",\r\n    "updated_by_date": [\r\n        {\r\n            "1": "demo_name; 12-10-2025 10:11",\r\n            "2": "demo_name; 12-10-2025 10:11",\r\n            "3": "demo_name; 12-10-2025 10:11",\r\n        }\r\n        .....\r\n\r\n        not more than 20\r\n    ]\r\n}'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
--
-- Indexes for table `travelers`
--
ALTER TABLE `travelers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `travelers`
--
ALTER TABLE `travelers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
