<?php
// admin/api/system_status.php - System Status Check API

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// เริ่มต้นระบบ
require_once '../../config/config.php';

try {
    // ตรวจสอบการเชื่อมต่อฐานข้อมูล
    $dbStatus = checkDatabaseConnection();
    
    // ตรวจสอบพื้นที่ดิสก์
    $diskFree = disk_free_space(ROOT_PATH);
    $diskTotal = disk_total_space(ROOT_PATH);
    $diskUsage = round((($diskTotal - $diskFree) / $diskTotal) * 100, 1);
    
    // ตรวจสอบหน่วยความจำ
    $memoryUsage = memory_get_usage(true);
    $memoryPeak = memory_get_peak_usage(true);
    $memoryLimit = ini_get('memory_limit');
    
    // แปลง memory limit เป็น bytes
    $memoryLimitBytes = convertToBytes($memoryLimit);
    $memoryPercent = round(($memoryUsage / $memoryLimitBytes) * 100, 1);
    
    // ตรวจสอบ PHP version
    $phpVersion = PHP_VERSION;
    $phpVersionOk = version_compare($phpVersion, '7.4.0', '>=');
    
    // ตรวจสอบ extensions ที่จำเป็น
    $requiredExtensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring'];
    $missingExtensions = [];
    
    foreach ($requiredExtensions as $ext) {
        if (!extension_loaded($ext)) {
            $missingExtensions[] = $ext;
        }
    }
    
    // ตรวจสอบไฟล์และโฟลเดอร์ที่จำเป็น
    $requiredPaths = [
        UPLOAD_PATH => 'Uploads Directory',
        LOG_PATH => 'Logs Directory',
        CACHE_PATH => 'Cache Directory'
    ];
    
    $pathIssues = [];
    foreach ($requiredPaths as $path => $name) {
        if (!is_dir($path)) {
            $pathIssues[] = "$name ไม่พบ";
        } elseif (!is_writable($path)) {
            $pathIssues[] = "$name ไม่สามารถเขียนได้";
        }
    }
    
    // ตรวจสอบการตั้งค่า security
    $securityIssues = [];
    
    if (PRODUCTION && ini_get('display_errors')) {
        $securityIssues[] = 'display_errors ควรปิดใน production';
    }
    
    if (!isset($_SERVER['HTTPS']) && PRODUCTION) {
        $securityIssues[] = 'ควรใช้ HTTPS ใน production';
    }
    
    // ตรวจสอบตารางฐานข้อมูลที่จำเป็น
    $requiredTables = [
        'menu_items', 'orders', 'order_items', 'queue', 
        'system_settings', 'notifications'
    ];
    
    $missingTables = [];
    if ($dbStatus) {
        foreach ($requiredTables as $table) {
            if (!$db->tableExists($table)) {
                $missingTables[] = $table;
            }
        }
    }
    
    // คำนวณ uptime
    $uptime = getSystemUptime();
    
    // สร้างคะแนนสุขภาพระบบ
    $healthScore = 100;
    
    if (!$dbStatus) $healthScore -= 30;
    if (!empty($missingExtensions)) $healthScore -= 20;
    if (!empty($pathIssues)) $healthScore -= 15;
    if (!empty($missingTables)) $healthScore -= 20;
    if ($diskUsage > 90) $healthScore -= 10;
    if ($memoryPercent > 80) $healthScore -= 5;
    if (!empty($securityIssues)) $healthScore -= 10;
    if (!$phpVersionOk) $healthScore -= 15;
    
    $healthScore = max(0, $healthScore);
    
    // กำหนดสถานะ
    $status = 'ok';
    $message = 'ระบบทำงานปกติ';
    
    if ($healthScore < 50) {
        $status = 'critical';
        $message = 'ระบบมีปัญหาร้ายแรง';
    } elseif ($healthScore < 80) {
        $status = 'warning';
        $message = 'ระบบมีปัญหาเล็กน้อย';
    }
    
    // ข้อมูลการตอบกลับ
    $response = [
        'status' => $status,
        'message' => $message,
        'health_score' => $healthScore,
        'uptime' => $uptime,
        'timestamp' => date('Y-m-d H:i:s'),
        'checks' => [
            'database' => [
                'status' => $dbStatus ? 'ok' : 'error',
                'message' => $dbStatus ? 'เชื่อมต่อสำเร็จ' : 'ไม่สามารถเชื่อมต่อได้'
            ],
            'php_version' => [
                'status' => $phpVersionOk ? 'ok' : 'warning',
                'message' => "PHP {$phpVersion}" . ($phpVersionOk ? '' : ' (แนะนำ 7.4+)')
            ],
            'extensions' => [
                'status' => empty($missingExtensions) ? 'ok' : 'error',
                'message' => empty($missingExtensions) ? 'ครบถ้วน' : 'ขาด: ' . implode(', ', $missingExtensions)
            ],
            'paths' => [
                'status' => empty($pathIssues) ? 'ok' : 'error',
                'message' => empty($pathIssues) ? 'ปกติ' : implode(', ', $pathIssues)
            ],
            'tables' => [
                'status' => empty($missingTables) ? 'ok' : 'error',
                'message' => empty($missingTables) ? 'ครบถ้วน' : 'ขาดตาราง: ' . implode(', ', $missingTables)
            ],
            'disk_usage' => [
                'status' => $diskUsage < 90 ? 'ok' : 'warning',
                'message' => "{$diskUsage}% ใช้แล้ว",
                'details' => [
                    'free' => formatFileSize($diskFree),
                    'total' => formatFileSize($diskTotal),
                    'usage_percent' => $diskUsage
                ]
            ],
            'memory_usage' => [
                'status' => $memoryPercent < 80 ? 'ok' : 'warning',
                'message' => "{$memoryPercent}% ใช้แล้ว",
                'details' => [
                    'current' => formatFileSize($memoryUsage),
                    'peak' => formatFileSize($memoryPeak),
                    'limit' => $memoryLimit,
                    'usage_percent' => $memoryPercent
                ]
            ],
            'security' => [
                'status' => empty($securityIssues) ? 'ok' : 'warning',
                'message' => empty($securityIssues) ? 'ปลอดภัย' : implode(', ', $securityIssues)
            ]
        ]
    ];
    
    // เพิ่มข้อมูลเพิ่มเติมถ้า debug mode
    if (!PRODUCTION && isset($_GET['debug'])) {
        $response['debug'] = [
            'server_info' => $_SERVER,
            'php_info' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI,
                'os' => PHP_OS,
                'extensions' => get_loaded_extensions()
            ],
            'config' => [
                'site_url' => SITE_URL,
                'root_path' => ROOT_PATH,
                'production' => PRODUCTION
            ]
        ];
    }
    
    // Log สถานะระบบ
    if ($status !== 'ok') {
        writeLog('WARNING', "System health check: {$message}", [
            'health_score' => $healthScore,
            'issues' => array_filter([
                'database' => !$dbStatus,
                'extensions' => !empty($missingExtensions),
                'paths' => !empty($pathIssues),
                'tables' => !empty($missingTables),
                'security' => !empty($securityIssues)
            ])
        ]);
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'เกิดข้อผิดพลาดในการตรวจสอบระบบ',
        'error' => PRODUCTION ? 'Internal Server Error' : $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
    writeLog('ERROR', 'System status check failed: ' . $e->getMessage());
}

