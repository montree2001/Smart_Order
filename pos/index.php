<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Database.php';
require_once '../classes/Order.php';
require_once '../classes/Queue.php';

// ตรวจสอบสิทธิ์การเข้าถึง
checkAuth(['admin', 'staff']);

$db = new Database();
$order = new Order($db->getConnection());
$queue = new Queue($db->getConnection());

// ดึงข้อมูลสถิติวันนี้
$todayStats = $order->getTodayStats();
$recentOrders = $order->getRecentOrders(5);
$topMenus = $order->getTopMenus(4);
$queueStats = $queue->getQueueStats();

$pageTitle = "ระบบ POS";
$activePage = "pos";
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Smart Order Management</title>
    
    <!-- CSS -->
    <link href="<?= SITE_URL ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>assets/css/datatables.min.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>assets/css/pos.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="pos-body">
    <div class="pos-container">
        
        <!-- Header -->
        <?php include 'includes/pos_header.php'; ?>

        <!-- Navigation -->
        <?php include 'includes/pos_nav.php'; ?>

        <!-- Main Content -->
        <div class="pos-content">
            
            <!-- Dashboard Stats -->
            <div class="row mb-4">
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card primary">
                        <div class="stat-content">
                            <div class="stat-text">
                                <h6>ยอดขายวันนี้</h6>
                                <h3 id="todaySales">฿<?= number_format($todayStats['total_sales'] ?? 0) ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card success">
                        <div class="stat-content">
                            <div class="stat-text">
                                <h6>ออเดอร์วันนี้</h6>
                                <h3 id="todayOrders"><?= $todayStats['total_orders'] ?? 0 ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card warning">
                        <div class="stat-content">
                            <div class="stat-text">
                                <h6>คิวที่รอ</h6>
                                <h3 id="waitingQueue"><?= $queueStats['waiting'] ?? 0 ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 mb-3">
                    <div class="stat-card info">
                        <div class="stat-content">
                            <div class="stat-text">
                                <h6>คิวปัจจุบัน</h6>
                                <h3 id="currentQueueNum"><?= $queueStats['current'] ?? 'N/A' ?></h3>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-user"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders & Top Menus -->
            <div class="row">
                <div class="col-md-8">
                    <div class="pos-card">
                        <div class="card-header">
                            <h5><i class="fas fa-list"></i> ออเดอร์ล่าสุด</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="recentOrdersTable">
                                    <thead>
                                        <tr>
                                            <th>คิว</th>
                                            <th>ลูกค้า</th>
                                            <th>รายการ</th>
                                            <th>ยอดรวม</th>
                                            <th>สถานะ</th>
                                            <th>การจัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentOrders as $recentOrder): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($recentOrder['queue_number']) ?></strong></td>
                                            <td><?= htmlspecialchars($recentOrder['customer_name']) ?></td>
                                            <td><?= htmlspecialchars($recentOrder['items_summary']) ?></td>
                                            <td>฿<?= number_format($recentOrder['total_amount']) ?></td>
                                            <td>
                                                <span class="badge status-<?= $recentOrder['status'] ?>">
                                                    <?= getStatusText($recentOrder['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="order_detail.php?id=<?= $recentOrder['id'] ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="pos-card">
                        <div class="card-header success">
                            <h5><i class="fas fa-trophy"></i> เมนูขายดี</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($topMenus as $menu): ?>
                            <div class="top-menu-item">
                                <div class="menu-info">
                                    <strong><?= htmlspecialchars($menu['name']) ?></strong>
                                    <small class="text-muted">฿<?= number_format($menu['price']) ?></small>
                                </div>
                                <span class="badge bg-primary"><?= $menu['total_orders'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="pos-card mt-3">
                        <div class="card-header">
                            <h5><i class="fas fa-bolt"></i> เมนูด่วน</h5>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <a href="new_order.php" class="btn btn-primary btn-lg w-100 mb-2">
                                    <i class="fas fa-plus-circle"></i> สั่งซื้อใหม่
                                </a>
                                <a href="queue_display.php" class="btn btn-success btn-lg w-100 mb-2">
                                    <i class="fas fa-users"></i> จัดการคิว
                                </a>
                                <a href="order_list.php" class="btn btn-info btn-lg w-100">
                                    <i class="fas fa-list"></i> ดูออเดอร์
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= SITE_URL ?>assets/js/jquery.min.js"></script>
    <script src="<?= SITE_URL ?>assets/js/bootstrap.min.js"></script>
    <script src="<?= SITE_URL ?>assets/js/datatables.min.js"></script>
    <script src="<?= SITE_URL ?>assets/js/pos.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#recentOrdersTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/th.json'
                },
                pageLength: 5,
                searching: false,
                ordering: false,
                info: false,
                lengthChange: false
            });

            // Auto refresh every 30 seconds
            setInterval(function() {
                location.reload();
            }, 30000);

            updateDateTime();
            setInterval(updateDateTime, 1000);
        });

        function updateDateTime() {
            const now = new Date();
            const options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                timeZone: 'Asia/Bangkok'
            };
            document.getElementById('currentTime').textContent = 
                now.toLocaleDateString('th-TH', options);
        }
    </script>

</body>
</html>

<?php
function getStatusText($status) {
    $statusTexts = [
        'pending' => 'รอดำเนินการ',
        'preparing' => 'กำลังทำ',
        'ready' => 'พร้อมเสิร์ฟ',
        'completed' => 'เสร็จสิ้น',
        'cancelled' => 'ยกเลิก'
    ];
    return $statusTexts[$status] ?? $status;
}
?>