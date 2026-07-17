-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 14, 2026 at 10:32 AM
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
-- Database: `alami`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `log_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `causer_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `batch_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `log_name`, `description`, `subject_type`, `event`, `subject_id`, `causer_type`, `causer_id`, `properties`, `batch_uuid`, `created_at`, `updated_at`) VALUES
(11971, 'Product', 'Data Product has been created', 'App\\Models\\Product', 'created', 368, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":368,\"pic\":null,\"code\":\"A0001\",\"name\":\"ALAMI\",\"category_id\":1,\"desc\":null,\"warna\":null,\"ukuran\":null,\"outlet_id\":null,\"satuan\":\"PCS\",\"satuan_besar\":\"SLOP\",\"konversi_qty\":\"10.00\",\"brand\":null,\"model\":null,\"is_serialized\":false,\"harga_beli\":0,\"harga_jual\":null,\"min_stock\":0,\"lokasi\":null,\"status_produk\":\"sudah\",\"status_produk_note\":null,\"stock_value\":\"0.00\",\"diskon\":null,\"deleted_at\":null}}', NULL, '2026-06-27 03:14:12', '2026-06-27 03:14:12'),
(11972, 'PembelianProduct', 'Data PembelianProduct has been created', 'App\\Models\\PembelianProduct', 'created', 558, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":558,\"pembelian_id\":32,\"product_id\":368,\"harga_beli\":6500,\"qty\":120,\"qty_diterima\":0,\"subtotal\":780000,\"expired_at\":null,\"serial_numbers\":null,\"deleted_at\":null}}', NULL, '2026-06-27 03:35:49', '2026-06-27 03:35:49'),
(11973, 'StockPembelian', 'Data StockPembelian has been created', 'App\\Models\\StockPembelian', 'created', 554, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":554,\"pembelian_id\":32,\"product_id\":368,\"sku\":null,\"harga_beli\":6500,\"qty\":120,\"subtotal\":780000,\"serial_number\":null,\"imei\":null,\"condition\":\"new\",\"status\":\"available\",\"expired_at\":null,\"deleted_at\":null}}', NULL, '2026-06-27 03:35:49', '2026-06-27 03:35:49'),
(11974, 'Product', 'Data Product has been updated', 'App\\Models\\Product', 'updated', 368, 'App\\Models\\User', 1, '{\"attributes\":{\"harga_beli\":6500},\"old\":{\"harga_beli\":0}}', NULL, '2026-06-27 03:35:49', '2026-06-27 03:35:49'),
(11975, 'default', 'created', 'App\\Models\\PembelianTransaction', 'created', 28, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":null,\"payment_method\":\"bank_transfer\",\"payment_reference\":\"-\",\"payment_history\":null,\"status\":\"unpaid\",\"amount\":0,\"bukti_transfer\":null,\"notes\":null}}', NULL, '2026-06-27 03:35:49', '2026-06-27 03:35:49'),
(11976, 'PembelianProduct', 'Data PembelianProduct has been created', 'App\\Models\\PembelianProduct', 'created', 559, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":559,\"pembelian_id\":33,\"product_id\":368,\"harga_beli\":6500,\"qty\":120,\"qty_diterima\":0,\"subtotal\":780000,\"expired_at\":null,\"serial_numbers\":null,\"deleted_at\":null}}', NULL, '2026-06-27 03:53:57', '2026-06-27 03:53:57'),
(11977, 'StockPembelian', 'Data StockPembelian has been created', 'App\\Models\\StockPembelian', 'created', 555, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":555,\"pembelian_id\":33,\"product_id\":368,\"sku\":null,\"harga_beli\":6500,\"qty\":120,\"subtotal\":780000,\"serial_number\":null,\"imei\":null,\"condition\":\"new\",\"status\":\"available\",\"expired_at\":null,\"deleted_at\":null}}', NULL, '2026-06-27 03:53:57', '2026-06-27 03:53:57'),
(11978, 'default', 'created', 'App\\Models\\PembelianTransaction', 'created', 29, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":null,\"payment_method\":\"bank_transfer\",\"payment_reference\":\"-\",\"payment_history\":null,\"status\":\"unpaid\",\"amount\":0,\"bukti_transfer\":null,\"notes\":null}}', NULL, '2026-06-27 03:53:57', '2026-06-27 03:53:57'),
(11979, 'Product', 'Data Product has been updated', 'App\\Models\\Product', 'updated', 368, 'App\\Models\\User', 1, '{\"attributes\":{\"satuan\":\"BUNGKUS\"},\"old\":{\"satuan\":\"PCS\"}}', NULL, '2026-06-27 04:33:06', '2026-06-27 04:33:06'),
(11980, 'default', 'updated', 'App\\Models\\PembelianTransaction', 'updated', 29, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":\"2026-06-27T04:37:00.000000Z\",\"payment_method\":\"cash\",\"payment_reference\":\"123\",\"payment_history\":[{\"payment_date\":\"2026-06-27T11:37\",\"amount\":\"700000\",\"payment_method\":\"cash\",\"payment_reference\":\"123\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-06-27 11:37:50\"}],\"status\":\"partial\",\"amount\":700000},\"old\":{\"payment_date\":null,\"payment_method\":\"bank_transfer\",\"payment_reference\":\"-\",\"payment_history\":null,\"status\":\"unpaid\",\"amount\":0}}', NULL, '2026-06-27 04:37:50', '2026-06-27 04:37:50'),
(11981, 'default', 'updated', 'App\\Models\\PembelianTransaction', 'updated', 29, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_reference\":\"3321\",\"payment_history\":[{\"payment_date\":\"2026-06-27T11:37\",\"amount\":\"700000\",\"payment_method\":\"cash\",\"payment_reference\":\"123\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-06-27 11:37:50\"},{\"payment_date\":\"2026-06-27T11:37\",\"amount\":\"80000\",\"payment_method\":\"cash\",\"payment_reference\":\"3321\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-06-27 11:38:25\"}],\"status\":\"paid\",\"amount\":780000},\"old\":{\"payment_reference\":\"123\",\"payment_history\":[{\"payment_date\":\"2026-06-27T11:37\",\"amount\":\"700000\",\"payment_method\":\"cash\",\"payment_reference\":\"123\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-06-27 11:37:50\"}],\"status\":\"partial\",\"amount\":700000}}', NULL, '2026-06-27 04:38:25', '2026-06-27 04:38:25'),
(11982, 'PembelianProduct', 'Data PembelianProduct has been updated', 'App\\Models\\PembelianProduct', 'updated', 559, 'App\\Models\\User', 1, '{\"attributes\":{\"qty_diterima\":120},\"old\":{\"qty_diterima\":0}}', NULL, '2026-07-02 05:14:57', '2026-07-02 05:14:57'),
(11983, 'Stock', 'Data Stock has been created', 'App\\Models\\Stock', 'created', 321, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":321,\"product_id\":368,\"sku\":null,\"harga_beli\":6500,\"qty\":120,\"qty_reserved\":0,\"expired_at\":null,\"serial_number\":null,\"batch_number\":null,\"imei\":null,\"condition\":\"new\",\"location\":null,\"status\":\"available\",\"pembelian_id\":33,\"subtotal\":\"780000\",\"deleted_at\":null,\"qty_available\":120,\"stock_status\":\"available\"}}', NULL, '2026-07-02 05:14:57', '2026-07-02 05:14:57'),
(11984, 'StockPembelian', 'Data StockPembelian has been updated', 'App\\Models\\StockPembelian', 'updated', 555, 'App\\Models\\User', 1, '{\"attributes\":{\"qty\":0},\"old\":{\"qty\":120}}', NULL, '2026-07-02 05:14:57', '2026-07-02 05:14:57'),
(11985, 'StockPembelian', 'Data StockPembelian has been deleted', 'App\\Models\\StockPembelian', 'deleted', 555, 'App\\Models\\User', 1, '{\"old\":{\"id\":555,\"pembelian_id\":33,\"product_id\":368,\"sku\":null,\"harga_beli\":6500,\"qty\":0,\"subtotal\":780000,\"serial_number\":null,\"imei\":null,\"condition\":\"new\",\"status\":\"available\",\"expired_at\":null,\"deleted_at\":\"2026-07-02T05:14:57.000000Z\"}}', NULL, '2026-07-02 05:14:57', '2026-07-02 05:14:57'),
(11986, 'Product', 'Data Product has been updated', 'App\\Models\\Product', 'updated', 368, 'App\\Models\\User', 1, '{\"attributes\":{\"stock_value\":\"780000.00\"},\"old\":{\"stock_value\":\"0.00\"}}', NULL, '2026-07-02 05:14:57', '2026-07-02 05:14:57'),
(11987, 'Product', 'Data Product has been created', 'App\\Models\\Product', 'created', 369, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":369,\"pic\":null,\"code\":\"Necessitatibus hic e\",\"name\":\"Forrest Richards\",\"category_id\":2,\"desc\":null,\"warna\":null,\"ukuran\":null,\"outlet_id\":null,\"satuan\":\"Est quidem nemo pari\",\"satuan_besar\":\"Quia alias ducimus\",\"konversi_qty\":252,\"brand\":null,\"model\":null,\"is_serialized\":false,\"harga_beli\":0,\"harga_jual\":null,\"min_stock\":0,\"lokasi\":null,\"status_produk\":\"sudah\",\"status_produk_note\":null,\"stock_value\":\"0.00\",\"diskon\":null,\"deleted_at\":null}}', NULL, '2026-07-02 07:21:13', '2026-07-02 07:21:13'),
(11988, 'Product', 'Data Product has been deleted', 'App\\Models\\Product', 'deleted', 369, 'App\\Models\\User', 1, '{\"old\":{\"id\":369,\"pic\":null,\"code\":\"Necessitatibus hic e\",\"name\":\"Forrest Richards\",\"category_id\":2,\"desc\":null,\"warna\":null,\"ukuran\":null,\"outlet_id\":null,\"satuan\":\"Est quidem nemo pari\",\"satuan_besar\":\"Quia alias ducimus\",\"konversi_qty\":252,\"brand\":null,\"model\":null,\"is_serialized\":false,\"harga_beli\":0,\"harga_jual\":null,\"min_stock\":0,\"lokasi\":null,\"status_produk\":\"sudah\",\"status_produk_note\":null,\"stock_value\":\"0.00\",\"diskon\":null,\"deleted_at\":\"2026-07-02T07:21:41.000000Z\"}}', NULL, '2026-07-02 07:21:41', '2026-07-02 07:21:41'),
(11989, 'Product', 'Data Product has been created', 'App\\Models\\Product', 'created', 370, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":370,\"pic\":null,\"code\":\"Quam sunt totam anim\",\"name\":\"Julian Maxwell\",\"category_id\":2,\"desc\":null,\"warna\":null,\"ukuran\":null,\"outlet_id\":null,\"satuan\":\"Ut nobis voluptates\",\"satuan_besar\":\"Aut culpa autem anim\",\"konversi_qty\":16,\"satuan_terbesar\":null,\"konversi_qty_terbesar\":null,\"brand\":null,\"model\":null,\"is_serialized\":false,\"harga_beli\":0,\"harga_jual\":null,\"min_stock\":0,\"lokasi\":null,\"status_produk\":\"sudah\",\"status_produk_note\":null,\"stock_value\":\"0.00\",\"diskon\":null,\"deleted_at\":null}}', NULL, '2026-07-02 07:50:31', '2026-07-02 07:50:31'),
(11990, 'Product', 'Data Product has been updated', 'App\\Models\\Product', 'updated', 370, 'App\\Models\\User', 1, '{\"attributes\":{\"satuan_terbesar\":\"jlds\",\"konversi_qty_terbesar\":10},\"old\":{\"satuan_terbesar\":null,\"konversi_qty_terbesar\":null}}', NULL, '2026-07-02 08:27:51', '2026-07-02 08:27:51'),
(11991, 'Product', 'Data Product has been created', 'App\\Models\\Product', 'created', 371, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":371,\"pic\":null,\"code\":\"Aspernatur eiusmod p\",\"name\":\"Karyn Campos\",\"category_id\":2,\"desc\":null,\"warna\":null,\"ukuran\":null,\"outlet_id\":null,\"satuan\":\"Molestias accusantiu\",\"satuan_besar\":\"Vel expedita eos po\",\"konversi_qty\":390,\"satuan_terbesar\":\"Voluptatibus volupta\",\"konversi_qty_terbesar\":497,\"brand\":null,\"model\":null,\"is_serialized\":false,\"harga_beli\":0,\"harga_jual\":null,\"min_stock\":0,\"lokasi\":null,\"status_produk\":\"sudah\",\"status_produk_note\":null,\"stock_value\":\"0.00\",\"diskon\":null,\"deleted_at\":null}}', NULL, '2026-07-02 08:49:54', '2026-07-02 08:49:54'),
(11992, 'Product', 'Data Product has been updated', 'App\\Models\\Product', 'updated', 371, 'App\\Models\\User', 1, '{\"attributes\":{\"satuan_terbesar\":\"ball\"},\"old\":{\"satuan_terbesar\":\"Voluptatibus volupta\"}}', NULL, '2026-07-02 08:50:28', '2026-07-02 08:50:28'),
(11993, 'Product', 'Data Product has been updated', 'App\\Models\\Product', 'updated', 368, 'App\\Models\\User', 1, '{\"attributes\":{\"satuan\":\"Pack\",\"satuan_terbesar\":\"Ball\",\"konversi_qty_terbesar\":20},\"old\":{\"satuan\":\"BUNGKUS\",\"satuan_terbesar\":null,\"konversi_qty_terbesar\":null}}', NULL, '2026-07-02 08:58:05', '2026-07-02 08:58:05'),
(11994, 'Product', 'Data Product has been updated', 'App\\Models\\Product', 'updated', 368, 'App\\Models\\User', 1, '{\"attributes\":{\"harga_beli\":7000},\"old\":{\"harga_beli\":6500}}', NULL, '2026-07-03 01:57:16', '2026-07-03 01:57:16'),
(11995, 'PembelianProduct', 'Data PembelianProduct has been created', 'App\\Models\\PembelianProduct', 'created', 560, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":560,\"pembelian_id\":34,\"product_id\":368,\"harga_beli\":7000,\"qty\":250,\"qty_diterima\":0,\"subtotal\":1750000,\"expired_at\":null,\"serial_numbers\":null,\"deleted_at\":null}}', NULL, '2026-07-03 04:43:00', '2026-07-03 04:43:00'),
(11996, 'StockPembelian', 'Data StockPembelian has been created', 'App\\Models\\StockPembelian', 'created', 556, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":556,\"pembelian_id\":34,\"product_id\":368,\"sku\":null,\"harga_beli\":7000,\"qty\":250,\"subtotal\":1750000,\"serial_number\":null,\"imei\":null,\"condition\":\"new\",\"status\":\"available\",\"expired_at\":null,\"deleted_at\":null}}', NULL, '2026-07-03 04:43:00', '2026-07-03 04:43:00'),
(11997, 'default', 'created', 'App\\Models\\PembelianTransaction', 'created', 30, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":null,\"payment_method\":\"bank_transfer\",\"payment_reference\":\"-\",\"payment_history\":null,\"status\":\"unpaid\",\"amount\":0,\"bukti_transfer\":null,\"notes\":null}}', NULL, '2026-07-03 04:43:00', '2026-07-03 04:43:00'),
(11998, 'PembelianProduct', 'Data PembelianProduct has been updated', 'App\\Models\\PembelianProduct', 'updated', 560, 'App\\Models\\User', 1, '{\"attributes\":{\"qty\":260,\"subtotal\":1820000},\"old\":{\"qty\":250,\"subtotal\":1750000}}', NULL, '2026-07-03 04:45:09', '2026-07-03 04:45:09'),
(11999, 'StockPembelian', 'Data StockPembelian has been updated', 'App\\Models\\StockPembelian', 'updated', 556, 'App\\Models\\User', 1, '{\"attributes\":{\"qty\":260,\"subtotal\":1820000},\"old\":{\"qty\":250,\"subtotal\":1750000}}', NULL, '2026-07-03 04:45:09', '2026-07-03 04:45:09'),
(12000, 'default', 'updated', 'App\\Models\\PembelianTransaction', 'updated', 30, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":\"2026-07-03T04:45:00.000000Z\",\"payment_reference\":\"miun1\",\"payment_history\":[{\"payment_date\":\"2026-07-03T11:45\",\"amount\":\"1000000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"miun1\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-03 11:45:44\"}],\"status\":\"partial\",\"amount\":1000000},\"old\":{\"payment_date\":null,\"payment_reference\":\"-\",\"payment_history\":null,\"status\":\"unpaid\",\"amount\":0}}', NULL, '2026-07-03 04:45:44', '2026-07-03 04:45:44'),
(12001, 'default', 'updated', 'App\\Models\\PembelianTransaction', 'updated', 30, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_reference\":\"q312\",\"payment_history\":[{\"payment_date\":\"2026-07-03T11:45\",\"amount\":\"1000000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"miun1\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-03 11:45:44\"},{\"payment_date\":\"2026-07-03T11:45\",\"amount\":\"820000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"q312\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-03 11:46:07\"}],\"status\":\"paid\",\"amount\":1820000},\"old\":{\"payment_reference\":\"miun1\",\"payment_history\":[{\"payment_date\":\"2026-07-03T11:45\",\"amount\":\"1000000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"miun1\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-03 11:45:44\"}],\"status\":\"partial\",\"amount\":1000000}}', NULL, '2026-07-03 04:46:07', '2026-07-03 04:46:07'),
(12002, 'PembelianProduct', 'Data PembelianProduct has been updated', 'App\\Models\\PembelianProduct', 'updated', 560, 'App\\Models\\User', 1, '{\"attributes\":{\"qty_diterima\":260},\"old\":{\"qty_diterima\":0}}', NULL, '2026-07-03 04:46:38', '2026-07-03 04:46:38'),
(12003, 'Stock', 'Data Stock has been created', 'App\\Models\\Stock', 'created', 322, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":322,\"product_id\":368,\"sku\":null,\"harga_beli\":7000,\"qty\":260,\"qty_reserved\":0,\"expired_at\":null,\"serial_number\":null,\"batch_number\":null,\"imei\":null,\"condition\":\"new\",\"location\":null,\"status\":\"available\",\"pembelian_id\":34,\"subtotal\":\"1820000\",\"deleted_at\":null,\"qty_available\":260,\"stock_status\":\"available\"}}', NULL, '2026-07-03 04:46:38', '2026-07-03 04:46:38'),
(12004, 'StockPembelian', 'Data StockPembelian has been updated', 'App\\Models\\StockPembelian', 'updated', 556, 'App\\Models\\User', 1, '{\"attributes\":{\"qty\":0},\"old\":{\"qty\":260}}', NULL, '2026-07-03 04:46:38', '2026-07-03 04:46:38'),
(12005, 'StockPembelian', 'Data StockPembelian has been deleted', 'App\\Models\\StockPembelian', 'deleted', 556, 'App\\Models\\User', 1, '{\"old\":{\"id\":556,\"pembelian_id\":34,\"product_id\":368,\"sku\":null,\"harga_beli\":7000,\"qty\":0,\"subtotal\":1820000,\"serial_number\":null,\"imei\":null,\"condition\":\"new\",\"status\":\"available\",\"expired_at\":null,\"deleted_at\":\"2026-07-03T04:46:38.000000Z\"}}', NULL, '2026-07-03 04:46:38', '2026-07-03 04:46:38'),
(12006, 'Product', 'Data Product has been updated', 'App\\Models\\Product', 'updated', 368, 'App\\Models\\User', 1, '{\"attributes\":{\"stock_value\":\"2660000.00\"},\"old\":{\"stock_value\":\"780000.00\"}}', NULL, '2026-07-03 04:46:38', '2026-07-03 04:46:38'),
(12007, 'Stock', 'Data Stock has been updated', 'App\\Models\\Stock', 'updated', 322, 'App\\Models\\User', 1, '{\"attributes\":{\"qty\":250,\"qty_available\":250},\"old\":{\"qty\":260,\"qty_available\":260}}', NULL, '2026-07-03 07:28:22', '2026-07-03 07:28:22'),
(12008, 'Stock', 'Data Stock has been updated', 'App\\Models\\Stock', 'updated', 322, 'App\\Models\\User', 1, '{\"attributes\":{\"qty\":260,\"qty_available\":260},\"old\":{\"qty\":250,\"qty_available\":250}}', NULL, '2026-07-04 01:19:19', '2026-07-04 01:19:19'),
(12009, 'PembelianProduct', 'Data PembelianProduct has been created', 'App\\Models\\PembelianProduct', 'created', 561, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":561,\"pembelian_id\":35,\"product_id\":368,\"harga_beli\":7000,\"qty\":210,\"qty_diterima\":0,\"subtotal\":1470000,\"expired_at\":null,\"serial_numbers\":null,\"deleted_at\":null}}', NULL, '2026-07-04 01:37:40', '2026-07-04 01:37:40'),
(12010, 'StockPembelian', 'Data StockPembelian has been created', 'App\\Models\\StockPembelian', 'created', 557, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":557,\"pembelian_id\":35,\"product_id\":368,\"sku\":null,\"harga_beli\":7000,\"qty\":210,\"subtotal\":1470000,\"serial_number\":null,\"imei\":null,\"condition\":\"new\",\"status\":\"available\",\"expired_at\":null,\"deleted_at\":null}}', NULL, '2026-07-04 01:37:40', '2026-07-04 01:37:40'),
(12011, 'default', 'created', 'App\\Models\\PembelianTransaction', 'created', 31, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":null,\"payment_method\":\"bank_transfer\",\"payment_reference\":\"-\",\"payment_history\":null,\"status\":\"unpaid\",\"amount\":0,\"bukti_transfer\":null,\"notes\":null}}', NULL, '2026-07-04 01:37:40', '2026-07-04 01:37:40'),
(12012, 'PembelianProduct', 'Data PembelianProduct has been created', 'App\\Models\\PembelianProduct', 'created', 562, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":562,\"pembelian_id\":36,\"product_id\":368,\"harga_beli\":7000,\"qty\":150,\"qty_diterima\":0,\"subtotal\":1050000,\"expired_at\":null,\"serial_numbers\":null,\"deleted_at\":null}}', NULL, '2026-07-04 01:38:02', '2026-07-04 01:38:02'),
(12013, 'StockPembelian', 'Data StockPembelian has been created', 'App\\Models\\StockPembelian', 'created', 558, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":558,\"pembelian_id\":36,\"product_id\":368,\"sku\":null,\"harga_beli\":7000,\"qty\":150,\"subtotal\":1050000,\"serial_number\":null,\"imei\":null,\"condition\":\"new\",\"status\":\"available\",\"expired_at\":null,\"deleted_at\":null}}', NULL, '2026-07-04 01:38:02', '2026-07-04 01:38:02'),
(12014, 'default', 'created', 'App\\Models\\PembelianTransaction', 'created', 32, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":null,\"payment_method\":\"bank_transfer\",\"payment_reference\":\"-\",\"payment_history\":null,\"status\":\"unpaid\",\"amount\":0,\"bukti_transfer\":null,\"notes\":null}}', NULL, '2026-07-04 01:38:02', '2026-07-04 01:38:02'),
(12015, 'default', 'updated', 'App\\Models\\PembelianTransaction', 'updated', 32, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":\"2026-07-04T01:42:00.000000Z\",\"payment_reference\":\"1234\",\"payment_history\":[{\"payment_date\":\"2026-07-04T08:42\",\"amount\":\"550000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"1234\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 08:42:25\"}],\"status\":\"partial\",\"amount\":550000},\"old\":{\"payment_date\":null,\"payment_reference\":\"-\",\"payment_history\":null,\"status\":\"unpaid\",\"amount\":0}}', NULL, '2026-07-04 01:42:25', '2026-07-04 01:42:25'),
(12016, 'default', 'updated', 'App\\Models\\PembelianTransaction', 'updated', 32, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_reference\":\"PAY-20260704-PRODU-9892\",\"payment_history\":[{\"payment_date\":\"2026-07-04T08:42\",\"amount\":\"550000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"1234\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 08:42:25\"},{\"payment_date\":\"2026-07-04T08:42\",\"amount\":\"50000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-9892\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 08:55:59\"}],\"amount\":600000},\"old\":{\"payment_reference\":\"1234\",\"payment_history\":[{\"payment_date\":\"2026-07-04T08:42\",\"amount\":\"550000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"1234\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 08:42:25\"}],\"amount\":550000}}', NULL, '2026-07-04 01:55:59', '2026-07-04 01:55:59'),
(12017, 'PembelianProduct', 'Data PembelianProduct has been created', 'App\\Models\\PembelianProduct', 'created', 563, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":563,\"pembelian_id\":37,\"product_id\":368,\"harga_beli\":7000,\"qty\":320,\"qty_diterima\":0,\"subtotal\":2240000,\"expired_at\":null,\"serial_numbers\":null,\"deleted_at\":null}}', NULL, '2026-07-04 01:56:43', '2026-07-04 01:56:43'),
(12018, 'StockPembelian', 'Data StockPembelian has been created', 'App\\Models\\StockPembelian', 'created', 559, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":559,\"pembelian_id\":37,\"product_id\":368,\"sku\":null,\"harga_beli\":7000,\"qty\":320,\"subtotal\":2240000,\"serial_number\":null,\"imei\":null,\"condition\":\"new\",\"status\":\"available\",\"expired_at\":null,\"deleted_at\":null}}', NULL, '2026-07-04 01:56:43', '2026-07-04 01:56:43'),
(12019, 'default', 'created', 'App\\Models\\PembelianTransaction', 'created', 33, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":null,\"payment_method\":\"bank_transfer\",\"payment_reference\":\"-\",\"payment_history\":null,\"status\":\"unpaid\",\"amount\":0,\"bukti_transfer\":null,\"notes\":null}}', NULL, '2026-07-04 01:56:43', '2026-07-04 01:56:43'),
(12020, 'default', 'updated', 'App\\Models\\PembelianTransaction', 'updated', 33, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":\"2026-07-04T02:00:00.000000Z\",\"payment_reference\":\"PAY-20260704-PRODU-9569\",\"payment_history\":[{\"payment_date\":\"2026-07-04T09:00\",\"amount\":\"160000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-9569\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:00:49\"}],\"status\":\"partial\",\"amount\":160000},\"old\":{\"payment_date\":null,\"payment_reference\":\"-\",\"payment_history\":null,\"status\":\"unpaid\",\"amount\":0}}', NULL, '2026-07-04 02:00:49', '2026-07-04 02:00:49'),
(12021, 'default', 'updated', 'App\\Models\\PembelianTransaction', 'updated', 33, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_reference\":\"PAY-20260704-PRODU-8266\",\"payment_history\":[{\"payment_date\":\"2026-07-04T09:00\",\"amount\":\"160000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-9569\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:00:49\"},{\"payment_date\":\"2026-07-04T09:00\",\"amount\":\"1000000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-8266\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:01:04\"}],\"amount\":1160000},\"old\":{\"payment_reference\":\"PAY-20260704-PRODU-9569\",\"payment_history\":[{\"payment_date\":\"2026-07-04T09:00\",\"amount\":\"160000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-9569\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:00:49\"}],\"amount\":160000}}', NULL, '2026-07-04 02:01:04', '2026-07-04 02:01:04'),
(12022, 'PembelianProduct', 'Data PembelianProduct has been updated', 'App\\Models\\PembelianProduct', 'updated', 561, 'App\\Models\\User', 1, '{\"attributes\":{\"qty_diterima\":210},\"old\":{\"qty_diterima\":0}}', NULL, '2026-07-04 02:05:33', '2026-07-04 02:05:33'),
(12023, 'Stock', 'Data Stock has been created', 'App\\Models\\Stock', 'created', 323, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":323,\"product_id\":368,\"sku\":null,\"harga_beli\":7000,\"qty\":210,\"qty_reserved\":0,\"expired_at\":null,\"serial_number\":null,\"batch_number\":null,\"imei\":null,\"condition\":\"new\",\"location\":null,\"status\":\"available\",\"pembelian_id\":35,\"subtotal\":\"1470000\",\"deleted_at\":null,\"qty_available\":210,\"stock_status\":\"available\"}}', NULL, '2026-07-04 02:05:33', '2026-07-04 02:05:33'),
(12024, 'StockPembelian', 'Data StockPembelian has been updated', 'App\\Models\\StockPembelian', 'updated', 557, 'App\\Models\\User', 1, '{\"attributes\":{\"qty\":0},\"old\":{\"qty\":210}}', NULL, '2026-07-04 02:05:33', '2026-07-04 02:05:33'),
(12025, 'StockPembelian', 'Data StockPembelian has been deleted', 'App\\Models\\StockPembelian', 'deleted', 557, 'App\\Models\\User', 1, '{\"old\":{\"id\":557,\"pembelian_id\":35,\"product_id\":368,\"sku\":null,\"harga_beli\":7000,\"qty\":0,\"subtotal\":1470000,\"serial_number\":null,\"imei\":null,\"condition\":\"new\",\"status\":\"available\",\"expired_at\":null,\"deleted_at\":\"2026-07-04T02:05:33.000000Z\"}}', NULL, '2026-07-04 02:05:33', '2026-07-04 02:05:33'),
(12026, 'Product', 'Data Product has been updated', 'App\\Models\\Product', 'updated', 368, 'App\\Models\\User', 1, '{\"attributes\":{\"stock_value\":\"4130000.00\"},\"old\":{\"stock_value\":\"2660000.00\"}}', NULL, '2026-07-04 02:05:33', '2026-07-04 02:05:33'),
(12027, 'PembelianProduct', 'Data PembelianProduct has been created', 'App\\Models\\PembelianProduct', 'created', 564, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":564,\"pembelian_id\":38,\"product_id\":368,\"harga_beli\":7000,\"qty\":120,\"qty_diterima\":0,\"subtotal\":840000,\"expired_at\":null,\"serial_numbers\":null,\"deleted_at\":null}}', NULL, '2026-07-04 02:30:05', '2026-07-04 02:30:05'),
(12028, 'StockPembelian', 'Data StockPembelian has been created', 'App\\Models\\StockPembelian', 'created', 560, 'App\\Models\\User', 1, '{\"attributes\":{\"id\":560,\"pembelian_id\":38,\"product_id\":368,\"sku\":null,\"harga_beli\":7000,\"qty\":120,\"subtotal\":840000,\"serial_number\":null,\"imei\":null,\"condition\":\"new\",\"status\":\"available\",\"expired_at\":null,\"deleted_at\":null}}', NULL, '2026-07-04 02:30:05', '2026-07-04 02:30:05'),
(12029, 'default', 'created', 'App\\Models\\PembelianTransaction', 'created', 34, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":null,\"payment_method\":\"bank_transfer\",\"payment_reference\":\"-\",\"payment_history\":null,\"status\":\"unpaid\",\"amount\":0,\"bukti_transfer\":null,\"notes\":null}}', NULL, '2026-07-04 02:30:05', '2026-07-04 02:30:05'),
(12030, 'default', 'updated', 'App\\Models\\PembelianTransaction', 'updated', 34, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":\"2026-07-04T02:32:00.000000Z\",\"payment_reference\":\"PAY-20260704-PRODU-7699\",\"payment_history\":[{\"payment_date\":\"2026-07-04T09:32\",\"amount\":\"40000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-7699\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:32:38\"}],\"status\":\"partial\",\"amount\":40000},\"old\":{\"payment_date\":null,\"payment_reference\":\"-\",\"payment_history\":null,\"status\":\"unpaid\",\"amount\":0}}', NULL, '2026-07-04 02:32:38', '2026-07-04 02:32:38'),
(12031, 'default', 'updated', 'App\\Models\\PembelianTransaction', 'updated', 34, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_reference\":\"PAY-20260704-PRODU-2116\",\"payment_history\":[{\"payment_date\":\"2026-07-04T09:32\",\"amount\":\"40000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-7699\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:32:38\"},{\"payment_date\":\"2026-07-04T09:32\",\"amount\":\"200000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-2116\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:33:01\"}],\"amount\":240000},\"old\":{\"payment_reference\":\"PAY-20260704-PRODU-7699\",\"payment_history\":[{\"payment_date\":\"2026-07-04T09:32\",\"amount\":\"40000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-7699\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:32:38\"}],\"amount\":40000}}', NULL, '2026-07-04 02:33:01', '2026-07-04 02:33:01'),
(12032, 'default', 'updated', 'App\\Models\\PembelianTransaction', 'updated', 34, 'App\\Models\\User', 1, '{\"attributes\":{\"payment_date\":\"2026-07-04T02:33:00.000000Z\",\"payment_reference\":\"PAY-20260704-PRODU-2551\",\"payment_history\":[{\"payment_date\":\"2026-07-04T09:32\",\"amount\":\"40000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-7699\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:32:38\"},{\"payment_date\":\"2026-07-04T09:32\",\"amount\":\"200000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-2116\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:33:01\"},{\"payment_date\":\"2026-07-04T09:33\",\"amount\":\"600000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-2551\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:34:09\"}],\"status\":\"paid\",\"amount\":840000},\"old\":{\"payment_date\":\"2026-07-04T02:32:00.000000Z\",\"payment_reference\":\"PAY-20260704-PRODU-2116\",\"payment_history\":[{\"payment_date\":\"2026-07-04T09:32\",\"amount\":\"40000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-7699\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:32:38\"},{\"payment_date\":\"2026-07-04T09:32\",\"amount\":\"200000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-2116\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:33:01\"}],\"status\":\"partial\",\"amount\":240000}}', NULL, '2026-07-04 02:34:09', '2026-07-04 02:34:09');

-- --------------------------------------------------------

--
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banks`
--

