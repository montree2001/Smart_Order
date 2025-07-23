<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
?>
<div class="pos-navigation">
    <nav class="pos-nav">
        <div class="nav-container">
            <ul class="nav nav-pills nav-justified" id="mainNavigation">
                
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?= ($activePage == 'dashboard' || !isset($activePage)) ? 'active' : '' ?>" 
                       href="index.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="nav-text">แดชบอร์ด</span>
                        <small class="nav-description">ภาพรวมระบบ</small>
                    </a>
                </li>

                <!-- New Order -->
                <li class="nav-item">
                    <a class="nav-link <?= $activePage == 'new-order' ? 'active' : '' ?>" 
                       href="new_order.php">
                        <i class="fas fa-plus-circle"></i>
                        <span class="nav-text">สั่งซื้อใหม่</span>
                        <small class="nav-description">รับออเดอร์</small>
                    </a>
                </li>

                <!-- Order List -->
                <li class="nav-item">
                    <a class="nav-link <?= $activePage == 'orders' ? 'active' : '' ?>" 
                       href="order_list.php">
                        <i class="fas fa-list"></i>
                        <span class="nav-text">จัดการออเดอร์</span>
                        <small class="nav-description">รายการออเดอร์</small>
                        <?php if (isset($pendingOrdersCount) && $pendingOrdersCount > 0): ?>
                        <span class="badge bg-warning ms-1"><?= $pendingOrdersCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- Queue Management -->
                <li class="nav-item">
                    <a class="nav-link <?= $activePage == 'queue' ? 'active' : '' ?>" 
                       href="queue_display.php">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">จัดการคิว</span>
                        <small class="nav-description">เรียกคิวลูกค้า</small>
                        <?php if (isset($waitingQueueCount) && $waitingQueueCount > 0): ?>
                        <span class="badge bg-info ms-1"><?= $waitingQueueCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <!-- Payment -->
                <li class="nav-item">
                    <a class="nav-link <?= $activePage == 'payment' ? 'active' : '' ?>" 
                       href="payment.php" style="<?= $activePage != 'payment' ? 'display: none;' : '' ?>">
                        <i class="fas fa-credit-card"></i>
                        <span class="nav-text">ชำระเงิน</span>
                        <small class="nav-description">ระบบชำระเงิน</small>
                    </a>
                </li>

                <!-- Reports (Only for admin/manager) -->
                <?php if (hasPermission('view_reports')): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $activePage == 'reports' ? 'active' : '' ?>" 
                       href="reports.php">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">รายงาน</span>
                        <small class="nav-description">สถิติยอดขาย</small>
                    </a>
                </li>
                <?php endif; ?>

            </ul>
        </div>
    </nav>
    
    <!-- Mobile Navigation Toggle -->
    <button class="mobile-nav-toggle d-lg-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileNav">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Quick Action Buttons -->
    <div class="quick-actions">
        
        <!-- Emergency Stop -->
        <button class="btn btn-outline-danger btn-sm quick-action-btn" 
                onclick="emergencyStop()" 
                title="หยุดฉุกเฉิน">
            <i class="fas fa-stop"></i>
        </button>
        
        <!-- Kitchen Display -->
        <a href="../kitchen/" 
           target="_blank" 
           class="btn btn-outline-info btn-sm quick-action-btn"
           title="จอครัว">
            <i class="fas fa-kitchen-set"></i>
        </a>
        
        <!-- Customer Display -->
        <a href="../customer/" 
           target="_blank" 
           class="btn btn-outline-success btn-sm quick-action-btn"
           title="หน้าลูกค้า">
            <i class="fas fa-store"></i>
        </a>
        
        <!-- Print Last Receipt -->
        <button class="btn btn-outline-primary btn-sm quick-action-btn" 
                onclick="printLastReceipt()" 
                title="พิมพ์ใบเสร็จล่าสุด">
            <i class="fas fa-print"></i>
        </button>
        
        <!-- Calculator -->
        <button class="btn btn-outline-secondary btn-sm quick-action-btn" 
                onclick="showCalculator()" 
                title="เครื่องคิดเลข">
            <i class="fas fa-calculator"></i>
        </button>
        
    </div>
</div>

