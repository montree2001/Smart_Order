<?php
<<<<<<< HEAD
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
=======
// pos/new_order.php - สร้างออเดอร์ใหม่ POS
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// ตรวจสอบสิทธิ์การเข้าถึง POS
checkPermission(['admin', 'pos_staff', 'manager']);

// ดึงหมวดหมู่เมนู
$categories_query = "
    SELECT * FROM menu_categories 
    WHERE is_active = 1 
    ORDER BY sort_order ASC, name ASC
";
$categories = mysqli_query($connection, $categories_query);

// ดึงเมนูอาหารทั้งหมด
$menu_items_query = "
    SELECT mi.*, mc.name as category_name, mc.color_code, mc.icon
    FROM menu_items mi
    JOIN menu_categories mc ON mi.category_id = mc.id
    WHERE mi.is_active = 1 AND mc.is_active = 1
    ORDER BY mc.sort_order ASC, mi.sort_order ASC, mi.name ASC
";
$menu_items = mysqli_query($connection, $menu_items_query);

// จัดกลุ่มเมนูตามหมวดหมู่
$menu_by_category = [];
while ($item = mysqli_fetch_assoc($menu_items)) {
    $menu_by_category[$item['category_id']][] = $item;
}

// ตั้งค่าระบบ
$settings_query = "SELECT setting_key, setting_value FROM system_settings WHERE category IN ('shop', 'general')";
$settings_result = mysqli_query($connection, $settings_query);
$settings = [];
while ($setting = mysqli_fetch_assoc($settings_result)) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

