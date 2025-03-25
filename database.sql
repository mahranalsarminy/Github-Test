-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- مضيف: localhost:3306
-- وقت الجيل: 25 مارس 2025 الساعة 04:25
-- إصدار الخادم: 8.0.41
-- نسخة PHP: 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- قاعدة بيانات: `mahrannnn_newa`
--

-- --------------------------------------------------------

--
-- بنية الجدول `about_content`
--

CREATE TABLE `about_content` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text,
  `image_url` varchar(255) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `about_content`
--

INSERT INTO `about_content` (`id`, `title`, `content`, `image_url`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'WallPix.Top', '<p class=\"text-gray-800 dark:text-white text-lg\">Welcome to WallPiX.Top, your premier destination for breathtaking wallpapers, backgrounds, and media designed exclusively for mobile devices. Our mission is to elevate your smartphone experience by offering a diverse collection of visuals that are both unique and high-quality.</p>\r\n<p class=\"text-gray-800 dark:text-white mt-4 text-lg\">At WallPiX.Top, we harness the power of artificial intelligence to generate stunning, high-resolution wallpapers and backgrounds. By leveraging cutting-edge AI technology, we ensure that each image is crafted with precision and creativity, catering to a wide variety of tastes and preferences.</p>', '/uploads/about/about_1741856466_67d29ed24d748.png', 0, 1, '2025-03-13 09:01:06'),
(2, 'What Makes Us Unique?', '<ul class=\"space-y-4\">\r\n<li class=\"flex items-center text-lg text-gray-800 dark:text-white\">AI-Driven Creativity: We use advanced AI to design and produce visually captivating images that stand out.</li>\r\n<li class=\"flex items-center text-lg text-gray-800 dark:text-white\">Extensive Variety: A rich and growing library of wallpapers to match every mood and style.</li>\r\n<li class=\"flex items-center text-lg text-gray-800 dark:text-white\">Unmatched Quality: Enjoy premium, high-definition visuals optimized for modern mobile screens.</li>\r\n<li class=\"flex items-center text-lg text-gray-800 dark:text-white\">Enhanced Experience: Ads tailored to your preferences, helping us provide free, top-notch content while adhering to Google AdSense policies.</li>\r\n</ul>', '/uploads/about/about_1741856830_67d2a03ec3f0a.jpg', 2, 1, '2025-03-13 09:07:10');

-- --------------------------------------------------------

--
-- بنية الجدول `activities`
--

CREATE TABLE `activities` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `activities`
--

