<?php
// admin/index.php - Dashboard หลักของระบบ
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// ตรวจสอบสิทธิ์การเข้าถึง
checkPermission(['admin', 'pos_staff', 'manager', 'kitchen_staff']);

// ดึงข้อมูลผู้ใช้ปัจจุบัน
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];

// ดึงสถิติวันนี้
$today = date('Y-m-d');
$stats_query = "
    SELECT 
        COUNT(DISTINCT o.id) as total_orders,
        COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN o.id END) as completed_orders,
        COUNT(DISTINCT CASE WHEN o.status = 'pending' THEN o.id END) as pending_orders,
        COUNT(DISTINCT CASE WHEN o.status = 'in_progress' THEN o.id END) as in_progress_orders,
        COUNT(DISTINCT CASE WHEN o.status = 'cancelled' THEN o.id END) as cancelled_orders,
        SUM(CASE WHEN o.payment_status = 'paid' THEN o.total_amount ELSE 0 END) as total_revenue,
        AVG(CASE WHEN o.payment_status = 'paid' THEN o.total_amount END) as avg_order_value,
        COUNT(DISTINCT o.customer_id) as unique_customers
    FROM orders o 
    WHERE DATE(o.created_at) = '$today'
";

$stats_result = mysqli_query($connection, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// ดึงข้อมูลคิวปัจจุบัน
$queue_query = "
    SELECT 
        COUNT(*) as total_queue,
        COUNT(CASE WHEN status = 'waiting' THEN 1 END) as waiting_queue,
        COUNT(CASE WHEN status = 'called' THEN 1 END) as called_queue,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_queue
    FROM queue 
    WHERE DATE(queue_date) = '$today'
";

$queue_result = mysqli_query($connection, $queue_query);
$queue_stats = mysqli_fetch_assoc($queue_result);

// ดึงออเดอร์ล่าสุด
$recent_orders_query = "
    SELECT 
        o.id,
        o.order_number,
        o.customer_name,
        o.total_amount,
        o.status,
        o.payment_status,
        o.created_at,
        q.queue_number
    FROM orders o
    LEFT JOIN queue q ON o.id = q.order_id
    WHERE DATE(o.created_at) = '$today'
    ORDER BY o.created_at DESC
    LIMIT 10
";

$recent_orders = mysqli_query($connection, $recent_orders_query);

// ดึงเมนูขายดีวันนี้
$popular_items_query = "
    SELECT 
        mi.name,
        mi.image,
        SUM(oi.quantity) as total_sold,
        SUM(oi.total_price) as revenue
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE DATE(o.created_at) = '$today'
    AND o.status NOT IN ('cancelled')
    GROUP BY mi.id
    ORDER BY total_sold DESC
    LIMIT 5
";

$popular_items = mysqli_query($connection, $popular_items_query);

// ดึงการตั้งค่าร้าน
$shop_name = get_setting('shop_name', 'ร้านอาหาร');

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $shop_name; ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }
        
        .card:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--bg-color, #007bff) 0%, var(--bg-color-end, #0056b3) 100%);
            color: white;
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        
        .stat-label {
            opacity: 0.9;
            font-size: 0.9rem;
            margin: 0;
        }
        
        .quick-action-btn {
            height: 120px;
            border-radius: 15px;
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .quick-action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.5s ease;
        }
        
        .quick-action-btn:hover::before {
            left: 100%;
        }
        
        .order-status-pending { color: #ffc107; }
        .order-status-in-progress { color: #17a2b8; }
        .order-status-ready { color: #28a745; }
        .order-status-completed { color: #6c757d; }
        .order-status-cancelled { color: #dc3545; }
        
        @media (max-width: 768px) {
            .stat-card .card-body {
                padding: 1rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .stat-icon {
                font-size: 2rem;
            }
            
            .quick-action-btn {
                height: 100px;
            }
        }
    </style>
</head>
<body>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-utensils me-2"></i>
                <?php echo $shop_name; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i>
                            <?php echo $user_name; ?>
                            <span class="badge bg-light text-dark ms-2"><?php echo $user_role; ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($user_role === 'admin'): ?>
                                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>ตั้งค่าระบบ</a></li>
                                <li><a class="dropdown-item" href="users.php"><i class="fas fa-users me-2"></i>จัดการผู้ใช้</a></li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="../pos/"><i class="fas fa-cash-register me-2"></i>ระบบ POS</a></li>
                            <li><a class="dropdown-item" href="../kitchen/"><i class="fas fa-utensils me-2"></i>ระบบครัว</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-1">สวัสดี, <?php echo $user_name; ?>!</h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-calendar me-1"></i>
                    วันที่ <?php echo thai_date($today, 'full'); ?>
                    <span class="ms-3">
                        <i class="fas fa-clock me-1"></i>
                        <span id="current-time"><?php echo date('H:i:s'); ?></span>
                    </span>
                </p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-6 col-md-3 mb-3">
                <div class="card stat-card" style="--bg-color: #007bff; --bg-color-end: #0056b3;">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart stat-icon mb-3"></i>
                        <h3 class="stat-number"><?php echo $stats['total_orders'] ?? 0; ?></h3>
                        <p class="stat-label">ออเดอร์ทั้งหมด</p>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3 mb-3">
                <div class="card stat-card" style="--bg-color: #28a745; --bg-color-end: #1e7e34;">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle stat-icon mb-3"></i>
                        <h3 class="stat-number"><?php echo $stats['completed_orders'] ?? 0; ?></h3>
                        <p class="stat-label">เสร็จสิ้น</p>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3 mb-3">
                <div class="card stat-card" style="--bg-color: #ffc107; --bg-color-end: #e0a800;">
                    <div class="card-body text-center">
                        <i class="fas fa-clock stat-icon mb-3"></i>
                        <h3 class="stat-number"><?php echo $queue_stats['waiting_queue'] ?? 0; ?></h3>
                        <p class="stat-label">คิวรอ</p>
                    </div>
                </div>
            </div>
            
            <div class="col-6 col-md-3 mb-3">
                <div class="card stat-card" style="--bg-color: #17a2b8; --bg-color-end: #138496;">
                    <div class="card-body text-center">
                        <i class="fas fa-money-bill-wave stat-icon mb-3"></i>
                        <h3 class="stat-number">฿<?php echo number_format($stats['total_revenue'] ?? 0); ?></h3>
                        <p class="stat-label">ยอดขายวันนี้</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt text-warning me-2"></i>
                            เมนูด่วน
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            
                            <?php if (in_array($user_role, ['admin', 'pos_staff', 'manager'])): ?>
                            <div class="col-6 col-md-3">
                                <a href="../pos/new_order.php" class="btn quick-action-btn w-100 d-flex flex-column align-items-center justify-content-center" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                    <i class="fas fa-plus-circle fa-2x mb-2"></i>
                                    <span>ออเดอร์ใหม่</span>
                                </a>
                            </div>
                            
                            <div class="col-6 col-md-3">
                                <a href="../pos/order_list.php" class="btn quick-action-btn w-100 d-flex flex-column align-items-center justify-content-center" style="background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%);">
                                    <i class="fas fa-list fa-2x mb-2"></i>
                                    <span>รายการออเดอร์</span>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (in_array($user_role, ['admin', 'kitchen_staff', 'manager'])): ?>
                            <div class="col-6 col-md-3">
                                <a href="../kitchen/" class="btn quick-action-btn w-100 d-flex flex-column align-items-center justify-content-center" style="background: linear-gradient(135deg, #fd7e14 0%, #dc3545 100%);">
                                    <i class="fas fa-utensils fa-2x mb-2"></i>
                                    <span>ระบบครัว</span>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-6 col-md-3">
                                <a href="../pos/queue_display.php" class="btn quick-action-btn w-100 d-flex flex-column align-items-center justify-content-center" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                                    <i class="fas fa-tv fa-2x mb-2"></i>
                                    <span>จอแสดงคิว</span>
                                </a>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Row -->
        <div class="row">
            
            <!-- Recent Orders -->
            <div class="col-lg-7 mb-4">
                <div class="card">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-clock me-2"></i>ออเดอร์ล่าสุด
                        </h5>
                        <a href="../pos/order_list.php" class="btn btn-sm btn-outline-primary">ดูทั้งหมด</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>คิว</th>
                                        <th>ออเดอร์</th>
                                        <th>ลูกค้า</th>
                                        <th>ยอดรวม</th>
                                        <th>สถานะ</th>
                                        <th>เวลา</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                                        <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($order['queue_number']): ?>
                                                        <span class="badge bg-primary"><?php echo sprintf('%03d', $order['queue_number']); ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">#<?php echo $order['order_number']; ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td>฿<?php echo number_format($order['total_amount']); ?></td>
                                                <td>
                                                    <span class="order-status-<?php echo $order['status']; ?>">
                                                        <i class="fas fa-circle fa-sm me-1"></i>
                                                        <?php
                                                        $status_text = [
                                                            'pending' => 'รอดำเนินการ',
                                                            'in_progress' => 'กำลังทำ',
                                                            'ready' => 'พร้อมเสิร์ฟ',
                                                            'completed' => 'เสร็จสิ้น',
                                                            'cancelled' => 'ยกเลิก'
                                                        ];
                                                        echo $status_text[$order['status']] ?? $order['status'];
                                                        ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                ยังไม่มีออเดอร์วันนี้
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Popular Items -->
            <div class="col-lg-5 mb-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-fire me-2"></i>เมนูขายดีวันนี้
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($popular_items) > 0): ?>
                            <?php while ($item = mysqli_fetch_assoc($popular_items)): ?>
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <?php if ($item['image']): ?>
                                            <img src="../<?php echo $item['image']; ?>" 
                                                 class="rounded" width="50" height="50" 
                                                 style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px;">
                                                <i class="fas fa-utensils text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">ขาย: <?php echo $item['total_sold']; ?> รายการ</small>
                                            <small class="text-success fw-bold">฿<?php echo number_format($item['revenue']); ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-chart-bar fa-2x mb-2 d-block"></i>
                                ยังไม่มีข้อมูลการขายวันนี้
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // อัปเดตเวลาทุกวินาที
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
        
        // Auto refresh stats every 30 seconds
        function refreshStats() {
            // ใช้ AJAX เพื่ออัปเดตสถิติ
            fetch('api/get_dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // อัปเดตตัวเลขต่างๆ
                        // Implementation ขึ้นอยู่กับการออกแบบ API
                    }
                })
                .catch(error => {
                    console.log('Stats refresh error:', error);
                });
        }
        
        // รีเฟรชทุก 30 วินาที
        setInterval(refreshStats, 30000);
        
        // Welcome animation
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>

</body>
</html>