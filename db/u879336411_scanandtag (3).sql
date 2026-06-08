-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 17, 2025 at 02:51 AM
-- Server version: 11.8.3-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u879336411_La Carlota City Veterinary Office`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role_name` enum('Veterinarian','Staff') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `first_name`, `middle_name`, `last_name`, `role_name`, `created_at`) VALUES
(1, 'admin', '$2y$10$jO6aZRMpii9O4u17aSYObu2lRkxkWD6WIPYv9oJWT4nQ1dkA0bKNK', 'Dr. Carlos Ceasar', '', 'Catabas', 'Veterinarian', '2025-03-17 12:55:46'),
(9, 'cdelacruz', '$2y$10$V8NDX7VyvFzEkcdrYAUdVeb2bSFLJmNOD94nSWTZLEnMydKCdWN/.', 'Carlos', '', 'Dela Cruz', 'Staff', '2025-10-09 03:03:35'),
(10, 'jmendoza', '$2y$10$lE/5wVHEWIjS4ZgZaw8dCuQtoGvLaXa9iiMcpo9covfBkz4Nbr052', 'Jeric', 'Roquero', 'Mendoza', 'Staff', '2025-10-17 06:28:36'),
(11, 'avillanueva', '$2y$10$RluC1xdnQd13JQe.FsquOut7jFFz42h3e66Q8gS7iPpW/iffTfFwy', 'Adrian ', 'Santos', 'Villanueva', 'Staff', '2025-10-17 09:36:55'),
(12, 'mareyes', '$2y$10$94thsBtIv9fujwb7PEtH5eqc0/8J1JRot9W3DjlH7lT.T01gULujC', 'Mark Anthony', 'Lucena', 'Reyes', 'Staff', '2025-10-17 09:38:23'),
(13, 'lfontanilla', '$2y$10$uloVglB.DNTYCrFLECw3QuLtxyttGwvtoQvI5a6NCFnbAjvTdNlwK', 'Liza Marie', 'Leones', 'Fontanilla', 'Staff', '2025-10-28 14:08:42'),
(14, 'fmanalansan', '$2y$10$i.ngxEPjGAstUS2r4MKzz.AwQjao6qwORDux98aNLtXJi.KPhm7wG', 'Faye', 'Cordero', 'Manalansan', 'Staff', '2025-10-28 14:09:42'),
(15, 'caguilar', '$2y$10$x2E22n17f928Sl./5u7tsu16/JF0i6kmBwO9ndqVx4e0jGn5UZsq2', 'Clarisse', '', 'Aguilar', 'Veterinarian', '2025-10-28 14:10:38'),
(16, 'rsantos', '$2y$10$H.7tOpigzYfMNVUIaqrgAeanWEWJxvoo3Pwq4eEJG9bb8wi42gAj6', 'Ramon', '', 'Santos', 'Staff', '2025-10-28 15:02:25'),
(17, 'nadinestaff', '$2y$10$KzmB7KIQslXsZ/fv9hwwLOqmG.aUK7qZxwkRuswZOEuAtqmCcEaz.', 'Nadine Lustre', 'Tamburong', 'Lustre', 'Staff', '2025-11-17 01:44:44');

-- --------------------------------------------------------

--
-- Table structure for table `animals`
--