$tax_rate = floatval($settings['tax_rate'] ?? 7);
$service_charge_rate = floatval($settings['service_charge_rate'] ?? 0);

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
=======
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>สร้างออเดอร์ใหม่ - POS System</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/pos.css" rel="stylesheet">
    
    <style>
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            padding: 20px;
        }
        
        .menu-item-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
            border: none;
        }
        
        .menu-item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .menu-item-card.selected {
            border: 3px solid #007bff;
            transform: scale(1.05);
        }
        
        .menu-item-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
        }
        
        .menu-item-info {
            padding: 15px;
        }
        
        .menu-item-name {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 5px;
            color: #333;
        }
        
        .menu-item-price {
            color: #007bff;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .category-tab {
            border: none;
            border-radius: 25px;
            padding: 10px 20px;
            margin: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .category-tab.active {
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .cart-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
        }
        
        .cart-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
        }
        
        .cart-items {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-summary {
            padding: 20px;
            border-top: 2px solid #f8f9fa;
            background: #f8f9fa;
            border-radius: 0 0 15px 15px;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            border: 1px solid #dee2e6;
            border-radius: 25px;
            overflow: hidden;
        }
        
        .quantity-controls button {
            border: none;
            background: #f8f9fa;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .quantity-controls button:hover {
            background: #007bff;
            color: white;
        }
        
        .quantity-controls input {
            border: none;
            width: 50px;
            text-align: center;
            background: white;
            font-weight: 600;
        }
        
        @media (max-width: 991.98px) {
            .menu-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 10px;
                padding: 15px;
            }
            
            .menu-item-image {
                height: 100px;
            }
            
            .menu-item-info {
                padding: 10px;
            }
            
            .cart-panel {
                height: auto;
                margin-top: 20px;
            }
        }
        
        @media (max-width: 767.98px) {
            .menu-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .category-tab {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body class="pos-body">
    
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg pos-navbar fixed-top">
        <div class="container-fluid">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-arrow-left me-2"></i>
                <span class="fw-bold">สร้างออเดอร์ใหม่</span>
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
        <div class="row">
            
            <!-- Menu Panel -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    
                    <!-- Category Tabs -->
                    <div class="card-header bg-light">
                        <div class="d-flex flex-wrap justify-content-center">
                            <button class="category-tab btn btn-outline-primary active" 
                                    data-category="all" 
                                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <i class="fas fa-th-large me-2"></i>ทั้งหมด
                            </button>
                            
                            <?php mysqli_data_seek($categories, 0); ?>
                            <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                                <button class="category-tab btn btn-outline-primary" 
                                        data-category="<?php echo $category['id']; ?>"
                                        style="--bs-btn-color: <?php echo $category['color_code']; ?>; --bs-btn-border-color: <?php echo $category['color_code']; ?>; --bs-btn-hover-bg: <?php echo $category['color_code']; ?>; --bs-btn-hover-border-color: <?php echo $category['color_code']; ?>;">
                                    <i class="<?php echo $category['icon']; ?> me-2"></i>
                                    <?php echo $category['name']; ?>
                                </button>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    
                    <!-- Menu Items -->
                    <div class="card-body p-0" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                        
                        <!-- All Items -->
                        <div class="menu-category-content" data-category="all">
                            <div class="menu-grid">
                                <?php foreach ($menu_by_category as $cat_id => $items): ?>
                                    <?php foreach ($items as $item): ?>
                                        <div class="menu-item-card" 
                                             data-item-id="<?php echo $item['id']; ?>"
                                             data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                                             data-item-price="<?php echo $item['price']; ?>"
                                             data-item-image="<?php echo $item['image']; ?>">
                                            
                                            <?php if ($item['image']): ?>
                                                <img src="../<?php echo $item['image']; ?>" 
                                                     class="menu-item-image" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php else: ?>
                                                <div class="menu-item-image d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-utensils fa-2x text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="menu-item-info text-center">
                                                <div class="menu-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <div class="menu-item-price">฿<?php echo number_format($item['price']); ?></div>
                                                
                                                <?php if ($item['description']): ?>
                                                    <small class="text-muted d-block mt-1">
                                                        <?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>
                                                        <?php if (strlen($item['description']) > 50): ?>...<?php endif; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Category-specific content -->
                        <?php foreach ($menu_by_category as $cat_id => $items): ?>
                            <div class="menu-category-content" data-category="<?php echo $cat_id; ?>" style="display: none;">
                                <div class="menu-grid">
                                    <?php foreach ($items as $item): ?>
                                        <div class="menu-item-card" 
                                             data-item-id="<?php echo $item['id']; ?>"
                                             data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                                             data-item-price="<?php echo $item['price']; ?>"
                                             data-item-image="<?php echo $item['image']; ?>">
                                            
                                            <?php if ($item['image']): ?>
                                                <img src="../<?php echo $item['image']; ?>" 
                                                     class="menu-item-image" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php else: ?>
                                                <div class="menu-item-image d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-utensils fa-2x text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="menu-item-info text-center">
                                                <div class="menu-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                                <div class="menu-item-price">฿<?php echo number_format($item['price']); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                    </div>
                </div>
            </div>

            <!-- Cart Panel -->
            <div class="col-lg-4">
                <div class="cart-panel">
                    
                    <!-- Cart Header -->
                    <div class="cart-header">
                        <h4 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>
                            ตะกร้าสินค้า
                        </h4>
                        <small class="opacity-75">รายการที่เลือก: <span id="cart-count">0</span></small>
                    </div>
                    
                    <!-- Cart Items -->
                    <div class="cart-items" id="cart-items">
                        <div class="text-center text-muted py-5" id="empty-cart">
                            <i class="fas fa-shopping-cart fa-3x mb-3 opacity-50"></i>
                            <p class="mb-0">ยังไม่มีรายการในตะกร้า</p>
                            <small>กดเลือกเมนูที่ต้องการ</small>
                        </div>
                    </div>
                    
                    <!-- Customer Info -->
                    <div class="px-3 mb-3">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-user me-2"></i>ชื่อลูกค้า
                                </label>
                                <input type="text" class="form-control" id="customer-name" 
                                       placeholder="ชื่อลูกค้า (ไม่บังคับ)">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-phone me-2"></i>เบอร์โทร
                                </label>
                                <input type="text" class="form-control" id="customer-phone" 
                                       placeholder="เบอร์โทรศัพท์ (ไม่บังคับ)">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Type -->
                    <div class="px-3 mb-3">
                        <label class="form-label fw-semibold">
                            <i class="fas fa-utensils me-2"></i>ประเภทออเดอร์
                        </label>
                        <select class="form-select" id="order-type">
                            <option value="dine_in">ทานที่ร้าน</option>
                            <option value="takeaway">ซื้อกลับ</option>
                            <option value="delivery">เดลิเวอรี่</option>
                        </select>
                    </div>
                    
                    <!-- Cart Summary -->
                    <div class="cart-summary">
                        <div class="d-flex justify-content-between mb-2">
                            <span>ราคารวม:</span>
                            <span id="subtotal">฿0</span>
                        </div>
                        
                        <?php if ($service_charge_rate > 0): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>ค่าบริการ (<?php echo $service_charge_rate; ?>%):</span>
                            <span id="service-charge">฿0</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>ภาษี (<?php echo $tax_rate; ?>%):</span>
                            <span id="tax-amount">฿0</span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>ยอดรวมสุทธิ:</strong>
                            <strong id="total-amount" class="text-primary fs-5">฿0</strong>
                        </div>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-outline-secondary w-100" onclick="clearCart()">
                                    <i class="fas fa-trash me-2"></i>ล้าง
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-success w-100" id="checkout-btn" onclick="proceedToPayment()" disabled>
                                    <i class="fas fa-credit-card me-2"></i>ชำระเงิน
>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<<<<<<< HEAD
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
=======
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery.min.js"></script>
    
    <script>
        // Global variables
        let cart = [];
        const taxRate = <?php echo $tax_rate; ?>;
        const serviceChargeRate = <?php echo $service_charge_rate; ?>;
        
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
        
        // Category tab switching
        $(document).on('click', '.category-tab', function() {
            const categoryId = $(this).data('category');
            
            // อัปเดตปุ่ม active
            $('.category-tab').removeClass('active').css('color', '').css('background', '');
            $(this).addClass('active').css('color', 'white').css('background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)');
            
            // แสดง/ซ่อน content
            $('.menu-category-content').hide();
            $(`.menu-category-content[data-category="${categoryId}"]`).show();
        });
        
        // เพิ่มสินค้าลงตะกร้า
        $(document).on('click', '.menu-item-card', function() {
            const itemId = parseInt($(this).data('item-id'));
            const itemName = $(this).data('item-name');
            const itemPrice = parseFloat($(this).data('item-price'));
            const itemImage = $(this).data('item-image');
            
            // เพิ่มเอฟเฟกต์การเลือก
            $(this).addClass('selected');
            setTimeout(() => {
                $(this).removeClass('selected');
            }, 300);
            
            // ตรวจสอบว่ามีสินค้าอยู่ในตะกร้าแล้วหรือไม่
            const existingItem = cart.find(item => item.id === itemId);
>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
            
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
<<<<<<< HEAD
                    id: item.id,
                    name: item.name,
                    price: parseFloat(item.price),
=======
                    id: itemId,
                    name: itemName,
                    price: itemPrice,
                    image: itemImage,
>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
                    quantity: 1
                });
            }
            
            updateCartDisplay();
<<<<<<< HEAD
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
=======
        });
        
        // อัปเดตการแสดงผลตะกร้า
        function updateCartDisplay() {
            const cartItemsContainer = document.getElementById('cart-items');
            const emptyCart = document.getElementById('empty-cart');
            
            if (cart.length === 0) {
                cartItemsContainer.innerHTML = `
                    <div class="text-center text-muted py-5" id="empty-cart">
                        <i class="fas fa-shopping-cart fa-3x mb-3 opacity-50"></i>
                        <p class="mb-0">ยังไม่มีรายการในตะกร้า</p>
                        <small>กดเลือกเมนูที่ต้องการ</small>
                    </div>
                `;
            } else {
                let cartHTML = '';
                cart.forEach(item => {
                    cartHTML += `
                        <div class="cart-item">
                            <div class="flex-shrink-0">
                                ${item.image ? 
                                    `<img src="../${item.image}" width="50" height="50" class="rounded" style="object-fit: cover;">` :
                                    `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-utensils text-muted"></i>
                                     </div>`
                                }
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">${item.name}</h6>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="quantity-controls">
                                        <button type="button" onclick="decreaseQuantity(${item.id})">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" value="${item.quantity}" min="1" readonly>
                                        <button type="button" onclick="increaseQuantity(${item.id})">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold">฿${(item.price * item.quantity).toLocaleString()}</div>
                                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.id})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                cartItemsContainer.innerHTML = cartHTML;
            }
            
            updateCartSummary();
        }
        
        // เพิ่มจำนวนสินค้า
        function increaseQuantity(itemId) {
            const item = cart.find(item => item.id === itemId);
            if (item) {
                item.quantity += 1;
                updateCartDisplay();
            }
        }
        
        // ลดจำนวนสินค้า
        function decreaseQuantity(itemId) {
            const item = cart.find(item => item.id === itemId);
            if (item && item.quantity > 1) {
                item.quantity -= 1;
                updateCartDisplay();
            }
        }
        
        // ลบสินค้าออกจากตะกร้า
        function removeFromCart(itemId) {
            cart = cart.filter(item => item.id !== itemId);
            updateCartDisplay();
        }
        
        // ล้างตะกร้าทั้งหมด
        function clearCart() {
            if (cart.length > 0 && confirm('ต้องการล้างรายการในตะกร้าทั้งหมด?')) {
                cart = [];
                updateCartDisplay();
            }
        }
        
        // อัปเดตสรุปราคา
        function updateCartSummary() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const serviceCharge = (subtotal * serviceChargeRate) / 100;
            const taxAmount = ((subtotal + serviceCharge) * taxRate) / 100;
            const total = subtotal + serviceCharge + taxAmount;
            
            document.getElementById('cart-count').textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('subtotal').textContent = '฿' + subtotal.toLocaleString();
            
            if (serviceChargeRate > 0) {
                document.getElementById('service-charge').textContent = '฿' + serviceCharge.toLocaleString();
            }
            
            document.getElementById('tax-amount').textContent = '฿' + taxAmount.toLocaleString();
            document.getElementById('total-amount').textContent = '฿' + total.toLocaleString();
            
            // เปิด/ปิดปุ่มชำระเงิน
            const checkoutBtn = document.getElementById('checkout-btn');
            if (cart.length > 0) {
                checkoutBtn.disabled = false;
                checkoutBtn.classList.remove('btn-secondary');
                checkoutBtn.classList.add('btn-success');
            } else {
                checkoutBtn.disabled = true;
                checkoutBtn.classList.remove('btn-success');
                checkoutBtn.classList.add('btn-secondary');
            }
        }
        
        // ไปยังหน้าชำระเงิน
        function proceedToPayment() {
            if (cart.length === 0) {
                alert('กรุณาเลือกสินค้าก่อนทำการชำระเงิน');
                return;
            }
            
            const customerName = document.getElementById('customer-name').value.trim();
            const customerPhone = document.getElementById('customer-phone').value.trim();
            const orderType = document.getElementById('order-type').value;
            
            // ส่งข้อมูลไปยังหน้าชำระเงิน
            const orderData = {
                items: cart,
                customer_name: customerName || 'ลูกค้า Walk-in',
                customer_phone: customerPhone,
                order_type: orderType,
                subtotal: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
                service_charge: (cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) * serviceChargeRate) / 100,
                tax_amount: ((cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) + (cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) * serviceChargeRate) / 100) * taxRate) / 100,
                total_amount: cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) + ((cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) * serviceChargeRate) / 100) + (((cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) + (cart.reduce((sum, item) => sum + (item.price * item.quantity), 0) * serviceChargeRate) / 100) * taxRate) / 100)
            };
            
            // เก็บข้อมูลใน sessionStorage
            sessionStorage.setItem('pos_order_data', JSON.stringify(orderData));
            
            // ไปยังหน้าชำระเงิน
            window.location.href = 'payment.php';
        }
        
        // Initialize
        $(document).ready(function() {
            updateCartDisplay();
        });
>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
    </script>

</body>
</html>