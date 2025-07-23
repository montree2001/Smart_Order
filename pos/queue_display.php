<?php
<<<<<<< HEAD
session_start();
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../classes/Database.php';
require_once '../classes/Queue.php';
require_once '../classes/Order.php';
require_once '../classes/VoiceSystem.php';

// ตรวจสอบสิทธิ์การเข้าถึง
checkAuth(['admin', 'staff']);

$db = new Database();
$queue = new Queue($db->getConnection());
$order = new Order($db->getConnection());
$voice = new VoiceSystem();

// Handle queue actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'call_queue':
                $result = callQueue($_POST['queue_id'], $queue, $voice);
                echo json_encode($result);
                break;
                
            case 'next_queue':
                $result = nextQueue($queue);
                echo json_encode($result);
                break;
                
            case 'skip_queue':
                $result = skipQueue($_POST['queue_id'], $queue);
                echo json_encode($result);
                break;
                
            case 'update_queue_status':
                $result = updateQueueStatus($_POST['queue_id'], $_POST['status'], $queue);
                echo json_encode($result);
                break;
                
            case 'get_queue_data':
                $result = getQueueData($queue);
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

// Get current queue data
$currentQueue = $queue->getCurrentQueue();
$waitingQueues = $queue->getWaitingQueues();
$readyQueues = $queue->getReadyQueues();
$callingQueues = $queue->getCallingQueues();

$pageTitle = "จัดการคิว";
$activePage = "queue";
=======
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

>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title><?= $pageTitle ?> - Smart Order Management</title>
    
    <!-- CSS -->
    <link href="<?= SITE_URL ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>assets/css/pos.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .queue-display-main {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
=======
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
>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
        }
        
        .current-queue-number {
            font-size: 6rem;
            font-weight: 900;
<<<<<<< HEAD
            text-shadow: 3px 3px 6px rgba(0,0,0,0.3);
            margin: 20px 0;
        }
        
        .queue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .queue-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .queue-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .queue-card.waiting {
            border-color: #f59e0b;
            background: linear-gradient(135deg, #fef3c7, #fde68a);
        }
        
        .queue-card.ready {
            border-color: #10b981;
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
        }
        
        .queue-card.calling {
            border-color: #ef4444;
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            animation: pulse-glow 1.5s infinite;
        }
        
        .queue-card.completed {
            border-color: #6b7280;
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            opacity: 0.7;
        }
        
        @keyframes pulse-glow {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }
        }
        
        .queue-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .queue-info {
            font-size: 0.9rem;
            color: #666;
        }
        
        .voice-indicator {
            display: inline-block;
            width: 15px;
            height: 15px;
            background: #10b981;
            border-radius: 50%;
            margin-left: 10px;
            animation: voice-pulse 1.5s infinite;
        }
        
        @keyframes voice-pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.3); }
        }
        
        .queue-controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .btn-queue {
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 15px;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-queue:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255,255,255,0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.3s ease;
        }
        
        .btn-queue:hover:before {
            width: 300px;
            height: 300px;
        }
        
        .queue-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .section-header {
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 0;
            color: #1e293b;
        }
        
        .queue-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
=======
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
            
>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
            .current-queue-number {
                font-size: 4rem;
            }
            
<<<<<<< HEAD
            .queue-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 10px;
            }
            
            .queue-controls {
                gap: 10px;
            }
            
            .btn-queue {
                padding: 12px 20px;
                font-size: 1rem;
            }
            
            .queue-display-main {
                padding: 25px;
            }
        }
    </style>
</head>

