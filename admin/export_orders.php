<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d') . '.csv"');

$startDate = $_GET['start_date'] ?? date('Y-m-d');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$orders = $db->fetchAll("
    SELECT 
        o.id,
        o.queue_number,
        o.customer_name,
        o.customer_phone,
        o.total_amount,
        o.status,
        o.payment_method,
        o.payment_status,
        o.order_type,
        o.created_at,
        COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
", [$startDate, $endDate]);

// Output CSV headers
$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

fputcsv($output, [
    'รหัสออเดอร์',
    'หมายเลขคิว',
    'ชื่อลูกค้า',
    'เบอร์โทร',
    'จำนวนรายการ',
    'ยอดรวม',
    'สถานะ',
    'การชำระเงิน',
    'สถานะการชำระ',
    'ประเภทออเดอร์',
    'วันที่สั่ง'
]);

foreach ($orders as $order) {
    fputcsv($output, [
        $order['id'],
        $order['queue_number'],
        $order['customer_name'],
        $order['customer_phone'],
        $order['item_count'],
        $order['total_amount'],
        getStatusText($order['status']),
        getPaymentText($order['payment_method']),
        $order['payment_status'],
        $order['order_type'],
        $order['created_at']
    ]);
}

fclose($output);
