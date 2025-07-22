<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    require_once '../config/config.php';
    require_once '../config/database.php';
} catch (Exception $e) {
    die('Error loading config: ' . $e->getMessage());
}

// Check if user is logged in
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isset($_SESSION['admin_id'])) {
            header('Location: login.php');
            exit;
        }
    }
}

requireLogin();

$pageTitle = 'จัดการผู้ใช้';
$activePage = 'users';

// Helper functions
if (!function_exists('formatDateTime')) {
    function formatDateTime($datetime) {
        return date('d/m/Y H:i:s', strtotime($datetime));
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount) {
        return number_format($amount, 2) . ' ฿';
    }
}

if (!function_exists('getRoleColor')) {
    function getRoleColor($role) {
        $colors = [
            'admin' => 'danger',
            'pos' => 'primary',
            'kitchen' => 'success',
            'customer' => 'info'
        ];
        return $colors[$role] ?? 'secondary';
    }
}

if (!function_exists('getRoleText')) {
    function getRoleText($role) {
        $texts = [
            'admin' => 'ผู้ดูแลระบบ',
            'pos' => 'พนักงาน POS',
            'kitchen' => 'พนักงานครัว',
            'customer' => 'ลูกค้า'
        ];
        return $texts[$role] ?? $role;
    }
}

if (!function_exists('generateRandomPassword')) {
    function generateRandomPassword($length = 8) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $password;
    }
}

// Handle form submissions
if ($_POST) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    $response = addUser($_POST);
                    break;
                
                case 'edit':
                    $response = editUser($_POST);
                    break;
                
                case 'delete':
                    $response = deleteUser($_POST['id']);
                    break;
                
                case 'toggle_status':
                    $response = toggleUserStatus($_POST['id']);
                    break;
                
                case 'reset_password':
                    $response = resetUserPassword($_POST['id']);
                    break;
            }
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
    
    // Return JSON for AJAX requests
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    if ($response['success']) {
        $message = $response['message'];
    } else {
        $error = $response['message'];
    }
}

// Create users table if not exists
createUsersTable();

// Get users with statistics
$users = getUsersWithStats();
$userStats = getUserStatistics();

// =============== PHP Functions ===============

