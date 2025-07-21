<?php
require_once '../config/config.php';
require_once '../classes/Order.php';
require_once '../classes/Menu.php';

requireLogin();

$pageTitle = 'Dashboard';
$activePage = 'dashboard';

$order = new Order($db);
$menu = new Menu($db);

// ดึงสถิติวันนี้
$todayStats = $order->getTodayStats();
$popularItems = $order->getPopularItems();
$recentOrders = $order->getAllOrders(10);

// ข้อมูลสำหรับกราฟ
$dailySales = $db->fetchAll("
    SELECT DATE(created_at) as date, SUM(total_amount) as sales, COUNT(*) as orders
    FROM orders 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY date
");

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
            <i class="fas fa-sync"></i> Refresh
        </button>
        <button type="button" class="btn btn-primary" onclick="exportDashboard()">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">ออเดอร์วันนี้</div>
                        <div class="h5 mb-0 font-weight-bold"><?= number_format($todayStats['total_orders']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-shopping-cart fa-2x"></i>
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
                        <div class="text-xs font-weight-bold text-uppercase mb-1">ยอดขายวันนี้</div>
                        <div class="h5 mb-0 font-weight-bold"><?= formatCurrency($todayStats['total_sales'] ?? 0) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x"></i>
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
                        <div class="text-xs font-weight-bold text-uppercase mb-1">รอดำเนินการ</div>
                        <div class="h5 mb-0 font-weight-bold"><?= number_format($todayStats['pending_orders']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x"></i>
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
                        <div class="text-xs font-weight-bold text-uppercase mb-1">เสร็จสิ้น</div>
                        <div class="h5 mb-0 font-weight-bold"><?= number_format($todayStats['completed_orders']) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-xl-8 col-lg-7">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">ยอดขาย 7 วันที่ผ่านมา</h6>
            </div>
            <div class="card-body">
                <canvas id="salesChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">เมนูขายดีวันนี้</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($popularItems)): ?>
                    <?php foreach($popularItems as $item): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($item['name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($item['category']) ?></small>
                        </div>
                        <span class="badge bg-primary"><?= $item['total_sold'] ?> จาน</span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-utensils fa-2x mb-2"></i>
                        <p>ยังไม่มีข้อมูลเมนูขายดีในวันนี้</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">ออเดอร์ล่าสุด</h6>
        <a href="order_management.php" class="btn btn-sm btn-primary">ดูทั้งหมด</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>คิว</th>
                        <th>ลูกค้า</th>
                        <th>ยอดรวม</th>
                        <th>สถานะ</th>
                        <th>เวลา</th>
                        <th>การกระทำ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach($recentOrders as $orderItem): ?>
                        <tr>
                            <td><span class="badge bg-info">#<?= $orderItem['queue_number'] ?></span></td>
                            <td><?= htmlspecialchars($orderItem['customer_name']) ?></td>
                            <td><?= formatCurrency($orderItem['total_amount']) ?></td>
                            <td>
                                <span class="badge bg-<?= getStatusColor($orderItem['status']) ?>">
                                    <?= getStatusText($orderItem['status']) ?>
                                </span>
                            </td>
                            <td><?= formatDateTime($orderItem['created_at']) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewOrderDetail(<?= $orderItem['id'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                ยังไม่มีออเดอร์ในระบบ
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Sales Chart
const ctx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [<?= implode(',', array_map(function($item) { return '"' . date('d/m', strtotime($item['date'])) . '"'; }, $dailySales)) ?>],
        datasets: [{
            label: 'ยอดขาย (บาท)',
            data: [<?= implode(',', array_column($dailySales, 'sales')) ?>],
            borderColor: 'rgb(111, 66, 193)',
            backgroundColor: 'rgba(111, 66, 193, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'จำนวนออเดอร์',
            data: [<?= implode(',', array_column($dailySales, 'orders')) ?>],
            borderColor: 'rgb(32, 201, 151)',
            backgroundColor: 'rgba(32, 201, 151, 0.1)',
            tension: 0.4,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: false
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'ยอดขาย (บาท)'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'จำนวนออเดอร์'
                },
                grid: {
                    drawOnChartArea: false,
                },
            }
        }
    }
});

function viewOrderDetail(orderId) {
    window.location.href = `order_management.php#order_${orderId}`;
}

function exportDashboard() {
    window.open('export_dashboard.php', '_blank');
}

// Auto refresh every 30 seconds
setInterval(function() {
    if (!document.hidden) {
        location.reload();
    }
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>