-- =============================================
-- ฐานข้อมูลระบบจัดการออเดอร์อัจฉริยะ
-- Smart Order Management System Database Schema
-- =============================================

CREATE DATABASE IF NOT EXISTS `smart_order_system` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `smart_order_system`;

-- =============================================
-- ตาราง: ผู้ใช้งานระบบ (Users)
-- =============================================
CREATE TABLE `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(20) NULL,
    `role` ENUM('admin', 'pos_staff', 'kitchen_staff', 'manager') NOT NULL DEFAULT 'pos_staff',
    `avatar` VARCHAR(255) NULL COMMENT 'รูปโปรไฟล์',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login` TIMESTAMP NULL,
    `created_by` INT(11) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางผู้ใช้งานระบบ';

-- =============================================
-- ตาราง: หมวดหมู่เมนู (Menu Categories)
-- =============================================
CREATE TABLE `menu_categories` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `name_en` VARCHAR(100) NULL COMMENT 'ชื่อภาษาอังกฤษ',
    `description` TEXT NULL,
    `image` VARCHAR(255) NULL,
    `color_code` VARCHAR(7) NULL DEFAULT '#007bff' COMMENT 'รหัสสี hex',
    `icon` VARCHAR(50) NULL COMMENT 'Font Awesome icon class',
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_sort_order` (`sort_order`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางหมวดหมู่เมนูอาหาร';

-- =============================================
-- ตาราง: รายการเมนู (Menu Items)
-- =============================================
CREATE TABLE `menu_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `category_id` INT(11) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `name_en` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `image` VARCHAR(255) NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `cost` DECIMAL(10,2) NULL COMMENT 'ต้นทุน',
    `preparation_time` INT(11) NULL DEFAULT 5 COMMENT 'เวลาเตรียม (นาที)',
    `calories` INT(11) NULL COMMENT 'แคลอรี่',
    `spicy_level` TINYINT(1) NULL DEFAULT 0 COMMENT 'ระดับความเผ็ด 0-5',
    `tags` JSON NULL COMMENT 'แท็กต่างๆ เช่น vegetarian, gluten-free',
    `allergens` JSON NULL COMMENT 'สารก่อภูมิแพ้',
    `nutritional_info` JSON NULL COMMENT 'ข้อมูลโภชนาการ',
    `is_available` TINYINT(1) NOT NULL DEFAULT 1,
    `is_recommended` TINYINT(1) NOT NULL DEFAULT 0,
    `is_bestseller` TINYINT(1) NOT NULL DEFAULT 0,
    `stock_quantity` INT(11) NULL COMMENT 'จำนวนคงเหลือ (ถ้าจำกัด)',
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_category` (`category_id`),
    INDEX `idx_available` (`is_available`),
    INDEX `idx_price` (`price`),
    INDEX `idx_sort_order` (`sort_order`),
    FOREIGN KEY (`category_id`) REFERENCES `menu_categories`(`id`) ON DELETE CASCADE,
    FULLTEXT(`name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางรายการเมนูอาหาร';

