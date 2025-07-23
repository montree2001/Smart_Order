<?php
/**
 * ไฟล์ config/database.php ที่แก้ไขแล้ว
 * แก้ปัญหา SQL syntax error และปรับปรุงการเชื่อมต่อฐานข้อมูล
 */

// การตั้งค่าฐานข้อมูล
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_order_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// เก็บ connection เป็น global variable
$connection = null;
$db_error = null;

// ฟังก์ชันเชื่อมต่อฐานข้อมูล
function connect_database() {
    global $connection, $db_error;
    
    try {
        // สร้าง connection ใหม่
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // ตรวจสอบการเชื่อมต่อ
        if ($connection->connect_error) {
            throw new Exception("Connection failed: " . $connection->connect_error);
        }
        
        // ตั้งค่า charset
        if (!$connection->set_charset(DB_CHARSET)) {
            throw new Exception("Error setting charset: " . $connection->error);
        }
        
        return true;
        
    } catch (Exception $e) {
        $db_error = $e->getMessage();
        error_log("Database connection error: " . $db_error);
        
        // แสดง error แบบ user-friendly
        if (strpos($db_error, 'Access denied') !== false) {
            $db_error = "รหัสผ่านฐานข้อมูลไม่ถูกต้อง";
        } elseif (strpos($db_error, 'Unknown database') !== false) {
            $db_error = "ไม่พบฐานข้อมูล '" . DB_NAME . "'";
        } elseif (strpos($db_error, "Can't connect") !== false) {
            $db_error = "ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ฐานข้อมูลได้";
        }
        
        return false;
    }
}

// ฟังก์ชันตรวจสอบการเชื่อมต่อ
function check_database_connection() {
    global $connection;
    
    if (!$connection) {
        return connect_database();
    }
    
    // ตรวจสอบว่า connection ยังใช้งานได้หรือไม่
    if (!$connection->ping()) {
        return connect_database();
    }
    
    return true;
}

// ฟังก์ชันตรวจสอบว่าตารางมีอยู่หรือไม่ (แก้ไข SQL syntax)
function table_exists($table_name) {
    global $connection;
    
    if (!check_database_connection()) {
        return false;
    }
    
    try {
        // ใช้ INFORMATION_SCHEMA แทน SHOW TABLES LIKE เพื่อหลีกเลี่ยง syntax error
        $table_name = $connection->real_escape_string($table_name);
        $query = "SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES 
                  WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
                  AND TABLE_NAME = '$table_name'";
        
        $result = $connection->query($query);
        
        if (!$result) {
            error_log("Table exists check error: " . $connection->error);
            return false;
        }
        
        $row = $result->fetch_assoc();
        return ($row['count'] > 0);
        
    } catch (Exception $e) {
        error_log("Table exists check exception: " . $e->getMessage());
        return false;
    }
}

// ฟังก์ชัน query ที่ปลอดภัย
function safe_query($sql, $params = []) {
    global $connection;
    
    if (!check_database_connection()) {
        return false;
    }
    
    try {
        // ใช้ prepared statement
        if (!empty($params)) {
            $stmt = $connection->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $connection->error);
            }
            
            // สร้าง type string สำหรับ bind_param
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            return $stmt;
        } else {
            // Query ธรรมดา
            $result = $connection->query($sql);
            if (!$result) {
                throw new Exception("Query failed: " . $connection->error);
            }
            return $result;
        }
        
    } catch (Exception $e) {
        error_log("Safe query error: " . $e->getMessage());
        error_log("SQL: " . $sql);
        return false;
    }
}

// ฟังก์ชันรัน SQL file
function run_sql_file($file_path) {
    global $connection;
    
    if (!file_exists($file_path)) {
        return ['success' => false, 'message' => 'ไม่พบไฟล์ SQL'];
    }
    
    if (!check_database_connection()) {
        return ['success' => false, 'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้'];
    }
    
    try {
        $sql = file_get_contents($file_path);
        
        // แยก SQL statements
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                return !empty($stmt) && !preg_match('/^(\/\*|\-\-)/', $stmt);
            }
        );
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            $result = $connection->query($statement);
            if ($result) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = $connection->error;
                error_log("SQL Error: " . $connection->error);
                error_log("Statement: " . $statement);
            }
        }
        
        return [
            'success' => $error_count === 0,
            'message' => "ดำเนินการ $success_count คำสั่ง, ผิดพลาด $error_count คำสั่ง",
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false, 
            'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()
        ];
    }
}

// ฟังก์ชันสำรองฐานข้อมูล
function backup_database($backup_path = null) {
    if (!$backup_path) {
        $backup_path = __DIR__ . '/../backups/' . DB_NAME . '_' . 
                      date('Y-m-d_H-i-s') . '.sql';
    }
    
    // สร้างโฟลเดอร์ backup หากไม่มี
    $backup_dir = dirname($backup_path);
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $command = sprintf(
        'mysqldump --user=%s --password=%s --host=%s --single-transaction --routines --triggers %s > %s',
        escapeshellarg(DB_USER),
        escapeshellarg(DB_PASS),
        escapeshellarg(DB_HOST),
        escapeshellarg(DB_NAME),
        escapeshellarg($backup_path)
    );
    
    exec($command, $output, $return_var);
    
    return $return_var === 0 ? $backup_path : false;
}

