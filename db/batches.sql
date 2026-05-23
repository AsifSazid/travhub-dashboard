-- phpMyAdmin SQL Dump
-- TravHub v2 : `batches` table
-- One batch = one upload session. Created as a shell (documents/summary NULL),
-- then filled in after the per-file Gemini loop completes.
--
-- Database: `travhub_dev`
-- Engine: InnoDB   Charset: utf8mb4   Collation: utf8mb4_general_ci

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
-- Table structure for table `batches`
--

CREATE TABLE `batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL COMMENT 'THR-BT-26-00K001',
  `traveler_id` varchar(16) NOT NULL COMMENT 'FK -> travelers.sys_id',
  `documents` longtext DEFAULT NULL COMMENT 'NULL at creation; after loop -> [{"sys_id":"THR-DC-26-00K001","doc_type":"passport_identity"}, ...]',
  `summary` longtext DEFAULT NULL COMMENT 'Combined AI narrative of all docs in this batch',
  `summary_info` text DEFAULT NULL COMMENT 'JSON {"taken_token": total, "time": total} for all calls in this batch',
  `meta_data` longtext DEFAULT NULL COMMENT '{created_by_date, updated_by_date[max 20]}'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `traveler_id` (`traveler_id`);

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;