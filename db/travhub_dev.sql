-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 07, 2026 at 02:21 PM
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
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(55) NOT NULL,
  `country_sys_id` varchar(30) NOT NULL,
  `country_name` varchar(100) DEFAULT NULL,
  `city_sys_id` varchar(50) DEFAULT NULL,
  `city_name` varchar(100) DEFAULT NULL,
  `vendor_sys_id` varchar(30) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `search_terms` text DEFAULT NULL,
  `type` enum('tour','transfer','both') NOT NULL DEFAULT 'tour',
  `category` varchar(50) DEFAULT NULL,
  `tags` longtext DEFAULT NULL,
  `short_description` longtext DEFAULT NULL,
  `long_description` longtext DEFAULT NULL,
  `highlights` text DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `duration_hours` decimal(5,1) NOT NULL DEFAULT 0.0,
  `duration_typical` varchar(40) DEFAULT NULL,
  `operating_days` varchar(60) DEFAULT NULL,
  `booking_lead_days` smallint(6) DEFAULT NULL,
  `popularity` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `usage_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `source` enum('manual','imported','ai_added') NOT NULL DEFAULT 'manual',
  `pickup_from_city` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pickup_from_city`)),
  `dropoff_city` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dropoff_city`)),
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `unstructured_data` longtext DEFAULT NULL,
  `package_sys_id` varchar(30) DEFAULT NULL,
  `is_package_override` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_variants`
--

CREATE TABLE `activity_variants` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(70) NOT NULL,
  `activity_sys_id` varchar(55) NOT NULL,
  `country_sys_id` varchar(30) NOT NULL,
  `vendor_sys_id` varchar(30) DEFAULT NULL,
  `variant_name` varchar(160) NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `min_pax` int(10) UNSIGNED DEFAULT NULL,
  `max_pax` int(10) UNSIGNED DEFAULT NULL,
  `age_min` tinyint(3) UNSIGNED DEFAULT NULL,
  `season_from` date DEFAULT NULL,
  `season_to` date DEFAULT NULL,
  `languages` varchar(120) DEFAULT NULL,
  `meeting_point` varchar(200) DEFAULT NULL,
  `cancellation_policy` text DEFAULT NULL,
  `inclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inclusions`)),
  `exclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`exclusions`)),
  `itineraries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`itineraries`)),
  `transport_mode` enum('none','sic','sedan','suv','van','minibus','coach','boat') NOT NULL DEFAULT 'none',
  `meal_breakfast` tinyint(1) NOT NULL DEFAULT 0,
  `meal_lunch` tinyint(1) NOT NULL DEFAULT 0,
  `meal_dinner` tinyint(1) NOT NULL DEFAULT 0,
  `ticket_included` tinyint(1) NOT NULL DEFAULT 1,
  `guide_included` tinyint(1) NOT NULL DEFAULT 0,
  `guide_language` varchar(60) DEFAULT NULL,
  `capacity_min` int(10) UNSIGNED DEFAULT NULL,
  `capacity_max` int(10) UNSIGNED DEFAULT NULL,
  `price_basis` enum('per_pax','per_group') NOT NULL DEFAULT 'per_pax',
  `currency_code` varchar(5) NOT NULL,
  `net_cost` decimal(12,2) NOT NULL,
  `markup_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `markup_value` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sell_price` decimal(12,2) NOT NULL,
  `child_price` decimal(12,2) DEFAULT NULL,
  `usage_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `unstructured_data` longtext DEFAULT NULL,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ac_banking`
--

CREATE TABLE `ac_banking` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `main_type` varchar(255) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `acc_name` varchar(255) DEFAULT NULL,
  `is_transactionable` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `balance` varchar(255) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL COMMENT '{ "created_by_date": "demo_name; 12-10-2025 10:11", "updated_by_date": [ { "1": "demo_name; 12-10-2025 10:11", "2": "demo_name; 12-10-2025 10:11", "3": "demo_name; 12-10-2025 10:11", } ..... not more than 20 ] }'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ac_banking_stmts`
--

CREATE TABLE `ac_banking_stmts` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `ledger_db_id` varchar(255) DEFAULT NULL COMMENT 'account sys_id',
  `name` varchar(255) DEFAULT NULL,
  `transfer_type` varchar(36) DEFAULT NULL COMMENT 'a2a, a2p',
  `transfer_method` varchar(36) DEFAULT NULL COMMENT 'cash, cheque, npsb-rtgs, bftn-eft',
  `related_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=transfer, 1=receive, 2=payment',
  `date` varchar(255) DEFAULT NULL,
  `particular` longtext DEFAULT NULL,
  `withdraw` varchar(255) DEFAULT NULL,
  `deposit` varchar(255) DEFAULT NULL,
  `balance` varchar(255) DEFAULT NULL,
  `reconsilation` varchar(255) DEFAULT NULL,
  `reconsilation_type` int(11) DEFAULT NULL,
  `ref` varchar(255) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL COMMENT '{ "created_by_date": "demo_name; 12-10-2025 10:11", "updated_by_date": [ { "1": "demo_name; 12-10-2025 10:11", "2": "demo_name; 12-10-2025 10:11", "3": "demo_name; 12-10-2025 10:11", } ..... not more than 20 ] }',
  `is_historical` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Opening Balance এর আগের এন্ট্রি (ক্যালকুলেশনের বাইরে)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ac_banking_stmts_backups`
--

CREATE TABLE `ac_banking_stmts_backups` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `ledger_db_id` varchar(255) DEFAULT NULL COMMENT 'account sys_id',
  `name` varchar(255) DEFAULT NULL,
  `transfer_type` varchar(36) DEFAULT NULL COMMENT 'a2a, a2p',
  `transfer_method` varchar(36) DEFAULT NULL COMMENT 'cash, cheque, npsb-rtgs, bftn-eft',
  `related_type` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=transfer, 1=receive, 2=payment',
  `date` varchar(255) DEFAULT NULL,
  `particular` longtext DEFAULT NULL,
  `withdraw` varchar(255) DEFAULT NULL,
  `deposit` varchar(255) DEFAULT NULL,
  `balance` varchar(255) DEFAULT NULL,
  `reconsilation` varchar(255) DEFAULT NULL,
  `reconsilation_type` int(11) DEFAULT NULL,
  `ref` varchar(255) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL COMMENT '{ "created_by_date": "demo_name; 12-10-2025 10:11", "updated_by_date": [ { "1": "demo_name; 12-10-2025 10:11", "2": "demo_name; 12-10-2025 10:11", "3": "demo_name; 12-10-2025 10:11", } ..... not more than 20 ] }',
  `is_historical` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Opening Balance এর আগের এন্ট্রি (ক্যালকুলেশনের বাইরে)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ac_gateway_tokens`
--

CREATE TABLE `ac_gateway_tokens` (
  `id` int(11) NOT NULL,
  `gateway` varchar(20) NOT NULL,
  `token` text NOT NULL,
  `expire_date` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ac_instrument_tracking`
--

CREATE TABLE `ac_instrument_tracking` (
  `id` bigint(20) NOT NULL,
  `uuid` char(36) NOT NULL,
  `sys_id` varchar(50) NOT NULL,
  `instrument_type` varchar(50) NOT NULL,
  `trnx_type` enum('debit','credit') DEFAULT NULL,
  `instrument_no` varchar(100) DEFAULT NULL,
  `account_name` varchar(100) NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `instrument_date` date NOT NULL,
  `payment_to` varchar(32) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `related_type` enum('a2a','a2p','received','payment') NOT NULL,
  `related_from` varchar(255) DEFAULT NULL,
  `related_to` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL,
  `date` date NOT NULL,
  `clearing_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`meta_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `revised_amount` decimal(15,2) DEFAULT NULL,
  `history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`history`)),
  `cleared_at` datetime DEFAULT NULL,
  `cleared_by` varchar(100) DEFAULT NULL,
  `cleared_transaction_id` varchar(50) DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` varchar(100) DEFAULT NULL,
  `finance_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`finance_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `air_tickets`
--

CREATE TABLE `air_tickets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `lead_sys_id` varchar(20) DEFAULT NULL COMMENT 'Source lead (nullable — direct work possible)',
  `work_sys_id` varchar(20) NOT NULL,
  `task_sys_id` varchar(20) DEFAULT NULL COMMENT 'FK → tasks.sys_id (nullable — set only after confirmation)',
  `commands` longtext DEFAULT NULL,
  `at_quotations` longtext DEFAULT NULL CHECK (json_valid(`at_quotations`)),
  `at_bookings` longtext DEFAULT NULL CHECK (json_valid(`at_bookings`)),
  `at_confirmations` longtext DEFAULT NULL CHECK (json_valid(`at_confirmations`)),
  `at_confirmation` longtext DEFAULT NULL CHECK (json_valid(`at_confirmation`)),
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Air Ticket module — 1 row per task, quotations/bookings/confirmation stored as JSON arrays';

-- --------------------------------------------------------

--
-- Table structure for table `air_ticket_calculations`
--

CREATE TABLE `air_ticket_calculations` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `airline` varchar(255) DEFAULT NULL,
  `pax` longtext DEFAULT NULL,
  `raw_gds` longtext DEFAULT NULL,
  `segments_json` longtext DEFAULT NULL,
  `pricing_json` longtext DEFAULT NULL,
  `copy_text` longtext DEFAULT NULL,
  `gross_fare` decimal(12,2) DEFAULT 0.00,
  `base_fare` decimal(12,2) DEFAULT 0.00,
  `taxes` decimal(12,2) DEFAULT 0.00,
  `commission_a` decimal(12,2) DEFAULT 0.00,
  `govt_tax_b` decimal(12,2) DEFAULT 0.00,
  `iata_charge` decimal(12,2) DEFAULT 0.00,
  `net_fare` decimal(12,2) DEFAULT 0.00,
  `payable` decimal(12,2) DEFAULT 0.00,
  `total_payable` decimal(12,2) DEFAULT 0.00,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `air_ticket_quotations`
--

