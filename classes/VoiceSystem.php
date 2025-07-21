<?php
class VoiceSystem {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    public function generateQueueAnnouncement($queueNumber, $customerName = null) {
        $message = "หมายเลขคิว {$queueNumber}";
        
        if ($customerName) {
            $message .= " คุณ{$customerName}";
        }
        
        $message .= " ขอเชิญมารับออเดอร์ได้ครับ";
        
        return [
            'text' => $message,
            'audio_url' => $this->generateAudioFile($message)
        ];
    }

    private function generateAudioFile($text) {
        // In a real implementation, you would use a TTS service
        // For now, we'll return a placeholder
        return SITE_URL . 'assets/sounds/queue_' . md5($text) . '.mp3';
    }

    public function announceQueue($queueNumber, $customerName = null) {
        $announcement = $this->generateQueueAnnouncement($queueNumber, $customerName);
        
        // Log the announcement
        $this->db->query(
            "INSERT INTO queue_announcements (queue_number, message, created_at) VALUES (?, ?, NOW())",
            [$queueNumber, $announcement['text']]
        );
        
        return $announcement;
    }
}