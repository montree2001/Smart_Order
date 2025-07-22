<?php
session_start();

// ตั้งค่าระบบ
define('SITE_NAME', 'Smart Order Management');
define('SITE_URL', 'http://localhost/smart_order/');
define('ADMIN_URL', SITE_URL . 'admin/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('TIMEZONE', 'Asia/Bangkok');

date_default_timezone_set(TIMEZONE);

// รวมไฟล์ที่จำเป็น
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Database connection
$db = new Database();

// เพิ่มการตั้งค่าสำหรับ error reporting (สำหรับ development)
if (defined('DEVELOPMENT') && DEVELOPMENT === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// เพิ่มการตั้งค่าสำหรับ timezone database
try {
    $db->query("SET time_zone = '+07:00'");
} catch (Exception $e) {
    // Handle timezone setting error silently
}
?>