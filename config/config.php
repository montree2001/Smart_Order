<?php
// config/config.php - การตั้งค่าระบบ
if (!defined('SYSTEM_CONFIG_LOADED')) {
    define('SYSTEM_CONFIG_LOADED', true);
}

// เริ่มต้น session หากยังไม่ได้เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    // ตั้งค่า session security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

// การตั้งค่าพื้นฐาน
define('SYSTEM_NAME', 'Smart Order Management System');
define('SYSTEM_VERSION', '1.0.0');
define('SYSTEM_AUTHOR', 'Smart Order Team');

// การตั้งค่าเส้นทาง
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
define('ADMIN_URL', BASE_URL . '/admin');
define('POS_URL', BASE_URL . '/pos');
define('CUSTOMER_URL', BASE_URL . '/customer');
define('API_URL', BASE_URL . '/api');

// การตั้งค่าไฟล์และโฟลเดอร์
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');
define('LOGS_PATH', BASE_PATH . '/logs');
define('CACHE_PATH', BASE_PATH . '/cache');
define('BACKUP_PATH', BASE_PATH . '/backups');

// การตั้งค่าเขตเวลา
define('TIMEZONE', 'Asia/Bangkok');
date_default_timezone_set(TIMEZONE);

// การตั้งค่า PHP
ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '256M');
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');

// การตั้งค่า Error Reporting
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_PATH . '/php_errors.log');
}

// การตั้งค่า Security
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour
define('SESSION_TIMEOUT', 28800); // 8 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('PASSWORD_MIN_LENGTH', 6);
define('PASSWORD_REQUIRE_UPPERCASE', false);
define('PASSWORD_REQUIRE_LOWERCASE', false);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', false);

// การตั้งค่า File Upload
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
define('IMAGE_UPLOAD_PATH', UPLOAD_PATH . '/images');
define('DOCUMENT_UPLOAD_PATH', UPLOAD_PATH . '/documents');

// การตั้งค่า Email SMTP
$smtp_config = [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => '', // ให้ผู้ใช้กรอก
    'password' => '', // ให้ผู้ใช้กรอก
    'encryption' => 'tls',
    'from_name' => SYSTEM_NAME,
    'from_email' => 'noreply@smartorder.com'
];

// การตั้งค่า LINE Bot
$line_config = [
    'channel_access_token' => '', // ให้ผู้ใช้กรอก
    'channel_secret' => '', // ให้ผู้ใช้กรอก
    'webhook_url' => BASE_URL . '/api/line_webhook.php'
];

// การตั้งค่า Payment Gateway
$payment_config = [
    'promptpay_id' => '', // ให้ผู้ใช้กรอก
    'omise_public_key' => '', // ถ้าใช้ Omise
    'omise_secret_key' => '', // ถ้าใช้ Omise
    'true_money_api_key' => '', // ถ้าใช้ TrueMoney
    'scb_easy_api_key' => '' // ถ้าใช้ SCB Easy
];

// การตั้งค่าเริ่มต้นของร้าน
$default_shop_settings = [
    'shop_name' => 'ร้านอาหารอัจฉริยะ',
    'shop_phone' => '02-XXX-XXXX',
    'shop_address' => '',
    'shop_email' => '',
    'shop_website' => '',
    'tax_id' => '',
    'tax_rate' => 7.00,
    'service_charge_rate' => 0.00,
    'currency' => 'THB',
    'currency_symbol' => '฿',
    'timezone' => TIMEZONE,
    'default_language' => 'th',
    'date_format' => 'd/m/Y',
    'time_format' => 'H:i',
    'decimal_places' => 2
];

// การตั้งค่าเริ่มต้นของระบบคิว
$default_queue_settings = [
    'queue_reset_daily' => true,
    'max_queue_per_day' => 999,
    'queue_call_timeout' => 300, // 5 minutes
    'notification_before_queue' => 3,
    'enable_voice_queue' => true,
    'voice_language' => 'th',
    'preparation_time_per_item' => 5, // minutes
    'queue_display_refresh_interval' => 5000 // milliseconds
];

// การตั้งค่าเริ่มต้นของระบบการชำระเงิน
$default_payment_settings = [
    'accept_cash' => true,
    'accept_qr' => true,
    'accept_card' => false,
    'accept_bank_transfer' => false,
    'require_customer_info' => false,
    'auto_print_receipt' => false,
    'send_receipt_email' => false,
    'send_receipt_line' => true
];

// การตั้งค่าเริ่มต้นของระบบแจ้งเตือน
$default_notification_settings = [
    'enable_line_notifications' => true,
    'enable_email_notifications' => false,
    'enable_sms_notifications' => false,
    'notify_new_order' => true,
    'notify_order_ready' => true,
    'notify_payment_received' => true,
    'notify_queue_called' => true
];