INSERT INTO `activities` (`id`, `user_id`, `description`, `created_at`) VALUES
(3, 1, 'تم تسجيل دخول المشرف بنجاح', '2025-03-17 08:28:28'),
(4, 1, 'تم تسجيل دخول المشرف بنجاح', '2025-03-17 08:28:31'),
(5, 1, 'تم تسجيل دخول المشرف بنجاح', '2025-03-17 08:28:32'),
(6, 1, 'Successfully logged in', '2025-03-17 22:21:54'),
(7, 1, 'Successfully logged in', '2025-03-18 09:10:39'),
(8, NULL, 'Viewed media list', '2025-03-18 22:06:36'),
(9, NULL, 'Viewed media list', '2025-03-18 22:23:28'),
(10, NULL, 'Viewed media list', '2025-03-18 22:47:29'),
(11, NULL, 'Viewed media list', '2025-03-18 22:49:15'),
(12, NULL, 'Viewed media list', '2025-03-18 22:49:16'),
(13, NULL, 'Viewed media list', '2025-03-18 22:49:17'),
(14, NULL, 'Viewed media list', '2025-03-18 22:52:03'),
(15, NULL, 'Viewed media list', '2025-03-18 22:52:04'),
(16, NULL, 'Viewed media list', '2025-03-18 22:52:05'),
(17, NULL, 'Viewed media list', '2025-03-18 22:52:06'),
(18, NULL, 'Viewed media list', '2025-03-18 22:52:06'),
(19, NULL, 'Viewed media list', '2025-03-18 22:52:06'),
(20, NULL, 'Viewed media list', '2025-03-18 22:52:07'),
(21, NULL, 'Viewed media list', '2025-03-18 22:52:08'),
(22, NULL, 'Viewed media list', '2025-03-18 22:52:35'),
(23, NULL, 'Viewed media list', '2025-03-18 22:52:55'),
(24, NULL, 'Viewed media list', '2025-03-18 22:53:43'),
(25, NULL, 'Viewed media list', '2025-03-18 22:56:02'),
(26, NULL, 'Viewed media list', '2025-03-18 22:57:07'),
(27, NULL, 'Viewed media list', '2025-03-18 22:58:34'),
(28, NULL, 'Viewed media list', '2025-03-18 22:58:53'),
(29, NULL, 'Viewed media list', '2025-03-18 22:58:54'),
(30, NULL, 'Viewed media list', '2025-03-18 22:59:02'),
(31, NULL, 'Toggled featured status for media #22', '2025-03-18 22:59:15'),
(32, NULL, 'Viewed media list', '2025-03-18 22:59:15'),
(33, NULL, 'Viewed media list', '2025-03-18 22:59:16'),
(34, NULL, 'Toggled featured status for media #22', '2025-03-18 22:59:18'),
(35, NULL, 'Viewed media list', '2025-03-18 22:59:18'),
(36, NULL, 'Viewed media list', '2025-03-18 22:59:18'),
(37, NULL, 'Changed orientation for media #22 to landscape', '2025-03-18 22:59:23'),
(38, NULL, 'Viewed media list', '2025-03-18 22:59:23'),
(39, NULL, 'Viewed media list', '2025-03-18 22:59:23'),
(40, NULL, 'Changed orientation for media #22 to portrait', '2025-03-18 22:59:24'),
(41, NULL, 'Viewed media list', '2025-03-18 22:59:24'),
(42, NULL, 'Viewed media list', '2025-03-18 22:59:25'),
(43, NULL, 'Toggled active status for media #25', '2025-03-18 22:59:56'),
(44, NULL, 'Viewed media list', '2025-03-18 22:59:56'),
(45, NULL, 'Viewed media list', '2025-03-18 22:59:56'),
(46, NULL, 'Toggled active status for media #25', '2025-03-18 22:59:57'),
(47, NULL, 'Viewed media list', '2025-03-18 22:59:58'),
(48, NULL, 'Viewed media list', '2025-03-18 22:59:58'),
(49, NULL, 'Viewed media list', '2025-03-18 23:00:34'),
(50, NULL, 'Viewed media list', '2025-03-18 23:17:13'),
(51, NULL, 'Viewed media list', '2025-03-19 01:05:53'),
(52, NULL, 'Viewed media list', '2025-03-19 02:30:07'),
(53, NULL, 'Viewed media list', '2025-03-19 02:41:36'),
(54, NULL, 'Viewed media list', '2025-03-19 02:43:00'),
(55, NULL, 'Viewed media list', '2025-03-19 02:44:13'),
(56, NULL, 'Viewed media list', '2025-03-19 02:59:18'),
(57, NULL, 'Viewed media list', '2025-03-19 02:59:20'),
(58, NULL, 'Viewed media list', '2025-03-19 03:11:09'),
(59, NULL, 'Viewed media list', '2025-03-19 03:11:25'),
(60, 1, 'Category \"Islamic\" was deactivated', '2025-03-19 03:20:50'),
(61, 1, 'Category \"Islamic\" was activated', '2025-03-19 03:20:52'),
(62, 1, 'Category \"Islamic\" was deactivated', '2025-03-19 03:30:19'),
(63, 1, 'Category \"Islamic\" was activated', '2025-03-19 03:30:24'),
(64, NULL, 'Viewed media list', '2025-03-19 15:01:11'),
(65, NULL, 'Viewed media list', '2025-03-19 15:01:11'),
(66, NULL, 'Viewed media list', '2025-03-19 15:01:21'),
(67, 1, 'Updated tag: islamic (ID: 19)', '2025-03-20 01:51:41'),
(68, 1, 'Updated tag: muslim (ID: 18)', '2025-03-20 01:52:04'),
(69, 1, 'Added new tag: mahran (ID: 20)', '2025-03-20 01:52:17'),
(70, NULL, 'Viewed media list', '2025-03-20 01:52:27'),
(71, NULL, 'Viewed media list', '2025-03-20 01:52:36'),
(72, NULL, 'Viewed media list', '2025-03-20 01:53:10'),
(73, NULL, 'Viewed media list', '2025-03-20 01:53:44'),
(74, NULL, 'Viewed media list', '2025-03-20 01:53:50'),
(75, NULL, 'Viewed media list', '2025-03-20 01:54:04'),
(76, NULL, 'Viewed media list', '2025-03-20 01:54:10'),
(77, NULL, 'Viewed media list', '2025-03-20 01:54:22'),
(78, NULL, 'Viewed media list', '2025-03-20 01:54:31'),
(79, 1, 'Successfully logged in', '2025-03-20 06:03:03'),
(80, NULL, 'Viewed media list', '2025-03-20 07:31:06'),
(81, 1, 'Successfully logged in', '2025-03-20 16:42:06'),
(82, 1, 'User logged out', '2025-03-20 16:43:51'),
(83, 1, 'Successfully logged in', '2025-03-20 16:44:04'),
(84, 1, 'User logged out', '2025-03-20 16:44:15'),
(85, 1, 'Successfully logged in', '2025-03-20 16:44:27'),
(86, 1, 'User logged out', '2025-03-20 16:45:03'),
(87, 1, 'Successfully logged in', '2025-03-20 16:45:08'),
(88, 1, 'Updated profile information', '2025-03-20 16:45:24'),
(89, 5, 'New user registered', '2025-03-20 17:58:06'),
(90, 5, 'Updated profile information', '2025-03-20 17:59:14'),
(91, 1, 'Successfully logged in', '2025-03-20 22:12:17'),
(92, 1, 'Successfully logged in', '2025-03-21 02:51:46'),
(93, NULL, 'Viewed media list', '2025-03-21 03:05:45'),
(94, NULL, 'Viewed media list', '2025-03-21 03:07:45'),
(95, NULL, 'Viewed media list', '2025-03-21 03:10:56'),
(96, 1, 'Added new category #7: Mahran', '2025-03-21 03:11:29'),
(97, NULL, 'Viewed media list', '2025-03-21 03:11:42'),
(98, NULL, 'Viewed media list', '2025-03-21 03:12:30'),
(99, NULL, 'Viewed media list', '2025-03-21 03:15:14'),
(100, NULL, 'Viewed media list', '2025-03-21 03:18:24'),
(101, NULL, 'Viewed media list', '2025-03-21 03:18:28'),
(102, NULL, 'Viewed media list', '2025-03-21 03:20:54'),
(103, NULL, 'Viewed media list', '2025-03-21 03:21:01'),
(104, NULL, 'Viewed media list', '2025-03-21 03:23:21'),
(105, NULL, 'Viewed media list', '2025-03-21 03:24:58'),
(106, NULL, 'Viewed media list', '2025-03-21 03:25:27'),
(107, NULL, 'Viewed media list', '2025-03-21 03:25:29'),
(108, NULL, 'Viewed media list', '2025-03-21 03:27:47'),
(109, NULL, 'Viewed media list', '2025-03-21 03:29:02'),
(110, 1, 'Added new media item #29: test2', '2025-03-21 03:29:21'),
(111, NULL, 'Viewed media list', '2025-03-21 03:29:23'),
(112, NULL, 'Viewed media list', '2025-03-21 03:30:59'),
(113, NULL, 'Viewed media list', '2025-03-21 03:31:08'),
(114, NULL, 'Viewed media list', '2025-03-21 03:33:19'),
(115, NULL, 'Viewed media list', '2025-03-21 03:47:25'),
(116, NULL, 'Viewed media list', '2025-03-21 03:50:09'),
(117, 1, 'Successfully logged in', '2025-03-21 05:47:49'),
(118, NULL, 'Viewed media list', '2025-03-21 06:22:22'),
(119, NULL, 'Viewed media list', '2025-03-21 06:23:03'),
(120, NULL, 'Viewed media list', '2025-03-21 06:39:14'),
(121, NULL, 'Viewed media list', '2025-03-21 06:40:28'),
(122, NULL, 'Viewed media list', '2025-03-21 06:40:29'),
(123, NULL, 'Viewed media list', '2025-03-21 06:40:30'),
(124, NULL, 'Viewed media list', '2025-03-21 06:40:30'),
(125, 1, 'Successfully logged in', '2025-03-21 19:41:42'),
(126, NULL, 'Viewed media list', '2025-03-21 20:45:21'),
(127, NULL, 'Viewed media list', '2025-03-21 20:45:37'),
(128, NULL, 'Viewed media list', '2025-03-21 20:45:40'),
(129, NULL, 'Viewed media list', '2025-03-21 20:45:41'),
(130, NULL, 'Viewed media list', '2025-03-21 20:45:43'),
(131, NULL, 'Viewed media list', '2025-03-21 20:49:16'),
(132, NULL, 'Viewed media list', '2025-03-21 20:55:09'),
(133, 1, 'Successfully logged in', '2025-03-22 02:57:50'),
(134, NULL, 'Viewed media list', '2025-03-22 03:01:37'),
(135, NULL, 'Viewed media list', '2025-03-22 03:03:00'),
(136, NULL, 'Viewed media list', '2025-03-22 03:03:39'),
(137, NULL, 'Viewed media list', '2025-03-22 03:04:24'),
(138, NULL, 'Viewed media list', '2025-03-22 03:06:20'),
(139, NULL, 'Viewed media list', '2025-03-22 03:10:50'),
(140, NULL, 'Viewed media list', '2025-03-22 03:10:59'),
(141, NULL, 'Viewed media list', '2025-03-22 03:11:00'),
(142, NULL, 'Viewed media list', '2025-03-22 03:16:49'),
(143, 1, 'تم تحديث إعدادات الموقع', '2025-03-22 03:22:49'),
(144, 1, 'تم تحديث إعدادات الموقع', '2025-03-22 03:24:14'),
(145, 1, 'Added menu item: Mahran to menu ID 2', '2025-03-22 03:35:05'),
(146, 1, 'Updated menu item: Mahran', '2025-03-22 03:35:27'),
(147, 1, 'Site settings updated', '2025-03-22 03:35:51'),
(148, 1, 'Site settings updated', '2025-03-22 03:36:23'),
(149, 1, 'Site settings updated', '2025-03-22 03:36:49'),
(150, 1, 'Site settings updated', '2025-03-22 03:36:54'),
(151, 1, 'Successfully logged in', '2025-03-22 05:45:19'),
(152, 1, 'Successfully logged in', '2025-03-22 06:08:11'),
(153, 1, 'Successfully logged in', '2025-03-22 12:15:28'),
(154, NULL, 'Viewed media list', '2025-03-22 12:20:13'),
(155, NULL, 'Viewed media list', '2025-03-22 12:27:48'),
(156, NULL, 'Viewed media list', '2025-03-22 12:27:58'),
(157, NULL, 'Viewed media list', '2025-03-22 12:28:30'),
(158, NULL, 'Viewed media list', '2025-03-22 12:28:35'),
(159, NULL, 'Viewed media list', '2025-03-22 12:51:29'),
(160, NULL, 'Viewed media list', '2025-03-22 12:51:31'),
(161, NULL, 'Viewed media list', '2025-03-22 13:41:42'),
(162, NULL, 'Viewed media list', '2025-03-22 13:41:47'),
(163, NULL, 'Viewed media list', '2025-03-22 13:41:52'),
(164, 1, 'Successfully logged in', '2025-03-22 19:44:45'),
(165, NULL, 'Viewed media list', '2025-03-22 19:45:02'),
(166, NULL, 'Viewed media list', '2025-03-22 19:47:08'),
(167, NULL, 'Viewed media list', '2025-03-22 19:48:07'),
(168, NULL, 'Viewed media list', '2025-03-22 19:48:12'),
(169, NULL, 'Viewed media list', '2025-03-22 19:56:59'),
(170, NULL, 'Viewed media list', '2025-03-22 20:00:39'),
(171, 1, 'Successfully logged in', '2025-03-22 21:56:21'),
(172, NULL, 'Viewed media list', '2025-03-22 21:56:35'),
(173, NULL, 'Viewed media list', '2025-03-22 21:57:40'),
(174, 1, 'Successfully logged in', '2025-03-23 01:38:44'),
(175, NULL, 'Viewed media list', '2025-03-23 01:39:36'),
(176, NULL, 'Viewed media list', '2025-03-23 02:09:16'),
(177, NULL, 'Viewed media list', '2025-03-23 02:11:20'),
(178, NULL, 'Viewed media list', '2025-03-23 02:12:24'),
(179, NULL, 'Viewed media list', '2025-03-23 02:12:27'),
(180, NULL, 'Viewed media list', '2025-03-23 02:12:40'),
(181, NULL, 'Viewed media list', '2025-03-23 02:13:00'),
(182, NULL, 'Viewed media list', '2025-03-23 03:24:00'),
(183, 1, 'Successfully logged in', '2025-03-23 03:24:04'),
(184, NULL, 'Viewed media list', '2025-03-23 03:24:09'),
(185, NULL, 'Viewed media list', '2025-03-23 03:24:41'),
(186, NULL, 'Viewed media list', '2025-03-23 03:25:21'),
(187, NULL, 'Viewed media list', '2025-03-23 03:25:55'),
(188, NULL, 'Viewed media list', '2025-03-23 03:27:51'),
(189, NULL, 'Viewed media list', '2025-03-23 19:33:14'),
(190, NULL, 'Viewed media list', '2025-03-23 19:52:15'),
(191, NULL, 'Viewed media list', '2025-03-23 19:57:05'),
(192, NULL, 'Viewed media list', '2025-03-23 19:59:16'),
(193, NULL, 'Viewed media list', '2025-03-23 20:00:14'),
(194, NULL, 'Viewed media list', '2025-03-23 20:00:48'),
(195, NULL, 'Viewed media list', '2025-03-23 20:07:13'),
(196, 1, 'Successfully logged in', '2025-03-24 05:48:10'),
(197, NULL, 'Viewed media list', '2025-03-24 05:48:14'),
(198, 1, 'Added new media item #32: mahran123', '2025-03-24 05:54:09'),
(199, NULL, 'Viewed media list', '2025-03-24 05:54:09'),
(200, NULL, 'Viewed media list', '2025-03-24 05:58:15'),
(201, NULL, 'Viewed media list', '2025-03-24 05:58:30'),
(202, NULL, 'Viewed media list', '2025-03-24 06:03:18'),
(203, NULL, 'Viewed media list', '2025-03-24 06:17:31'),
(204, 1, 'Added new media item #35: mahran12345qqq', '2025-03-24 06:17:59'),
(205, NULL, 'Viewed media list', '2025-03-24 06:17:59'),
(206, NULL, 'Viewed media list', '2025-03-24 06:18:10'),
(207, NULL, 'Viewed media list', '2025-03-24 06:54:26'),
(208, 1, 'Added new tag: عربي (ID: 27)', '2025-03-24 06:58:29'),
(209, 1, 'Added new media item #37: cccccccccccccccc', '2025-03-24 06:58:29'),
(210, NULL, 'Viewed media list', '2025-03-24 06:58:29'),
(211, NULL, 'Viewed media list', '2025-03-24 07:00:13'),
(212, NULL, 'Viewed media list', '2025-03-24 07:04:00'),
(213, NULL, 'Viewed media list', '2025-03-24 07:06:29'),
(214, NULL, 'Toggled active status for media #37', '2025-03-24 07:06:35'),
(215, NULL, 'Viewed media list', '2025-03-24 07:06:35'),
(216, NULL, 'Viewed media list', '2025-03-24 07:06:35'),
(217, NULL, 'Toggled active status for media #37', '2025-03-24 07:06:39'),
(218, NULL, 'Viewed media list', '2025-03-24 07:06:39'),
(219, NULL, 'Viewed media list', '2025-03-24 07:06:39'),
(220, 1, 'Added new ad: Spring Sale Banner 2025', '2025-03-24 06:38:57'),
(221, 1, 'Updated ad: Product Recommendation', '2025-03-24 06:40:15'),
(222, 2, 'Added new ad: Tech Gadget Affiliate', '2025-03-24 07:12:33'),
(223, 1, 'Deleted ad: Outdated Holiday Promotion', '2025-03-24 08:27:44'),
(224, 3, 'Updated ad placement for: Subscription Offer', '2025-03-24 10:05:19'),
(225, 1, 'Site settings updated', '2025-03-24 08:02:27'),
(226, 1, 'Site settings updated', '2025-03-24 08:03:11'),
(227, 1, 'Site settings updated', '2025-03-24 08:46:28'),
(228, 1, 'Site settings updated', '2025-03-24 09:10:24'),
(229, 1, 'Site settings updated', '2025-03-24 09:10:57'),
(230, 1, 'Site settings updated', '2025-03-24 09:32:35'),
(231, 1, 'Site settings updated', '2025-03-24 09:37:21'),
(232, 1, 'Site settings updated', '2025-03-24 09:38:30'),
(233, 1, 'Site settings updated', '2025-03-24 09:45:09'),
(234, 1, 'Site settings updated', '2025-03-24 09:51:33'),
(235, 1, 'Site settings updated', '2025-03-24 09:52:33'),
(236, 1, 'Site settings updated', '2025-03-24 10:34:17'),
(237, 1, 'Site settings updated', '2025-03-24 10:38:39'),
(238, 1, 'Viewed message #7 from Max Lammertink', '2025-03-24 11:15:02'),
(239, 1, 'Viewed message #5 from Johnmes', '2025-03-24 11:26:13'),
(240, 1, 'Viewed message #3 from تجربة عربي', '2025-03-24 11:26:59'),
(241, 1, 'Viewed message #1 from wallpix.top', '2025-03-24 11:53:41'),
(242, 1, 'Replied to message #1 from wallpix.top', '2025-03-24 11:53:51'),
(243, 1, 'Marked message #5 as spam', '2025-03-24 11:55:46'),
(244, NULL, 'Viewed media list', '2025-03-24 12:18:18'),
(245, NULL, 'Viewed media list', '2025-03-24 12:18:39'),
(246, NULL, 'Viewed media list', '2025-03-24 12:18:51'),
(247, NULL, 'Viewed media list', '2025-03-24 12:21:16'),
(248, NULL, 'Viewed media list', '2025-03-24 12:21:25'),
(249, NULL, 'Viewed media list', '2025-03-24 12:21:29'),
(250, NULL, 'Viewed media list', '2025-03-24 12:21:31'),
(251, NULL, 'Viewed media list', '2025-03-24 12:21:35'),
(252, NULL, 'Viewed media list', '2025-03-24 12:21:39'),
(253, NULL, 'Viewed media list', '2025-03-24 12:21:44'),
(254, NULL, 'Viewed media list', '2025-03-24 12:21:48'),
(255, NULL, 'Viewed media list', '2025-03-24 12:21:52'),
(256, NULL, 'Viewed media list', '2025-03-24 12:21:56'),
(257, NULL, 'Viewed media list', '2025-03-24 12:21:59'),
(258, NULL, 'Viewed media list', '2025-03-24 12:22:02'),
(259, NULL, 'Viewed media list', '2025-03-24 12:22:04'),
(260, NULL, 'Viewed media list', '2025-03-24 12:22:07'),
(261, NULL, 'Viewed media list', '2025-03-24 12:46:32'),
(262, NULL, 'Viewed media list', '2025-03-24 12:53:03'),
(263, 1, 'Category \"Mahran\" was deactivated', '2025-03-24 12:53:49'),
(264, 1, 'Category \"Mahran\" was activated', '2025-03-24 12:53:58'),
(265, 1, 'Updated category #7: Mahran', '2025-03-24 12:54:52'),
(266, NULL, 'Updated paypal payment gateway settings', '2025-03-24 13:18:25'),
(267, NULL, 'Updated stripe payment gateway settings', '2025-03-24 13:18:27'),
(268, NULL, 'Viewed media list', '2025-03-24 19:54:36'),
(269, 1, 'Successfully logged in', '2025-03-24 19:54:39'),
(270, NULL, 'Viewed media list', '2025-03-24 19:54:52'),
(271, 1, 'Added new tag: paid (ID: 28)', '2025-03-24 19:56:48'),
(272, 1, 'Added new media item #38: paid 1', '2025-03-24 19:56:48'),
(273, NULL, 'Viewed media list', '2025-03-24 19:56:48'),
(274, NULL, 'Viewed media list', '2025-03-24 20:18:51'),
(275, 1, 'Site settings updated', '2025-03-24 20:33:42'),
(276, 1, 'Site settings updated', '2025-03-24 20:34:08'),
(277, 1, 'Site settings updated', '2025-03-24 20:34:19'),
(278, NULL, 'Viewed media list', '2025-03-24 20:38:55'),
(279, 1, 'Added new tag: 3 (ID: 29)', '2025-03-24 20:40:01'),
(280, 1, 'Added new media item #39: 1', '2025-03-24 20:40:01'),
(281, NULL, 'Viewed media list', '2025-03-24 20:40:01'),
(282, NULL, 'Toggled active status for media #39', '2025-03-24 20:41:02'),
(283, NULL, 'Viewed media list', '2025-03-24 20:41:02'),
(284, NULL, 'Viewed media list', '2025-03-24 20:41:02'),
(285, NULL, 'Toggled active status for media #39', '2025-03-24 20:41:20'),
(286, NULL, 'Viewed media list', '2025-03-24 20:41:20'),
(287, NULL, 'Viewed media list', '2025-03-24 20:41:20'),
(288, NULL, 'Toggled featured status for media #39', '2025-03-24 20:41:25'),
(289, NULL, 'Viewed media list', '2025-03-24 20:41:25'),
(290, NULL, 'Viewed media list', '2025-03-24 20:41:25'),
(291, NULL, 'Toggled featured status for media #39', '2025-03-24 20:41:33'),
(292, NULL, 'Viewed media list', '2025-03-24 20:41:33'),
(293, NULL, 'Viewed media list', '2025-03-24 20:41:33'),
(294, NULL, 'Viewed media list', '2025-03-24 20:41:42'),
(295, NULL, 'Viewed media list', '2025-03-24 20:41:50'),
(296, 1, 'Site settings updated', '2025-03-24 20:54:28'),
(297, 1, 'Successfully logged in', '2025-03-25 01:35:34'),
(298, NULL, 'Viewed media list', '2025-03-25 02:09:39'),
(299, 1, 'Marked message #3 as spam', '2025-03-25 02:13:33'),
(300, 1, 'Viewed message #9 from Marya', '2025-03-25 02:27:26'),
(301, 1, 'Replied to message #9 from Marya', '2025-03-25 02:27:33');

-- --------------------------------------------------------

--
-- بنية الجدول `add_ons`
--

CREATE TABLE `add_ons` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Name of the add-on',
  `slug` varchar(255) NOT NULL COMMENT 'Unique identifier for the add-on',
  `description` text COMMENT 'Description of the add-on and its functionality',
  `version` varchar(50) NOT NULL COMMENT 'Current version number',
  `file_path` varchar(255) NOT NULL COMMENT 'Path to the compressed file',
  `status` enum('active','inactive','error') DEFAULT 'inactive' COMMENT 'Current status of the add-on',
  `install_date` timestamp NULL DEFAULT NULL COMMENT 'When the add-on was installed',
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last time the add-on was updated',
  `author` varchar(100) DEFAULT NULL COMMENT 'Creator or publisher name',
  `author_url` varchar(255) DEFAULT NULL COMMENT 'Link to author website or profile',
  `requirements` text COMMENT 'System requirements or dependencies',
  `type` varchar(50) DEFAULT 'extension' COMMENT 'Type like theme, plugin, extension, etc.',
  `is_core` tinyint(1) DEFAULT '0' COMMENT 'Whether this is a core add-on',
  `created_by` int DEFAULT NULL COMMENT 'User ID who added this add-on',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `add_on_updates`
--