<body class="pos-body">
    <div class="pos-container">
        
        <!-- Header -->
        <?php include 'includes/pos_header.php'; ?>

        <!-- Navigation -->
        <?php include 'includes/pos_nav.php'; ?>

        <!-- Main Content -->
        <div class="pos-content">
            
            <!-- Current Queue Display -->
            <div class="queue-display-main">
                <h2><i class="fas fa-bullhorn"></i> คิวที่กำลังเรียก</h2>
                <div class="current-queue-number" id="currentQueueNumber">
                    <?= $currentQueue['queue_number'] ?? 'N/A' ?>
                </div>
                <div class="queue-customer-info">
                    <?php if ($currentQueue): ?>
                    <h5><?= htmlspecialchars($currentQueue['customer_name']) ?></h5>
                    <p><?= htmlspecialchars($currentQueue['items_summary']) ?></p>
                    <small>เวลาสั่ง: <?= date('H:i', strtotime($currentQueue['created_at'])) ?></small>
                    <?php else: ?>
                    <p>ไม่มีคิวที่กำลังเรียก</p>
                    <?php endif; ?>
                </div>
                
                <div class="queue-controls">
                    <button class="btn btn-light btn-queue" onclick="callCurrentQueue()" 
                            <?= !$currentQueue ? 'disabled' : '' ?>>
                        <i class="fas fa-volume-up"></i> 
                        เรียกคิว
                        <span class="voice-indicator" id="voiceIndicator" style="display: none;"></span>
                    </button>
                    
                    <button class="btn btn-success btn-queue" onclick="markAsServed()" 
                            <?= !$currentQueue ? 'disabled' : '' ?>>
                        <i class="fas fa-check"></i> 
                        บริการแล้ว
                    </button>
                    
                    <button class="btn btn-warning btn-queue" onclick="skipCurrentQueue()" 
                            <?= !$currentQueue ? 'disabled' : '' ?>>
                        <i class="fas fa-step-forward"></i> 
                        ข้ามคิว
                    </button>
                    
                    <button class="btn btn-info btn-queue" onclick="nextQueue()">
                        <i class="fas fa-forward"></i> 
                        คิวถัดไป
                    </button>
                </div>
            </div>

            <!-- Queue Statistics -->
            <div class="queue-stats">
                <div class="stat-box">
                    <div class="stat-number" id="waitingCount"><?= count($waitingQueues) ?></div>
                    <div class="stat-label">คิวที่รอ</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" id="readyCount"><?= count($readyQueues) ?></div>
                    <div class="stat-label">คิวที่พร้อม</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" id="callingCount"><?= count($callingQueues) ?></div>
                    <div class="stat-label">กำลังเรียก</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number" id="avgWaitTime">12</div>
                    <div class="stat-label">เวลารอเฉลี่ย (นาที)</div>
                </div>
            </div>

            <div class="row">
                <!-- Waiting Queues -->
                <div class="col-lg-4">
                    <div class="queue-section">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="fas fa-clock text-warning"></i> 
                                คิวที่รอ (<?= count($waitingQueues) ?>)
                            </h5>
                        </div>
                        <div class="queue-grid" id="waitingQueueGrid">
                            <?php foreach ($waitingQueues as $queueItem): ?>
                            <div class="queue-card waiting" data-queue-id="<?= $queueItem['id'] ?>">
                                <div class="queue-number"><?= htmlspecialchars($queueItem['queue_number']) ?></div>
                                <div class="queue-info">
                                    <strong><?= htmlspecialchars($queueItem['customer_name']) ?></strong><br>
                                    <small><?= substr($queueItem['items_summary'], 0, 20) ?>...</small><br>
                                    <small>รอ <?= getWaitTime($queueItem['created_at']) ?> นาที</small>
                                </div>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="setAsCurrentQueue(<?= $queueItem['id'] ?>)">
                                        เรียก
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($waitingQueues)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <p>ไม่มีคิวที่รอ</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Ready Queues -->
                <div class="col-lg-4">
                    <div class="queue-section">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="fas fa-check text-success"></i> 
                                คิวที่พร้อม (<?= count($readyQueues) ?>)
                            </h5>
                        </div>
                        <div class="queue-grid" id="readyQueueGrid">
                            <?php foreach ($readyQueues as $queueItem): ?>
                            <div class="queue-card ready" data-queue-id="<?= $queueItem['id'] ?>">
                                <div class="queue-number"><?= htmlspecialchars($queueItem['queue_number']) ?></div>
                                <div class="queue-info">
                                    <strong><?= htmlspecialchars($queueItem['customer_name']) ?></strong><br>
                                    <small><?= substr($queueItem['items_summary'], 0, 20) ?>...</small><br>
                                    <small class="text-success">พร้อมเสิร์ฟ</small>
                                </div>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-success" 
                                            onclick="setAsCurrentQueue(<?= $queueItem['id'] ?>)">
                                        เรียก
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($readyQueues)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-check fa-2x mb-2"></i>
                                <p>ไม่มีคิวที่พร้อม</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Calling/Processing Queues -->
                <div class="col-lg-4">
                    <div class="queue-section">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="fas fa-bullhorn text-danger"></i> 
                                กำลังเรียก/ดำเนินการ (<?= count($callingQueues) ?>)
                            </h5>
                        </div>
                        <div class="queue-grid" id="callingQueueGrid">
                            <?php foreach ($callingQueues as $queueItem): ?>
                            <div class="queue-card calling" data-queue-id="<?= $queueItem['id'] ?>">
                                <div class="queue-number"><?= htmlspecialchars($queueItem['queue_number']) ?></div>
                                <div class="queue-info">
                                    <strong><?= htmlspecialchars($queueItem['customer_name']) ?></strong><br>
                                    <small><?= substr($queueItem['items_summary'], 0, 20) ?>...</small><br>
                                    <small class="text-danger">กำลังเรียก</small>
                                </div>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-success" 
                                            onclick="markQueueAsServed(<?= $queueItem['id'] ?>)">
                                        เสร็จ
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($callingQueues)): ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-bullhorn fa-2x mb-2"></i>
                                <p>ไม่มีคิวที่กำลังเรียก</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="queue-section">
                        <div class="section-header">
                            <h5 class="section-title">
                                <i class="fas fa-bolt"></i> 
                                การจัดการด่วน
                            </h5>
                        </div>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <button class="btn btn-primary btn-lg" onclick="refreshQueueDisplay()">
                                <i class="fas fa-sync-alt"></i> รีเฟรชข้อมูล
                            </button>
                            <button class="btn btn-info btn-lg" onclick="showQueueSettings()">
                                <i class="fas fa-cog"></i> ตั้งค่าคิว
                            </button>
                            <button class="btn btn-success btn-lg" onclick="testVoiceSystem()">
                                <i class="fas fa-volume-up"></i> ทดสอบเสียง
                            </button>
                            <a href="new_order.php" class="btn btn-warning btn-lg">
                                <i class="fas fa-plus-circle"></i> สั่งซื้อใหม่
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Queue Settings Modal -->
    <div class="modal fade" id="queueSettingsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-cog"></i> ตั้งค่าระบบคิว</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">เสียงเรียกคิว</label>
                        <select class="form-select" id="voiceLanguage">
                            <option value="th-TH">ภาษาไทย</option>
                            <option value="en-US">English</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ความเร็วการพูด</label>
                        <input type="range" class="form-range" id="voiceSpeed" min="0.5" max="2" step="0.1" value="0.8">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ระดับเสียง</label>
                        <input type="range" class="form-range" id="voiceVolume" min="0" max="1" step="0.1" value="1">
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                            <label class="form-check-label">รีเฟรชอัตโนมัติ</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-primary" onclick="saveQueueSettings()">บันทึก</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= SITE_URL ?>assets/js/jquery.min.js"></script>
    <script src="<?= SITE_URL ?>assets/js/bootstrap.min.js"></script>
    <script src="<?= SITE_URL ?>assets/js/pos.js"></script>

    <script>
        let currentQueueId = <?= $currentQueue['id'] ?? 'null' ?>;
        let autoRefreshEnabled = true;
        let voiceSettings = {
            language: 'th-TH',
            speed: 0.8,
            volume: 1.0
        };

        $(document).ready(function() {
            updateDateTime();
            setInterval(updateDateTime, 1000);
            
            // Auto refresh every 15 seconds
            if (autoRefreshEnabled) {
                setInterval(refreshQueueData, 15000);
            }
            
            loadQueueSettings();
        });

        // Call current queue
        function callCurrentQueue() {
            if (!currentQueueId) {
                showToast('ไม่มีคิวที่จะเรียก', '', 'warning');
                return;
            }
            
            const voiceIndicator = document.getElementById('voiceIndicator');
            voiceIndicator.style.display = 'inline-block';
            
            $.post('queue_display.php', {
                action: 'call_queue',
                queue_id: currentQueueId
            })
            .done(function(response) {
                if (response.success) {
                    playVoiceAnnouncement(response.queue_number, response.customer_name);
                    showToast('เรียกคิวสำเร็จ', `เรียกคิว ${response.queue_number}`, 'success');
                } else {
                    showToast('เกิดข้อผิดพลาด', response.message, 'error');
                }
            })
            .always(function() {
                setTimeout(() => {
                    voiceIndicator.style.display = 'none';
                }, 3000);
            });
        }

        // Play voice announcement
        function playVoiceAnnouncement(queueNumber, customerName) {
            if ('speechSynthesis' in window) {
                const text = `หมายเลขคิว ${queueNumber} คุณ ${customerName} พร้อมรับออเดอร์ค่ะ`;
                const utterance = new SpeechSynthesisUtterance(text);
                
                utterance.lang = voiceSettings.language;
                utterance.rate = voiceSettings.speed;
                utterance.volume = voiceSettings.volume;
                utterance.pitch = 1.2;
                
                speechSynthesis.speak(utterance);
            }
        }

        // Mark current queue as served
        function markAsServed() {
            if (!currentQueueId) {
                showToast('ไม่มีคิวที่จะทำเครื่องหมาย', '', 'warning');
                return;
            }
            
            $.post('queue_display.php', {
                action: 'update_queue_status',
                queue_id: currentQueueId,
                status: 'completed'
            })
            .done(function(response) {
                if (response.success) {
                    showToast('อัปเดตสำเร็จ', 'คิวถูกทำเครื่องหมายว่าบริการแล้ว', 'success');
                    nextQueue();
                } else {
                    showToast('เกิดข้อผิดพลาด', response.message, 'error');
                }
            });
        }

        // Skip current queue
        function skipCurrentQueue() {
            if (!currentQueueId) {
                showToast('ไม่มีคิวที่จะข้าม', '', 'warning');
                return;
            }
            
            if (confirm('คุณต้องการข้ามคิวนี้หรือไม่?')) {
                $.post('queue_display.php', {
                    action: 'skip_queue',
                    queue_id: currentQueueId
                })
                .done(function(response) {
                    if (response.success) {
                        showToast('ข้ามคิวสำเร็จ', '', 'warning');
                        nextQueue();
                    } else {
                        showToast('เกิดข้อผิดพลาด', response.message, 'error');
                    }
                });
            }
        }

        // Move to next queue
        function nextQueue() {
            $.post('queue_display.php', {
                action: 'next_queue'
            })
            .done(function(response) {
                if (response.success) {
                    if (response.next_queue) {
                        currentQueueId = response.next_queue.id;
                        document.getElementById('currentQueueNumber').textContent = response.next_queue.queue_number;
                        showToast('เปลี่ยนคิวสำเร็จ', `เปลี่ยนเป็นคิว ${response.next_queue.queue_number}`, 'info');
                    } else {
                        currentQueueId = null;
                        document.getElementById('currentQueueNumber').textContent = 'N/A';
                        showToast('ไม่มีคิวถัดไป', 'ไม่มีคิวที่รออยู่', 'info');
                    }
                    refreshQueueData();
                } else {
                    showToast('เกิดข้อผิดพลาด', response.message, 'error');
                }
            });
        }

        // Set queue as current
        function setAsCurrentQueue(queueId) {
            $.post('queue_display.php', {
                action: 'update_queue_status',
                queue_id: queueId,
                status: 'calling'
            })
            .done(function(response) {
                if (response.success) {
                    currentQueueId = queueId;
                    refreshQueueData();
                    showToast('เปลี่ยนคิวสำเร็จ', '', 'success');
                } else {
                    showToast('เกิดข้อผิดพลาด', response.message, 'error');
                }
            });
        }

        // Mark specific queue as served
        function markQueueAsServed(queueId) {
            $.post('queue_display.php', {
                action: 'update_queue_status',
                queue_id: queueId,
                status: 'completed'
            })
            .done(function(response) {
                if (response.success) {
                    showToast('อัปเดตสำเร็จ', 'คิวถูกทำเครื่องหมายว่าเสร็จสิ้น', 'success');
                    refreshQueueData();
                } else {
                    showToast('เกิดข้อผิดพลาด', response.message, 'error');
                }
            });
        }

        // Refresh queue data
        function refreshQueueData() {
            $.post('queue_display.php', {
                action: 'get_queue_data'
            })
            .done(function(response) {
                if (response.success) {
                    updateQueueDisplay(response.data);
                }
            });
        }

        // Update queue display
        function updateQueueDisplay(data) {
            // Update statistics
            document.getElementById('waitingCount').textContent = data.waiting_count;
            document.getElementById('readyCount').textContent = data.ready_count;
            document.getElementById('callingCount').textContent = data.calling_count;
            
            // Update current queue
            if (data.current_queue) {
                document.getElementById('currentQueueNumber').textContent = data.current_queue.queue_number;
                currentQueueId = data.current_queue.id;
            } else {
                document.getElementById('currentQueueNumber').textContent = 'N/A';
                currentQueueId = null;
            }
        }

        // Refresh queue display
        function refreshQueueDisplay() {
            location.reload();
        }

        // Show queue settings
        function showQueueSettings() {
            const modal = new bootstrap.Modal(document.getElementById('queueSettingsModal'));
            modal.show();
        }

        // Save queue settings
        function saveQueueSettings() {
            voiceSettings.language = document.getElementById('voiceLanguage').value;
            voiceSettings.speed = parseFloat(document.getElementById('voiceSpeed').value);
            voiceSettings.volume = parseFloat(document.getElementById('voiceVolume').value);
            autoRefreshEnabled = document.getElementById('autoRefresh').checked;
            
            localStorage.setItem('queueSettings', JSON.stringify(voiceSettings));
            localStorage.setItem('autoRefresh', autoRefreshEnabled);
            
            showToast('บันทึกสำเร็จ', 'ตั้งค่าถูกบันทึกแล้ว', 'success');
            
            const modal = bootstrap.Modal.getInstance(document.getElementById('queueSettingsModal'));
            modal.hide();
        }

        // Load queue settings
        function loadQueueSettings() {
            const savedSettings = localStorage.getItem('queueSettings');
            const savedAutoRefresh = localStorage.getItem('autoRefresh');
            
            if (savedSettings) {
                voiceSettings = JSON.parse(savedSettings);
                document.getElementById('voiceLanguage').value = voiceSettings.language;
                document.getElementById('voiceSpeed').value = voiceSettings.speed;
                document.getElementById('voiceVolume').value = voiceSettings.volume;
            }
            
            if (savedAutoRefresh !== null) {
                autoRefreshEnabled = savedAutoRefresh === 'true';
                document.getElementById('autoRefresh').checked = autoRefreshEnabled;
            }
        }

        // Test voice system
        function testVoiceSystem() {
            playVoiceAnnouncement('A001', 'ทดสอบระบบ');
            showToast('ทดสอบเสียง', 'กำลังทดสอบระบบเสียง', 'info');
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
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = now.toLocaleDateString('th-TH', options);
            }
        }

        function showToast(title, message, type = 'info') {
            // Toast implementation
            console.log(`${type}: ${title} - ${message}`);
        }
    </script>

