<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Order.php';
require_once '../classes/VoiceSystem.php';

requireLogin();

$pageTitle = 'จัดการคิว';
$activePage = 'queue';

$order = new Order($db);
$voice = new VoiceSystem($db);

// Handle queue actions
if ($_POST && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'call_queue':
            $queueId = $_POST['queue_id'];
            $queueNumber = $_POST['queue_number'];
            $customerName = $_POST['customer_name'] ?? null;
            
            $db->query("UPDATE queue SET status = 'calling', called_at = CURRENT_TIMESTAMP WHERE id = ?", [$queueId]);
            
            // Generate voice announcement
            $announcement = $voice->announceQueue($queueNumber, $customerName);
            
            $message = "เรียกคิว #{$queueNumber} สำเร็จ";
            break;
        
        case 'serve_queue':
            $queueId = $_POST['queue_id'];
            $db->query("UPDATE queue SET status = 'served', served_at = CURRENT_TIMESTAMP WHERE id = ?", [$queueId]);
            $db->query("UPDATE orders SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = (SELECT order_id FROM queue WHERE id = ?)", [$queueId]);
            $message = "บริการเสร็จสิ้น";
            break;
        
        case 'no_show':
            $queueId = $_POST['queue_id'];
            $db->query("UPDATE queue SET status = 'no_show' WHERE id = ?", [$queueId]);
            $message = "ทำเครื่องหมายไม่มาติดต่อ";
            break;
            
        case 'reset_queue':
            $queueId = $_POST['queue_id'];
            $db->query("UPDATE queue SET status = 'waiting', called_at = NULL WHERE id = ?", [$queueId]);
            $message = "รีเซ็ตคิวเรียบร้อย";
            break;
            
        case 'call_all_ready':
            $readyOrders = $db->fetchAll("
                SELECT q.id, q.queue_number, o.customer_name 
                FROM queue q 
                JOIN orders o ON q.order_id = o.id 
                WHERE o.status = 'ready' AND q.status = 'waiting'
                AND DATE(q.created_at) = CURDATE()
            ");
            
            foreach ($readyOrders as $queue) {
                $db->query("UPDATE queue SET status = 'calling', called_at = CURRENT_TIMESTAMP WHERE id = ?", [$queue['id']]);
                $voice->announceQueue($queue['queue_number'], $queue['customer_name']);
            }
            
            $message = "เรียกคิวพร้อมเสิร์ฟทั้งหมดแล้ว (" . count($readyOrders) . " คิว)";
            break;
    }
}

