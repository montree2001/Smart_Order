<?php
// config/database.php - การเชื่อมต่อฐานข้อมูล
if (!defined('DB_CONFIG_LOADED')) {
    define('DB_CONFIG_LOADED', true);
}

// การตั้งค่าฐานข้อมูล
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_order_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// ตัวแปรสำหรับการเชื่อมต่อ
$connection = null;
$db_connected = false;
$db_error = '';

// ฟังก์ชันเชื่อมต่อฐานข้อมูล
function connect_database() {
    global $connection, $db_connected, $db_error;
    
    try {
        // สร้างการเชื่อมต่อ
        $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // ตรวจสอบการเชื่อมต่อ
        if ($connection->connect_error) {
            throw new Exception("Connection failed: " . $connection->connect_error);
        }
        
        // ตั้งค่า charset
        if (!$connection->set_charset(DB_CHARSET)) {
            throw new Exception("Error setting charset: " . $connection->error);
        }
        
        // ตั้งค่า timezone
        $connection->query("SET time_zone = '+07:00'");
        
        // ตั้งค่า SQL mode
        $connection->query("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
        
        $db_connected = true;
        
        return $connection;
        
    } catch (Exception $e) {
        $db_error = $e->getMessage();
        $db_connected = false;
        
        // Log error
        error_log("Database Connection Error: " . $db_error);
        
        return false;
    }
}

// ฟังก์ชันตรวจสอบการเชื่อมต่อ
function check_database_connection() {
    global $connection, $db_connected;
    
    if (!$db_connected || !$connection || $connection->connect_error) {
        return connect_database();
    }
    
    // Ping เพื่อตรวจสอบว่าการเชื่อมต่อยังใช้งานได้
    if (!$connection->ping()) {
        return connect_database();
    }
    
    return true;
}

// ฟังก์ชันปิดการเชื่อมต่อ
function close_database_connection() {
    global $connection, $db_connected;
    
    if ($connection && $db_connected) {
        $connection->close();
        $connection = null;
        $db_connected = false;
    }
}

// ฟังก์ชัน execute query แบบปลอดภัย
function safe_query($query, $params = [], $types = '') {
    global $connection;
    
    if (!check_database_connection()) {
        return false;
    }
    
    if (empty($params)) {
        return $connection->query($query);
    }
    
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        error_log("Prepare failed: " . $connection->error);
        return false;
    }
    
    if (!empty($params)) {
        if (empty($types)) {
            // สร้าง types string อัตโนมัติ
            $types = str_repeat('s', count($params));
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        error_log("Execute failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    // สำหรับ SELECT
    if (stripos(trim($query), 'SELECT') === 0) {
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    }
    
    // สำหรับ INSERT, UPDATE, DELETE
    $affected_rows = $stmt->affected_rows;
    $insert_id = $connection->insert_id;
    $stmt->close();
    
    return (object) [
        'success' => true,
        'affected_rows' => $affected_rows,
        'insert_id' => $insert_id
    ];
}

// ฟังก์ชันดึงข้อมูลแถวเดียว
function fetch_single($query, $params = [], $types = '') {
    $result = safe_query($query, $params, $types);
    
    if (!$result || !is_object($result)) {
        return false;
    }
    
    return $result->fetch_assoc();
}

// ฟังก์ชันดึงข้อมูลหลายแถว
function fetch_all($query, $params = [], $types = '') {
    $result = safe_query($query, $params, $types);
    
    if (!$result || !is_object($result)) {
        return [];
    }
    
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    return $rows;
}

// ฟังก์ชันนับจำนวนแถว
function count_rows($table, $where = '', $params = [], $types = '') {
    $query = "SELECT COUNT(*) as count FROM `$table`";
    
    if (!empty($where)) {
        $query .= " WHERE $where";
    }
    
    $result = fetch_single($query, $params, $types);
    
    return $result ? (int)$result['count'] : 0;
}

// ฟังก์ชัน insert ข้อมูล
function insert_data($table, $data) {
    if (empty($data)) {
        return false;
    }
    
    $columns = array_keys($data);
    $values = array_values($data);
    $placeholders = str_repeat('?,', count($values) - 1) . '?';
    
    $query = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES ($placeholders)";
    
    return safe_query($query, $values);
}

// ฟังก์ชัน update ข้อมูล
function update_data($table, $data, $where, $where_params = []) {
    if (empty($data)) {
        return false;
    }
    
    $set_clause = [];
    $values = [];
    
    foreach ($data as $column => $value) {
        $set_clause[] = "`$column` = ?";
        $values[] = $value;
    }
    
    $query = "UPDATE `$table` SET " . implode(', ', $set_clause) . " WHERE $where";
    
    // รวม parameters
    $all_params = array_merge($values, $where_params);
    
    return safe_query($query, $all_params);
}

// ฟังก์ชัน delete ข้อมูล
function delete_data($table, $where, $params = []) {
    $query = "DELETE FROM `$table` WHERE $where";
    return safe_query($query, $params);
}

// ฟังก์ชันตรวจสอบว่าตารางมีอยู่หรือไม่
function table_exists($table_name) {
    global $connection;
    
    if (!check_database_connection()) {
        return false;
    }
    
    $query = "SHOW TABLES LIKE ?";
    $result = safe_query($query, [$table_name], 's');
    
    return $result && $result->num_rows > 0;
}

// ฟังก์ชันรัน SQL file
function run_sql_file($file_path) {
    if (!file_exists($file_path)) {
        return ['success' => false, 'message' => 'ไม่พบไฟล์ SQL'];
    }
    
    $sql = file_get_contents($file_path);
    if ($sql === false) {
        return ['success' => false, 'message' => 'ไม่สามารถอ่านไฟล์ SQL ได้'];
    }
    
    // แยก SQL statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        'strlen'
    );
    
    $errors = [];
    $success_count = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        $result = safe_query($statement);
        if ($result) {
            $success_count++;
        } else {
            global $connection;
            $errors[] = $connection->error;
        }
    }
    
    return [
        'success' => empty($errors),
        'success_count' => $success_count,
        'total_statements' => count($statements),
        'errors' => $errors
    ];
}

// ฟังก์ชัน backup ฐานข้อมูล
function backup_database($backup_path = null) {
    if (!$backup_path) {
        $backup_path = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
    }
    
    // สร้างโฟลเดอร์ backup หากไม่มี
    $backup_dir = dirname($backup_path);
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $command = sprintf(
        'mysqldump --user=%s --password=%s --host=%s --single-transaction --routines --triggers %s > %s',
        DB_USER,
        DB_PASS,
        DB_HOST,
        DB_NAME,
        $backup_path
    );
    
    system($command, $return_var);
    
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
    
    $log_message = date('Y-m-d H:i:s') . " - Database Error [$errno]: $errstr in $errfile on line $errline\n";
    error_log($log_message, 3, '../logs/database_errors.log');
    
    return true;
}

// ตั้งค่า error handler
set_error_handler('database_error_handler', E_ALL);

// เชื่อมต่อฐานข้อมูลเมื่อโหลดไฟล์
if (!$db_connected) {
    connect_database();
}

// ตรวจสอบตารางที่จำเป็นเมื่อเชื่อมต่อสำเร็จ
if ($db_connected) {
    $missing_tables = check_required_tables();
    
    if (!empty($missing_tables)) {
        // Log missing tables
        error_log("Missing database tables: " . implode(', ', $missing_tables));
        
        // สามารถสร้างตารางอัตโนมัติได้ (ถ้าต้องการ)
        // create_initial_tables();
    }
}

// Auto-close connection เมื่อจบ script
register_shutdown_function('close_database_connection');

?>