CREATE TABLE `banks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_rek` varchar(255) DEFAULT NULL,
  `no_rek` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `canvases`
--

CREATE TABLE `canvases` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_storage`
--

CREATE TABLE `cart_storage` (
  `id` varchar(255) NOT NULL,
  `cart_data` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `type` enum('product','pengeluaran') DEFAULT NULL,
  `outlet_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`, `type`, `outlet_id`) VALUES
(1, 'KRETEK', '2026-05-23 04:27:48', '2026-07-03 01:37:24', NULL, NULL, NULL),
(2, 'FILTER', '2026-05-23 04:27:48', '2026-07-03 01:37:29', NULL, NULL, NULL),
(3, 'ALAT TULIS', '2026-05-23 04:27:48', '2026-06-27 02:59:04', '2026-06-27 02:59:04', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `delivery_orders`
--

CREATE TABLE `delivery_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `request_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `picking_list_id` bigint(20) UNSIGNED DEFAULT NULL,
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL,
  `prepared_by` bigint(20) UNSIGNED DEFAULT NULL,
  `received_by` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `status` enum('draft','sent','delivered','completed') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_order_items`
--

CREATE TABLE `delivery_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `delivery_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `stock_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `qty_sent` int(11) DEFAULT 0,
  `sku` varchar(255) DEFAULT NULL,
  `expired_at` date DEFAULT NULL,
  `harga_beli` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
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
-- Table structure for table `kas`
--

CREATE TABLE `kas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `outlet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nominal` varchar(255) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
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
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2023_07_24_132335_add_alamat_to_users_table', 1),
(6, '2023_07_24_140934_create_banks_table', 1),
(7, '2023_07_25_042406_create_outlets_table', 1),
(8, '2023_07_25_064851_create_suppliers_table', 1),
(9, '2023_07_25_065615_create_categories_table', 1),
(10, '2023_07_25_070456_create_products_table', 1),
(11, '2023_07_25_072123_create_stocks_table', 1),
(12, '2023_07_25_120000_create_vouchers_table', 1),
(13, '2023_07_25_154511_add_type_to_categories_table', 1),
(14, '2023_07_25_154853_create_pengeluarans_table', 1),
(15, '2023_07_26_135858_create_pembelians_table', 1),
(16, '2023_07_26_140853_add_pembelian_to_stocks_table', 1),
(17, '2023_07_27_173402_create_penjualans_table', 1),
(18, '2023_07_27_175731_create_penjualan_items_table', 1),
(19, '2023_07_28_041013_create_user_cart_table', 1),
(20, '2023_07_28_144451_create_refunds_table', 1),
(21, '2023_07_28_151812_create_refund_items_table', 1),
(22, '2023_07_28_152904_create_sliders_table', 1),
(23, '2023_07_28_181409_create_transactions_table', 1),
(24, '2023_07_28_182652_create_reviews_table', 1),
(25, '2023_07_29_075343_create_user_wishlist_table', 1),
(26, '2023_08_03_115722_create_kas_table', 1),
(27, '2023_08_03_121921_add_type_to_vouchers_table', 1),
(28, '2023_08_03_123245_add_outlet_to_categories_table', 1),
(29, '2023_08_03_160159_add_kas_to_pembelians_table', 1),
(30, '2023_08_04_053654_add_kas_to_penjualans_table', 1),
(31, '2023_08_06_174145_create_refund_pembelians_table', 1),
(32, '2023_08_06_174510_create_refund_pembelian_items_table', 1),
(33, '2023_08_08_144226_update_pengeluarans_table', 1),
(34, '2023_08_08_153237_add_product_to_vouchers_table', 1),
(35, '2023_08_12_151405_create_cart_storage_table', 1),
(36, '2023_08_15_061313_add_new_columns_to_transactions_table', 1),
(37, '2023_08_18_053530_add_draft_to_pembelians_table', 1),
(38, '2023_08_18_061900_create_pembelian_products_table', 1),
(39, '2023_08_18_103201_create_payment_methods_table', 1),
(40, '2023_08_20_140108_add_user_to_refunds_table', 1),
(41, '2023_08_23_191406_add_supplier_to_refund_pembelians_table', 1),
(42, '2023_08_24_114443_add_kas_to_refunds_table', 1),
(43, '2023_08_24_114459_add_kas_to_refund_pembelians_table', 1),
(44, '2023_08_24_123318_add_outlet_to_users_table', 1),
(45, '2025_06_08_155203_create_activity_log_table', 1),
(46, '2025_06_08_155204_add_event_column_to_activity_log_table', 1),
(47, '2025_06_08_155205_add_batch_uuid_column_to_activity_log_table', 1),
(48, '2025_06_08_155605_update_stock_pembelian_products', 1),
(49, '2025_06_13_202231_add_stock_id_and_serial_number_to_penjualan_items_table', 1),
(50, '2025_06_14_185110_add_status_to_stocks_table', 1),
(51, '2025_06_15_200441_add_voucher_to_penjualan_table', 1),
(52, '2025_06_17_165802_add_limit_discount_to_users_table', 1),
(53, '2025_06_17_172626_create_salesman_table', 1),
(54, '2025_06_19_061503_add_salesman_to_penjualans_table', 1),
(55, '2025_06_20_061618_create_stock_pembelians_table', 1),
(56, '2026_02_14_064719_add_warehouse_fields_to_products', 1),
(57, '2026_02_14_065032_create_request_orders_table', 1),
(58, '2026_02_14_065041_create_request_order_items_table', 1),
(59, '2026_02_14_065050_create_picking_lists_table', 1),
(60, '2026_02_14_065057_create_picking_list_items_table', 1),
(61, '2026_02_14_065106_create_delivery_orders_table', 1),
(62, '2026_02_14_065115_create_delivery_order_items_table', 1),
(63, '2026_02_14_065124_create_owner_stocks_table', 1),
(64, '2026_02_14_065133_create_stock_movements_table', 1),
(65, '2026_02_24_030556_add_jenis_outlet', 1),
(66, '2026_02_24_064120_add_receipt_fields_to_pembelians', 1),
(67, '2026_02_25_030704_create_pembelian_transactions_table', 1),
(68, '2026_02_26_132713_update_product_supplier_tables', 1),
(69, '2026_02_26_183236_add_sku_to_stocks', 1),
(70, '2026_03_07_024920_create_stock_adjustments_table', 1),
(71, '2026_03_31_202723_add_opname_fields_to_stock_adjustments_table', 2),
(72, '2026_04_04_104343_add_qty_diterima_pembelian_product', 3),
(73, '2026_04_14_001118_add_type_status_to_refund_pembelians', 4),
(74, '2026_04_14_215945_add_delivery_order_id_to_refund_pembelians', 4),
(75, '2026_04_24_000001_add_deadline_fields_to_suppliers_table', 5),
(76, '2026_04_24_000002_create_product_minimum_adjustments_table', 5),
(77, '2026_04_26_104348_add_qty_sent_to_delivery_order_items', 6),
(78, '2026_04_26_111959_create_request_order_notes_table', 7),
(79, '2026_04_27_000001_add_konversi_to_products_table', 8),
(80, '2026_05_12_133816_rename_user_roles', 9),
(81, '2026_05_14_165921_add_picker_name_to_picking_lists_table', 10),
(82, '2026_05_18_100000_add_product_status_and_owner_approval_to_pembelians', 11),
(83, '2026_05_23_122321_create_jobs_table', 12),
(84, '2026_05_23_130000_create_job_batches_table', 12),
(85, '2026_05_23_130100_create_product_imports_table', 12),
(86, '2026_05_23_130200_create_product_import_failures_table', 12),
(87, '2026_07_01_094007_create_agents_table', 13),
(88, '2026_07_01_094704_create_canvases_table', 13),
(89, '2026_07_01_094731_create_branches_table', 13);

