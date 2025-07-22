<?php
class Queue {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * เพิ่มคิวใหม่
     */
    public function addToQueue($orderId, $estimatedTime = 15) {
        $queueNumber = $this->generateQueueNumber();
        
        $sql = "INSERT INTO queue (order_id, queue_number, status, estimated_time, created_at) 
                VALUES (?, ?, 'waiting', ?, NOW())";
        
        $result = $this->db->query($sql, [$orderId, $queueNumber, $estimatedTime]);
        
        if ($result) {
            return [
                'success' => true,
                'queue_id' => $this->db->lastInsertId(),
                'queue_number' => $queueNumber
            ];
        }
        
        return ['success' => false, 'message' => 'ไม่สามารถสร้างคิวได้'];
    }
    
    /**
     * สร้างหมายเลขคิวใหม่
     */
    public function generateQueueNumber() {
        $prefix = date('md'); // วันที่ + เดือน เช่น 2207
        
        $sql = "SELECT MAX(CAST(SUBSTRING(queue_number, 5) AS UNSIGNED)) as max_num 
                FROM queue 
                WHERE DATE(created_at) = CURDATE() 
                AND queue_number LIKE ?";
        
        $result = $this->db->fetchOne($sql, [$prefix . '%']);
        $nextNumber = ($result['max_num'] ?? 0) + 1;
        
        return $prefix . sprintf('%03d', $nextNumber); // เช่น 2207001
    }
    
    /**
     * ดึงคิวปัจจุบันที่กำลังเรียก
     */
    public function getCurrentQueue() {
        $sql = "SELECT q.*, o.customer_name, o.customer_phone, o.total_amount,
                       GROUP_CONCAT(CONCAT(mi.name, ' x', oi.quantity) SEPARATOR ', ') as items_summary
                FROM queue q
                JOIN orders o ON q.order_id = o.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                WHERE q.status = 'calling'
                AND DATE(q.created_at) = CURDATE()
                GROUP BY q.id
                ORDER BY q.called_at ASC
                LIMIT 1";
        
        return $this->db->fetchOne($sql);
    }
    
