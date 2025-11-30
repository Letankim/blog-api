-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2025 at 11:51 PM
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
-- Database: `personal_blog_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `activation_keys`
--

CREATE TABLE `activation_keys` (
  `id` int(11) NOT NULL,
  `key_value` varchar(32) NOT NULL,
  `status` enum('used','unused') NOT NULL DEFAULT 'unused',
  `email` varchar(255) DEFAULT NULL,
  `buyer` varchar(500) NOT NULL DEFAULT 'system',
  `user_info` varchar(500) DEFAULT NULL,
  `active` int(11) NOT NULL DEFAULT 1,
  `device_id` varchar(400) DEFAULT NULL,
  `app_name` varchar(255) DEFAULT NULL,
  `usage_count` int(11) NOT NULL DEFAULT 0,
  `note` text DEFAULT NULL,
  `type` enum('bán lẻ','bán sỉ','khác','rush') NOT NULL DEFAULT 'khác',
  `logger` text DEFAULT NULL,
  `reason_for_reset` text DEFAULT NULL,
  `number_of_resets` int(11) NOT NULL DEFAULT 0,
  `account_history` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `used_at` timestamp NULL DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `expiration_time` timestamp NULL DEFAULT NULL,
  `order_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activation_keys`
--

INSERT INTO `activation_keys` (`id`, `key_value`, `status`, `email`, `buyer`, `user_info`, `active`, `device_id`, `app_name`, `usage_count`, `note`, `type`, `logger`, `reason_for_reset`, `number_of_resets`, `account_history`, `created_at`, `updated_at`, `used_at`, `start_time`, `expiration_time`, `order_id`) VALUES
(1, 'WIN11-PRO-ABCDE-FGHIJ-KLMNO-PQRS', 'used', 'letankim2003@gmail.com', 'Lê Tấn Kim', '{\"ip\":\"127.0.0.1\",\"browser\":\"Chrome\"}', 1, 'DEV-12345-ABCDE', 'Windows 11', 1, 'Kích hoạt thành công', 'bán lẻ', '{\"activated_at\":\"2025-10-31 11:35:00\"}', NULL, 0, NULL, '2025-10-31 04:30:00', '2025-10-31 04:35:00', '2025-10-31 04:35:00', NULL, NULL, 'order001'),
(2, 'WIN11-PRO-12345-67890-ABCDE-FGHI', 'unused', NULL, 'system', NULL, 1, NULL, NULL, 0, 'Sẵn sàng bán', 'bán sỉ', NULL, NULL, 0, NULL, '2025-10-31 02:00:00', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` char(36) NOT NULL,
  `post_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `content` text NOT NULL,
  `parent_id` char(36) DEFAULT NULL,
  `status` enum('approved','pending','rejected','banned') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `post_id`, `user_id`, `content`, `parent_id`, `status`, `created_at`, `updated_at`) VALUES
('cmt001', 'post001', 'b2c3d4e5-f6g7-8901-bcde-f23456789012', 'Bài viết rất hay, cảm ơn tác giả!', NULL, 'approved', '2025-10-31 04:00:00', NULL),
('cmt002', 'post001', '88041f44-b641-11f0-b120-040e3c9ccf10', 'Cảm ơn bạn đã góp ý!', 'cmt001', 'approved', '2025-10-31 04:05:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
  `id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_link` varchar(500) DEFAULT NULL,
  `user_id` char(36) NOT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`id`, `title`, `description`, `file_link`, `user_id`, `seo_keywords`, `created_at`, `updated_at`) VALUES
('mat001', 'Tài liệu Laravel 11 PDF', 'Tài liệu hướng dẫn Laravel 11 chi tiết 200 trang', 'https://example.com/docs/laravel11.pdf', 'a1b2c3d4-e5f6-7890-abcd-ef1234567890', 'laravel, pdf, tài liệu', '2025-10-31 03:15:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `material_images`
--

CREATE TABLE `material_images` (
  `id` char(36) NOT NULL,
  `material_id` char(36) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `material_images`
--

INSERT INTO `material_images` (`id`, `material_id`, `image_url`, `alt_text`, `is_primary`, `created_at`) VALUES
('mimg001', 'mat001', 'https://example.com/materials/laravel-pdf.jpg', 'Tài liệu Laravel PDF', 1, '2025-10-31 03:16:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order','comment','review','system','promotion','key') NOT NULL DEFAULT 'system',
  `related_id` char(36) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `related_id`, `is_read`, `created_at`) VALUES
('notif001', '88041f44-b641-11f0-b120-040e3c9ccf10', 'Đơn hàng đã hoàn tất', 'Đơn hàng ORD001 của bạn đã được xác nhận và key đã gửi.', 'order', 'order001', 1, '2025-10-31 04:36:00'),
('notif002', 'b2c3d4e5-f6g7-8901-bcde-f23456789012', 'Bình luận mới', 'Bạn có bình luận mới trên bài viết.', 'comment', 'cmt001', 0, '2025-10-31 04:06:00');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `order_code` char(6) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `voucher_id` char(36) DEFAULT NULL,
  `status` enum('pending','completed','cancelled','failed') NOT NULL DEFAULT 'pending',
  `payment_method` enum('credit_card','paypal','bank_transfer','cash_on_delivery') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_code`, `total_price`, `voucher_id`, `status`, `payment_method`, `created_at`) VALUES