// =============== Helper Functions ===============

function convertToBytes($size) {
    $unit = strtoupper(substr($size, -1));
    $value = (int) $size;
    
    switch ($unit) {
        case 'G':
            $value *= 1024;
        case 'M':
            $value *= 1024;
        case 'K':
            $value *= 1024;
    }
    
    return $value;
}

function getSystemUptime() {
    // สำหรับ Linux/Unix
    if (file_exists('/proc/uptime')) {
        $uptime = file_get_contents('/proc/uptime');
        $seconds = (float) explode(' ', $uptime)[0];
        return formatUptime($seconds);
    }
    
    // สำหรับ Windows หรือไม่สามารถหาได้
    $sessionFile = sys_get_temp_dir() . '/smart_order_start_time';
    
    if (!file_exists($sessionFile)) {
        file_put_contents($sessionFile, time());
        return 'เพิ่งเริ่มต้น';
    }
    
    $startTime = (int) file_get_contents($sessionFile);
    $seconds = time() - $startTime;
    
    return formatUptime($seconds);
}

function formatUptime($seconds) {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    $parts = [];
    
    if ($days > 0) {
        $parts[] = "{$days} วัน";
    }
    if ($hours > 0) {
        $parts[] = "{$hours} ชั่วโมง";
    }
    if ($minutes > 0) {
        $parts[] = "{$minutes} นาที";
    }
    
    return empty($parts) ? 'น้อยกว่า 1 นาที' : implode(' ', $parts);
}
?>