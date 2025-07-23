<?php
// เริ่ม session เฉพาะเมื่อยังไม่ได้เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตั้งค่าเขตเวลา
date_default_timezone_set('Asia/Bangkok');

// ตั้งค่าการแสดงข้อผิดพลาด (สำหรับ development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ค่าคงที่ของระบบ (ตรวจสอบก่อน define)
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost/pos/');
}
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Smart Order Management System');
}
if (!defined('VERSION')) {
    define('VERSION', '1.0.0');
}

// ตั้งค่าฐานข้อมูล (ตรวจสอบก่อน define)
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'smart_order');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// ตั้งค่า LINE OA (ตรวจสอบก่อน define)
if (!defined('LINE_CHANNEL_ACCESS_TOKEN')) {
    define('LINE_CHANNEL_ACCESS_TOKEN', 'YOUR_LINE_CHANNEL_ACCESS_TOKEN');
}
if (!defined('LINE_CHANNEL_SECRET')) {
    define('LINE_CHANNEL_SECRET', 'YOUR_LINE_CHANNEL_SECRET');
}

// ตั้งค่าการอัปโหลดไฟล์ (ตรวจสอบก่อน define)
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', __DIR__ . '/../uploads/');
}
// Add missing path constants
if (!defined('IMAGE_UPLOAD_PATH')) {
    define('IMAGE_UPLOAD_PATH', __DIR__ . '/../uploads/menu_images/');
}
if (!defined('DOCUMENT_UPLOAD_PATH')) {
    define('DOCUMENT_UPLOAD_PATH', __DIR__ . '/../uploads/documents/');
}
if (!defined('LOGS_PATH')) {
    define('LOGS_PATH', __DIR__ . '/../logs/');
}
if (!defined('CACHE_PATH')) {
    define('CACHE_PATH', __DIR__ . '/../cache/');
}
if (!defined('BACKUP_PATH')) {
    define('BACKUP_PATH', __DIR__ . '/../backups/');
}
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
}

// ตั้งค่า PromptPay (ตรวจสอบก่อน define)
if (!defined('PROMPTPAY_ID')) {
    define('PROMPTPAY_ID', '0123456789'); // เลขประจำตัวผู้เสียภาษี หรือ เบอร์โทร
}

// ตั้งค่าระบบคิว (ตรวจสอบก่อน define)
if (!defined('QUEUE_PREFIX')) {
    define('QUEUE_PREFIX', 'A');
}
if (!defined('QUEUE_DIGITS')) {
    define('QUEUE_DIGITS', 3);
}

// ตั้งค่าเสียงเรียกคิว (ตรวจสอบก่อน define)
if (!defined('VOICE_ENABLED')) {
    define('VOICE_ENABLED', true);
}
if (!defined('VOICE_LANGUAGE')) {
    define('VOICE_LANGUAGE', 'th-TH');
}
if (!defined('VOICE_SPEED')) {
    define('VOICE_SPEED', 0.8);
}

// ฟังก์ชันช่วยเหลือ
function formatPrice($price) {
    return '฿' . number_format($price, 0);
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatTime($time) {
    return date('H:i', strtotime($time));
}

function generateOrderId() {
    return 'ORD' . date('YmdHis') . rand(100, 999);
}

function generateQueueNumber($orderCount = null) {
    if ($orderCount === null) {
        $orderCount = rand(1, 999);
    }
    return QUEUE_PREFIX . str_pad($orderCount, QUEUE_DIGITS, '0', STR_PAD_LEFT);
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function isValidPhone($phone) {
    return preg_match('/^[0-9]{9,10}$/', $phone);
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// ฟังก์ชันจัดการข้อผิดพลาด
function handleError($message, $file = '', $line = '') {
    $errorLog = [
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'file' => $file,
        'line' => $line,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
    ];
    
    error_log(json_encode($errorLog), 3, __DIR__ . '/../logs/error.log');
}

// ฟังก์ชัน JSON Response
function jsonResponse($data, $success = true, $message = '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ฟังก์ชันสร้าง CSRF Token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ฟังก์ชันตรวจสอบ CSRF Token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ฟังก์ชันสร้าง URL สำหรับการ redirect
function redirectTo($path, $params = []) {
    $url = SITE_URL . ltrim($path, '/');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    header("Location: $url");
    exit;
}

// ฟังก์ชันตรวจสอบว่าเป็น AJAX request หรือไม่
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// ฟังก์ชันส่งการแจ้งเตือน
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

// ฟังก์ชันดึงการแจ้งเตือน
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

// ฟังก์ชันตรวจสอบสิทธิ์การเข้าถึง (ย้ายไป auth.php แล้ว)
// ฟังก์ชันนี้อยู่ใน includes/auth.php

// อัปเดต last activity
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}

// ตรวจสอบ session timeout (30 นาที)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
    session_unset();
    session_destroy();
    if (isAjaxRequest()) {
        jsonResponse(null, false, 'Session expired');
    } else {
        redirectTo('admin/login.php', ['expired' => 1]);
    }
}

// สร้างโฟลเดอร์ที่จำเป็น
$requiredDirs = [
    __DIR__ . '/../uploads/menu_images',
    __DIR__ . '/../uploads/receipts',
    __DIR__ . '/../uploads/temp',
    __DIR__ . '/../logs'
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// กำหนดค่าเริ่มต้นสำหรับ user ที่ยังไม่ได้ล็อกอิน (สำหรับทดสอบ)
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    // ตั้งค่า session ชั่วคราวสำหรับทดสอบระบบ POS
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Admin';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['login_time'] = time();
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