<?php
session_start();
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header('Location: ../public/login.php');
    exit();
}
$tenant_id = $_SESSION['user_id'];

// Mark all as read
$conn->query("UPDATE notifications SET is_read=1 WHERE tenant_id=$tenant_id AND is_read=0");

// Fetch notifications
$stmt = $conn->prepare("SELECT message, type, created_at FROM notifications WHERE tenant_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Notifications | PropertyPro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fb;
            color: #1e293b;
        }
        .container {
            max-width: 800px;
            margin: 60px auto;
            padding: 20px;
        }
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .notification-title {
            font-size: 2rem;
            font-weight: 600;
            color: #3f37c9;
        }
        .notification-list {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 0;
            list-style: none;
        }
        .notification-item {
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: start;
            gap: 15px;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-icon {
            width: 45px;
            height: 45px;
            background: #e0e7ff;
            color: #4361ee;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .notification-content {
            flex: 1;
        }
        .notification-time {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
        }
        .notification-empty {
            text-align: center;
            padding: 40px 20px;
            color: #64748b;
        }
        .notification-empty i {
            font-size: 3.5rem;
            margin-bottom: 15px;
            color: #e2e8f0;
        }
        .btn-back {
            margin-top: 25px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="notification-header">
        <h2 class="notification-title">
            <i class="fas fa-bell"></i> Notifications
        </h2>
    </div>

    <?php if ($result->num_rows == 0): ?>
        <div class="notification-empty">
            <i class="far fa-bell-slash"></i>
            <h4>No notifications</h4>
            <p>You’ll see updates here from your landlord.</p>
        </div>
    <?php else: ?>
        <ul class="notification-list">
            <?php while ($row = $result->fetch_assoc()): ?>
                <li class="notification-item">
                    <div class="notification-icon"><i class="fas fa-info-circle"></i></div>
                    <div class="notification-content">
                        <div><?= htmlspecialchars($row['message']) ?></div>
                        <div class="notification-time"><?= date('M j, Y \a\t g:i a', strtotime($row['created_at'])) ?></div>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php endif; ?>

    <a href="dashboard.php" class="btn btn-outline-primary btn-back">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>
</body>
</html>
