<?php
<<<<<<< HEAD
/**
 * Authentication Functions
 * Smart Order Management System
 * Fixed: เพิ่มฟังก์ชัน requireLogin() และ authentication functions
 */

// เริ่ม session หากยังไม่ได้เริ่ม
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * ได้รับข้อมูลผู้ใช้ปัจจุบัน
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'staff',
        'name' => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? ''
    ];
}

/**
 * ตรวจสอบสิทธิ์การเข้าถึง
 */
function checkAuth($requiredRoles = []) {
    if (!isLoggedIn()) {
        redirectToLogin();
        exit;
    }
    
    if (!empty($requiredRoles)) {
        $userRole = $_SESSION['user_role'] ?? 'guest';
        
        if (!in_array($userRole, $requiredRoles)) {
            http_response_code(403);
            die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
        }
    }
=======
// includes/auth.php - ระบบตรวจสอบสิทธิ์
if (!defined('INCLUDED_AUTH')) {
    define('INCLUDED_AUTH', true);
}

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

// ตรวจสอบสิทธิ์การเข้าถึง
function hasPermission($roles = []) {
    if (!isLoggedIn()) {
        return false;
    }
    
    if (empty($roles)) {
        return true; // ถ้าไม่กำหนด role ก็ให้ผ่าน
    }
    
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    $userRole = $_SESSION['user_role'] ?? '';
    return in_array($userRole, $roles);
}

// บังคับให้ล็อกอิน
function requireLogin($redirect_url = '/admin/login.php') {
    if (!isLoggedIn()) {
        $current_url = $_SERVER['REQUEST_URI'];
        $login_url = $redirect_url . '?redirect=' . urlencode($current_url);
        
        // ถ้าเป็น AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ', 'redirect' => $login_url]);
            exit();
        }
        
        header("Location: $login_url");
        exit();
    }
}

// ตรวจสอบสิทธิ์และบังคับให้มีสิทธิ์ที่กำหนด
function checkPermission($roles = [], $redirect_url = '/admin/login.php') {
    requireLogin($redirect_url);
    
    if (!hasPermission($roles)) {
        // ถ้าเป็น AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
            exit();
        }
        
        // Redirect ไปหน้า access denied หรือกลับไปหน้าแรก
        header("Location: /admin/access-denied.php");
        exit();
    }
}

// เข้าสู่ระบบ
function login($user_id, $user_data) {
    global $connection;
    
    // เก็บข้อมูลใน session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_role'] = $user_data['role'];
    $_SESSION['user_name'] = $user_data['full_name'];
    $_SESSION['username'] = $user_data['username'];
    $_SESSION['login_time'] = time();
    
    // อัปเดต last_login ในฐานข้อมูล
    $update_query = "UPDATE users SET last_login = NOW() WHERE id = " . intval($user_id);
    mysqli_query($connection, $update_query);
    
    // สร้าง CSRF token
    $_SESSION['csrf_token'] = generateCSRFToken();
>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
    
    return true;
}

<<<<<<< HEAD
/**
 * ฟังก์ชัน requireLogin() ที่หายไป
 * Fixed: เพิ่มฟังก์ชันนี้เพื่อแก้ปัญหา undefined function
 */
function requireLogin($requiredRoles = []) {
    return checkAuth($requiredRoles);
}

/**
 * เข้าสู่ระบบ
 */
function login($username, $password) {
    // รวม database configuration
    require_once __DIR__ . '/../config/database.php';
    
    try {
        // ค้นหาผู้ใช้จากฐานข้อมูล
        $query = "SELECT * FROM users WHERE username = ? AND active = 1";
        $user = db_fetch_one($query, [$username]);
        
        if ($user && password_verify($password, $user['password'])) {
            // เก็บข้อมูลใน session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['login_time'] = time();
            
            // อัปเดต last login
            $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = ?";
            db_execute($updateQuery, [$user['id']]);
            
            return true;
        }
        
    } catch (Exception $e) {
        error_log("Login Error: " . $e->getMessage());
    }
    
    return false;
}

/**
 * ออกจากระบบ
 */
function logout() {
    // ลบข้อมูล session ทั้งหมด
    $_SESSION = array();
    
    // ลบ session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // ทำลาย session
    session_destroy();
}

/**
 * เปลี่ยนเส้นทางไปหน้า login
 */
function redirectToLogin() {
    $loginUrl = getLoginUrl();
    
    // ถ้าเป็น AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        http_response_code(401);
        echo json_encode(['error' => 'Session expired', 'redirect' => $loginUrl]);
        exit;
    }
    
    // เปลี่ยนเส้นทางปกติ
    header("Location: $loginUrl");
    exit;
}

