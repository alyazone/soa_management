-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 26, 2025 at 03:03 AM
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
-- Database: `soa_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `claims`
--

CREATE TABLE `claims` (
  `claim_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `claim_month` varchar(20) DEFAULT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `description` text NOT NULL,
  `travel_date` date DEFAULT NULL,
  `travel_from` varchar(255) DEFAULT NULL,
  `travel_to` varchar(255) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `parking_fee` decimal(10,2) DEFAULT NULL,
  `toll_fee` decimal(10,2) DEFAULT NULL,
  `miles_traveled` decimal(10,2) DEFAULT NULL,
  `km_rate` decimal(10,2) DEFAULT NULL,
  `total_km_amount` decimal(10,2) DEFAULT NULL,
  `total_meal_amount` decimal(10,2) DEFAULT 0.00,
  `amount` decimal(10,2) NOT NULL,
  `employee_signature` tinyint(1) DEFAULT 0,
  `signature_date` date DEFAULT NULL,
  `approval_signature` tinyint(1) DEFAULT 0,
  `approval_date` date DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `submitted_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_date` timestamp NULL DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `approved_by` varchar(100) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `payment_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `claims`
--

INSERT INTO `claims` (`claim_id`, `staff_id`, `claim_month`, `vehicle_type`, `description`, `travel_date`, `travel_from`, `travel_to`, `purpose`, `parking_fee`, `toll_fee`, `miles_traveled`, `km_rate`, `total_km_amount`, `total_meal_amount`, `amount`, `employee_signature`, `signature_date`, `approval_signature`, `approval_date`, `status`, `submitted_date`, `processed_date`, `processed_by`, `created_at`, `approved_by`, `rejection_reason`, `payment_details`) VALUES
(12, 3, 'May', 'Car', 'Mileage Reimbursement for May - Total KM: 1100', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.50, 700.00, 0.00, 735.00, 1, '2025-05-07', 1, '2025-05-07', '', '2025-05-07 04:10:53', NULL, NULL, '2025-05-07 12:10:53', 'admin', NULL, 'Ref: 020394939393'),
(13, 9, 'May', 'Car', 'Mileage Reimbursement for May - Total KM: 1500', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.50, 900.00, 0.00, 945.00, 1, '2025-05-07', 1, '2025-05-07', 'Rejected', '2025-05-07 04:26:14', NULL, NULL, '2025-05-07 12:26:14', 'admin', 'gvfg', NULL),
(14, 9, 'May', 'Car', 'Mileage Reimbursement for May - Total KM: 600', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.50, 450.00, 0.00, 470.00, 1, '2025-05-07', 1, '2025-05-07', 'Rejected', '2025-05-07 04:35:14', NULL, NULL, '2025-05-07 12:35:14', 'admin', 'Wrong Milleage', NULL),
(15, 7, 'May', 'Car', 'Mileage Reimbursement for May - Total KM: 249', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.80, 199.20, 0.00, 199.20, 1, '2025-05-07', 0, NULL, 'Pending', '2025-05-07 07:30:01', NULL, NULL, '2025-05-07 15:30:01', NULL, NULL, NULL),
(16, 7, 'May', 'Car', 'Mileage Reimbursement for May - Total KM: 123', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.80, 98.40, 0.00, 98.40, 1, '2025-05-07', 0, NULL, 'Pending', '2025-05-07 07:32:12', NULL, NULL, '2025-05-07 15:32:12', NULL, NULL, NULL),
(17, 10, 'May', 'Car', 'Mileage Reimbursement for May - Total KM: 1600', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.80, 1380.00, 0.00, 1430.00, 1, '2025-05-08', 0, NULL, 'Pending', '2025-05-08 03:39:48', NULL, NULL, '2025-05-08 11:39:48', NULL, NULL, NULL),
(18, 3, 'May', 'Car', 'Mileage Reimbursement for May - Total KM: 400', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.80, 320.00, 0.00, 360.00, 1, '2025-05-13', 1, '2025-05-13', 'Approved', '2025-05-13 01:47:49', NULL, NULL, '2025-05-13 09:47:49', 'admin', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `claim_meal_entries`
--

CREATE TABLE `claim_meal_entries` (
  `meal_id` int(11) NOT NULL,
  `claim_id` int(11) NOT NULL,
  `meal_date` date NOT NULL,
  `meal_type` enum('Breakfast','Lunch','Dinner','Other') NOT NULL,
  `description` varchar(255) NOT NULL,
  `receipt_reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `claim_travel_entries`