// Get today's queue with detailed information
$todayQueue = $db->fetchAll("
    SELECT 
        q.*,
        o.customer_name,
        o.customer_phone,
        o.total_amount,
        o.status as order_status,
        o.payment_method,
        o.payment_status,
        o.notes,
        COUNT(oi.id) as item_count,
        GROUP_CONCAT(CONCAT(mi.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items_summary
    FROM queue q
    JOIN orders o ON q.order_id = o.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
    WHERE DATE(q.created_at) = CURDATE()
    GROUP BY q.id, o.id
    ORDER BY q.queue_number
");

// Current queue stats
$queueStats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_queue,
        COUNT(CASE WHEN q.status = 'waiting' THEN 1 END) as waiting,
        COUNT(CASE WHEN q.status = 'calling' THEN 1 END) as calling,
        COUNT(CASE WHEN q.status = 'served' THEN 1 END) as served,
        COUNT(CASE WHEN q.status = 'no_show' THEN 1 END) as no_show,
        COUNT(CASE WHEN o.status = 'ready' AND q.status = 'waiting' THEN 1 END) as ready_to_call,
        AVG(CASE WHEN q.status = 'served' AND q.called_at IS NOT NULL AND q.served_at IS NOT NULL 
                 THEN TIMESTAMPDIFF(MINUTE, q.called_at, q.served_at) END) as avg_serve_time
    FROM queue q 
    JOIN orders o ON q.order_id = o.id
    WHERE DATE(q.created_at) = CURDATE()
");

// Get orders by status for quick stats
$orderStats = $db->fetchOne("
    SELECT 
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed,
        COUNT(CASE WHEN status = 'preparing' THEN 1 END) as preparing,
        COUNT(CASE WHEN status = 'ready' THEN 1 END) as ready,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled
    FROM orders 
    WHERE DATE(created_at) = CURDATE()
");

// Get next queue number
$nextQueue = $db->fetchOne("
    SELECT COALESCE(MAX(queue_number), 0) + 1 as next_number
    FROM queue 
    WHERE DATE(created_at) = CURDATE()
");

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-users"></i> จัดการคิววันนี้</h1>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-info" onclick="location.reload()">
            <i class="fas fa-sync"></i> Refresh
        </button>
        <button type="button" class="btn btn-success" id="voiceQueueBtn">
            <i class="fas fa-volume-up"></i> เรียกคิวด้วยเสียง
        </button>
        <button type="button" class="btn btn-warning" onclick="callAllReady()">
            <i class="fas fa-bullhorn"></i> เรียกคิวพร้อมทั้งหมด
        </button>
    </div>
</div>

<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Queue Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-list-ol fa-2x mb-2"></i>
                <h4><?= $queueStats['total_queue'] ?></h4>
                <p class="mb-0 small">คิวทั้งหมด</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card stats-card warning">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x mb-2"></i>
                <h4><?= $queueStats['waiting'] ?></h4>
                <p class="mb-0 small">กำลังรอ</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card stats-card info">
            <div class="card-body text-center">
                <i class="fas fa-bell fa-2x mb-2"></i>
                <h4><?= $queueStats['calling'] ?></h4>
                <p class="mb-0 small">กำลังเรียก</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card stats-card success">
            <div class="card-body text-center">
                <i class="fas fa-check fa-2x mb-2"></i>
                <h4><?= $queueStats['served'] ?></h4>
                <p class="mb-0 small">เสร็จแล้ว</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card" style="background: linear-gradient(135deg, #dc3545, #e74c3c); color: white;">
            <div class="card-body text-center">
                <i class="fas fa-user-times fa-2x mb-2"></i>
                <h4><?= $queueStats['no_show'] ?></h4>
                <p class="mb-0 small">ไม่มา</p>
            </div>
        </div>
    </div>
    
    <div class="col-xl-2 col-md-4 col-6 mb-3">
        <div class="card" style="background: linear-gradient(135deg, #ff6b35, #f7931e); color: white;">
            <div class="card-body text-center">
                <i class="fas fa-utensils fa-2x mb-2"></i>
                <h4><?= $queueStats['ready_to_call'] ?></h4>
                <p class="mb-0 small">พร้อมเรียก</p>
            </div>
        </div>
    </div>
</div>

<!-- Order Status Summary -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-pie"></i> สรุปสถานะออเดอร์วันนี้</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-2 col-4 mb-2">
                        <div class="border rounded p-2">
                            <h5 class="text-warning"><?= $orderStats['pending'] ?></h5>
                            <small>รอยืนยัน</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-4 mb-2">
                        <div class="border rounded p-2">
                            <h5 class="text-info"><?= $orderStats['confirmed'] ?></h5>
                            <small>ยืนยันแล้ว</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-4 mb-2">
                        <div class="border rounded p-2">
                            <h5 class="text-warning"><?= $orderStats['preparing'] ?></h5>
                            <small>กำลังทำ</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-4 mb-2">
                        <div class="border rounded p-2">
                            <h5 class="text-success"><?= $orderStats['ready'] ?></h5>
                            <small>พร้อมเสิร์ฟ</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-4 mb-2">
                        <div class="border rounded p-2">
                            <h5 class="text-success"><?= $orderStats['completed'] ?></h5>
                            <small>เสร็จสิ้น</small>
                        </div>
                    </div>
                    <div class="col-md-2 col-4 mb-2">
                        <div class="border rounded p-2">
                            <h5 class="text-danger"><?= $orderStats['cancelled'] ?></h5>
                            <small>ยกเลิก</small>
                        </div>
                    </div>
                </div>
                
                <?php if ($queueStats['avg_serve_time']): ?>
                <div class="mt-3 text-center">
                    <span class="badge bg-info fs-6">
                        <i class="fas fa-clock"></i> เวลาเสิร์ฟเฉลี่ย: <?= round($queueStats['avg_serve_time'], 1) ?> นาที
                    </span>
                    <span class="badge bg-secondary fs-6 ms-2">
                        <i class="fas fa-sort-numeric-up"></i> คิวถัดไป: #<?= $nextQueue['next_number'] ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Current Calling Queue -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-bullhorn"></i> คิวที่กำลังเรียก
                    <span class="badge bg-dark ms-2"><?= $queueStats['calling'] ?> คิว</span>
                </h5>
            </div>
            <div class="card-body">
                <div class="row" id="callingQueues">
                    <?php 
                    $callingQueues = array_filter($todayQueue, function($q) { return $q['status'] == 'calling'; });
                    if (empty($callingQueues)): 
                    ?>
                    <div class="col-12 text-center text-muted py-4">
                        <i class="fas fa-info-circle fa-2x mb-2"></i>
                        <p class="mb-0">ไม่มีคิวที่กำลังเรียก</p>
                    </div>
                    <?php else: ?>
                    <?php foreach($callingQueues as $queue): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                        <div class="card border-warning h-100">
                            <div class="card-body text-center">
                                <div class="display-6 text-warning mb-2">#<?= $queue['queue_number'] ?></div>
                                <h6 class="card-title"><?= htmlspecialchars($queue['customer_name']) ?></h6>
                                <p class="text-muted small mb-2">
                                    <?= $queue['item_count'] ?> รายการ • <?= formatCurrency($queue['total_amount']) ?>
                                </p>
                                <p class="text-muted small mb-3">
                                    <i class="fas fa-clock"></i> เรียกเมื่อ: <?= date('H:i:s', strtotime($queue['called_at'])) ?>
                                </p>
                                
                                <div class="btn-group w-100" role="group">
                                    <button class="btn btn-success btn-sm" onclick="serveQueue(<?= $queue['id'] ?>)" data-bs-toggle="tooltip" title="บริการเสร็จแล้ว">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="announceQueue(<?= $queue['queue_number'] ?>, '<?= htmlspecialchars($queue['customer_name']) ?>')" data-bs-toggle="tooltip" title="เรียกซ้ำ">
                                        <i class="fas fa-volume-up"></i>
                                    </button>
                                    <button class="btn btn-secondary btn-sm" onclick="noShowQueue(<?= $queue['id'] ?>)" data-bs-toggle="tooltip" title="ไม่มาติดต่อ">
                                        <i class="fas fa-user-times"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm" onclick="resetQueue(<?= $queue['id'] ?>)" data-bs-toggle="tooltip" title="รีเซ็ตคิว">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </div>
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

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-bolt"></i> การดำเนินการด่วน</h6>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success" onclick="callReadyOrders()">
                        <i class="fas fa-bullhorn"></i> เรียกคิวพร้อมเสิร์ฟทั้งหมด (<?= $queueStats['ready_to_call'] ?>)
                    </button>
                    <button class="btn btn-info" onclick="playTestVoice()">
                        <i class="fas fa-volume-up"></i> ทดสอบเสียง
                    </button>
                    <button class="btn btn-warning" onclick="showQueueSummary()">
                        <i class="fas fa-chart-bar"></i> สรุปคิว
                    </button>
                    <button class="btn btn-secondary" onclick="printQueueList()">
                        <i class="fas fa-print"></i> พิมพ์รายการคิว
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Queue Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold"><i class="fas fa-list"></i> รายการคิวทั้งหมด</h6>
        <div class="d-flex gap-2">
            <!-- Status Filter -->
            <select class="form-select form-select-sm" id="statusFilter" style="min-width: 120px;">
                <option value="">ทุกสถานะ</option>
                <option value="waiting">รอเรียก</option>
                <option value="calling">กำลังเรียก</option>
                <option value="served">เสร็จแล้ว</option>
                <option value="no_show">ไม่มา</option>
            </select>
            
            <!-- Order Status Filter -->
            <select class="form-select form-select-sm" id="orderStatusFilter" style="min-width: 120px;">
                <option value="">ทุกออเดอร์</option>
                <option value="pending">รอยืนยัน</option>
                <option value="confirmed">ยืนยันแล้ว</option>
                <option value="preparing">กำลังทำ</option>
                <option value="ready">พร้อมเสิร์ฟ</option>
                <option value="completed">เสร็จสิ้น</option>
                <option value="cancelled">ยกเลิก</option>
            </select>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="queueTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>คิว</th>
                        <th>ลูกค้า</th>
                        <th>รายการอาหาร</th>
                        <th>ยอดรวม</th>
                        <th>สถานะคิว</th>
                        <th>สถานะออเดอร์</th>
                        <th>เวลา</th>
                        <th>การกระทำ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($todayQueue as $queue): ?>
                    <tr class="<?= getQueueRowClass($queue['status']) ?>" data-queue-status="<?= $queue['status'] ?>" data-order-status="<?= $queue['order_status'] ?>">
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary fs-6 me-2">#<?= $queue['queue_number'] ?></span>
                                <?php if ($queue['status'] == 'calling'): ?>
                                <span class="badge bg-warning">
                                    <i class="fas fa-bell fa-beat"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div>
                                <div class="fw-bold"><?= htmlspecialchars($queue['customer_name']) ?></div>
                                <small class="text-muted">
                                    <i class="fas fa-phone"></i> <?= htmlspecialchars($queue['customer_phone']) ?>
                                </small>
                            </div>
                        </td>
                        <td>
                            <div>
                                <span class="badge bg-secondary me-1"><?= $queue['item_count'] ?> รายการ</span>
                                <?php if ($queue['items_summary']): ?>
                                <button class="btn btn-link btn-sm p-0" onclick="showOrderItems('<?= htmlspecialchars($queue['items_summary']) ?>')" data-bs-toggle="tooltip" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($queue['payment_method'] == 'qr_payment'): ?>
                                <span class="badge bg-info">QR</span>
                                <?php elseif ($queue['payment_method'] == 'card'): ?>
                                <span class="badge bg-warning">Card</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold"><?= formatCurrency($queue['total_amount']) ?></div>
                            <small class="text-muted">
                                <span class="badge bg-<?= $queue['payment_status'] == 'paid' ? 'success' : 'warning' ?>">
                                    <?= $queue['payment_status'] == 'paid' ? 'ชำระแล้ว' : 'รอชำระ' ?>
                                </span>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-<?= getQueueStatusColor($queue['status']) ?> position-relative">
                                <?= getQueueStatusText($queue['status']) ?>
                                <?php if ($queue['status'] == 'calling'): ?>
                                <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle">
                                    <span class="visually-hidden">เรียกคิว</span>
                                </span>
                                <?php endif; ?>
                            </span>
                            <?php if ($queue['called_at']): ?>
                            <br><small class="text-muted">เรียกเมื่อ: <?= date('H:i', strtotime($queue['called_at'])) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= getStatusColor($queue['order_status']) ?>">
                                <?= getStatusText($queue['order_status']) ?>
                            </span>
                        </td>
                        <td>
                            <div><?= formatDateTime($queue['created_at']) ?></div>
                            <?php if ($queue['estimated_time'] > 0): ?>
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> ~<?= $queue['estimated_time'] ?> นาที
                            </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group-vertical btn-group-sm" role="group">
                                <?php if ($queue['status'] == 'waiting' && $queue['order_status'] == 'ready'): ?>
                                <button class="btn btn-warning btn-sm" onclick="callQueue(<?= $queue['id'] ?>, <?= $queue['queue_number'] ?>, '<?= htmlspecialchars($queue['customer_name']) ?>')" data-bs-toggle="tooltip" title="เรียกคิว">
                                    <i class="fas fa-bullhorn"></i>
                                </button>
                                <?php elseif ($queue['status'] == 'calling'): ?>
                                <button class="btn btn-success btn-sm" onclick="serveQueue(<?= $queue['id'] ?>)" data-bs-toggle="tooltip" title="บริการเสร็จแล้ว">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="announceQueue(<?= $queue['queue_number'] ?>, '<?= htmlspecialchars($queue['customer_name']) ?>')" data-bs-toggle="tooltip" title="เรียกซ้ำ">
                                    <i class="fas fa-volume-up"></i>
                                </button>
                                <button class="btn btn-secondary btn-sm" onclick="noShowQueue(<?= $queue['id'] ?>)" data-bs-toggle="tooltip" title="ไม่มาติดต่อ">
                                    <i class="fas fa-user-times"></i>
                                </button>
                                <?php elseif ($queue['status'] == 'no_show'): ?>
                                <button class="btn btn-outline-secondary btn-sm" onclick="resetQueue(<?= $queue['id'] ?>)" data-bs-toggle="tooltip" title="รีเซ็ตคิว">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline-primary btn-sm" onclick="viewOrderDetail(<?= $queue['order_id'] ?>)" data-bs-toggle="tooltip" title="ดูรายละเอียดออเดอร์">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Order Items Modal -->
<div class="modal fade" id="orderItemsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-utensils"></i> รายการอาหาร</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderItemsContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-receipt"></i> รายละเอียดออเดอร์</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-primary" onclick="printCurrentOrder()">
                    <i class="fas fa-print"></i> พิมพ์ใบเสร็จ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Queue Summary Modal -->
<div class="modal fade" id="queueSummaryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-chart-bar"></i> สรุปคิววันนี้</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <canvas id="queueSummaryChart" style="height: 400px;"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    const table = $('#queueTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json'
        },
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: [7] },
            { searchable: true, targets: [0, 1, 2] }
        ],
        pageLength: 25,
        responsive: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // Filter functionality
    $('#statusFilter, #orderStatusFilter').on('change', function() {
        table.draw();
    });

    // Custom search
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const statusFilter = $('#statusFilter').val();
        const orderStatusFilter = $('#orderStatusFilter').val();
        
        const row = table.row(dataIndex).node();
        const queueStatus = $(row).data('queue-status');
        const orderStatus = $(row).data('order-status');
        
        if (statusFilter && queueStatus !== statusFilter) return false;
        if (orderStatusFilter && orderStatus !== orderStatusFilter) return false;
        
        return true;
    });

    // Auto refresh every 15 seconds
    setInterval(function() {
        if (!document.hidden) {
            location.reload();
        }
    }, 15000);

    // Initialize tooltips
    initTooltips();
});