('4504df3c-908b-4403-a380-79709e9387b6', '88041f44-b641-11f0-b120-040e3c9ccf10', '369238', 3097000.00, NULL, 'cancelled', 'credit_card', '2025-10-31 15:46:29'),
('order001', '88041f44-b641-11f0-b120-040e3c9ccf10', 'ORD001', 799000.00, 'vouch001', 'completed', 'bank_transfer', '2025-10-31 04:30:00'),
('order002', 'b2c3d4e5-f6g7-8901-bcde-f23456789012', 'ORD002', 1299000.00, NULL, 'pending', 'cash_on_delivery', '2025-10-31 05:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` char(36) NOT NULL,
  `order_id` char(36) NOT NULL,
  `product_id` char(36) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_at_purchase` decimal(10,2) NOT NULL,
  `product_name_at_purchase` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price_at_purchase`, `product_name_at_purchase`) VALUES
('51446edb-c6fe-4a75-bf78-6c385eb71eb6', '4504df3c-908b-4403-a380-79709e9387b6', 'prod002', 1, 1299000.00, 'Source Code Website Bán Hàng Laravel'),
('9fd9a634-eb96-4f2d-850d-7b4059bd5fa7', '4504df3c-908b-4403-a380-79709e9387b6', 'prod001', 2, 899000.00, 'Windows 11 Pro Key'),
('oi001', 'order001', 'prod001', 1, 799000.00, 'Windows 11 Pro Key'),
('oi002', 'order002', 'prod002', 1, 1299000.00, 'Source Code Website Bán Hàng Laravel');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `category_id` char(36) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `short_description` text DEFAULT NULL,
  `view_count` int(11) NOT NULL DEFAULT 0,
  `like_count` int(11) NOT NULL DEFAULT 0,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `user_id`, `category_id`, `title`, `content`, `short_description`, `view_count`, `like_count`, `seo_title`, `seo_description`, `seo_keywords`, `status`, `created_at`, `updated_at`) VALUES
