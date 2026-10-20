-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 16, 2026 at 08:59 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clinic_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `clinics`
--

CREATE TABLE `clinics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clinics`
--

INSERT INTO `clinics` (`id`, `name`, `slug`, `active`, `created_at`) VALUES
(1, 'Main Clinic', 'main-clinic', 1, '2026-10-14 12:11:39'),
(6, 'ineza', 'ineza', 1, '2026-10-14 12:17:18');

-- --------------------------------------------------------

--
-- Table structure for table `consultations`
--

CREATE TABLE `consultations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `visit_id` bigint(20) UNSIGNED NOT NULL,
  `chief_complaint` text DEFAULT NULL,
  `medical_history` text DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `examination` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment_plan` text DEFAULT NULL,
  `final_assessment` text DEFAULT NULL,
  `lab_reviewed_at` datetime(6) DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consultations`
--

INSERT INTO `consultations` (`id`, `visit_id`, `chief_complaint`, `medical_history`, `symptoms`, `examination`, `diagnosis`, `treatment_plan`, `final_assessment`, `lab_reviewed_at`, `recorded_by`, `updated_at`) VALUES
(1, 1, 'treat', 'chbkhfchkjcf', 'hadache', 'btrnbn,mr', 'rhkgjkethjhetl', 'reghkgtrjtr', 'etywtywy', NULL, 1, '2026-10-14 09:49:30'),
(7, 15, 'adwaye malaria', 'iribwa umutwe umuriro', 'umuriro mwinshi', 'ntaryakozwe', 'malaria', 'icyumweru', 'akeneye imiti', '2026-10-15 09:51:34.885638', 5, '2026-10-15 08:51:34'),
(10, 23, 'ghhgjk', 'nbbnbn', 'h', '5y46w4', 'jjhkh', 'hkghkg', 'hkgkhj', '2026-10-15 11:39:08.432117', 6, '2026-10-15 10:39:08'),
(12, 24, 'malaria', 'kjhvj', 'tjkrhgvjklt4', 'vhjbecvhkheg', 'vethrvrhvh', 'ctthchtct', 'tchvjychhctchh', '2026-10-15 12:36:19.787877', 12, '2026-10-15 11:36:19'),
(20, 25, 'yatangiye aribwa umutwe', 'none', 'kuribwa umutwe', 'kumufatata amaraso', 'umutwe', 'unomunsi', 'arwaye umutwe', '2026-10-15 14:48:00.614015', 6, '2026-10-15 13:48:00');

-- --------------------------------------------------------

--
-- Table structure for table `follow_ups`
--

CREATE TABLE `follow_ups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `visit_id` bigint(20) UNSIGNED NOT NULL,
  `scheduled_date` date NOT NULL,
  `specialist` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `follow_ups`
--

INSERT INTO `follow_ups` (`id`, `visit_id`, `scheduled_date`, `specialist`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, '2026-10-14', 'basad', 'kjhjdlhkdhkhdfl;kgjjkldfhkgs', 1, '2026-10-14 09:54:36'),
(3, 15, '2026-10-15', 'basag', 'rate us please', 5, '2026-10-15 08:54:17'),
(4, 23, '2026-10-15', 'basag', 'lnmmmmmmmmmm', 6, '2026-10-15 10:46:25');

-- --------------------------------------------------------

--
-- Table structure for table `form_drafts`
--

CREATE TABLE `form_drafts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `clinic_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `visit_id` bigint(20) UNSIGNED DEFAULT NULL,
  `draft_key` varchar(100) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `form_drafts`
--

