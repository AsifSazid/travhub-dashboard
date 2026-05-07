-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 25, 2026 at 11:43 PM
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
-- Table structure for table `hotel_bookings`
--

CREATE TABLE `hotel_bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `uuid` varchar(16) NOT NULL,
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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hotel_bookings`
--
ALTER TABLE `hotel_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
