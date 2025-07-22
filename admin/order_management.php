<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Order.php';

requireLogin();

$pageTitle = 'จัดการออเดอร์';
$activePage = 'orders';

$order = new Order($db);

// Handle status updates
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_status') {
        $order->updateStatus($_POST['order_id'], $_POST['status']);
        $message = "อัปเดตสถานะออเดอร์สำเร็จ";
    }
}

$orders = $order->getAllOrders();

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-shopping-cart"></i> จัดการออเดอร์</h1>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
            <i class="fas fa-sync"></i> Refresh
        </button>
        <button type="button" class="btn btn-primary" onclick="exportOrders()">
            <i class="fas fa-download"></i> Export
        </button>
    </div>
</div>

<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filter Row -->
<div class="row mb-4">
    <div class="col-md-3">
        <select class="form-select" id="statusFilter">
            <option value="">ทุกสถานะ</option>
            <option value="pending">รอยืนยัน</option>
            <option value="confirmed">ยืนยันแล้ว</option>
            <option value="preparing">กำลังทำ</option>
            <option value="ready">พร้อมเสิร์ฟ</option>
            <option value="completed">เสร็จสิ้น</option>
            <option value="cancelled">ยกเลิก</option>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="paymentFilter">
            <option value="">ทุกการชำระ</option>
            <option value="cash">เงินสด</option>
            <option value="qr_payment">QR Payment</option>
            <option value="card">บัตร</option>
        </select>
    </div>
    <div class="col-md-3">
        <input type="date" class="form-control" id="dateFilter" value="<?= date('Y-m-d') ?>">
    </div>
    <div class="col-md-3">
        <button type="button" class="btn btn-secondary w-100" onclick="clearFilters()">
            <i class="fas fa-times"></i> Clear Filters
        </button>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-header">
        <h6 class="m-0 font-weight-bold">รายการออเดอร์ทั้งหมด</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="ordersTable" class="table table-hover">
                <thead>
                    <tr>
                        <th>คิว</th>
                        <th>ลูกค้า</th>
                        <th>รายการ</th>
                        <th>ยอดรวม</th>
                        <th>การชำระ</th>
                        <th>สถานะ</th>
                        <th>เวลา</th>
                        <th>การกระทำ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $orderItem): ?>
                    <tr>
                        <td>
                            <span class="badge bg-info fs-6">#<?= $orderItem['queue_number'] ?></span>
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($orderItem['customer_name']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($orderItem['customer_phone']) ?></small>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?= $orderItem['item_count'] ?> รายการ</span>
                        </td>
                        <td class="fw-bold"><?= formatCurrency($orderItem['total_amount']) ?></td>
                        <td>
                            <span class="badge bg-<?= getPaymentColor($orderItem['payment_method']) ?>">
                                <?= getPaymentText($orderItem['payment_method']) ?>
                            </span>
                        </td>
                        <td>
                            <select class="form-select form-select-sm" onchange="updateOrderStatus(<?= $orderItem['id'] ?>, this.value)">
                                <option value="pending" <?= $orderItem['status'] == 'pending' ? 'selected' : '' ?>>รอยืนยัน</option>
                                <option value="confirmed" <?= $orderItem['status'] == 'confirmed' ? 'selected' : '' ?>>ยืนยันแล้ว</option>
                                <option value="preparing" <?= $orderItem['status'] == 'preparing' ? 'selected' : '' ?>>กำลังทำ</option>
                                <option value="ready" <?= $orderItem['status'] == 'ready' ? 'selected' : '' ?>>พร้อมเสิร์ฟ</option>
                                <option value="completed" <?= $orderItem['status'] == 'completed' ? 'selected' : '' ?>>เสร็จสิ้น</option>
                                <option value="cancelled" <?= $orderItem['status'] == 'cancelled' ? 'selected' : '' ?>>ยกเลิก</option>
                            </select>
                        </td>
                        <td><?= formatDateTime($orderItem['created_at']) ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewOrderDetail(<?= $orderItem['id'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="printReceipt(<?= $orderItem['id'] ?>)">
                                    <i class="fas fa-print"></i>
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

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รายละเอียดออเดอร์</h5>
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

<script>
$(document).ready(function() {
    const table = $('#ordersTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/th.json'
        },
        order: [[6, 'desc']],
        columnDefs: [
            { orderable: false, targets: [7] }
        ]
    });

    // Filter functionality
    $('#statusFilter, #paymentFilter, #dateFilter').on('change', function() {
        table.draw();
    });

    // Custom search
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        const statusFilter = $('#statusFilter').val();
        const paymentFilter = $('#paymentFilter').val();
        const dateFilter = $('#dateFilter').val();
        
        if (statusFilter && !data[5].includes(statusFilter)) return false;
        if (paymentFilter && !data[4].includes(paymentFilter)) return false;
        if (dateFilter && !data[6].includes(dateFilter)) return false;
        
        return true;
    });
});

function updateOrderStatus(orderId, status) {
    if (confirm('คุณต้องการเปลี่ยนสถานะออเดอร์นี้หรือไม่?')) {
        $.post('', {
            action: 'update_status',
            order_id: orderId,
            status: status
        }, function() {
            location.reload();
        });
    }
}

function viewOrderDetail(orderId) {
    $('#orderDetailContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</div>');
    $('#orderDetailModal').modal('show');
    
    // Load order details via AJAX
    $.get('api/order_detail.php', {id: orderId}, function(data) {
        $('#orderDetailContent').html(data);
    });
}

function printReceipt(orderId) {
    window.open(`print_receipt.php?id=${orderId}`, '_blank');
}

function clearFilters() {
    $('#statusFilter, #paymentFilter, #dateFilter').val('');
    $('#ordersTable').DataTable().search('').draw();
}

function exportOrders() {
    window.open('export_orders.php', '_blank');
}
</script>

<?php include 'includes/footer.php'; ?>