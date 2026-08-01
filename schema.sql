-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 27, 2026 at 11:25 PM
-- Server version: 10.11.18-MariaDB
-- PHP Version: 8.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `obhsbeatleanalyt_OBHS`
--

-- --------------------------------------------------------

--
-- Table structure for table `app_version`
--

CREATE TABLE `app_version` (
  `id` int(11) NOT NULL,
  `version` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `base_attendance`
--

CREATE TABLE `base_attendance` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(100) NOT NULL,
  `employee_name` varchar(100) DEFAULT NULL,
  `station_id` int(11) DEFAULT NULL,
  `type_of_attendance` varchar(100) DEFAULT NULL,
  `train_no` varchar(100) NOT NULL,
  `desination` varchar(100) NOT NULL,
  `grade` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `photo` varchar(100) DEFAULT NULL,
  `toc` varchar(255) DEFAULT NULL,
  `employee_name_unique` varchar(255) DEFAULT NULL,
  `created_by` varchar(20) NOT NULL DEFAULT 'APP',
  `fullLocation` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `base_employees`
--

CREATE TABLE `base_employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(255) NOT NULL,
  `name` text NOT NULL,
  `station` varchar(255) NOT NULL,
  `desination` varchar(255) NOT NULL,
  `photo` text NOT NULL,
  `station_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `DOB` varchar(255) DEFAULT NULL,
  `AADHAR_NO` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `base_employees_jodhpur`
--

CREATE TABLE `base_employees_jodhpur` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(100) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `station` varchar(100) DEFAULT NULL,
  `desination` varchar(100) DEFAULT NULL,
  `photo` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `REN_ID` text DEFAULT NULL,
  `Rakshak_ID` text DEFAULT NULL,
  `FATHER_NAME` text DEFAULT NULL,
  `Police_ver` varchar(255) DEFAULT NULL,
  `Police_ver_dt` text DEFAULT NULL,
  `MOBILE_NO` text DEFAULT NULL,
  `ADHAR_NO` text DEFAULT NULL,
  `DOB` text DEFAULT NULL,
  `FORMULA_DOB` text DEFAULT NULL,
  `AGE` text DEFAULT NULL,
  `ADDRESH` text DEFAULT NULL,
  `PVC` text DEFAULT NULL,
  `PVC_Ok_Applied` text DEFAULT NULL,
  `PVC_Issue_Month` text DEFAULT NULL,
  `MEDICAL` text DEFAULT NULL,
  `MEDICAL_ISSUE_MONTH` text DEFAULT NULL,
  `PAN_CARD` text DEFAULT NULL,
  `AC_NAME` text DEFAULT NULL,
  `AC_NO` text DEFAULT NULL,
  `IFSC_CODE` text DEFAULT NULL,
  `EDU` text DEFAULT NULL,
  `Doc_Status` text DEFAULT NULL,
  `REMARK` text DEFAULT NULL,
  `STATUS` text DEFAULT NULL,
  `Issue_Date` text DEFAULT NULL,
  `Valid_Upto_date` text DEFAULT NULL,
  `FORMULA_Valid_Upto` text DEFAULT NULL,
  `Valid_Upto_Month` text DEFAULT NULL,
  `DOCUMENT_LINK` text DEFAULT NULL,
  `notification` text DEFAULT NULL,
  `station_id` int(11) NOT NULL DEFAULT 17
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `base_fb_target`
--

CREATE TABLE `base_fb_target` (
  `id` int(11) NOT NULL,
  `train_no` varchar(100) NOT NULL,
  `no_ac_coach` int(11) NOT NULL,
  `feed_per_ac_coach` int(11) NOT NULL,
  `no_non_ac_coach` int(11) NOT NULL,
  `feed_per_non_ac_coach` int(11) NOT NULL,
  `feedback_tte` int(11) DEFAULT NULL,
  `station` char(32) NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `base_photo_report`
--

CREATE TABLE `base_photo_report` (
  `id` int(11) NOT NULL,
  `train_no` varchar(10) NOT NULL,
  `grade` varchar(100) NOT NULL,
  `coach_no` varchar(100) NOT NULL,
  `coach_type` varchar(100) NOT NULL,
  `station_id` char(32) NOT NULL,
  `date` datetime NOT NULL DEFAULT current_timestamp(),
  `photo` varchar(100) NOT NULL,
  `cleaning_area` varchar(100) DEFAULT NULL,
  `time_of_cleaning` varchar(100) DEFAULT NULL,
  `janitor` varchar(100) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `location_link` varchar(150) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `OBHS_divisions`
--

CREATE TABLE `OBHS_divisions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `zone_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `OBHS_feedback`
--

CREATE TABLE `OBHS_feedback` (
  `unique_no` int(11) NOT NULL,
  `id` char(64) DEFAULT NULL,
  `feed_param` varchar(100) NOT NULL,
  `value` double NOT NULL,
  `passenger_id` char(64) NOT NULL,
  `super_name` varchar(100) DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `OBHS_Globaladvertisment`
--

CREATE TABLE `OBHS_Globaladvertisment` (
  `id` int(11) NOT NULL,
  `info` text NOT NULL,
  `image` text NOT NULL,
  `date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `OBHS_marking`
--

CREATE TABLE `OBHS_marking` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category` varchar(255) NOT NULL,
  `value` varchar(10) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `OBHS_passenger`
--

CREATE TABLE `OBHS_passenger` (
  `unique_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `id` char(64) NOT NULL,
  `ph_number` varchar(100) NOT NULL,
  `pnr_number` varchar(100) NOT NULL,
  `seat_no` int(11) NOT NULL,
  `coach_no` varchar(100) NOT NULL,
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `train_no` varchar(100) NOT NULL,
  `coach_type` varchar(100) NOT NULL,
  `station_id` char(32) NOT NULL,
  `grade` varchar(100) NOT NULL,
  `remark` varchar(100) DEFAULT NULL,
  `photo` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `verified` int(11) NOT NULL,
  `created_by` varchar(255) NOT NULL DEFAULT 'app'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `OBHS_questions`
--

CREATE TABLE `OBHS_questions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `eng_question` text NOT NULL,
  `hin_question` text NOT NULL,
  `type` varchar(255) NOT NULL,
  `station_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `OBHS_reports`
--

CREATE TABLE `OBHS_reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reports_name` varchar(255) NOT NULL,
  `link` varchar(255) NOT NULL,
  `app_link` varchar(255) DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `station_id` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1 COMMENT '	1 means=Active 0 means not active	',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `OBHS_station`
--

CREATE TABLE `OBHS_station` (
  `station_id` int(11) NOT NULL,
  `Zone_id` int(11) NOT NULL,
  `Division_id` int(11) NOT NULL,
  `station_name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `url` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `OBHS_Station_POPUP`
--

CREATE TABLE `OBHS_Station_POPUP` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `info` text NOT NULL,
  `image` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `OBHS_users`
--

CREATE TABLE `OBHS_users` (
  `user_id` int(11) NOT NULL,
  `organisation_name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `mobile` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `station_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `app_password` varchar(255) DEFAULT '$2y$10$e80ejz1Y7XYV099iZ/kzfe.T.gLOz/NdoIbAn1EoCyp2ab7qC8x4q' COMMENT 'by default password 123456',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `type` int(11) NOT NULL COMMENT 'type 1= admin, 2= organisation , 3=app login\r\n',
  `status` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `url` int(11) NOT NULL DEFAULT 1,
  `PNR` int(11) NOT NULL COMMENT 'pnr functionality 0 means not active 1 means active\r\n',
  `pnr_skip` int(11) NOT NULL DEFAULT 1 COMMENT 'pnr skip functionality 1 means show skip option 0 means off',
  `otp` int(11) NOT NULL DEFAULT 1,
  `otp_skip` int(11) NOT NULL DEFAULT 1,
  `photo` int(11) NOT NULL DEFAULT 1,
  `photo_skip` int(11) NOT NULL DEFAULT 1,
  `no_of_train` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `OBHS_zones`
--

CREATE TABLE `OBHS_zones` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pdf_attendence`
--

CREATE TABLE `pdf_attendence` (
  `id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `train_up` varchar(20) NOT NULL,
  `train_down` varchar(20) NOT NULL,
  `from_date` date NOT NULL,
  `pdf_file` varchar(255) NOT NULL,
  `created_by` varchar(30) DEFAULT 'BACKEND',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `app_version`
--
ALTER TABLE `app_version`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `base_attendance`
--
ALTER TABLE `base_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_station_date` (`station_id`,`created_at`);

--
-- Indexes for table `base_employees`
--
ALTER TABLE `base_employees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_station_id` (`station_id`);

--
-- Indexes for table `base_employees_jodhpur`
--
ALTER TABLE `base_employees_jodhpur`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `base_fb_target`
--
ALTER TABLE `base_fb_target`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_statio_id` (`station`);

--
-- Indexes for table `base_photo_report`
--
ALTER TABLE `base_photo_report`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_station_createdat` (`station_id`,`created_at`);

--
-- Indexes for table `OBHS_divisions`
--
ALTER TABLE `OBHS_divisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zone_id` (`zone_id`);

--
-- Indexes for table `OBHS_feedback`
--
ALTER TABLE `OBHS_feedback`
  ADD PRIMARY KEY (`unique_no`),
  ADD KEY `passenger_id` (`passenger_id`),
  ADD KEY `idx_feed_passenger` (`feed_param`,`passenger_id`);

--
-- Indexes for table `OBHS_Globaladvertisment`
--
ALTER TABLE `OBHS_Globaladvertisment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `OBHS_marking`
--
ALTER TABLE `OBHS_marking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `OBHS_passenger`
--
ALTER TABLE `OBHS_passenger`
  ADD PRIMARY KEY (`unique_id`),
  ADD KEY `idx_station_date` (`station_id`,`created`);

--
-- Indexes for table `OBHS_questions`
--
ALTER TABLE `OBHS_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `OBHS_reports`
--
ALTER TABLE `OBHS_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `OBHS_reports_ibfk_1` (`user_id`);

--
-- Indexes for table `OBHS_station`
--
ALTER TABLE `OBHS_station`
  ADD PRIMARY KEY (`station_id`),
  ADD KEY `Zone_id` (`Zone_id`),
  ADD KEY `Division_id` (`Division_id`);

--
-- Indexes for table `OBHS_Station_POPUP`
--
ALTER TABLE `OBHS_Station_POPUP`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `OBHS_users`
--
ALTER TABLE `OBHS_users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `OBHS_zones`
--
ALTER TABLE `OBHS_zones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pdf_attendence`
--
ALTER TABLE `pdf_attendence`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_station_id` (`station_id`),
  ADD KEY `idx_date_range` (`from_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `app_version`
--
ALTER TABLE `app_version`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `base_attendance`
--
ALTER TABLE `base_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `base_employees`
--
ALTER TABLE `base_employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `base_employees_jodhpur`
--
ALTER TABLE `base_employees_jodhpur`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `base_fb_target`
--
ALTER TABLE `base_fb_target`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `base_photo_report`
--
ALTER TABLE `base_photo_report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `OBHS_divisions`
--
ALTER TABLE `OBHS_divisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `OBHS_feedback`
--
ALTER TABLE `OBHS_feedback`
  MODIFY `unique_no` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `OBHS_Globaladvertisment`
--
ALTER TABLE `OBHS_Globaladvertisment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `OBHS_marking`
--
ALTER TABLE `OBHS_marking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `OBHS_passenger`
--
ALTER TABLE `OBHS_passenger`
  MODIFY `unique_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `OBHS_questions`
--
ALTER TABLE `OBHS_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `OBHS_reports`
--
ALTER TABLE `OBHS_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `OBHS_station`
--
ALTER TABLE `OBHS_station`
  MODIFY `station_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `OBHS_Station_POPUP`
--
ALTER TABLE `OBHS_Station_POPUP`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `OBHS_users`
--
ALTER TABLE `OBHS_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `OBHS_zones`
--
ALTER TABLE `OBHS_zones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pdf_attendence`
--
ALTER TABLE `pdf_attendence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `OBHS_divisions`
--
ALTER TABLE `OBHS_divisions`
  ADD CONSTRAINT `OBHS_divisions_ibfk_1` FOREIGN KEY (`zone_id`) REFERENCES `OBHS_zones` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `OBHS_questions`
--
ALTER TABLE `OBHS_questions`
  ADD CONSTRAINT `OBHS_questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `OBHS_users` (`user_id`);

--
-- Constraints for table `OBHS_reports`
--
ALTER TABLE `OBHS_reports`
  ADD CONSTRAINT `OBHS_reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `OBHS_users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `OBHS_station`
--
ALTER TABLE `OBHS_station`
  ADD CONSTRAINT `OBHS_station_ibfk_1` FOREIGN KEY (`Zone_id`) REFERENCES `OBHS_zones` (`id`),
  ADD CONSTRAINT `OBHS_station_ibfk_2` FOREIGN KEY (`Division_id`) REFERENCES `OBHS_divisions` (`id`);

--
-- Constraints for table `OBHS_Station_POPUP`
--
ALTER TABLE `OBHS_Station_POPUP`
  ADD CONSTRAINT `OBHS_Station_POPUP_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `OBHS_users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