<!-- Mobile Navigation Collapse -->
<div class="collapse d-lg-none" id="mobileNav">
    <div class="mobile-nav-menu">
        <div class="mobile-nav-item">
            <a href="index.php" class="<?= ($activePage == 'dashboard' || !isset($activePage)) ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> แดชบอร์ด
            </a>
        </div>
        <div class="mobile-nav-item">
            <a href="new_order.php" class="<?= $activePage == 'new-order' ? 'active' : '' ?>">
                <i class="fas fa-plus-circle"></i> สั่งซื้อใหม่
            </a>
        </div>
        <div class="mobile-nav-item">
            <a href="order_list.php" class="<?= $activePage == 'orders' ? 'active' : '' ?>">
                <i class="fas fa-list"></i> จัดการออเดอร์
                <?php if (isset($pendingOrdersCount) && $pendingOrdersCount > 0): ?>
                <span class="badge bg-warning ms-1"><?= $pendingOrdersCount ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="mobile-nav-item">
            <a href="queue_display.php" class="<?= $activePage == 'queue' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> จัดการคิว
                <?php if (isset($waitingQueueCount) && $waitingQueueCount > 0): ?>
                <span class="badge bg-info ms-1"><?= $waitingQueueCount ?></span>
                <?php endif; ?>
            </a>
        </div>
        <?php if (hasPermission('view_reports')): ?>
        <div class="mobile-nav-item">
            <a href="reports.php" class="<?= $activePage == 'reports' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i> รายงาน
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Calculator Modal -->
<div class="modal fade" id="calculatorModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calculator"></i> เครื่องคิดเลข</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="calculator">
                    <input type="text" id="calcDisplay" class="form-control form-control-lg text-end mb-3" readonly>
                    <div class="calculator-buttons">
                        <div class="row g-1">
                            <div class="col-3"><button class="btn btn-outline-secondary w-100" onclick="clearCalc()">C</button></div>
                            <div class="col-3"><button class="btn btn-outline-secondary w-100" onclick="calcBackspace()">⌫</button></div>
                            <div class="col-3"><button class="btn btn-outline-secondary w-100" onclick="calcInput('/')">/</button></div>
                            <div class="col-3"><button class="btn btn-outline-secondary w-100" onclick="calcInput('*')">×</button></div>
                        </div>
                        <div class="row g-1">
                            <div class="col-3"><button class="btn btn-outline-primary w-100" onclick="calcInput('7')">7</button></div>
                            <div class="col-3"><button class="btn btn-outline-primary w-100" onclick="calcInput('8')">8</button></div>
                            <div class="col-3"><button class="btn btn-outline-primary w-100" onclick="calcInput('9')">9</button></div>
                            <div class="col-3"><button class="btn btn-outline-secondary w-100" onclick="calcInput('-')">-</button></div>
                        </div>
                        <div class="row g-1">
                            <div class="col-3"><button class="btn btn-outline-primary w-100" onclick="calcInput('4')">4</button></div>
                            <div class="col-3"><button class="btn btn-outline-primary w-100" onclick="calcInput('5')">5</button></div>
                            <div class="col-3"><button class="btn btn-outline-primary w-100" onclick="calcInput('6')">6</button></div>
                            <div class="col-3"><button class="btn btn-outline-secondary w-100" onclick="calcInput('+')">+</button></div>
                        </div>
                        <div class="row g-1">
                            <div class="col-3"><button class="btn btn-outline-primary w-100" onclick="calcInput('1')">1</button></div>
                            <div class="col-3"><button class="btn btn-outline-primary w-100" onclick="calcInput('2')">2</button></div>
                            <div class="col-3"><button class="btn btn-outline-primary w-100" onclick="calcInput('3')">3</button></div>
                            <div class="col-3 d-flex align-items-stretch">
                                <button class="btn btn-success w-100" onclick="calcCalculate()" style="writing-mode: vertical-lr;">=</button>
                            </div>
                        </div>
                        <div class="row g-1">
                            <div class="col-6"><button class="btn btn-outline-primary w-100" onclick="calcInput('0')">0</button></div>
                            <div class="col-3"><button class="btn btn-outline-secondary w-100" onclick="calcInput('.')">.</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Emergency Stop Modal -->
<div class="modal fade" id="emergencyModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> หยุดฉุกเฉิน</h5>
            </div>
            <div class="modal-body text-center">
                <i class="fas fa-stop-circle fa-4x text-danger mb-3"></i>
                <h4>ระบบถูกหยุดชั่วคราว</h4>
                <p class="text-muted">ระบบ POS ถูกหยุดเพื่อการบำรุงรักษาหรือแก้ไขปัญหา</p>
                <div class="mt-4">
                    <button class="btn btn-success btn-lg" onclick="resumeSystem()">
                        <i class="fas fa-play"></i> เริ่มระบบใหม่
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Navigation Functions
let calcValue = '';
let calcOperator = '';
let calcWaitingForNewOperand = false;

