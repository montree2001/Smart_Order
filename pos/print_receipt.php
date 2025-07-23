<?php
// pos/print_receipt.php - พิมพ์ใบเสร็จ POS
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// ตรวจสอบสิทธิ์การเข้าถึง
checkPermission(['admin', 'pos_staff', 'manager']);

$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    die('ไม่พบหมายเลขออเดอร์');
}

// ดึงข้อมูลออเดอร์
$order_query = "
    SELECT 
        o.*,
        q.queue_number,
        q.estimated_time,
        u.full_name as staff_name,
        c.name as customer_full_name,
        c.email as customer_email
    FROM orders o
    LEFT JOIN queue q ON o.id = q.order_id
    LEFT JOIN users u ON o.staff_id = u.id
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE o.id = $order_id
";

$order_result = mysqli_query($connection, $order_query);

if (mysqli_num_rows($order_result) === 0) {
    die('ไม่พบออเดอร์นี้');
}

$order = mysqli_fetch_assoc($order_result);

// ดึงรายการสินค้า
$items_query = "
    SELECT 
        oi.*,
        mi.name,
        mi.description,
        mc.name as category_name
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    LEFT JOIN menu_categories mc ON mi.category_id = mc.id
    WHERE oi.order_id = $order_id
    ORDER BY mi.name ASC
";

$items_result = mysqli_query($connection, $items_query);

// ดึงข้อมูลการชำระเงิน
$payment_query = "
    SELECT * FROM payments 
    WHERE order_id = $order_id 
    ORDER BY created_at DESC 
    LIMIT 1
";

$payment_result = mysqli_query($connection, $payment_query);
$payment = mysqli_fetch_assoc($payment_result);

// ดึงข้อมูลร้าน
$shop_settings_query = "
    SELECT setting_key, setting_value 
    FROM system_settings 
    WHERE category = 'shop'
";

$shop_settings_result = mysqli_query($connection, $shop_settings_query);
$shop_settings = [];
while ($setting = mysqli_fetch_assoc($shop_settings_result)) {
    $shop_settings[$setting['setting_key']] = $setting['setting_value'];
}

