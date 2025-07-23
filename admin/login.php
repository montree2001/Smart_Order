<?php
// admin/login.php - หน้าเข้าสู่ระบบ
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../config/config.php';

// ถ้าล็อกอินแล้วให้ redirect ไปหน้า dashboard
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $redirect_url = $_GET['redirect'] ?? '/smart_order/admin/';
    header("Location: $redirect_url");
    exit();
}

$error_message = '';
$success_message = '';
$redirect_url = $_GET['redirect'] ?? '/smart_order/admin/';

// ตรวจสอบข้อความจาก logout
if (isset($_SESSION['logout_message'])) {
    $success_message = $_SESSION['logout_message'];
    unset($_SESSION['logout_message']);
}

// ประมวลผลการเข้าสู่ระบบ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        $error_message = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        // ตรวจสอบการพยายามเข้าสู่ระบบ
        if (!checkLoginAttempts($username)) {
            $error_message = 'บัญชีถูกล็อกชั่วคราวเนื่องจากพยายามเข้าสู่ระบบผิดหลายครั้ง';
        } else {
            // ค้นหาผู้ใช้ในฐานข้อมูล
            $username_escaped = mysqli_real_escape_string($connection, $username);
            $query = "
                SELECT id, username, password, full_name, email, role, is_active 
                FROM users 
                WHERE (username = '$username_escaped' OR email = '$username_escaped') 
                AND is_active = 1 
                LIMIT 1
            ";
            
            $result = mysqli_query($connection, $query);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                
                // ตรวจสอบรหัสผ่าน
                if (password_verify($password, $user['password'])) {
                    // เข้าสู่ระบบสำเร็จ
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['login_time'] = time();
                    $_SESSION['session_ip'] = $_SERVER['REMOTE_ADDR'];
                    $_SESSION['session_ua'] = $_SERVER['HTTP_USER_AGENT'];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    
                    // อัปเดต last_login
                    $update_query = "UPDATE users SET last_login = NOW() WHERE id = " . intval($user['id']);
                    mysqli_query($connection, $update_query);
                    
                    // บันทึกความพยายามเข้าสู่ระบบสำเร็จ
                    recordLoginAttempt($username, true);
                    
                    // ล้างการพยายามเข้าสู่ระบบที่ผิด
                    clearLoginAttempts($username);
                    
                    // Remember Me
                    if ($remember_me) {
                        $token = bin2hex(random_bytes(32));
                        $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 วัน
                        
                        // บันทึก token ในฐานข้อมูล (จำเป็นต้องสร้างตาราง remember_tokens)
                        $token_query = "
                            INSERT INTO remember_tokens (user_id, token, expires_at, created_at) 
                            VALUES ({$user['id']}, '$token', '$expires', NOW())
                            ON DUPLICATE KEY UPDATE 
                            token = VALUES(token), expires_at = VALUES(expires_at), created_at = NOW()
                        ";
                        mysqli_query($connection, $token_query);
                        
                        // ตั้ง cookie
                        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                    }
                    
                    // บันทึก log
                    logUserActivity($user['id'], 'login', 'เข้าสู่ระบบสำเร็จ');
                    
                    // Redirect
                    header("Location: $redirect_url");
                    exit();
                } else {
                    $error_message = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                    recordLoginAttempt($username, false);
                }
            } else {
                $error_message = 'ไม่พบบัญชีผู้ใช้นี้หรือบัญชีถูกปิดใช้งาน';
                recordLoginAttempt($username, false);
            }
        }
    }
}

