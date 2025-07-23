<?php
// includes/functions.php - ฟังก์ชันทั่วไป
if (!defined('INCLUDED_FUNCTIONS')) {
    define('INCLUDED_FUNCTIONS', true);
}

// ฟังก์ชันสำหรับการทำความสะอาดข้อมูล
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// ฟังก์ชันสำหรับการตรวจสอบอีเมล
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// ฟังก์ชันสำหรับการตรวจสอบเบอร์โทรศัพท์ไทย
function validate_thai_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^(0[689]{1}[0-9]{8}|0[2-7]{1}[0-9]{7})$/', $phone);
}

// ฟังก์ชันสำหรับการแปลงวันที่
function thai_date($date, $format = 'full') {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }
    
    $thai_months = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    
    $thai_months_short = [
        'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
        'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
    ];
    
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = date('n', $timestamp) - 1;
    $year = date('Y', $timestamp) + 543;
    
    switch ($format) {
        case 'full':
            return $day . ' ' . $thai_months[$month] . ' ' . $year;
        case 'short':
            return $day . ' ' . $thai_months_short[$month] . ' ' . $year;
        case 'date_time':
            $time = date('H:i', $timestamp);
            return $day . ' ' . $thai_months_short[$month] . ' ' . $year . ' เวลา ' . $time . ' น.';
        default:
            return date('d/m/Y', $timestamp);
    }
}

// ฟังก์ชันสำหรับการสร้าง slug
function create_slug($string) {
    $string = trim($string);
    $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
    $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
    $string = preg_replace('/\s+/', '-', $string);
    $string = strtolower($string);
    return $string;
}

// ฟังก์ชันสำหรับการอัปโหลดไฟล์
function upload_file($file, $upload_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'ไม่พบไฟล์ที่อัปโหลด'];
    }
    
    // ตรวจสอบข้อผิดพลาด
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการอัปโหลด'];
    }
    
    // ตรวจสอบขนาดไฟล์
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'ไฟล์มีขนาดใหญ่เกินไป'];
    }
    
    // ตรวจสอบประเภทไฟล์
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'ประเภทไฟล์ไม่ถูกต้อง'];
    }
    
    // สร้างชื่อไฟล์ใหม่
    $new_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $upload_path = rtrim($upload_dir, '/') . '/' . $new_filename;
    
    // สร้างโฟลเดอร์หากไม่มี
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // ย้ายไฟล์
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return [
            'success' => true, 
            'filename' => $new_filename, 
            'path' => $upload_path,
            'url' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $upload_path)
        ];
    }
    
    return ['success' => false, 'message' => 'ไม่สามารถบันทึกไฟล์ได้'];
}

// ฟังก์ชันสำหรับการลบไฟล์
function delete_file($file_path) {
    if (file_exists($file_path) && is_file($file_path)) {
        return unlink($file_path);
    }
    return false;
}

// ฟังก์ชันสำหรับการสร้าง thumbnail
function create_thumbnail($source, $destination, $max_width = 300, $max_height = 300, $quality = 80) {
    if (!extension_loaded('gd')) {
        return false;
    }
    
    $image_info = getimagesize($source);
    if (!$image_info) {
        return false;
    }
    
    $width = $image_info[0];
    $height = $image_info[1];
    $type = $image_info[2];
    
    // คำนวณขนาดใหม่
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = (int)($width * $ratio);
    $new_height = (int)($height * $ratio);
    
    // สร้างภาพใหม่
    $new_image = imagecreatetruecolor($new_width, $new_height);
    
    // รักษาความโปร่งใส
    imagealphablending($new_image, false);
    imagesavealpha($new_image, true);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source_image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $source_image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $source_image = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    if (!$source_image) {
        return false;
    }
    
    // Resize ภาพ
    imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // บันทึกภาพ
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($new_image, $destination, $quality);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($new_image, $destination);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($new_image, $destination);
            break;
    }
    
    imagedestroy($source_image);
    imagedestroy($new_image);
    
    return $result;
}

// ฟังก์ชันสำหรับการแปลงเงิน
function format_currency($amount, $currency = '฿', $decimals = 2) {
    return $currency . number_format($amount, $decimals);
}

