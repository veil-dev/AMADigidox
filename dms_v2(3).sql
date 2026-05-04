-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 27, 2026 at 04:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dms_v2`
CREATE DATABASE IF NOT EXISTS `dms_v2` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `dms_v2`;e


-- --------------------------------------------------------

--
-- Table structure for table `doc_definitions`
--

CREATE TABLE `doc_definitions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL,
  `doc_type` varchar(32) NOT NULL COMMENT 'Identity, Academic, Health, etc.',
  `is_required` tinyint(1) DEFAULT 1,
  `student_type` enum('shs','college') NOT NULL DEFAULT 'shs',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `doc_definitions`
--

INSERT INTO `doc_definitions` (`id`, `name`, `doc_type`, `is_required`, `student_type`, `created_at`) VALUES
(1, 'Birth Certificate', 'Identity', 1, 'shs', '2026-04-03 07:48:33'),
(2, 'Junior High School Diploma', 'Academic', 1, 'shs', '2026-04-03 07:48:33'),
(3, 'E-Signature', 'Personal', 1, 'shs', '2026-04-03 07:48:33'),
(4, 'F137', 'Academic', 1, 'shs', '2026-04-03 07:48:33'),
(5, 'F138', 'Academic', 1, 'shs', '2026-04-03 07:48:33'),
(6, 'Good Moral Certificate', 'Academic', 1, 'shs', '2026-04-03 07:48:33'),
(7, 'Work Immersion Certificate of Completion (COC)', 'Academic', 1, 'shs', '2026-04-03 07:48:33'),
(8, '1x1 ID Picture (White Background)', 'Personal', 0, 'shs', '2026-04-03 07:48:33'),
(9, '2x2 ID Picture (White Background)', 'Personal', 0, 'shs', '2026-04-03 07:48:33'),
(10, 'Current School ID', 'Identity', 0, 'shs', '2026-04-03 07:48:33'),
(12, 'ID from Previous School', 'Identity', 0, 'shs', '2026-04-03 07:48:33'),
(13, 'COR from Previous School', 'Academic', 0, 'shs', '2026-04-03 07:48:33'),
(16, 'Birth Certificate', 'Identity', 1, 'college', '2026-04-04 14:02:45'),
(18, 'E-Signature', 'Personal', 1, 'college', '2026-04-04 14:02:45'),
(19, 'F137', 'Academic', 1, 'college', '2026-04-04 14:02:45'),
(20, 'F138', 'Academic', 1, 'college', '2026-04-04 14:02:45'),
(21, 'Good Moral Certificate', 'Academic', 1, 'college', '2026-04-04 14:02:45'),
(23, '1x1 ID Picture (White Background)', 'Personal', 0, 'college', '2026-04-04 14:02:45'),
(24, '2x2 ID Picture (White Background)', 'Personal', 0, 'college', '2026-04-04 14:02:45'),
(25, 'Current School ID', 'Identity', 0, 'college', '2026-04-04 14:02:45'),
(27, 'ID from Previous School', 'Identity', 0, 'college', '2026-04-04 14:02:45'),
(28, 'COR from Previous School', 'Academic', 0, 'college', '2026-04-04 14:02:45'),
(31, 'Senior High School Diploma', 'Academic', 1, 'college', '2026-04-04 14:08:22'),
(40, 'Other Document 1', 'Other', 0, 'shs', '2026-04-05 07:50:19'),
(41, 'Other Document 2', 'Other', 0, 'shs', '2026-04-05 07:50:19'),
(42, 'Other Document 3', 'Other', 0, 'shs', '2026-04-05 07:50:19'),
(43, 'Other Document 1', 'Other', 0, 'college', '2026-04-05 07:50:19'),
(44, 'Other Document 2', 'Other', 0, 'college', '2026-04-05 07:50:19'),
(45, 'Other Document 3', 'Other', 0, 'college', '2026-04-05 07:50:19');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(64) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(128) NOT NULL,
  `role` enum('admin','registrar','officer','viewer') NOT NULL DEFAULT 'registrar',
  `email` varchar(128) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `username`, `password_hash`, `full_name`, `role`, `email`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$ziVwn.q5946HwNmKB.403.buGO0VDN7CfFHSVC/5empyKfwtHpa7e', 'System Administrator', 'admin', 'admin@ama.edu.ph', 1, '2026-04-03 10:35:51', '2026-04-03 11:28:13'),