-- --------------------------------------------------------

--
-- Table structure for table `outlets`
--

CREATE TABLE `outlets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `jenis_outlet` varchar(255) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `npwp` varchar(255) DEFAULT NULL,
  `slogan` varchar(255) DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `footer` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `outlets`
--

INSERT INTO `outlets` (`id`, `logo`, `name`, `jenis_outlet`, `alamat`, `npwp`, `slogan`, `desc`, `footer`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, NULL, 'TOKO', 'TOKO', 'PACITAN', NULL, NULL, '-', NULL, '2026-04-29 04:16:42', '2026-04-29 04:16:42', NULL),
(2, NULL, 'BEAUTY', 'BEAUTY', 'PACITAN', NULL, NULL, '-', NULL, '2026-05-15 09:27:11', '2026-05-15 09:27:11', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `owner_stocks`
--

CREATE TABLE `owner_stocks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `stock_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `sku` varchar(255) DEFAULT NULL,
  `expired_at` date DEFAULT NULL,
  `harga_beli` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `bank_number` varchar(255) DEFAULT NULL,
  `desc` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pembelians`
--

CREATE TABLE `pembelians` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `code_gr` varchar(255) DEFAULT NULL,
  `outlet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `total` varchar(255) DEFAULT NULL,
  `receipt_date` datetime DEFAULT NULL,
  `receipt_pic` varchar(255) DEFAULT NULL,
  `receipt_status` enum('draft','validated','completed') DEFAULT 'draft',
  `receipt_photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `kas_id` bigint(20) UNSIGNED DEFAULT NULL,
  `uuid` char(36) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `owner_approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `owner_approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `owner_approved_at` timestamp NULL DEFAULT NULL,
  `owner_approval_note` text DEFAULT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `publisher_type` varchar(255) DEFAULT NULL,
  `publisher_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pembelians`
