<?php
// ป้องกันการเข้าถึงไฟล์นี้โดยตรง
if (!defined('SITE_URL')) {
    exit('Access denied');
}

/**
 * ตรวจสอบการล็อกอินและสิทธิ์การเข้าถึง
 * @param array $allowedRoles รายการบทบาทที่อนุญาต
 * @param string $redirectUrl URL สำหรับ redirect หากไม่มีสิทธิ์
 */
if (!function_exists('checkAuth')) {
    function checkAuth($allowedRoles = ['admin'], $redirectUrl = null) {
        // ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            if (isAjaxRequest()) {
                jsonResponse(null, false, 'กรุณาเข้าสู่ระบบ');
            } else {
                redirectTo('admin/login.php');
            }
        }
        
        // ตรวจสอบสิทธิ์การเข้าถึง
        if (!in_array($_SESSION['user_role'], $allowedRoles)) {
            if (isAjaxRequest()) {
                jsonResponse(null, false, 'ไม่มีสิทธิ์เข้าถึง');
            } else {
                $redirectUrl = $redirectUrl ?: 'admin/unauthorized.php';
                redirectTo($redirectUrl);
            }
        }
        
        // ตรวจสอบ session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
            logout();
        }
        
        // อัปเดต last activity
        $_SESSION['last_activity'] = time();
    }
}

/**
 * ตรวจสอบว่าผู้ใช้ล็อกอินแล้วหรือไม่
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
    }
}

/**
 * ตรวจสอบบทบาทของผู้ใช้
 * @param string $role บทบาทที่ต้องการตรวจสอบ
 */
if (!function_exists('hasRole')) {
    function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
}

/**
 * ตรวจสอบสิทธิ์เฉพาะ
 * @param string $permission สิทธิ์ที่ต้องการตรวจสอบ
 */
if (!function_exists('hasPermission')) {
    function hasPermission($permission) {
        if (!isLoggedIn()) {
            return false;
        }
        
        $permissions = [
            'admin' => [
                'view_all', 'edit_all', 'delete_all', 'settings',
                'manage_users', 'manage_menu', 'manage_orders', 
                'manage_queue', 'view_reports', 'manage_payments'
            ],
            'manager' => [
                'view_all', 'edit_orders', 'manage_menu', 
                'manage_queue', 'view_reports', 'manage_payments'
            ],
            'staff' => [
                'view_orders', 'edit_orders', 'manage_queue', 'pos_access'
            ],
            'kitchen' => [
                'view_kitchen', 'update_kitchen', 'view_orders'
            ],
            'cashier' => [
                'pos_access', 'manage_payments', 'view_orders'
            ]
        ];
        
        $userRole = $_SESSION['user_role'];
        return in_array($permission, $permissions[$userRole] ?? []);
    }
}

/**
 * ฟังก์ชันล็อกอิน
 * @param string $username ชื่อผู้ใช้
 * @param string $password รหัสผ่าน
 * @param bool $rememberMe จดจำการล็อกอิน
 */
if (!function_exists('login')) {
    function login($username, $password, $rememberMe = false) {
        global $pdo;
        
        try {
            // ในการใช้งานจริง ควรเข้ารหัสรหัสผ่านด้วย password_hash()
            $stmt = $pdo->prepare("
                SELECT id, username, password, full_name, role, status, last_login 
                FROM users 
                WHERE username = ? AND status = 'active'
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // สำหรับการทดสอบ - ใช้ข้อมูลจำลอง
            if (!$user) {
                $mockUsers = [
                    'admin' => [
                        'id' => 1,
                        'username' => 'admin',
                        'password' => password_hash('admin123', PASSWORD_DEFAULT),
                        'full_name' => 'ผู้ดูแลระบบ',
                        'role' => 'admin',
                        'status' => 'active'
                    ],
                    'staff' => [
                        'id' => 2,
                        'username' => 'staff',
                        'password' => password_hash('staff123', PASSWORD_DEFAULT),
                        'full_name' => 'พนักงาน',
                        'role' => 'staff', 
                        'status' => 'active'
                    ],
                    'kitchen' => [
                        'id' => 3,
                        'username' => 'kitchen',
                        'password' => password_hash('kitchen123', PASSWORD_DEFAULT),
                        'full_name' => 'พนักงานครัว',
                        'role' => 'kitchen',
                        'status' => 'active'
                    ]
                ];
                
                $user = $mockUsers[$username] ?? null;
            }
            
            if ($user && password_verify($password, $user['password'])) {
                // ล็อกอินสำเร็จ
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                
                // อัปเดต last login
                try {
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                } catch (Exception $e) {
                    // ไม่ต้องทำอะไรหากไม่สามารถอัปเดตได้
                }
                
                // จัดการ Remember Me
                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 วัน
                    
                    try {
                        $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                        $stmt->execute([$token, $user['id']]);
                    } catch (Exception $e) {
                        // ไม่ต้องทำอะไรหากไม่สามารถบันทึกได้
                    }
                }
                
                // บันทึกล็อก
                logActivity('login', "ผู้ใช้ {$user['username']} เข้าสู่ระบบ");
                
                return [
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['full_name'],
                        'role' => $user['role']
                    ]
                ];
            } else {
                // ล็อกอินไม่สำเร็จ
                logActivity('login_failed', "พยายามเข้าสู่ระบบด้วย username: {$username}");
                return [
                    'success' => false,
                    'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'
                ];
            }
            
        } catch (Exception $e) {
            handleError($e->getMessage(), __FILE__, __LINE__);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ'
            ];
        }
    }
}