--

CREATE TABLE `claim_travel_entries` (
  `entry_id` int(11) NOT NULL,
  `claim_id` int(11) NOT NULL,
  `travel_date` date NOT NULL,
  `travel_from` varchar(255) NOT NULL,
  `travel_to` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `parking_fee` decimal(10,2) DEFAULT 0.00,
  `toll_fee` decimal(10,2) DEFAULT 0.00,
  `miles_traveled` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `claim_travel_entries`
--

INSERT INTO `claim_travel_entries` (`entry_id`, `claim_id`, `travel_date`, `travel_from`, `travel_to`, `purpose`, `parking_fee`, `toll_fee`, `miles_traveled`, `created_at`) VALUES
(8, 12, '2025-05-07', 'Putrajaya', 'KL', 'Maintenance', 10.00, 15.00, 1000.00, '2025-05-07 04:10:53'),
(9, 12, '2025-05-07', 'KL', 'Melaka', 'Maintenance', 0.00, 10.00, 100.00, '2025-05-07 04:10:53'),
(12, 13, '2025-05-07', 'Beranang', 'Kuala Lumpur', 'Working', 10.00, 15.00, 500.00, '2025-05-07 04:26:52'),
(13, 13, '2025-05-07', 'Kuala Lumpur', 'Melaka', 'Maintenance', 5.00, 15.00, 1000.00, '2025-05-07 04:26:52'),
(14, 14, '2025-05-07', 'Johor', 'Melaka', 'Maintenance', 10.00, 10.00, 600.00, '2025-05-07 04:35:14'),
(15, 15, '2025-04-28', 'itech', 'mrpp', 'PM MRPP & PDPP', 0.00, 0.00, 123.00, '2025-05-07 07:30:01'),
(16, 15, '2025-04-28', 'mrpp', 'pdpp', 'PM MRPP & PDPP', 0.00, 0.00, 63.00, '2025-05-07 07:30:01'),
(17, 15, '2025-04-28', 'pdpp', 'itech', 'PM MRPP & PDPP', 0.00, 0.00, 63.00, '2025-05-07 07:30:01'),
(18, 16, '2025-04-28', 'itech', 'mrpp', 'PM MRPP & PDPP', 0.00, 0.00, 123.00, '2025-05-07 07:32:12'),
(23, 17, '2025-05-08', 'KL', 'Putrajaya', 'KLFW', 10.00, 10.00, 800.00, '2025-05-08 03:41:52'),
(24, 17, '2025-05-08', 'Putrajaya', 'JB', 'Working', 10.00, 20.00, 800.00, '2025-05-08 03:41:52'),
(25, 18, '2025-05-13', 'asdd', 'fff', 'ffuu', 10.00, 10.00, 200.00, '2025-05-13 01:47:49'),
(26, 18, '2025-05-13', 'ffuu', 'ggg', 'asdd', 10.00, 10.00, 200.00, '2025-05-13 01:47:49');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `client_id` int(11) NOT NULL,
  `client_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `pic_name` varchar(100) NOT NULL,
  `pic_contact` varchar(50) NOT NULL,
  `pic_email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`client_id`, `client_name`, `address`, `pic_name`, `pic_contact`, `pic_email`, `created_at`, `updated_at`) VALUES