--

INSERT INTO `pembelians` (`id`, `code`, `code_gr`, `outlet_id`, `supplier_id`, `total`, `receipt_date`, `receipt_pic`, `receipt_status`, `receipt_photo`, `created_at`, `updated_at`, `deleted_at`, `kas_id`, `uuid`, `published_at`, `is_published`, `owner_approval_status`, `owner_approved_by`, `owner_approved_at`, `owner_approval_note`, `is_current`, `publisher_type`, `publisher_id`) VALUES
(32, 'PO00001', NULL, NULL, 1, '780000', NULL, NULL, 'draft', NULL, '2026-06-27 03:35:49', '2026-06-27 03:53:34', '2026-06-27 03:53:34', NULL, NULL, NULL, 0, 'approved', NULL, NULL, NULL, 0, NULL, NULL),
(33, 'PO00001', 'PENERIMAAN00001', NULL, 1, '780000', '2026-07-02 12:14:00', 'superadmin', 'completed', NULL, '2026-06-27 03:53:57', '2026-07-02 05:14:57', NULL, NULL, NULL, NULL, 1, 'approved', NULL, NULL, NULL, 0, NULL, NULL),
(34, 'PO00002', 'PENERIMAAN00002', NULL, 1, '1820000', '2026-07-03 11:46:00', 'superadmin', 'completed', NULL, '2026-07-03 04:43:00', '2026-07-03 04:46:38', NULL, NULL, NULL, NULL, 1, 'approved', NULL, NULL, NULL, 0, NULL, NULL),
(35, 'PO00003', 'GR-20260704-PRODUK-PO00003', NULL, 1, '1470000', '2026-07-04 09:05:00', 'superadmin', 'completed', NULL, '2026-07-04 01:37:40', '2026-07-04 02:05:33', NULL, NULL, NULL, NULL, 1, 'approved', NULL, NULL, NULL, 0, NULL, NULL),
(36, 'PO00004', NULL, NULL, 1, '1050000', NULL, NULL, 'draft', NULL, '2026-07-04 01:38:02', '2026-07-04 01:38:02', NULL, NULL, NULL, NULL, 0, 'approved', NULL, NULL, NULL, 0, NULL, NULL),
(37, 'PO00005', NULL, NULL, 1, '2240000', NULL, NULL, 'draft', NULL, '2026-07-04 01:56:43', '2026-07-04 01:56:43', NULL, NULL, NULL, NULL, 0, 'approved', NULL, NULL, NULL, 0, NULL, NULL),
(38, 'PO00006', NULL, NULL, 1, '840000', NULL, NULL, 'draft', NULL, '2026-07-04 02:30:05', '2026-07-04 02:30:05', NULL, NULL, NULL, NULL, 0, 'approved', NULL, NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pembelian_products`
--

CREATE TABLE `pembelian_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pembelian_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `harga_beli` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `qty_diterima` bigint(20) DEFAULT 0,
  `subtotal` int(11) NOT NULL,
  `expired_at` date DEFAULT NULL,
  `serial_numbers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pembelian_products`
--

INSERT INTO `pembelian_products` (`id`, `pembelian_id`, `product_id`, `harga_beli`, `qty`, `qty_diterima`, `subtotal`, `expired_at`, `serial_numbers`, `created_at`, `updated_at`, `deleted_at`) VALUES
(558, 32, 368, 6500, 120, 0, 780000, NULL, NULL, '2026-06-27 03:35:49', '2026-06-27 03:35:49', NULL),
(559, 33, 368, 6500, 120, 120, 780000, NULL, NULL, '2026-06-27 03:53:57', '2026-07-02 05:14:57', NULL),
(560, 34, 368, 7000, 260, 260, 1820000, NULL, NULL, '2026-07-03 04:43:00', '2026-07-03 04:46:38', NULL),
(561, 35, 368, 7000, 210, 210, 1470000, NULL, NULL, '2026-07-04 01:37:40', '2026-07-04 02:05:33', NULL),
(562, 36, 368, 7000, 150, 0, 1050000, NULL, NULL, '2026-07-04 01:38:02', '2026-07-04 01:38:02', NULL),
(563, 37, 368, 7000, 320, 0, 2240000, NULL, NULL, '2026-07-04 01:56:43', '2026-07-04 01:56:43', NULL),
(564, 38, 368, 7000, 120, 0, 840000, NULL, NULL, '2026-07-04 02:30:05', '2026-07-04 02:30:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pembelian_transactions`
--

CREATE TABLE `pembelian_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pembelian_id` bigint(20) UNSIGNED NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `payment_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'unpaid',
  `bukti_transfer` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pembelian_transactions`
--

INSERT INTO `pembelian_transactions` (`id`, `pembelian_id`, `payment_date`, `payment_method`, `payment_reference`, `amount`, `payment_history`, `status`, `bukti_transfer`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(28, 32, NULL, 'bank_transfer', '-', 0.00, NULL, 'unpaid', NULL, NULL, '2026-06-27 03:35:49', '2026-06-27 03:35:49', NULL),
(29, 33, '2026-06-27 11:37:00', 'cash', '3321', 780000.00, '[{\"payment_date\":\"2026-06-27T11:37\",\"amount\":\"700000\",\"payment_method\":\"cash\",\"payment_reference\":\"123\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-06-27 11:37:50\"},{\"payment_date\":\"2026-06-27T11:37\",\"amount\":\"80000\",\"payment_method\":\"cash\",\"payment_reference\":\"3321\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-06-27 11:38:25\"}]', 'paid', NULL, NULL, '2026-06-27 03:53:57', '2026-06-27 04:38:25', NULL),
(30, 34, '2026-07-03 11:45:00', 'bank_transfer', 'q312', 1820000.00, '[{\"payment_date\":\"2026-07-03T11:45\",\"amount\":\"1000000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"miun1\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-03 11:45:44\"},{\"payment_date\":\"2026-07-03T11:45\",\"amount\":\"820000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"q312\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-03 11:46:07\"}]', 'paid', NULL, NULL, '2026-07-03 04:43:00', '2026-07-03 04:46:07', NULL),
(31, 35, NULL, 'bank_transfer', '-', 0.00, NULL, 'unpaid', NULL, NULL, '2026-07-04 01:37:40', '2026-07-04 01:37:40', NULL),
(32, 36, '2026-07-04 08:42:00', 'bank_transfer', 'PAY-20260704-PRODU-9892', 600000.00, '[{\"payment_date\":\"2026-07-04T08:42\",\"amount\":\"550000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"1234\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 08:42:25\"},{\"payment_date\":\"2026-07-04T08:42\",\"amount\":\"50000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-9892\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 08:55:59\"}]', 'partial', NULL, NULL, '2026-07-04 01:38:02', '2026-07-04 01:55:59', NULL),
(33, 37, '2026-07-04 09:00:00', 'bank_transfer', 'PAY-20260704-PRODU-8266', 1160000.00, '[{\"payment_date\":\"2026-07-04T09:00\",\"amount\":\"160000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-9569\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:00:49\"},{\"payment_date\":\"2026-07-04T09:00\",\"amount\":\"1000000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-8266\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:01:04\"}]', 'partial', NULL, NULL, '2026-07-04 01:56:43', '2026-07-04 02:01:04', NULL),
(34, 38, '2026-07-04 09:33:00', 'bank_transfer', 'PAY-20260704-PRODU-2551', 840000.00, '[{\"payment_date\":\"2026-07-04T09:32\",\"amount\":\"40000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-7699\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:32:38\"},{\"payment_date\":\"2026-07-04T09:32\",\"amount\":\"200000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-2116\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:33:01\"},{\"payment_date\":\"2026-07-04T09:33\",\"amount\":\"600000\",\"payment_method\":\"bank_transfer\",\"payment_reference\":\"PAY-20260704-PRODU-2551\",\"bukti_transfer\":null,\"notes\":null,\"created_at\":\"2026-07-04 09:34:09\"}]', 'paid', NULL, NULL, '2026-07-04 02:30:05', '2026-07-04 02:34:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pengeluarans`
--

CREATE TABLE `pengeluarans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tanggal` timestamp NULL DEFAULT NULL,
  `biaya` varchar(255) DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `jumlah` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `kas_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penjualans`
--

CREATE TABLE `penjualans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `kasir_id` varchar(255) DEFAULT NULL,
  `outlet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `discount` bigint(20) DEFAULT 20,
  `total` bigint(20) DEFAULT 20,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `kas_id` bigint(20) UNSIGNED DEFAULT NULL,
  `voucher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `salesman_id` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penjualan_items`
--

CREATE TABLE `penjualan_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `penjualan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `price` bigint(20) NOT NULL,
  `subtotal` bigint(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `stock_id` bigint(20) UNSIGNED DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `picking_lists`
--

CREATE TABLE `picking_lists` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `request_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `picker_id` bigint(20) UNSIGNED DEFAULT NULL,
  `picker_name` varchar(255) DEFAULT NULL,
  `status` enum('draft','in_progress','completed') NOT NULL DEFAULT 'draft',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `picking_list_items`
--

CREATE TABLE `picking_list_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `picking_list_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `stock_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qty_to_pick` int(11) DEFAULT NULL,
  `qty_picked` int(11) DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `is_picked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `desc` text DEFAULT NULL,
  `warna` varchar(255) DEFAULT NULL,
  `ukuran` varchar(255) DEFAULT NULL,
  `outlet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `satuan` varchar(255) DEFAULT NULL,
  `satuan_besar` varchar(255) DEFAULT NULL,
  `konversi_qty` mediumint(255) DEFAULT NULL,
  `satuan_terbesar` varchar(255) DEFAULT NULL,
  `konversi_qty_terbesar` mediumint(9) DEFAULT NULL,
  `brand` varchar(244) DEFAULT NULL,
  `model` varchar(255) DEFAULT NULL,
  `is_serialized` tinyint(1) NOT NULL DEFAULT 0,
  `harga_beli` int(11) DEFAULT NULL,
  `harga_jual` int(11) DEFAULT NULL,
  `min_stock` int(11) NOT NULL DEFAULT 0,
  `lokasi` varchar(255) DEFAULT NULL,
  `status_produk` varchar(255) NOT NULL DEFAULT 'sudah',
  `status_produk_note` varchar(255) DEFAULT NULL,
  `stock_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `diskon` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `pic`, `code`, `name`, `category_id`, `desc`, `warna`, `ukuran`, `outlet_id`, `satuan`, `satuan_besar`, `konversi_qty`, `satuan_terbesar`, `konversi_qty_terbesar`, `brand`, `model`, `is_serialized`, `harga_beli`, `harga_jual`, `min_stock`, `lokasi`, `status_produk`, `status_produk_note`, `stock_value`, `diskon`, `created_at`, `updated_at`, `deleted_at`) VALUES
(368, NULL, 'A0001', 'ALAMI', 1, NULL, NULL, NULL, NULL, 'Pack', 'SLOP', 10, 'Ball', 20, NULL, NULL, 0, 7000, NULL, 0, NULL, 'sudah', NULL, 4130000.00, NULL, '2026-06-27 03:14:12', '2026-07-04 02:05:33', NULL),
(369, NULL, 'Necessitatibus hic e', 'Forrest Richards', 2, NULL, NULL, NULL, NULL, 'Est quidem nemo pari', 'Quia alias ducimus', 252, NULL, NULL, NULL, NULL, 0, 0, NULL, 0, NULL, 'sudah', NULL, 0.00, NULL, '2026-07-02 07:21:13', '2026-07-02 07:21:41', '2026-07-02 07:21:41'),
(370, NULL, 'Quam sunt totam anim', 'Julian Maxwell', 2, NULL, NULL, NULL, NULL, 'Ut nobis voluptates', 'Aut culpa autem anim', 16, 'jlds', 10, NULL, NULL, 0, 0, NULL, 0, NULL, 'sudah', NULL, 0.00, NULL, '2026-07-02 07:50:31', '2026-07-02 08:27:51', NULL),
(371, NULL, 'Aspernatur eiusmod p', 'Karyn Campos', 2, NULL, NULL, NULL, NULL, 'Molestias accusantiu', 'Vel expedita eos po', 390, 'ball', 497, NULL, NULL, 0, 0, NULL, 0, NULL, 'sudah', NULL, 0.00, NULL, '2026-07-02 08:49:54', '2026-07-02 08:50:28', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_imports`
--

CREATE TABLE `product_imports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` varchar(255) DEFAULT NULL,
  `original_file_name` varchar(255) NOT NULL,
  `stored_file_path` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'queued',
  `total_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `chunk_size` int(10) UNSIGNED NOT NULL DEFAULT 100,
  `total_chunks` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `processed_chunks` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `processed_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `successful_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `failed_rows` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `requested_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_import_failures`
--

CREATE TABLE `product_import_failures` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_import_id` bigint(20) UNSIGNED NOT NULL,
  `row_number` int(10) UNSIGNED NOT NULL,
  `product_code` varchar(255) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `row_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`row_data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_minimum_adjustments`
--

CREATE TABLE `product_minimum_adjustments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `adjustment_percentage` tinyint(3) UNSIGNED NOT NULL,
  `active_from` date NOT NULL,
  `active_until` date DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_supplier`
--

CREATE TABLE `product_supplier` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_supplier`
--

INSERT INTO `product_supplier` (`id`, `product_id`, `supplier_id`, `created_at`, `updated_at`) VALUES
(391, 368, 1, NULL, NULL),
(392, 369, 1, NULL, NULL),
(393, 370, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `penjualan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `outlet_id` varchar(255) DEFAULT NULL,
  `tanggal` timestamp NULL DEFAULT NULL,
  `total` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `kas_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refund_items`
--

CREATE TABLE `refund_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `refund_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `alasan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refund_pembelians`
--

CREATE TABLE `refund_pembelians` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `pembelian_id` bigint(20) UNSIGNED DEFAULT NULL,
  `outlet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `tanggal` timestamp NULL DEFAULT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'gudang_ke_supplier',
  `status` varchar(255) NOT NULL DEFAULT 'retur',
  `total` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `kas_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refund_pembelian_items`
--

CREATE TABLE `refund_pembelian_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `refund_pembelian_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `stock_pembelian_id` bigint(20) UNSIGNED DEFAULT NULL,
  `stock_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `alasan` text DEFAULT NULL,
  `resolution` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_orders`
--

CREATE TABLE `request_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL,
  `requested_by` bigint(20) UNSIGNED DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `request_date` date DEFAULT NULL,
  `verified_date` date DEFAULT NULL,
  `status` enum('pending','approved','partial','rejected') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `verification_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_order_items`
--

CREATE TABLE `request_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `stock_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qty_requested` int(11) DEFAULT NULL,
  `qty_approved` int(11) DEFAULT 0,
  `qty_difference` int(11) GENERATED ALWAYS AS (`qty_requested` - `qty_approved`) STORED,
  `item_status` enum('pending','approved','partial','rejected') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_order_notes`
--

CREATE TABLE `request_order_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `request_order_id` bigint(20) UNSIGNED NOT NULL,
  `kategori` varchar(255) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `nama_pj` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `comment` text NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salesmans`
--

CREATE TABLE `salesmans` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_telp` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active','non-active') DEFAULT 'non-active',
  `type` enum('default','link') DEFAULT 'default',
  `desc` text DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stocks`
--

CREATE TABLE `stocks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `harga_beli` int(11) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `qty_reserved` int(11) DEFAULT 0,
  `expired_at` timestamp NULL DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `batch_number` varchar(255) DEFAULT NULL,
  `imei` varchar(255) DEFAULT NULL,
  `condition` enum('new','used','refurbished') NOT NULL DEFAULT 'new',
  `location` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'free',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pembelian_id` bigint(20) UNSIGNED DEFAULT NULL,
  `subtotal` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `qty_available` int(11) GENERATED ALWAYS AS (`qty` - `qty_reserved`) STORED,
  `stock_status` enum('available','reserved','damaged','expired') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stocks`
--

INSERT INTO `stocks` (`id`, `product_id`, `sku`, `harga_beli`, `qty`, `qty_reserved`, `expired_at`, `serial_number`, `batch_number`, `imei`, `condition`, `location`, `status`, `created_at`, `updated_at`, `pembelian_id`, `subtotal`, `deleted_at`, `stock_status`) VALUES
(321, 368, NULL, 6500, 120, 0, NULL, NULL, NULL, NULL, 'new', NULL, 'available', '2026-07-02 05:14:57', '2026-07-02 05:14:57', 33, '780000', NULL, 'available'),
(322, 368, NULL, 7000, 260, 0, NULL, NULL, NULL, NULL, 'new', NULL, 'available', '2026-07-03 04:46:38', '2026-07-04 01:19:19', 34, '1820000', NULL, 'available'),
(323, 368, NULL, 7000, 210, 0, NULL, NULL, NULL, NULL, 'new', NULL, 'available', '2026-07-04 02:05:33', '2026-07-04 02:05:33', 35, '1470000', NULL, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `stock_adjustments`
--

CREATE TABLE `stock_adjustments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `adjustment_date` date NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `stock_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `system_qty` decimal(10,2) DEFAULT NULL,
  `physical_qty` decimal(10,2) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'Selesai',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_adjustments`
--

INSERT INTO `stock_adjustments` (`id`, `adjustment_date`, `product_id`, `stock_id`, `sku`, `quantity`, `system_qty`, `physical_qty`, `keterangan`, `reason`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '2026-07-03', 368, 322, NULL, -10.00, 380.00, 370.00, NULL, NULL, 'Selesai', '2026-07-03 07:28:22', '2026-07-03 07:28:22', NULL),
(2, '2026-07-04', 368, 322, NULL, 10.00, 370.00, 380.00, NULL, NULL, 'Selesai', '2026-07-04 01:19:19', '2026-07-04 01:19:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` enum('in','out','adjustment','reserved','unreserved') DEFAULT NULL,
  `reference_type` varchar(255) DEFAULT NULL,
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qty_in` int(11) NOT NULL DEFAULT 0,
  `qty_out` int(11) NOT NULL DEFAULT 0,
  `balance` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `user_id`, `type`, `reference_type`, `reference_id`, `qty_in`, `qty_out`, `balance`, `notes`, `created_at`, `updated_at`) VALUES
(321, 368, 1, 'in', 'App\\Models\\Pembelian', 33, 120, 0, 120, 'Goods receipt from Produksi', '2026-07-02 05:14:57', '2026-07-02 05:14:57'),
(322, 368, 1, 'in', 'App\\Models\\Pembelian', 34, 260, 0, 380, 'Goods receipt from Produksi', '2026-07-03 04:46:38', '2026-07-03 04:46:38'),
(323, 368, 1, 'adjustment', 'App\\Models\\StockAdjustment', 1, 0, 10, 370, 'Stock opname adjustment - Stock adjustment', '2026-07-03 07:28:22', '2026-07-03 07:28:22'),
(324, 368, 1, 'adjustment', 'App\\Models\\StockAdjustment', 2, 10, 0, 380, 'Stock opname adjustment - Stock adjustment', '2026-07-04 01:19:19', '2026-07-04 01:19:19'),
(325, 368, 1, 'in', 'App\\Models\\Pembelian', 35, 210, 0, 590, 'Goods receipt from Produksi', '2026-07-04 02:05:33', '2026-07-04 02:05:33');

-- --------------------------------------------------------

--
-- Table structure for table `stock_pembelians`
--

CREATE TABLE `stock_pembelians` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pembelian_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `harga_beli` int(11) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `subtotal` int(11) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `imei` varchar(255) DEFAULT NULL,
  `condition` enum('new','used','refurbished') NOT NULL DEFAULT 'new',
  `status` enum('available','sent_to_outlet','reserved') NOT NULL DEFAULT 'available',
  `expired_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_pembelians`
--

INSERT INTO `stock_pembelians` (`id`, `pembelian_id`, `product_id`, `sku`, `harga_beli`, `qty`, `subtotal`, `serial_number`, `imei`, `condition`, `status`, `expired_at`, `created_at`, `updated_at`, `deleted_at`) VALUES
(554, 32, 368, NULL, 6500, 120, 780000, NULL, NULL, 'new', 'available', NULL, '2026-06-27 03:35:49', '2026-06-27 03:35:49', NULL),
(555, 33, 368, NULL, 6500, 0, 780000, NULL, NULL, 'new', 'available', NULL, '2026-06-27 03:53:57', '2026-07-02 05:14:57', '2026-07-02 05:14:57'),
(556, 34, 368, NULL, 7000, 0, 1820000, NULL, NULL, 'new', 'available', NULL, '2026-07-03 04:43:00', '2026-07-03 04:46:38', '2026-07-03 04:46:38'),
(557, 35, 368, NULL, 7000, 0, 1470000, NULL, NULL, 'new', 'available', NULL, '2026-07-04 01:37:40', '2026-07-04 02:05:33', '2026-07-04 02:05:33'),
(558, 36, 368, NULL, 7000, 150, 1050000, NULL, NULL, 'new', 'available', NULL, '2026-07-04 01:38:02', '2026-07-04 01:38:02', NULL),
(559, 37, 368, NULL, 7000, 320, 2240000, NULL, NULL, 'new', 'available', NULL, '2026-07-04 01:56:43', '2026-07-04 01:56:43', NULL),
(560, 38, 368, NULL, 7000, 120, 840000, NULL, NULL, 'new', 'available', NULL, '2026-07-04 02:30:05', '2026-07-04 02:30:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `kode_supplier` varchar(255) DEFAULT NULL,
  `pic_supplier` varchar(255) DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_telp` varchar(255) DEFAULT NULL,
  `deadline_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`deadline_days`)),
  `deadline_interval_weeks` tinyint(3) UNSIGNED DEFAULT NULL,
  `deadline_reference_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `kode_supplier`, `pic_supplier`, `alamat`, `no_telp`, `deadline_days`, `deadline_interval_weeks`, `deadline_reference_date`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Produksi', 'S00001', NULL, 'Pacitan', '0891234532', NULL, NULL, '2026-06-22', '2026-05-22 21:26:41', '2026-06-27 02:42:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tanggal` timestamp NULL DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `penjualan_id` bigint(20) UNSIGNED DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `role` varchar(255) DEFAULT 'customer',
  `status` varchar(255) DEFAULT 'active',
  `email` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `no_telp` varchar(255) DEFAULT NULL,
  `outlet_id` bigint(20) UNSIGNED DEFAULT NULL,
  `limit_discount` bigint(20) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `role`, `status`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `deleted_at`, `alamat`, `no_telp`, `outlet_id`, `limit_discount`) VALUES
(1, 'superadmin', 'superadmin@gmail.com', 'superadmin', 'active', 'superadmin@gmail.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ZVdnkIwqJ3DkmJh3cNgWNHNzc8li5RPWAcY3e9PCrBBnx2RSDP7IzMfsvH3O', '2026-03-28 08:28:40', '2026-03-28 08:28:40', NULL, 'magelang', '+620000000003', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_cart`
--

CREATE TABLE `user_cart` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qty` int(10) UNSIGNED NOT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `stock_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_wishlist`
--

CREATE TABLE `user_wishlist` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `outlet_id` varchar(255) DEFAULT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `qty` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `stock_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(255) DEFAULT NULL,
  `type` enum('nominal','percentage') DEFAULT NULL,
  `limit` int(11) DEFAULT NULL,
  `value` int(11) DEFAULT NULL,
  `min_purchase` int(11) DEFAULT NULL,
  `start_at` datetime DEFAULT NULL,
  `end_at` datetime DEFAULT NULL,
  `desc` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `jenis` enum('satuan','keseluruhan') DEFAULT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `kasir_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject` (`subject_type`,`subject_id`),
  ADD KEY `causer` (`causer_type`,`causer_id`),
  ADD KEY `activity_log_log_name_index` (`log_name`);

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `banks`
--
ALTER TABLE `banks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `canvases`
--
ALTER TABLE `canvases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart_storage`
--
ALTER TABLE `cart_storage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_storage_id_index` (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categories_outlet_id_foreign` (`outlet_id`);

--
-- Indexes for table `delivery_orders`
--
ALTER TABLE `delivery_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_orders_code_unique` (`code`),
  ADD KEY `delivery_orders_request_order_id_foreign` (`request_order_id`),
  ADD KEY `delivery_orders_picking_list_id_foreign` (`picking_list_id`),
  ADD KEY `delivery_orders_owner_id_foreign` (`owner_id`),
  ADD KEY `delivery_orders_prepared_by_foreign` (`prepared_by`),
  ADD KEY `delivery_orders_received_by_foreign` (`received_by`);

--
-- Indexes for table `delivery_order_items`
--
ALTER TABLE `delivery_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_order_items_delivery_order_id_foreign` (`delivery_order_id`),
  ADD KEY `delivery_order_items_product_id_foreign` (`product_id`),
  ADD KEY `delivery_order_items_stock_id_foreign` (`stock_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

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
-- Indexes for table `kas`
--
ALTER TABLE `kas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kas_outlet_id_foreign` (`outlet_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `outlets`
--
ALTER TABLE `outlets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `owner_stocks`
--
ALTER TABLE `owner_stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `owner_stocks_owner_id_product_id_batch_number_unique` (`owner_id`,`product_id`,`sku`),
  ADD KEY `owner_stocks_product_id_foreign` (`product_id`),
  ADD KEY `owner_stocks_stock_id_foreign` (`stock_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pembelians`
--
ALTER TABLE `pembelians`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pembelians_outlet_id_foreign` (`outlet_id`),
  ADD KEY `pembelians_supplier_id_foreign` (`supplier_id`),
  ADD KEY `pembelians_kas_id_foreign` (`kas_id`),
  ADD KEY `pembelians_publisher_type_publisher_id_index` (`publisher_type`,`publisher_id`),
  ADD KEY `pembelians_uuid_is_published_is_current_index` (`uuid`,`is_published`,`is_current`),
  ADD KEY `pembelians_owner_approved_by_foreign` (`owner_approved_by`);

--
-- Indexes for table `pembelian_products`
--
ALTER TABLE `pembelian_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pembelian_products_pembelian_id_foreign` (`pembelian_id`),
  ADD KEY `pembelian_products_product_id_foreign` (`product_id`);

--
-- Indexes for table `pembelian_transactions`
--
ALTER TABLE `pembelian_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pembelian_transactions_pembelian_id_foreign` (`pembelian_id`);

--
-- Indexes for table `pengeluarans`
--
ALTER TABLE `pengeluarans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengeluarans_category_id_foreign` (`category_id`),
  ADD KEY `pengeluarans_kas_id_foreign` (`kas_id`);

--
-- Indexes for table `penjualans`
--
ALTER TABLE `penjualans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `penjualans_outlet_id_foreign` (`outlet_id`),
  ADD KEY `penjualans_kas_id_foreign` (`kas_id`),
  ADD KEY `penjualans_voucher_id_foreign` (`voucher_id`);

--
-- Indexes for table `penjualan_items`
--
ALTER TABLE `penjualan_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `penjualan_items_penjualan_id_foreign` (`penjualan_id`),
  ADD KEY `penjualan_items_product_id_foreign` (`product_id`),
  ADD KEY `penjualan_items_stock_id_foreign` (`stock_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `picking_lists`
--
ALTER TABLE `picking_lists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `picking_lists_code_unique` (`code`),
  ADD KEY `picking_lists_request_order_id_foreign` (`request_order_id`),
  ADD KEY `picking_lists_picker_id_foreign` (`picker_id`);

--
-- Indexes for table `picking_list_items`
--
ALTER TABLE `picking_list_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `picking_list_items_picking_list_id_foreign` (`picking_list_id`),
  ADD KEY `picking_list_items_product_id_foreign` (`product_id`),
  ADD KEY `picking_list_items_stock_id_foreign` (`stock_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_category_id_foreign` (`category_id`),
  ADD KEY `products_outlet_id_foreign` (`outlet_id`);

--
-- Indexes for table `product_imports`
--
ALTER TABLE `product_imports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_imports_requested_by_foreign` (`requested_by`),
  ADD KEY `product_imports_batch_id_index` (`batch_id`);

--
-- Indexes for table `product_import_failures`
--
ALTER TABLE `product_import_failures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_import_failures_product_import_id_foreign` (`product_import_id`);

--
-- Indexes for table `product_minimum_adjustments`
--
ALTER TABLE `product_minimum_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_minimum_adjustments_created_by_foreign` (`created_by`),
  ADD KEY `idx_prod_adj_dates` (`product_id`,`active_from`,`active_until`);

--
-- Indexes for table `product_supplier`
--
ALTER TABLE `product_supplier`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_supplier_product_id_supplier_id_unique` (`product_id`,`supplier_id`),
  ADD KEY `product_supplier_supplier_id_foreign` (`supplier_id`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `refunds_penjualan_id_foreign` (`penjualan_id`),
  ADD KEY `refunds_user_id_foreign` (`user_id`),
  ADD KEY `refunds_kas_id_foreign` (`kas_id`);

--
-- Indexes for table `refund_items`
--
ALTER TABLE `refund_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `refund_items_refund_id_foreign` (`refund_id`),
  ADD KEY `refund_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `refund_pembelians`
--
ALTER TABLE `refund_pembelians`
  ADD PRIMARY KEY (`id`),
  ADD KEY `refund_pembelians_pembelian_id_foreign` (`pembelian_id`),
  ADD KEY `refund_pembelians_outlet_id_foreign` (`outlet_id`),
  ADD KEY `refund_pembelians_user_id_foreign` (`user_id`),
  ADD KEY `refund_pembelians_supplier_id_foreign` (`supplier_id`),
  ADD KEY `refund_pembelians_kas_id_foreign` (`kas_id`),
  ADD KEY `refund_pembelians_delivery_order_id_foreign` (`delivery_order_id`);

--
-- Indexes for table `refund_pembelian_items`
--
ALTER TABLE `refund_pembelian_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `refund_pembelian_items_refund_pembelian_id_foreign` (`refund_pembelian_id`),
  ADD KEY `refund_pembelian_items_product_id_foreign` (`product_id`);

--
-- Indexes for table `request_orders`
--
ALTER TABLE `request_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_orders_code_unique` (`code`),
  ADD KEY `request_orders_owner_id_foreign` (`owner_id`),
  ADD KEY `request_orders_requested_by_foreign` (`requested_by`),
  ADD KEY `request_orders_verified_by_foreign` (`verified_by`);

--
-- Indexes for table `request_order_items`
--
ALTER TABLE `request_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_order_items_request_order_id_foreign` (`request_order_id`),
  ADD KEY `request_order_items_product_id_foreign` (`product_id`),
  ADD KEY `request_order_items_stock_id_foreign` (`stock_id`);

--
-- Indexes for table `request_order_notes`
--
ALTER TABLE `request_order_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_order_notes_request_order_id_foreign` (`request_order_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviews_product_id_foreign` (`product_id`),
  ADD KEY `reviews_user_id_foreign` (`user_id`);

--
-- Indexes for table `salesmans`
--
ALTER TABLE `salesmans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stocks_pembelian_id_foreign` (`pembelian_id`),
  ADD KEY `stocks_product_id_sku_index` (`product_id`,`sku`);

--
-- Indexes for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_adjustments_product_id_foreign` (`product_id`),
  ADD KEY `stock_adjustments_stock_id_foreign` (`stock_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_movements_product_id_foreign` (`product_id`),
  ADD KEY `stock_movements_user_id_foreign` (`user_id`);

--
-- Indexes for table `stock_pembelians`
--
ALTER TABLE `stock_pembelians`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_pembelians_pembelian_id_foreign` (`pembelian_id`),
  ADD KEY `stock_pembelians_product_id_foreign` (`product_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transactions_penjualan_id_foreign` (`penjualan_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_outlet_id_foreign` (`outlet_id`);

--
-- Indexes for table `user_cart`
--
ALTER TABLE `user_cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_cart_user_id_foreign` (`user_id`),
  ADD KEY `user_cart_product_id_foreign` (`product_id`),
  ADD KEY `user_cart_stock_id_foreign` (`stock_id`);

--
-- Indexes for table `user_wishlist`
--
ALTER TABLE `user_wishlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_wishlist_user_id_foreign` (`user_id`),
  ADD KEY `user_wishlist_product_id_foreign` (`product_id`),
  ADD KEY `user_wishlist_stock_id_foreign` (`stock_id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vouchers_code_unique` (`code`),
  ADD KEY `vouchers_product_id_foreign` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12033;

--
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `banks`
--
ALTER TABLE `banks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `canvases`
--
ALTER TABLE `canvases`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `delivery_orders`
--
ALTER TABLE `delivery_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_order_items`
--
ALTER TABLE `delivery_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1720;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1725;

--
-- AUTO_INCREMENT for table `kas`
--
ALTER TABLE `kas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `outlets`
--
ALTER TABLE `outlets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `owner_stocks`
--
ALTER TABLE `owner_stocks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pembelians`
--
ALTER TABLE `pembelians`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `pembelian_products`
--
ALTER TABLE `pembelian_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=565;

--
-- AUTO_INCREMENT for table `pembelian_transactions`
--
ALTER TABLE `pembelian_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `pengeluarans`
--
ALTER TABLE `pengeluarans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penjualans`
--
ALTER TABLE `penjualans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penjualan_items`
--
ALTER TABLE `penjualan_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `picking_lists`
--
ALTER TABLE `picking_lists`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `picking_list_items`
--
ALTER TABLE `picking_list_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=372;

--
-- AUTO_INCREMENT for table `product_imports`
--
ALTER TABLE `product_imports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `product_import_failures`
--
ALTER TABLE `product_import_failures`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `product_minimum_adjustments`
--
ALTER TABLE `product_minimum_adjustments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_supplier`
--
ALTER TABLE `product_supplier`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=394;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refund_items`
--
ALTER TABLE `refund_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refund_pembelians`
--
ALTER TABLE `refund_pembelians`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `refund_pembelian_items`
--
ALTER TABLE `refund_pembelian_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_orders`
--
ALTER TABLE `request_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_order_items`
--
ALTER TABLE `request_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_order_notes`
--
ALTER TABLE `request_order_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salesmans`
--
ALTER TABLE `salesmans`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=324;

--
-- AUTO_INCREMENT for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=326;

--
-- AUTO_INCREMENT for table `stock_pembelians`
--
ALTER TABLE `stock_pembelians`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=561;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=938;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `user_cart`
--
ALTER TABLE `user_cart`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_wishlist`
--
ALTER TABLE `user_wishlist`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vouchers`
--
ALTER TABLE `vouchers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_orders`
--
ALTER TABLE `delivery_orders`
  ADD CONSTRAINT `delivery_orders_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `outlets` (`id`),
  ADD CONSTRAINT `delivery_orders_picking_list_id_foreign` FOREIGN KEY (`picking_list_id`) REFERENCES `picking_lists` (`id`),
  ADD CONSTRAINT `delivery_orders_prepared_by_foreign` FOREIGN KEY (`prepared_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `delivery_orders_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `delivery_orders_request_order_id_foreign` FOREIGN KEY (`request_order_id`) REFERENCES `request_orders` (`id`);

--
-- Constraints for table `delivery_order_items`
--
ALTER TABLE `delivery_order_items`
  ADD CONSTRAINT `delivery_order_items_delivery_order_id_foreign` FOREIGN KEY (`delivery_order_id`) REFERENCES `delivery_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `delivery_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `delivery_order_items_stock_id_foreign` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`);

--
-- Constraints for table `kas`
--
ALTER TABLE `kas`
  ADD CONSTRAINT `kas_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `owner_stocks`
--
ALTER TABLE `owner_stocks`
  ADD CONSTRAINT `owner_stocks_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `outlets` (`id`),
  ADD CONSTRAINT `owner_stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `owner_stocks_stock_id_foreign` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`);

--
-- Constraints for table `pembelians`
--
ALTER TABLE `pembelians`
  ADD CONSTRAINT `pembelians_kas_id_foreign` FOREIGN KEY (`kas_id`) REFERENCES `kas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pembelians_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pembelians_owner_approved_by_foreign` FOREIGN KEY (`owner_approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pembelians_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pembelian_products`
--
ALTER TABLE `pembelian_products`
  ADD CONSTRAINT `pembelian_products_pembelian_id_foreign` FOREIGN KEY (`pembelian_id`) REFERENCES `pembelians` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pembelian_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pembelian_transactions`
--
ALTER TABLE `pembelian_transactions`
  ADD CONSTRAINT `pembelian_transactions_pembelian_id_foreign` FOREIGN KEY (`pembelian_id`) REFERENCES `pembelians` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengeluarans`
--
ALTER TABLE `pengeluarans`
  ADD CONSTRAINT `pengeluarans_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengeluarans_kas_id_foreign` FOREIGN KEY (`kas_id`) REFERENCES `kas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penjualans`
--
ALTER TABLE `penjualans`
  ADD CONSTRAINT `penjualans_kas_id_foreign` FOREIGN KEY (`kas_id`) REFERENCES `kas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penjualans_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penjualans_voucher_id_foreign` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penjualan_items`
--
ALTER TABLE `penjualan_items`
  ADD CONSTRAINT `penjualan_items_penjualan_id_foreign` FOREIGN KEY (`penjualan_id`) REFERENCES `penjualans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penjualan_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penjualan_items_stock_id_foreign` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `picking_lists`
--
ALTER TABLE `picking_lists`
  ADD CONSTRAINT `picking_lists_picker_id_foreign` FOREIGN KEY (`picker_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `picking_lists_request_order_id_foreign` FOREIGN KEY (`request_order_id`) REFERENCES `request_orders` (`id`);

--
-- Constraints for table `picking_list_items`
--
ALTER TABLE `picking_list_items`
  ADD CONSTRAINT `picking_list_items_picking_list_id_foreign` FOREIGN KEY (`picking_list_id`) REFERENCES `picking_lists` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `picking_list_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `picking_list_items_stock_id_foreign` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_imports`
--
ALTER TABLE `product_imports`
  ADD CONSTRAINT `product_imports_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_import_failures`
--
ALTER TABLE `product_import_failures`
  ADD CONSTRAINT `product_import_failures_product_import_id_foreign` FOREIGN KEY (`product_import_id`) REFERENCES `product_imports` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_minimum_adjustments`
--
ALTER TABLE `product_minimum_adjustments`
  ADD CONSTRAINT `product_minimum_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `product_minimum_adjustments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_supplier`
--
ALTER TABLE `product_supplier`
  ADD CONSTRAINT `product_supplier_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_supplier_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refunds`
--
ALTER TABLE `refunds`
  ADD CONSTRAINT `refunds_kas_id_foreign` FOREIGN KEY (`kas_id`) REFERENCES `kas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refunds_penjualan_id_foreign` FOREIGN KEY (`penjualan_id`) REFERENCES `penjualans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refunds_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refund_items`
--
ALTER TABLE `refund_items`
  ADD CONSTRAINT `refund_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refund_items_refund_id_foreign` FOREIGN KEY (`refund_id`) REFERENCES `refunds` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refund_pembelians`
--
ALTER TABLE `refund_pembelians`
  ADD CONSTRAINT `refund_pembelians_delivery_order_id_foreign` FOREIGN KEY (`delivery_order_id`) REFERENCES `delivery_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `refund_pembelians_kas_id_foreign` FOREIGN KEY (`kas_id`) REFERENCES `kas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refund_pembelians_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refund_pembelians_pembelian_id_foreign` FOREIGN KEY (`pembelian_id`) REFERENCES `pembelians` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refund_pembelians_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refund_pembelians_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refund_pembelian_items`
--
ALTER TABLE `refund_pembelian_items`
  ADD CONSTRAINT `refund_pembelian_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `refund_pembelian_items_refund_pembelian_id_foreign` FOREIGN KEY (`refund_pembelian_id`) REFERENCES `refund_pembelians` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_orders`
--
ALTER TABLE `request_orders`
  ADD CONSTRAINT `request_orders_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `outlets` (`id`),
  ADD CONSTRAINT `request_orders_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `request_orders_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `request_order_items`
--
ALTER TABLE `request_order_items`
  ADD CONSTRAINT `request_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `request_order_items_request_order_id_foreign` FOREIGN KEY (`request_order_id`) REFERENCES `request_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `request_order_items_stock_id_foreign` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`);

--
-- Constraints for table `request_order_notes`
--
ALTER TABLE `request_order_notes`
  ADD CONSTRAINT `request_order_notes_request_order_id_foreign` FOREIGN KEY (`request_order_id`) REFERENCES `request_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stocks`
--
ALTER TABLE `stocks`
  ADD CONSTRAINT `stocks_pembelian_id_foreign` FOREIGN KEY (`pembelian_id`) REFERENCES `pembelians` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stocks_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_adjustments`
--
ALTER TABLE `stock_adjustments`
  ADD CONSTRAINT `stock_adjustments_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_adjustments_stock_id_foreign` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `stock_pembelians`
--
ALTER TABLE `stock_pembelians`
  ADD CONSTRAINT `stock_pembelians_pembelian_id_foreign` FOREIGN KEY (`pembelian_id`) REFERENCES `pembelians` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_pembelians_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_penjualan_id_foreign` FOREIGN KEY (`penjualan_id`) REFERENCES `penjualans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_outlet_id_foreign` FOREIGN KEY (`outlet_id`) REFERENCES `outlets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_cart`
--
ALTER TABLE `user_cart`
  ADD CONSTRAINT `user_cart_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_cart_stock_id_foreign` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_cart_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_wishlist`
--
ALTER TABLE `user_wishlist`
  ADD CONSTRAINT `user_wishlist_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_wishlist_stock_id_foreign` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_wishlist_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD CONSTRAINT `vouchers_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
