-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 03, 2026 at 09:26 AM
-- Server version: 8.0.30
-- PHP Version: 8.2.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `travhub_workflow`
--

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(17) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `type` varchar(128) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `phone` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `basic_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci COMMENT 'basic info, detailed address, banking info',
  `company_reg_compliance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `contact_n_communication_details` longtext,
  `auth_sign_info` longtext,
  `internal_control_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `work_name` text,
  `status` varchar(50) DEFAULT NULL,
  `vendor_status` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'jodi vendor client theke make kora hoy',
  `is_vendor` int DEFAULT NULL,
  `vendor_sys_id` varchar(17) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL COMMENT 'if is_vendor = 1',
  `meta _date` longtext COMMENT '{\r\n    "created_by_date": "demo_name; 12-10-2025 10:11",\r\n    "updated_by_date": [\r\n        {\r\n            "1": "demo_name; 12-10-2025 10:11",\r\n            "2": "demo_name; 12-10-2025 10:11",\r\n            "3": "demo_name; 12-10-2025 10:11",\r\n        }\r\n        .....\r\n\r\n        not more than 20\r\n    ]\r\n}'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `vendors`
--

INSERT INTO `vendors` (`id`, `uuid`, `sys_id`, `type`, `name`, `email`, `phone`, `address`, `basic_info`, `company_reg_compliance`, `contact_n_communication_details`, `auth_sign_info`, `internal_control_info`, `work_name`, `status`, `vendor_status`, `is_vendor`, `vendor_sys_id`, `meta _date`) VALUES
(1, '982aad9f-da57-45db-be96-d049a9b8d971', 'TH-NR-26-00K-001', 'individual', 'Shakil Ahmed', '{\"primary\":\"travhub.shakil@gmail.com\",\"secondary\":[]}', '{\"primary_no\":\"+8801848484848\",\"secondary_no\":[]}', '{\"address_line_1\":\"Road 6\",\"address_line_2\":\"Sector-3\",\"city\":\"Dhaka\",\"state\":\"\",\"zip_code\":\"1230\"}', NULL, NULL, NULL, NULL, NULL, 'New Work', 'active', NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`vendor_sys_id`),
  ADD UNIQUE KEY `client_sys_id` (`vendor_sys_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