/**
 * ได้รับ URL ของหน้า login
 */
function getLoginUrl() {
    $currentPath = $_SERVER['REQUEST_URI'];
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    
    // ตรวจสอบว่าอยู่ในโฟลเดอร์ไหน
    if (strpos($currentPath, '/admin/') !== false) {
        $loginUrl = '/pos/admin/login.php';
    } else if (strpos($currentPath, '/pos/') !== false) {
        $loginUrl = '/pos/pos/login.php';
    } else {
        $loginUrl = '/pos/login.php';
    }
    
    return $loginUrl;
}

/**
 * ตรวจสอบสิทธิ์ admin
 */
function requireAdmin() {
    return checkAuth(['admin']);
}

/**
 * ตรวจสอบสิทธิ์ staff หรือ admin
 */
function requireStaff() {
    return checkAuth(['admin', 'staff']);
}

/**
 * ตรวจสอบว่าเป็น admin หรือไม่
 */
function isAdmin() {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

/**
 * ตรวจสอบว่าเป็น staff หรือไม่
 */
function isStaff() {
    $role = $_SESSION['user_role'] ?? '';
    return isLoggedIn() && in_array($role, ['admin', 'staff']);
}

/**
 * สร้าง CSRF Token
 */
=======
// ออกจากระบบ
function logout() {
    // ล้างข้อมูล session
    session_unset();
    session_destroy();
    
    // ลบ cookies (ถ้ามี)
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // เริ่ม session ใหม่
    session_start();
    session_regenerate_id(true);
    
    return true;
}

// ตรวจสอบรหัสผ่าน
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// สร้างรหัสผ่านที่เข้ารหัสแล้ว
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// สร้าง CSRF Token
>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

<<<<<<< HEAD
/**
 * ตรวจสอบ CSRF Token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * สร้าง HTML สำหรับ CSRF token field
 */
function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * ตรวจสอบ session timeout
 */
function checkSessionTimeout() {
    $timeout = 3600; // 1 ชั่วโมง
    
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
        logout();
        redirectToLogin();
        exit;
    }
    
    // อัปเดตเวลา
    $_SESSION['login_time'] = time();
}

/**
 * ป้องกัน brute force attack
 */
function checkLoginAttempts($username) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    $attempts = $_SESSION['login_attempts'][$username] ?? 0;
    $max_attempts = 5;
    $lockout_time = 900; // 15 นาที
    
    if ($attempts >= $max_attempts) {
        $last_attempt = $_SESSION['last_login_attempt'][$username] ?? 0;
        if (time() - $last_attempt < $lockout_time) {
            return false; // ถูก lock
        } else {
            // หมดเวลา lock แล้ว
            unset($_SESSION['login_attempts'][$username]);
            unset($_SESSION['last_login_attempt'][$username]);
        }
    }
    
    return true;
}

/**
 * บันทึกความพยายามเข้าสู่ระบบ
 */
function recordLoginAttempt($username, $success = false) {
    if ($success) {
        // ล็อกอินสำเร็จ - ล้างข้อมูล attempts
        unset($_SESSION['login_attempts'][$username]);
        unset($_SESSION['last_login_attempt'][$username]);
    } else {
        // ล็อกอินไม่สำเร็จ - เพิ่มจำนวน attempts
        $_SESSION['login_attempts'][$username] = ($_SESSION['login_attempts'][$username] ?? 0) + 1;
        $_SESSION['last_login_attempt'][$username] = time();
    }
}

/**
 * สร้างผู้ใช้ admin เริ่มต้น (หากยังไม่มี)
 */
function createDefaultAdmin() {
    require_once __DIR__ . '/../config/database.php';
    
    try {
        // ตรวจสอบว่ามี admin หรือไม่
        $query = "SELECT COUNT(*) as count FROM users WHERE role = 'admin'";
        $result = db_fetch_one($query);
        
        if ($result['count'] == 0) {
            // สร้าง admin เริ่มต้น
            $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
            
            $insertQuery = "INSERT INTO users (username, password, role, full_name, email, active, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            db_execute($insertQuery, [
                'admin',
                $hashedPassword,
                'admin',
                'System Administrator',
                'admin@smartorder.local',
                1
            ]);
            
            error_log("Default admin user created: username=admin, password=admin123");
        }
        
    } catch (Exception $e) {
        error_log("Create Default Admin Error: " . $e->getMessage());
    }
}

// เรียกใช้สร้าง admin เริ่มต้น
createDefaultAdmin();

/**
 * ตรวจสอบการอนุญาต API
 */
