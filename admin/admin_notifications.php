<?php
session_start();
require_once '../config/db.php';

// Ensure only admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit();
}

$user_id = $_SESSION['user_id']; // This is set after session check

// Handle AJAX notification deletion
if (isset($_GET['delete_id']) && isset($_GET['ajax'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM admin_notifications WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    echo json_encode(['success' => $stmt->affected_rows > 0]);
    exit;
}

// Mark all as read when opening this page
$conn->query("UPDATE admin_notifications SET is_read=1 WHERE user_id=$user_id");

// Fetch all notifications
$notifications = $conn->query("
    SELECT n.*, t.name as tenant_name 
    FROM admin_notifications n
    LEFT JOIN tenants t ON n.tenant_id = t.id
    WHERE n.user_id = $user_id
    ORDER BY n.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications | PropertyPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f5f7fb; color: #1e293b; }
        .navbar { box-shadow: 0 1px 2px rgba(0,0,0,.05); background: #fff; }
        .notif-badge {
            position: absolute; top: 7px; right: 7px;
            font-size: 0.75rem; min-width: 18px; min-height: 18px;
            padding: 2px 5px; border-radius: 10px;
            display: inline-block; text-align: center; z-index: 1;
        }
        .dropdown-menu[aria-labelledby="notifDropdown"] { max-height: 350px; overflow-y: auto; }
        .container { padding: 30px; max-width: 900px; margin: 0 auto; }
        .notification-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;
        }
        .notification-title { font-size: 2rem; font-weight: 600; color: #3f37c9; }
        .notification-count {
            background-color: #4361ee; color: #fff; border-radius: 50px;
            padding: 2px 10px; font-size: 1rem; margin-left: 10px;
        }
        .notification-list { background: #fff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1),0 2px 4px -1px rgba(0,0,0,0.06); overflow: hidden; padding: 0; list-style: none; }
        .notification-item { padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; gap: 15px; align-items: flex-start; }
        .notification-item:last-child { border-bottom: none; }
        .notification-icon { width: 45px; height: 45px; border-radius: 50%; background: #e0e7ff; display: flex; align-items: center; justify-content: center; color: #4361ee; font-size: 1.2rem; flex-shrink: 0; }
        .notification-content { flex: 1; }
        .notification-actions { display: flex; gap: 10px; align-items: center; }
        .notification-actions .btn-delete { color: #ef4444; background: none; border: none; cursor: pointer; font-size: 1rem; margin-left: 10px; }
        .notification-actions .btn-delete:hover { color: #c20000; }
        .notification-time { font-size: 0.92em; color: #6c757d; margin-top: 5px; }
        .notification-empty { text-align: center; color: #64748b; padding: 40px 20px; }
        .notification-empty i { font-size: 3.5rem; color: #e2e8f0; margin-bottom: 15px; }
        .notification-footer { margin-top: 30px; text-align: center; }
        @media (max-width: 768px) { .container { padding: 15px; } .notification-item { flex-direction: column; } }
    </style>
</head>
<body>
<div class="container">
    <div class="notification-header">
        <h2 class="notification-title">
            <i class="fas fa-bell"></i>
            Notifications
        </h2>
        <div class="notification-count" id="notificationCounter">
            <?= count($notifications) ?>
        </div>
    </div>
    <?php if (count($notifications) == 0): ?>
        <div class="notification-empty">
            <i class="far fa-bell-slash"></i>
            <h4>No notifications yet</h4>
            <p>You'll see important updates here</p>
        </div>
    <?php else: ?>
        <ul class="notification-list" id="notificationList">
            <?php foreach ($notifications as $notif): ?>
                <li class="notification-item" data-id="<?= $notif['id'] ?>">
                    <div class="notification-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-message">
                            <?= htmlspecialchars($notif['message']) ?>
                            <?php if($notif['tenant_name']): ?>
                                <span class="text-primary">(<?= htmlspecialchars($notif['tenant_name']) ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div class="notification-time">
                            <?= date('M j, Y \a\t g:i a', strtotime($notif['created_at'])) ?>
                        </div>
                    </div>
                    <div class="notification-actions">
                        <?php if ($notif['tenant_id']): ?>
                            <a href="messages_admin.php?tenant_id=<?= $notif['tenant_id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye me-1"></i> View
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= $notif['id'] ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <div class="notification-footer">
        <a href="dashboard.php" class="btn btn-outline-primary btn-notification">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
<script>
$(document).ready(function() {
    // Delete notification AJAX
    $('.notification-list').on('click', '.btn-delete', function() {
        if (!confirm('Are you sure you want to delete this notification?')) return;
        var $btn = $(this);
        var $item = $btn.closest('.notification-item');
        var notifId = $btn.data('id');
        $.get('?delete_id=' + notifId + '&ajax=1', function(resp) {
            try {
                var data = JSON.parse(resp);
                if (data.success) {
                    $item.fadeOut(300, function() {
                        $(this).remove();
                        let count = $('.notification-item').length;
                        $('#notificationCounter').text(count);
                    });
                } else {
                    alert('Failed to delete notification.');
                }
            } catch(e) {
                alert('Failed to delete notification.');
            }
        });
    });
});
</script>
</body>
</html>