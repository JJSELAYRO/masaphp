<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

include '../config/db.php';

// Handle delete actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['delete_maintenance_id'])) {
        $delete_id = intval($_GET['delete_maintenance_id']);
        $conn->query("DELETE FROM maintenance_requests WHERE id=$delete_id");
        $_SESSION['success'] = "Maintenance request deleted successfully";
        header("Location: dashboard.php");
        exit();
    }
    
    if (isset($_GET['delete_tenant_id'])) {
        $delete_id = intval($_GET['delete_tenant_id']);  
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM payments WHERE tenant_id=$delete_id");
            $conn->query("DELETE FROM maintenance_requests WHERE tenant_id=$delete_id");
            $conn->query("DELETE FROM tenants WHERE id=$delete_id");
            $conn->commit();
            $_SESSION['success'] = "Tenant and all related data deleted successfully";
            header("Location: dashboard.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Delete tenant failed: " . $e->getMessage());
            $_SESSION['error'] = "Failed to delete tenant. Please try again.";
            header("Location: dashboard.php");
            exit();
        }
    }
}

function fetchDashboardData($conn) {
    $data = [];
    
    // Fetch tenants
    $data['tenants'] = $conn->query("SELECT * FROM tenants ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
    
    // Fetch rooms
    $data['apartments'] = $conn->query("SELECT * FROM rooms")->fetch_all(MYSQLI_ASSOC);
    
    // Fetch maintenance requests
    $data['maintenance_requests'] = $conn->query(
        "SELECT mr.*, t.name as tenant_name, r.number as apartment_number
        FROM maintenance_requests mr
        LEFT JOIN tenants t ON mr.tenant_id = t.id
        LEFT JOIN rooms r ON mr.apartment_id = r.id
        ORDER BY request_date DESC LIMIT 5"
    )->fetch_all(MYSQLI_ASSOC);
    
    // Fetch payments
    $data['payments'] = $conn->query(
        "SELECT p.*, t.name as tenant_name, r.number as apartment_number
         FROM payments p
         LEFT JOIN tenants t ON p.tenant_id = t.id
         LEFT JOIN rooms r ON t.apartment_id = r.id
         WHERE p.status = 'paid' OR p.status = 'completed'
         ORDER BY p.date DESC LIMIT 5"
    )->fetch_all(MYSQLI_ASSOC);
    
    return $data;
}

$dashboardData = fetchDashboardData($conn);

// Extract data
$tenants = $dashboardData['tenants'];
$apartments = $dashboardData['apartments'];
$maintenance_requests = $dashboardData['maintenance_requests'];
$payments = $dashboardData['payments'];

// Counts for summary cards
$tenant_count = $conn->query("SELECT COUNT(*) as count FROM tenants")->fetch_assoc()['count'];
$apartment_count = $conn->query("SELECT COUNT(*) as count FROM rooms")->fetch_assoc()['count'];

// Maintenance counts
$active_maintenance_result = $conn->query(
    "SELECT COUNT(*) as count FROM maintenance_requests 
     WHERE status IN ('pending', 'in_progress', 'inprogress')"
)->fetch_assoc();
$active_maintenance = $active_maintenance_result['count'];

$maintenance_status = [
    'pending' => $conn->query("SELECT COUNT(*) as count FROM maintenance_requests WHERE status = 'pending'")->fetch_assoc()['count'],
    'in_progress' => $conn->query("SELECT COUNT(*) as count FROM maintenance_requests WHERE status IN ('in_progress', 'inprogress')")->fetch_assoc()['count'],
    'completed' => $conn->query("SELECT COUNT(*) as count FROM maintenance_requests WHERE status = 'completed'")->fetch_assoc()['count']
];

// Revenue calculation
$total_payments_result = $conn->query(
    "SELECT SUM(amount) as total FROM payments WHERE status IN ('completed', 'paid')"
)->fetch_assoc();
$total_payments = $total_payments_result['total'] ?? 0;

$completed_payments_result = $conn->query(
    "SELECT COUNT(*) as count FROM payments WHERE status IN ('completed', 'paid')"
)->fetch_assoc();
$completed_payments = $completed_payments_result['count'];

// Monthly payments data
$monthly_payments = array_fill(1, 12, 0);
$monthly_result = $conn->query(
    "SELECT MONTH(date) as month, SUM(amount) as total 
     FROM payments 
     WHERE status IN ('completed', 'paid') AND YEAR(date) = YEAR(CURDATE())
     GROUP BY MONTH(date)"
);

while ($row = $monthly_result->fetch_assoc()) {
    $month = intval($row['month']);
    if ($month >= 1 && $month <= 12) {
        $monthly_payments[$month] = floatval($row['total']);
    }
}

// Prepare chart data
$monthly_payments_chart = array_values($monthly_payments);

// Room status counts
$apartment_status_counts = [
    'occupied' => $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'occupied'")->fetch_assoc()['count'],
    'vacant' => $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'vacant'")->fetch_assoc()['count'],
    'maintenance' => $conn->query("SELECT COUNT(*) as count FROM rooms WHERE status = 'maintenance'")->fetch_assoc()['count']
];

// Pending applications
$pending_app_count = $conn->query("SELECT COUNT(*) as count FROM room_applications WHERE status = 'pending'")->fetch_assoc()['count'];
$latest_pending_apps = $conn->query("
    SELECT ra.*, r.number AS room_number
    FROM room_applications ra
    JOIN rooms r ON ra.room_id = r.id
    WHERE ra.status = 'pending'
    ORDER BY ra.applied_at DESC
    LIMIT 5
");

// Prepare recent activity data
$recent_activities = [];

foreach ($tenants as $tenant) {
    $recent_activities[] = [
        'type' => 'tenant',
        'title' => 'New Tenant Added',
        'description' => $tenant['name'] . ' joined',
        'time' => date('M j, g:i a', strtotime($tenant['created_at'])),
        'id' => $tenant['id']
    ];
}

foreach ($payments as $payment) {
    $recent_activities[] = [
        'type' => 'payment',
        'title' => 'Payment Received',
        'description' => '₱' . number_format($payment['amount'], 2) . ' from ' . $payment['tenant_name'] . ' (Apt ' . $payment['apartment_number'] . ')',
        'time' => date('M j, g:i a', strtotime($payment['date'])),
        'id' => $payment['id']
    ];
}

foreach ($maintenance_requests as $request) {
    $recent_activities[] = [
        'type' => 'maintenance',
        'title' => 'Maintenance Request',
        'description' => 'From ' . $request['tenant_name'] . ' (Apt ' . $request['apartment_number'] . '): ' . substr($request['request_text'], 0, 30) . (strlen($request['request_text']) > 30 ? '...' : ''),
        'time' => date('M j, g:i a', strtotime($request['request_date'])),
        'id' => $request['id']
    ];
}

usort($recent_activities, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

$recent_activities = array_slice($recent_activities, 0, 5);

// Notifications
$admin_id = $_SESSION['user_id'];

$count_stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM admin_notifications WHERE user_id=? AND is_read=0");
$count_stmt->bind_param("i", $admin_id);
$count_stmt->execute();
$notif_count = $count_stmt->get_result()->fetch_assoc()['unread_count'];
$count_stmt->close();

$notif_stmt = $conn->prepare("
    SELECT n.id, n.message, n.type, n.created_at, n.tenant_id, t.name as tenant_name
    FROM admin_notifications n
    LEFT JOIN tenants t ON n.tenant_id = t.id
    WHERE n.user_id=? AND n.is_read=0 
    ORDER BY n.created_at DESC 
    LIMIT 5");
$notif_stmt->bind_param("i", $admin_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
$unread_notifs = $notif_result ? $notif_result->fetch_all(MYSQLI_ASSOC) : [];
$notif_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | PropertyPro</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e0e7ff;
            --secondary: #3f37c9;
            --success: #10b981;
            --success-light: #d1fae5;
            --danger: #ef4444;
            --danger-light: #fee2e2;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --info: #3b82f6;
            --info-light: #dbeafe;
            --dark: #1f2937;
            --light: #f9fafb;
            --gray: #6b7280;
            --gray-light: #f3f4f6;
            --sidebar-width: 280px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark);
            overflow-x: hidden;
        }
        
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            color: white;
            padding: 1.5rem 1rem;
            transition: var(--transition);
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-brand {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            padding: 0 1rem;
            margin-bottom: 2rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .sidebar-brand i {
            font-size: 1.75rem;
        }
        
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }
        
        .nav-item {
            margin-bottom: 0.5rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }
        
        .nav-link i {
            font-size: 1.1rem;
            margin-right: 0.75rem;
            width: 24px;
            text-align: center;
        }
        
        .nav-link:hover, 
        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
            transform: translateX(4px);
            border-left: 3px solid white;
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            font-weight: 600;
        }
        
        .badge-notification {
            font-size: 0.7rem;
            font-weight: 600;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: auto;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: var(--transition);
            min-height: 100vh;
        }
        
        /* Cards */
        .dashboard-card {
            background: white;
            border-radius: 12px;
            border: none;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            overflow: hidden;
            height: 100%;
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }
        
        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }
        
        .card-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
        }
        
        /* Summary Card Gradient Effects */
        .summary-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            z-index: 0;
        }
        
        /* Charts */
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }
        
        /* Activity Feed */
        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-light);
            transition: var(--transition);
        }
        
        .activity-item:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        /* Notification Dropdown */
        .notification-dropdown {
            width: 350px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--gray-light);
            transition: var(--transition);
        }
        
        .notification-item:hover {
            background-color: var(--gray-light);
        }
        
        /* Navbar */
        .top-navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
            border-radius: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                left: calc(-1 * var(--sidebar-width));
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--gray-light);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(67, 97, 238, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(67, 97, 238, 0); }
            100% { box-shadow: 0 0 0 0 rgba(67, 97, 238, 0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .slide-in {
            animation: slideIn 0.5s ease-out;
        }
        
        .pulse {
            animation: pulse 1.5s infinite;
        }
        
        /* Toast */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
        
        .toast {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        /* Welcome Message */
        .welcome-message h4 {
            font-weight: 700;
            color: var(--dark);
        }
        
        /* Application Alert */
        .application-alert {
            border-left: 4px solid var(--warning);
        }
        
        /* Status Badges */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-pending {
            background-color: rgba(248, 150, 30, 0.1);
            color: var(--warning);
        }
        
        .status-in-progress {
            background-color: rgba(6, 182, 212, 0.1);
            color: var(--info);
        }
        
        .status-completed {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        /* Loading Spinner */
        .loading-spinner {
            display: inline-block;
            width: 1.5rem;
            height: 1.5rem;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Interactive Elements */
        .interactive-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .interactive-card:hover {
            transform: translateY(-3px);
        }
        
        /* Dark Mode Toggle */
        .dark-mode-toggle {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .dark-mode-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .dark-mode-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .dark-mode-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .dark-mode-slider {
            background-color: var(--primary);
        }
        
        input:checked + .dark-mode-slider:before {
            transform: translateX(26px);
        }
        
        /* Modern Checkbox */
        .form-check-input:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 100;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .fab:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="fas fa-city"></i>
                <span>PropertyPro</span>
            </a>
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_notifications.php" class="nav-link">
                        <i class="fas fa-bell"></i>
                        <span>Notifications</span>
                        <?php if ($notif_count > 0): ?>
                            <span class="badge-notification"><?= $notif_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="applications.php" class="nav-link">
                        <i class="fas fa-inbox"></i>
                        <span>Applications</span>
                        <?php if ($pending_app_count > 0): ?>
                            <span class="badge-notification"><?= $pending_app_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="tenants.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Tenants</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="apartment.php" class="nav-link">
                        <i class="fas fa-building"></i>
                        <span>Apartments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="payment.php" class="nav-link">
                        <i class="fas fa-credit-card"></i>
                        <span>Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="maintenance.php" class="nav-link">
                        <i class="fas fa-tools"></i>
                        <span>Maintenance</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="messages_admin_list.php" class="nav-link">
                        <i class="fas fa-comments"></i>
                        <span>Messages</span>
                    </a>
                </li>
                <li class="nav-item mt-auto">
                    <div class="d-flex align-items-center px-3 py-2">
                        <label class="dark-mode-toggle me-2">
                            <input type="checkbox" id="darkModeToggle">
                            <span class="dark-mode-slider"></span>
                        </label>
                        <small class="text-white-50">Dark Mode</small>
                    </div>
                </li>
                <li class="nav-item">
                    <a href="../public/website.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <nav class="top-navbar mb-4">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between w-100">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm d-lg-none me-3" id="sidebarToggle">
                                <i class="fas fa-bars"></i>
                            </button>
                            <h5 class="mb-0 fw-bold">Dashboard Overview</h5>
                        </div>
                        <div class="navbar-nav ms-auto align-items-center">
                            <li class="nav-item dropdown me-3">
                                <a class="nav-link position-relative" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell fs-5"></i>
                                    <?php if ($notif_count > 0): ?>
                                        <span class="badge-notification"><?= $notif_count ?></span>
                                    <?php endif; ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notifDropdown">
                                    <?php if (count($unread_notifs) === 0): ?>
                                        <li class="dropdown-item text-center py-3">
                                            <i class="far fa-bell-slash fs-4 text-muted mb-2"></i>
                                            <p class="mb-0 text-muted">No new notifications</p>
                                        </li>
                                    <?php else: ?>
                                        <li class="dropdown-header fw-bold px-3 pt-2 pb-1">New Notifications</li>
                                        <?php foreach ($unread_notifs as $notif): ?>
                                            <li>
                                                <a class="dropdown-item" href="messages_admin.php?tenant_id=<?= $notif['tenant_id'] ?>">
                                                    <div class="d-flex align-items-center">
                                                        <div class="me-3">
                                                            <div class="bg-primary bg-opacity-10 rounded-circle p-2">
                                                                <i class="fas fa-envelope text-primary"></i>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <p class="mb-1"><?= htmlspecialchars($notif['message']) ?></p>
                                                            <small class="text-muted"><?= date('M j, Y H:i', strtotime($notif['created_at'])) ?></small>
                                                        </div>
                                                    </div>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-center" href="admin_notifications.php">View All Notifications</a></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=Admin&background=4361ee&color=fff" alt="Admin" class="user-avatar me-2">
                                        <span class="d-none d-md-inline">Admin</span>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-cog me-2"></i> Profile</a></li>
                                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="../public/website.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                                </ul>
                            </li>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="container-fluid">
                <!-- Welcome Message -->
                <div class="welcome-message mb-4 slide-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-2">Welcome Back, <span class="text-primary">Admin</span> 👋</h4>
                            <p class="text-muted">Here's what's happening with your property today.</p>
                        </div>
                        <div class="d-flex">
                            <button class="btn btn-outline-primary me-2" id="refreshBtn">
                                <i class="fas fa-sync-alt me-1"></i> Refresh
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button" id="quickActions" data-bs-toggle="dropdown" aria-expanded="false">
                                    Quick Actions
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="quickActions">
                                    <li><a class="dropdown-item" href="tenants.php?action=add"><i class="fas fa-user-plus me-2"></i> Add Tenant</a></li>
                                    <li><a class="dropdown-item" href="payment.php?action=add"><i class="fas fa-money-bill-wave me-2"></i> Record Payment</a></li>
                                    <li><a class="dropdown-item" href="maintenance.php?action=add"><i class="fas fa-tools me-2"></i> Create Maintenance</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Alert -->
                <?php if ($pending_app_count > 0): ?>
                <div class="alert application-alert alert-warning alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-inbox me-3 fs-4"></i>
                        <div>
                            <strong><?= $pending_app_count ?> pending application<?= $pending_app_count > 1 ? 's' : '' ?></strong> need your review.
                            <a href="applications.php" class="btn btn-sm btn-warning ms-2">Review Now</a>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <!-- Summary Cards -->
                <div class="row g-4 mb-4" id="summaryCards">
                    <!-- Tenants Card -->
                    <div class="col-md-6 col-lg-3">
                        <div class="dashboard-card summary-card fade-in interactive-card" onclick="window.location.href='tenants.php'">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="card-icon bg-primary bg-opacity-10 text-primary me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-muted mb-1">Tenants</h6>
                                        <h3 class="card-title mb-0" id="tenantCount"><?= $tenant_count ?></h3>
                                        <small class="text-muted" id="occupancyRate"><?= round(($tenant_count / $apartment_count) * 100) ?>% occupancy</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Apartments Card -->
                    <div class="col-md-6 col-lg-3">
                        <div class="dashboard-card summary-card fade-in interactive-card" onclick="window.location.href='apartment.php'">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="card-icon bg-success bg-opacity-10 text-success me-3">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div>
                                        <h6 class="text-muted mb-1">Apartments</h6>
                                        <h3 class="card-title mb-0" id="apartmentCount"><?= $apartment_count ?></h3>
                                        <small class="text-muted" id="vacantCount"><?= $apartment_status_counts['vacant'] ?> available</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Maintenance Card -->
                    <div class="col-md-6 col-lg-3">
                        <div class="dashboard-card summary-card fade-in interactive-card" onclick="window.location.href='maintenance.php'">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="card-icon bg-warning bg-opacity-10 text-warning me-3">
                                        <i class="fas fa-tools"></i>
                                    </div>
                                     <div>
                                        <h6 class="card-subtitle mb-1 text-muted">Active Requests</h6>
                                        <h3 class="card-title mb-0 text-dark" id="active-maintenance"><?php echo $active_maintenance; ?></h3>
                                        <small class="text-muted" id="maintenance-stats">
                                            <?php echo $maintenance_status['pending']+$maintenance_status['in_progress']; ?> active, <?php echo $maintenance_status['completed']; ?> completed
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>         
                <!-- Payment Card -->
<div class="col-md-6 col-lg-3">
  <div class="modern-card summary-card card-payment fade-in">
    <div class="card-body">
      <div class="d-flex align-items-center">
        <div class="bg-info bg-opacity-10 rounded-lg p-3 me-3 fs-4 text-info fw-bold" style="font-style: normal;">
          ₱
        </div>
        <div>
          <h6 class="card-subtitle mb-1 text-muted">Total Revenue</h6>
          <h3 class="card-title mb-0 text-dark" id="total-revenue">₱<?php echo number_format($total_payments, 2); ?></h3>
          <small class="text-muted" id="payment-count">
            <?php echo $completed_payments; ?> payments
          </small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-bell text-warning me-2"></i>Recent Notifications</span>
        <a href="admin_notifications.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <ul class="list-group list-group-flush">
        <?php if (count($unread_notifs) === 0): ?>
            <li class="list-group-item text-muted">No new notifications.</li>
        <?php else: ?>
            <?php foreach ($unread_notifs as $notif): ?>
                <li class="list-group-item">
                    <?= htmlspecialchars($notif['message']) ?>
                    <br>
                    <small class="text-muted"><?= date('M j, Y H:i', strtotime($notif['created_at'])) ?></small>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>

                <ul class="list-group list-group-flush">
<?php foreach ($unread_notifs as $notif): ?>
    <li class="list-group-item">
        <?= htmlspecialchars($notif['message']) ?>
        <br>
        <small class="text-muted"><?= date('M j, Y H:i', strtotime($notif['created_at'])) ?></small>
        <?php if ($notif['type'] === 'message'): ?>
            <a href="messages_admin.php?tenant_id=<?= $notif['tenant_id'] ?>" class="btn btn-sm btn-primary ms-2">View</a>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>
                <!-- Charts Section -->
                <div class="row g-4 mb-4">
                    <!-- Apartment Status Chart -->
                    <div class="col-lg-6">
                        <div class="modern-card chart-container h-100 slide-in">
                            <div class="chart-title">
                                <h5 class="fw-bold">Apartment Status</h5>
                                <div>
                                    <span class="badge bg-light text-dark me-2" id="apartment-last-update">Updated: Just now</span>
                                    <a href="apartment.php" class="btn btn-sm btn-outline-primary btn-ripple">View All</a>
                                </div>
                            </div>
                            <canvas id="apartmentStatusChart" height="250"></canvas>
                            <div class="quick-stats mt-3 d-flex justify-content-around text-center">
                                <div>
                                    <div class="text-success fw-bold"><?php echo $apartment_status_counts['occupied']; ?></div>
                                    <small class="text-muted">Occupied</small>
                                </div>
                                <div>
                                    <div class="text-primary fw-bold"><?php echo $apartment_status_counts['vacant']; ?></div>
                                    <small class="text-muted">Vacant</small>
                                </div>
                                <div>
                                    <div class="text-warning fw-bold"><?php echo $apartment_status_counts['maintenance']; ?></div>
                                    <small class="text-muted">Maintenance</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monthly Revenue Chart -->
                    <div class="col-lg-6">
                        <div class="modern-card chart-container h-100 slide-in">
                            <div class="chart-title">
                                <h5 class="fw-bold">Monthly Revenue</h5>
                                <div>
                                    <span class="badge bg-light text-dark me-2" id="revenue-last-update">Updated: Just now</span>
                                    <a href="payment.php" class="btn btn-sm btn-outline-primary btn-ripple">View All</a>
                                </div>
                            </div>
                            <canvas id="monthlyRevenueChart" height="250"></canvas>
                            <div class="text-center mt-3">
                                <span class="badge bg-success" id="total-revenue-badge">Total: ₱<?php echo number_format($total_payments, 2); ?></span>
                                <span class="badge bg-primary ms-2" id="avg-revenue-badge">Avg: ₱<?php echo number_format($completed_payments > 0 ? $total_payments / $completed_payments : 0, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Maintenance and Activity Section -->
                <div class="row g-4">
                    <!-- Maintenance Status -->
                    <div class="col-lg-6">
                        <div class="modern-card chart-container h-100 slide-in">
                            <div class="chart-title">
                                <h5 class="fw-bold">Maintenance Requests</h5>
                                <div>
                                    <span class="badge bg-light text-dark me-2" id="maintenance-last-update">Updated: Just now</span>
                                    <a href="maintenance.php" class="btn btn-sm btn-outline-primary btn-ripple">View All</a>
                                </div>
                            </div>
                            <canvas id="maintenanceStatusChart" height="250"></canvas>
                            <div class="quick-stats mt-3 d-flex justify-content-around text-center">
                                <div>
                                    <div class="text-warning fw-bold"><?php echo $maintenance_status['pending']; ?></div>
                                    <small class="text-muted">Pending</small>
                                </div>
                                <div>
                                    <div class="text-info fw-bold"><?php echo $maintenance_status['in_progress']; ?></div>
                                    <small class="text-muted">In Progress</small>
                                </div>
                                <div>
                                    <div class="text-success fw-bold"><?php echo $maintenance_status['completed']; ?></div>
                                    <small class="text-muted">Completed</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="col-lg-6">
                        <div class="modern-card chart-container h-100 slide-in">
                            <div class="chart-title">
                                <h5 class="fw-bold">Recent Activity</h5>
                                <div>
                                    <span class="badge bg-light text-dark me-2" id="activity-last-update">Updated: Just now</span>
                                    <a href="#" class="btn btn-sm btn-outline-primary btn-ripple">View All</a>
                                </div>
                            </div>
                            <div id="recent-activity-container" style="max-height: 400px; overflow-y: auto;">
                                <ul class="recent-activity" id="recent-activity-list">
                                    <?php foreach ($recent_activities as $activity): 
                                        // Fix: Add parentheses for ternary operator associativity
                                        $iconClass = $activity['type'] === 'tenant' ? 'text-primary' : 
                                                    ($activity['type'] === 'payment' ? 'text-success' : 'text-warning');
                                        $icon = $activity['type'] === 'tenant' ? 'bi-people' : 
                                                ($activity['type'] === 'payment' ? 'bi-credit-card' : 'bi-tools');
                                        $deleteLink = $activity['type'] === 'tenant'
                                            ? "<a class='delete-btn' href='?delete_tenant_id={$activity['id']}' title='Delete tenant and all related data' onclick='return confirm(\"Are you sure you want to delete this tenant and all related data?\")'>
                                                    <i class='bi bi-trash'></i>
                                                </a>"
                                            : ($activity['type'] === 'maintenance'
                                                ? "<a class='delete-btn' href='?delete_maintenance_id={$activity['id']}' title='Delete maintenance request' onclick='return confirm(\"Are you sure you want to delete this maintenance request?\")'>
                                                    <i class='bi bi-trash'></i>
                                                </a>"
                                                : ''
                                            );
                                    ?>
                                    <li class="animate__animated animate__fadeIn">
                                        <div class="activity-icon <?php echo $iconClass; ?>">
                                            <i class="bi <?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="activity-details">
                                            <strong><?php echo htmlspecialchars($activity['title']); ?></strong>
                                            <div><?php echo htmlspecialchars($activity['description']); ?></div>
                                            <div class="activity-time">
                                                <?php echo $activity['time']; ?>
                                                <?php echo $deleteLink; ?>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    let apartmentChart, revenueChart, maintenanceChart;

    // Function to initialize or update charts
    function initCharts() {
        // Apartment Status Chart
        const apartmentCtx = document.getElementById('apartmentStatusChart').getContext('2d');
        apartmentChart = new Chart(apartmentCtx, {
            type: 'doughnut',
            data: {
                labels: ['Occupied', 'Vacant', 'Maintenance'],
                datasets: [{
                    data: [
                        <?php echo $apartment_status_counts['occupied']; ?>,
                        <?php echo $apartment_status_counts['vacant']; ?>,
                        <?php echo $apartment_status_counts['maintenance']; ?>
                    ],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(245, 158, 11, 0.8)'
                    ],
                    borderColor: [
                        'rgba(16, 185, 129, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(245, 158, 11, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12
                        },
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });

        // Monthly Revenue Chart
        const revenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
        revenueChart = new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Revenue (₱)',
                    data: <?php echo json_encode($monthly_payments_chart); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    borderRadius: 6,
                    hoverBackgroundColor: 'rgba(59, 130, 246, 0.9)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            },
                            font: {
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12
                        },
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toLocaleString();
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            }
        });

        // Maintenance Status Chart
        const maintenanceCtx = document.getElementById('maintenanceStatusChart').getContext('2d');
        maintenanceChart = new Chart(maintenanceCtx, {
            type: 'pie',
            data: {
                labels: ['Pending', 'In Progress', 'Completed'],
                datasets: [{
                    data: [
                        <?php echo $maintenance_status['pending']; ?>,
                        <?php echo $maintenance_status['in_progress']; ?>,
                        <?php echo $maintenance_status['completed']; ?>
                    ],
                    backgroundColor: [
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(6, 182, 212, 0.8)',
                        'rgba(16, 185, 129, 0.8)'
                    ],
                    borderColor: [
                        'rgba(245, 158, 11, 1)',
                        'rgba(6, 182, 212, 1)',
                        'rgba(16, 185, 129, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 12
                        },
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                animation: {
                    animateScale: true,
                    animateRotate: true
                }
            }
        });
    }

    // Call chart initialization
    initCharts();

    // Initialize Bootstrap toasts
    const toastElList = [].slice.call(document.querySelectorAll('.toast'));
    const toastList = toastElList.map(function (toastEl) {
        return new bootstrap.Toast(toastEl);
    });
    toastList.forEach(toast => toast.show());

    // Toggle sidebar on mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
            document.querySelector('.main-content').classList.toggle('full-width');
        });
    }

    // Add ripple effect to buttons
    document.querySelectorAll('.btn-ripple').forEach(button => {
        button.addEventListener('click', function(e) {
            let x = e.clientX - e.target.getBoundingClientRect().left;
            let y = e.clientY - e.target.getBoundingClientRect().top;

            let ripples = document.createElement('span');
            ripples.style.left = x + 'px';
            ripples.style.top = y + 'px';
            this.appendChild(ripples);

            setTimeout(() => {
                ripples.remove();
            }, 1000);
        });
    });

    // Start real-time updates
    startRealTimeUpdates();

    // Add animation to cards on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate__animated', 'animate__fadeInUp');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.modern-card').forEach(card => {
        observer.observe(card);
    });

    // Notification dropdown toggle
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');

    function closeDropdown(e) {
        if (
            !notificationBtn.contains(e.target) && 
            !notificationDropdown.contains(e.target)
        ) {
            notificationDropdown.classList.remove('active');
            document.removeEventListener('click', closeDropdown);
        }
    }

    if (notificationBtn && notificationDropdown) {
        notificationBtn.addEventListener('click', function(e) {
            e.preventDefault();
            notificationDropdown.classList.toggle('active');
            if (notificationDropdown.classList.contains('active')) {
                setTimeout(() => {
                    document.addEventListener('click', closeDropdown);
                }, 0);
            } else {
                document.removeEventListener('click', closeDropdown);
            }
        });
    }

    // Show any existing PHP messages as modern toasts
    <?php if (isset($_SESSION['success'])): ?>
        showModernToast("<?= addslashes($_SESSION['success']) ?>", 'success');
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        showModernToast("<?= addslashes($_SESSION['error']) ?>", 'error');
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
});

// Function to start real-time updates
function startRealTimeUpdates() {
    // Update dashboard every 30 seconds
    setInterval(updateDashboard, 30000);
}

// Function to update dashboard data
function updateDashboard() {
    fetch('ajax/get_dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            // Update summary cards with animation
            updateCardWithAnimation('tenant-count', data.tenant_count);
            updateCardWithAnimation('occupancy-rate', `${data.occupancy_rate}% occupancy`);
            updateCardWithAnimation('apartment-count', data.apartment_count);
            updateCardWithAnimation('vacant-count', `${data.vacant_count} available`);
            updateCardWithAnimation('active-maintenance', data.active_maintenance);
            updateCardWithAnimation('maintenance-stats', `${data.active_maintenance} active, ${data.completed_maintenance} completed`);
            updateCardWithAnimation('total-revenue', `₱${data.total_revenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
            updateCardWithAnimation('payment-count', `${data.completed_payments} payments`);
            updateCardWithAnimation('total-revenue-badge', `Total: ₱${data.total_revenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
            updateCardWithAnimation('avg-revenue-badge', `Avg: ₱${data.avg_revenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);

            // Update charts
            updateChartData(window.apartmentChart, [data.occupied_count, data.vacant_count, data.maintenance_count]);
            updateChartData(window.revenueChart, data.monthly_revenue);
            updateChartData(window.maintenanceChart, [data.pending_maintenance, data.in_progress_maintenance, data.completed_maintenance]);

            // Update last update times
            const now = new Date();
            const updateTime = now.toLocaleTimeString();
            document.querySelectorAll('[id$="-last-update"]').forEach(el => {
                el.textContent = `Updated: ${updateTime}`;
            });

            // Update connection status
            document.getElementById('connection-status').innerHTML = 
                '<i class="bi bi-circle-fill text-success"></i><small class="ms-1">Live</small>';
        })
        .catch(error => {
            console.error('Error updating dashboard:', error);
            document.getElementById('connection-status').innerHTML = 
                '<i class="bi bi-circle-fill text-danger"></i><small class="ms-1">Offline</small>';
        });
}

// Function to update a card with animation
function updateCardWithAnimation(elementId, newValue) {
    const element = document.getElementById(elementId);
    if (element && element.textContent !== newValue) {
        element.parentElement.classList.add('pulse-update');
        element.textContent = newValue;

        // Remove animation class after animation completes
        setTimeout(() => {
            element.parentElement.classList.remove('pulse-update');
        }, 1000);
    }
}

// Function to update chart data
function updateChartData(chart, newData) {
    if (chart && chart.data.datasets) {
        chart.data.datasets.forEach(dataset => {
            dataset.data = newData;
        });
        chart.update();
    }
}

// Modern toast notifications
function showModernToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast-modern ${type} mb-3`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');

    toast.innerHTML = `
        <div class="toast-body d-flex align-items-center">
            <div class="me-3">
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill text-success' : 
                  type === 'error' ? 'bi-x-circle-fill text-danger' : 
                  type === 'warning' ? 'bi-exclamation-triangle-fill text-warning' : 
                  'bi-info-circle-fill text-info'} fs-4"></i>
            </div>
            <div class="flex-grow-1">
                ${message}
            </div>
            <button type="button" class="btn-close ms-3" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1100';
        document.body.appendChild(toastContainer);
    }
    toastContainer.appendChild(toast);

    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
}
</script>
</body>
</html>