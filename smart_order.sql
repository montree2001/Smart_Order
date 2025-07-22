-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 22, 2025 at 08:10 AM
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
-- Database: `smart_order`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--
-- Error reading structure for table smart_order.activity_logs: #1932 - Table &#039;smart_order.activity_logs&#039; doesn&#039;t exist in engine
-- Error reading data for table smart_order.activity_logs: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `smart_order`.`activity_logs`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'ชื่อสินค้า',
  `price` decimal(10,2) NOT NULL COMMENT 'ราคา',
  `category` varchar(100) NOT NULL COMMENT 'หมวดหมู่',
  `description` text DEFAULT NULL COMMENT 'รายละเอียด',
  `image_url` varchar(500) DEFAULT NULL COMMENT 'รูปภาพ',
  `available` tinyint(1) DEFAULT 1 COMMENT 'สถานะพร้อมขาย',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `name`, `price`, `category`, `description`, `image_url`, `available`, `created_at`, `updated_at`) VALUES
(1, 'ข้าวผัดหมู', 45.00, 'อาหารจานเดียว', 'ข้าวผัดหมูสับ ใส่ไข่ ผักโขม', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(2, 'ข้าวผัดกุ้ง', 55.00, 'อาหารจานเดียว', 'ข้าวผัดกุ้งสด ใส่ไข่ ผักโขม', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(3, 'ข้าวผัดปู', 65.00, 'อาหารจานเดียว', 'ข้าวผัดปูแท้ ใส่ไข่ ผักโขม', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(4, 'ข้าวผัดไข่', 35.00, 'อาหารจานเดียว', 'ข้าวผัดไข่เจียว ผักโขม', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(5, 'ข้าวหมูแดง', 40.00, 'อาหารจานเดียว', 'ข้าวหมูแดงชาร์ชู ไข่ต้ม', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(6, 'ก๋วยเตี๋ยวหมูน้ำใส', 40.00, 'ก๋วยเตี๋ยว', 'ก๋วยเตี๋ยวเส้นเล็ก หมูลูกชิ้น น้ำใส', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(7, 'ก๋วยเตี๋ยวหมูต้มยำ', 45.00, 'ก๋วยเตี๋ยว', 'ก๋วยเตี๋ยวต้มยำ หมูลูกชิ้น', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(8, 'ก๋วยเตี๋ยวเรือ', 50.00, 'ก๋วยเตี๋ยว', 'ก๋วยเตี๋ยวเรือ หมูเลือดตับ', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(9, 'บะหมี่หมูแดง', 45.00, 'ก๋วยเตี๋ยว', 'บะหมี่หมูแดง ใส่ใหญ่', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(10, 'น้ำเปล่า', 10.00, 'เครื่องดื่ม', 'น้ำเปล่าขวด', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(11, 'โค้ก', 15.00, 'เครื่องดื่ม', 'โค้กขวด', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(12, 'น้าำส้ม', 20.00, 'เครื่องดื่ม', 'น้ำส้มคั้นสด', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(13, 'กาแฟร้อน', 25.00, 'เครื่องดื่ม', 'กาแฟดำร้อน', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(14, 'กาแฟเย็น', 30.00, 'เครื่องดื่ม', 'กาแฟเย็นใส่นม', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(15, 'ชาเย็น', 25.00, 'เครื่องดื่ม', 'ชาเย็นใส่นม', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(16, 'ไอศกรีมวานิลลา', 25.00, 'ของหวาน', 'ไอศกรีมวานิลลา 1 สกู๊ป', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(17, 'ไอศกรีมช็อกโกแลต', 25.00, 'ของหวาน', 'ไอศกรีมช็อกโกแลต 1 สกู๊ป', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(18, 'ขนมถ้วย', 15.00, 'ของหวาน', 'ขนมถ้วยแป้งมัน', NULL, 1, '2025-07-21 03:20:36', '2025-07-21 03:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `type` enum('order_confirmed','queue_update','ready_to_serve','receipt') NOT NULL,
  `message` text NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','sent','failed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--
-- Error reading structure for table smart_order.orders: #1932 - Table &#039;smart_order.orders&#039; doesn&#039;t exist in engine
-- Error reading data for table smart_order.orders: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `smart_order`.`orders`&#039; at line 1

-- --------------------------------------------------------

--
-- Stand-in structure for view `order_details`
-- (See below for the actual view)
--
CREATE TABLE `order_details` (
);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1 COMMENT 'จำนวน',
  `price` decimal(10,2) NOT NULL COMMENT 'ราคาต่อหน่วย',
  `total_price` decimal(10,2) NOT NULL COMMENT 'ราคารวม',
  `special_notes` text DEFAULT NULL COMMENT 'หมายเหตุพิเศษ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `order_items_detail`
-- (See below for the actual view)
--
CREATE TABLE `order_items_detail` (
);

-- --------------------------------------------------------

--
-- Table structure for table `queue`
--

CREATE TABLE `queue` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `queue_number` int(11) NOT NULL COMMENT 'หมายเลขคิว',
  `status` enum('waiting','calling','served','no_show') DEFAULT 'waiting' COMMENT 'สถานะคิว',
  `called_at` timestamp NULL DEFAULT NULL COMMENT 'เวลาที่เรียกคิว',
  `served_at` timestamp NULL DEFAULT NULL COMMENT 'เวลาที่เสิร์ฟ',
  `estimated_time` int(11) DEFAULT 0 COMMENT 'เวลาโดยประมาณ (นาที)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `queue_announcements`
--
-- Error reading structure for table smart_order.queue_announcements: #1932 - Table &#039;smart_order.queue_announcements&#039; doesn&#039;t exist in engine
-- Error reading data for table smart_order.queue_announcements: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `smart_order`.`queue_announcements`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'shop_name', 'ร้านอาหารตัวอย่าง', 'ชื่อร้าน', '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(2, 'shop_phone', '02-XXX-XXXX', 'เบอร์โทรร้าน', '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(3, 'line_channel_access_token', '', 'LINE Channel Access Token', '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(4, 'line_channel_secret', '', 'LINE Channel Secret', '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(5, 'promptpay_id', '', 'หมายเลข PromptPay', '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(6, 'queue_reset_daily', '1', 'รีเซ็ตหมายเลขคิวทุกวัน (1=เปิด, 0=ปิด)', '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(7, 'estimated_time_per_item', '5', 'เวลาโดยประมาณต่อรายการ (นาที)', '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(8, 'max_queue_per_day', '999', 'จำนวนคิวสูงสุดต่อวัน', '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(9, 'notification_before_queue', '3', 'แจ้งเตือนก่อนถึงคิว (จำนวนคิว)', '2025-07-21 03:20:36', '2025-07-21 03:20:36'),
(10, 'receipt_footer_text', 'ขอบคุณที่ใช้บริการ', 'ข้อความท้ายใบเสร็จ', '2025-07-21 03:20:36', '2025-07-21 03:20:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- Error reading structure for table smart_order.users: #1932 - Table &#039;smart_order.users&#039; doesn&#039;t exist in engine
-- Error reading data for table smart_order.users: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `smart_order`.`users`&#039; at line 1

-- --------------------------------------------------------

--
-- Structure for view `order_details`
--
DROP TABLE IF EXISTS `order_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `order_details`  AS SELECT `o`.`id` AS `id`, `o`.`queue_number` AS `queue_number`, `o`.`customer_name` AS `customer_name`, `o`.`customer_phone` AS `customer_phone`, `o`.`total_amount` AS `total_amount`, `o`.`status` AS `status`, `o`.`payment_method` AS `payment_method`, `o`.`payment_status` AS `payment_status`, `o`.`order_type` AS `order_type`, `o`.`created_at` AS `created_at`, `q`.`status` AS `queue_status`, `q`.`estimated_time` AS `estimated_time`, count(`oi`.`id`) AS `item_count` FROM ((`orders` `o` left join `queue` `q` on(`o`.`id` = `q`.`order_id`)) left join `order_items` `oi` on(`o`.`id` = `oi`.`order_id`)) GROUP BY `o`.`id`, `q`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `order_items_detail`
--
DROP TABLE IF EXISTS `order_items_detail`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `order_items_detail`  AS SELECT `oi`.`id` AS `id`, `oi`.`order_id` AS `order_id`, `oi`.`quantity` AS `quantity`, `oi`.`price` AS `price`, `oi`.`total_price` AS `total_price`, `oi`.`special_notes` AS `special_notes`, `mi`.`name` AS `item_name`, `mi`.`category` AS `category`, `o`.`queue_number` AS `queue_number`, `o`.`customer_name` AS `customer_name`, `o`.`status` AS `order_status` FROM ((`order_items` `oi` join `menu_items` `mi` on(`oi`.`menu_item_id` = `mi`.`id`)) join `orders` `o` on(`oi`.`order_id` = `o`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_menu_items_category` (`category`),
  ADD KEY `idx_menu_items_available` (`available`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- Indexes for table `queue`
--
ALTER TABLE `queue`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_queue_number` (`queue_number`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_queue_status` (`status`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `queue`
--
ALTER TABLE `queue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`);

--
-- Constraints for table `queue`
--
ALTER TABLE `queue`
  ADD CONSTRAINT `queue_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