('post001', '88041f44-b641-11f0-b120-040e3c9ccf10', 'cat_post_001', 'Hướng dẫn dùng Laravel 11 từ A-Z', '<p>Laravel 11 là phiên bản mới nhất...</p>', 'Học Laravel 11 cơ bản đến nâng cao', 1250, 89, 'Laravel 11 Tutorial Tiếng Việt', 'Hướng dẫn chi tiết Laravel 11', 'laravel, php, web dev', 'published', '2025-10-31 02:30:00', NULL),
('post002', 'a1b2c3d4-e5f6-7890-abcd-ef1234567890', 'cat_post_002', 'AI sẽ thay thế lập trình viên?', '<p>Nhiều người lo lắng AI sẽ...</p>', 'Phân tích tác động của AI đến ngành lập trình', 3400, 210, 'AI có thay thế lập trình viên?', 'AI vs Developer', 'AI, lập trình, tương lai', 'published', '2025-10-31 03:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `post_categories`
--

CREATE TABLE `post_categories` (
  `id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` char(36) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_categories`
--

INSERT INTO `post_categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image_url`, `seo_title`, `seo_description`, `seo_keywords`, `display_order`, `status`, `created_at`, `updated_at`) VALUES
('cat_post_001', 'Lập Trình', 'lap-trinh', 'Chia sẻ kiến thức lập trình web, mobile, AI', NULL, 'https://example.com/cat/laptrinh.jpg', 'Học Lập Trình Miễn Phí', 'Khóa học, tài liệu, mẹo lập trình', 'php, laravel, javascript, python', 1, 'active', '2025-10-31 01:10:00', NULL),
('cat_post_002', 'Công Nghệ', 'cong-nghe', 'Tin tức công nghệ mới nhất', NULL, 'https://example.com/cat/tech.jpg', 'Tin Công Nghệ 24h', 'Cập nhật công nghệ mới', 'AI, blockchain, 5G', 2, 'active', '2025-10-31 01:11:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `post_images`
--

CREATE TABLE `post_images` (
  `id` char(36) NOT NULL,
  `post_id` char(36) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_images`
--

INSERT INTO `post_images` (`id`, `post_id`, `image_url`, `alt_text`, `is_primary`, `created_at`) VALUES
('img_post_001', 'post001', 'https://example.com/posts/laravel11.jpg', 'Laravel 11 Banner', 1, '2025-10-31 02:31:00'),
('img_post_002', 'post002', 'https://example.com/posts/ai-future.jpg', 'AI và lập trình viên', 1, '2025-10-31 03:01:00');

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `id` char(36) NOT NULL,
  `post_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`id`, `post_id`, `user_id`, `created_at`) VALUES
('like001', 'post001', 'b2c3d4e5-f6g7-8901-bcde-f23456789012', '2025-10-31 04:00:00'),
('like002', 'post002', '88041f44-b641-11f0-b120-040e3c9ccf10', '2025-10-31 03:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `post_tags`
--

CREATE TABLE `post_tags` (
  `id` char(36) NOT NULL,
  `post_id` char(36) NOT NULL,
  `tag_id` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_tags`
--

INSERT INTO `post_tags` (`id`, `post_id`, `tag_id`) VALUES
('pt1', 'post001', 'tag001'),
('pt2', 'post001', 'tag002'),
('pt3', 'post001', 'tag004'),
('pt4', 'post002', 'tag003'),
('pt5', 'post002', 'tag004');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_id` char(36) DEFAULT NULL,
  `description` text NOT NULL,
  `short_description` text DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `type` enum('activation_key','other') NOT NULL DEFAULT 'other',
  `stock` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `category_id`, `description`, `short_description`, `features`, `price`, `sale_price`, `type`, `stock`, `status`, `created_at`, `updated_at`) VALUES
('prod001', 'Windows 11 Pro Key', 'cat_prod_001', 'Key kích hoạt Windows 11 Pro bản quyền vĩnh viễn', 'Windows 11 Pro Retail Key', '{\"cpu\":\"Intel/AMD 64-bit\",\"ram\":\"4GB+\",\"storage\":\"64GB+\"}', 899000.00, 799000.00, 'activation_key', 56, 'active', '2025-10-31 02:00:00', '2025-10-31 21:59:21'),
('prod002', 'Source Code Website Bán Hàng Laravel', 'cat_prod_002', 'Full source code website bán hàng + admin panel', 'Laravel E-commerce Full Source', '{\"framework\":\"Laravel 11\",\"database\":\"MySQL\",\"features\":\"Giỏ hàng, thanh toán, quản lý\"}', 1299000.00, NULL, 'other', 4, 'active', '2025-10-31 02:15:00', '2025-10-31 21:59:21');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` char(36) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_categories`
--

INSERT INTO `product_categories` (`id`, `name`, `slug`, `description`, `parent_id`, `image_url`, `seo_title`, `seo_description`, `seo_keywords`, `display_order`, `status`, `created_at`, `updated_at`) VALUES
('cat_prod_001', 'Phần Mềm', 'phan-mem', 'Key bản quyền phần mềm chính hãng', NULL, 'https://example.com/cat/software.jpg', 'Mua Key Phần Mềm Giá Rẻ', 'Windows, Office, Antivirus', 'windows, office, key', 1, 'active', '2025-10-31 01:20:00', NULL),
('cat_prod_002', 'Tài Liệu', 'tai-lieu', 'Tài liệu học tập, source code', NULL, 'https://example.com/cat/docs.jpg', 'Tài Liệu Lập Trình', 'PDF, source code, khóa học', 'source code, pdf', 2, 'active', '2025-10-31 01:21:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` char(36) NOT NULL,
  `product_id` char(36) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `alt_text`, `is_primary`, `created_at`) VALUES
('pimg001', 'prod001', 'https://example.com/prod/windows11.jpg', 'Windows 11 Pro Key', 1, '2025-10-31 02:01:00'),
('pimg002', 'prod002', 'https://example.com/prod/laravel-shop.jpg', 'Source Code Laravel', 1, '2025-10-31 02:16:00');

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` char(36) NOT NULL,
  `product_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `content` text NOT NULL,
  `status` enum('approved','pending','rejected','banned') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_reviews`
--

INSERT INTO `product_reviews` (`id`, `product_id`, `user_id`, `rating`, `content`, `status`, `created_at`, `updated_at`) VALUES
('rev001', 'prod001', '88041f44-b641-11f0-b120-040e3c9ccf10', 5, 'Key hoạt động tốt, hỗ trợ nhanh!', 'approved', '2025-10-31 04:40:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `review_replies`
--

CREATE TABLE `review_replies` (
  `id` char(36) NOT NULL,
  `review_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `content` text NOT NULL,
  `status` enum('visible','hidden') NOT NULL DEFAULT 'visible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_replies`
--

INSERT INTO `review_replies` (`id`, `review_id`, `user_id`, `content`, `status`, `created_at`) VALUES
('rep001', 'rev001', 'a1b2c3d4-e5f6-7890-abcd-ef1234567890', 'Cảm ơn bạn đã tin tưởng!', 'visible', '2025-10-31 04:45:00');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` char(36) NOT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `social_links` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_links`)),
  `seo_global_title` varchar(255) DEFAULT NULL,
  `seo_global_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `logo_url`, `social_links`, `seo_global_title`, `seo_global_description`, `created_at`, `updated_at`) VALUES
('site001', 'https://example.com/logo.png', '{\"facebook\":\"https://fb.com/blogshop\",\"youtube\":\"https://youtube.com/@blogshop\",\"tiktok\":\"https://tiktok.com/@blogshop\"}', 'BlogShop - Mua Key Bản Quyền & Chia Sẻ Kiến Thức', 'Cửa hàng bán key bản quyền phần mềm, tài liệu lập trình, bài viết công nghệ miễn phí.', '2025-10-31 01:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

CREATE TABLE `tags` (
  `id` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tags`