INSERT INTO `form_drafts` (`id`, `clinic_id`, `user_id`, `visit_id`, `draft_key`, `payload`, `updated_at`) VALUES
(210, 6, 12, 24, 'prescribe:24', '{\"medicine_id\":\"19\",\"dosage\":\"66778\",\"duration\":\"8\",\"quantity\":\"99\",\"instructions\":\"77\"}', '2026-10-15 12:30:24');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `visit_id` bigint(20) UNSIGNED NOT NULL,
  `consultation_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `other_charges` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `visit_id`, `consultation_fee`, `other_charges`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 10000.00, 500.00, 'pay directly', 1, '2026-10-14 09:52:54'),
(5, 15, 100.00, 2000.00, 'pay directly to 0789722814', 5, '2026-10-15 08:53:09'),
(6, 23, 3000.00, 3000.00, 'pay directly', 6, '2026-10-15 10:41:56'),
(7, 24, 1000.00, 4000.00, 'pay directly', 15, '2026-10-15 12:39:58'),
(11, 25, 2000.00, 7000.00, 'pay directly in order to get medecine', 15, '2026-10-15 13:53:56');

-- --------------------------------------------------------

--
-- Table structure for table `lab_orders`
--

CREATE TABLE `lab_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `visit_id` bigint(20) UNSIGNED NOT NULL,
  `test_name` varchar(150) NOT NULL,
  `instructions` text DEFAULT NULL,
  `status` enum('Ordered','In Progress','Results Available') NOT NULL DEFAULT 'Ordered',
  `requested_by` bigint(20) UNSIGNED DEFAULT NULL,
  `ordered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_orders`
--

INSERT INTO `lab_orders` (`id`, `visit_id`, `test_name`, `instructions`, `status`, `requested_by`, `ordered_at`) VALUES
(1, 1, 'trhbhtr', 'errrrrty', 'Results Available', 1, '2026-10-14 09:49:30'),
(5, 15, 'singobwa', 'bamuhe imiti', 'Results Available', 5, '2026-10-15 08:47:29'),
(6, 15, 'palacetamour', 'yes', 'Results Available', 5, '2026-10-15 08:49:58'),
(7, 23, 'bkgj', 'fjhgkhhjnmnmnm,', 'Results Available', 6, '2026-10-15 10:35:24'),
(8, 24, 'chhtyhhcd', 'cvhvtyhvyvyc', 'Results Available', 12, '2026-10-15 11:15:31'),
(9, 24, 'guefy', 'guufffe', 'Results Available', 12, '2026-10-15 11:16:31'),
(10, 24, 'bjjb,m', 'kbnmm', 'Results Available', 12, '2026-10-15 11:17:14'),
(11, 25, 'ni ngobwa', 'urebe ko ari muzima', 'Results Available', 6, '2026-10-15 13:38:31'),
(12, 25, 'umuvuduko wamaraso', 'akwiriye imiti', 'Results Available', 6, '2026-10-15 13:43:04'),
(13, 25, 'umuvuduko wamaraso', 'avurwe', 'Results Available', 6, '2026-10-15 13:44:59');

-- --------------------------------------------------------

--
-- Table structure for table `lab_results`
--

CREATE TABLE `lab_results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `lab_order_id` bigint(20) UNSIGNED NOT NULL,
  `result_text` text NOT NULL,
  `report_file` varchar(255) DEFAULT NULL,
  `entered_by` bigint(20) UNSIGNED DEFAULT NULL,
  `entered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_results`
--

INSERT INTO `lab_results` (`id`, `lab_order_id`, `result_text`, `report_file`, `entered_by`, `entered_at`) VALUES
(1, 1, 'parcetammor', 'a286f0f1799b18fd5e1e74652141d2231b9210ff.jpg', 1, '2026-10-14 09:50:26'),
(5, 5, 'fever,hadache', NULL, 5, '2026-10-15 08:49:13'),
(6, 6, 'yes', NULL, 5, '2026-10-15 08:50:58'),
(7, 7, 'gh', NULL, 11, '2026-10-15 10:37:57'),
(8, 8, 't4fggg', NULL, 12, '2026-10-15 11:15:59'),
(9, 9, 'y4gggggggggru', NULL, 12, '2026-10-15 11:16:51'),
(10, 10, 'bbbb', NULL, 12, '2026-10-15 11:18:31'),
(11, 11, 'umuvuduko wamaraso', NULL, 11, '2026-10-15 13:42:10'),
(12, 12, 'yego', NULL, 11, '2026-10-15 13:43:49'),
(13, 13, 'yes', NULL, 11, '2026-10-15 13:46:47');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `clinic_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `strength` varchar(80) DEFAULT NULL,
  `stock_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `clinic_id`, `name`, `strength`, `stock_quantity`, `unit_price`, `active`) VALUES
(1, 1, 'Paracetamol', '500 mg', 200, 0.10, 1),
(2, 1, 'Amoxicillin', '500 mg', 95, 0.35, 1),
(3, 1, 'Ibuprofen', '200 mg', 100, 0.15, 1),
(16, 6, 'Paracetamol', '500 mg', 195, 0.10, 1),
(17, 6, 'Amoxicillin', '500 mg', 89, 0.35, 1),
(18, 6, 'Ibuprofen', '200 mg', 99, 0.15, 1),
(19, 6, 'malaria', '3', 15, 3000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `clinic_id` bigint(20) UNSIGNED NOT NULL,
  `patient_number` varchar(30) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Female','Male','Other') NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `insurance_provider` varchar(150) DEFAULT NULL,
  `insurance_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `clinic_id`, `patient_number`, `full_name`, `date_of_birth`, `gender`, `phone`, `email`, `address`, `insurance_provider`, `insurance_number`, `created_at`) VALUES
