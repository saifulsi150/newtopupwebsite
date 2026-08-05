-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 25, 2026 at 08:07 AM
-- Server version: 12.1.2-MariaDB
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `vnbazer121`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `email`, `created_at`, `last_login`) VALUES
(1, 'vnbazersaiful51', '$2b$10$gHfSCmK/GhirFeghcULx8OR2mC.ueNnMvyWi563j0rdGKW24o4V3m', NULL, '2025-12-21 07:58:28', '2026-07-16 13:59:52'),
(3, 'vnbazer121', '$2b$10$oiWLc6nZdAzbUaJVQON3cevmd8aPvoi.Bf64HspgOp/a.PPxcvq1a', NULL, '2026-06-01 18:31:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `code_usage`
--

CREATE TABLE `code_usage` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `code_id` int(11) NOT NULL,
  `used_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `error_screenshots`
--

CREATE TABLE `error_screenshots` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `split_id` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `screenshot_path` varchar(500) NOT NULL,
  `screenshot_filename` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `error_screenshots`
--

INSERT INTO `error_screenshots` (`id`, `order_id`, `split_id`, `error_message`, `screenshot_path`, `screenshot_filename`, `created_at`) VALUES
(578, 1, 6553, 'Invalid uid or not BD server', 'C:\\bot\\logs\\error_order_1_split_6553_1783789683553.png', 'error_order_1_split_6553_1783789683553.png', '2026-07-11 17:08:03');

-- --------------------------------------------------------

--
-- Table structure for table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `order_reference` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','error','success') DEFAULT 'info',
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logs`
--

INSERT INTO `logs` (`id`, `order_id`, `order_reference`, `message`, `type`, `details`, `created_at`) VALUES
(1, NULL, NULL, 'CAPTCHA test initiated from admin dashboard', 'info', NULL, '2026-07-16 20:36:21'),
(2, NULL, NULL, 'Error starting CAPTCHA test: Unexpected identifier \'enterUID\'', 'error', NULL, '2026-07-16 20:36:21'),
(3, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-17 00:07:09'),
(4, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-17 00:07:13'),
(5, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-17 01:34:53'),
(6, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-17 09:21:06'),
(7, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-17 13:39:21'),
(8, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-17 13:39:23'),
(9, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-17 13:39:33'),
(10, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-17 13:39:35'),
(11, NULL, NULL, 'Received Humayun webhook from Website to /webhook/humayun/order', 'info', NULL, '2026-07-17 20:21:48'),
(12, NULL, NULL, 'Humayun API key validated successfully for order 09b6b666-a378-43ca-a206-31bc5987f5b4 (Main website)', 'info', NULL, '2026-07-17 20:21:48'),
(13, NULL, NULL, 'Processing Humayun order 09b6b666-a378-43ca-a206-31bc5987f5b4 with UID: 8090024844', 'info', NULL, '2026-07-17 20:21:48'),
(14, NULL, NULL, 'Order structure summary: {\"has_meta_data\":true,\"meta_data_count\":1,\"has_line_items\":true,\"line_items_count\":1,\"meta_data_keys\":\"Player ID Code\"}', 'info', NULL, '2026-07-17 20:21:48'),
(15, NULL, NULL, '📦 Processing order 09b6b666-a378-43ca-a206-31bc5987f5b4', 'info', NULL, '2026-07-17 20:21:48'),
(16, NULL, NULL, 'Found and cleaned UID in order meta_data (exact match): 8090024844 (key: Player ID Code)', 'info', NULL, '2026-07-17 20:21:48'),
(17, NULL, NULL, 'Setting order 2 to processing status (0 orders processing, puppeteer running: false)', 'info', NULL, '2026-07-17 20:21:48'),
(18, NULL, NULL, 'Inserting log entry for order 2', 'info', NULL, '2026-07-17 20:21:49'),
(19, 2, '09b6b666-a378-43ca-a206-31bc5987f5b4', 'New order received: 355 Diamond', 'info', NULL, '2026-07-17 20:21:49'),
(20, NULL, NULL, 'Processing requested amount: 355 diamonds', 'info', NULL, '2026-07-17 20:21:49'),
(21, NULL, NULL, 'Using pre-defined optimal split for 355 diamonds: 240 + 115', 'info', NULL, '2026-07-17 20:21:49'),
(22, NULL, NULL, 'No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-17 20:21:49'),
(23, NULL, NULL, '❌ Order processing error: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-17 20:21:49'),
(24, 2, '09b6b666-a378-43ca-a206-31bc5987f5b4', 'Order processing failed: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-17 20:21:49'),
(25, NULL, NULL, 'Failed to process Humayun order 09b6b666-a378-43ca-a206-31bc5987f5b4: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-17 20:21:49'),
(26, NULL, '09b6b666-a378-43ca-a206-31bc5987f5b4', 'Humayun order processing failed: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-17 20:21:49'),
(27, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-18 01:53:28'),
(28, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-18 15:54:06'),
(29, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-19 04:47:57'),
(30, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-19 08:46:39'),
(31, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-19 12:12:15'),
(32, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-19 13:43:05'),
(33, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-19 16:20:09'),
(34, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-19 19:06:08'),
(35, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-19 19:06:08'),
(36, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-19 19:06:09'),
(37, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-19 19:06:11'),
(38, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-19 19:06:13'),
(39, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:24'),
(40, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:30'),
(41, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:34'),
(42, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:36'),
(43, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:37'),
(44, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:38'),
(45, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:43'),
(46, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:44'),
(47, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:45'),
(48, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:45'),
(49, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:45'),
(50, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:49'),
(51, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:53'),
(52, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:56'),
(53, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:56'),
(54, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:58'),
(55, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:25:58'),
(56, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:01'),
(57, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:01'),
(58, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:02'),
(59, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:02'),
(60, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:03'),
(61, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:05'),
(62, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:08'),
(63, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:08'),
(64, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:08'),
(65, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:11'),
(66, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:12'),
(67, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:13'),
(68, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:13'),
(69, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:13'),
(70, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:16'),
(71, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:18'),
(72, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:19'),
(73, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:21'),
(74, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:26:22'),
(75, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:40:43'),
(76, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:41:07'),
(77, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:41:18'),
(78, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:41:20'),
(79, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:41:21'),
(80, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:41:33'),
(81, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:41:36'),
(82, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:41:57'),
(83, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:42:22'),
(84, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 02:42:28'),
(85, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:11:06'),
(86, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:11:44'),
(87, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:12:08'),
(88, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:12:10'),
(89, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:12:13'),
(90, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:12:19'),
(91, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:12:28'),
(92, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:12:38'),
(93, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:12:39'),
(94, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:12:46'),
(95, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:12:57'),
(96, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:12:58'),
(97, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:13:44'),
(98, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:13:45'),
(99, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:13:57'),
(100, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:14:19'),
(101, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:14:24'),
(102, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:14:46'),
(103, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:15:26'),
(104, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:15:29'),
(105, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:15:40'),
(106, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:16:53'),
(107, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:17:16'),
(108, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:17:19'),
(109, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:17:23'),
(110, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:17:34'),
(111, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:17:35'),
(112, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:17:37'),
(113, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:18:08'),
(114, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:18:15'),
(115, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:18:16'),
(116, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:18:16'),
(117, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:18:17'),
(118, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:18:22'),
(119, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:18:25'),
(120, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:18:42'),
(121, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 03:18:43'),
(122, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 15:47:45'),
(123, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 16:51:30'),
(124, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 16:51:30'),
(125, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 18:56:35'),
(126, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 18:58:47'),
(127, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 19:21:57'),
(128, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-20 19:22:00'),
(129, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 03:28:36'),
(130, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 03:28:37'),
(131, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 03:28:44'),
(132, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 03:28:47'),
(133, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 03:28:48'),
(134, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 03:28:53'),
(135, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 03:43:37'),
(136, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 03:43:39'),
(137, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 03:43:40'),
(138, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 03:43:41'),
(139, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 03:43:48'),
(140, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 03:43:51'),
(141, NULL, NULL, 'Received Humayun webhook from Website to /webhook/humayun/order', 'info', NULL, '2026-07-21 09:19:25'),
(142, NULL, NULL, 'Humayun API key validated successfully for order 78bb39d4-4b58-41f5-b4bc-9352da45f8ac (Main website)', 'info', NULL, '2026-07-21 09:19:25'),
(143, NULL, NULL, 'Processing Humayun order 78bb39d4-4b58-41f5-b4bc-9352da45f8ac with UID: 9492705899', 'info', NULL, '2026-07-21 09:19:25'),
(144, NULL, NULL, 'Found and cleaned UID in order meta_data (exact match): 9492705899 (key: Player ID Code)', 'info', NULL, '2026-07-21 09:19:25'),
(145, NULL, NULL, '📦 Processing order 78bb39d4-4b58-41f5-b4bc-9352da45f8ac', 'info', NULL, '2026-07-21 09:19:25'),
(146, NULL, NULL, 'Order structure summary: {\"has_meta_data\":true,\"meta_data_count\":1,\"has_line_items\":true,\"line_items_count\":1,\"meta_data_keys\":\"Player ID Code\"}', 'info', NULL, '2026-07-21 09:19:25'),
(147, NULL, NULL, 'Setting order 3 to processing status (0 orders processing, puppeteer running: false)', 'info', NULL, '2026-07-21 09:19:25'),
(148, NULL, NULL, 'Inserting log entry for order 3', 'info', NULL, '2026-07-21 09:19:26'),
(149, 3, '78bb39d4-4b58-41f5-b4bc-9352da45f8ac', 'New order received: 240 Diamond', 'info', NULL, '2026-07-21 09:19:26'),
(150, NULL, NULL, 'Processing requested amount: 240 diamonds', 'info', NULL, '2026-07-21 09:19:26'),
(151, NULL, NULL, 'Found exact match for 240 diamonds', 'info', NULL, '2026-07-21 09:19:26'),
(152, NULL, NULL, 'No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-21 09:19:26'),
(153, NULL, NULL, '❌ Order processing error: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-21 09:19:26'),
(154, 3, '78bb39d4-4b58-41f5-b4bc-9352da45f8ac', 'Order processing failed: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-21 09:19:26'),
(155, NULL, NULL, 'Failed to process Humayun order 78bb39d4-4b58-41f5-b4bc-9352da45f8ac: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-21 09:19:26'),
(156, NULL, '78bb39d4-4b58-41f5-b4bc-9352da45f8ac', 'Humayun order processing failed: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-21 09:19:26'),
(157, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 17:38:30'),
(158, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 18:27:09'),
(159, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 18:27:11'),
(160, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 18:27:12'),
(161, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 18:27:14'),
(162, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 18:27:17'),
(163, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 18:27:20'),
(164, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-21 20:32:56'),
(165, NULL, NULL, 'Received Humayun webhook from Website to /webhook/humayun/order', 'info', NULL, '2026-07-22 06:42:29'),
(166, NULL, NULL, 'Humayun API key validated successfully for order 29a3997a-2ffa-4034-aa31-4ef8caa696f0 (Main website)', 'info', NULL, '2026-07-22 06:42:29'),
(167, NULL, NULL, '📦 Processing order 29a3997a-2ffa-4034-aa31-4ef8caa696f0', 'info', NULL, '2026-07-22 06:42:29'),
(168, NULL, NULL, 'Order structure summary: {\"has_meta_data\":true,\"meta_data_count\":1,\"has_line_items\":true,\"line_items_count\":1,\"meta_data_keys\":\"Player ID Code\"}', 'info', NULL, '2026-07-22 06:42:29'),
(169, NULL, NULL, 'Processing Humayun order 29a3997a-2ffa-4034-aa31-4ef8caa696f0 with UID: 6733111071', 'info', NULL, '2026-07-22 06:42:29'),
(170, NULL, NULL, 'Found and cleaned UID in order meta_data (exact match): 6733111071 (key: Player ID Code)', 'info', NULL, '2026-07-22 06:42:29'),
(171, NULL, NULL, 'Setting order 4 to processing status (0 orders processing, puppeteer running: false)', 'info', NULL, '2026-07-22 06:42:29'),
(172, NULL, NULL, 'Inserting log entry for order 4', 'info', NULL, '2026-07-22 06:42:29'),
(173, 4, '29a3997a-2ffa-4034-aa31-4ef8caa696f0', 'New order received: 355 Diamond', 'info', NULL, '2026-07-22 06:42:29'),
(174, NULL, NULL, 'Using pre-defined optimal split for 355 diamonds: 240 + 115', 'info', NULL, '2026-07-22 06:42:29'),
(175, NULL, NULL, 'Processing requested amount: 355 diamonds', 'info', NULL, '2026-07-22 06:42:29'),
(176, NULL, NULL, 'No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-22 06:42:29'),
(177, NULL, NULL, '❌ Order processing error: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-22 06:42:29'),
(178, 4, '29a3997a-2ffa-4034-aa31-4ef8caa696f0', 'Order processing failed: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-22 06:42:29'),
(179, NULL, NULL, 'Failed to process Humayun order 29a3997a-2ffa-4034-aa31-4ef8caa696f0: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-22 06:42:29'),
(180, NULL, '29a3997a-2ffa-4034-aa31-4ef8caa696f0', 'Humayun order processing failed: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-22 06:42:29'),
(181, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-22 09:01:49'),
(182, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-22 13:42:43'),
(183, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-22 14:59:38'),
(184, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-22 17:48:38'),
(185, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-23 07:25:25'),
(186, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-23 11:08:01'),
(187, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-24 21:48:18'),
(188, NULL, NULL, 'API request rejected: Missing API key', 'warning', NULL, '2026-07-24 22:49:01'),
(189, NULL, NULL, 'Received Humayun webhook from Website to /webhook/humayun/order', 'info', NULL, '2026-07-25 01:13:23'),
(190, NULL, NULL, 'Humayun API key validated successfully for order ddc98c21-4014-439b-91c2-7a1f1fa9b2d8 (Main website)', 'info', NULL, '2026-07-25 01:13:23'),
(191, NULL, NULL, 'Processing Humayun order ddc98c21-4014-439b-91c2-7a1f1fa9b2d8 with UID: 8162907462', 'info', NULL, '2026-07-25 01:13:23'),
(192, NULL, NULL, '📦 Processing order ddc98c21-4014-439b-91c2-7a1f1fa9b2d8', 'info', NULL, '2026-07-25 01:13:23'),
(193, NULL, NULL, 'Order structure summary: {\"has_meta_data\":true,\"meta_data_count\":1,\"has_line_items\":true,\"line_items_count\":1,\"meta_data_keys\":\"Player ID Code\"}', 'info', NULL, '2026-07-25 01:13:23'),
(194, NULL, NULL, 'Found and cleaned UID in order meta_data (exact match): 8162907462 (key: Player ID Code)', 'info', NULL, '2026-07-25 01:13:23'),
(195, NULL, NULL, 'Setting order 5 to processing status (0 orders processing, puppeteer running: false)', 'info', NULL, '2026-07-25 01:13:23'),
(196, NULL, NULL, 'Inserting log entry for order 5', 'info', NULL, '2026-07-25 01:13:23'),
(197, 5, 'ddc98c21-4014-439b-91c2-7a1f1fa9b2d8', 'New order received: 480 Diamond', 'info', NULL, '2026-07-25 01:13:23'),
(198, NULL, NULL, 'Processing requested amount: 480 diamonds', 'info', NULL, '2026-07-25 01:13:23'),
(199, NULL, NULL, 'Using pre-defined optimal split for 480 diamonds: 240 + 240', 'info', NULL, '2026-07-25 01:13:23'),
(200, NULL, NULL, 'No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-25 01:13:23'),
(201, NULL, NULL, '❌ Order processing error: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-25 01:13:23'),
(202, 5, 'ddc98c21-4014-439b-91c2-7a1f1fa9b2d8', 'Order processing failed: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-25 01:13:23'),
(203, NULL, NULL, 'Failed to process Humayun order ddc98c21-4014-439b-91c2-7a1f1fa9b2d8: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-25 01:13:23'),
(204, NULL, 'ddc98c21-4014-439b-91c2-7a1f1fa9b2d8', 'Humayun order processing failed: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-25 01:13:23'),
(205, NULL, NULL, 'Received Humayun webhook from Website to /webhook/humayun/order', 'info', NULL, '2026-07-25 02:30:57'),
(206, NULL, NULL, 'Humayun API key validated successfully for order 2608d4f8-ef05-46a4-8710-628c6d1791d8 (Main website)', 'info', NULL, '2026-07-25 02:30:57'),
(207, NULL, NULL, 'Processing Humayun order 2608d4f8-ef05-46a4-8710-628c6d1791d8 with UID: 2664840438', 'info', NULL, '2026-07-25 02:30:57'),
(208, NULL, NULL, '📦 Processing order 2608d4f8-ef05-46a4-8710-628c6d1791d8', 'info', NULL, '2026-07-25 02:30:57'),
(209, NULL, NULL, 'Order structure summary: {\"has_meta_data\":true,\"meta_data_count\":1,\"has_line_items\":true,\"line_items_count\":1,\"meta_data_keys\":\"Player ID Code\"}', 'info', NULL, '2026-07-25 02:30:57'),
(210, NULL, NULL, 'Found and cleaned UID in order meta_data (exact match): 2664840438 (key: Player ID Code)', 'info', NULL, '2026-07-25 02:30:57'),
(211, NULL, NULL, 'Setting order 6 to processing status (0 orders processing, puppeteer running: false)', 'info', NULL, '2026-07-25 02:30:57'),
(212, NULL, NULL, 'Inserting log entry for order 6', 'info', NULL, '2026-07-25 02:30:57'),
(213, 6, '2608d4f8-ef05-46a4-8710-628c6d1791d8', 'New order received: 240 Diamond', 'info', NULL, '2026-07-25 02:30:57'),
(214, NULL, NULL, 'Processing requested amount: 240 diamonds', 'info', NULL, '2026-07-25 02:30:57'),
(215, NULL, NULL, 'Found exact match for 240 diamonds', 'info', NULL, '2026-07-25 02:30:57'),
(216, NULL, NULL, 'No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-25 02:30:57'),
(217, NULL, NULL, '❌ Order processing error: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-25 02:30:57'),
(218, 6, '2608d4f8-ef05-46a4-8710-628c6d1791d8', 'Order processing failed: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-25 02:30:57'),
(219, NULL, NULL, 'Failed to process Humayun order 2608d4f8-ef05-46a4-8710-628c6d1791d8: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-25 02:30:57'),
(220, NULL, '2608d4f8-ef05-46a4-8710-628c6d1791d8', 'Humayun order processing failed: No suitable code found for split 240 diamonds', 'error', NULL, '2026-07-25 02:30:57');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `uid` varchar(50) NOT NULL,
  `diamond_quantity` varchar(100) NOT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `api_key` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_id`, `uid`, `diamond_quantity`, `status`, `api_key`, `created_at`, `updated_at`) VALUES
(1, 'MND19231', '2211623091', '115 Diamond', 'failed', NULL, '2026-07-11 23:07:55', '2026-07-11 23:08:04'),
(2, '09b6b666-a378-43ca-a206-31bc5987f5b4', '8090024844', '355 Diamond', 'failed', NULL, '2026-07-17 20:21:48', '2026-07-17 20:21:49'),
(3, '78bb39d4-4b58-41f5-b4bc-9352da45f8ac', '9492705899', '240 Diamond', 'failed', NULL, '2026-07-21 09:19:26', '2026-07-21 09:19:26'),
(4, '29a3997a-2ffa-4034-aa31-4ef8caa696f0', '6733111071', '355 Diamond', 'failed', NULL, '2026-07-22 06:42:29', '2026-07-22 06:42:29'),
(5, 'ddc98c21-4014-439b-91c2-7a1f1fa9b2d8', '8162907462', '480 Diamond', 'failed', NULL, '2026-07-25 01:13:23', '2026-07-25 01:13:23'),
(6, '2608d4f8-ef05-46a4-8710-628c6d1791d8', '2664840438', '240 Diamond', 'failed', NULL, '2026-07-25 02:30:57', '2026-07-25 02:30:57');

-- --------------------------------------------------------

--
-- Table structure for table `order_splits`
--

CREATE TABLE `order_splits` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `split_index` int(11) NOT NULL,
  `diamond_amount` varchar(50) NOT NULL,
  `code_id` int(11) DEFAULT NULL,
  `code_used` varchar(255) DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `payment_url` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_splits`
--

INSERT INTO `order_splits` (`id`, `order_id`, `split_index`, `diamond_amount`, `code_id`, `code_used`, `status`, `error_message`, `payment_url`, `started_at`, `completed_at`, `created_at`, `updated_at`) VALUES
(6553, 1, 1, '115', 1, 'BDMB-J-S-04221152 4547-5776-7294-5164', 'failed', 'Invalid uid or not BD server', NULL, '2026-07-11 17:08:26', '2026-07-11 17:08:32', '2026-07-11 17:07:56', '2026-07-11 17:08:32');

-- --------------------------------------------------------

--
-- Table structure for table `redeem_codes`
--

CREATE TABLE `redeem_codes` (
  `id` int(11) NOT NULL,
  `code` varchar(255) NOT NULL,
  `uc_value` int(11) NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `type` enum('BDMB','UPBD') NOT NULL,
  `date_added` datetime DEFAULT current_timestamp(),
  `date_used` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `redeem_codes`
--

INSERT INTO `redeem_codes` (`id`, `code`, `uc_value`, `used`, `type`, `date_added`, `date_used`) VALUES
(1, 'BDMB-J-S-04221152 4547-5776-7294-5164', 80, 0, 'BDMB', '2026-07-11 23:07:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `settings` longtext NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `settings`, `created_at`, `updated_at`) VALUES
(1, '{\"website_api_url\":\"https://eyzgmqloxysarnamxmfu.supabase.co/functions/v1\",\"website_api_key\":\"qSkDiWlMDEGtzqDHfhZTttms2bjgjgsjgdTOPUP\",\"automation_enabled\":true,\"resellers\":[{\"name\":\"TOPUP WEBSITE\",\"api_url\":\"https://apitopup.oktopupbd.com\",\"api_key\":\"uejjfufhjiakwkmwhy\"}]}', '2025-12-21 20:26:21', '2025-12-21 20:26:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `code_usage`
--
ALTER TABLE `code_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `code_id` (`code_id`);

--
-- Indexes for table `error_screenshots`
--
ALTER TABLE `error_screenshots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_split_id` (`split_id`);

--
-- Indexes for table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `idx_api_key` (`api_key`);

--
-- Indexes for table `order_splits`
--
ALTER TABLE `order_splits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_split` (`order_id`,`split_index`),
  ADD KEY `code_id` (`code_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `redeem_codes`
--
ALTER TABLE `redeem_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `code_usage`
--
ALTER TABLE `code_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `error_screenshots`
--
ALTER TABLE `error_screenshots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=579;

--
-- AUTO_INCREMENT for table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=221;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_splits`
--
ALTER TABLE `order_splits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6554;

--
-- AUTO_INCREMENT for table `redeem_codes`
--
ALTER TABLE `redeem_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `code_usage`
--
ALTER TABLE `code_usage`
  ADD CONSTRAINT `code_usage_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `code_usage_ibfk_2` FOREIGN KEY (`code_id`) REFERENCES `redeem_codes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `error_screenshots`
--
ALTER TABLE `error_screenshots`
  ADD CONSTRAINT `error_screenshots_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `error_screenshots_ibfk_2` FOREIGN KEY (`split_id`) REFERENCES `order_splits` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_splits`
--
ALTER TABLE `order_splits`
  ADD CONSTRAINT `order_splits_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_splits_ibfk_2` FOREIGN KEY (`code_id`) REFERENCES `redeem_codes` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