function checkAPIAuth() {
    $headers = apache_request_headers();
    $token = $headers['Authorization'] ?? '';
    
    if (empty($token)) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // ตรวจสอบ token (implementation ตามต้องการ)
    return true;
}
=======
// ตรวจสอบ CSRF Token
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ป้องกัน Session Fixation
function regenerateSession() {
    session_regenerate_id(true);
}

// ตรวจสอบ Session timeout
function checkSessionTimeout($timeout_minutes = 480) { // 8 ชั่วโมงเริ่มต้น
    if (isset($_SESSION['login_time'])) {
        $session_duration = time() - $_SESSION['login_time'];
        if ($session_duration > ($timeout_minutes * 60)) {
            logout();
            return false;
        }
    }
    return true;
}

// ตรวจสอบ IP Address (ป้องกัน Session Hijacking)
function validateSessionIP() {
    $current_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (isset($_SESSION['session_ip'])) {
        if ($_SESSION['session_ip'] !== $current_ip) {
            logout();
            return false;
        }
    } else {
        $_SESSION['session_ip'] = $current_ip;
    }
    
    return true;
}

// ตรวจสอบ User Agent (ป้องกัน Session Hijacking)
function validateSessionUserAgent() {
    $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (isset($_SESSION['session_ua'])) {
        if ($_SESSION['session_ua'] !== $current_ua) {
            logout();
            return false;
        }
    } else {
        $_SESSION['session_ua'] = $current_ua;
    }
    
    return true;
}

// ฟังก์ชันตรวจสอบความปลอดภัยครบถ้วน
function validateSession() {
    if (!checkSessionTimeout()) {
        return false;
    }
    
    // คอมเมนต์ออกถ้าไม่ต้องการตรวจสอบ IP (สำหรับ mobile app)
    // if (!validateSessionIP()) {
    //     return false;
    // }
    
    // คอมเมนต์ออกถ้าไม่ต้องการตรวจสอบ User Agent
    // if (!validateSessionUserAgent()) {
    //     return false;
    // }
    
    return true;
}

