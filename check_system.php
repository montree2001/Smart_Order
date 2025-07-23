<?php
/**
 * ไฟล์ check_system.php - ตรวจสอบสถานะระบบ
 */

require_once 'config/database.php';

header('Content-Type: application/json; charset=utf-8');

$checks = [];
$overall_status = 'healthy';

// 1. ตรวจสอบ PHP Version
$php_version_check = version_compare(PHP_VERSION, '7.4.0', '>=');
$checks['php_version'] = [
    'name' => 'PHP Version',
    'status' => $php_version_check ? 'pass' : 'fail',
    'value' => PHP_VERSION,
    'requirement' => '>= 7.4.0',
    'message' => $php_version_check ? 'PHP version ใช้งานได้' : 'PHP version ต่ำเกินไป'
];

if (!$php_version_check) {
    $overall_status = 'warning';
}

// 2. ตรวจสอบ Extensions
$required_extensions = ['mysqli', 'json', 'mbstring', 'curl'];
$extension_status = true;

foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    $checks["ext_$ext"] = [
        'name' => "Extension: $ext",
        'status' => $loaded ? 'pass' : 'fail',
        'value' => $loaded ? 'Loaded' : 'Not loaded',
        'requirement' => 'Required',
        'message' => $loaded ? "Extension $ext พร้อมใช้งาน" : "Extension $ext ไม่พร้อมใช้งาน"
    ];
    
    if (!$loaded) {
        $extension_status = false;
        $overall_status = 'error';
    }
}

// 3. ตรวจสอบการเชื่อมต่อฐานข้อมูล
$db_status = check_database_connection();
$checks['database_connection'] = [
    'name' => 'Database Connection',
    'status' => $db_status ? 'pass' : 'fail',
    'value' => $db_status ? 'Connected' : 'Failed',
    'requirement' => 'Required',
    'message' => $db_status ? 'เชื่อมต่อฐานข้อมูลสำเร็จ' : 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'
];

if (!$db_status) {
    $overall_status = 'error';
} else {
    // 4. ตรวจสอบตารางที่จำเป็น
    $missing_tables = check_required_tables();
    $tables_ok = empty($missing_tables);
    
    $checks['database_tables'] = [
        'name' => 'Database Tables',
        'status' => $tables_ok ? 'pass' : 'warning',
        'value' => $tables_ok ? 'All tables exist' : count($missing_tables) . ' missing',
        'requirement' => '10 tables required',
        'message' => $tables_ok ? 'ตารางฐานข้อมูลครบถ้วน' : 'ตารางฐานข้อมูลไม่ครบ: ' . implode(', ', $missing_tables),
        'details' => $missing_tables
    ];
    
    if (!$tables_ok) {
        $overall_status = 'warning';
    }
    
    // 5. ตรวจสอบข้อมูลเริ่มต้น
    if ($tables_ok) {
        // ตรวจสอบ admin user
        $admin_exists = false;
        $result = safe_query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        if ($result) {
            $row = $result->fetch_assoc();
            $admin_exists = $row['count'] > 0;
        }
        
        $checks['admin_user'] = [
            'name' => 'Admin User',
            'status' => $admin_exists ? 'pass' : 'warning',
            'value' => $admin_exists ? 'Exists' : 'Not found',
            'requirement' => 'At least 1 admin',
            'message' => $admin_exists ? 'มีผู้ดูแลระบบ' : 'ไม่พบผู้ดูแลระบบ'
        ];
        
        // ตรวจสอบการตั้งค่าระบบ
        $settings_count = 0;
        $result = safe_query("SELECT COUNT(*) as count FROM system_settings");
        if ($result) {
            $row = $result->fetch_assoc();
            $settings_count = $row['count'];
        }
        
        $checks['system_settings'] = [
            'name' => 'System Settings',
            'status' => $settings_count > 0 ? 'pass' : 'warning',
            'value' => "$settings_count settings",
            'requirement' => 'Basic settings required',
            'message' => $settings_count > 0 ? 'มีการตั้งค่าระบบ' : 'ไม่พบการตั้งค่าระบบ'
        ];
    }
}

// 6. ตรวจสอบสิทธิ์ไฟล์
$writable_dirs = [
    'uploads' => __DIR__ . '/uploads',
    'logs' => __DIR__ . '/logs',
    'cache' => __DIR__ . '/cache',
    'backups' => __DIR__ . '/backups'
];

foreach ($writable_dirs as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    
    $checks["dir_$name"] = [
        'name' => "Directory: $name",
        'status' => $writable ? 'pass' : ($exists ? 'warning' : 'fail'),
        'value' => $exists ? ($writable ? 'Writable' : 'Read-only') : 'Not exists',
        'requirement' => 'Writable',
        'message' => $writable ? "โฟลเดอร์ $name พร้อมใช้งาน" : ($exists ? "โฟลเดอร์ $name ไม่สามารถเขียนได้" : "ไม่พบโฟลเดอร์ $name"),
        'path' => $path
    ];
    
    if (!$writable) {
        if ($overall_status === 'healthy') {
            $overall_status = 'warning';
        }
    }
}

// 7. ตรวจสอบการตั้งค่า PHP
$php_settings = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];

$checks['php_settings'] = [
    'name' => 'PHP Settings',
    'status' => 'info',
    'value' => 'See details',
    'requirement' => 'Recommended values',
    'message' => 'การตั้งค่า PHP',
    'details' => $php_settings
];

// 8. ตรวจสอบ Server Software
$checks['server_info'] = [
    'name' => 'Server Software',
    'status' => 'info',
    'value' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'requirement' => 'Any web server',
    'message' => 'ข้อมูลเซิร์ฟเวอร์'
];

// สร้าง summary
$pass_count = 0;
$warning_count = 0;
$fail_count = 0;

foreach ($checks as $check) {
    switch ($check['status']) {
        case 'pass':
            $pass_count++;
            break;
        case 'warning':
            $warning_count++;
            break;
        case 'fail':
            $fail_count++;
            break;
    }
}

$summary = [
    'overall_status' => $overall_status,
    'total_checks' => count($checks),
    'pass_count' => $pass_count,
    'warning_count' => $warning_count,
    'fail_count' => $fail_count,
    'timestamp' => date('Y-m-d H:i:s'),
    'system_ready' => ($overall_status !== 'error')
];

// Response
$response = [
    'success' => true,
    'summary' => $summary,
    'checks' => $checks,
    'recommendations' => []
];

// เพิ่มคำแนะนำ
if ($fail_count > 0) {
    $response['recommendations'][] = 'มีปัญหาที่ต้องแก้ไขก่อนใช้งาน กรุณาติดตั้ง PHP extensions ที่จำเป็น';
}

if ($warning_count > 0) {
    $response['recommendations'][] = 'มีการตั้งค่าที่ควรปรับปรุง เพื่อประสิทธิภาพที่ดีขึ้น';
}

if (!empty($missing_tables)) {
    $response['recommendations'][] = 'ควรสร้างตารางที่หายไปในฐานข้อมูล โดยไปที่หน้า /setup.php';
}

if ($overall_status === 'healthy') {
    $response['recommendations'][] = 'ระบบพร้อมใช้งาน!';
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>