CREATE TABLE `air_ticket_quotations` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `client_sys_id` varchar(16) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `informations` longtext NOT NULL,
  `quotations` longtext DEFAULT NULL,
  `percentage` decimal(10,2) DEFAULT NULL,
  `ve_fixed_price` decimal(12,2) DEFAULT 0.00,
  `form_data` longtext DEFAULT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `sys_id` varchar(50) NOT NULL,
  `employee_sys_id` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `status` enum('present','absent','late','half_day','on_leave','holiday','weekend') DEFAULT 'present',
  `leave_application_sys_id` varchar(50) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `marked_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `classify_tokens`
--

CREATE TABLE `classify_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `token` varchar(64) NOT NULL COMMENT '48-char hex, sent to frontend',
  `traveler_id` varchar(20) NOT NULL COMMENT 'FK → travelers.sys_id',
  `tmp_path` text NOT NULL COMMENT 'Absolute path to staged file in tmp/',
  `original_filename` varchar(255) DEFAULT NULL,
  `file_size` int(10) UNSIGNED DEFAULT NULL,
  `mime_type` varchar(64) DEFAULT NULL,
  `page_count` tinyint(3) UNSIGNED DEFAULT 1,
  `classify_result` longtext DEFAULT NULL COMMENT 'JSON: doc_type, doc_number, suggested_filename_stem, issue_date, expiry_date, summary, confidence, needs_review, language, pages[], doc_data{}',
  `passport_analysis` longtext DEFAULT NULL COMMENT 'JSON: scenario, resolved_status, bio_diff — passport doc এর জন্য',
  `merge_analysis` longtext DEFAULT NULL COMMENT 'JSON: existing_sys_id, duplicate_pages, new_pages_to_add — duplicate doc এর জন্য',
  `expires_at` datetime NOT NULL COMMENT 'created_at + 2 hours',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Temp store for classify results. Cleared after commit or expiry (2h).';

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `type` varchar(128) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` longtext DEFAULT NULL,
  `phone` longtext DEFAULT NULL,
  `address` text DEFAULT NULL,
  `basic_info` longtext DEFAULT NULL COMMENT 'basic info, detailed address, banking info',
  `company_reg_compliance` longtext DEFAULT NULL,
  `contact_n_communication_details` longtext DEFAULT NULL,
  `auth_sign_info` longtext DEFAULT NULL,
  `internal_control_info` longtext DEFAULT NULL,
  `work_name` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `is_vendor` int(11) DEFAULT NULL,
  `vendor_sys_id` varchar(17) DEFAULT NULL COMMENT 'if is_vendor = 1',
  `meta_data` longtext DEFAULT NULL COMMENT '{\r\n    "created_by_date": "demo_name; 12-10-2025 10:11",\r\n    "updated_by_date": [\r\n        {\r\n            "1": "demo_name; 12-10-2025 10:11",\r\n            "2": "demo_name; 12-10-2025 10:11",\r\n            "3": "demo_name; 12-10-2025 10:11",\r\n        }\r\n        .....\r\n\r\n        not more than 20\r\n    ]\r\n}',
  `rep_name` varchar(255) DEFAULT NULL,
  `rep_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_payments`
--

CREATE TABLE `client_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(30) NOT NULL,
  `package_sys_id` varchar(30) NOT NULL,
  `quote_sys_id` varchar(30) DEFAULT NULL,
  `type` enum('deposit','installment','balance','refund') NOT NULL DEFAULT 'deposit',
  `amount` decimal(14,2) NOT NULL,
  `currency_code` varchar(5) NOT NULL,
  `method` enum('bank','card','cash','cheque','online','bkash','nagad') NOT NULL DEFAULT 'bank',
  `paid_on` date NOT NULL,
  `reference` varchar(80) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `components`
--

CREATE TABLE `components` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(30) NOT NULL,
  `vendor_sys_id` varchar(30) DEFAULT NULL,
  `name` varchar(160) NOT NULL,
  `search_terms` text DEFAULT NULL,
  `category` enum('insurance','visa','guide','sim','tip','porterage','meal','ticket','fee','misc') NOT NULL DEFAULT 'misc',
  `description` text DEFAULT NULL,
  `usage_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `source` enum('manual','imported','ai_added') NOT NULL DEFAULT 'manual',
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `component_variants`
--

CREATE TABLE `component_variants` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(45) NOT NULL,
  `component_sys_id` varchar(30) NOT NULL,
  `vendor_sys_id` varchar(30) DEFAULT NULL,
  `variant_name` varchar(160) NOT NULL,
  `unit_basis` enum('per_pax','per_group','per_day','flat') NOT NULL DEFAULT 'per_pax',
  `currency_code` varchar(5) NOT NULL,
  `net_cost` decimal(12,2) NOT NULL,
  `markup_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `markup_value` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sell_price` decimal(12,2) NOT NULL,
  `attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attributes`)),
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `com_works`
--

CREATE TABLE `com_works` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `file_name` text DEFAULT NULL,
  `client_sys_id` varchar(16) DEFAULT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `owned_by` varchar(255) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `com_works_backup`
--

CREATE TABLE `com_works_backup` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `file_name` text DEFAULT NULL,
  `client_sys_id` varchar(16) DEFAULT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `owned_by` varchar(255) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(30) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(3) NOT NULL,
  `currency` varchar(50) NOT NULL,
  `currency_code` varchar(5) NOT NULL,
  `default_rate` decimal(12,4) NOT NULL DEFAULT 1.0000,
  `region` varchar(50) NOT NULL,
  `for_work` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = available for lead, work, task builder selection',
  `for_package` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = available for package builder selection',
  `cities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cities`)),
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(30) NOT NULL,
  `currency_code` varchar(5) NOT NULL,
  `name` varchar(60) NOT NULL,
  `symbol` varchar(8) DEFAULT NULL,
  `decimal_places` tinyint(4) NOT NULL DEFAULT 2,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `directors`
--

CREATE TABLE `directors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
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

-- --------------------------------------------------------

--
-- Table structure for table `director_balances`
--

CREATE TABLE `director_balances` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `sys_id` varchar(16) DEFAULT NULL,
  `director_sys_id` varchar(16) DEFAULT NULL,
  `total_investment` decimal(18,2) NOT NULL DEFAULT 0.00,
  `total_percentage` float DEFAULT 12.5,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `director_dividends`
--

CREATE TABLE `director_dividends` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `sys_id` varchar(16) DEFAULT NULL,
  `total_profit` decimal(18,2) NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `director_transactions`
--

CREATE TABLE `director_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `sys_id` varchar(16) DEFAULT NULL,
  `director_sys_id` varchar(16) DEFAULT NULL,
  `type` enum('invest','withdraw') NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dividend_details`
--

CREATE TABLE `dividend_details` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `sys_id` varchar(16) DEFAULT NULL,
  `dividend_sys_id` varchar(16) DEFAULT NULL COMMENT 'references dividends(id)',
  `director_sys_id` varchar(16) DEFAULT NULL COMMENT 'references directors(id)',
  `director_name` varchar(255) DEFAULT NULL,
  `investment` decimal(18,2) NOT NULL,
  `ownership_percent` decimal(10,4) NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `ini_pass` varchar(36) DEFAULT NULL,
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
  `department_sys_id` varchar(20) DEFAULT NULL,
  `company_related_info` longtext DEFAULT NULL,
  `previous_job_details` longtext DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `emp_path` text DEFAULT NULL,
  `image_name` text DEFAULT NULL,
  `profile_photo` text DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL COMMENT '{\r\n    "created_by_date": "demo_name; 12-10-2025 10:11",\r\n    "updated_by_date": [\r\n        {\r\n            "1": "demo_name; 12-10-2025 10:11",\r\n            "2": "demo_name; 12-10-2025 10:11",\r\n            "3": "demo_name; 12-10-2025 10:11",\r\n        }\r\n        .....\r\n\r\n        not more than 20\r\n    ]\r\n}'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_credentials`
--

CREATE TABLE `employee_credentials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sys_id` varchar(40) NOT NULL,
  `employee_sys_id` varchar(40) NOT NULL,
  `title` varchar(120) NOT NULL,
  `url` varchar(512) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password_enc` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_documents`
--

CREATE TABLE `employee_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sys_id` varchar(40) NOT NULL,
  `employee_sys_id` varchar(40) NOT NULL,
  `title` varchar(200) NOT NULL,
  `file_path` varchar(512) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(80) NOT NULL,
  `file_size` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_by` varchar(40) NOT NULL,
  `uploaded_by_name` varchar(120) DEFAULT NULL,
  `is_hr_issued` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_notes`
--

CREATE TABLE `employee_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sys_id` varchar(40) NOT NULL,
  `employee_sys_id` varchar(40) NOT NULL,
  `note_date` date NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `repeat_yearly` tinyint(1) NOT NULL DEFAULT 0,
  `note_text` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_notifications`
--

CREATE TABLE `employee_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sys_id` varchar(40) NOT NULL,
  `employee_sys_id` varchar(40) NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text DEFAULT NULL,
  `type` varchar(40) NOT NULL DEFAULT 'info',
  `ref_sys_id` varchar(40) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_permissions`
--

CREATE TABLE `employee_permissions` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `employee_sys_id` varchar(20) NOT NULL,
  `permission_key` varchar(50) NOT NULL,
  `granted_by` varchar(100) NOT NULL,
  `granted_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `eps_structures`
--

