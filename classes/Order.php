
<?php
session_start();

// ตั้งค่าระบบ
define('SITE_NAME', 'Smart Order Management');
define('SITE_URL', 'http://localhost/smart_order/');
define('ADMIN_URL', SITE_URL . 'admin/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('TIMEZONE', 'Asia/Bangkok');

date_default_timezone_set(TIMEZONE);

// Database
$db = new Database();

// ฟังก์ชันช่วยเหลือ
function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect(ADMIN_URL . 'login.php');
    }
}

function formatCurrency($amount) {
    return number_format($amount, 2) . ' ฿';
}

function formatDateTime($datetime) {
    return date('d/m/Y H:i:s', strtotime($datetime));
}

function generateQueueNumber() {
    global $db;
    $result = $db->fetchOne("CALL GetNextQueueNumber()");
    return $result['queue_number'];
}