// Queue Management Functions
function callQueue(queueId, queueNumber, customerName) {
    if (confirm(`เรียกคิวหมายเลข ${queueNumber} (${customerName}) หรือไม่?`)) {
        submitQueueAction('call_queue', {
            queue_id: queueId,
            queue_number: queueNumber,
            customer_name: customerName
        });
    }
}

function serveQueue(queueId) {
    submitQueueAction('serve_queue', { queue_id: queueId });
}

function noShowQueue(queueId) {
    if (confirm('ยืนยันการทำเครื่องหมาย "ไม่มาติดต่อ" หรือไม่?')) {
        submitQueueAction('no_show', { queue_id: queueId });
    }
}

function resetQueue(queueId) {
    if (confirm('ยืนยันการรีเซ็ตคิวหรือไม่?')) {
        submitQueueAction('reset_queue', { queue_id: queueId });
    }
}

function callAllReady() {
    if (confirm('เรียกคิวพร้อมเสิร์ฟทั้งหมดหรือไม่?')) {
        submitQueueAction('call_all_ready', {});
    }
}

function submitQueueAction(action, data) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `<input type="hidden" name="action" value="${action}">`;
    
    for (const [key, value] of Object.entries(data)) {
        form.innerHTML += `<input type="hidden" name="${key}" value="${value}">`;
    }
    
    document.body.appendChild(form);
    form.submit();
}

