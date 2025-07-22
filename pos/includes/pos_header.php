<div class="pos-header">
    <div class="header-content">
        <div class="header-left">
            <h4 class="header-title">
                <i class="fas fa-store"></i> 
                ระบบ POS - ร้านอาหารดีๆ
            </h4>
            <small class="header-subtitle">Smart Order Management System</small>
        </div>
        
        <div class="header-center">
            <div class="datetime-display">
                <div class="current-time">
                    <i class="fas fa-clock"></i>
                    <span id="currentTime"><?= date('d/m/Y H:i:s') ?></span>
                </div>
                <div class="store-status">
                    <span class="status-indicator online"></span>
                    <span>เปิดให้บริการ</span>
                </div>
            </div>
        </div>
        
        <div class="header-right">
            <div class="user-info">
                <div class="user-details">
                    <strong><?= $_SESSION['user_name'] ?? 'ผู้ใช้งาน' ?></strong>
                    <small><?= getRoleText($_SESSION['user_role'] ?? 'staff') ?></small>
                </div>
                <div class="user-avatar">
                    <i class="fas fa-user-circle fa-2x"></i>
                </div>
            </div>
            
            <div class="header-actions">
                <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-cog"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="#" onclick="showSettings()">
                        <i class="fas fa-cog"></i> ตั้งค่า
                    </a></li>
                    <li><a class="dropdown-item" href="#" onclick="showProfile()">
                        <i class="fas fa-user"></i> โปรไฟล์
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="toggleFullscreen()">
                        <i class="fas fa-expand"></i> เต็มจอ
                    </a></li>
                    <li><a class="dropdown-item" href="../admin/" target="_blank">
                        <i class="fas fa-tools"></i> จัดการระบบ
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="confirmLogout()">
                        <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats Bar -->
    <div class="quick-stats-bar">
        <div class="stat-item">
            <i class="fas fa-shopping-cart"></i>
            <span>ออเดอร์วันนี้</span>
            <strong id="headerTodayOrders"><?= rand(15, 45) ?></strong>
        </div>
        
        <div class="stat-item">
            <i class="fas fa-users"></i>
            <span>คิวที่รอ</span>
            <strong id="headerWaitingQueue" class="text-warning"><?= rand(3, 12) ?></strong>
        </div>
        
        <div class="stat-item">
            <i class="fas fa-check"></i>
            <span>คิวพร้อม</span>
            <strong id="headerReadyQueue" class="text-success"><?= rand(1, 5) ?></strong>
        </div>
        
        <div class="stat-item">
            <i class="fas fa-money-bill-wave"></i>
            <span>ยอดขาย</span>
            <strong id="headerTodaySales" class="text-primary">฿<?= number_format(rand(5000, 15000)) ?></strong>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user"></i> โปรไฟล์ผู้ใช้</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="user-avatar-large">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="profile-info">
                            <div class="mb-3">
                                <label class="form-label">ชื่อผู้ใช้</label>
                                <input type="text" class="form-control" value="<?= $_SESSION['username'] ?? '' ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ชื่อ-นามสกุล</label>
                                <input type="text" class="form-control" value="<?= $_SESSION['user_name'] ?? '' ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">บทบาท</label>
                                <input type="text" class="form-control" value="<?= getRoleText($_SESSION['user_role'] ?? '') ?>" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">เข้าสู่ระบบเมื่อ</label>
                                <input type="text" class="form-control" value="<?= isset($_SESSION['login_time']) ? date('d/m/Y H:i', $_SESSION['login_time']) : '' ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="showChangePassword()">
                    <i class="fas fa-key"></i> เปลี่ยนรหัสผ่าน
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cog"></i> ตั้งค่าระบบ POS</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="settings-section">
                    <h6><i class="fas fa-palette"></i> การแสดงผล</h6>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="darkMode">
                            <label class="form-check-label">โหมดมืด</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                            <label class="form-check-label">รีเฟรชอัตโนมัติ</label>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="settings-section">
                    <h6><i class="fas fa-volume-up"></i> เสียง</h6>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="soundEnabled" checked>
                            <label class="form-check-label">เปิดเสียง</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ระดับเสียง</label>
                        <input type="range" class="form-range" id="volumeLevel" min="0" max="100" value="80">
                    </div>
                </div>
                
                <hr>
                
                <div class="settings-section">
                    <h6><i class="fas fa-print"></i> การพิมพ์</h6>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="autoPrint">
                            <label class="form-check-label">พิมพ์ใบเสร็จอัตโนมัติ</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ขนาดกระดาษ</label>
                        <select class="form-select" id="paperSize">
                            <option value="80mm">80mm (ใบเสร็จ)</option>
                            <option value="A4">A4</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="saveSettings()">
                    <i class="fas fa-save"></i> บันทึก
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
            </div>
        </div>
    </div>
