<?php
// install.php - Smart Order System Installation

// ป้องกันการเรียกใช้หลังจากติดตั้งแล้ว
if (file_exists('config/installed.lock')) {
    die('
    <div style="padding: 20px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;">
        <h3>✅ ระบบถูกติดตั้งแล้ว</h3>
        <p>หากต้องการติดตั้งใหม่ กรุณาลบไฟล์ <code>config/installed.lock</code></p>
        <a href="admin/" class="btn" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">เข้าสู่ระบบ Admin</a>
    </div>
    ');
}

session_start();
$step = $_GET['step'] ?? 1;
$errors = [];
$success = [];

// ตรวจสอบ PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    $errors[] = 'ต้องการ PHP 7.4.0 หรือสูงกว่า (ปัจจุบัน: ' . PHP_VERSION . ')';
}

// ตรวจสอบ extensions ที่จำเป็น
$required_extensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    $errors[] = 'ขาด PHP Extensions: ' . implode(', ', $missing_extensions);
}

// ประมวลผลการติดตั้ง
if ($_POST) {
    switch ($_POST['action']) {
        case 'setup_database':
            $result = setupDatabase($_POST);
            if ($result['success']) {
                $success[] = $result['message'];
                $step = 3;
            } else {
                $errors[] = $result['message'];
            }
            break;
            
        case 'create_admin':
            $result = createAdminUser($_POST);
            if ($result['success']) {
                $success[] = $result['message'];
                $step = 4;
            } else {
                $errors[] = $result['message'];
            }
            break;
            
        case 'finish_install':
            $result = finishInstallation($_POST);
            if ($result['success']) {
                $success[] = $result['message'];
                $step = 5;
            } else {
                $errors[] = $result['message'];
            }
            break;
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตั้งระบบ - Smart Order Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #6f42c1, #8b5cf6);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .install-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .install-header {
            background: linear-gradient(135deg, #6f42c1, #8b5cf6);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .install-content {
            padding: 2rem;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .step.active {
            background: #6f42c1;
            color: white;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step-line {
            height: 2px;
            width: 60px;
            background: #e9ecef;
            margin-top: 19px;
            transition: all 0.3s;
        }
        
        .step-line.completed {
            background: #28a745;
        }
        
        .form-control {
            border-radius: 0.75rem;
            border: 1px solid #dee2e6;
            padding: 0.75rem 1rem;
        }
        
        .btn {
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
        }
        
        .requirement-check {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            margin: 0.5rem 0;
            border-radius: 0.5rem;
            background: #f8f9fa;
        }
        
        .requirement-check.success {
            background: #d4edda;
            color: #155724;
        }
        
        .requirement-check.error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1><i class="fas fa-utensils"></i> Smart Order Management</h1>
            <p class="mb-0">ระบบจัดการออเดอร์อัจฉริยะ - การติดตั้ง</p>
        </div>
        
        <div class="install-content">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>">1</div>
                <div class="step-line <?= $step > 1 ? 'completed' : '' ?>"></div>
                <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'completed' : '' ?>">2</div>
                <div class="step-line <?= $step > 2 ? 'completed' : '' ?>"></div>
                <div class="step <?= $step >= 3 ? 'active' : '' ?> <?= $step > 3 ? 'completed' : '' ?>">3</div>
                <div class="step-line <?= $step > 3 ? 'completed' : '' ?>"></div>
                <div class="step <?= $step >= 4 ? 'active' : '' ?> <?= $step > 4 ? 'completed' : '' ?>">4</div>
                <div class="step-line <?= $step > 4 ? 'completed' : '' ?>"></div>
                <div class="step <?= $step >= 5 ? 'active' : '' ?>">5</div>
            </div>
            
            <!-- Alerts -->
            <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endforeach; ?>
            
            <?php foreach ($success as $msg): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endforeach; ?>
            
            <!-- Installation Steps -->
            <?php if ($step == 1): ?>
            <!-- Step 1: Requirements Check -->
            <div class="text-center mb-4">
                <h3><i class="fas fa-clipboard-check"></i> ตรวจสอบความพร้อม</h3>
                <p class="text-muted">ตรวจสอบระบบและความต้องการในการติดตั้ง</p>
            </div>
            
            <div class="requirements">
                <div class="requirement-check <?= version_compare(PHP_VERSION, '7.4.0', '>=') ? 'success' : 'error' ?>">
                    <span><i class="fas fa-code"></i> PHP Version</span>
                    <span>
                        <?= PHP_VERSION ?>
                        <?= version_compare(PHP_VERSION, '7.4.0', '>=') ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?>
                    </span>
                </div>
                
                <?php foreach ($required_extensions as $ext): ?>
                <div class="requirement-check <?= extension_loaded($ext) ? 'success' : 'error' ?>">
                    <span><i class="fas fa-puzzle-piece"></i> <?= $ext ?> Extension</span>
                    <span>
                        <?= extension_loaded($ext) ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?>
                    </span>
                </div>
                <?php endforeach; ?>
                
                <div class="requirement-check <?= is_writable('.') ? 'success' : 'error' ?>">
                    <span><i class="fas fa-folder"></i> Write Permission</span>
                    <span>
                        <?= is_writable('.') ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' ?>
                    </span>
                </div>
            </div>
            
            <?php if (empty($errors)): ?>
            <div class="text-center mt-4">
                <a href="?step=2" class="btn btn-primary btn-lg">
                    <i class="fas fa-arrow-right"></i> ขั้นตอนถัดไป
                </a>
            </div>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                กรุณาแก้ไขปัญหาด้านบนก่อนดำเนินการต่อ
            </div>
            <?php endif; ?>
            
            <?php elseif ($step == 2): ?>
            <!-- Step 2: Database Configuration -->
            <div class="text-center mb-4">
                <h3><i class="fas fa-database"></i> ตั้งค่าฐานข้อมูล</h3>
                <p class="text-muted">กรอกข้อมูลการเชื่อมต่อฐานข้อมูล MySQL</p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="setup_database">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="db_host" class="form-label">Database Host</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="db_port" class="form-label">Port</label>
                        <input type="number" class="form-control" id="db_port" name="db_port" value="3306">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="db_name" class="form-label">Database Name</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="smart_order" required>
                        <div class="form-text">ฐานข้อมูลจะถูกสร้างอัตโนมัติหากไม่มี</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="db_charset" class="form-label">Charset</label>
                        <select class="form-select" id="db_charset" name="db_charset">
                            <option value="utf8mb4">utf8mb4 (แนะนำ)</option>
                            <option value="utf8">utf8</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="db_user" class="form-label">Username</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="db_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="db_password" name="db_password">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="import_sample_data" name="import_sample_data" checked>
                        <label class="form-check-label" for="import_sample_data">
                            Import ข้อมูลตัวอย่าง (เมนูอาหาร, การตั้งค่า)
                        </label>
                    </div>
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-database"></i> ติดตั้งฐานข้อมูล
                    </button>
                </div>
            </form>
            
            <?php elseif ($step == 3): ?>
            <!-- Step 3: Admin User Creation -->
            <div class="text-center mb-4">
                <h3><i class="fas fa-user-shield"></i> สร้างผู้ดูแลระบบ</h3>
                <p class="text-muted">สร้างบัญชีผู้ดูแลระบบหลัก</p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_admin">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="admin_username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="admin_username" name="admin_username" value="admin" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="admin_email" class="form-label">อีเมล</label>
                        <input type="email" class="form-control" id="admin_email" name="admin_email">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="admin_password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                        <div class="form-text">ควรมีความยาวอย่างน้อย 8 ตัวอักษร</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="admin_password_confirm" class="form-label">ยืนยันรหัสผ่าน</label>
                        <input type="password" class="form-control" id="admin_password_confirm" name="admin_password_confirm" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="admin_fullname" class="form-label">ชื่อ-นามสกุล</label>
                    <input type="text" class="form-control" id="admin_fullname" name="admin_fullname" placeholder="ผู้ดูแลระบบ">
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus"></i> สร้างผู้ดูแลระบบ
                    </button>
                </div>
            </form>
            
            <?php elseif ($step == 4): ?>
            <!-- Step 4: System Configuration -->
            <div class="text-center mb-4">
                <h3><i class="fas fa-cogs"></i> ตั้งค่าระบบ</h3>
                <p class="text-muted">ตั้งค่าข้อมูลพื้นฐานของร้าน</p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="finish_install">
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="shop_name" class="form-label">ชื่อร้าน</label>
                        <input type="text" class="form-control" id="shop_name" name="shop_name" value="ร้านอาหารตัวอย่าง" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="shop_phone" class="form-label">เบอร์โทรศัพท์</label>
                        <input type="text" class="form-control" id="shop_phone" name="shop_phone" value="02-XXX-XXXX">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="queue_reset_daily" class="form-label">รีเซ็ตคิวทุกวัน</label>
                        <select class="form-select" id="queue_reset_daily" name="queue_reset_daily">
                            <option value="1">เปิด (แนะนำ)</option>
                            <option value="0">ปิด</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="estimated_time_per_item" class="form-label">เวลาโดยประมาณต่อรายการ (นาที)</label>
                        <input type="number" class="form-control" id="estimated_time_per_item" name="estimated_time_per_item" value="5" min="1" max="60">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="site_url" class="form-label">URL ของระบบ</label>
                    <input type="url" class="form-control" id="site_url" name="site_url" value="<?= getCurrentUrl() ?>" required>
                    <div class="form-text">URL นี้จะใช้สำหรับ LINE Webhook และการสร้างลิงก์</div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>หมายเหตุ:</strong> การตั้งค่า LINE OA และ PromptPay สามารถทำได้ภายหลังในหน้า Admin
                </div>
                
                <div class="text-center">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-check"></i> เสร็จสิ้นการติดตั้ง
                    </button>
                </div>
            </form>
            
            <?php elseif ($step == 5): ?>
            <!-- Step 5: Installation Complete -->
            <div class="text-center">
                <div class="mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                
                <h3 class="text-success">ติดตั้งเสร็จสิ้น!</h3>
                <p class="text-muted mb-4">ระบบ Smart Order Management พร้อมใช้งานแล้ว</p>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-tachometer-alt fa-2x mb-3 text-primary"></i>
                                <h5>Admin Panel</h5>
                                <p class="small text-muted">จัดการระบบทั้งหมด</p>
                                <a href="admin/" class="btn btn-primary">เข้าสู่ระบบ Admin</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-store fa-2x mb-3 text-success"></i>
                                <h5>หน้าร้าน</h5>
                                <p class="small text-muted">สำหรับลูกค้าสั่งอาหาร</p>
                                <a href="customer/" class="btn btn-success">หน้าลูกค้า</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-cash-register fa-2x mb-3 text-warning"></i>
                                <h5>POS System</h5>
                                <p class="small text-muted">ระบบขายหน้าร้าน</p>
                                <a href="pos/" class="btn btn-warning">ระบบ POS</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning mt-4">
                    <i class="fas fa-shield-alt"></i>
                    <strong>เพื่อความปลอดภัย:</strong> กรุณาลบไฟล์ <code>install.php</code> หลังจากติดตั้งเสร็จสิ้น
                </div>
                
                <div class="mt-4">
                    <h6>ขั้นตอนถัดไป:</h6>
                    <ul class="list-unstyled text-start">
                        <li>✅ เข้าสู่ระบบ Admin และตั้งค่า LINE OA</li>
                        <li>✅ เพิ่มเมนูอาหารของร้าน</li>
                        <li>✅ ทดสอบระบบการสั่งอาหาร</li>
                        <li>✅ ตั้งค่า PromptPay สำหรับการชำระเงิน</li>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php

// =============== Installation Functions ===============

function setupDatabase($data) {
    try {
        // สร้างการเชื่อมต่อเพื่อสร้างฐานข้อมูล
        $tempPdo = new PDO(
            "mysql:host={$data['db_host']};charset={$data['db_charset']}", 
            $data['db_user'], 
            $data['db_password']
        );
        
        // สร้างฐานข้อมูล
        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$data['db_name']}` CHARACTER SET {$data['db_charset']} COLLATE {$data['db_charset']}_unicode_ci");
        
        // เชื่อมต่อกับฐานข้อมูลที่สร้าง
        $pdo = new PDO(
            "mysql:host={$data['db_host']};dbname={$data['db_name']};charset={$data['db_charset']}", 
            $data['db_user'], 
            $data['db_password']
        );
        
        // สร้างไฟล์ config ฐานข้อมูล
        createDatabaseConfig($data);
        
        // Import SQL structure
        $sqlFile = file_get_contents('smart_order.sql');
        $pdo->exec($sqlFile);
        
        // Import sample data ถ้าเลือก
        if (isset($data['import_sample_data'])) {
            importSampleData($pdo);
        }
        
        return ['success' => true, 'message' => 'ติดตั้งฐานข้อมูลสำเร็จ'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

function createAdminUser($data) {
    try {
        if ($data['admin_password'] !== $data['admin_password_confirm']) {
            throw new Exception('รหัสผ่านไม่ตรงกัน');
        }
        
        if (strlen($data['admin_password']) < 6) {
            throw new Exception('รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร');
        }
        
        // เชื่อมต่อฐานข้อมูล
        require_once 'config/config.php';
        
        // ตรวจสอบว่ามี admin อยู่แล้วหรือไม่
        $existing = $db->fetchOne("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        if ($existing) {
            throw new Exception('มี Admin อยู่แล้วในระบบ');
        }
        
        // สร้าง admin user
        $hashedPassword = password_hash($data['admin_password'], PASSWORD_DEFAULT);
        
        $db->query("
            INSERT INTO users (username, password, full_name, email, role, active, created_at) 
            VALUES (?, ?, ?, ?, 'admin', 1, NOW())
        ", [
            $data['admin_username'],
            $hashedPassword,
            $data['admin_fullname'] ?: 'ผู้ดูแลระบบ',
            $data['admin_email'] ?: null
        ]);
        
        return ['success' => true, 'message' => 'สร้างผู้ดูแลระบบสำเร็จ'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function finishInstallation($data) {
    try {
        require_once 'config/config.php';
        
        // อัปเดตการตั้งค่าระบบ
        $settings = [
            'shop_name' => $data['shop_name'],
            'shop_phone' => $data['shop_phone'],
            'queue_reset_daily' => $data['queue_reset_daily'],
            'estimated_time_per_item' => $data['estimated_time_per_item'],
            'site_url' => rtrim($data['site_url'], '/')
        ];
        
        foreach ($settings as $key => $value) {
            $db->query("
                INSERT INTO system_settings (setting_key, setting_value, description, created_at) 
                VALUES (?, ?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ", [$key, $value, '', $value]);
        }
        
        // สร้างไฟล์ lock
        if (!is_dir('config')) {
            mkdir('config', 0755, true);
        }
        
        file_put_contents('config/installed.lock', json_encode([
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'admin_user' => $data['shop_name']
        ]));
        
        return ['success' => true, 'message' => 'ติดตั้งระบบเสร็จสิ้น'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function createDatabaseConfig($data) {
    $configContent = "<?php
// config/database.php - Auto-generated by installer

define('DB_HOST', '{$data['db_host']}');
define('DB_NAME', '{$data['db_name']}');
define('DB_USER', '{$data['db_user']}');
define('DB_PASS', '{$data['db_password']}');
define('DB_CHARSET', '{$data['db_charset']}');

// Include the main database class
require_once __DIR__ . '/../classes/Database.php';
?>";
    
    if (!is_dir('config')) {
        mkdir('config', 0755, true);
    }
    
    file_put_contents('config/database.php', $configContent);
}

function importSampleData($pdo) {
    // Sample menu items will be imported from SQL file
    // Additional sample data can be added here
}

function getCurrentUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    $path = rtrim($path, '/') . '/';
    
    return $protocol . $host . $path;
}

?>