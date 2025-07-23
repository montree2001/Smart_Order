<?php
<<<<<<< HEAD
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Database.php';
require_once '../classes/Order.php';
require_once '../classes/Queue.php';
require_once '../classes/Payment.php';

// ตรวจสอบสิทธิ์การเข้าถึง
checkAuth(['admin', 'staff']);

$db = new Database();
$order = new Order($db->getConnection());
$queue = new Queue($db->getConnection());
$payment = new Payment($db->getConnection());

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'process_payment':
                $result = processPayment($_POST, $order, $queue, $payment);
                echo json_encode($result);
                break;
                
            case 'generate_qr':
                $result = generateQRCode($_POST['amount']);
                echo json_encode($result);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

$pageTitle = "ชำระเงิน";
$activePage = "payment";
=======
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

>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
<<<<<<< HEAD
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Smart Order Management</title>
    
    <!-- CSS -->
    <link href="<?= SITE_URL ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>assets/css/pos.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="pos-body">
    <div class="pos-container">
        
        <!-- Header -->
        <?php include 'includes/pos_header.php'; ?>

        <!-- Navigation -->
        <?php include 'includes/pos_nav.php'; ?>

        <!-- Main Content -->
        <div class="pos-content">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="payment-container">
                        
                        <!-- Order Summary -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="pos-card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-receipt"></i> สรุปออเดอร์</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="orderSummary">
                                            <!-- Order summary will be loaded here -->
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="customer-info" id="customerInfo">
                                            <!-- Customer info will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Methods -->
                            <div class="col-md-6">
                                <div class="pos-card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-credit-card"></i> วิธีการชำระเงิน</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="payment-methods">
                                            <div class="row">
                                                <div class="col-6 mb-3">
                                                    <div class="payment-method" data-method="cash" onclick="selectPaymentMethod('cash')">
                                                        <i class="fas fa-money-bill-wave fa-3x"></i>
                                                        <h6>เงินสด</h6>
                                                        <small>Cash Payment</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-6 mb-3">
                                                    <div class="payment-method" data-method="qr" onclick="selectPaymentMethod('qr')">
                                                        <i class="fas fa-qrcode fa-3x"></i>
                                                        <h6>QR Code</h6>
                                                        <small>PromptPay</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-6 mb-3">
                                                    <div class="payment-method" data-method="card" onclick="selectPaymentMethod('card')">
                                                        <i class="fas fa-credit-card fa-3x"></i>
                                                        <h6>บัตรเครดิต</h6>
                                                        <small>Credit/Debit Card</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-6 mb-3">
                                                    <div class="payment-method" data-method="transfer" onclick="selectPaymentMethod('transfer')">
                                                        <i class="fas fa-university fa-3x"></i>
                                                        <h6>โอนเงิน</h6>
                                                        <small>Bank Transfer</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Payment Details -->
                                        <div id="paymentDetails" style="display: none;">
                                            <hr>
                                            
                                            <!-- Cash Payment -->
                                            <div id="cashPayment" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label class="form-label">ยอดที่ต้องชำระ</label>
                                                        <input type="text" class="form-control form-control-lg" 
                                                               id="totalAmount" readonly>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label">จำนวนเงินที่รับ</label>
                                                        <input type="number" class="form-control form-control-lg" 
                                                               id="receivedAmount" placeholder="0" 
                                                               onchange="calculateChange()">
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <div class="change-display">
                                                        <h5>เงินทอน: <span id="changeAmount">฿0</span></h5>
                                                    </div>
                                                </div>
                                                
                                                <!-- Quick Amount Buttons -->
                                                <div class="mt-3">
                                                    <label class="form-label">จำนวนเงินด่วน</label>
                                                    <div class="quick-amounts" id="quickAmounts">
                                                        <!-- Quick amount buttons will be generated here -->
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- QR Payment -->
                                            <div id="qrPayment" style="display: none;">
                                                <div class="text-center">
                                                    <div id="qrCodeContainer">
                                                        <div class="loading-spinner"></div>
                                                        <p>กำลังสร้าง QR Code...</p>
                                                    </div>
                                                    <div class="mt-3">
                                                        <p class="text-primary">
                                                            <i class="fas fa-mobile-alt"></i>
                                                            สแกน QR Code ด้วยแอปธนาคาร
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Card Payment -->
                                            <div id="cardPayment" style="display: none;">
                                                <div class="text-center">
                                                    <div class="card-payment-status">
                                                        <i class="fas fa-credit-card fa-3x text-primary"></i>
                                                        <h5 class="mt-3">กรุณาเสียบบัตรที่เครื่อง EDC</h5>
                                                        <p class="text-muted">รอการยืนยันการชำระเงิน...</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Transfer Payment -->
                                            <div id="transferPayment" style="display: none;">
                                                <div class="bank-info">
                                                    <h6>ข้อมูลบัญชีสำหรับโอนเงิน</h6>
                                                    <div class="bank-details">
                                                        <p><strong>ธนาคาร:</strong> ไทยพาณิชย์</p>
                                                        <p><strong>ชื่อบัญชี:</strong> ร้านอาหารดีๆ</p>
                                                        <p><strong>เลขบัญชี:</strong> 123-456-7890</p>
                                                        <p class="text-primary"><strong>จำนวนเงิน:</strong> <span id="transferAmount">฿0</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Actions -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="payment-actions">
                                    <button type="button" class="btn btn-secondary btn-lg" onclick="goBack()">
                                        <i class="fas fa-arrow-left"></i> ย้อนกลับ
                                    </button>
                                    
                                    <button type="button" class="btn btn-success btn-lg" id="confirmPaymentBtn" 
                                            onclick="confirmPayment()" disabled>
                                        <i class="fas fa-check"></i> ยืนยันการชำระเงิน
