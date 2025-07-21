
<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

$settings = $db->fetchAll("SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE 'line_%'");
$lineSettings = [];
foreach ($settings as $setting) {
    $lineSettings[$setting['setting_key']] = $setting['setting_value'];
}

$channelAccessToken = $lineSettings['line_channel_access_token'] ?? '';

if (empty($channelAccessToken)) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบ Channel Access Token']);
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

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo json_encode([
        'success' => true, 
        'message' => 'การเชื่อมต่อสำเร็จ',
        'bot_info' => $data
    ]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'การเชื่อมต่อล้มเหลว (HTTP ' . $httpCode . ')'
    ]);
}