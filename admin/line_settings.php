<?php
require_once '../config/config.php';
require_once '../config/database.php';

requireLogin();

$pageTitle = 'ตั้งค่า LINE OA';
$activePage = 'line';

if ($_POST) {
    $db->query("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'line_channel_access_token'", [$_POST['line_channel_access_token']]);
    $db->query("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'line_channel_secret'", [$_POST['line_channel_secret']]);
    $message = "บันทึกการตั้งค่า LINE OA สำเร็จ";
}

$lineSettings = $db->fetchAll("SELECT * FROM system_settings WHERE setting_key LIKE 'line_%'");
$settings = [];
foreach ($lineSettings as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

include 'includes/header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><i class="fab fa-line"></i> ตั้งค่า LINE OA</h1>
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#lineSetupModal">
        <i class="fas fa-question-circle"></i> วิธีการตั้งค่า
    </button>
</div>

<?php if (isset($message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">การตั้งค่า LINE Channel</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="line_channel_access_token" class="form-label">Channel Access Token</label>
                        <input type="text" class="form-control" id="line_channel_access_token" name="line_channel_access_token" 
                               value="<?= htmlspecialchars($settings['line_channel_access_token'] ?? '') ?>">
                        <div class="form-text">ใช้สำหรับส่งข้อความผ่าน LINE Bot</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="line_channel_secret" class="form-label">Channel Secret</label>
                        <input type="text" class="form-control" id="line_channel_secret" name="line_channel_secret" 
                               value="<?= htmlspecialchars($settings['line_channel_secret'] ?? '') ?>">
                        <div class="form-text">ใช้สำหรับตรวจสอบความถูกต้องของ Webhook</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Webhook URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" readonly value="<?= SITE_URL ?>api/line_webhook.php">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('<?= SITE_URL ?>api/line_webhook.php')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="form-text">ใช้ URL นี้ในการตั้งค่า Webhook ของ LINE Channel</div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึกการตั้งค่า
                    </button>
                    <button type="button" class="btn btn-outline-success" onclick="testLineConnection()">
                        <i class="fas fa-paper-plane"></i> ทดสอบการเชื่อมต่อ
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">สถานะการเชื่อมต่อ</h6>
            </div>
            <div class="card-body">
                <div id="connectionStatus">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                        <p class="mt-2 text-muted">กำลังตรวจสอบ...</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold">ฟีเจอร์ LINE Bot</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-check-circle text-success"></i>
                        ส่งหมายเลขคิวเมื่อสั่งซื้อ
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check-circle text-success"></i>
                        แจ้งเตือนเมื่อใกล้ถึงคิว
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check-circle text-success"></i>
                        แจ้งเมื่ออาหารพร้อม
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check-circle text-success"></i>
                        ส่งใบเสร็จอิเล็กทรอนิกส์
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Setup Modal -->
<div class="modal fade" id="lineSetupModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">วิธีการตั้งค่า LINE OA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="accordion" id="setupAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#step1">
                                ขั้นตอนที่ 1: สร้าง LINE Official Account
                            </button>
                        </h2>
                        <div id="step1" class="accordion-collapse collapse show" data-bs-parent="#setupAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>เข้าไปที่ <a href="https://manager.line.biz/" target="_blank">LINE Official Account Manager</a></li>
                                    <li>สร้างบัญชี LINE Official Account ใหม่</li>
                                    <li>เลือกแผนการใช้งาน (แนะนำ Developer Trial)</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step2">
                                ขั้นตอนที่ 2: สร้าง Messaging API Channel
                            </button>
                        </h2>
                        <div id="step2" class="accordion-collapse collapse" data-bs-parent="#setupAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>เข้าไปที่ <a href="https://developers.line.biz/console/" target="_blank">LINE Developers Console</a></li>
                                    <li>เลือก Provider และ Channel ที่สร้างไว้</li>
                                    <li>ไปที่แท็บ "Messaging API"</li>
                                    <li>คัดลอก "Channel Access Token" และ "Channel Secret"</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#step3">
                                ขั้นตอนที่ 3: ตั้งค่า Webhook
                            </button>
                        </h2>
                        <div id="step3" class="accordion-collapse collapse" data-bs-parent="#setupAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>ในหน้า Messaging API ให้ตั้งค่า Webhook URL เป็น: <br>
                                        <code><?= SITE_URL ?>api/line_webhook.php</code>
                                    </li>
                                    <li>เปิดใช้งาน "Use webhook"</li>
                                    <li>ปิดใช้งาน "Auto-reply messages"</li>
                                    <li>ปิดใช้งาน "Greeting messages"</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('คัดลอกแล้ว!');
    });
}

function testLineConnection() {
    // Test LINE connection
    fetch('api/test_line.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('การเชื่อมต่อ LINE สำเร็จ', 'success');
        } else {
            showAlert('การเชื่อมต่อ LINE ล้มเหลว: ' + data.message, 'danger');
        }
    });
}

// Check connection status on load
$(document).ready(function() {
    checkConnectionStatus();
});

function checkConnectionStatus() {
    const token = $('#line_channel_access_token').val();
    const secret = $('#line_channel_secret').val();
    
    if (!token || !secret) {
        $('#connectionStatus').html(`
            <div class="text-center">
                <i class="fas fa-times-circle fa-2x text-danger"></i>
                <p class="mt-2 text-danger">ยังไม่ได้ตั้งค่า</p>
            </div>
        `);
        return;
    }
    
    $('#connectionStatus').html(`
        <div class="text-center">
            <i class="fas fa-check-circle fa-2x text-success"></i>
            <p class="mt-2 text-success">พร้อมใช้งาน</p>
        </div>
    `);
}
</script>
