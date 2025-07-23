<?php
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
 * ตรวจสอบสิทธิ์การเข้าถึงหรือบทบาท
 * ตัวอย่าง: hasPermission('view_reports') หรือ hasPermission(['admin', 'manager'])
 */
function hasPermission($permissionOrRoles) {
    if (!isLoggedIn()) return false;
    $userRole = $_SESSION['user_role'] ?? '';
    // กรณีตรวจสอบบทบาทโดยตรง
    if (is_array($permissionOrRoles)) {
        return in_array($userRole, $permissionOrRoles);
    }
    // กรณีตรวจสอบ permission แบบ string
    if (is_string($permissionOrRoles)) {
        // ตัวอย่าง mapping permission -> roles
        $permissionMap = [
            'view_reports' => ['admin', 'manager'],
            // เพิ่ม permission อื่น ๆ ที่นี่
        ];
        if (isset($permissionMap[$permissionOrRoles])) {
            return in_array($userRole, $permissionMap[$permissionOrRoles]);
        }
        // ถ้าไม่พบ permission ให้ตรวจสอบตรงกับ role
        return $userRole === $permissionOrRoles;
    }
    return false;
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
    
    return true;
}

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
?>