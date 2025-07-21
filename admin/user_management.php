
<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$pageTitle = 'จัดการผู้ใช้';
$activePage = 'users';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $db->query("INSERT INTO users (username, password, full_name, role, active) VALUES (?, ?, ?, ?, ?)", 
                    [$_POST['username'], $hashedPassword, $_POST['full_name'], $_POST['role'], isset($_POST['active']) ? 1 : 0]);
                $message = "เพิ่มผู้ใช้สำเร็จ";
                break;
            
            case 'edit':
                $sql = "UPDATE users SET username = ?, full_name = ?, role = ?, active = ?";
                $params = [$_POST['username'], $_POST['full_name'], $_POST['role'], isset($_POST['active']) ? 1 : 0];
                
                if (!empty($_POST['password'])) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $_POST['id'];
                
                $db->query($sql, $params);
                $message = "แก้ไขผู้ใช้สำเร็จ";
                break;
            
            case 'delete':
                $db->query("DELETE FROM users WHERE id = ?", [$_POST['id']]);
                $message = "ลบผู้ใช้สำเร็จ";
                break;
        }
    }
}

// Create users table if not exists
$db->query("CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'pos', 'kitchen') DEFAULT 'pos',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert default admin if no users exist
$userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
if ($userCount == 0) {
    $db->query("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)", 
        ['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Administrator', 'admin']);
}

$users = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC");

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-users-cog"></i> จัดการผู้ใช้</h1>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="fas fa-plus"></i> เพิ่มผู้ใช้ใหม่
    </button>
</div>

<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">รายการผู้ใช้ทั้งหมด</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="usersTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>ชื่อผู้ใช้</th>
                        <th>ชื่อ-นามสกุล</th>
                        <th>บทบาท</th>
                        <th>สถานะ</th>
                        <th>วันที่สร้าง</th>
                        <th>การกระทำ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
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
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มผู้ใช้ใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">บทบาท</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="pos">พนักงาน POS</option>
                            <option value="kitchen">พนักงานครัว</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                        <label class="form-check-label" for="active">
                            เปิดใช้งาน
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขผู้ใช้</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_id" name="id">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">รหัสผ่านใหม่</label>
                        <input type="password" class="form-control" id="edit_password" name="password">
                        <div class="form-text">เว้นว่างไว้หากไม่ต้องการเปลี่ยน</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_role" class="form-label">บทบาท</label>
                        <select class="form-select" id="edit_role" name="role" required>
                            <option value="pos">พนักงาน POS</option>
                            <option value="kitchen">พนักงานครัว</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="edit_active" name="active">
                        <label class="form-check-label" for="edit_active">
                            เปิดใช้งาน
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#usersTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json'
        },
        order: [[4, 'desc']],
        columnDefs: [
            { orderable: false, targets: [5] }
        ]
    });
});

function editUser(user) {
    $('#edit_id').val(user.id);
    $('#edit_username').val(user.username);
    $('#edit_full_name').val(user.full_name);
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
</script>

<?php
function getRoleColor($role) {
    $colors = [
        'admin' => 'danger',
        'pos' => 'primary',
        'kitchen' => 'success'
    ];
    return $colors[$role] ?? 'secondary';
}

function getRoleText($role) {
    $texts = [
        'admin' => 'ผู้ดูแลระบบ',
        'pos' => 'พนักงาน POS',
        'kitchen' => 'พนักงานครัว'
    ];
    return $texts[$role] ?? $role;
}
?>

// admin/queue_management.php
<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Order.php';

requireLogin();

$pageTitle = 'จัดการคิว';
$activePage = 'queue';

$order = new Order($db);

// Handle queue actions
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'call_queue':
            $db->query("UPDATE queue SET status = 'calling', called_at = CURRENT_TIMESTAMP WHERE id = ?", [$_POST['queue_id']]);
            $message = "เรียกคิวสำเร็จ";
            break;
        
        case 'serve_queue':
            $db->query("UPDATE queue SET status = 'served', served_at = CURRENT_TIMESTAMP WHERE id = ?", [$_POST['queue_id']]);
            $message = "บริการเสร็จสิ้น";
            break;
        
        case 'no_show':
            $db->query("UPDATE queue SET status = 'no_show' WHERE id = ?", [$_POST['queue_id']]);
            $message = "ทำเครื่องหมายไม่มาติดต่อ";
            break;
    }
}