// ฟังก์ชันสำหรับการแปลงตัวเลขเป็นคำ (ภาษาไทย)
function number_to_thai_text($number) {
    $number = number_format($number, 2, '.', '');
    $number_arr = explode('.', $number);
    $int_part = $number_arr[0];
    $dec_part = $number_arr[1];
    
    $thai_number = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
    $thai_unit = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];
    
    $result = '';
    $int_length = strlen($int_part);
    
    if ($int_part == 0) {
        $result = 'ศูนย์';
    } else {
        for ($i = 0; $i < $int_length; $i++) {
            $digit = substr($int_part, $i, 1);
            $pos = $int_length - $i - 1;
            
            if ($digit != 0) {
                if ($pos == 1 && $digit == 1 && $int_length > 1) {
                    $result .= $thai_unit[$pos];
                } elseif ($pos == 1 && $digit == 2) {
                    $result .= 'ยี่' . $thai_unit[$pos];
                } elseif ($pos == 0 && $digit == 1 && $int_length > 1) {
                    $result .= 'เอ็ด';
                } else {
                    $result .= $thai_number[$digit] . $thai_unit[$pos];
                }
            }
        }
    }
    
    $result .= 'บาท';
    
    if ($dec_part != '00') {
        $result .= number_to_thai_text_helper($dec_part) . 'สตางค์';
    } else {
        $result .= 'ถ้วน';
    }
    
    return $result;
}

function number_to_thai_text_helper($number) {
    $thai_number = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
    $thai_unit = ['', 'สิบ'];
    
    $result = '';
    $length = strlen($number);
    
    for ($i = 0; $i < $length; $i++) {
        $digit = substr($number, $i, 1);
        $pos = $length - $i - 1;
        
        if ($digit != 0) {
            if ($pos == 1 && $digit == 1) {
                $result .= $thai_unit[$pos];
            } elseif ($pos == 1 && $digit == 2) {
                $result .= 'ยี่' . $thai_unit[$pos];
            } elseif ($pos == 0 && $digit == 1 && $length > 1) {
                $result .= 'เอ็ด';
            } else {
                $result .= $thai_number[$digit] . $thai_unit[$pos];
            }
        }
    }
    
    return $result;
}

// ฟังก์ชันสำหรับการสร้าง QR Code
function generate_qr_code($data, $size = 200) {
    // ใช้ Google Charts API
    $url = 'https://chart.googleapis.com/chart?';
    $url .= 'chs=' . $size . 'x' . $size;
    $url .= '&cht=qr';
    $url .= '&chl=' . urlencode($data);
    $url .= '&choe=UTF-8';
    
    return $url;
}

// ฟังก์ชันสำหรับการสร้าง PromptPay QR
function generate_promptpay_qr($mobile_number, $amount = null) {
    // ลบ 0 หน้าเบอร์และเติม +66
    if (substr($mobile_number, 0, 1) === '0') {
        $mobile_number = '+66' . substr($mobile_number, 1);
    }
    
    $data = $mobile_number;
    if ($amount && $amount > 0) {
        $data .= '|' . number_format($amount, 2, '.', '');
    }
    
    return generate_qr_code($data);
}