(1, 'Petronas', 'cknddvd,ldlfd', 'En. Abu', '20348438482', 'abu@gmail.com', '2025-04-15 01:00:53', '2025-04-21 01:03:54'),
(2, 'PSP Pipeline', 'dfjdkofef', 'Ms. Han', '20394843', 'ps@gmail.com', '2025-04-15 01:13:53', '2025-04-15 01:13:53'),
(5, 'MBPJP', 'No.123, Jalan Ampang, 23400, Kuala Lumpur', 'Mrs Sarah', '012938000', 'sarah@mbpj.com', '2025-04-21 01:09:55', '2025-04-21 01:10:22'),
(7, 'BIBI!!!!!!!!!!', 'SNFJNFJJJJ', 'Ms. Alya', '19293202', 'alya@gmail.com', '2025-04-21 01:47:21', '2025-08-04 07:04:57');

-- --------------------------------------------------------

--
-- Table structure for table `client_soa`
--

CREATE TABLE `client_soa` (
  `soa_id` int(11) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `terms` varchar(50) NOT NULL,
  `purchase_date` date DEFAULT NULL,
  `issue_date` date NOT NULL,
  `po_number` varchar(50) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `due_date` date NOT NULL,
  `service_description` text NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Paid','Overdue','Closed') DEFAULT 'Pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_soa`
--

INSERT INTO `client_soa` (`soa_id`, `account_number`, `client_id`, `terms`, `purchase_date`, `issue_date`, `po_number`, `invoice_number`, `due_date`, `service_description`, `total_amount`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(3, '435', 5, 'Qui unde quia lorem', '2022-04-29', '1986-08-05', '805', '464', '2020-10-26', 'Quia esse maiores no', 4500.00, 'Pending', 3, '2025-05-05 00:20:48', '2025-05-05 00:20:48'),
(4, '585', 5, 'Ab natus beatae debi', '1981-01-07', '1990-05-24', '75', '364', '1991-11-05', 'Illo eligendi occaec', 400.00, 'Pending', 3, '2025-05-06 06:52:34', '2025-05-06 06:52:34'),
(5, '804', 7, 'Sint possimus adip', '1980-03-21', '1999-06-22', '999', '331', '1970-03-08', 'Deleniti debitis at', 10000.00, 'Closed', 3, '2025-05-06 07:13:26', '2025-08-05 06:59:49'),
(6, '361', 2, 'Distinctio Rerum al', '2000-08-11', '2006-08-23', '801', '911', '2012-09-04', 'Cillum ratione molli', 30000.00, 'Pending', 3, '2025-05-07 00:42:34', '2025-05-07 06:17:43'),
(7, '54', 1, 'Sed alias ipsa quaeaaaaaaaaaaaaaaaaaaaa', '1987-06-25', '2014-08-16', '272', '691', '2011-11-29', 'Nobis et esse numqua', 500.00, 'Pending', 3, '2025-05-07 00:43:57', '2025-08-05 06:57:10'),
(8, '949', 5, 'Alias ut cumque non', '2009-08-08', '1997-03-14', '225', '652', '2022-09-13', 'Aut laboris vel repr', 64.00, 'Overdue', 3, '2025-08-05 07:19:26', '2025-08-05 07:19:37');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL,
  `document_type` enum('Receipt','Invoice','Warranty','Claim') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `reference_type` enum('Client','Supplier','Staff','SOA') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploaded_by` int(11) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`document_id`, `document_type`, `reference_id`, `reference_type`, `file_name`, `file_path`, `upload_date`, `uploaded_by`, `description`) VALUES
