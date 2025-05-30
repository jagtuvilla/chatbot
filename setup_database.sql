-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: May 30, 2025 at 04:27 PM
-- Server version: 10.4.32-MariaDB-log
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spendex`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'PHP',
  `description` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `name`, `type`, `balance`, `currency`, `description`, `user_id`, `created_at`) VALUES
(1, 'CASH(ON HAND)', 'cash', 5000.00, 'JPY', '', 1, '2025-05-26 23:55:31'),
(2, 'BPO BANK ACCOUNT', 'savings', 2554.00, 'JPY', '', 1, '2025-05-26 23:55:44'),
(3, 'LANDBANK ACCOUNT', 'savings', 10000.00, 'JPY', '', 1, '2025-05-26 23:56:07'),
(15, 'GCASH', 'ewallet', 47268.00, 'JPY', '', 1, '2025-05-28 21:06:33'),
(16, 'Gcash', 'ewallet', 10000.00, 'PHP', '', 7, '2025-05-28 23:14:19'),
(17, 'LANDBANK ACCOUNT', 'credit', 20000.00, 'PHP', '', 7, '2025-05-28 23:14:48'),
(18, 'BPO ACCOUNT', 'savings', 20000.00, 'PHP', '', 7, '2025-05-28 23:15:29'),
(19, 'Cash on Hand', 'cash', 5000.00, 'EUR', '', 7, '2025-05-28 23:15:58'),
(26, 'weee', 'savings', 3123000.00, 'PHP', '', 9, '2025-05-30 12:13:26'),
(28, 'waw', 'checking', -2377.00, 'PHP', '', 9, '2025-05-30 12:15:43'),
(31, 'Account 1', 'checking', 28300.00, 'KRW', '', 12, '2025-05-30 14:04:25'),
(32, 'Account 2', 'credit', 5000.00, 'KRW', '', 12, '2025-05-30 14:07:49');

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `recurring` tinyint(1) DEFAULT 0,
  `recurring_type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`id`, `name`, `amount`, `category_id`, `start_date`, `end_date`, `user_id`, `created_at`, `recurring`, `recurring_type`) VALUES
