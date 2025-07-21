<?php
class LineBot {
    private $db;
    private $channelAccessToken;
    private $channelSecret;

    public function __construct($database) {
        $this->db = $database;
        $this->loadSettings();
    }

    private function loadSettings() {
        $settings = $this->db->fetchAll("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'line_%'");
        foreach ($settings as $setting) {
            if ($setting['setting_key'] == 'line_channel_access_token') {
                $this->channelAccessToken = $setting['setting_value'];
            } elseif ($setting['setting_key'] == 'line_channel_secret') {
                $this->channelSecret = $setting['setting_value'];
            }
        }
    }

    public function handleEvent($event) {
        if ($event['type'] === 'message' && $event['message']['type'] === 'text') {
            $this->handleTextMessage($event);
        }
    }

    private function handleTextMessage($event) {
        $userId = $event['source']['userId'];
        $text = trim($event['message']['text']);
        $replyToken = $event['replyToken'];

        // Handle different commands
        if (strpos($text, 'คิว') !== false || strpos($text, 'queue') !== false) {
            $this->handleQueueInquiry($replyToken, $text);
        } elseif (strpos($text, 'เมนู') !== false || strpos($text, 'menu') !== false) {
            $this->handleMenuInquiry($replyToken);
        } elseif (preg_match('/^(0[0-9]{8,9})$/', $text)) {
            $this->handlePhoneNumber($replyToken, $text);
        } else {
            $this->replyWelcomeMessage($replyToken);
        }
    }

    private function handleQueueInquiry($replyToken, $text) {
        // Extract phone number from text if provided
        preg_match('/\b0[0-9]{8,9}\b/', $text, $matches);
        
        if (!empty($matches)) {
            $phone = $matches[0];
            $this->checkQueueStatus($replyToken, $phone);
        } else {
            $message = "กรุณาส่งหมายเลขโทรศัพท์ที่ใช้สั่งอาหาร (เช่น 0812345678) เพื่อตรวจสอบสถานะคิว";
            $this->replyMessage($replyToken, $message);
        }
    }

    private function handlePhoneNumber($replyToken, $phone) {
        $this->checkQueueStatus($replyToken, $phone);
    }

