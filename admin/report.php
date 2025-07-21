<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Order.php';

requireLogin();

$pageTitle = 'รายงาน';
$activePage = 'reports';

$order = new Order($db);

// Default date range (last 30 days)
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Sales summary
$salesSummary = $db->fetchOne("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        AVG(total_amount) as avg_order_value,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
", [$startDate, $endDate]);

// Daily sales data
$dailySales = $db->fetchAll("
    SELECT 
        DATE(created_at) as date,
        SUM(total_amount) as sales,
        COUNT(*) as orders
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    AND status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY date
", [$startDate, $endDate]);

// Popular items
$popularItems = $db->fetchAll("
    SELECT 
        mi.name,
        mi.category,
        SUM(oi.quantity) as total_sold,
        SUM(oi.total_price) as total_revenue
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.status = 'completed'
    GROUP BY mi.id, mi.name, mi.category
    ORDER BY total_sold DESC
    LIMIT 10
", [$startDate, $endDate]);

// Hourly distribution
$hourlyData = $db->fetchAll("
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as orders,
        SUM(total_amount) as sales
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    AND status = 'completed'
    GROUP BY HOUR(created_at)
    ORDER BY hour
", [$startDate, $endDate]);

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-chart-bar"></i> รายงาน</h1>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-primary" onclick="exportReport()">
            <i class="fas fa-download"></i> Export PDF
        </button>
        <button type="button" class="btn btn-primary" onclick="location.reload()">
            <i class="fas fa-sync"></i> Refresh
        </button>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="start_date" class="form-label">วันที่เริ่มต้น</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $startDate ?>">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">วันที่สิ้นสุด</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $endDate ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> ดูรายงาน
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">ออเดอร์ทั้งหมด</div>
                        <div class="h5 mb-0 font-weight-bold"><?= number_format($salesSummary['total_orders']) ?></div>
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
                        <div class="text-xs font-weight-bold text-uppercase mb-1">ยอดขายรวม</div>
                        <div class="h5 mb-0 font-weight-bold"><?= formatCurrency($salesSummary['total_sales'] ?? 0) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x"></i>
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
                        <div class="text-xs font-weight-bold text-uppercase mb-1">ค่าเฉลี่ยต่อออเดอร์</div>
                        <div class="h5 mb-0 font-weight-bold"><?= formatCurrency($salesSummary['avg_order_value'] ?? 0) ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-line fa-2x"></i>
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
                        <div class="text-xs font-weight-bold text-uppercase mb-1">อัตราความสำเร็จ</div>
                        <div class="h5 mb-0 font-weight-bold">
                            <?= $salesSummary['total_orders'] > 0 ? number_format(($salesSummary['completed_orders'] / $salesSummary['total_orders']) * 100, 1) : 0 ?>%
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-percentage fa-2x"></i>
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
                <h6 class="m-0 font-weight-bold">ยอดขายรายวัน</h6>
            </div>
            <div class="card-body">
                <canvas id="dailySalesChart" style="height: 400px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">การขายตามช่วงเวลา</h6>
            </div>
            <div class="card-body">
                <canvas id="hourlyChart" style="height: 400px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Popular Items -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">เมนูขายดี (Top 10)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>อันดับ</th>
                        <th>ชื่อเมนู</th>
                        <th>หมวดหมู่</th>
                        <th>จำนวนที่ขาย</th>
                        <th>ยอดขาย</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($popularItems as $index => $item): ?>
                    <tr>
                        <td>
                            <span class="badge bg-<?= $index < 3 ? 'success' : 'info' ?> fs-6">
                                #<?= $index + 1 ?>
                            </span>
                        </td>
                        <td class="fw-bold"><?= htmlspecialchars($item['name']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($item['category']) ?></span></td>
                        <td><?= number_format($item['total_sold']) ?> จาน</td>
                        <td class="fw-bold"><?= formatCurrency($item['total_revenue']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Daily Sales Chart
const dailySalesCtx = document.getElementById('dailySalesChart').getContext('2d');
const dailySalesChart = new Chart(dailySalesCtx, {
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

// Hourly Distribution Chart
const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
const hourlyChart = new Chart(hourlyCtx, {
    type: 'bar',
    data: {
        labels: [<?= implode(',', array_map(function($hour) { return '"' . sprintf('%02d:00', $hour) . '"'; }, range(0, 23))) ?>],
        datasets: [{
            label: 'จำนวนออเดอร์',
            data: [<?php 
                $hourlyOrders = array_fill(0, 24, 0);
                foreach($hourlyData as $item) {
                    $hourlyOrders[$item['hour']] = $item['orders'];
                }
                echo implode(',', $hourlyOrders);
            ?>],
            backgroundColor: 'rgba(111, 66, 193, 0.8)',
            borderColor: 'rgb(111, 66, 193)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'จำนวนออเดอร์'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'ช่วงเวลา'
                }
            }
        }
    }
});

function exportReport() {
    const params = new URLSearchParams({
        start_date: '<?= $startDate ?>',
        end_date: '<?= $endDate ?>'
    });
    window.open(`export_report.php?${params.toString()}`, '_blank');
}
</script>

// admin/system_settings.php
<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$pageTitle = 'ตั้งค่าระบบ';
$activePage = 'settings';

if ($_POST) {
    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            $db->query("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
        }
    }
    $message = "บันทึกการตั้งค่าสำเร็จ";
}

$settings = $db->fetchAll("SELECT * FROM system_settings ORDER BY id");
$settingsArray = [];
foreach ($settings as $setting) {
    $settingsArray[$setting['setting_key']] = $setting['setting_value'];
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-cog"></i> ตั้งค่าระบบ</h1>
</div>

<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">
    <div class="row">
        <!-- Shop Information -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">ข้อมูลร้าน</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="shop_name" class="form-label">ชื่อร้าน</label>
                        <input type="text" class="form-control" id="shop_name" name="shop_name" 
                               value="<?= htmlspecialchars($settingsArray['shop_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="shop_phone" class="form-label">เบอร์โทรร้าน</label>
                        <input type="text" class="form-control" id="shop_phone" name="shop_phone" 
                               value="<?= htmlspecialchars($settingsArray['shop_phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="promptpay_id" class="form-label">หมายเลข PromptPay</label>
                        <input type="text" class="form-control" id="promptpay_id" name="promptpay_id" 
                               value="<?= htmlspecialchars($settingsArray['promptpay_id'] ?? '') ?>">
                        <div class="form-text">ใช้สำหรับสร้าง QR Code ชำระเงิน</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Queue Settings -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">การตั้งค่าคิว</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="queue_reset_daily" name="queue_reset_daily" 
                                   value="1" <?= ($settingsArray['queue_reset_daily'] ?? '0') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="queue_reset_daily">
                                รีเซ็ตหมายเลขคิวทุกวัน
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="max_queue_per_day" class="form-label">จำนวนคิวสูงสุดต่อวัน</label>
                        <input type="number" class="form-control" id="max_queue_per_day" name="max_queue_per_day" 
                               value="<?= htmlspecialchars($settingsArray['max_queue_per_day'] ?? '999') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="estimated_time_per_item" class="form-label">เวลาโดยประมาณต่อรายการ (นาที)</label>
                        <input type="number" class="form-control" id="estimated_time_per_item" name="estimated_time_per_item" 
                               value="<?= htmlspecialchars($settingsArray['estimated_time_per_item'] ?? '5') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="notification_before_queue" class="form-label">แจ้งเตือนก่อนถึงคิว (จำนวนคิว)</label>
                        <input type="number" class="form-control" id="notification_before_queue" name="notification_before_queue" 
                               value="<?= htmlspecialchars($settingsArray['notification_before_queue'] ?? '3') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Receipt Settings -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold">การตั้งค่าใบเสร็จ</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="receipt_footer_text" class="form-label">ข้อความท้ายใบเสร็จ</label>
                        <textarea class="form-control" id="receipt_footer_text" name="receipt_footer_text" rows="3"><?= htmlspecialchars($settingsArray['receipt_footer_text'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-end">
        <button type="submit" name="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> บันทึกการตั้งค่า
        </button>
    </div>
</form>