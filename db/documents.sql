-- phpMyAdmin SQL Dump
-- TravHub v2 : `documents` table
-- One row per uploaded file. Holds the Gemini-extracted structured data,
-- the per-document perspective narrative, and token/time accounting.
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
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL COMMENT 'THR-DC-26-00K001',
  `traveler_id` varchar(16) NOT NULL COMMENT 'FK -> travelers.sys_id',
  `batch_id` varchar(16) NOT NULL COMMENT 'FK -> batches.sys_id',
  `doc_type` varchar(64) DEFAULT NULL COMMENT 'Gemini-classified; one of the 9 folder names (passport_identity, nid, ...)',
  `doc_json` longtext DEFAULT NULL COMMENT 'Full structured data extracted by Gemini',
  `doc_summary` longtext DEFAULT NULL COMMENT 'Perspective-based narrative for this single document',
  `summary_info` text DEFAULT NULL COMMENT 'JSON {"taken_token": int, "time": "2.3s"} for this single Gemini call',
  `total_pages` int(11) NOT NULL DEFAULT 1 COMMENT 'Page count (>1 for multi-page PDFs)',
  `meta_data` longtext DEFAULT NULL COMMENT '{created_by_date, updated_by_date[max 20]}'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `traveler_id` (`traveler_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;