// Voice Functions
function announceQueue(queueNumber, customerName = null) {
    if ('speechSynthesis' in window) {
        let message = `หมายเลขคิว ${queueNumber}`;
        if (customerName) {
            message += ` คุณ${customerName}`;
        }
        message += ' ขอเชิญมารับออเดอร์ได้ครับ';
        
        const utterance = new SpeechSynthesisUtterance(message);
        utterance.lang = 'th-TH';
        utterance.rate = 0.8;
        utterance.volume = 1.0;
        speechSynthesis.speak(utterance);
    } else {
        alert('เบราว์เซอร์ไม่รองรับการพูด');
    }
}

function playTestVoice() {
    announceQueue('999', 'ทดสอบระบบ');
}

$('#voiceQueueBtn').click(function() {
    const callingQueues = <?= json_encode(array_map(function($q) { 
        return ['number' => $q['queue_number'], 'name' => $q['customer_name']]; 
    }, array_filter($todayQueue, function($q) { return $q['status'] == 'calling'; }))) ?>;
    
    if (callingQueues.length > 0) {
        callingQueues.forEach((queue, index) => {
            setTimeout(() => {
                announceQueue(queue.number, queue.name);
            }, index * 2000); // 2 seconds delay between announcements
        });
    } else {
        alert('ไม่มีคิวที่กำลังเรียก');
    }
});

