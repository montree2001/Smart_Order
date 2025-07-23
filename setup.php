<?php
/**
 * ไฟล์ setup.php - ติดตั้งและตั้งค่าระบบ Smart Order Management
 */

define('SKIP_TABLE_CHECK', true);
require_once 'config/database.php';

$step = $_GET['step'] ?? 1;
$message = '';
$error = '';

// ตรวจสอบการติดตั้งแล้ว
function is_already_installed() {
    return table_exists('system_settings') && table_exists('users');
}

// ฟังก์ชันสร้างฐานข้อมูลใหม่
function create_database() {
    try {
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS);
        
        if ($connection->connect_error) {
            throw new Exception("Connection failed: " . $connection->connect_error);
        }
        
        $sql = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        
        if (!$connection->query($sql)) {
            throw new Exception("Error creating database: " . $connection->error);
        }
        
        $connection->close();
        return true;
        
    } catch (Exception $e) {
        error_log("Database creation error: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันสร้างตารางทั้งหมด
function create_all_tables() {
    global $connection;
    
    $tables_sql = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NULL,
        `phone` VARCHAR(20) NULL,
        `role` ENUM('admin', 'pos_staff', 'kitchen_staff', 'manager') NOT NULL DEFAULT 'pos_staff',
        `avatar` VARCHAR(255) NULL,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `last_login` TIMESTAMP NULL,
        `created_by` INT(11) NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_username` (`username`),
        INDEX `idx_role` (`role`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `menu_categories` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(100) NOT NULL,
        `description` TEXT NULL,
        `image` VARCHAR(255) NULL,
        `color_code` VARCHAR(7) NULL DEFAULT '#007bff',
        `sort_order` INT(11) NOT NULL DEFAULT 0,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_sort_order` (`sort_order`),
        INDEX `idx_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `menu_items` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `category_id` INT(11) NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        `description` TEXT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `image` VARCHAR(255) NULL,
        `is_available` TINYINT(1) NOT NULL DEFAULT 1,
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `sort_order` INT(11) NOT NULL DEFAULT 0,
        `preparation_time` INT(11) NULL DEFAULT 5,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_category` (`category_id`),
        INDEX `idx_active_available` (`is_active`, `is_available`),
        FOREIGN KEY (`category_id`) REFERENCES `menu_categories`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `customers` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(20) NULL,
        `email` VARCHAR(255) NULL,
        `line_user_id` VARCHAR(100) NULL,
        `total_orders` INT(11) NOT NULL DEFAULT 0,
        `total_spent` DECIMAL(12,2) NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE INDEX `idx_phone` (`phone`),
        INDEX `idx_line_user` (`line_user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `order_number` VARCHAR(20) NOT NULL UNIQUE,
        `customer_id` INT(11) NULL,
        `customer_name` VARCHAR(255) NOT NULL,
        `customer_phone` VARCHAR(20) NULL,
        `order_type` ENUM('dine_in', 'takeaway', 'delivery') NOT NULL DEFAULT 'dine_in',
        `subtotal_amount` DECIMAL(10,2) NOT NULL,
        `service_charge_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `total_amount` DECIMAL(10,2) NOT NULL,
        `payment_method` ENUM('cash', 'card', 'promptpay', 'transfer') NOT NULL DEFAULT 'cash',
        `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
        `status` ENUM('pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        `staff_id` INT(11) NULL,
        `notes` TEXT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE INDEX `idx_order_number` (`order_number`),
        INDEX `idx_customer` (`customer_id`),
        INDEX `idx_status` (`status`),
        INDEX `idx_date` (`created_at`),
        FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
        FOREIGN KEY (`staff_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `order_id` INT(11) NOT NULL,
        `menu_item_id` INT(11) NOT NULL,
        `quantity` INT(11) NOT NULL,
        `unit_price` DECIMAL(10,2) NOT NULL,
        `total_price` DECIMAL(10,2) NOT NULL,
        `notes` TEXT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_order` (`order_id`),
        INDEX `idx_menu_item` (`menu_item_id`),
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `queue` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `order_id` INT(11) NOT NULL,
        `queue_number` INT(11) NOT NULL,
        `queue_date` DATE NOT NULL,
        `status` ENUM('waiting', 'called', 'completed', 'cancelled') NOT NULL DEFAULT 'waiting',
        `called_at` TIMESTAMP NULL,
        `completed_at` TIMESTAMP NULL,
        `estimated_time` TIMESTAMP NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE INDEX `idx_queue_date_number` (`queue_date`, `queue_number`),
        INDEX `idx_order` (`order_id`),
        INDEX `idx_status` (`status`),
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `payments` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `order_id` INT(11) NOT NULL,
        `amount` DECIMAL(10,2) NOT NULL,
        `payment_method` ENUM('cash', 'card', 'promptpay', 'transfer') NOT NULL,
        `payment_details` JSON NULL,
        `payment_status` ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
        `staff_id` INT(11) NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_order` (`order_id`),
        INDEX `idx_status` (`payment_status`),
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`staff_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `type` ENUM('order_status', 'queue_update', 'payment', 'system') NOT NULL,
        `recipient_id` INT(11) NULL,
        `recipient_phone` VARCHAR(20) NULL,
        `title` VARCHAR(255) NOT NULL,
        `message` TEXT NOT NULL,
        `data` JSON NULL,
        `is_sent` TINYINT(1) NOT NULL DEFAULT 0,
        `sent_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_recipient` (`recipient_id`),
        INDEX `idx_phone` (`recipient_phone`),
        INDEX `idx_sent` (`is_sent`),
        FOREIGN KEY (`recipient_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS `system_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `setting_key` VARCHAR(100) NOT NULL UNIQUE,
        `setting_value` TEXT NOT NULL,
        `description` TEXT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE INDEX `idx_setting_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $statements = array_filter(array_map('trim', explode(';', $tables_sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !$connection->query($statement)) {
            throw new Exception("Error creating table: " . $connection->error);
        }
    }
    
    return true;
}

// ฟังก์ชันเพิ่มข้อมูลเริ่มต้น
function insert_default_data() {
    global $connection;
    
    // สร้างผู้ใช้ admin
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_sql = "INSERT IGNORE INTO users (username, password, full_name, role, is_active) 
                  VALUES ('admin', '$admin_password', 'ผู้ดูแลระบบ', 'admin', 1)";
    $connection->query($admin_sql);
    
    // ข้อมูลการตั้งค่าเริ่มต้น
    $default_settings = [
        ['shop_name', 'ร้านอาหารตัวอย่าง', 'ชื่อร้าน'],
        ['shop_phone', '02-XXX-XXXX', 'เบอร์โทรร้าน'],
        ['line_channel_access_token', '', 'LINE Channel Access Token'],
        ['line_channel_secret', '', 'LINE Channel Secret'],
        ['promptpay_id', '', 'หมายเลข PromptPay'],
        ['queue_reset_daily', '1', 'รีเซ็ตหมายเลขคิวทุกวัน'],
        ['estimated_time_per_item', '5', 'เวลาโดยประมาณต่อรายการ (นาที)'],
        ['max_queue_per_day', '999', 'จำนวนคิวสูงสุดต่อวัน'],
        ['notification_before_queue', '3', 'แจ้งเตือนก่อนถึงคิว (จำนวนคิว)'],
        ['receipt_footer_text', 'ขอบคุณที่ใช้บริการ', 'ข้อความท้ายใบเสร็จ']
    ];
    
    foreach ($default_settings as $setting) {
        $key = $connection->real_escape_string($setting[0]);
        $value = $connection->real_escape_string($setting[1]);
        $desc = $connection->real_escape_string($setting[2]);
        
        $sql = "INSERT IGNORE INTO system_settings (setting_key, setting_value, description) 
                VALUES ('$key', '$value', '$desc')";
        $connection->query($sql);
    }
    
    // เพิ่มหมวดหมู่เมนูตัวอย่าง
    $categories = [
        ['อาหารจานหลัก', 'เมนูอาหารจานหลักต่างๆ', '#28a745'],
        ['เครื่องดื่ม', 'เครื่องดื่มทุกประเภท', '#007bff'],
        ['ของหวาน', 'ขนมหวานและของหวาน', '#ffc107']
    ];
    
    foreach ($categories as $i => $cat) {
        $name = $connection->real_escape_string($cat[0]);
        $desc = $connection->real_escape_string($cat[1]);
        $color = $cat[2];
        
        $sql = "INSERT IGNORE INTO menu_categories (name, description, color_code, sort_order) 
                VALUES ('$name', '$desc', '$color', " . ($i + 1) . ")";
        $connection->query($sql);
    }
    
    return true;
}

// การจัดการ POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_database':
            if (create_database()) {
                $message = 'สร้างฐานข้อมูลสำเร็จ!';
                $step = 2;
            } else {
                $error = 'ไม่สามารถสร้างฐานข้อมูลได้';
            }
            break;
            
        case 'create_tables':
            try {
                if (!check_database_connection()) {
                    throw new Exception('ไม่สามารถเชื่อมต่อฐานข้อมูลได้');
                }
                
                create_all_tables();
                insert_default_data();
                
                $message = 'สร้างตารางและข้อมูลเริ่มต้นสำเร็จ!';
                $step = 3;
                
            } catch (Exception $e) {
                $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
            }
            break;
    }
}

// ตรวจสอบสถานะปัจจุบัน
$db_exists = false;
$tables_exist = false;

try {
    $db_exists = check_database_connection();
    if ($db_exists) {
        $tables_exist = !empty(check_required_tables()) ? false : true;
    }
} catch (Exception $e) {
    // ไม่ต้องทำอะไร
}

if (is_already_installed() && $step == 1) {
    $step = 4; // แสดงว่าติดตั้งแล้ว
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตั้งระบบ - Smart Order Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Sarabun', sans-serif;
        }
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        .setup-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .setup-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        .setup-title {
            color: #333;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 3rem;
        }
        .step {
            display: flex;
            align-items: center;
            margin: 0 1rem;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 10px;
        }
        .step.active .step-number {
            background: #667eea;
            color: white;
        }
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        .step.pending .step-number {
            background: #e9ecef;
            color: #6c757d;
        }
        .status-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .status-item:last-child {
            margin-bottom: 0;
        }
        .status-icon {
            width: 24px;
            margin-right: 12px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 500;
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="setup-container">
            <div class="setup-card">
                <div class="setup-header">
                    <h1 class="setup-title h2">
                        <i class="fas fa-tools me-3"></i>
                        ติดตั้งระบบ Smart Order Management
                    </h1>
                    <p class="text-muted">ติดตั้งและตั้งค่าระบบเพื่อเริ่มใช้งาน</p>
                </div>

                <div class="step-indicator">
                    <div class="step <?php echo $step >= 1 ? 'active' : 'pending'; ?>">
                        <div class="step-number">1</div>
                        <span>ตรวจสอบ</span>
                    </div>
                    <div class="step <?php echo $step >= 2 ? 'active' : 'pending'; ?>">
                        <div class="step-number">2</div>
                        <span>ฐานข้อมูล</span>
                    </div>
                    <div class="step <?php echo $step >= 3 ? 'active' : 'pending'; ?>">
                        <div class="step-number">3</div>
                        <span>ตาราง</span>
                    </div>
                    <div class="step <?php echo $step >= 4 ? 'completed' : 'pending'; ?>">
                        <div class="step-number">4</div>
                        <span>เสร็จสิ้น</span>
                    </div>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
                <?php endif; ?>

                <?php if ($step == 1): ?>
                <!-- Step 1: ตรวจสอบระบบ -->
                <div class="status-card">
                    <h5 class="mb-3"><i class="fas fa-search me-2"></i>ตรวจสอบสถานะระบบ</h5>
                    
                    <div class="status-item">
                        <div class="status-icon">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <span>PHP Version: <?php echo PHP_VERSION; ?></span>
                    </div>
                    
                    <div class="status-item">
                        <div class="status-icon">
                            <?php if (extension_loaded('mysqli')): ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i>
                            <?php endif; ?>
                        </div>
                        <span>MySQLi Extension: <?php echo extension_loaded('mysqli') ? 'พร้อมใช้งาน' : 'ไม่พร้อมใช้งาน'; ?></span>
                    </div>
                    
                    <div class="status-item">
                        <div class="status-icon">
                            <?php if ($db_exists): ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-warning"></i>
                            <?php endif; ?>
                        </div>
                        <span>การเชื่อมต่อฐานข้อมูล: <?php echo $db_exists ? 'เชื่อมต่อได้' : 'ไม่สามารถเชื่อมต่อได้'; ?></span>
                    </div>
                </div>

                <?php if (!$db_exists): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="create_database">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-database me-2"></i>สร้างฐานข้อมูล
                    </button>
                </form>
                <?php else: ?>
                <a href="?step=2" class="btn btn-primary">
                    <i class="fas fa-arrow-right me-2"></i>ดำเนินการต่อ
                </a>
                <?php endif; ?>

                <?php elseif ($step == 2): ?>
                <!-- Step 2: ตรวจสอบตาราง -->
                <div class="status-card">
                    <h5 class="mb-3"><i class="fas fa-table me-2"></i>ตรวจสอบตารางฐานข้อมูล</h5>
                    
                    <?php $missing_tables = check_required_tables(); ?>
                    <?php if (empty($missing_tables)): ?>
                        <div class="status-item">
                            <div class="status-icon">
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                            <span>ตารางทั้งหมดพร้อมใช้งาน</span>
                        </div>
                        <a href="?step=3" class="btn btn-primary mt-3">
                            <i class="fas fa-arrow-right me-2"></i>ดำเนินการต่อ
                        </a>
                    <?php else: ?>
                        <div class="status-item">
                            <div class="status-icon">
                                <i class="fas fa-exclamation-triangle text-warning"></i>
                            </div>
                            <span>ตารางที่หายไป: <?php echo implode(', ', $missing_tables); ?></span>
                        </div>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="create_tables">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>สร้างตารางและข้อมูลเริ่มต้น
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <?php elseif ($step == 3): ?>
                <!-- Step 3: เสร็จสิ้น -->
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="text-success mb-3">ติดตั้งเสร็จสิ้น!</h3>
                    <p class="text-muted mb-4">ระบบพร้อมใช้งานแล้ว คุณสามารถเข้าสู่ระบบด้วยข้อมูลต่อไปนี้:</p>
                    
                    <div class="status-card text-start">
                        <h6><i class="fas fa-user me-2"></i>ข้อมูลผู้ดูแลระบบ</h6>
                        <p class="mb-1"><strong>Username:</strong> admin</p>
                        <p class="mb-0"><strong>Password:</strong> admin123</p>
                    </div>
                    
                    <div class="d-flex gap-3 justify-content-center mt-4">
                        <a href="/index.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>ไปหน้าหลัก
                        </a>
                        <a href="/login.php" class="btn btn-outline-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                        </a>
                    </div>
                </div>

                <?php elseif ($step == 4): ?>
                <!-- Step 4: ติดตั้งแล้ว -->
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-info-circle text-info" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="text-info mb-3">ระบบถูกติดตั้งแล้ว</h3>
                    <p class="text-muted mb-4">ระบบได้ถูกติดตั้งและตั้งค่าเรียบร้อยแล้ว</p>
                    
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="/index.php" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>ไปหน้าหลัก
                        </a>
                        <a href="/pos/" class="btn btn-outline-primary">
                            <i class="fas fa-cash-register me-2"></i>ระบบ POS
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>