<?php
// pos/api/queue_management.php - API จัดการคิว
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_current_queue':
        getCurrentQueue();
        break;
        
    case 'call_queue':
        callQueue();
        break;
        
    case 'start_queue':
        startQueue();
        break;
        
    case 'complete_queue':
        completeQueue();
        break;
        
    case 'skip_queue':
        skipQueue();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

// ดึงคิวปัจจุบัน
function getCurrentQueue() {
    global $connection;
    
    try {
        $today = date('Y-m-d');
        
        $query = "
            SELECT 
                q.queue_number,
                q.status,
                q.estimated_time,
                q.called_at,
                q.started_at,
                o.order_number,
                o.customer_name,
                o.customer_phone,
                o.total_amount,
                o.order_type,
                TIME(o.created_at) as order_time,
                COUNT(oi.id) as item_count
            FROM queue q
            JOIN orders o ON q.order_id = o.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE DATE(q.queue_date) = '$today' 
            AND q.status IN ('waiting', 'called', 'in_progress')
            GROUP BY q.id
            ORDER BY q.queue_number ASC
        ";
        
        $result = mysqli_query($connection, $query);
        $queues = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $queues[] = [
                'queue_number' => intval($row['queue_number']),
                'status' => $row['status'],
                'order_number' => $row['order_number'],
                'customer_name' => $row['customer_name'],
                'customer_phone' => $row['customer_phone'],
                'total_amount' => floatval($row['total_amount']),
                'order_type' => $row['order_type'],
                'order_time' => $row['order_time'],
                'item_count' => intval($row['item_count']),
                'estimated_time' => $row['estimated_time'] ? date('H:i', strtotime($row['estimated_time'])) : null,
                'called_at' => $row['called_at'],
                'started_at' => $row['started_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'queues' => $queues,
            'count' => count($queues)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// เรียกคิว
function callQueue() {
    global $connection;
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $queue_number = intval($input['queue_number'] ?? 0);
        
        if ($queue_number <= 0) {
            throw new Exception('หมายเลขคิวไม่ถูกต้อง');
        }
        
        $today = date('Y-m-d');
        
        // ตรวจสอบว่าคิวมีอยู่และยังไม่ถูกเรียก
        $check_query = "
            SELECT q.id, q.order_id, o.customer_phone 
            FROM queue q
            JOIN orders o ON q.order_id = o.id
            WHERE q.queue_number = $queue_number 
            AND DATE(q.queue_date) = '$today'
            AND q.status = 'waiting'
        ";
        
        $check_result = mysqli_query($connection, $check_query);
        
        if (mysqli_num_rows($check_result) === 0) {
            throw new Exception('ไม่พบคิวหรือคิวไม่สามารถเรียกได้');
        }
        
        $queue_data = mysqli_fetch_assoc($check_result);
        
        // อัปเดตสถานะคิวเป็น 'called'
        $update_query = "
            UPDATE queue 
            SET status = 'called', called_at = NOW() 
            WHERE id = {$queue_data['id']}
        ";
        
        if (!mysqli_query($connection, $update_query)) {
            throw new Exception('ไม่สามารถอัปเดตสถานะคิวได้');
        }
        
        // ส่งการแจ้งเตือนผ่าน LINE (ถ้ามีเบอร์โทร)
        if ($queue_data['customer_phone']) {
            sendQueueCalledNotification($queue_data['customer_phone'], $queue_number);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'เรียกคิวหมายเลข ' . $queue_number . ' สำเร็จ'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// เริ่มทำออเดอร์
function startQueue() {
    global $connection;
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $queue_number = intval($input['queue_number'] ?? 0);
        
        if ($queue_number <= 0) {
            throw new Exception('หมายเลขคิวไม่ถูกต้อง');
        }
        
        $today = date('Y-m-d');
        
        // ตรวจสอบว่าคิวถูกเรียกแล้ว
        $check_query = "
            SELECT q.id, q.order_id
            FROM queue q
            WHERE q.queue_number = $queue_number 
            AND DATE(q.queue_date) = '$today'
            AND q.status = 'called'
        ";
        
        $check_result = mysqli_query($connection, $check_query);
        
        if (mysqli_num_rows($check_result) === 0) {
            throw new Exception('คิวยังไม่ถูกเรียกหรือไม่สามารถเริ่มทำได้');
        }
        
        $queue_data = mysqli_fetch_assoc($check_result);
        
        mysqli_begin_transaction($connection);
        
        // อัปเดตสถานะคิวเป็น 'in_progress'
        $update_queue = "
            UPDATE queue 
            SET status = 'in_progress', started_at = NOW() 
            WHERE id = {$queue_data['id']}
        ";
        
        if (!mysqli_query($connection, $update_queue)) {
            throw new Exception('ไม่สามารถอัปเดตสถานะคิวได้');
        }
        
        // อัปเดตสถานะออเดอร์เป็น 'in_progress'
        $update_order = "
            UPDATE orders 
            SET status = 'in_progress' 
            WHERE id = {$queue_data['order_id']}
        ";
        
        if (!mysqli_query($connection, $update_order)) {
            throw new Exception('ไม่สามารถอัปเดตสถานะออเดอร์ได้');
        }
        
        mysqli_commit($connection);
        
        echo json_encode([
            'success' => true,
            'message' => 'เริ่มทำออเดอร์คิวหมายเลข ' . $queue_number . ' แล้ว'
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($connection);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// เสร็จสิ้นคิว
function completeQueue() {
    global $connection;
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $queue_number = intval($input['queue_number'] ?? 0);
        
        if ($queue_number <= 0) {
            throw new Exception('หมายเลขคิวไม่ถูกต้อง');
        }
        
        $today = date('Y-m-d');
        
        // ตรวจสอบว่าคิวกำลังดำเนินการอยู่
        $check_query = "
            SELECT q.id, q.order_id, o.customer_phone
            FROM queue q
            JOIN orders o ON q.order_id = o.id
            WHERE q.queue_number = $queue_number 
            AND DATE(q.queue_date) = '$today'
            AND q.status = 'in_progress'
        ";
        
        $check_result = mysqli_query($connection, $check_query);
        
        if (mysqli_num_rows($check_result) === 0) {
            throw new Exception('คิวไม่อยู่ในสถานะกำลังดำเนินการ');
        }
        
        $queue_data = mysqli_fetch_assoc($check_result);
        
        mysqli_begin_transaction($connection);
        
        // อัปเดตสถานะคิวเป็น 'completed'
        $update_queue = "
            UPDATE queue 
            SET status = 'completed', completed_at = NOW() 
            WHERE id = {$queue_data['id']}
        ";
        
        if (!mysqli_query($connection, $update_queue)) {
            throw new Exception('ไม่สามารถอัปเดตสถานะคิวได้');
        }
        
        // อัปเดตสถานะออเดอร์เป็น 'ready'
        $update_order = "
            UPDATE orders 
            SET status = 'ready' 
            WHERE id = {$queue_data['order_id']}
        ";
        
        if (!mysqli_query($connection, $update_order)) {
            throw new Exception('ไม่สามารถอัปเดตสถานะออเดอร์ได้');
        }
        
        mysqli_commit($connection);
        
        // ส่งการแจ้งเตือนว่าอาหารพร้อม
        if ($queue_data['customer_phone']) {
            sendOrderReadyNotification($queue_data['customer_phone'], $queue_number);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'คิวหมายเลข ' . $queue_number . ' เสร็จสิ้นแล้ว'
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($connection);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// ข้ามคิว
function skipQueue() {
    global $connection;
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        $queue_number = intval($input['queue_number'] ?? 0);
        $reason = mysqli_real_escape_string($connection, $input['reason'] ?? '');
        
        if ($queue_number <= 0) {
            throw new Exception('หมายเลขคิวไม่ถูกต้อง');
        }
        
        $today = date('Y-m-d');
        
        // ตรวจสอบว่าคิวมีอยู่และยังไม่เสร็จสิ้น
        $check_query = "
            SELECT q.id, q.order_id
            FROM queue q
            WHERE q.queue_number = $queue_number 
            AND DATE(q.queue_date) = '$today'
            AND q.status IN ('waiting', 'called', 'in_progress')
        ";
        
        $check_result = mysqli_query($connection, $check_query);
        
        if (mysqli_num_rows($check_result) === 0) {
            throw new Exception('ไม่พบคิวหรือคิวเสร็จสิ้นแล้ว');
        }
        
        $queue_data = mysqli_fetch_assoc($check_result);
        
        mysqli_begin_transaction($connection);
        
        // อัปเดตสถานะคิวเป็น 'skipped'
        $update_queue = "
            UPDATE queue 
            SET status = 'skipped', skipped_at = NOW(), skip_reason = '$reason' 
            WHERE id = {$queue_data['id']}
        ";
        
        if (!mysqli_query($connection, $update_queue)) {
            throw new Exception('ไม่สามารถอัปเดตสถานะคิวได้');
        }
        
        // อัปเดตสถานะออเดอร์เป็น 'cancelled'
        $update_order = "
            UPDATE orders 
            SET status = 'cancelled' 
            WHERE id = {$queue_data['order_id']}
        ";
        
        if (!mysqli_query($connection, $update_order)) {
            throw new Exception('ไม่สามารถอัปเดตสถานะออเดอร์ได้');
        }
        
        mysqli_commit($connection);
        
        echo json_encode([
            'success' => true,
            'message' => 'ข้ามคิวหมายเลข ' . $queue_number . ' แล้ว'
        ]);
        
    } catch (Exception $e) {
        mysqli_rollback($connection);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// ฟังก์ชันส่งการแจ้งเตือนเมื่อเรียกคิว
function sendQueueCalledNotification($phone, $queue_number) {
    try {
        $message = "🔔 เรียกคิวแล้ว!\n\n";
        $message .= "🎫 หมายเลขคิว: " . str_pad($queue_number, 3, '0', STR_PAD_LEFT) . "\n";
        $message .= "🏃‍♂️ กรุณามารับออเดอร์ที่เคาน์เตอร์\n\n";
        $message .= "⚠️ หากไม่มารับภายใน 5 นาที จะข้ามไปคิวถัดไป";
        
        // TODO: ส่งข้อความผ่าน LINE Messaging API
        // sendLineMessage($phone, $message);
        
    } catch (Exception $e) {
        error_log("Queue called notification error: " . $e->getMessage());
    }
}

// ฟังก์ชันส่งการแจ้งเตือนเมื่ออาหารพร้อม
function sendOrderReadyNotification($phone, $queue_number) {
    try {
        $message = "🍽️ อาหารพร้อมแล้ว!\n\n";
        $message .= "🎫 หมายเลขคิว: " . str_pad($queue_number, 3, '0', STR_PAD_LEFT) . "\n";
        $message .= "✅ อาหารของคุณพร้อมเสิร์ฟแล้ว\n";
        $message .= "🏃‍♂️ กรุณามารับที่เคาน์เตอร์\n\n";
        $message .= "ขอบคุณที่ใช้บริการค่ะ 🙏";
        
        // TODO: ส่งข้อความผ่าน LINE Messaging API
        // sendLineMessage($phone, $message);
        
    } catch (Exception $e) {
        error_log("Order ready notification error: " . $e->getMessage());
    }
}

?>