// Get today's queue
$todayQueue = $db->fetchAll("
    SELECT q.*, o.customer_name, o.customer_phone, o.total_amount, o.status as order_status,
           COUNT(oi.id) as item_count
    FROM queue q
    JOIN orders o ON q.order_id = o.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE DATE(q.created_at) = CURDATE()
    GROUP BY q.id, o.id
    ORDER BY q.queue_number
");

// Current queue stats
$queueStats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_queue,
        COUNT(CASE WHEN status = 'waiting' THEN 1 END) as waiting,
        COUNT(CASE WHEN status = 'calling' THEN 1 END) as calling,
        COUNT(CASE WHEN status = 'served' THEN 1 END) as served,
        COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show
    FROM queue 
    WHERE DATE(created_at) = CURDATE()
");

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-users"></i> จัดการคิววันนี้</h1>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
            <i class="fas fa-sync"></i> Refresh
        </button>
        <button type="button" class="btn btn-success" id="voiceQueueBtn">
            <i class="fas fa-volume-up"></i> เรียกคิวด้วยเสียง
        </button>
    </div>
</div>

<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Queue Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <h3><?= $queueStats['total_queue'] ?></h3>
                <p class="mb-0">คิวทั้งหมด</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card warning">
            <div class="card-body text-center">
                <h3><?= $queueStats['waiting'] ?></h3>
                <p class="mb-0">กำลังรอ</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card info">
            <div class="card-body text-center">
                <h3><?= $queueStats['calling'] ?></h3>
                <p class="mb-0">กำลังเรียก</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card success">
            <div class="card-body text-center">
                <h3><?= $queueStats['served'] ?></h3>
                <p class="mb-0">เสร็จแล้ว</p>
            </div>
        </div>
    </div>
</div>

<!-- Current Calling Queue -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-bullhorn"></i> คิวที่กำลังเรียก</h5>
            </div>
            <div class="card-body">
                <div class="row" id="callingQueues">
                    <?php 
                    $callingQueues = array_filter($todayQueue, function($q) { return $q['status'] == 'calling'; });
                    if (empty($callingQueues)): 
                    ?>
                    <div class="col-12 text-center text-muted">
                        <i class="fas fa-info-circle"></i> ไม่มีคิวที่กำลังเรียก
                    </div>
                    <?php else: ?>
                    <?php foreach($callingQueues as $queue): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card border-warning">
                            <div class="card-body text-center">
                                <h2 class="text-warning">#<?= $queue['queue_number'] ?></h2>
                                <p class="mb-1 fw-bold"><?= htmlspecialchars($queue['customer_name']) ?></p>
                                <p class="mb-2 small text-muted"><?= $queue['item_count'] ?> รายการ</p>
                                <button class="btn btn-success btn-sm" onclick="serveQueue(<?= $queue['id'] ?>)">
                                    <i class="fas fa-check"></i> บริการแล้ว
                                </button>
                                <button class="btn btn-secondary btn-sm" onclick="noShowQueue(<?= $queue['id'] ?>)">
                                    <i class="fas fa-times"></i> ไม่มาติดต่อ
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Queue Table -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">รายการคิวทั้งหมด</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="queueTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>คิว</th>
                        <th>ลูกค้า</th>
                        <th>รายการ</th>
                        <th>ยอดรวม</th>
                        <th>สถานะคิว</th>
                        <th>สถานะออเดอร์</th>
                        <th>เวลา</th>
                        <th>การกระทำ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($todayQueue as $queue): ?>
                    <tr class="<?= getQueueRowClass($queue['status']) ?>">
                        <td>
                            <span class="badge bg-primary fs-6">#<?= $queue['queue_number'] ?></span>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($queue['customer_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($queue['customer_phone']) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= $queue['item_count'] ?> รายการ</span>
                        </td>
                        <td class="fw-bold"><?= formatCurrency($queue['total_amount']) ?></td>
                        <td>
                            <span class="badge bg-<?= getQueueStatusColor($queue['status']) ?>">
                                <?= getQueueStatusText($queue['status']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= getStatusColor($queue['order_status']) ?>">
                                <?= getStatusText($queue['order_status']) ?>
                            </span>
                        </td>
                        <td><?= formatDateTime($queue['created_at']) ?></td>
                        <td>
                            <?php if ($queue['status'] == 'waiting' && $queue['order_status'] == 'ready'): ?>
                            <button class="btn btn-sm btn-warning" onclick="callQueue(<?= $queue['id'] ?>, <?= $queue['queue_number'] ?>)">
                                <i class="fas fa-bullhorn"></i> เรียก
                            </button>
                            <?php elseif ($queue['status'] == 'calling'): ?>
                            <button class="btn btn-sm btn-success" onclick="serveQueue(<?= $queue['id'] ?>)">
                                <i class="fas fa-check"></i> เสร็จ
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="noShowQueue(<?= $queue['id'] ?>)">
                                <i class="fas fa-times"></i> ไม่มา
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#queueTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json'
        },
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [7] }
        ]
    });

    // Auto refresh every 10 seconds
    setInterval(function() {
        location.reload();
    }, 10000);
});

