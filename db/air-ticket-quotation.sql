-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 14, 2026 at 05:17 PM
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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `air_ticket_quotations`
--
ALTER TABLE `air_ticket_quotations`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `air_ticket_quotations`
--
ALTER TABLE `air_ticket_quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;
