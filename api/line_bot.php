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
        $text = $event['message']['text'];
        $replyToken = $event['replyToken'];

        // Handle different commands
        if (strpos($text, 'คิว') !== false || strpos($text, 'queue') !== false) {
            $this->handleQueueInquiry($replyToken, $userId);
        } elseif (strpos($text, 'เมนู') !== false || strpos($text, 'menu') !== false) {
            $this->handleMenuInquiry($replyToken);
        } else {
            $this->replyMessage($replyToken, 'สวัสดีครับ! พิมพ์ "คิว" เพื่อตรวจสอบสถานะคิว หรือ "เมนู" เพื่อดูรายการอาหาร');
        }
    }

    private function handleQueueInquiry($replyToken, $userId) {
        // Find user's queue by phone number (you might need to implement user linking)
        $message = "กรุณาแจ้งหมายเลขโทรศัพท์ที่ใช้สั่งอาหารเพื่อตรวจสอบสถานะคิว";
        $this->replyMessage($replyToken, $message);
    }

    private function handleMenuInquiry($replyToken) {
        $menuItems = $this->db->fetchAll("SELECT name, price, category FROM menu_items WHERE available = 1 LIMIT 10");
        
        $message = "📋 เมนูแนะนำ\n\n";
        foreach ($menuItems as $item) {
            $message .= "🍽️ " . $item['name'] . "\n";
            $message .= "💰 " . number_format($item['price'], 2) . " บาท\n\n";
        }
        $message .= "สั่งอาหารได้ที่: " . SITE_URL . "customer/";
        
        $this->replyMessage($replyToken, $message);
    }

    public function sendMessage($userId, $message) {
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
        // In a real implementation, you'd need to link phone numbers to LINE user IDs
        $message = "🎉 ยืนยันการสั่งอาหารแล้ว!\n\n";
        $message .= "📋 หมายเลขคิว: #{$queueNumber}\n";
        $message .= "📱 เบอร์โทร: {$phone}\n\n";
        $message .= "📝 รายการอาหาร:\n{$orderDetails}\n\n";
        $message .= "⏰ กรุณารอการแจ้งเตือนเมื่อถึงคิวของท่าน";
        
        // You would need to implement phone-to-LINE-ID mapping
        // For demo purposes, we'll just log this
        error_log("LINE Message to {$phone}: {$message}");
    }

    public function sendQueueAlert($phone, $queueNumber, $estimatedTime) {
        $message = "⚠️ แจ้งเตือนคิว!\n\n";
        $message .= "📋 หมายเลขคิว: #{$queueNumber}\n";
        $message .= "⏰ เหลืออีกประมาณ {$estimatedTime} นาที\n";
        $message .= "📍 กรุณาเตรียมตัวมารับอาหาร";
        
        error_log("LINE Alert to {$phone}: {$message}");
    }

    public function sendReadyNotification($phone, $queueNumber) {
        $message = "✅ อาหารพร้อมแล้ว!\n\n";
        $message .= "📋 หมายเลขคิว: #{$queueNumber}\n";
        $message .= "🏃‍♂️ กรุณามารับอาหารได้เลยครับ";
        
        error_log("LINE Ready notification to {$phone}: {$message}");
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
