<?php
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Get LINE settings
$settings = $db->fetchAll("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'line_%'");
$lineSettings = [];
foreach ($settings as $setting) {
    $lineSettings[$setting['setting_key']] = $setting['setting_value'];
}

$channelAccessToken = $lineSettings['line_channel_access_token'] ?? '';

if (empty($channelAccessToken)) {
    echo json_encode([
        'success' => false, 
        'message' => 'ไม่พบ Channel Access Token กรุณาตั้งค่าใน LINE Settings'
    ]);
    exit;
}

// Test LINE API connection
$url = 'https://api.line.me/v2/bot/info';
$headers = [
    'Authorization: Bearer ' . $channelAccessToken,
    'Content-Type: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo json_encode([
        'success' => true, 
        'message' => 'การเชื่อมต่อ LINE API สำเร็จ',
        'bot_info' => [
            'basic_id' => $data['basicId'] ?? 'N/A',
            'display_name' => $data['displayName'] ?? 'N/A',
            'picture_url' => $data['pictureUrl'] ?? 'N/A'
        ]
    ]);
} else {
    $errorMessage = 'การเชื่อมต่อ LINE API ล้มเหลว';
    
    if ($httpCode === 401) {
        $errorMessage = 'Channel Access Token ไม่ถูกต้อง';
    } elseif ($httpCode === 403) {
        $errorMessage = 'ไม่มีสิทธิ์เข้าถึง LINE API';
    } elseif (!empty($error)) {
        $errorMessage = 'เกิดข้อผิดพลาด: ' . $error;
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $errorMessage . ' (HTTP ' . $httpCode . ')',
        'response' => $response
    ]);
}