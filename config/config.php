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

// ค่าคงที่ของระบบ
define('SITE_URL', 'http://localhost/smart_order/');
define('SITE_NAME', 'Smart Order Management System');
define('VERSION', '1.0.0');

// ตั้งค่าฐานข้อมูล
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_order');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ตั้งค่า LINE OA
define('LINE_CHANNEL_ACCESS_TOKEN', 'YOUR_LINE_CHANNEL_ACCESS_TOKEN');
define('LINE_CHANNEL_SECRET', 'YOUR_LINE_CHANNEL_SECRET');

// ตั้งค่าการอัปโหลดไฟล์
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// ตั้งค่า PromptPay
define('PROMPTPAY_ID', '0123456789'); // เลขประจำตัวผู้เสียภาษี หรือ เบอร์โทร

// ตั้งค่าระบบคิว
define('QUEUE_PREFIX', 'A');
define('QUEUE_DIGITS', 3);

// ตั้งค่าเสียงเรียกคิว
define('VOICE_ENABLED', true);
define('VOICE_LANGUAGE', 'th-TH');
define('VOICE_SPEED', 0.8);

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

// ฟังก์ชันตรวจสอบสิทธิ์การเข้าถึง
function hasPermission($permission) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    $permissions = [
        'admin' => ['view_all', 'edit_all', 'delete_all', 'settings'],
        'manager' => ['view_all', 'edit_orders', 'view_reports'],
        'staff' => ['view_orders', 'edit_orders'],
        'kitchen' => ['view_kitchen', 'update_kitchen']
    ];
    
    $userRole = $_SESSION['user_role'];
    return in_array($permission, $permissions[$userRole] ?? []);
}

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
?>