(2, 'Invoice', 5, 'Client', 'TEKS UCAPAN PENGERUSI KYROL SECURITY LABS.pdf', 'uploads/invoices/68bfeb307c43a.pdf', '2025-09-09 08:54:08', 3, 'dddnmdk');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`category_id`, `category_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Desktop / PC', 'Desktop / PC only', '2025-04-16 07:03:26', '2025-04-16 07:03:26'),
(2, 'Laptop', 'Laptop only', '2025-04-16 07:20:47', '2025-04-16 07:20:47'),
(3, 'Server', 'sdjdjd', '2025-04-21 01:53:56', '2025-04-21 01:53:56'),
(4, 'Other Hardware', 'Keyboard, Switch, Motherboard and etc', '2025-04-22 02:04:50', '2025-04-22 02:04:50');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `model_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `status` enum('Available','Assigned','Maintenance','Disposed') DEFAULT 'Available',
  `location` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`item_id`, `item_name`, `category_id`, `supplier_id`, `serial_number`, `model_number`, `purchase_date`, `purchase_price`, `warranty_expiry`, `status`, `location`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'HP Laptop', 2, 1, '293839291', '392238292', '2025-04-01', 3456.00, '2028-03-16', 'Assigned', 'Office', 'Assigned to En. Amir', 3, '2025-04-16 07:22:27', '2025-04-16 07:22:27'),
(2, 'Desktop ABC', 1, 2, '2938492', '20392938', '2020-01-28', 3000.00, '2025-04-16', 'Disposed', 'Storage room', '', 3, '2025-04-16 07:27:29', '2025-04-17 04:03:38'),
(3, 'Laptop ABC', 2, 1, '3943029302', '19239201', '2025-02-05', 12345.00, '2027-06-16', 'Maintenance', 'Office', 'Need to be fixed', 3, '2025-04-16 07:44:01', '2025-04-16 07:44:01'),
(4, 'PC ABCD', 1, 2, '394838292', '1939292', '2025-04-09', 123493.00, '2027-04-16', 'Disposed', 'Office', '', 3, '2025-04-16 07:49:35', '2025-04-22 07:08:14'),
(5, 'Server001', 3, 4, '193930220', 'SER20392', '2025-04-10', 7800.00, '2025-04-23', 'Maintenance', 'Office', '', 3, '2025-04-21 01:55:14', '2025-04-21 01:56:51'),
(9, 'MOUSE01', 4, 1, 'MOUSE01023', 'MSHDH02', '2025-04-03', 100.00, '2025-12-22', 'Available', 'STORAGE ROOM', 'EXTRA MOUSE', 3, '2025-04-22 03:21:24', '2025-04-22 03:21:24'),
(10, 'QAAAAAAAAAAAAAAAAAA', 2, 4, 'FFJFDKDLD', '3049302', '2025-04-02', 234.00, '2026-11-18', 'Available', 'Storage room', 'jfdnkdkdsl', 3, '2025-04-22 06:30:36', '2025-04-22 07:08:35');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_maintenance`
--

CREATE TABLE `inventory_maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `maintenance_date` date NOT NULL,
  `maintenance_type` varchar(100) NOT NULL,
  `performed_by` varchar(100) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_maintenance`
--

INSERT INTO `inventory_maintenance` (`maintenance_id`, `item_id`, `maintenance_date`, `maintenance_type`, `performed_by`, `cost`, `description`, `next_maintenance_date`, `created_by`, `created_at`) VALUES
(1, 2, '2025-04-17', 'Repair', 'Ali Abu', 1234.00, 'replace the motherboard with new one', '2025-04-25', 6, '2025-04-17 04:03:05');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `transaction_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `transaction_type` enum('Purchase','Assignment','Return','Maintenance','Disposal') NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `from_status` varchar(50) DEFAULT NULL,
  `to_status` varchar(50) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `performed_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`transaction_id`, `item_id`, `transaction_type`, `quantity`, `from_status`, `to_status`, `assigned_to`, `transaction_date`, `notes`, `performed_by`) VALUES
(1, 1, 'Purchase', 1, NULL, 'Assigned', NULL, '2025-04-16 07:22:28', 'Initial purchase', 3),
(2, 2, 'Purchase', 1, NULL, 'Available', NULL, '2025-04-16 07:27:29', 'Initial purchase', 3),
(3, 3, 'Purchase', 1, NULL, 'Maintenance', NULL, '2025-04-16 07:44:01', 'Initial purchase', 3),
(4, 4, 'Purchase', 1, NULL, 'Disposed', NULL, '2025-04-16 07:49:35', 'Initial purchase', 3),
(5, 2, 'Assignment', 1, 'Available', 'Assigned', 6, '2025-04-17 03:17:05', 'assign this laptop to Mr. Adam', 6),
(6, 2, 'Maintenance', 1, 'Assigned', 'Maintenance', NULL, '2025-04-17 04:03:05', 'replace the motherboard with new one', 6),
(7, 2, 'Return', 1, 'Maintenance', 'Available', NULL, '2025-04-17 04:03:28', '', 6),
(8, 2, 'Disposal', 1, 'Available', 'Disposed', NULL, '2025-04-17 04:03:38', '', 6),
(9, 5, 'Purchase', 1, NULL, 'Available', NULL, '2025-04-21 01:55:14', 'Initial purchase', 3),
(10, 5, 'Assignment', 1, 'Available', 'Assigned', 7, '2025-04-21 01:56:24', '', 3),
(11, 5, 'Maintenance', 1, 'Assigned', 'Maintenance', NULL, '2025-04-21 01:56:51', '', 3),
(12, 9, 'Purchase', 1, NULL, 'Available', NULL, '2025-04-22 03:21:24', 'Initial purchase', 3),
(13, 10, 'Purchase', 1, NULL, 'Available', NULL, '2025-04-22 06:30:36', 'Initial purchase', 3);

-- --------------------------------------------------------

--
-- Table structure for table `mileage_rates`
--

CREATE TABLE `mileage_rates` (
  `rate_id` int(11) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL,
  `km_threshold` int(11) NOT NULL,
  `rate_per_km` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mileage_rates`
