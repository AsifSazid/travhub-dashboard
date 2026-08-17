-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Aug 16, 2026 at 12:07 AM
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
-- Table structure for table `doc_type_registry`
--

CREATE TABLE `doc_type_registry` (
  `id` int(10) UNSIGNED NOT NULL,
  `doc_type` varchar(64) NOT NULL,
  `display_name` varchar(128) NOT NULL,
  `smb_folder` varchar(64) NOT NULL COMMENT 'Must be one of the 9 fixed folders',
  `tracks_expiry` tinyint(1) NOT NULL DEFAULT 0,
  `tracks_validity` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Populate validity_from/to from doc_data',
  `has_structured_schema` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = use doc_data; 0 = use key_fields',
  `updates_traveler_column` varchar(64) DEFAULT NULL COMMENT 'e.g. passport_info, nid_info; NULL for no mirror',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doc_type_registry`
--

INSERT INTO `doc_type_registry` (`id`, `doc_type`, `display_name`, `smb_folder`, `tracks_expiry`, `tracks_validity`, `has_structured_schema`, `updates_traveler_column`, `display_order`, `description`, `is_active`) VALUES
(1, 'passport', 'Passport', 'passport_identity', 1, 0, 1, 'passport_info', 1, NULL, 1),
(2, 'nid', 'National ID', 'nid', 0, 0, 1, 'nid_info', 2, NULL, 1),
(3, 'visa', 'Visa', 'countries_documents', 1, 1, 1, NULL, 3, NULL, 1),
(4, 'visa_stamp', 'Visa Stamp / Entry-Exit', 'countries_documents', 0, 0, 0, NULL, 4, NULL, 1),
(5, 'air_ticket', 'Air Ticket', 'travel_history', 0, 0, 1, NULL, 5, NULL, 1),
(6, 'hotel_voucher', 'Hotel Voucher', 'travel_history', 0, 0, 1, NULL, 6, NULL, 1),
(7, 'invitation_letter', 'Invitation Letter', 'travel_history', 0, 0, 0, NULL, 7, NULL, 1),
(8, 'bank_statement', 'Bank Statement', 'financial_documents', 0, 0, 1, NULL, 8, NULL, 1),
(9, 'sponsor_letter', 'Sponsor Letter', 'financial_documents', 0, 0, 0, NULL, 9, NULL, 1),
(10, 'employment_letter', 'Employment Letter', 'professional_documents', 0, 0, 0, NULL, 10, NULL, 1),
(11, 'education_certificate', 'Education Certificate', 'professional_documents', 0, 0, 0, NULL, 11, NULL, 1),
(12, 'medical_report', 'Medical Report', 'personal_documents', 0, 0, 0, NULL, 12, NULL, 1),
(13, 'vaccination_card', 'Vaccination Card', 'personal_documents', 0, 0, 0, NULL, 13, NULL, 1),
(14, 'marriage_certificate', 'Marriage Certificate', 'personal_documents', 0, 0, 0, NULL, 14, NULL, 1),
(15, 'birth_certificate', 'Birth Certificate', 'personal_documents', 0, 0, 0, NULL, 15, NULL, 1),
(16, 'photo', 'Photograph', 'photos_signature', 0, 0, 0, NULL, 16, NULL, 1),
(17, 'signature', 'Signature', 'photos_signature', 0, 0, 0, NULL, 17, NULL, 1),
(18, 'other', 'Other Document', 'all_documents', 0, 0, 0, NULL, 99, NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `doc_type_registry`
--
ALTER TABLE `doc_type_registry`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_doc_type` (`doc_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `doc_type_registry`
--
ALTER TABLE `doc_type_registry`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
