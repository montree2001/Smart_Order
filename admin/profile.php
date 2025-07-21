<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$pageTitle = 'โปรไฟล์';
$activePage = '';

$userId = $_SESSION['admin_id'];

if ($_POST) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $fullName = $_POST['full_name'] ?? '';

    // Verify current password (simplified for demo)
    if ($currentPassword === 'admin123') {
        $updates = [];
        $params = [];

        if (!empty($fullName)) {
            $updates[] = "full_name = ?";
            $params[] = $fullName;
        }

        if (!empty($newPassword) && $newPassword === $confirmPassword) {
            $updates[] = "password = ?";
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        if (!empty($updates)) {
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $params[] = $userId;
            $db->query($sql, $params);
            $message = "อัปเดตโปรไฟล์สำเร็จ";
        }
    } else {
        $error = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
    }
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-user-edit"></i> โปรไฟล์</h1>
</div>

<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= $error ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">แก้ไขโปรไฟล์</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" value="<?= $_SESSION['admin_name'] ?? 'admin' ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" id="full_name" name="full_name">
                    </div>

                    <hr>

                    <h6>เปลี่ยนรหัสผ่าน</h6>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">ข้อมูลการเข้าสู่ระบบ</h6>
            </div>
            <div class="card-body">
                <p><strong>เข้าสู่ระบบล่าสุด:</strong><br><?= date('d/m/Y H:i:s') ?></p>
                <p><strong>สถานะ:</strong><br><span class="badge bg-success">ออนไลน์</span></p>
                <p><strong>บทบาท:</strong><br><span class="badge bg-danger">ผู้ดูแลระบบ</span></p>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">การตั้งค่าความปลอดภัย</h6>
            </div>
            <div class="card-body">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="twoFactorAuth" checked>
                    <label class="form-check-label" for="twoFactorAuth">
                        การยืนยันตัวตน 2 ขั้นตอน
                    </label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="loginNotifications" checked>
                    <label class="form-check-label" for="loginNotifications">
                        แจ้งเตือนการเข้าสู่ระบบ
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