$shop_name = $shop_settings['shop_name'] ?? 'ร้านอาหาร';
$shop_address = $shop_settings['shop_address'] ?? '';
$shop_phone = $shop_settings['shop_phone'] ?? '';
$tax_id = $shop_settings['tax_id'] ?? '';

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบเสร็จ #<?php echo $order['order_number']; ?></title>
    
    <style>
        @media print {
            @page {
                size: A4;
                margin: 0.5in;
            }
            
            body {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-after: always;
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
            padding: 20px;
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .receipt-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .receipt-subtitle {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .receipt-body {
            padding: 30px;
        }
        
        .receipt-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            color: #6c757d;
            flex-shrink: 0;
            margin-right: 20px;
        }
        
        .info-value {
            font-weight: 600;
            text-align: right;
            flex-grow: 1;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .items-table th {
            background: #f8f9fa;
            color: #495057;
            font-weight: 600;
            padding: 15px 10px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        
        .items-table th:nth-child(2),
        .items-table th:nth-child(3),
        .items-table th:nth-child(4) {
            text-align: right;
        }
        
        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .items-table td:nth-child(2),
        .items-table td:nth-child(3),
        .items-table td:nth-child(4) {
            text-align: right;
        }
        
        .items-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .summary-table {
            margin-top: 30px;
            border-top: 2px solid #dee2e6;
            padding-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 1.1rem;
        }
        
        .summary-row.total {
            background: #f8f9fa;
            padding: 15px 20px;
            margin: 15px -20px 0 -20px;
            border-radius: 10px;
            font-size: 1.3rem;
            font-weight: 700;
            color: #007bff;
        }
        
        .payment-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .queue-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .queue-number {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 10px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .receipt-footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        
        .print-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .print-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin: 5px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,123,255,0.3);
        }
        
        .print-btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,123,255,0.4);
        }
        
        .print-btn.secondary {
            background: #6c757d;
            box-shadow: 0 2px 10px rgba(108,117,125,0.3);
        }
        
        .print-btn.secondary:hover {
            background: #545b62;
            box-shadow: 0 4px 15px rgba(108,117,125,0.4);
        }
        
        .qr-code {
            width: 100px;
            height: 100px;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px auto;
            border-radius: 10px;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .receipt-body {
                padding: 20px;
            }
            
            .receipt-header {
                padding: 20px;
            }
            
            .receipt-title {
                font-size: 1.5rem;
            }
            
            .items-table th,
            .items-table td {
                padding: 8px 5px;
                font-size: 0.9rem;
            }
            
            .summary-row.total {
                margin: 15px -10px 0 -10px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    
    <!-- Print Buttons -->
    <div class="print-buttons no-print">
        <button class="print-btn" onclick="window.print()">
            <i class="fas fa-print"></i> พิมพ์ใบเสร็จ
        </button>
        <button class="print-btn secondary" onclick="window.close()">
            <i class="fas fa-times"></i> ปิด
        </button>
    </div>

    <div class="receipt-container">
        
        <!-- Receipt Header -->
        <div class="receipt-header">
            <div class="receipt-title"><?php echo $shop_name; ?></div>
            <div class="receipt-subtitle">ใบเสร็จรับเงิน / Receipt</div>
            <?php if ($shop_address): ?>
                <div style="margin-top: 15px; opacity: 0.9;"><?php echo $shop_address; ?></div>
            <?php endif; ?>
            <?php if ($shop_phone): ?>
                <div style="opacity: 0.9;">โทร: <?php echo $shop_phone; ?></div>
            <?php endif; ?>
            <?php if ($tax_id): ?>
                <div style="opacity: 0.9;">เลขประจำตัวผู้เสียภาษี: <?php echo $tax_id; ?></div>
            <?php endif; ?>
        </div>

        <div class="receipt-body">
            
            <!-- Queue Information -->
            <?php if ($order['queue_number']): ?>
                <div class="queue-info">
                    <div class="queue-number"><?php echo sprintf('%03d', $order['queue_number']); ?></div>
                    <div>หมายเลขคิว / Queue Number</div>
                    <?php if ($order['estimated_time']): ?>
                        <div style="font-size: 0.9rem; margin-top: 5px; opacity: 0.9;">
                            เวลาโดยประมาณ: <?php echo date('H:i น.', strtotime($order['estimated_time'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Order Information -->
            <div class="receipt-section">
                <div class="section-title">ข้อมูลออเดอร์</div>
                
                <div class="info-row">
                    <div class="info-label">หมายเลขออเดอร์:</div>
                    <div class="info-value"><?php echo $order['order_number']; ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">วันที่:</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i:s', strtotime($order['created_at'])); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">พนักงาน:</div>
                    <div class="info-value"><?php echo $order['staff_name'] ?? '-'; ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">ประเภทออเดอร์:</div>
                    <div class="info-value">
                        <?php 
                        $order_types = [
                            'dine_in' => 'ทานที่ร้าน',
                            'takeaway' => 'ซื้อกลับ',
                            'delivery' => 'เดลิเวอรี่'
                        ];
                        echo $order_types[$order['order_type']] ?? $order['order_type'];
                        ?>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="receipt-section">
                <div class="section-title">ข้อมูลลูกค้า</div>
                
                <div class="info-row">
                    <div class="info-label">ชื่อลูกค้า:</div>
                    <div class="info-value"><?php echo $order['customer_full_name'] ?: $order['customer_name']; ?></div>
                </div>
                
                <?php if ($order['customer_phone']): ?>
                    <div class="info-row">
                        <div class="info-label">เบอร์โทร:</div>
                        <div class="info-value"><?php echo $order['customer_phone']; ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($order['customer_email']): ?>
                    <div class="info-row">
                        <div class="info-label">อีเมล:</div>
                        <div class="info-value"><?php echo $order['customer_email']; ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Order Items -->
            <div class="receipt-section">
                <div class="section-title">รายการสินค้า</div>
                
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>รายการ</th>
                            <th>จำนวน</th>
                            <th>ราคาต่อหน่วย</th>
                            <th>ราคารวม</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $item['name']; ?></strong>
                                    <?php if ($item['description']): ?>
                                        <br><small style="color: #6c757d;"><?php echo $item['description']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>฿<?php echo number_format($item['unit_price'], 2); ?></td>
                                <td>฿<?php echo number_format($item['total_price'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary -->
            <div class="summary-table">
                <div class="summary-row">
                    <span>ราคารวม (Subtotal):</span>
                    <span>฿<?php echo number_format($order['subtotal_amount'], 2); ?></span>
                </div>
                
                <?php if ($order['service_charge_amount'] > 0): ?>
                    <div class="summary-row">
                        <span>ค่าบริการ (Service Charge):</span>
                        <span>฿<?php echo number_format($order['service_charge_amount'], 2); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($order['discount_amount'] > 0): ?>
                    <div class="summary-row">
                        <span>ส่วนลด (Discount):</span>
                        <span>-฿<?php echo number_format($order['discount_amount'], 2); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="summary-row">
                    <span>ภาษี (Tax):</span>
                    <span>฿<?php echo number_format($order['tax_amount'], 2); ?></span>
                </div>
                
                <div class="summary-row total">
                    <span>ยอดรวมสุทธิ (Total):</span>
                    <span>฿<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>

            <!-- Payment Information -->
            <?php if ($payment): ?>
                <div class="payment-info">
                    <h5 style="margin-bottom: 15px;">ข้อมูลการชำระเงิน</h5>
                    
                    <div class="info-row">
                        <div class="info-label">วิธีการชำระ:</div>
                        <div class="info-value">
                            <?php 
                            $payment_methods = [
                                'cash' => 'เงินสด',
                                'qr' => 'QR Payment',
                                'card' => 'บัตรเครดิต/เดบิต'
                            ];
                            echo $payment_methods[$payment['payment_method']] ?? $payment['payment_method'];
                            ?>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">สถานะการชำระ:</div>
                        <div class="info-value">
                            <span class="status-badge <?php echo $payment['payment_status'] === 'completed' ? 'status-paid' : 'status-pending'; ?>">
                                <?php echo $payment['payment_status'] === 'completed' ? 'ชำระแล้ว' : 'รอชำระ'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-row">
                        <div class="info-label">จำนวนเงิน:</div>
                        <div class="info-value">฿<?php echo number_format($payment['amount'], 2); ?></div>
                    </div>
                    
                    <?php if ($payment['payment_method'] === 'cash' && $payment['payment_details']): ?>
                        <?php $payment_details = json_decode($payment['payment_details'], true); ?>
                        <?php if (isset($payment_details['cash_received'])): ?>
                            <div class="info-row">
                                <div class="info-label">เงินที่รับ:</div>
                                <div class="info-value">฿<?php echo number_format($payment_details['cash_received'], 2); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">เงินทอน:</div>
                                <div class="info-value">฿<?php echo number_format($payment_details['change_amount'], 2); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

        <!-- Receipt Footer -->
        <div class="receipt-footer">
            <div style="margin-bottom: 20px;">
                <strong>ขอบคุณที่ใช้บริการ</strong><br>
                <span style="color: #6c757d;">Thank you for your business</span>
            </div>
            
            <!-- QR Code สำหรับการรีวิว (ถ้าต้องการ) -->
            <!--
            <div class="qr-code">
                <span style="color: #6c757d;">QR Code</span>
            </div>
            <div style="color: #6c757d; font-size: 0.9rem;">
                สแกนเพื่อรีวิวและให้คะแนน
            </div>
            -->
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                <small style="color: #6c757d;">
                    ใบเสร็จนี้ออกโดยระบบ POS อัตโนมัติ<br>
                    พิมพ์เมื่อ: <?php echo date('d/m/Y H:i:s'); ?>
                </small>
            </div>
        </div>

    </div>

    <!-- Auto Print Script -->
    <script>
        // พิมพ์อัตโนมัติเมื่อโหลดหน้า (ถ้าต้องการ)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 1000);
        // };
        
        // ปิดหน้าต่างหลังจากพิมพ์เสร็จ
        window.onafterprint = function() {
            // window.close();
        };
    </script>

    <!-- Font Awesome for icons -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

</body>
</html>