--

INSERT INTO `mileage_rates` (`rate_id`, `vehicle_type`, `km_threshold`, `rate_per_km`, `created_at`, `updated_at`) VALUES
(1, 'Car', 500, 0.80, '2025-05-07 02:12:17', '2025-05-07 02:12:17'),
(2, 'Car', 999999, 0.50, '2025-05-07 02:12:17', '2025-05-07 02:12:17'),
(3, 'Motorcycle', 999999, 0.50, '2025-05-07 02:12:17', '2025-05-07 02:12:17');

-- --------------------------------------------------------

--
-- Table structure for table `outstation_applications`
--

CREATE TABLE `outstation_applications` (
  `application_id` int(11) NOT NULL,
  `application_number` varchar(50) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `purpose_details` text NOT NULL,
  `destination` varchar(255) NOT NULL,
  `departure_date` date NOT NULL,
  `departure_time` time DEFAULT NULL,
  `return_date` date NOT NULL,
  `return_time` time DEFAULT NULL,
  `total_nights` int(11) NOT NULL DEFAULT 0,
  `is_claimable` tinyint(1) NOT NULL DEFAULT 0,
  `transportation_mode` varchar(100) NOT NULL,
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `accommodation_details` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled','Completed') NOT NULL DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `outstation_applications`
--

INSERT INTO `outstation_applications` (`application_id`, `application_number`, `staff_id`, `purpose`, `purpose_details`, `destination`, `departure_date`, `departure_time`, `return_date`, `return_time`, `total_nights`, `is_claimable`, `transportation_mode`, `estimated_cost`, `accommodation_details`, `remarks`, `status`, `approved_by`, `approved_at`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 'OSL-2025-2035', 9, 'Maintenance', 'PM TESTING', 'Melaka', '2025-11-27', '09:00:00', '2025-11-28', '17:00:00', 1, 1, 'Company Vehicle', 0.00, 'HOTEL ABC TESTING', 'TESTING REMARKS', 'Pending', NULL, NULL, NULL, '2025-11-26 01:28:08', '2025-11-26 01:42:58');

-- --------------------------------------------------------

--
-- Table structure for table `outstation_claims`
--

CREATE TABLE `outstation_claims` (
  `claim_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `claim_date` date NOT NULL,
  `claim_status` enum('Submitted','Approved','Rejected','Paid') NOT NULL DEFAULT 'Submitted',
  `claim_amount` decimal(10,2) DEFAULT 0.00,
  `actual_expenses` decimal(10,2) DEFAULT 0.00,
  `supporting_documents` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `outstation_settings`
--

CREATE TABLE `outstation_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `outstation_settings`
--

INSERT INTO `outstation_settings` (`setting_id`, `setting_key`, `setting_value`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'minimum_nights_claimable', '1', 'Minimum number of nights required to qualify for outstation leave claim', NULL, '2025-11-26 01:23:33'),
(2, 'default_allowance_per_day', '100.00', 'Default daily allowance amount in RM', NULL, '2025-11-26 01:23:33'),
(3, 'require_manager_approval', '1', 'Whether applications require manager approval (1=yes, 0=no)', NULL, '2025-11-26 01:23:33'),
(4, 'auto_approve_days', '0', 'Number of days after which pending applications are auto-approved (0=disabled)', NULL, '2025-11-26 01:23:33');

-- --------------------------------------------------------

--
-- Table structure for table `soa`
--

CREATE TABLE `soa` (
  `soa_id` int(11) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `terms` varchar(50) NOT NULL,
  `purchase_date` date NOT NULL,
  `issue_date` date NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `balance_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Paid','Overdue') DEFAULT 'Pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `soa`
--

INSERT INTO `soa` (`soa_id`, `account_number`, `client_id`, `supplier_id`, `terms`, `purchase_date`, `issue_date`, `po_number`, `invoice_number`, `description`, `balance_amount`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '34829329', 1, 2, 'Net 30', '2025-04-10', '2025-04-15', '123445667', '494202', 'djdjfjeej', 234567.00, 'Paid', 3, '2025-04-15 01:25:04', '2025-04-15 08:22:03'),
(2, '349384573', 2, 1, 'Net 30', '2025-04-01', '2025-04-15', '2938283', '1229392', 'hrhjsjshf', 1234.00, 'Pending', 3, '2025-04-15 08:18:07', '2025-04-16 01:00:57'),
(3, '392000', 5, 4, 'Net 40', '2025-04-18', '2025-04-24', '123943', '3339392', 'SOA Cisco', 1900.00, 'Pending', 3, '2025-04-21 01:31:28', '2025-04-21 01:31:28'),
(4, '1239392', 7, 4, 'net 3', '2025-04-24', '2025-04-23', '293292', '384933', 'fjfjdsjs', 912.00, 'Pending', 3, '2025-04-21 01:51:21', '2025-04-21 01:51:36');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department` varchar(50) NOT NULL,
  `position` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staff_id`, `username`, `password`, `full_name`, `email`, `department`, `position`, `created_at`, `updated_at`) VALUES
(3, 'admin', '$2y$10$7LscS9VBVelEV68hRc2B/.uRgvEAYIfKlwf/lOE9j7.xz3dk2n6n6', 'System Administrator', 'admin@example.com', 'IT', 'Admin', '2025-04-14 07:57:32', '2025-04-14 07:57:32'),
(6, 'manager', '$2y$10$DhnOq4mTAYIKZAnT8Lil3OwKIxlH5i/5F6bhM2q9/aX4jASngwko6', 'manager', 'mngr@gmail.com', 'HR Department', 'Manager', '2025-04-14 08:05:29', '2025-11-18 07:48:25'),
(7, 'staff', '$2y$10$KAW4a43XxboYNev.SFSJh.L3Cp9HUVK1wRV47KWVUod4B3o0hBl9C', 'KYROL staff', 'staff@kyrolsecuritylabs.com', 'IT Department', 'Staff', '2025-04-17 04:13:09', '2025-04-17 04:13:09'),
(8, 'admin123', '$2y$10$LsYlKjjjYjAHPuQqgjBVI.HLvi2BYcU0B5R1w0EYWXLg6kLlKqf/i', 'admin', 'admin@gmail.com', 'HR Department', 'Admin', '2025-04-21 01:42:32', '2025-07-23 05:01:01'),
(9, 'staff-alya', '$2y$10$JOcpSkae8snShdrtB9cqi.k7DG1yQy4JWm.v3A6aS1k9C90xpyLUu', 'Alya Maisarah', 'alya@kyrolsecuritylabs.com', 'IT Department', 'Staff', '2025-05-07 04:22:55', '2025-11-18 08:14:46'),
(10, 'boss', '$2y$10$KioDQ5ZmtzkfYAH5g0yAFurVjXIhua3vMJ/V1ZBEYfdlurDYxWwsm', 'En. Khairol', 'khairol@kyrolsecuritylabs.com', 'Management', 'Manager', '2025-05-08 03:07:12', '2025-05-08 03:07:12');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `pic_name` varchar(100) NOT NULL,
  `pic_contact` varchar(50) NOT NULL,
  `pic_email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `supplier_name`, `address`, `pic_name`, `pic_contact`, `pic_email`, `created_at`, `updated_at`) VALUES
(1, 'HP', 'jncdkdowcndd', 'hperson', '0129384928', 'hp@gmail.com', '2025-04-15 01:14:55', '2025-04-15 01:14:55'),
(2, 'F&N', 'dsndoddo', 'lili', '023982833', 'fn@gmail.com', '2025-04-15 01:23:54', '2025-04-15 01:23:54'),
(4, 'Cisco!', 'No.123 Jalan Ampang, 34900, Kuala Lumpur', 'Mr Arif', '01938493', 'arif@gmail.com', '2025-04-21 01:20:48', '2025-04-22 00:48:38');

-- --------------------------------------------------------

--
-- Table structure for table `supplier_soa`
--

CREATE TABLE `supplier_soa` (
  `soa_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `issue_date` date NOT NULL,
  `payment_due_date` date NOT NULL,
  `purchase_description` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('Pending','Paid','Overdue') DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier_soa`
--

INSERT INTO `supplier_soa` (`soa_id`, `invoice_number`, `supplier_id`, `issue_date`, `payment_due_date`, `purchase_description`, `amount`, `payment_status`, `payment_method`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '395', 2, '2017-06-30', '1972-11-02', 'Aut ullamco irure euaaaaaaaaaaaaaaaaaaaaaa!', 23000.00, 'Paid', 'Bank Transfer', 3, '2025-04-30 04:12:41', '2025-04-30 04:59:52'),
(2, '259', 1, '2012-04-01', '1982-03-26', 'Accusantium natus vo', 10000.00, 'Pending', 'Bank Transfer', 3, '2025-04-30 04:21:26', '2025-04-30 04:21:26'),
(3, '738', 4, '1996-06-06', '1980-06-29', 'Accusamus sunt debi', 10000.00, 'Paid', 'Bank Transfer', 3, '2025-04-30 04:22:08', '2025-04-30 04:22:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `claims`
--
ALTER TABLE `claims`
  ADD PRIMARY KEY (`claim_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indexes for table `claim_meal_entries`
--
ALTER TABLE `claim_meal_entries`
  ADD PRIMARY KEY (`meal_id`),
  ADD KEY `claim_id` (`claim_id`);

--
-- Indexes for table `claim_travel_entries`
--
ALTER TABLE `claim_travel_entries`
  ADD PRIMARY KEY (`entry_id`),
  ADD KEY `claim_id` (`claim_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`);

--
-- Indexes for table `client_soa`
--
ALTER TABLE `client_soa`
  ADD PRIMARY KEY (`soa_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `inventory_maintenance`
--
ALTER TABLE `inventory_maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `mileage_rates`
--
ALTER TABLE `mileage_rates`
  ADD PRIMARY KEY (`rate_id`);

--
-- Indexes for table `outstation_applications`
--
ALTER TABLE `outstation_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD KEY `fk_staff` (`staff_id`),
  ADD KEY `fk_approver` (`approved_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_departure_date` (`departure_date`),
  ADD KEY `idx_is_claimable` (`is_claimable`),
  ADD KEY `idx_application_number` (`application_number`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `outstation_claims`
--
ALTER TABLE `outstation_claims`
  ADD PRIMARY KEY (`claim_id`),
  ADD KEY `fk_claim_application` (`application_id`),
  ADD KEY `fk_claim_staff` (`staff_id`),
  ADD KEY `fk_claim_processor` (`processed_by`),
  ADD KEY `idx_claim_status` (`claim_status`);

--
-- Indexes for table `outstation_settings`
--
ALTER TABLE `outstation_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `soa`
--
ALTER TABLE `soa`
  ADD PRIMARY KEY (`soa_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `supplier_soa`
--
ALTER TABLE `supplier_soa`
  ADD PRIMARY KEY (`soa_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `claims`
--
ALTER TABLE `claims`
  MODIFY `claim_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `claim_meal_entries`
--
ALTER TABLE `claim_meal_entries`
  MODIFY `meal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `claim_travel_entries`
--
ALTER TABLE `claim_travel_entries`
  MODIFY `entry_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `client_soa`
--
ALTER TABLE `client_soa`
  MODIFY `soa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inventory_maintenance`
--
ALTER TABLE `inventory_maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `mileage_rates`
--
ALTER TABLE `mileage_rates`
  MODIFY `rate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `outstation_applications`
--
ALTER TABLE `outstation_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `outstation_claims`
--
ALTER TABLE `outstation_claims`
  MODIFY `claim_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `outstation_settings`
--
ALTER TABLE `outstation_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `soa`
--
ALTER TABLE `soa`
  MODIFY `soa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `supplier_soa`
--
ALTER TABLE `supplier_soa`
  MODIFY `soa_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `claims`
--
ALTER TABLE `claims`
  ADD CONSTRAINT `claims_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`),
  ADD CONSTRAINT `claims_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `claim_meal_entries`
--
ALTER TABLE `claim_meal_entries`
  ADD CONSTRAINT `claim_meal_entries_ibfk_1` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`claim_id`) ON DELETE CASCADE;

--
-- Constraints for table `claim_travel_entries`
--
ALTER TABLE `claim_travel_entries`
  ADD CONSTRAINT `claim_travel_entries_ibfk_1` FOREIGN KEY (`claim_id`) REFERENCES `claims` (`claim_id`) ON DELETE CASCADE;

--
-- Constraints for table `client_soa`
--
ALTER TABLE `client_soa`
  ADD CONSTRAINT `client_soa_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  ADD CONSTRAINT `client_soa_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`category_id`),
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `inventory_items_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `inventory_maintenance`
--
ALTER TABLE `inventory_maintenance`
  ADD CONSTRAINT `inventory_maintenance_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`),
  ADD CONSTRAINT `inventory_maintenance_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`item_id`),
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`staff_id`),
  ADD CONSTRAINT `inventory_transactions_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `outstation_applications`
--
ALTER TABLE `outstation_applications`
  ADD CONSTRAINT `fk_outstation_approver` FOREIGN KEY (`approved_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_outstation_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE;

--
-- Constraints for table `outstation_claims`
--
ALTER TABLE `outstation_claims`
  ADD CONSTRAINT `fk_claim_application` FOREIGN KEY (`application_id`) REFERENCES `outstation_applications` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_claim_processor` FOREIGN KEY (`processed_by`) REFERENCES `staff` (`staff_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_claim_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`staff_id`) ON DELETE CASCADE;

--
-- Constraints for table `soa`
--
ALTER TABLE `soa`
  ADD CONSTRAINT `soa_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`),
  ADD CONSTRAINT `soa_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `soa_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`);

--
-- Constraints for table `supplier_soa`
--
ALTER TABLE `supplier_soa`
  ADD CONSTRAINT `supplier_soa_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `supplier_soa_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `staff` (`staff_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
