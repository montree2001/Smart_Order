<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../classes/LineBot.php';

$entityBody = file_get_contents('php://input');
$data = json_decode($entityBody, true);

if (!$data || !isset($data['events'])) {
    http_response_code(400);
    exit;
}

$lineBot = new LineBot($db);

foreach ($data['events'] as $event) {
    $lineBot->handleEvent($event);
}

http_response_code(200);
echo 'OK';