CREATE TABLE `eps_structures` (
  `id` int(11) NOT NULL,
  `uuid` varchar(100) NOT NULL,
  `sys_id` varchar(50) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `employee_name` varchar(255) DEFAULT NULL,
  `effective_date` date NOT NULL,
  `basic_salary` decimal(15,2) NOT NULL,
  `house_rent` decimal(15,2) DEFAULT 0.00,
  `medical_allowance` decimal(15,2) DEFAULT 0.00,
  `conveyance` decimal(15,2) DEFAULT 0.00,
  `allowance` int(11) DEFAULT NULL,
  `pf_deduction` decimal(15,2) DEFAULT 0.00,
  `tax_deduction` decimal(15,2) DEFAULT 0.00,
  `other_deduction` decimal(15,2) DEFAULT 0.00,
  `gross_salary` decimal(15,2) NOT NULL,
  `total_deductions` decimal(15,2) DEFAULT 0.00,
  `net_salary` decimal(15,2) NOT NULL,
  `status` enum('active','inactive','draft','history') DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_entries`
--

CREATE TABLE `financial_entries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `transaction_group_id` varchar(30) DEFAULT NULL,
  `user_sys_id` varchar(16) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_type` enum('client','vendor','account','employee') DEFAULT NULL,
  `account_head` varchar(30) DEFAULT NULL,
  `vendor_type` int(11) DEFAULT 0 COMMENT '0 for vendor, 1 for own account',
  `task_sys_id` varchar(16) DEFAULT NULL,
  `task_title` varchar(255) DEFAULT NULL,
  `work_sys_id` varchar(16) DEFAULT NULL,
  `work_title` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `purpose` longtext DEFAULT NULL,
  `type` enum('credit','debit') NOT NULL,
  `related_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=refund, 1=sale, 2=purchase, 3=receive, 4=payment, 5 = refund/deiscount, 6 = advance,  7=baksheesh',
  `is_paid` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=not paid, 1=paid',
  `is_discounted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=no discount, 1=discounted',
  `is_invoiced` tinyint(1) NOT NULL DEFAULT 0,
  `is_partial` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=not partial, 1=partial payment',
  `amount` decimal(12,2) NOT NULL,
  `ref` longtext DEFAULT NULL,
  `meta_data` longtext NOT NULL COMMENT '{ "created_by_date": "demo_name; 12-10-2025 10:11", "updated_by_date": [ { "1": "demo_name; 12-10-2025 10:11", "2": "demo_name; 12-10-2025 10:11", "3": "demo_name; 12-10-2025 10:11", } ..... not more than 20 ] }',
  `edit_history` longtext DEFAULT NULL,
  `qty_rate` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '{"qty": 2, "rate": 18000}' CHECK (json_valid(`qty_rate`)),
  `files_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`files_json`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_entries_backup`
--

CREATE TABLE `financial_entries_backup` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `user_sys_id` varchar(16) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_type` enum('client','vendor','account','employee') DEFAULT NULL,
  `vendor_type` int(11) DEFAULT 0 COMMENT '0 for vendor, 1 for own account',
  `task_sys_id` varchar(16) DEFAULT NULL,
  `task_title` varchar(255) DEFAULT NULL,
  `work_sys_id` varchar(16) DEFAULT NULL,
  `work_title` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `purpose` longtext DEFAULT NULL,
  `type` enum('credit','debit') NOT NULL,
  `related_type` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0=refund, 1=sale, 2=purchase, 3=receive, 4=payment, 5 = refund/deiscount, 6 = advance,  7=baksheesh',
  `is_paid` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=not paid, 1=paid',
  `is_discounted` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=no discount, 1=discounted',
  `is_invoiced` tinyint(1) NOT NULL DEFAULT 0,
  `is_partial` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=not partial, 1=partial payment',
  `amount` decimal(12,2) NOT NULL,
  `ref` longtext DEFAULT NULL,
  `meta_data` longtext NOT NULL COMMENT '{ "created_by_date": "demo_name; 12-10-2025 10:11", "updated_by_date": [ { "1": "demo_name; 12-10-2025 10:11", "2": "demo_name; 12-10-2025 10:11", "3": "demo_name; 12-10-2025 10:11", } ..... not more than 20 ] }',
  `qty_rate` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT '{"qty": 2, "rate": 18000}' CHECK (json_valid(`qty_rate`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fx_rates`
--

CREATE TABLE `fx_rates` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(30) NOT NULL,
  `currency_sys_id` varchar(30) NOT NULL,
  `currency_code` varchar(5) NOT NULL,
  `rate` decimal(18,8) NOT NULL,
  `buffer_pct` decimal(6,4) NOT NULL DEFAULT 0.0000,
  `effective_date` date NOT NULL,
  `source` enum('manual','api') NOT NULL DEFAULT 'manual',
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gateway_payments`
--

CREATE TABLE `gateway_payments` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `invoice_sys_id` varchar(20) NOT NULL,
  `client_sys_id` varchar(20) NOT NULL,
  `gateway` varchar(20) NOT NULL DEFAULT 'eps',
  `merchant_transaction_id` varchar(50) NOT NULL,
  `eps_transaction_id` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'initiated',
  `gateway_response` longtext DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `settled_at` datetime DEFAULT NULL,
  `settled_by` varchar(100) DEFAULT NULL,
  `settlement_account_id` varchar(20) DEFAULT NULL,
  `financial_entries_group_id` varchar(30) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(30) NOT NULL,
  `country_sys_id` varchar(30) NOT NULL,
  `country_name` varchar(100) DEFAULT NULL,
  `city_sys_id` varchar(50) DEFAULT NULL,
  `city_name` varchar(100) DEFAULT NULL,
  `vendor_sys_id` varchar(30) DEFAULT NULL,
  `name` varchar(160) NOT NULL,
  `search_terms` text DEFAULT NULL,
  `star_rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`amenities`)),
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `usage_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `source` enum('manual','imported','ai_added') NOT NULL DEFAULT 'manual',
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `for_umrah` int(11) DEFAULT NULL COMMENT '0/null for not umrah, 1 for umrah',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotel_bookings`
--

CREATE TABLE `hotel_bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `booking_ref` varchar(100) NOT NULL,
  `hotel_details` longtext DEFAULT NULL COMMENT 'JSON: hotel_name, hotel_phone_no, hotel_email, hotel_address, hotel_city, hotel_zip_code',
  `guest_details` longtext DEFAULT NULL COMMENT 'JSON: first_name, last_name, traveler_sys_id, total_pax {adult, child, infant}',
  `traveler_sys_id` varchar(100) DEFAULT NULL,
  `traveler_name` varchar(150) DEFAULT NULL,
  `staying_details` longtext DEFAULT NULL COMMENT 'JSON: check_in, check_out, room_type, meal_type, room_info, cancellation_policy',
  `pcn` varchar(100) DEFAULT NULL,
  `hcn` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL COMMENT '{ "created_by_date": "demo_name; 12-10-2025 10:11", "updated_by_date": [ { "1": "demo_name; 12-10-2025 10:11", "2": "demo_name; 12-10-2025 10:11", "3": "demo_name; 12-10-2025 10:11", } ..... not more than 20 ] }'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotel_quotations`
--

CREATE TABLE `hotel_quotations` (
  `id` int(11) NOT NULL,
  `uuid` varchar(100) NOT NULL,
  `sys_id` varchar(100) NOT NULL,
  `client_sys_id` varchar(16) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `informations` longtext NOT NULL,
  `quotations` longtext DEFAULT NULL,
  `percentage` decimal(10,2) DEFAULT NULL,
  `form_data` longtext DEFAULT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotel_services`
--

CREATE TABLE `hotel_services` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(32) NOT NULL,
  `work_sys_id` varchar(32) DEFAULT NULL,
  `task_sys_id` varchar(32) DEFAULT NULL,
  `lead_sys_id` varchar(32) DEFAULT NULL,
  `ht_quotations` longtext DEFAULT NULL COMMENT 'JSON array of hotel quotations',
  `ht_bookings` longtext DEFAULT NULL COMMENT 'JSON array of hotel bookings',
  `ht_confirmations` longtext DEFAULT NULL COMMENT 'JSON array of confirmations',
  `meta_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `investors`
--

CREATE TABLE `investors` (
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
  `date_of_investing` varchar(32) DEFAULT NULL,
  `percentage` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `ivs_path` text DEFAULT NULL,
  `image_name` text DEFAULT NULL,
  `profile_photo` text DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL COMMENT '{\r\n    "created_by_date": "demo_name; 12-10-2025 10:11",\r\n    "updated_by_date": [\r\n        {\r\n            "1": "demo_name; 12-10-2025 10:11",\r\n            "2": "demo_name; 12-10-2025 10:11",\r\n            "3": "demo_name; 12-10-2025 10:11",\r\n        }\r\n        .....\r\n\r\n        not more than 20\r\n    ]\r\n}'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sys_id` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `date` date NOT NULL,
  `client_sys_id` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `client_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `client_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount_in_words` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `work_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `financial_entry_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`financial_entry_ids`)),
  `vendor_payment_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` int(11) DEFAULT 0 COMMENT '0 for pending, 1 for paid, 2 for partial, 4 for overdue',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `public_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices_backup`
--

CREATE TABLE `invoices_backup` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sys_id` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `date` date NOT NULL,
  `client_sys_id` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `client_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `client_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount_in_words` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `work_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `financial_entry_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`financial_entry_ids`)),
  `vendor_payment_methods` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` int(11) DEFAULT 0 COMMENT '0 for pending, 1 for paid, 2 for partial, 4 for overdue',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `client_info` longtext DEFAULT NULL CHECK (json_valid(`client_info`)),
  `service_type` longtext DEFAULT NULL CHECK (json_valid(`service_type`)),
  `service_count` int(11) DEFAULT 0,
  `service_data` longtext DEFAULT NULL CHECK (json_valid(`service_data`)),
  `instruction` longtext DEFAULT NULL CHECK (json_valid(`instruction`)),
  `special_instruction` longtext DEFAULT NULL CHECK (json_valid(`special_instruction`)),
  `lead_info` longtext DEFAULT NULL CHECK (json_valid(`lead_info`)),
  `lead_status` varchar(30) DEFAULT 'pending',
  `ai_prompt` longtext DEFAULT NULL,
  `voice_transcript` longtext DEFAULT NULL,
  `assigned_to` varchar(255) DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` varchar(200) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_applications`
--

CREATE TABLE `leave_applications` (
  `sys_id` varchar(50) NOT NULL,
  `employee_sys_id` varchar(50) NOT NULL,
  `leave_type_sys_id` varchar(50) NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `total_days` int(11) NOT NULL DEFAULT 1,
  `bridged_days` int(11) DEFAULT 0,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `reviewed_by` varchar(50) DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--

CREATE TABLE `leave_balances` (
  `id` int(11) NOT NULL,
  `employee_sys_id` varchar(50) NOT NULL,
  `leave_type_sys_id` varchar(50) NOT NULL,
  `year` year(4) NOT NULL,
  `allocated` int(11) DEFAULT 0,
  `used` int(11) DEFAULT 0,
  `pending` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `sys_id` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `max_days_per_year` int(11) DEFAULT 10,
  `carry_forward` tinyint(1) DEFAULT 0,
  `is_paid` tinyint(1) DEFAULT 1,
  `color` varchar(7) DEFAULT '#3b82f6',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `lender_type` varchar(20) NOT NULL,
  `lender_id` varchar(20) NOT NULL,
  `lender_name` varchar(150) NOT NULL,
  `borrower_type` varchar(20) NOT NULL,
  `borrower_id` varchar(20) NOT NULL,
  `borrower_name` varchar(150) NOT NULL,
  `principal_amount` decimal(15,2) NOT NULL,
  `interest_rate` decimal(6,3) DEFAULT NULL,
  `interest_method` varchar(20) DEFAULT NULL,
  `repayment_type` varchar(20) NOT NULL,
  `emi_calculation_mode` varchar(10) DEFAULT NULL,
  `tenure_months` int(11) DEFAULT NULL,
  `emi_amount` decimal(15,2) DEFAULT NULL,
  `disbursement_date` datetime DEFAULT NULL,
  `disbursement_account_id` varchar(20) DEFAULT NULL,
  `disbursed` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `outstanding_balance` decimal(15,2) NOT NULL,
  `meta_data` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_installments`
--

CREATE TABLE `loan_installments` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `loan_sys_id` varchar(20) NOT NULL,
  `installment_no` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `principal_component` decimal(15,2) DEFAULT NULL,
  `interest_component` decimal(15,2) DEFAULT NULL,
  `total_due` decimal(15,2) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `paid_date` datetime DEFAULT NULL,
  `paid_amount` decimal(15,2) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_repayments`
--

CREATE TABLE `loan_repayments` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `loan_sys_id` varchar(20) NOT NULL,
  `installment_sys_id` varchar(20) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `date` datetime NOT NULL,
  `account_id` varchar(20) DEFAULT NULL,
  `payment_method` varchar(20) DEFAULT NULL,
  `instrument_no` varchar(50) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(11) NOT NULL,
  `role` int(2) DEFAULT NULL COMMENT '0 for admin, 1 for others\r\n',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `user_id` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `master_visa_services`
--

CREATE TABLE `master_visa_services` (
  `id` int(11) NOT NULL,
  `uuid` varchar(100) NOT NULL,
  `sys_id` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `country_sys_id` varchar(100) NOT NULL,
  `country_name` varchar(150) NOT NULL DEFAULT '',
  `visa_type_sys_id` varchar(100) NOT NULL,
  `visa_category_sys_id` varchar(100) NOT NULL,
  `duration_days` int(11) NOT NULL DEFAULT 0,
  `duration_label` varchar(100) NOT NULL DEFAULT '',
  `required_documents` longtext DEFAULT NULL CHECK (json_valid(`required_documents`)),
  `description` longtext DEFAULT NULL CHECK (json_valid(`description`)),
  `b2c_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `purchase_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) NOT NULL DEFAULT 'BDT',
  `file_size_limit_kb` int(11) NOT NULL DEFAULT 300,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `recipient_type` enum('department','user') NOT NULL DEFAULT 'department',
  `department_sys_id` varchar(20) DEFAULT NULL,
  `user_sys_id` varchar(20) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `body` text DEFAULT NULL,
  `work_sys_id` varchar(20) DEFAULT NULL,
  `task_sys_id` varchar(20) DEFAULT NULL,
  `service_work_sys_id` varchar(20) DEFAULT NULL,
  `link` varchar(512) DEFAULT NULL COMMENT 'Relative URL to navigate to when notification is clicked',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` varchar(30) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `office_calendar`
--

CREATE TABLE `office_calendar` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `type` enum('holiday','office_closed','working_day') DEFAULT 'holiday',
  `title` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `office_calendar_events`
--

CREATE TABLE `office_calendar_events` (
  `sys_id` varchar(50) NOT NULL,
  `event_date` date NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `visibility` enum('public','private') NOT NULL DEFAULT 'public',
  `visible_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of employee sys_ids when visibility=private' CHECK (json_valid(`visible_to`)),
  `created_by` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `office_holidays`
--

CREATE TABLE `office_holidays` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sys_id` varchar(40) NOT NULL,
  `holiday_date` date NOT NULL,
  `title` varchar(200) NOT NULL,
  `type` varchar(40) NOT NULL DEFAULT 'public_holiday',
  `year` smallint(5) UNSIGNED NOT NULL,
  `created_by` varchar(40) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `old_tasks`
--

CREATE TABLE `old_tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(17) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `info_file_name` text DEFAULT NULL,
  `info_details` text DEFAULT NULL,
  `work_sys_id` varchar(16) DEFAULT NULL,
  `work_title` varchar(255) DEFAULT NULL,
  `hotel_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hotel_info`)),
  `air_ticket_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`air_ticket_info`)),
  `all_file_name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`all_file_name`)),
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `performed_by` varchar(255) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL COMMENT '{ "created_by_date": "demo_name; 12-10-2025 10:11", "updated_by_date": [ { "1": "demo_name; 12-10-2025 10:11", "2": "demo_name; 12-10-2025 10:11", "3": "demo_name; 12-10-2025 10:11", } ..... not more than 20 ] }'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `old_tasks_backup`
--

CREATE TABLE `old_tasks_backup` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(17) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `info_file_name` text DEFAULT NULL,
  `info_details` text DEFAULT NULL,
  `work_sys_id` varchar(16) DEFAULT NULL,
  `work_title` varchar(255) DEFAULT NULL,
  `hotel_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hotel_info`)),
  `air_ticket_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`air_ticket_info`)),
  `all_file_name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`all_file_name`)),
  `title` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `performed_by` varchar(255) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL COMMENT '{ "created_by_date": "demo_name; 12-10-2025 10:11", "updated_by_date": [ { "1": "demo_name; 12-10-2025 10:11", "2": "demo_name; 12-10-2025 10:11", "3": "demo_name; 12-10-2025 10:11", } ..... not more than 20 ] }'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(30) NOT NULL,
  `booking_ref` varchar(30) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `full_description` text DEFAULT NULL,
  `package_type` enum('group','fit','corporate','factory_tour','custom','umrah') NOT NULL DEFAULT 'custom',
  `client_sys_id` varchar(30) DEFAULT NULL,
  `client_name` varchar(160) DEFAULT NULL,
  `adults` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `children` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `infants` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `duration` smallint(5) UNSIGNED DEFAULT NULL,
  `sell_currency_code` varchar(5) NOT NULL DEFAULT 'BDT',
  `sell_currency_title` varchar(60) DEFAULT NULL,
  `sell_currency_symbol` varchar(8) DEFAULT NULL,
  `overall_price` decimal(14,2) DEFAULT NULL,
  `air_ticket_details` text DEFAULT NULL,
  `countries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`countries`)),
  `cities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cities`)),
  `nights_per_city` longtext DEFAULT NULL,
  `hotels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hotels`)),
  `pack_itenaries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pack_itenaries`)),
  `ai_source_text` longtext DEFAULT NULL,
  `pack_price` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pack_price`)),
  `pack_inclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pack_inclusions`)),
  `pack_exclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pack_exclusions`)),
  `pack_components` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pack_components`)),
  `pricing_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pricing_config`)),
  `ai_inclusions_draft` longtext DEFAULT NULL,
  `no_of_pax` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`no_of_pax`)),
  `image` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `progress_step` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `completion_status` enum('draft','saved','quoted','confirmed','in_progress','completed','cancelled') NOT NULL DEFAULT 'draft',
  `active_quote_sys_id` varchar(30) DEFAULT NULL,
  `rating` tinyint(3) UNSIGNED DEFAULT 0,
  `description` text DEFAULT NULL,
  `highlights` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`highlights`)),
  `assigned_to_sys_id` varchar(30) DEFAULT NULL,
  `version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_quotes`
--

CREATE TABLE `package_quotes` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(30) NOT NULL,
  `package_sys_id` varchar(30) NOT NULL,
  `version` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `quote_currency_code` varchar(5) NOT NULL,
  `quote_currency_title` varchar(60) DEFAULT NULL,
  `fx_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`fx_snapshot`)),
  `subtotal_cost` decimal(14,2) NOT NULL DEFAULT 0.00,
  `subtotal_sell` decimal(14,2) NOT NULL DEFAULT 0.00,
  `markup_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `markup_value` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `markup_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `service_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(14,2) NOT NULL DEFAULT 0.00,
  `per_person` decimal(14,2) NOT NULL DEFAULT 0.00,
  `rounding_rule` varchar(40) DEFAULT NULL,
  `margin_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `margin_pct` decimal(6,2) DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `quote_status` enum('draft','sent','accepted','expired','superseded') NOT NULL DEFAULT 'draft',
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_services`
--

CREATE TABLE `package_services` (
  `id` int(11) NOT NULL,
  `uuid` varchar(100) NOT NULL,
  `sys_id` varchar(100) NOT NULL,
  `work_sys_id` varchar(100) NOT NULL,
  `pk_quotations` longtext DEFAULT NULL CHECK (json_valid(`pk_quotations`)),
  `pk_confirmation` longtext DEFAULT NULL CHECK (json_valid(`pk_confirmation`)),
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_travelers`
--