    private function checkQueueStatus($replyToken, $phone) {
        $order = $this->db->fetchOne("
            SELECT o.*, q.status as queue_status, q.estimated_time
            FROM orders o
            LEFT JOIN queue q ON o.id = q.order_id
            WHERE o.customer_phone = ? 
            AND DATE(o.created_at) = CURDATE()
            AND o.status NOT IN ('completed', 'cancelled')
            ORDER BY o.created_at DESC
            LIMIT 1
        ", [$phone]);

        if ($order) {
            $waitingQueues = $this->db->fetchOne("
                SELECT COUNT(*) as waiting_count
                FROM queue q
                JOIN orders o ON q.order_id = o.id
                WHERE q.queue_number < ? 
                AND q.status = 'waiting'
                AND DATE(o.created_at) = CURDATE()
            ", [$order['queue_number']]);

            $message = "🎯 สถานะคิวของคุณ\n\n";
            $message .= "📋 หมายเลขคิว: #{$order['queue_number']}\n";
            $message .= "👤 ชื่อ: {$order['customer_name']}\n";
            $message .= "📱 เบอร์: {$order['customer_phone']}\n\n";
            
            $statusText = $this->getQueueStatusText($order['queue_status']);
            $message .= "📊 สถานะ: {$statusText}\n";
            
            if ($order['queue_status'] == 'waiting') {
                $message .= "⏳ คิวที่รออยู่ข้างหน้า: {$waitingQueues['waiting_count']} คิว\n";
                $message .= "⏰ เวลาโดยประมาณ: {$order['estimated_time']} นาที\n";
            } elseif ($order['queue_status'] == 'calling') {
                $message .= "🔔 กำลังเรียกคิวของคุณ! กรุณามารับอาหารได้เลย\n";
            }
            
            $message .= "\n💰 ยอดรวม: " . number_format($order['total_amount'], 2) . " บาท";
            
        } else {
            $message = "❌ ไม่พบข้อมูลคิวสำหรับหมายเลข {$phone} ในวันนี้\n\n";
            $message .= "กรุณาตรวจสอบหมายเลขโทรศัพท์ หรือสั่งอาหารที่: " . SITE_URL . "customer/";
        }

        $this->replyMessage($replyToken, $message);
    }

    private function handleMenuInquiry($replyToken) {
        $menuItems = $this->db->fetchAll("
            SELECT name, price, category 
            FROM menu_items 
            WHERE available = 1 
            ORDER BY category, name 
            LIMIT 10
        ");
        
        $message = "📋 เมนูแนะนำวันนี้\n\n";
        $currentCategory = '';
        
        foreach ($menuItems as $item) {
            if ($currentCategory != $item['category']) {
                $currentCategory = $item['category'];
                $message .= "🍽️ {$currentCategory}\n";
            }
            $message .= "   • " . $item['name'] . " - " . number_format($item['price'], 2) . " บาท\n";
        }
        
        $message .= "\n🛒 สั่งอาหารออนไลน์: " . SITE_URL . "customer/";
        $message .= "\n📞 สั่งผ่านโทรศัพท์: 02-XXX-XXXX";
        
        $this->replyMessage($replyToken, $message);
    }

    private function replyWelcomeMessage($replyToken) {
        $message = "🍽️ ยินดีต้อนรับสู่ร้านอาหารของเรา!\n\n";
        $message .= "📋 พิมพ์ 'เมนู' เพื่อดูรายการอาหาร\n";
        $message .= "🔍 พิมพ์ 'คิว' หรือหมายเลขโทรศัพท์เพื่อตรวจสอบสถานะ\n";
        $message .= "🛒 สั่งอาหารออนไลน์: " . SITE_URL . "customer/\n\n";
        $message .= "มีคำถามอะไรสามารถถามได้เลยนะครับ! 😊";
        
        $this->replyMessage($replyToken, $message);
    }

    private function getQueueStatusText($status) {
        $texts = [
            'waiting' => '⏳ รอเรียกคิว',
            'calling' => '🔔 กำลังเรียกคิว',
            'served' => '✅ บริการแล้ว',
            'no_show' => '❌ ไม่มาติดต่อ'
        ];
        return $texts[$status] ?? $status;
    }

    public function sendMessage($userId, $message) {
        if (empty($this->channelAccessToken)) {
            return false;
        }

        $url = 'https://api.line.me/v2/bot/message/push';
        $data = [
            'to' => $userId,
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        ];
        
        return $this->callAPI($url, $data);
    }

    public function replyMessage($replyToken, $message) {
        if (empty($this->channelAccessToken)) {
            return false;
        }

        $url = 'https://api.line.me/v2/bot/message/reply';
        $data = [
            'replyToken' => $replyToken,
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        ];
        
        return $this->callAPI($url, $data);
    }

    public function sendOrderConfirmation($phone, $queueNumber, $orderDetails) {
        $message = "🎉 ยืนยันการสั่งอาหารแล้ว!\n\n";
        $message .= "📋 หมายเลขคิว: #{$queueNumber}\n";
        $message .= "📱 เบอร์โทร: {$phone}\n\n";
        $message .= "📝 รายการอาหาร:\n{$orderDetails}\n\n";
        $message .= "⏰ กรุณารอการแจ้งเตือนเมื่อถึงคิวของท่าน\n";
        $message .= "🔍 ตรวจสอบสถานะ: ส่งหมายเลขโทรศัพท์มาที่แชทนี้";
        
        // Log notification (in real app, you'd send to actual LINE user)
        $this->logNotification('order_confirmed', $phone, $message);
        
        return true;
    }

    public function sendQueueAlert($phone, $queueNumber, $estimatedTime) {
        $message = "⚠️ แจ้งเตือนคิว!\n\n";
        $message .= "📋 หมายเลขคิว: #{$queueNumber}\n";
        $message .= "⏰ เหลืออีกประมาณ {$estimatedTime} นาที\n";
        $message .= "📍 กรุณาเตรียมตัวมารับอาหาร";
        
        $this->logNotification('queue_alert', $phone, $message);
        
        return true;
    }

    public function sendReadyNotification($phone, $queueNumber) {
        $message = "✅ อาหารพร้อมแล้ว!\n\n";
        $message .= "📋 หมายเลขคิว: #{$queueNumber}\n";
        $message .= "🏃‍♂️ กรุณามารับอาหารได้เลยครับ\n";
        $message .= "📍 ที่เคาน์เตอร์ร้านอาหาร";
        
        $this->logNotification('ready', $phone, $message);
        
        return true;
    }

    private function logNotification($type, $phone, $message) {
        // ในระบบจริงจะส่ง LINE message จริง ๆ
        // ตอนนี้เก็บ log ไว้ใน database
        $this->db->query("
            INSERT INTO notifications (order_id, type, message, sent_at, status) 
            SELECT o.id, ?, ?, NOW(), 'sent'
            FROM orders o 
            WHERE o.customer_phone = ? 
            AND DATE(o.created_at) = CURDATE()
            ORDER BY o.created_at DESC 
            LIMIT 1
        ", [$type, $message, $phone]);
    }

    private function callAPI($url, $data) {
        $headers = [
            'Authorization: Bearer ' . $this->channelAccessToken,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ['status' => $httpCode, 'response' => $response];
    }
}