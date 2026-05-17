-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 17, 2026 at 07:36 PM
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
  `meta_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_finals`
--

INSERT INTO `payroll_finals` (`id`, `uuid`, `sys_id`, `eps_id`, `employee_id`, `employee_name`, `eps_salary`, `bonus`, `overtime`, `allowances`, `deduction`, `net_payable_salary`, `note`, `payment_date`, `month`, `payment_type`, `payment_components`, `status`, `prepared_info`, `collected_info`, `authorized_info`, `from_account`, `meta_data`) VALUES
(1, '0444f982-d22f-41f0-900e-859835d526c6', 'THR-PS-26-00K001', 'THR-EP-26-00K001', 'EMP-5102513', 'Asif Mostofa Sazid', '{\"gross_salary\":27000,\"basic_salary\":17000,\"house_rent\":8000,\"medical_allowance\":1500,\"conveyance\":500,\"allowance\":0,\"pf_deduction\":0,\"tax_deduction\":0,\"other_deduction\":0,\"total_deductions\":0,\"net_salary\":27000}', 13500.00, 0.00, 0, '{\"provident\":0,\"office_loan\":0,\"tax\":0,\"total\":0}', 13500.00, 'Eid Ul Adha Bonus', '2026-05-17', 'Eid Bonus', 'bonus', '{\"payment_type\":\"bonus\",\"include_base_salary\":0,\"base_salary\":0,\"bonus\":13500,\"overtime\":0,\"other_allowances\":0,\"pf\":0,\"loan\":0,\"tax\":0,\"gross_amount\":13500,\"total_deduction\":0,\"net_payable\":13500}', 'collected', '{\"user_name\":\"Asif Mostofa Sazid\",\"designation\":\"Software Engineer, Web System & Automation \",\"user_id\":\"EMP-5102513\",\"date\":\"2026-05-17 19:13:33\"}', '{\"user_name\":\"Asif Mostofa Sazid\",\"designation\":\"Software Engineer, Web System & Automation \",\"user_id\":\"EMP-5102513\",\"date\":\"2026-05-17 19:31:04\"}', NULL, 'THR-AC-26-00K001', '{\n    \"created_by_date\": {\n        \"user\": \"Asif Mostofa Sazid\",\n        \"date\": \"17-05-2026 17:13\"\n    },\n    \"updated_by_date\": []\n}'),
(2, '6c6282c8-df5e-439a-84a0-c0c1c21dabf2', 'THR-PS-26-00K002', 'THR-EP-26-00K002', 'EMP-2062512', 'Shahanoor Alam Tanvir', '{\"gross_salary\":20000,\"basic_salary\":12000,\"house_rent\":6000,\"medical_allowance\":1500,\"conveyance\":500,\"allowance\":0,\"pf_deduction\":0,\"tax_deduction\":0,\"other_deduction\":0,\"total_deductions\":0,\"net_salary\":20000}', 0.00, 0.00, 0, '{\"provident\":0,\"office_loan\":0,\"tax\":0,\"total\":0}', 20000.00, 'No Notes', '2026-05-17', 'March 2026', 'salary', '{\"payment_type\":\"salary\",\"include_base_salary\":1,\"base_salary\":20000,\"bonus\":0,\"overtime\":0,\"other_allowances\":0,\"pf\":0,\"loan\":0,\"tax\":0,\"gross_amount\":20000,\"total_deduction\":0,\"net_payable\":20000}', 'collected', '{\"user_name\":\"Asif Mostofa Sazid\",\"designation\":\"Software Engineer, Web System & Automation \",\"user_id\":\"EMP-5102513\",\"date\":\"2026-05-17 19:33:18\"}', '{\"user_name\":\"Shahanoor Alam Tanvir\",\"designation\":\"Executive - Visa\",\"user_id\":\"EMP-2062512\",\"date\":\"2026-05-17 19:33:57\"}', NULL, 'THR-AC-26-00K001', '{\n    \"created_by_date\": {\n        \"user\": \"Asif Mostofa Sazid\",\n        \"date\": \"17-05-2026 17:33\"\n    },\n    \"updated_by_date\": []\n}');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `payroll_finals`
--
ALTER TABLE `payroll_finals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