// ตรวจสอบ Remember Me Token
if (isset($_COOKIE['remember_token']) && empty($_SESSION['user_id'])) {
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
        $user = mysqli_fetch_assoc($result);
        
        // เข้าสู่ระบบอัตโนมัติ
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['login_time'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        header("Location: $redirect_url");
        exit();
    } else {
        // ลบ cookie ที่ไม่ถูกต้อง
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// Helper functions
function checkLoginAttempts($username, $max_attempts = 5, $lockout_time = 900) {
    global $connection;
    
    $username = mysqli_real_escape_string($connection, $username);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $cutoff_time = date('Y-m-d H:i:s', time() - $lockout_time);
    
    $query = "
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE (username = '$username' OR ip_address = '$ip_address') 
        AND success = 0 
        AND attempted_at > '$cutoff_time'
    ";
    
    $result = mysqli_query($connection, $query);
    if ($result) {
        $data = mysqli_fetch_assoc($result);
        return ($data['attempts'] ?? 0) < $max_attempts;
    }
    
    return true;
}

function recordLoginAttempt($username, $success = false) {
    global $connection;
    
    $username = mysqli_real_escape_string($connection, $username);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $success_int = $success ? 1 : 0;
    
    // สร้างตารางถ้ายังไม่มี
    $create_table = "
        CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            success TINYINT(1) NOT NULL DEFAULT 0,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_ip_address (ip_address),
            INDEX idx_attempted_at (attempted_at)
        )
    ";
    mysqli_query($connection, $create_table);
    
    $query = "
        INSERT INTO login_attempts 
        (username, ip_address, user_agent, success, attempted_at) 
        VALUES ('$username', '$ip_address', '$user_agent', $success_int, NOW())
    ";
    
    mysqli_query($connection, $query);
}

function clearLoginAttempts($username) {
    global $connection;
    
    $username = mysqli_real_escape_string($connection, $username);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $query = "
        DELETE FROM login_attempts 
        WHERE (username = '$username' OR ip_address = '$ip_address') 
        AND success = 0
    ";
    
    mysqli_query($connection, $query);
}

function logUserActivity($user_id, $action, $details = '') {
    global $connection;
    
    $user_id = intval($user_id);
    $action = mysqli_real_escape_string($connection, $action);
    $details = mysqli_real_escape_string($connection, $details);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // สร้างตารางถ้ายังไม่มี
    $create_table = "
        CREATE TABLE IF NOT EXISTS user_activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        )
    ";
    mysqli_query($connection, $create_table);
    
    $query = "
        INSERT INTO user_activity_logs 
        (user_id, action, details, ip_address, user_agent) 
        VALUES ($user_id, '$action', '$details', '$ip_address', '$user_agent')
    ";
    
    mysqli_query($connection, $query);
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Smart Order System</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            opacity: 0.9;
            margin: 0;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .form-control {
            border-radius: 15px;
            border: 2px solid #e9ecef;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 15px;
            padding: 12px 0;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-login:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.5s;
        }
        
        .btn-login:hover:before {
            left: 100%;
        }
        
        .alert {
            border-radius: 15px;
            border: none;
            margin-bottom: 1rem;
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .login-footer {
            background: #f8f9fa;
            padding: 1rem 2rem;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        
        .system-info {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border-radius: 15px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .loading-spinner {
            display: none;
        }
        
        .btn-login.loading .loading-spinner {
            display: inline-block;
        }
        
        .btn-login.loading .btn-text {
            display: none;
        }
        
        @media (max-width: 576px) {
            .login-card {
                margin: 10px;
            }
            
            .login-header,
            .login-body {
                padding: 1.5rem;
            }
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: #495057;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="login-card">
            
            <!-- Header -->
            <div class="login-header">
                <i class="fas fa-utensils fa-2x mb-3"></i>
                <h1>Smart Order System</h1>
                <p>ระบบจัดการออเดอร์อัจฉริยะ</p>
            </div>

            <!-- Body -->
            <div class="login-body">
                
                <!-- Error/Success Messages -->
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" id="loginForm">
                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username" placeholder="ชื่อผู้ใช้หรืออีเมล" required>
                        <label for="username">
                            <i class="fas fa-user me-2"></i>ชื่อผู้ใช้หรืออีเมล
                        </label>
                    </div>

                    <div class="form-floating position-relative">
                        <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน" required>
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>รหัสผ่าน
                        </label>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me">
                        <label class="form-check-label" for="remember_me">
                            จดจำการเข้าสู่ระบบ
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-login w-100">
                        <span class="btn-text">
                            <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                        </span>
                        <span class="loading-spinner">
                            <i class="fas fa-spinner fa-spin"></i> กำลังเข้าสู่ระบบ...
                        </span>
                    </button>
                </form>

                <!-- System Info -->
                <div class="system-info">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        เวอร์ชัน <?php echo SYSTEM_VERSION; ?> | 
                        <i class="fas fa-clock me-1"></i>
                        <?php echo date('d/m/Y H:i'); ?>
                    </small>
                </div>

            </div>

            <!-- Footer -->
            <div class="login-footer">
                <small class="text-muted">
                    © <?php echo date('Y'); ?> Smart Order System. สงวนลิขสิทธิ์.
                </small>
            </div>

        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('passwordToggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-login');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            
            // Re-enable if there's an error (will be handled by page reload)
            setTimeout(() => {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }, 10000);
        });

        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Enter key to submit form
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').submit();
            }
        });

        // Clear any existing error after user starts typing
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                const alertDiv = document.querySelector('.alert-danger');
                if (alertDiv) {
                    alertDiv.style.display = 'none';
                }
            });
        });

        // Add floating label animation
        document.querySelectorAll('.form-floating input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentNode.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentNode.classList.remove('focused');
                }
            });
        });
    </script>

</body>
</html>