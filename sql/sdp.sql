-- ========================================================
-- Scholarship Data Profiling System (SDP)
-- Database Structure and Seed Data
-- Target Database: `sdp`
-- Compatible with: MySQL 5.7+, MariaDB 10.4+, phpMyAdmin
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database Creation
--
CREATE DATABASE IF NOT EXISTS `sdp` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sdp`;

-- Disable foreign key checks for clean recreation
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table structure for table `campuses`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `campuses`;
CREATE TABLE `campuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campus_name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `campuses`
--

INSERT INTO `campuses` (`id`, `campus_name`) VALUES
(1, 'ACCESS'),
(2, 'BAGUMBAYAN'),
(3, 'ISULAN'),
(4, 'KALAMANSIG'),
(5, 'LUTAYAN'),
(6, 'PALIMBANG'),
(7, 'TACURONG');

-- --------------------------------------------------------
-- Table structure for table `scholarships`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `scholarships`;
CREATE TABLE `scholarships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scholarship_name` varchar(255) NOT NULL,
  `benefactor` varchar(255) DEFAULT NULL,
  `amount_per_sem` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scholarships`
--

INSERT INTO `scholarships` (`id`, `scholarship_name`, `benefactor`, `amount_per_sem`) VALUES
(1, 'CHED FULL SSP', NULL, 40000),
(2, 'CHED HALF SSP', NULL, 20000),
(3, 'PS 151', NULL, 10500),
(4, 'PS 101', NULL, 10500),
(5, 'FS GAD', NULL, 6000),
(6, 'TS GAD', NULL, 4000),
(7, 'DA ACEF', NULL, 13700),
(8, 'FS 101', NULL, 15000),
(9, 'SMART', NULL, 10000),
(10, 'CONTINUING G', NULL, 7500),
(11, 'NEW CHED TDP', NULL, 7500),
(12, 'ISKOLAR NG LUNGSOD NG TACURONG (ILT)', NULL, 7500),
(13, 'CHED-TES', NULL, 20000),
(14, 'UNIFAST FREE EDUCATION', NULL, 100000),
(15, 'KABUGWASON', NULL, 15000),
(16, 'DA-ACEF', NULL, 13700),
(17, 'INSTITUTIONAL PRESIDENT’S LIST AND DEAN’S LIST', NULL, 2000),
(18, 'SEN. IMEE MARCOS', NULL, 5000),
(19, 'TDP SEN. LOREDO', NULL, 7500),
(20, 'KAPET BISIG SA PAGBANGON PROGRAM (NESTLE PHILIPPINES)', NULL, 25000),
(21, 'CHED–TDP SUC-GOKONGWEI', NULL, 7500),
(22, 'BROTHER FOUNDATION INC. (GBFI)', NULL, 10000),
(23, 'CONG. PAGLAS EDUCATIONAL GRANT', NULL, 5000),
(24, 'MAYOR RAFSANJANI P. ALI EDUCATIONAL GRANT', NULL, 2000);

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `role` enum('super_admin','admin','encoder') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
-- Default Logins:
-- 1. Super Admin: superadmin@sksu.edu.ph / superadmin123
-- 2. Admin:       admin@gmail.com        / admin123
-- 3. Encoder:     vonessonvergara@sksu.edu.ph / 123456
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `profile_photo`, `role`, `created_at`) VALUES
(1, 'Super Admin', 'superadmin@sksu.edu.ph', '$2y$10$j6qmp/T/hm86aF1qD15UnuAJXQGCUuf.jmFP.FILYX7V7jBfoYRW.', NULL, 'super_admin', '2025-11-29 16:43:10'),
(2, 'Admin', 'admin@gmail.com', '$2y$10$h5hWFKU1UlS5Lg20C291fu/6W3xJj..KnaMbuYo3KQ3npjfGiXbxW', NULL, 'admin', '2025-11-30 12:18:24'),
(3, 'ENCODER VON', 'vonessonvergara@sksu.edu.ph', '$2y$10$zS6P/WZZoYSInPnWOZ1RjeDeEd2SVmhKLd2u4sF/0TEiIWXrNNUOS', NULL, 'encoder', '2025-12-05 06:29:29');

-- --------------------------------------------------------
-- Table structure for table `scholars`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `scholars`;
CREATE TABLE `scholars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `last_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_initial` varchar(5) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `course` varchar(150) DEFAULT NULL,
  `campus_id` int(11) DEFAULT NULL,
  `scholarship_id` int(11) DEFAULT NULL,
  `encoded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `campus_id` (`campus_id`),
  KEY `scholarship_id` (`scholarship_id`),
  KEY `encoded_by` (`encoded_by`),
  CONSTRAINT `scholars_ibfk_1` FOREIGN KEY (`campus_id`) REFERENCES `campuses` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `scholars_ibfk_2` FOREIGN KEY (`scholarship_id`) REFERENCES `scholarships` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `scholars_ibfk_3` FOREIGN KEY (`encoded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scholars`
--

INSERT INTO `scholars` (`id`, `last_name`, `first_name`, `middle_initial`, `year_level`, `course`, `campus_id`, `scholarship_id`, `encoded_by`, `created_at`) VALUES
(4, 'Justine Carl', 'Rosas', 'D', '3rd Year', 'Bachelor of Science in Information Technology (BSIT)', 3, 18, 2, '2025-12-06 13:30:16'),
(7, 'Tucayao', 'Jemark', 'Loro', '3rd Year', 'Bachelor of Science in Information Technology (BSIT)', 3, 4, 1, '2025-12-15 07:18:43'),
(18, 'Vergara', 'Von Esson', 'A.', '3rd Year', 'Bachelor of Science in Information Technology (BSIT)', 3, 13, 1, '2025-12-18 04:25:16'),
(19, 'Dela Cruz', 'Juan', 'P', '3rd Year', 'BS Computer Science', 1, 1, 1, '2025-12-18 05:26:17'),
(20, 'Santos', 'Maria', 'L', '2nd Year', 'BS Information Technology', 7, 18, 1, '2025-12-18 05:26:17'),
(21, 'Reyes', 'Pedro', 'M', '4th Year', 'BS Accountancy', 3, 2, 1, '2025-12-18 05:26:17');

-- --------------------------------------------------------
-- Table structure for table `audit_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 1, 'Added new scholar', 'Added scholar: Vergara, Von (ID: 1)', '2025-11-29 17:19:29'),
(2, 1, 'Added new user: Admin (admin) - ID: 2', NULL, '2025-11-30 12:18:24'),
(3, 1, 'Updated system settings', 'Updated system configuration', '2025-11-30 12:52:28'),
(4, 1, 'Updated system settings', 'Updated system configuration', '2025-11-30 12:52:32'),
(5, 1, 'Updated system settings', 'Updated system configuration', '2025-11-30 12:52:49'),
(6, 1, 'Added new user: ENCODER VON (encoder) - ID: 3', NULL, '2025-12-05 06:29:29'),
(7, 2, 'Added new scholar', 'Added scholar: Belonghilot, Joshua (ID: 2)', '2025-12-05 07:39:02'),
(8, 2, 'Imported 4 scholars via CSV', NULL, '2025-12-06 13:30:16'),
(9, 1, 'Added new scholar', 'Added scholar: Tucayao, Jemark (ID: 7)', '2025-12-15 07:18:43'),
(10, 1, 'Imported 3 scholars via CSV', NULL, '2025-12-15 07:19:05'),
(11, 2, 'Updated scholarship: UNIFAST FREE EDUCATION (ID: 14)', 'Updated scholarship information', '2025-12-17 02:39:59'),
(12, 1, 'Deleted scholar ID: 6', NULL, '2025-12-17 02:42:54'),
(13, 1, 'Deleted scholar ID: 3', NULL, '2025-12-17 02:43:03'),
(14, 1, 'Deleted scholar ID: 1', NULL, '2025-12-17 02:43:08'),
(15, 1, 'Deleted scholar ID: 5', NULL, '2025-12-17 02:43:11'),
(16, 1, 'Deleted scholar ID: 2', NULL, '2025-12-17 02:43:14'),
(17, 1, 'Deleted scholar ID: 9', NULL, '2025-12-17 02:43:19'),
(18, 1, 'Deleted scholar ID: 10', NULL, '2025-12-17 02:43:26'),
(19, 1, 'Deleted scholar ID: 8', NULL, '2025-12-17 02:43:30'),
(20, 1, 'Updated user: ENCODER VON', NULL, '2025-12-17 02:51:25'),
(21, 1, 'Updated user: ENCODER VON', NULL, '2025-12-17 02:51:31'),
(22, 3, 'Added new scholar', 'Added scholar: Natividad, Jhomel (ID: 11)', '2025-12-17 02:56:11'),
(23, 3, 'Imported 3 scholars via CSV', NULL, '2025-12-17 03:04:21'),
(24, 1, 'Imported 3 scholars via CSV', NULL, '2025-12-17 05:01:35'),
(25, 1, 'Deleted scholar ID: 15', NULL, '2025-12-18 04:24:19'),
(26, 1, 'Deleted scholar ID: 16', NULL, '2025-12-18 04:24:22'),
(27, 1, 'Deleted scholar ID: 17', NULL, '2025-12-18 04:24:28'),
(28, 1, 'Deleted scholar ID: 12', NULL, '2025-12-18 04:24:33'),
(29, 1, 'Deleted scholar ID: 13', NULL, '2025-12-18 04:24:36'),
(30, 1, 'Deleted scholar ID: 14', NULL, '2025-12-18 04:24:41'),
(31, 1, 'Deleted scholar ID: 11', NULL, '2025-12-18 04:24:46'),
(32, 1, 'Added new scholar', 'Added scholar: Vergara, Von Esson (ID: 18)', '2025-12-18 04:25:16'),
(33, 1, 'Updated scholar: Justine Carl, Rosas (ID: 4)', NULL, '2025-12-18 04:25:29'),
(34, 1, 'Imported 3 scholars via CSV', NULL, '2025-12-18 05:26:17'),
(35, 1, 'Updated user: ENCODER VON', NULL, '2025-12-18 05:30:59');

--
-- Set AUTO_INCREMENT for tables
--
ALTER TABLE `campuses` AUTO_INCREMENT = 8;
ALTER TABLE `scholarships` AUTO_INCREMENT = 25;
ALTER TABLE `users` AUTO_INCREMENT = 4;
ALTER TABLE `scholars` AUTO_INCREMENT = 22;
ALTER TABLE `audit_logs` AUTO_INCREMENT = 36;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
