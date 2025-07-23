<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Database.php';
require_once '../classes/Menu.php';
require_once '../classes/Order.php';

// ตรวจสอบสิทธิ์การเข้าถึง
checkAuth(['admin', 'staff']);

$db = new Database();
$menu = new Menu($db->getConnection());
$order = new Order($db->getConnection());

// ดึงข้อมูลเมนูทั้งหมด
$menuItems = $menu->getAllMenuItems();
$categories = $menu->getCategories();

$pageTitle = "สั่งซื้อใหม่";
$activePage = "new-order";
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
            <div class="row">
                
                <!-- Menu Section -->
                <div class="col-lg-8">
                    <div class="pos-card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-utensils"></i> เลือกเมนูอาหาร</h5>
                                
                                <!-- Category Filter -->
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary active" onclick="filterMenu('all')">
                                        ทั้งหมด
                                    </button>
                                    <?php foreach ($categories as $category): ?>
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="filterMenu('<?= $category['code'] ?>')">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body">
                            <!-- Search Bar -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="menuSearch" 
                                           placeholder="ค้นหาเมนู...">
                                </div>
                            </div>
                            
                            <!-- Menu Grid -->
                            <div class="menu-grid" id="menuGrid">
                                <?php foreach ($menuItems as $item): ?>
                                <div class="menu-item" data-category="<?= $item['category_code'] ?>" 
                                     data-name="<?= strtolower($item['name']) ?>"
                                     onclick="addToCart(<?= $item['id'] ?>)">
                                    <div class="menu-image">
                                        <?php if ($item['image']): ?>
                                        <img src="<?= SITE_URL ?>uploads/menu_images/<?= $item['image'] ?>" 
                                             alt="<?= htmlspecialchars($item['name']) ?>"
                                             onerror="this.src='<?= SITE_URL ?>assets/images/no-image.png'">
                                        <?php else: ?>
                                        <img src="<?= SITE_URL ?>assets/images/no-image.png" 
                                             alt="No Image">
                                        <?php endif; ?>
                                    </div>
                                    <div class="menu-info">
                                        <h6><?= htmlspecialchars($item['name']) ?></h6>
                                        <p class="menu-description"><?= htmlspecialchars($item['description']) ?></p>
                                        <div class="menu-price">฿<?= number_format($item['price']) ?></div>
                                        <?php if ($item['is_available'] == 0): ?>
                                        <div class="menu-unavailable">หมด</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cart Section -->
                <div class="col-lg-4">
                    <div class="cart-sidebar">
                        <div class="cart-header">
                            <h5><i class="fas fa-shopping-cart"></i> รายการสั่งซื้อ</h5>
                            <button class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                                <i class="fas fa-trash"></i> ล้าง
                            </button>
                        </div>
                        
                        <div class="cart-body">
                            <!-- Customer Information -->
                            <div class="customer-info">
                                <div class="mb-3">
                                    <label class="form-label">ชื่อลูกค้า <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="customerName" 
                                           placeholder="กรอกชื่อลูกค้า" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">เบอร์โทรศัพท์</label>
                                    <input type="text" class="form-control" id="customerPhone" 
                                           placeholder="กรอกเบอร์โทรศัพท์">
                                </div>
                            </div>

                            <!-- Cart Items -->
                            <div class="cart-items" id="cartItems">
                                <div class="empty-cart">
                                    <i class="fas fa-shopping-cart fa-2x text-muted"></i>
                                    <p class="text-muted mt-2">ยังไม่มีรายการในตะกร้า</p>
                                </div>
                            </div>
                        </div>

                        <!-- Cart Summary -->
                        <div class="cart-footer">
                            <div class="cart-summary">
                                <div class="summary-row">
                                    <span>จำนวนรายการ:</span>
                                    <span id="totalItems">0</span>
                                </div>
                                <div class="summary-row">
                                    <span>ราคารวม:</span>
                                    <span id="totalPrice">฿0</span>
                                </div>
                                <hr>
                                <div class="summary-total">
                                    <strong>ยอดชำระ: <span id="finalPrice">฿0</span></strong>
                                </div>
                            </div>
                            
                            <div class="cart-actions">
                                <button class="btn btn-success btn-lg w-100" id="processOrderBtn" 
                                        onclick="processOrder()" disabled>
                                    <i class="fas fa-check"></i> สั่งซื้อ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= SITE_URL ?>assets/js/jquery.min.js"></script>
    <script src="<?= SITE_URL ?>assets/js/bootstrap.min.js"></script>
    <script src="<?= SITE_URL ?>assets/js/pos.js"></script>

    <script>
        let cart = [];
        let menuItems = <?= json_encode($menuItems) ?>;

        $(document).ready(function() {
            updateDateTime();
            setInterval(updateDateTime, 1000);
            
            // Search functionality
            $('#menuSearch').on('input', function() {
                searchMenu($(this).val());
            });
            
            // Customer name validation
            $('#customerName').on('input', function() {
                validateOrder();
            });
        });

        // Add item to cart
        function addToCart(itemId) {
            const item = menuItems.find(m => m.id == itemId);
            
            if (!item || item.is_available == 0) {
                showToast('ไม่สามารถเพิ่มได้', 'สินค้านี้ไม่พร้อมให้บริการ', 'warning');
                return;
            }
            
            const existingItem = cart.find(c => c.id == itemId);
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: item.id,
                    name: item.name,
                    price: parseFloat(item.price),
                    quantity: 1
                });
            }
            
            updateCartDisplay();
            updateCartSummary();
            validateOrder();
            
            // Show success feedback
            showToast('เพิ่มสำเร็จ', `${item.name} ถูกเพิ่มลงตะกร้าแล้ว`, 'success');
        }

        // Update cart display
        function updateCartDisplay() {
            const cartItems = document.getElementById('cartItems');
            
            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart fa-2x text-muted"></i>
                        <p class="text-muted mt-2">ยังไม่มีรายการในตะกร้า</p>
                    </div>
                `;
                return;
            }
            
            cartItems.innerHTML = cart.map(item => `
                <div class="cart-item">
                    <div class="item-info">
                        <h6>${item.name}</h6>
                        <div class="item-price">฿${item.price} x ${item.quantity}</div>
                    </div>
                    <div class="item-controls">
                        <div class="quantity-controls">
                            <button class="btn btn-sm btn-outline-secondary" 
                                    onclick="updateQuantity(${item.id}, ${item.quantity - 1})">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="quantity">${item.quantity}</span>
                            <button class="btn btn-sm btn-outline-secondary" 
                                    onclick="updateQuantity(${item.id}, ${item.quantity + 1})">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="item-total">฿${(item.price * item.quantity).toFixed(0)}</div>
                    </div>
                </div>
            `).join('');
        }

        // Update item quantity
        function updateQuantity(itemId, newQuantity) {
            if (newQuantity <= 0) {
                cart = cart.filter(item => item.id != itemId);
            } else {
                const item = cart.find(c => c.id == itemId);
                if (item) item.quantity = newQuantity;
            }
            
            updateCartDisplay();
            updateCartSummary();
            validateOrder();
        }

        // Update cart summary
        function updateCartSummary() {
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            document.getElementById('totalItems').textContent = totalItems;
            document.getElementById('totalPrice').textContent = `฿${totalPrice.toFixed(0)}`;
            document.getElementById('finalPrice').textContent = `฿${totalPrice.toFixed(0)}`;
        }

        // Clear cart
        function clearCart() {
            if (confirm('คุณต้องการล้างรายการทั้งหมดหรือไม่?')) {
                cart = [];
                updateCartDisplay();
                updateCartSummary();
                document.getElementById('customerName').value = '';
                document.getElementById('customerPhone').value = '';
                validateOrder();
                showToast('ล้างสำเร็จ', 'ล้างรายการในตะกร้าแล้ว', 'info');
            }
        }

        // Filter menu by category
        function filterMenu(category) {
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Update active button
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }

        // Search menu
        function searchMenu(query) {
            const menuItems = document.querySelectorAll('.menu-item');
            const searchTerm = query.toLowerCase();
            
            menuItems.forEach(item => {
                const itemName = item.dataset.name;
                if (itemName.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Validate order
        function validateOrder() {
            const customerName = document.getElementById('customerName').value.trim();
            const hasItems = cart.length > 0;
            const processBtn = document.getElementById('processOrderBtn');
            
            if (customerName && hasItems) {
                processBtn.disabled = false;
                processBtn.classList.remove('btn-secondary');
                processBtn.classList.add('btn-success');
            } else {
                processBtn.disabled = true;
                processBtn.classList.remove('btn-success');
                processBtn.classList.add('btn-secondary');
            }
        }

        // Process order
        function processOrder() {
            const customerName = document.getElementById('customerName').value.trim();
            const customerPhone = document.getElementById('customerPhone').value.trim();
            
            if (!customerName) {
                showToast('ข้อมูลไม่ครบ', 'กรุณากรอกชื่อลูกค้า', 'warning');
                return;
            }
            
            if (cart.length === 0) {
                showToast('ไม่มีสินค้า', 'กรุณาเลือกสินค้าก่อน', 'warning');
                return;
            }
            
            // Redirect to payment page
            sessionStorage.setItem('orderData', JSON.stringify({
                customer_name: customerName,
                customer_phone: customerPhone,
                cart: cart
            }));
            
            window.location.href = 'payment.php';
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