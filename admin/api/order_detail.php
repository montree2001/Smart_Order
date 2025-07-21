<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Order.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($_GET['id'])) {
    echo '<div class="alert alert-danger">ไม่พบรหัสออเดอร์</div>';
    exit;
}

$order = new Order($db);
$orderDetail = $order->getOrderById($_GET['id']);
$orderItems = $order->getOrderItems($_GET['id']);

if (!$orderDetail) {
    echo '<div class="alert alert-danger">ไม่พบออเดอร์</div>';
    exit;
}
?>

<div class="row">
    <div class="col-md-6">
        <h6>ข้อมูลออเดอร์</h6>
        <table class="table table-borderless table-sm">
            <tr>
                <td><strong>หมายเลขคิว:</strong></td>
                <td><span class="badge bg-primary fs-6">#<?= $orderDetail['queue_number'] ?></span></td>
            </tr>
            <tr>
                <td><strong>ลูกค้า:</strong></td>
                <td><?= htmlspecialchars($orderDetail['customer_name']) ?></td>
            </tr>
            <tr>
                <td><strong>เบอร์โทร:</strong></td>
                <td><?= htmlspecialchars($orderDetail['customer_phone']) ?></td>
            </tr>
            <tr>
                <td><strong>วันที่สั่ง:</strong></td>
                <td><?= formatDateTime($orderDetail['created_at']) ?></td>
            </tr>
            <tr>
                <td><strong>สถานะ:</strong></td>
                <td>
                    <span class="badge bg-<?= getStatusColor($orderDetail['status']) ?>">
                        <?= getStatusText($orderDetail['status']) ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>การชำระเงิน:</strong></td>
                <td>
                    <span class="badge bg-<?= getPaymentColor($orderDetail['payment_method']) ?>">
                        <?= getPaymentText($orderDetail['payment_method']) ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6>สถานะคิว</h6>
        <table class="table table-borderless table-sm">
            <tr>
                <td><strong>สถานะคิว:</strong></td>
                <td>
                    <span class="badge bg-<?= getQueueStatusColor($orderDetail['queue_status'] ?? 'waiting') ?>">
                        <?= getQueueStatusText($orderDetail['queue_status'] ?? 'waiting') ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td><strong>เวลาโดยประมาณ:</strong></td>
                <td><?= $orderDetail['estimated_time'] ?? '5' ?> นาที</td>
            </tr>
            <tr>
                <td><strong>จำนวนรายการ:</strong></td>
                <td><?= $orderDetail['item_count'] ?? '0' ?> รายการ</td>
            </tr>
        </table>
    </div>
</div>

<hr>

<h6>รายการอาหาร</h6>
<div class="table-responsive">
    <table class="table table-striped table-sm">
        <thead>
            <tr>
                <th>รายการ</th>
                <th>หมวดหมู่</th>
                <th>จำนวน</th>
                <th>ราคาต่อหน่วย</th>
                <th>ราคารวม</th>
                <th>หมายเหตุ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($orderItems)): ?>
                <?php foreach($orderItems as $item): ?>
                <tr>
                    <td class="fw-bold"><?= htmlspecialchars($item['item_name']) ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($item['category']) ?></span></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= formatCurrency($item['price']) ?></td>
                    <td class="fw-bold"><?= formatCurrency($item['total_price']) ?></td>
                    <td><?= htmlspecialchars($item['special_notes'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">ไม่พบรายการอาหาร</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="table-warning">
                <td colspan="4" class="text-end fw-bold">ยอดรวมทั้งสิ้น:</td>
                <td class="fw-bold fs-5"><?= formatCurrency($orderDetail['total_amount']) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>