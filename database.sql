-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- مضيف: localhost:3306
-- وقت الجيل: 21 مارس 2025 الساعة 05:17
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
(1, NULL, 'Viewed media list', '2025-03-21 03:50:09');

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
(7, 'Mahran', NULL, '/uploads/categories/icons/icon_67dcd8e1e6de9.png', '#300344', 0, '', 1, 1, '/uploads/categories/images/image_67dcd8e1e6ee3.png', 'mahran', 'Mahran test 2', 0, 'Admin', NULL, 1, '2025-03-21 03:11:29', '2025-03-21 03:11:29');

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
(5, 'Black', '#000000', '2025-02-20 03:34:24');

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
(1, 'wallpix.top', 'info@wallpix.top', 'hhhhhhhh', 'yyyyyyyyyyyyyyyyy', 'new', 'medium', '83.253.108.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36 Edg/134.0.0.0', NULL, NULL, NULL, '2025-03-13 20:51:53', '2025-03-13 20:51:53'),
(3, 'تجربة عربي', 'hhh@hhh.com', 'موضوع حب', 'احب الصور كثيرا', 'new', 'medium', '83.253.108.100', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0.1 Mobile/15E148 Safari/604.1', NULL, NULL, NULL, '2025-03-13 21:55:05', '2025-03-13 21:55:05');

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
(1, 'admin@example.com', 'successful', '83.253.108.100', 1, NULL, '2025-03-21 02:51:46', NULL);

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
  `ai_description` text COLLATE utf8mb4_unicode_ci,
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
(29, 'test2', 'ssssssssssss', 7, '67dcdd1149237_login-page.jpg', '/uploads/media/2025/03/67dcdd1149237_login-page.jpg', '', NULL, '/uploads/thumbnails/2025/03/thumb_67dcdd1149237_login-page.jpg', 1, 1, '1920', '1080', NULL, NULL, 0, 'landscape', NULL, NULL, NULL, 0, NULL, NULL, '', 1, '2025-03-21 03:29:21', 0, NULL, NULL, 0.00, 0.00);

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
-- بنية الجدول `media_colors`
--

CREATE TABLE `media_colors` (
  `id` int NOT NULL,
  `media_id` int NOT NULL,
  `color_id` int DEFAULT NULL,
  `primary_color` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#FFFFFF',
  `secondary_color` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT '#000000',
  `is_dark` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- إرجاع أو استيراد بيانات الجدول `media_colors`
--

INSERT INTO `media_colors` (`id`, `media_id`, `color_id`, `primary_color`, `secondary_color`, `is_dark`, `created_at`, `updated_at`) VALUES
(3, 29, NULL, '#14d7a6', '#ee1b1b', 0, '2025-03-21 04:29:21', '2025-03-17 23:21:04');

-- --------------------------------------------------------

--
-- بنية الجدول `media_color_types`
--

CREATE TABLE `media_color_types` (
  `id` int NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
(14, 29, 1, '2025-03-21 03:55:26', 'ddc29cecf5032c596ff79f139194d1a7');

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
(29, 24, NULL);

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
(42, 29, 1, '2025-03-21 03:29:31', 'ddc29cecf5032c596ff79f139194d1a7');

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
(1, 2, 'Home', 'https://mahrannnn.se/', 1);

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
(1, 'paypal', 'PayPal', 'Accept payments via PayPal', 0, 1, '/assets/images/paypal-logo.png', '{\"live\": {\"client_id\": \"\", \"webhook_id\": \"\", \"client_secret\": \"\", \"webhook_secret\": \"\"}, \"test\": {\"client_id\": \"\", \"webhook_id\": \"\", \"client_secret\": \"\", \"webhook_secret\": \"\"}}', '2025-03-20 07:09:15', '2025-03-20 07:09:15', NULL),
(2, 'stripe', 'Stripe', 'Accept credit card payments via Stripe', 0, 1, '/assets/images/stripe-logo.png', '{\"live\": {\"secret_key\": \"\", \"webhook_secret\": \"\", \"publishable_key\": \"\"}, \"test\": {\"secret_key\": \"\", \"webhook_secret\": \"\", \"publishable_key\": \"\"}}', '2025-03-20 07:09:15', '2025-03-20 07:09:15', NULL);

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
(5, '2560x1440', '2025-02-20 03:34:11');

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
(1, 'Wallpix Top', '', 'Welcome', 'website,portal,media', 'https://mahrannnn.se', 'admin@example.com', '', 10, 10, 10, 'https://www.facebook.com/unifaktoura/', 'https://twitter.com/unifaktoura', 'https://Instagram.com/unifaktoura', 'https://YouTube.com/unifaktoura', '2025-03-14 04:46:34', '2025-03-17 07:03:47', 'mahranalsarminy', '1', 1, 'All rights reserved 2025 © WallPix Media and Wallpaper Platform', 'logo-wallpix.png', '', '', 2, NULL, '', 0, 0, 'en', 1, 1, 1, 1, 1);

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
(24, 'test2', 'test2', NULL, NULL, '2025-03-21 03:29:21');

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
(1, 'Admin', 'admin@example.com', '$ecfdscsdcdscdsvvczqJTQei98BpJATJIpmOrSs3', 'uploads/profile_pictures/1_1742489124_logo-wallpix.png', 'admin', '2025-02-20 01:31:31', '', '2025-03-21 02:51:46', '2025-03-18 09:10:39', 1, '83.253.108.100'),
(2, '', 'subscriber@example.com', '$ecfdscsdcdscdsvvczqJTQei98BpJATJIpmOrSs3', NULL, 'subscriber', '2025-02-20 01:31:31', NULL, '2025-03-17 06:12:00', '2025-03-17 06:12:00', 1, NULL),
(3, '', 'freeuser@example.com', '$ecfdscsdcdscdsvvczqJTQei98BpJATJIpmOrSs3', NULL, 'free_user', '2025-02-20 01:31:31', NULL, '2025-03-17 06:12:00', '2025-03-17 06:12:00', 1, NULL),
(5, 'Test', 'ms.mamym@icloud.com', '$ecfdscsdcdscdsvvczqJTQei98BpJATJIpmOrSs3', 'uploads/profile_pictures/5_1742493554_dc259757-534d-4ba5-aa80-e6bc4c644e71.jpeg', 'free_user', '2025-03-20 17:58:05', 'Mahran', '2025-03-20 18:40:37', NULL, 1, '194.11.199.107');

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
-- فهارس للجدول `media_colors`
--
ALTER TABLE `media_colors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_media_colors` (`media_id`),
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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=117;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `colors`
--
ALTER TABLE `colors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_attempts`
--
ALTER TABLE `contact_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_blacklist`
--
ALTER TABLE `contact_blacklist`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `media_colors`
--
ALTER TABLE `media_colors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `media_color_types`
--
ALTER TABLE `media_color_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `media_downloads`
--
ALTER TABLE `media_downloads`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `media_views`
--
ALTER TABLE `media_views`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
-- القيود المفروضة على الجداول الملقاة
--

--
-- قيود الجداول `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

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