(1, 1, 'PT-261014-ABD30E', 'nibishaka Radjab', NULL, 'Female', NULL, 'nibishakaradjab5@gmail.com', NULL, 'yes', NULL, '2026-10-14 09:36:09'),
(10, 6, 'PT-261014-AFAD3D', 'party', '2026-10-14', 'Male', '0789722814', 'basagradjabu694@gmailcom', 'Rubavu', 'yes', 'yes', '2026-10-14 12:18:33'),
(17, 6, 'PT-261015-CFAD9A', 'nibishaka Radjab', '1980-02-15', 'Male', '0789722814', 'nibishakaradjab5@gmail.com', 'Rubavu', 'yes', 'yes', '2026-10-15 10:18:04'),
(18, 6, 'PT-261015-A7389A', 'nibishaka Radjab', '1980-02-15', 'Male', '0789722814', 'nibishakaradjab5@gmail.com', 'Rubavu', 'yes', 'yes', '2026-10-15 11:10:59');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Card','Insurance','Airtel Money','Other') NOT NULL,
  `reference` varchar(120) DEFAULT NULL,
  `received_by` bigint(20) UNSIGNED DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `invoice_id`, `amount`, `payment_method`, `reference`, `received_by`, `paid_at`) VALUES
(1, 1, 10501.75, 'Airtel Money', '0789722814', 1, '2026-10-14 09:53:36'),
(4, 5, 2103.50, 'Airtel Money', '0789722814', 5, '2026-10-15 08:53:42'),
(5, 6, 6000.50, 'Airtel Money', '0789722814', 6, '2026-10-15 10:43:22'),
(6, 7, 5000.15, 'Airtel Money', '0789722814', 15, '2026-10-15 12:40:21'),
(12, 11, 9000.35, 'Airtel Money', '0789722814', 15, '2026-10-15 13:54:30');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `visit_id` bigint(20) UNSIGNED NOT NULL,
  `prescribed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Pending','Prepared','Partially Dispensed','Dispensed') NOT NULL DEFAULT 'Pending',
  `prepared_by` bigint(20) UNSIGNED DEFAULT NULL,
  `prepared_at` datetime DEFAULT NULL,
  `dispensed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `dispensed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `visit_id`, `prescribed_by`, `notes`, `status`, `prepared_by`, `prepared_at`, `dispensed_by`, `dispensed_at`, `created_at`) VALUES
(1, 1, 1, NULL, 'Dispensed', NULL, NULL, NULL, NULL, '2026-10-14 09:51:58'),
(4, 15, 5, NULL, 'Dispensed', NULL, NULL, NULL, NULL, '2026-10-15 08:52:12'),
(5, 23, 6, NULL, 'Dispensed', NULL, NULL, NULL, NULL, '2026-10-15 10:40:15'),
(6, 24, 6, NULL, 'Dispensed', 12, '2026-10-15 13:37:08', 12, '2026-10-15 13:42:26', '2026-10-15 12:31:55'),
(10, 25, 6, NULL, 'Dispensed', 12, '2026-10-15 14:49:40', 12, '2026-10-16 07:54:14', '2026-10-15 13:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `prescription_items`
--

CREATE TABLE `prescription_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `prescription_id` bigint(20) UNSIGNED NOT NULL,
  `medicine_id` bigint(20) UNSIGNED NOT NULL,
  `dosage` varchar(120) NOT NULL,
  `duration` varchar(100) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `instructions` varchar(255) DEFAULT NULL,
  `dispensed_quantity` int(10) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prescription_items`
--

INSERT INTO `prescription_items` (`id`, `prescription_id`, `medicine_id`, `dosage`, `duration`, `quantity`, `instructions`, `dispensed_quantity`) VALUES
(1, 1, 2, '3', '1 week', 5, '1 night ,1 day', 5),
(4, 4, 17, '1', '1 week', 10, '1 night ,1 day', 10),
(5, 5, 16, '3', '1 week', 5, '1 night ,1 day', 5),
(6, 6, 18, '3', '1 week', 1, '1 night ,1 day', 1),
(10, 10, 17, '3', '1 week', 1, '1 night ,1 day', 1);

-- --------------------------------------------------------

--
-- Table structure for table `queue_counters`
--

CREATE TABLE `queue_counters` (
  `clinic_id` bigint(20) UNSIGNED NOT NULL,
  `visit_date` date NOT NULL,
  `last_number` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `queue_counters`
--

INSERT INTO `queue_counters` (`clinic_id`, `visit_date`, `last_number`) VALUES
(1, '2026-10-14', 2),
(6, '2026-10-14', 3),
(6, '2026-10-15', 5),
(6, '2026-10-16', 2);

-- --------------------------------------------------------

--
-- Table structure for table `triage`
--

CREATE TABLE `triage` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `visit_id` bigint(20) UNSIGNED NOT NULL,
  `weight_kg` decimal(6,2) DEFAULT NULL,
  `height_cm` decimal(6,2) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `temperature_c` decimal(4,1) DEFAULT NULL,
  `pulse_bpm` smallint(5) UNSIGNED DEFAULT NULL,
  `oxygen_percent` decimal(5,2) DEFAULT NULL,
  `complaints` text DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `triage`
--

INSERT INTO `triage` (`id`, `visit_id`, `weight_kg`, `height_cm`, `blood_pressure`, `temperature_c`, `pulse_bpm`, `oxygen_percent`, `complaints`, `recorded_by`, `recorded_at`) VALUES
(1, 1, 180.00, 49.00, '70', 20.0, 45, 36.00, 'very serious', 1, '2026-10-14 09:45:26'),
(6, 11, 60.00, 180.00, '70', 26.0, 10, 30.00, 'vhjklehrgklkjte;k;tej;k;j', 5, '2026-10-14 12:19:43'),
(8, 12, 62.00, 180.00, '60', 30.0, 15, 23.00, 'very serious', 5, '2026-10-14 12:31:22'),
(9, 14, 65.00, 180.00, '60', 20.0, 14, 30.00, 'bnbmvbv', 6, '2026-10-14 13:12:17'),
(10, 15, 60.00, 160.00, '40', 20.0, 14, 30.00, 'malaria', 5, '2026-10-15 06:54:10'),
(12, 23, 70.00, 180.00, '80', 26.0, 15, 30.00, 'sdhksdjklsajghsggshdjshd', 10, '2026-10-15 10:19:17'),
(13, 24, 50.00, 130.00, '60', 30.0, 15, 30.00, 'vhjgjlhj', 12, '2026-10-15 11:11:26'),
(18, 25, 60.00, 160.00, '60', 10.0, 15, 36.00, 'hadache', 9, '2026-10-15 13:35:18');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `clinic_id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','Receptionist','Nurse','Doctor','Laboratory','Pharmacist','Cashier') NOT NULL DEFAULT 'Receptionist',
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `clinic_id`, `full_name`, `username`, `password_hash`, `role`, `active`, `created_at`) VALUES
(1, 1, 'basag radjab', 'basagradjabu694@gmail.com', '$2y$10$f50EqnboGRE7jltMAd5mI.ceKYN/vI7qqCLYykBZG1EXnMiuVZ2U.', 'Admin', 1, '2026-10-14 09:34:28'),
(5, 6, 'admin', 'admin', '$2y$10$lYnCDTgnQMdiJqGsTeINMuPqbo7UMaD9W3iS0NcqW4PyC/l1yWlcy', 'Admin', 1, '2026-10-14 12:17:18'),
(6, 6, 'doctor', 'doctor', '$2y$10$dBbX0PTYWgHpY6dxkOM7Wud50OTZSN00.Fulx/59AUI1IbeccjvJW', 'Doctor', 1, '2026-10-14 13:10:42'),
(9, 6, 'nurse', 'nurse', '$2y$10$lNH0A65eWWf1LFIaYsZbdeJjV0Wa4rL1yGGUKT50i.KFdfE31a/DK', 'Nurse', 1, '2026-10-15 06:14:18'),
(10, 6, 'receptionist', 'receptionist', '$2y$10$jRpLurOzmO3P0cwCEky17OoqmbPi4VVz6UxV7bgvjEoQkU4RtrKd.', 'Receptionist', 1, '2026-10-15 06:15:14'),
(11, 6, 'laboratory', 'laboratory', '$2y$10$IzRigex6sTPrCQpyfqSR..AoURAVla6c2joD76Sf9PwsaGj7/79vO', 'Laboratory', 1, '2026-10-15 06:16:15'),
(12, 6, 'pharmacist', 'pharmacist', '$2y$10$eHyBgcTik7PpVw9uy/fT..62evD00goDh8RxbNCtsc547PmnrNoua', 'Pharmacist', 1, '2026-10-15 06:16:56'),
(15, 6, 'caissier', 'caissier', '$2y$10$7CtNE9sCMzZf5fsCLft8gOx9uttl8Xs5Bdsp94eiGw/2v48YAYoVW', 'Cashier', 1, '2026-10-15 11:00:01');

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `clinic_id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `queue_number` int(10) UNSIGNED NOT NULL,
  `visit_date` date NOT NULL,
  `status` enum('Waiting','Ready for Doctor','In Consultation','Waiting for Results','Results Available','Ready for Pharmacy','Ready for Billing','Follow-up Scheduled','Completed') NOT NULL DEFAULT 'Waiting',
  `reason_for_visit` varchar(500) DEFAULT NULL,
  `checked_in_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `clinic_id`, `patient_id`, `queue_number`, `visit_date`, `status`, `reason_for_visit`, `checked_in_at`, `closed_at`, `created_by`) VALUES
