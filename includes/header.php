<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Dashboard' ?> - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #6f42c1;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --info-color: #0dcaf0;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --dark-color: #212529;
            --light-color: #f8f9fa;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), #8b5cf6);
            transition: all 0.3s;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            margin: 0.2rem 0;
            border-radius: 0.5rem;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }

        .main-content {
            min-height: 100vh;
        }

        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: none;
            border-radius: 1rem;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            border-radius: 1rem 1rem 0 0 !important;
        }

        .stats-card {
            background: linear-gradient(135deg, var(--primary-color), #8b5cf6);
            color: white;
        }

        .stats-card.success {
            background: linear-gradient(135deg, var(--success-color), #20c997);
        }

        .stats-card.warning {
            background: linear-gradient(135deg, var(--warning-color), #ffcd39);
            color: #212529;
        }

        .stats-card.info {
            background: linear-gradient(135deg, var(--info-color), #39c3f3);
        }

        .btn {
            border-radius: 0.75rem;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .table {
            border-radius: 1rem;
            overflow: hidden;
        }

        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
        }

        .navbar {
            background: white !important;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }

        .dropdown-menu {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .modal-content {
            border-radius: 1rem;
            border: none;
        }

        .form-control, .form-select {
            border-radius: 0.75rem;
            border: 1px solid #dee2e6;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(111, 66, 193, 0.25);
        }

        .status-pending { color: var(--warning-color); }
        .status-confirmed { color: var(--info-color); }
        .status-preparing { color: var(--warning-color); }
        .status-ready { color: var(--success-color); }
        .status-completed { color: var(--success-color); }
        .status-cancelled { color: var(--danger-color); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-utensils"></i>
                            Smart Order
                        </h4>
                        <p class="text-white-50 small">Admin Panel</p>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage == 'dashboard' ? 'active' : '' ?>" href="index.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage == 'orders' ? 'active' : '' ?>" href="order_management.php">
                                <i class="fas fa-shopping-cart"></i>
                                จัดการออเดอร์
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage == 'menu' ? 'active' : '' ?>" href="menu_management.php">
                                <i class="fas fa-utensils"></i>
                                จัดการเมนู
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage == 'queue' ? 'active' : '' ?>" href="queue_management.php">
                                <i class="fas fa-users"></i>
                                จัดการคิว
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage == 'reports' ? 'active' : '' ?>" href="reports.php">
                                <i class="fas fa-chart-bar"></i>
                                รายงาน
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage == 'users' ? 'active' : '' ?>" href="user_management.php">
                                <i class="fas fa-users-cog"></i>
                                จัดการผู้ใช้
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage == 'line' ? 'active' : '' ?>" href="line_settings.php">
                                <i class="fab fa-line"></i>
                                ตั้งค่า LINE OA
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage == 'settings' ? 'active' : '' ?>" href="system_settings.php">
                                <i class="fas fa-cog"></i>
                                ตั้งค่าระบบ
                            </a>
                        </li>
                    </ul>

                    <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="../pos/" target="_blank">
                                <i class="fas fa-cash-register"></i>
                                ระบบ POS
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../kitchen/" target="_blank">
                                <i class="fas fa-kitchen-set"></i>
                                จอครัว
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../customer/" target="_blank">
                                <i class="fas fa-store"></i>
                                หน้าร้าน
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Top navbar -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white mb-4">
                    <div class="container-fluid">
                        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        
                        <div class="ms-auto">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user"></i>
                                    <?= $_SESSION['admin_name'] ?? 'Admin' ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> โปรไฟล์</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>