// ฟังก์ชันโหลดการตั้งค่าจากฐานข้อมูล
function load_system_settings() {
    global $connection, $system_settings;
    
    if (!$connection) {
        return false;
    }
    
    $query = "SELECT setting_key, setting_value FROM system_settings ORDER BY category, setting_key";
    $result = mysqli_query($connection, $query);
    
    $system_settings = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $system_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    return $system_settings;
}

// ฟังก์ชันได้การตั้งค่า
function get_setting($key, $default = null) {
    global $system_settings;
    
    if (!isset($system_settings)) {
        load_system_settings();
    }
    
    return $system_settings[$key] ?? $default;
}

// ฟังก์ชันบันทึกการตั้งค่า
function save_setting($key, $value, $category = 'general', $data_type = 'string', $description = '') {
    global $connection;
    
    if (!$connection) {
        return false;
    }
    
    $key = mysqli_real_escape_string($connection, $key);
    $value = mysqli_real_escape_string($connection, $value);
    $category = mysqli_real_escape_string($connection, $category);
    $data_type = mysqli_real_escape_string($connection, $data_type);
    $description = mysqli_real_escape_string($connection, $description);
    
    $query = "
        INSERT INTO system_settings 
        (setting_key, setting_value, category, data_type, description, updated_at) 
        VALUES ('$key', '$value', '$category', '$data_type', '$description', NOW())
        ON DUPLICATE KEY UPDATE 
        setting_value = VALUES(setting_value),
        updated_at = NOW()
    ";
    
    return mysqli_query($connection, $query);
}

// ฟังก์ชันลบการตั้งค่า
function delete_setting($key) {
    global $connection;
    
    if (!$connection) {
        return false;
    }
    
    $key = mysqli_real_escape_string($connection, $key);
    $query = "DELETE FROM system_settings WHERE setting_key = '$key'";
    
    return mysqli_query($connection, $query);
}

// ฟังก์ชันได้การตั้งค่าทั้งหมดของหมวดหมู่
function get_category_settings($category) {
    global $connection;
    
    if (!$connection) {
        return [];
    }
    
    $category = mysqli_real_escape_string($connection, $category);
    $query = "SELECT * FROM system_settings WHERE category = '$category' ORDER BY setting_key";
    $result = mysqli_query($connection, $query);
    
    $settings = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row['setting_key']] = $row;
        }
    }
    
    return $settings;
}

