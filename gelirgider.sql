-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 26 May 2025, 15:14:35
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `gelirgider`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `level` enum('INFO','WARNING','ERROR','DEBUG','ACTIVITY') NOT NULL DEFAULT 'INFO',
  `message` text NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `level`, `message`, `context`, `ip_address`, `user_agent`, `url`, `method`, `created_at`) VALUES
(1, 1, 'ERROR', 'Veri dışa aktarma hatası: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'first_name\' in \'field list\'', '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '/gelirgider/app/controllers/SettingsController.php?action=exportData', 'GET', '2025-05-25 15:32:51');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `analytics_cache`
--

CREATE TABLE `analytics_cache` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `cache_key` varchar(255) NOT NULL,
  `cache_data` longtext NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `wallet_id` int(11) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `period` enum('daily','weekly','monthly','yearly','custom') DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `categories`
--

INSERT INTO `categories` (`id`, `user_id`, `name`, `type`, `icon`, `color`, `parent_id`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 1, 'Maaş', 'income', 'money-bill', '#28a745', NULL, 1, '2025-05-25 10:06:17', NULL),
(2, 1, 'Diğer Gelir', 'income', 'plus-circle', '#17a2b8', NULL, 1, '2025-05-25 10:06:17', NULL),
(3, 1, 'Market', 'expense', 'shopping-cart', '#dc3545', NULL, 1, '2025-05-25 10:06:17', NULL),
(4, 1, 'Faturalar', 'expense', 'file-invoice', '#ffc107', NULL, 1, '2025-05-25 10:06:17', NULL),
(5, 1, 'Ulaşım', 'expense', 'car', '#6f42c1', NULL, 1, '2025-05-25 10:06:17', NULL),
(6, 1, 'Sağlık', 'expense', 'heartbeat', '#20c997', NULL, 1, '2025-05-25 10:06:17', NULL),
(7, 1, 'Eğlence', 'expense', 'film', '#fd7e14', NULL, 1, '2025-05-25 10:06:17', NULL),
(8, 1, 'Diğer Gider', 'expense', 'ellipsis-h', '#6c757d', NULL, 1, '2025-05-25 10:06:17', NULL),
(9, 1, 'OTOMOBİL', 'expense', 'car', '#070a0d', NULL, 0, '2025-05-25 15:58:55', '2025-05-25 15:59:04'),
(10, 1, 'MAĞAZA', 'expense', 'file-invoice', '#ee00ff', NULL, 0, '2025-05-25 19:37:50', NULL),
(11, 1, 'KRİPTO', 'income', 'money-bill', '#cfd20f', NULL, 0, '2025-05-25 20:06:28', '2025-05-26 11:49:55'),
(12, 1, 'YEMEK', 'expense', 'money-bill', '#00ff4c', NULL, 0, '2025-05-26 04:16:31', NULL),
(13, 2, 'Maaş', 'income', 'money-bill', '#28a745', NULL, 1, '2025-05-26 05:06:58', NULL),
(14, 2, 'Diğer Gelir', 'income', 'plus-circle', '#17a2b8', NULL, 1, '2025-05-26 05:06:58', NULL),
(15, 2, 'Market', 'expense', 'shopping-cart', '#dc3545', NULL, 1, '2025-05-26 05:06:58', NULL),
(16, 2, 'Faturalar', 'expense', 'file-invoice', '#ffc107', NULL, 1, '2025-05-26 05:06:58', NULL),
(17, 2, 'Ulaşım', 'expense', 'car', '#6f42c1', NULL, 1, '2025-05-26 05:06:58', NULL),
(18, 2, 'Sağlık', 'expense', 'heartbeat', '#20c997', NULL, 1, '2025-05-26 05:06:58', NULL),
(19, 2, 'Eğlence', 'expense', 'film', '#fd7e14', NULL, 1, '2025-05-26 05:06:58', NULL),
(20, 2, 'Diğer Gider', 'expense', 'ellipsis-h', '#6c757d', NULL, 1, '2025-05-26 05:06:58', NULL),
(21, 1, 'SİTE', 'expense', 'home', '#74b5fb', NULL, 0, '2025-05-26 11:30:42', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `credit_cards`
--

CREATE TABLE `credit_cards` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `card_number_last4` varchar(4) DEFAULT NULL,
  `card_type` enum('visa','mastercard','amex','troy','other') DEFAULT 'visa',
  `credit_limit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `available_limit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `used_limit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'TRY',
  `statement_day` int(11) DEFAULT 1,
  `due_day` int(11) DEFAULT 15,
  `minimum_payment_rate` decimal(5,2) DEFAULT 5.00,
  `interest_rate` decimal(5,2) DEFAULT 2.50,
  `annual_fee` decimal(10,2) DEFAULT 0.00,
  `color` varchar(20) DEFAULT '#007bff',
  `icon` varchar(50) DEFAULT 'credit-card',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `credit_cards`
--

INSERT INTO `credit_cards` (`id`, `user_id`, `name`, `bank_name`, `card_number_last4`, `card_type`, `credit_limit`, `available_limit`, `used_limit`, `currency`, `statement_day`, `due_day`, `minimum_payment_rate`, `interest_rate`, `annual_fee`, `color`, `icon`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'BINANCE123', 'qwedsa', '1233', 'visa', 123123213.00, 123096040.54, 27172.46, 'TRY', 1, 15, 5.00, 2.50, 0.00, '#007bff', 'credit-card', 0, '2025-05-25 10:16:42', '2025-05-25 11:34:53'),
(2, 1, 'BINANCE', 'qwedsa', '1323', 'visa', 123123.00, 121923.00, 1200.00, 'TRY', 1, 15, 5.00, 2.50, 0.00, '#007bff', 'credit-card', 0, '2025-05-25 10:17:24', '2025-05-25 10:30:52'),
(3, 1, 'BONUS TRINK', 'GARANTİ BBA', '5155', 'mastercard', 288000.00, 286191.00, 1809.00, 'TRY', 23, 2, 5.00, 2.50, 0.00, '#338d0c', 'credit-card', 1, '2025-05-25 10:26:07', '2025-05-25 19:36:56'),
(4, 1, 'ENPARA KREDİ KARTI', 'ENPARA', '1586', 'visa', 500000.00, 450434.41, 49565.59, 'TRY', 29, 5, 5.00, 2.50, 0.00, '#a30f97', 'credit-card', 1, '2025-05-25 10:30:38', '2025-05-26 11:12:35'),
(5, 1, 'AXESS BUS TROY', 'AKBANK', '2777', 'troy', 500000.00, 494224.00, 5776.00, 'TRY', 1, 6, 5.00, 2.50, 0.00, '#d30d0d', 'credit-card', 1, '2025-05-25 10:56:09', '2025-05-25 19:31:09');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `credit_card_transactions`
--

CREATE TABLE `credit_card_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `credit_card_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `type` enum('purchase','payment','fee','interest','refund','installment') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'TRY',
  `description` text DEFAULT NULL,
  `merchant_name` varchar(255) DEFAULT NULL,
  `installment_count` int(11) DEFAULT 1,
  `installment_number` int(11) DEFAULT 1,
  `parent_transaction_id` int(11) DEFAULT NULL,
  `transaction_date` datetime NOT NULL,
  `statement_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `is_paid` tinyint(1) DEFAULT 0,
  `paid_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `payment_wallet_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `credit_card_transactions`
--

INSERT INTO `credit_card_transactions` (`id`, `user_id`, `credit_card_id`, `category_id`, `type`, `amount`, `currency`, `description`, `merchant_name`, `installment_count`, `installment_number`, `parent_transaction_id`, `transaction_date`, `statement_date`, `due_date`, `is_paid`, `paid_date`, `created_at`, `updated_at`, `payment_wallet_id`) VALUES
(43, 1, 4, 9, 'purchase', 8132.00, 'TRY', 'ARABA TRAFİK SİGORTASI', 'SAMPO SİGORTA', 2, 1, NULL, '2025-05-26 14:03:00', NULL, NULL, 0, NULL, '2025-05-25 12:04:03', '2025-05-26 11:07:41', 1),
(109, 1, 4, 9, 'installment', 4066.00, 'TRY', 'ARABA TRAFİK SİGORTASI - Taksit 1/2', 'ALLIANZ SİGORTA', 2, 1, 43, '2025-05-26 14:03:00', NULL, NULL, 0, NULL, '2025-05-25 16:20:50', '2025-05-26 11:07:41', 1),
(110, 1, 4, 9, 'installment', 4066.00, 'TRY', 'ARABA TRAFİK SİGORTASI - Taksit 2/2', 'ALLIANZ SİGORTA', 2, 2, 43, '2025-06-26 14:03:00', NULL, NULL, 0, NULL, '2025-05-25 16:20:50', '2025-05-26 11:07:41', 1),
(112, 1, 5, 10, 'purchase', 57760.00, 'TRY', 'Ayşe ve Anneme Telefon', 'MediaMart', 3, 1, NULL, '2025-05-10 21:30:00', NULL, NULL, 0, NULL, '2025-05-25 19:31:09', '2025-05-25 19:38:07', 1),
(113, 1, 5, 10, 'installment', 19253.33, 'TRY', 'Ayşe ve Anneme Telefon - Taksit 1/3', 'MediaMart', 3, 1, 112, '2025-05-10 21:30:00', NULL, NULL, 0, NULL, '2025-05-25 19:31:09', '2025-05-25 19:38:07', 1),
(114, 1, 5, 10, 'installment', 19253.33, 'TRY', 'Ayşe ve Anneme Telefon - Taksit 2/3', 'MediaMart', 3, 2, 112, '2025-06-10 21:30:00', NULL, NULL, 0, NULL, '2025-05-25 19:31:09', '2025-05-25 19:38:07', 1),
(115, 1, 5, 10, 'installment', 19253.33, 'TRY', 'Ayşe ve Anneme Telefon - Taksit 3/3', 'MediaMart', 3, 3, 112, '2025-07-10 21:30:00', NULL, NULL, 0, NULL, '2025-05-25 19:31:09', '2025-05-25 19:38:07', 1),
(116, 1, 4, 9, 'purchase', 15004.00, 'TRY', 'ARABA KASKO Yusuf abi ', 'AXA SİGORTA', 10, 1, NULL, '2025-05-26 21:32:00', NULL, NULL, 0, NULL, '2025-05-25 19:34:05', '2025-05-26 11:07:18', 1),
(117, 1, 4, 9, 'installment', 1500.40, 'TRY', 'ARABA KASKO Yusuf abi allianz - Taksit 1/10', 'ALLIANZ SİGORTA', 10, 1, 116, '2025-05-26 21:32:00', NULL, NULL, 0, NULL, '2025-05-25 19:34:05', '2025-05-26 11:07:18', 1),
(118, 1, 4, 9, 'installment', 1500.40, 'TRY', 'ARABA KASKO Yusuf abi allianz - Taksit 2/10', 'ALLIANZ SİGORTA', 10, 2, 116, '2025-06-26 21:32:00', NULL, NULL, 0, NULL, '2025-05-25 19:34:05', '2025-05-26 11:07:18', 1),
(119, 1, 4, 9, 'installment', 1500.40, 'TRY', 'ARABA KASKO Yusuf abi allianz - Taksit 3/10', 'ALLIANZ SİGORTA', 10, 3, 116, '2025-07-26 21:32:00', NULL, NULL, 0, NULL, '2025-05-25 19:34:05', '2025-05-26 11:07:18', 1),
(120, 1, 4, 9, 'installment', 1500.40, 'TRY', 'ARABA KASKO Yusuf abi allianz - Taksit 4/10', 'ALLIANZ SİGORTA', 10, 4, 116, '2025-08-26 21:32:00', NULL, NULL, 0, NULL, '2025-05-25 19:34:05', '2025-05-26 11:07:18', 1),
(121, 1, 4, 9, 'installment', 1500.40, 'TRY', 'ARABA KASKO Yusuf abi allianz - Taksit 5/10', 'ALLIANZ SİGORTA', 10, 5, 116, '2025-09-26 21:32:00', NULL, NULL, 0, NULL, '2025-05-25 19:34:05', '2025-05-26 11:07:18', 1),
(122, 1, 4, 9, 'installment', 1500.40, 'TRY', 'ARABA KASKO Yusuf abi allianz - Taksit 6/10', 'ALLIANZ SİGORTA', 10, 6, 116, '2025-10-26 21:32:00', NULL, NULL, 0, NULL, '2025-05-25 19:34:05', '2025-05-26 11:07:18', 1),
(123, 1, 4, 9, 'installment', 1500.40, 'TRY', 'ARABA KASKO Yusuf abi allianz - Taksit 7/10', 'ALLIANZ SİGORTA', 10, 7, 116, '2025-11-26 21:32:00', NULL, NULL, 0, NULL, '2025-05-25 19:34:05', '2025-05-26 11:07:18', 1),
(124, 1, 4, 9, 'installment', 1500.40, 'TRY', 'ARABA KASKO Yusuf abi allianz - Taksit 8/10', 'ALLIANZ SİGORTA', 10, 8, 116, '2025-12-26 21:32:00', NULL, NULL, 0, NULL, '2025-05-25 19:34:05', '2025-05-26 11:07:18', 1),
(125, 1, 4, 9, 'installment', 1500.40, 'TRY', 'ARABA KASKO Yusuf abi allianz - Taksit 9/10', 'ALLIANZ SİGORTA', 10, 9, 116, '2026-01-26 21:32:00', NULL, NULL, 0, NULL, '2025-05-25 19:34:05', '2025-05-26 11:07:18', 1),
(126, 1, 4, 9, 'installment', 1500.40, 'TRY', 'ARABA KASKO Yusuf abi allianz - Taksit 10/10', 'ALLIANZ SİGORTA', 10, 10, 116, '2026-02-26 21:32:00', NULL, NULL, 0, NULL, '2025-05-25 19:34:05', '2025-05-26 11:07:18', 1),
(127, 1, 3, 10, 'purchase', 1809.00, 'TRY', 'Amazon alışveriş', 'AMAZON', 1, 1, NULL, '2025-05-03 21:34:00', NULL, NULL, 0, NULL, '2025-05-25 19:36:56', '2025-05-25 19:38:57', 1),
(128, 1, 4, 12, 'purchase', 120.00, 'TRY', '4 TANE ÇAY', 'A.B.B ÖZDEMİR GIDA', 1, 1, NULL, '2025-05-25 06:14:00', NULL, NULL, 0, NULL, '2025-05-26 04:16:09', '2025-05-26 04:16:48', 1),
(129, 1, 4, 12, 'purchase', 815.00, 'TRY', 'KOKOREÇ', 'OZDEMİR KEBAPCISI', 1, 1, NULL, '2025-05-25 06:16:00', NULL, NULL, 0, NULL, '2025-05-26 04:17:50', '2025-05-26 04:17:50', 1),
(130, 1, 4, 3, 'purchase', 270.00, 'TRY', 'DAMACANA SU', 'GETİR', 1, 1, NULL, '2025-05-26 06:17:00', NULL, NULL, 0, NULL, '2025-05-26 04:18:30', '2025-05-26 04:18:30', 1),
(131, 1, 4, 3, 'purchase', 437.00, 'TRY', '', 'BİM', 1, 1, NULL, '2025-05-23 06:18:00', NULL, NULL, 0, NULL, '2025-05-26 04:19:19', '2025-05-26 04:19:19', 1),
(132, 1, 4, 10, 'purchase', 280.00, 'TRY', 'SIVA ÜSTÜ 2Lİ PRİZ', 'BAUHAUS', 1, 1, NULL, '2025-05-22 06:19:00', NULL, NULL, 0, NULL, '2025-05-26 04:20:10', '2025-05-26 04:20:10', 1),
(133, 1, 4, 3, 'purchase', 1740.00, 'TRY', 'SİGARA', 'GIMAT GROSS', 1, 1, NULL, '2025-05-22 06:20:00', NULL, NULL, 0, NULL, '2025-05-26 04:20:52', '2025-05-26 04:23:28', 1),
(134, 1, 4, 12, 'purchase', 268.00, 'TRY', 'ÇİĞ KÖFTE', 'KOMAGENE', 1, 1, NULL, '2025-05-22 06:20:00', NULL, NULL, 0, NULL, '2025-05-26 04:22:01', '2025-05-26 04:22:01', 1),
(136, 1, 4, 12, 'purchase', 3381.75, 'TRY', 'GIDA ALIŞVERİŞİ', 'GIMAT MAĞAZACILIK', 1, 1, NULL, '2025-05-22 06:22:00', NULL, NULL, 0, NULL, '2025-05-26 04:23:13', '2025-05-26 04:23:13', 1),
(137, 1, 4, 3, 'purchase', 5212.89, 'TRY', 'ALIŞVERİŞ', 'GIMSA PARK ERYAMAN', 1, 1, NULL, '2025-05-20 06:23:00', NULL, NULL, 0, NULL, '2025-05-26 04:24:47', '2025-05-26 04:24:47', 1),
(138, 1, 4, 12, 'purchase', 1440.00, 'TRY', 'GECE DÖNERCİSİ', 'USTABASI', 1, 1, NULL, '2025-05-20 06:24:00', NULL, NULL, 0, NULL, '2025-05-26 04:26:00', '2025-05-26 04:26:00', 1),
(139, 1, 4, 10, 'purchase', 2764.00, 'TRY', 'MUTFAK BANYO MALZEMESİ', 'IKEA ANKARA', 1, 1, NULL, '2025-05-20 06:26:00', NULL, NULL, 0, NULL, '2025-05-26 04:26:55', '2025-05-26 04:26:55', 1),
(140, 1, 4, 10, 'purchase', 6142.95, 'TRY', 'BANYO MUTFAK MALZEMESİ', 'IKEA ANKARA', 6, 1, NULL, '2025-05-20 06:26:00', NULL, NULL, 0, NULL, '2025-05-26 04:27:38', '2025-05-26 04:27:38', 1),
(141, 1, 4, 10, 'installment', 1023.83, 'TRY', 'BANYO MUTFAK MALZEMESİ - Taksit 1/6', 'IKEA ANKARA', 6, 1, 140, '2025-06-05 06:27:38', NULL, NULL, 0, NULL, '2025-05-26 04:27:38', '2025-05-26 04:27:38', 1),
(142, 1, 4, 10, 'installment', 1023.83, 'TRY', 'BANYO MUTFAK MALZEMESİ - Taksit 2/6', 'IKEA ANKARA', 6, 2, 140, '2025-07-05 06:27:38', NULL, NULL, 0, NULL, '2025-05-26 04:27:38', '2025-05-26 04:27:38', 1),
(143, 1, 4, 10, 'installment', 1023.83, 'TRY', 'BANYO MUTFAK MALZEMESİ - Taksit 3/6', 'IKEA ANKARA', 6, 3, 140, '2025-08-05 06:27:38', NULL, NULL, 0, NULL, '2025-05-26 04:27:39', '2025-05-26 04:27:39', 1),
(144, 1, 4, 10, 'installment', 1023.83, 'TRY', 'BANYO MUTFAK MALZEMESİ - Taksit 4/6', 'IKEA ANKARA', 6, 4, 140, '2025-09-05 06:27:38', NULL, NULL, 0, NULL, '2025-05-26 04:27:39', '2025-05-26 04:27:39', 1),
(145, 1, 4, 10, 'installment', 1023.83, 'TRY', 'BANYO MUTFAK MALZEMESİ - Taksit 5/6', 'IKEA ANKARA', 6, 5, 140, '2025-10-05 06:27:38', NULL, NULL, 0, NULL, '2025-05-26 04:27:39', '2025-05-26 04:27:39', 1),
(146, 1, 4, 10, 'installment', 1023.83, 'TRY', 'BANYO MUTFAK MALZEMESİ - Taksit 6/6', 'IKEA ANKARA', 6, 6, 140, '2025-11-05 06:27:38', NULL, NULL, 0, NULL, '2025-05-26 04:27:39', '2025-05-26 04:27:39', 1),
(147, 1, 4, 9, 'purchase', 1648.00, 'TRY', 'AKARYAKIT', 'SHELL', 1, 1, NULL, '2025-05-26 13:10:00', NULL, NULL, 0, NULL, '2025-05-26 11:11:24', '2025-05-26 11:11:24', 1),
(148, 1, 4, 6, 'purchase', 1910.00, 'TRY', 'KEDİ MUAYNE ', 'ANKARA ÜNİVERSİTESİ VETERİNERLİK FAKÜLTESİ', 1, 1, NULL, '2025-05-26 13:11:00', NULL, NULL, 0, NULL, '2025-05-26 11:12:35', '2025-05-26 11:12:35', 1);

--
-- Tetikleyiciler `credit_card_transactions`
--
DELIMITER $$
CREATE TRIGGER `update_credit_card_limit_after_transaction` AFTER INSERT ON `credit_card_transactions` FOR EACH ROW BEGIN
    DECLARE current_used_limit DECIMAL(15,2);
    
    SELECT COALESCE(SUM(
        CASE 
            WHEN type IN ('purchase', 'fee', 'interest') THEN amount
            WHEN type IN ('payment', 'refund') THEN -amount
            ELSE 0
        END
    ), 0) INTO current_used_limit
    FROM credit_card_transactions 
    WHERE credit_card_id = NEW.credit_card_id;
    
    UPDATE credit_cards 
    SET 
        used_limit = current_used_limit,
        available_limit = credit_limit - current_used_limit,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.credit_card_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `credit_card_transaction_tags`
--

CREATE TABLE `credit_card_transaction_tags` (
  `credit_card_transaction_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `credit_card_transaction_tags`
--

INSERT INTO `credit_card_transaction_tags` (`credit_card_transaction_id`, `tag_id`) VALUES
(43, 19),
(112, 20),
(116, 18),
(127, 21),
(132, 21),
(147, 22),
(148, 23);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `custom_icons`
--

CREATE TABLE `custom_icons` (
  `id` int(11) NOT NULL,
  `icon_name` varchar(50) NOT NULL,
  `icon_class` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `custom_icons`
--

INSERT INTO `custom_icons` (`id`, `icon_name`, `icon_class`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'market', 'fas fa-shopping-basket', 'Market al????veri??i', 1, '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(2, 'restaurant', 'fas fa-utensils', 'Restoran', 1, '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(3, 'transport', 'fas fa-bus', 'Ula????m', 1, '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(4, 'entertainment', 'fas fa-gamepad', 'E??lence', 1, '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(5, 'health', 'fas fa-heartbeat', 'Sa??l??k', 1, '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(6, 'education', 'fas fa-graduation-cap', 'E??itim', 1, '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(7, 'clothing', 'fas fa-tshirt', 'Giyim', 1, '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(8, 'electronics', 'fas fa-laptop', 'Elektronik', 1, '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(9, 'beauty', 'fas fa-cut', 'G??zellik', 1, '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(10, 'sports', 'fas fa-dumbbell', 'Spor', 1, '2025-05-26 10:54:15', '2025-05-26 10:54:15');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `exchange_rates`
--

CREATE TABLE `exchange_rates` (
  `id` int(11) NOT NULL,
  `from_currency` varchar(3) NOT NULL,
  `to_currency` varchar(3) NOT NULL,
  `rate` decimal(15,8) NOT NULL,
  `source` varchar(50) DEFAULT 'manual',
  `date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `exchange_rates`
--

INSERT INTO `exchange_rates` (`id`, `from_currency`, `to_currency`, `rate`, `source`, `date`, `created_at`) VALUES
(1, 'USD', 'TRY', 32.50000000, 'manual', '2025-05-25', '2025-05-25 10:06:17'),
(2, 'EUR', 'TRY', 35.20000000, 'manual', '2025-05-25', '2025-05-25 10:06:17'),
(3, 'GBP', 'TRY', 41.10000000, 'manual', '2025-05-25', '2025-05-25 10:06:17'),
(4, 'TRY', 'USD', 0.03100000, 'manual', '2025-05-25', '2025-05-25 10:06:17'),
(5, 'TRY', 'EUR', 0.02800000, 'manual', '2025-05-25', '2025-05-25 10:06:17'),
(6, 'TRY', 'GBP', 0.02400000, 'manual', '2025-05-25', '2025-05-25 10:06:17');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `financial_goals`
--

CREATE TABLE `financial_goals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `wallet_id` int(11) DEFAULT NULL,
  `target_amount` decimal(18,2) NOT NULL,
  `current_amount` decimal(18,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'TRY',
  `target_date` date DEFAULT NULL,
  `status` enum('planned','in_progress','completed','cancelled') DEFAULT 'planned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `installment_plans`
--

CREATE TABLE `installment_plans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `credit_card_id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `installment_count` int(11) NOT NULL,
  `installment_amount` decimal(15,2) NOT NULL,
  `paid_installments` int(11) DEFAULT 0,
  `remaining_amount` decimal(15,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `installment_plans`
--

INSERT INTO `installment_plans` (`id`, `user_id`, `credit_card_id`, `transaction_id`, `total_amount`, `installment_count`, `installment_amount`, `paid_installments`, `remaining_amount`, `start_date`, `end_date`, `description`, `is_completed`, `created_at`, `updated_at`) VALUES
(6, 1, 4, 43, 8150.00, 2, 4075.00, 0, 8150.00, '2025-05-25', '2025-06-25', 'ARABA TRAFİK SİGORTASI', 0, '2025-05-25 12:04:03', '2025-05-25 12:04:03');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payment_plans`
--

CREATE TABLE `payment_plans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'TRY',
  `category_id` int(11) DEFAULT NULL,
  `plan_type` enum('installment','milestone','mixed','custom') NOT NULL DEFAULT 'installment',
  `payment_method` enum('cash','credit_card','bank_transfer','mixed') NOT NULL DEFAULT 'cash',
  `status` enum('pending','active','completed','cancelled','overdue') NOT NULL DEFAULT 'pending',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `payment_plans`
--

INSERT INTO `payment_plans` (`id`, `user_id`, `title`, `description`, `total_amount`, `paid_amount`, `remaining_amount`, `currency`, `category_id`, `plan_type`, `payment_method`, `status`, `start_date`, `end_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'Ürün Alımı - 60.000 TL', 'Ürün alımı, 10 TL kapora verildi, kalan kredi kartına taksit', 60000.00, 10.00, 59990.00, 'TRY', 1, 'mixed', 'mixed', 'cancelled', '2025-01-15', NULL, 'Kapora nakit, kalan kredi kartı taksiti', '2025-05-25 19:48:27', '2025-05-25 21:02:42'),
(2, 1, 'Sineklik - 13.000 TL', 'Sineklik alımı, 3.000 TL kapora, kalan takıldığında ödenecek', 13000.00, 3000.00, 10000.00, 'TRY', 1, 'milestone', 'cash', 'cancelled', '2025-01-10', NULL, 'Takıldığında kalan ödeme yapılacak', '2025-05-25 19:48:27', '2025-05-25 20:14:48'),
(3, 1, 'Ev Alımı - 800.000 TL', 'Ev alımı, 6 taksit nakit ödeme, ilk 4 ay 100k, son 2 ay 150k', 800000.00, 0.00, 800000.00, 'TRY', 1, 'custom', 'cash', 'cancelled', '2025-02-01', NULL, 'Senet ile yapıldı', '2025-05-25 19:48:27', '2025-05-25 19:57:24'),
(4, 1, 'Kapı - 60.000 TL', 'Kapı alımı, 30k ödendi, 15k takılınca, 15k temmuzda', 60000.00, 30000.00, 30000.00, 'TRY', 1, 'milestone', 'mixed', 'cancelled', '2025-01-20', NULL, 'Karma ödeme planı', '2025-05-25 19:48:27', '2025-05-25 21:24:35'),
(5, 1, 'EV ALIMI', '6 AYLIK TAKSİT VAR. İLK 4 AY 100BİN TL SON 2 AY 150BİN TL', 800000.00, 0.00, 800000.00, 'TRY', 8, '', 'cash', 'cancelled', '2025-06-05', '2025-11-05', 'ÖDEMELER SENET OLARAK YAPILMIŞTIR. HER ÖDEMEDEN SONRA SENETLER YIRTILACAK.', '2025-05-25 19:57:12', '2025-05-25 21:02:56'),
(6, 1, 'Ürün Alımı - 60.000 TL', 'Ürün alımı, 10 TL kapora verildi, kalan kredi kartına taksit', 60001.00, 10.00, 59990.00, 'TRY', 1, 'mixed', 'cash', 'cancelled', '2025-01-15', NULL, 'Kapora nakit, kalan kredi kartı taksiti', '2025-05-25 20:07:22', '2025-05-25 21:23:05'),
(7, 1, 'Sineklik - 13.000 TL', 'Sineklik alımı, 3.000 TL kapora, kalan takıldığında ödenecek', 13000.00, 3000.00, 10000.00, 'TRY', 1, 'milestone', 'cash', 'cancelled', '2025-01-10', NULL, 'Takıldığında kalan ödeme yapılacak', '2025-05-25 20:07:22', '2025-05-25 20:27:08'),
(8, 1, 'Ev Alımı - 800.000 TL', 'Ev alımı, 6 taksit nakit ödeme, ilk 4 ay 100k, son 2 ay 150k', 800000.00, 0.00, 800000.00, 'TRY', 1, 'custom', 'cash', 'cancelled', '2025-02-01', NULL, 'Senet ile yapıldı', '2025-05-25 20:07:22', '2025-05-25 21:20:22'),
(9, 1, 'Kapı - 60.000 TL', 'Kapı alımı, 30k ödendi, 15k takılınca, 15k temmuzda', 60000.00, 30000.00, 30000.00, 'TRY', 1, 'milestone', 'mixed', 'cancelled', '2025-01-20', NULL, 'Karma ödeme planı', '2025-05-25 20:07:22', '2025-05-25 21:24:31'),
(10, 1, 'mahmut abi', 'ev taksiti', 800000.00, 0.00, 800000.00, 'TRY', 8, 'installment', 'cash', 'cancelled', '2025-06-06', '2025-11-06', '', '2025-05-25 21:22:40', '2025-05-25 21:24:25'),
(11, 1, '123123', '', 123123123.00, 0.00, 123123123.00, 'TRY', 8, 'installment', 'cash', 'cancelled', '2025-05-25', '2025-05-31', '', '2025-05-25 21:25:24', '2025-05-25 21:25:57'),
(12, 1, 'EV ÖDEMESİ', 'MAHMUT ABİYE SENETLİ 6 AYLIK VADELİ ÖDEME', 800000.00, 0.00, 800000.00, 'TRY', 8, 'installment', 'cash', 'cancelled', '2025-05-25', '2025-11-06', '', '2025-05-25 21:29:43', '2025-05-25 21:47:44'),
(13, 1, 'EV TAKSİTLERİ (SENET)', 'MAHMUT ABİYE ELDEN VADELİ EV TAKSİTİ ÖDEME İLK 4 AY 100BİN SON 2 AY 150BİN', 800000.00, 0.00, 800000.00, 'TRY', 8, '', 'cash', 'cancelled', '2025-06-06', '2025-12-06', '', '2025-05-25 21:46:49', '2025-05-25 21:46:57'),
(14, 1, 'EV TAKSİTİ (SENET)', 'MAHMUT ABİYE 7 AYLIK SENET ÖDEMESİ', 800000.00, 0.00, 800000.00, 'TRY', 8, 'installment', 'cash', 'active', '2025-06-06', '2025-12-06', '', '2025-05-25 21:49:15', '2025-05-25 21:51:52'),
(15, 1, 'BAHADIRA BORÇ', 'BAHADIRA ÖDEME', 600000.00, 0.00, 600000.00, 'TRY', 8, 'installment', 'cash', 'active', '2025-06-10', '2025-09-10', 'ÖDEMELER ESNEK', '2025-05-25 21:50:39', '2025-05-25 21:51:59'),
(16, 1, 'JALUZİ PERDE', '40 TLSİ NAKİT OLARAK ÖDENDİ. KALAN 6 HAZİRANDAN SONRA ÇEKİLECEK.', 45000.00, 0.00, 45000.00, 'TRY', 8, 'custom', 'credit_card', 'pending', '2025-06-06', NULL, '', '2025-05-26 11:19:13', NULL),
(17, 1, 'DİĞER PERDELER', 'KALAN PERDELER İÇİN 50000 TL ÖDEME VAR. 10000 TLSİ ELDEN ÖDENDİ.', 50000.00, 0.00, 50000.00, 'TRY', 8, 'custom', 'credit_card', 'pending', '2025-06-06', NULL, '', '2025-05-26 11:20:17', NULL),
(18, 1, 'SALON KAPISI', 'SALON KAPISI 60000 TL. 30BİN TL ÖDENDİ. KAPI TAKILINCA 15BİN. TEMMUZ AYINDA DA DİĞER KALAN 15BİN TL.', 30000.00, 0.00, 30000.00, 'TRY', 8, 'installment', 'cash', 'pending', '2025-06-10', '2025-07-10', '', '2025-05-26 11:26:23', NULL),
(19, 1, 'SİNEKLİK', '13000 TL SİNEKLİK. 3BİN ÖDENDİ. 10BİN TL SİNEKLİKLER TAKILINCA.', 10000.00, 0.00, 10000.00, 'TRY', 8, 'custom', 'cash', 'pending', '2025-05-26', '2025-06-28', '', '2025-05-26 11:28:04', NULL),
(20, 1, 'MUTFAK HALISI', '14BİN TL MUTFAK HALISI. 4BİN TL ÖDENDİ. 10000TL KREDİ KARTINA BÖLÜNECEK.', 10000.00, 0.00, 10000.00, 'TRY', 8, '', 'credit_card', 'pending', '2025-06-06', NULL, '', '2025-05-26 11:29:40', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payment_plan_history`
--

CREATE TABLE `payment_plan_history` (
  `id` int(11) NOT NULL,
  `payment_plan_id` int(11) NOT NULL,
  `payment_plan_item_id` int(11) DEFAULT NULL,
  `action` enum('created','updated','payment_made','payment_cancelled','status_changed','completed') NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `payment_plan_history`
--

INSERT INTO `payment_plan_history` (`id`, `payment_plan_id`, `payment_plan_item_id`, `action`, `old_value`, `new_value`, `amount`, `notes`, `created_by`, `created_at`) VALUES
(1, 5, NULL, 'created', NULL, '{\"user_id\":\"1\",\"title\":\"EV ALIMI\",\"description\":\"6 AYLIK TAKS\\u0130T VAR. \\u0130LK 4 AY 100B\\u0130N TL SON 2 AY 150B\\u0130N TL\",\"total_amount\":800000,\"category_id\":\"8\",\"plan_type\":\"mixed\",\"payment_method\":\"cash\",\"start_date\":\"2025-06-05\",\"end_date\":\"2025-11-05\",\"notes\":\"\\u00d6DEMELER SENET OLARAK YAPILMI\\u015eTIR. HER \\u00d6DEMEDEN SONRA SENETLER YIRTILACAK.\"}', NULL, 'Ödeme planı oluşturuldu', 1, '2025-05-25 19:57:12'),
(2, 3, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 19:57:24'),
(3, 1, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 19:59:10'),
(4, 1, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 19:59:12'),
(5, 1, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 19:59:16'),
(6, 1, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 19:59:22'),
(7, 5, NULL, 'updated', NULL, '{\"title\":\"EV ALIMI\",\"description\":\"6 AYLIK TAKS\\u0130T VAR. \\u0130LK 4 AY 100B\\u0130N TL SON 2 AY 150B\\u0130N TL\",\"total_amount\":800000,\"category_id\":\"8\",\"plan_type\":\"cash_installment\",\"payment_method\":\"cash\",\"start_date\":\"2025-06-05\",\"end_date\":\"2025-11-05\",\"notes\":\"\\u00d6DEMELER SENET OLARAK YAPILMI\\u015eTIR. HER \\u00d6DEMEDEN SONRA SENETLER YIRTILACAK.\",\"status\":\"active\"}', NULL, 'Ödeme planı güncellendi', 1, '2025-05-25 20:10:38'),
(8, 6, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:14:41'),
(9, 6, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:14:43'),
(10, 7, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:14:46'),
(11, 2, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:14:48'),
(12, 7, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:14:50'),
(13, 6, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:14:52'),
(14, 6, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:14:55'),
(15, 6, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:26:56'),
(16, 6, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:26:59'),
(17, 6, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:27:02'),
(18, 1, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:27:05'),
(19, 7, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:27:08'),
(20, 6, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:27:13'),
(21, 6, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:33:26'),
(22, 5, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:33:35'),
(23, 5, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:35:16'),
(24, 6, NULL, 'updated', NULL, '{\"title\":\"\\u00dcr\\u00fcn Al\\u0131m\\u0131 - 60.000 TL\",\"description\":\"\\u00dcr\\u00fcn al\\u0131m\\u0131, 10 TL kapora verildi, kalan kredi kart\\u0131na taksit\",\"total_amount\":60001,\"category_id\":\"1\",\"plan_type\":\"mixed\",\"payment_method\":\"cash\",\"start_date\":\"2025-01-15\",\"end_date\":null,\"notes\":\"Kapora nakit, kalan kredi kart\\u0131 taksiti\",\"status\":\"cancelled\"}', NULL, 'Ödeme planı güncellendi', 1, '2025-05-25 20:35:31'),
(25, 6, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:51:03'),
(26, 1, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:53:30'),
(27, 6, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:56:41'),
(28, 5, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:58:06'),
(29, 5, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 20:58:17'),
(30, 6, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:02:37'),
(31, 1, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:02:42'),
(32, 5, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:02:48'),
(33, 5, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:02:56'),
(34, 6, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:06:00'),
(35, 8, NULL, 'status_changed', 'pending', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:06:19'),
(36, 6, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:08:06'),
(37, 6, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:09:30'),
(38, 6, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:19:37'),
(39, 8, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:20:22'),
(40, 6, NULL, 'status_changed', 'cancelled', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:23:05'),
(41, 10, NULL, 'status_changed', 'pending', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:24:25'),
(42, 9, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:24:31'),
(43, 4, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:24:35'),
(44, 11, NULL, 'created', NULL, '{\"user_id\":\"1\",\"title\":\"123123\",\"description\":\"\",\"total_amount\":123123123,\"category_id\":\"8\",\"plan_type\":\"installment\",\"payment_method\":\"cash\",\"start_date\":\"2025-05-25\",\"end_date\":\"2025-05-31\",\"notes\":\"\"}', NULL, 'Ödeme planı oluşturuldu', 1, '2025-05-25 21:25:24'),
(45, 11, NULL, 'status_changed', 'pending', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:25:57'),
(46, 12, NULL, 'created', NULL, '{\"user_id\":\"1\",\"title\":\"EV \\u00d6DEMES\\u0130\",\"description\":\"MAHMUT AB\\u0130YE SENETL\\u0130 6 AYLIK VADEL\\u0130 \\u00d6DEME\",\"total_amount\":800000,\"category_id\":\"8\",\"plan_type\":\"installment\",\"payment_method\":\"cash\",\"start_date\":\"2025-05-25\",\"end_date\":\"2025-11-06\",\"notes\":\"\"}', NULL, 'Ödeme planı oluşturuldu', 1, '2025-05-25 21:29:43'),
(47, 12, NULL, 'updated', NULL, '{\"title\":\"EV \\u00d6DEMES\\u0130\",\"description\":\"MAHMUT AB\\u0130YE SENETL\\u0130 6 AYLIK VADEL\\u0130 \\u00d6DEME\",\"total_amount\":800000,\"category_id\":\"8\",\"plan_type\":\"cash_installment\",\"payment_method\":\"cash\",\"start_date\":\"2025-05-25\",\"end_date\":\"2025-11-06\",\"notes\":\"\",\"status\":\"pending\"}', NULL, 'Ödeme planı güncellendi', 1, '2025-05-25 21:34:43'),
(48, 12, NULL, 'updated', NULL, '{\"title\":\"EV \\u00d6DEMES\\u0130\",\"description\":\"MAHMUT AB\\u0130YE SENETL\\u0130 6 AYLIK VADEL\\u0130 \\u00d6DEME\",\"total_amount\":800000,\"category_id\":\"8\",\"plan_type\":\"installment\",\"payment_method\":\"cash\",\"start_date\":\"2025-05-25\",\"end_date\":\"2025-11-06\",\"notes\":\"\",\"status\":\"active\"}', NULL, 'Ödeme planı güncellendi', 1, '2025-05-25 21:34:48'),
(49, 12, 27, '', NULL, '{\"payment_plan_id\":\"12\",\"item_order\":1,\"title\":\"1. taksit\",\"description\":\"\",\"amount\":100000,\"due_date\":\"2025-06-06\",\"payment_method\":\"cash\",\"wallet_id\":null,\"credit_card_id\":null,\"installment_count\":1,\"notes\":\"\"}', 100000.00, 'Ödeme detayı eklendi', 1, '2025-05-25 21:41:41'),
(50, 13, NULL, 'created', NULL, '{\"user_id\":\"1\",\"title\":\"EV TAKS\\u0130TLER\\u0130 (SENET)\",\"description\":\"MAHMUT AB\\u0130YE ELDEN VADEL\\u0130 EV TAKS\\u0130T\\u0130 \\u00d6DEME \\u0130LK 4 AY 100B\\u0130N SON 2 AY 150B\\u0130N\",\"total_amount\":800000,\"category_id\":\"8\",\"plan_type\":\"cash_installment\",\"payment_method\":\"cash\",\"start_date\":\"2025-06-06\",\"end_date\":\"2025-12-06\",\"notes\":\"\"}', NULL, 'Ödeme planı oluşturuldu', 1, '2025-05-25 21:46:49'),
(51, 13, NULL, 'status_changed', 'pending', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:46:57'),
(52, 12, NULL, 'status_changed', 'active', 'cancelled', NULL, 'Ödeme planı iptal edildi', 1, '2025-05-25 21:47:44'),
(53, 14, NULL, 'created', NULL, '{\"user_id\":\"1\",\"title\":\"EV TAKS\\u0130T\\u0130 (SENET)\",\"description\":\"MAHMUT AB\\u0130YE 7 AYLIK SENET \\u00d6DEMES\\u0130\",\"total_amount\":800000,\"category_id\":\"8\",\"plan_type\":\"cash_installment\",\"payment_method\":\"cash\",\"start_date\":\"2025-06-06\",\"end_date\":\"2025-12-06\",\"notes\":\"\"}', NULL, 'Ödeme planı oluşturuldu', 1, '2025-05-25 21:49:15'),
(54, 15, NULL, 'created', NULL, '{\"user_id\":\"1\",\"title\":\"BAHADIRA BOR\\u00c7\",\"description\":\"BAHADIRA \\u00d6DEME\",\"total_amount\":600000,\"category_id\":\"8\",\"plan_type\":\"cash_installment\",\"payment_method\":\"cash\",\"start_date\":\"2025-06-10\",\"end_date\":\"2025-09-10\",\"notes\":\"\\u00d6DEMELER ESNEK\"}', NULL, 'Ödeme planı oluşturuldu', 1, '2025-05-25 21:50:39'),
(55, 14, NULL, 'updated', NULL, '{\"title\":\"EV TAKS\\u0130T\\u0130 (SENET)\",\"description\":\"MAHMUT AB\\u0130YE 7 AYLIK SENET \\u00d6DEMES\\u0130\",\"total_amount\":800000,\"category_id\":\"8\",\"plan_type\":\"installment\",\"payment_method\":\"cash\",\"start_date\":\"2025-06-06\",\"end_date\":\"2025-12-06\",\"notes\":\"\",\"status\":\"active\"}', NULL, 'Ödeme planı güncellendi', 1, '2025-05-25 21:51:52'),
(56, 15, NULL, 'updated', NULL, '{\"title\":\"BAHADIRA BOR\\u00c7\",\"description\":\"BAHADIRA \\u00d6DEME\",\"total_amount\":600000,\"category_id\":\"8\",\"plan_type\":\"installment\",\"payment_method\":\"cash\",\"start_date\":\"2025-06-10\",\"end_date\":\"2025-09-10\",\"notes\":\"\\u00d6DEMELER ESNEK\",\"status\":\"active\"}', NULL, 'Ödeme planı güncellendi', 1, '2025-05-25 21:51:59'),
(57, 16, NULL, 'created', NULL, '{\"user_id\":\"1\",\"title\":\"JALUZ\\u0130 PERDE\",\"description\":\"40 TLS\\u0130 NAK\\u0130T OLARAK \\u00d6DEND\\u0130. KALAN 6 HAZ\\u0130RANDAN SONRA \\u00c7EK\\u0130LECEK.\",\"total_amount\":45000,\"category_id\":\"8\",\"plan_type\":\"custom\",\"payment_method\":\"credit_card\",\"start_date\":\"2025-06-06\",\"end_date\":null,\"notes\":\"\"}', NULL, 'Ödeme planı oluşturuldu', 1, '2025-05-26 11:19:13'),
(58, 17, NULL, 'created', NULL, '{\"user_id\":\"1\",\"title\":\"D\\u0130\\u011eER PERDELER\",\"description\":\"KALAN PERDELER \\u0130\\u00c7\\u0130N 50000 TL \\u00d6DEME VAR. 10000 TLS\\u0130 ELDEN \\u00d6DEND\\u0130.\",\"total_amount\":50000,\"category_id\":\"8\",\"plan_type\":\"custom\",\"payment_method\":\"credit_card\",\"start_date\":\"2025-06-06\",\"end_date\":null,\"notes\":\"\"}', NULL, 'Ödeme planı oluşturuldu', 1, '2025-05-26 11:20:17'),
(59, 18, NULL, 'created', NULL, '{\"user_id\":\"1\",\"title\":\"SALON KAPISI\",\"description\":\"SALON KAPISI 60000 TL. 30B\\u0130N TL \\u00d6DEND\\u0130. KAPI TAKILINCA 15B\\u0130N. TEMMUZ AYINDA DA D\\u0130\\u011eER KALAN 15B\\u0130N TL.\",\"total_amount\":30000,\"category_id\":\"8\",\"plan_type\":\"installment\",\"payment_method\":\"cash\",\"start_date\":\"2025-06-10\",\"end_date\":\"2025-07-10\",\"notes\":\"\"}', NULL, 'Ödeme planı oluşturuldu', 1, '2025-05-26 11:26:23'),
(60, 19, NULL, 'created', NULL, '{\"user_id\":\"1\",\"title\":\"S\\u0130NEKL\\u0130K\",\"description\":\"13000 TL S\\u0130NEKL\\u0130K. 3B\\u0130N \\u00d6DEND\\u0130. 10B\\u0130N TL S\\u0130NEKL\\u0130KLER TAKILINCA.\",\"total_amount\":10000,\"category_id\":\"8\",\"plan_type\":\"custom\",\"payment_method\":\"cash\",\"start_date\":\"2025-05-26\",\"end_date\":\"2025-06-28\",\"notes\":\"\"}', NULL, 'Ödeme planı oluşturuldu', 1, '2025-05-26 11:28:04'),
(61, 20, NULL, 'created', NULL, '{\"user_id\":\"1\",\"title\":\"MUTFAK HALISI\",\"description\":\"14B\\u0130N TL MUTFAK HALISI. 4B\\u0130N TL \\u00d6DEND\\u0130. 10000TL KRED\\u0130 KARTINA B\\u00d6L\\u00dcNECEK.\",\"total_amount\":10000,\"category_id\":\"8\",\"plan_type\":\"cash_installment\",\"payment_method\":\"credit_card\",\"start_date\":\"2025-06-06\",\"end_date\":null,\"notes\":\"\"}', NULL, 'Ödeme planı oluşturuldu', 1, '2025-05-26 11:29:40');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `payment_plan_items`
--

CREATE TABLE `payment_plan_items` (
  `id` int(11) NOT NULL,
  `payment_plan_id` int(11) NOT NULL,
  `item_order` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `due_date` date NOT NULL,
  `payment_method` enum('cash','credit_card','bank_transfer') NOT NULL DEFAULT 'cash',
  `wallet_id` int(11) DEFAULT NULL,
  `credit_card_id` int(11) DEFAULT NULL,
  `installment_count` int(11) DEFAULT 1,
  `status` enum('pending','paid','overdue','cancelled') NOT NULL DEFAULT 'pending',
  `paid_date` date DEFAULT NULL,
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `transaction_id` int(11) DEFAULT NULL,
  `credit_card_transaction_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `payment_plan_items`
--

INSERT INTO `payment_plan_items` (`id`, `payment_plan_id`, `item_order`, `title`, `description`, `amount`, `due_date`, `payment_method`, `wallet_id`, `credit_card_id`, `installment_count`, `status`, `paid_date`, `paid_amount`, `transaction_id`, `credit_card_transaction_id`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Kapora', NULL, 10.00, '2025-01-15', 'cash', NULL, NULL, 1, 'paid', '2025-01-15', 10.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(2, 1, 2, 'Kredi Kartı Taksiti', NULL, 59990.00, '2025-06-06', 'credit_card', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(3, 2, 1, 'Kapora', NULL, 3000.00, '2025-01-10', 'cash', NULL, NULL, 1, 'paid', '2025-01-10', 3000.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(4, 2, 2, 'Takılınca Ödeme', NULL, 10000.00, '2025-03-15', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(5, 3, 1, '1. Taksit', NULL, 100000.00, '2025-02-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(6, 3, 2, '2. Taksit', NULL, 100000.00, '2025-03-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(7, 3, 3, '3. Taksit', NULL, 100000.00, '2025-04-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(8, 3, 4, '4. Taksit', NULL, 100000.00, '2025-05-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(9, 3, 5, '5. Taksit', NULL, 150000.00, '2025-06-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(10, 3, 6, '6. Taksit', NULL, 150000.00, '2025-07-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(11, 4, 1, 'İlk Ödeme', NULL, 30000.00, '2025-01-20', 'cash', NULL, NULL, 1, 'paid', '2025-01-20', 30000.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(12, 4, 2, 'Takılınca Ödeme', NULL, 15000.00, '2025-03-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(13, 4, 3, 'Son Ödeme', NULL, 15000.00, '2025-07-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 19:48:27', NULL),
(14, 1, 1, 'Kapora', NULL, 10.00, '2025-01-15', 'cash', NULL, NULL, 1, 'paid', '2025-01-15', 10.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(15, 1, 2, 'Kredi Kartı Taksiti', NULL, 59990.00, '2025-06-06', 'credit_card', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(16, 2, 1, 'Kapora', NULL, 3000.00, '2025-01-10', 'cash', NULL, NULL, 1, 'paid', '2025-01-10', 3000.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(17, 2, 2, 'Takılınca Ödeme', NULL, 10000.00, '2025-03-15', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(18, 3, 1, '1. Taksit', NULL, 100000.00, '2025-02-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(19, 3, 2, '2. Taksit', NULL, 100000.00, '2025-03-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(20, 3, 3, '3. Taksit', NULL, 100000.00, '2025-04-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(21, 3, 4, '4. Taksit', NULL, 100000.00, '2025-05-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(22, 3, 5, '5. Taksit', NULL, 150000.00, '2025-06-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(23, 3, 6, '6. Taksit', NULL, 150000.00, '2025-07-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(24, 4, 1, 'İlk Ödeme', NULL, 30000.00, '2025-01-20', 'cash', NULL, NULL, 1, 'paid', '2025-01-20', 30000.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(25, 4, 2, 'Takılınca Ödeme', NULL, 15000.00, '2025-03-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(26, 4, 3, 'Son Ödeme', NULL, 15000.00, '2025-07-01', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, NULL, '2025-05-25 20:07:22', NULL),
(27, 12, 1, '1. taksit', '', 100000.00, '2025-06-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:41:41', NULL),
(28, 13, 1, '1. Nakit Taksit', '', 100000.00, '2025-06-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:46:49', NULL),
(29, 13, 2, '2. Nakit Taksit', '', 100000.00, '2025-07-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:46:49', NULL),
(30, 13, 3, '3. Nakit Taksit', '', 100000.00, '2025-08-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:46:49', NULL),
(31, 13, 4, '4. Nakit Taksit', '', 100000.00, '2025-09-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:46:49', NULL),
(32, 13, 5, '5. Nakit Taksit', '', 100000.00, '2025-10-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:46:49', NULL),
(33, 13, 6, '6. Nakit Taksit', '', 150000.00, '2025-11-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:46:49', NULL),
(34, 13, 7, '7. Nakit Taksit', '', 150000.00, '2025-12-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:46:49', NULL),
(35, 14, 1, '1. Nakit Taksit', '', 100000.00, '2025-06-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:49:15', NULL),
(36, 14, 2, '2. Nakit Taksit', '', 100000.00, '2025-07-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:49:15', NULL),
(37, 14, 3, '3. Nakit Taksit', '', 100000.00, '2025-08-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:49:15', NULL),
(38, 14, 4, '4. Nakit Taksit', '', 100000.00, '2025-09-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:49:15', NULL),
(39, 14, 5, '5. Nakit Taksit', '', 100000.00, '2025-10-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:49:15', NULL),
(40, 14, 6, '6. Nakit Taksit', '', 150000.00, '2025-11-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:49:15', NULL),
(41, 14, 7, '7. Nakit Taksit', '', 150000.00, '2025-12-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:49:15', NULL),
(42, 15, 1, '1. Nakit Taksit', '', 200000.00, '2025-06-10', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:50:39', NULL),
(43, 15, 2, '2. Nakit Taksit', '', 200000.00, '2025-07-10', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:50:39', NULL),
(44, 15, 3, '3. Nakit Taksit', '', 200000.00, '2025-08-10', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-25 21:50:39', NULL),
(45, 18, 1, '1.TAKSİT', '', 15000.00, '2025-06-10', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-26 11:26:23', NULL),
(46, 18, 2, '2.TAKSİT', '', 15000.00, '2025-07-10', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-26 11:26:23', NULL),
(47, 20, 1, '1. Nakit Taksit', '', 2000.00, '2025-06-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-26 11:29:40', NULL),
(48, 20, 2, '2. Nakit Taksit', '', 2000.00, '2025-07-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-26 11:29:40', NULL),
(49, 20, 3, '3. Nakit Taksit', '', 2000.00, '2025-08-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-26 11:29:40', NULL),
(50, 20, 4, '4. Nakit Taksit', '', 2000.00, '2025-09-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-26 11:29:40', NULL),
(51, 20, 5, '5. Nakit Taksit', '', 2000.00, '2025-10-06', 'cash', NULL, NULL, 1, 'pending', NULL, 0.00, NULL, NULL, '', '2025-05-26 11:29:40', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `scheduled_payments`
--

CREATE TABLE `scheduled_payments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `wallet_id` int(11) DEFAULT NULL,
  `credit_card_id` int(11) DEFAULT NULL,
  `frequency` enum('daily','weekly','monthly','yearly') NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_payment_date` date NOT NULL,
  `last_payment_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `auto_pay` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Gelir Gider Takip Sistemi', 'Site adı', '2025-05-26 10:54:15', '2025-05-26 10:59:51'),
(2, 'site_description', 'Kişisel finans yönetim sistemi', 'Site açıklaması', '2025-05-26 10:54:15', '2025-05-26 10:59:51'),
(3, 'site_logo', '', 'Site logosu URL', '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(4, 'maintenance_mode', '0', 'Bakım modu (0: Kapalı, 1: Açık)', '2025-05-26 10:54:15', '2025-05-26 10:59:51'),
(5, 'user_registration', '1', 'Kullanıcı kaydına izin ver (0: Kapalı, 1: Açık)', '2025-05-26 10:54:15', '2025-05-26 10:59:51'),
(6, 'max_wallets_per_user', '10', 'Kullanıcı başına maksimum cüzdan sayısı', '2025-05-26 10:54:15', '2025-05-26 10:59:51'),
(7, 'max_categories_per_user', '50', 'Kullan??c?? ba????na maksimum kategori say??s??', '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(8, 'default_currency', 'TRY', 'Varsayılan para birimi', '2025-05-26 10:54:15', '2025-05-26 10:59:51'),
(9, 'date_format', 'd.m.Y', 'Tarih formatı', '2025-05-26 10:54:15', '2025-05-26 10:59:51'),
(10, 'timezone', 'Europe/Istanbul', 'Zaman dilimi', '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(11, 'email_notifications', '1', 'E-posta bildirimleri (0: Kapal??, 1: A????k)', '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(12, 'backup_retention_days', '30', 'Yedek dosya saklama s??resi (g??n)', '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(13, 'session_timeout', '1440', 'Oturum zaman aşımı (dakika)', '2025-05-26 10:54:15', '2025-05-26 10:59:51'),
(14, 'max_file_size', '5242880', 'Maksimum dosya boyutu (byte)', '2025-05-26 10:54:15', '2025-05-26 10:54:15'),
(15, 'allowed_file_types', 'jpg,jpeg,png,gif,pdf,xlsx,csv', '??zin verilen dosya t??rleri', '2025-05-26 10:54:15', '2025-05-26 10:54:15');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `color` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `tags`
--

INSERT INTO `tags` (`id`, `user_id`, `name`, `color`, `created_at`) VALUES
(18, 1, 'KASKO', '#ff00ea', '2025-05-25 17:17:54'),
(19, 1, 'TRAFİK SİGORTASI', '#0ab83e', '2025-05-25 17:19:40'),
(20, 1, 'TELEFON', '#d4810c', '2025-05-25 17:20:45'),
(21, 1, 'ELEKTRONİK', '#7b430e', '2025-05-25 19:38:30'),
(22, 1, 'AKARYAKIT', '#6f42c1', '2025-05-26 11:09:11'),
(23, 1, 'KEDİ', '#4b6f95', '2025-05-26 11:11:48'),
(24, 1, 'AİDAT', '#e97782', '2025-05-26 11:31:05');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `type` enum('income','expense','transfer') NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'TRY',
  `description` varchar(255) DEFAULT NULL,
  `transaction_date` datetime NOT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `photo` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `wallet_id`, `category_id`, `type`, `amount`, `currency`, `description`, `transaction_date`, `is_recurring`, `photo`, `location`, `created_at`, `updated_at`) VALUES
(7, 1, 3, 11, 'income', 28.00, 'TRY', '', '2025-05-25 22:19:00', 0, NULL, NULL, '2025-05-25 20:19:39', NULL),
(8, 1, 1, 21, 'expense', 2900.00, 'TRY', 'SİTE AİDATI', '2025-05-26 13:31:00', 0, NULL, NULL, '2025-05-26 11:31:33', NULL),
(9, 1, 3, 11, 'income', 264.00, 'TRY', 'usdc', '2025-05-26 13:35:00', 0, NULL, NULL, '2025-05-26 11:36:02', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `transaction_tags`
--

CREATE TABLE `transaction_tags` (
  `transaction_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `email_verified` tinyint(1) DEFAULT 0,
  `avatar` varchar(255) DEFAULT NULL,
  `twofa_secret` varchar(32) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `username`, `name`, `surname`, `email`, `full_name`, `phone`, `password`, `is_admin`, `email_verified`, `avatar`, `twofa_secret`, `last_login`, `login_count`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Admin', 'User', 'admin@example.com', 'Admin User', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, NULL, NULL, '2025-05-26 14:05:05', 0, 1, '2025-05-25 10:06:17', '2025-05-26 11:05:05'),
(2, NULL, 'ahmet', 'yıldırım', 'ahmet@eposta.com', NULL, NULL, '$2y$10$Aq6lSN0bVWPlXPUOVbc1kuQc.EP81ApmgHYIObUlrI5Ee8zwF/cDK', 0, 0, NULL, NULL, '2025-05-26 14:05:02', 0, 1, '2025-05-26 05:06:58', '2025-05-26 11:05:02');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `wallets`
--

CREATE TABLE `wallets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('cash','bank','credit_card','savings','investment','crypto') NOT NULL,
  `currency` varchar(10) DEFAULT 'TRY',
  `balance` decimal(18,2) DEFAULT 0.00,
  `color` varchar(20) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `wallets`
--

INSERT INTO `wallets` (`id`, `user_id`, `name`, `type`, `currency`, `balance`, `color`, `icon`, `is_default`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'ENPARA', 'bank', 'TRY', 174203.00, '#860935', 'money-bill', 0, 1, '2025-05-25 10:21:10', '2025-05-25 20:20:09'),
(3, 1, 'BINANCE', 'cash', 'USD', 18314.00, '#dfe212', 'ellipsis-h', 0, 1, '2025-05-25 11:50:22', '2025-05-25 20:19:11'),
(4, 2, 'Nakit', 'cash', 'TRY', 0.00, NULL, NULL, 1, 1, '2025-05-26 05:06:58', NULL);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `id` int(11) NOT NULL,
  `wallet_id` int(11) NOT NULL,
  `type` enum('deposit','withdraw','transfer') NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `source_wallet_id` int(11) DEFAULT NULL,
  `target_wallet_id` int(11) DEFAULT NULL,
  `transaction_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Tablo için indeksler `analytics_cache`
--
ALTER TABLE `analytics_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_cache` (`user_id`,`cache_key`),
  ADD KEY `expires_at` (`expires_at`);

--
-- Tablo için indeksler `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `wallet_id` (`wallet_id`);

--
-- Tablo için indeksler `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_type` (`user_id`,`type`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Tablo için indeksler `credit_cards`
--
ALTER TABLE `credit_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Tablo için indeksler `credit_card_transactions`
--
ALTER TABLE `credit_card_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_credit_card_id` (`credit_card_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `parent_transaction_id` (`parent_transaction_id`),
  ADD KEY `payment_wallet_id` (`payment_wallet_id`);

--
-- Tablo için indeksler `credit_card_transaction_tags`
--
ALTER TABLE `credit_card_transaction_tags`
  ADD PRIMARY KEY (`credit_card_transaction_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Tablo için indeksler `custom_icons`
--
ALTER TABLE `custom_icons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `icon_name` (`icon_name`);

--
-- Tablo için indeksler `exchange_rates`
--
ALTER TABLE `exchange_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_currencies_date` (`from_currency`,`to_currency`,`date`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_currencies` (`from_currency`,`to_currency`);

--
-- Tablo için indeksler `financial_goals`
--
ALTER TABLE `financial_goals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `wallet_id` (`wallet_id`);

--
-- Tablo için indeksler `installment_plans`
--
ALTER TABLE `installment_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_credit_card_id` (`credit_card_id`),
  ADD KEY `idx_transaction_id` (`transaction_id`);

--
-- Tablo için indeksler `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `payment_plans`
--
ALTER TABLE `payment_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `status` (`status`);

--
-- Tablo için indeksler `payment_plan_history`
--
ALTER TABLE `payment_plan_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_plan_id` (`payment_plan_id`),
  ADD KEY `payment_plan_item_id` (`payment_plan_item_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Tablo için indeksler `payment_plan_items`
--
ALTER TABLE `payment_plan_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_plan_id` (`payment_plan_id`),
  ADD KEY `wallet_id` (`wallet_id`),
  ADD KEY `credit_card_id` (`credit_card_id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `credit_card_transaction_id` (`credit_card_transaction_id`),
  ADD KEY `status` (`status`),
  ADD KEY `due_date` (`due_date`);

--
-- Tablo için indeksler `scheduled_payments`
--
ALTER TABLE `scheduled_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `wallet_id` (`wallet_id`),
  ADD KEY `credit_card_id` (`credit_card_id`);

--
-- Tablo için indeksler `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_setting` (`user_id`,`setting_key`);

--
-- Tablo için indeksler `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Tablo için indeksler `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Tablo için indeksler `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type_date` (`type`,`transaction_date`),
  ADD KEY `idx_amount` (`amount`),
  ADD KEY `idx_user_date` (`user_id`,`transaction_date`),
  ADD KEY `wallet_id` (`wallet_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Tablo için indeksler `transaction_tags`
--
ALTER TABLE `transaction_tags`
  ADD PRIMARY KEY (`transaction_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Tablo için indeksler `wallets`
--
ALTER TABLE `wallets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_currency` (`user_id`,`currency`);

--
-- Tablo için indeksler `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wallet_id` (`wallet_id`),
  ADD KEY `source_wallet_id` (`source_wallet_id`),
  ADD KEY `target_wallet_id` (`target_wallet_id`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `analytics_cache`
--
ALTER TABLE `analytics_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Tablo için AUTO_INCREMENT değeri `credit_cards`
--
ALTER TABLE `credit_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Tablo için AUTO_INCREMENT değeri `credit_card_transactions`
--
ALTER TABLE `credit_card_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=149;

--
-- Tablo için AUTO_INCREMENT değeri `custom_icons`
--
ALTER TABLE `custom_icons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Tablo için AUTO_INCREMENT değeri `exchange_rates`
--
ALTER TABLE `exchange_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `financial_goals`
--
ALTER TABLE `financial_goals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `installment_plans`
--
ALTER TABLE `installment_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Tablo için AUTO_INCREMENT değeri `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- Tablo için AUTO_INCREMENT değeri `payment_plans`
--
ALTER TABLE `payment_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- Tablo için AUTO_INCREMENT değeri `payment_plan_history`
--
ALTER TABLE `payment_plan_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- Tablo için AUTO_INCREMENT değeri `payment_plan_items`
--
ALTER TABLE `payment_plan_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- Tablo için AUTO_INCREMENT değeri `scheduled_payments`
--
ALTER TABLE `scheduled_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Tablo için AUTO_INCREMENT değeri `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Tablo için AUTO_INCREMENT değeri `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `wallets`
--
ALTER TABLE `wallets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Tablo için AUTO_INCREMENT değeri `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `budgets_ibfk_3` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `categories_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `credit_cards`
--
ALTER TABLE `credit_cards`
  ADD CONSTRAINT `credit_cards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `credit_card_transactions`
--
ALTER TABLE `credit_card_transactions`
  ADD CONSTRAINT `credit_card_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credit_card_transactions_ibfk_2` FOREIGN KEY (`credit_card_id`) REFERENCES `credit_cards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credit_card_transactions_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `credit_card_transactions_ibfk_4` FOREIGN KEY (`parent_transaction_id`) REFERENCES `credit_card_transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credit_card_transactions_payment_wallet_fk` FOREIGN KEY (`payment_wallet_id`) REFERENCES `wallets` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `credit_card_transaction_tags`
--
ALTER TABLE `credit_card_transaction_tags`
  ADD CONSTRAINT `credit_card_transaction_tags_ibfk_1` FOREIGN KEY (`credit_card_transaction_id`) REFERENCES `credit_card_transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credit_card_transaction_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `financial_goals`
--
ALTER TABLE `financial_goals`
  ADD CONSTRAINT `financial_goals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `financial_goals_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `financial_goals_ibfk_3` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `installment_plans`
--
ALTER TABLE `installment_plans`
  ADD CONSTRAINT `installment_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `installment_plans_ibfk_2` FOREIGN KEY (`credit_card_id`) REFERENCES `credit_cards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `installment_plans_ibfk_3` FOREIGN KEY (`transaction_id`) REFERENCES `credit_card_transactions` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `tags`
--
ALTER TABLE `tags`
  ADD CONSTRAINT `tags_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `transaction_tags`
--
ALTER TABLE `transaction_tags`
  ADD CONSTRAINT `transaction_tags_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transaction_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `wallets`
--
ALTER TABLE `wallets`
  ADD CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wallet_transactions_ibfk_2` FOREIGN KEY (`source_wallet_id`) REFERENCES `wallets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `wallet_transactions_ibfk_3` FOREIGN KEY (`target_wallet_id`) REFERENCES `wallets` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
