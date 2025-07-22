<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$pageTitle = 'ตั้งค่าระบบ';
$activePage = 'settings';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_shop_info':
                $shopSettings = [
                    'shop_name' => $_POST['shop_name'],
                    'shop_phone' => $_POST['shop_phone'],
                    'shop_address' => $_POST['shop_address'],
                    'shop_email' => $_POST['shop_email'],
                    'shop_description' => $_POST['shop_description']
                ];
                
                foreach ($shopSettings as $key => $value) {
                    $db->query("INSERT INTO system_settings (setting_key, setting_value, description, created_at) 
                               VALUES (?, ?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()", 
                               [$key, $value, '', $value]);
                }
                $message = "บันทึกข้อมูลร้านสำเร็จ";
                break;
                
            case 'save_queue_settings':
                $queueSettings = [
                    'queue_reset_daily' => isset($_POST['queue_reset_daily']) ? '1' : '0',
                    'max_queue_per_day' => $_POST['max_queue_per_day'],
                    'estimated_time_per_item' => $_POST['estimated_time_per_item'],
                    'notification_before_queue' => $_POST['notification_before_queue'],
                    'auto_advance_queue' => isset($_POST['auto_advance_queue']) ? '1' : '0',
                    'queue_display_limit' => $_POST['queue_display_limit']
                ];
                
                foreach ($queueSettings as $key => $value) {
                    $db->query("INSERT INTO system_settings (setting_key, setting_value, description, created_at) 
                               VALUES (?, ?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()", 
                               [$key, $value, '', $value]);
                }
                $message = "บันทึกการตั้งค่าคิวสำเร็จ";
                break;
                
            case 'save_payment_settings':
                $paymentSettings = [
                    'promptpay_id' => $_POST['promptpay_id'],
                    'promptpay_name' => $_POST['promptpay_name'],
                    'enable_cash_payment' => isset($_POST['enable_cash_payment']) ? '1' : '0',
                    'enable_qr_payment' => isset($_POST['enable_qr_payment']) ? '1' : '0',
                    'enable_card_payment' => isset($_POST['enable_card_payment']) ? '1' : '0',
                    'tax_rate' => $_POST['tax_rate'],
                    'service_charge' => $_POST['service_charge']
                ];
                
                foreach ($paymentSettings as $key => $value) {
                    $db->query("INSERT INTO system_settings (setting_key, setting_value, description, created_at) 
                               VALUES (?, ?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()", 
                               [$key, $value, '', $value]);
                }
                $message = "บันทึกการตั้งค่าการชำระเงินสำเร็จ";
                break;
                
            case 'save_receipt_settings':
                $receiptSettings = [
                    'receipt_header_text' => $_POST['receipt_header_text'],
                    'receipt_footer_text' => $_POST['receipt_footer_text'],
                    'receipt_logo_url' => $_POST['receipt_logo_url'],
                    'auto_print_receipt' => isset($_POST['auto_print_receipt']) ? '1' : '0',
                    'send_receipt_line' => isset($_POST['send_receipt_line']) ? '1' : '0',
                    'receipt_paper_size' => $_POST['receipt_paper_size']
                ];
                
                foreach ($receiptSettings as $key => $value) {
                    $db->query("INSERT INTO system_settings (setting_key, setting_value, description, created_at) 
                               VALUES (?, ?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()", 
                               [$key, $value, '', $value]);
                }
                $message = "บันทึกการตั้งค่าใบเสร็จสำเร็จ";
                break;
                
            case 'save_notification_settings':
                $notificationSettings = [
                    'enable_line_notifications' => isset($_POST['enable_line_notifications']) ? '1' : '0',
                    'enable_email_notifications' => isset($_POST['enable_email_notifications']) ? '1' : '0',
                    'enable_sms_notifications' => isset($_POST['enable_sms_notifications']) ? '1' : '0',
                    'notification_sound' => isset($_POST['notification_sound']) ? '1' : '0',
                    'voice_language' => $_POST['voice_language'],
                    'voice_speed' => $_POST['voice_speed'],
                    'voice_volume' => $_POST['voice_volume']
                ];
                
                foreach ($notificationSettings as $key => $value) {
                    $db->query("INSERT INTO system_settings (setting_key, setting_value, description, created_at) 
                               VALUES (?, ?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()", 
                               [$key, $value, '', $value]);
                }
                $message = "บันทึกการตั้งค่าการแจ้งเตือนสำเร็จ";
                break;
                
            case 'save_system_settings':
                $systemSettings = [
                    'site_timezone' => $_POST['site_timezone'],
                    'default_language' => $_POST['default_language'],
                    'date_format' => $_POST['date_format'],
                    'currency_symbol' => $_POST['currency_symbol'],
                    'enable_maintenance_mode' => isset($_POST['enable_maintenance_mode']) ? '1' : '0',
                    'maintenance_message' => $_POST['maintenance_message'],
                    'auto_refresh_interval' => $_POST['auto_refresh_interval'],
                    'enable_debug_mode' => isset($_POST['enable_debug_mode']) ? '1' : '0'
                ];
                
                foreach ($systemSettings as $key => $value) {
                    $db->query("INSERT INTO system_settings (setting_key, setting_value, description, created_at) 
                               VALUES (?, ?, ?, NOW()) 
                               ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()", 
                               [$key, $value, '', $value]);
                }
                $message = "บันทึกการตั้งค่าระบบสำเร็จ";
                break;
        }
    }
}

// Get all settings
$settings = $db->fetchAll("SELECT * FROM system_settings ORDER BY setting_key");
$settingsArray = [];
foreach ($settings as $setting) {
    $settingsArray[$setting['setting_key']] = $setting['setting_value'];
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fas fa-cog"></i> ตั้งค่าระบบ</h1>
    <div class="btn-group">
        <button type="button" class="btn btn-outline-primary" onclick="exportSettings()">
            <i class="fas fa-download"></i> Export
        </button>
        <button type="button" class="btn btn-outline-warning" onclick="resetToDefault()">
            <i class="fas fa-undo"></i> Reset
        </button>
        <button type="button" class="btn btn-primary" onclick="backupSettings()">
            <i class="fas fa-save"></i> Backup
        </button>
    </div>
</div>

<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Settings Navigation -->
<div class="row">
    <div class="col-lg-3 mb-4">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0"><i class="fas fa-list"></i> หมวดการตั้งค่า</h6>
            </div>
            <div class="list-group list-group-flush">
                <a href="#shop-info" class="list-group-item list-group-item-action active" data-tab="shop-info">
                    <i class="fas fa-store me-2"></i> ข้อมูลร้าน
                </a>
                <a href="#queue-settings" class="list-group-item list-group-item-action" data-tab="queue-settings">
                    <i class="fas fa-users me-2"></i> การตั้งค่าคิว
                </a>
                <a href="#payment-settings" class="list-group-item list-group-item-action" data-tab="payment-settings">
                    <i class="fas fa-credit-card me-2"></i> การชำระเงิน
                </a>
                <a href="#receipt-settings" class="list-group-item list-group-item-action" data-tab="receipt-settings">
                    <i class="fas fa-receipt me-2"></i> การตั้งค่าใบเสร็จ
                </a>
                <a href="#notification-settings" class="list-group-item list-group-item-action" data-tab="notification-settings">
                    <i class="fas fa-bell me-2"></i> การแจ้งเตือน
                </a>
                <a href="#system-settings" class="list-group-item list-group-item-action" data-tab="system-settings">
                    <i class="fas fa-cogs me-2"></i> ระบบทั่วไป
                </a>
            </div>
        </div>
        
        <!-- System Status Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="m-0"><i class="fas fa-heartbeat"></i> สถานะระบบ</h6>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>ฐานข้อมูล</span>
                    <span class="badge bg-success">ปกติ</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>LINE OA</span>
                    <span class="badge bg-<?= !empty($settingsArray['line_channel_access_token']) ? 'success' : 'warning' ?>">
                        <?= !empty($settingsArray['line_channel_access_token']) ? 'เชื่อมต่อ' : 'ไม่ได้ตั้งค่า' ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>PromptPay</span>
                    <span class="badge bg-<?= !empty($settingsArray['promptpay_id']) ? 'success' : 'warning' ?>">
                        <?= !empty($settingsArray['promptpay_id']) ? 'พร้อมใช้' : 'ไม่ได้ตั้งค่า' ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span>การบำรุงรักษา</span>
                    <span class="badge bg-<?= ($settingsArray['enable_maintenance_mode'] ?? '0') == '1' ? 'danger' : 'success' ?>">
                        <?= ($settingsArray['enable_maintenance_mode'] ?? '0') == '1' ? 'เปิด' : 'ปิด' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-9">
        <!-- Shop Information -->
        <div class="card setting-panel" id="shop-info">
            <div class="card-header">
                <h5 class="m-0"><i class="fas fa-store"></i> ข้อมูลร้าน</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_shop_info">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="shop_name" class="form-label">ชื่อร้าน</label>
                            <input type="text" class="form-control" id="shop_name" name="shop_name" 
                                   value="<?= htmlspecialchars($settingsArray['shop_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="shop_phone" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="text" class="form-control" id="shop_phone" name="shop_phone" 
                                   value="<?= htmlspecialchars($settingsArray['shop_phone'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="shop_address" class="form-label">ที่อยู่ร้าน</label>
                        <textarea class="form-control" id="shop_address" name="shop_address" rows="3"><?= htmlspecialchars($settingsArray['shop_address'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="shop_email" class="form-label">อีเมลร้าน</label>
                            <input type="email" class="form-control" id="shop_email" name="shop_email" 
                                   value="<?= htmlspecialchars($settingsArray['shop_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="shop_website" class="form-label">เว็บไซต์</label>
                            <input type="url" class="form-control" id="shop_website" name="shop_website" 
                                   value="<?= htmlspecialchars($settingsArray['shop_website'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="shop_description" class="form-label">คำอธิบายร้าน</label>
                        <textarea class="form-control" id="shop_description" name="shop_description" rows="3"><?= htmlspecialchars($settingsArray['shop_description'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกข้อมูลร้าน
                    </button>
                </form>
            </div>
        </div>

        <!-- Queue Settings -->
        <div class="card setting-panel d-none" id="queue-settings">
            <div class="card-header">
                <h5 class="m-0"><i class="fas fa-users"></i> การตั้งค่าคิว</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_queue_settings">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="queue_reset_daily" name="queue_reset_daily" 
                                       <?= ($settingsArray['queue_reset_daily'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="queue_reset_daily">
                                    รีเซ็ตหมายเลขคิวทุกวัน
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="auto_advance_queue" name="auto_advance_queue" 
                                       <?= ($settingsArray['auto_advance_queue'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="auto_advance_queue">
                                    เรียกคิวอัตโนมัติ
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="max_queue_per_day" class="form-label">จำนวนคิวสูงสุดต่อวัน</label>
                            <input type="number" class="form-control" id="max_queue_per_day" name="max_queue_per_day" 
                                   value="<?= htmlspecialchars($settingsArray['max_queue_per_day'] ?? '999') ?>" min="1" max="9999">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="estimated_time_per_item" class="form-label">เวลาโดยประมาณต่อรายการ (นาที)</label>
                            <input type="number" class="form-control" id="estimated_time_per_item" name="estimated_time_per_item" 
                                   value="<?= htmlspecialchars($settingsArray['estimated_time_per_item'] ?? '5') ?>" min="1" max="60">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="notification_before_queue" class="form-label">แจ้งเตือนก่อนถึงคิว (จำนวนคิว)</label>
                            <input type="number" class="form-control" id="notification_before_queue" name="notification_before_queue" 
                                   value="<?= htmlspecialchars($settingsArray['notification_before_queue'] ?? '3') ?>" min="1" max="10">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="queue_display_limit" class="form-label">จำนวนคิวที่แสดงบนจอ</label>
                        <select class="form-select" id="queue_display_limit" name="queue_display_limit">
                            <option value="10" <?= ($settingsArray['queue_display_limit'] ?? '20') == '10' ? 'selected' : '' ?>>10 คิว</option>
                            <option value="20" <?= ($settingsArray['queue_display_limit'] ?? '20') == '20' ? 'selected' : '' ?>>20 คิว</option>
                            <option value="50" <?= ($settingsArray['queue_display_limit'] ?? '20') == '50' ? 'selected' : '' ?>>50 คิว</option>
                            <option value="100" <?= ($settingsArray['queue_display_limit'] ?? '20') == '100' ? 'selected' : '' ?>>100 คิว</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการตั้งค่าคิว
                    </button>
                </form>
            </div>
        </div>

        <!-- Payment Settings -->
        <div class="card setting-panel d-none" id="payment-settings">
            <div class="card-header">
                <h5 class="m-0"><i class="fas fa-credit-card"></i> การตั้งค่าการชำระเงิน</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_payment_settings">
                    
                    <h6 class="mb-3"><i class="fas fa-qrcode"></i> PromptPay QR Code</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="promptpay_id" class="form-label">หมายเลข PromptPay</label>
                            <input type="text" class="form-control" id="promptpay_id" name="promptpay_id" 
                                   value="<?= htmlspecialchars($settingsArray['promptpay_id'] ?? '') ?>"
                                   placeholder="เบอร์โทรศัพท์หรือเลขบัตรประชาชน">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="promptpay_name" class="form-label">ชื่อบัญชี PromptPay</label>
                            <input type="text" class="form-control" id="promptpay_name" name="promptpay_name" 
                                   value="<?= htmlspecialchars($settingsArray['promptpay_name'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3"><i class="fas fa-money-bill"></i> วิธีการชำระเงิน</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_cash_payment" name="enable_cash_payment" 
                                       <?= ($settingsArray['enable_cash_payment'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enable_cash_payment">
                                    เงินสด
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_qr_payment" name="enable_qr_payment" 
                                       <?= ($settingsArray['enable_qr_payment'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enable_qr_payment">
                                    QR Payment
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_card_payment" name="enable_card_payment" 
                                       <?= ($settingsArray['enable_card_payment'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enable_card_payment">
                                    บัตรเครดิต/เดบิต
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3"><i class="fas fa-percentage"></i> ภาษีและค่าบริการ</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tax_rate" class="form-label">อัตราภาษี (%)</label>
                            <input type="number" class="form-control" id="tax_rate" name="tax_rate" 
                                   value="<?= htmlspecialchars($settingsArray['tax_rate'] ?? '7') ?>" 
                                   min="0" max="20" step="0.01">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="service_charge" class="form-label">ค่าบริการ (%)</label>
                            <input type="number" class="form-control" id="service_charge" name="service_charge" 
                                   value="<?= htmlspecialchars($settingsArray['service_charge'] ?? '0') ?>" 
                                   min="0" max="20" step="0.01">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการตั้งค่าการชำระเงิน
                    </button>
                </form>
            </div>
        </div>

        <!-- Receipt Settings -->
        <div class="card setting-panel d-none" id="receipt-settings">
            <div class="card-header">
                <h5 class="m-0"><i class="fas fa-receipt"></i> การตั้งค่าใบเสร็จ</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_receipt_settings">
                    
                    <div class="mb-3">
                        <label for="receipt_header_text" class="form-label">ข้อความหัวใบเสร็จ</label>
                        <textarea class="form-control" id="receipt_header_text" name="receipt_header_text" rows="2"><?= htmlspecialchars($settingsArray['receipt_header_text'] ?? 'ใบเสร็จรับเงิน') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="receipt_footer_text" class="form-label">ข้อความท้ายใบเสร็จ</label>
                        <textarea class="form-control" id="receipt_footer_text" name="receipt_footer_text" rows="3"><?= htmlspecialchars($settingsArray['receipt_footer_text'] ?? 'ขอบคุณที่ใช้บริการ') ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="receipt_logo_url" class="form-label">URL โลโก้ใบเสร็จ</label>
                            <input type="url" class="form-control" id="receipt_logo_url" name="receipt_logo_url" 
                                   value="<?= htmlspecialchars($settingsArray['receipt_logo_url'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="receipt_paper_size" class="form-label">ขนาดกระดาษ</label>
                            <select class="form-select" id="receipt_paper_size" name="receipt_paper_size">
                                <option value="80mm" <?= ($settingsArray['receipt_paper_size'] ?? '80mm') == '80mm' ? 'selected' : '' ?>>80mm (ใบเสร็จปกติ)</option>
                                <option value="58mm" <?= ($settingsArray['receipt_paper_size'] ?? '80mm') == '58mm' ? 'selected' : '' ?>>58mm (ใบเสร็จเล็ก)</option>
                                <option value="A4" <?= ($settingsArray['receipt_paper_size'] ?? '80mm') == 'A4' ? 'selected' : '' ?>>A4 (กระดาษ A4)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="auto_print_receipt" name="auto_print_receipt" 
                                       <?= ($settingsArray['auto_print_receipt'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="auto_print_receipt">
                                    พิมพ์ใบเสร็จอัตโนมัติ
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="send_receipt_line" name="send_receipt_line" 
                                       <?= ($settingsArray['send_receipt_line'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="send_receipt_line">
                                    ส่งใบเสร็จผ่าน LINE
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการตั้งค่าใบเสร็จ
                    </button>
                </form>
            </div>
        </div>

        <!-- Notification Settings -->
        <div class="card setting-panel d-none" id="notification-settings">
            <div class="card-header">
                <h5 class="m-0"><i class="fas fa-bell"></i> การตั้งค่าการแจ้งเตือน</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_notification_settings">
                    
                    <h6 class="mb-3"><i class="fas fa-mobile-alt"></i> ช่องทางการแจ้งเตือน</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_line_notifications" name="enable_line_notifications" 
                                       <?= ($settingsArray['enable_line_notifications'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enable_line_notifications">
                                    การแจ้งเตือนผ่าน LINE
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_email_notifications" name="enable_email_notifications" 
                                       <?= ($settingsArray['enable_email_notifications'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enable_email_notifications">
                                    การแจ้งเตือนผ่านอีเมล
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_sms_notifications" name="enable_sms_notifications" 
                                       <?= ($settingsArray['enable_sms_notifications'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enable_sms_notifications">
                                    การแจ้งเตือนผ่าน SMS
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3"><i class="fas fa-volume-up"></i> การตั้งค่าเสียง AI Voice</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notification_sound" name="notification_sound" 
                                       <?= ($settingsArray['notification_sound'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="notification_sound">
                                    เปิดเสียงการแจ้งเตือน
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="voice_language" class="form-label">ภาษาเสียง</label>
                            <select class="form-select" id="voice_language" name="voice_language">
                                <option value="th-TH" <?= ($settingsArray['voice_language'] ?? 'th-TH') == 'th-TH' ? 'selected' : '' ?>>ไทย</option>
                                <option value="en-US" <?= ($settingsArray['voice_language'] ?? 'th-TH') == 'en-US' ? 'selected' : '' ?>>English (US)</option>
                                <option value="en-GB" <?= ($settingsArray['voice_language'] ?? 'th-TH') == 'en-GB' ? 'selected' : '' ?>>English (UK)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="voice_speed" class="form-label">ความเร็วเสียง</label>
                            <select class="form-select" id="voice_speed" name="voice_speed">
                                <option value="0.5" <?= ($settingsArray['voice_speed'] ?? '0.8') == '0.5' ? 'selected' : '' ?>>ช้า</option>
                                <option value="0.8" <?= ($settingsArray['voice_speed'] ?? '0.8') == '0.8' ? 'selected' : '' ?>>ปกติ</option>
                                <option value="1.0" <?= ($settingsArray['voice_speed'] ?? '0.8') == '1.0' ? 'selected' : '' ?>>เร็ว</option>
                                <option value="1.2" <?= ($settingsArray['voice_speed'] ?? '0.8') == '1.2' ? 'selected' : '' ?>>เร็วมาก</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="voice_volume" class="form-label">ระดับเสียง (%)</label>
                            <input type="range" class="form-range" id="voice_volume" name="voice_volume" 
                                   min="0" max="100" value="<?= htmlspecialchars($settingsArray['voice_volume'] ?? '80') ?>"
                                   oninput="updateVolumeDisplay(this.value)">
                            <div class="text-center"><span id="volume-display"><?= htmlspecialchars($settingsArray['voice_volume'] ?? '80') ?>%</span></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-primary" onclick="testVoice()">
                            <i class="fas fa-play"></i> ทดสอบเสียง
                        </button>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการตั้งค่าการแจ้งเตือน
                    </button>
                </form>
            </div>
        </div>

        <!-- System Settings -->
        <div class="card setting-panel d-none" id="system-settings">
            <div class="card-header">
                <h5 class="m-0"><i class="fas fa-cogs"></i> การตั้งค่าระบบทั่วไป</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="save_system_settings">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="site_timezone" class="form-label">เขตเวลา</label>
                            <select class="form-select" id="site_timezone" name="site_timezone">
                                <option value="Asia/Bangkok" <?= ($settingsArray['site_timezone'] ?? 'Asia/Bangkok') == 'Asia/Bangkok' ? 'selected' : '' ?>>Asia/Bangkok (UTC+7)</option>
                                <option value="Asia/Singapore" <?= ($settingsArray['site_timezone'] ?? 'Asia/Bangkok') == 'Asia/Singapore' ? 'selected' : '' ?>>Asia/Singapore (UTC+8)</option>
                                <option value="UTC" <?= ($settingsArray['site_timezone'] ?? 'Asia/Bangkok') == 'UTC' ? 'selected' : '' ?>>UTC (UTC+0)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="default_language" class="form-label">ภาษาเริ่มต้น</label>
                            <select class="form-select" id="default_language" name="default_language">
                                <option value="th" <?= ($settingsArray['default_language'] ?? 'th') == 'th' ? 'selected' : '' ?>>ไทย</option>
                                <option value="en" <?= ($settingsArray['default_language'] ?? 'th') == 'en' ? 'selected' : '' ?>>English</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_format" class="form-label">รูปแบบวันที่</label>
                            <select class="form-select" id="date_format" name="date_format">
                                <option value="d/m/Y" <?= ($settingsArray['date_format'] ?? 'd/m/Y') == 'd/m/Y' ? 'selected' : '' ?>>31/12/2024 (dd/mm/yyyy)</option>
                                <option value="Y-m-d" <?= ($settingsArray['date_format'] ?? 'd/m/Y') == 'Y-m-d' ? 'selected' : '' ?>>2024-12-31 (yyyy-mm-dd)</option>
                                <option value="m/d/Y" <?= ($settingsArray['date_format'] ?? 'd/m/Y') == 'm/d/Y' ? 'selected' : '' ?>>12/31/2024 (mm/dd/yyyy)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="currency_symbol" class="form-label">สัญลักษณ์เงินตรา</label>
                            <select class="form-select" id="currency_symbol" name="currency_symbol">
                                <option value="THB" <?= ($settingsArray['currency_symbol'] ?? 'THB') == 'THB' ? 'selected' : '' ?>>THB (บาท)</option>
                                <option value="USD" <?= ($settingsArray['currency_symbol'] ?? 'THB') == 'USD' ? 'selected' : '' ?>>USD ($)</option>
                                <option value="EUR" <?= ($settingsArray['currency_symbol'] ?? 'THB') == 'EUR' ? 'selected' : '' ?>>EUR (€)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="auto_refresh_interval" class="form-label">ช่วงเวลาการรีเฟรชอัตโนมัติ (วินาที)</label>
                            <select class="form-select" id="auto_refresh_interval" name="auto_refresh_interval">
                                <option value="15" <?= ($settingsArray['auto_refresh_interval'] ?? '30') == '15' ? 'selected' : '' ?>>15 วินาที</option>
                                <option value="30" <?= ($settingsArray['auto_refresh_interval'] ?? '30') == '30' ? 'selected' : '' ?>>30 วินาที</option>
                                <option value="60" <?= ($settingsArray['auto_refresh_interval'] ?? '30') == '60' ? 'selected' : '' ?>>1 นาที</option>
                                <option value="0" <?= ($settingsArray['auto_refresh_interval'] ?? '30') == '0' ? 'selected' : '' ?>>ปิดการรีเฟรช</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="enable_debug_mode" name="enable_debug_mode" 
                                       <?= ($settingsArray['enable_debug_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enable_debug_mode">
                                    เปิด Debug Mode
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3"><i class="fas fa-tools"></i> โหมดการบำรุงรักษา</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_maintenance_mode" name="enable_maintenance_mode" 
                                       <?= ($settingsArray['enable_maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enable_maintenance_mode">
                                    เปิดโหมดการบำรุงรักษา
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="maintenance_message" class="form-label">ข้อความการบำรุงรักษา</label>
                            <input type="text" class="form-control" id="maintenance_message" name="maintenance_message" 
                                   value="<?= htmlspecialchars($settingsArray['maintenance_message'] ?? 'ระบบอยู่ระหว่างการบำรุงรักษา') ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการตั้งค่าระบบ
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Tab navigation
    $('.list-group-item-action').click(function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        $('.list-group-item-action').removeClass('active');
        $('.setting-panel').addClass('d-none');
        
        // Add active class to clicked tab
        $(this).addClass('active');
        
        // Show corresponding panel
        const targetTab = $(this).data('tab');
        $('#' + targetTab).removeClass('d-none');
    });
    
    // Auto-save form data
    $('form input, form select, form textarea').change(function() {
        localStorage.setItem('settings_' + this.name, this.value);
    });
    
    // Load saved form data
    $('form input, form select, form textarea').each(function() {
        const savedValue = localStorage.getItem('settings_' + this.name);
        if (savedValue && this.type !== 'checkbox') {
            this.value = savedValue;
        }
    });
});

function updateVolumeDisplay(value) {
    document.getElementById('volume-display').textContent = value + '%';
}

function testVoice() {
    if ('speechSynthesis' in window) {
        const text = 'หมายเลขคิว 1 ขอเชิญมารับออเดอร์ครับ';
        const utterance = new SpeechSynthesisUtterance(text);
        
        utterance.lang = document.getElementById('voice_language').value;
        utterance.rate = parseFloat(document.getElementById('voice_speed').value);
        utterance.volume = parseFloat(document.getElementById('voice_volume').value) / 100;
        
        speechSynthesis.speak(utterance);
    } else {
        alert('เบราว์เซอร์ของคุณไม่สนับสนุนการอ่านข้อความเป็นเสียง');
    }
}

function exportSettings() {
    // Export settings to JSON file
    fetch('api/export_settings.php')
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'system_settings_' + new Date().toISOString().split('T')[0] + '.json';
            a.click();
            window.URL.revokeObjectURL(url);
        })
        .catch(error => {
            showAlert('เกิดข้อผิดพลาดในการ Export การตั้งค่า', 'danger');
        });
}

function resetToDefault() {
    if (confirm('คุณต้องการรีเซ็ตการตั้งค่าทั้งหมดเป็นค่าเริ่มต้นหรือไม่?')) {
        fetch('api/reset_settings.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('รีเซ็ตการตั้งค่าสำเร็จ', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('เกิดข้อผิดพลาด: ' + data.message, 'danger');
            }
        });
    }
}

function backupSettings() {
    fetch('api/backup_settings.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('สำรองข้อมูลการตั้งค่าสำเร็จ', 'success');
        } else {
            showAlert('เกิดข้อผิดพลาด: ' + data.message, 'danger');
        }
    });
}

// Form validation
$('form').submit(function() {
    const requiredFields = $(this).find('[required]');
    let isValid = true;
    
    requiredFields.each(function() {
        if (!this.value.trim()) {
            $(this).addClass('is-invalid');
            isValid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    if (!isValid) {
        showAlert('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน', 'warning');
        return false;
    }
    
    return true;
});

// Real-time validation
$('input[required], select[required], textarea[required]').blur(function() {
    if (!this.value.trim()) {
        $(this).addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid');
    }
});

// Phone number validation
$('#shop_phone, #promptpay_id').on('input', function() {
    const value = this.value.replace(/[^0-9]/g, '');
    if (value.length > 0 && value.length < 10) {
        $(this).addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid');
    }
});

// Email validation
$('#shop_email').on('input', function() {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (this.value && !emailRegex.test(this.value)) {
        $(this).addClass('is-invalid');
    } else {
        $(this).removeClass('is-invalid');
    }
});

// Auto-save notification
let saveTimeout;
$('input, select, textarea').on('change', function() {
    clearTimeout(saveTimeout);
    
    // Show saving indicator
    showSavingIndicator();
    
    saveTimeout = setTimeout(() => {
        hideSavingIndicator();
        showAlert('บันทึกข้อมูลอัตโนมัติแล้ว', 'info');
    }, 1000);
});

function showSavingIndicator() {
    if (!$('#saving-indicator').length) {
        $('body').append('<div id="saving-indicator" class="position-fixed bottom-0 end-0 p-3"><div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...</div></div>');
    }
}

function hideSavingIndicator() {
    $('#saving-indicator').fadeOut(() => {
        $('#saving-indicator').remove();
    });
}
</script>

<?php include 'includes/footer.php'; ?>