// ฟังก์ชันสำหรับการส่งอีเมล
function send_email($to, $subject, $message, $from_name = '', $from_email = '') {
    global $smtp_config;
    
    if (empty($from_email)) {
        $from_email = $smtp_config['username'] ?? 'noreply@smartorder.com';
    }
    
    if (empty($from_name)) {
        $from_name = 'Smart Order System';
    }
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $from_email,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

// ฟังก์ชันสำหรับการส่ง SMS (จำลอง)
function send_sms($mobile, $message) {
    // นี่เป็นเพียงตัวอย่าง - ต้องใช้ SMS Gateway จริง
    // เช่น Twilio, SMS.to, หรือ True Business API
    
    // Log SMS for testing
    error_log("SMS to $mobile: $message");
    
    // ส่งกลับ true เพื่อจำลองการส่งสำเร็จ
    return true;
}

// ฟังก์ชันสำหรับการสร้างรหัสยืนยัน
function generate_verification_code($length = 6) {
    return str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// ฟังก์ชันสำหรับการสร้าง UUID
function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// ฟังก์ชันสำหรับการคำนวณระยะทาง (Haversine formula)
function calculate_distance($lat1, $lon1, $lat2, $lon2, $unit = 'km') {
    $earth_radius = ($unit === 'km') ? 6371 : 3959; // km or miles
    
    $lat_delta = deg2rad($lat2 - $lat1);
    $lon_delta = deg2rad($lon2 - $lon1);
    
    $a = sin($lat_delta / 2) * sin($lat_delta / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lon_delta / 2) * sin($lon_delta / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earth_radius * $c;
}

// ฟังก์ชันสำหรับการแปลงเวลา
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'เมื่อสักครู่';
    if ($time < 3600) return floor($time / 60) . ' นาทีที่แล้ว';
    if ($time < 86400) return floor($time / 3600) . ' ชั่วโมงที่แล้ว';
    if ($time < 2629746) return floor($time / 86400) . ' วันที่แล้ว';
    
    return thai_date($datetime, 'short');
}

// ฟังก์ชันสำหรับการสร้างการแจ้งเตือน
function create_notification($user_id, $title, $message, $type = 'info', $action_url = null) {
    global $connection;
    
    $user_id = intval($user_id);
    $title = mysqli_real_escape_string($connection, $title);
    $message = mysqli_real_escape_string($connection, $message);
    $type = mysqli_real_escape_string($connection, $type);
    $action_url = $action_url ? mysqli_real_escape_string($connection, $action_url) : null;
    
    $query = "
        INSERT INTO notifications (user_id, title, message, type, action_url, created_at)
        VALUES ($user_id, '$title', '$message', '$type', " . ($action_url ? "'$action_url'" : 'NULL') . ", NOW())
    ";
    
    return mysqli_query($connection, $query);
}

// ฟังก์ชันสำหรับการดึงการแจ้งเตือน
function get_notifications($user_id, $unread_only = false, $limit = 10) {
    global $connection;
    
    $user_id = intval($user_id);
    $where_clause = "user_id = $user_id";
    
    if ($unread_only) {
        $where_clause .= " AND is_read = 0";
    }
    
    $query = "
        SELECT * FROM notifications 
        WHERE $where_clause 
        ORDER BY created_at DESC 
        LIMIT $limit
    ";
    
    $result = mysqli_query($connection, $query);
    $notifications = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

// ฟังก์ชันสำหรับการทำเครื่องหมายการแจ้งเตือนเป็นอ่านแล้ว
function mark_notification_read($notification_id) {
    global $connection;
    
    $notification_id = intval($notification_id);
    $query = "UPDATE notifications SET is_read = 1 WHERE id = $notification_id";
    
    return mysqli_query($connection, $query);
}

// ฟังก์ชันสำหรับการตรวจสอบว่าเป็นมือถือหรือไม่
function is_mobile() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

// ฟังก์ชันสำหรับการสร้างข้อความแจ้งเตือน
function flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

// ฟังก์ชันสำหรับการแสดงข้อความแจ้งเตือน
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $alert_class = 'alert-info';
        switch ($flash['type']) {
            case 'success':
                $alert_class = 'alert-success';
                break;
            case 'error':
            case 'danger':
                $alert_class = 'alert-danger';
                break;
            case 'warning':
                $alert_class = 'alert-warning';
                break;
        }
        
        return '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">' .
               htmlspecialchars($flash['message']) .
               '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' .
               '</div>';
    }
    
    return '';
}

// ฟังก์ชันสำหรับการสร้างลิงก์แบบมีหน้า
function create_pagination($current_page, $total_pages, $base_url, $query_params = []) {
    $pagination = '';
    $range = 2; // จำนวนหน้าที่แสดงรอบๆ หน้าปัจจุบัน
    
    // สร้าง query string
    $query_string = '';
    if (!empty($query_params)) {
        $query_string = '?' . http_build_query($query_params);
    }
    
    $pagination .= '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . $prev_page . $query_string . '">&laquo; ก่อนหน้า</a></li>';
    }
    
    // Page numbers
    for ($i = max(1, $current_page - $range); $i <= min($total_pages, $current_page + $range); $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base_url . $i . $query_string . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . $next_page . $query_string . '">ถัดไป &raquo;</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    
    return $pagination;
}

// ฟังก์ชันสำหรับการบีบอัดรูปภาพ
function compress_image($source, $destination, $quality = 75) {
    $info = getimagesize($source);
    
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        return false;
    }
    
    return imagejpeg($image, $destination, $quality);
}

// ฟังก์ชันสำหรับการตรวจสอบ URL
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// ฟังก์ชันสำหรับการ escape HTML
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// ฟังก์ชันสำหรับการตัดข้อความ
function truncate($string, $length = 100, $suffix = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    
    return substr($string, 0, $length) . $suffix;
}

// ฟังก์ชันสำหรับการแปลง array เป็น JSON
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// ฟังก์ชันสำหรับการ redirect
function redirect($url, $status_code = 302) {
    header("Location: $url", true, $status_code);
    exit();
}

// ฟังก์ชันสำหรับการตรวจสอบ AJAX request
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

?>