-- =============================================
-- ตาราง: ออปชันเมนู (Menu Options)
-- =============================================
CREATE TABLE `menu_options` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `menu_item_id` INT(11) NOT NULL,
    `option_name` VARCHAR(100) NOT NULL COMMENT 'เช่น ขนาด, ความหวาน',
    `option_type` ENUM('single', 'multiple') NOT NULL DEFAULT 'single',
    `is_required` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_menu_item` (`menu_item_id`),
    FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางออปชันของเมนู';

-- =============================================
-- ตาราง: ค่าออปชันเมนู (Menu Option Values)
-- =============================================
CREATE TABLE `menu_option_values` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `option_id` INT(11) NOT NULL,
    `value_name` VARCHAR(100) NOT NULL,
    `price_adjustment` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `is_default` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    
    PRIMARY KEY (`id`),
    INDEX `idx_option` (`option_id`),
    FOREIGN KEY (`option_id`) REFERENCES `menu_options`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางค่าของออปชันเมนู';

-- =============================================
-- ตาราง: ลูกค้า (Customers)
-- =============================================
CREATE TABLE `customers` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `line_user_id` VARCHAR(255) NULL UNIQUE,
    `name` VARCHAR(255) NULL,
    `phone` VARCHAR(20) NULL,
    `email` VARCHAR(255) NULL,
    `address` TEXT NULL,
    `birthday` DATE NULL,
    `gender` ENUM('male', 'female', 'other') NULL,
    `preferences` JSON NULL COMMENT 'ความชอบส่วนตัว',
    `total_orders` INT(11) NOT NULL DEFAULT 0,
    `total_spent` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `loyalty_points` INT(11) NOT NULL DEFAULT 0,
    `is_vip` TINYINT(1) NOT NULL DEFAULT 0,
    `last_order_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_line_user` (`line_user_id`),
    INDEX `idx_phone` (`phone`),
    INDEX `idx_total_orders` (`total_orders`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางลูกค้า';

-- =============================================
-- ตาราง: ออเดอร์หลัก (Orders)
-- =============================================
CREATE TABLE `orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_number` VARCHAR(20) NOT NULL UNIQUE,
    `customer_id` INT(11) NULL,
    `customer_name` VARCHAR(255) NULL,
    `customer_phone` VARCHAR(20) NULL,
    `customer_line_id` VARCHAR(255) NULL,
    `order_type` ENUM('online', 'pos', 'line') NOT NULL DEFAULT 'pos',
    `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `service_charge` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    `payment_status` ENUM('pending', 'paid', 'partial', 'refunded') NOT NULL DEFAULT 'pending',
    `payment_method` ENUM('cash', 'qr_payment', 'card', 'transfer', 'credit') NULL,
    `payment_reference` VARCHAR(255) NULL,
    `special_instructions` TEXT NULL,
    `estimated_ready_time` TIMESTAMP NULL,
    `ready_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `cancelled_at` TIMESTAMP NULL,
    `cancellation_reason` TEXT NULL,
    `served_by` INT(11) NULL,
    `created_by` INT(11) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_number` (`order_number`),
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_payment_status` (`payment_status`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_order_type` (`order_type`),
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`served_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางออเดอร์หลัก';

-- =============================================
-- ตาราง: รายการในออเดอร์ (Order Items)
-- =============================================
CREATE TABLE `order_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `menu_item_id` INT(11) NOT NULL,
    `quantity` INT(11) NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `special_notes` TEXT NULL,
    `status` ENUM('pending', 'preparing', 'ready', 'served') NOT NULL DEFAULT 'pending',
    `preparation_started_at` TIMESTAMP NULL,
    `ready_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_order` (`order_id`),
    INDEX `idx_menu_item` (`menu_item_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางรายการในออเดอร์';

-- =============================================
-- ตาราง: ออปชันที่เลือกในรายการ (Order Item Options)
-- =============================================
CREATE TABLE `order_item_options` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_item_id` INT(11) NOT NULL,
    `option_id` INT(11) NOT NULL,
    `option_value_id` INT(11) NOT NULL,
    `price_adjustment` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    
    PRIMARY KEY (`id`),
    INDEX `idx_order_item` (`order_item_id`),
    FOREIGN KEY (`order_item_id`) REFERENCES `order_items`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`option_id`) REFERENCES `menu_options`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`option_value_id`) REFERENCES `menu_option_values`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางออปชันที่เลือกในรายการ';

