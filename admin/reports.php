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
$compareDate = $_GET['compare_date'] ?? date('Y-m-d', strtotime('-60 days'));

// Handle export requests
if (isset($_GET['export'])) {
    switch ($_GET['export']) {
        case 'pdf':
            header('Location: export_report.php?' . http_build_query($_GET));
            exit;
        case 'excel':
            header('Location: export_excel.php?' . http_build_query($_GET));
            exit;
    }
}

// Sales summary
$salesSummary = $db->fetchOne("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        AVG(total_amount) as avg_order_value,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
        COUNT(DISTINCT customer_phone) as unique_customers,
        MIN(total_amount) as min_order,
        MAX(total_amount) as max_order
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
", [$startDate, $endDate]);

// Comparison data (previous period)
$comparisonData = $db->fetchOne("
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        AVG(total_amount) as avg_order_value,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
", [$compareDate, date('Y-m-d', strtotime($startDate . ' -1 day'))]);

// Daily sales data
$dailySales = $db->fetchAll("
    SELECT 
        DATE(created_at) as date,
        SUM(total_amount) as sales,
        COUNT(*) as orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
        AVG(total_amount) as avg_amount
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    AND status IN ('completed', 'cancelled')
    GROUP BY DATE(created_at)
    ORDER BY date
", [$startDate, $endDate]);

// Popular items
$popularItems = $db->fetchAll("
    SELECT 
        mi.name,
        mi.category,
        mi.price,
        SUM(oi.quantity) as total_sold,
        SUM(oi.total_price) as total_revenue,
        COUNT(DISTINCT oi.order_id) as order_count,
        AVG(oi.quantity) as avg_quantity
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.status = 'completed'
    GROUP BY mi.id, mi.name, mi.category, mi.price
    ORDER BY total_sold DESC
    LIMIT 20
", [$startDate, $endDate]);

// Hourly distribution
$hourlyData = $db->fetchAll("
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as orders,
        SUM(total_amount) as sales,
        AVG(total_amount) as avg_sale
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    AND status = 'completed'
    GROUP BY HOUR(created_at)
    ORDER BY hour
", [$startDate, $endDate]);

// Payment method analysis
$paymentMethods = $db->fetchAll("
    SELECT 
        payment_method,
        COUNT(*) as order_count,
        SUM(total_amount) as total_amount,
        AVG(total_amount) as avg_amount,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'completed')), 2) as percentage
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    AND status = 'completed'
    GROUP BY payment_method
    ORDER BY order_count DESC
", [$startDate, $endDate, $startDate, $endDate]);

// Queue analysis
$queueStats = $db->fetchOne("
    SELECT 
        COUNT(*) as total_queues,
        AVG(CASE WHEN served_at IS NOT NULL AND called_at IS NOT NULL 
                 THEN TIMESTAMPDIFF(MINUTE, called_at, served_at) END) as avg_serve_time,
        COUNT(CASE WHEN status = 'served' THEN 1 END) as served_queues,
        COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show_queues,
        MAX(queue_number) as max_queue_number
    FROM queue q
    JOIN orders o ON q.order_id = o.id
    WHERE DATE(q.created_at) BETWEEN ? AND ?
", [$startDate, $endDate]);

// Customer analysis
$customerStats = $db->fetchAll("
    SELECT 
        customer_phone,
        customer_name,
        COUNT(*) as order_count,
        SUM(total_amount) as total_spent,
        AVG(total_amount) as avg_spent,
        MAX(created_at) as last_order,
        MIN(created_at) as first_order
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ?
    AND status = 'completed'
    GROUP BY customer_phone, customer_name
    HAVING order_count > 1
    ORDER BY total_spent DESC
    LIMIT 20
", [$startDate, $endDate]);

// Category performance
$categoryStats = $db->fetchAll("
    SELECT 
        mi.category,
        COUNT(DISTINCT mi.id) as item_count,
        SUM(oi.quantity) as total_sold,
        SUM(oi.total_price) as total_revenue,
        AVG(oi.total_price) as avg_revenue,
        COUNT(DISTINCT oi.order_id) as order_count
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    AND o.status = 'completed'
    GROUP BY mi.category
    ORDER BY total_revenue DESC
", [$startDate, $endDate]);

// Calculate growth rates
function calculateGrowth($current, $previous) {
    if ($previous == 0) return $current > 0 ? 100 : 0;
    return round((($current - $previous) / $previous) * 100, 1);
}

$salesGrowth = calculateGrowth($salesSummary['total_sales'] ?? 0, $comparisonData['total_sales'] ?? 0);
$ordersGrowth = calculateGrowth($salesSummary['total_orders'] ?? 0, $comparisonData['total_orders'] ?? 0);
$avgOrderGrowth = calculateGrowth($salesSummary['avg_order_value'] ?? 0, $comparisonData['avg_order_value'] ?? 0);

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-chart-bar"></i> รายงาน</h1>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-primary" onclick="exportReport('pdf')">
            <i class="fas fa-file-pdf"></i> Export PDF
        </button>
        <button type="button" class="btn btn-outline-success" onclick="exportReport('excel')">
            <i class="fas fa-file-excel"></i> Export Excel
        </button>
        <button type="button" class="btn btn-primary" onclick="location.reload()">
            <i class="fas fa-sync"></i> Refresh
        </button>
    </div>
</div>

<!-- Date Range Filter -->
<div class="card mb-4">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold"><i class="fas fa-calendar"></i> เลือกช่วงเวลา</h6>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="start_date" class="form-label">วันที่เริ่มต้น</label>
                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $startDate ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">วันที่สิ้นสุด</label>
                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $endDate ?>">
            </div>
            <div class="col-md-3">
                <label for="compare_date" class="form-label">เปรียบเทียบจาก</label>
                <input type="date" class="form-control" id="compare_date" name="compare_date" value="<?= $compareDate ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> ดูรายงาน
                </button>
            </div>
        </form>
        
        <!-- Quick Date Ranges -->
        <div class="mt-3">
            <span class="me-2">ช่วงเวลาด่วน:</span>
            <button class="btn btn-outline-secondary btn-sm me-1" onclick="setDateRange('today')">วันนี้</button>
            <button class="btn btn-outline-secondary btn-sm me-1" onclick="setDateRange('yesterday')">เมื่อวาน</button>
            <button class="btn btn-outline-secondary btn-sm me-1" onclick="setDateRange('week')">7 วันที่แล้ว</button>
            <button class="btn btn-outline-secondary btn-sm me-1" onclick="setDateRange('month')">30 วันที่แล้ว</button>
            <button class="btn btn-outline-secondary btn-sm" onclick="setDateRange('quarter')">90 วันที่แล้ว</button>
        </div>
    </div>
</div>

<!-- Summary Cards with Growth -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stats-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">ออเดอร์ทั้งหมด</div>
                        <div class="h5 mb-0 font-weight-bold"><?= number_format($salesSummary['total_orders'] ?? 0) ?></div>
                        <div class="small">
                            <span class="badge bg-<?= $ordersGrowth >= 0 ? 'success' : 'danger' ?>">
                                <i class="fas fa-arrow-<?= $ordersGrowth >= 0 ? 'up' : 'down' ?>"></i>
                                <?= abs($ordersGrowth) ?>%
                            </span>
                        </div>
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
                        <div class="small">
                            <span class="badge bg-<?= $salesGrowth >= 0 ? 'success' : 'danger' ?>">
                                <i class="fas fa-arrow-<?= $salesGrowth >= 0 ? 'up' : 'down' ?>"></i>
                                <?= abs($salesGrowth) ?>%
                            </span>
                        </div>
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
                        <div class="small">
                            <span class="badge bg-<?= $avgOrderGrowth >= 0 ? 'success' : 'danger' ?>">
                                <i class="fas fa-arrow-<?= $avgOrderGrowth >= 0 ? 'up' : 'down' ?>"></i>
                                <?= abs($avgOrderGrowth) ?>%
                            </span>
                        </div>
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
                        <div class="small">
                            เสร็จสิ้น: <?= number_format($salesSummary['completed_orders'] ?? 0) ?>
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

<!-- Additional Metrics -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-primary h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">ลูกค้าไม่ซ้ำ</div>
                <div class="h6 mb-0 font-weight-bold"><?= number_format($salesSummary['unique_customers'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-success h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">ออเดอร์สูงสุด</div>
                <div class="h6 mb-0 font-weight-bold"><?= formatCurrency($salesSummary['max_order'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-info h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">เวลาเสิร์ฟเฉลี่ย</div>
                <div class="h6 mb-0 font-weight-bold"><?= round($queueStats['avg_serve_time'] ?? 0, 1) ?> นาที</div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-left-warning h-100">
            <div class="card-body">
                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">คิวสูงสุด</div>
                <div class="h6 mb-0 font-weight-bold">#<?= $queueStats['max_queue_number'] ?? 0 ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-xl-8 col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-chart-line"></i> แนวโน้มยอดขาย</h6>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary active" onclick="switchChart('sales')">ยอดขาย</button>
                    <button class="btn btn-outline-primary" onclick="switchChart('orders')">จำนวนออเดอร์</button>
                    <button class="btn btn-outline-primary" onclick="switchChart('avg')">ค่าเฉลี่ย</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="salesChart" style="height: 400px;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-credit-card"></i> วิธีการชำระเงิน</h6>
            </div>
            <div class="card-body">
                <canvas id="paymentChart" style="height: 300px;"></canvas>
                
                <div class="mt-3">
                    <?php foreach($paymentMethods as $method): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold"><?= getPaymentText($method['payment_method']) ?></span>
                        <div>
                            <span class="badge bg-primary"><?= $method['percentage'] ?>%</span>
                            <span class="small text-muted"><?= formatCurrency($method['total_amount']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hourly Analysis -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-clock"></i> การขายตามช่วงเวลา</h6>
            </div>
            <div class="card-body">
                <canvas id="hourlyChart" style="height: 300px;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div class="row mb-4">
    <!-- Popular Items -->
    <div class="col-xl-6 col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-utensils"></i> เมนูขายดี</h6>
                <button class="btn btn-sm btn-outline-primary" onclick="showAllItems()">ดูทั้งหมด</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>เมนู</th>
                                <th>ขาย</th>
                                <th>รายได้</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(array_slice($popularItems, 0, 10) as $index => $item): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?= $index < 3 ? 'warning' : 'secondary' ?>">
                                        <?= $index + 1 ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($item['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($item['category']) ?></small>
                                </td>
                                <td><?= number_format($item['total_sold']) ?></td>
                                <td class="fw-bold"><?= formatCurrency($item['total_revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Performance -->
    <div class="col-xl-6 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-tags"></i> ประสิทธิภาพหมวดหมู่</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>หมวดหมู่</th>
                                <th>รายการ</th>
                                <th>ขาย</th>
                                <th>รายได้</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($categoryStats as $category): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($category['category']) ?></td>
                                <td><?= number_format($category['item_count']) ?></td>
                                <td><?= number_format($category['total_sold']) ?></td>
                                <td class="fw-bold"><?= formatCurrency($category['total_revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Customer Analysis -->
<?php if (!empty($customerStats)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold"><i class="fas fa-users"></i> ลูกค้าประจำ (สั่งมากกว่า 1 ครั้ง)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ชื่อลูกค้า</th>
                                <th>เบอร์โทร</th>
                                <th>จำนวนครั้ง</th>
                                <th>ยอดรวม</th>
                                <th>ค่าเฉลี่ย</th>
                                <th>ครั้งล่าสุด</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($customerStats as $customer): ?>
                            <tr>
                                <td class="fw-bold"><?= htmlspecialchars($customer['customer_name']) ?></td>
                                <td><?= htmlspecialchars($customer['customer_phone']) ?></td>
                                <td><span class="badge bg-primary"><?= $customer['order_count'] ?></span></td>
                                <td class="fw-bold"><?= formatCurrency($customer['total_spent']) ?></td>
                                <td><?= formatCurrency($customer['avg_spent']) ?></td>
                                <td><?= formatDateTime($customer['last_order']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Advanced Analytics Modal -->
<div class="modal fade" id="advancedAnalyticsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-chart-pie"></i> การวิเคราะห์ขั้นสูง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <canvas id="detailedSalesChart" style="height: 300px;"></canvas>
                    </div>
                    <div class="col-md-6">
                        <canvas id="categoryChart" style="height: 300px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- All Items Modal -->
<div class="modal fade" id="allItemsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-list"></i> เมนูขายดีทั้งหมด</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="allItemsTable" class="table table-striped">
                        <thead>
                            <tr>
                                <th>อันดับ</th>
                                <th>ชื่อเมนู</th>
                                <th>หมวดหมู่</th>
                                <th>ราคา</th>
                                <th>จำนวนขาย</th>
                                <th>รายได้</th>
                                <th>จำนวนออเดอร์</th>
                                <th>เฉลี่ย/ออเดอร์</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($popularItems as $index => $item): ?>
                            <tr>
                                <td><span class="badge bg-<?= $index < 5 ? 'warning' : 'secondary' ?>"><?= $index + 1 ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($item['name']) ?></td>
                                <td><span class="badge bg-info"><?= htmlspecialchars($item['category']) ?></span></td>
                                <td><?= formatCurrency($item['price']) ?></td>
                                <td><?= number_format($item['total_sold']) ?></td>
                                <td class="fw-bold"><?= formatCurrency($item['total_revenue']) ?></td>
                                <td><?= number_format($item['order_count']) ?></td>
                                <td><?= number_format($item['avg_quantity'], 1) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Chart variables
let salesChart, paymentChart, hourlyChart;

$(document).ready(function() {
    initializeCharts();
    setupEventHandlers();
});

function initializeCharts() {
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    salesChart = new Chart(salesCtx, {
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
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'บาท'
                    }
                }
            }
        }
    });

    // Payment Methods Chart
    const paymentCtx = document.getElementById('paymentChart').getContext('2d');
    paymentChart = new Chart(paymentCtx, {
        type: 'doughnut',
        data: {
            labels: [<?= implode(',', array_map(function($item) { return '"' . getPaymentText($item['payment_method']) . '"'; }, $paymentMethods)) ?>],
            datasets: [{
                data: [<?= implode(',', array_column($paymentMethods, 'order_count')) ?>],
                backgroundColor: [
                    'rgba(25, 135, 84, 0.8)',
                    'rgba(13, 202, 240, 0.8)',
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(111, 66, 193, 0.8)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Hourly Chart
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    hourlyChart = new Chart(hourlyCtx, {
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
            }, {
                label: 'ยอดขาย (บาท)',
                data: [<?php 
                    $hourlySales = array_fill(0, 24, 0);
                    foreach($hourlyData as $item) {
                        $hourlySales[$item['hour']] = $item['sales'];
                    }
                    echo implode(',', $hourlySales);
                ?>],
                type: 'line',
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                yAxisID: 'y1',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'จำนวนออเดอร์'
                    }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'ยอดขาย (บาท)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

function setupEventHandlers() {
    // Chart switching
    $('.btn-group button').click(function() {
        $(this).addClass('active').siblings().removeClass('active');
    });
}

function switchChart(type) {
    const dailySalesData = <?= json_encode($dailySales) ?>;
    let newData, label;
    
    switch(type) {
        case 'sales':
            newData = dailySalesData.map(item => item.sales);
            label = 'ยอดขาย (บาท)';
            break;
        case 'orders':
            newData = dailySalesData.map(item => item.orders);
            label = 'จำนวนออเดอร์';
            break;
        case 'avg':
            newData = dailySalesData.map(item => item.avg_amount);
            label = 'ค่าเฉลี่ยต่อออเดอร์ (บาท)';
            break;
    }
    
    salesChart.data.datasets[0].data = newData;
    salesChart.data.datasets[0].label = label;
    salesChart.update();
}

function setDateRange(range) {
    let startDate, endDate;
    const today = new Date();
    
    switch(range) {
        case 'today':
            startDate = endDate = formatDate(today);
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            startDate = endDate = formatDate(yesterday);
            break;
        case 'week':
            const weekAgo = new Date(today);
            weekAgo.setDate(weekAgo.getDate() - 7);
            startDate = formatDate(weekAgo);
            endDate = formatDate(today);
            break;
        case 'month':
            const monthAgo = new Date(today);
            monthAgo.setDate(monthAgo.getDate() - 30);
            startDate = formatDate(monthAgo);
            endDate = formatDate(today);
            break;
        case 'quarter':
            const quarterAgo = new Date(today);
            quarterAgo.setDate(quarterAgo.getDate() - 90);
            startDate = formatDate(quarterAgo);
            endDate = formatDate(today);
            break;
    }
    
    document.getElementById('start_date').value = startDate;
    document.getElementById('end_date').value = endDate;
    
    // Auto submit
    document.querySelector('form').submit();
}

function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function exportReport(type) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', type);
    window.open(`?${params.toString()}`, '_blank');
}

function showAdvancedAnalytics() {
    $('#advancedAnalyticsModal').modal('show');
    
    // Initialize additional charts in modal
    setTimeout(() => {
        // Detailed Sales Chart
        const detailedCtx = document.getElementById('detailedSalesChart').getContext('2d');
        new Chart(detailedCtx, {
            type: 'bar',
            data: {
                labels: [<?= implode(',', array_map(function($item) { return '"' . $item['category'] . '"'; }, $categoryStats)) ?>],
                datasets: [{
                    label: 'รายได้ (บาท)',
                    data: [<?= implode(',', array_column($categoryStats, 'total_revenue')) ?>],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'รายได้ตามหมวดหมู่'
                    }
                }
            }
        });
        
        // Category Pie Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: [<?= implode(',', array_map(function($item) { return '"' . $item['category'] . '"'; }, $categoryStats)) ?>],
                datasets: [{
                    data: [<?= implode(',', array_column($categoryStats, 'total_sold')) ?>],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'จำนวนขายตามหมวดหมู่'
                    }
                }
            }
        });
    }, 500);
}

function showAllItems() {
    $('#allItemsModal').modal('show');
    
    // Initialize DataTable for all items
    setTimeout(() => {
        if (!$.fn.DataTable.isDataTable('#allItemsTable')) {
            $('#allItemsTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json'
                },
                order: [[4, 'desc']],
                pageLength: 25,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
        }
    }, 500);
}

// Auto refresh every 5 minutes
setInterval(function() {
    if (!document.hidden) {
        const now = new Date();
        const lastRefresh = sessionStorage.getItem('lastReportRefresh');
        
        if (!lastRefresh || (now.getTime() - parseInt(lastRefresh)) > 300000) {
            location.reload();
        }
    }
}, 300000);

sessionStorage.setItem('lastReportRefresh', Date.now());

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.altKey && e.key === 'e') {
        e.preventDefault();
        exportReport('pdf');
    }
    if (e.altKey && e.key === 'x') {
        e.preventDefault();
        exportReport('excel');
    }
    if (e.altKey && e.key === 'a') {
        e.preventDefault();
        showAdvancedAnalytics();
    }
});

// Add floating action button for quick actions
$(document).ready(function() {
    $('body').append(`
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
            <div class="btn-group-vertical">
                <button class="btn btn-primary btn-sm mb-2" onclick="showAdvancedAnalytics()" data-bs-toggle="tooltip" title="การวิเคราะห์ขั้นสูง (Alt+A)">
                    <i class="fas fa-chart-pie"></i>
                </button>
                <button class="btn btn-success btn-sm mb-2" onclick="exportReport('excel')" data-bs-toggle="tooltip" title="Export Excel (Alt+X)">
                    <i class="fas fa-file-excel"></i>
                </button>
                <button class="btn btn-danger btn-sm" onclick="exportReport('pdf')" data-bs-toggle="tooltip" title="Export PDF (Alt+E)">
                    <i class="fas fa-file-pdf"></i>
                </button>
            </div>
        </div>
    `);
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'includes/footer.php'; ?>