// Calculator Functions
function showCalculator() {
    const modal = new bootstrap.Modal(document.getElementById('calculatorModal'));
    modal.show();
    clearCalc();
}

function calcInput(value) {
    const display = document.getElementById('calcDisplay');
    
    if (calcWaitingForNewOperand) {
        display.value = value;
        calcWaitingForNewOperand = false;
    } else {
        display.value = display.value === '0' ? value : display.value + value;
    }
}

function calcCalculate() {
    const display = document.getElementById('calcDisplay');
    try {
        const result = Function('"use strict"; return (' + display.value.replace(/×/g, '*') + ')')();
        display.value = result;
        calcWaitingForNewOperand = true;
    } catch (error) {
        display.value = 'Error';
        calcWaitingForNewOperand = true;
    }
}

function clearCalc() {
    document.getElementById('calcDisplay').value = '0';
    calcWaitingForNewOperand = true;
}

function calcBackspace() {
    const display = document.getElementById('calcDisplay');
    display.value = display.value.slice(0, -1) || '0';
}

// Emergency Stop
function emergencyStop() {
    if (confirm('คุณต้องการหยุดระบบฉุกเฉินหรือไม่?')) {
        const modal = new bootstrap.Modal(document.getElementById('emergencyModal'));
        modal.show();
        
        // หยุดการทำงานของระบบ
        clearInterval(window.autoRefreshInterval);
        
        // แสดงข้อความเตือนบนหน้าจอ
        document.body.style.pointerEvents = 'none';
        document.querySelector('.pos-container').style.opacity = '0.5';
    }
}

function resumeSystem() {
    // เริ่มระบบใหม่
    document.body.style.pointerEvents = 'auto';
    document.querySelector('.pos-container').style.opacity = '1';
    
    // ปิด modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('emergencyModal'));
    modal.hide();
    
    // เริ่ม auto refresh ใหม่
    if (typeof startAutoRefresh === 'function') {
        startAutoRefresh();
    }
    
    showToast('ระบบพร้อมใช้งาน', 'ระบบกลับมาทำงานปกติแล้ว', 'success');
}

// Print Last Receipt
function printLastReceipt() {
    const lastReceipt = localStorage.getItem('lastReceipt');
    if (lastReceipt) {
        const printWindow = window.open('', '', 'height=600,width=400');
        printWindow.document.write('<html>\n' +
            '<head><title>ใบเสร็จ</title></head>\n' +
            '<body>' +
            lastReceipt +
            '<script>window.print(); window.close();<\/script>' +
            '</body>\n' +
            '</html>');
    } else {
        showToast('ไม่พบใบเสร็จ', 'ไม่มีใบเสร็จล่าสุด', 'warning');
    }
}

// Update navigation badges
function updateNavigationBadges() {
    // อัปเดต badge จำนวนออเดอร์ที่รอ
    fetch('api/navigation_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // อัปเดต badge ใน navigation
                const ordersBadge = document.querySelector('a[href="order_list.php"] .badge');
                if (ordersBadge && data.pending_orders > 0) {
                    ordersBadge.textContent = data.pending_orders;
                    ordersBadge.style.display = 'inline';
                } else if (ordersBadge) {
                    ordersBadge.style.display = 'none';
                }
                
                const queueBadge = document.querySelector('a[href="queue_display.php"] .badge');
                if (queueBadge && data.waiting_queue > 0) {
                    queueBadge.textContent = data.waiting_queue;
                    queueBadge.style.display = 'inline';
                } else if (queueBadge) {
                    queueBadge.style.display = 'none';
                }
            }
        })
        .catch(error => console.log('Error updating badges:', error));
}

// Update badges every 30 seconds
setInterval(updateNavigationBadges, 30000);

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Alt + 1-6 for quick navigation
    if (e.altKey) {
        switch(e.key) {
            case '1':
                window.location.href = 'index.php';
                break;
            case '2':
                window.location.href = 'new_order.php';
                break;
            case '3':
                window.location.href = 'order_list.php';
                break;
            case '4':
                window.location.href = 'queue_display.php';
                break;
            case 'c':
                showCalculator();
                break;
        }
    }
    
    // ESC for emergency stop
    if (e.key === 'Escape' && e.ctrlKey) {
        emergencyStop();
    }
});