function createUsersTable() {
    global $db;
    
    try {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NULL,
            phone VARCHAR(20) NULL,
            role ENUM('admin', 'pos', 'kitchen', 'customer') DEFAULT 'customer',
            department VARCHAR(100) NULL,
            avatar VARCHAR(500) NULL,
            active TINYINT(1) DEFAULT 1,
            email_verified TINYINT(1) DEFAULT 0,
            last_login TIMESTAMP NULL,
            last_activity TIMESTAMP NULL,
            login_count INT DEFAULT 0,
            failed_login_attempts INT DEFAULT 0,
            locked_until TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_active (active),
            INDEX idx_last_activity (last_activity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->query($sql);
        
        // Insert default admin if no users exist
        $userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users");
        if (!$userCount || $userCount['count'] == 0) {
            $db->query("INSERT INTO users (username, password, full_name, email, role, active) VALUES (?, ?, ?, ?, ?, ?)", [
                'admin',
                password_hash('admin123', PASSWORD_DEFAULT),
                'ผู้ดูแลระบบ',
                'admin@smartorder.com',
                'admin',
                1
            ]);
        }
    } catch (Exception $e) {
        // Handle error silently in production
        error_log('Error creating users table: ' . $e->getMessage());
    }
}

function getUsersWithStats() {
    global $db;
    
    try {
        return $db->fetchAll("
            SELECT u.*,
                   (CASE WHEN u.last_activity > DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 ELSE 0 END) as is_online
            FROM users u
            ORDER BY u.created_at DESC
        ");
    } catch (Exception $e) {
        error_log('Error getting users: ' . $e->getMessage());
        return [];
    }
}

function getUserStatistics() {
    global $db;
    
    try {
        $stats = $db->fetchOne("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN active = 1 THEN 1 END) as active,
                COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins,
                COUNT(CASE WHEN last_activity > DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as online_today
            FROM users
        ");
        
        return $stats ?: ['total' => 0, 'active' => 0, 'admins' => 0, 'online_today' => 0];
    } catch (Exception $e) {
        error_log('Error getting user statistics: ' . $e->getMessage());
        return ['total' => 0, 'active' => 0, 'admins' => 0, 'online_today' => 0];
    }
}

function addUser($data) {
    global $db;
    
    // Validation
    if (empty($data['username']) || empty($data['password']) || empty($data['role'])) {
        return ['success' => false, 'message' => 'กรุณากรอกข้อมูลที่จำเป็น'];
    }
    
    if (isset($data['confirm_password']) && $data['password'] !== $data['confirm_password']) {
        return ['success' => false, 'message' => 'รหัสผ่านไม่ตรงกัน'];
    }
    
    if (strlen($data['password']) < 6) {
        return ['success' => false, 'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร'];
    }
    
    // Check if username exists
    try {
        $existing = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$data['username']]);
        if ($existing) {
            return ['success' => false, 'message' => 'ชื่อผู้ใช้นี้มีอยู่แล้ว'];
        }
        
        // Check if email exists
        if (!empty($data['email'])) {
            $existing = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$data['email']]);
            if ($existing) {
                return ['success' => false, 'message' => 'อีเมลนี้มีอยู่แล้ว'];
            }
        }
        
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $db->query("
            INSERT INTO users (username, email, password, full_name, phone, role, department, active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $data['username'],
            $data['email'] ?: null,
            $hashedPassword,
            $data['full_name'] ?: null,
            $data['phone'] ?: null,
            $data['role'],
            $data['department'] ?: null,
            isset($data['active']) ? 1 : 0
        ]);
        
        return ['success' => true, 'message' => 'เพิ่มผู้ใช้สำเร็จ'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

function editUser($data) {
    global $db;
    
    if (empty($data['id']) || empty($data['username']) || empty($data['role'])) {
        return ['success' => false, 'message' => 'กรุณากรอกข้อมูลที่จำเป็น'];
    }
    
    try {
        // Check if username exists (excluding current user)
        $existing = $db->fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$data['username'], $data['id']]);
        if ($existing) {
            return ['success' => false, 'message' => 'ชื่อผู้ใช้นี้มีอยู่แล้ว'];
        }
        
        $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, role = ?, department = ?, active = ?";
        $params = [
            $data['username'],
            $data['email'] ?: null,
            $data['full_name'] ?: null,
            $data['phone'] ?: null,
            $data['role'],
            $data['department'] ?: null,
            isset($data['active']) ? 1 : 0
        ];
        
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                return ['success' => false, 'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร'];
            }
            $sql .= ", password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $data['id'];
        
        $db->query($sql, $params);
        
        return ['success' => true, 'message' => 'แก้ไขผู้ใช้สำเร็จ'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

function deleteUser($id) {
    global $db;
    
    if ($id == $_SESSION['admin_id']) {
        return ['success' => false, 'message' => 'ไม่สามารถลบบัญชีตัวเองได้'];
    }
    
    try {
        $db->query("DELETE FROM users WHERE id = ?", [$id]);
        return ['success' => true, 'message' => 'ลบผู้ใช้สำเร็จ'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

function toggleUserStatus($id) {
    global $db;
    
    if ($id == $_SESSION['admin_id']) {
        return ['success' => false, 'message' => 'ไม่สามารถเปลี่ยนสถานะบัญชีตัวเองได้'];
    }
    
    try {
        $user = $db->fetchOne("SELECT active FROM users WHERE id = ?", [$id]);
        if (!$user) {
            return ['success' => false, 'message' => 'ไม่พบผู้ใช้'];
        }
        
        $newStatus = $user['active'] ? 0 : 1;
        $db->query("UPDATE users SET active = ? WHERE id = ?", [$newStatus, $id]);
        
        $message = $newStatus ? 'เปิดใช้งานผู้ใช้สำเร็จ' : 'ระงับผู้ใช้สำเร็จ';
        return ['success' => true, 'message' => $message];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

function resetUserPassword($id) {
    global $db;
    
    try {
        $newPassword = generateRandomPassword(8);
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $db->query("UPDATE users SET password = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?", [$hashedPassword, $id]);
        
        return [
            'success' => true, 
            'message' => 'รีเซ็ตรหัสผ่านสำเร็จ',
            'new_password' => $newPassword
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()];
    }
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'เมื่อสักครู่';
    if ($time < 3600) return floor($time/60) . ' นาทีที่แล้ว';
    if ($time < 86400) return floor($time/3600) . ' ชั่วโมงที่แล้ว';
    if ($time < 2592000) return floor($time/86400) . ' วันที่แล้ว';
    if ($time < 31536000) return floor($time/2592000) . ' เดือนที่แล้ว';
    
    return floor($time/31536000) . ' ปีที่แล้ว';
}

// Include header if it exists
$headerFile = 'includes/header.php';
if (file_exists($headerFile)) {
    include $headerFile;
} else {
    // Basic HTML header if includes/header.php doesn't exist
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $pageTitle ?? 'จัดการผู้ใช้' ?> - Smart Order</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
        <style>
            body { background-color: #f8f9fa; }
            .card { border-radius: 1rem; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
            .stats-card { background: linear-gradient(135deg, #6f42c1, #8b5cf6); color: white; }
            .stats-card.success { background: linear-gradient(135deg, #198754, #20c997); }
            .stats-card.warning { background: linear-gradient(135deg, #ffc107, #ffcd39); color: #212529; }
            .stats-card.info { background: linear-gradient(135deg, #0dcaf0, #39c3f3); }
            .btn { border-radius: 0.75rem; }
            .table { border-radius: 1rem; overflow: hidden; }
            .badge { font-size: 0.75rem; padding: 0.5rem 0.75rem; }
        </style>
    </head>
    <body>
        <div class="container-fluid py-4">
    <?php
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-users-cog"></i> จัดการผู้ใช้</h1>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
            <i class="fas fa-sync"></i> Refresh
        </button>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus"></i> เพิ่มผู้ใช้ใหม่
        </button>
    </div>
</div>

<!-- Alert Messages -->
<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- User Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">ผู้ใช้ทั้งหมด</div>
                        <div class="h5 mb-0 font-weight-bold"><?= $userStats['total'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card success">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">ใช้งานอยู่</div>
                        <div class="h5 mb-0 font-weight-bold"><?= $userStats['active'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card warning">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">ผู้ดูแลระบบ</div>
                        <div class="h5 mb-0 font-weight-bold"><?= $userStats['admins'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-shield fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card info">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">ออนไลน์วันนี้</div>
                        <div class="h5 mb-0 font-weight-bold"><?= $userStats['online_today'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-wifi fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">รายการผู้ใช้ทั้งหมด</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อผู้ใช้</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>อีเมล</th>
                        <th>บทบาท</th>
                        <th>สถานะ</th>
                        <th>วันที่สร้าง</th>
                        <th>การกระทำ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($user['username']) ?></div>
                            <?php if ($user['id'] == $_SESSION['admin_id']): ?>
                                <small class="text-primary"><i class="fas fa-user"></i> คุณ</small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($user['full_name'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($user['email'] ?: '-') ?></td>
                        <td>
                            <span class="badge bg-<?= getRoleColor($user['role']) ?>">
                                <?= getRoleText($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $user['active'] ? 'success' : 'danger' ?>">
                                <?= $user['active'] ? 'ใช้งาน' : 'ระงับ' ?>
                            </span>
                        </td>
                        <td><?= formatDateTime($user['created_at']) ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="toggleUserStatus(<?= $user['id'] ?>)">
                                    <i class="fas fa-<?= $user['active'] ? 'ban' : 'check' ?>"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> เพิ่มผู้ใช้ใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addUserForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">ชื่อผู้ใช้ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="full_name" class="form-label">ชื่อ-นามสกุล</label>
                            <input type="text" class="form-control" id="full_name" name="full_name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">บทบาท <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">เลือกบทบาท</option>
                                <option value="admin">ผู้ดูแลระบบ</option>
                                <option value="pos">พนักงาน POS</option>
                                <option value="kitchen">พนักงานครัว</option>
                                <option value="customer">ลูกค้า</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                                <label class="form-check-label" for="active">เปิดใช้งาน</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-edit"></i> แก้ไขผู้ใช้</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_username" class="form-label">ชื่อผู้ใช้</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="edit_email" name="email">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="edit_full_name" class="form-label">ชื่อ-นามสกุล</label>
                            <input type="text" class="form-control" id="edit_full_name" name="full_name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="edit_phone" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="tel" class="form-control" id="edit_phone" name="phone">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_role" class="form-label">บทบาท</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="admin">ผู้ดูแลระบบ</option>
                                <option value="pos">พนักงาน POS</option>
                                <option value="kitchen">พนักงานครัว</option>
                                <option value="customer">ลูกค้า</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_password" class="form-label">รหัสผ่านใหม่</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                            <div class="form-text">เว้นว่างไว้หากไม่ต้องการเปลี่ยน</div>
                        </div>
                    </div>
                    
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit_active" name="active">
                        <label class="form-check-label" for="edit_active">เปิดใช้งาน</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#usersTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json'
        },
        order: [[6, 'desc']], // Sort by created_at
        pageLength: 25,
        responsive: true
    });

    // Form submissions
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();
        
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
            alert('รหัสผ่านไม่ตรงกัน');
            return;
        }
        
        this.submit();
    });

    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();
        this.submit();
    });
});

function editUser(user) {
    $('#edit_id').val(user.id);
    $('#edit_username').val(user.username);
    $('#edit_email').val(user.email);
    $('#edit_full_name').val(user.full_name);
    $('#edit_phone').val(user.phone);
    $('#edit_role').val(user.role);
    $('#edit_active').prop('checked', user.active == 1);
    
    $('#editUserModal').modal('show');
}

function deleteUser(id, username) {
    if (confirm(`คุณต้องการลบผู้ใช้ "${username}" หรือไม่?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function toggleUserStatus(userId) {
    if (confirm('คุณต้องการเปลี่ยนสถานะผู้ใช้นี้หรือไม่?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// Include footer if it exists
$footerFile = 'includes/footer.php';
if (file_exists($footerFile)) {
    include $footerFile;
} else {
    echo "</div></body></html>";
}
?>