// Modal Functions
function showOrderItems(itemsSummary) {
    const items = itemsSummary.split(', ');
    let content = '<ul class="list-group list-group-flush">';
    
    items.forEach(item => {
        content += `<li class="list-group-item">${item}</li>`;
    });
    
    content += '</ul>';
    
    $('#orderItemsContent').html(content);
    $('#orderItemsModal').modal('show');
}

function viewOrderDetail(orderId) {
    $('#orderDetailContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</div>');
    $('#orderDetailModal').modal('show');
    
    // Load order details via AJAX
    $.get('../admin/api/order_detail.php', {id: orderId}, function(data) {
        $('#orderDetailContent').html(data);
    }).fail(function() {
        $('#orderDetailContent').html('<div class="alert alert-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>');
    });
}

function showQueueSummary() {
    $('#queueSummaryModal').modal('show');
    
    // Create chart
    const ctx = document.getElementById('queueSummaryChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['รอเรียก', 'กำลังเรียก', 'เสร็จแล้ว', 'ไม่มา'],
            datasets: [{
                data: [
                    <?= $queueStats['waiting'] ?>,
                    <?= $queueStats['calling'] ?>,
                    <?= $queueStats['served'] ?>,
                    <?= $queueStats['no_show'] ?>
                ],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(13, 202, 240, 0.8)',
                    'rgba(25, 135, 84, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 193, 7, 1)',
                    'rgba(13, 202, 240, 1)',
                    'rgba(25, 135, 84, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: true,
                    text: 'สัดส่วนสถานะคิววันนี้'
                }
            }
        }
    });
}

