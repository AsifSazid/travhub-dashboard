-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 17, 2026 at 07:38 PM
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

--
-- Dumping data for table `air_ticket_calculations`
--

INSERT INTO `air_ticket_calculations` (`id`, `uuid`, `sys_id`, `airline`, `pax`, `raw_gds`, `segments_json`, `pricing_json`, `copy_text`, `gross_fare`, `base_fare`, `taxes`, `commission_a`, `govt_tax_b`, `iata_charge`, `net_fare`, `payable`, `total_payable`, `meta_data`, `created_at`) VALUES
(1, '67269c8c-33c9-44e7-a7d7-1e1f5fd34d57', 'THR-TC-26-00K001', 'Turkish Airlines', NULL, '1 . TK  713 M  19MAY DACIST HK3  0650   1245  O*        E TU    1   \n 2 . TK 1875 M  19MAY ISTMXP HK3  1550   1745  O*        E TU    1   \n 3.    ARNK\n 4 . TK 1858 S  25MAY MADIST HK3  1200   1715  O*        E MO    2   \n 5 . TK  712 S  26MAY ISTDAC HK3  1840  #0515  O*        E TU/WE 2\n\nPSGR                  FARE     TAXES         TOTAL PSG DES   \nFQG 1         BDT      242758      77047       319805 ADT', '[{\"line\":1,\"flight\":\"TK713\",\"class\":\"M\",\"date\":\"19MAY\",\"route\":\"DAC-IST\",\"status\":\"HS3\",\"departure\":\"0650\",\"arrival\":\"1245\"},{\"line\":2,\"flight\":\"TK1875\",\"class\":\"M\",\"date\":\"19MAY\",\"route\":\"IST-MXP\",\"status\":\"HS3\",\"departure\":\"1550\",\"arrival\":\"1745\"},{\"line\":4,\"flight\":\"TK1858\",\"class\":\"S\",\"date\":\"25MAY\",\"route\":\"MAD-IST\",\"status\":\"HS3\",\"departure\":\"1200\",\"arrival\":\"1715\"},{\"line\":5,\"flight\":\"TK712\",\"class\":\"S\",\"date\":\"26MAY\",\"route\":\"IST-DAC\",\"status\":\"HS3\",\"departure\":\"1840\",\"arrival\":\"0515\"}]', 'null', 'Turkish Airlines:\n1. TK713 M 19MAY DAC-IST HS3 0650 1245\n2. TK1875 M 19MAY IST-MXP HS3 1550 1745\n3. TK1858 S 25MAY MAD-IST HS3 1200 1715\n4. TK712 S 26MAY IST-DAC HS3 1840 0515\nPrice:\n\nADT x 1\n- Gross: BDT 319,805 per person\n- Payable: BDT 311,788/- per person.\nTotal payable: 311,788/-\n\nGrand Total Payable: 311,788/-', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\n    \"created_by_date\": {\n        \"user\": \"Asif Mostofa Sazid\",\n        \"date\": \"16-05-2026 18:39\"\n    },\n    \"updated_by_date\": []\n}', '2026-05-16 12:39:46'),
(2, '45f6aed5-2b74-45ee-a2f9-352494f242bf', 'THR-TC-26-00K002', 'Turkish Airlines', '{\"total\":2,\"info\":{\"adult\":1,\"child\":0,\"infant\":1}}', '1 . TK  713 M  19MAY DACIST HK3  0650   1245  O*        E TU    1   \n 2 . TK 1875 M  19MAY ISTMXP HK3  1550   1745  O*        E TU    1   \n 3.    ARNK\n 4 . TK 1858 S  25MAY MADIST HK3  1200   1715  O*        E MO    2   \n 5 . TK  712 S  26MAY ISTDAC HK3  1840  #0515  O*        E TU/WE\n\n2PSGR                  FARE     TAXES         TOTAL PSG DES   \nFQG 1         BDT      242758      77047       319805 ADT\nFQG 1         BDT      242758      77047       99805 Inf', '[{\"line\":1,\"flight\":\"TK713\",\"class\":\"M\",\"date\":\"19MAY\",\"route\":\"DAC-IST\",\"status\":\"HS3\",\"departure\":\"0650\",\"arrival\":\"1245\"},{\"line\":2,\"flight\":\"TK1875\",\"class\":\"M\",\"date\":\"19MAY\",\"route\":\"IST-MXP\",\"status\":\"HS3\",\"departure\":\"1550\",\"arrival\":\"1745\"},{\"line\":4,\"flight\":\"TK1858\",\"class\":\"S\",\"date\":\"25MAY\",\"route\":\"MAD-IST\",\"status\":\"HS3\",\"departure\":\"1200\",\"arrival\":\"1715\"},{\"line\":5,\"flight\":\"TK712\",\"class\":\"S\",\"date\":\"26MAY\",\"route\":\"IST-DAC\",\"status\":\"HS3\",\"departure\":\"1840\",\"arrival\":\"0515\"}]', '[{\"type\":\"ADT\",\"pax\":1,\"base_fare\":242758,\"taxes\":77047,\"gross_fare\":319805,\"commission_a\":16993,\"govt_tax_b\":959,\"iata_charge\":0,\"net_fare\":303771,\"payable\":311788,\"payable_edited\":false,\"total_payable\":311788},{\"type\":\"INF\",\"pax\":1,\"base_fare\":242758,\"taxes\":77047,\"gross_fare\":99805,\"commission_a\":16993,\"govt_tax_b\":299,\"iata_charge\":0,\"net_fare\":83111,\"payable\":91458,\"payable_edited\":false,\"total_payable\":91458}]', 'Turkish Airlines:\n1. TK713 M 19MAY DAC-IST HS3 0650 1245\n2. TK1875 M 19MAY IST-MXP HS3 1550 1745\n3. TK1858 S 25MAY MAD-IST HS3 1200 1715\n4. TK712 S 26MAY IST-DAC HS3 1840 0515\nPrice:\n\nADT x 1\n- Gross: BDT 319,805 per person\n- Payable: BDT 311,788/- per person.\nTotal payable: 311,788/-\n\nINF x 1\n- Gross: BDT 99,805 per person\n- Payable: BDT 91,458/- per person.\nTotal payable: 91,458/-\n\nGrand Total Payable: 403,246/-', 419610.00, 485516.00, 154094.00, 33986.00, 1258.00, 0.00, 386882.00, 403246.00, 403246.00, '{\n    \"created_by_date\": {\n        \"user\": \"Asif Mostofa Sazid\",\n        \"date\": \"17-05-2026 13:00\"\n    },\n    \"updated_by_date\": []\n}', '2026-05-17 07:00:18'),
(3, '6621e519-75c2-47f0-a69e-97a3b10b129e', 'THR-TC-26-00K003', 'Turkish Airlines', '{\"total\":2,\"info\":{\"adult\":1,\"child\":1,\"infant\":0}}', '1 . TK  713 M  19MAY DACIST HK3  0650   1245  O*        E TU    1   \n 2 . TK 1875 M  19MAY ISTMXP HK3  1550   1745  O*        E TU    1   \n 3.    ARNK\n 4 . TK 1858 S  25MAY MADIST HK3  1200   1715  O*        E MO    2   \n 5 . TK  712 S  26MAY ISTDAC HK3  1840  #0515  O*        E TU/WE 2\n\nPSGR                  FARE     TAXES         TOTAL PSG DES   \nFQG 1         BDT      242758      77047       319805 ADT\nFQG 1         BDT      242758      77047       279805 CHD', '[{\"line\":1,\"flight\":\"TK713\",\"class\":\"M\",\"date\":\"19MAY\",\"route\":\"DAC-IST\",\"status\":\"HS3\",\"departure\":\"0650\",\"arrival\":\"1245\"},{\"line\":2,\"flight\":\"TK1875\",\"class\":\"M\",\"date\":\"19MAY\",\"route\":\"IST-MXP\",\"status\":\"HS3\",\"departure\":\"1550\",\"arrival\":\"1745\"},{\"line\":4,\"flight\":\"TK1858\",\"class\":\"S\",\"date\":\"25MAY\",\"route\":\"MAD-IST\",\"status\":\"HS3\",\"departure\":\"1200\",\"arrival\":\"1715\"},{\"line\":5,\"flight\":\"TK712\",\"class\":\"S\",\"date\":\"26MAY\",\"route\":\"IST-DAC\",\"status\":\"HS3\",\"departure\":\"1840\",\"arrival\":\"0515\"}]', '[{\"type\":\"ADT\",\"pax\":1,\"base_fare\":242758,\"taxes\":77047,\"gross_fare\":319805,\"commission_a\":16993,\"govt_tax_b\":959,\"iata_charge\":0,\"net_fare\":303771,\"payable\":311788,\"payable_edited\":false,\"total_payable\":311788},{\"type\":\"CHD\",\"pax\":1,\"base_fare\":242758,\"taxes\":77047,\"gross_fare\":279805,\"commission_a\":16993,\"govt_tax_b\":839,\"iata_charge\":0,\"net_fare\":263651,\"payable\":271728,\"payable_edited\":false,\"total_payable\":271728}]', 'Turkish Airlines:\n1. TK713 M 19MAY DAC-IST HS3 0650 1245\n2. TK1875 M 19MAY IST-MXP HS3 1550 1745\n3. TK1858 S 25MAY MAD-IST HS3 1200 1715\n4. TK712 S 26MAY IST-DAC HS3 1840 0515\nPrice:\n\nADT x 1\n- Gross: BDT 319,805 per person\n- Payable: BDT 311,788/- per person.\nTotal payable: 311,788/-\n\nCHD x 1\n- Gross: BDT 279,805 per person\n- Payable: BDT 271,728/- per person.\nTotal payable: 271,728/-\n\nGrand Total Payable: 583,516/-', 599610.00, 485516.00, 154094.00, 33986.00, 1798.00, 0.00, 567422.00, 583516.00, 583516.00, '{\n    \"created_by_date\": {\n        \"user\": \"Asif Mostofa Sazid\",\n        \"date\": \"17-05-2026 16:52\"\n    },\n    \"updated_by_date\": []\n}', '2026-05-17 10:52:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `air_ticket_calculations`
--
ALTER TABLE `air_ticket_calculations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uuid` (`uuid`),
  ADD UNIQUE KEY `sys_id` (`sys_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `air_ticket_calculations`
--
ALTER TABLE `air_ticket_calculations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
