<?php
session_start();
require_once "../config/db.php";

// Access control: only admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

// Approve/Reject handler with tenant/room logic
if (isset($_POST['action'], $_POST['app_id'])) {
    $app_id = intval($_POST['app_id']);
    $action = $_POST['action'];

    // Get application details
    $stmt = $conn->prepare("SELECT * FROM room_applications WHERE id=?");
    $stmt->bind_param('i', $app_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $app = $result->fetch_assoc();
    $stmt->close();

    if ($app && $app['status'] === 'pending') {
        if ($action === 'approve') {
            // 1. Approve the application
            $stmt = $conn->prepare("UPDATE room_applications SET status='approved' WHERE id=?");
            $stmt->bind_param('i', $app_id);
            $stmt->execute();
            $stmt->close();

            // 2. Set room as occupied
            $stmt = $conn->prepare("UPDATE rooms SET status='occupied' WHERE id=?");
            $stmt->bind_param('i', $app['room_id']);
            $stmt->execute();
            $stmt->close();

            // 3. Register new tenant (using your schema)
            $stmt = $conn->prepare("INSERT INTO tenants (name, email, phone, apartment_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param(
                'sssi',
                $app['applicant_name'],
                $app['applicant_email'],
                $app['applicant_phone'],
                $app['room_id'] // this is the apartment_id for tenants table
            );
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['flash_message'] = "Application approved and tenant registered!";
            $_SESSION['flash_type'] = "success";
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE room_applications SET status='rejected' WHERE id=?");
            $stmt->bind_param('i', $app_id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['flash_message'] = "Application rejected!";
            $_SESSION['flash_type'] = "warning";
        }
        header("Location: applications.php");
        exit();
    }
}

// Get all applications
$apps = $conn->query("SELECT ra.*, r.number AS room_number FROM room_applications ra JOIN rooms r ON ra.room_id = r.id ORDER BY ra.applied_at DESC");

// Get application statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0
];

$res = $conn->query("SELECT status, COUNT(*) as count FROM room_applications GROUP BY status");
while ($row = $res->fetch_assoc()) {
    $stats['total'] += $row['count'];
    $stats[strtolower($row['status'])] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications | PropertyPro Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e0e7ff;
            --secondary: #3f37c9;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --light-gray: #e2e8f0;
            
            --border-radius: 12px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f5f9;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1400px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
        }
        
        .container:hover {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        
        h1 {
            margin-top: 0;
            color: var(--primary);
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }
        
        h1::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
        }
        
        .summary-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary);
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-pending {
            background-color: rgba(245, 158, 11, 0.15);
            color: var(--warning);
        }
        
        .status-approved {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }
        
        .status-rejected {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
            font-weight: 500;
            padding: 15px;
        }
        
        .table td {
            vertical-align: middle;
            padding: 12px 15px;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .flash-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            animation: slideIn 0.5s forwards, fadeOut 0.5s 3s forwards;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .message-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .badge-filter {
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .badge-filter:hover {
            transform: scale(1.05);
        }
        
        .badge-filter.active {
            box-shadow: 0 0 0 2px white, 0 0 0 4px var(--primary);
        }
    </style>
</head>
<body>
<div class="container fade-in">
    <a href="dashboard.php" class="btn btn-outline-primary mb-3">
        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
    </a>
    
    <h1><i class="fas fa-file-alt me-2"></i> Room Applications</h1>
    
    <?php if(isset($_SESSION['flash_message'])): ?>
        <div class="flash-message alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
    <?php endif; ?>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2"><i class="fas fa-file-alt me-2"></i> Total</h6>
                        <h3 class="mb-0"><?= $stats['total'] ?></h3>
                    </div>
                    <div class="bg-primary-light p-3 rounded">
                        <i class="fas fa-file-alt fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2"><i class="fas fa-hourglass-half me-2"></i> Pending</h6>
                        <h3 class="mb-0"><?= $stats['pending'] ?></h3>
                    </div>
                    <div class="bg-warning-light p-3 rounded">
                        <i class="fas fa-hourglass-half fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2"><i class="fas fa-check-circle me-2"></i> Approved</h6>
                        <h3 class="mb-0"><?= $stats['approved'] ?></h3>
                    </div>
                    <div class="bg-success-light p-3 rounded">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="summary-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2"><i class="fas fa-times-circle me-2"></i> Rejected</h6>
                        <h3 class="mb-0"><?= $stats['rejected'] ?></h3>
                    </div>
                    <div class="bg-danger-light p-3 rounded">
                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Applications Table -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i> Application Records</h5>
                <div>
                    <span class="badge badge-filter bg-primary me-2 active" data-filter="all">All</span>
                    <span class="badge badge-filter bg-warning me-2" data-filter="pending">Pending</span>
                    <span class="badge badge-filter bg-success me-2" data-filter="approved">Approved</span>
                    <span class="badge badge-filter bg-danger" data-filter="rejected">Rejected</span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Contact</th>
                            <th>Room #</th>
                            <th>Message</th>
                            <th>Applied At</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($app = $apps->fetch_assoc()): ?>
                        <tr class="fade-in" data-status="<?= strtolower($app['status']) ?>">
                            <td>
                                <strong><?= htmlspecialchars($app['applicant_name']) ?></strong>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($app['applicant_email']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($app['applicant_phone']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-primary">#<?= htmlspecialchars($app['room_number']) ?></span>
                            </td>
                            <td class="message-preview" title="<?= htmlspecialchars($app['message']) ?>">
                                <?= nl2br(htmlspecialchars($app['message'])) ?>
                            </td>
                            <td>
                                <?= date('M d, Y', strtotime($app['applied_at'])) ?>
                                <br>
                                <small class="text-muted"><?= date('h:i A', strtotime($app['applied_at'])) ?></small>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower($app['status']) ?>">
                                    <?php if($app['status'] == 'pending'): ?>
                                        <i class="fas fa-hourglass-half"></i>
                                    <?php elseif($app['status'] == 'approved'): ?>
                                        <i class="fas fa-check-circle"></i>
                                    <?php else: ?>
                                        <i class="fas fa-times-circle"></i>
                                    <?php endif; ?>
                                    <?= ucfirst($app['status']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if($app['status'] == 'pending'): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm me-1">
                                        <i class="fas fa-check me-1"></i> Approve
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                                        <i class="fas fa-times me-1"></i> Reject
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span class="text-muted">No action available</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Filter applications by status
    document.addEventListener('DOMContentLoaded', function() {
        const filterBadges = document.querySelectorAll('.badge-filter');
        
        filterBadges.forEach(badge => {
            badge.addEventListener('click', function() {
                const filter = this.getAttribute('data-filter');
                
                // Update active badge
                filterBadges.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Filter table rows
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    if (filter === 'all') {
                        row.style.display = '';
                    } else {
                        if (row.getAttribute('data-status') === filter) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    }
                });
            });
        });
        
        // Close flash message after 3 seconds
        const flashMessage = document.querySelector('.flash-message');
        if (flashMessage) {
            setTimeout(() => {
                flashMessage.style.display = 'none';
            }, 3000);
        }
    });
</script>
</body>
</html>
<?php $conn->close(); ?>