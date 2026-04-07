-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 09:01 PM
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
-- Database: `macj_pest_control`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
-- (For reports/rescheduling, retained in admin)
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `client_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time` time NOT NULL,
  `kind_of_place` varchar(50) NOT NULL,
  `location_address` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `technician_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'assigned',
  `pest_problems` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_report`
-- (For chemical recommendations and assessments)
--

CREATE TABLE `assessment_report` (
  `report_id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `end_time` time NOT NULL,
  `area` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `attachments` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pest_types` varchar(255) DEFAULT NULL,
  `problem_area` varchar(255) DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `chemical_recommendations` text DEFAULT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_time` time DEFAULT NULL,
  `frequency` enum('one-time','weekly','monthly','quarterly') DEFAULT 'one-time',
  `type_of_work` varchar(255) DEFAULT NULL,
  `report_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chemical_inventory`
-- (For chemical management)
--

CREATE TABLE `chemical_inventory` (
  `id` int(11) NOT NULL,
  `chemical_name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` enum('Liters','Kilograms','Grams','Pieces') NOT NULL,
  `manufacturer` varchar(255) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `safety_info` text DEFAULT NULL,
  `expiration_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) GENERATED ALWAYS AS (case when `quantity` <= 0 then 'Out of Stock' when `quantity` < 10 then 'Low Stock' else 'In Stock' end) STORED,
  `target_pest` varchar(255) DEFAULT NULL,
  `dosage_modifier` decimal(5,2) NOT NULL DEFAULT 1.00 COMMENT 'Modifier for dosage calculation (default: 1.00, meaning 100% of standard dosage)',
  `dilution_rate` decimal(10,2) DEFAULT NULL COMMENT 'Dilution rate in ml per liter',
  `area_coverage` decimal(10,2) DEFAULT 100.00 COMMENT 'Area coverage in square meters per liter of diluted solution'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
-- (OPTIONAL: For landing testimonials. DELETE IF NOT NEEDED)
--

CREATE TABLE `clients` (
  `client_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `location_address` varchar(255) DEFAULT NULL,
  `type_of_place` varchar(50) DEFAULT NULL,
  `location_lat` varchar(20) DEFAULT NULL,
  `location_lng` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_order`
-- (For reports/rescheduling, retained in admin)
--

CREATE TABLE `job_order` (
  `job_order_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `type_of_work` varchar(50) NOT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `frequency` enum('one-time','weekly','monthly','quarterly') NOT NULL DEFAULT 'one-time',
  `client_approval_status` enum('pending','approved','declined','one-time') NOT NULL DEFAULT 'pending',
  `client_approval_date` datetime DEFAULT NULL,
  `chemical_recommendations` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT 'scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_order_checklists`
-- (For checklists in assessments)
--

CREATE TABLE `job_order_checklists` (
  `id` int(11) NOT NULL,
  `job_order_id` int(11) NOT NULL,
  `technician_id` int(11) NOT NULL,
  `type_of_work` varchar(100) NOT NULL,
  `checked_items` text NOT NULL,
  `checked_tools` text DEFAULT '[]',
  `total_items` int(11) NOT NULL DEFAULT 0,
  `checked_count` int(11) NOT NULL DEFAULT 0,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `joborder_feedback`
-- (For landing testimonials)
--

CREATE TABLE `joborder_feedback` (
  `feedback_id` int(11) NOT NULL,
  `job_order_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `technician_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `technician_arrived` tinyint(1) NOT NULL DEFAULT 0,
  `job_completed` tinyint(1) NOT NULL DEFAULT 0,
  `verification_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
-- (For admin notifications)
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('client','technician','admin') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `related_type` varchar(50) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `office_staff`
-- (For admin users)
--

CREATE TABLE `office_staff` (
  `staff_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pest_checkboxes`
-- (For pest options)
--

CREATE TABLE `pest_checkboxes` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
-- (For service listings on landing and admin)
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fa-spray-can',
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tools_equipment`
-- (For tool management)
--

CREATE TABLE `tools_equipment` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('in stock','in use') NOT NULL DEFAULT 'in stock'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_types`
-- (For work type definitions)
--

CREATE TABLE `work_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `assessment_report`
--
ALTER TABLE `assessment_report`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `appointment_id` (`appointment_id`);

--
-- Indexes for table `chemical_inventory`
--
ALTER TABLE `chemical_inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `job_order`
--
ALTER TABLE `job_order`
  ADD PRIMARY KEY (`job_order_id`),
  ADD KEY `report_id` (`report_id`);

--
-- Indexes for table `job_order_checklists`
--
ALTER TABLE `job_order_checklists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_order_id` (`job_order_id`),
  ADD KEY `technician_id` (`technician_id`);

--
-- Indexes for table `joborder_feedback`
--
ALTER TABLE `joborder_feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD UNIQUE KEY `job_order_id` (`job_order_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `technician_id` (`technician_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_type` (`user_type`),
  ADD KEY `is_read` (`is_read`);

--
-- Indexes for table `office_staff`
--
ALTER TABLE `office_staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `pest_checkboxes`
--
ALTER TABLE `pest_checkboxes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `tools_equipment`
--
ALTER TABLE `tools_equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `work_types`
--
ALTER TABLE `work_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_report`
--
ALTER TABLE `assessment_report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chemical_inventory`
--
ALTER TABLE `chemical_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_order`
--
ALTER TABLE `job_order`
  MODIFY `job_order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_order_checklists`
--
ALTER TABLE `job_order_checklists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `joborder_feedback`
--
ALTER TABLE `joborder_feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `office_staff`
--
ALTER TABLE `office_staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pest_checkboxes`
--
ALTER TABLE `pest_checkboxes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `service_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tools_equipment`
--
ALTER TABLE `tools_equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_types`
--
ALTER TABLE `work_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`);

--
-- Constraints for table `assessment_report`
--
ALTER TABLE `assessment_report`
  ADD CONSTRAINT `assessment_report_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`);

--
-- Constraints for table `job_order`
--
ALTER TABLE `job_order`
  ADD CONSTRAINT `job_order_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `assessment_report` (`report_id`);

--
-- Constraints for table `joborder_feedback`
--
ALTER TABLE `joborder_feedback`
  ADD CONSTRAINT `joborder_feedback_ibfk_1` FOREIGN KEY (`job_order_id`) REFERENCES `job_order` (`job_order_id`),
  ADD CONSTRAINT `joborder_feedback_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;