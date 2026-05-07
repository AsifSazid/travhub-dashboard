-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 21, 2026 at 10:50 PM
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
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
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
  `contact_n_communication_details` longtext DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL COMMENT '            { id: 1, name: ''Management'' },\r\n            { id: 2, name: ''Visa'' },\r\n            { id: 3, name: ''Package'' },\r\n            { id: 4, name: ''Ticket'' },\r\n            { id: 5, name: ''IT'' },\r\n            { id: 6, name: ''Student'' },\r\n            { id: 7, name: ''Medical'' },\r\n            { id: 8, name: ''Account'' }',
  `department_name` varchar(255) DEFAULT NULL,
  `company_related_info` longtext DEFAULT NULL,
  `previous_job_details` longtext DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `emp_path` text DEFAULT NULL,
  `image_name` text DEFAULT NULL,
  `profile_photo` text DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL COMMENT '{\r\n    "created_by_date": "demo_name; 12-10-2025 10:11",\r\n    "updated_by_date": [\r\n        {\r\n            "1": "demo_name; 12-10-2025 10:11",\r\n            "2": "demo_name; 12-10-2025 10:11",\r\n            "3": "demo_name; 12-10-2025 10:11",\r\n        }\r\n        .....\r\n\r\n        not more than 20\r\n    ]\r\n}'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `uuid`, `sys_id`, `type`, `name`, `email`, `phone`, `address`, `basic_info`, `emergency_contact`, `contact_n_communication_details`, `department_id`, `department_name`, `company_related_info`, `previous_job_details`, `status`, `emp_path`, `image_name`, `profile_photo`, `meta_data`) VALUES
(2, '874aa904-3618-498b-93cb-28b8b66c7497', 'EMP-5102501', 'permanent', 'Asif M Sazid', '{\"primary\":\"travhub.asif@gmail.com\"}', '{\"primary_no\":\"+8801751906710\"}', '{\"address_line_1\":\"492\\/C-44, Amaya Road, Borobari\",\"address_line_2\":\"Kanchkura, Uttarkhan\",\"city\":\"Dhaka\",\"state\":\"Bangladesh\",\"zip_code\":\"1230\",\"country\":\"\"}', '{\"date_of_birth\":\"2000-03-08\",\"blood_group\":\"b+\"}', '{\"person\":\"Hazi Md. Rofiqul Islam\",\"relation\":\"Father\",\"phone\":\"01714445709\",\"address\":{\"address_line_1\":\"492\\/C-44, Amaya Road, Borobari\",\"address_line_2\":\"Kanchkura, Uttarkhan\",\"city\":\"Dhaka\",\"state\":\"Bangladesh\",\"zip_code\":\"1230\"}}', NULL, 5, 'IT', '{\"designation\":\"Software Developer\",\"company_role\":\"NA\",\"date_of_join\":\"2025-10-21\",\"employment_type\":\"permanent\",\"status\":\"active\",\"created_at\":\"2026-01-21 20:46:14\",\"created_by\":\"system_admin\",\"created_by_id\":0,\"department\":\"IT\",\"department_id\":\"5\"}', NULL, 'active', NULL, '[{\"original_name\":\"Cover_Pic-removebg-preview.png\",\"stored_name\":\"6970e6b6ebf54_20260121_204614.png\",\"file_type\":\"image\\/png\",\"file_size\":136881,\"file_path\":\"employees\\/EMP-5102501_AsifMSazid\\/6970e6b6ebf54_20260121_204614.png\",\"upload_date\":\"2026-01-21 20:46:14\"}]', NULL, '{\n    \"created_by_date\": {\n        \"user\": \"system_admin\",\n        \"date\": \"21-01-2026 20:46\"\n    },\n    \"updated_by_date\": []\n}');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `client_id` (`sys_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
