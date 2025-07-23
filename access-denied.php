<?php
// admin/access-denied.php - หน้าไม่มีสิทธิ์เข้าถึง
session_start();
require_once '../config/config.php';

$user_role = $_SESSION['user_role'] ?? 'guest';
$user_name = $_SESSION['user_name'] ?? 'ผู้เยี่ยมชม';

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ไม่มีสิทธิ์เข้าถึง - Smart Order System</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .access-denied-container {
            max-width: 600px;
            text-align: center;
            color: white;
            padding: 2rem;
        }
        
        .access-denied-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 3rem;
            color: #333;
        }
        
        .error-icon {
            font-size: 6rem;
            color: #dc3545;
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .error-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        
        .error-subtitle {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 2rem;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 15px;
            margin: 2rem 0;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 15px;
            padding: 12px 30px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 0 10px;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .btn-login:hover {
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }
        
        .permissions-info {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .permissions-info h5 {
            color: #856404;
            margin-bottom: 1rem;
        }
        
        .permissions-list {
            list-style: none;
            padding: 0;
        }
        
        .permissions-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 193, 7, 0.2);
        }
        
        .permissions-list li:last-child {
            border-bottom: none;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .role-admin { background: #dc3545; color: white; }
        .role-manager { background: #007bff; color: white; }
        .role-pos-staff { background: #28a745; color: white; }
        .role-kitchen-staff { background: #fd7e14; color: white; }
        .role-guest { background: #6c757d; color: white; }
        
        @media (max-width: 576px) {
            .access-denied-card {
                padding: 2rem;
                margin: 1rem;
            }
            
            .error-icon {
                font-size: 4rem;
            }
            
            .error-title {
                font-size: 2rem;
            }
            
            .error-subtitle {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>

    <div class="access-denied-container">
        <div class="access-denied-card">
            
            <!-- Error Icon -->
            <div class="error-icon">
                <i class="fas fa-ban"></i>
            </div>
            
            <!-- Error Message -->
            <h1 class="error-title">ไม่มีสิทธิ์เข้าถึง</h1>
            <p class="error-subtitle">ขออภัย คุณไม่มีสิทธิ์เข้าถึงหน้านี้</p>
            
            <!-- Current User Info -->
            <div class="user-info">
                <h5><i class="fas fa-user me-2"></i>ข้อมูลผู้ใช้ปัจจุบัน</h5>
                <p class="mb-2">
                    <strong>ชื่อผู้ใช้:</strong> <?php echo htmlspecialchars($user_name); ?>
                </p>
                <p class="mb-0">
                    <strong>สิทธิ์:</strong> 
                    <span class="role-badge role-<?php echo str_replace('_', '-', $user_role); ?>">
                        <?php 
                        $role_names = [
                            'admin' => 'ผู้ดูแลระบบ',
                            'manager' => 'ผู้จัดการ',
                            'pos_staff' => 'พนักงาน POS',
                            'kitchen_staff' => 'พนักงานครัว',
                            'guest' => 'ผู้เยี่ยมชม'
                        ];
                        echo $role_names[$user_role] ?? $user_role;
                        ?>
                    </span>
                </p>
            </div>
            
            <!-- Permissions Info -->
            <div class="permissions-info">
                <h5><i class="fas fa-info-circle me-2"></i>สิทธิ์การเข้าถึงแต่ละระดับ</h5>
                <ul class="permissions-list text-start">
                    <li>
                        <span class="role-badge role-admin">Admin</span>
                        <span class="ms-2">เข้าถึงได้ทุกหน้า รวมถึงการตั้งค่าระบบและจัดการผู้ใช้</span>
                    </li>
                    <li>
                        <span class="role-badge role-manager">Manager</span>
                        <span class="ms-2">เข้าถึงได้ทุกหน้า ยกเว้นการตั้งค่าระบบขั้นสูง</span>
                    </li>
                    <li>
                        <span class="role-badge role-pos-staff">POS Staff</span>
                        <span class="ms-2">เข้าถึงระบบ POS และจัดการออเดอร์เท่านั้น</span>
                    </li>
                    <li>
                        <span class="role-badge role-kitchen-staff">Kitchen Staff</span>
                        <span class="ms-2">เข้าถึงระบบครัวและจัดการสถานะอาหารเท่านั้น</span>
                    </li>
                </ul>
            </div>
            
            <!-- Action Buttons -->
            <div class="mt-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="index.php" class="btn-back">
                        <i class="fas fa-arrow-left me-2"></i>กลับหน้าหลัก
                    </a>
                    
                    <?php if (in_array($user_role, ['pos_staff', 'manager', 'admin'])): ?>
                        <a href="../pos/" class="btn-back">
                            <i class="fas fa-cash-register me-2"></i>ไปยัง POS
                        </a>
                    <?php endif; ?>
                    
                    <?php if (in_array($user_role, ['kitchen_staff', 'manager', 'admin'])): ?>
                        <a href="../kitchen/" class="btn-back">
                            <i class="fas fa-utensils me-2"></i>ไปยังครัว
                        </a>
                    <?php endif; ?>
                    
                    <a href="logout.php" class="btn-back" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                        <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn-back btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Additional Info -->
            <div class="mt-4 pt-4 border-top">
                <p class="text-muted mb-0">
                    <i class="fas fa-question-circle me-2"></i>
                    หากคุณคิดว่านี่เป็นข้อผิดพลาด กรุณาติดต่อผู้ดูแลระบบ
                </p>
                <p class="text-muted mt-2">
                    <small>
                        <i class="fas fa-clock me-1"></i>
                        เวลาที่เกิดข้อผิดพลาด: <?php echo date('d/m/Y H:i:s'); ?>
                        <span class="ms-3">
                            <i class="fas fa-globe me-1"></i>
                            IP: <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?>
                        </span>
                    </small>
                </p>
            </div>
            
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate card entrance
            const card = document.querySelector('.access-denied-card');
            card.style.opacity = '0';
            card.style.transform = 'translateY(50px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.8s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
            
            // Auto redirect after 30 seconds if user is not logged in
            <?php if (!isset($_SESSION['user_id'])): ?>
            let countdown = 30;
            const redirectTimer = setInterval(() => {
                countdown--;
                if (countdown <= 0) {
                    window.location.href = 'login.php';
                    clearInterval(redirectTimer);
                }
            }, 1000);
            <?php endif; ?>
        });
        
        // Handle back button
        window.addEventListener('popstate', function(event) {
            <?php if (isset($_SESSION['user_id'])): ?>
                window.location.href = 'index.php';
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        });
    </script>

</body>
</html>