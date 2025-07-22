<?php
class Order {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function createOrder($data) {
        try {
            $this->db->getConnection()->beginTransaction();

            // สร้างหมายเลขคิว
            $queueNumber = $this->generateQueueNumber();

            // เพิ่มออเดอร์
            $sql = "INSERT INTO orders (queue_number, customer_name, customer_phone, total_amount, status, payment_method, payment_status, notes, order_type) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $queueNumber,
                $data['customer_name'],
                $data['customer_phone'],
                $data['total_amount'],
                $data['status'] ?? 'pending',
                $data['payment_method'] ?? 'cash',
                $data['payment_status'] ?? 'pending',
                $data['notes'] ?? null,
                $data['order_type'] ?? 'online'
            ]);

            $orderId = $this->db->lastInsertId();

            // เพิ่มรายการอาหาร
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->addOrderItem($orderId, $item);
                }
            }

            // เพิ่มคิว
            $this->addToQueue($orderId, $queueNumber, $data['estimated_time'] ?? 0);

            $this->db->getConnection()->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            throw $e;
        }
    }

    public function addOrderItem($orderId, $itemData) {
        $sql = "INSERT INTO order_items (order_id, menu_item_id, quantity, price, total_price, special_notes) 
                VALUES (?, ?, ?, ?, ?, ?)";
        return $this->db->query($sql, [
            $orderId,
            $itemData['menu_item_id'],
            $itemData['quantity'],
            $itemData['price'],
            $itemData['total_price'],
            $itemData['special_notes'] ?? null
        ]);
    }

    public function addToQueue($orderId, $queueNumber, $estimatedTime = 0) {
        $sql = "INSERT INTO queue (order_id, queue_number, estimated_time) VALUES (?, ?, ?)";
        return $this->db->query($sql, [$orderId, $queueNumber, $estimatedTime]);
    }

    public function generateQueueNumber() {
        $result = $this->db->fetchOne("CALL GetNextQueueNumber()");
        return $result['queue_number'] ?? 1;
    }

    public function getAllOrders($limit = null) {
        $sql = "SELECT * FROM order_details ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        return $this->db->fetchAll($sql);
    }

    public function getOrderById($id) {
        return $this->db->fetchOne("SELECT * FROM order_details WHERE id = ?", [$id]);
    }

    public function getOrderItems($orderId) {
        return $this->db->fetchAll("SELECT * FROM order_items_detail WHERE order_id = ?", [$orderId]);
    }

    public function updateStatus($orderId, $status) {
        try {
            $this->db->getConnection()->beginTransaction();
            
            // อัปเดตสถานะออเดอร์
            $this->db->query("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$status, $orderId]);
            
            // อัปเดตสถานะคิวตามสถานะออเดอร์
            switch ($status) {
                case 'ready':
                    $this->db->query("UPDATE queue SET status = 'calling', called_at = CURRENT_TIMESTAMP WHERE order_id = ?", [$orderId]);
                    break;
                case 'completed':
                    $this->db->query("UPDATE queue SET status = 'served', served_at = CURRENT_TIMESTAMP WHERE order_id = ?", [$orderId]);
                    break;
            }
            
            $this->db->getConnection()->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            throw $e;
        }
    }

    public function cancelOrder($orderId, $reason = '') {
        $this->db->query("UPDATE orders SET status = 'cancelled', notes = CONCAT(COALESCE(notes, ''), ' | ยกเลิก: ?'), updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$reason, $orderId]);
        $this->db->query("UPDATE queue SET status = 'no_show' WHERE order_id = ?", [$orderId]);
    }

    public function getTodayStats() {
        return $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_sales,
                COUNT(CASE WHEN status IN ('pending', 'confirmed', 'preparing', 'ready') THEN 1 END) as pending_orders,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders
            FROM orders 
            WHERE DATE(created_at) = CURDATE()
        ");
    }

    public function getPopularItems($limit = 5) {
        return $this->db->fetchAll("
            SELECT 
                mi.name,
                mi.category,
                SUM(oi.quantity) as total_sold
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

    public function getOrdersByStatus($status) {
        return $this->db->fetchAll("SELECT * FROM order_details WHERE status = ? ORDER BY created_at", [$status]);
    }

    public function getOrdersByDateRange($startDate, $endDate) {
        return $this->db->fetchAll("
            SELECT * FROM order_details 
            WHERE DATE(created_at) BETWEEN ? AND ? 
            ORDER BY created_at DESC
        ", [$startDate, $endDate]);
    }

    public function getQueuePosition($orderId) {
        $order = $this->getOrderById($orderId);
        if (!$order) return 0;

        $result = $this->db->fetchOne("
            SELECT COUNT(*) as position 
            FROM queue 
            WHERE status = 'waiting' 
            AND queue_number < ? 
            AND DATE(created_at) = CURDATE()
        ", [$order['queue_number']]);

        return $result['position'] + 1;
    }

    public function getEstimatedWaitTime($orderId) {
        $position = $this->getQueuePosition($orderId);
        $settings = $this->db->fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = 'estimated_time_per_item'");
        $timePerItem = $settings['setting_value'] ?? 5;
        
        return $position * $timePerItem;
    }

    public function searchOrders($searchTerm) {
        $sql = "SELECT * FROM order_details WHERE 
                customer_name LIKE ? OR 
                customer_phone LIKE ? OR 
                queue_number = ? OR
                id = ?
                ORDER BY created_at DESC";
        $searchParam = '%' . $searchTerm . '%';
        return $this->db->fetchAll($sql, [$searchParam, $searchParam, $searchTerm, $searchTerm]);
    }

    public function getDailySalesReport($date = null) {
        if (!$date) $date = date('Y-m-d');
        
        return $this->db->fetchOne("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_orders,
                SUM(total_amount) as total_sales,
                AVG(total_amount) as avg_order_value,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
            FROM orders 
            WHERE DATE(created_at) = ?
        ", [$date]);
    }

    public function getMonthlyReport($year, $month) {
        return $this->db->fetchAll("
            SELECT 
                DAY(created_at) as day,
                COUNT(*) as orders,
                SUM(total_amount) as sales
            FROM orders 
            WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?
            AND status = 'completed'
            GROUP BY DAY(created_at)
            ORDER BY day
        ", [$year, $month]);
    }

    public function getPaymentMethodStats($startDate = null, $endDate = null) {
        if (!$startDate) $startDate = date('Y-m-d');
        if (!$endDate) $endDate = date('Y-m-d');

        return $this->db->fetchAll("
            SELECT 
                payment_method,
                COUNT(*) as order_count,
                SUM(total_amount) as total_amount
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
            AND status = 'completed'
            GROUP BY payment_method
        ", [$startDate, $endDate]);
    }

    public function updateOrderNotes($orderId, $notes) {
        return $this->db->query("UPDATE orders SET notes = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [$notes, $orderId]);
    }

    public function getOrdersForKitchen() {
        return $this->db->fetchAll("
            SELECT * FROM order_details 
            WHERE status IN ('confirmed', 'preparing') 
            ORDER BY created_at ASC
        ");
    }

    public function getReadyOrders() {
        return $this->db->fetchAll("
            SELECT * FROM order_details 
            WHERE status = 'ready' 
            ORDER BY created_at ASC
        ");
    }

    public function markOrderReady($orderId) {
        return $this->updateStatus($orderId, 'ready');
    }

    public function markOrderCompleted($orderId) {
        return $this->updateStatus($orderId, 'completed');
    }

    public function getOrderStatistics($days = 30) {
        return $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_amount) as total_revenue,
                AVG(total_amount) as average_order_value,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
                COUNT(DISTINCT customer_phone) as unique_customers
            FROM orders 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ", [$days]);
    }
}
?>