CREATE TABLE `add_on_updates` (
  `id` int NOT NULL,
  `add_on_id` int NOT NULL COMMENT 'References the add_ons table',
  `version` varchar(50) NOT NULL COMMENT 'Version number of this update',
  `update_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the update was applied',
  `change_log` text COMMENT 'Description of changes in this version',
  `status` enum('pending','completed','failed') DEFAULT 'pending' COMMENT 'Status of the update process',
  `updated_by` int DEFAULT NULL COMMENT 'User ID who performed the update'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `ads`
--

CREATE TABLE `ads` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL COMMENT 'Identifying name of the ad',
  `ad_code` text NOT NULL COMMENT 'JavaScript code for the ad',
  `position` varchar(50) DEFAULT NULL COMMENT 'Default position (header, sidebar, content, footer)',
  `size` varchar(50) NOT NULL COMMENT 'Ad size (e.g., 300x250)',
  `type` varchar(50) NOT NULL DEFAULT 'adsense' COMMENT 'Ad type (adsense, custom, etc.)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Ad status (active/inactive)',
  `start_date` datetime DEFAULT NULL COMMENT 'Campaign start date',
  `end_date` datetime DEFAULT NULL COMMENT 'Campaign end date',
  `impressions` int DEFAULT '0' COMMENT 'Impressions counter',
  `clicks` int DEFAULT '0' COMMENT 'Estimated clicks counter',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `ads`
--

INSERT INTO `ads` (`id`, `name`, `ad_code`, `position`, `size`, `type`, `status`, `start_date`, `end_date`, `impressions`, `clicks`, `created_at`, `updated_at`) VALUES
(1, 'Spring Sale Banner 2025', '<script async src=\"https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456\" crossorigin=\"anonymous\"></script>\r\n<!-- Spring Sale Banner -->\r\n<ins class=\"adsbygoogle\"\r\n     style=\"display:block\"\r\n     data-ad-client=\"ca-pub-1234567890123456\"\r\n     data-ad-slot=\"9876543210\"\r\n     data-ad-format=\"auto\"\r\n     data-full-width-responsive=\"true\"></ins>\r\n<script>\r\n     (adsbygoogle = window.adsbygoogle || []).push({});\r\n</script>', 'header', 'responsive', 'adsense', 1, '2025-03-20 00:00:00', '2025-04-15 23:59:59', 0, 0, '2025-03-24 06:38:57', '2025-03-24 07:40:12'),
(2, 'Product Recommendation', '<script async src=\"https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456\" crossorigin=\"anonymous\"></script>\r\n<!-- Sidebar Rectangle -->\r\n<ins class=\"adsbygoogle\"\r\n     style=\"display:inline-block;width:300px;height:250px\"\r\n     data-ad-client=\"ca-pub-1234567890123456\"\r\n     data-ad-slot=\"5678901234\"></ins>\r\n<script>\r\n     (adsbygoogle = window.adsbygoogle || []).push({});\r\n</script>', 'sidebar_top', '300x250', 'adsense', 1, NULL, NULL, 0, 0, '2025-03-24 06:38:57', '2025-03-24 07:40:12'),
(3, 'Tech Gadget Affiliate', '<div style=\"width: 728px; height: 90px; overflow: hidden; position: relative;\">\r\n    <a href=\"https://example-affiliate.com/refer/12345\" target=\"_blank\">\r\n        <img src=\"https://yourdomain.com/assets/images/ads/tech-gadget-banner.jpg\" alt=\"Latest Tech Gadgets\" \r\n             style=\"width: 100%; height: auto; display: block;\">\r\n    </a>\r\n</div>', 'before_content', '728x90', 'custom', 0, NULL, NULL, 0, 0, '2025-03-24 06:38:57', '2025-03-24 07:40:12'),
(4, 'Subscription Offer', '<script async src=\"https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456\" crossorigin=\"anonymous\"></script>\r\n<!-- In-Content Ad -->\r\n<ins class=\"adsbygoogle\"\r\n     style=\"display:block; text-align:center;\"\r\n     data-ad-layout=\"in-article\"\r\n     data-ad-format=\"fluid\"\r\n     data-ad-client=\"ca-pub-1234567890123456\"\r\n     data-ad-slot=\"1234567890\"></ins>\r\n<script>\r\n     (adsbygoogle = window.adsbygoogle || []).push({});\r\n</script>', 'middle_content', 'responsive', 'adsense', 1, NULL, NULL, 450, 32, '2025-03-10 14:22:41', '2025-03-24 07:40:12'),
(5, 'Mobile App Promotion', '<script async src=\"https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456\" crossorigin=\"anonymous\"></script>\r\n<!-- Mobile Banner -->\r\n<ins class=\"adsbygoogle\"\r\n     style=\"display:inline-block;width:320px;height:50px\"\r\n     data-ad-client=\"ca-pub-1234567890123456\"\r\n     data-ad-slot=\"6789012345\"></ins>\r\n<script>\r\n     (adsbygoogle = window.adsbygoogle || []).push({});\r\n</script>', 'footer', '320x50', 'adsense', 1, NULL, NULL, 0, 0, '2025-03-24 06:38:57', '2025-03-24 07:40:12');

-- --------------------------------------------------------

--
-- بنية الجدول `ad_placements`
--

CREATE TABLE `ad_placements` (
  `id` int NOT NULL,
  `page` varchar(100) NOT NULL COMMENT 'Page or section where it appears (home, category, article, etc.)',
  `ad_id` int NOT NULL COMMENT 'Ad ID',
  `position` varchar(50) NOT NULL COMMENT 'Position on the page',
  `priority` int DEFAULT '0' COMMENT 'Display priority',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `ad_placements`
--

INSERT INTO `ad_placements` (`id`, `page`, `ad_id`, `position`, `priority`, `is_active`, `created_at`) VALUES
(1, 'home', 1, 'header', 10, 1, '2025-03-24 06:38:57'),
(2, 'article', 2, 'sidebar_top', 5, 1, '2025-03-24 06:38:57'),
(3, 'category', 4, 'middle_content', 3, 1, '2025-03-24 06:38:57'),
(4, 'global', 5, 'footer', 1, 1, '2025-03-24 06:38:57');

-- --------------------------------------------------------

--
-- بنية الجدول `categories`
--

CREATE TABLE `categories` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `parent_id` int DEFAULT NULL,
  `icon_url` varchar(255) DEFAULT NULL COMMENT 'رابط أيقونة الفئة',
  `bg_color` varchar(7) DEFAULT '#FFFFFF' COMMENT 'لون خلفية الفئة',
  `display_order` int DEFAULT '0' COMMENT 'ترتيب العرض',
  `description_ar` text COMMENT 'Arabic description for the category',
  `is_active` tinyint(1) DEFAULT '1' COMMENT 'Indicates if the category is currently active',
  `featured` tinyint(1) DEFAULT '0' COMMENT 'Indicates if the category is featured',
  `image_url` varchar(255) DEFAULT NULL COMMENT 'URL of the category image',
  `slug` varchar(255) NOT NULL,
  `description` text,
  `sort_order` int DEFAULT '0',
  `created_by` varchar(100) DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `categories`
--

INSERT INTO `categories` (`id`, `name`, `parent_id`, `icon_url`, `bg_color`, `display_order`, `description_ar`, `is_active`, `featured`, `image_url`, `slug`, `description`, `sort_order`, `created_by`, `updated_by`, `active`, `updated_at`, `created_at`) VALUES
(1, 'Technology', NULL, 'https://img.icons8.com/color/48/lively-wallpaper.png', '#ff5733', 1, 'وصف الفئة التقنية', 1, 0, 'https://example.com/images/tech.jpg', 'technology', 'Description of Technology', 1, 'admin', 'mahranalsarminy', 1, '2025-03-14 06:19:49', '2025-03-15 23:27:03'),
(2, 'Nature', NULL, 'https://img.icons8.com/color/48/stack-of-photos--v1.png', '#28a745', 2, 'وصف الفئة الطبيعية', 1, 0, 'https://example.com/images/nature.jpg', 'nature', 'Description of Nature', 2, 'admin', 'mahranalsarminy', 1, '2025-03-14 06:19:49', '2025-03-15 23:27:03'),
(3, 'Abstract', NULL, 'https://img.icons8.com/color/48/class-dojo.png', '#ffc107', 3, 'وصف الفئة التجريدية', 1, 0, 'https://example.com/images/abstract.jpg', 'abstract', 'Description of Abstract', 3, 'admin', 'mahranalsarminy', 1, '2025-03-14 06:19:49', '2025-03-15 23:27:03'),
(4, 'Animals', NULL, 'https://img.icons8.com/color/48/medium-icons.png', '#17a2b8', 4, 'وصف الفئة الحيوانية', 1, 0, 'https://example.com/images/animals.jpg', 'animals', 'Description of Animals', 4, 'admin', 'mahranalsarminy', 1, '2025-03-14 06:19:49', '2025-03-15 23:27:03'),
(5, 'Travel', NULL, 'https://img.icons8.com/color/48/stack-of-photos--v1.png', '#6c757d', 5, 'وصف الفئة السفرية', 1, 0, 'https://example.com/images/travel.jpg', 'travel', 'Description of Travel', 5, 'admin', 'mahranalsarminy', 1, '2025-03-14 06:19:49', '2025-03-15 23:27:03'),
(6, 'Islamic', NULL, 'https://cdn1.iconfinder.com/data/icons/festivals-and-holidays-1/512/47_Kaaba_Religion_Islam_Hajj_Islamic_Architecture_And_City-48.png', '#4511d4', 0, NULL, 1, 0, NULL, 'islamic', 'Islamic', 0, 'mahranalsarminy', 'mahranalsarminy', 1, '2025-03-19 03:30:24', '2025-03-14 06:19:49'),
(7, 'Mahran', NULL, '/uploads/categories/icons/icon_67e1561c623ab.png', '#300344', 0, '', 1, 1, '/uploads/categories/images/image_67dcd8e1e6ee3.png', 'mahran', 'Mahran test 2', 0, 'Admin', 'Admin', 1, '2025-03-24 12:54:52', '2025-03-21 03:11:29');

-- --------------------------------------------------------

--
-- بنية الجدول `colors`
--

CREATE TABLE `colors` (
  `id` int NOT NULL,
  `color_name` varchar(50) NOT NULL,
  `hex_code` char(7) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `colors`
--

INSERT INTO `colors` (`id`, `color_name`, `hex_code`, `created_at`) VALUES
(1, 'Red', '#FF0000', '2025-02-20 03:34:24'),
(2, 'Blue', '#0000FF', '2025-02-20 03:34:24'),
(3, 'Green', '#00FF00', '2025-02-20 03:34:24'),
(4, 'Yellow', '#FFFF00', '2025-02-20 03:34:24'),
(5, 'Black', '#000000', '2025-02-20 03:34:24'),
(6, 'prppp', '#9C0AD1', '2025-03-24 12:46:29'),
(7, 'gggggg', '#71F00A', '2025-03-25 02:09:35');

-- --------------------------------------------------------

--
-- بنية الجدول `contact_attempts`
--

CREATE TABLE `contact_attempts` (
  `id` int NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_count` int DEFAULT '1',
  `last_attempt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `contact_attempts`
--

INSERT INTO `contact_attempts` (`id`, `ip_address`, `attempt_count`, `last_attempt`, `created_at`) VALUES
(1, '185.7.214.130', 1, '2025-03-23 23:51:54', '2025-03-23 23:51:54'),
(2, '92.255.85.164', 2, '2025-03-24 13:04:42', '2025-03-24 05:27:59'),
(3, '5.180.61.60', 1, '2025-03-24 07:41:27', '2025-03-24 07:41:27'),
(4, '31.208.251.109', 1, '2025-03-25 02:27:14', '2025-03-25 02:27:14');

-- --------------------------------------------------------

--
-- بنية الجدول `contact_blacklist`
--

CREATE TABLE `contact_blacklist` (
  `id` int NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `reason` text,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied','archived','spam') DEFAULT 'new',
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `reply_message` text,
  `reply_date` timestamp NULL DEFAULT NULL,
  `admin_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `status`, `priority`, `ip_address`, `user_agent`, `reply_message`, `reply_date`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 'wallpix.top', 'info@wallpix.top', 'hhhhhhhh', 'yyyyyyyyyyyyyyyyy', 'replied', 'medium', '83.253.108.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0', 'Dear wallpix.top,\r\n\r\nThank you for contacting us. This is an automatic confirmation that we have received your message regarding \"hhhhhhhh\".\r\n\r\nWe will review your inquiry and get back to you as soon as possible. Our typical response time is within 24-48 hours during business days.\r\n\r\nFor your reference, here\'s a copy of your message:\r\n{message}\r\n\r\nBest regards,\r\n[Company Name] Support Team', '2025-03-24 11:53:51', NULL, '2025-03-13 20:51:53', '2025-03-24 11:53:51'),
