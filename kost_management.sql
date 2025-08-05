-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 05, 2025 at 01:14 PM
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
-- Database: `kost_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_logs`
--

CREATE TABLE `access_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `rfid_uid` varchar(50) NOT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `access_granted` tinyint(1) NOT NULL DEFAULT 1,
  `reason` varchar(255) DEFAULT NULL,
  `accessed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `access_logs`
--

INSERT INTO `access_logs` (`id`, `user_id`, `room_id`, `rfid_uid`, `device_id`, `access_granted`, `reason`, `accessed_at`) VALUES
(1, 2, 1, 'RF001A2B3C4D', 'DOOR_A01', 1, NULL, '2025-07-01 03:32:34'),
(2, 3, 2, 'RF002E5F6G7H', 'DOOR_A02', 1, NULL, '2025-07-01 00:32:34'),
(3, 4, 4, 'RF003I8J9K0L', 'DOOR_B01', 1, NULL, '2025-07-01 05:02:34'),
(4, NULL, 1, 'RF999UNKNOWN', 'DOOR_A01', 0, 'Unknown RFID card', '2025-06-30 05:32:34'),
(5, NULL, NULL, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 03:35:06'),
(6, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 03:35:22'),
(7, 5, 7, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 03:35:44'),
(8, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 03:36:16'),
(9, 5, 7, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 03:36:24'),
(10, NULL, NULL, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 03:36:32'),
(11, NULL, NULL, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 03:36:39'),
(12, NULL, NULL, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 03:36:48'),
(13, NULL, NULL, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 03:36:56'),
(14, NULL, NULL, '2384261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 03:37:02'),
(15, NULL, NULL, '2384261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 03:37:10'),
(16, NULL, NULL, '2384261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 03:39:34'),
(17, NULL, NULL, '2384261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 03:39:46'),
(18, NULL, NULL, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 03:39:54'),
(19, NULL, NULL, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 03:40:13'),
(20, NULL, NULL, '2384261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 03:40:40'),
(21, NULL, NULL, '2384261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 03:46:14'),
(22, NULL, NULL, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 03:49:02'),
(23, NULL, NULL, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 03:49:08'),
(24, NULL, NULL, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 03:49:41'),
(25, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 03:54:42'),
(26, NULL, NULL, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 04:08:33'),
(27, NULL, NULL, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 04:11:01'),
(28, NULL, NULL, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 04:11:08'),
(29, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 04:18:44'),
(30, 5, 7, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:18:54'),
(31, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:18:58'),
(32, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:19:05'),
(33, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:19:12'),
(34, NULL, NULL, '2384261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 04:19:19'),
(35, NULL, NULL, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 04:19:28'),
(36, 5, 7, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:19:34'),
(37, NULL, NULL, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 04:19:39'),
(38, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:20:18'),
(39, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:20:25'),
(40, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:20:33'),
(41, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:20:38'),
(42, NULL, NULL, '2384261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 04:20:46'),
(43, NULL, NULL, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 04:20:54'),
(44, NULL, NULL, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, Auto User 261E!', '2025-07-03 04:21:00'),
(45, NULL, NULL, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 04:21:57'),
(46, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 04:22:15'),
(47, NULL, NULL, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 04:23:00'),
(48, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 0, 'Card is inactive', '2025-07-03 04:23:17'),
(49, 5, 7, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:23:23'),
(50, 5, 7, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:23:31'),
(51, 5, 7, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:23:40'),
(52, 5, 7, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:24:50'),
(53, NULL, NULL, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, Auto User 1ABB!', '2025-07-03 04:24:57'),
(54, NULL, NULL, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, Auto User 1ABB!', '2025-07-03 04:25:04'),
(55, NULL, NULL, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Auto User 3003!', '2025-07-03 04:41:06'),
(56, 5, 7, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:41:32'),
(57, 5, 7, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS!', '2025-07-03 04:41:47'),
(58, NULL, 7, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 08:26:27'),
(59, 5, 7, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 08:26:54'),
(60, NULL, 7, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 08:27:02'),
(61, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 08:27:16'),
(62, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 08:27:27'),
(63, 5, 7, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 08:27:32'),
(64, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 08:28:01'),
(65, 5, 7, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 08:28:08'),
(66, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 08:28:16'),
(67, 5, 7, '32AF2C1E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 08:29:16'),
(68, 5, 7, '32AF2C1E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 08:49:00'),
(69, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 09:04:32'),
(70, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 09:04:37'),
(71, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 09:04:52'),
(72, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 09:05:58'),
(73, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 09:06:00'),
(74, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 09:06:24'),
(75, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 09:06:25'),
(76, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 09:07:06'),
(77, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 09:07:08'),
(78, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 09:07:16'),
(79, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 09:07:23'),
(80, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 09:25:21'),
(81, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 09:26:10'),
(82, 5, 7, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 09:26:19'),
(83, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 09:26:25'),
(84, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 09:26:35'),
(85, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 09:26:51'),
(86, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 09:26:56'),
(87, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 09:28:10'),
(88, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 09:28:23'),
(89, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 09:28:23'),
(90, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 09:28:32'),
(91, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 09:28:35'),
(92, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 09:28:47'),
(93, 5, 7, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 09:28:51'),
(94, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 09:29:22'),
(95, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 09:29:53'),
(96, 5, 7, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 09:30:01'),
(97, NULL, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 09:30:04'),
(98, NULL, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 09:30:10'),
(99, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 09:30:14'),
(100, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 09:34:04'),
(101, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 09:34:16'),
(102, 5, 7, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 09:34:32'),
(103, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 09:34:32'),
(104, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 09:42:52'),
(105, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 09:43:03'),
(106, NULL, 1, '00000070', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 09:43:22'),
(107, 5, 7, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 09:43:22'),
(108, NULL, 7, '443FDA8A', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 09:43:41'),
(109, NULL, 7, '443FDA8A', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 09:44:21'),
(110, 5, 7, '443FDA8A', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 09:44:29'),
(111, NULL, 1, '6FF1E711', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 10:15:25'),
(112, NULL, 1, '044C4192C07380', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 10:15:37'),
(113, NULL, 1, '044C4192C07380', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 10:15:53'),
(114, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 10:26:34'),
(115, NULL, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 10:27:01'),
(116, NULL, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 10:27:05'),
(117, NULL, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 10:27:26'),
(118, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 10:27:29'),
(119, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 10:27:32'),
(120, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 10:27:40'),
(121, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 10:29:31'),
(122, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual close_door: Admin manual close door', '2025-07-03 10:29:36'),
(123, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 10:29:41'),
(124, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual close_door: Admin manual close door', '2025-07-03 10:29:46'),
(125, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual close_door: Admin manual close door', '2025-07-03 10:29:51'),
(126, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 10:29:53'),
(127, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual close_door: Admin manual close door', '2025-07-03 10:29:55'),
(128, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 10:30:22'),
(129, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 10:30:30'),
(130, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 10:30:34'),
(131, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual open door', '2025-07-03 10:34:52'),
(132, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 10:35:08'),
(133, NULL, 1, '00000070', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 10:35:08'),
(134, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 10:35:31'),
(135, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 10:35:45'),
(136, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 10:42:57'),
(137, 5, 7, '01CB261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 10:43:08'),
(138, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 10:43:08'),
(139, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 13:35:01'),
(140, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual close_door: Admin manual close door', '2025-07-03 13:36:41'),
(141, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 13:37:10'),
(142, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 13:37:15'),
(143, NULL, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 13:37:52'),
(144, NULL, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 13:37:59'),
(145, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 13:38:04'),
(146, NULL, 2, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 14:54:29'),
(147, NULL, 2, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 14:55:24'),
(148, NULL, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 14:55:47'),
(149, NULL, 2, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 14:56:04'),
(150, NULL, 2, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 14:56:12'),
(151, NULL, 2, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 14:56:57'),
(152, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 14:57:05'),
(153, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 14:57:14'),
(154, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-03 15:07:08'),
(155, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 15:07:13'),
(156, NULL, 7, 'C8D81ABB', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-03 15:07:38'),
(157, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 15:08:02'),
(158, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 15:08:07'),
(159, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 15:08:22'),
(160, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 15:08:24'),
(161, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 15:08:42'),
(162, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 15:08:47'),
(163, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-03 15:09:12'),
(164, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-03 15:09:16'),
(165, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 01:02:33'),
(166, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 01:04:29'),
(167, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 01:05:24'),
(168, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 01:05:46'),
(169, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 01:37:59'),
(170, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 01:38:07'),
(171, NULL, 7, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 01:38:16'),
(172, NULL, 7, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 01:40:44'),
(173, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 01:40:52'),
(174, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Manual control from compact dashboard', '2025-07-04 02:01:24'),
(175, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 02:01:55'),
(176, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 02:02:13'),
(177, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Manual control from compact dashboard', '2025-07-04 02:02:22'),
(178, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 02:27:04'),
(179, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 02:27:10'),
(180, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Manual control from compact dashboard', '2025-07-04 02:27:24'),
(181, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Manual control from compact dashboard', '2025-07-04 02:27:56'),
(182, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Manual control from compact dashboard', '2025-07-04 02:28:01'),
(183, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Manual control from compact dashboard', '2025-07-04 02:28:07'),
(184, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Manual control from compact dashboard', '2025-07-04 02:28:09'),
(185, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Manual control from compact dashboard', '2025-07-04 02:28:16'),
(186, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Manual control from compact dashboard', '2025-07-04 02:28:19'),
(187, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 02:35:45'),
(188, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 02:35:55'),
(189, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 02:36:05'),
(190, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:05'),
(191, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:06'),
(192, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:08'),
(193, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:10'),
(194, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:11'),
(195, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:13'),
(196, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:15'),
(197, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:28'),
(198, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:29'),
(199, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:30'),
(200, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:32'),
(201, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:33'),
(202, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:34'),
(203, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Emergency open all - Admin override', '2025-07-04 02:37:36'),
(204, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 02:49:55'),
(205, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 02:50:06'),
(206, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 02:58:19'),
(207, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 02:58:21'),
(208, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 03:01:48'),
(209, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 03:01:51'),
(210, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 05:49:05'),
(211, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 05:53:38'),
(212, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 05:55:06'),
(213, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 05:55:24'),
(214, NULL, 1, '2384261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 05:55:43'),
(215, NULL, 1, '2384261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 05:58:23'),
(216, NULL, 1, '2384261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:06:22'),
(217, NULL, 1, '2384261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:06:38'),
(218, NULL, 1, '2384261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:06:55'),
(219, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:07:05'),
(220, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:07:10'),
(221, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:07:17'),
(222, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:07:23'),
(223, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:09:13'),
(224, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:09:23'),
(225, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:10:22'),
(226, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:10:32'),
(227, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:11:13'),
(228, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:11:36'),
(229, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:11:49'),
(230, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:12:28'),
(231, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:12:38'),
(232, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:12:46'),
(233, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:13:13'),
(234, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:13:20'),
(235, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:13:26'),
(236, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:14:00'),
(237, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:14:11'),
(238, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:14:54'),
(239, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:14:59'),
(240, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:15:48'),
(241, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:15:55'),
(242, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:16:20'),
(243, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:17:58'),
(244, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:18:20'),
(245, NULL, 1, '2384261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:18:47'),
(246, NULL, 1, '2384261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:19:22'),
(247, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:20:31'),
(248, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:22:26'),
(249, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:27:56'),
(250, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:30:45'),
(251, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:31:35'),
(252, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:31:40'),
(253, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 06:33:21'),
(254, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:33:32'),
(255, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 06:33:41'),
(256, 2, 1, '52B43003', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-04 13:47:36'),
(257, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 13:47:45'),
(258, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 13:47:57'),
(259, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 18:41:52'),
(260, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 18:41:55'),
(261, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 19:01:17'),
(262, NULL, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 19:01:19'),
(263, NULL, 7, '01CB261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:02:41'),
(264, NULL, 7, '01CB261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:05:48'),
(265, NULL, 7, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:07:40'),
(266, NULL, 7, '01CB261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:08:09'),
(267, NULL, 7, '01CB261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:12:13'),
(268, NULL, 7, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:12:39'),
(269, NULL, 7, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:12:45'),
(270, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:14:26'),
(271, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:14:32'),
(272, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:14:38'),
(273, NULL, 7, '01CB261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:15:31'),
(274, NULL, 7, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:15:52'),
(275, NULL, 7, '01CB261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:16:26'),
(276, NULL, 7, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:16:35'),
(277, NULL, 7, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:16:40'),
(278, NULL, 7, '01CB261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:16:47'),
(279, NULL, 7, '01CB261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:16:53'),
(280, NULL, 7, '01CB261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:17:08'),
(281, NULL, 7, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:17:54'),
(282, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 19:18:02'),
(283, 5, 7, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 19:18:13'),
(284, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:18:25'),
(285, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:18:34'),
(286, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:18:39'),
(287, NULL, 7, '01CB261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:19:15'),
(288, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:20:28'),
(289, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:21:32'),
(290, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:21:38'),
(291, 5, 7, '32AF2C1E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 19:22:00'),
(292, NULL, 7, '52B43003', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:22:07'),
(293, 5, 7, '2384261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 19:22:14'),
(294, 5, 7, '2384261E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 19:29:21'),
(295, NULL, 7, '01CB261E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:29:35'),
(296, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:40:09'),
(297, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:40:16'),
(298, NULL, 7, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this device/room', '2025-07-04 19:40:31'),
(299, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-04 22:54:07'),
(300, 5, 7, '32AF2C1E', 'ESP32-RFID-01', 1, 'Welcome, IbnuQS! Room B03', '2025-07-04 22:54:10'),
(301, NULL, 1, '2384261E', 'ESP32-RFID-02', 0, 'Card not authorized for this device/room', '2025-07-05 11:14:43'),
(302, 5, 6, '2384261E', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 08:12:17'),
(303, 5, 6, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 08:12:22'),
(304, 5, 6, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 08:12:47'),
(305, NULL, 6, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 08:14:32'),
(306, 5, 6, '2384261E', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 08:14:49'),
(307, 5, 6, '2384261E', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 08:15:12'),
(308, 5, 7, '2384261E', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 09:12:49'),
(309, 5, 7, '2384261E', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 09:13:04'),
(310, NULL, 7, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 09:13:40'),
(311, NULL, 7, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 09:19:01'),
(312, NULL, 7, '2384261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 09:19:10'),
(313, NULL, 7, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 09:19:27'),
(314, 2, 7, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 09:19:43'),
(315, 2, 4, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 09:20:04'),
(316, NULL, 4, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 09:23:56'),
(317, 5, 4, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 09:24:33'),
(318, 5, 4, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 09:25:23'),
(319, 5, 7, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 09:25:53'),
(320, 5, 4, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 09:26:01'),
(321, NULL, 7, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 09:29:09'),
(322, 5, 7, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 09:30:09'),
(323, NULL, 7, '2384261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 09:30:16'),
(324, NULL, 7, '52B43003', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 09:30:27'),
(325, 2, 7, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 09:30:54'),
(326, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Admin manual control', '2025-07-06 09:37:58'),
(327, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Admin manual control', '2025-07-06 09:41:16'),
(328, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Admin manual control', '2025-07-06 09:42:07'),
(329, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: jhvbhjvbhj', '2025-07-06 09:42:22'),
(330, 1, NULL, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: ijhijbnijb', '2025-07-06 09:42:31'),
(331, 1, 7, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Admin manual control', '2025-07-06 09:47:59'),
(332, 1, 7, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Admin manual control', '2025-07-06 09:59:15'),
(333, 1, 2, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Admin manual control', '2025-07-06 10:07:30'),
(334, 1, 2, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Kontrol manual admin', '2025-07-06 10:11:32'),
(335, NULL, 2, '2384261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 13:10:50'),
(336, 5, 4, 'C8D81ABB', 'ESP32-RFID-01', 0, 'Card not authorized for this room', '2025-07-06 13:18:25'),
(337, 5, 2, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 13:18:28'),
(338, NULL, 4, '2384261E', 'ESP32-RFID-01', 0, 'Card not found or inactive', '2025-07-06 13:18:33'),
(339, NULL, 2, '2384261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 13:18:35'),
(340, NULL, 4, '01CB261E', 'ESP32-RFID-01', 0, 'Card not found or inactive', '2025-07-06 13:18:39'),
(341, NULL, 2, '01CB261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 13:18:40'),
(342, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:18:45'),
(343, 4, 2, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 13:18:46'),
(344, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:18:49'),
(345, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:18:56'),
(346, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:19:02'),
(347, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:19:15'),
(348, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:19:23'),
(349, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:19:28'),
(350, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:19:37'),
(351, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:19:57'),
(352, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:20:05'),
(353, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:20:14'),
(354, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:20:22'),
(355, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:20:31'),
(356, 5, 7, 'C8D81ABB', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 13:21:15'),
(357, 5, 7, 'C8D81ABB', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 13:21:23'),
(358, 5, 7, 'C8D81ABB', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 13:21:43'),
(359, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:21:49'),
(360, NULL, 7, '2384261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 13:21:53'),
(361, 5, 7, 'C8D81ABB', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 13:22:01'),
(362, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:22:02'),
(363, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:22:10'),
(364, 5, 7, 'C8D81ABB', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 13:22:10'),
(365, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:22:19'),
(366, 5, 7, 'C8D81ABB', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 13:22:19'),
(367, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:22:27'),
(368, 5, 7, 'C8D81ABB', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 13:22:27'),
(369, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:22:35'),
(370, 5, 7, 'C8D81ABB', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 13:22:35'),
(371, 5, 7, 'C8D81ABB', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 13:22:43'),
(372, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:22:43'),
(373, 5, 7, 'C8D81ABB', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-06 13:22:52'),
(374, 1, 4, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Kontrol manual admin', '2025-07-06 13:23:06'),
(375, 1, 7, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Kontrol manual admin', '2025-07-06 13:23:07'),
(376, 1, 4, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual close_door: Kontrol manual admin', '2025-07-06 13:23:47'),
(377, 1, 4, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Kontrol manual admin', '2025-07-06 13:23:53'),
(378, 1, 4, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual close_door: Kontrol manual admin', '2025-07-06 13:23:56'),
(379, 4, 7, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 13:24:27'),
(380, 4, 4, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:24:29'),
(381, NULL, 4, 'C8D81ABB', 'ESP32-RFID-01', 0, 'Card not found or inactive', '2025-07-06 13:24:37'),
(382, 4, 4, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 13:24:45'),
(383, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 14:29:24'),
(384, 4, 7, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 14:29:26'),
(385, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 19:55:22'),
(386, NULL, 4, '01CB261E', 'ESP32-RFID-01', 0, 'Card not found or inactive', '2025-07-06 19:56:52'),
(387, NULL, 4, '01CB261E', 'ESP32-RFID-01', 0, 'Card not found or inactive', '2025-07-06 19:56:57'),
(388, NULL, 4, '01CB261E', 'ESP32-RFID-01', 0, 'Card not found or inactive', '2025-07-06 19:57:04'),
(389, NULL, 4, '01CB261E', 'ESP32-RFID-01', 0, 'Card not found or inactive', '2025-07-06 19:57:10'),
(390, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 22:03:38'),
(391, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-06 22:17:33'),
(392, NULL, 7, '01CB261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 22:17:48'),
(393, 4, 7, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-06 22:17:55'),
(394, NULL, 7, '2384261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-06 22:18:00'),
(395, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-07 09:26:22'),
(396, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-07 09:26:35'),
(397, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-07 09:30:56'),
(398, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-07 09:32:50'),
(399, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-07 09:38:26'),
(400, NULL, 4, '2384261E', 'ESP32-RFID-01', 0, 'Card not found or inactive', '2025-07-07 09:39:58'),
(401, 5, 7, '2384261E', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-07 09:40:46'),
(402, 5, 4, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this room', '2025-07-07 09:40:53'),
(403, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-07 14:31:17'),
(404, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-07 14:31:25'),
(405, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-07 14:32:04'),
(406, 4, 7, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-07 14:33:19'),
(407, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-07 14:33:23'),
(408, 4, 7, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-07 14:33:28'),
(409, 5, 7, '2384261E', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-07 14:33:36'),
(410, 4, 4, '52B43003', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-07 14:38:45'),
(411, 4, 7, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-07 14:38:46'),
(412, 5, 7, '2384261E', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-08 18:28:07'),
(413, 5, 4, '2384261E', 'ESP32-RFID-01', 0, 'Card not authorized for this room', '2025-07-08 18:28:46'),
(414, 5, 7, '2384261E', 'ESP32-RFID-02', 1, 'Welcome, IbnuQS! Room B03', '2025-07-08 18:35:20'),
(415, NULL, 4, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not found or inactive', '2025-07-10 11:37:45'),
(416, 2, 4, '32AF2C1E', 'ESP32-RFID-01', 0, 'Card not authorized for this room', '2025-07-10 11:37:55'),
(417, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 11:38:44'),
(418, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 11:39:36'),
(419, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 11:59:03'),
(420, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 12:05:28'),
(421, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 12:13:39'),
(422, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-10 12:13:51'),
(423, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-10 12:43:17'),
(424, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-10 12:43:27'),
(425, 4, 4, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-10 12:43:44'),
(426, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-10 12:43:45'),
(427, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-10 12:43:55'),
(428, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 12:44:07'),
(429, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 12:50:59'),
(430, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 12:51:12'),
(431, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 12:51:26'),
(432, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 12:51:34'),
(433, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 12:51:42'),
(434, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 12:51:50'),
(435, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 12:52:00'),
(436, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-10 13:37:36'),
(437, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 13:37:46'),
(438, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santoso! Room A01', '2025-07-10 13:37:58'),
(439, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-10 18:00:58'),
(440, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-10 18:01:04'),
(441, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-10 18:11:29'),
(442, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-10 18:11:39'),
(443, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-10 18:11:58'),
(444, 1, 1, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Kontrol manual admin', '2025-07-10 18:12:54'),
(445, 1, 1, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Kontrol manual admin', '2025-07-10 18:14:00'),
(446, 1, 1, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Kontrol manual admin', '2025-07-10 18:14:16'),
(447, 1, 1, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Kontrol manual admin', '2025-07-11 07:10:11'),
(448, 4, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-11 10:02:09'),
(449, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-12 10:15:26'),
(450, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-12 16:53:29'),
(451, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-13 16:41:31'),
(452, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-13 16:41:40'),
(453, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-13 21:15:35'),
(454, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-13 21:15:55'),
(455, 1, 1, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Kontrol manual admin', '2025-07-13 22:49:45'),
(456, 1, 4, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Kontrol manual admin', '2025-07-13 22:49:51'),
(457, 1, 4, 'MANUAL_COMMAND', 'ESP32-RFID-01', 1, 'Manual open_door: Kontrol manual admin', '2025-07-13 22:50:00'),
(458, 1, 1, 'MANUAL_COMMAND', 'ESP32-RFID-02', 1, 'Manual open_door: Kontrol manual admin', '2025-07-13 22:50:10'),
(459, 4, 4, 'C8D81ABB', 'ESP32-RFID-01', 1, 'Welcome, Ahmad Rizki! Room A04', '2025-07-19 03:07:32'),
(460, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-19 03:07:35'),
(461, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-29 21:06:18'),
(462, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-29 21:06:41'),
(463, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-29 21:06:46'),
(464, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-29 21:18:49'),
(465, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-29 21:34:04'),
(466, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-29 22:37:41'),
(467, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-29 22:37:47'),
(468, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-29 22:43:03'),
(469, 4, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-30 14:51:48'),
(470, 4, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-30 15:15:01'),
(471, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-30 15:15:11'),
(472, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-30 15:17:31');
INSERT INTO `access_logs` (`id`, `user_id`, `room_id`, `rfid_uid`, `device_id`, `access_granted`, `reason`, `accessed_at`) VALUES
(473, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-30 15:34:07'),
(474, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-30 15:34:12'),
(475, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-30 16:03:14'),
(476, NULL, 1, 'MANUAL_ADMIN', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-30 20:16:04'),
(477, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-31 00:23:05'),
(478, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-31 00:23:36'),
(479, NULL, 1, 'MANUAL_ADMIN', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-31 00:35:33'),
(480, NULL, 1, 'MANUAL_ADMIN', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-31 00:35:44'),
(481, NULL, 1, 'MANUAL_ADMIN', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-31 00:35:47'),
(482, NULL, 1, 'MANUAL_ADMIN', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-31 00:35:48'),
(483, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-31 00:54:48'),
(484, 4, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-31 00:55:06'),
(485, NULL, 1, 'MANUAL_ADMIN', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-31 00:57:27'),
(486, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-31 01:07:53'),
(487, NULL, 1, 'MANUAL_ADMIN', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-31 01:09:32'),
(488, NULL, 1, 'MANUAL_ADMIN', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-31 01:25:12'),
(489, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-07-31 05:05:07'),
(490, 4, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-07-31 05:05:14'),
(491, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-07-31 05:05:21'),
(492, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-01 19:08:52'),
(493, NULL, 1, '2384261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-08-01 19:09:00'),
(494, 4, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-08-01 19:09:05'),
(495, NULL, 1, 'MANUAL_ADMIN', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-08-01 19:28:12'),
(496, NULL, 1, 'MANUAL_ADMIN', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-08-01 19:52:58'),
(497, NULL, 1, 'MANUAL_ADMIN', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-08-01 19:59:23'),
(498, 4, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-08-01 20:30:32'),
(499, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-08-01 20:30:37'),
(500, NULL, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-08-01 20:30:51'),
(501, 2, 1, '01CB261E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-01 20:34:25'),
(502, 2, 1, '01CB261E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-03 00:00:56'),
(503, 2, 1, '01CB261E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-03 00:03:33'),
(504, 2, 1, '01CB261E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-03 00:40:45'),
(505, 2, 1, '01CB261E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-03 00:56:05'),
(506, 2, 1, '01CB261E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-03 00:59:08'),
(507, 2, 1, '01CB261E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-03 01:05:13'),
(508, 2, 1, '01CB261E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-03 01:08:59'),
(509, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-03 01:10:00'),
(510, 2, 1, '01CB261E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-03 01:10:38'),
(511, 2, 1, '01CB261E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-03 01:13:30'),
(512, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Access denied: unpaid payments', '2025-08-03 01:14:38'),
(513, 2, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Access denied: unpaid payments', '2025-08-03 01:15:10'),
(514, 2, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Access denied: unpaid payments', '2025-08-03 01:15:18'),
(515, 2, 1, '01CB261E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-03 01:15:54'),
(516, 2, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Access denied: unpaid payments', '2025-08-03 01:16:23'),
(517, 2, 1, '01CB261E', 'ESP32-RFID-02', 1, 'Welcome, Budi Santosos! Room A01', '2025-08-03 01:28:22'),
(518, 2, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Access denied: overdue payments', '2025-08-03 01:29:18'),
(519, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Access denied: overdue payments', '2025-08-05 01:29:13'),
(520, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Access denied: overdue payments', '2025-08-05 01:29:43'),
(521, 4, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-08-05 01:29:49'),
(522, 4, 1, '52B43003', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-08-05 01:31:00'),
(523, 2, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Access denied: overdue payments', '2025-08-05 01:31:06'),
(524, 4, 1, 'C8D81ABB', 'ESP32-RFID-02', 0, 'Card not authorized for this room', '2025-08-05 01:31:53'),
(525, 2, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Access denied: overdue payments', '2025-08-05 01:32:10'),
(526, 2, 1, '01CB261E', 'ESP32-RFID-02', 0, 'Access denied: overdue payments', '2025-08-05 02:09:34'),
(527, NULL, 1, '32AF2C1E', 'ESP32-RFID-02', 0, 'Card not found or inactive', '2025-08-05 10:35:11');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('kost_management_system_cache_access_history_daily_7', 'a:7:{i:0;a:6:{s:6:\"period\";s:6:\"Jul 30\";s:12:\"total_access\";i:15;s:17:\"successful_access\";i:6;s:13:\"failed_access\";i:9;s:12:\"unique_users\";i:2;s:12:\"success_rate\";d:40;}i:1;a:6:{s:6:\"period\";s:6:\"Jul 31\";s:12:\"total_access\";i:16;s:17:\"successful_access\";i:1;s:13:\"failed_access\";i:15;s:12:\"unique_users\";i:2;s:12:\"success_rate\";d:6.25;}i:2;a:6:{s:6:\"period\";s:6:\"Aug 01\";s:12:\"total_access\";i:0;s:17:\"successful_access\";i:0;s:13:\"failed_access\";i:0;s:12:\"unique_users\";i:0;s:12:\"success_rate\";d:0;}i:3;a:6:{s:6:\"period\";s:6:\"Aug 02\";s:12:\"total_access\";i:10;s:17:\"successful_access\";i:2;s:13:\"failed_access\";i:8;s:12:\"unique_users\";i:2;s:12:\"success_rate\";d:20;}i:4;a:6:{s:6:\"period\";s:6:\"Aug 03\";s:12:\"total_access\";i:17;s:17:\"successful_access\";i:12;s:13:\"failed_access\";i:5;s:12:\"unique_users\";i:1;s:12:\"success_rate\";d:70.59;}i:5;a:6:{s:6:\"period\";s:6:\"Aug 04\";s:12:\"total_access\";i:0;s:17:\"successful_access\";i:0;s:13:\"failed_access\";i:0;s:12:\"unique_users\";i:0;s:12:\"success_rate\";d:0;}i:6;a:6:{s:6:\"period\";s:6:\"Aug 05\";s:12:\"total_access\";i:9;s:17:\"successful_access\";i:0;s:13:\"failed_access\";i:9;s:12:\"unique_users\";i:2;s:12:\"success_rate\";d:0;}}', 1754390660),
('kost_management_system_cache_admin_dashboard_stats', 'a:6:{s:5:\"rooms\";a:5:{s:5:\"total\";i:12;s:8:\"occupied\";i:7;s:9:\"available\";i:1;s:11:\"maintenance\";i:4;s:14:\"occupancy_rate\";d:58.33;}s:7:\"tenants\";a:4:{s:12:\"total_active\";i:7;s:11:\"total_users\";i:9;s:14:\"new_this_month\";i:1;s:20:\"moved_out_this_month\";i:1;}s:4:\"rfid\";a:4:{s:11:\"total_cards\";i:3;s:12:\"active_cards\";i:1;s:14:\"assigned_cards\";i:3;s:16:\"unassigned_cards\";i:0;}s:7:\"finance\";a:5:{s:15:\"monthly_revenue\";d:1000000;s:14:\"yearly_revenue\";d:4000000;s:14:\"pending_amount\";d:7500000;s:14:\"overdue_amount\";d:0;s:15:\"collection_rate\";d:16.67;}s:8:\"payments\";a:5:{s:13:\"pending_count\";i:5;s:13:\"overdue_count\";i:0;s:15:\"paid_this_month\";i:1;s:16:\"total_this_month\";i:6;s:15:\"collection_rate\";d:16.67;}s:6:\"access\";a:5:{s:11:\"total_today\";i:9;s:15:\"total_this_week\";i:9;s:14:\"total_all_time\";i:527;s:18:\"unique_users_today\";i:2;s:9:\"peak_hour\";s:5:\"08:00\";}}', 1754390660),
('kost_management_system_cache_app_start_time', 'O:25:\"Illuminate\\Support\\Carbon\":3:{s:4:\"date\";s:26:\"2025-07-01 12:32:32.797432\";s:13:\"timezone_type\";i:3;s:8:\"timezone\";s:3:\"UTC\";}', 1782909152),
('kost_management_system_cache_payment_trends_monthly_2025', 'a:12:{i:0;a:4:{s:6:\"period\";s:3:\"Jan\";s:14:\"total_payments\";i:0;s:12:\"total_amount\";d:0;s:10:\"avg_amount\";d:0;}i:1;a:4:{s:6:\"period\";s:3:\"Feb\";s:14:\"total_payments\";i:0;s:12:\"total_amount\";d:0;s:10:\"avg_amount\";d:0;}i:2;a:4:{s:6:\"period\";s:3:\"Mar\";s:14:\"total_payments\";i:0;s:12:\"total_amount\";d:0;s:10:\"avg_amount\";d:0;}i:3;a:4:{s:6:\"period\";s:3:\"Apr\";s:14:\"total_payments\";i:0;s:12:\"total_amount\";d:0;s:10:\"avg_amount\";d:0;}i:4;a:4:{s:6:\"period\";s:3:\"May\";s:14:\"total_payments\";i:0;s:12:\"total_amount\";d:0;s:10:\"avg_amount\";d:0;}i:5;a:4:{s:6:\"period\";s:3:\"Jun\";s:14:\"total_payments\";i:0;s:12:\"total_amount\";d:0;s:10:\"avg_amount\";d:0;}i:6;a:4:{s:6:\"period\";s:3:\"Jul\";s:14:\"total_payments\";i:0;s:12:\"total_amount\";d:0;s:10:\"avg_amount\";d:0;}i:7;a:4:{s:6:\"period\";s:3:\"Aug\";s:14:\"total_payments\";i:3;s:12:\"total_amount\";d:4000000;s:10:\"avg_amount\";d:1333333.33;}i:8;a:4:{s:6:\"period\";s:3:\"Sep\";s:14:\"total_payments\";i:0;s:12:\"total_amount\";d:0;s:10:\"avg_amount\";d:0;}i:9;a:4:{s:6:\"period\";s:3:\"Oct\";s:14:\"total_payments\";i:0;s:12:\"total_amount\";d:0;s:10:\"avg_amount\";d:0;}i:10;a:4:{s:6:\"period\";s:3:\"Nov\";s:14:\"total_payments\";i:0;s:12:\"total_amount\";d:0;s:10:\"avg_amount\";d:0;}i:11;a:4:{s:6:\"period\";s:3:\"Dec\";s:14:\"total_payments\";i:0;s:12:\"total_amount\";d:0;s:10:\"avg_amount\";d:0;}}', 1754390901),
('kost_management_system_cache_recent_activities_15', 'a:11:{i:0;a:9:{s:2:\"id\";s:37:\"access_access_527_0_1754390600.4487_0\";s:4:\"type\";s:6:\"access\";s:5:\"title\";s:11:\"Room Access\";s:11:\"description\";s:30:\"Unknown User accessed Room A01\";s:4:\"user\";N;s:9:\"timestamp\";s:25:\"2025-08-05T17:35:11+07:00\";s:4:\"icon\";s:9:\"door-open\";s:5:\"color\";s:3:\"red\";s:8:\"priority\";s:6:\"medium\";}i:1;a:9:{s:2:\"id\";s:38:\"payment_payment_46_0_1754390600.4487_1\";s:4:\"type\";s:7:\"payment\";s:5:\"title\";s:16:\"Payment Received\";s:11:\"description\";s:42:\"Payment of Rp 1.000.000 from Budi Santosos\";s:4:\"user\";a:3:{s:2:\"id\";i:2;s:4:\"name\";s:13:\"Budi Santosos\";s:5:\"email\";s:14:\"budi@gmail.com\";}s:9:\"timestamp\";s:25:\"2025-08-05T10:32:09+07:00\";s:4:\"icon\";s:11:\"credit-card\";s:5:\"color\";s:5:\"green\";s:8:\"priority\";s:6:\"medium\";}i:2;a:9:{s:2:\"id\";s:38:\"payment_payment_43_1_1754390600.4487_2\";s:4:\"type\";s:7:\"payment\";s:5:\"title\";s:16:\"Payment Received\";s:11:\"description\";s:42:\"Payment of Rp 1.000.000 from Budi Santosos\";s:4:\"user\";a:3:{s:2:\"id\";i:2;s:4:\"name\";s:13:\"Budi Santosos\";s:5:\"email\";s:14:\"budi@gmail.com\";}s:9:\"timestamp\";s:25:\"2025-08-05T09:56:00+07:00\";s:4:\"icon\";s:11:\"credit-card\";s:5:\"color\";s:5:\"green\";s:8:\"priority\";s:6:\"medium\";}i:3;a:9:{s:2:\"id\";s:37:\"access_access_526_1_1754390600.4487_3\";s:4:\"type\";s:6:\"access\";s:5:\"title\";s:11:\"Room Access\";s:11:\"description\";s:31:\"Budi Santosos accessed Room A01\";s:4:\"user\";a:3:{s:2:\"id\";i:2;s:4:\"name\";s:13:\"Budi Santosos\";s:5:\"email\";s:14:\"budi@gmail.com\";}s:9:\"timestamp\";s:25:\"2025-08-05T09:09:34+07:00\";s:4:\"icon\";s:9:\"door-open\";s:5:\"color\";s:3:\"red\";s:8:\"priority\";s:6:\"medium\";}i:4;a:9:{s:2:\"id\";s:37:\"access_access_525_2_1754390600.4487_4\";s:4:\"type\";s:6:\"access\";s:5:\"title\";s:11:\"Room Access\";s:11:\"description\";s:31:\"Budi Santosos accessed Room A01\";s:4:\"user\";a:3:{s:2:\"id\";i:2;s:4:\"name\";s:13:\"Budi Santosos\";s:5:\"email\";s:14:\"budi@gmail.com\";}s:9:\"timestamp\";s:25:\"2025-08-05T08:32:10+07:00\";s:4:\"icon\";s:9:\"door-open\";s:5:\"color\";s:3:\"red\";s:8:\"priority\";s:6:\"medium\";}i:5;a:9:{s:2:\"id\";s:37:\"access_access_524_3_1754390600.4488_5\";s:4:\"type\";s:6:\"access\";s:5:\"title\";s:11:\"Room Access\";s:11:\"description\";s:29:\"Ahmad Rizki accessed Room A01\";s:4:\"user\";a:3:{s:2:\"id\";i:4;s:4:\"name\";s:11:\"Ahmad Rizki\";s:5:\"email\";s:15:\"ahmad@gmail.com\";}s:9:\"timestamp\";s:25:\"2025-08-05T08:31:53+07:00\";s:4:\"icon\";s:9:\"door-open\";s:5:\"color\";s:3:\"red\";s:8:\"priority\";s:6:\"medium\";}i:6;a:9:{s:2:\"id\";s:37:\"access_access_523_4_1754390600.4488_6\";s:4:\"type\";s:6:\"access\";s:5:\"title\";s:11:\"Room Access\";s:11:\"description\";s:31:\"Budi Santosos accessed Room A01\";s:4:\"user\";a:3:{s:2:\"id\";i:2;s:4:\"name\";s:13:\"Budi Santosos\";s:5:\"email\";s:14:\"budi@gmail.com\";}s:9:\"timestamp\";s:25:\"2025-08-05T08:31:06+07:00\";s:4:\"icon\";s:9:\"door-open\";s:5:\"color\";s:3:\"red\";s:8:\"priority\";s:6:\"medium\";}i:7;a:9:{s:2:\"id\";s:38:\"payment_payment_42_2_1754390600.4488_7\";s:4:\"type\";s:7:\"payment\";s:5:\"title\";s:16:\"Payment Received\";s:11:\"description\";s:35:\"Payment of Rp 2.000.000 from IbnuQS\";s:4:\"user\";a:3:{s:2:\"id\";i:5;s:4:\"name\";s:6:\"IbnuQS\";s:5:\"email\";s:22:\"ibnuqolbys17@gmail.com\";}s:9:\"timestamp\";s:25:\"2025-08-03T07:35:16+07:00\";s:4:\"icon\";s:11:\"credit-card\";s:5:\"color\";s:5:\"green\";s:8:\"priority\";s:6:\"medium\";}i:8;a:9:{s:2:\"id\";s:31:\"rfid_rfid_5_0_1754390600.4488_8\";s:4:\"type\";s:4:\"rfid\";s:5:\"title\";s:18:\"RFID Card Assigned\";s:11:\"description\";s:39:\"Card 32AF2C1E assigned to Budi Santosos\";s:4:\"user\";a:3:{s:2:\"id\";i:2;s:4:\"name\";s:13:\"Budi Santosos\";s:5:\"email\";s:14:\"budi@gmail.com\";}s:9:\"timestamp\";s:25:\"2025-07-10T18:37:52+07:00\";s:4:\"icon\";s:11:\"credit-card\";s:5:\"color\";s:6:\"purple\";s:8:\"priority\";s:3:\"low\";}i:9;a:9:{s:2:\"id\";s:31:\"rfid_rfid_4_1_1754390600.4488_9\";s:4:\"type\";s:4:\"rfid\";s:5:\"title\";s:18:\"RFID Card Assigned\";s:11:\"description\";s:32:\"Card 2384261E assigned to IbnuQS\";s:4:\"user\";a:3:{s:2:\"id\";i:5;s:4:\"name\";s:6:\"IbnuQS\";s:5:\"email\";s:22:\"ibnuqolbys17@gmail.com\";}s:9:\"timestamp\";s:25:\"2025-07-07T16:40:04+07:00\";s:4:\"icon\";s:11:\"credit-card\";s:5:\"color\";s:6:\"purple\";s:8:\"priority\";s:3:\"low\";}i:10;a:9:{s:2:\"id\";s:32:\"rfid_rfid_2_2_1754390600.4488_10\";s:4:\"type\";s:4:\"rfid\";s:5:\"title\";s:18:\"RFID Card Assigned\";s:11:\"description\";s:37:\"Card C8D81ABB assigned to Ahmad Rizki\";s:4:\"user\";a:3:{s:2:\"id\";i:4;s:4:\"name\";s:11:\"Ahmad Rizki\";s:5:\"email\";s:15:\"ahmad@gmail.com\";}s:9:\"timestamp\";s:25:\"2025-07-06T16:29:12+07:00\";s:4:\"icon\";s:11:\"credit-card\";s:5:\"color\";s:6:\"purple\";s:8:\"priority\";s:3:\"low\";}}', 1754390720),
('kost_management_system_cache_revenue_analytics_monthly_2025', 'a:12:{i:0;a:4:{s:5:\"month\";s:3:\"Jan\";s:7:\"revenue\";d:0;s:8:\"payments\";i:0;s:11:\"avg_payment\";d:0;}i:1;a:4:{s:5:\"month\";s:3:\"Feb\";s:7:\"revenue\";d:0;s:8:\"payments\";i:0;s:11:\"avg_payment\";d:0;}i:2;a:4:{s:5:\"month\";s:3:\"Mar\";s:7:\"revenue\";d:0;s:8:\"payments\";i:0;s:11:\"avg_payment\";d:0;}i:3;a:4:{s:5:\"month\";s:3:\"Apr\";s:7:\"revenue\";d:0;s:8:\"payments\";i:0;s:11:\"avg_payment\";d:0;}i:4;a:4:{s:5:\"month\";s:3:\"May\";s:7:\"revenue\";d:0;s:8:\"payments\";i:0;s:11:\"avg_payment\";d:0;}i:5;a:4:{s:5:\"month\";s:3:\"Jun\";s:7:\"revenue\";d:0;s:8:\"payments\";i:0;s:11:\"avg_payment\";d:0;}i:6;a:4:{s:5:\"month\";s:3:\"Jul\";s:7:\"revenue\";d:0;s:8:\"payments\";i:0;s:11:\"avg_payment\";d:0;}i:7;a:4:{s:5:\"month\";s:3:\"Aug\";s:7:\"revenue\";d:4000000;s:8:\"payments\";i:3;s:11:\"avg_payment\";d:1333333.33;}i:8;a:4:{s:5:\"month\";s:3:\"Sep\";s:7:\"revenue\";d:0;s:8:\"payments\";i:0;s:11:\"avg_payment\";d:0;}i:9;a:4:{s:5:\"month\";s:3:\"Oct\";s:7:\"revenue\";d:0;s:8:\"payments\";i:0;s:11:\"avg_payment\";d:0;}i:10;a:4:{s:5:\"month\";s:3:\"Nov\";s:7:\"revenue\";d:0;s:8:\"payments\";i:0;s:11:\"avg_payment\";d:0;}i:11;a:4:{s:5:\"month\";s:3:\"Dec\";s:7:\"revenue\";d:0;s:8:\"payments\";i:0;s:11:\"avg_payment\";d:0;}}', 1754391200);

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `iot_devices`
--

CREATE TABLE `iot_devices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `device_id` varchar(100) NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `device_type` enum('door_lock','card_scanner','rfid_reader') DEFAULT NULL,
  `room_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('online','offline') NOT NULL DEFAULT 'offline',
  `device_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`device_info`)),
  `last_seen` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `iot_devices`
--

INSERT INTO `iot_devices` (`id`, `device_id`, `device_name`, `device_type`, `room_id`, `status`, `device_info`, `last_seen`, `created_at`, `updated_at`) VALUES
(7, 'ESP32-RFID-01', 'Perangkat #01', 'rfid_reader', 4, 'online', '\"{\\\"wifi_connected\\\":true,\\\"mqtt_connected\\\":true,\\\"rfid_ready\\\":true,\\\"device_ip\\\":\\\"192.168.110.135\\\",\\\"uptime\\\":\\\"0h 54m\\\",\\\"firmware_version\\\":\\\"v2.1.0\\\",\\\"door_status\\\":\\\"unknown\\\",\\\"wifi_ssid\\\":null,\\\"rssi\\\":null,\\\"free_heap\\\":null,\\\"last_updated\\\":\\\"2025-07-19T03:59:38.034873Z\\\"}\"', '2025-07-19 03:59:38', '2025-07-02 07:45:19', '2025-07-19 03:59:38'),
(8, 'ESP32-RFID-02', 'Perangkat #02', 'rfid_reader', 1, 'online', '\"{\\\"wifi_connected\\\":true,\\\"mqtt_connected\\\":true,\\\"rfid_ready\\\":true,\\\"device_ip\\\":\\\"192.168.110.2\\\",\\\"uptime\\\":\\\"9h 6m\\\",\\\"firmware_version\\\":\\\"v2.1.0\\\",\\\"door_status\\\":\\\"unknown\\\",\\\"wifi_ssid\\\":null,\\\"rssi\\\":null,\\\"free_heap\\\":null,\\\"last_updated\\\":\\\"2025-08-05T11:14:13.080184Z\\\"}\"', '2025-08-05 11:14:13', '2025-07-02 08:04:21', '2025-08-05 11:14:13'),
(9, 'ESP32-RFID-03', 'Perangkat #03', 'card_scanner', NULL, 'online', NULL, '2025-07-03 14:37:55', '2025-07-03 14:37:55', '2025-07-03 14:37:55'),
(10, 'ESP32-RFID-04', 'Perangkat #04', 'door_lock', NULL, 'online', NULL, '2025-07-03 14:50:41', '2025-07-03 14:50:41', '2025-07-03 14:50:41');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_06_05_185654_create_personal_access_tokens_table', 1),
(5, '2025_06_05_185728_create_rooms_table', 1),
(6, '2025_06_05_185730_create_tenants_table', 1),
(7, '2025_06_05_185731_create_payments_table', 1),
(8, '2025_06_05_185732_create_rfid_cards_table', 1),
(9, '2025_06_05_185733_create_access_logs_table', 1),
(10, '2025_06_05_185734_create_iot_devices_table', 1),
(11, '2025_06_05_185735_create_notifications_table', 1),
(12, '2025_07_01_000001_add_archived_status_to_rooms', 1),
(13, '2025_07_01_000002_add_archived_at_to_rooms', 1),
(14, '2025_07_02_000001_update_iot_devices_schema', 2),
(15, '2025_07_03_000001_add_device_id_to_rfid_cards', 3);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('payment','access','system') NOT NULL,
  `status` enum('unread','read') NOT NULL DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `status`, `created_at`) VALUES
(1, 2, 'Pengingat Pembayaran', 'Pembayaran bulan ini sudah lunas. Terima kasih!', 'payment', 'read', '2025-07-01 12:32:34'),
(2, 2, 'Pembayaran Menunggu', 'Pembayaran untuk bulan 2025-07 masih pending. Silakan selesaikan pembayaran.', 'payment', 'read', '2025-07-01 12:32:34'),
(3, 1, 'Device Offline', 'Smart lock kamar B02 sudah offline selama 2 jam.', 'system', 'unread', '2025-07-01 12:32:34'),
(4, 2, 'Tagihan Bulanan Dibuat', 'Tagihan sewa untuk bulan August 2025 telah dibuat sebesar Rp 1.000.000', 'payment', 'read', '2025-08-02 22:55:58'),
(5, 3, 'Tagihan Bulanan Dibuat', 'Tagihan sewa untuk bulan August 2025 telah dibuat sebesar Rp 1.500.000', 'payment', 'read', '2025-08-02 22:55:58'),
(6, 4, 'Tagihan Bulanan Dibuat', 'Tagihan sewa untuk bulan August 2025 telah dibuat sebesar Rp 1.000.000', 'payment', 'read', '2025-08-02 22:55:58'),
(7, 5, 'Tagihan Bulanan Dibuat', 'Tagihan sewa untuk bulan August 2025 telah dibuat sebesar Rp 2.000.000', 'payment', 'unread', '2025-08-02 22:55:58'),
(8, 20, 'Tagihan Bulanan Dibuat', 'Tagihan sewa untuk bulan August 2025 telah dibuat sebesar Rp 1.000.000', 'payment', 'unread', '2025-08-02 22:55:58'),
(9, 22, 'Tagihan Bulanan Dibuat', 'Tagihan sewa untuk bulan August 2025 telah dibuat sebesar Rp 2.000.000', 'payment', 'unread', '2025-08-02 22:55:58'),
(10, 2, 'Tagihan Bulanan Dibuat', 'Tagihan sewa untuk bulan August 2025 telah dibuat sebesar Rp 1.000.000', 'payment', 'read', '2025-08-02 23:31:28'),
(11, 3, 'Tagihan Bulanan Dibuat', 'Tagihan sewa untuk bulan August 2025 telah dibuat sebesar Rp 1.500.000', 'payment', 'unread', '2025-08-02 23:31:28'),
(12, 4, 'Tagihan Bulanan Dibuat', 'Tagihan sewa untuk bulan August 2025 telah dibuat sebesar Rp 1.000.000', 'payment', 'read', '2025-08-02 23:31:28'),
(13, 5, 'Tagihan Bulanan Dibuat', 'Tagihan sewa untuk bulan August 2025 telah dibuat sebesar Rp 2.000.000', 'payment', 'unread', '2025-08-02 23:31:28'),
(14, 20, 'Tagihan Bulanan Dibuat', 'Tagihan sewa untuk bulan August 2025 telah dibuat sebesar Rp 1.000.000', 'payment', 'unread', '2025-08-02 23:31:28'),
(15, 22, 'Tagihan Bulanan Dibuat', 'Tagihan sewa untuk bulan August 2025 telah dibuat sebesar Rp 2.000.000', 'payment', 'unread', '2025-08-02 23:31:28'),
(16, 5, 'Tagihan Baru', 'Tagihan pembayaran sewa untuk bulan July 2025 telah dibuat. Jumlah: Rp 2.000.000', 'payment', 'unread', '2025-08-03 00:24:33'),
(17, 2, 'Tagihan Baru', 'Tagihan pembayaran sewa untuk bulan July 2025 telah dibuat. Jumlah: Rp 1.000.000', 'payment', 'read', '2025-08-03 00:39:39'),
(18, 2, 'Tagihan Baru', 'Tagihan pembayaran sewa untuk bulan August 2025 telah dibuat. Jumlah: Rp 1.000.000', 'payment', 'read', '2025-08-05 03:09:34'),
(19, 2, 'Tagihan Baru', 'Tagihan pembayaran sewa untuk bulan August 2025 telah dibuat. Jumlah: Rp 1.000.000', 'payment', 'read', '2025-08-05 03:22:23'),
(20, 2, 'Tagihan Baru', 'Tagihan pembayaran sewa untuk bulan August 2025 telah dibuat. Jumlah: Rp 1.000.000', 'payment', 'read', '2025-08-05 03:31:25');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `payment_month` varchar(10) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `status` enum('pending','paid','overdue','expired','cancelled') NOT NULL DEFAULT 'pending',
  `generation_type` enum('auto','manual') NOT NULL DEFAULT 'auto',
  `generated_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `regenerated_from` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `snap_token` varchar(255) DEFAULT NULL,
  `snap_token_created_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `expired_at` timestamp NULL DEFAULT NULL,
  `failure_reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `order_id`, `tenant_id`, `payment_month`, `amount`, `status`, `generation_type`, `generated_by_user_id`, `description`, `regenerated_from`, `payment_method`, `snap_token`, `snap_token_created_at`, `paid_at`, `expired_at`, `failure_reason`, `notes`, `created_at`, `updated_at`) VALUES
(35, 'PAY250803A92AYO', 2, '2025-08', 1500000.00, 'pending', 'auto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-02 23:31:28', '2025-08-02 23:31:28'),
(36, 'PAY250803X3XVLV', 3, '2025-08', 1000000.00, 'pending', 'auto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-02 23:31:28', '2025-08-02 23:31:28'),
(37, 'PAY250803PK5I8B', 4, '2025-08', 2000000.00, 'pending', 'auto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-02 23:31:28', '2025-08-02 23:31:28'),
(38, 'PAY250803K2XH3Z', 5, '2025-08', 1000000.00, 'pending', 'auto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-02 23:31:28', '2025-08-02 23:31:28'),
(39, 'PAY250803CQLOV7', 7, '2025-08', 2000000.00, 'pending', 'auto', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-02 23:31:28', '2025-08-02 23:31:28'),
(42, 'PAY250803FTHD8N', 4, '2025-07', 2000000.00, 'paid', 'auto', NULL, NULL, NULL, 'manual_override', NULL, NULL, '2025-08-03 00:35:16', NULL, NULL, NULL, '2025-08-03 00:24:33', '2025-08-03 00:35:16'),
(43, 'PAY250803OTFCC3', 1, '2025-07', 1000000.00, 'paid', 'auto', NULL, NULL, NULL, 'qris', 'b146a2f0-4314-4caa-ad12-7f7cf51997f8', '2025-08-05 02:55:13', '2025-08-05 02:56:00', NULL, NULL, NULL, '2025-08-03 00:39:39', '2025-08-05 02:56:00'),
(46, 'PAY2508058MZBEV', 1, '2025-08', 1000000.00, 'paid', 'auto', NULL, NULL, NULL, 'qris', '9b7697db-35d3-41cd-be00-67c14abcea0b', '2025-08-05 03:31:53', '2025-08-05 03:32:09', NULL, NULL, NULL, '2025-08-05 03:31:25', '2025-08-05 03:32:09');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(3, 'App\\Models\\User', 1, 'auth_token', '7bbbe63e7ad4a12e32ba60fcf80a430a1bdb93d9ce88568fbc78b3f888200a71', '[\"admin\"]', '2025-07-02 05:44:12', '2025-07-02 06:41:30', '2025-07-01 06:41:30', '2025-07-02 05:44:12'),
(4, 'App\\Models\\User', 1, 'auth_token', '06cd1cfab7133da86f9fa364d09c3ba16fe068bbff41aa28da19a22aa274afd6', '[\"admin\"]', '2025-07-02 06:16:58', '2025-07-03 05:46:42', '2025-07-02 05:46:42', '2025-07-02 06:16:58'),
(5, 'App\\Models\\User', 1, 'auth_token', 'a2e9c4c1e2a3797928ebe2694ad40d0c6ead0ad6b076de50f8daa27b7996e2dc', '[\"admin\"]', '2025-07-02 06:59:36', '2025-07-03 06:31:48', '2025-07-02 06:31:48', '2025-07-02 06:59:36'),
(6, 'App\\Models\\User', 1, 'auth_token', '64263c0a922d9e9e4b4d760ea2dd9d747205d1c8f673cb4629c67358123fd55b', '[\"admin\"]', '2025-07-02 08:28:51', '2025-07-03 07:00:27', '2025-07-02 07:00:27', '2025-07-02 08:28:51'),
(7, 'App\\Models\\User', 1, 'auth_token', 'a963f6d270d7be96f40422c8a32b524b5a8b1373bd4763a198d47ea4c9dc7e84', '[\"admin\"]', '2025-07-02 08:44:40', '2025-07-03 08:32:08', '2025-07-02 08:32:08', '2025-07-02 08:44:40'),
(8, 'App\\Models\\User', 1, 'auth_token', 'ff87d57c9736d6df16878d656ef874f55bed9d29cb2ca9b78463cf1aa51e1bd4', '[\"admin\"]', '2025-07-02 09:24:08', '2025-07-03 08:48:44', '2025-07-02 08:48:44', '2025-07-02 09:24:08'),
(9, 'App\\Models\\User', 1, 'auth_token', '15b4427fea0a0c24c946dcbd3a33d58f75dc62e35ad29e71c5fbea5f70140eae', '[\"admin\"]', '2025-07-02 14:15:27', '2025-07-03 09:25:57', '2025-07-02 09:25:57', '2025-07-02 14:15:27'),
(10, 'App\\Models\\User', 1, 'auth_token', '7f4d96cbc9c8d39173f7ddaae4db4166eba08d8fd5e5d344746326d9ed81b480', '[\"admin\"]', '2025-07-02 14:20:04', '2025-07-03 14:19:33', '2025-07-02 14:19:33', '2025-07-02 14:20:04'),
(11, 'App\\Models\\User', 1, 'auth_token', '234c7f245dc0546d2221405d399f7c3d47df1cc8f18d0cff0325e3e016d9826c', '[\"admin\"]', '2025-07-06 09:20:54', NULL, '2025-07-02 14:28:46', '2025-07-06 09:20:54'),
(12, 'App\\Models\\User', 1, 'auth_token', '0cd64ad5c208bbb5dc055ea4d590490b7418aa90b6e252a5cf231f30c6be288d', '[\"admin\"]', '2025-07-06 10:15:31', NULL, '2025-07-06 09:21:05', '2025-07-06 10:15:31'),
(15, 'App\\Models\\User', 1, 'auth_token', 'd793fbf4ac248694cb195aff1a16a957e2c88d5ae93b1944cf261b6656cbac32', '[\"admin\"]', '2025-07-06 22:05:32', NULL, '2025-07-06 22:02:57', '2025-07-06 22:05:32'),
(16, 'App\\Models\\User', 1, 'auth_token', 'abdb9b3ae0fb147494aefc0a7e9d40aa096bebb734180c4c91f1e41049f40bf1', '[\"admin\"]', '2025-07-06 22:06:56', NULL, '2025-07-06 22:06:52', '2025-07-06 22:06:56'),
(17, 'App\\Models\\User', 1, 'auth_token', '4ac2ab5bd2f33d76849beaff9c30b584d56ddfd347770cbd2e4006229d22205b', '[\"admin\"]', '2025-07-08 21:10:12', NULL, '2025-07-06 22:07:19', '2025-07-08 21:10:12'),
(18, 'App\\Models\\User', 1, 'auth_token', 'b7d31c1edc1259b803de4bbc30fad67f0763be15d07fec6ef73964aca2bec80e', '[\"admin\"]', '2025-07-07 12:12:44', NULL, '2025-07-07 09:16:44', '2025-07-07 12:12:44'),
(19, 'App\\Models\\User', 1, 'auth_token', '30eecbc3bcbf85f899266462efc33895dbad637230b89ced02dd31ee59841a5d', '[\"admin\"]', '2025-07-07 16:05:46', NULL, '2025-07-07 14:14:11', '2025-07-07 16:05:46'),
(20, 'App\\Models\\User', 1, 'auth_token', '17c91ec9347f178c85481dc3853103260858cfbcb9237a482dc9779e13b794cf', '[\"admin\"]', '2025-07-07 20:30:53', NULL, '2025-07-07 20:12:53', '2025-07-07 20:30:53'),
(22, 'App\\Models\\User', 2, 'auth_token', '15c37ddff39c600624acdc52ad4b9828da0b423ad8feecfd309dbf64f8134ec0', '[\"tenant\"]', '2025-07-11 10:07:43', NULL, '2025-07-09 18:05:30', '2025-07-11 10:07:43'),
(23, 'App\\Models\\User', 1, 'auth_token', '2f452e9d5aef17b12a0d3da157611e73efd6d0592d6a43addda185d1fa4952c4', '[\"admin\"]', '2025-07-09 18:38:21', NULL, '2025-07-09 18:17:40', '2025-07-09 18:38:21'),
(24, 'App\\Models\\User', 1, 'auth_token', '20649e3f22ce16750676fd989610cae37a5ee4ef2142387f08109db29cfb9177', '[\"admin\"]', '2025-07-10 11:49:53', NULL, '2025-07-10 11:37:04', '2025-07-10 11:49:53'),
(25, 'App\\Models\\User', 1, 'auth_token', 'b21217ac0586c180157f344d90423ce67b1f531d080e82c0d687c19a06b96072', '[\"admin\"]', '2025-07-10 18:14:15', NULL, '2025-07-10 18:12:41', '2025-07-10 18:14:15'),
(26, 'App\\Models\\User', 1, 'auth_token', 'd40bc2b57aae506e9ebeaead2e8f128251e0336259a4c5dcff387a2b0032b1b2', '[\"admin\"]', '2025-07-11 07:10:09', NULL, '2025-07-11 07:09:35', '2025-07-11 07:10:09'),
(27, 'App\\Models\\User', 2, 'auth_token', 'b407afbc25138b6ed33cb7da7fd44376d9fcb8f896adb20c477f8046c53f024e', '[\"tenant\"]', '2025-07-12 10:06:14', NULL, '2025-07-11 17:35:13', '2025-07-12 10:06:14'),
(32, 'App\\Models\\User', 2, 'auth_token', '9b13f6ab55f0613c20c6b0ef744a50ac5057dc57536224c4466318aa5f67a6de', '[\"tenant\"]', '2025-07-12 11:20:36', NULL, '2025-07-12 10:29:24', '2025-07-12 11:20:36'),
(33, 'App\\Models\\User', 2, 'auth_token', '1f82251b43a1265c2c6a8d95b417f778a9b91c5171a8e44e35733487f0920660', '[\"tenant\"]', '2025-07-12 16:47:52', NULL, '2025-07-12 16:47:49', '2025-07-12 16:47:52'),
(35, 'App\\Models\\User', 2, 'auth_token', '7e53092d11a69aedb1bc3e8d0af92249d08616b416af3d0d02f848d0570c982b', '[\"tenant\"]', '2025-07-13 16:44:49', NULL, '2025-07-12 16:48:47', '2025-07-13 16:44:49'),
(38, 'App\\Models\\User', 1, 'auth_token', 'dbdca9da1ab339a47595dc42b53603c79f3e7e3a362849ef026c7a6358e99960', '[\"admin\"]', '2025-07-13 22:50:09', NULL, '2025-07-13 21:50:47', '2025-07-13 22:50:09'),
(40, 'App\\Models\\User', 2, 'auth_token', '79497bd9a8d44c9b34dff36032e22f52360e01f62256219dc27ec0b5ebfd90bf', '[\"tenant\"]', '2025-07-14 20:09:09', NULL, '2025-07-14 15:49:23', '2025-07-14 20:09:09'),
(41, 'App\\Models\\User', 1, 'auth_token', '80401e3e706a5699a4117be66ed3a5442562ffad114cef14ee09930e29ccdf9c', '[\"admin\"]', '2025-07-19 03:14:23', NULL, '2025-07-19 03:07:23', '2025-07-19 03:14:23'),
(42, 'App\\Models\\User', 2, 'auth_token', '9d0fdc5de88f11b735a02036bdd39f5cccda0045b5fa7e8c38d7ee8f2cfc2fce', '[\"tenant\"]', '2025-07-19 03:57:57', NULL, '2025-07-19 03:19:16', '2025-07-19 03:57:57'),
(43, 'App\\Models\\User', 1, 'auth_token', '8d385e761f179e7e519b1dd6d89242648e5cd035fc6db19436e766f321843f86', '[\"admin\"]', '2025-07-24 08:00:51', NULL, '2025-07-24 07:18:27', '2025-07-24 08:00:51'),
(45, 'App\\Models\\User', 1, 'auth_token', 'af2586e62d07173643c7564a8fb7d1191eadc7978e9d5749a76ff38f91ffe061', '[\"admin\"]', '2025-07-30 16:26:57', NULL, '2025-07-29 22:30:06', '2025-07-30 16:26:57'),
(48, 'App\\Models\\User', 1, 'auth_token', '6295d0f2fb10431a9bf83e682e21a857c5df7297b8cdd8802069e17c61c0f39a', '[\"admin\"]', '2025-07-31 05:08:03', NULL, '2025-07-31 05:05:29', '2025-07-31 05:08:03'),
(51, 'App\\Models\\User', 1, 'auth_token', '9f0bb37ff4ff2ea2f91fabc826b43506cfe46fba56bb23ea80e0f09c9d5befd5', '[\"admin\"]', '2025-08-03 01:29:14', NULL, '2025-08-02 20:18:20', '2025-08-03 01:29:14'),
(55, 'App\\Models\\User', 1, 'auth_token', 'c0d0046ec6e236deb1b01db300dc6964bd9ca13a400885d4762f2a94b566176d', '[\"admin\"]', '2025-08-05 04:55:24', NULL, '2025-08-05 01:32:41', '2025-08-05 04:55:24'),
(56, 'App\\Models\\User', 2, 'auth_token', '66f95a684a9f42f82df5196ced814d12c0f9e3bedfc7b28f7b2bc88acf54aa13', '[\"tenant\"]', '2025-08-05 05:00:17', NULL, '2025-08-05 01:33:00', '2025-08-05 05:00:17');

-- --------------------------------------------------------

--
-- Table structure for table `rfid_cards`
--

CREATE TABLE `rfid_cards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uid` varchar(50) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `card_type` varchar(20) DEFAULT 'primary',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `suspended_at` timestamp NULL DEFAULT NULL,
  `suspension_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rfid_cards`
--

INSERT INTO `rfid_cards` (`id`, `uid`, `user_id`, `tenant_id`, `card_type`, `status`, `suspended_at`, `suspension_reason`, `created_at`, `updated_at`) VALUES
(2, 'C8D81ABB', 4, 3, 'primary', 'active', NULL, NULL, '2025-07-06 09:29:12', '2025-07-06 13:24:42'),
(4, '2384261E', 5, 4, 'primary', 'inactive', NULL, NULL, '2025-07-07 09:40:04', '2025-07-31 01:35:48'),
(5, '32AF2C1E', 2, 1, 'primary', 'inactive', NULL, NULL, '2025-07-10 11:37:52', '2025-08-05 10:34:28');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_name` varchar(255) NOT NULL,
  `monthly_price` decimal(12,2) NOT NULL,
  `status` enum('available','occupied','maintenance','archived') DEFAULT 'available',
  `archived_at` timestamp NULL DEFAULT NULL,
  `archived_reason` varchar(255) DEFAULT NULL,
  `reserved_at` timestamp NULL DEFAULT NULL,
  `reserved_until` timestamp NULL DEFAULT NULL,
  `reserved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `reserved_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_number`, `room_name`, `monthly_price`, `status`, `archived_at`, `archived_reason`, `reserved_at`, `reserved_until`, `reserved_by`, `reserved_reason`, `created_at`, `updated_at`) VALUES
(1, 'A01', 'Kamar A01 - Standard', 1500000.00, 'occupied', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-01 05:32:34', '2025-07-07 09:54:56'),
(2, 'A02', 'Kamar A02 - Standard', 1500000.00, 'occupied', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-01 05:32:34', '2025-07-01 05:32:34'),
(3, 'A03', 'Kamar A03 - Standard', 1500000.00, 'maintenance', '2025-07-07 21:55:56', 'Archived by admin', NULL, NULL, NULL, NULL, '2025-07-01 05:32:34', '2025-07-07 21:56:13'),
(4, 'A04', 'Kamar A04 - Standard', 1500000.00, 'occupied', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-01 05:32:34', '2025-07-01 05:33:51'),
(5, 'B01', 'Kamar B01 - Deluxe', 2000000.00, 'occupied', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-01 05:32:34', '2025-07-29 20:54:04'),
(6, 'B02', 'Kamar B02 - Deluxe', 2000000.00, 'maintenance', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-01 05:32:34', '2025-07-01 05:32:34'),
(7, 'B03', 'Kamar B03 - Deluxe', 2000000.00, 'occupied', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-01 05:32:34', '2025-07-01 12:58:41'),
(8, 'C01', 'Kamar C01 - Premium (Direnovasi)', 2500000.00, 'maintenance', '2025-06-01 05:32:34', 'Sedang dalam proses renovasi besar-besaran', NULL, NULL, NULL, NULL, '2025-01-01 05:32:34', '2025-06-01 05:32:34'),
(9, 'C02', 'Kamar C02 - Premium (Tidak Digunakan)', 2500000.00, 'maintenance', '2025-06-16 05:32:34', 'Tidak digunakan sementara karena pindah lokasi usaha', NULL, NULL, NULL, NULL, '2025-01-01 05:32:34', '2025-07-07 21:56:18'),
(10, 'K01', 'Kamar K01', 1000000.00, 'occupied', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-07 09:54:07', '2025-07-07 21:44:49'),
(11, 'K02', 'Kamar 02', 1000000.00, 'available', NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-08 09:17:38', '2025-08-05 10:32:09'),
(12, 'A05', 'Kamar A04', 1500000.00, 'occupied', NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-05 10:27:52', '2025-08-05 10:30:10');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('Co1kp8GxP9DYknTHzKTQ4caNjrVJEon5PsBjNrvV', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.4202', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiUWxSanZRaWlmTGtsZ2xPdTlWZ3JrS1cxY3JEWFdIWlpXSXFHaWxBQSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1752415118),
('IaZ83XWg4f72vG0xCikockq0LAZwO6ciHvMn03f7', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.4202', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiWHBzOE5LNzI4S3JLME02Zk1RSzZ3R1lsZDBtbVNvTjdnbGxzNDROMSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1752415384),
('Ix7ah0y1WzHIxiuhc6KbMri1VlMXS9QnwxjZxAef', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieDM1RjM5QmNwQWdoTHVGajByaXVOQnJVd3pKSGd2bnBhMVpVcFQxbiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1752415547),
('lWZhs31Td3gZZqhkRJcL3OcxEadCpGDWM6I6ADLp', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.4202', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoibFZBS3dqR0NNZ2Qzc0M1clpBNUgzTVZYekVKNk5CTWZxbXFyR2h0TiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1752415505),
('o7kx2Aq236KMQzvUAx0lR60rq8kBEfzkdaQc1A28', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.4202', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoicVVBNDBkTE0yaVg0ODBkcEN1VWVmSEs5aEg4bG9SSURwbEY3OGhhbSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1752415276),
('OCBOHiLRbt5JYjbOX15fjXx26laLUmrafGFp3Twj', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.4202', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiZktPbXBaMnhGZnlpaTVkSElsUm9XbExFZFpBM1lSSU96ZDRqd1F1OCI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1752415504);

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tenant_code` varchar(255) DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `room_id` bigint(20) UNSIGNED NOT NULL,
  `monthly_rent` decimal(12,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `suspension_reason` text DEFAULT NULL,
  `reactivated_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','moved_out','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`id`, `tenant_code`, `user_id`, `room_id`, `monthly_rent`, `start_date`, `end_date`, `suspended_at`, `suspension_reason`, `reactivated_at`, `status`, `created_at`, `updated_at`) VALUES
(1, 'T001', 2, 1, 1000000.00, '2025-07-06', '2025-08-06', NULL, NULL, NULL, 'active', '2025-07-01 12:32:34', '2025-07-07 11:00:18'),
(2, 'T002', 3, 2, 1500000.00, '2025-07-06', '2025-08-06', NULL, NULL, NULL, 'active', '2025-07-01 12:32:34', '2025-07-07 11:00:34'),
(3, 'T003', 4, 4, 1000000.00, '2025-07-06', '2025-08-06', NULL, NULL, NULL, 'active', '2025-07-01 12:32:34', '2025-07-07 11:00:35'),
(4, 'TNT2507MTKY', 5, 7, 2000000.00, '2025-07-06', '2025-08-06', NULL, NULL, NULL, 'active', '2025-07-01 12:58:41', '2025-07-07 11:00:36'),
(5, 'TNT2507QXK4', 20, 10, 1000000.00, '2025-07-07', '2025-07-08', NULL, NULL, NULL, 'active', '2025-07-07 14:45:28', '2025-07-07 21:23:57'),
(6, 'TNT2507WVIU', 21, 11, 1000000.00, '2025-07-08', '2025-07-08', NULL, NULL, NULL, 'moved_out', '2025-07-08 09:41:44', '2025-07-08 18:30:20'),
(7, 'TNT2507GJJM', 22, 5, 2000000.00, '2025-07-30', NULL, NULL, NULL, NULL, 'active', '2025-07-29 20:54:04', '2025-07-29 20:54:04'),
(8, 'TNT2508IYI8', 23, 12, 1500000.00, '2025-08-05', NULL, NULL, NULL, NULL, 'active', '2025-08-05 10:30:10', '2025-08-05 10:30:10'),
(9, 'TNT2508JXK8', 24, 11, 1000000.00, '2025-08-05', '2025-08-05', NULL, NULL, NULL, 'moved_out', '2025-08-05 10:30:49', '2025-08-05 10:32:09');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','tenant') NOT NULL DEFAULT 'tenant',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin Potuna Kost', 'admin@potunakost.com', '+6281234567890', '$2y$12$wpabXyZUMx.fBRmlAeKIYOfTulvx6BBbMCUm2Xm1Ren/0zljdKZK6', 'admin', 'active', '2025-07-01 05:32:33', '2025-07-01 05:32:33'),
(2, 'Budi Santosos', 'budi@gmail.com', '+6281234567891', '$2y$12$7yk7W6fSynhOkzYLvgibOOdmtJDHjMP5A66a.b9iKHHA4S.YNzUwu', 'tenant', 'active', '2025-07-01 12:32:34', '2025-07-10 17:53:38'),
(3, 'Sari Dewi', 'sari@gmail.com', '+6281234567893', '$2y$12$30m9div9luzhhCTAwtWPfuZJw5buUJgEm3eqGtFkSdmVSr1losQz.', 'tenant', 'active', '2025-07-01 12:32:34', '2025-07-01 12:32:34'),
(4, 'Ahmad Rizki', 'ahmad@gmail.com', '+6281234567895', '$2y$12$LSg.8n1eTFHyUrtA56VK7u36TSFjFBIU5NMk5vUIKqVwXDw8tfgga', 'tenant', 'active', '2025-07-01 12:32:34', '2025-07-01 12:32:34'),
(5, 'IbnuQS', 'ibnuqolbys17@gmail.com', '085155278707', '$2y$12$U57iMxqdUSVz8e7u9d0hneIbuAgkpMmuix35c2Gd3Jfcp12YIGGy.', 'tenant', 'active', '2025-07-01 12:58:41', '2025-07-01 12:58:41'),
(6, 'System Admin', 'admin@localhost.local', '+62000000000', '$2y$12$ToJvYTWaNDQwL.lzSbI2H.9KIJSK4cxwdA7XGarCt6JjTb4MFyVrW', 'admin', 'active', '2025-07-02 08:47:45', '2025-07-02 08:47:45'),
(20, 'TEST 3', 'test1@gmail.com', '08123412412', '$2y$12$DNwLkJjRQNkbPpyJOgRg5O7hzadnnns0TtNIToK8lk3u22Y.G5m0y', 'tenant', 'active', '2025-07-07 14:45:28', '2025-07-07 14:56:21'),
(21, 'test11', 'test11@gmail.com', '08621341265', '$2y$12$i5gPK6Ij2j8axRbqGI1rRuPi6PdOTWzgNnI5u3pYkWcKOHdBCGIIq', 'tenant', 'active', '2025-07-08 09:41:44', '2025-07-08 09:41:44'),
(22, 'test12345', 'test12345@gmail.com', '08123456123', '$2y$12$v.kBwtPY2BMlR5bSbo.93eOnQgDa3YKKg0AdEe1Q5CoIWk0XhRbrC', 'tenant', 'active', '2025-07-29 20:54:04', '2025-07-29 20:54:04'),
(23, 'vigo', 'vigo@gmail.com', '0523118321312', '$2y$12$ljiNDc6gB3WzMzwST6XloOj/dlUm1K.7V48V5rDna3kpxVTniwJge', 'tenant', 'active', '2025-08-05 10:30:10', '2025-08-05 10:30:10'),
(24, 'Gibran', 'gibran@gmail.com', '08412414114', '$2y$12$FQwidR8w/3rpqfXHFM7zz.JvqK0fvc.KORgS7g1DdlG3JOUhLq8K6', 'tenant', 'active', '2025-08-05 10:30:49', '2025-08-05 10:31:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_logs`
--
ALTER TABLE `access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `access_logs_room_id_index` (`room_id`),
  ADD KEY `idx_access_user_time` (`user_id`,`accessed_at`),
  ADD KEY `idx_rfid_uid` (`rfid_uid`),
  ADD KEY `idx_device_id` (`device_id`),
  ADD KEY `idx_access_granted` (`access_granted`),
  ADD KEY `idx_accessed_at` (`accessed_at`),
  ADD KEY `idx_device_time` (`device_id`,`accessed_at`),
  ADD KEY `idx_room_granted` (`room_id`,`access_granted`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `iot_devices`
--
ALTER TABLE `iot_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `iot_devices_device_id_unique` (`device_id`),
  ADD KEY `iot_devices_room_id_index` (`room_id`),
  ADD KEY `iot_devices_status_index` (`status`),
  ADD KEY `iot_devices_device_type_index` (`device_type`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_index` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payments_order_id_unique` (`order_id`),
  ADD KEY `idx_payments_tenant` (`tenant_id`),
  ADD KEY `payments_generated_by_user_id_foreign` (`generated_by_user_id`),
  ADD KEY `idx_snap_token_created_at` (`snap_token_created_at`),
  ADD KEY `idx_expired_at` (`expired_at`),
  ADD KEY `idx_generation_type` (`generation_type`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `rfid_cards`
--
ALTER TABLE `rfid_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rfid_cards_uid_unique` (`uid`),
  ADD KEY `rfid_cards_user_id_index` (`user_id`),
  ADD KEY `idx_rfid_cards_tenant_id` (`tenant_id`),
  ADD KEY `idx_rfid_cards_card_type` (`card_type`),
  ADD KEY `idx_rfid_cards_status_type` (`status`,`card_type`),
  ADD KEY `rfid_cards_status_suspended_at_index` (`status`,`suspended_at`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rooms_room_number_unique` (`room_number`),
  ADD KEY `rooms_archived_at_index` (`archived_at`),
  ADD KEY `rooms_status_reserved_until_index` (`status`,`reserved_until`),
  ADD KEY `rooms_reserved_by_index` (`reserved_by`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenants_tenant_code_unique` (`tenant_code`),
  ADD KEY `idx_tenants_user` (`user_id`),
  ADD KEY `idx_tenant_code` (`tenant_code`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_room_status` (`room_id`,`status`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_end_date` (`end_date`),
  ADD KEY `tenants_status_suspended_at_index` (`status`,`suspended_at`),
  ADD KEY `tenants_suspended_at_index` (`suspended_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_logs`
--
ALTER TABLE `access_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=528;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `iot_devices`
--
ALTER TABLE `iot_devices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `rfid_cards`
--
ALTER TABLE `rfid_cards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `access_logs`
--
ALTER TABLE `access_logs`
  ADD CONSTRAINT `access_logs_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `access_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `iot_devices`
--
ALTER TABLE `iot_devices`
  ADD CONSTRAINT `iot_devices_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_generated_by_user_id_foreign` FOREIGN KEY (`generated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rfid_cards`
--
ALTER TABLE `rfid_cards`
  ADD CONSTRAINT `fk_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`),
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_reserved_by_foreign` FOREIGN KEY (`reserved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tenants`
--
ALTER TABLE `tenants`
  ADD CONSTRAINT `tenants_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
