-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 22, 2025 at 12:59 AM
-- Server version: 10.11.15-MariaDB
-- PHP Version: 8.4.16

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
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `service_count` int(11) DEFAULT NULL,
  `service_type` varchar(100) DEFAULT NULL,
  `client_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`client_info`)),
  `service_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`service_data`)),
  `lead_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`lead_info`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `lead_status` varchar(128) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `uuid`, `service_count`, `service_type`, `client_info`, `service_data`, `lead_info`, `metadata`, `lead_status`, `created_at`, `updated_at`) VALUES
(1, '698913a7-f636-4c08-b723-8f10d7b4f8ae', 1, 'Array', '{\"type\":\"new\",\"name\":\"Asif M Sazid\",\"phones\":[],\"phoneTypes\":[\"whatsapp\"],\"emails\":[],\"position\":\"\",\"company\":\"\",\"socialMedia\":[]}', '{\"visa\":{\"country\":\"\",\"visaCategory\":\"\",\"visaSubCategory\":\"\",\"dateOfTravel\":\"2025-12-11\",\"dateOfReturn\":\"2025-12-11\",\"applicationType\":\"single\",\"costBearer\":\"self\",\"invitationStatus\":\"no\"}}', '{\"conversationNote\":\"No Data\",\"workPriority\":\"easy\"}', '{\"submittedAt\":\"2025-12-11T12:15:38.153Z\",\"source\":\"web_dashboard\"}', 'pending', '2025-12-11 12:15:39', '2025-12-11 12:15:39'),
(2, 'aba5a7b1-5a69-4c14-8a57-8997e2d94c0f', 1, 'Array', '{\"type\":\"new\",\"name\":\"Tarekul Islam\",\"phones\":[\"01611482773\"],\"phoneTypes\":[\"whatsapp\"],\"emails\":[\"tarekul.du@gmail.com\"],\"position\":\"\",\"company\":\"\",\"socialMedia\":[]}', '{\"visa\":{\"country\":\"usa\",\"visaCategory\":\"tourist\",\"visaSubCategory\":\"single\",\"dateOfTravel\":\"2025-12-24\",\"dateOfReturn\":\"2025-12-30\",\"applicationType\":\"single\",\"costBearer\":\"self\",\"invitationStatus\":\"no\"}}', '{\"conversationNote\":\"Need to apply for tourist visa.\",\"workPriority\":\"urgent\"}', '{\"submittedAt\":\"2025-12-11T12:19:40.847Z\",\"source\":\"web_dashboard\"}', 'pending', '2025-12-11 12:19:43', '2025-12-11 12:19:43'),
(3, '6c197120-fba3-4d13-aefb-271bff483425', 2, 'Array', '{\"type\":\"new\",\"name\":\"TAREKUL ISLAM\",\"phones\":[\"01611482773\"],\"phoneTypes\":[\"whatsapp\"],\"emails\":[\"tarekul.du@gmail.com\"],\"position\":\"\",\"company\":\"TravHub\",\"socialMedia\":[]}', '{\"visa\":{\"country\":\"\",\"visaCategory\":\"\",\"visaSubCategory\":\"\",\"dateOfTravel\":\"2025-12-11\",\"dateOfReturn\":\"2025-12-11\",\"applicationType\":\"single\",\"costBearer\":\"self\",\"invitationStatus\":\"no\"},\"hotel\":{\"totalBookings\":1,\"bookings\":[{\"bookingNumber\":1,\"checkIn\":\"2025-12-17\",\"checkOut\":\"2025-12-23\",\"pax\":3,\"rooms\":1,\"nights\":1,\"destination\":\"Royal Benja\",\"note\":\"1 Double room \\n1 Twin Room without Breakfast\"}]}}', '{\"conversationNote\":\"Need urgently\",\"workPriority\":\"easy\"}', '{\"submittedAt\":\"2025-12-11T12:22:36.783Z\",\"source\":\"web_dashboard\"}', 'pending', '2025-12-11 12:22:39', '2025-12-11 12:22:39'),
(4, '853f7a8a-ea1e-462c-984c-c73e8a28c41d', 1, 'Array', '{\"type\":\"new\",\"name\":\"Asif M sazid\",\"phones\":[],\"phoneTypes\":[\"whatsapp\"],\"emails\":[],\"position\":\"\",\"company\":\"\",\"socialMedia\":[]}', '{\"visa\":{\"country\":\"\",\"visaCategory\":\"\",\"visaSubCategory\":\"\",\"dateOfTravel\":\"2025-12-13\",\"dateOfReturn\":\"2025-12-13\",\"applicationType\":\"single\",\"costBearer\":\"self\",\"invitationStatus\":\"no\"}}', '{\"conversationNote\":\"No note\",\"workPriority\":\"easy\"}', '{\"submittedAt\":\"2025-12-13T06:07:35.808Z\",\"source\":\"web_dashboard\"}', 'pending', '2025-12-13 06:07:33', '2025-12-13 06:07:33');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