(3, 'تجربة عربي', 'hhh@hhh.com', 'موضوع حب', 'احب الصور كثيرا', 'spam', 'medium', '83.253.108.100', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1', NULL, NULL, NULL, '2025-03-13 21:55:05', '2025-03-25 02:13:33'),
(5, 'Johnmes', 'zekisuquc419@gmail.com', 'Hi, i am wrote about your   price for reseller', 'Γεια σου, ήθελα να μάθω την τιμή σας.', 'spam', 'medium', '185.7.214.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36', NULL, NULL, NULL, '2025-03-23 23:51:54', '2025-03-24 11:55:46'),
(6, 'Tedmes', 'aferinohis056@gmail.com', 'Hello  i writing about   the price for reseller', 'Hi, roeddwn i eisiau gwybod eich pris.', 'new', 'medium', '92.255.85.164', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36 Avast/131.0.0.0', NULL, NULL, NULL, '2025-03-24 05:27:59', '2025-03-24 05:27:59'),
(7, 'Max Lammertink', 'max@talosgrowth.nl', 'Your LinkedIn company page', 'Hey!\r\n\r\nComing across your LinkedIn company page on linkedin.com/company/mahrannnn I was surprised about how good your content is and why you don\'t have more followers on the page. Your page should at least grow with 250 - 500 followers per month right? If not, you might currently not be reaching the right audience.\r\n\r\nWith Talos Growth we grow your company page on LinkedIn by liking posts from other companies and individuals that are interested in your business on behalf of your company page.\r\n\r\nThis way, you\'re generating exposure and genuine followers, who are interested in your business. Of course, you define the keywords, location, and other parameters yourself.\r\n\r\nFollowers eventually turn into customers, clients, or even employees. \r\n\r\nWould you like to use our two-week free trial to experience Talos Growth? You can directly signup at https://talosgrowth.com. Since I really see some potential for your page, you can use your personal code ‘mahrannnn-10’ to get a 10% discount for the first 3 months. \r\n\r\nPlease let me know if you’ve questions or check out https://talosgrowth.com.\r\n\r\nMax', 'read', 'medium', '5.180.61.60', 'Mozilla/5.0 (Linux x86_64; rv:114.0) Gecko/20100101 Firefox/114.0', NULL, NULL, NULL, '2025-03-24 07:41:27', '2025-03-24 11:15:02'),
(8, 'Tedmes', 'aferinohis056@gmail.com', 'Hi, i am wrote about     price for reseller', 'Dia duit, theastaigh uaim do phraghas a fháil.', 'new', 'medium', '92.255.85.164', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36', NULL, NULL, NULL, '2025-03-24 13:04:42', '2025-03-24 13:04:42'),
(9, 'Marya', 'marimahran@gmail.com', 'Problemet', 'Gjort det', 'replied', 'medium', '31.208.251.109', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_3_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3.1 Mobile/15E148 Safari/604.1', 'Dear Marya,\r\n\r\nThank you for your positive feedback regarding \"Problemet\". We\'re delighted to hear about your experience with our [product/service].\r\n\r\nYour satisfaction is important to us, and we appreciate you taking the time to share your thoughts.\r\n\r\nBest regards,\r\n[Your Name]\r\nCustomer Relations Team', '2025-03-25 02:27:33', NULL, '2025-03-25 02:27:14', '2025-03-25 02:27:33');

-- --------------------------------------------------------

--
-- بنية الجدول `contact_settings`
--

CREATE TABLE `contact_settings` (
  `id` int NOT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `email_subject_prefix` varchar(100) DEFAULT '[Contact Form]',
  `recaptcha_site_key` varchar(255) DEFAULT NULL,
  `recaptcha_secret_key` varchar(255) DEFAULT NULL,
  `enable_auto_reply` tinyint(1) DEFAULT '0',
  `auto_reply_template_id` int DEFAULT NULL,
  `enable_attachments` tinyint(1) DEFAULT '0',
  `max_file_size` int DEFAULT '5',
  `allowed_file_types` varchar(255) DEFAULT 'pdf,doc,docx,jpg,jpeg,png',
  `required_fields` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `contact_settings`
--

INSERT INTO `contact_settings` (`id`, `contact_email`, `email_subject_prefix`, `recaptcha_site_key`, `recaptcha_secret_key`, `enable_auto_reply`, `auto_reply_template_id`, `enable_attachments`, `max_file_size`, `allowed_file_types`, `required_fields`, `created_at`, `updated_at`) VALUES
(1, 'play.earn.net@gmail.com', 'WallPix.Top', 'ecfdscsdcdscdsvvczqJTQei98BpJATJIpmOrSs3', 'ecfdscsdcdscdsvvczqJTQei98BpJATJIpmOrSs3', 1, 1, 0, 5, 'pdf,doc,docx,jpg,jpeg,png', '[\"name\",\"email\",\"subject\",\"message\"]', '2025-03-14 01:06:51', '2025-03-14 01:45:45');

-- --------------------------------------------------------

--
-- بنية الجدول `contact_templates`
--

CREATE TABLE `contact_templates` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `type` enum('auto_reply','admin_reply') DEFAULT 'admin_reply',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `contact_templates`
--

INSERT INTO `contact_templates` (`id`, `title`, `content`, `type`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'General Auto Reply', 'Dear {name},\r\n\r\nThank you for contacting us. This is an automatic confirmation that we have received your message regarding \"{subject}\".\r\n\r\nWe will review your inquiry and get back to you as soon as possible. Our typical response time is within 24-48 hours during business days.\r\n\r\nFor your reference, here\'s a copy of your message:\r\n{message}\r\n\r\nBest regards,\r\n[Company Name] Support Team', 'auto_reply', 1, '2025-03-13 23:38:22', '2025-03-13 23:38:22'),
(2, 'Technical Support Auto Reply', 'Dear {name},\r\n\r\nThank you for submitting a technical support request. Your ticket has been received and our technical team will review it shortly.\r\n\r\nTo help us resolve your issue more quickly, please ensure you have included:\r\n- Detailed steps to reproduce the issue\r\n- Any error messages you\'re receiving\r\n- Screenshots (if applicable)\r\n\r\nWe aim to respond to all technical support requests within 24 hours during business days.\r\n\r\nBest regards,\r\nTechnical Support Team', 'auto_reply', 1, '2025-03-13 23:38:22', '2025-03-13 23:38:22'),
(3, 'General Response Template', 'Dear {name},\r\n\r\nThank you for your message. \r\n\r\n[Your detailed response here]\r\n\r\nIf you have any further questions, please don\'t hesitate to contact us.\r\n\r\nBest regards,\r\n[Your Name]\r\nCustomer Support Team', 'admin_reply', 1, '2025-03-13 23:38:22', '2025-03-13 23:38:22'),
(4, 'Technical Solution Template', 'Dear {name},\r\n\r\nThank you for reporting this issue. I\'ve reviewed your request regarding \"{subject}\" and here\'s the solution:\r\n\r\n[Technical solution details]\r\n\r\nSteps to implement:\r\n1. [Step one]\r\n2. [Step two]\r\n3. [Step three]\r\n\r\nIf you need any clarification or further assistance, please don\'t hesitate to reply to this message.\r\n\r\nBest regards,\r\n[Your Name]\r\nTechnical Support Team', 'admin_reply', 1, '2025-03-13 23:38:22', '2025-03-13 23:38:22'),
(5, 'Feature Request Response', 'Dear {name},\r\n\r\nThank you for your feature suggestion regarding \"{subject}\". We appreciate you taking the time to share your ideas with us.\r\n\r\nWe have logged your request and will consider it for future updates. Our product team regularly reviews user feedback to help guide our development roadmap.\r\n\r\nWe\'ll keep you updated if we decide to implement this feature.\r\n\r\nBest regards,\r\n[Your Name]\r\nProduct Team', 'admin_reply', 1, '2025-03-13 23:38:22', '2025-03-13 23:38:22'),
(6, 'Inquiry Follow-up', 'Dear {name},\r\n\r\nI hope this email finds you well. I\'m following up on your inquiry about \"{subject}\".\r\n\r\n[Follow-up details/questions]\r\n\r\nPlease let me know if you need any additional information or clarification.\r\n\r\nBest regards,\r\n[Your Name]\r\nCustomer Support Team', 'admin_reply', 1, '2025-03-13 23:38:22', '2025-03-13 23:38:22'),
(7, 'Thank You Response', 'Dear {name},\r\n\r\nThank you for your positive feedback regarding \"{subject}\". We\'re delighted to hear about your experience with our [product/service].\r\n\r\nYour satisfaction is important to us, and we appreciate you taking the time to share your thoughts.\r\n\r\nBest regards,\r\n[Your Name]\r\nCustomer Relations Team', 'admin_reply', 1, '2025-03-13 23:38:22', '2025-03-13 23:38:22');

-- --------------------------------------------------------

--
-- بنية الجدول `deleted_accounts`
--

CREATE TABLE `deleted_accounts` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `deletion_reason` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL COMMENT 'Original account creation date',
  `deletion_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `downloads`
--

CREATE TABLE `downloads` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `wallpaper_id` int NOT NULL COMMENT 'References media.id',
  `download_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `download_type` varchar(50) DEFAULT NULL COMMENT 'Resolution or size type',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `favorites`
--

CREATE TABLE `favorites` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `wallpaper_id` int NOT NULL COMMENT 'References media.id',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `features`
--

CREATE TABLE `features` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(255) DEFAULT NULL,
  `icon_class` varchar(255) DEFAULT NULL,
  `icon_color` varchar(50) DEFAULT 'blue-500',
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `features`
--

INSERT INTO `features` (`id`, `title`, `description`, `image_url`, `icon_class`, `icon_color`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'High-Resolution Images', '<p>Our website offers an extensive collection&nbsp;of&nbsp;<strong>high-resolution images designed </strong>to suit <strong><span style=\"color: rgb(45, 194, 107);\">every mobile device</span></strong>. Whether you\'re looking for vibrant wallpapers,&nbsp;stunning landscapes, or artistic portraits, you\'ll find exactly&nbsp;what you need in ultra-clear quality. Perfect for those who want&nbsp;to elevate their phone\'s appearance with sharp and breathtaking visuals.</p>', '/uploads/features/feature_1741853640_67d293c844055.gif', 'fa fa-image', 'blue-500', 1, 1, '2025-03-13 06:56:06'),
(2, 'AI-Generated Content', '<p>Harnessing the power of artificial intelligence, we&rsquo;ve curated a selection of dynamic and innovative wallpapers. Our AI-powered algorithms create custom designs and backgrounds based on current trends and user preferences, ensuring you always have fresh, cutting-edge options at your fingertips.</p>', '/uploads/features/feature_1741853762_67d29442a8d54.gif', NULL, 'blue-500', 2, 1, '2025-03-13 08:16:02'),
(3, 'Extensive Mobile Wallpapers Collection', '<p>Explore an ever-growing collection of mobile wallpapers designed specifically for smartphones. From minimalist designs to vibrant patterns, you&rsquo;ll discover backgrounds that cater to every style and taste. Our wallpapers are optimized for various screen sizes, so they always fit perfectly on your device.</p>', '/uploads/features/feature_1741853809_67d294710b1a0.gif', NULL, 'blue-500', 3, 1, '2025-03-13 08:16:49'),
(4, 'Easy and Fast Downloads', '<p>We&rsquo;ve made downloading media a breeze. With just a few clicks, you can download high-quality images, wallpapers, and photos directly to your device. Our fast and efficient system ensures that you get the files you need in seconds, without any hassle.</p>', '/uploads/features/feature_1741853840_67d29490356c6.gif', NULL, 'blue-500', 4, 1, '2025-03-13 08:17:20'),
(5, 'Daily Updates with New Content', '<p>Stay up-to-date with our daily content updates. Our team continually adds new images, wallpapers, and photos to keep your mobile experience fresh and exciting. With AI-generated designs and real-time trend analysis, we bring the latest and greatest directly to your device.</p>', '/uploads/features/feature_1741853865_67d294a968d80.gif', NULL, 'blue-500', 5, 1, '2025-03-13 08:17:45'),
(6, 'User-Friendly Interface', '<p>Navigating our website is simple and intuitive. Whether you\'re a first-time visitor or a returning user, you\'ll easily find exactly what you\'re looking for. Our clean, easy-to-use design ensures a smooth browsing experience, making it effortless to browse, download, and enjoy your favorite images.</p>', '/uploads/features/feature_1741853892_67d294c489cdd.gif', NULL, 'blue-500', 6, 1, '2025-03-13 08:18:12');

-- --------------------------------------------------------

--
-- بنية الجدول `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('failed','successful') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '1',
  `attempt_details` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `lock_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `email`, `status`, `ip_address`, `is_admin`, `attempt_details`, `created_at`, `lock_time`) VALUES
(1, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-20 06:03:03', NULL),
(2, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-20 16:42:06', NULL),
(3, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-20 16:44:04', NULL),
(4, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-20 16:44:27', NULL),
(5, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-20 16:45:08', NULL),
(6, 'ggggg@gmail.com', 'successful', '83.253.108.100', 0, NULL, '2025-03-20 17:58:38', NULL),
(7, 'ggggg@gmail.com', 'successful', '194.11.199.107', 0, NULL, '2025-03-20 18:40:37', NULL),
(8, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-20 22:12:17', NULL),
(9, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-21 02:51:46', NULL),
(10, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-21 05:47:49', NULL),
(11, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-21 19:41:42', NULL),
(12, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-22 02:57:50', NULL),
(13, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-22 05:45:19', NULL),
(14, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-22 06:08:11', NULL),
(15, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-22 12:15:28', NULL),
(16, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-22 19:44:45', NULL),
(17, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-22 21:56:21', NULL),
(18, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-23 01:38:44', NULL),
(19, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-23 03:24:04', NULL),
(20, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-24 05:48:10', NULL),
(21, 'ggggg@gmail.com', 'successful', '194.11.199.70', 0, NULL, '2025-03-24 14:06:11', NULL),
(22, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-24 19:54:39', NULL),
(23, 'ggggghhhh@gmail.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-25 01:35:34', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `media`
--

CREATE TABLE `media` (
  `id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `category_id` int DEFAULT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `file_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '',
  `file_size` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `thumbnail_url` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `featured` tinyint(1) DEFAULT '0',
  `width` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `height` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `background_color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `maintain_aspect_ratio` tinyint(1) DEFAULT '0',
  `orientation` enum('portrait','landscape') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'portrait',
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `publish_date` date DEFAULT NULL,
  `paid_content` tinyint(1) DEFAULT '0',
  `watermark_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ai_enhanced` tinyint(1) DEFAULT '0',
  `ai_tags` json DEFAULT NULL,
  `ai_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content_quality_score` decimal(3,2) DEFAULT '0.00',
  `engagement_score` decimal(3,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `media`
--

INSERT INTO `media` (`id`, `title`, `description`, `category_id`, `file_name`, `file_path`, `file_type`, `file_size`, `thumbnail_url`, `status`, `featured`, `width`, `height`, `size_type`, `background_color`, `maintain_aspect_ratio`, `orientation`, `owner`, `license`, `publish_date`, `paid_content`, `watermark_text`, `original_filename`, `external_url`, `created_by`, `created_at`, `ai_enhanced`, `ai_tags`, `ai_description`, `content_quality_score`, `engagement_score`) VALUES
(12, 'Makka', 'A timelapse of the night sky.', 4, '', '', 'video/mp4', '450MB', NULL, 1, 0, NULL, NULL, 'large', NULL, 0, 'portrait', NULL, NULL, NULL, 0, NULL, '', 'https://v1.pinimg.com/videos/mc/720p/b9/6a/6b/b96a6b84a481d27c56d4f67b7843aca8.mp4', 1, '2025-03-15 23:27:28', 0, NULL, NULL, 0.00, 0.00),
(21, 'Islamic wallpaper', 'Islamic wallpaper', 6, NULL, '', '', NULL, NULL, 1, 1, NULL, NULL, NULL, '#ffffff', 0, 'portrait', 'Mahran-Owner', 'Mahran-License', '2025-03-16', 0, NULL, NULL, 'https://i.pinimg.com/736x/72/d1/9f/72d19f3ce7fa1c14ab74f3416d7f7e28.jpg', NULL, '2025-03-16 05:51:10', 0, NULL, NULL, 0.00, 0.00),
(22, 'Islamic wallpaper 2', 'Islamic wallpaper 2', 3, NULL, '', '', NULL, NULL, 1, 0, NULL, NULL, NULL, '#ffffff', 0, 'portrait', 'Mahran-Owner', 'Mahran-License', '2025-03-16', 0, NULL, NULL, 'https://i.pinimg.com/736x/6a/d1/88/6ad188208dfe63abdb8f5c14e79f2c29.jpg', NULL, '2025-03-16 05:52:24', 0, NULL, NULL, 0.01, 0.00),
(23, 'Islamic wallpaper 2', 'Islamic wallpaper 2', 3, NULL, '', '', NULL, NULL, 1, 1, NULL, NULL, NULL, '#ffffff', 0, 'portrait', 'Mahran-Owner', 'Mahran-License', '2025-03-16', 0, NULL, NULL, 'https://i.pinimg.com/736x/ed/4f/66/ed4f666d97d7314ed2215ef3c13c6944.jpg', NULL, '2025-03-16 05:52:24', 0, NULL, NULL, 0.01, 0.00),
(24, 'Islamic wallpaper 2', 'Islamic wallpaper 2', 3, NULL, '', '', NULL, NULL, 1, 1, NULL, NULL, NULL, '#ffffff', 0, 'portrait', 'Mahran-Owner', 'Mahran-License', '2025-03-16', 0, NULL, NULL, 'https://i.pinimg.com/736x/74/fb/3f/74fb3ff0bc2ed7144e9ada38e5362560.jpg', NULL, '2025-03-16 05:52:24', 0, NULL, NULL, 0.00, 0.00),
(25, 'Islamic wallpaper 5', 'Islamic wallpaper 5', 3, '1742104740__dc6e93c2-e760-40ec-8d34-ef5f210b2562.jpeg', 'uploads/media/2025/03/1742104740__dc6e93c2-e760-40ec-8d34-ef5f210b2562.jpeg', 'image/jpeg', '124917', NULL, 1, 1, NULL, NULL, NULL, '#ffffff', 0, 'portrait', 'Mahran-Owner', 'Mahran-License', '2025-03-16', 0, '', NULL, NULL, NULL, '2025-03-16 05:59:00', 0, NULL, NULL, 0.01, 0.00),
(29, 'test2', 'ssssssssssss', 7, '67dcdd1149237_login-page.jpg', '/uploads/media/2025/03/67dcdd1149237_login-page.jpg', '', NULL, '/uploads/thumbnails/2025/03/thumb_67dcdd1149237_login-page.jpg', 1, 1, '1920', '1080', NULL, NULL, 0, 'landscape', NULL, NULL, NULL, 0, NULL, NULL, '', 1, '2025-03-21 03:29:21', 0, NULL, NULL, 0.01, 0.00),
(32, 'mahran123', 'dddddddddddd', 7, '1742795649__0e87af24-f0ff-49a9-9926-a2c3c3740179.jpeg', '/uploads/media/2025/03/1742795649__0e87af24-f0ff-49a9-9926-a2c3c3740179.jpeg', 'image/jpeg', '206.7 KB', '/uploads/thumbnails/2025/03/thumb_1742795649__0e87af24-f0ff-49a9-9926-a2c3c3740179.jpeg', 1, 1, '1024', '1024', 'large', '#ffffff', 0, 'portrait', 'Mahran-Owner', 'Editorial Use Only', NULL, 0, 'Wallpix.Top', '_0e87af24-f0ff-49a9-9926-a2c3c3740179.jpeg', '', 1, '2025-03-24 05:54:09', 1, NULL, 'nnnnnnnnnnnnnnnnnnnn', 0.01, 0.00),
(35, 'mahran12345qqq', 'wwwwwwwwwww', 7, '67e0f9176a52f__37b5c5c8-d01a-494e-82ea-4efce2545565.jpeg', '/uploads/media/2025/03/67e0f9176a52f__37b5c5c8-d01a-494e-82ea-4efce2545565.jpeg', 'image/jpeg', '186.14 KB', '/uploads/thumbnails/2025/03/thumb_67e0f9176a52f__37b5c5c8-d01a-494e-82ea-4efce2545565.jpeg', 1, 1, '750', '1334', 'large', '#ffffff', 0, 'portrait', 'Mahran-Owner', '', NULL, 0, 'Wallpix.Top', '_37b5c5c8-d01a-494e-82ea-4efce2545565.jpeg', NULL, 1, '2025-03-24 06:17:59', 0, NULL, 'sssssssssss', 0.01, 0.00),
(37, 'cccccccccccccccc', 'assssssssss', 7, '67e102957007a__ecf53819-128d-499c-bddf-700b9c6444d2.jpeg', '/uploads/media/2025/03/67e102957007a__ecf53819-128d-499c-bddf-700b9c6444d2.jpeg', 'image/jpeg', '183.83 KB', '/uploads/thumbnails/2025/03/thumb_67e102957007a__ecf53819-128d-499c-bddf-700b9c6444d2.jpeg', 1, 1, '1440', '2560', 'large', '#ff0000', 0, 'portrait', 'Mahran-Owner', 'Free to Use', NULL, 0, 'Wallpix.Top', '_ecf53819-128d-499c-bddf-700b9c6444d2.jpeg', NULL, 1, '2025-03-24 06:58:29', 0, NULL, 'ششششششششش', 0.01, 0.00),
(38, 'paid 1', 'paid 2', 7, '67e1b9005f44f__86dfd24d-b20e-4996-b544-1d7f0f8af977.jpeg', '/uploads/media/2025/03/67e1b9005f44f__86dfd24d-b20e-4996-b544-1d7f0f8af977.jpeg', 'image/jpeg', '237.8 KB', '/uploads/thumbnails/2025/03/thumb_67e1b9005f44f__86dfd24d-b20e-4996-b544-1d7f0f8af977.jpeg', 1, 0, '3840', '2160', 'large', '#ffffff', 0, 'portrait', 'Mahran-Owner', 'Editorial Use Only', NULL, 1, 'Wallpix.Top', '_86dfd24d-b20e-4996-b544-1d7f0f8af977.jpeg', NULL, 1, '2025-03-24 19:56:48', 1, NULL, 'tesssssssssst paid', 0.01, 0.00),
(39, '1', '2', 7, '67e1c3217acc6__5aa4208d-1638-481d-8f12-21c3fcb97ec0.jpeg', '/uploads/media/2025/03/67e1c3217acc6__5aa4208d-1638-481d-8f12-21c3fcb97ec0.jpeg', 'image/jpeg', '204.17 KB', '/uploads/thumbnails/2025/03/thumb_67e1c3217acc6__5aa4208d-1638-481d-8f12-21c3fcb97ec0.jpeg', 1, 1, '1080', '1920', 'xl', '#16195a', 0, 'portrait', 'Mahran-Owner', 'Premium', NULL, 1, 'Wallpix.Top', '_5aa4208d-1638-481d-8f12-21c3fcb97ec0.jpeg', NULL, 1, '2025-03-24 20:40:01', 1, NULL, '4', 0.01, 0.00);

--
-- القوادح `media`
--
DELIMITER $$
CREATE TRIGGER `after_media_insert` AFTER INSERT ON `media` FOR EACH ROW BEGIN
  INSERT IGNORE INTO `media_colors` (`media_id`, `primary_color`, `secondary_color`, `is_dark`, `created_at`) 
  VALUES (NEW.id, '#FFFFFF', '#000000', 0, NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- بنية الجدول `media_batches`
--

CREATE TABLE `media_batches` (
  `id` int NOT NULL,
  `batch_name` varchar(255) NOT NULL,
  `user_id` int NOT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `total_files` int DEFAULT '0',
  `processed_files` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `media_batch_items`
--

CREATE TABLE `media_batch_items` (
  `id` int NOT NULL,
  `batch_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `status` enum('pending','processed','failed') DEFAULT 'pending',
  `media_id` int DEFAULT NULL,
  `error_message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `media_colors`
--

CREATE TABLE `media_colors` (
  `id` int NOT NULL,
  `media_id` int NOT NULL,
  `color_id` int DEFAULT NULL,
  `primary_color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#FFFFFF',
  `secondary_color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '#000000',
  `is_dark` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `media_colors`
--

INSERT INTO `media_colors` (`id`, `media_id`, `color_id`, `primary_color`, `secondary_color`, `is_dark`, `created_at`, `updated_at`) VALUES
(3, 29, NULL, '#14d7a6', '#ee1b1b', 0, '2025-03-21 04:29:21', '2025-03-17 23:21:04'),
(8, 32, NULL, '#FFFFFF', '#000000', 0, '2025-03-24 06:54:09', NULL),
(9, 32, 5, '#000000', '#000000', 0, '2025-03-24 06:54:09', NULL),
(12, 35, NULL, '#FFFFFF', '#000000', 0, '2025-03-24 07:17:59', NULL),
(14, 37, NULL, '#FFFFFF', '#000000', 0, '2025-03-24 07:58:29', NULL),
(15, 37, 5, '#000000', '#000000', 0, '2025-03-24 07:58:29', NULL),
(16, 38, NULL, '#FFFFFF', '#000000', 0, '2025-03-24 20:56:48', NULL),
(17, 38, 2, '#0000FF', '#000000', 0, '2025-03-24 20:56:48', NULL),
(18, 38, 1, '#FF0000', '#000000', 0, '2025-03-24 20:56:48', NULL),
(19, 38, 4, '#FFFF00', '#000000', 0, '2025-03-24 20:56:48', NULL),
(20, 39, NULL, '#FFFFFF', '#000000', 0, '2025-03-24 21:40:01', NULL),
(21, 39, 4, '#FFFF00', '#000000', 0, '2025-03-24 21:40:01', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `media_color_types`
--

CREATE TABLE `media_color_types` (
  `id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `media_color_types`
--

INSERT INTO `media_color_types` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Custom', 'Custom colors manually set', '2025-03-21 04:13:32'),
(2, 'Auto-extracted', 'Colors automatically extracted from image', '2025-03-21 04:13:32'),
(3, 'Light Theme', 'Default light theme colors', '2025-03-21 04:13:32'),
(4, 'Dark Theme', 'Default dark theme colors', '2025-03-21 04:13:32'),
(5, 'Vibrant', 'Vibrant color scheme', '2025-03-21 04:13:32'),
(6, 'Pastel', 'Pastel color scheme', '2025-03-21 04:13:32'),
(7, 'Monochrome', 'Monochrome color scheme', '2025-03-21 04:13:32');

-- --------------------------------------------------------

--
-- بنية الجدول `media_downloads`
--

CREATE TABLE `media_downloads` (
  `id` bigint NOT NULL,
  `media_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `downloaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `session_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `media_downloads`
--

INSERT INTO `media_downloads` (`id`, `media_id`, `user_id`, `downloaded_at`, `session_id`) VALUES
(1, 25, NULL, '2025-03-19 23:03:11', '56c45c1f7190f0d46e5704f0c47a2b63'),
(2, 22, NULL, '2025-03-19 23:03:24', '56c45c1f7190f0d46e5704f0c47a2b63'),
(3, 25, 1, '2025-03-19 23:05:03', 'e336dc38e2683a41a22e205421b3f90c'),
(4, 25, 1, '2025-03-19 23:25:54', 'e336dc38e2683a41a22e205421b3f90c'),
(5, 22, 1, '2025-03-19 23:26:19', 'e336dc38e2683a41a22e205421b3f90c'),
(6, 12, NULL, '2025-03-20 00:04:44', '05f124cb2d5c5b39d59917e6525e9c43'),
(7, 23, NULL, '2025-03-20 00:04:56', '05f124cb2d5c5b39d59917e6525e9c43'),
(8, 25, NULL, '2025-03-20 00:05:05', '05f124cb2d5c5b39d59917e6525e9c43'),
(9, 25, 1, '2025-03-20 02:05:52', 'd92d675a3fd717216367f6724a852f32'),
(10, 22, 1, '2025-03-20 02:09:26', 'd92d675a3fd717216367f6724a852f32'),
(11, 23, 1, '2025-03-20 03:09:35', 'd92d675a3fd717216367f6724a852f32'),
(12, 23, NULL, '2025-03-20 17:55:47', '51eed21ee9308fb47aeb0cef97f1d45b'),
(13, 25, NULL, '2025-03-20 17:55:55', '51eed21ee9308fb47aeb0cef97f1d45b'),
(14, 29, 1, '2025-03-21 03:55:26', 'ddc29cecf5032c596ff79f139194d1a7'),
(15, 23, 1, '2025-03-22 03:04:59', 'ddc29cecf5032c596ff79f139194d1a7'),
(16, 37, 1, '2025-03-24 07:07:04', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(17, 23, 1, '2025-03-24 07:07:32', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(18, 24, 1, '2025-03-24 08:04:30', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(19, 37, NULL, '2025-03-24 13:37:27', '87bd693de70bb61fcb9c36be927fd4c3'),
(20, 32, NULL, '2025-03-24 13:37:51', '87bd693de70bb61fcb9c36be927fd4c3'),
(21, 35, NULL, '2025-03-24 13:38:08', '87bd693de70bb61fcb9c36be927fd4c3'),
(22, 29, NULL, '2025-03-24 13:38:51', '87bd693de70bb61fcb9c36be927fd4c3'),
(23, 25, NULL, '2025-03-24 19:54:28', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(24, 32, NULL, '2025-03-25 02:24:41', '19bbd81b74caf23fb273be3161630c16');

-- --------------------------------------------------------

--
-- بنية الجدول `media_licenses`
--

CREATE TABLE `media_licenses` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `media_licenses`
--

INSERT INTO `media_licenses` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Free to Use', 'Content is free to use without any restrictions', '2025-03-22 12:19:31'),
(2, 'Attribution Required', 'Content can be used freely with proper attribution', '2025-03-22 12:19:31'),
(3, 'Editorial Use Only', 'Content can only be used for editorial purposes', '2025-03-22 12:19:31'),
(4, 'Personal Use Only', 'Content is restricted to personal use only', '2025-03-22 12:19:31'),
(5, 'Premium', 'Content is available for premium users only', '2025-03-22 12:19:31');

-- --------------------------------------------------------

--
-- بنية الجدول `media_tags`
--

CREATE TABLE `media_tags` (
  `media_id` int NOT NULL,
  `tag_id` int NOT NULL,
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `media_tags`
--

INSERT INTO `media_tags` (`media_id`, `tag_id`, `created_by`) VALUES
(12, 3, 1),
(22, 18, NULL),
(23, 18, NULL),
(24, 18, NULL),
(25, 19, NULL),
(29, 24, NULL),
(32, 24, 1),
(37, 20, 1),
(37, 27, 1),
(38, 28, 1),
(39, 29, 1);

-- --------------------------------------------------------

--
-- بنية الجدول `media_views`
--

CREATE TABLE `media_views` (
  `id` bigint NOT NULL,
  `media_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `viewed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `session_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `media_views`
--

INSERT INTO `media_views` (`id`, `media_id`, `user_id`, `viewed_at`, `session_id`) VALUES
(1, 25, NULL, '2025-03-19 22:59:29', '56c45c1f7190f0d46e5704f0c47a2b63'),
(2, 24, NULL, '2025-03-19 22:59:56', '266b667c2173609caaa89d50d3cb619b'),
(3, 12, NULL, '2025-03-19 23:00:26', '56c45c1f7190f0d46e5704f0c47a2b63'),
(4, 23, NULL, '2025-03-19 23:01:02', '33ae9b809e37bcee558481bb0c8a8fd3'),
(5, 22, NULL, '2025-03-19 23:01:09', '56c45c1f7190f0d46e5704f0c47a2b63'),
(6, 22, NULL, '2025-03-19 23:04:20', 'd7a69f7e3456a07d9d5a9d26b77dba30'),
(7, 25, 1, '2025-03-19 23:04:59', 'e336dc38e2683a41a22e205421b3f90c'),
(8, 23, 1, '2025-03-19 23:05:12', 'e336dc38e2683a41a22e205421b3f90c'),
(9, 21, 1, '2025-03-19 23:06:30', 'e336dc38e2683a41a22e205421b3f90c'),
(10, 12, 1, '2025-03-19 23:11:31', 'e336dc38e2683a41a22e205421b3f90c'),
(11, 22, 1, '2025-03-19 23:14:20', 'e336dc38e2683a41a22e205421b3f90c'),
(12, 25, NULL, '2025-03-20 00:04:17', '05f124cb2d5c5b39d59917e6525e9c43'),
(13, 22, NULL, '2025-03-20 00:04:25', '05f124cb2d5c5b39d59917e6525e9c43'),
(14, 12, NULL, '2025-03-20 00:04:33', '05f124cb2d5c5b39d59917e6525e9c43'),
(15, 23, NULL, '2025-03-20 00:04:52', '05f124cb2d5c5b39d59917e6525e9c43'),
(16, 25, NULL, '2025-03-20 00:05:37', 'ee6d169fc888763fc8336b639895378e'),
(17, 24, NULL, '2025-03-20 00:06:58', 'ee6d169fc888763fc8336b639895378e'),
(18, 25, NULL, '2025-03-20 01:44:58', 'e336dc38e2683a41a22e205421b3f90c'),
(19, 23, NULL, '2025-03-20 01:47:11', 'e336dc38e2683a41a22e205421b3f90c'),
(20, 21, NULL, '2025-03-20 01:47:25', 'e336dc38e2683a41a22e205421b3f90c'),
(21, 24, NULL, '2025-03-20 01:47:28', 'e336dc38e2683a41a22e205421b3f90c'),
(22, 12, NULL, '2025-03-20 01:47:55', 'e336dc38e2683a41a22e205421b3f90c'),
(23, 22, 1, '2025-03-20 01:54:25', 'd92d675a3fd717216367f6724a852f32'),
(24, 25, 1, '2025-03-20 01:56:54', 'd92d675a3fd717216367f6724a852f32'),
(25, 25, NULL, '2025-03-20 02:09:52', 'd544d91becc0d8417cfd30ad7b698217'),
(26, 21, NULL, '2025-03-20 02:10:04', 'd544d91becc0d8417cfd30ad7b698217'),
(27, 12, 1, '2025-03-20 02:25:06', 'd92d675a3fd717216367f6724a852f32'),
(28, 23, 1, '2025-03-20 02:39:20', 'd92d675a3fd717216367f6724a852f32'),
(29, 21, NULL, '2025-03-20 04:21:04', '8732cf48472d57b81deab76384017181'),
(30, 24, NULL, '2025-03-20 04:25:13', '15fb85fdc0454836d3763176faad157f'),
(31, 22, NULL, '2025-03-20 04:25:14', 'd1c396a47c1c7604d103a2df453eea34'),
(32, 25, NULL, '2025-03-20 04:25:14', '15fb85fdc0454836d3763176faad157f'),
(33, 23, NULL, '2025-03-20 04:25:15', '15fb85fdc0454836d3763176faad157f'),
(34, 21, NULL, '2025-03-20 04:25:15', '15fb85fdc0454836d3763176faad157f'),
(35, 12, NULL, '2025-03-20 04:25:16', '15fb85fdc0454836d3763176faad157f'),
(36, 22, NULL, '2025-03-20 04:25:25', '15fb85fdc0454836d3763176faad157f'),
(37, 23, NULL, '2025-03-20 04:49:08', 'd92d675a3fd717216367f6724a852f32'),
(38, 25, NULL, '2025-03-20 16:41:46', 'ddc29cecf5032c596ff79f139194d1a7'),
(39, 23, 1, '2025-03-20 16:47:10', 'ddc29cecf5032c596ff79f139194d1a7'),
(40, 23, NULL, '2025-03-20 17:55:30', '51eed21ee9308fb47aeb0cef97f1d45b'),
(41, 25, NULL, '2025-03-20 17:55:51', '51eed21ee9308fb47aeb0cef97f1d45b'),
(42, 29, 1, '2025-03-21 03:29:31', 'ddc29cecf5032c596ff79f139194d1a7'),
(43, 29, NULL, '2025-03-21 13:21:33', '814ab00366e01e932f5eb3d9c77708a5'),
(44, 29, 1, '2025-03-22 03:02:14', 'ddc29cecf5032c596ff79f139194d1a7'),
(45, 23, 1, '2025-03-22 03:02:17', 'ddc29cecf5032c596ff79f139194d1a7'),
(46, 24, 1, '2025-03-22 03:07:03', 'ddc29cecf5032c596ff79f139194d1a7'),
(47, 12, NULL, '2025-03-22 10:43:01', '1ea1ebc1cf1794cd71f66eb1b00d5b00'),
(48, 21, NULL, '2025-03-22 12:27:30', 'db4edbd9cd8fec1a9334270c80aa43dd'),
(49, 22, NULL, '2025-03-22 13:21:18', 'c7e411e8cc2b74b1f6551dd7c35fd60b'),
(50, 21, NULL, '2025-03-23 01:53:07', 'f433c776816b6e55255769c62f6a3f26'),
(51, 29, NULL, '2025-03-23 01:53:13', '33ee0f47f6a034eadda19a6778663c78'),
(52, 29, NULL, '2025-03-23 05:26:19', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(53, 25, NULL, '2025-03-23 06:03:13', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(54, 22, NULL, '2025-03-23 06:28:19', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(55, 24, NULL, '2025-03-23 06:34:09', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(56, 29, 1, '2025-03-23 19:57:07', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(57, 32, 1, '2025-03-24 05:54:12', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(58, 35, 1, '2025-03-24 06:18:03', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(59, 23, NULL, '2025-03-24 06:43:16', 'b3c006c8f7240a808bc6990adbd14c7f'),
(60, 37, 1, '2025-03-24 06:58:32', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(61, 25, 1, '2025-03-24 07:07:24', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(62, 23, 1, '2025-03-24 07:07:27', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(63, 24, NULL, '2025-03-24 07:52:53', '888f944eac544fda5f69f078490ed4bd'),
(64, 24, 1, '2025-03-24 08:04:23', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(65, 22, 1, '2025-03-24 08:18:45', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(66, 21, 1, '2025-03-24 09:10:36', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(67, 37, NULL, '2025-03-24 13:37:01', '87bd693de70bb61fcb9c36be927fd4c3'),
(68, 32, NULL, '2025-03-24 13:37:38', '87bd693de70bb61fcb9c36be927fd4c3'),
(69, 35, NULL, '2025-03-24 13:38:03', '87bd693de70bb61fcb9c36be927fd4c3'),
(70, 29, NULL, '2025-03-24 13:38:45', '87bd693de70bb61fcb9c36be927fd4c3'),
(71, 37, 5, '2025-03-24 14:08:17', '061700fe67bed6ad2d7daee925f1882a'),
(72, 25, NULL, '2025-03-24 19:12:58', '19258abdb3fddcc97e2ebfa3b5aa24f2'),
(73, 35, NULL, '2025-03-24 19:43:46', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(74, 25, NULL, '2025-03-24 19:51:10', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(75, 38, 1, '2025-03-24 19:56:54', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(76, 37, 1, '2025-03-24 20:11:50', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(77, 32, 1, '2025-03-24 20:37:33', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(78, 39, 1, '2025-03-24 20:40:08', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(79, 39, NULL, '2025-03-24 20:55:28', '6f1c79aafbcab4d20ef64b970d2b7b15'),
(80, 37, NULL, '2025-03-24 20:55:36', '6f1c79aafbcab4d20ef64b970d2b7b15'),
(81, 35, NULL, '2025-03-24 20:55:43', '6f1c79aafbcab4d20ef64b970d2b7b15'),
(82, 29, NULL, '2025-03-24 20:57:49', 'c78e3bd1b66ef98a1e097a4fdbc35862'),
(83, 32, NULL, '2025-03-24 21:15:52', 'd8f3d4b94c5d9639c0fab05ac953a2fb'),
(84, 25, 1, '2025-03-25 01:48:30', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(85, 37, 1, '2025-03-25 01:53:52', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(86, 39, 1, '2025-03-25 02:00:41', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(87, 39, NULL, '2025-03-25 02:04:25', '952c53e5b57a066473f91dc62273be13'),
(88, 37, NULL, '2025-03-25 02:04:28', 'e0242d05e0faa3af2569e4e0a5344315'),
(89, 35, NULL, '2025-03-25 02:04:30', '3e6108b3b657a638ae68c2ccec989a48'),
(90, 32, NULL, '2025-03-25 02:04:32', 'b2c2f9f67aa69af9928172e0bc96f847'),
(91, 38, NULL, '2025-03-25 02:04:34', '521920020e1ec0ea3fd60d642c82ac9f'),
(92, 38, 1, '2025-03-25 02:21:37', '9fedf0e9633b26d0d1fb5a68cfb2d924'),
(93, 39, NULL, '2025-03-25 02:24:30', '19bbd81b74caf23fb273be3161630c16'),
(94, 32, NULL, '2025-03-25 02:24:37', '19bbd81b74caf23fb273be3161630c16');

--
-- القوادح `media_views`
--
DELIMITER $$
CREATE TRIGGER `update_media_quality_score` AFTER INSERT ON `media_views` FOR EACH ROW BEGIN
    UPDATE media m
    SET content_quality_score = (
        SELECT (
            (COUNT(DISTINCT mv.user_id) * 0.4) + 
            (COUNT(DISTINCT md.user_id) * 0.6)
        ) / 100
        FROM media_views mv
        LEFT JOIN media_downloads md ON m.id = md.media_id
        WHERE m.id = NEW.media_id
        GROUP BY m.id
    )
    WHERE m.id = NEW.media_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- بنية الجدول `menus`
--

CREATE TABLE `menus` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `menus`
--

INSERT INTO `menus` (`id`, `name`) VALUES
(2, 'Top'),
(3, 'Footer');

-- --------------------------------------------------------

--
-- بنية الجدول `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int NOT NULL,
  `menu_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `menu_items`
--

INSERT INTO `menu_items` (`id`, `menu_id`, `name`, `url`, `sort_order`) VALUES
(1, 2, 'Home', 'https://mahrannnn.se/', 1),
(2, 2, 'Mahran', 'https://www.facebook.com/', 2);

-- --------------------------------------------------------

--
-- بنية الجدول `navbar`
--

CREATE TABLE `navbar` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `navbar`
--

INSERT INTO `navbar` (`id`, `name`, `url`, `sort_order`) VALUES
(1, 'Mahran', 'https://www.facebook.com/', 1);

-- --------------------------------------------------------

--
-- بنية الجدول `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('stripe','paypal') NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `transaction_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `payments`
--

INSERT INTO `payments` (`id`, `user_id`, `amount`, `payment_method`, `status`, `transaction_id`, `created_at`) VALUES
(1, 2, 5.00, 'paypal', 'completed', 'txn_123456789', '2025-02-20 01:31:31'),
(2, 3, 10.00, 'stripe', 'completed', 'ch_987654321', '2025-02-20 01:31:31');

-- --------------------------------------------------------

--
-- بنية الجدول `payment_gateways`
--

CREATE TABLE `payment_gateways` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '0',
  `test_mode` tinyint(1) DEFAULT '1',
  `logo_url` varchar(255) DEFAULT NULL,
  `config` json DEFAULT NULL COMMENT 'Stores API keys and other configuration',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `payment_gateways`
--

INSERT INTO `payment_gateways` (`id`, `name`, `display_name`, `description`, `is_active`, `test_mode`, `logo_url`, `config`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'paypal', 'PayPal', 'Accept payments via PayPal', 0, 1, '/assets/images/paypal-logo.png', '{\"live\": {\"client_id\": \"\", \"webhook_id\": \"\", \"client_secret\": \"\", \"webhook_secret\": \"\"}, \"test\": {\"client_id\": \"ggggghhhh@gmail.com\", \"webhook_id\": \"\", \"client_secret\": \"Kaka198500\", \"webhook_secret\": \"\"}}', '2025-03-20 07:09:15', '2025-03-24 13:18:25', NULL),
(2, 'stripe', 'Stripe', 'Accept credit card payments via Stripe', 0, 1, '/assets/images/stripe-logo.png', '{\"live\": {\"secret_key\": \"\", \"webhook_secret\": \"\", \"publishable_key\": \"\"}, \"test\": {\"secret_key\": \"Kaka198500\", \"webhook_secret\": \"\", \"publishable_key\": \"ggggghhhh@gmail.com\"}}', '2025-03-20 07:09:15', '2025-03-24 13:18:27', NULL);

-- --------------------------------------------------------

--
-- بنية الجدول `privacy_content`
--

CREATE TABLE `privacy_content` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `privacy_content`
--

INSERT INTO `privacy_content` (`id`, `title`, `content`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'Privacy', '<p class=\"mt-4\">At WallPiX.Top, accessible from&nbsp;<a class=\"text-blue-500\" href=\"https://www.wallpix.top/\">[https://www.wallpix.top](https://www.wallpix.top)</a>, the privacy of our visitors is one of our main priorities. This Privacy Policy document explains the types of information we collect and how we use it.</p>\r\n<div class=\"mt-6 space-y-4\">\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">1. Information We Collect</h3>\r\n<ul class=\"list-disc pl-6\">\r\n<li><strong>Personal Information:</strong>&nbsp;Names, email addresses, or any other details voluntarily provided by users.</li>\r\n<li><strong>Non-Personal Information:</strong>&nbsp;Browser type, device information, IP address, and user activity on the site.</li>\r\n</ul>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">2. How We Use Your Information</h3>\r\n<ul class=\"list-disc pl-6\">\r\n<li>Improving website functionality and user experience.</li>\r\n<li>Personalizing advertisements and content to suit user preferences.</li>\r\n<li>Monitoring website performance and user engagement.</li>\r\n</ul>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">3. Google AdSense and Third-Party Advertisers</h3>\r\n<p>WallPiX.Top uses Google AdSense and other third-party advertising networks. These advertisers may use cookies, web beacons, or similar tracking technologies to provide relevant ads based on user activity. Their practices are governed by their own Privacy Policies.</p>\r\n<p>Google&rsquo;s use of the DoubleClick cookie enables it and its partners to serve ads based on users&rsquo; visits to WallPiX.Top and other websites. Users can manage their preferences for ads by visiting the&nbsp;<a class=\"text-blue-500\" href=\"https://adssettings.google.com/\">Google Ads Settings page</a>.</p>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">4. Cookies and Tracking</h3>\r\n<p>We use cookies and other tracking technologies to:</p>\r\n<ul class=\"list-disc pl-6\">\r\n<li>Understand how visitors interact with our website.</li>\r\n<li>Deliver tailored advertisements and measure ad performance.</li>\r\n</ul>\r\n<p>Users can disable cookies through their browser settings, but this may affect website functionality.</p>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">5. Data Sharing</h3>\r\n<p>We do not sell, trade, or otherwise transfer your personal information to third parties, except:</p>\r\n<ul class=\"list-disc pl-6\">\r\n<li>To trusted partners who assist in website operations.</li>\r\n<li>When required by law or to protect legal rights.</li>\r\n</ul>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">6. User Consent</h3>\r\n<p>By using WallPiX.Top, you consent to our Privacy Policy and agree to its terms, including the use of tracking and advertising technologies.</p>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">7. Third-Party Privacy Policies</h3>\r\n<p>WallPiX.Top&rsquo;s Privacy Policy does not apply to other advertisers or websites. We encourage users to review the Privacy Policies of these third-party ad servers for more information.</p>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">8. Data Security</h3>\r\n<p>We take reasonable steps to protect your data against unauthorized access, alteration, disclosure, or destruction.</p>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">9. Changes to This Privacy Policy</h3>\r\n<p>We may update our Privacy Policy from time to time. We encourage users to review this page periodically for any changes.</p>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">10. Contact Us</h3>\r\n<p>If you have any questions about this Privacy Policy, you can contact us at:</p>\r\n<ul class=\"list-disc pl-6\">\r\n<li>Email:&nbsp;<a class=\"text-blue-500\" href=\"mailto:support@wallpix.top\">support@wallpix.top</a></li>\r\n</ul>\r\n</div>\r\n</div>', 0, 1, '2025-03-13 19:18:21');

-- --------------------------------------------------------

--
-- بنية الجدول `resolutions`
--

CREATE TABLE `resolutions` (
  `id` int NOT NULL,
  `resolution` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `resolutions`
--

INSERT INTO `resolutions` (`id`, `resolution`, `created_at`) VALUES
(1, '1920x1080', '2025-02-20 03:34:11'),
(2, '1280x720', '2025-02-20 03:34:11'),
(3, '3840x2160', '2025-02-20 03:34:11'),
(4, '800x600', '2025-02-20 03:34:11'),
(5, '2560x1440', '2025-02-20 03:34:11'),
(7, '4800x2800', '2025-03-22 03:02:57'),
(8, '8800x4800', '2025-03-23 02:11:15');

-- --------------------------------------------------------

--
-- بنية الجدول `search_box_settings`
--

CREATE TABLE `search_box_settings` (
  `id` int NOT NULL,
  `background_type` varchar(10) NOT NULL DEFAULT 'color',
  `background_value` varchar(255) NOT NULL DEFAULT '#ffffff',
  `categories` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `search_box_settings`
--

INSERT INTO `search_box_settings` (`id`, `background_type`, `background_value`, `categories`) VALUES
(1, 'color', '#ff0000', 'Technology,Nature,Abstract,');

-- --------------------------------------------------------

--
-- بنية الجدول `search_suggestions`
--

CREATE TABLE `search_suggestions` (
  `id` int NOT NULL,
  `keyword` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'الكلمة المقترحة للبحث',
  `search_count` int DEFAULT '0' COMMENT 'عدد مرات البحث',
  `last_searched_at` timestamp NULL DEFAULT NULL COMMENT 'آخر وقت تم البحث فيه',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول الكلمات المقترحة للبحث';

-- --------------------------------------------------------

--
-- بنية الجدول `seo_analysis`
--

CREATE TABLE `seo_analysis` (
  `id` int NOT NULL,
  `content_id` int NOT NULL,
  `content_type` varchar(50) NOT NULL,
  `title_score` int DEFAULT '0',
  `description_score` int DEFAULT '0',
  `keyword_score` int DEFAULT '0',
  `content_score` int DEFAULT '0',
  `total_score` int DEFAULT '0',
  `recommendations` text,
  `analyzed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `seo_settings`
--

CREATE TABLE `seo_settings` (
  `id` int NOT NULL,
  `setting_name` varchar(100) NOT NULL,
  `setting_value` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `seo_settings`
--

INSERT INTO `seo_settings` (`id`, `setting_name`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'sitemap_index', 'sitemap.xml', '2025-03-24 10:50:18', '2025-03-24 10:50:18'),
(2, 'sitemap_last_generated', '2025-03-25 03:12:45', '2025-03-24 10:50:18', '2025-03-25 02:12:45'),
(3, 'robots_txt_content', 'User-agent: *\nDisallow: /admin/\nDisallow: /theme/admin/\nSitemap: https://mahrannnn.se/sitemap.xml', '2025-03-24 10:50:18', '2025-03-24 10:50:18'),
(4, 'meta_title_format', '{title} | {site_name}', '2025-03-24 10:50:18', '2025-03-24 10:50:18'),
(5, 'meta_description_format', '{excerpt}', '2025-03-24 10:50:18', '2025-03-24 10:50:18'),
(6, 'enable_ai_seo', '1', '2025-03-24 10:50:18', '2025-03-24 10:50:18'),
(7, 'ai_seo_last_run', NULL, '2025-03-24 10:50:18', '2025-03-24 10:50:18'),
(8, 'canonical_url_format', '{permalink}', '2025-03-24 10:50:18', '2025-03-24 10:50:18'),
(9, 'enable_schema_markup', '1', '2025-03-24 10:50:18', '2025-03-24 10:50:18'),
(10, 'enable_social_meta', '1', '2025-03-24 10:50:18', '2025-03-24 10:50:18');

-- --------------------------------------------------------

--
-- بنية الجدول `sitemaps`
--

CREATE TABLE `sitemaps` (
  `id` int NOT NULL,
  `type` varchar(50) NOT NULL,
  `filename` varchar(100) NOT NULL,
  `url_count` int DEFAULT '0',
  `file_size` int DEFAULT '0',
  `last_generated` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `sitemaps`
--

INSERT INTO `sitemaps` (`id`, `type`, `filename`, `url_count`, `file_size`, `last_generated`, `created_at`, `updated_at`) VALUES
(1, 'content', 'sitemap-content.xml', 0, 0, NULL, '2025-03-24 10:50:18', '2025-03-24 10:50:18'),
(2, 'category', 'sitemap-categories.xml', 7, 1279, '2025-03-24 11:06:02', '2025-03-24 10:50:18', '2025-03-24 11:06:02'),
(3, 'tag', 'sitemap-tags.xml', 11, 1892, '2025-03-24 11:54:49', '2025-03-24 10:50:18', '2025-03-24 11:54:49'),
(4, 'author', 'sitemap-authors.xml', 0, 0, NULL, '2025-03-24 10:50:18', '2025-03-24 10:50:18'),
(5, 'image', 'sitemap-images.xml', 0, 0, NULL, '2025-03-24 10:50:18', '2025-03-24 10:50:18');

-- --------------------------------------------------------

--
-- بنية الجدول `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int NOT NULL,
  `site_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `site_keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `site_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `site_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `google_analytics` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `latest_items_count` int NOT NULL DEFAULT '10',
  `featured_wallpapers_count` int NOT NULL DEFAULT '10',
  `featured_media_count` int NOT NULL DEFAULT '10',
  `facebook_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `twitter_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instagram_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `news_ticker_enabled` tinyint(1) DEFAULT '1' COMMENT 'Enable or disable the news ticker',
  `footer_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'محتوى الفوتر',
  `site_logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL of the site logo',
  `site_favicon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `footer_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `header_menu_id` int DEFAULT NULL,
  `footer_menu_id` int DEFAULT NULL,
  `contact_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dark_mode` tinyint(1) DEFAULT '0',
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT '0',
  `language` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en',
  `enable_header` tinyint(1) DEFAULT '0',
  `enable_footer` tinyint(1) DEFAULT '0',
  `enable_navbar` tinyint(1) DEFAULT '0',
  `enable_search_box` tinyint(1) DEFAULT '0',
  `enable_categories` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `site_settings`
--

INSERT INTO `site_settings` (`id`, `site_name`, `site_title`, `site_description`, `site_keywords`, `site_url`, `site_email`, `google_analytics`, `latest_items_count`, `featured_wallpapers_count`, `featured_media_count`, `facebook_url`, `twitter_url`, `instagram_url`, `youtube_url`, `created_at`, `updated_at`, `created_by`, `updated_by`, `news_ticker_enabled`, `footer_content`, `site_logo`, `site_favicon`, `footer_text`, `header_menu_id`, `footer_menu_id`, `contact_email`, `dark_mode`, `maintenance_mode`, `language`, `enable_header`, `enable_footer`, `enable_navbar`, `enable_search_box`, `enable_categories`) VALUES
(1, 'Wallpix', 'High-Quality Mobile Wallpapers | HD Phone Backgrounds - Free Download', 'Discover a vast collection of high-quality images and wallpapers for mobile and desktop devices. Explore diverse categories like nature, technology, animals, travel, and more. Download stunning, free, high-resolution wallpapers that enhance your device\'s aesthetics and personalize your experience. Enjoy a user-friendly interface and an extensive selection designed to meet all your visual needs.', 'high-quality images, free wallpapers, mobile wallpapers, desktop backgrounds, nature wallpapers, technology images, animal wallpapers, travel backgrounds, HD wallpapers, stunning wallpapers, free downloads, high-resolution images, aesthetic wallpapers, custom wallpapers, personal wallpapers', 'https://mahrannnn.se', 'ggggghhhh@gmail.com', 'ممممممممممممممممممم', 5, 6, 6, 'https://www.facebook.com/unifaktoura/', 'https://twitter.com/unifaktoura', 'https://Instagram.com/unifaktoura', 'https://YouTube.com/unifaktoura', '2025-03-14 04:46:34', '2025-03-24 20:54:28', 'mahranalsarminy', '1', 1, 'WallPix Media and Wallpaper Platform', 'logo-wallpix.png', '', 'WallPix All rights reserved.', 2, 2, 'info@wallpix.top', 0, 0, 'en', 1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- بنية الجدول `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `plan` enum('free','regular','premium') DEFAULT 'free',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `tags`
--

CREATE TABLE `tags` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `tags`
--

INSERT INTO `tags` (`id`, `name`, `slug`, `updated_by`, `created_by`, `created_at`) VALUES
(1, 'Nature', 'nature', NULL, 1, '2025-03-15 23:27:14'),
(2, 'Landscape', 'landscape', NULL, 1, '2025-03-15 23:27:14'),
(3, 'Travel', 'travel', NULL, 1, '2025-03-15 23:27:14'),
(4, 'Animals', 'animals', NULL, 1, '2025-03-15 23:27:14'),
(7, 'cccc', 'cccc', NULL, NULL, '2025-03-16 04:52:04'),
(8, 'eeeee', 'eeeee', NULL, NULL, '2025-03-16 04:52:04'),
(18, 'muslim', 'muslim', NULL, NULL, '2025-03-16 05:52:24'),
(19, 'islamic', 'islamic', NULL, NULL, '2025-03-16 05:59:00'),
(20, 'mahran', 'mahran', NULL, 1, '2025-03-20 01:52:17'),
(24, 'test2', 'test2', NULL, NULL, '2025-03-21 03:29:21'),
(27, 'عربي', 'tag-67e102958244a', NULL, NULL, '2025-03-24 06:58:29'),
(28, 'paid', 'paid', NULL, NULL, '2025-03-24 19:56:48'),
(29, '3', '3', NULL, NULL, '2025-03-24 20:40:01');

-- --------------------------------------------------------

--
-- بنية الجدول `terms_content`
--

CREATE TABLE `terms_content` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `terms_content`
--

INSERT INTO `terms_content` (`id`, `title`, `content`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'Terms & Conditions', '<p class=\"mt-4\">Welcome to WallPiX.Top! These terms and conditions outline the rules and regulations for using our website, located at&nbsp;<a class=\"text-blue-500\" href=\"https://www.wallpix.top/\">[https://www.wallpix.top](https://www.wallpix.top)</a>. By accessing this website, you accept these terms and conditions in full. Do not continue to use WallPiX.Top if you do not agree to all the terms and conditions stated on this page.</p>\r\n<div class=\"mt-6 space-y-4\">\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">1. Use of the Website</h3>\r\n<ul class=\"list-disc pl-6\">\r\n<li>You must be at least 13 years of age to use this website.</li>\r\n<li>The content and services offered by WallPiX.Top are provided for personal, non-commercial use only.</li>\r\n<li>You agree not to use the website in any way that may harm its performance, accessibility, or functionality.</li>\r\n</ul>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">2. Intellectual Property</h3>\r\n<ul class=\"list-disc pl-6\">\r\n<li>All content on WallPiX.Top, including images, backgrounds, and media, is owned or licensed by WallPiX.Top and is protected by copyright and intellectual property laws.</li>\r\n<li>Users are prohibited from reproducing, distributing, modifying, or using any content without prior written consent.</li>\r\n</ul>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">3. Advertisements and User Tracking</h3>\r\n<p>WallPiX.Top features advertisements provided by Google AdSense and other advertising networks. These ads may utilize cookies or tracking technologies.</p>\r\n<p>By using this website, you consent to the use of cookies and agree to our tracking practices, which are aimed at enhancing your experience and delivering relevant advertisements.</p>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">4. User Content and Submissions</h3>\r\n<ul class=\"list-disc pl-6\">\r\n<li>If you upload or share any content on WallPiX.Top, you grant us a non-exclusive, royalty-free license to use, distribute, and display that content.</li>\r\n<li>Any user-generated content must comply with applicable laws and must not violate the rights of any third party.</li>\r\n</ul>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">5. Third-Party Links</h3>\r\n<p>WallPiX.Top may include links to third-party websites or services. We are not responsible for the content or policies of these external websites.</p>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">6. Disclaimer of Warranties</h3>\r\n<ul class=\"list-disc pl-6\">\r\n<li>The content and services on WallPiX.Top are provided \"as is\" and \"as available.\" We make no warranties or representations regarding the accuracy, reliability, or completeness of the content.</li>\r\n<li>WallPiX.Top disclaims all warranties, including but not limited to implied warranties of merchantability and fitness for a particular purpose.</li>\r\n</ul>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">7. Limitation of Liability</h3>\r\n<p>WallPiX.Top shall not be held liable for any direct, indirect, incidental, or consequential damages arising from the use or inability to use this website or its content.</p>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">8. Indemnification</h3>\r\n<p>You agree to indemnify and hold WallPiX.Top harmless from any claims, losses, damages, liabilities, or expenses (including legal fees) resulting from your use of the website or violation of these terms.</p>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">9. Changes to Terms</h3>\r\n<p>WallPiX.Top reserves the right to update or modify these terms and conditions at any time. It is your responsibility to review this page periodically for updates.</p>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">10. Governing Law</h3>\r\n<p>These terms and conditions shall be governed by and interpreted in accordance with the laws of the country or region where WallPiX.Top operates.</p>\r\n</div>\r\n<div class=\"space-y-2\">\r\n<h3 class=\"text-xl font-semibold\">11. Contact Information</h3>\r\n<p>If you have any questions about these terms, please contact us at:</p>\r\n<ul class=\"list-disc pl-6\">\r\n<li>Email:&nbsp;<a class=\"text-blue-500\" href=\"mailto:support@wallpix.top\">support@wallpix.top</a></li>\r\n</ul>\r\n</div>\r\n</div>', 0, 1, '2025-03-13 20:06:12');

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `role` enum('admin','subscriber','free_user') DEFAULT 'free_user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `bio` text COMMENT 'Biography of the user',
  `last_login` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `last_ip` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `profile_picture`, `role`, `created_at`, `bio`, `last_login`, `updated_at`, `active`, `last_ip`) VALUES
(1, 'Admin', 'ggggghhhh@gmail.com', 'ecfdscsdcdscdsvvczqJTQei98BpJATJIpmOrSs3', 'uploads/profile_pictures/1_1742489124_logo-wallpix.png', 'admin', '2025-02-20 01:31:31', '', '2025-03-25 01:35:34', '2025-03-18 09:10:39', 1, '83.253.108.100'),
(2, '', 'subscriber@example.com', 'ecfdscsdcdscdsvvczqJTQei98BpJATJIpmOrSs3', NULL, 'subscriber', '2025-02-20 01:31:31', NULL, '2025-03-17 06:12:00', '2025-03-17 06:12:00', 1, NULL),
(3, '', 'freeuser@example.com', 'ecfdscsdcdscdsvvczqJTQei98BpJATJIpmOrSs3', NULL, 'free_user', '2025-02-20 01:31:31', NULL, '2025-03-17 06:12:00', '2025-03-17 06:12:00', 1, NULL),
(5, 'Test', 'ggggg@gmail.com', 'ecfdscsdcdscdsvvczqJTQei98BpJATJIpmOrSs3', 'uploads/profile_pictures/5_1742493554_dc259757-534d-4ba5-aa80-e6bc4c644e71.jpeg', 'free_user', '2025-03-20 17:58:05', 'Mahran', '2025-03-24 14:06:11', NULL, 1, '194.11.199.70');

-- --------------------------------------------------------

--
-- بنية الجدول `user_subscriptions`
--

CREATE TABLE `user_subscriptions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `subscription_plan` enum('free','regular','premium') DEFAULT 'free',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','expired','cancelled') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `user_subscriptions`
--

INSERT INTO `user_subscriptions` (`id`, `user_id`, `subscription_plan`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 2, 'regular', '2023-10-01', '2023-11-01', 'active', '2025-02-20 01:31:31'),
(2, 3, 'premium', '2023-10-01', '2023-11-01', 'active', '2025-02-20 01:31:31');

-- --------------------------------------------------------

--
-- بنية الجدول `watermark_settings`
--

CREATE TABLE `watermark_settings` (
  `id` int NOT NULL,
  `type` enum('text','image') NOT NULL DEFAULT 'text',
  `text_content` varchar(255) DEFAULT NULL,
  `text_font` varchar(100) DEFAULT 'Arial',
  `text_size` int DEFAULT '24',
  `text_color` varchar(20) DEFAULT '#FFFFFF',
  `text_opacity` decimal(3,2) DEFAULT '0.70',
  `image_path` varchar(255) DEFAULT NULL,
  `image_opacity` decimal(3,2) DEFAULT '0.70',
  `position` enum('top-left','top-right','bottom-left','bottom-right','center','full') NOT NULL DEFAULT 'bottom-right',
  `padding` int DEFAULT '10',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `apply_to_new` tinyint(1) NOT NULL DEFAULT '1',
  `apply_to_downloads` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- إرجاع أو استيراد بيانات الجدول `watermark_settings`
--

INSERT INTO `watermark_settings` (`id`, `type`, `text_content`, `text_font`, `text_size`, `text_color`, `text_opacity`, `image_path`, `image_opacity`, `position`, `padding`, `is_active`, `apply_to_new`, `apply_to_downloads`, `created_at`, `updated_at`) VALUES
(1, 'text', 'WallPix.Top', 'Arial', 24, '#FFFFFF', 0.70, NULL, 0.70, 'bottom-right', 10, 1, 1, 1, '2025-03-22 12:21:56', '2025-03-22 12:21:56');

--
-- Indexes for dumped tables
--

--
-- فهارس للجدول `about_content`
--
ALTER TABLE `about_content`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- فهارس للجدول `add_ons`
--
ALTER TABLE `add_ons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- فهارس للجدول `add_on_updates`
--
ALTER TABLE `add_on_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `add_on_id` (`add_on_id`);

--
-- فهارس للجدول `ads`
--
ALTER TABLE `ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `position_idx` (`position`),
  ADD KEY `status_idx` (`status`);

--
-- فهارس للجدول `ad_placements`
--
ALTER TABLE `ad_placements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ad_id_idx` (`ad_id`),
  ADD KEY `page_position_idx` (`page`,`position`);

--
-- فهارس للجدول `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `colors`
--
ALTER TABLE `colors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `color_name` (`color_name`);

--
-- فهارس للجدول `contact_attempts`
--
ALTER TABLE `contact_attempts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`);

--
-- فهارس للجدول `contact_blacklist`
--
ALTER TABLE `contact_blacklist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_idx` (`ip_address`),
  ADD KEY `email_idx` (`email`);

--
-- فهارس للجدول `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status_idx` (`status`),
  ADD KEY `email_idx` (`email`);

--
-- فهارس للجدول `contact_settings`
--
ALTER TABLE `contact_settings`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `contact_templates`
--
ALTER TABLE `contact_templates`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `deleted_accounts`
--
ALTER TABLE `deleted_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_deleted_user_id` (`user_id`);

--
-- فهارس للجدول `downloads`
--
ALTER TABLE `downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_downloads` (`user_id`),
  ADD KEY `idx_wallpaper_downloads` (`wallpaper_id`);

--
-- فهارس للجدول `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorite` (`user_id`,`wallpaper_id`),
  ADD KEY `idx_user_favorites` (`user_id`),
  ADD KEY `idx_wallpaper_favorites` (`wallpaper_id`);

--
-- فهارس للجدول `features`
--
ALTER TABLE `features`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- فهارس للجدول `media_batches`
--
ALTER TABLE `media_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- فهارس للجدول `media_batch_items`
--
ALTER TABLE `media_batch_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `media_id` (`media_id`);

--
-- فهارس للجدول `media_colors`
--
ALTER TABLE `media_colors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_media_colors_media` (`media_id`),
  ADD KEY `fk_media_colors_color` (`color_id`);

--
-- فهارس للجدول `media_color_types`
--
ALTER TABLE `media_color_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_color_type_name` (`name`);

--
-- فهارس للجدول `media_downloads`
--
ALTER TABLE `media_downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media_downloads` (`media_id`),
  ADD KEY `idx_user_downloads` (`user_id`);

--
-- فهارس للجدول `media_licenses`
--
ALTER TABLE `media_licenses`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `media_tags`
--
ALTER TABLE `media_tags`
  ADD PRIMARY KEY (`media_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- فهارس للجدول `media_views`
--
ALTER TABLE `media_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media_views` (`media_id`),
  ADD KEY `idx_user_views` (`user_id`);

--
-- فهارس للجدول `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_items_ibfk_2` (`menu_id`);

--
-- فهارس للجدول `navbar`
--
ALTER TABLE `navbar`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- فهارس للجدول `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- فهارس للجدول `payment_gateways`
--
ALTER TABLE `payment_gateways`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- فهارس للجدول `privacy_content`
--
ALTER TABLE `privacy_content`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `resolutions`
--
ALTER TABLE `resolutions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `resolution` (`resolution`);

--
-- فهارس للجدول `search_box_settings`
--
ALTER TABLE `search_box_settings`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `search_suggestions`
--
ALTER TABLE `search_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_keyword` (`keyword`);

--
-- فهارس للجدول `seo_analysis`
--
ALTER TABLE `seo_analysis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_idx` (`content_id`,`content_type`);

--
-- فهارس للجدول `seo_settings`
--
ALTER TABLE `seo_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_name_idx` (`setting_name`);

--
-- فهارس للجدول `sitemaps`
--
ALTER TABLE `sitemaps`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_idx` (`type`);

--
-- فهارس للجدول `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `header_menu_id` (`header_menu_id`),
  ADD KEY `footer_menu_id` (`footer_menu_id`);

--
-- فهارس للجدول `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- فهارس للجدول `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- فهارس للجدول `terms_content`
--
ALTER TABLE `terms_content`
  ADD PRIMARY KEY (`id`);

--
-- فهارس للجدول `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `last_login_idx` (`last_login`);

--
-- فهارس للجدول `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- فهارس للجدول `watermark_settings`
--
ALTER TABLE `watermark_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_content`
--
ALTER TABLE `about_content`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=302;

--
-- AUTO_INCREMENT for table `add_ons`
--
ALTER TABLE `add_ons`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `add_on_updates`
--
ALTER TABLE `add_on_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ads`
--
ALTER TABLE `ads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ad_placements`
--
ALTER TABLE `ad_placements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `colors`
--
ALTER TABLE `colors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `contact_attempts`
--
ALTER TABLE `contact_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contact_blacklist`
--
ALTER TABLE `contact_blacklist`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `contact_settings`
--
ALTER TABLE `contact_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_templates`
--
ALTER TABLE `contact_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `deleted_accounts`
--
ALTER TABLE `deleted_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `downloads`
--
ALTER TABLE `downloads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `features`
--
ALTER TABLE `features`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `media_batches`
--
ALTER TABLE `media_batches`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_batch_items`
--
ALTER TABLE `media_batch_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media_colors`
--
ALTER TABLE `media_colors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `media_color_types`
--
ALTER TABLE `media_color_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `media_downloads`
--
ALTER TABLE `media_downloads`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `media_licenses`
--
ALTER TABLE `media_licenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `media_views`
--
ALTER TABLE `media_views`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `navbar`
--
ALTER TABLE `navbar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment_gateways`
--
ALTER TABLE `payment_gateways`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `privacy_content`
--
ALTER TABLE `privacy_content`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `resolutions`
--
ALTER TABLE `resolutions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `search_box_settings`
--
ALTER TABLE `search_box_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `search_suggestions`
--
ALTER TABLE `search_suggestions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seo_analysis`
--
ALTER TABLE `seo_analysis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seo_settings`
--
ALTER TABLE `seo_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sitemaps`
--
ALTER TABLE `sitemaps`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `terms_content`
--
ALTER TABLE `terms_content`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `watermark_settings`
--
ALTER TABLE `watermark_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- القيود المفروضة على الجداول الملقاة
--

--
-- قيود الجداول `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- قيود الجداول `add_on_updates`
--
ALTER TABLE `add_on_updates`
  ADD CONSTRAINT `fk_addon_updates` FOREIGN KEY (`add_on_id`) REFERENCES `add_ons` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `ad_placements`
--
ALTER TABLE `ad_placements`
  ADD CONSTRAINT `fk_ad_id` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `downloads`
--
ALTER TABLE `downloads`
  ADD CONSTRAINT `fk_downloads_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_downloads_wallpaper` FOREIGN KEY (`wallpaper_id`) REFERENCES `media` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorites_wallpaper` FOREIGN KEY (`wallpaper_id`) REFERENCES `media` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `media_batch_items`
--
ALTER TABLE `media_batch_items`
  ADD CONSTRAINT `media_batch_items_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `media_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `media_batch_items_ibfk_2` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `media_colors`
--
ALTER TABLE `media_colors`
  ADD CONSTRAINT `fk_media_colors_color_type` FOREIGN KEY (`color_id`) REFERENCES `media_color_types` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_media_colors_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `media_downloads`
--
ALTER TABLE `media_downloads`
  ADD CONSTRAINT `media_downloads_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `media_downloads_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `media_tags`
--
ALTER TABLE `media_tags`
  ADD CONSTRAINT `media_tags_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `media_tags_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `media_views`
--
ALTER TABLE `media_views`
  ADD CONSTRAINT `media_views_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `media_views_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- قيود الجداول `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_items_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- قيود الجداول `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- قيود الجداول `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- قيود الجداول `site_settings`
--
ALTER TABLE `site_settings`
  ADD CONSTRAINT `site_settings_ibfk_1` FOREIGN KEY (`header_menu_id`) REFERENCES `menus` (`id`),
  ADD CONSTRAINT `site_settings_ibfk_2` FOREIGN KEY (`footer_menu_id`) REFERENCES `menus` (`id`);

--
-- قيود الجداول `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- قيود الجداول `user_subscriptions`
--
ALTER TABLE `user_subscriptions`
  ADD CONSTRAINT `user_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