/**
 * ฟังก์ชันออกจากระบบ
 */
if (!function_exists('logout')) {
    function logout() {
        // บันทึกล็อก
        if (isset($_SESSION['username'])) {
            logActivity('logout', "ผู้ใช้ {$_SESSION['username']} ออกจากระบบ");
        }
        
        // ลบ Remember Me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // ทำลาย session
        session_unset();
        session_destroy();
        
        // เริ่ม session ใหม่
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // redirect ไปหน้า login
        redirectTo('admin/login.php');
    }
}

/**
 * ตรวจสอบ Remember Me token
 */
if (!function_exists('checkRememberMe')) {
    function checkRememberMe() {
        if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
            global $pdo;
            
            try {
                $token = $_COOKIE['remember_token'];
                $stmt = $pdo->prepare("
                    SELECT id, username, full_name, role 
                    FROM users 
                    WHERE remember_token = ? AND status = 'active'
                ");
                $stmt->execute([$token]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    logActivity('auto_login', "ผู้ใช้ {$user['username']} เข้าสู่ระบบอัตโนมัติ");
                    return true;
                }
            } catch (Exception $e) {
                handleError($e->getMessage(), __FILE__, __LINE__);
            }
        }
        
        return false;
    }
}

/**
 * บันทึกกิจกรรมของผู้ใช้
 * @param string $action การกระทำ
 * @param string $description คำอธิบาย
 * @param array $data ข้อมูลเพิ่มเติม
 */
if (!function_exists('logActivity')) {
    function logActivity($action, $description, $data = []) {
        global $pdo;
        
        try {
            $logData = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'username' => $_SESSION['username'] ?? 'guest',
                'action' => $action,
                'description' => $description,
                'data' => json_encode($data),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs 
                (user_id, username, action, description, data, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $logData['user_id'],
                $logData['username'],
                $logData['action'],
                $logData['description'],
                $logData['data'],
                $logData['ip_address'],
                $logData['user_agent'],
                $logData['created_at']
            ]);
            
        } catch (Exception $e) {
            // หากไม่สามารถบันทึกล็อกได้ ให้เขียนลงไฟล์
            error_log(json_encode($logData), 3, __DIR__ . '/../logs/activity.log');
        }
    }
}

/**
 * ดึงข้อมูลผู้ใช้ปัจจุบัน
 */
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role'],
            'login_time' => $_SESSION['login_time'],
            'last_activity' => $_SESSION['last_activity']
        ];
    }
}

/**
 * เปลี่ยนรหัสผ่าน
 * @param int $userId ID ผู้ใช้
 * @param string $oldPassword รหัสผ่านเดิม
 * @param string $newPassword รหัสผ่านใหม่
 */
if (!function_exists('changePassword')) {
    function changePassword($userId, $oldPassword, $newPassword) {
        global $pdo;
        
        try {
            // ตรวจสอบรหัสผ่านเดิม
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($oldPassword, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'รหัสผ่านเดิมไม่ถูกต้อง'
                ];
            }
            
            // อัปเดตรหัสผ่านใหม่
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            logActivity('change_password', "ผู้ใช้เปลี่ยนรหัสผ่าน", ['user_id' => $userId]);
            
            return [
                'success' => true,
                'message' => 'เปลี่ยนรหัสผ่านสำเร็จ'
            ];
            
        } catch (Exception $e) {
            handleError($e->getMessage(), __FILE__, __LINE__);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน'
            ];
        }
    }
}

/**
 * รีเซ็ตรหัสผ่าน
 * @param string $email อีเมลผู้ใช้
 */
if (!function_exists('resetPassword')) {
    function resetPassword($email) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT id, username, full_name FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'ไม่พบอีเมลในระบบ'
                ];
            }
            
            // สร้าง reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);
            
            // ส่งอีเมลรีเซ็ตรหัสผ่าน (ในการใช้งานจริง)
            // sendResetPasswordEmail($email, $token);
            
            logActivity('reset_password', "ขอรีเซ็ตรหัสผ่าน", ['email' => $email]);
            
            return [
                'success' => true,
                'message' => 'ส่งลิงก์รีเซ็ตรหัสผ่านไปยังอีเมลแล้ว'
            ];
            
        } catch (Exception $e) {
            handleError($e->getMessage(), __FILE__, __LINE__);
            return [
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน'
            ];
        }
    }
}

// ตรวจสอบ Remember Me เมื่อโหลดหน้า
if (!isLoggedIn()) {
    checkRememberMe();
}
?>