    /**
     * ดึงรายการคิวที่รอ
     */
    public function getWaitingQueues($limit = 20) {
        $sql = "SELECT q.*, o.customer_name, o.customer_phone, o.total_amount,
                       GROUP_CONCAT(CONCAT(mi.name, ' x', oi.quantity) SEPARATOR ', ') as items_summary,
                       TIMESTAMPDIFF(MINUTE, q.created_at, NOW()) as wait_minutes
                FROM queue q
                JOIN orders o ON q.order_id = o.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                WHERE q.status = 'waiting'
                AND DATE(q.created_at) = CURDATE()
                GROUP BY q.id
                ORDER BY q.created_at ASC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    /**
     * ดึงรายการคิวที่พร้อมเสิร์ฟ
     */
    public function getReadyQueues($limit = 20) {
        $sql = "SELECT q.*, o.customer_name, o.customer_phone, o.total_amount,
                       GROUP_CONCAT(CONCAT(mi.name, ' x', oi.quantity) SEPARATOR ', ') as items_summary
                FROM queue q
                JOIN orders o ON q.order_id = o.id
                LEFT JOIN order_items oi ON o.id = oi.order_id  
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                WHERE o.status = 'ready' AND q.status IN ('waiting', 'calling')
                AND DATE(q.created_at) = CURDATE()
                GROUP BY q.id
                ORDER BY q.created_at ASC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    /**
     * ดึงรายการคิวที่กำลังเรียก
     */
    public function getCallingQueues($limit = 10) {
        $sql = "SELECT q.*, o.customer_name, o.customer_phone, o.total_amount,
                       GROUP_CONCAT(CONCAT(mi.name, ' x', oi.quantity) SEPARATOR ', ') as items_summary
                FROM queue q
                JOIN orders o ON q.order_id = o.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                WHERE q.status = 'calling'
                AND DATE(q.created_at) = CURDATE()
                GROUP BY q.id
                ORDER BY q.called_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    /**
     * เรียกคิว
     */
    public function callQueue($queueId) {
        $sql = "UPDATE queue SET status = 'calling', called_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$queueId]);
        
        if ($result) {
            // ดึงข้อมูลคิวที่เรียก
            $queue = $this->getQueueById($queueId);
            return [
                'success' => true,
                'message' => 'เรียกคิว ' . $queue['queue_number'] . ' เรียบร้อย',
                'queue_data' => $queue
            ];
        }
        
        return ['success' => false, 'message' => 'ไม่สามารถเรียกคิวได้'];
    }
    
    /**
     * เสร็จสิ้นการให้บริการ
     */
    public function serveQueue($queueId) {
        $sql = "UPDATE queue SET status = 'served', served_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$queueId]);
        
        if ($result) {
            // อัปเดตสถานะออเดอร์
            $this->db->query(
                "UPDATE orders o 
                 JOIN queue q ON o.id = q.order_id 
                 SET o.status = 'completed' 
                 WHERE q.id = ?", 
                [$queueId]
            );
            
            return ['success' => true, 'message' => 'บริการเสร็จสิ้น'];
        }
        
        return ['success' => false, 'message' => 'ไม่สามารถอัปเดตสถานะได้'];
    }
    
    /**
     * ข้ามคิว
     */
    public function skipQueue($queueId) {
        $sql = "UPDATE queue SET status = 'no_show', no_show_at = NOW() WHERE id = ?";
        $result = $this->db->query($sql, [$queueId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'ข้ามคิวเรียบร้อย'];
        }
        
        return ['success' => false, 'message' => 'ไม่สามารถข้ามคิวได้'];
    }
    
    /**
     * ดึงข้อมูลคิวตาม ID
     */
    public function getQueueById($queueId) {
        $sql = "SELECT q.*, o.customer_name, o.customer_phone, o.total_amount,
                       GROUP_CONCAT(CONCAT(mi.name, ' x', oi.quantity) SEPARATOR ', ') as items_summary
                FROM queue q
                JOIN orders o ON q.order_id = o.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                WHERE q.id = ?
                GROUP BY q.id";
        
        return $this->db->fetchOne($sql, [$queueId]);
    }
    
    /**
     * ดึงสถิติคิว
     */
    public function getQueueStats() {
        $stats = [];
        
        // จำนวนคิววันนี้
        $sql = "SELECT COUNT(*) as total FROM queue WHERE DATE(created_at) = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $stats['total_today'] = $result['total'] ?? 0;
        
        // คิวที่รอ
        $sql = "SELECT COUNT(*) as waiting FROM queue WHERE status = 'waiting' AND DATE(created_at) = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $stats['waiting'] = $result['waiting'] ?? 0;
        
        // คิวที่กำลังเรียก
        $sql = "SELECT COUNT(*) as calling FROM queue WHERE status = 'calling' AND DATE(created_at) = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $stats['calling'] = $result['calling'] ?? 0;
        
        // เวลารอเฉลี่ย
        $sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, called_at)) as avg_wait 
                FROM queue 
                WHERE called_at IS NOT NULL 
                AND DATE(created_at) = CURDATE()";
        $result = $this->db->fetchOne($sql);
        $stats['avg_wait_time'] = round($result['avg_wait'] ?? 0);
        
        return $stats;
    }
    
    /**
     * ดึงคิวถัดไป
     */
    public function getNextQueue() {
        $sql = "SELECT q.*, o.customer_name, o.customer_phone
                FROM queue q
                JOIN orders o ON q.order_id = o.id
                WHERE q.status = 'waiting'
                AND DATE(q.created_at) = CURDATE()
                ORDER BY q.created_at ASC
                LIMIT 1";
        
        return $this->db->fetchOne($sql);
    }
    
    /**
     * คำนวณเวลารอโดยประมาณ
     */
    public function calculateEstimatedWaitTime($queueId) {
        // หาตำแหน่งในคิว
        $sql = "SELECT COUNT(*) as position 
                FROM queue q1, queue q2
                WHERE q1.id = ? 
                AND q2.created_at < q1.created_at 
                AND q2.status = 'waiting'
                AND DATE(q1.created_at) = CURDATE()
                AND DATE(q2.created_at) = CURDATE()";
        
        $result = $this->db->fetchOne($sql, [$queueId]);
        $position = $result['position'] ?? 0;
        
        // เวลาเฉลี่ยต่อคิว (นาที)
        $avgTimePerQueue = 8;
        
        return max(0, $position * $avgTimePerQueue);
    }
    
    /**
     * ดึงรายการคิววันนี้ทั้งหมด
     */
    public function getTodayQueues() {
        $sql = "SELECT q.*, o.customer_name, o.customer_phone, o.total_amount, o.status as order_status,
                       GROUP_CONCAT(CONCAT(mi.name, ' x', oi.quantity) SEPARATOR ', ') as items_summary,
                       CASE 
                           WHEN q.served_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, q.created_at, q.served_at)
                           WHEN q.called_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, q.created_at, NOW())
                           ELSE TIMESTAMPDIFF(MINUTE, q.created_at, NOW())
                       END as wait_time
                FROM queue q
                JOIN orders o ON q.order_id = o.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                WHERE DATE(q.created_at) = CURDATE()
                GROUP BY q.id
                ORDER BY q.created_at DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * รีเซ็ตคิว (ใช้เมื่อต้องการเรียกใหม่)
     */
    public function resetQueue($queueId) {
        $sql = "UPDATE queue SET status = 'waiting', called_at = NULL WHERE id = ?";
        $result = $this->db->query($sql, [$queueId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'รีเซ็ตคิวเรียบร้อย'];
        }
        
        return ['success' => false, 'message' => 'ไม่สามารถรีเซ็ตคิวได้'];
    }
    
    /**
     * อัปเดตสถานะคิว
     */
    public function updateQueueStatus($queueId, $status) {
        $allowedStatuses = ['waiting', 'calling', 'served', 'no_show'];
        
        if (!in_array($status, $allowedStatuses)) {
            return ['success' => false, 'message' => 'สถานะไม่ถูกต้อง'];
        }
        
        $updateFields = ['status = ?'];
        $params = [$status, $queueId];
        
        // เพิ่มเวลาตามสถานะ
        switch ($status) {
            case 'calling':
                $updateFields[] = 'called_at = NOW()';
                break;
            case 'served':
                $updateFields[] = 'served_at = NOW()';
                break;
            case 'no_show':
                $updateFields[] = 'no_show_at = NOW()';
                break;
        }
        
        $sql = "UPDATE queue SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $result = $this->db->query($sql, $params);
        
        if ($result) {
            return ['success' => true, 'message' => 'อัปเดตสถานะเรียบร้อย'];
        }
        
        return ['success' => false, 'message' => 'ไม่สามารถอัปเดตสถานะได้'];
    }
}
?>