=======
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
>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
                                    </button>
                                </div>
                            </div>
                        </div>
<<<<<<< HEAD

                    </div>
                </div>
=======
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

>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
            </div>
        </div>
    </div>

<<<<<<< HEAD
    <!-- Processing Modal -->
    <div class="modal fade" id="processingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center py-5">
                    <div class="loading-spinner mb-3"></div>
                    <h5>กำลังดำเนินการ...</h5>
                    <p class="text-muted">กรุณารอสักครู่</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check-circle"></i> ชำระเงินสำเร็จ</h5>
                </div>
                <div class="modal-body">
                    <div id="receiptPreview">
                        <!-- Receipt will be shown here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="printReceipt()">
                        <i class="fas fa-print"></i> พิมพ์ใบเสร็จ
                    </button>
                    <button type="button" class="btn btn-success" onclick="sendReceiptLine()">
                        <i class="fab fa-line"></i> ส่งทาง LINE
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="newOrder()">
                        <i class="fas fa-plus"></i> สั่งซื้อใหม่
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= SITE_URL ?>assets/js/jquery.min.js"></script>
    <script src="<?= SITE_URL ?>assets/js/bootstrap.min.js"></script>
    <script src="<?= SITE_URL ?>assets/js/pos.js"></script>

    <script>
        let orderData = null;
        let selectedPaymentMethod = null;
        let totalAmount = 0;

        $(document).ready(function() {
            loadOrderData();
            updateDateTime();
            setInterval(updateDateTime, 1000);
        });

        // Load order data from session storage
        function loadOrderData() {
            const data = sessionStorage.getItem('orderData');
            if (!data) {
                alert('ไม่พบข้อมูลออเดอร์');
=======
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
>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
                window.location.href = 'new_order.php';
                return;
            }
            
<<<<<<< HEAD
            orderData = JSON.parse(data);
            displayOrderSummary();
            displayCustomerInfo();
        }

        // Display order summary
        function displayOrderSummary() {
            totalAmount = orderData.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            const summaryHtml = `
                <div class="order-items">
                    ${orderData.cart.map(item => `
                        <div class="order-item">
                            <div class="item-details">
                                <h6>${item.name}</h6>
                                <small>฿${item.price} x ${item.quantity}</small>
                            </div>
                            <div class="item-total">
                                ฿${(item.price * item.quantity).toFixed(0)}
                            </div>
                        </div>
                    `).join('')}
                </div>
                <hr>
                <div class="order-total">
                    <div class="total-row">
                        <span>จำนวนรายการ:</span>
                        <span>${orderData.cart.reduce((sum, item) => sum + item.quantity, 0)} รายการ</span>
                    </div>
                    <div class="total-row">
                        <span>ราคารวม:</span>
                        <span>฿${totalAmount.toFixed(0)}</span>
                    </div>
                    <div class="total-final">
                        <strong>ยอดชำระ: ฿${totalAmount.toFixed(0)}</strong>
                    </div>
                </div>
            `;
            
            document.getElementById('orderSummary').innerHTML = summaryHtml;
        }

        // Display customer info
        function displayCustomerInfo() {
            const infoHtml = `
                <h6>ข้อมูลลูกค้า</h6>
                <p><strong>ชื่อ:</strong> ${orderData.customer_name}</p>
                ${orderData.customer_phone ? `<p><strong>เบอร์โทร:</strong> ${orderData.customer_phone}</p>` : ''}
            `;
            
            document.getElementById('customerInfo').innerHTML = infoHtml;
        }

        // Select payment method
        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;
            
            // Reset all payment methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('active');
            });
            
            // Activate selected method
            document.querySelector(`[data-method="${method}"]`).classList.add('active');
            
            // Show payment details
            document.getElementById('paymentDetails').style.display = 'block';
            
            // Hide all payment forms
            ['cashPayment', 'qrPayment', 'cardPayment', 'transferPayment'].forEach(id => {
                document.getElementById(id).style.display = 'none';
            });
            
            // Show selected payment form
            document.getElementById(method + 'Payment').style.display = 'block';
            
            // Initialize payment method specific logic
            switch (method) {
                case 'cash':
                    initCashPayment();
                    break;
                case 'qr':
                    initQRPayment();
                    break;
                case 'card':
                    initCardPayment();
                    break;
                case 'transfer':
                    initTransferPayment();
                    break;
            }
            
            // Enable confirm button
            document.getElementById('confirmPaymentBtn').disabled = false;
        }

        // Initialize cash payment
        function initCashPayment() {
            document.getElementById('totalAmount').value = `฿${totalAmount.toFixed(0)}`;
            generateQuickAmounts();
        }

        // Generate quick amount buttons
        function generateQuickAmounts() {
            const amounts = [
                Math.ceil(totalAmount / 100) * 100, // Round up to nearest 100
                Math.ceil(totalAmount / 500) * 500, // Round up to nearest 500
                Math.ceil(totalAmount / 1000) * 1000, // Round up to nearest 1000
            ];
            
            // Remove duplicates and add some common amounts
            const uniqueAmounts = [...new Set([...amounts, 500, 1000, 2000])].sort((a, b) => a - b);
            
            const quickAmountsHtml = uniqueAmounts
                .filter(amount => amount >= totalAmount)
                .slice(0, 6)
                .map(amount => `
                    <button class="btn btn-outline-primary quick-amount" 
                            onclick="setReceivedAmount(${amount})">
                        ฿${amount}
                    </button>
                `).join('');
            
            document.getElementById('quickAmounts').innerHTML = quickAmountsHtml;
        }

        // Set received amount
        function setReceivedAmount(amount) {
            document.getElementById('receivedAmount').value = amount;
            calculateChange();
        }

        // Calculate change
        function calculateChange() {
            const received = parseFloat(document.getElementById('receivedAmount').value) || 0;
            const change = received - totalAmount;
            document.getElementById('changeAmount').textContent = `฿${Math.max(0, change).toFixed(0)}`;
        }

        // Initialize QR payment
        function initQRPayment() {
            generateQRCode(totalAmount);
        }

        // Generate QR code
        function generateQRCode(amount) {
            $.post('payment.php', {
                action: 'generate_qr',
                amount: amount
            })
            .done(function(response) {
                if (response.success) {
                    document.getElementById('qrCodeContainer').innerHTML = `
                        <img src="${response.qr_code}" alt="QR Code" style="width: 250px; height: 250px;">
                        <p class="mt-2"><strong>จำนวนเงิน: ฿${amount}</strong></p>
                    `;
                } else {
                    document.getElementById('qrCodeContainer').innerHTML = `
                        <div class="alert alert-danger">
                            ไม่สามารถสร้าง QR Code ได้: ${response.message}
                        </div>
                    `;
                }
            })
            .fail(function() {
                document.getElementById('qrCodeContainer').innerHTML = `
                    <div class="alert alert-danger">
                        เกิดข้อผิดพลาดในการสร้าง QR Code
                    </div>
                `;
            });
        }

        // Initialize card payment
        function initCardPayment() {
            // Simulate card reader connection
            setTimeout(() => {
                document.querySelector('.card-payment-status').innerHTML = `
                    <i class="fas fa-check-circle fa-3x text-success"></i>
                    <h5 class="mt-3">พร้อมรับชำระ</h5>
                    <p class="text-muted">เครื่อง EDC พร้อมใช้งาน</p>
                `;
            }, 2000);
        }

        // Initialize transfer payment
        function initTransferPayment() {
            document.getElementById('transferAmount').textContent = `฿${totalAmount.toFixed(0)}`;
        }

        // Confirm payment
        function confirmPayment() {
            if (!selectedPaymentMethod) {
                showToast('กรุณาเลือกวิธีการชำระเงิน', '', 'warning');
                return;
            }
            
            // Validate payment method specific requirements
            if (selectedPaymentMethod === 'cash') {
                const received = parseFloat(document.getElementById('receivedAmount').value) || 0;
                if (received < totalAmount) {
                    showToast('จำนวนเงินไม่เพียงพอ', 'กรุณาใส่จำนวนเงินที่ถูกต้อง', 'warning');
                    return;
                }
            }
            
            // Show processing modal
            const processingModal = new bootstrap.Modal(document.getElementById('processingModal'));
            processingModal.show();
            
            // Prepare payment data
            const paymentData = {
                action: 'process_payment',
                payment_method: selectedPaymentMethod,
                order_data: orderData,
                total_amount: totalAmount
            };
            
            if (selectedPaymentMethod === 'cash') {
                paymentData.received_amount = parseFloat(document.getElementById('receivedAmount').value);
                paymentData.change_amount = paymentData.received_amount - totalAmount;
            }
            
            // Process payment
            $.post('payment.php', paymentData)
                .done(function(response) {
                    processingModal.hide();
                    
                    if (response.success) {
                        showSuccessModal(response.order_id, response.queue_number);
                        // Clear session storage
                        sessionStorage.removeItem('orderData');
                    } else {
                        showToast('เกิดข้อผิดพลาด', response.message, 'error');
                    }
                })
                .fail(function() {
                    processingModal.hide();
                    showToast('เกิดข้อผิดพลาด', 'ไม่สามารถดำเนินการได้', 'error');
                });
        }

        // Show success modal
        function showSuccessModal(orderId, queueNumber) {
            const receiptHtml = generateReceipt(orderId, queueNumber);
            document.getElementById('receiptPreview').innerHTML = receiptHtml;
            
            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        }

        // Generate receipt HTML
        function generateReceipt(orderId, queueNumber) {
            const now = new Date();
            const change = selectedPaymentMethod === 'cash' ? 
                (parseFloat(document.getElementById('receivedAmount').value) - totalAmount) : 0;
            
            return `
                <div class="receipt-container">
                    <div class="receipt-header text-center">
                        <h4>ร้านอาหารดีๆ</h4>
                        <p>Smart Order Management<br>โทร: 02-XXX-XXXX</p>
                        <hr>
                    </div>
                    
                    <div class="receipt-body">
                        <div class="receipt-info">
                            <p><strong>ใบเสร็จ #${orderId}</strong></p>
                            <p>คิว: <strong style="font-size: 1.2em;">${queueNumber}</strong></p>
                            <p>วันที่: ${now.toLocaleDateString('th-TH')}</p>
                            <p>เวลา: ${now.toLocaleTimeString('th-TH')}</p>
                            <p>ลูกค้า: ${orderData.customer_name}</p>
                            ${orderData.customer_phone ? `<p>เบอร์: ${orderData.customer_phone}</p>` : ''}
                        </div>
                        
                        <hr>
                        
                        <div class="receipt-items">
                            ${orderData.cart.map(item => `
                                <div class="receipt-item">
                                    <div>${item.name} x${item.quantity}</div>
                                    <div>฿${(item.price * item.quantity).toFixed(0)}</div>
                                </div>
                            `).join('')}
                        </div>
                        
                        <hr>
                        
                        <div class="receipt-total">
                            <div class="receipt-item">
                                <div><strong>รวมทั้งสิ้น</strong></div>
                                <div><strong>฿${totalAmount.toFixed(0)}</strong></div>
                            </div>
                            ${selectedPaymentMethod === 'cash' ? `
                                <div class="receipt-item">
                                    <div>รับเงิน</div>
                                    <div>฿${parseFloat(document.getElementById('receivedAmount').value).toFixed(0)}</div>
                                </div>
                                <div class="receipt-item">
                                    <div>เงินทอน</div>
                                    <div>฿${change.toFixed(0)}</div>
                                </div>
                            ` : ''}
                            <div class="receipt-item">
                                <div>วิธีชำระ</div>
                                <div>${getPaymentMethodText(selectedPaymentMethod)}</div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="receipt-footer text-center">
                            <p>ขอบคุณที่ใช้บริการ</p>
                            <p><small>กรุณาเก็บใบเสร็จไว้เป็นหลักฐาน</small></p>
                        </div>
                    </div>
                </div>
            `;
        }

        // Get payment method text
        function getPaymentMethodText(method) {
            const texts = {
                'cash': 'เงินสด',
                'qr': 'QR Code (PromptPay)',
                'card': 'บัตรเครดิต/เดบิต',
                'transfer': 'โอนเงิน'
            };
            return texts[method] || method;
        }

        // Print receipt
        function printReceipt() {
            const receiptContent = document.getElementById('receiptPreview').innerHTML;
            const printWindow = window.open('', '', 'height=600,width=400');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>ใบเสร็จ</title>
                        <style>
                            body { font-family: 'Kanit', sans-serif; font-size: 14px; }
                            .receipt-container { max-width: 300px; margin: 0 auto; }
                            .receipt-item { display: flex; justify-content: space-between; margin: 5px 0; }
                        </style>
                    </head>
                    <body>
                        ${receiptContent}
                        <script>window.print(); window.close();</script>
                    </body>
                </html>
            `);
        }

        // Send receipt via LINE
        function sendReceiptLine() {
            if (!orderData.customer_phone) {
                showToast('ไม่มีเบอร์โทรศัพท์', 'ไม่สามารถส่งใบเสร็จทาง LINE ได้', 'warning');
                return;
            }
            
            showToast('ส่งสำเร็จ', 'ใบเสร็จถูกส่งทาง LINE แล้ว', 'success');
        }

        // Create new order
        function newOrder() {
            window.location.href = 'new_order.php';
        }

        // Go back to previous page
        function goBack() {
            window.location.href = 'new_order.php';
        }

        function updateDateTime() {
            const now = new Date();
            const options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                timeZone: 'Asia/Bangkok'
            };
            document.getElementById('currentTime').textContent = 
                now.toLocaleDateString('th-TH', options);
        }

        function showToast(title, message, type = 'info') {
            // Toast implementation
            console.log(`${type}: ${title} - ${message}`);
        }
    </script>

</body>
</html>

<?php
function processPayment($data, $order, $queue, $payment) {
    try {
        $conn = $order->getConnection();
        $conn->beginTransaction();
        
        // Create order
        $orderData = json_decode($data['order_data'], true);
        
        $orderId = $order->createOrder([
            'customer_name' => $orderData['customer_name'],
            'customer_phone' => $orderData['customer_phone'] ?? null,
            'items' => $orderData['cart'],
            'total_amount' => $data['total_amount'],
            'payment_method' => $data['payment_method'],
            'received_amount' => $data['received_amount'] ?? null,
            'change_amount' => $data['change_amount'] ?? null,
            'status' => 'pending'
        ]);
        
        // Create queue
        $queueNumber = $queue->createQueue($orderId);
        
        // Record payment
        $payment->recordPayment([
            'order_id' => $orderId,
            'payment_method' => $data['payment_method'],
            'amount' => $data['total_amount'],
            'received_amount' => $data['received_amount'] ?? null,
            'change_amount' => $data['change_amount'] ?? null,
            'status' => 'completed'
        ]);
        
        $conn->commit();
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'queue_number' => $queueNumber,
            'message' => 'ชำระเงินสำเร็จ'
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function generateQRCode($amount) {
    // Simulate QR code generation
    // In real implementation, you would generate actual PromptPay QR code
    $qrData = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==";
    
    return [
        'success' => true,
        'qr_code' => $qrData,
        'amount' => $amount
    ];
}
?>
=======
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
>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