CREATE TABLE `package_travelers` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(55) NOT NULL,
  `package_sys_id` varchar(30) NOT NULL,
  `traveler_sys_id` varchar(30) NOT NULL,
  `lead_pax` tinyint(1) NOT NULL DEFAULT 0,
  `pax_type` enum('adult','child','infant') NOT NULL DEFAULT 'adult',
  `room_assignment` varchar(40) DEFAULT NULL,
  `segment_coverage` varchar(120) DEFAULT NULL,
  `visa_status` enum('not_required','pending','applied','approved','refused') NOT NULL DEFAULT 'not_required',
  `ticket_ref` varchar(40) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_finals`
--

CREATE TABLE `payroll_finals` (
  `id` int(11) NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(16) NOT NULL,
  `eps_id` varchar(16) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `employee_name` varchar(255) DEFAULT NULL,
  `eps_salary` longtext DEFAULT NULL,
  `bonus` decimal(15,2) NOT NULL DEFAULT 0.00,
  `overtime` decimal(15,2) NOT NULL DEFAULT 0.00,
  `allowances` int(11) NOT NULL,
  `deduction` longtext DEFAULT NULL,
  `net_payable_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `month` varchar(20) NOT NULL,
  `payment_type` enum('salary','bonus','overtime','allowance','adjustment','custom') DEFAULT 'salary',
  `payment_components` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_components`)),
  `status` enum('prepared','collected','authorized','cancelled') DEFAULT 'prepared',
  `prepared_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`prepared_info`)),
  `collected_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`collected_info`)),
  `authorized_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`authorized_info`)),
  `from_account` varchar(16) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL,
  `disbursed_info` text DEFAULT NULL,
  `verified_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `status` enum('pending','approved','confirm') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `ref` longtext DEFAULT NULL,
  `meta_data` longtext NOT NULL COMMENT '{ "created_by_date": "demo_name; 12-10-2025 10:11", "updated_by_date": [ { "1": "demo_name; 12-10-2025 10:11", "2": "demo_name; 12-10-2025 10:11", "3": "demo_name; 12-10-2025 10:11", } ..... not more than 20 ] }'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portal_links`
--