(25, 'Food monthly', 12000.00, 32, '2025-05-28', '2025-05-31', 1, '2025-05-28 16:30:47', 0, 'monthly'),
(26, 'Transportation', 1000.00, 33, '2025-05-28', '2025-06-02', 1, '2025-05-28 16:33:29', 0, 'monthly'),
(27, 'Medicine', 200.00, 38, '2025-05-28', '2025-06-01', 1, '2025-05-28 16:35:51', 0, 'monthly'),
(28, 'Shopee', 600.00, 37, '2025-05-28', '2025-06-28', 1, '2025-05-28 21:07:25', 0, NULL),
(31, 'School Supplies', 8000.00, 39, '2025-05-29', '2025-05-30', 1, '2025-05-28 21:15:39', 0, 'monthly'),
(32, 'Electrical Supplies', 14000.00, 35, '2025-05-28', '2025-06-28', 1, '2025-05-28 21:16:08', 0, NULL),
(36, 'Samgyup', 5000.00, 91, '2025-05-29', '2025-06-29', 7, '2025-05-28 23:17:43', 0, NULL),
(37, 'Monthly Salary', 123.00, 87, '2025-05-29', '2025-05-30', 7, '2025-05-28 23:19:02', 0, 'monthly'),
(45, 'Educat', 122.00, 122, '2025-05-30', '2025-06-30', 9, '2025-05-30 12:32:53', 0, NULL),
(47, 'Budget 1', 1000.00, 159, '2025-05-29', '2025-06-30', 12, '2025-05-30 14:04:45', 0, NULL),
(48, 'Budget 2', 200.00, 153, '2025-05-30', '2025-06-05', 12, '2025-05-30 14:06:54', 0, 'monthly');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` enum('income','expense') NOT NULL DEFAULT 'expense',
  `color` varchar(7) NOT NULL DEFAULT '#4F46E5',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `user_id`, `name`, `type`, `color`, `created_at`) VALUES
(27, 1, 'Salary', 'income', '#10B981', '2025-05-26 23:54:31'),
(28, 1, 'Freelance', 'income', '#F59E0B', '2025-05-26 23:54:31'),
(29, 1, 'Investments', 'income', '#8b5cf6', '2025-05-26 23:54:31'),
(30, 1, 'Gifts', 'income', '#EC4899', '2025-05-26 23:54:31'),
(32, 1, 'Food', 'expense', '#EF4444', '2025-05-26 23:54:31'),
(33, 1, 'Transportation', 'expense', '#84cc16', '2025-05-26 23:54:31'),
(34, 1, 'Housing', 'expense', '#F97316', '2025-05-26 23:54:31'),
(35, 1, 'Utilities', 'expense', '#6366F1', '2025-05-26 23:54:31'),
(36, 1, 'Entertainment', 'expense', '#4F46E5', '2025-05-26 23:54:31'),
(37, 1, 'Shopping', 'expense', '#10B981', '2025-05-26 23:54:31'),
(38, 1, 'Healthcare', 'expense', '#F59E0B', '2025-05-26 23:54:31'),
(39, 1, 'Education', 'expense', '#8B5CF6', '2025-05-26 23:54:31'),
(41, 4, 'Salary', 'income', '#10B981', '2025-05-27 04:23:11'),
(42, 4, 'Freelance', 'income', '#F59E0B', '2025-05-27 04:23:11'),
(43, 4, 'Investments', 'income', '#8B5CF6', '2025-05-27 04:23:11'),
(44, 4, 'Gifts', 'income', '#EC4899', '2025-05-27 04:23:11'),
(45, 4, 'Other Income', 'income', '#06B6D4', '2025-05-27 04:23:11'),
(46, 4, 'Food', 'expense', '#EF4444', '2025-05-27 04:23:11'),
(47, 4, 'Transportation', 'expense', '#84CC16', '2025-05-27 04:23:11'),
(48, 4, 'Housing', 'expense', '#F97316', '2025-05-27 04:23:11'),
(49, 4, 'Utilities', 'expense', '#6366F1', '2025-05-27 04:23:11'),
(50, 4, 'Entertainment', 'expense', '#4F46E5', '2025-05-27 04:23:11'),
(51, 4, 'Shopping', 'expense', '#10B981', '2025-05-27 04:23:11'),
(52, 4, 'Healthcare', 'expense', '#F59E0B', '2025-05-27 04:23:11'),
(53, 4, 'Education', 'expense', '#8B5CF6', '2025-05-27 04:23:11'),
(54, 4, 'Other Expenses', 'expense', '#EC4899', '2025-05-27 04:23:11'),
(55, 4, 'Test1', 'expense', '#4f46e5', '2025-05-27 04:24:57'),
(56, 5, 'Salary', 'income', '#10B981', '2025-05-27 04:25:34'),
(57, 5, 'Freelance', 'income', '#F59E0B', '2025-05-27 04:25:34'),
(58, 5, 'Investments', 'income', '#8B5CF6', '2025-05-27 04:25:34'),
(59, 5, 'Gifts', 'income', '#EC4899', '2025-05-27 04:25:34'),
(60, 5, 'Other Income', 'income', '#06B6D4', '2025-05-27 04:25:34'),
(61, 5, 'Food', 'expense', '#EF4444', '2025-05-27 04:25:34'),
(62, 5, 'Transportation', 'expense', '#84CC16', '2025-05-27 04:25:34'),
(63, 5, 'Housing', 'expense', '#F97316', '2025-05-27 04:25:34'),
(64, 5, 'Utilities', 'expense', '#6366F1', '2025-05-27 04:25:34'),
(65, 5, 'Entertainment', 'expense', '#4F46E5', '2025-05-27 04:25:34'),
(66, 5, 'Shopping', 'expense', '#10B981', '2025-05-27 04:25:34'),
(67, 5, 'Healthcare', 'expense', '#F59E0B', '2025-05-27 04:25:34'),
(68, 5, 'Education', 'expense', '#8B5CF6', '2025-05-27 04:25:34'),
(69, 5, 'Other Expenses', 'expense', '#EC4899', '2025-05-27 04:25:34'),
(87, 7, 'Salary', 'income', '#10B981', '2025-05-28 16:16:11'),
(88, 7, 'Freelance', 'income', '#F59E0B', '2025-05-28 16:16:11'),
(89, 7, 'Investments', 'income', '#8B5CF6', '2025-05-28 16:16:11'),
(90, 7, 'Gifts', 'income', '#EC4899', '2025-05-28 16:16:11'),
(91, 7, 'Food', 'expense', '#EF4444', '2025-05-28 16:16:11'),
(92, 7, 'Transportation', 'expense', '#84CC16', '2025-05-28 16:16:11'),
(93, 7, 'Housing', 'expense', '#F97316', '2025-05-28 16:16:11'),
(94, 7, 'Utilities', 'expense', '#6366F1', '2025-05-28 16:16:11'),
(95, 7, 'Entertainment', 'expense', '#4F46E5', '2025-05-28 16:16:11'),
(96, 7, 'Shopping', 'expense', '#10B981', '2025-05-28 16:16:11'),
(97, 7, 'Healthcare', 'expense', '#F59E0B', '2025-05-28 16:16:11'),
(98, 7, 'Education', 'expense', '#8B5CF6', '2025-05-28 16:16:11'),
(111, 9, 'Salary', 'income', '#10b981', '2025-05-30 11:43:40'),
(112, 9, 'Freelance', 'income', '#F59E0B', '2025-05-30 11:43:40'),
(113, 9, 'Investments', 'income', '#8B5CF6', '2025-05-30 11:43:40'),
(114, 9, 'Gifts', 'income', '#EC4899', '2025-05-30 11:43:40'),
(115, 9, 'Food', 'expense', '#EF4444', '2025-05-30 11:43:40'),
(116, 9, 'Transportation', 'expense', '#84CC16', '2025-05-30 11:43:40'),
(117, 9, 'Housing', 'expense', '#F97316', '2025-05-30 11:43:40'),
(118, 9, 'Utilities', 'expense', '#6366F1', '2025-05-30 11:43:40'),
(119, 9, 'Entertainment', 'expense', '#4F46E5', '2025-05-30 11:43:40'),
(120, 9, 'Shopping', 'expense', '#10B981', '2025-05-30 11:43:40'),
(121, 9, 'Healthcare', 'expense', '#F59E0B', '2025-05-30 11:43:40'),
(122, 9, 'Education', 'expense', '#8B5CF6', '2025-05-30 11:43:40'),
(148, 12, 'Salary', 'income', '#10B981', '2025-05-30 14:04:02'),
(149, 12, 'Freelance', 'income', '#F59E0B', '2025-05-30 14:04:02'),
(150, 12, 'Investments', 'income', '#8B5CF6', '2025-05-30 14:04:02'),
(151, 12, 'Gifts', 'income', '#EC4899', '2025-05-30 14:04:02'),
(152, 12, 'Food', 'expense', '#EF4444', '2025-05-30 14:04:02'),
(153, 12, 'Transportation', 'expense', '#84CC16', '2025-05-30 14:04:02'),
(154, 12, 'Housing', 'expense', '#F97316', '2025-05-30 14:04:02'),
(155, 12, 'Utilities', 'expense', '#6366F1', '2025-05-30 14:04:02'),
(156, 12, 'Entertainment', 'expense', '#4F46E5', '2025-05-30 14:04:02'),
(157, 12, 'Shopping', 'expense', '#10B981', '2025-05-30 14:04:02'),
(158, 12, 'Healthcare', 'expense', '#F59E0B', '2025-05-30 14:04:02'),
(159, 12, 'Education', 'expense', '#8B5CF6', '2025-05-30 14:04:02'),
(160, 12, 'New Category 1', 'expense', '#aaa8d1', '2025-05-30 14:06:13'),
(161, 12, 'New Category 3', 'income', '#82e64c', '2025-05-30 14:06:23');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_user` tinyint(1) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `user_id`, `message`, `is_user`, `timestamp`) VALUES
(70, 1, '👋 Hello! I\'m Spendora, your AI financial assistant. I\'m here to help you with:\n\n📊 Budgeting advice\n💰 Investment strategies\n💳 Debt management\n💵 Saving tips\n❓ System guidance\n\nFeel free to ask me anything about your finances or how to use Spendex!', 0, '2025-05-30 21:28:55');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `date` date NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `transaction_type` enum('income','expense') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `amount`, `description`, `date`, `category_id`, `account_id`, `user_id`, `transaction_type`, `created_at`) VALUES
(85, 1200.00, 'Light bulb', '2025-05-28', 35, 2, 1, 'income', '2025-05-28 21:16:56'),
(86, 11499.00, 'Mcdo', '2025-05-28', 32, 15, 1, 'income', '2025-05-28 21:17:52'),
(95, 123.00, 'a', '2025-05-29', 39, 2, 1, 'income', '2025-05-29 09:32:43'),
(96, 123.00, 'a', '2025-05-29', 38, 2, 1, 'income', '2025-05-29 09:32:50'),
(97, 1233.00, 'a', '2025-05-26', 36, 15, 1, 'income', '2025-05-29 09:33:36'),
(107, 1000.00, 'a', '2025-05-30', 122, 28, 9, 'income', '2025-05-30 12:34:23'),
(108, 1000.00, 'e', '2025-05-30', 122, 28, 9, 'income', '2025-05-30 12:37:30'),
(109, 123.00, 'a', '2025-05-30', 122, 26, 9, 'income', '2025-05-30 12:37:58'),
(110, 200.00, 'e', '2025-05-30', 121, 28, 9, 'income', '2025-05-30 12:43:34'),
(111, 300.00, 'a', '2025-05-30', 120, 28, 9, 'income', '2025-05-30 12:45:37'),
(112, 123.00, 'a', '2025-05-30', 116, 28, 1, 'income', '2025-05-30 12:47:44'),
(113, 123.00, 'a', '2025-05-30', 91, 18, 7, 'income', '2025-05-30 12:55:47'),
(114, 123.00, 'a', '2025-05-30', 90, 18, 7, 'income', '2025-05-30 12:55:57'),
(117, 10000.00, 'Income 1', '2025-05-30', 150, 31, 12, 'income', '2025-05-30 14:05:04'),
(118, 500.00, 'School supplies', '2025-05-30', 159, 31, 12, 'income', '2025-05-30 14:05:27'),
(119, 200.00, 'Healthcare', '2025-05-30', 158, 31, 12, 'income', '2025-05-30 14:05:50'),
(120, 1000.00, 'Jeepney', '2025-05-30', 153, 31, 12, 'income', '2025-05-30 14:07:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'PHP',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `currency`, `last_login`, `created_at`) VALUES
(1, 'Mark Erwin Solis', 'markerwintuvillasolis123@gmail.com', '$2y$10$0Nr2K4ApcW0hOs1b2uOM5O8FSBE1j/1/4k/K0S6WiKjrpgq51kaLe', 'JPY', '2025-05-30 13:28:54', '2025-05-26 23:49:03'),
(4, 'Jaspher John Samalburo', 'jaspher123@gmail.com', '$2y$10$2JKzI8/PTtuuGhH30dJd3OKfcUJ1iNDtxSG0zZbv/zZ8yBHXPOxP.', 'PHP', '2025-05-28 09:36:37', '2025-05-27 04:23:11'),
(5, 'Test Number Two', 'test2@gmail.com', '$2y$10$khnJ5R9FjqEeV1wGwiL72uKLpJV6hy/jdKFUTGOIBwHM/F1AOn4wi', 'PHP', '2025-05-28 09:37:20', '2025-05-27 04:25:34'),
(7, 'Tester Number Three', 'test3@gmail.com', '$2y$10$K/.aqQglgHGKQAHtt6uNfOlw0y2VTMKtQy6w8IJhNgE228CY4tW4i', 'PHP', '2025-05-30 12:54:07', '2025-05-28 16:16:11'),
(9, 'Test Number One', 'test1@gmail.com', '$2y$10$EhpPB14P9QPVJMvEi2w3su0cnG/DkD6MhxljmuDyMye0voESi0gBe', 'PHP', '2025-05-30 11:43:51', '2025-05-30 11:43:40'),
(12, 'New User', 'newuser@gmail.com', '$2y$10$baES.nTwrnw0uGcJXqBfCeCc5udvSIxSnVPjfdEZHy7DJ6P5gyCUa', 'KRW', '2025-05-30 14:04:09', '2025-05-30 14:04:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=162;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=121;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