</body>
</html>

<?php
function getWaitTime($createdAt) {
    $now = new DateTime();
    $created = new DateTime($createdAt);
    $diff = $now->diff($created);
    return $diff->i + ($diff->h * 60);
}

function callQueue($queueId, $queue, $voice) {
    $queueData = $queue->getQueueById($queueId);
    if (!$queueData) {
        return ['success' => false, 'message' => 'ไม่พบคิว'];
    }
    
    // Update status to calling
    $queue->updateStatus($queueId, 'calling');
    
    // Log voice call (in real implementation, integrate with actual voice system)
    $voice->logCall($queueId, $queueData['queue_number']);
    
    return [
        'success' => true,
        'queue_number' => $queueData['queue_number'],
        'customer_name' => $queueData['customer_name']
    ];
}

function nextQueue($queue) {
    $nextQueue = $queue->getNextWaitingQueue();
    if ($nextQueue) {
        $queue->updateStatus($nextQueue['id'], 'calling');
        return [
            'success' => true,
            'next_queue' => $nextQueue
        ];
    }
    
    return [
        'success' => true,
        'next_queue' => null
    ];
}

function skipQueue($queueId, $queue) {
    $queue->updateStatus($queueId, 'skipped');
    return ['success' => true];
}

function updateQueueStatus($queueId, $status, $queue) {
    $queue->updateStatus($queueId, $status);
    return ['success' => true];
}

function getQueueData($queue) {
    return [
        'success' => true,
        'data' => [
            'current_queue' => $queue->getCurrentQueue(),
            'waiting_count' => $queue->getWaitingCount(),
            'ready_count' => $queue->getReadyCount(),
            'calling_count' => $queue->getCallingCount()
        ]
    ];
}
?>
=======
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
>>>>>>> 4f0b250224a8b9c2467a45845675bf7ab01b4999