-- =============================================
-- ตาราง: ระบบคิว (Queue System)
-- =============================================
CREATE TABLE `queue` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `queue_number` INT(11) NOT NULL,
    `queue_date` DATE NOT NULL,
    `status` ENUM('waiting', 'calling', 'served', 'no_show', 'cancelled') NOT NULL DEFAULT 'waiting',
    `priority` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=ปกติ, 1=ด่วน, 2=VIP',
    `estimated_time` INT(11) NULL COMMENT 'เวลาโดยประมาณ (นาที)',
    `called_count` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'จำนวนครั้งที่เรียก',
    `called_at` TIMESTAMP NULL,
    `served_at` TIMESTAMP NULL,
    `no_show_at` TIMESTAMP NULL,
    `voice_language` ENUM('th', 'en') NOT NULL DEFAULT 'th',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_order_queue` (`order_id`),
    INDEX `idx_queue_number` (`queue_number`),
    INDEX `idx_queue_date` (`queue_date`),
    INDEX `idx_status` (`status`),
    INDEX `idx_priority` (`priority`),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางระบบคิว';

-- =============================================
-- ตาราง: การชำระเงิน (Payments)
-- =============================================
CREATE TABLE `payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `transaction_id` VARCHAR(255) NULL UNIQUE,
    `payment_method` ENUM('cash', 'qr_payment', 'card', 'transfer', 'credit', 'points') NOT NULL,
    `payment_provider` VARCHAR(50) NULL COMMENT 'เช่น promptpay, truemoney',
    `amount` DECIMAL(10,2) NOT NULL,
    `fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `net_amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'THB',
    `exchange_rate` DECIMAL(10,4) NOT NULL DEFAULT 1.0000,
    `status` ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',
    `gateway_response` JSON NULL,
    `reference_number` VARCHAR(255) NULL,
    `qr_code_data` TEXT NULL,
    `receipt_number` VARCHAR(50) NULL,
    `processed_by` INT(11) NULL,
    `processed_at` TIMESTAMP NULL,
    `refunded_at` TIMESTAMP NULL,
    `refund_reason` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_order` (`order_id`),
    INDEX `idx_transaction` (`transaction_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_method` (`payment_method`),
    INDEX `idx_processed_at` (`processed_at`),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางการชำระเงิน';

-- =============================================
-- ตาราง: การแจ้งเตือน (Notifications)
-- =============================================
CREATE TABLE `notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NULL,
    `customer_id` INT(11) NULL,
    `line_user_id` VARCHAR(255) NULL,
    `type` ENUM('order_confirmed', 'order_ready', 'queue_called', 'payment_received', 'order_cancelled', 'system') NOT NULL,
    `channel` ENUM('line', 'sms', 'email', 'push', 'system') NOT NULL DEFAULT 'line',
    `title` VARCHAR(255) NULL,
    `message` TEXT NOT NULL,
    `data` JSON NULL COMMENT 'ข้อมูลเพิ่มเติม',
    `status` ENUM('pending', 'sent', 'delivered', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    `sent_at` TIMESTAMP NULL,
    `delivered_at` TIMESTAMP NULL,
    `error_message` TEXT NULL,
    `retry_count` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_order` (`order_id`),
    INDEX `idx_customer` (`customer_id`),
    INDEX `idx_line_user` (`line_user_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางการแจ้งเตือน';

-- =============================================
-- ตาราง: ใบเสร็จ (Receipts)
-- =============================================
CREATE TABLE `receipts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `order_id` INT(11) NOT NULL,
    `receipt_number` VARCHAR(50) NOT NULL UNIQUE,
    `type` ENUM('original', 'copy', 'refund', 'void') NOT NULL DEFAULT 'original',
    `format` ENUM('thermal', 'a4', 'digital') NOT NULL DEFAULT 'thermal',
    `file_path` VARCHAR(255) NULL,
    `file_size` INT(11) NULL,
    `printed_count` INT(11) NOT NULL DEFAULT 0,
    `last_printed_at` TIMESTAMP NULL,
    `sent_via_line` TINYINT(1) NOT NULL DEFAULT 0,
    `sent_via_email` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` INT(11) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_receipt_number` (`receipt_number`),
    INDEX `idx_order` (`order_id`),
    INDEX `idx_type` (`type`),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางใบเสร็จ';

-- =============================================
-- ตาราง: การตั้งค่าระบบ (System Settings)
-- =============================================
CREATE TABLE `system_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `category` VARCHAR(50) NOT NULL DEFAULT 'general',
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT NULL,
    `data_type` ENUM('string', 'integer', 'decimal', 'boolean', 'json', 'text') NOT NULL DEFAULT 'string',
    `is_public` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'แสดงใน frontend',
    `description` TEXT NULL,
    `validation_rules` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_setting_key` (`setting_key`),
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางการตั้งค่าระบบ';

-- =============================================
-- ตาราง: กิจกรรมของระบบ (Activity Log)
-- =============================================
CREATE TABLE `activity_logs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NULL,
    `action` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(50) NULL,
    `record_id` INT(11) NULL,
    `description` TEXT NULL,
    `old_data` JSON NULL,
    `new_data` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_table` (`table_name`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางบันทึกกิจกรรม';

-- =============================================
-- ตาราง: คูปองส่วนลด (Coupons)
-- =============================================
CREATE TABLE `coupons` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `type` ENUM('percentage', 'fixed_amount', 'free_item') NOT NULL,
    `value` DECIMAL(10,2) NOT NULL,
    `min_order_amount` DECIMAL(10,2) NULL,
    `max_discount` DECIMAL(10,2) NULL,
    `usage_limit` INT(11) NULL COMMENT 'จำกัดการใช้งาน',
    `usage_count` INT(11) NOT NULL DEFAULT 0,
    `usage_limit_per_customer` INT(11) NULL,
    `applicable_items` JSON NULL COMMENT 'เมนูที่สามารถใช้ได้',
    `start_date` TIMESTAMP NOT NULL,
    `end_date` TIMESTAMP NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT(11) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_coupon_code` (`code`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_dates` (`start_date`, `end_date`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางคูปองส่วนลด';

-- =============================================
-- ตาราง: การใช้คูปอง (Coupon Usage)
-- =============================================
CREATE TABLE `coupon_usage` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `coupon_id` INT(11) NOT NULL,
    `order_id` INT(11) NOT NULL,
    `customer_id` INT(11) NULL,
    `discount_amount` DECIMAL(10,2) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    INDEX `idx_coupon` (`coupon_id`),
    INDEX `idx_order` (`order_id`),
    INDEX `idx_customer` (`customer_id`),
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางการใช้คูปอง';

-- =============================================
-- สร้าง Views สำหรับการค้นหาข้อมูลที่ซับซ้อน
-- =============================================

-- View: รายละเอียดออเดอร์แบบเต็ม
CREATE VIEW `order_details` AS
SELECT 
    o.id,
    o.order_number,
    o.customer_name,
    o.customer_phone,
    o.order_type,
    o.total_amount,
    o.status,
    o.payment_status,
    o.payment_method,
    o.created_at,
    q.queue_number,
    q.status as queue_status,
    q.estimated_time,
    COUNT(oi.id) as item_count,
    GROUP_CONCAT(CONCAT(mi.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items_summary
FROM orders o
LEFT JOIN queue q ON o.id = q.order_id
LEFT JOIN order_items oi ON o.id = oi.order_id
LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
GROUP BY o.id;

-- View: สถิติเมนูขายดี
CREATE VIEW `bestselling_items` AS
SELECT 
    mi.id,
    mi.name,
    mi.category_id,
    mc.name as category_name,
    mi.price,
    COUNT(oi.id) as order_count,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.total_price) as total_revenue,
    AVG(oi.total_price) as avg_order_value
FROM menu_items mi
LEFT JOIN order_items oi ON mi.id = oi.menu_item_id
LEFT JOIN menu_categories mc ON mi.category_id = mc.id
LEFT JOIN orders o ON oi.order_id = o.id
WHERE o.status IN ('completed', 'ready')
GROUP BY mi.id
ORDER BY total_quantity DESC;

-- View: สถิติยอดขายรายวัน
CREATE VIEW `daily_sales` AS
SELECT 
    DATE(created_at) as sale_date,
    COUNT(DISTINCT id) as total_orders,
    COUNT(DISTINCT customer_id) as unique_customers,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_order_value,
    SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as paid_revenue,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
FROM orders 
GROUP BY DATE(created_at)
ORDER BY sale_date DESC;

-- =============================================
-- เพิ่มข้อมูลเริ่มต้น (Initial Data)
-- =============================================

-- ผู้ดูแลระบบเริ่มต้น
INSERT INTO `users` (`username`, `password`, `full_name`, `email`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ผู้ดูแลระบบ', 'admin@smartorder.com', 'admin');

-- หมวดหมู่เมนูเริ่มต้น
INSERT INTO `menu_categories` (`name`, `name_en`, `description`, `color_code`, `icon`, `sort_order`) VALUES
('อาหารจานหลัก', 'Main Dishes', 'อาหารจานหลักต่างๆ', '#dc3545', 'fa-utensils', 1),
('เครื่องดื่ม', 'Beverages', 'เครื่องดื่มหลากหลาย', '#007bff', 'fa-glass-water', 2),
('ของหวาน', 'Desserts', 'ขนมหวานและของหวาน', '#fd7e14', 'fa-cake-candles', 3),
('อาหารเรียกได้', 'Appetizers', 'อาหารเรียกได้ต่างๆ', '#28a745', 'fa-bowl-food', 4);

-- การตั้งค่าระบบเริ่มต้น
INSERT INTO `system_settings` (`category`, `setting_key`, `setting_value`, `data_type`, `description`) VALUES
-- การตั้งค่าร้าน
('shop', 'shop_name', 'ร้านอาหารอัจฉริยะ', 'string', 'ชื่อร้าน'),
('shop', 'shop_phone', '02-XXX-XXXX', 'string', 'เบอร์โทรศัพท์ร้าน'),
('shop', 'shop_address', 'ที่อยู่ร้าน', 'text', 'ที่อยู่ร้าน'),
('shop', 'tax_rate', '7.00', 'decimal', 'อัตราภาษี (%)'),
('shop', 'service_charge_rate', '0.00', 'decimal', 'ค่าบริการ (%)'),

-- การตั้งค่าคิว
('queue', 'queue_reset_daily', '1', 'boolean', 'รีเซ็ตหมายเลขคิวทุกวัน'),
('queue', 'max_queue_per_day', '999', 'integer', 'จำนวนคิวสูงสุดต่อวัน'),
('queue', 'queue_call_timeout', '300', 'integer', 'เวลาที่เรียกคิวค้าง (วินาที)'),
('queue', 'notification_before_queue', '3', 'integer', 'แจ้งเตือนก่อนถึงคิวกี่หมายเลข'),

-- การตั้งค่า LINE
('line', 'channel_access_token', '', 'string', 'LINE Channel Access Token'),
('line', 'channel_secret', '', 'string', 'LINE Channel Secret'),
('line', 'webhook_url', '', 'string', 'LINE Webhook URL'),

-- การตั้งค่าการชำระเงิน
('payment', 'promptpay_id', '', 'string', 'หมายเลข PromptPay'),
('payment', 'accept_cash', '1', 'boolean', 'รับเงินสด'),
('payment', 'accept_card', '0', 'boolean', 'รับบัตรเครดิต'),
('payment', 'accept_qr', '1', 'boolean', 'รับ QR Payment'),

-- การตั้งค่าทั่วไป
('general', 'timezone', 'Asia/Bangkok', 'string', 'เขตเวลา'),
('general', 'default_language', 'th', 'string', 'ภาษาเริ่มต้น'),
('general', 'enable_voice_queue', '1', 'boolean', 'เปิดใช้เรียกคิวด้วยเสียง'),
('general', 'voice_language', 'th', 'string', 'ภาษาเสียงเรียกคิว'),
('general', 'preparation_time_per_item', '5', 'integer', 'เวลาเตรียมอาหารต่อรายการ (นาที)');

-- =============================================
-- สร้าง Indexes เพิ่มเติมเพื่อประสิทธิภาพ
-- =============================================

-- Composite indexes สำหรับการค้นหาที่ซับซ้อน
CREATE INDEX `idx_orders_status_date` ON `orders` (`status`, `created_at`);
CREATE INDEX `idx_orders_customer_date` ON `orders` (`customer_id`, `created_at`);
CREATE INDEX `idx_queue_date_status` ON `queue` (`queue_date`, `status`);
CREATE INDEX `idx_notifications_customer_type` ON `notifications` (`customer_id`, `type`);
CREATE INDEX `idx_order_items_menu_date` ON `order_items` (`menu_item_id`, `created_at`);

-- =============================================
-- สร้าง Triggers สำหรับการอัปเดตข้อมูลอัตโนมัติ
-- =============================================

-- Trigger: อัปเดตสถิติลูกค้าเมื่อมีออเดอร์ใหม่
DELIMITER //
CREATE TRIGGER `update_customer_stats_after_order` 
AFTER UPDATE ON `orders` 
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' AND NEW.customer_id IS NOT NULL THEN
        UPDATE customers 
        SET 
            total_orders = total_orders + 1,
            total_spent = total_spent + NEW.total_amount,
            last_order_at = NEW.completed_at
        WHERE id = NEW.customer_id;
    END IF;
END//

-- Trigger: สร้างหมายเลขคิวอัตโนมัติ
DELIMITER //
CREATE TRIGGER `generate_queue_number` 
BEFORE INSERT ON `queue` 
FOR EACH ROW
BEGIN
    DECLARE next_queue INT DEFAULT 1;
    
    SELECT COALESCE(MAX(queue_number), 0) + 1 INTO next_queue
    FROM queue 
    WHERE queue_date = CURDATE();
    
    SET NEW.queue_number = next_queue;
    SET NEW.queue_date = CURDATE();
END//

-- Trigger: อัปเดตยอดรวมใน order เมื่อมีการเปลี่ยนแปลง order_items
DELIMITER //
CREATE TRIGGER `update_order_total_after_item_change` 
AFTER INSERT ON `order_items` 
FOR EACH ROW
BEGIN
    UPDATE orders 
    SET 
        subtotal = (SELECT SUM(total_price) FROM order_items WHERE order_id = NEW.order_id),
        total_amount = subtotal + tax_amount + service_charge - discount_amount
    WHERE id = NEW.order_id;
END//

DELIMITER ;

-- =============================================
-- Functions และ Procedures ที่มีประโยชน์
-- =============================================

-- Function: คำนวณเวลาโดยประมาณของคิว
DELIMITER //
CREATE FUNCTION `calculate_queue_wait_time`(queue_id INT) 
RETURNS INT
READS SQL DATA
BEGIN
    DECLARE wait_time INT DEFAULT 0;
    DECLARE current_queue INT;
    DECLARE prep_time_per_item INT DEFAULT 5;
    
    SELECT queue_number INTO current_queue
    FROM queue WHERE id = queue_id;
    
    SELECT COALESCE(setting_value, 5) INTO prep_time_per_item
    FROM system_settings 
    WHERE setting_key = 'preparation_time_per_item';
    
    SELECT COUNT(*) * prep_time_per_item INTO wait_time
    FROM queue q
    JOIN orders o ON q.order_id = o.id
    WHERE q.queue_number < current_queue 
    AND q.queue_date = CURDATE()
    AND q.status IN ('waiting', 'calling');
    
    RETURN wait_time;
END//

DELIMITER ;