--

INSERT INTO `tags` (`id`, `name`, `created_at`) VALUES
('tag001', 'Laravel', '2025-10-31 02:00:00'),
('tag002', 'PHP', '2025-10-31 02:00:00'),
('tag003', 'AI', '2025-10-31 02:00:00'),
('tag004', 'Web Dev', '2025-10-31 02:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` char(36) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` enum('pending','active','inactive','banned') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `email_verified_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `phone_number`, `avatar_url`, `role`, `status`, `created_at`, `updated_at`, `email_verified_at`) VALUES
('88041f44-b641-11f0-b120-040e3c9ccf10', 'letankim2003', 'letankim2003@gmail.com', '$2y$10$elCdIDLq3dANpH/zIu4iy.cz7SmKYcLm4x6sBXFP7bRujmk/Y3sxC', '0901234567', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRu6gJ6Z-2LIWnmo8nfIAxfBWwEvy-okK3Kpw&s', 'user', 'active', '2025-10-31 10:08:22', '2025-10-31 10:26:00', '2025-10-31 10:10:08'),
('a1b2c3d4-e5f6-7890-abcd-ef1234567890', 'admin', 'admin@blogshop.com', '$2y$10$elCdIDLq3dANpH/zIu4iy.cz7SmKYcLm4x6sBXFP7bRujmk/Y3sxC', '0912345678', NULL, 'admin', 'active', '2025-10-31 02:00:00', '2025-10-31 11:25:18', '2025-10-31 02:00:00'),
('b2c3d4e5-f6g7-8901-bcde-f23456789012', 'nguyenvanA', 'nguyenvana@gmail.com', '$2y$10$elCdIDLq3dANpH/zIu4iy.cz7SmKYcLm4x6sBXFP7bRujmk/Y3sxC', '0923456789', NULL, 'user', 'active', '2025-10-30 08:30:00', '2025-10-31 11:25:24', '2025-10-30 08:31:00'),
('d10571a5-b657-11f0-b120-040e3c9ccf10', 'dupuser', 'test1@example.com', '$2y$10$Da6cLJz724t1em1HdOvzMul/uaNjFWZ6DghOXKJoFIwAIPGTL37MS', NULL, NULL, 'user', 'pending', '2025-10-31 12:47:53', NULL, '2025-10-31 12:47:53');

-- --------------------------------------------------------

--
-- Table structure for table `user_verifications`
--

CREATE TABLE `user_verifications` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `type` enum('activation','password_reset') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_verifications`
--

INSERT INTO `user_verifications` (`id`, `user_id`, `otp_code`, `token`, `expires_at`, `type`, `created_at`) VALUES
('', 'd10571a5-b657-11f0-b120-040e3c9ccf10', NULL, '0d3cecd71046297991df3269164397ccd470618cf19b4fcc89ccd032b933', '2025-10-31 13:47:53', 'activation', '2025-10-31 12:47:53'),
('verify001', 'b2c3d4e5-f6g7-8901-bcde-f23456789012', '123456', 'verify-token-abc123', '2025-10-30 08:40:00', 'activation', '2025-10-30 08:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

CREATE TABLE `vouchers` (
  `id` char(36) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percent','fixed','','') NOT NULL DEFAULT 'percent',
  `discount` decimal(5,2) NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `max_uses` int(11) NOT NULL DEFAULT 1,
  `uses_count` int(11) NOT NULL DEFAULT 0,
  `is_public` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `discount_type`, `discount`, `expires_at`, `max_uses`, `uses_count`, `is_public`, `created_at`) VALUES
('vouch001', 'GIAM10', 'percent', 10.00, '2025-12-31 16:59:59', 100, 3, 1, '2025-10-31 01:00:00'),
('vouch002', 'WELCOME20', 'percent', 20.00, '2025-11-30 16:59:59', 1, 0, 0, '2025-10-31 01:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `voucher_usage`
--

CREATE TABLE `voucher_usage` (
  `id` char(36) NOT NULL,
  `voucher_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activation_keys`
--
ALTER TABLE `activation_keys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `material_images`
--
ALTER TABLE `material_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `voucher_id` (`voucher_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_post_category` (`category_id`);

--
-- Indexes for table `post_categories`
--
ALTER TABLE `post_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `post_images`
--
ALTER TABLE `post_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `post_id` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `post_id` (`post_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_category` (`category_id`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_id` (`review_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`);

--
-- Indexes for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `vouchers`
--
ALTER TABLE `vouchers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `voucher_usage`
--
ALTER TABLE `voucher_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_voucher` (`user_id`,`voucher_id`),
  ADD KEY `idx_voucher_id` (`voucher_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activation_keys`
--
ALTER TABLE `activation_keys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activation_keys`
--
ALTER TABLE `activation_keys`
  ADD CONSTRAINT `activation_keys_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `materials`
--
ALTER TABLE `materials`
  ADD CONSTRAINT `materials_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `material_images`
--
ALTER TABLE `material_images`
  ADD CONSTRAINT `material_images_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_post_category` FOREIGN KEY (`category_id`) REFERENCES `post_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_categories`
--
ALTER TABLE `post_categories`
  ADD CONSTRAINT `post_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `post_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `post_images`
--
ALTER TABLE `post_images`
  ADD CONSTRAINT `post_images_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `post_tags`
--
ALTER TABLE `post_tags`
  ADD CONSTRAINT `post_tags_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD CONSTRAINT `review_replies_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `product_reviews` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `review_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD CONSTRAINT `user_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `voucher_usage`
--
ALTER TABLE `voucher_usage`
  ADD CONSTRAINT `fk_voucher_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_voucher_usage_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