(1, 1, 1, 1, '2026-10-14', 'Completed', 'sickness', '2026-10-14 09:36:09', '2026-10-14 10:54:47', 1),
(7, 1, 1, 2, '2026-10-14', 'Waiting', 'sickness', '2026-10-14 10:15:07', NULL, 1),
(11, 6, 10, 1, '2026-10-14', 'In Consultation', 'sickness', '2026-10-14 12:18:33', NULL, 5),
(12, 6, 10, 2, '2026-10-14', 'Ready for Doctor', 'sickness', '2026-10-14 12:26:31', NULL, 5),
(14, 6, 10, 3, '2026-10-14', 'In Consultation', 'sickness', '2026-10-14 13:11:28', NULL, 6),
(15, 6, 10, 1, '2026-10-15', 'Completed', 'sickness', '2026-10-15 06:43:14', '2026-10-15 09:54:53', 10),
(23, 6, 17, 2, '2026-10-15', 'Follow-up Scheduled', 'sickness', '2026-10-15 10:18:04', NULL, 10),
(24, 6, 18, 3, '2026-10-15', 'Ready for Billing', 'sickness', '2026-10-15 11:10:59', NULL, 12),
(25, 6, 17, 4, '2026-10-15', 'Ready for Billing', 'sickness', '2026-10-15 13:01:56', NULL, 10),
(30, 6, 10, 5, '2026-10-15', 'Waiting', NULL, '2026-10-15 14:44:29', NULL, 10),
(31, 6, 18, 1, '2026-10-16', 'Waiting', NULL, '2026-10-16 06:58:06', NULL, 5),
(32, 6, 10, 2, '2026-10-16', 'Waiting', NULL, '2026-10-16 06:58:16', NULL, 5);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `visit_id` (`visit_id`),
  ADD KEY `fk_consult_user` (`recorded_by`);