CREATE TABLE `portal_links` (
  `id` int(11) NOT NULL,
  `uuid` varchar(64) NOT NULL,
  `sys_id` varchar(32) NOT NULL,
  `portal_name` varchar(255) NOT NULL,
  `portal_url` varchar(500) DEFAULT NULL,
  `portal_type` enum('air_ticket','hotel','package','visa','umrah','transport','other') NOT NULL DEFAULT 'other',
  `credentials` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`credentials`)),
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profit_loss`
--

CREATE TABLE `profit_loss` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `sys_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `type` enum('profit','loss') NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `note` varchar(500) DEFAULT NULL,
  `date` date NOT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quote_lines`
--

CREATE TABLE `quote_lines` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(50) NOT NULL,
  `quote_sys_id` varchar(30) NOT NULL,
  `item_sys_id` varchar(60) DEFAULT NULL,
  `description` varchar(200) NOT NULL,
  `qty` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `source_currency_code` varchar(5) NOT NULL,
  `source_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `source_sell` decimal(12,2) NOT NULL DEFAULT 0.00,
  `fx_rate` decimal(18,8) NOT NULL DEFAULT 1.00000000,
  `cost_quote_ccy` decimal(14,2) NOT NULL DEFAULT 0.00,
  `sell_quote_ccy` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_rates`
--

CREATE TABLE `room_rates` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(70) NOT NULL,
  `room_type_sys_id` varchar(50) NOT NULL,
  `hotel_sys_id` varchar(30) NOT NULL,
  `vendor_sys_id` varchar(30) DEFAULT NULL,
  `meal_plan` enum('room_only','bb','hb','fb','ai') NOT NULL DEFAULT 'bb',
  `occupancy_basis` enum('per_room','single','double','triple','extra_bed') NOT NULL DEFAULT 'per_room',
  `valid_from` date NOT NULL,
  `valid_to` date NOT NULL,
  `currency_code` varchar(5) NOT NULL,
  `net_cost` decimal(12,2) NOT NULL,
  `markup_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `markup_value` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sell_price` decimal(12,2) NOT NULL,
  `tax_basis` enum('inclusive','plus_plus') NOT NULL DEFAULT 'plus_plus',
  `tax_service_pct` decimal(5,2) DEFAULT NULL,
  `tax_vat_pct` decimal(5,2) DEFAULT NULL,
  `cancellation_policy` text DEFAULT NULL,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(50) NOT NULL,
  `hotel_sys_id` varchar(30) NOT NULL,
  `hotel_name` varchar(160) DEFAULT NULL,
  `room_name` varchar(120) NOT NULL,
  `description` varchar(400) DEFAULT NULL,
  `max_adults` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  `max_children` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `standard_occupancy` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  `bed_config` varchar(80) DEFAULT NULL,
  `size_sqm` smallint(5) UNSIGNED DEFAULT NULL,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(100) DEFAULT 'fa-circle',
  `color` varchar(30) DEFAULT 'indigo',
  `description` text DEFAULT NULL,
  `fields` longtext DEFAULT NULL CHECK (json_valid(`fields`)),
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_works`
--

CREATE TABLE `service_works` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `work_sys_id` varchar(20) NOT NULL,
  `department_sys_id` varchar(20) DEFAULT NULL,
  `assigned_to` varchar(20) DEFAULT NULL,
  `assigned_to_name` varchar(150) DEFAULT NULL,
  `service_slug` varchar(100) DEFAULT NULL,
  `service_name` varchar(100) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'open',
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sm_posts`
--

CREATE TABLE `sm_posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `platform` varchar(30) NOT NULL,
  `tone` varchar(30) DEFAULT NULL,
  `language` varchar(20) DEFAULT NULL,
  `content_size` varchar(20) DEFAULT NULL,
  `word_limit` int(11) DEFAULT 150,
  `temperature` decimal(3,1) DEFAULT 0.7,
  `raw_input` text DEFAULT NULL,
  `post_text` longtext DEFAULT NULL,
  `hook` text DEFAULT NULL,
  `cta` text DEFAULT NULL,
  `hashtags` longtext DEFAULT NULL CHECK (json_valid(`hashtags`)),
  `keywords` longtext DEFAULT NULL CHECK (json_valid(`keywords`)),
  `tips` longtext DEFAULT NULL CHECK (json_valid(`tips`)),
  `has_image` tinyint(1) DEFAULT 0,
  `image_url` longtext DEFAULT NULL,
  `image_prompt` text DEFAULT NULL,
  `image_ratio` varchar(10) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(30) NOT NULL,
  `package_sys_id` varchar(30) NOT NULL,
  `vendor_sys_id` varchar(30) NOT NULL,
  `vendor_name` varchar(150) DEFAULT NULL,
  `item_sys_id` varchar(60) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `currency_code` varchar(5) NOT NULL,
  `fx_rate_used` decimal(18,8) DEFAULT NULL,
  `amount_bdt` decimal(14,2) DEFAULT NULL,
  `method` enum('bank','card','cash','cheque','online','bkash','nagad') NOT NULL DEFAULT 'bank',
  `paid_on` date NOT NULL,
  `reference` varchar(80) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `service_work_sys_id` varchar(20) NOT NULL,
  `service_slug` varchar(50) DEFAULT NULL COMMENT 'air_ticket | visa | hotel | tour_package | umrah | transport',
  `confirmation_sys_id` varchar(20) DEFAULT NULL COMMENT 'FK → at_confirmations.sys_id — set when task is auto-created on confirmation confirmed',
  `is_merged` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = merged into another task, hidden from list',
  `merged_into` varchar(20) DEFAULT NULL COMMENT 'FK → tasks.sys_id of the merged task',
  `work_sys_id` varchar(20) NOT NULL,
  `client_sys_id` varchar(20) DEFAULT NULL,
  `traveler_id` longtext DEFAULT NULL CHECK (json_valid(`traveler_id`)),
  `workname` varchar(200) DEFAULT NULL,
  `client_name` varchar(200) DEFAULT NULL,
  `performed_by` longtext DEFAULT NULL CHECK (json_valid(`performed_by`)),
  `assigned_to` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'open',
  `overall_status` varchar(30) DEFAULT 'pending',
  `holding_on` longtext DEFAULT NULL,
  `plans` longtext DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `instruction` longtext DEFAULT NULL,
  `special_ins` longtext DEFAULT NULL CHECK (json_valid(`special_ins`)),
  `quotation` longtext DEFAULT NULL CHECK (json_valid(`quotation`)),
  `booking` longtext DEFAULT NULL CHECK (json_valid(`booking`)),
  `confirmation` longtext DEFAULT NULL CHECK (json_valid(`confirmation`)),
  `total_quotation` decimal(12,2) DEFAULT 0.00,
  `total_booking` decimal(12,2) DEFAULT 0.00,
  `total_confirmation` decimal(12,2) DEFAULT 0.00,
  `confirmed_amount` decimal(12,2) DEFAULT 0.00,
  `files_json` longtext DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `task_notes`
--