(2, 'Registrar', '$2y$10$DmOFMWQ0VRRF5naL9.Ztb.IKI8NwcZQsRmEooGUIjBpfvUus9YSeq', 'Registrar', 'registrar', 'juan@email.com', 1, '2026-04-03 11:30:02', '2026-04-03 12:06:02');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_code` varchar(32) NOT NULL,
  `name` varchar(128) NOT NULL,
  `grade` varchar(32) NOT NULL,
  `deadline` date NOT NULL,
  `color_index` tinyint(3) UNSIGNED DEFAULT 0,
  `note` text DEFAULT NULL,
  `email` varchar(128) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usn` varchar(11) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_code`, `name`, `grade`, `deadline`, `color_index`, `note`, `email`, `created_at`, `updated_at`, `usn`, `password_hash`) VALUES
(16, 'SHS-2026-7786', 'test', 'Grade 12', '0000-00-00', 0, NULL, 'test@test.gmail.com', '2026-04-04 14:14:49', '2026-04-04 14:14:49', '24000000000', '$2y$10$7jBMrXi9koDG/0m13qop0.lNXrFfYQwde3AxG2H2v220s4EA1NNDC'),
(17, 'COL-2026-6363', 'John College', 'College', '0000-00-00', 0, NULL, 'john@college.com', '2026-04-04 15:41:56', '2026-04-04 15:41:56', '35000000000', '$2y$10$UeubmZ2W4KBLYY6DKikiTOP2y6eEhOR9fMlSXTRZFOtUf2r/ew3Ca'),
(19, 'SHS-2026-8298', 'kyle', 'Grade 11', '0000-00-00', 0, NULL, 'gomezkyledenver@gmail.com', '2026-04-05 10:22:26', '2026-04-05 10:33:20', '24000120110', '$2y$10$PGMnCLlJ.jfZpnFRmAtGquYF2FlAA.Z5o1CrMVb273SXZ8iProZ1a'),
(20, 'SHS-2026-9244', 'test', 'Grade 11', '0000-00-00', 0, NULL, 'test@test.com', '2026-04-09 06:32:34', '2026-04-09 06:32:34', '24000000001', '$2y$10$lbkdq1Yd1dTMtAqdiJs3kuFOZTby7I8YioXXoURWXaxt1B5Ni//D.'),
(21, 'SHS-2026-2557', 'test', 'Grade 11', '0000-00-00', 0, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04', '22000000000', '$2y$10$gd7F8uBdyfUsky0I79C7/uIt0zfOwpASXcvNJ.pro/ixdYjSwVSAK'),
(22, '', 'test', 'Grade 11', '2025-12-31', 0, '', NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31', '24120000000', '$2y$10$Cn9gibNZX0KA/CO8hODHcOaAhJiy/agRztowsZAyuCcSFSy6QSBC.'),
(27, 'SHS-2026-0157', 'pog', 'Grade 12', '2025-12-31', 0, '', NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13', '10000000000', '$2y$10$KlvJLpAKXgNV/dSTcVGG9ukZbMKnIOov3WPk0E0TtEUjihbVJRcw.');

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

CREATE TABLE `student_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `doc_def_id` int(10) UNSIGNED NOT NULL,
  `custom_name` varchar(128) DEFAULT NULL COMMENT 'Custom name for Other documents',
  `status` enum('uploaded','reviewing','missing','expired') NOT NULL DEFAULT 'missing',
  `uploaded_date` varchar(32) DEFAULT NULL,
  `file_size` varchar(32) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL COMMENT 'Optional file storage path',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_documents`
--

INSERT INTO `student_documents` (`id`, `student_id`, `doc_def_id`, `custom_name`, `status`, `uploaded_date`, `file_size`, `file_path`, `created_at`, `updated_at`) VALUES
(463, 16, 1, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 14:14:49', '2026-04-05 07:31:30'),
(464, 16, 2, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 14:14:49', '2026-04-04 14:14:49'),
(465, 16, 3, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 14:14:49', '2026-04-04 14:14:49'),
(466, 16, 4, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 14:14:49', '2026-04-04 14:14:49'),
(467, 16, 5, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 14:14:49', '2026-04-04 14:14:49'),
(468, 16, 6, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 14:14:49', '2026-04-04 14:14:49'),
(469, 16, 7, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 14:14:49', '2026-04-04 14:14:49'),
(470, 16, 8, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 14:14:49', '2026-04-04 14:14:49'),
(471, 16, 9, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 14:14:49', '2026-04-04 14:14:49'),
(472, 16, 10, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 14:14:49', '2026-04-04 14:14:49'),
(474, 16, 12, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 14:14:49', '2026-04-04 14:14:49'),
(475, 16, 13, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 14:14:49', '2026-04-04 14:14:49'),
(488, 17, 16, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 15:41:56', '2026-04-05 08:53:51'),
(489, 17, 18, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 15:41:56', '2026-04-04 15:41:56'),
(490, 17, 19, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 15:41:56', '2026-04-04 15:41:56'),
(491, 17, 20, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 15:41:56', '2026-04-04 15:41:56'),
(492, 17, 21, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 15:41:56', '2026-04-04 15:41:56'),
(493, 17, 23, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 15:41:56', '2026-04-04 15:41:56'),
(494, 17, 24, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 15:41:56', '2026-04-04 15:41:56'),
(495, 17, 25, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 15:41:56', '2026-04-04 15:41:56'),
(497, 17, 27, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 15:41:56', '2026-04-04 15:41:56'),
(498, 17, 28, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 15:41:56', '2026-04-04 15:41:56'),
(499, 17, 31, NULL, 'missing', NULL, NULL, NULL, '2026-04-04 15:41:56', '2026-04-04 15:41:56'),
(525, 16, 41, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 07:50:19', '2026-04-05 07:50:19'),
(527, 16, 42, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 07:50:19', '2026-04-05 07:50:19'),
(532, 17, 44, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 07:50:19', '2026-04-05 07:50:19'),
(534, 17, 45, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 07:50:19', '2026-04-05 07:50:19'),
(556, 19, 1, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(557, 19, 2, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(558, 19, 3, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(559, 19, 4, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(560, 19, 5, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(561, 19, 6, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(562, 19, 7, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(563, 19, 8, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(564, 19, 9, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(565, 19, 10, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(566, 19, 12, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(567, 19, 13, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(568, 19, 40, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(569, 19, 41, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(570, 19, 42, NULL, 'missing', NULL, NULL, NULL, '2026-04-05 10:22:26', '2026-04-05 10:22:26'),
(571, 20, 1, NULL, 'reviewing', 'Apr 9', '223.1 KB', 'uploads/s20_d1_1775716383.pdf', '2026-04-09 06:32:34', '2026-04-09 06:33:03'),
(572, 20, 2, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(573, 20, 3, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(574, 20, 4, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(575, 20, 5, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(576, 20, 6, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(577, 20, 7, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(578, 20, 8, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(579, 20, 9, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(580, 20, 10, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(581, 20, 12, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(582, 20, 13, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(583, 20, 40, 'tyh', 'reviewing', 'Apr 9', '772.8 KB', 'uploads/s20_d0_1775716406.pdf', '2026-04-09 06:32:34', '2026-04-09 06:33:26'),
(584, 20, 41, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(585, 20, 42, NULL, 'missing', NULL, NULL, NULL, '2026-04-09 06:32:34', '2026-04-09 06:32:34'),
(587, 21, 1, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(588, 21, 2, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(589, 21, 3, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(590, 21, 4, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(591, 21, 5, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(592, 21, 6, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(593, 21, 7, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(594, 21, 8, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(595, 21, 9, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(596, 21, 10, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(597, 21, 12, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(598, 21, 13, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(599, 21, 40, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(600, 21, 41, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(601, 21, 42, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:26:04', '2026-04-10 13:26:04'),
(602, 22, 1, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(603, 22, 2, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(604, 22, 3, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(605, 22, 4, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(606, 22, 5, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(607, 22, 6, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(608, 22, 7, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(609, 22, 8, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(610, 22, 9, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(611, 22, 10, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(612, 22, 12, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(613, 22, 13, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(614, 22, 40, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(615, 22, 41, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(616, 22, 42, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 13:42:31', '2026-04-10 13:42:31'),
(617, 27, 1, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(618, 27, 2, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(619, 27, 3, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(620, 27, 4, NULL, 'reviewing', 'Apr 10', '106.4 KB', 'uploads/s27_d4_1775829748.pdf', '2026-04-10 14:02:13', '2026-04-10 14:02:28'),
(621, 27, 5, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(622, 27, 6, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(623, 27, 7, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(624, 27, 8, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(625, 27, 9, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(626, 27, 10, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(627, 27, 12, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(628, 27, 13, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(629, 27, 40, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(630, 27, 41, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13'),
(631, 27, 42, NULL, 'missing', NULL, NULL, NULL, '2026-04-10 14:02:13', '2026-04-10 14:02:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `doc_definitions`
--
ALTER TABLE `doc_definitions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_code` (`student_code`),
  ADD UNIQUE KEY `usn` (`usn`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_deadline` (`deadline`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_doc` (`student_id`,`doc_def_id`),
  ADD KEY `doc_def_id` (`doc_def_id`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `doc_definitions`
--
ALTER TABLE `doc_definitions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=633;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_documents_ibfk_2` FOREIGN KEY (`doc_def_id`) REFERENCES `doc_definitions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