CREATE TABLE `animals` (
  `animal_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `animal_name` varchar(50) NOT NULL,
  `species` enum('Dog','Cat','Cow','Goat','Carabao') DEFAULT NULL,
  `breed` varchar(50) DEFAULT NULL,
  `birthdate` date NOT NULL,
  `sex` enum('Male','Female') NOT NULL,
  `color` varchar(50) NOT NULL,
  `qr_code` varchar(200) DEFAULT NULL,
  `behavior` varchar(50) NOT NULL,
  `animal_class` enum('Domestic','Livestock') NOT NULL,
  `date_registered` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('lost','found','safe') NOT NULL DEFAULT 'safe',
  `vaccination_status` enum('Not Vaccinated','Vaccinated') NOT NULL,
  `animal_image` varchar(255) NOT NULL,
  `added_by` text DEFAULT NULL,
  `updated_by` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `animals`
--

INSERT INTO `animals` (`animal_id`, `owner_id`, `animal_name`, `species`, `breed`, `birthdate`, `sex`, `color`, `qr_code`, `behavior`, `animal_class`, `date_registered`, `status`, `vaccination_status`, `animal_image`, `added_by`, `updated_by`) VALUES
(116, 33, 'Hugo', 'Cat', 'Persian', '2025-07-08', 'Male', 'brown', 'QR/ID116.png', 'Aggressive', 'Domestic', '2025-09-27 00:00:00', 'lost', 'Vaccinated', 'images/animals/animal_1763346958.jpg', 'Dr. Carlos Ceasar Catabas', NULL),
(117, 33, 'Kumal', 'Dog', 'Aspin', '2025-07-07', 'Male', 'brown and White', 'QR/ID117.png', 'Calm', 'Domestic', '2025-10-04 00:00:00', 'lost', 'Not Vaccinated', 'images/animals/animal_1760071580.jpg', 'Dr. Carlos Ceasar Catabas', NULL),
(118, 36, 'Browny', 'Dog', 'Aspin', '2025-09-02', 'Male', 'brown', 'QR/ID118.png', 'Calm', 'Domestic', '2025-10-09 00:00:00', 'safe', 'Vaccinated', 'images/animals/6913374c4a0e0_360_F_766773931_pMS11EynQKttctMCd6wayLkdezKLZM4W.jpg', 'Dr. Carlos Ceasar Catabas', NULL),
(119, 37, 'Matt', 'Dog', 'Aspin', '2025-08-04', 'Male', 'Brown', 'QR/ID119.png', 'Calm', 'Domestic', '2025-10-09 00:00:00', 'safe', 'Vaccinated', 'images/animals/69134380f3548_pexels-alexeydemidov-11132408.jpg', 'Dr. Carlos Ceasar Catabas', NULL),
(120, 34, 'Kumpol', 'Dog', 'Aspin', '2023-08-07', 'Male', 'White', 'QR/ID120.png', 'Calm', 'Domestic', '2025-10-12 23:42:24', 'safe', 'Vaccinated', 'images/animals/animal_1760312544.jpg', 'Dr. Carlos Ceasar Catabas', NULL),
(121, 38, 'Pichea', 'Dog', 'Japanese Pits Mix Shihtzu', '2023-09-14', 'Female', 'Off White', 'QR/ID121.png', 'Aggressive', 'Domestic', '2025-10-14 00:00:00', 'safe', 'Vaccinated', 'images/animals/691345583f528_Gemini_Generated_Image_ftvi9eftvi9eftvi.png', 'Dr. Carlos Ceasar Catabas', NULL),
(122, 39, 'Pot-pot', 'Cat', 'Mixed', '2021-09-20', 'Male', 'White', 'QR/ID122.png', 'Aggressive', 'Domestic', '2025-10-14 00:00:00', 'safe', 'Vaccinated', 'images/animals/691345b7ebcdb_Gemini_Generated_Image_lnm62ilnm62ilnm6.png', 'Dr. Carlos Ceasar Catabas', NULL),
(123, 40, 'Booger', 'Cat', 'Ginger', '2020-07-30', 'Male', 'Orange White', 'QR/ID123.png', 'Calm', 'Domestic', '2025-10-15 00:00:00', 'safe', 'Not Vaccinated', 'images/animals/6913463dd29c0_Gemini_Generated_Image_4h7y494h7y494h7y.png', 'Dr. Carlos Ceasar Catabas', NULL),
(125, 41, 'Chico', 'Dog', 'Maltese Shih tzu', '2023-11-01', 'Male', 'Brown/White', 'QR/ID125.png', 'Calm', 'Domestic', '2025-10-16 00:00:00', 'safe', 'Vaccinated', 'images/animals/691346fd61e48_Gemini_Generated_Image_pe4pa6pe4pa6pe4p.png', 'Dr. Carlos Ceasar Catabas', NULL),
(126, 43, 'Rocky', 'Dog', 'Shih Tzu Mix', '2022-01-31', 'Male', 'Beige White', 'QR/ID126.png', 'Calm', 'Domestic', '2025-10-16 07:07:55', 'lost', 'Vaccinated', 'images/animals/animal_1760598475.jpeg', 'Dr. Carlos Ceasar Catabas', NULL),
(127, 44, 'Quinos', 'Dog', 'Shih Tzu Mix', '2025-10-16', 'Female', 'Black', 'QR/ID127.png', 'Calm', 'Domestic', '2025-10-16 09:51:59', 'safe', 'Not Vaccinated', 'images/animals/animal_1760608319.jpeg', 'Dr. Carlos Ceasar Catabas', NULL),
(128, 43, 'Quinos', 'Carabao', 'Native Carabao', '2025-10-16', 'Male', 'Black', 'QR/ID128.png', 'Calm', 'Livestock', '2025-10-16 00:00:00', 'found', 'Vaccinated', 'images/animals/691892d7bbdff_Gemini_Generated_Image_o8t39vo8t39vo8t3.png', 'Dr. Carlos Ceasar Catabas', NULL),
(129, 46, 'Hugo', 'Dog', 'Mixed', '2025-02-20', 'Male', 'Brown', 'QR/ID129.png', 'Calm', 'Domestic', '2025-10-17 00:00:00', 'lost', 'Vaccinated', 'images/animals/69189348bbad8_Gemini_Generated_Image_ocz2sbocz2sbocz2.png', 'Dr. Carlos Ceasar Catabas', NULL),
(130, 48, 'Lolita', 'Cat', 'mixed', '2002-11-21', 'Male', 'brown', 'QR/ID130.png', 'Calm', 'Domestic', '2025-10-17 00:00:00', 'found', 'Vaccinated', 'images/animals/6900b12f596d0_0546030f-3d08-4c66-a6a7-6e46d1f74a2b.jpg', 'Dr. Carlos Ceasar Catabas', NULL),
(131, 50, 'Yumi', 'Dog', 'Dutchound', '2020-11-08', 'Female', 'black', 'QR/ID131.png', 'Calm', 'Domestic', '2025-10-21 12:05:37', 'safe', 'Vaccinated', 'images/animals/animal_1761048517.jpg', 'Dr. Carlos Ceasar Catabas', NULL),
(132, 51, 'Gabriel', 'Dog', 'Mini Pin', '2020-01-04', 'Male', 'Brown', 'QR/ID132.png', 'Calm', 'Domestic', '2025-10-28 14:00:53', 'lost', 'Not Vaccinated', 'images/animals/animal_1761660053.jpg', 'Dr. Carlos Ceasar Catabas', NULL),
(133, 41, 'lebron', 'Dog', 'Mixed', '2025-10-06', 'Male', 'White', 'QR/ID133.png', 'Calm', 'Domestic', '2025-10-28 15:18:48', 'safe', 'Not Vaccinated', '', 'Dr. Carlos Ceasar Catabas', NULL),
(134, 36, 'Lukas', 'Cow', 'Philippine Native Cow', '2025-09-30', 'Male', 'White', 'QR/ID134.png', 'Aggressive', 'Livestock', '2025-10-28 00:00:00', 'safe', 'Vaccinated', 'images/animals/691893c091cff_Gemini_Generated_Image_uldb0suldb0suldb.png', 'Jeric Mendoza', NULL),
(135, 46, 'Booger', 'Dog', 'Aspin', '2025-10-01', 'Male', 'Brown/White', 'QR/ID135.png', 'Calm', 'Domestic', '2025-10-29 08:43:05', 'safe', 'Not Vaccinated', '', 'Dr. Carlos Ceasar Catabas', NULL),
(136, 54, 'Percy', 'Dog', 'Labrador', '2020-02-10', 'Male', 'Brown', NULL, 'Calm', 'Domestic', '2025-11-11 00:00:00', 'safe', 'Vaccinated', 'images/animals/6912caf2cdbeb_Gemini_Generated_Image_ocz2sbocz2sbocz2.png', 'Dr. Carlos Ceasar Catabas', NULL),
(137, 54, 'Miso', 'Cat', 'Persian', '2021-05-06', 'Female', 'White', 'QR/ID137.png', 'Calm', 'Domestic', '2025-11-11 05:36:16', 'safe', 'Vaccinated', 'images/animals/animal_1762839376.png', 'Dr. Carlos Ceasar Catabas', NULL),
(138, 55, 'Nena', 'Goat', 'Boer', '2022-04-03', 'Female', 'White and brown (ears part)', 'QR/ID138.png', 'Calm', 'Livestock', '2025-11-11 05:38:13', 'lost', 'Not Vaccinated', 'images/animals/animal_1762839493.png', 'Dr. Carlos Ceasar Catabas', NULL),
(139, 56, 'Kalabaw', 'Carabao', 'Native', '2018-08-05', 'Male', 'Gray', 'QR/ID139.png', 'Calm', 'Livestock', '2025-11-11 05:41:55', 'safe', 'Not Vaccinated', 'images/animals/animal_1762839715.png', 'Dr. Carlos Ceasar Catabas', NULL),
(140, 57, 'Coco', 'Dog', 'Aspin', '2020-02-04', 'Female', 'Black', 'QR/ID140.png', 'Calm', 'Domestic', '2025-11-11 05:45:00', 'safe', 'Vaccinated', 'images/animals/animal_1762839900.png', 'Dr. Carlos Ceasar Catabas', NULL),
(141, 60, 'Gabriel', 'Dog', 'Mini Pinscher', '2020-06-16', 'Male', 'Brown', 'QR/ID141.png', 'Calm', 'Domestic', '2025-11-17 01:33:29', 'safe', 'Vaccinated', '', 'Dr. Carlos Ceasar Catabas', NULL),
(142, 60, 'Kufra', 'Carabao', 'Bisaya', '2025-06-03', 'Female', 'White', 'QR/ID142.png', 'Calm', 'Livestock', '2025-11-17 01:46:30', 'lost', 'Vaccinated', 'images/animals/animal_1763343990.jpg', 'Nadine Lustre Lustre', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `animal_medication`
--

CREATE TABLE `animal_medication` (
  `animed_id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `med_id` int(11) NOT NULL,
  `dosage` varchar(20) NOT NULL,
  `date_given` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `added_by` text DEFAULT NULL,
  `updated_by` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `animal_medication`
--

INSERT INTO `animal_medication` (`animed_id`, `animal_id`, `med_id`, `dosage`, `date_given`, `created_at`, `added_by`, `updated_by`) VALUES
(55, 118, 6, '', '2025-09-22', '2025-10-09 02:57:39', 'Dr. Carlos Ceasar Catabas', NULL),
(56, 118, 7, '', '2025-09-29', '2025-10-09 02:57:39', 'Dr. Carlos Ceasar Catabas', NULL),
(57, 118, 13, '', '2025-10-20', '2025-10-09 02:57:39', 'Dr. Carlos Ceasar Catabas', NULL),
(58, 119, 7, '', '2025-10-09', '2025-10-09 03:22:46', 'Dr. Carlos Ceasar Catabas', NULL),
(59, 120, 6, '', '2024-10-13', '2025-10-12 23:42:24', 'Dr. Carlos Ceasar Catabas', NULL),
(60, 121, 6, '', '2025-07-14', '2025-10-14 01:48:00', 'Dr. Carlos Ceasar Catabas', NULL),
(61, 121, 9, '', '2023-09-28', '2025-10-14 01:48:00', 'Dr. Carlos Ceasar Catabas', NULL),
(62, 122, 6, '', '2024-10-20', '2025-10-14 02:35:34', 'Dr. Carlos Ceasar Catabas', NULL),
(65, 125, 6, '', '2024-02-12', '2025-10-16 04:48:07', 'Dr. Carlos Ceasar Catabas', NULL),
(66, 126, 6, '', '2025-05-17', '2025-10-16 07:07:55', 'Dr. Carlos Ceasar Catabas', NULL),
(67, 126, 12, '', '2025-09-16', '2025-10-16 07:07:55', 'Dr. Carlos Ceasar Catabas', NULL),
(68, 116, 12, '5ml', '2025-10-16', '2025-11-15 00:55:44', 'Dr. Carlos Ceasar Catabas', 'Dr. Carlos Ceasar Catabas'),
(69, 128, 6, '', '2025-10-16', '2025-10-16 11:07:58', 'Dr. Carlos Ceasar Catabas', NULL),
(70, 129, 6, '', '2025-04-29', '2025-10-17 06:17:30', 'Dr. Carlos Ceasar Catabas', NULL),
(71, 129, 9, '', '2025-06-14', '2025-10-17 06:17:30', 'Dr. Carlos Ceasar Catabas', NULL),
(72, 130, 6, '', '2004-11-11', '2025-10-17 09:26:54', 'Dr. Carlos Ceasar Catabas', NULL),
(73, 130, 9, '', '2023-11-30', '2025-10-17 09:26:54', 'Dr. Carlos Ceasar Catabas', NULL),
(74, 131, 9, '', '2025-10-01', '2025-10-21 12:05:37', 'Dr. Carlos Ceasar Catabas', NULL),
(75, 134, 13, '', '2023-11-11', '2025-11-17 01:45:56', 'Jeric Mendoza', NULL),
(76, 136, 6, '', '2024-12-03', '2025-11-11 05:32:01', 'Dr. Carlos Ceasar Catabas', NULL),
(77, 136, 12, '', '2024-11-03', '2025-11-11 05:32:01', 'Dr. Carlos Ceasar Catabas', NULL),
(78, 137, 10, '', '2024-04-04', '2025-11-11 05:36:16', 'Dr. Carlos Ceasar Catabas', NULL),
(79, 140, 6, '', '2024-10-02', '2025-11-11 05:45:00', 'Dr. Carlos Ceasar Catabas', NULL),
(80, 141, 6, '', '2023-01-02', '2025-11-17 01:33:29', 'Dr. Carlos Ceasar Catabas', NULL),
(81, 141, 11, '', '2025-01-08', '2025-11-17 01:33:29', 'Dr. Carlos Ceasar Catabas', NULL),
(82, 142, 12, '', '2025-10-27', '2025-11-17 01:46:30', 'Nadine Lustre Lustre', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `found_reports`
--

CREATE TABLE `found_reports` (
  `report_id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `reporter_name` varchar(100) NOT NULL,
  `reporter_phone` varchar(20) NOT NULL,
  `reporter_address` text NOT NULL,
  `report_time` datetime DEFAULT current_timestamp(),
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `image_proof` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `found_reports`
--

INSERT INTO `found_reports` (`report_id`, `animal_id`, `owner_id`, `reporter_name`, `reporter_phone`, `reporter_address`, `report_time`, `latitude`, `longitude`, `message`, `image_proof`) VALUES
(55, 116, 33, 'Liezel Yanos', '09918179415', 'Purok, Crotons , Brgy. Ayungon', '2025-09-27 06:26:19', 10.4608607, 122.9187128, 'running', ''),
(56, 116, 33, 'Andrea Cawaya', '09615595696', 'Purok 6, Barangay La Granja', '2025-09-27 07:19:29', 10.4052331, 122.9996958, 'Under the tree', ''),
(57, 119, 37, 'Andrea', '09615595696', 'La Granja', '2025-10-09 03:15:57', 10.4243637, 122.9137948, 'under the tree', 'proof_1759979757_24ed4690.jpg'),
(58, 116, 33, 'Andrea Cawaya', '9615595696', 'Purok 6, Barangay La Granja', '2025-10-14 06:56:00', 10.4053622, 122.9993375, 'Under the rambutan tree', ''),
(59, 116, 33, 'Vernaliza Batillano', '+639918179071', 'Brgy. Batuan', '2025-10-14 07:00:12', 10.4288186, 122.9205218, 'Walking beside erigasyon HAHAH', 'proof_1760425212_f77453de.jpg'),
(60, 116, 33, 'Jayvee Francisco Balimiento', '+639123485762', '4th Street FJE Village', '2025-10-14 09:37:46', 10.4606438, 122.9183200, 'dalagan', ''),
(61, 116, 33, 'John Philip Panta Yanos', '+639918179071', 'Crotons', '2025-10-14 17:48:28', 10.4608796, 122.9187046, '', ''),
(62, 116, 33, 'Jade Cawaya', '09615595696', '6th Street', '2025-10-16 14:41:38', 10.6741571, 122.9564872, 'Outside the cafe', 'proof_1760596898_8f093376.jpg'),
(63, 126, 43, 'Andrea', '09615595696', '6th street', '2025-10-16 15:18:22', 10.6741449, 122.9564692, 'Outside the bldg', 'proof_1760599102_427dc4b5.jpg'),
(64, 126, 43, 'test', '0948542424', '21st st', '2025-10-16 18:42:31', 10.6830086, 122.9560023, 'test', 'proof_1760611351_b9fd6a84.jpeg'),
(65, 128, 43, 'Test', '094545454', 'quinos cafe', '2025-10-16 19:11:42', 10.6829993, 122.9559971, 'nakita', 'proof_1760613102_95c37ff2.jpg'),
(66, 129, 46, 'Shania Maglantay', '9668066105', 'Purok Santan, Brgy. Ayungon, La Carlota', '2025-10-17 14:21:50', 10.4593872, 122.9196722, '..', 'proof_1760682110_a401d515.jpg'),
(67, 130, 48, 'Andrea', '09615595696', '1st Street', '2025-10-17 17:29:27', 10.4249557, 122.9216142, 'outside', 'proof_1760693367_816b02a6.jpg'),
(68, 126, 43, 'Edrian Mobisa Ardenio', '+639637931263', 'Hda. Elena', '2025-11-16 23:03:33', 10.4608796, 122.9187285, 'Outside', 'proof_1763305413_71f6ce93.jpg'),
(69, 126, 43, 'Edrian Mobisa Ardenio', '+639637931263', 'Hda. Elena', '2025-11-16 23:03:43', 10.4608796, 122.9187285, 'Outside', 'proof_1763305423_039bc4dd.jpg'),
(70, 126, 43, 'John Philip Panta Yanos', '+639918179071', 'purok Crotons', '2025-11-16 23:04:10', 10.4608481, 122.9187374, 'outside of the gate', 'proof_1763305450_d2158c18.jpg'),
(71, 126, 43, 'Sheena Roquero Panta', '+639931660771', 'Crotons', '2025-11-16 23:04:26', 10.4608246, 122.9188642, 'test', 'proof_1763305466_5906b958.jpg'),
(72, 126, 43, 'Joshua ni', '0945457', 'brgy doz', '2025-11-16 23:04:47', 10.4067804, 122.9425049, '', 'proof_1763305487_e96f4723.jpeg'),
(73, 126, 43, 'joshuaaa', '095184877', 'brgy', '2025-11-16 23:05:20', 0.0000000, 0.0000000, 'wala', 'proof_1763305520_2828bb03.jpeg'),
(74, 126, 43, 'Sheena Roquero Panta', '+639931660771', 'Crotons', '2025-11-16 23:14:15', 10.4608246, 122.9188642, 'test', 'proof_1763306055_39131c43.jpg'),
(75, 126, 43, 'Edrian Mobisa Ardenio', '+639637931263', 'Hda. Elena', '2025-11-16 23:15:18', 10.4608706, 122.9187212, 'Outside', 'proof_1763306118_923e084b.jpg'),
(76, 126, 43, 'Andrea Cawaya', '09615595696', 'Brgy. Alijis, Bacolod City', '2025-11-16 23:39:23', 10.6453988, 122.9390724, 'Outside the gate', 'proof_1763307563_73b7f0b6.jpeg'),
(77, 126, 43, 'joshuaaa', '095184877', 'brgy', '2025-11-17 09:52:00', 0.0000000, 0.0000000, 'wala', 'proof_1763344320_5ba7be37.jpeg'),
(78, 142, 60, 'Jhun July', '095784216', 'My street', '2025-11-17 10:04:18', 10.6707086, 122.9541779, 'ga dalagan', 'proof_1763345058_269477e6.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `lost_found_history`
--

CREATE TABLE `lost_found_history` (
  `lf_id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `updated_by` varchar(50) DEFAULT NULL,
  `update_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lost_found_history`
--

INSERT INTO `lost_found_history` (`lf_id`, `animal_id`, `status`, `updated_by`, `update_date`) VALUES
(13, 116, 'Lost', 'Dr. Carlos Ceasar Catabas', '2025-09-27 06:23:53'),
(14, 117, 'lost', 'John Philip Yanos', '2025-10-07 02:47:27'),
(15, 119, 'lost', 'Joshua Dela Cruz', '2025-10-09 03:13:28'),
(16, 119, 'found', 'Joshua Dela Cruz', '2025-10-09 03:16:49'),
(17, 119, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-10-09 03:17:51'),
(18, 117, 'Found', 'Dr. Carlos Ceasar Catabas', '2025-10-13 05:06:11'),
(19, 117, 'lost', 'John Philip Yanos', '2025-10-13 05:13:41'),
(20, 116, 'found', 'John Philip Yanos', '2025-10-13 16:12:12'),
(21, 121, 'lost', 'Jgar Nebrija', '2025-10-14 01:48:50'),
(22, 121, 'found', 'Jgar Nebrija', '2025-10-14 01:50:31'),
(23, 121, 'safe', 'Jgar Nebrija', '2025-10-14 01:50:38'),
(24, 116, 'lost', 'John Philip Yanos', '2025-10-14 06:52:32'),
(25, 117, 'found', 'John Philip Yanos', '2025-10-14 10:26:08'),
(26, 126, 'lost', 'Mary Beatrice Trixia Tupino', '2025-10-16 07:11:58'),
(27, 126, 'found', 'Mary Beatrice Trixia Tupino', '2025-10-16 07:21:54'),
(28, 126, 'safe', 'Mary Beatrice Trixia Tupino', '2025-10-16 07:21:58'),
(29, 126, 'lost', 'Mary Beatrice Trixia Tupino', '2025-10-16 09:54:37'),
(30, 126, 'found', 'Mary Beatrice Trixia Tupino', '2025-10-16 10:11:02'),
(31, 126, 'safe', 'Mary Beatrice Trixia Tupino', '2025-10-16 10:11:05'),
(32, 126, 'lost', 'Mary Beatrice Trixia Tupino', '2025-10-16 10:17:03'),
(33, 126, 'found', 'Mary Beatrice Trixia Tupino', '2025-10-16 10:39:22'),
(34, 126, 'lost', 'Mary Beatrice Trixia Tupino', '2025-10-16 10:39:37'),
(35, 126, 'found', 'Mary Beatrice Trixia Tupino', '2025-10-16 10:39:47'),
(36, 126, 'lost', 'Mary Beatrice Trixia Tupino', '2025-10-16 10:40:25'),
(37, 128, 'lost', 'Mary Beatrice Trixia Tupino', '2025-10-16 11:10:12'),
(38, 128, 'found', 'Mary Beatrice Trixia Tupino', '2025-10-16 11:14:53'),
(39, 129, 'Lost', 'Dr. Carlos Ceasar Catabas', '2025-10-17 06:19:34'),
(40, 117, 'lost', 'John Philip Yanos', '2025-10-17 06:36:39'),
(41, 130, 'Lost', 'Dr. Carlos Ceasar Catabas', '2025-10-17 09:28:06'),
(42, 130, 'Found', 'Dr. Carlos Ceasar Catabas', '2025-10-17 09:32:35'),
(43, 116, 'Lost', 'Dr. Carlos Ceasar Catabas', '2025-10-28 11:47:34'),
(44, 116, 'Lost', 'Dr. Carlos Ceasar Catabas', '2025-10-28 11:49:37'),
(45, 129, 'Lost', 'Dr. Carlos Ceasar Catabas', '2025-10-28 12:03:22'),
(46, 130, 'Found', 'Dr. Carlos Ceasar Catabas', '2025-10-28 12:03:59'),
(47, 132, 'lost', 'Joshua Pescasiosa', '2025-10-28 22:20:57'),
(48, 136, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-11-11 05:34:42'),
(49, 116, 'Lost', 'Dr. Carlos Ceasar Catabas', '2025-11-11 13:15:24'),
(50, 118, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-11-11 13:17:00'),
(51, 118, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-11-11 13:17:03'),
(52, 118, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-11-11 13:17:59'),
(53, 118, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-11-11 13:18:09'),
(54, 119, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-11-11 14:09:05'),
(55, 121, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-11-11 14:16:56'),
(56, 122, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-11-11 14:18:31'),
(57, 123, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-11-11 14:20:45'),
(58, 125, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-11-11 14:23:57'),
(59, 116, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-11-14 13:05:15'),
(60, 116, 'Lost', 'Dr. Carlos Ceasar Catabas', '2025-11-14 13:05:31'),
(61, 116, 'Found', 'Dr. Carlos Ceasar Catabas', '2025-11-15 00:56:38'),
(62, 116, 'Lost', 'Dr. Carlos Ceasar Catabas', '2025-11-15 00:56:45'),
(63, 128, 'Found', 'Dr. Carlos Ceasar Catabas', '2025-11-15 14:48:55'),
(64, 129, 'Lost', 'Dr. Carlos Ceasar Catabas', '2025-11-15 14:50:48'),
(65, 134, 'Safe', 'Dr. Carlos Ceasar Catabas', '2025-11-15 14:52:48'),
(66, 130, 'Found', 'Dr. Carlos Ceasar Catabas', '2025-11-15 15:05:16'),
(67, 126, 'found', 'Mary Beatrice Trixia Tupino', '2025-11-16 22:22:34'),
(68, 126, 'safe', 'Mary Beatrice Trixia Tupino', '2025-11-16 22:23:41'),
(69, 126, 'lost', 'Mary Beatrice Trixia Tupino', '2025-11-16 22:30:37'),
(70, 138, 'lost', 'Maria Lopez', '2025-11-16 23:37:07'),
(71, 142, 'lost', 'James Reid', '2025-11-17 09:49:54'),
(72, 126, 'found', 'Mary Beatrice Trixia Tupino', '2025-11-17 10:01:09'),
(73, 126, 'safe', 'Mary Beatrice Trixia Tupino', '2025-11-17 10:01:16'),
(74, 126, 'lost', 'Mary Beatrice Trixia Tupino', '2025-11-17 10:12:44');

-- --------------------------------------------------------

--
-- Table structure for table `medication`
--

CREATE TABLE `medication` (
  `med_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('Vaccination','Deworming','Vitamins') NOT NULL,
  `description` text DEFAULT NULL,
  `added_by` text DEFAULT NULL,
  `updated_by` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medication`
--

INSERT INTO `medication` (`med_id`, `name`, `type`, `description`, `added_by`, `updated_by`, `created_at`) VALUES
(6, 'Rabies', 'Vaccination', 'For Dogs and Cats', 'Dr. Carlos Ceasar Catabas', 'Dr. Carlos Ceasar Catabas', '2025-05-27 08:48:35'),
(7, 'Fenbendazole ', 'Deworming', 'Animal parasitic infections treatment', 'Dr. Carlos Ceasar Catabas', 'Dr. Carlos Ceasar Catabas', '2025-05-27 08:51:39'),
(8, 'Bovilis Nasalgen', 'Vaccination', 'For Cows , Carabaos', 'Dr. Carlos Ceasar Catabas', NULL, '2025-05-26 11:33:00'),
(9, 'Parvo', 'Vaccination', 'For Puppies ', 'Dr. Carlos Ceasar Catabas', NULL, '2025-05-27 08:49:00'),
(10, 'Feline parvovirus', 'Vaccination', 'For Kittens', 'Dr. Carlos Ceasar Catabas', NULL, '2025-05-27 08:53:20'),
(11, 'Vitamin A, D, and E', 'Vitamins', 'Especially For Livestocks', 'Dr. Carlos Ceasar Catabas', NULL, '2025-05-27 08:54:54'),
(12, 'LC-Vit Pet Multivitamins ', 'Vitamins', 'A dietary supplement for dogs, cats and poultry', 'Dr. Carlos Ceasar Catabas', NULL, '2025-05-27 10:58:01'),
(13, 'Nematocide', 'Deworming', 'A substance used to kill nematodes, which are also known as roundworms', 'Dr. Carlos Ceasar Catabas', NULL, '2025-05-27 11:00:01');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `notif_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `scan_id` int(11) DEFAULT NULL,
  `lf_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `date_notify` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `report_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification`
--

INSERT INTO `notification` (`notif_id`, `admin_id`, `owner_id`, `scan_id`, `lf_id`, `message`, `date_notify`, `is_read`, `report_id`) VALUES
(578, 1, 33, 339, NULL, 'Lost animal named Hugo was scanned.', '2025-09-27 06:24:39', 1, NULL),
(579, 1, 33, NULL, NULL, 'Liezel Yanos submitted a found report of animal named Hugo.', '2025-09-27 06:26:19', 1, 55),
(580, 1, 33, 340, NULL, 'Lost animal named Hugo was scanned.', '2025-09-27 06:35:21', 1, NULL),
(581, 1, 33, 341, NULL, 'Lost animal named Hugo was scanned.', '2025-09-27 07:17:09', 1, NULL),
(583, 1, 33, 343, NULL, 'Lost animal named Hugo was scanned.', '2025-09-27 07:18:25', 1, NULL),
(584, 1, 33, NULL, NULL, 'Andrea Cawaya submitted a found report of animal named Hugo.', '2025-09-27 07:19:29', 1, 56),
(585, 1, NULL, NULL, NULL, 'The status of animal named Kumal has been updated to lost by its owner named John Philip Yanos.', '2025-10-07 02:47:27', 1, NULL),
(586, 1, 33, 344, NULL, 'Lost animal named Kumal was scanned.', '2025-10-07 02:48:20', 1, NULL),
(587, 1, 33, 345, NULL, 'Lost animal named Kumal was scanned.', '2025-10-07 02:49:04', 1, NULL),
(588, 1, 33, 346, NULL, 'Lost animal named Hugo was scanned.', '2025-10-07 11:56:38', 1, NULL),
(589, 1, NULL, NULL, NULL, 'The status of animal named Math has been updated to lost by its owner named Joshua Dela Cruz.', '2025-10-09 03:13:28', 1, NULL),
(590, 9, NULL, NULL, NULL, 'The status of animal named Math has been updated to lost by its owner named Joshua Dela Cruz.', '2025-10-09 03:13:28', 0, NULL),
(591, 1, 37, 347, NULL, 'Lost animal named Math was scanned.', '2025-10-09 03:13:48', 1, NULL),
(592, 9, 37, 347, NULL, 'Lost animal named Math was scanned.', '2025-10-09 03:13:48', 0, NULL),
(593, 1, 37, NULL, NULL, 'Andrea submitted a found report of animal named Math.', '2025-10-09 03:15:57', 1, 57),
(594, 9, 37, NULL, NULL, 'Andrea submitted a found report of animal named Math.', '2025-10-09 03:15:57', 0, 57),
(595, 1, NULL, NULL, NULL, 'The status of animal named Math has been updated to found by its owner named Joshua Dela Cruz.', '2025-10-09 03:16:49', 1, NULL),
(596, 9, NULL, NULL, NULL, 'The status of animal named Math has been updated to found by its owner named Joshua Dela Cruz.', '2025-10-09 03:16:49', 0, NULL),
(597, 1, 33, 348, NULL, 'Lost animal named Hugo was scanned.', '2025-10-10 06:46:30', 1, NULL),
(598, 9, 33, 348, NULL, 'Lost animal named Hugo was scanned.', '2025-10-10 06:46:30', 1, NULL),
(599, 1, 33, NULL, 18, 'Your pet Kumal\'s status has been updated to Found by an Dr. Carlos Ceasar Catabas.', '2025-10-13 05:06:11', 1, NULL),
(600, 1, NULL, NULL, 19, 'The status of animal named Kumal has been updated to lost by its owner named John Philip Yanos.', '2025-10-13 05:13:41', 1, NULL),
(601, 9, NULL, NULL, 19, 'The status of animal named Kumal has been updated to lost by its owner named John Philip Yanos.', '2025-10-13 05:13:41', 0, NULL),
(602, 1, 33, 349, NULL, 'Lost animal named Kumal was scanned.', '2025-10-13 05:14:24', 1, NULL),
(603, 9, 33, 349, NULL, 'Lost animal named Kumal was scanned.', '2025-10-13 05:14:24', 1, NULL),
(604, 1, NULL, NULL, 20, 'The status of animal named Hugo has been updated to found by its owner named John Philip Yanos.', '2025-10-13 16:12:12', 1, NULL),
(605, 9, NULL, NULL, 20, 'The status of animal named Hugo has been updated to found by its owner named John Philip Yanos.', '2025-10-13 16:12:12', 0, NULL),
(606, 1, NULL, NULL, 21, 'The status of animal named Pichea has been updated to lost by its owner named Jgar Nebrija.', '2025-10-14 01:48:50', 1, NULL),
(607, 9, NULL, NULL, 21, 'The status of animal named Pichea has been updated to lost by its owner named Jgar Nebrija.', '2025-10-14 01:48:50', 0, NULL),
(608, 1, 38, 350, NULL, 'Lost animal named Pichea was scanned.', '2025-10-14 01:49:23', 1, NULL),
(609, 9, 38, 350, NULL, 'Lost animal named Pichea was scanned.', '2025-10-14 01:49:23', 1, NULL),
(610, 1, NULL, NULL, 22, 'The status of animal named Pichea has been updated to found by its owner named Jgar Nebrija.', '2025-10-14 01:50:32', 1, NULL),
(611, 9, NULL, NULL, 22, 'The status of animal named Pichea has been updated to found by its owner named Jgar Nebrija.', '2025-10-14 01:50:32', 0, NULL),
(612, 1, NULL, NULL, 23, 'The status of animal named Pichea has been updated to safe by its owner named Jgar Nebrija.', '2025-10-14 01:50:38', 1, NULL),
(613, 9, NULL, NULL, 23, 'The status of animal named Pichea has been updated to safe by its owner named Jgar Nebrija.', '2025-10-14 01:50:38', 0, NULL),
(614, 1, NULL, NULL, 24, 'The status of animal named Hugo has been updated to lost by its owner named John Philip Yanos.', '2025-10-14 06:52:32', 1, NULL),
(615, 9, NULL, NULL, 24, 'The status of animal named Hugo has been updated to lost by its owner named John Philip Yanos.', '2025-10-14 06:52:32', 0, NULL),
(616, 1, 33, 351, NULL, 'Lost animal named Hugo was scanned.', '2025-10-14 06:54:29', 1, NULL),
(617, 9, 33, 351, NULL, 'Lost animal named Hugo was scanned.', '2025-10-14 06:54:29', 1, NULL),
(618, 1, 33, NULL, NULL, 'Andrea Cawaya submitted a found report of animal named Hugo.', '2025-10-14 06:56:00', 1, 58),
(619, 9, 33, NULL, NULL, 'Andrea Cawaya submitted a found report of animal named Hugo.', '2025-10-14 06:56:00', 1, 58),
(620, 1, 33, 352, NULL, 'Lost animal named Hugo was scanned.', '2025-10-14 06:57:49', 1, NULL),
(621, 9, 33, 352, NULL, 'Lost animal named Hugo was scanned.', '2025-10-14 06:57:49', 1, NULL),
(622, 1, 33, NULL, NULL, 'Vernaliza Batillano submitted a found report of animal named Hugo.', '2025-10-14 07:00:12', 1, 59),
(623, 9, 33, NULL, NULL, 'Vernaliza Batillano submitted a found report of animal named Hugo.', '2025-10-14 07:00:12', 1, 59),
(624, 1, 33, 353, NULL, 'Lost animal named Hugo was scanned.', '2025-10-14 08:45:44', 1, NULL),
(625, 9, 33, 353, NULL, 'Lost animal named Hugo was scanned.', '2025-10-14 08:45:44', 1, NULL),
(627, 9, 33, 354, NULL, 'Lost animal named Hugo was scanned.', '2025-10-14 17:20:38', 1, NULL),
(629, 9, 33, 355, NULL, 'Lost animal named Hugo was scanned.', '2025-10-14 17:22:35', 1, NULL),
(631, 9, 33, 356, NULL, 'Lost animal named Hugo was scanned.', '2025-10-14 17:28:12', 1, NULL),
(632, 1, 33, NULL, NULL, 'Jayvee Francisco Balimiento submitted a found report of animal named Hugo.', '2025-10-14 09:37:46', 1, 60),
(633, 9, 33, NULL, NULL, 'Jayvee Francisco Balimiento submitted a found report of animal named Hugo.', '2025-10-14 09:37:46', 1, 60),
(634, 1, 33, NULL, NULL, 'John Philip Panta Yanos submitted a found report of animal named Hugo.', '2025-10-14 09:48:28', 1, 61),
(635, 9, 33, NULL, NULL, 'John Philip Panta Yanos submitted a found report of animal named Hugo.', '2025-10-14 09:48:28', 1, 61),
(636, 1, NULL, NULL, 25, 'The status of animal named Kumal has been updated to found by its owner named John Philip Yanos.', '2025-10-14 10:26:08', 1, NULL),
(637, 9, NULL, NULL, 25, 'The status of animal named Kumal has been updated to found by its owner named John Philip Yanos.', '2025-10-14 10:26:08', 0, NULL),
(638, 1, 33, 357, NULL, 'Lost animal named Hugo was scanned.', '2025-10-16 14:40:58', 1, NULL),
(639, 9, 33, 357, NULL, 'Lost animal named Hugo was scanned.', '2025-10-16 14:40:58', 0, NULL),
(640, 1, 33, NULL, NULL, 'Jade Cawaya submitted a found report of animal named Hugo.', '2025-10-16 06:41:38', 1, 62),
(641, 9, 33, NULL, NULL, 'Jade Cawaya submitted a found report of animal named Hugo.', '2025-10-16 06:41:38', 0, 62),
(642, 1, NULL, NULL, 26, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 07:11:58', 1, NULL),
(643, 9, NULL, NULL, 26, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 07:11:58', 0, NULL),
(644, 1, 43, 358, NULL, 'Lost animal named Rocky was scanned.', '2025-10-16 15:14:24', 1, NULL),
(645, 9, 43, 358, NULL, 'Lost animal named Rocky was scanned.', '2025-10-16 15:14:24', 1, NULL),
(646, 1, 43, NULL, NULL, 'Andrea submitted a found report of animal named Rocky.', '2025-10-16 07:18:22', 1, 63),
(647, 9, 43, NULL, NULL, 'Andrea submitted a found report of animal named Rocky.', '2025-10-16 07:18:22', 1, 63),
(648, 1, 43, 359, NULL, 'Lost animal named Rocky was scanned.', '2025-10-16 15:18:33', 1, NULL),
(649, 9, 43, 359, NULL, 'Lost animal named Rocky was scanned.', '2025-10-16 15:18:33', 1, NULL),
(650, 1, NULL, NULL, 27, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 07:21:54', 1, NULL),
(651, 9, NULL, NULL, 27, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 07:21:54', 0, NULL),
(652, 1, NULL, NULL, 28, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 07:21:58', 1, NULL),
(653, 9, NULL, NULL, 28, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 07:21:58', 0, NULL),
(654, 1, NULL, NULL, 29, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 09:54:37', 1, NULL),
(655, 9, NULL, NULL, 29, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 09:54:37', 0, NULL),
(656, 1, 43, 360, NULL, 'Lost animal named Rocky was scanned.', '2025-10-16 18:02:41', 1, NULL),
(657, 9, 43, 360, NULL, 'Lost animal named Rocky was scanned.', '2025-10-16 18:02:41', 1, NULL),
(658, 1, 43, 361, NULL, 'Lost animal named Rocky was scanned.', '2025-10-16 18:08:02', 1, NULL),
(659, 9, 43, 361, NULL, 'Lost animal named Rocky was scanned.', '2025-10-16 18:08:02', 1, NULL),
(660, 1, NULL, NULL, 30, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:11:02', 1, NULL),
(661, 9, NULL, NULL, 30, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:11:02', 0, NULL),
(662, 1, NULL, NULL, 31, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:11:05', 1, NULL),
(663, 9, NULL, NULL, 31, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:11:05', 0, NULL),
(664, 1, NULL, NULL, 32, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:17:03', 1, NULL),
(665, 9, NULL, NULL, 32, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:17:03', 0, NULL),
(666, 1, NULL, NULL, 33, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:39:22', 1, NULL),
(667, 9, NULL, NULL, 33, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:39:22', 0, NULL),
(668, 1, NULL, NULL, 34, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:39:37', 1, NULL),
(669, 9, NULL, NULL, 34, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:39:37', 0, NULL),
(670, 1, NULL, NULL, 35, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:39:47', 1, NULL),
(671, 9, NULL, NULL, 35, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:39:47', 0, NULL),
(672, 1, NULL, NULL, 36, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:40:25', 1, NULL),
(673, 9, NULL, NULL, 36, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 10:40:25', 0, NULL),
(674, 1, 43, 362, NULL, 'Lost animal named Rocky was scanned.', '2025-10-16 18:40:43', 1, NULL),
(675, 9, 43, 362, NULL, 'Lost animal named Rocky was scanned.', '2025-10-16 18:40:43', 1, NULL),
(676, 1, 43, NULL, NULL, 'test submitted a found report of animal named Rocky.', '2025-10-16 10:42:31', 1, 64),
(677, 9, 43, NULL, NULL, 'test submitted a found report of animal named Rocky.', '2025-10-16 10:42:31', 1, 64),
(678, 1, NULL, NULL, 37, 'The status of animal named Quinos has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 11:10:12', 1, NULL),
(679, 9, NULL, NULL, 37, 'The status of animal named Quinos has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 11:10:12', 0, NULL),
(680, 1, 43, 363, NULL, 'Lost animal named Quinos was scanned.', '2025-10-16 19:10:21', 1, NULL),
(681, 9, 43, 363, NULL, 'Lost animal named Quinos was scanned.', '2025-10-16 19:10:21', 1, NULL),
(682, 1, 43, NULL, NULL, 'Test submitted a found report of animal named Quinos.', '2025-10-16 11:11:42', 1, 65),
(683, 9, 43, NULL, NULL, 'Test submitted a found report of animal named Quinos.', '2025-10-16 11:11:42', 1, 65),
(684, 1, NULL, NULL, 38, 'The status of animal named Quinos has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 11:14:53', 1, NULL),
(685, 9, NULL, NULL, 38, 'The status of animal named Quinos has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-10-16 11:14:53', 0, NULL),
(686, 1, 46, NULL, 39, 'Your pet Hugo\'s status has been updated to Lost by Dr. Carlos Ceasar Catabas.', '2025-10-17 06:19:34', 1, NULL),
(688, 9, 46, 364, NULL, 'Lost animal named Hugo was scanned.', '2025-10-17 14:19:49', 0, NULL),
(689, 1, 46, NULL, NULL, 'Shania Maglantay submitted a found report of animal named Hugo.', '2025-10-17 06:21:50', 1, 66),
(690, 9, 46, NULL, NULL, 'Shania Maglantay submitted a found report of animal named Hugo.', '2025-10-17 06:21:50', 0, 66),
(691, 1, NULL, NULL, 40, 'The status of animal named Kumal has been updated to lost by its owner named John Philip Yanos.', '2025-10-17 06:36:39', 1, NULL),
(692, 10, NULL, NULL, 40, 'The status of animal named Kumal has been updated to lost by its owner named John Philip Yanos.', '2025-10-17 06:36:39', 0, NULL),
(693, 9, NULL, NULL, 40, 'The status of animal named Kumal has been updated to lost by its owner named John Philip Yanos.', '2025-10-17 06:36:39', 0, NULL),
(694, 1, 33, 365, NULL, 'Lost animal named Kumal was scanned.', '2025-10-17 16:40:59', 1, NULL),
(695, 10, 33, 365, NULL, 'Lost animal named Kumal was scanned.', '2025-10-17 16:40:59', 1, NULL),
(696, 9, 33, 365, NULL, 'Lost animal named Kumal was scanned.', '2025-10-17 16:40:59', 1, NULL),
(697, 1, 48, NULL, 41, 'Your pet lolita\'s status has been updated to Lost by Dr. Carlos Ceasar Catabas.', '2025-10-17 09:28:06', 1, NULL),
(698, 1, 48, 366, NULL, 'Lost animal named lolita was scanned.', '2025-10-17 17:28:26', 1, NULL),
(699, 10, 48, 366, NULL, 'Lost animal named lolita was scanned.', '2025-10-17 17:28:26', 0, NULL),
(700, 9, 48, 366, NULL, 'Lost animal named lolita was scanned.', '2025-10-17 17:28:26', 0, NULL),
(701, 1, 48, 367, NULL, 'Lost animal named lolita was scanned.', '2025-10-17 17:29:18', 1, NULL),
(702, 10, 48, 367, NULL, 'Lost animal named lolita was scanned.', '2025-10-17 17:29:18', 0, NULL),
(703, 9, 48, 367, NULL, 'Lost animal named lolita was scanned.', '2025-10-17 17:29:18', 0, NULL),
(704, 1, 48, NULL, NULL, 'Andrea submitted a found report of animal named lolita.', '2025-10-17 09:29:27', 1, 67),
(705, 10, 48, NULL, NULL, 'Andrea submitted a found report of animal named lolita.', '2025-10-17 09:29:27', 0, 67),
(706, 9, 48, NULL, NULL, 'Andrea submitted a found report of animal named lolita.', '2025-10-17 09:29:27', 0, 67),
(707, 1, 48, NULL, 42, 'Your pet lolita\'s status has been updated to Found by Dr. Carlos Ceasar Catabas.', '2025-10-17 09:32:35', 1, NULL),
(708, 1, 33, NULL, 43, 'Your pet Hugo\'s status has been updated to Lost by an Dr. Carlos Ceasar Catabas.', '2025-10-28 19:47:34', 1, NULL),
(709, 1, 33, NULL, 44, 'Your pet Hugo\'s status has been updated to Lost by an Dr. Carlos Ceasar Catabas.', '2025-10-28 19:49:37', 1, NULL),
(710, 1, NULL, 368, NULL, 'Lost animal named Hugo was scanned.', '2025-10-28 19:58:12', 1, NULL),
(711, 10, NULL, 368, NULL, 'Lost animal named Hugo was scanned.', '2025-10-28 19:58:12', 0, NULL),
(712, 12, NULL, 368, NULL, 'Lost animal named Hugo was scanned.', '2025-10-28 19:58:12', 0, NULL),
(713, 9, NULL, 368, NULL, 'Lost animal named Hugo was scanned.', '2025-10-28 19:58:12', 0, NULL),
(714, 11, NULL, 368, NULL, 'Lost animal named Hugo was scanned.', '2025-10-28 19:58:12', 0, NULL),
(716, 1, 46, NULL, 45, 'Your pet Hugo\'s status has been updated to Lost by an Dr. Carlos Ceasar Catabas.', '2025-10-28 20:03:22', 1, NULL),
(717, 1, 48, NULL, 46, 'Your pet lolita\'s status has been updated to Found by an Dr. Carlos Ceasar Catabas.', '2025-10-28 20:03:59', 1, NULL),
(718, 1, NULL, NULL, 47, 'The status of animal named Gabriel has been updated to lost by its owner named Joshua Pescasiosa.', '2025-10-28 22:20:57', 1, NULL),
(719, 10, NULL, NULL, 47, 'The status of animal named Gabriel has been updated to lost by its owner named Joshua Pescasiosa.', '2025-10-28 22:20:57', 0, NULL),
(720, 13, NULL, NULL, 47, 'The status of animal named Gabriel has been updated to lost by its owner named Joshua Pescasiosa.', '2025-10-28 22:20:57', 0, NULL),
(721, 12, NULL, NULL, 47, 'The status of animal named Gabriel has been updated to lost by its owner named Joshua Pescasiosa.', '2025-10-28 22:20:57', 0, NULL),
(722, 14, NULL, NULL, 47, 'The status of animal named Gabriel has been updated to lost by its owner named Joshua Pescasiosa.', '2025-10-28 22:20:57', 0, NULL),
(723, 9, NULL, NULL, 47, 'The status of animal named Gabriel has been updated to lost by its owner named Joshua Pescasiosa.', '2025-10-28 22:20:57', 0, NULL),
(724, 11, NULL, NULL, 47, 'The status of animal named Gabriel has been updated to lost by its owner named Joshua Pescasiosa.', '2025-10-28 22:20:57', 0, NULL),
(725, 15, NULL, NULL, 47, 'The status of animal named Gabriel has been updated to lost by its owner named Joshua Pescasiosa.', '2025-10-28 22:20:57', 0, NULL),
(726, 1, NULL, 369, NULL, 'Lost animal named Hugo was scanned.', '2025-10-30 11:20:17', 1, NULL),
(727, 16, NULL, 369, NULL, 'Lost animal named Hugo was scanned.', '2025-10-30 11:20:17', 0, NULL),
(728, 10, NULL, 369, NULL, 'Lost animal named Hugo was scanned.', '2025-10-30 11:20:17', 0, NULL),
(729, 13, NULL, 369, NULL, 'Lost animal named Hugo was scanned.', '2025-10-30 11:20:17', 0, NULL),
(730, 12, NULL, 369, NULL, 'Lost animal named Hugo was scanned.', '2025-10-30 11:20:17', 0, NULL),
(731, 14, NULL, 369, NULL, 'Lost animal named Hugo was scanned.', '2025-10-30 11:20:17', 0, NULL),
(732, 9, NULL, 369, NULL, 'Lost animal named Hugo was scanned.', '2025-10-30 11:20:17', 0, NULL),
(733, 11, NULL, 369, NULL, 'Lost animal named Hugo was scanned.', '2025-10-30 11:20:17', 0, NULL),
(734, 15, NULL, 369, NULL, 'Lost animal named Hugo was scanned.', '2025-10-30 11:20:17', 0, NULL),
(736, 1, 54, NULL, 48, 'Your pet Percy\'s status has been updated to Safe by an Dr. Carlos Ceasar Catabas.', '2025-11-11 13:34:42', 0, NULL),
(737, 1, 33, NULL, 49, 'Your pet Hugo\'s status has been updated to Lost by an Dr. Carlos Ceasar Catabas.', '2025-11-11 21:15:24', 1, NULL),
(738, 1, 36, NULL, 50, 'Your pet Browny\'s status has been updated to Safe by an Dr. Carlos Ceasar Catabas.', '2025-11-11 21:17:00', 0, NULL),
(739, 1, 36, NULL, 51, 'Your pet Browny\'s status has been updated to Safe by an Dr. Carlos Ceasar Catabas.', '2025-11-11 21:17:03', 0, NULL),
(740, 1, 36, NULL, 52, 'Your pet Browny\'s status has been updated to Safe by an Dr. Carlos Ceasar Catabas.', '2025-11-11 21:17:59', 0, NULL),
(741, 1, 36, NULL, 53, 'Your pet Browny\'s status has been updated to Safe by an Dr. Carlos Ceasar Catabas.', '2025-11-11 21:18:09', 0, NULL),
(742, 1, 37, NULL, 54, 'Your pet Matt\'s status has been updated to Safe by an Dr. Carlos Ceasar Catabas.', '2025-11-11 22:09:04', 0, NULL),
(743, 1, 38, NULL, 55, 'Your pet Pichea\'s status has been updated to Safe by an Dr. Carlos Ceasar Catabas.', '2025-11-11 22:16:56', 0, NULL),
(744, 1, 39, NULL, 56, 'Your pet Pot-pot\'s status has been updated to Safe by an Dr. Carlos Ceasar Catabas.', '2025-11-11 22:18:31', 0, NULL),
(745, 1, 40, NULL, 57, 'Your pet Booger\'s status has been updated to Safe by an Dr. Carlos Ceasar Catabas.', '2025-11-11 22:20:45', 0, NULL),
(746, 1, 41, NULL, 58, 'Your pet Chico\'s status has been updated to Safe by an Dr. Carlos Ceasar Catabas.', '2025-11-11 22:23:57', 0, NULL),
(747, 1, NULL, 370, NULL, 'Lost animal named Hugo was scanned.', '2025-11-14 20:58:24', 0, NULL),
(748, 16, NULL, 370, NULL, 'Lost animal named Hugo was scanned.', '2025-11-14 20:58:24', 0, NULL),
(749, 10, NULL, 370, NULL, 'Lost animal named Hugo was scanned.', '2025-11-14 20:58:24', 0, NULL),
(750, 13, NULL, 370, NULL, 'Lost animal named Hugo was scanned.', '2025-11-14 20:58:24', 0, NULL),
(751, 12, NULL, 370, NULL, 'Lost animal named Hugo was scanned.', '2025-11-14 20:58:24', 0, NULL),
(752, 14, NULL, 370, NULL, 'Lost animal named Hugo was scanned.', '2025-11-14 20:58:24', 0, NULL),
(753, 9, NULL, 370, NULL, 'Lost animal named Hugo was scanned.', '2025-11-14 20:58:24', 0, NULL),
(754, 11, NULL, 370, NULL, 'Lost animal named Hugo was scanned.', '2025-11-14 20:58:24', 0, NULL),
(755, 15, NULL, 370, NULL, 'Lost animal named Hugo was scanned.', '2025-11-14 20:58:24', 0, NULL),
(757, 1, 33, NULL, 59, 'Your pet Hugo\'s status has been updated to Safe by an Dr. Carlos Ceasar Catabas.', '2025-11-14 21:05:15', 0, NULL),
(758, 1, 33, NULL, 60, 'Your pet Hugo\'s status has been updated to Lost by an Dr. Carlos Ceasar Catabas.', '2025-11-14 21:05:31', 0, NULL),
(759, 1, 33, NULL, 61, 'Your pet Hugo\'s status has been updated to Found by an Dr. Carlos Ceasar Catabas.', '2025-11-15 08:56:38', 0, NULL),
(760, 1, 33, NULL, 62, 'Your pet Hugo\'s status has been updated to Lost by an Dr. Carlos Ceasar Catabas.', '2025-11-15 08:56:45', 0, NULL),
(761, 1, 43, NULL, 63, 'Your pet Quinos\'s status has been updated to Found by an Dr. Carlos Ceasar Catabas.', '2025-11-15 22:48:55', 1, NULL),
(762, 1, 46, NULL, 64, 'Your pet Hugo\'s status has been updated to Lost by an Dr. Carlos Ceasar Catabas.', '2025-11-15 22:50:48', 0, NULL),
(763, 1, 36, NULL, 65, 'Your pet Lukas\'s status has been updated to Safe by an Dr. Carlos Ceasar Catabas.', '2025-11-15 22:52:48', 0, NULL),
(764, 1, 48, NULL, 66, 'Your pet Lolita\'s status has been updated to Found by an Dr. Carlos Ceasar Catabas.', '2025-11-15 23:05:16', 0, NULL),
(765, 1, NULL, 371, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:36:24', 0, NULL),
(766, 11, NULL, 371, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:36:24', 0, NULL),
(767, 15, NULL, 371, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:36:24', 0, NULL),
(768, 9, NULL, 371, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:36:24', 0, NULL),
(769, 14, NULL, 371, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:36:24', 0, NULL),
(770, 10, NULL, 371, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:36:24', 0, NULL),
(771, 13, NULL, 371, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:36:24', 0, NULL),
(772, 12, NULL, 371, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:36:24', 0, NULL),
(773, 16, NULL, 371, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:36:24', 0, NULL),
(775, 1, NULL, 372, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:47:19', 1, NULL),
(776, 11, NULL, 372, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:47:19', 0, NULL),
(777, 15, NULL, 372, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:47:19', 0, NULL),
(778, 9, NULL, 372, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:47:19', 0, NULL),
(779, 14, NULL, 372, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:47:19', 0, NULL),
(780, 10, NULL, 372, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:47:19', 0, NULL),
(781, 13, NULL, 372, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:47:19', 0, NULL),
(782, 12, NULL, 372, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:47:19', 0, NULL),
(783, 16, NULL, 372, NULL, 'Lost animal named Rocky was scanned.', '2025-11-15 23:47:19', 0, NULL),
(785, 1, NULL, NULL, 67, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:22:34', 0, NULL),
(786, 11, NULL, NULL, 67, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:22:34', 0, NULL),
(787, 15, NULL, NULL, 67, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:22:34', 0, NULL),
(788, 9, NULL, NULL, 67, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:22:34', 0, NULL),
(789, 14, NULL, NULL, 67, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:22:34', 0, NULL),
(790, 10, NULL, NULL, 67, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:22:34', 0, NULL),
(791, 13, NULL, NULL, 67, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:22:34', 0, NULL),
(792, 12, NULL, NULL, 67, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:22:34', 0, NULL),
(793, 16, NULL, NULL, 67, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:22:34', 0, NULL),
(794, 1, NULL, NULL, 68, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:23:41', 0, NULL),
(795, 11, NULL, NULL, 68, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:23:41', 0, NULL),
(796, 15, NULL, NULL, 68, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:23:41', 0, NULL),
(797, 9, NULL, NULL, 68, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:23:41', 0, NULL),
(798, 14, NULL, NULL, 68, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:23:41', 0, NULL),
(799, 10, NULL, NULL, 68, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:23:41', 0, NULL),
(800, 13, NULL, NULL, 68, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:23:41', 0, NULL),
(801, 12, NULL, NULL, 68, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:23:41', 0, NULL),
(802, 16, NULL, NULL, 68, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:23:41', 0, NULL),
(803, 1, NULL, NULL, 69, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:30:37', 1, NULL),
(804, 11, NULL, NULL, 69, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:30:37', 0, NULL),
(805, 15, NULL, NULL, 69, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:30:37', 0, NULL),
(806, 9, NULL, NULL, 69, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:30:37', 0, NULL),
(807, 14, NULL, NULL, 69, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:30:37', 0, NULL),
(808, 10, NULL, NULL, 69, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:30:37', 0, NULL),
(809, 13, NULL, NULL, 69, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:30:37', 0, NULL),
(810, 12, NULL, NULL, 69, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:30:37', 0, NULL),
(811, 16, NULL, NULL, 69, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-16 22:30:37', 0, NULL),
(812, 1, NULL, 373, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:35:09', 1, NULL),
(813, 11, NULL, 373, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:35:09', 0, NULL),
(814, 15, NULL, 373, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:35:09', 0, NULL),
(815, 9, NULL, 373, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:35:09', 0, NULL),
(816, 14, NULL, 373, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:35:09', 0, NULL),
(817, 10, NULL, 373, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:35:09', 0, NULL),
(818, 13, NULL, 373, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:35:09', 0, NULL),
(819, 12, NULL, 373, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:35:09', 0, NULL),
(820, 16, NULL, 373, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:35:09', 0, NULL),
(822, 1, NULL, 374, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:43:54', 1, NULL),
(823, 11, NULL, 374, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:43:54', 0, NULL),
(824, 15, NULL, 374, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:43:54', 0, NULL),
(825, 9, NULL, 374, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:43:54', 0, NULL),
(826, 14, NULL, 374, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:43:54', 0, NULL),
(827, 10, NULL, 374, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:43:54', 0, NULL),
(828, 13, NULL, 374, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:43:54', 0, NULL),
(829, 12, NULL, 374, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:43:54', 0, NULL),
(830, 16, NULL, 374, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:43:54', 0, NULL),
(832, 1, NULL, 375, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:52:20', 0, NULL),
(833, 11, NULL, 375, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:52:20', 0, NULL),
(834, 15, NULL, 375, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:52:20', 0, NULL),
(835, 9, NULL, 375, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:52:20', 0, NULL),
(836, 14, NULL, 375, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:52:20', 0, NULL),
(837, 10, NULL, 375, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:52:20', 0, NULL),
(838, 13, NULL, 375, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:52:20', 0, NULL),
(839, 12, NULL, 375, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:52:20', 0, NULL),
(840, 16, NULL, 375, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:52:20', 0, NULL),
(842, 1, NULL, 376, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:59:59', 0, NULL),
(843, 11, NULL, 376, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:59:59', 0, NULL),
(844, 15, NULL, 376, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:59:59', 0, NULL),
(845, 9, NULL, 376, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:59:59', 0, NULL),
(846, 14, NULL, 376, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:59:59', 0, NULL),
(847, 10, NULL, 376, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:59:59', 0, NULL),
(848, 13, NULL, 376, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:59:59', 0, NULL),
(849, 12, NULL, 376, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:59:59', 0, NULL),
(850, 16, NULL, 376, NULL, 'Lost animal named Rocky was scanned.', '2025-11-16 22:59:59', 0, NULL),
(852, 1, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:33', 0, 68),
(853, 11, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:33', 0, 68),
(854, 15, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:33', 0, 68),
(855, 9, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:33', 0, 68),
(856, 14, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:33', 0, 68),
(857, 10, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:33', 0, 68),
(858, 13, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:33', 0, 68),
(859, 12, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:33', 0, 68),
(860, 16, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:33', 0, 68),
(862, 1, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:43', 0, 69),
(863, 11, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:43', 0, 69),
(864, 15, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:43', 0, 69),
(865, 9, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:43', 0, 69),
(866, 14, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:43', 0, 69),
(867, 10, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:43', 0, 69),
(868, 13, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:43', 0, 69),
(869, 12, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:43', 0, 69),
(870, 16, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:03:43', 0, 69),
(872, 1, NULL, NULL, NULL, 'John Philip Panta Yanos submitted a found report of animal named Rocky.', '2025-11-16 23:04:10', 0, 70),
(873, 11, NULL, NULL, NULL, 'John Philip Panta Yanos submitted a found report of animal named Rocky.', '2025-11-16 23:04:10', 0, 70),
(874, 15, NULL, NULL, NULL, 'John Philip Panta Yanos submitted a found report of animal named Rocky.', '2025-11-16 23:04:10', 0, 70),
(875, 9, NULL, NULL, NULL, 'John Philip Panta Yanos submitted a found report of animal named Rocky.', '2025-11-16 23:04:10', 0, 70),
(876, 14, NULL, NULL, NULL, 'John Philip Panta Yanos submitted a found report of animal named Rocky.', '2025-11-16 23:04:10', 0, 70),
(877, 10, NULL, NULL, NULL, 'John Philip Panta Yanos submitted a found report of animal named Rocky.', '2025-11-16 23:04:10', 0, 70),
(878, 13, NULL, NULL, NULL, 'John Philip Panta Yanos submitted a found report of animal named Rocky.', '2025-11-16 23:04:10', 0, 70),
(879, 12, NULL, NULL, NULL, 'John Philip Panta Yanos submitted a found report of animal named Rocky.', '2025-11-16 23:04:10', 0, 70),
(880, 16, NULL, NULL, NULL, 'John Philip Panta Yanos submitted a found report of animal named Rocky.', '2025-11-16 23:04:10', 0, 70),
(882, 1, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:04:26', 0, 71),
(883, 11, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:04:26', 0, 71),
(884, 15, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:04:26', 0, 71),
(885, 9, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:04:26', 0, 71),
(886, 14, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:04:26', 0, 71),
(887, 10, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:04:26', 0, 71),
(888, 13, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:04:26', 0, 71),
(889, 12, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:04:26', 0, 71),
(890, 16, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:04:26', 0, 71),
(892, 1, NULL, NULL, NULL, 'Joshua ni submitted a found report of animal named Rocky.', '2025-11-16 23:04:47', 0, 72),
(893, 11, NULL, NULL, NULL, 'Joshua ni submitted a found report of animal named Rocky.', '2025-11-16 23:04:47', 0, 72),
(894, 15, NULL, NULL, NULL, 'Joshua ni submitted a found report of animal named Rocky.', '2025-11-16 23:04:47', 0, 72),
(895, 9, NULL, NULL, NULL, 'Joshua ni submitted a found report of animal named Rocky.', '2025-11-16 23:04:47', 0, 72),
(896, 14, NULL, NULL, NULL, 'Joshua ni submitted a found report of animal named Rocky.', '2025-11-16 23:04:47', 0, 72),
(897, 10, NULL, NULL, NULL, 'Joshua ni submitted a found report of animal named Rocky.', '2025-11-16 23:04:47', 0, 72),
(898, 13, NULL, NULL, NULL, 'Joshua ni submitted a found report of animal named Rocky.', '2025-11-16 23:04:47', 0, 72),
(899, 12, NULL, NULL, NULL, 'Joshua ni submitted a found report of animal named Rocky.', '2025-11-16 23:04:47', 0, 72),
(900, 16, NULL, NULL, NULL, 'Joshua ni submitted a found report of animal named Rocky.', '2025-11-16 23:04:47', 0, 72),
(902, 1, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-16 23:05:20', 1, 73),
(903, 11, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-16 23:05:20', 0, 73),
(904, 15, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-16 23:05:20', 0, 73),
(905, 9, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-16 23:05:20', 0, 73),
(906, 14, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-16 23:05:20', 0, 73),
(907, 10, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-16 23:05:20', 0, 73),
(908, 13, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-16 23:05:20', 0, 73),
(909, 12, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-16 23:05:20', 0, 73),
(910, 16, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-16 23:05:20', 0, 73),
(912, 1, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:14:15', 0, 74),
(913, 11, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:14:15', 0, 74),
(914, 15, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:14:15', 0, 74),
(915, 9, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:14:15', 0, 74),
(916, 14, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:14:15', 0, 74),
(917, 10, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:14:15', 0, 74),
(918, 13, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:14:15', 0, 74),
(919, 12, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:14:15', 0, 74),
(920, 16, NULL, NULL, NULL, 'Sheena Roquero Panta submitted a found report of animal named Rocky.', '2025-11-16 23:14:15', 0, 74),
(921, NULL, 43, NULL, NULL, 'Good news! Someone reported finding your animal named Rocky.', '2025-11-16 23:14:15', 1, 74),
(922, 1, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:15:18', 1, 75),
(923, 11, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:15:18', 0, 75),
(924, 15, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:15:18', 0, 75),
(925, 9, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:15:18', 0, 75),
(926, 14, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:15:18', 0, 75),
(927, 10, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:15:18', 0, 75),
(928, 13, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:15:18', 0, 75),
(929, 12, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:15:18', 0, 75),
(930, 16, NULL, NULL, NULL, 'Edrian Mobisa Ardenio submitted a found report of animal named Rocky.', '2025-11-16 23:15:18', 0, 75),
(931, NULL, 43, NULL, NULL, 'Good news! Someone reported finding your animal named Rocky.', '2025-11-16 23:15:18', 1, 75),
(932, 1, NULL, NULL, 70, 'The status of animal named Nena has been updated to lost by its owner named Maria Lopez.', '2025-11-16 23:37:07', 0, NULL),
(933, 11, NULL, NULL, 70, 'The status of animal named Nena has been updated to lost by its owner named Maria Lopez.', '2025-11-16 23:37:07', 0, NULL),
(934, 15, NULL, NULL, 70, 'The status of animal named Nena has been updated to lost by its owner named Maria Lopez.', '2025-11-16 23:37:07', 0, NULL),
(935, 9, NULL, NULL, 70, 'The status of animal named Nena has been updated to lost by its owner named Maria Lopez.', '2025-11-16 23:37:07', 0, NULL),
(936, 14, NULL, NULL, 70, 'The status of animal named Nena has been updated to lost by its owner named Maria Lopez.', '2025-11-16 23:37:07', 0, NULL),
(937, 10, NULL, NULL, 70, 'The status of animal named Nena has been updated to lost by its owner named Maria Lopez.', '2025-11-16 23:37:07', 0, NULL),
(938, 13, NULL, NULL, 70, 'The status of animal named Nena has been updated to lost by its owner named Maria Lopez.', '2025-11-16 23:37:07', 0, NULL),
(939, 12, NULL, NULL, 70, 'The status of animal named Nena has been updated to lost by its owner named Maria Lopez.', '2025-11-16 23:37:07', 0, NULL),
(940, 16, NULL, NULL, 70, 'The status of animal named Nena has been updated to lost by its owner named Maria Lopez.', '2025-11-16 23:37:07', 0, NULL),
(942, 11, NULL, NULL, NULL, 'Andrea Cawaya submitted a found report of animal named Rocky.', '2025-11-16 23:39:23', 0, 76),
(943, 15, NULL, NULL, NULL, 'Andrea Cawaya submitted a found report of animal named Rocky.', '2025-11-16 23:39:23', 0, 76),
(944, 9, NULL, NULL, NULL, 'Andrea Cawaya submitted a found report of animal named Rocky.', '2025-11-16 23:39:23', 0, 76),
(945, 14, NULL, NULL, NULL, 'Andrea Cawaya submitted a found report of animal named Rocky.', '2025-11-16 23:39:23', 0, 76),
(946, 10, NULL, NULL, NULL, 'Andrea Cawaya submitted a found report of animal named Rocky.', '2025-11-16 23:39:23', 0, 76),
(947, 13, NULL, NULL, NULL, 'Andrea Cawaya submitted a found report of animal named Rocky.', '2025-11-16 23:39:23', 0, 76),
(948, 12, NULL, NULL, NULL, 'Andrea Cawaya submitted a found report of animal named Rocky.', '2025-11-16 23:39:23', 0, 76),
(949, 16, NULL, NULL, NULL, 'Andrea Cawaya submitted a found report of animal named Rocky.', '2025-11-16 23:39:23', 0, 76),
(950, NULL, 43, NULL, NULL, 'Good news! Someone reported finding your animal named Rocky.', '2025-11-16 23:39:23', 1, 76),
(951, 1, NULL, NULL, 71, 'The status of animal named Kufra has been updated to lost by its owner named James Reid.', '2025-11-17 09:49:54', 1, NULL),
(952, 11, NULL, NULL, 71, 'The status of animal named Kufra has been updated to lost by its owner named James Reid.', '2025-11-17 09:49:54', 0, NULL),
(953, 15, NULL, NULL, 71, 'The status of animal named Kufra has been updated to lost by its owner named James Reid.', '2025-11-17 09:49:54', 0, NULL),
(954, 9, NULL, NULL, 71, 'The status of animal named Kufra has been updated to lost by its owner named James Reid.', '2025-11-17 09:49:54', 0, NULL),
(955, 14, NULL, NULL, 71, 'The status of animal named Kufra has been updated to lost by its owner named James Reid.', '2025-11-17 09:49:54', 0, NULL),
(956, 10, NULL, NULL, 71, 'The status of animal named Kufra has been updated to lost by its owner named James Reid.', '2025-11-17 09:49:54', 0, NULL),
(957, 13, NULL, NULL, 71, 'The status of animal named Kufra has been updated to lost by its owner named James Reid.', '2025-11-17 09:49:54', 0, NULL),
(958, 12, NULL, NULL, 71, 'The status of animal named Kufra has been updated to lost by its owner named James Reid.', '2025-11-17 09:49:54', 0, NULL),
(959, 17, NULL, NULL, 71, 'The status of animal named Kufra has been updated to lost by its owner named James Reid.', '2025-11-17 09:49:54', 1, NULL),
(960, 16, NULL, NULL, 71, 'The status of animal named Kufra has been updated to lost by its owner named James Reid.', '2025-11-17 09:49:54', 0, NULL),
(961, 1, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-17 09:52:00', 1, 77),
(962, 11, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-17 09:52:00', 0, 77),
(963, 15, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-17 09:52:00', 0, 77),
(964, 9, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-17 09:52:00', 0, 77),
(965, 14, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-17 09:52:00', 0, 77),
(966, 10, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-17 09:52:00', 0, 77),
(967, 13, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-17 09:52:00', 0, 77),
(968, 12, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-17 09:52:00', 0, 77),
(969, 17, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-17 09:52:00', 0, 77),
(970, 16, NULL, NULL, NULL, 'joshuaaa submitted a found report of animal named Rocky.', '2025-11-17 09:52:00', 0, 77),
(971, NULL, 43, NULL, NULL, 'Good news! Someone reported finding your animal named Rocky.', '2025-11-17 09:52:00', 1, 77),
(972, 1, NULL, 377, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:02', 0, NULL),
(973, 11, NULL, 377, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:02', 0, NULL),
(974, 15, NULL, 377, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:02', 0, NULL),
(975, 9, NULL, 377, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:02', 0, NULL),
(976, 14, NULL, 377, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:02', 0, NULL),
(977, 10, NULL, 377, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:02', 0, NULL),
(978, 13, NULL, 377, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:02', 0, NULL),
(979, 12, NULL, 377, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:02', 0, NULL),
(980, 17, NULL, 377, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:02', 0, NULL),
(981, 16, NULL, 377, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:02', 0, NULL),
(982, NULL, 43, 377, NULL, 'Your lost animal named Rocky was scanned at a location.', '2025-11-17 09:59:02', 1, NULL),
(983, 1, NULL, 378, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:52', 0, NULL),
(984, 11, NULL, 378, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:52', 0, NULL),
(985, 15, NULL, 378, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:52', 0, NULL),
(986, 9, NULL, 378, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:52', 0, NULL),
(987, 14, NULL, 378, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:52', 0, NULL),
(988, 10, NULL, 378, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:52', 0, NULL),
(989, 13, NULL, 378, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:52', 0, NULL),
(990, 12, NULL, 378, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:52', 0, NULL),
(991, 17, NULL, 378, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:52', 0, NULL),
(992, 16, NULL, 378, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 09:59:52', 0, NULL),
(993, NULL, 43, 378, NULL, 'Your lost animal named Rocky was scanned at a location.', '2025-11-17 09:59:52', 1, NULL),
(994, 1, NULL, NULL, 72, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:09', 0, NULL),
(995, 11, NULL, NULL, 72, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:09', 0, NULL),
(996, 15, NULL, NULL, 72, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:09', 0, NULL),
(997, 9, NULL, NULL, 72, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:09', 0, NULL),
(998, 14, NULL, NULL, 72, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:09', 0, NULL);
INSERT INTO `notification` (`notif_id`, `admin_id`, `owner_id`, `scan_id`, `lf_id`, `message`, `date_notify`, `is_read`, `report_id`) VALUES
(999, 10, NULL, NULL, 72, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:09', 0, NULL),
(1000, 13, NULL, NULL, 72, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:09', 0, NULL),
(1001, 12, NULL, NULL, 72, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:09', 0, NULL),
(1002, 17, NULL, NULL, 72, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:09', 0, NULL),
(1003, 16, NULL, NULL, 72, 'The status of animal named Rocky has been updated to found by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:09', 0, NULL),
(1004, 1, NULL, NULL, 73, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:16', 0, NULL),
(1005, 11, NULL, NULL, 73, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:16', 0, NULL),
(1006, 15, NULL, NULL, 73, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:16', 0, NULL),
(1007, 9, NULL, NULL, 73, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:16', 0, NULL),
(1008, 14, NULL, NULL, 73, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:16', 0, NULL),
(1009, 10, NULL, NULL, 73, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:16', 0, NULL),
(1010, 13, NULL, NULL, 73, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:16', 0, NULL),
(1011, 12, NULL, NULL, 73, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:16', 0, NULL),
(1012, 17, NULL, NULL, 73, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:16', 0, NULL),
(1013, 16, NULL, NULL, 73, 'The status of animal named Rocky has been updated to safe by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:01:16', 0, NULL),
(1014, 1, NULL, 379, NULL, 'Lost animal named Kufra was scanned.', '2025-11-17 10:02:34', 0, NULL),
(1015, 11, NULL, 379, NULL, 'Lost animal named Kufra was scanned.', '2025-11-17 10:02:34', 0, NULL),
(1016, 15, NULL, 379, NULL, 'Lost animal named Kufra was scanned.', '2025-11-17 10:02:34', 0, NULL),
(1017, 9, NULL, 379, NULL, 'Lost animal named Kufra was scanned.', '2025-11-17 10:02:34', 0, NULL),
(1018, 14, NULL, 379, NULL, 'Lost animal named Kufra was scanned.', '2025-11-17 10:02:34', 0, NULL),
(1019, 10, NULL, 379, NULL, 'Lost animal named Kufra was scanned.', '2025-11-17 10:02:34', 0, NULL),
(1020, 13, NULL, 379, NULL, 'Lost animal named Kufra was scanned.', '2025-11-17 10:02:34', 0, NULL),
(1021, 12, NULL, 379, NULL, 'Lost animal named Kufra was scanned.', '2025-11-17 10:02:34', 0, NULL),
(1022, 17, NULL, 379, NULL, 'Lost animal named Kufra was scanned.', '2025-11-17 10:02:34', 0, NULL),
(1023, 16, NULL, 379, NULL, 'Lost animal named Kufra was scanned.', '2025-11-17 10:02:34', 0, NULL),
(1024, NULL, 60, 379, NULL, 'Your lost animal named Kufra was scanned at a location.', '2025-11-17 10:02:34', 1, NULL),
(1025, 1, NULL, NULL, NULL, 'Jhun July submitted a found report of animal named Kufra.', '2025-11-17 10:04:18', 0, 78),
(1026, 11, NULL, NULL, NULL, 'Jhun July submitted a found report of animal named Kufra.', '2025-11-17 10:04:18', 0, 78),
(1027, 15, NULL, NULL, NULL, 'Jhun July submitted a found report of animal named Kufra.', '2025-11-17 10:04:18', 0, 78),
(1028, 9, NULL, NULL, NULL, 'Jhun July submitted a found report of animal named Kufra.', '2025-11-17 10:04:18', 0, 78),
(1029, 14, NULL, NULL, NULL, 'Jhun July submitted a found report of animal named Kufra.', '2025-11-17 10:04:18', 0, 78),
(1030, 10, NULL, NULL, NULL, 'Jhun July submitted a found report of animal named Kufra.', '2025-11-17 10:04:18', 0, 78),
(1031, 13, NULL, NULL, NULL, 'Jhun July submitted a found report of animal named Kufra.', '2025-11-17 10:04:18', 0, 78),
(1032, 12, NULL, NULL, NULL, 'Jhun July submitted a found report of animal named Kufra.', '2025-11-17 10:04:18', 0, 78),
(1033, 17, NULL, NULL, NULL, 'Jhun July submitted a found report of animal named Kufra.', '2025-11-17 10:04:18', 0, 78),
(1034, 16, NULL, NULL, NULL, 'Jhun July submitted a found report of animal named Kufra.', '2025-11-17 10:04:18', 0, 78),
(1035, NULL, 60, NULL, NULL, 'Good news! Someone reported finding your animal named Kufra.', '2025-11-17 10:04:18', 1, 78),
(1036, 1, NULL, NULL, 74, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:12:44', 0, NULL),
(1037, 11, NULL, NULL, 74, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:12:44', 0, NULL),
(1038, 15, NULL, NULL, 74, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:12:44', 0, NULL),
(1039, 9, NULL, NULL, 74, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:12:44', 0, NULL),
(1040, 14, NULL, NULL, 74, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:12:44', 0, NULL),
(1041, 10, NULL, NULL, 74, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:12:44', 0, NULL),
(1042, 13, NULL, NULL, 74, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:12:44', 0, NULL),
(1043, 12, NULL, NULL, 74, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:12:44', 0, NULL),
(1044, 17, NULL, NULL, 74, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:12:44', 0, NULL),
(1045, 16, NULL, NULL, 74, 'The status of animal named Rocky has been updated to lost by its owner named Mary Beatrice Trixia Tupino.', '2025-11-17 10:12:44', 0, NULL),
(1046, 1, NULL, 380, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 10:13:39', 0, NULL),
(1047, 11, NULL, 380, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 10:13:39', 0, NULL),
(1048, 15, NULL, 380, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 10:13:39', 0, NULL),
(1049, 9, NULL, 380, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 10:13:39', 0, NULL),
(1050, 14, NULL, 380, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 10:13:39', 0, NULL),
(1051, 10, NULL, 380, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 10:13:39', 0, NULL),
(1052, 13, NULL, 380, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 10:13:39', 0, NULL),
(1053, 12, NULL, 380, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 10:13:39', 0, NULL),
(1054, 17, NULL, 380, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 10:13:39', 0, NULL),
(1055, 16, NULL, 380, NULL, 'Lost animal named Rocky was scanned.', '2025-11-17 10:13:39', 0, NULL),
(1056, NULL, 43, 380, NULL, 'Your lost animal named Rocky was scanned at a location.', '2025-11-17 10:13:39', 1, NULL),
(1057, 1, NULL, 381, NULL, 'Lost animal named Hugo was scanned.', '2025-11-17 10:38:09', 0, NULL),
(1058, 11, NULL, 381, NULL, 'Lost animal named Hugo was scanned.', '2025-11-17 10:38:09', 0, NULL),
(1059, 15, NULL, 381, NULL, 'Lost animal named Hugo was scanned.', '2025-11-17 10:38:09', 0, NULL),
(1060, 9, NULL, 381, NULL, 'Lost animal named Hugo was scanned.', '2025-11-17 10:38:09', 0, NULL),
(1061, 14, NULL, 381, NULL, 'Lost animal named Hugo was scanned.', '2025-11-17 10:38:09', 0, NULL),
(1062, 10, NULL, 381, NULL, 'Lost animal named Hugo was scanned.', '2025-11-17 10:38:09', 0, NULL),
(1063, 13, NULL, 381, NULL, 'Lost animal named Hugo was scanned.', '2025-11-17 10:38:09', 0, NULL),
(1064, 12, NULL, 381, NULL, 'Lost animal named Hugo was scanned.', '2025-11-17 10:38:09', 0, NULL),
(1065, 17, NULL, 381, NULL, 'Lost animal named Hugo was scanned.', '2025-11-17 10:38:09', 0, NULL),
(1066, 16, NULL, 381, NULL, 'Lost animal named Hugo was scanned.', '2025-11-17 10:38:09', 0, NULL),
(1067, NULL, 33, 381, NULL, 'Your lost animal named Hugo was scanned at a location.', '2025-11-17 10:38:09', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `owners`
--

CREATE TABLE `owners` (
  `owner_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(50) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `birthdate` date NOT NULL,
  `barangay` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('pending','verified','rejected') DEFAULT 'pending',
  `verified_by` text DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `owners`
--

INSERT INTO `owners` (`owner_id`, `first_name`, `middle_name`, `last_name`, `email`, `phone`, `address`, `city`, `province`, `zip_code`, `latitude`, `longitude`, `created_at`, `birthdate`, `barangay`, `password`, `status`, `verified_by`, `verified_at`) VALUES
(33, 'John Philip', 'Panta', 'Yanos', 'yanosjohnphilip@gmail.com', '09918179071', 'Crotons', 'La Carlota City', 'Negros Occidental', '6130', 10.459981, 122.918824, '2025-09-27 06:15:29', '2003-11-25', 'Ayungon', '$2y$10$DEfHAiSWkpu6XbbDdCUzHu0LybEfoBEIKMq2O.7zJzXWHmlcon5ra', 'verified', NULL, NULL),
(34, 'Liezel Mae', 'Panta', 'Yanos', 'liezelpnata@gma.com', '09918179415', 'Purok Crotons', 'La Carlota City', 'Negros Occidental', '6130', 10.459981, 122.918824, '2025-09-27 06:39:29', '2003-01-17', 'Ayungon', '$2y$10$jnxwjD3hCCi4KFR9xWpWp.LuqM/u0CMscuk/9nTxyA7Qjs98onEz6', 'verified', '1', '2025-10-12 23:38:32'),
(35, 'Raiza', 'Bojos', 'Montero', 'raizamontero9@gmail.com', '09941265271', 'Ayungon', 'La Carlota City', 'Negros Occidental', '6130', 10.459981, 122.918824, '2025-09-27 07:07:29', '2006-05-05', 'Ayungon', '$2y$10$Ki0AUGKxmdtWc0xRy2JI6.VZ1N/H7tYJ7A1BMbe/JMimY2ND3C44a', 'verified', '1', '2025-10-14 06:45:26'),
(36, 'Vhon Kian', '', 'Molleno', 'yokianhere@gmail.com', '09637200190', 'prk. camantigue', 'La Carlota City', 'Negros Occidental', '6130', 10.460487, 122.919416, '2025-10-07 02:33:14', '2003-02-11', 'Ayungon', '$2y$10$1QLlOo9RaPjHiwSmTPG8IO7tL/3lJuXbM1Ap/oXD4YEk9v6d1D/rm', 'verified', '1', '2025-10-09 02:53:58'),
(37, 'Joshua', '', 'Dela Cruz', 'joshua@gmail.com', '09918179071', 'Red', 'La Carlota City', 'Negros Occidental', '6130', 10.424677, 122.923209, '2025-10-09 03:01:46', '2000-02-11', 'Barangay I (Poblacion)', '$2y$10$uhvsvbL3ZUgptKUB0f8HX.V8DDok6DBMjJGZoqFaFQ34MUh9KFzpa', 'verified', NULL, NULL),
(38, 'Jgar', 'Galupo', 'Nebrija', 'jgarnebrija@gmail.com', '09483156958', 'Gumamela st.', 'La Carlota City', 'Negros Occidental', '6130', 10.459807, 122.918327, '2025-10-14 01:41:56', '1989-10-13', 'Ayungon', '$2y$10$eRs1bNFWmVUCHiENXSOcFuykR07SMgnNsGpBSvoYN2jyEw.U7oWN6', 'verified', NULL, NULL),
(39, 'Sheena', 'Roquero', 'Panta', 'sheenapanta90@gmail.com', '09931660771', 'Crotons', 'La Carlota City', 'Negros Occidental', '6130', 10.461011, 122.918733, '2025-10-14 02:32:33', '1990-04-20', 'Ayungon', '$2y$10$S53Qo5vIWoBsaOBYW13mbOvI718.lyLgcbuqyRQlnxK9a.ZWodrUm', 'verified', '1', '2025-10-14 02:33:40'),
(40, 'Guianne Ysabelle', 'Alcantara', 'Quingco', 'geyanaugh30@gmail.com', '09459667142', 'Simeon Pidoy', 'La Carlota City', 'Negros Occidental', '6130', 10.417984, 122.913623, '2025-10-15 02:06:26', '2003-07-30', 'Barangay III (Poblacion)', '$2y$10$eXRh5XrEex8qLg262xgssuUbyHGof4j1YKHzXicUhxadP2tlA4Kpi', 'verified', NULL, NULL),
(41, 'Jayvee', 'Francisco', 'Balimiento', 'jayvee@gmail.com', '09123485762', '4th Street FJE Village', 'La Carlota City', 'Negros Occidental', '6130', 10.445484, 122.907122, '2025-10-16 03:03:39', '2003-02-12', 'Batuan', '$2y$10$D/XFYMoXXiohAUyQozPrBONEHTkElDODEtnCYkx6W5ehlLSqGBUoy', 'verified', '1', '2025-10-16 03:14:14'),
(43, 'Mary Beatrice Trixia', 'Aloro', 'Tupino', 'mbttupino03@gmail.com', '09663559139', 'Purok 1', 'La Carlota City', 'Negros Occidental', '6130', 10.405705, 122.996613, '2025-10-16 07:00:46', '2003-12-31', 'La Granja', '$2y$10$JDTOnqJ4UuSYtahp1OMUDeaamsR70PI39W.qTeQQTHb0JGIBJ3kM.', 'verified', '1', '2025-10-16 07:01:26'),
(44, 'Rose', 'Anne', 'Mercado', 'rmmercado@gmail.com', '09102550519', 'Orange St.', 'La Carlota City', 'Negros Occidental', '6130', 10.424677, 122.923209, '2025-10-16 09:48:31', '2005-02-22', 'Barangay I (Poblacion)', '$2y$10$jJ.6V9vqtb3bl6B4f3ajuuZmUKKZPAHuSvfhBekXXCISPGfUseqZK', 'verified', '1', '2025-10-16 09:49:16'),
(45, 'Sofia', 'Ramos', 'Gutierrez', 'sofia.gutierrez@gmail.com', '0927161920', 'Kilid School', 'La Carlota City', 'Negros Occidental', '6130', 10.404673, 122.996138, '2025-10-16 11:00:23', '2000-01-17', 'La Granja', '$2y$10$aklGTNF7yuHf7W7gW7l5aeOd.fzPlDLm25skKEnTbeqThH9hx7bAy', 'rejected', '1', '2025-10-16 11:02:28'),
(46, 'Aliana', 'Gamboa', 'Maglantay', 'alianamaglantay@gmail.com', '09668066105', 'Purok Santan', 'La Carlota City', 'Negros Occidental', '6130', 10.459981, 122.918824, '2025-10-17 06:14:58', '2002-04-20', 'Ayungon', '$2y$10$CsRA56xj2PYCYT0LmpgP2eXoybe5dbfk0XZGI/wcpJYP48WETXtJy', 'verified', '1', '2025-10-17 06:14:58'),
(47, 'Edrian', 'Mobisa', 'Ardenio', 'ardenio9@gmail.com', '09637931263', 'Hda. Elena', 'La Carlota City', 'Negros Occidental', '6130', 10.459981, 122.918824, '2025-10-17 06:25:39', '2001-10-17', 'Ayungon', '$2y$10$y2QiC7WXi/ArZLokvC4NjOx8S0hmr/PDuEP/lHB17vYZdPqRDnIhO', 'verified', '1', '2025-10-17 06:25:56'),
(48, 'Clark', 'Onas', 'Punsalan', 'clark@gmail.com', '09987675241', 'Red', 'La Carlota City', 'Negros Occidental', '6130', 10.405767, 122.996593, '2025-10-17 09:24:43', '2001-11-11', 'La Granja', '$2y$10$CrY.EnJaOXa7.kxY7T.uNu6NnvC6XISV.UWCSl/B35fONTmi/qhrK', 'verified', '1', '2025-10-17 09:24:43'),
(49, 'Gerald', 'Galupo', 'Nebrija', 'gerald@gmail.com', '09483156958', 'Gumamela st.', 'La Carlota City', 'Negros Occidental', '6130', 10.459981, 122.918824, '2025-10-17 09:34:44', '1970-10-17', 'Ayungon', '$2y$10$xo/Ad5CbKno.DFN0i6PuHuHnbF1CK4OFcAkH9gu2vwH1IhuqycZ3G', 'verified', '1', '2025-10-17 09:35:03'),
(50, 'Jerome', 'Maquilan', 'Parcon', 'jerome@gmail.com', '09123456781', 'Bucroz', 'La Carlota City', 'Negros Occidental', '6130', 10.434384, 122.922851, '2025-10-21 12:03:38', '2004-03-13', 'Ayungon', '$2y$10$tuZEvbWyspgmos77DogF9ec0hHU5X/8w6/gJLg9/OGSjdt.Fq9gSO', 'verified', '1', '2025-10-21 12:03:38'),
(51, 'Joshua', '', 'Pescasiosa', 'joshuapescasiosa@gmail.com', '09078945612', 'Scott street', 'La Carlota City', 'Negros Occidental', '6130', 10.397083, 122.970064, '2025-10-28 13:45:56', '2003-11-04', 'Nagasi', '$2y$10$CwQcXJpR5oWzpiI8LgSUx.GTNm77GoBgRwvZF2QoWYQHTNIT1a1AW', 'verified', '1', '2025-10-28 13:45:56'),
(52, 'Patrick', 'Alonzo', 'Reyes', 'patrick.reyes@gmail.com', '09078945667', 'Bario Site', 'La Carlota City', 'Negros Occidental', '6130', 10.444287, 122.908629, '2025-10-28 13:54:38', '2003-06-04', 'Batuan', '$2y$10$mmgDIrS4qwNvrmV8T7ahHef.T97fOE3HHw7pwGTHM9aNKJFxRYBD.', 'verified', '1', '2025-10-28 13:55:18'),
(53, 'Maria', 'Elena', 'Santos', 'maria@gmail.com', '09637931263', 'purok 1', 'La Carlota City', 'Negros Occidental', '6130', 10.390361, 123.046768, '2025-11-02 14:55:48', '2000-11-16', 'Yubo', '$2y$10$MRUrRwm6sLi4ZJF68VZRO.WQDIWNsWU1ALY9gMifO7qTYC2mXTCci', 'verified', '1', '2025-11-11 04:32:44'),
(54, 'Regina', 'Lozada', 'Estrabon', 'reginaestrabon12@gmail.com', '09287211396', 'Purok 1', 'La Carlota City', 'Negros Occidental', '6130', 10.460688, 122.918499, '2025-11-11 04:23:51', '1985-03-12', 'Ayungon', '$2y$10$hGtLXfItBFBlO8FylfhsGeyalKfzpuaZaG9Ar6WhLR2ZjJ43nGT2.', 'verified', '1', '2025-11-11 04:32:40'),
(55, 'Maria', 'Rivera', 'Lopez', 'maria.lopez@gmail.com', '09234567890', '5th Street', 'La Carlota City', 'Negros Occidental', '6130', 10.433556, 122.978082, '2025-11-11 04:26:17', '1992-06-05', 'Balabag', '$2y$10$Ay4OuqZiYsLJa/uQZhfRUem73BZ.zZdm1JFf867bvFIfgZlTlRew6', 'verified', '1', '2025-11-11 04:32:49'),
(56, 'Roberto', 'Pineda', 'Garcia', 'roberto.garcia@gmail.com', '09198765432', 'Guava Street', 'La Carlota City', 'Negros Occidental', '6130', 10.395099, 122.922989, '2025-11-11 04:29:26', '1980-09-09', 'Barangay RSB (Consuelo)', '$2y$10$FpSahcq.A4ATPYyppdw8XOXphbtN/.yy8wCJCajEcmmfe40sGcW0G', 'verified', '1', '2025-11-11 04:32:53'),
(57, 'Angela', 'Cruz', 'Ramos', 'angelaramos@gmail.com', '09051234567', 'Burgos Street', 'La Carlota City', 'Negros Occidental', '6130', 10.430447, 122.919848, '2025-11-11 04:32:13', '1995-10-11', 'Barangay II (Poblacion)', '$2y$10$F9P5dJsMdFZcFdxWxgn21uIvcV6igAXuZ1lu63A65NVHpfmnleoEO', 'verified', '1', '2025-11-11 04:32:56'),
(58, 'Jeri Klyde', 'Roquero', 'Panta', 'sheena@gmail.com', '09931660771', 'Crotons', 'La Carlota City', 'Negros Occidental', '6130', 10.459981, 122.918824, '2025-11-12 15:07:00', '2003-11-24', 'Ayungon', '$2y$10$xxXa4uUOcPQTwx8TzfEDfOYoYbYBp8V1bsVTjia/3vhHtDSNUWQxS', 'rejected', '1', '2025-11-15 00:53:03'),
(59, 'Marivic', 'Nicor', 'Panta', 'marvic@gmail.com', '09637931263', 'Teachers Village', 'La Carlota City', 'Negros Occidental', '6130', 10.394043, 122.923558, '2025-11-15 00:38:34', '1986-11-25', 'Barangay RSB (Consuelo)', '$2y$10$8Tg02ZtJ8lrcRNoF633YQOVWun0g7wjzhFH9STiC4DjDM1VnWOlPC', 'verified', '1', '2025-11-15 00:52:56'),
(60, 'James', 'Bolinao', 'Reid', 'james@gmail.com', '0921456789', 'Cornelia St.', 'La Carlota City', 'Negros Occidental', '6130', 10.419080, 122.947644, '2025-11-17 01:23:54', '2000-06-04', 'Cubay', '$2y$10$Bxa9kERmjTOvrzNgf2wWoeC/pXBh5jwdpYPeP3p7PiTxIstfe0dMS', 'verified', '1', '2025-11-17 01:24:32');

-- --------------------------------------------------------

--
-- Table structure for table `scan_history`
--

CREATE TABLE `scan_history` (
  `scan_id` int(11) NOT NULL,
  `animal_id` int(11) NOT NULL,
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `scan_time` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `scan_history`
--

INSERT INTO `scan_history` (`scan_id`, `animal_id`, `latitude`, `longitude`, `scan_time`) VALUES
(339, 116, 10.460867, 122.918718, '2025-09-27 06:24:39'),
(340, 116, 10.460879, 122.918724, '2025-09-27 06:35:21'),
(341, 116, 10.405205, 122.999425, '2025-09-27 07:17:09'),
(342, 116, 14.608400, 120.975300, '2025-09-27 07:17:16'),
(343, 116, 10.405233, 122.999696, '2025-09-27 07:18:25'),
(344, 117, 10.640511, 122.940453, '2025-10-07 02:48:20'),
(345, 117, 10.640511, 122.940453, '2025-10-07 02:49:04'),
(346, 116, 10.640514, 122.940452, '2025-10-07 11:56:38'),
(347, 119, 10.424370, 122.913802, '2025-10-09 03:13:48'),
(348, 116, 10.640511, 122.940456, '2025-10-10 06:46:30'),
(349, 117, 10.460806, 122.918816, '2025-10-13 05:14:24'),
(350, 121, 10.460809, 122.918746, '2025-10-14 01:49:23'),
(351, 116, 10.405362, 122.999337, '2025-10-14 06:54:29'),
(352, 116, 10.428830, 122.920587, '2025-10-14 06:57:49'),
(353, 116, 10.670714, 122.954539, '2025-10-14 08:45:44'),
(354, 116, 10.460822, 122.918778, '2025-10-14 17:20:38'),
(355, 116, 10.670747, 122.954542, '2025-10-14 17:22:35'),
(356, 116, 10.460520, 122.918208, '2025-10-14 17:28:12'),
(357, 116, 10.674156, 122.956482, '2025-10-16 14:40:58'),
(358, 126, 10.674104, 122.956423, '2025-10-16 15:14:24'),
(359, 126, 10.674150, 122.956478, '2025-10-16 15:18:33'),
(360, 126, 10.682969, 122.955998, '2025-10-16 18:02:41'),
(361, 126, 10.682985, 122.956013, '2025-10-16 18:08:02'),
(362, 126, 10.682994, 122.956007, '2025-10-16 18:40:43'),
(363, 128, 10.682981, 122.956006, '2025-10-16 19:10:21'),
(364, 129, 10.459387, 122.919672, '2025-10-17 14:19:49'),
(365, 117, 10.425269, 122.921783, '2025-10-17 16:40:59'),
(366, 130, 10.425158, 122.921623, '2025-10-17 17:28:26'),
(367, 130, 10.425299, 122.921746, '2025-10-17 17:29:18'),
(368, 116, 10.652083, 122.933191, '2025-10-28 19:58:12'),
(369, 116, 10.431061, 122.843824, '2025-10-30 11:20:17'),
(370, 116, 10.460870, 122.918714, '2025-11-14 20:58:24'),
(371, 126, 10.405214, 122.999663, '2025-11-15 23:36:24'),
(372, 126, 10.405214, 122.999663, '2025-11-15 23:47:19'),
(373, 126, 10.645425, 122.939066, '2025-11-16 22:35:09'),
(374, 126, 10.645496, 122.939160, '2025-11-16 22:43:54'),
(375, 126, 10.460860, 122.918783, '2025-11-16 22:52:20'),
(376, 126, 10.460848, 122.918737, '2025-11-16 22:59:59'),
(377, 126, 14.608400, 120.975300, '2025-11-17 09:59:02'),
(378, 126, 10.645475, 122.939179, '2025-11-17 09:59:52'),
(379, 142, 10.670785, 122.954569, '2025-11-17 10:02:34'),
(380, 126, 10.645468, 122.939167, '2025-11-17 10:13:39'),
(381, 116, 10.460874, 122.918700, '2025-11-17 10:38:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `animals`
--
ALTER TABLE `animals`
  ADD PRIMARY KEY (`animal_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `animal_medication`
--
ALTER TABLE `animal_medication`
  ADD PRIMARY KEY (`animed_id`),
  ADD KEY `animal_id` (`animal_id`),
  ADD KEY `med_id` (`med_id`);

--
-- Indexes for table `found_reports`
--
ALTER TABLE `found_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_animal` (`animal_id`),
  ADD KEY `fk_owner` (`owner_id`);

--
-- Indexes for table `lost_found_history`
--
ALTER TABLE `lost_found_history`
  ADD PRIMARY KEY (`lf_id`),
  ADD KEY `animal_id` (`animal_id`);

--
-- Indexes for table `medication`
--
ALTER TABLE `medication`
  ADD PRIMARY KEY (`med_id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`notif_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `scan_id` (`scan_id`),
  ADD KEY `fk_report` (`report_id`),
  ADD KEY `lf_id` (`lf_id`);

--
-- Indexes for table `owners`
--
ALTER TABLE `owners`
  ADD PRIMARY KEY (`owner_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `scan_history`
--
ALTER TABLE `scan_history`
  ADD PRIMARY KEY (`scan_id`),
  ADD KEY `animal_id` (`animal_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `animals`
--
ALTER TABLE `animals`
  MODIFY `animal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT for table `animal_medication`
--
ALTER TABLE `animal_medication`
  MODIFY `animed_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `found_reports`
--
ALTER TABLE `found_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `lost_found_history`
--
ALTER TABLE `lost_found_history`
  MODIFY `lf_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `medication`
--
ALTER TABLE `medication`
  MODIFY `med_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1068;

--
-- AUTO_INCREMENT for table `owners`
--
ALTER TABLE `owners`
  MODIFY `owner_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `scan_history`
--
ALTER TABLE `scan_history`
  MODIFY `scan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=382;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `animals`
--
ALTER TABLE `animals`
  ADD CONSTRAINT `animals_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`) ON DELETE CASCADE;

--
-- Constraints for table `animal_medication`
--
ALTER TABLE `animal_medication`
  ADD CONSTRAINT `animal_medication_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`),
  ADD CONSTRAINT `animal_medication_ibfk_2` FOREIGN KEY (`med_id`) REFERENCES `medication` (`med_id`);

--
-- Constraints for table `found_reports`
--
ALTER TABLE `found_reports`
  ADD CONSTRAINT `fk_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_owner` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`) ON DELETE CASCADE;

--
-- Constraints for table `lost_found_history`
--
ALTER TABLE `lost_found_history`
  ADD CONSTRAINT `lost_found_history_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`);

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `fk_notification_lf` FOREIGN KEY (`lf_id`) REFERENCES `lost_found_history` (`lf_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_report` FOREIGN KEY (`report_id`) REFERENCES `found_reports` (`report_id`),
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`admin_id`),
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`owner_id`) REFERENCES `owners` (`owner_id`),
  ADD CONSTRAINT `notification_ibfk_3` FOREIGN KEY (`scan_id`) REFERENCES `scan_history` (`scan_id`);

--
-- Constraints for table `scan_history`
--
ALTER TABLE `scan_history`
  ADD CONSTRAINT `scan_history_ibfk_1` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`animal_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