function printQueueList() {
    window.open('print_queue_list.php', '_blank');
}

function printCurrentOrder() {
    // This would be implemented based on the order detail modal content
    window.print();
}

// Utility Functions
function callReadyOrders() {
    callAllReady();
}

function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Alt + R = Refresh
    if (e.altKey && e.key === 'r') {
        e.preventDefault();
        location.reload();
    }
    
    // Alt + V = Voice test
    if (e.altKey && e.key === 'v') {
        e.preventDefault();
        playTestVoice();
    }
    
    // Alt + C = Call all ready
    if (e.altKey && e.key === 'c') {
        e.preventDefault();
        callAllReady();
    }
});

// Page visibility API for auto-refresh control
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Page became visible, refresh if it's been more than 30 seconds
        const lastRefresh = sessionStorage.getItem('lastRefresh');
        const now = Date.now();
        
        if (!lastRefresh || (now - parseInt(lastRefresh)) > 30000) {
            location.reload();
        }
    }
});

// Store last refresh time
sessionStorage.setItem('lastRefresh', Date.now());
</script>

<?php
// Helper Functions
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

function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'confirmed' => 'info',
        'preparing' => 'warning',
        'ready' => 'success',
        'completed' => 'success',
        'cancelled' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

function getStatusText($status) {
    $texts = [
        'pending' => 'รอยืนยัน',
        'confirmed' => 'ยืนยันแล้ว',
        'preparing' => 'กำลังทำ',
        'ready' => 'พร้อมเสิร์ฟ',
        'completed' => 'เสร็จสิ้น',
        'cancelled' => 'ยกเลิก'
    ];
    return $texts[$status] ?? $status;
}

include 'includes/footer.php';
?>