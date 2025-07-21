<?php
class Order {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function createOrder($data) {
        try {
            $this->db->beginTransaction();

            // สร้างหมายเลขคิว (ใช้ SQL ธรรมดาแทน stored procedure)
            $queueResult = $this->db->fetchOne("
                SELECT COALESCE(MAX(queue_number), 0) + 1 as queue_number
                FROM orders 
                WHERE DATE(created_at) = CURDATE()
            ");
            $queueNumber = $queueResult['queue_number'] ?? 1;

            // สร้างออเดอร์
            $sql = "INSERT INTO orders (queue_number, customer_name, customer_phone, total_amount, payment_method, payment_status, notes, order_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->query($sql, [
                $queueNumber,
                $data['customer_name'],
                $data['customer_phone'],
                $data['total_amount'],
                $data['payment_method'] ?? 'cash',
                $data['payment_status'] ?? 'pending',
                $data['notes'] ?? '',
                $data['order_type'] ?? 'online'
            ]);

            $orderId = $this->db->lastInsertId();

            // เพิ่มรายการสินค้า
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $itemSql = "INSERT INTO order_items (order_id, menu_item_id, quantity, price, total_price, special_notes) 
                               VALUES (?, ?, ?, ?, ?, ?)";
                    
                    $this->db->query($itemSql, [
                        $orderId,
                        $item['menu_item_id'],
                        $item['quantity'],
                        $item['price'],
                        $item['total_price'],
                        $item['special_notes'] ?? ''
                    ]);
                }
            }

            // สร้างคิว
            $queueSql = "INSERT INTO queue (order_id, queue_number, estimated_time) VALUES (?, ?, ?)";
            $estimatedTime = count($data['items'] ?? []) * 5; // 5 นาทีต่อรายการ
            $this->db->query($queueSql, [$orderId, $queueNumber, $estimatedTime]);

            $this->db->commit();
            
            return ['success' => true, 'order_id' => $orderId, 'queue_number' => $queueNumber];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Order creation error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getAllOrders($limit = null) {
        $sql = "SELECT o.*, q.status as queue_status, q.estimated_time, COUNT(oi.id) as item_count
                FROM orders o
                LEFT JOIN queue q ON o.id = q.order_id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                GROUP BY o.id
                ORDER BY o.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        return $this->db->fetchAll($sql);
    }

    public function getOrderById($id) {
        return $this->db->fetchOne("
            SELECT o.*, q.status as queue_status, q.estimated_time, COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN queue q ON o.id = q.order_id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.id = ?
            GROUP BY o.id
        ", [$id]);
    }

    public function getOrderItems($orderId) {
        return $this->db->fetchAll("
            SELECT oi.*, mi.name as item_name, mi.category, o.queue_number, o.customer_name, o.status as order_status
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            JOIN orders o ON oi.order_id = o.id
            WHERE oi.order_id = ?
        ", [$orderId]);
    }

    public function updateStatus($orderId, $status) {
        try {
            $this->db->beginTransaction();
            
            // อัปเดตสถานะออเดอร์
            $this->db->query("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$status, $orderId]);
            
            // อัปเดตสถานะคิวตามสถานะออเดอร์
            if ($status == 'ready') {
                $this->db->query("UPDATE queue SET status = 'calling', called_at = CURRENT_TIMESTAMP WHERE order_id = ?", [$orderId]);
            } elseif ($status == 'completed') {
                $this->db->query("UPDATE queue SET status = 'served', served_at = CURRENT_TIMESTAMP WHERE order_id = ?", [$orderId]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Update status error: " . $e->getMessage());
            return false;
        }
    }

    public function getTodayStats() {
        $stats = $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders
            FROM orders 
            WHERE DATE(created_at) = CURDATE()
        ");
        
        return $stats ?: [
            'total_orders' => 0,
            'total_sales' => 0,
            'pending_orders' => 0,
            'completed_orders' => 0
        ];
    }

    public function getPopularItems($limit = 5) {
        return $this->db->fetchAll("
            SELECT mi.name, mi.category, SUM(oi.quantity) as total_sold
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            JOIN orders o ON oi.order_id = o.id
            WHERE DATE(o.created_at) = CURDATE()
            AND o.status = 'completed'
            GROUP BY mi.id, mi.name, mi.category
            ORDER BY total_sold DESC
            LIMIT ?
        ", [$limit]);
    }
}