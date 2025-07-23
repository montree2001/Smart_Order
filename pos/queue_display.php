<?php
// pos/queue_display.php - จอแสดงคิวขนาดใหญ่
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// ตรวจสอบสิทธิ์การเข้าถึง POS
checkPermission(['admin', 'pos_staff', 'manager']);

// ดึงการตั้งค่าร้าน
$shop_settings_query = "
    SELECT setting_key, setting_value 
    FROM system_settings 
    WHERE category = 'shop'
";
$shop_settings_result = mysqli_query($connection, $shop_settings_query);
$shop_settings = [];
while ($setting = mysqli_fetch_assoc($shop_settings_result)) {
    $shop_settings[$setting['setting_key']] = $setting['setting_value'];
}

$shop_name = $shop_settings['shop_name'] ?? 'ร้านอาหาร';

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จอแสดงคิว - <?php echo $shop_name; ?></title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            overflow: hidden;
            color: white;
        }
        
        .queue-display-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header */
        .queue-header {
            background: rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            padding: 20px 40px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
            flex-shrink: 0;
        }
        
        .shop-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
            margin-bottom: 10px;
        }
        
        .current-time {
            text-align: center;
            font-size: 1.3rem;
            opacity: 0.9;
        }
        
        /* Main Content */
        .queue-main {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        /* Current Queue Section */
        .current-queue-section {
            flex-shrink: 0;
            margin-bottom: 40px;
        }
        
        .current-queue-title {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .current-queue-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(15px);
            border-radius: 25px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .current-queue-number {
            font-size: 6rem;
            font-weight: 900;
            color: #ffd700;
            text-shadow: 0 4px 20px rgba(255,215,0,0.5);
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .current-queue-status {
            font-size: 1.5rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .current-queue-customer {
            font-size: 1.2rem;
            opacity: 0.8;
        }
        
        /* Waiting Queue Section */
        .waiting-queue-section {
            flex: 1;
            min-height: 0;
        }
        
        .waiting-queue-title {
            font-size: 1.8rem;
            text-align: center;
            margin-bottom: 20px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .waiting-queue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .waiting-queue-item {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .waiting-queue-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .waiting-queue-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #e3f2fd;
            margin-bottom: 10px;
        }
        
        .waiting-queue-info {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-waiting {
            background: rgba(255,193,7,0.3);
            color: #ffd700;
            border: 1px solid rgba(255,193,7,0.5);
        }
        
        .status-called {
            background: rgba(0,123,255,0.3);
            color: #64b5f6;
            border: 1px solid rgba(0,123,255,0.5);
            animation: blink 1s infinite;
        }
        
        .status-in-progress {
            background: rgba(76,175,80,0.3);
            color: #81c784;
            border: 1px solid rgba(76,175,80,0.5);
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.5; }
        }
        
        /* Empty State */
        .empty-queue {
            text-align: center;
            padding: 60px 20px;
            opacity: 0.7;
        }
        
        .empty-queue i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-queue h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        
        /* Scrollbar Styling */
        .waiting-queue-grid::-webkit-scrollbar {
            width: 8px;
        }
        
        .waiting-queue-grid::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
        }
        
        .waiting-queue-grid::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 4px;
        }
        
        .waiting-queue-grid::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .queue-header {
                padding: 15px 20px;
            }
            
            .shop-title {
                font-size: 1.8rem;
            }
            
            .current-time {
                font-size: 1rem;
            }
            
            .queue-main {
                padding: 20px;
            }
            
            .current-queue-number {
                font-size: 4rem;
            }
            
            .current-queue-title {
                font-size: 1.5rem;
            }
            
            .waiting-queue-title {
                font-size: 1.3rem;
            }
            
            .waiting-queue-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
            }
            
            .waiting-queue-number {
                font-size: 2rem;
            }
        }
        
        /* Animation for new queue */
        .queue-item-enter {
            animation: slideInUp 0.5s ease;
        }
        
        @keyframes slideInUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Controls */
        .queue-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .control-btn {
            background: rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            margin: 5px;
            transition: all 0.3s ease;
        }
        
        .control-btn:hover {
            background: rgba(0,0,0,0.7);
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    
    <div class="queue-display-container">
        
        <!-- Controls -->
        <div class="queue-controls">
            <button class="btn control-btn" onclick="toggleFullscreen()">
                <i class="fas fa-expand-alt"></i>
            </button>
            <button class="btn control-btn" onclick="refreshQueue()">
                <i class="fas fa-sync-alt" id="refresh-icon"></i>
            </button>
            <a href="index.php" class="btn control-btn">
                <i class="fas fa-home"></i>
            </a>
        </div>
        
        <!-- Header -->
        <div class="queue-header">
            <h1 class="shop-title">
                <i class="fas fa-utensils me-3"></i>
                <?php echo $shop_name; ?>
            </h1>
            <div class="current-time">
                <i class="fas fa-clock me-2"></i>
                <span id="current-time"><?php echo date('H:i:s'); ?></span>
                <span class="ms-3">
                    <i class="fas fa-calendar me-2"></i>
                    <?php echo date('d/m/Y'); ?>
                </span>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="queue-main">
            
            <!-- Current Queue -->
            <div class="current-queue-section">
                <h2 class="current-queue-title">
                    <i class="fas fa-megaphone me-3"></i>
                    คิวปัจจุบัน
                </h2>
                
                <div class="current-queue-card" id="current-queue-card">
                    <div id="current-queue-content">
                        <!-- จะถูกเติมด้วย JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Waiting Queue -->
            <div class="waiting-queue-section">
                <h2 class="waiting-queue-title">
                    <i class="fas fa-clock me-3"></i>
                    คิวที่รอ (<span id="waiting-count">0</span>)
                </h2>
                
                <div class="waiting-queue-grid" id="waiting-queue-grid">
                    <!-- จะถูกเติมด้วย JavaScript -->
                </div>
            </div>
            
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/jquery.min.js"></script>
    
    <script>
        let currentQueueData = [];
        let lastUpdateTime = 0;
        
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
        
        // อัปเดตทุกวินาที
        setInterval(updateTime, 1000);
        
        // รีเฟรชข้อมูลคิว
        function refreshQueue() {
            const refreshIcon = document.getElementById('refresh-icon');
            refreshIcon.classList.add('fa-spin');
            
            fetch('api/queue_management.php?action=get_current_queue')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateQueueDisplay(data.queues);
                        lastUpdateTime = Date.now();
                    } else {
                        console.error('Error loading queue:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                })
                .finally(() => {
                    setTimeout(() => {
                        refreshIcon.classList.remove('fa-spin');
                    }, 500);
                });
        }
        
        // อัปเดตการแสดงผลคิว
        function updateQueueDisplay(queues) {
            const currentQueueContent = document.getElementById('current-queue-content');
            const waitingQueueGrid = document.getElementById('waiting-queue-grid');
            const waitingCount = document.getElementById('waiting-count');
            
            // แยกคิวปัจจุบันและคิวที่รอ
            const calledQueue = queues.find(q => q.status === 'called');
            const inProgressQueue = queues.find(q => q.status === 'in_progress');
            const waitingQueues = queues.filter(q => q.status === 'waiting');
            
            // คิวปัจจุบัน (ที่กำลังเรียกหรือกำลังทำ)
            const currentQueue = inProgressQueue || calledQueue;
            
            if (currentQueue) {
                const statusText = currentQueue.status === 'called' ? 'กรุณามารับ' : 'กำลังเตรียม';
                const statusClass = currentQueue.status === 'called' ? 'status-called' : 'status-in-progress';
                
                currentQueueContent.innerHTML = `
                    <div class="current-queue-number">
                        ${String(currentQueue.queue_number).padStart(3, '0')}
                    </div>
                    <div class="current-queue-status">
                        <span class="status-badge ${statusClass}">
                            ${statusText}
                        </span>
                    </div>
                    <div class="current-queue-customer">
                        <i class="fas fa-user me-2"></i>
                        ${currentQueue.customer_name}
                    </div>
                `;
                
                // เล่นเสียงถ้าเป็นคิวใหม่ที่ถูกเรียก
                if (currentQueue.status === 'called' && 
                    !currentQueueData.find(q => q.queue_number === currentQueue.queue_number && q.status === 'called')) {
                    playQueueCallSound(currentQueue.queue_number, currentQueue.customer_name);
                }
            } else {
                currentQueueContent.innerHTML = `
                    <div style="padding: 40px;">
                        <i class="fas fa-pause-circle fa-4x mb-4 opacity-50"></i>
                        <h3>ไม่มีคิวที่กำลังเรียก</h3>
                        <p class="opacity-75">รอการเรียกคิวถัดไป</p>
                    </div>
                `;
            }
            
            // คิวที่รอ
            waitingCount.textContent = waitingQueues.length;
            
            if (waitingQueues.length === 0) {
                waitingQueueGrid.innerHTML = `
                    <div class="empty-queue" style="grid-column: 1 / -1;">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>ไม่มีคิวที่รอ</h3>
                        <p>ทุกคิวได้รับการดำเนินการแล้ว</p>
                    </div>
                `;
            } else {
                let waitingHTML = '';
                waitingQueues.forEach((queue, index) => {
                    const isNew = !currentQueueData.find(q => q.queue_number === queue.queue_number);
                    const animationClass = isNew ? 'queue-item-enter' : '';
                    
                    waitingHTML += `
                        <div class="waiting-queue-item ${animationClass}">
                            <div class="waiting-queue-number">
                                ${String(queue.queue_number).padStart(3, '0')}
                            </div>
                            <div class="waiting-queue-info">
                                <div class="mb-1">
                                    <i class="fas fa-user me-1"></i>
                                    ${queue.customer_name}
                                </div>
                                <div class="mb-1">
                                    <i class="fas fa-shopping-bag me-1"></i>
                                    ${queue.item_count} รายการ
                                </div>
                                <div>
                                    <i class="fas fa-clock me-1"></i>
                                    ${queue.estimated_time || 'กำลังคำนวณ'}
                                </div>
                            </div>
                        </div>
                    `;
                });
                waitingQueueGrid.innerHTML = waitingHTML;
            }
            
            // เก็บข้อมูลปัจจุบันสำหรับเปรียบเทียบ
            currentQueueData = queues;
        }
        
        // เล่นเสียงเรียกคิว
        function playQueueCallSound(queueNumber, customerName) {
            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(
                    `เรียกคิวหมายเลข ${queueNumber} คุณ ${customerName} กรุณามารับออเดอร์ที่เคาน์เตอร์ค่ะ`
                );
                utterance.lang = 'th-TH';
                utterance.rate = 0.8;
                utterance.pitch = 1.0;
                utterance.volume = 0.8;
                
                // เล่นเสียงหลังจาก delay เล็กน้อย
                setTimeout(() => {
                    speechSynthesis.speak(utterance);
                }, 1000);
            }
        }
        
        // Toggle Fullscreen
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.error('Error attempting to enable fullscreen:', err);
                });
            } else {
                document.exitFullscreen();
            }
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            switch(e.key) {
                case 'F11':
                    e.preventDefault();
                    toggleFullscreen();
                    break;
                case 'F5':
                case 'r':
                    if (e.ctrlKey) {
                        e.preventDefault();
                        refreshQueue();
                    }
                    break;
                case 'Escape':
                    if (document.fullscreenElement) {
                        document.exitFullscreen();
                    }
                    break;
            }
        });
        
        // Auto refresh every 5 seconds
        setInterval(refreshQueue, 5000);
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            refreshQueue();
            
            // Show loading animation initially
            document.getElementById('current-queue-content').innerHTML = `
                <div style="padding: 40px;">
                    <div class="spinner-border text-light mb-3" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <h3>กำลังโหลดข้อมูลคิว</h3>
                </div>
            `;
        });
        
        // Prevent context menu
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
        
        // Prevent text selection
        document.addEventListener('selectstart', function(e) {
            e.preventDefault();
        });
    </script>

</body>
</html>