// Touch/Swipe Navigation for Mobile
let touchStartX = 0;
let touchEndX = 0;

document.addEventListener('touchstart', function(e) {
    touchStartX = e.changedTouches[0].screenX;
});

document.addEventListener('touchend', function(e) {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
});

function handleSwipe() {
    const swipeThreshold = 50;
    const swipeDistance = touchEndX - touchStartX;
    
    if (Math.abs(swipeDistance) > swipeThreshold) {
        if (swipeDistance > 0) {
            // Swipe right - previous page
            navigatePrevious();
        } else {
            // Swipe left - next page
            navigateNext();
        }
    }
}

function navigatePrevious() {
    // Logic สำหรับไปหน้าก่อนหน้า
    const currentPage = '<?= $activePage ?? "dashboard" ?>';
    const pages = ['dashboard', 'new-order', 'orders', 'queue'];
    const currentIndex = pages.indexOf(currentPage);
    
    if (currentIndex > 0) {
        const previousPage = pages[currentIndex - 1];
        navigateToPage(previousPage);
    }
}

function navigateNext() {
    // Logic สำหรับไปหน้าถัดไป
    const currentPage = '<?= $activePage ?? "dashboard" ?>';
    const pages = ['dashboard', 'new-order', 'orders', 'queue'];
    const currentIndex = pages.indexOf(currentPage);
    
    if (currentIndex < pages.length - 1) {
        const nextPage = pages[currentIndex + 1];
        navigateToPage(nextPage);
    }
}

function navigateToPage(page) {
    const urls = {
        'dashboard': 'index.php',
        'new-order': 'new_order.php',
        'orders': 'order_list.php',
        'queue': 'queue_display.php'
    };
    
    if (urls[page]) {
        window.location.href = urls[page];
    }
}

// Initialize navigation
document.addEventListener('DOMContentLoaded', function() {
    // เพิ่ม tooltip สำหรับ quick action buttons
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // เพิ่ม active class animation
    const activeLink = document.querySelector('.nav-link.active');
    if (activeLink) {
        activeLink.style.transform = 'scale(1.05)';
        setTimeout(() => {
            activeLink.style.transform = '';
        }, 300);
    }
});
</script>

<style>
/* Navigation Styles */
.pos-navigation {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    border-bottom: 2px solid #cbd5e1;
    padding: 15px 0;
}

.nav-pills .nav-link {
    background: transparent;
    border: 2px solid transparent;
    color: #64748b;
    margin: 0 5px;
    padding: 15px 20px;
    border-radius: 15px;
    transition: all 0.3s ease;
    text-align: center;
    min-height: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.nav-pills .nav-link i {
    font-size: 1.5rem;
    margin-bottom: 5px;
}

.nav-pills .nav-link .nav-text {
    font-weight: 600;
    font-size: 0.9rem;
}

.nav-pills .nav-link .nav-description {
    font-size: 0.75rem;
    opacity: 0.7;
    margin-top: 2px;
}

.nav-pills .nav-link:hover {
    background: rgba(59, 130, 246, 0.1);
    border-color: #3b82f6;
    color: #1e40af;
    transform: translateY(-2px);
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-color: #1e40af;
    color: white;
    box-shadow: 0 5px 15px rgba(59, 130, 246, 0.3);
}

.quick-actions {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    gap: 10px;
    align-items: center;
}

.quick-action-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.quick-action-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
}

@media (max-width: 992px) {
    .pos-navigation {
        position: relative;
    }
    
    .nav-pills .nav-link {
        padding: 10px 15px;
        min-height: 60px;
        margin: 0 2px;
    }
    
    .nav-pills .nav-link i {
        font-size: 1.2rem;
    }
    
    .nav-pills .nav-link .nav-text {
        font-size: 0.8rem;
    }
    
    .nav-pills .nav-link .nav-description {
        display: none;
    }
    
    .quick-actions {
        position: static;
        transform: none;
        justify-content: center;
        margin-top: 15px;
    }
}

@media (max-width: 768px) {
    .mobile-nav-menu {
        background: white;
        padding: 15px;
        border-radius: 10px;
        margin: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .mobile-nav-item {
        margin-bottom: 10px;
    }
    
    .mobile-nav-item a {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: #64748b;
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s ease;
    }
    
    .mobile-nav-item a:hover,
    .mobile-nav-item a.active {
        background: #3b82f6;
        color: white;
    }
    
    .mobile-nav-item a i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }
}
</style>