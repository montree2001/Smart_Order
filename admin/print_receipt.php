
<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/Order.php';

if (!isset($_GET['id'])) {
    die('ไม่พบรหัสออเดอร์');
}

$order = new Order($db);
$orderDetail = $order->getOrderById($_GET['id']);
$orderItems = $order->getOrderItems($_GET['id']);

if (!$orderDetail) {
    die('ไม่พบออเดอร์');
}

$settings = $db->fetchAll("SELECT setting_key, setting_value FROM system_settings");
$settingsArray = [];
foreach ($settings as $setting) {
    $settingsArray[$setting['setting_key']] = $setting['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จ #<?= $orderDetail['queue_number'] ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        .receipt {
            width: 300px;
            margin: 0 auto;
            border: 1px solid #ccc;
            padding: 15px;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .shop-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .order-info {
            margin: 10px 0;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 10px;
        }
        .items {
            margin: 10px 0;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .item-name {
            flex: 1;
        }
        .item-qty {
            margin: 0 10px;
        }
        .item-price {
            text-align: right;
            min-width: 60px;
        }
        .total {
            border-top: 1px dashed #ccc;
            padding-top: 10px;
            margin-top: 10px;
            font-weight: bold;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px dashed #ccc;
            font-size: 10px;
        }
        @media print {
            body { margin: 0; padding: 0; }
            .receipt { border: none; width: auto; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()">พิมพ์ใบเสร็จ</button>
        <button onclick="window.close()">ปิด</button>
    </div>

    <div class="receipt">
        <div class="header">
            <div class="shop-name"><?= htmlspecialchars($settingsArray['shop_name'] ?? 'ร้านอาหาร') ?></div>
            <div><?= htmlspecialchars($settingsArray['shop_phone'] ?? '') ?></div>
            <div>ใบเสร็จรับเงิน</div>
        </div>

        <div class="order-info">
            <div style="display: flex; justify-content: space-between;">
                <span>คิวที่:</span>
                <span><strong>#<?= $orderDetail['queue_number'] ?></strong></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>ลูกค้า:</span>
                <span><?= htmlspecialchars($orderDetail['customer_name']) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>วันที่:</span>
                <span><?= date('d/m/Y H:i:s', strtotime($orderDetail['created_at'])) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>ชำระโดย:</span>
                <span><?= getPaymentText($orderDetail['payment_method']) ?></span>
            </div>
        </div>

        <div class="items">
            <?php foreach($orderItems as $item): ?>
            <div class="item">
                <div class="item-name"><?= htmlspecialchars($item['item_name']) ?></div>
                <div class="item-qty">x<?= $item['quantity'] ?></div>
                <div class="item-price"><?= number_format($item['total_price'], 2) ?></div>
            </div>
            <?php if ($item['special_notes']): ?>
            <div style="font-size: 10px; color: #666; margin-left: 10px;">
                * <?= htmlspecialchars($item['special_notes']) ?>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="total">
            <div style="display: flex; justify-content: space-between;">
                <span>ยอดรวมทั้งสิ้น:</span>
                <span><?= number_format($orderDetail['total_amount'], 2) ?> บาท</span>
            </div>
        </div>

        <div class="footer">
            <div><?= htmlspecialchars($settingsArray['receipt_footer_text'] ?? 'ขอบคุณที่ใช้บริการ') ?></div>
            <div style="margin-top: 10px;">
                ระบบจัดการออเดอร์อัจฉริยะ<br>
                Smart Order Management System
            </div>
        </div>
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
