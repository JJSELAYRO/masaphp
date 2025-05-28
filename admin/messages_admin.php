<?php
session_start();
require_once '../config/db.php';

// ✅ Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit();
}

if (!isset($_GET['tenant_id'])) {
    die("No tenant specified.");
}

$tenant_id = intval($_GET['tenant_id']);
$admin_id = $_SESSION['user_id'];

// ✅ Mark tenant messages as read
$conn->query("
    UPDATE admin_notifications 
    SET is_read = 1 
    WHERE user_id = $admin_id 
    AND tenant_id = $tenant_id 
    AND type = 'message'
");

// ✅ Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg = trim($_POST['message']);
    if ($msg !== '') {
        // Save message
        $stmt = $conn->prepare("INSERT INTO messages (tenant_id, sender, message) VALUES (?, 'admin', ?)");
        $stmt->bind_param("is", $tenant_id, $msg);
        $stmt->execute();
        $stmt->close();

        // Notify tenant
        $notif_msg = "You have a new message from the admin.";
        $stmt2 = $conn->prepare("INSERT INTO notifications (tenant_id, message, type, is_read) VALUES (?, ?, 'message', 0)");
        $stmt2->bind_param("is", $tenant_id, $notif_msg);
        $stmt2->execute();
        $stmt2->close();
    }
    header("Location: messages_admin.php?tenant_id=$tenant_id");
    exit();
}

// ✅ Get tenant name
$stmt = $conn->prepare("SELECT name FROM tenants WHERE id = ?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$stmt->bind_result($tenant_name);
$stmt->fetch();
$stmt->close();

// ✅ Fetch all messages with this tenant
$stmt = $conn->prepare("SELECT sender, message, created_at FROM messages WHERE tenant_id = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages with <?= htmlspecialchars($tenant_name) ?> | Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .chat-container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            padding: 30px;
        }
        .chat-history {
            height: 320px;
            overflow-y: auto;
            margin-bottom: 25px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }
        .chat-bubble {
            margin-bottom: 16px;
            padding: 10px 16px;
            border-radius: 16px;
            max-width: 80%;
            clear: both;
        }
        .chat-bubble.tenant {
            background: #e9f0ff;
            color: #263159;
            margin-left: auto;
            text-align: right;
        }
        .chat-bubble.admin {
            background: #f1f3f4;
            color: #222;
            margin-right: auto;
            text-align: left;
        }
        .chat-meta {
            font-size: 0.85em;
            color: #8a8a8a;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<div class="chat-container">
    <h3 class="mb-4 text-primary">Messages with <?= htmlspecialchars($tenant_name) ?></h3>
    <div class="chat-history" id="chatHistory">
        <?php if (count($messages) === 0): ?>
            <div class="text-center text-muted mt-4">No messages yet. Start the conversation!</div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="chat-bubble <?= $msg['sender'] === 'tenant' ? 'tenant' : 'admin' ?>">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    <div class="chat-meta">
                        <small>
                            <?= ucfirst($msg['sender']) ?> &middot; <?= date('M j, Y H:i', strtotime($msg['created_at'])) ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <form method="post" autocomplete="off">
        <div class="mb-3">
            <textarea name="message" class="form-control" placeholder="Type your message..." required rows="3"></textarea>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-primary">Send</button>
        </div>
    </form>
    <div class="mt-3">
        <a href="messages_admin_list.php" class="btn btn-link">&larr; Back to Tenants</a>
    </div>
</div>
<script>
    // Scroll chat to bottom on load
    var chatHistory = document.getElementById('chatHistory');
    if (chatHistory) {
        chatHistory.scrollTop = chatHistory.scrollHeight;
    }
</script>
</body>
</html>