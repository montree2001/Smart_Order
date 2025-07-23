<?php
// pos/payment.php - หน้าชำระเงิน POS
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// ตรวจสอบสิทธิ์การเข้าถึง POS
checkPermission(['admin', 'pos_staff', 'manager']);

// ดึงการตั้งค่าการชำระเงิน
$payment_settings_query = "
    SELECT setting_key, setting_value 
    FROM system_settings 
    WHERE category IN ('payment', 'shop')
";
$payment_settings_result = mysqli_query($connection, $payment_settings_query);
$payment_settings = [];
while ($setting = mysqli_fetch_assoc($payment_settings_result)) {
    $payment_settings[$setting['setting_key']] = $setting['setting_value'];
}

$accept_cash = $payment_settings['accept_cash'] ?? '1';
$accept_qr = $payment_settings['accept_qr'] ?? '1';
$accept_card = $payment_settings['accept_card'] ?? '0';
$promptpay_id = $payment_settings['promptpay_id'] ?? '';
$shop_name = $payment_settings['shop_name'] ?? 'ร้านอาหาร';

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>ชำระเงิน - POS System</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/pos.css" rel="stylesheet">
    
    <style>
        .payment-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .payment-method-card {
            border: 2px solid transparent;
            border-radius: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .payment-method-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .payment-method-card.selected {
            border-color: #007bff;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        }
        
        .payment-method-card .payment-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .payment-method-card .payment-title {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .order-summary-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 90px;
        }
        
        .cash-input-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .quick-amount-btn {
            border-radius: 10px;
            font-weight: 600;
            margin: 5px;
            transition: all 0.2s ease;
        }
        
        .quick-amount-btn:hover {
            transform: scale(1.05);
        }
        
        .qr-code-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .payment-success {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-top: 20px;
            display: none;
        }
        
        @media (max-width: 991.98px) {
            .order-summary-card {
                position: static;
                margin-bottom: 20px;
            }
        }
        
        @media (max-width: 767.98px) {
            .payment-method-card {
                min-height: 100px;
            }
            
            .payment-method-card .payment-icon {
                font-size: 2rem;
            }
            
            .payment-method-card .payment-title {
                font-size: 0.95rem;
            }
            
            .quick-amount-btn {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body class="pos-body">
    
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg pos-navbar fixed-top">
        <div class="container-fluid">
            <a href="new_order.php" class="navbar-brand">
                <i class="fas fa-arrow-left me-2"></i>
                <span class="fw-bold">ชำระเงิน</span>
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item">
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-clock me-1"></i>
                        <span id="current-time"><?php echo date('H:i:s'); ?></span>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid pos-main-content">
        <div class="payment-container">
            <div class="row">
                
                <!-- Payment Methods & Process -->
                <div class="col-lg-8 mb-4">
                    
                    <!-- Payment Methods Selection -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-credit-card me-2"></i>
                                เลือกวิธีการชำระเงิน
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                
                                <!-- Cash Payment -->
                                <?php if ($accept_cash == '1'): ?>
                                <div class="col-6 col-md-4">
                                    <div class="payment-method-card bg-success text-white" data-method="cash">
                                        <div class="text-center">
                                            <i class="fas fa-money-bill-wave payment-icon"></i>
                                            <div class="payment-title">เงินสด</div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- QR Payment -->
                                <?php if ($accept_qr == '1' && $promptpay_id): ?>
                                <div class="col-6 col-md-4">
                                    <div class="payment-method-card bg-info text-white" data-method="qr">
                                        <div class="text-center">
                                            <i class="fas fa-qrcode payment-icon"></i>
                                            <div class="payment-title">QR PromptPay</div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Card Payment -->
                                <?php if ($accept_card == '1'): ?>
                                <div class="col-6 col-md-4">
                                    <div class="payment-method-card bg-warning text-dark" data-method="card">
                                        <div class="text-center">
                                            <i class="fas fa-credit-card payment-icon"></i>
                                            <div class="payment-title">บัตรเครดิต/เดบิต</div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                            </div>
                        </div>
                    </div>

                    <!-- Cash Payment Section -->
                    <div class="payment-section" id="cash-section" style="display: none;">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-money-bill-wave me-2"></i>
                                    การชำระด้วยเงินสด
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="cash-input-section">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">จำนวนเงินที่ลูกค้าจ่าย</label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-success text-white">฿</span>
                                                <input type="number" class="form-control" id="cash-received" 
                                                       placeholder="0" min="0" step="0.01">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">เงินทอน</label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-info text-white">฿</span>
                                                <input type="text" class="form-control" id="change-amount" 
                                                       readonly placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Quick Amount Buttons -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">จำนวนเงินแนะนำ</label>
                                        <div class="d-flex flex-wrap" id="quick-amounts">
                                            <!-- จะถูกสร้างด้วย JavaScript -->
                                        </div>
                                    </div>
                                    
                                    <button class="btn btn-success btn-lg w-100" id="confirm-cash-payment" disabled>
                                        <i class="fas fa-check me-2"></i>
                                        ยืนยันการชำระเงินสด
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- QR Payment Section -->
                    <div class="payment-section" id="qr-section" style="display: none;">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-qrcode me-2"></i>
                                    สแกน QR Code เพื่อชำระเงิน
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="qr-code-section">
                                    <div id="qr-code-container">
                                        <div class="spinner-border text-info mb-3" role="status">
                                            <span class="visually-hidden">กำลังสร้าง QR Code...</span>
                                        </div>
                                        <p>กำลังสร้าง QR Code...</p>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <p class="mb-2">
                                            <i class="fas fa-mobile-alt me-2"></i>
                                            กรุณาแสกน QR Code ด้วยแอป PromptPay
                                        </p>
                                        <p class="text-muted">
                                            หรือโอนเงินไปที่หมายเลข: <strong><?php echo $promptpay_id; ?></strong>
                                        </p>
                                    </div>
                                    
                                    <div class="row mt-4">
                                        <div class="col-6">
                                            <button class="btn btn-outline-secondary w-100" onclick="refreshQRCode()">
                                                <i class="fas fa-sync-alt me-2"></i>
                                                รีเฟรช QR
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button class="btn btn-info w-100" onclick="confirmQRPayment()">
                                                <i class="fas fa-check me-2"></i>
                                                ยืนยันการชำระ
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Payment Section -->
                    <div class="payment-section" id="card-section" style="display: none;">
                        <div class="card">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-credit-card me-2"></i>
                                    การชำระด้วยบัตร
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center py-4">
                                    <i class="fas fa-credit-card fa-4x text-warning mb-3"></i>
                                    <h4>กรุณาใส่บัตรเครดิต/เดบิต</h4>
                                    <p class="text-muted">รอการอนุมัติจากเครื่องรูดบัตร</p>
                                    
                                    <div class="spinner-border text-warning mt-3 mb-3" role="status">
                                        <span class="visually-hidden">กำลังประมวลผล...</span>
                                    </div>
                                    
                                    <div class="row mt-4">
                                        <div class="col-6">
                                            <button class="btn btn-outline-secondary w-100" onclick="cancelCardPayment()">
                                                <i class="fas fa-times me-2"></i>
                                                ยกเลิก
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button class="btn btn-warning w-100" onclick="confirmCardPayment()">
                                                <i class="fas fa-check me-2"></i>
                                                ยืนยันการชำระ
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Success -->
                    <div class="payment-success" id="payment-success">
                        <div class="mb-4">
                            <i class="fas fa-check-circle fa-4x mb-3"></i>
                            <h3>ชำระเงินสำเร็จ!</h3>
                            <p>ออเดอร์ได้ถูกบันทึกในระบบแล้ว</p>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <button class="btn btn-light w-100" onclick="printReceipt()">
                                    <i class="fas fa-print me-2"></i>
                                    พิมพ์ใบเสร็จ
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-success w-100" onclick="newOrder()">
                                    <i class="fas fa-plus me-2"></i>
                                    ออเดอร์ใหม่
                                </button>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="order-summary-card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-receipt me-2"></i>
                                สรุปออเดอร์
                            </h5>
                        </div>
                        <div class="card-body">
                            
                            <!-- Customer Info -->
                            <div class="mb-3 pb-3 border-bottom">
                                <h6 class="fw-bold text-muted">ข้อมูลลูกค้า</h6>
                                <p class="mb-1">
                                    <i class="fas fa-user me-2"></i>
                                    <span id="summary-customer-name">-</span>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-phone me-2"></i>
                                    <span id="summary-customer-phone">-</span>
                                </p>
                                <p class="mb-0">
                                    <i class="fas fa-utensils me-2"></i>
                                    <span id="summary-order-type">-</span>
                                </p>
                            </div>

                            <!-- Order Items -->
                            <div class="mb-3 pb-3 border-bottom">
                                <h6 class="fw-bold text-muted">รายการสินค้า</h6>
                                <div id="summary-items" style="max-height: 300px; overflow-y: auto;">
                                    <!-- จะถูกเติมด้วย JavaScript -->
                                </div>
                            </div>

                            <!-- Price Summary -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>ราคารวม:</span>
                                    <span id="summary-subtotal">฿0</span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2" id="summary-service-charge-row" style="display: none;">
                                    <span>ค่าบริการ:</span>
                                    <span id="summary-service-charge">฿0</span>
                                </div>
                                
                                <div class="d-flex justify-content-between mb-2">
                                    <span>ภาษี:</span>
                                    <span id="summary-tax">฿0</span>
                                </div>
                                
                                <hr>
                                
                                <div class="d-flex justify-content-between mb-3">
                                    <strong class="fs-5">ยอดรวมสุทธิ:</strong>
                                    <strong class="fs-4 text-primary" id="summary-total">฿0</strong>
                                </div>
                            </div>
                            
                            <!-- Payment Method Info -->
                            <div class="alert alert-info" id="payment-method-info" style="display: none;">
                                <i class="fas fa-info-circle me-2"></i>
                                <span id="payment-method-text">เลือกวิธีการชำระเงิน</span>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery.min.js"></script>
    
    <script>
        // Global variables
        let orderData = {};
        let selectedPaymentMethod = '';
        let orderCreated = false;
        
        // อัปเดตเวลา
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('th-TH', {
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }
        setInterval(updateTime, 1000);
        
        // โหลดข้อมูลออเดอร์จาก sessionStorage
        function loadOrderData() {
            const storedData = sessionStorage.getItem('pos_order_data');
            if (!storedData) {
                alert('ไม่พบข้อมูลออเดอร์ กรุณาเริ่มสร้างออเดอร์ใหม่');
                window.location.href = 'new_order.php';
                return;
            }
            
            orderData = JSON.parse(storedData);
            displayOrderSummary();
        }
        
        // แสดงสรุปออเดอร์
        function displayOrderSummary() {
            // ข้อมูลลูกค้า
            document.getElementById('summary-customer-name').textContent = orderData.customer_name;
            document.getElementById('summary-customer-phone').textContent = orderData.customer_phone || '-';
            document.getElementById('summary-order-type').textContent = getOrderTypeText(orderData.order_type);
            
            // รายการสินค้า
            let itemsHTML = '';
            orderData.items.forEach(item => {
                itemsHTML += `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="fw-semibold">${item.name}</div>
                            <small class="text-muted">฿${item.price.toLocaleString()} x ${item.quantity}</small>
                        </div>
                        <div class="fw-bold">฿${(item.price * item.quantity).toLocaleString()}</div>
                    </div>
                `;
            });
            document.getElementById('summary-items').innerHTML = itemsHTML;
            
            // สรุปราคา
            document.getElementById('summary-subtotal').textContent = '฿' + orderData.subtotal.toLocaleString();
            
            if (orderData.service_charge > 0) {
                document.getElementById('summary-service-charge-row').style.display = 'flex';
                document.getElementById('summary-service-charge').textContent = '฿' + orderData.service_charge.toLocaleString();
            }
            
            document.getElementById('summary-tax').textContent = '฿' + orderData.tax_amount.toLocaleString();
            document.getElementById('summary-total').textContent = '฿' + orderData.total_amount.toLocaleString();
            
            // สร้างปุ่มจำนวนเงินแนะนำสำหรับเงินสด
            createQuickAmountButtons();
        }
        
        // แปลงประเภทออเดอร์เป็นข้อความ
        function getOrderTypeText(type) {
            const types = {
                'dine_in': 'ทานที่ร้าน',
                'takeaway': 'ซื้อกลับ',
                'delivery': 'เดลิเวอรี่'
            };
            return types[type] || type;
        }
        
        // สร้างปุ่มจำนวนเงินแนะนำ
        function createQuickAmountButtons() {
            const total = orderData.total_amount;
            const amounts = [
                total, // จำนวนเงินพอดี
                Math.ceil(total / 100) * 100, // ปัดเป็นร้อย
                Math.ceil(total / 500) * 500, // ปัดเป็น 500
                Math.ceil(total / 1000) * 1000, // ปัดเป็นพัน
            ];
            
            // เพิ่มธนบัตรยอดนิยม
            const popularAmounts = [100, 500, 1000];
            popularAmounts.forEach(amount => {
                if (amount > total && !amounts.includes(amount)) {
                    amounts.push(amount);
                }
            });
            
            // เรียงลำดับและลบค่าซ้ำ
            const uniqueAmounts = [...new Set(amounts)].sort((a, b) => a - b);
            
            let buttonsHTML = '';
            uniqueAmounts.forEach(amount => {
                buttonsHTML += `
                    <button class="btn btn-outline-success quick-amount-btn" 
                            onclick="setCashAmount(${amount})">
                        ฿${amount.toLocaleString()}
                    </button>
                `;
            });
            
            document.getElementById('quick-amounts').innerHTML = buttonsHTML;
        }
        
        // เลือกวิธีการชำระเงิน
        $(document).on('click', '.payment-method-card', function() {
            const method = $(this).data('method');
            selectedPaymentMethod = method;
            
            // อัปเดต UI
            $('.payment-method-card').removeClass('selected');
            $(this).addClass('selected');
            
            // ซ่อนส่วนการชำระเงินทั้งหมด
            $('.payment-section').hide();
            
            // แสดงส่วนที่เลือก
            $(`#${method}-section`).show();
            
            // อัปเดตข้อมูลวิธีการชำระ
            const methodTexts = {
                'cash': 'ชำระด้วยเงินสด',
                'qr': 'ชำระด้วย QR PromptPay',
                'card': 'ชำระด้วยบัตรเครดิต/เดบิต'
            };
            
            document.getElementById('payment-method-info').style.display = 'block';
            document.getElementById('payment-method-text').textContent = methodTexts[method];
            
            // สำหรับ QR Payment ให้สร้าง QR Code
            if (method === 'qr') {
                generateQRCode();
            }
        });
        
        // ตั้งค่าจำนวนเงินสด
        function setCashAmount(amount) {
            document.getElementById('cash-received').value = amount;
            calculateChange();
        }
        
        // คำนวณเงินทอน
        function calculateChange() {
            const cashReceived = parseFloat(document.getElementById('cash-received').value) || 0;
            const totalAmount = orderData.total_amount;
            const change = cashReceived - totalAmount;
            
            const changeInput = document.getElementById('change-amount');
            const confirmBtn = document.getElementById('confirm-cash-payment');
            
            if (change >= 0) {
                changeInput.value = change.toLocaleString();
                changeInput.classList.remove('text-danger');
                changeInput.classList.add('text-success');
                confirmBtn.disabled = false;
            } else {
                changeInput.value = Math.abs(change).toLocaleString() + ' (ขาด)';
                changeInput.classList.remove('text-success');
                changeInput.classList.add('text-danger');
                confirmBtn.disabled = true;
            }
        }
        
        // ฟังเหตุการณ์เปลี่ยนแปลงในช่องจำนวนเงิน
        $(document).on('input', '#cash-received', calculateChange);
        
        // สร้าง QR Code
        function generateQRCode() {
            const qrContainer = document.getElementById('qr-code-container');
            
            // ใช้ API สร้าง QR Code (ในที่นี้จำลอง)
            setTimeout(() => {
                qrContainer.innerHTML = `
                    <div class="qr-code-placeholder bg-light border rounded p-4 mb-3" style="width: 200px; height: 200px; margin: 0 auto;">
                        <div class="d-flex align-items-center justify-content-center h-100">
                            <div class="text-center">
                                <i class="fas fa-qrcode fa-4x text-muted mb-2"></i>
                                <p class="mb-0 text-muted">QR Code</p>
                                <small class="text-muted">฿${orderData.total_amount.toLocaleString()}</small>
                            </div>
                        </div>
                    </div>
                `;
            }, 1000);
        }
        
        // รีเฟรช QR Code
        function refreshQRCode() {
            document.getElementById('qr-code-container').innerHTML = `
                <div class="spinner-border text-info mb-3" role="status">
                    <span class="visually-hidden">กำลังสร้าง QR Code...</span>
                </div>
                <p>กำลังสร้าง QR Code...</p>
            `;
            generateQRCode();
        }
        
        // ยืนยันการชำระเงินสด
        $(document).on('click', '#confirm-cash-payment', function() {
            if (confirm('ยืนยันการชำระด้วยเงินสด?')) {
                const cashReceived = parseFloat(document.getElementById('cash-received').value);
                const change = cashReceived - orderData.total_amount;
                
                processPayment('cash', {
                    cash_received: cashReceived,
                    change_amount: change
                });
            }
        });
        
        // ยืนยันการชำระด้วย QR
        function confirmQRPayment() {
            if (confirm('ได้รับการชำระเงินผ่าน QR Code แล้ว?')) {
                processPayment('qr', {});
            }
        }
        
        // ยืนยันการชำระด้วยบัตร
        function confirmCardPayment() {
            if (confirm('การชำระด้วยบัตรสำเร็จแล้ว?')) {
                processPayment('card', {});
            }
        }
        
        // ยกเลิกการชำระด้วยบัตร
        function cancelCardPayment() {
            selectedPaymentMethod = '';
            $('.payment-method-card').removeClass('selected');
            $('.payment-section').hide();
            document.getElementById('payment-method-info').style.display = 'none';
        }
        
        // ประมวลผลการชำระเงิน
        function processPayment(method, paymentDetails) {
            // แสดง loading
            $('body').append('<div class="modal-backdrop fade show" id="loading-backdrop"></div>');
            
            // เตรียมข้อมูลสำหรับส่งไปยัง server
            const paymentData = {
                ...orderData,
                payment_method: method,
                payment_details: paymentDetails,
                staff_id: <?php echo $_SESSION['user_id']; ?>
            };
            
            // ส่งข้อมูลไป server
            fetch('api/create_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(paymentData)
            })
            .then(response => response.json())
            .then(data => {
                $('#loading-backdrop').remove();
                
                if (data.success) {
                    // เก็บข้อมูลออเดอร์ที่สร้างสำเร็จ
                    sessionStorage.setItem('completed_order', JSON.stringify(data.order));
                    
                    // แสดงหน้าสำเร็จ
                    $('.payment-section').hide();
                    $('.payment-method-card').removeClass('selected');
                    document.getElementById('payment-success').style.display = 'block';
                    
                    orderCreated = true;
                    
                    // เล่นเสียงแจ้งเตือน
                    if (data.order.queue_number) {
                        setTimeout(() => {
                            playQueueSound(data.order.queue_number);
                        }, 1000);
                    }
                } else {
                    alert('เกิดข้อผิดพลาด: ' + data.message);
                }
            })
            .catch(error => {
                $('#loading-backdrop').remove();
                console.error('Error:', error);
                alert('เกิดข้อผิดพลาดในการประมวลผล');
            });
        }
        
        // เล่นเสียงประกาศหมายเลขคิว
        function playQueueSound(queueNumber) {
            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(
                    `ได้รับออเดอร์หมายเลขคิว ${queueNumber} เรียบร้อยแล้วค่ะ`
                );
                utterance.lang = 'th-TH';
                utterance.rate = 0.8;
                speechSynthesis.speak(utterance);
            }
        }
        
        // พิมพ์ใบเสร็จ
        function printReceipt() {
            const orderData = sessionStorage.getItem('completed_order');
            if (orderData) {
                const order = JSON.parse(orderData);
                window.open(`print_receipt.php?order_id=${order.id}`, '_blank');
            }
        }
        
        // สร้างออเดอร์ใหม่
        function newOrder() {
            sessionStorage.removeItem('pos_order_data');
            sessionStorage.removeItem('completed_order');
            window.location.href = 'new_order.php';
        }
        
        // ป้องกันการปิดหน้าโดยไม่ตั้งใจ
        window.addEventListener('beforeunload', function(e) {
            if (!orderCreated && orderData.items && orderData.items.length > 0) {
                e.preventDefault();
                e.returnValue = 'คุณมีออเดอร์ที่ยังไม่ได้ชำระเงิน ต้องการออกจากหน้านี้?';
            }
        });
        
        // Initialize
        $(document).ready(function() {
            loadOrderData();
        });
    </script>

</body>
</html>