</div>

<script>
// ฟังก์ชันสำหรับ Header
function showProfile() {
    const modal = new bootstrap.Modal(document.getElementById('profileModal'));
    modal.show();
}

function showSettings() {
    loadSettings();
    const modal = new bootstrap.Modal(document.getElementById('settingsModal'));
    modal.show();
}

function showChangePassword() {
    // ในการใช้งานจริง จะเปิด modal เปลี่ยนรหัสผ่าน
    alert('ฟีเจอร์เปลี่ยนรหัสผ่านจะพร้อมใช้งานเร็วๆ นี้');
}

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

function confirmLogout() {
    if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
        window.location.href = '../includes/logout.php';
    }
}

function loadSettings() {
    // โหลดการตั้งค่าจาก localStorage
    const settings = JSON.parse(localStorage.getItem('posSettings') || '{}');
    
    document.getElementById('darkMode').checked = settings.darkMode || false;
    document.getElementById('autoRefresh').checked = settings.autoRefresh !== false;
    document.getElementById('soundEnabled').checked = settings.soundEnabled !== false;
    document.getElementById('volumeLevel').value = settings.volumeLevel || 80;
    document.getElementById('autoPrint').checked = settings.autoPrint || false;
    document.getElementById('paperSize').value = settings.paperSize || '80mm';
}

function saveSettings() {
    const settings = {
        darkMode: document.getElementById('darkMode').checked,
        autoRefresh: document.getElementById('autoRefresh').checked,
        soundEnabled: document.getElementById('soundEnabled').checked,
        volumeLevel: document.getElementById('volumeLevel').value,
        autoPrint: document.getElementById('autoPrint').checked,
        paperSize: document.getElementById('paperSize').value
    };
    
    localStorage.setItem('posSettings', JSON.stringify(settings));
    applySettings(settings);
    
    const modal = bootstrap.Modal.getInstance(document.getElementById('settingsModal'));
    modal.hide();
    
    showToast('บันทึกสำเร็จ', 'ตั้งค่าถูกบันทึกแล้ว', 'success');
}

function applySettings(settings) {
    // ประยุกต์ใช้การตั้งค่า
    if (settings.darkMode) {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }
    
    // ตั้งค่าอื่นๆ ตามต้องการ
}

// อัปเดตสถิติใน header
function updateHeaderStats() {
    // ในการใช้งานจริง จะดึงข้อมูลจาก API
    fetch('api/dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('headerTodayOrders').textContent = data.today_orders || '0';
                document.getElementById('headerWaitingQueue').textContent = data.waiting_queue || '0';
                document.getElementById('headerReadyQueue').textContent = data.ready_queue || '0';
                document.getElementById('headerTodaySales').textContent = '฿' + (data.today_sales || '0');
            }
        })
        .catch(error => console.log('Error updating stats:', error));
}

// อัปเดตสถิติทุก 30 วินาที
setInterval(updateHeaderStats, 30000);

// โหลดและประยุกต์ใช้การตั้งค่าเมื่อเริ่มต้น
document.addEventListener('DOMContentLoaded', function() {
    const settings = JSON.parse(localStorage.getItem('posSettings') || '{}');
    applySettings(settings);
});
</script>

<?php
function getRoleText($role) {
    $roleTexts = [
        'admin' => 'ผู้ดูแลระบบ',
        'manager' => 'ผู้จัดการ', 
        'staff' => 'พนักงาน',
        'kitchen' => 'พนักงานครัว',
        'cashier' => 'พนักงานเก็บเงิน'
    ];
    return $roleTexts[$role] ?? 'ผู้ใช้งาน';
}
?>