// ดึงข้อมูลผู้ใช้ปัจจุบัน
function getCurrentUser() {
    global $connection;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $user_id = intval($_SESSION['user_id']);
    $query = "SELECT * FROM users WHERE id = $user_id AND is_active = 1 LIMIT 1";
    $result = mysqli_query($connection, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// ดึงสิทธิ์ของผู้ใช้ปัจจุบัน
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

// ตรวจสอบว่าเป็น Admin หรือไม่
function isAdmin() {
    return getCurrentUserRole() === 'admin';
}

// ตรวจสอบว่าเป็น Manager หรือไม่
function isManager() {
    $role = getCurrentUserRole();
    return in_array($role, ['admin', 'manager']);
}

// ตรวจสอบว่าเป็นพนักงาน POS หรือไม่
function isPOSStaff() {
    $role = getCurrentUserRole();
    return in_array($role, ['admin', 'manager', 'pos_staff']);
}

// ตรวจสอบว่าเป็นพนักงานครัวหรือไม่
function isKitchenStaff() {
    $role = getCurrentUserRole();
    return in_array($role, ['admin', 'manager', 'kitchen_staff']);
}

// ล็อกกิจกรรมการเข้าสู่ระบบ
function logUserActivity($action, $details = '') {
    global $connection;
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_id = intval($_SESSION['user_id']);
    $action = mysqli_real_escape_string($connection, $action);
    $details = mysqli_real_escape_string($connection, $details);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $query = "
        INSERT INTO user_activity_logs 
        (user_id, action, details, ip_address, user_agent, created_at) 
        VALUES ($user_id, '$action', '$details', '$ip_address', '$user_agent', NOW())
    ";
    
    return mysqli_query($connection, $query);
}

// ตรวจสอบการเข้าสู่ระบบหลายครั้งที่ผิด
function checkLoginAttempts($username, $max_attempts = 5, $lockout_time = 900) { // 15 นาที
    global $connection;
    
    $username = mysqli_real_escape_string($connection, $username);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $cutoff_time = date('Y-m-d H:i:s', time() - $lockout_time);
    
    $query = "
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE (username = '$username' OR ip_address = '$ip_address') 
        AND success = 0 
        AND attempted_at > '$cutoff_time'
    ";
    
    $result = mysqli_query($connection, $query);
    $data = mysqli_fetch_assoc($result);
    
    return ($data['attempts'] ?? 0) < $max_attempts;
}

// บันทึกการพยายามเข้าสู่ระบบ
function recordLoginAttempt($username, $success = false) {
    global $connection;
    
    $username = mysqli_real_escape_string($connection, $username);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $success_int = $success ? 1 : 0;
    
    $query = "
        INSERT INTO login_attempts 
        (username, ip_address, user_agent, success, attempted_at) 
        VALUES ('$username', '$ip_address', '$user_agent', $success_int, NOW())
    ";
    
    return mysqli_query($connection, $query);
}

// ล้างการพยายามเข้าสู่ระบบที่ผิด
function clearLoginAttempts($username) {
    global $connection;
    
    $username = mysqli_real_escape_string($connection, $username);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    $query = "
        DELETE FROM login_attempts 
        WHERE (username = '$username' OR ip_address = '$ip_address') 
        AND success = 0
    ";
    
    return mysqli_query($connection, $query);
}

// ฟังก์ชันเข้ารหัสข้อมูลสำคัญ
function encryptSensitiveData($data, $key = null) {
    if ($key === null) {
        $key = $_SESSION['csrf_token'] ?? 'default_key';
    }
    
    $method = 'AES-256-CBC';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
    
    return base64_encode($iv . $encrypted);
}

// ฟังก์ชันถอดรหัสข้อมูลสำคัญ
function decryptSensitiveData($data, $key = null) {
    if ($key === null) {
        $key = $_SESSION['csrf_token'] ?? 'default_key';
    }
    
    $data = base64_decode($data);
    $method = 'AES-256-CBC';
    $iv_length = openssl_cipher_iv_length($method);
    $iv = substr($data, 0, $iv_length);
    $encrypted = substr($data, $iv_length);
    
    return openssl_decrypt($encrypted, $method, $key, 0, $iv);
}

// ฟังก์ชันสำหรับ Remember Me
function setRememberMeToken($user_id) {
    global $connection;
    
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 วัน
    
    // ลบ token เก่า
    $delete_query = "DELETE FROM remember_tokens WHERE user_id = " . intval($user_id);
    mysqli_query($connection, $delete_query);
    
    // สร้าง token ใหม่
    $insert_query = "
        INSERT INTO remember_tokens (user_id, token, expires_at) 
        VALUES (" . intval($user_id) . ", '$token', '$expires')
    ";
    
    if (mysqli_query($connection, $insert_query)) {
        // ตั้ง cookie
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
        return true;
    }
    
    return false;
}

// ตรวจสอบ Remember Me Token
function checkRememberMeToken() {
    global $connection;
    
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    $token = mysqli_real_escape_string($connection, $_COOKIE['remember_token']);
    $query = "
        SELECT rt.*, u.* 
        FROM remember_tokens rt
        JOIN users u ON rt.user_id = u.id
        WHERE rt.token = '$token' 
        AND rt.expires_at > NOW() 
        AND u.is_active = 1
        LIMIT 1
    ";
    
    $result = mysqli_query($connection, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        
        // ล็อกอินอัตโนมัติ
        login($data['user_id'], $data);
        
        // สร้าง token ใหม่เพื่อความปลอดภัย
        setRememberMeToken($data['user_id']);
        
        return true;
    } else {
        // ลบ cookie ที่ไม่ถูกต้อง
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    return false;
}

// ลบ Remember Me Token
function removeRememberMeToken() {
    global $connection;
    
    if (isset($_COOKIE['remember_token']) && isLoggedIn()) {
        $token = mysqli_real_escape_string($connection, $_COOKIE['remember_token']);
        $user_id = intval($_SESSION['user_id']);
        
        // ลบจากฐานข้อมูล
        $delete_query = "
            DELETE FROM remember_tokens 
            WHERE token = '$token' OR user_id = $user_id
        ";
        mysqli_query($connection, $delete_query);
    }
    
    // ลบ cookie
    setcookie('remember_token', '', time() - 3600, '/');
}

// ฟังก์ชันสำหรับ Two-Factor Authentication (ถ้าต้องการใช้)
function generate2FASecret() {
    return bin2hex(random_bytes(16));
}

function verify2FACode($secret, $code) {
    // ใช้ library เช่น Google Authenticator
    // นี่เป็นเพียงตัวอย่าง
    return strlen($code) === 6 && is_numeric($code);
}

// เริ่มต้น session security
function initSessionSecurity() {
    // ตั้งค่า session security
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    
    // เริ่ม session หากยังไม่ได้เริ่ม
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // ตรวจสอบ Remember Me Token
    if (!isLoggedIn()) {
        checkRememberMeToken();
    }
    
    // ตรวจสอบ session validity
    if (isLoggedIn()) {
        validateSession();
    }
}

// เรียกใช้เมื่อโหลดไฟล์
initSessionSecurity();

>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
?>