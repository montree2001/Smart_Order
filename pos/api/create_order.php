<?php
// pos/api/create_order.php - API สร้างออเดอร์ใหม่
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // รับข้อมูล JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('ไม่พบข้อมูลที่ส่งมา');
    }
    
    // ตรวจสอบข้อมูลที่จำเป็น
    if (empty($input['items']) || !is_array($input['items']) || count($input['items']) === 0) {
        throw new Exception('ไม่พบรายการสินค้า');
    }
    
    // เริ่ม Transaction
    mysqli_begin_transaction($connection);
    
    // สร้างหมายเลขออเดอร์
    $order_number = generateOrderNumber();
    
    // เตรียมข้อมูลออเดอร์
    $customer_name = mysqli_real_escape_string($connection, $input['customer_name'] ?? 'ลูกค้า Walk-in');
    $customer_phone = mysqli_real_escape_string($connection, $input['customer_phone'] ?? '');
    $order_type = mysqli_real_escape_string($connection, $input['order_type'] ?? 'dine_in');
    $payment_method = mysqli_real_escape_string($connection, $input['payment_method'] ?? 'cash');
    $subtotal = floatval($input['subtotal'] ?? 0);
    $service_charge = floatval($input['service_charge'] ?? 0);
    $tax_amount = floatval($input['tax_amount'] ?? 0);
    $total_amount = floatval($input['total_amount'] ?? 0);
    $staff_id = intval($input['staff_id'] ?? $_SESSION['user_id']);
    
    // ตรวจสอบความถูกต้องของราคา
    $calculated_subtotal = 0;
    foreach ($input['items'] as $item) {
        $calculated_subtotal += floatval($item['price']) * intval($item['quantity']);
    }
    
    if (abs($calculated_subtotal - $subtotal) > 0.01) {
        throw new Exception('ราคารวมไม่ถูกต้อง');
    }
    
    // ค้นหา customer_id (ถ้ามีเบอร์โทร)
    $customer_id = null;
    if ($customer_phone) {
        $customer_check = mysqli_query($connection, "
            SELECT id FROM customers WHERE phone = '$customer_phone' LIMIT 1
        ");
        
        if (mysqli_num_rows($customer_check) > 0) {
            $customer_data = mysqli_fetch_assoc($customer_check);
            $customer_id = $customer_data['id'];
        } else {
            // สร้างลูกค้าใหม่
            $insert_customer = mysqli_query($connection, "
                INSERT INTO customers (name, phone, created_at) 
                VALUES ('$customer_name', '$customer_phone', NOW())
            ");
            
            if ($insert_customer) {
                $customer_id = mysqli_insert_id($connection);
            }
        }
    }
    
    // สร้างออเดอร์ในฐานข้อมูล
    $order_query = "
        INSERT INTO orders (
            order_number, customer_id, customer_name, customer_phone,
            order_type, subtotal_amount, service_charge_amount, 
            tax_amount, total_amount, payment_method, payment_status,
            status, staff_id, created_at
        ) VALUES (
            '$order_number', " . ($customer_id ? $customer_id : 'NULL') . ", 
            '$customer_name', '$customer_phone', '$order_type', 
            $subtotal, $service_charge, $tax_amount, $total_amount,
            '$payment_method', 'paid', 'pending', $staff_id, NOW()
        )
    ";
    
    if (!mysqli_query($connection, $order_query)) {
        throw new Exception('ไม่สามารถสร้างออเดอร์ได้: ' . mysqli_error($connection));
    }
    
    $order_id = mysqli_insert_id($connection);
    
    // เพิ่มรายการสินค้าในออเดอร์
    foreach ($input['items'] as $item) {
        $menu_item_id = intval($item['id']);
        $quantity = intval($item['quantity']);
        $unit_price = floatval($item['price']);
        $total_price = $unit_price * $quantity;
        
        // ตรวจสอบว่าเมนูมีอยู่จริง
        $menu_check = mysqli_query($connection, "
            SELECT id, name FROM menu_items WHERE id = $menu_item_id AND is_active = 1
        ");
        
        if (mysqli_num_rows($menu_check) === 0) {
            throw new Exception('ไม่พบเมนู ID: ' . $menu_item_id);
        }
        
        // เพิ่มรายการสินค้า
        $item_query = "
            INSERT INTO order_items (
                order_id, menu_item_id, quantity, unit_price, total_price, created_at
            ) VALUES (
                $order_id, $menu_item_id, $quantity, $unit_price, $total_price, NOW()
            )
        ";
        
        if (!mysqli_query($connection, $item_query)) {
            throw new Exception('ไม่สามารถเพิ่มรายการสินค้าได้: ' . mysqli_error($connection));
        }
    }
    
    // สร้างหมายเลขคิว
    $queue_number = generateQueueNumber();
    $estimated_time = calculateEstimatedTime(count($input['items']));
    
    $queue_query = "
        INSERT INTO queue (
            order_id, queue_number, queue_date, status, 
            estimated_time, created_at
        ) VALUES (
            $order_id, $queue_number, CURDATE(), 'waiting', 
            DATE_ADD(NOW(), INTERVAL $estimated_time MINUTE), NOW()
        )
    ";
    
    if (!mysqli_query($connection, $queue_query)) {
        throw new Exception('ไม่สามารถสร้างคิวได้: ' . mysqli_error($connection));
    }
    
    // บันทึกข้อมูลการชำระเงิน
    $payment_details = json_encode($input['payment_details'] ?? []);
    $payment_query = "
        INSERT INTO payments (
            order_id, amount, payment_method, payment_details,
            payment_status, staff_id, created_at
        ) VALUES (
            $order_id, $total_amount, '$payment_method', 
            '$payment_details', 'completed', $staff_id, NOW()
        )
    ";
    
    if (!mysqli_query($connection, $payment_query)) {
        throw new Exception('ไม่สามารถบันทึกการชำระเงินได้: ' . mysqli_error($connection));
    }
    
    // Commit Transaction
    mysqli_commit($connection);
    
    // ส่งการแจ้งเตือนผ่าน LINE (ถ้ามีเบอร์โทร)
    if ($customer_phone) {
        // TODO: ส่งข้อความแจ้งเตือนผ่าน LINE OA
        sendLineNotification($customer_phone, $order_number, $queue_number);
    }
    
    // เตรียมข้อมูลสำหรับส่งกลับ
    $response = [
        'success' => true,
        'message' => 'สร้างออเดอร์สำเร็จ',
        'order' => [
            'id' => $order_id,
            'order_number' => $order_number,
            'queue_number' => $queue_number,
            'total_amount' => $total_amount,
            'payment_method' => $payment_method,
            'estimated_time' => $estimated_time,
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback Transaction
    mysqli_rollback($connection);
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// ฟังก์ชันช่วย
function generateOrderNumber() {
    $today = date('Ymd');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return $today . $random;
}

function generateQueueNumber() {
    global $connection;
    
    $today = date('Y-m-d');
    
    // ค้นหาหมายเลขคิวล่าสุดของวันนี้
    $queue_check = mysqli_query($connection, "
        SELECT MAX(queue_number) as max_queue 
        FROM queue 
        WHERE queue_date = '$today'
    ");
    
    $max_queue = 0;
    if ($queue_check && mysqli_num_rows($queue_check) > 0) {
        $row = mysqli_fetch_assoc($queue_check);
        $max_queue = intval($row['max_queue']);
    }
    
    return $max_queue + 1;
}

function calculateEstimatedTime($item_count) {
    global $connection;
    
    // ดึงเวลาเตรียมอาหารต่อรายการจากการตั้งค่า
    $settings = mysqli_query($connection, "
        SELECT setting_value 
        FROM system_settings 
        WHERE setting_key = 'preparation_time_per_item'
    ");
    
    $prep_time = 5; // นาที (ค่าเริ่มต้น)
    if ($settings && mysqli_num_rows($settings) > 0) {
        $setting = mysqli_fetch_assoc($settings);
        $prep_time = intval($setting['setting_value']);
    }
    
    // คำนวณเวลาโดยประมาณ
    $estimated_minutes = $item_count * $prep_time;
    
    // เพิ่มเวลาขึ้นอยู่กับจำนวนคิวที่รอ
    $waiting_queues = mysqli_query($connection, "
        SELECT COUNT(*) as count 
        FROM queue 
        WHERE queue_date = CURDATE() 
        AND status IN ('waiting', 'in_progress')
    ");
    
    if ($waiting_queues && mysqli_num_rows($waiting_queues) > 0) {
        $queue_data = mysqli_fetch_assoc($waiting_queues);
        $queue_count = intval($queue_data['count']);
        $estimated_minutes += ($queue_count * 2); // เพิ่ม 2 นาทีต่อคิว
    }
    
    // ขั้นต่ำ 5 นาที สูงสุด 60 นาที
    return max(5, min(60, $estimated_minutes));
}

function sendLineNotification($phone, $order_number, $queue_number) {
    // TODO: Implement LINE OA notification
    // ในที่นี้เป็นเพียงการจำลอง
    
    try {
        $message = "🍽️ ออเดอร์ของคุณได้ถูกรับแล้ว!\n\n";
        $message .= "📝 หมายเลขออเดอร์: {$order_number}\n";
        $message .= "🎫 หมายเลขคิว: " . str_pad($queue_number, 3, '0', STR_PAD_LEFT) . "\n";
        $message .= "⏰ กรุณารอการแจ้งเตือนเมื่อใกล้ถึงคิว\n\n";
        $message .= "ขอบคุณที่ใช้บริการค่ะ 🙏";
        
        // TODO: ส่งข้อความผ่าน LINE Messaging API
        // sendLineMessage($phone, $message);
        
    } catch (Exception $e) {
        // Log error แต่ไม่ให้กระทบต่อการสร้างออเดอร์
        error_log("LINE notification error: " . $e->getMessage());
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function hasPermission($roles) {
    if (!isLoggedIn()) return false;
    
    $user_role = $_SESSION['user_role'] ?? '';
    return in_array($user_role, $roles);
}

?>