CREATE TABLE `task_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `task_sys_id` varchar(20) DEFAULT NULL COMMENT 'FK → tasks.sys_id (nullable for work-level notes)',
  `work_sys_id` varchar(20) NOT NULL,
  `board_type` varchar(10) NOT NULL DEFAULT 'task' COMMENT 'task | work',
  `service_slug` varchar(30) DEFAULT NULL COMMENT 'air_ticket | hotel | visa etc (work notes only)',
  `board_name` varchar(20) DEFAULT NULL COMMENT 'mindboard | noteboard (work notes only)',
  `note_type` enum('text','image','audio','video','file','pdf_images') NOT NULL DEFAULT 'text',
  `content` longtext DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `files_json` longtext DEFAULT NULL,
  `pages_json` longtext DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_by` varchar(100) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_services`
--

CREATE TABLE `transport_services` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(60) NOT NULL,
  `country_sys_id` varchar(30) NOT NULL,
  `country_name` varchar(100) DEFAULT NULL,
  `vendor_sys_id` varchar(30) DEFAULT NULL,
  `name` varchar(160) NOT NULL,
  `search_terms` text DEFAULT NULL,
  `type` enum('airport_transfer','intercity','ferry','shuttle','car_hire','other') NOT NULL DEFAULT 'airport_transfer',
  `from_city_sys_id` varchar(50) DEFAULT NULL,
  `from_city_name` varchar(100) DEFAULT NULL,
  `to_city_sys_id` varchar(50) DEFAULT NULL,
  `to_city_name` varchar(100) DEFAULT NULL,
  `direction` enum('one_way','return') NOT NULL DEFAULT 'one_way',
  `description` text DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `duration_typical` varchar(40) DEFAULT NULL,
  `usage_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `source` enum('manual','imported','ai_added') NOT NULL DEFAULT 'manual',
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_variants`
--

CREATE TABLE `transport_variants` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(75) NOT NULL,
  `service_sys_id` varchar(60) NOT NULL,
  `country_sys_id` varchar(30) NOT NULL,
  `vendor_sys_id` varchar(30) DEFAULT NULL,
  `variant_name` varchar(160) NOT NULL,
  `vehicle_class` enum('sedan','suv','van','minibus','coach','boat','train','other') NOT NULL DEFAULT 'van',
  `capacity_max` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `luggage_capacity` varchar(40) DEFAULT NULL,
  `price_basis` enum('per_vehicle','per_person','per_day','per_km','per_hour') NOT NULL DEFAULT 'per_vehicle',
  `transfer_type` enum('sic','private') NOT NULL DEFAULT 'private',
  `meet_and_greet` tinyint(1) NOT NULL DEFAULT 0,
  `seat_count` tinyint(3) UNSIGNED DEFAULT NULL,
  `max_luggage_kg` smallint(5) UNSIGNED DEFAULT NULL,
  `max_luggage_bags` tinyint(3) UNSIGNED DEFAULT NULL,
  `currency_code` varchar(5) NOT NULL,
  `net_cost` decimal(12,2) NOT NULL,
  `markup_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `markup_value` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `sell_price` decimal(12,2) NOT NULL,
  `child_price` decimal(12,2) DEFAULT NULL,
  `extra_charges` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`extra_charges`)),
  `usage_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `passport_no` varchar(16) DEFAULT NULL,
  `nid_no` varchar(17) DEFAULT NULL,
  `date_of_birth` varchar(36) DEFAULT NULL,
  `email` longtext DEFAULT NULL,
  `phone` longtext DEFAULT NULL,
  `address` text DEFAULT NULL,
  `basic_info` longtext DEFAULT NULL COMMENT 'application form information',
  `nid_info` longtext DEFAULT NULL COMMENT 'nid extracted data in JSON format',
  `passport_info` longtext DEFAULT NULL COMMENT 'passport extracted data in JSON format',
  `travel_history` longtext DEFAULT NULL COMMENT 'single history structure->\r\n{\r\n  "country": "country_name",\r\n  "entry": {\r\n    "date": "entry_date",\r\n    "city": "entry_city",\r\n    "purpose": "purpose"\r\n  },\r\n  "exit": {\r\n    "date": "exit_date",\r\n    "city": "exit_city",\r\n    "purpose": "purpose"\r\n  },\r\n  "total_stay": 10\r\n}',
  `summary` longtext DEFAULT NULL COMMENT 'AI-generated living profile narrative (re-merged each batch)',
  `history_summary` longtext DEFAULT NULL COMMENT 'JSON array of previous summary snapshots-> [{"text":"...","date":"21-05-2026 10:30"}, ...]',
  `summary_info` text DEFAULT NULL COMMENT 'JSON {"taken_token": int, "time": "2.3s"} for the last traveler-merge Gemini call',
  `personal_info` longtext DEFAULT NULL COMMENT 'JSON-> DOB, gender, blood_group, religion, marital_status, etc.',
  `family_info` longtext DEFAULT NULL COMMENT 'JSON-> father, mother, spouse, children details',
  `employment_info` longtext DEFAULT NULL COMMENT 'JSON-> current employer, designation, salary, employment_type',
  `educational_info` longtext DEFAULT NULL COMMENT 'JSON-> degrees, institutions, passing_years',
  `work_info` longtext DEFAULT NULL COMMENT 'JSON-> work history / experience records',
  `others_info` longtext DEFAULT NULL COMMENT 'JSON-> miscellaneous extra fields',
  `credentials` longtext DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `smb_path` text DEFAULT NULL,
  `server_path` text DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL COMMENT '{\r\n    "created_by_date": "demo_name; 12-10-2025 10:11",\r\n    "updated_by_date": [\r\n        {\r\n            "1": "demo_name; 12-10-2025 10:11",\r\n            "2": "demo_name; 12-10-2025 10:11",\r\n            "3": "demo_name; 12-10-2025 10:11",\r\n        }\r\n        .....\r\n\r\n        not more than 20\r\n    ]\r\n}'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `traveler_documents`
--

CREATE TABLE `traveler_documents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL COMMENT 'THR-DC-YY-XXXXX',
  `traveler_id` varchar(20) NOT NULL COMMENT 'FK → travelers.sys_id',
  `batch_id` varchar(20) DEFAULT NULL COMMENT 'FK → batches.sys_id',
  `doc_type` varchar(64) NOT NULL,
  `doc_subtype` varchar(64) DEFAULT NULL COMMENT 'e.g. bio_page, visa_page, renewal',
  `doc_number` varchar(128) DEFAULT NULL COMMENT 'Passport no / NID no / Visa no etc.',
  `country` varchar(64) DEFAULT NULL COMMENT 'Issuing or destination country',
  `language` varchar(32) DEFAULT NULL,
  `passport_status` enum('current','previous','historical') DEFAULT NULL COMMENT 'Only for doc_type = passport',
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `validity_from` date DEFAULT NULL COMMENT 'For visa — tracks_validity = 1',
  `validity_to` date DEFAULT NULL COMMENT 'For visa — tracks_validity = 1',
  `summary` text DEFAULT NULL COMMENT 'Gemini 2-4 sentence narrative',
  `doc_data` longtext DEFAULT NULL COMMENT 'JSON: structured fields (name, dob, mrz etc.) — has_structured_schema=1 এ use হয়',
  `key_fields` text DEFAULT NULL COMMENT 'JSON: flattened shortlist for quick display — has_structured_schema=0 এ use হয়',
  `confidence` decimal(4,3) DEFAULT NULL COMMENT '0.000 to 1.000',
  `needs_review` tinyint(1) NOT NULL DEFAULT 0,
  `classification_mode` enum('auto','manual','overridden') DEFAULT 'auto' COMMENT 'auto = Gemini decided, manual = user picked, overridden = user changed Gemini result',
  `original_filename` varchar(255) DEFAULT NULL,
  `suggested_filename_stem` varchar(255) DEFAULT NULL COMMENT 'No extension, no page suffix',
  `smb_folder` varchar(64) DEFAULT NULL COMMENT 'Copied from doc_type_registry.smb_folder',
  `server_path` text DEFAULT NULL COMMENT 'Absolute local path to folder',
  `file_size` int(10) UNSIGNED DEFAULT NULL COMMENT 'Bytes',
  `mime_type` varchar(64) DEFAULT NULL,
  `page_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `pages` longtext DEFAULT NULL COMMENT 'JSON: [{page_no, filename, page_type, country}]',
  `verification_status` enum('unverified','verified','rejected') NOT NULL DEFAULT 'unverified',
  `verified_by` varchar(64) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = main/current document of this type for this traveler',
  `status` enum('active','expired','archived','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext DEFAULT NULL COMMENT 'JSON: {created_by_date, updated_by_date[]}',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='One row per committed document. Populated by commit-documents.php.';

-- --------------------------------------------------------

--
-- Table structure for table `traveler_groups`
--

CREATE TABLE `traveler_groups` (
  `id` int(11) NOT NULL,
  `uuid` varchar(64) NOT NULL,
  `sys_id` varchar(32) NOT NULL,
  `group_name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT 'umrah',
  `segments` longtext DEFAULT NULL,
  `hotels` longtext DEFAULT NULL,
  `itinerary` longtext DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `linked_work_id` varchar(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `traveler_group_members`
--

CREATE TABLE `traveler_group_members` (
  `id` int(11) NOT NULL,
  `uuid` varchar(64) NOT NULL,
  `sys_id` varchar(32) NOT NULL,
  `group_id` varchar(32) NOT NULL,
  `traveler_id` varchar(32) NOT NULL,
  `is_leader` tinyint(1) DEFAULT 0,
  `roaming_phone` varchar(30) DEFAULT NULL,
  `joined_at` datetime DEFAULT current_timestamp(),
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `traveler_links`
--

CREATE TABLE `traveler_links` (
  `id` int(11) NOT NULL,
  `uuid` varchar(64) NOT NULL,
  `sys_id` varchar(32) NOT NULL,
  `traveler_a_id` varchar(32) NOT NULL,
  `traveler_b_id` varchar(32) NOT NULL,
  `relation_type` enum('spouse','parent','sibling','other','group_member') NOT NULL,
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `umrah_nocs`
--

CREATE TABLE `umrah_nocs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `group_sys_id` varchar(32) NOT NULL,
  `doc_type` varchar(30) DEFAULT 'noc',
  `traveler_sys_ids` longtext NOT NULL,
  `smb_path` text DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT current_timestamp(),
  `meta_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(17) NOT NULL,
  `type` varchar(128) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` longtext DEFAULT NULL,
  `phone` longtext DEFAULT NULL,
  `address` text DEFAULT NULL,
  `basic_info` longtext DEFAULT NULL COMMENT 'basic info, detailed address, banking info',
  `company_reg_compliance` longtext DEFAULT NULL,
  `contact_n_communication_details` longtext DEFAULT NULL,
  `auth_sign_info` longtext DEFAULT NULL,
  `internal_control_info` longtext DEFAULT NULL,
  `work_name` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `is_client` int(11) DEFAULT NULL,
  `client_sys_id` varchar(16) DEFAULT NULL COMMENT 'if is_vendor = 1',
  `meta_data` longtext DEFAULT NULL COMMENT '{\r\n    "created_by_date": "demo_name; 12-10-2025 10:11",\r\n    "updated_by_date": [\r\n        {\r\n            "1": "demo_name; 12-10-2025 10:11",\r\n            "2": "demo_name; 12-10-2025 10:11",\r\n            "3": "demo_name; 12-10-2025 10:11",\r\n        }\r\n        .....\r\n\r\n        not more than 20\r\n    ]\r\n}'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_categories`
--

CREATE TABLE `visa_categories` (
  `id` int(11) NOT NULL,
  `uuid` varchar(100) NOT NULL,
  `sys_id` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_masterdata`
--

CREATE TABLE `visa_masterdata` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(30) NOT NULL,
  `country_sys_id` varchar(30) NOT NULL,
  `country_name` varchar(120) NOT NULL,
  `categories` longtext DEFAULT NULL COMMENT 'JSON array of visa categories with sub_categories, instructions, documents, requirements',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `meta_data` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_services`
--

CREATE TABLE `visa_services` (
  `id` int(11) NOT NULL,
  `uuid` varchar(100) NOT NULL,
  `sys_id` varchar(100) NOT NULL,
  `work_sys_id` varchar(100) NOT NULL,
  `vs_travelers` longtext DEFAULT NULL CHECK (json_valid(`vs_travelers`)),
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`)),
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visa_types`
--

CREATE TABLE `visa_types` (
  `id` int(11) NOT NULL,
  `uuid` varchar(100) NOT NULL,
  `sys_id` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(30) NOT NULL,
  `package_sys_id` varchar(30) NOT NULL,
  `item_sys_id` varchar(60) DEFAULT NULL,
  `type` enum('hotel','transport','activity','component','combined','flight') NOT NULL,
  `voucher_no` varchar(40) NOT NULL,
  `issued_on` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `voucher_status` enum('draft','issued','cancelled') NOT NULL DEFAULT 'draft',
  `status` enum('active','inactive','deleted') NOT NULL DEFAULT 'active',
  `meta_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `works`
--

CREATE TABLE `works` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(36) NOT NULL,
  `sys_id` varchar(20) NOT NULL,
  `work_name` varchar(255) DEFAULT NULL,
  `lead_sys_id` varchar(20) NOT NULL,
  `client_info` longtext DEFAULT NULL CHECK (json_valid(`client_info`)),
  `service_type` longtext DEFAULT NULL CHECK (json_valid(`service_type`)),
  `service_count` int(11) DEFAULT 0,
  `segment_type` varchar(20) DEFAULT NULL COMMENT 'one_way|round_trip|multi_city',
  `segment_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`segment_data`)),
  `service_data` longtext DEFAULT NULL CHECK (json_valid(`service_data`)),
  `instruction` longtext DEFAULT NULL CHECK (json_valid(`instruction`)),
  `special_instruction` longtext DEFAULT NULL CHECK (json_valid(`special_instruction`)),
  `lead_info` longtext DEFAULT NULL CHECK (json_valid(`lead_info`)),
  `lead_snapshot` longtext DEFAULT NULL CHECK (json_valid(`lead_snapshot`)),
  `traveler_sys_ids` longtext DEFAULT NULL CHECK (json_valid(`traveler_sys_ids`)),
  `work_status` varchar(30) DEFAULT 'open',
  `assigned_to` varchar(100) DEFAULT NULL,
  `meta_data` longtext DEFAULT NULL CHECK (json_valid(`meta_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_activities_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_activities_sys_id` (`sys_id`),
  ADD KEY `idx_act_country` (`country_sys_id`),
  ADD KEY `idx_act_city` (`city_sys_id`),
  ADD KEY `idx_act_vendor` (`vendor_sys_id`),
  ADD KEY `idx_act_type` (`type`),
  ADD KEY `idx_act_package` (`package_sys_id`),
  ADD KEY `idx_act_status` (`status`);
ALTER TABLE `activities` ADD FULLTEXT KEY `ft_activities` (`name`,`search_terms`);

--
-- Indexes for table `activity_variants`
--
ALTER TABLE `activity_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_av_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_av_sys_id` (`sys_id`),
  ADD KEY `idx_av_activity` (`activity_sys_id`),
  ADD KEY `idx_av_country` (`country_sys_id`),
  ADD KEY `idx_av_vendor` (`vendor_sys_id`),
  ADD KEY `idx_av_status` (`status`);

--
-- Indexes for table `ac_banking`
--
ALTER TABLE `ac_banking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD UNIQUE KEY `uniq_main_type_acc_name` (`main_type`,`acc_name`);

--
-- Indexes for table `ac_banking_stmts`
--
ALTER TABLE `ac_banking_stmts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `idx_ledger_date_historical` (`ledger_db_id`,`date`,`is_historical`);

--
-- Indexes for table `ac_banking_stmts_backups`
--
ALTER TABLE `ac_banking_stmts_backups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `idx_ledger_date_historical` (`ledger_db_id`,`date`,`is_historical`);

--
-- Indexes for table `ac_gateway_tokens`
--
ALTER TABLE `ac_gateway_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ac_instrument_tracking`
--
ALTER TABLE `ac_instrument_tracking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_instrument` (`instrument_type`,`instrument_no`);

--
-- Indexes for table `air_tickets`
--
ALTER TABLE `air_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD UNIQUE KEY `task_sys_id` (`task_sys_id`),
  ADD KEY `idx_work_sys_id` (`work_sys_id`),
  ADD KEY `idx_lead_sys_id` (`lead_sys_id`);

--
-- Indexes for table `air_ticket_calculations`
--
ALTER TABLE `air_ticket_calculations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `air_ticket_quotations`
--
ALTER TABLE `air_ticket_quotations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`sys_id`),
  ADD UNIQUE KEY `uq_att_emp_date` (`employee_sys_id`,`date`),
  ADD KEY `idx_att_emp` (`employee_sys_id`),
  ADD KEY `idx_att_date` (`date`);

--
-- Indexes for table `batches`
--
ALTER TABLE `batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `classify_tokens`
--
ALTER TABLE `classify_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `traveler_id` (`traveler_id`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `client_payments`
--
ALTER TABLE `client_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cp_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_cp_sys_id` (`sys_id`),
  ADD KEY `idx_cp_package` (`package_sys_id`),
  ADD KEY `idx_cp_quote` (`quote_sys_id`),
  ADD KEY `idx_cp_paid_on` (`paid_on`),
  ADD KEY `idx_cp_status` (`status`);

--
-- Indexes for table `components`
--
ALTER TABLE `components`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_components_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_components_sys_id` (`sys_id`),
  ADD KEY `idx_comp_vendor` (`vendor_sys_id`),
  ADD KEY `idx_comp_category` (`category`),
  ADD KEY `idx_comp_status` (`status`);
ALTER TABLE `components` ADD FULLTEXT KEY `ft_components` (`name`,`search_terms`);

--
-- Indexes for table `component_variants`
--
ALTER TABLE `component_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cv_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_cv_sys_id` (`sys_id`),
  ADD KEY `idx_cv_component` (`component_sys_id`),
  ADD KEY `idx_cv_status` (`status`);

--
-- Indexes for table `com_works`
--
ALTER TABLE `com_works`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `com_works_backup`
--
ALTER TABLE `com_works_backup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_countries_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_countries_sys_id` (`sys_id`),
  ADD UNIQUE KEY `uq_countries_code` (`code`),
  ADD KEY `idx_countries_region` (`region`),
  ADD KEY `idx_countries_status` (`status`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_currencies_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_currencies_sys_id` (`sys_id`),
  ADD UNIQUE KEY `uq_currencies_currency_code` (`currency_code`),
  ADD KEY `idx_currencies_status` (`status`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `directors`
--
ALTER TABLE `directors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `client_id` (`sys_id`);

--
-- Indexes for table `director_balances`
--
ALTER TABLE `director_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_uuid` (`uuid`);

--
-- Indexes for table `director_dividends`
--
ALTER TABLE `director_dividends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_uuid` (`uuid`);

--
-- Indexes for table `director_transactions`
--
ALTER TABLE `director_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_uuid` (`uuid`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `dividend_details`
--
ALTER TABLE `dividend_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_uuid` (`uuid`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `doc_type_registry`
--
ALTER TABLE `doc_type_registry`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_doc_type` (`doc_type`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `client_id` (`sys_id`),
  ADD KEY `idx_department_sys_id` (`department_sys_id`);

--
-- Indexes for table `employee_credentials`
--
ALTER TABLE `employee_credentials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `idx_ec_emp` (`employee_sys_id`);

--
-- Indexes for table `employee_documents`
--
ALTER TABLE `employee_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `idx_ed_emp` (`employee_sys_id`),
  ADD KEY `idx_ed_hr` (`is_hr_issued`);

--
-- Indexes for table `employee_notes`
--
ALTER TABLE `employee_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `idx_en_emp_date` (`employee_sys_id`,`note_date`);

--
-- Indexes for table `employee_notifications`
--
ALTER TABLE `employee_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `idx_notif_emp_read` (`employee_sys_id`,`is_read`),
  ADD KEY `idx_notif_created` (`created_at`);

--
-- Indexes for table `employee_permissions`
--
ALTER TABLE `employee_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_employee_perm` (`employee_sys_id`,`permission_key`);

--
-- Indexes for table `eps_structures`
--
ALTER TABLE `eps_structures`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD UNIQUE KEY `unique_employee_effective` (`employee_id`,`effective_date`),
  ADD KEY `idx_employee` (`employee_id`),
  ADD KEY `idx_effective_date` (`effective_date`);

--
-- Indexes for table `financial_entries`
--
ALTER TABLE `financial_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `idx_transaction_group_id` (`transaction_group_id`),
  ADD KEY `idx_account_head` (`account_head`);

--
-- Indexes for table `financial_entries_backup`
--
ALTER TABLE `financial_entries_backup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `fx_rates`
--
ALTER TABLE `fx_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fx_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_fx_sys_id` (`sys_id`),
  ADD UNIQUE KEY `uq_fx_ccy_date` (`currency_code`,`effective_date`),
  ADD KEY `idx_fx_currency` (`currency_sys_id`),
  ADD KEY `idx_fx_date` (`effective_date`),
  ADD KEY `idx_fx_status` (`status`);

--
-- Indexes for table `gateway_payments`
--
ALTER TABLE `gateway_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice` (`invoice_sys_id`),
  ADD KEY `idx_merchant_txn` (`merchant_transaction_id`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_hotels_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_hotels_sys_id` (`sys_id`),
  ADD KEY `idx_hotels_country` (`country_sys_id`),
  ADD KEY `idx_hotels_city` (`city_sys_id`),
  ADD KEY `idx_hotels_vendor` (`vendor_sys_id`),
  ADD KEY `idx_hotels_status` (`status`);
ALTER TABLE `hotels` ADD FULLTEXT KEY `ft_hotels` (`name`,`search_terms`);

--
-- Indexes for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `hotel_quotations`
--
ALTER TABLE `hotel_quotations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hotel_services`
--
ALTER TABLE `hotel_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_uuid` (`uuid`),
  ADD UNIQUE KEY `uniq_sys_id` (`sys_id`),
  ADD KEY `idx_work` (`work_sys_id`),
  ADD KEY `idx_task` (`task_sys_id`);

--
-- Indexes for table `investors`
--
ALTER TABLE `investors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `client_id` (`sys_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sys_id` (`sys_id`) USING BTREE;

--
-- Indexes for table `invoices_backup`
--
ALTER TABLE `invoices_backup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sys_id` (`sys_id`) USING BTREE;

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `leave_applications`
--
ALTER TABLE `leave_applications`
  ADD PRIMARY KEY (`sys_id`),
  ADD KEY `idx_la_emp` (`employee_sys_id`),
  ADD KEY `idx_la_dates` (`date_from`,`date_to`),
  ADD KEY `idx_la_status` (`status`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_lb` (`employee_sys_id`,`leave_type_sys_id`,`year`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`sys_id`),
  ADD UNIQUE KEY `uq_lt_code` (`code`);

--
-- Indexes for table `loans`
--
ALTER TABLE `loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lender` (`lender_type`,`lender_id`),
  ADD KEY `idx_borrower` (`borrower_type`,`borrower_id`);

--
-- Indexes for table `loan_installments`
--
ALTER TABLE `loan_installments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loan` (`loan_sys_id`);

--
-- Indexes for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_loan` (`loan_sys_id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `master_visa_services`
--
ALTER TABLE `master_visa_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `idx_country` (`country_sys_id`),
  ADD KEY `idx_visa_type` (`visa_type_sys_id`),
  ADD KEY `idx_visa_category` (`visa_category_sys_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `dept_sys_id` (`department_sys_id`),
  ADD KEY `work_sys_id` (`work_sys_id`),
  ADD KEY `task_sys_id` (`task_sys_id`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `office_calendar`
--
ALTER TABLE `office_calendar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_oc_date` (`date`);

--
-- Indexes for table `office_calendar_events`
--
ALTER TABLE `office_calendar_events`
  ADD PRIMARY KEY (`sys_id`),
  ADD KEY `idx_event_date` (`event_date`),
  ADD KEY `idx_visibility` (`visibility`);

--
-- Indexes for table `office_holidays`
--
ALTER TABLE `office_holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD UNIQUE KEY `uq_holiday_date` (`holiday_date`),
  ADD KEY `idx_hol_year` (`year`);

--
-- Indexes for table `old_tasks`
--
ALTER TABLE `old_tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `old_tasks_backup`
--
ALTER TABLE `old_tasks_backup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_packages_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_packages_sys_id` (`sys_id`),
  ADD UNIQUE KEY `uq_packages_booking_ref` (`booking_ref`),
  ADD KEY `idx_pkg_client` (`client_sys_id`),
  ADD KEY `idx_pkg_completion` (`completion_status`),
  ADD KEY `idx_pkg_assigned` (`assigned_to_sys_id`),
  ADD KEY `idx_pkg_status` (`status`);

--
-- Indexes for table `package_quotes`
--
ALTER TABLE `package_quotes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_quotes_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_quotes_sys_id` (`sys_id`),
  ADD UNIQUE KEY `uq_quotes_pkg_ver` (`package_sys_id`,`version`),
  ADD KEY `idx_q_package` (`package_sys_id`),
  ADD KEY `idx_q_quote_status` (`quote_status`),
  ADD KEY `idx_q_status` (`status`);

--
-- Indexes for table `package_services`
--
ALTER TABLE `package_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD UNIQUE KEY `work_sys_id` (`work_sys_id`);

--
-- Indexes for table `package_travelers`
--
ALTER TABLE `package_travelers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pt_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_pt_sys_id` (`sys_id`),
  ADD UNIQUE KEY `uq_pt_pkg_trav` (`package_sys_id`,`traveler_sys_id`),
  ADD KEY `idx_pt_package` (`package_sys_id`),
  ADD KEY `idx_pt_traveler` (`traveler_sys_id`),
  ADD KEY `idx_pt_status` (`status`);

--
-- Indexes for table `payroll_finals`
--
ALTER TABLE `payroll_finals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `system_id` (`sys_id`),
  ADD KEY `eps_id` (`eps_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `month` (`month`),
  ADD KEY `from_account` (`from_account`);

--
-- Indexes for table `petty_cashes`
--
ALTER TABLE `petty_cashes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `portal_links`
--
ALTER TABLE `portal_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `idx_portal_type` (`portal_type`);

--
-- Indexes for table `profit_loss`
--
ALTER TABLE `profit_loss`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_uuid` (`uuid`),
  ADD KEY `idx_sys_id` (`sys_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_date` (`date`);

--
-- Indexes for table `quote_lines`
--
ALTER TABLE `quote_lines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ql_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_ql_sys_id` (`sys_id`),
  ADD KEY `idx_ql_quote` (`quote_sys_id`),
  ADD KEY `idx_ql_item` (`item_sys_id`);

--
-- Indexes for table `room_rates`
--
ALTER TABLE `room_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_room_rates_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_room_rates_sys_id` (`sys_id`),
  ADD KEY `idx_rr_room_type` (`room_type_sys_id`),
  ADD KEY `idx_rr_hotel` (`hotel_sys_id`),
  ADD KEY `idx_rr_period` (`room_type_sys_id`,`valid_from`,`valid_to`),
  ADD KEY `idx_rr_status` (`status`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_room_types_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_room_types_sys_id` (`sys_id`),
  ADD KEY `idx_rt_hotel` (`hotel_sys_id`),
  ADD KEY `idx_rt_status` (`status`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `service_works`
--
ALTER TABLE `service_works`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `work_sys_id` (`work_sys_id`),
  ADD KEY `dept_sys_id` (`department_sys_id`);

--
-- Indexes for table `sm_posts`
--
ALTER TABLE `sm_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `platform` (`platform`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sp_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_sp_sys_id` (`sys_id`),
  ADD KEY `idx_sp_package` (`package_sys_id`),
  ADD KEY `idx_sp_vendor` (`vendor_sys_id`),
  ADD KEY `idx_sp_item` (`item_sys_id`),
  ADD KEY `idx_sp_paid_on` (`paid_on`),
  ADD KEY `idx_sp_status` (`status`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `service_work_sys_id` (`service_work_sys_id`),
  ADD KEY `work_sys_id` (`work_sys_id`),
  ADD KEY `idx_is_merged` (`is_merged`);

--
-- Indexes for table `task_notes`
--
ALTER TABLE `task_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `task_sys_id` (`task_sys_id`),
  ADD KEY `note_type` (`note_type`),
  ADD KEY `idx_board_work` (`board_type`,`work_sys_id`,`service_slug`,`board_name`);

--
-- Indexes for table `transport_services`
--
ALTER TABLE `transport_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ts_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_ts_sys_id` (`sys_id`),
  ADD KEY `idx_ts_country` (`country_sys_id`),
  ADD KEY `idx_ts_vendor` (`vendor_sys_id`),
  ADD KEY `idx_ts_type` (`type`),
  ADD KEY `idx_ts_status` (`status`);
ALTER TABLE `transport_services` ADD FULLTEXT KEY `ft_ts` (`name`,`search_terms`);

--
-- Indexes for table `transport_variants`
--
ALTER TABLE `transport_variants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tv_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_tv_sys_id` (`sys_id`),
  ADD KEY `idx_tv_service` (`service_sys_id`),
  ADD KEY `idx_tv_country` (`country_sys_id`),
  ADD KEY `idx_tv_status` (`status`);

--
-- Indexes for table `travelers`
--
ALTER TABLE `travelers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `traveler_documents`
--
ALTER TABLE `traveler_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `traveler_id` (`traveler_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `doc_type` (`doc_type`),
  ADD KEY `expiry_date` (`expiry_date`),
  ADD KEY `status` (`status`),
  ADD KEY `passport_status` (`passport_status`);

--
-- Indexes for table `traveler_groups`
--
ALTER TABLE `traveler_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD UNIQUE KEY `uniq_linked_work` (`linked_work_id`);

--
-- Indexes for table `traveler_group_members`
--
ALTER TABLE `traveler_group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD UNIQUE KEY `uniq_membership` (`group_id`,`traveler_id`),
  ADD KEY `idx_group` (`group_id`),
  ADD KEY `idx_traveler` (`traveler_id`);

--
-- Indexes for table `traveler_links`
--
ALTER TABLE `traveler_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD UNIQUE KEY `uniq_pair` (`traveler_a_id`,`traveler_b_id`),
  ADD KEY `idx_traveler_a` (`traveler_a_id`),
  ADD KEY `idx_traveler_b` (`traveler_b_id`);

--
-- Indexes for table `umrah_nocs`
--
ALTER TABLE `umrah_nocs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_sys_id` (`sys_id`),
  ADD KEY `idx_group` (`group_sys_id`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`client_sys_id`),
  ADD UNIQUE KEY `client_sys_id` (`client_sys_id`);

--
-- Indexes for table `visa_categories`
--
ALTER TABLE `visa_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `visa_masterdata`
--
ALTER TABLE `visa_masterdata`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `idx_country_sys_id` (`country_sys_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `visa_services`
--
ALTER TABLE `visa_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD UNIQUE KEY `work_sys_id` (`work_sys_id`);

--
-- Indexes for table `visa_types`
--
ALTER TABLE `visa_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_vouchers_uuid` (`uuid`),
  ADD UNIQUE KEY `uq_vouchers_sys_id` (`sys_id`),
  ADD UNIQUE KEY `uq_vouchers_voucher_no` (`voucher_no`),
  ADD KEY `idx_v_package` (`package_sys_id`),
  ADD KEY `idx_v_item` (`item_sys_id`),
  ADD KEY `idx_v_voucher_status` (`voucher_status`),
  ADD KEY `idx_v_status` (`status`);

--
-- Indexes for table `works`
--
ALTER TABLE `works`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`),
  ADD KEY `lead_sys_id` (`lead_sys_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_variants`
--
ALTER TABLE `activity_variants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ac_banking`
--
ALTER TABLE `ac_banking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ac_banking_stmts`
--
ALTER TABLE `ac_banking_stmts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ac_banking_stmts_backups`
--
ALTER TABLE `ac_banking_stmts_backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ac_gateway_tokens`
--
ALTER TABLE `ac_gateway_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ac_instrument_tracking`
--
ALTER TABLE `ac_instrument_tracking`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `air_tickets`
--
ALTER TABLE `air_tickets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `air_ticket_calculations`
--
ALTER TABLE `air_ticket_calculations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `air_ticket_quotations`
--
ALTER TABLE `air_ticket_quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batches`
--
ALTER TABLE `batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classify_tokens`
--
ALTER TABLE `classify_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_payments`
--
ALTER TABLE `client_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `components`
--
ALTER TABLE `components`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `component_variants`
--
ALTER TABLE `component_variants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `com_works`
--
ALTER TABLE `com_works`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `com_works_backup`
--
ALTER TABLE `com_works_backup`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `directors`
--
ALTER TABLE `directors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `director_balances`
--
ALTER TABLE `director_balances`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `director_dividends`
--
ALTER TABLE `director_dividends`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `director_transactions`
--
ALTER TABLE `director_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dividend_details`
--
ALTER TABLE `dividend_details`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doc_type_registry`
--
ALTER TABLE `doc_type_registry`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_credentials`
--
ALTER TABLE `employee_credentials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_documents`
--
ALTER TABLE `employee_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_notes`
--
ALTER TABLE `employee_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_notifications`
--
ALTER TABLE `employee_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_permissions`
--
ALTER TABLE `employee_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `eps_structures`
--
ALTER TABLE `eps_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_entries`
--
ALTER TABLE `financial_entries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_entries_backup`
--
ALTER TABLE `financial_entries_backup`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fx_rates`
--
ALTER TABLE `fx_rates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gateway_payments`
--
ALTER TABLE `gateway_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hotel_quotations`
--
ALTER TABLE `hotel_quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hotel_services`
--
ALTER TABLE `hotel_services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `investors`
--
ALTER TABLE `investors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices_backup`
--
ALTER TABLE `invoices_backup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_balances`
--
ALTER TABLE `leave_balances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loans`
--
ALTER TABLE `loans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_installments`
--
ALTER TABLE `loan_installments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `loan_repayments`
--
ALTER TABLE `loan_repayments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login`
--
ALTER TABLE `login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_visa_services`
--
ALTER TABLE `master_visa_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `office_calendar`
--
ALTER TABLE `office_calendar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `office_holidays`
--
ALTER TABLE `office_holidays`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `old_tasks`
--
ALTER TABLE `old_tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `old_tasks_backup`
--
ALTER TABLE `old_tasks_backup`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_quotes`
--
ALTER TABLE `package_quotes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_services`
--
ALTER TABLE `package_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_travelers`
--
ALTER TABLE `package_travelers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_finals`
--
ALTER TABLE `payroll_finals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `petty_cashes`
--
ALTER TABLE `petty_cashes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portal_links`
--
ALTER TABLE `portal_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profit_loss`
--
ALTER TABLE `profit_loss`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quote_lines`
--
ALTER TABLE `quote_lines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `room_rates`
--
ALTER TABLE `room_rates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_works`
--
ALTER TABLE `service_works`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sm_posts`
--
ALTER TABLE `sm_posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `task_notes`
--
ALTER TABLE `task_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transport_services`
--
ALTER TABLE `transport_services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transport_variants`
--
ALTER TABLE `transport_variants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `travelers`
--
ALTER TABLE `travelers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `traveler_documents`
--
ALTER TABLE `traveler_documents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `traveler_groups`
--
ALTER TABLE `traveler_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `traveler_group_members`
--
ALTER TABLE `traveler_group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `traveler_links`
--
ALTER TABLE `traveler_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `umrah_nocs`
--
ALTER TABLE `umrah_nocs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visa_categories`
--
ALTER TABLE `visa_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visa_masterdata`
--
ALTER TABLE `visa_masterdata`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visa_services`
--
ALTER TABLE `visa_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visa_types`
--
ALTER TABLE `visa_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `works`
--
ALTER TABLE `works`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
