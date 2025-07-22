<?php
// config/session.php - Session Management

// ป้องกันการเรียกไฟล์โดยตรง
if (!defined('CONFIG_LOADED')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Class สำหรับจัดการ Session
 */
class SessionManager {
    private static $instance = null;
    private $sessionStarted = false;
    
    private function __construct() {
        $this->configureSession();
        $this->startSession();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function configureSession() {
        // ตั้งค่า session security
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Lax');
        
        // ตั้งค่า session lifetime
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        ini_set('session.cookie_lifetime', SESSION_LIFETIME);
        
        // ตั้งค่า session name
        session_name('SMART_ORDER_SESSION');
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            $this->sessionStarted = true;
            
            // ตรวจสอบ session hijacking
            $this->validateSession();
            
            // อัปเดต last activity
            $_SESSION['last_activity'] = time();
        }
    }
    
    private function validateSession() {
        // ตรวจสอบ User Agent
        if (isset($_SESSION['user_agent'])) {
            if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                $this->destroy();
                return;
            }
        } else {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        
        // ตรวจสอบ IP Address (อาจจะหลวมเกินไปสำหรับ mobile users)
        if (isset($_SESSION['ip_address'])) {
            if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
                // Log suspicious activity
                writeLog('WARNING', 'Session IP mismatch', [
                    'old_ip' => $_SESSION['ip_address'],
                    'new_ip' => $_SERVER['REMOTE_ADDR']
                ]);
            }
        } else {
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        }
        
        // ตรวจสอบ session timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
                $this->destroy();
                return;
            }
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public function remove($key) {
        unset($_SESSION[$key]);
    }
    
    public function destroy() {
        if ($this->sessionStarted) {
            $_SESSION = [];
            
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 42000, '/');
            }
            
            session_destroy();
            $this->sessionStarted = false;
        }
    }
    
    public function regenerate() {
        if ($this->sessionStarted) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    
    public function isLoggedIn() {
        return $this->has('user_id') || $this->has('admin_id');
    }
    
    public function getUserId() {
        return $this->get('user_id') ?: $this->get('admin_id');
    }
    
    public function getUserRole() {
        return $this->get('user_role', 'guest');
    }
    
    public function getUserName() {
        return $this->get('user_name') ?: $this->get('admin_name');
    }
    
    public function login($userId, $userData) {
        $this->regenerate();
        
        // เซ็ต user data
        $this->set('user_id', $userId);
        $this->set('user_name', $userData['name'] ?? $userData['username']);
        $this->set('user_role', $userData['role'] ?? 'user');
        $this->set('login_time', time());
        
        // Log login activity
        writeLog('INFO', 'User logged in', [
            'user_id' => $userId,
            'username' => $userData['username'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    public function loginAdmin($adminId, $adminData) {
        $this->regenerate();
        
        // เซ็ต admin data
        $this->set('admin_id', $adminId);
        $this->set('admin_name', $adminData['name'] ?? $adminData['username']);
        $this->set('user_role', 'admin');
        $this->set('login_time', time());
        
        // Log admin login
        writeLog('INFO', 'Admin logged in', [
            'admin_id' => $adminId,
            'username' => $adminData['username'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    public function logout() {
        $userId = $this->getUserId();
        $userRole = $this->getUserRole();
        
        // Log logout
        writeLog('INFO', 'User logged out', [
            'user_id' => $userId,
            'role' => $userRole,
            'session_duration' => time() - $this->get('login_time', time())
        ]);
        
        $this->destroy();
    }
    
    public function requireLogin($redirectUrl = null) {
        if (!$this->isLoggedIn()) {
            if (!$redirectUrl) {
                $redirectUrl = ADMIN_URL . 'login.php';
            }
            
            // Save intended URL
            $this->set('intended_url', $_SERVER['REQUEST_URI']);
            
            redirect($redirectUrl);
        }
    }
    
    public function requireRole($role, $redirectUrl = null) {
        $this->requireLogin($redirectUrl);
        
        if ($this->getUserRole() !== $role) {
            if (!$redirectUrl) {
                $redirectUrl = SITE_URL . 'unauthorized.php';
            }
            redirect($redirectUrl);
        }
    }
    
    public function requireAdmin($redirectUrl = null) {
        $this->requireRole('admin', $redirectUrl);
    }
    
    public function getIntendedUrl($default = null) {
        $url = $this->get('intended_url', $default);
        $this->remove('intended_url');
        return $url;
    }
    
    public function setFlash($type, $message) {
        $flash = $this->get('flash_messages', []);
        $flash[] = [
            'type' => $type,
            'message' => $message,
            'timestamp' => time()
        ];
        $this->set('flash_messages', $flash);
    }
    
    public function getFlash() {
        $messages = $this->get('flash_messages', []);
        $this->remove('flash_messages');
        
        // ลบข้อความที่เก่าเกินไป (เก่ากว่า 5 นาที)
        return array_filter($messages, function($msg) {
            return (time() - $msg['timestamp']) < 300;
        });
    }
    
    public function hasFlash() {
        $messages = $this->get('flash_messages', []);
        return !empty($messages);
    }
    
    public function getSessionInfo() {
        return [
            'session_id' => session_id(),
            'started' => $this->sessionStarted,
            'user_id' => $this->getUserId(),
            'user_name' => $this->getUserName(),
            'user_role' => $this->getUserRole(),
            'logged_in' => $this->isLoggedIn(),
            'login_time' => $this->get('login_time'),
            'last_activity' => $this->get('last_activity'),
            'ip_address' => $this->get('ip_address'),
            'user_agent' => $this->get('user_agent')
        ];
    }
    
    public function cleanup() {
        // ลบ session ที่หมดอายุ (ใช้ใน cron job)
        if (random_int(1, 100) <= 5) { // 5% chance
            session_gc();
        }
    }
}

// =============== Helper Functions ===============

/**
 * Initialize session manager
 */
function initSession() {
    return SessionManager::getInstance();
}

/**
 * ตรวจสอบการเข้าสู่ระบบ
 */
function isLoggedIn() {
    $session = SessionManager::getInstance();
    return $session->isLoggedIn();
}

/**
 * บังคับให้เข้าสู่ระบบ
 */
function requireLogin($redirectUrl = null) {
    $session = SessionManager::getInstance();
    $session->requireLogin($redirectUrl);
}

/**
 * บังคับให้เป็น admin
 */
function requireAdmin($redirectUrl = null) {
    $session = SessionManager::getInstance();
    $session->requireAdmin($redirectUrl);
}

/**
 * ตรวจสอบสิทธิ์
 */
function hasRole($role) {
    $session = SessionManager::getInstance();
    return $session->getUserRole() === $role;
}

/**
 * ดึงข้อมูลผู้ใช้
 */
function getCurrentUser() {
    $session = SessionManager::getInstance();
    return [
        'id' => $session->getUserId(),
        'name' => $session->getUserName(),
        'role' => $session->getUserRole()
    ];
}

/**
 * เซ็ต flash message
 */
function setFlashMessage($type, $message) {
    $session = SessionManager::getInstance();
    $session->setFlash($type, $message);
}

/**
 * ดึง flash messages
 */
function getFlashMessages() {
    $session = SessionManager::getInstance();
    return $session->getFlash();
}

/**
 * ออกจากระบบ
 */
function logout($redirectUrl = null) {
    $session = SessionManager::getInstance();
    $session->logout();
    
    if ($redirectUrl) {
        redirect($redirectUrl);
    }
}

// =============== CSRF Protection ===============

/**
 * สร้าง CSRF token
 */
function generateCSRFToken() {
    $session = SessionManager::getInstance();
    
    if (!$session->has('csrf_token')) {
        $session->set('csrf_token', bin2hex(random_bytes(32)));
    }
    
    return $session->get('csrf_token');
}

/**
 * ตรวจสอบ CSRF token
 */
function validateCSRFToken($token) {
    $session = SessionManager::getInstance();
    $sessionToken = $session->get('csrf_token');
    
    return $sessionToken && hash_equals($sessionToken, $token);
}

/**
 * สร้าง hidden input สำหรับ CSRF token
 */
function csrfTokenInput() {
    $token = generateCSRFToken();
    return "<input type='hidden' name='csrf_token' value='{$token}'>";
}

/**
 * ตรวจสอบ CSRF token จาก POST
 */
function validateCSRFFromPost() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCSRFToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed');
        }
    }
}

// =============== Rate Limiting ===============

/**
 * ตรวจสอบ rate limiting
 */
function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
    $session = SessionManager::getInstance();
    $key = "rate_limit_{$action}";
    $attempts = $session->get($key, []);
    $now = time();
    
    // ลบ attempts ที่หมดอายุ
    $attempts = array_filter($attempts, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    if (count($attempts) >= $maxAttempts) {
        return false;
    }
    
    // เพิ่ม attempt ใหม่
    $attempts[] = $now;
    $session->set($key, $attempts);
    
    return true;
}

/**
 * รีเซ็ต rate limit
 */
function resetRateLimit($action) {
    $session = SessionManager::getInstance();
    $session->remove("rate_limit_{$action}");
}

// Initialize Session Manager
$sessionManager = initSession();

// Auto-cleanup (5% chance)
$sessionManager->cleanup();
?>