// ฟังก์ชันบันทึกการตั้งค่าหลายค่า
function save_multiple_settings($settings, $category = 'general') {
    global $connection;
    
    if (!$connection || empty($settings)) {
        return false;
    }
    
    mysqli_begin_transaction($connection);
    
    try {
        foreach ($settings as $key => $value) {
            if (!save_setting($key, $value, $category)) {
                throw new Exception("Failed to save setting: $key");
            }
        }
        
        mysqli_commit($connection);
        
        // อัปเดต cache
        load_system_settings();
        
        return true;
        
    } catch (Exception $e) {
        mysqli_rollback($connection);
        error_log("Save settings error: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชันสร้างการตั้งค่าเริ่มต้น
function create_default_settings() {
    global $default_shop_settings, $default_queue_settings, 
           $default_payment_settings, $default_notification_settings;
    
    $all_settings = [
        'shop' => $default_shop_settings,
        'queue' => $default_queue_settings,
        'payment' => $default_payment_settings,
        'notification' => $default_notification_settings
    ];
    
    foreach ($all_settings as $category => $settings) {
        if (!save_multiple_settings($settings, $category)) {
            return false;
        }
    }
    
    return true;
}

// ฟังก์ชันตรวจสอบการติดตั้ง
function is_system_installed() {
    global $connection;
    
    if (!$connection) {
        return false;
    }
    
    // ตรวจสอบว่ามีตาราง system_settings หรือไม่
    $result = mysqli_query($connection, "SHOW TABLES LIKE 'system_settings'");
    if (!$result || mysqli_num_rows($result) === 0) {
        return false;
    }
    
    // ตรวจสอบว่ามีผู้ใช้ admin หรือไม่
    $result = mysqli_query($connection, "SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    if (!$result || mysqli_num_rows($result) === 0) {
        return false;
    }
    
    return true;
}

// ฟังก์ชันได้ข้อมูลระบบ
function get_system_info() {
    return [
        'name' => SYSTEM_NAME,
        'version' => SYSTEM_VERSION,
        'author' => SYSTEM_AUTHOR,
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'mysql_version' => get_mysql_version(),
        'timezone' => TIMEZONE,
        'current_time' => date('Y-m-d H:i:s'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ];
}

// ฟังก์ชันได้เวอร์ชัน MySQL
function get_mysql_version() {
    global $connection;
    
    if (!$connection) {
        return 'Unknown';
    }
    
    $result = mysqli_query($connection, "SELECT VERSION() as version");
    $row = mysqli_fetch_assoc($result);
    
    return $row['version'] ?? 'Unknown';
}

// ฟังก์ชันตรวจสอบความต้องการของระบบ
function check_system_requirements() {
    $requirements = [
        'php_version' => [
            'required' => '7.4.0',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
        ],
        'mysqli_extension' => [
            'required' => true,
            'current' => extension_loaded('mysqli'),
            'status' => extension_loaded('mysqli')
        ],
        'gd_extension' => [
            'required' => true,
            'current' => extension_loaded('gd'),
            'status' => extension_loaded('gd')
        ],
        'curl_extension' => [
            'required' => true,
            'current' => extension_loaded('curl'),
            'status' => extension_loaded('curl')
        ],
        'json_extension' => [
            'required' => true,
            'current' => extension_loaded('json'),
            'status' => extension_loaded('json')
        ],
        'uploads_writable' => [
            'required' => true,
            'current' => is_writable(UPLOAD_PATH) || is_writable(dirname(UPLOAD_PATH)),
            'status' => is_writable(UPLOAD_PATH) || is_writable(dirname(UPLOAD_PATH))
        ],
        'logs_writable' => [
            'required' => true,
            'current' => is_writable(LOGS_PATH) || is_writable(dirname(LOGS_PATH)),
            'status' => is_writable(LOGS_PATH) || is_writable(dirname(LOGS_PATH))
        ]
    ];
    
    return $requirements;
}

// ฟังก์ชันสร้างโฟลเดอร์ที่จำเป็น
function create_required_directories() {
    $directories = [
        UPLOAD_PATH,
        IMAGE_UPLOAD_PATH,
        DOCUMENT_UPLOAD_PATH,
        LOGS_PATH,
        CACHE_PATH,
        BACKUP_PATH
    ];
    
    $errors = [];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                $errors[] = "Cannot create directory: $dir";
            }
        }
        
        if (!is_writable($dir)) {
            $errors[] = "Directory not writable: $dir";
        }
    }
    
    return empty($errors) ? true : $errors;
}

// ฟังก์ชันสร้างไฟล์ .htaccess
function create_htaccess_files() {
    $htaccess_content = [
        UPLOAD_PATH . '/.htaccess' => "Options -Indexes\nDeny from all",
        LOGS_PATH . '/.htaccess' => "Options -Indexes\nDeny from all",
        CACHE_PATH . '/.htaccess' => "Options -Indexes\nDeny from all",
        BACKUP_PATH . '/.htaccess' => "Options -Indexes\nDeny from all"
    ];
    
    foreach ($htaccess_content as $file => $content) {
        if (!file_exists($file)) {
            file_put_contents($file, $content);
        }
    }
}

// ฟังก์ชันล้างแคช
function clear_cache() {
    global $system_settings;
    
    // ล้าง system settings cache
    $system_settings = null;
    
    // ล้างไฟล์แคช (ถ้ามี)
    if (is_dir(CACHE_PATH)) {
        $files = glob(CACHE_PATH . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    return true;
}

// ฟังก์ชันได้ขนาดฐานข้อมูล
function get_database_size() {
    global $connection;
    
    if (!$connection) {
        return 0;
    }
    
    $query = "
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.tables 
        WHERE table_schema = '" . DB_NAME . "'
    ";
    
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['size_mb'] ?? 0;
}

// ฟังก์ชันได้สถิติระบบ
function get_system_stats() {
    global $connection;
    
    if (!$connection) {
        return [];
    }
    
    $stats = [];
    
    // นับจำนวนออเดอร์วันนี้
    $today = date('Y-m-d');
    $result = mysqli_query($connection, "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = '$today'");
    $stats['orders_today'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    // นับจำนวนลูกค้าทั้งหมด
    $result = mysqli_query($connection, "SELECT COUNT(*) as count FROM customers");
    $stats['total_customers'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    // นับจำนวนเมนู
    $result = mysqli_query($connection, "SELECT COUNT(*) as count FROM menu_items WHERE is_active = 1");
    $stats['active_menu_items'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    // นับจำนวนผู้ใช้
    $result = mysqli_query($connection, "SELECT COUNT(*) as count FROM users WHERE is_active = 1");
    $stats['active_users'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    // ยอดขายวันนี้
    $result = mysqli_query($connection, "SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_at) = '$today' AND payment_status = 'paid'");
    $stats['sales_today'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    return $stats;
}

// ตัวแปรสำหรับเก็บการตั้งค่าระบบ
$system_settings = null;

// สร้างโฟลเดอร์ที่จำเป็น
create_required_directories();

// สร้างไฟล์ .htaccess
create_htaccess_files();

// โหลดการตั้งค่าระบบ (ถ้ามีการเชื่อมต่อฐานข้อมูล)
if (isset($connection) && $connection) {
    load_system_settings();
}

?>