<?php
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
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
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
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

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
                window.location.href = 'new_order.php';
                return;
            }
            
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