function callQueue(queueId, queueNumber) {
    if (confirm(`เรียกคิวหมายเลข ${queueNumber} หรือไม่?`)) {
        $.post('', {
            action: 'call_queue',
            queue_id: queueId
        }, function() {
            // Call voice announcement
            announceQueue(queueNumber);
            location.reload();
        });
    }
}

function serveQueue(queueId) {
    $.post('', {
        action: 'serve_queue',
        queue_id: queueId
    }, function() {
        location.reload();
    });
}

function noShowQueue(queueId) {
    $.post('', {
        action: 'no_show',
        queue_id: queueId
    }, function() {
        location.reload();
    });
}

function announceQueue(queueNumber) {
    // Use Web Speech API for voice announcement
    if ('speechSynthesis' in window) {
        const utterance = new SpeechSynthesisUtterance(`หมายเลขคิว ${queueNumber} ครับ`);
        utterance.lang = 'th-TH';
        utterance.rate = 0.8;
        speechSynthesis.speak(utterance);
    }
}

$('#voiceQueueBtn').click(function() {
    const callingQueues = <?= json_encode(array_column($callingQueues ?? [], 'queue_number')) ?>;
    if (callingQueues.length > 0) {
        callingQueues.forEach(queueNumber => {
            setTimeout(() => announceQueue(queueNumber), 500);
        });
    } else {
        alert('ไม่มีคิวที่กำลังเรียก');
    }
});
</script>

<?php
function getQueueStatusColor($status) {
    $colors = [
        'waiting' => 'warning',
        'calling' => 'info',
        'served' => 'success',
        'no_show' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

function getQueueStatusText($status) {
    $texts = [
        'waiting' => 'รอเรียก',
        'calling' => 'กำลังเรียก',
        'served' => 'บริการแล้ว',
        'no_show' => 'ไม่มาติดต่อ'
    ];
    return $texts[$status] ?? $status;
}

function getQueueRowClass($status) {
    $classes = [
        'calling' => 'table-warning',
        'served' => 'table-success',
        'no_show' => 'table-danger'
    ];
    return $classes[$status] ?? '';
}
?>