--
-- Indexes for table `follow_ups`
--
ALTER TABLE `follow_ups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_followup_visit` (`visit_id`),
  ADD KEY `fk_followup_user` (`created_by`);

--
-- Indexes for table `form_drafts`
--
ALTER TABLE `form_drafts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_form_drafts_owner_key` (`clinic_id`,`user_id`,`draft_key`),
  ADD KEY `fk_form_drafts_user` (`user_id`),
  ADD KEY `fk_form_drafts_visit` (`visit_id`),
  ADD KEY `idx_form_drafts_visit` (`clinic_id`,`user_id`,`visit_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `visit_id` (`visit_id`),
  ADD KEY `fk_invoice_user` (`created_by`);

--
-- Indexes for table `lab_orders`
--
ALTER TABLE `lab_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lab_visit` (`visit_id`),
  ADD KEY `fk_lab_user` (`requested_by`),
  ADD KEY `idx_lab_status` (`status`);

--
-- Indexes for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lab_order_id` (`lab_order_id`),
  ADD KEY `fk_result_user` (`entered_by`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_medicines_clinic_name` (`clinic_id`,`name`),
  ADD UNIQUE KEY `uq_medicines_clinic_id` (`clinic_id`,`id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_patients_clinic_id` (`clinic_id`,`id`),
  ADD UNIQUE KEY `uq_patients_clinic_number` (`clinic_id`,`patient_number`),
  ADD KEY `idx_patient_name` (`full_name`),
  ADD KEY `idx_patient_phone` (`phone`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_user` (`received_by`),
  ADD KEY `idx_payment_invoice` (`invoice_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prescription_visit` (`visit_id`),
  ADD KEY `fk_prescription_user` (`prescribed_by`),
  ADD KEY `fk_prescription_preparer` (`prepared_by`),
  ADD KEY `fk_prescription_dispenser` (`dispensed_by`);

--
-- Indexes for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_medicine` (`medicine_id`),
  ADD KEY `idx_item_prescription` (`prescription_id`);

--
-- Indexes for table `queue_counters`
--
ALTER TABLE `queue_counters`
  ADD PRIMARY KEY (`clinic_id`,`visit_date`);

--
-- Indexes for table `triage`
--
ALTER TABLE `triage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `visit_id` (`visit_id`),
  ADD KEY `fk_triage_user` (`recorded_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_clinic_username` (`clinic_id`,`username`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_daily_queue` (`clinic_id`,`visit_date`,`queue_number`),
  ADD KEY `fk_visits_user` (`created_by`),
  ADD KEY `idx_visit_queue` (`clinic_id`,`visit_date`,`status`,`queue_number`),
  ADD KEY `fk_visits_patient` (`clinic_id`,`patient_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `follow_ups`
--
ALTER TABLE `follow_ups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `form_drafts`
--
ALTER TABLE `form_drafts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=347;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `lab_orders`
--
ALTER TABLE `lab_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `lab_results`
--
ALTER TABLE `lab_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `prescription_items`
--
ALTER TABLE `prescription_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `triage`
--
ALTER TABLE `triage`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `consultations`
--
ALTER TABLE `consultations`
  ADD CONSTRAINT `fk_consult_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_consult_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `follow_ups`
--
ALTER TABLE `follow_ups`
  ADD CONSTRAINT `fk_followup_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_followup_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `form_drafts`
--
ALTER TABLE `form_drafts`
  ADD CONSTRAINT `fk_form_drafts_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_form_drafts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_form_drafts_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoice_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_invoice_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_orders`
--
ALTER TABLE `lab_orders`
  ADD CONSTRAINT `fk_lab_user` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_lab_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_results`
--
ALTER TABLE `lab_results`
  ADD CONSTRAINT `fk_result_order` FOREIGN KEY (`lab_order_id`) REFERENCES `lab_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_result_user` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `medicines`
--
ALTER TABLE `medicines`
  ADD CONSTRAINT `fk_medicines_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`);

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patients_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payment_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `fk_prescription_dispenser` FOREIGN KEY (`dispensed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prescription_preparer` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prescription_user` FOREIGN KEY (`prescribed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prescription_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prescription_items`
--
ALTER TABLE `prescription_items`
  ADD CONSTRAINT `fk_item_medicine` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`),
  ADD CONSTRAINT `fk_item_prescription` FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `queue_counters`
--
ALTER TABLE `queue_counters`
  ADD CONSTRAINT `fk_queue_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`);

--
-- Constraints for table `triage`
--
ALTER TABLE `triage`
  ADD CONSTRAINT `fk_triage_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_triage_visit` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`);

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `fk_visits_clinic` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`),
  ADD CONSTRAINT `fk_visits_patient` FOREIGN KEY (`clinic_id`,`patient_id`) REFERENCES `patients` (`clinic_id`, `id`),
  ADD CONSTRAINT `fk_visits_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