// ฟังก์ชันเริ่มต้น transaction
function begin_transaction() {
    global $connection;
    
    if (!check_database_connection()) {
        return false;
    }
    
    return $connection->begin_transaction();
}

// ฟังก์ชัน commit transaction
function commit_transaction() {
    global $connection;
    return $connection && $connection->commit();
}

// ฟังก์ชัน rollback transaction
function rollback_transaction() {
    global $connection;
    return $connection && $connection->rollback();
}

// ฟังก์ชันตรวจสอบและสร้างตารางที่จำเป็น
function check_required_tables() {
    $required_tables = [
        'users', 'menu_categories', 'menu_items', 'customers', 
        'orders', 'order_items', 'queue', 'payments', 
        'notifications', 'system_settings'
    ];
    
    $missing_tables = [];
    
    foreach ($required_tables as $table) {
        if (!table_exists($table)) {
            $missing_tables[] = $table;
        }
    }
    
    return $missing_tables;
}

// ฟังก์ชันสร้างตารางเริ่มต้น
function create_initial_tables() {
    $sql_file = __DIR__ . '/smart_order_database.sql';
    
    if (file_exists($sql_file)) {
        return run_sql_file($sql_file);
    }
    
    return ['success' => false, 'message' => 'ไม่พบไฟล์ SQL สำหรับสร้างตาราง'];
}

// ฟังก์ชัน escape string
function escape_string($string) {
    global $connection;
    
    if (!check_database_connection()) {
        return false;
    }
    
    return $connection->real_escape_string($string);
}

// ฟังก์ชันได้ affected rows
function get_affected_rows() {
    global $connection;
    return $connection ? $connection->affected_rows : 0;
}

// ฟังก์ชันได้ insert id
function get_insert_id() {
    global $connection;
    return $connection ? $connection->insert_id : 0;
}

// ฟังก์ชันได้ error message
function get_db_error() {
    global $connection, $db_error;
    
    if ($connection) {
        return $connection->error;
    }
    
    return $db_error;
}

// Error handler สำหรับ database
function database_error_handler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $log_message = date('Y-m-d H:i:s') . " [ERROR $errno] $errstr in $errfile:$errline" . PHP_EOL;
    error_log($log_message, 3, __DIR__ . '/../logs/database_errors.log');
    
    return true;
}

// ตั้งค่า error handler
set_error_handler('database_error_handler');

// เชื่อมต่อฐานข้อมูลทันทีเมื่อ include ไฟล์นี้
if (!connect_database()) {
    // แสดง error page แทนการ die
    http_response_code(500);
    include __DIR__ . '/../views/errors/database_error.php';
    exit;
}

// ตรวจสอบตารางที่จำเป็น
$missing_tables = check_required_tables();
if (!empty($missing_tables)) {
    // สร้างตารางอัตโนมัติ หรือ redirect ไปหน้า setup
    error_log("Missing tables: " . implode(', ', $missing_tables));
    // แสดงหน้า setup แทนการ die
    if (!defined('SKIP_TABLE_CHECK')) {
        header('Location: /setup.php');
        exit;
    }
}

function load_system_settings() {
    global $connection, $system_settings;
    $system_settings = [];
    if (!$connection) {
        return;
    }
    $result = mysqli_query($connection, "SELECT setting_key, setting_value FROM system_settings");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $system_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// ดึงแถวเดียวจากฐานข้อมูล
function db_fetch_one($sql, $params = []) {
    $result = safe_query($sql, $params);
    if (!$result) return false;

    // ถ้าเป็น mysqli_stmt (prepared statement)
    if ($result instanceof mysqli_stmt) {
        $result->store_result();
        $meta = $result->result_metadata();
        if (!$meta) return false;
        $fields = [];
        $row = [];
        while ($field = $meta->fetch_field()) {
            $fields[] = &$row[$field->name];
        }
        call_user_func_array([$result, 'bind_result'], $fields);
        if ($result->fetch()) {
            $assoc = [];
            foreach ($row as $key => $val) {
                $assoc[$key] = $val;
            }
            return $assoc;
        }
        return false;
    }
    // ถ้าเป็น mysqli_result (query ธรรมดา)
    if ($result instanceof mysqli_result) {
        return $result->fetch_assoc();
    }
    return false;
}

// ใช้สำหรับ execute (insert, update, delete)
function db_execute($sql, $params = []) {
    $stmt = safe_query($sql, $params);
    if (!$stmt) return false;
    return $stmt->affected_rows;
}
?>