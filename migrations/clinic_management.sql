-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 15, 2026 at 10:26 AM
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
(1, 1, 'treat', 'chbkhfchkjcf', 'hadache', 'btrnbn,mr', 'rhkgjkethjhetl', 'reghkgtrjtr', 'etywtywy', NULL, 1, '2026-10-14 09:49:30');

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
(1, 1, '2026-10-14', 'basad', 'kjhjdlhkdhkhdfl;kgjjkldfhkgs', 1, '2026-10-14 09:54:36');

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
(1, 1, 10000.00, 500.00, 'pay directly', 1, '2026-10-14 09:52:54');

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
(1, 1, 'trhbhtr', 'errrrrty', 'Results Available', 1, '2026-10-14 09:49:30');

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
(1, 1, 'parcetammor', 'a286f0f1799b18fd5e1e74652141d2231b9210ff.jpg', 1, '2026-10-14 09:50:26');

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
(16, 6, 'Paracetamol', '500 mg', 200, 0.10, 1),
(17, 6, 'Amoxicillin', '500 mg', 100, 0.35, 1),
(18, 6, 'Ibuprofen', '200 mg', 100, 0.15, 1),
(19, 6, 'malaria', '3', 5, 3000.00, 1);

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
(10, 6, 'PT-261014-AFAD3D', 'party', '2026-10-14', 'Male', '0789722814', 'basagradjabu694@gmailcom', 'Rubavu', 'yes', 'yes', '2026-10-14 12:18:33');

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
(1, 1, 10501.75, 'Airtel Money', '0789722814', 1, '2026-10-14 09:53:36');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `visit_id` bigint(20) UNSIGNED NOT NULL,
  `prescribed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Pending','Partially Dispensed','Dispensed') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`id`, `visit_id`, `prescribed_by`, `notes`, `status`, `created_at`) VALUES
(1, 1, 1, NULL, 'Dispensed', '2026-10-14 09:51:58');

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
(1, 1, 2, '3', '1 week', 5, '1 night ,1 day', 5);

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
(6, '2026-10-15', 1);

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
(10, 15, 60.00, 160.00, '40', 20.0, 14, 30.00, 'malaria', 5, '2026-10-15 06:54:10');

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
(5, 6, 'nibishaka Radjab', 'basagradjabu694@gmail.com', '$2y$10$1VhgD06.LV84oMqLYSmbMOqhyZSIkAwG4NHu1BYJf9XD6IBdsCVUK', 'Admin', 1, '2026-10-14 12:17:18'),
(6, 6, 'basag nanone', 'basagnanone@gmail.com', '$2y$10$7.JLrSbHVk6HQWqbe.rjl.3venct242hNuHSwCbdK2VvR2ysE41vu', 'Doctor', 1, '2026-10-14 13:10:42'),
(9, 6, 'nurse', 'nurse', '$2y$10$qiBoLfY4bMc9iAjgbMDv1.BXi4LsHF.m5GEHnG8FjTk50blqfc6CW', 'Nurse', 1, '2026-10-15 06:14:18'),
(10, 6, 'receptionist', 'receptionist', '$2y$10$f6ccibWluf9ulVtSPD1ZBellfjz6KPzovCWxaPgKqRHuC7QRYItBG', 'Receptionist', 1, '2026-10-15 06:15:14'),
(11, 6, 'laboratory', 'laboratory', '$2y$10$t2RwcRiJ0k//2liV972PXO8hVian7hWuPbJPlVFVeo4VhqTijdvDe', 'Laboratory', 1, '2026-10-15 06:16:15'),
(12, 6, 'pharmacist', 'pharmacist', '$2y$10$eHyBgcTik7PpVw9uy/fT..62evD00goDh8RxbNCtsc547PmnrNoua', 'Pharmacist', 1, '2026-10-15 06:16:56');

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
(15, 6, 10, 1, '2026-10-15', 'In Consultation', 'sickness', '2026-10-15 06:43:14', NULL, 10);

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
  ADD KEY `fk_prescription_user` (`prescribed_by`);

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
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `follow_ups`
--
ALTER TABLE `follow_ups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `form_drafts`
--
ALTER TABLE `form_drafts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lab_orders`
--
ALTER TABLE `lab_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lab_results`
--
ALTER TABLE `lab_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `prescription_items`
--
ALTER TABLE `prescription_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `triage`
--
ALTER TABLE `triage`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

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
