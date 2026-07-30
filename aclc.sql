-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 09, 2026 at 02:14 PM
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
-- Database: `aclc`
--

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `student_type` varchar(20) NOT NULL DEFAULT 'New',
  `course` enum('BSIT','BSCS','BSHM','BSBA') NOT NULL,
  `year_level` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `payment_status` enum('PENDING','PAID','FAILED') NOT NULL DEFAULT 'PENDING',
  `is_accepted` enum('PENDING','ACCEPTED') NOT NULL DEFAULT 'PENDING',
  `gcash_ref` varchar(100) DEFAULT NULL,
  `enrollment_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('enrolled','inactive') DEFAULT 'enrolled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_id`, `first_name`, `last_name`, `email`, `phone`, `student_type`, `course`, `year_level`, `address`, `date_of_birth`, `payment_status`, `is_accepted`, `gcash_ref`, `enrollment_date`, `status`) VALUES
(25, 'STU20260120124946', 'KIAN DAVE', 'BORJA', 'kiandave@gmail.com', '+639683857079', 'Old', 'BSHM', NULL, 'Lapu2', '2020-01-01', 'PAID', 'ACCEPTED', '09683857079', '2026-01-20 11:49:46', 'enrolled'),
(26, 'STU20260120125016', 'HAROLD JAKE', 'PATINDOL', 'haroldjake@gmail.com', '+639683857055', 'Transferee', 'BSHM', NULL, 'canduman', '2005-02-02', 'PAID', 'ACCEPTED', '09683857022', '2026-01-20 11:50:16', 'enrolled'),
(27, 'STU20260120125741', 'EARL LAWRENCE', 'LLEGO', 'admin@aclc.com', '+639683857055', 'Old', 'BSHM', NULL, 'Cebu', '2001-01-01', 'PAID', 'ACCEPTED', '09683857022', '2026-01-20 11:57:41', 'enrolled');

-- --------------------------------------------------------

--
-- Table structure for table `student_loads`
--

CREATE TABLE `student_loads` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `schedule` varchar(100) DEFAULT NULL,
  `instructor` varchar(100) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `day` varchar(50) DEFAULT NULL,
  `grade` decimal(3,2) DEFAULT NULL,
  `status` enum('enrolled','dropped') DEFAULT 'enrolled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_loads`
--

INSERT INTO `student_loads` (`id`, `student_id`, `subject_id`, `schedule`, `instructor`, `room`, `start_time`, `end_time`, `day`, `grade`, `status`, `created_at`) VALUES
(78, 25, 6, NULL, 'SIR TAGALOG', '100', '06:00:00', '07:00:00', 'Monday', NULL, 'enrolled', '2026-01-20 11:50:22'),
(79, 25, 3, NULL, 'SIR BISAYA', '101', '08:00:00', '09:00:00', 'Tuesday', NULL, 'enrolled', '2026-01-20 11:50:22'),
(80, 25, 4, NULL, 'SIR INSIK', '102', '10:00:00', '11:00:00', 'Wednesday', NULL, 'enrolled', '2026-01-20 11:50:22'),
(81, 25, 5, NULL, 'SIR HAPON', '103', '13:00:00', '14:00:00', 'Thursday', NULL, 'enrolled', '2026-01-20 11:50:22'),
(82, 25, 1, NULL, 'SIR RUSSIA', '104', '15:00:00', '16:00:00', 'Friday', NULL, 'enrolled', '2026-01-20 11:50:22'),
(83, 25, 2, NULL, 'SIR FRANCE', '105', '17:00:00', '18:00:00', 'Saturday', NULL, 'enrolled', '2026-01-20 11:50:22'),
(84, 26, 6, NULL, 'SIR TAGALOG', '100', '06:00:00', '07:00:00', 'Monday', NULL, 'enrolled', '2026-01-20 11:50:30'),
(85, 26, 3, NULL, 'SIR BISAYA', '101', '08:00:00', '09:00:00', 'Tuesday', NULL, 'enrolled', '2026-01-20 11:50:30'),
(86, 26, 4, NULL, 'SIR INSIK', '102', '10:00:00', '11:00:00', 'Wednesday', NULL, 'enrolled', '2026-01-20 11:50:30'),
(87, 26, 5, NULL, 'SIR HAPON', '103', '13:00:00', '14:00:00', 'Thursday', NULL, 'enrolled', '2026-01-20 11:50:30'),
(88, 26, 1, NULL, 'SIR RUSSIA', '104', '15:00:00', '16:00:00', 'Friday', NULL, 'enrolled', '2026-01-20 11:50:30'),
(89, 26, 2, NULL, 'SIR FRANCE', '105', '17:00:00', '18:00:00', 'Saturday', NULL, 'enrolled', '2026-01-20 11:50:30'),
(90, 27, 6, NULL, 'SIR TAGALOG', '100', '06:00:00', '07:00:00', 'Monday', NULL, 'enrolled', '2026-01-20 11:57:44'),
(91, 27, 3, NULL, 'SIR BISAYA', '101', '08:00:00', '09:00:00', 'Tuesday', NULL, 'enrolled', '2026-01-20 11:57:44'),
(92, 27, 4, NULL, 'SIR INSIK', '102', '10:00:00', '11:00:00', 'Wednesday', NULL, 'enrolled', '2026-01-20 11:57:44'),
(93, 27, 5, NULL, 'SIR HAPON', '103', '13:00:00', '14:00:00', 'Thursday', NULL, 'enrolled', '2026-01-20 11:57:44'),
(94, 27, 1, NULL, 'SIR RUSSIA', '104', '15:00:00', '16:00:00', 'Friday', NULL, 'enrolled', '2026-01-20 11:57:44'),
(95, 27, 2, NULL, 'SIR FRANCE', '105', '17:00:00', '18:00:00', 'Saturday', NULL, 'enrolled', '2026-01-20 11:57:44');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(200) NOT NULL,
  `course` enum('BSIT','BSCS','BSHM','BSBA') NOT NULL,
  `units` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`, `course`, `units`, `created_at`) VALUES
(1, 'IT101', 'Introduction to Programming', 'BSIT', 3, '2025-11-22 01:34:40'),
(2, 'IT201', 'Web Development', 'BSIT', 3, '2025-11-22 01:34:40'),
(3, 'CS101', 'Data Structures', 'BSCS', 3, '2025-11-22 01:34:40'),
(4, 'CS201', 'Algorithms', 'BSCS', 3, '2025-11-22 01:34:40'),
(5, 'HM101', 'Hotel Management Basics', 'BSHM', 3, '2025-11-22 01:34:40'),
(6, 'BA101', 'Business Administration', 'BSBA', 3, '2025-11-22 01:34:40');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'earllawrencellego@gmail.com', '21232f297a57a5a743894a0e4a801fc3', 'admin', '2026-01-20 11:26:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `student_loads`
--
ALTER TABLE `student_loads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `student_loads`
--
ALTER TABLE `student_loads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=96;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `student_loads`
--
ALTER TABLE `student_loads`
  ADD CONSTRAINT `student_loads_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `student_loads_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
