<?php
// includes/functions.php - รวมฟังก์ชันช่วยเหลือทั้งหมด

// ป้องกันการประกาศฟังก์ชันซ้ำ
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return number_format($amount, 2) . ' ฿';
    }
}

if (!function_exists('formatDateTime')) {
    function formatDateTime($datetime) {
        return date('d/m/Y H:i:s', strtotime($datetime));
    }
}

// ฟังก์ชันสำหรับสถานะออเดอร์
if (!function_exists('getStatusColor')) {
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
}

if (!function_exists('getStatusText')) {
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
}

// ฟังก์ชันสำหรับการชำระเงิน
if (!function_exists('getPaymentColor')) {
    function getPaymentColor($method) {
        $colors = [
            'cash' => 'success',
            'qr_payment' => 'info',
            'card' => 'warning'
        ];
        return $colors[$method] ?? 'secondary';
    }
}

if (!function_exists('getPaymentText')) {
    function getPaymentText($method) {
        $texts = [
            'cash' => 'เงินสด',
            'qr_payment' => 'QR Payment',
            'card' => 'บัตร'
        ];
        return $texts[$method] ?? $method;
    }
}

// ฟังก์ชันสำหรับสถานะคิว
if (!function_exists('getQueueStatusColor')) {
    function getQueueStatusColor($status) {
        $colors = [
            'waiting' => 'warning',
            'calling' => 'info',
            'served' => 'success',
            'no_show' => 'danger'
        ];
        return $colors[$status] ?? 'secondary';
    }
}

if (!function_exists('getQueueStatusText')) {
    function getQueueStatusText($status) {
        $texts = [
            'waiting' => 'รอเรียก',
            'calling' => 'กำลังเรียก',
            'served' => 'บริการแล้ว',
            'no_show' => 'ไม่มาติดต่อ'
        ];
        return $texts[$status] ?? $status;
    }
}

// ฟังก์ชันสำหรับบทบาทผู้ใช้
if (!function_exists('getRoleColor')) {
    function getRoleColor($role) {
        $colors = [
            'admin' => 'danger',
            'pos' => 'primary',
            'kitchen' => 'success'
        ];
        return $colors[$role] ?? 'secondary';
    }
}

if (!function_exists('getRoleText')) {
    function getRoleText($role) {
        $texts = [
            'admin' => 'ผู้ดูแลระบบ',
            'pos' => 'พนักงาน POS',
            'kitchen' => 'พนักงานครัว'
        ];
        return $texts[$role] ?? $role;
    }
}

// ฟังก์ชันสร้างหมายเลขคิว
if (!function_exists('generateQueueNumber')) {
    function generateQueueNumber() {
        global $db;
        $result = $db->fetchOne("CALL GetNextQueueNumber()");
        return $result['queue_number'];
    }
}

// ฟังก์ชันตรวจสอบการเข้าสู่ระบบ
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            redirect(ADMIN_URL . 'login.php');
        }
    }
}

// ฟังก์ชันสำหรับการแสดงผลแจ้งเตือน
if (!function_exists('showAlert')) {
    function showAlert($message, $type = 'success') {
        return "
        <div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
            {$message}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
}

// ฟังก์ชันสำหรับการอัปโหลดไฟล์
if (!function_exists('uploadFile')) {
    function uploadFile($file, $destination = 'uploads/') {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        $filename = uniqid() . '_' . basename($file['name']);
        $filepath = $destination . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $filename;
        }

        return false;
    }
}

// ฟังก์ชันสำหรับการสร้าง UUID
if (!function_exists('generateUUID')) {
    function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

// ฟังก์ชันสำหรับการ sanitize input
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

// ฟังก์ชันสำหรับการตรวจสอบเบอร์โทรศัพท์
if (!function_exists('validatePhone')) {
    function validatePhone($phone) {
        // ตรวจสอบเบอร์โทรไทย (0x-xxxx-xxxx หรือ 0xx-xxx-xxxx)
        return preg_match('/^0[0-9]{8,9}$/', $phone);
    }
}

// ฟังก์ชันสำหรับการตรวจสอบอีเมล
if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}

// ฟังก์ชันสำหรับการคำนวณเวลาโดยประมาณ
if (!function_exists('calculateEstimatedTime')) {
    function calculateEstimatedTime($queuePosition, $itemCount = 1) {
        global $db;
        $settings = $db->fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = 'estimated_time_per_item'");
        $timePerItem = $settings['setting_value'] ?? 5;
        
        return ($queuePosition * $timePerItem) + ($itemCount * 2);
    }
}

// ฟังก์ชันสำหรับการสร้างรหัสผ่านแบบสุ่ม
if (!function_exists('generateRandomPassword')) {
    function generateRandomPassword($length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomPassword = '';
        for ($i = 0; $i < $length; $i++) {
            $randomPassword .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomPassword;
    }
}

// ฟังก์ชันสำหรับการจัดรูปแบบขนาดไฟล์
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// ฟังก์ชันสำหรับการล็อกกิ้ง
if (!function_exists('logActivity')) {
    function logActivity($action, $details = '', $userId = null) {
        global $db;
        
        if (!$userId && isset($_SESSION['admin_id'])) {
            $userId = $_SESSION['admin_id'];
        }
        
        $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())";
        $db->query($sql, [$userId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? '']);
    }
}

// ฟังก์ชันสำหรับการส่งอีเมล (ถ้าต้องการใช้งาน)
if (!function_exists('sendEmail')) {
    function sendEmail($to, $subject, $message, $from = '') {
        if (empty($from)) {
            $from = 'noreply@smartorder.com';
        }
        
        $headers = "From: $from\r\n";
        $headers .= "Reply-To: $from\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return mail($to, $subject, $message, $headers);
    }
}
?>