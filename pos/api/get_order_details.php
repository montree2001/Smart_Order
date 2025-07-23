<?php
// pos/api/get_order_details.php - API ดึงรายละเอียดออเดอร์
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// ตรวจสอบสิทธิ์
if (!isLoggedIn() || !hasPermission(['admin', 'pos_staff', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึง']);
    exit;
}

try {
    $order_id = intval($_GET['id'] ?? 0);
    
    if ($order_id <= 0) {
        throw new Exception('ไม่พบหมายเลขออเดอร์');
    }
    
    // ดึงข้อมูลออเดอร์
    $order_query = "
        SELECT 
            o.*,
            q.queue_number,
            q.status as queue_status,
            q.estimated_time,
            q.called_at,
            q.started_at,
            q.completed_at,
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
    
    if (!$order_result || mysqli_num_rows($order_result) === 0) {
        throw new Exception('ไม่พบออเดอร์นี้');
    }
    
    $order = mysqli_fetch_assoc($order_result);
    
    // ดึงรายการสินค้า
    $items_query = "
        SELECT 
            oi.*,
            mi.name,
            mi.description,
            mi.image,
            mc.name as category_name,
            mc.icon as category_icon
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        LEFT JOIN menu_categories mc ON mi.category_id = mc.id
        WHERE oi.order_id = $order_id
        ORDER BY mc.sort_order ASC, mi.sort_order ASC, mi.name ASC
    ";
    
    $items_result = mysqli_query($connection, $items_query);
    $items = [];
    
    while ($item = mysqli_fetch_assoc($items_result)) {
        $items[] = [
            'id' => intval($item['id']),
            'menu_item_id' => intval($item['menu_item_id']),
            'name' => $item['name'],
            'description' => $item['description'],
            'image' => $item['image'],
            'category_name' => $item['category_name'],
            'category_icon' => $item['category_icon'],
            'quantity' => intval($item['quantity']),
            'unit_price' => floatval($item['unit_price']),
            'total_price' => floatval($item['total_price'])
        ];
    }
    
    // ดึงข้อมูลการชำระเงิน
    $payment_query = "
        SELECT * FROM payments 
        WHERE order_id = $order_id 
        ORDER BY created_at DESC 
        LIMIT 1
    ";
    
    $payment_result = mysqli_query($connection, $payment_query);
    $payment = null;
    
    if ($payment_result && mysqli_num_rows($payment_result) > 0) {
        $payment_data = mysqli_fetch_assoc($payment_result);
        $payment = [
            'id' => intval($payment_data['id']),
            'amount' => floatval($payment_data['amount']),
            'payment_method' => $payment_data['payment_method'],
            'payment_status' => $payment_data['payment_status'],
            'payment_details' => json_decode($payment_data['payment_details'], true),
            'created_at' => $payment_data['created_at']
        ];
    }
    
    // ดึงประวัติการอัปเดต (ถ้ามี)
    $history_query = "
        SELECT 
            'order' as type,
            CONCAT('สถานะ: ', status) as description,
            updated_at as created_at,
            NULL as user_name
        FROM orders 
        WHERE id = $order_id
        
        UNION ALL
        
        SELECT 
            'queue' as type,
            CONCAT('คิว: ', status) as description,
            CASE 
                WHEN status = 'called' THEN called_at
                WHEN status = 'in_progress' THEN started_at
                WHEN status = 'completed' THEN completed_at
                ELSE created_at
            END as created_at,
            NULL as user_name
        FROM queue
        WHERE order_id = $order_id
        AND status IN ('called', 'in_progress', 'completed')
        
        ORDER BY created_at DESC
    ";
    
    $history_result = mysqli_query($connection, $history_query);
    $history = [];
    
    while ($hist = mysqli_fetch_assoc($history_result)) {
        if ($hist['created_at']) {
            $history[] = [
                'type' => $hist['type'],
                'description' => $hist['description'],
                'created_at' => $hist['created_at'],
                'user_name' => $hist['user_name']
            ];
        }
    }
    
    // จัดรูปแบบข้อมูลออเดอร์
    $formatted_order = [
        'id' => intval($order['id']),
        'order_number' => $order['order_number'],
        'customer_id' => $order['customer_id'] ? intval($order['customer_id']) : null,
        'customer_name' => $order['customer_full_name'] ?: $order['customer_name'],
        'customer_phone' => $order['customer_phone'],
        'customer_email' => $order['customer_email'],
        'order_type' => $order['order_type'],
        'status' => $order['status'],
        'payment_status' => $order['payment_status'],
        'payment_method' => $order['payment_method'],
        'subtotal_amount' => floatval($order['subtotal_amount']),
        'service_charge_amount' => floatval($order['service_charge_amount']),
        'discount_amount' => floatval($order['discount_amount']),
        'tax_amount' => floatval($order['tax_amount']),
        'total_amount' => floatval($order['total_amount']),
        'queue_number' => $order['queue_number'] ? intval($order['queue_number']) : null,
        'queue_status' => $order['queue_status'],
        'estimated_time' => $order['estimated_time'],
        'called_at' => $order['called_at'],
        'started_at' => $order['started_at'],
        'completed_at' => $order['completed_at'],
        'staff_name' => $order['staff_name'],
        'notes' => $order['notes'],
        'created_at' => $order['created_at'],
        'updated_at' => $order['updated_at'],
        'items' => $items,
        'payment' => $payment,
        'history' => $history
    ];
    
    // คำนวณสถิติเพิ่มเติม
    $stats = [
        'total_items' => count($items),
        'total_quantity' => array_sum(array_column($items, 'quantity')),
        'average_item_price' => count($items) > 0 ? $formatted_order['subtotal_amount'] / count($items) : 0,
        'preparation_time' => null,
        'wait_time' => null,
        'service_time' => null
    ];
    
    // คำนวณเวลาต่างๆ
    if ($order['started_at'] && $order['created_at']) {
        $wait_time = strtotime($order['started_at']) - strtotime($order['created_at']);
        $stats['wait_time'] = floor($wait_time / 60); // นาที
    }
    
    if ($order['completed_at'] && $order['started_at']) {
        $prep_time = strtotime($order['completed_at']) - strtotime($order['started_at']);
        $stats['preparation_time'] = floor($prep_time / 60); // นาที
    }
    
    if ($order['completed_at'] && $order['created_at']) {
        $service_time = strtotime($order['completed_at']) - strtotime($order['created_at']);
        $stats['service_time'] = floor($service_time / 60); // นาที
    }
    
    $formatted_order['stats'] = $stats;
    
    echo json_encode([
        'success' => true,
        'order' => $formatted_order
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>