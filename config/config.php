<?php
session_start();

// ตั้งค่าระบบ
define('SITE_NAME', 'Smart Order Management');
define('SITE_URL', 'http://localhost/smart_order/');
define('ADMIN_URL', SITE_URL . 'admin/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('TIMEZONE', 'Asia/Bangkok');

date_default_timezone_set(TIMEZONE);

// เชื่อมต่อฐานข้อมูล
require_once __DIR__ . '/../classes/Database.php';
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
    $result = $db->fetchOne("
        SELECT COALESCE(MAX(queue_number), 0) + 1 as queue_number
        FROM orders 
        WHERE DATE(created_at) = CURDATE()
    ");
    return $result['queue_number'] ?? 1;
}

function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'preparing' => 'warning',
        'ready' => 'success',
        'completed' => 'success',
        'cancelled' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

function getStatusText($status) {
    $texts = [
        'pending' => 'รอยืนยัน',
        'confirmed' => 'ยืนยันแล้ว',
        'preparing' => 'กำลังทำ',
        'ready' => 'พร้อมเสิร์ฟ',
        'completed' => 'เสร็จสิ้น',
        'cancelled' => 'ยกเลิก'
    ];
    return $texts[$status] ?? $status;
}

function getPaymentColor($method) {
    $colors = [
        'cash' => 'success',
        'qr_payment' => 'info',
        'card' => 'warning'
    ];
    return $colors[$method] ?? 'secondary';
}

function getPaymentText($method) {
    $texts = [
        'cash' => 'เงินสด',
        'qr_payment' => 'QR Payment',
        'card' => 'บัตร'
    ];
    return $texts[$method] ?? $method;
}

function getQueueStatusColor($status) {
    $colors = [
        'waiting' => 'warning',
        'calling' => 'info',
        'served' => 'success',
        'no_show' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

function getQueueStatusText($status) {
    $texts = [
        'waiting' => 'รอเรียก',
        'calling' => 'กำลังเรียก',
        'served' => 'บริการแล้ว',
        'no_show' => 'ไม่มาติดต่อ'
    ];
    return $texts[$status] ?? $status;
}