<?php
// admin/logout.php - ออกจากระบบ
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// บันทึก log การออกจากระบบ
if (isset($_SESSION['user_id'])) {
    $user_id = intval($_SESSION['user_id']);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // บันทึกลง user_activity_logs
    if ($connection) {
        $query = "
            INSERT INTO user_activity_logs 
            (user_id, action, details, ip_address, user_agent, created_at) 
            VALUES ($user_id, 'logout', 'ออกจากระบบ', '$ip_address', '$user_agent', NOW())
        ";
        mysqli_query($connection, $query);
    }
    
    // ลบ Remember Me Token
    if (isset($_COOKIE['remember_token'])) {
        $token = mysqli_real_escape_string($connection, $_COOKIE['remember_token']);
        $delete_query = "DELETE FROM remember_tokens WHERE token = '$token' OR user_id = $user_id";
        mysqli_query($connection, $delete_query);
        
        // ลบ cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
}

// ล้างข้อมูล session ทั้งหมด
session_unset();
session_destroy();

// ลบ cookies ที่เกี่ยวข้อง
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// สร้าง session ใหม่เพื่อป้องกัน session fixation
session_start();
session_regenerate_id(true);

// ตั้งค่าข้อความแจ้งเตือน
$_SESSION['logout_message'] = 'คุณได้ออกจากระบบเรียบร้อยแล้ว';

// Redirect ไปหน้า login
header('Location: login.php');
exit();
?>