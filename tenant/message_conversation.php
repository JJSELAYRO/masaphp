<?php
session_start();
require_once '../config/db.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// For admin viewing a specific tenant's messages
$tenant_id = null;
if ($role === 'admin' && isset($_GET['tenant_id'])) {
    $tenant_id = intval($_GET['tenant_id']);
    
    // Verify tenant exists
    $stmt = $conn->prepare("SELECT id, name FROM tenants WHERE id = ?");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $tenant_result = $stmt->get_result();
    
    if ($tenant_result->num_rows === 0) {
        $_SESSION['error'] = "Tenant not found";
        header("Location: messages_admin_list.php");
        exit();
    }
    
    $tenant = $tenant_result->fetch_assoc();
    $stmt->close();
} elseif ($role === 'tenant') {
    $tenant_id = $user_id;
}

// Fetch chat history
$messages = [];
if ($tenant_id) {
    $query = "
        SELECT m.id, m.message, m.created_at, m.is_from_admin
        FROM messages m
        WHERE m.tenant_id = ?
        ORDER BY m.created_at ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $row['sender'] = $row['is_from_admin'] ? 'admin' : 'tenant';
        $messages[] = $row;
    }
    $stmt->close();
}

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $tenant_id) {
    $message = trim($_POST['message']);
    
    if (!empty($message)) {
        $is_from_admin = ($role === 'admin') ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO messages (tenant_id, message, is_from_admin, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param('isi', $tenant_id, $message, $is_from_admin);
        
        if ($stmt->execute()) {
            // Create notification for the recipient
            $notification_message = "New message received";
            $notification_type = "message";
            
            if ($role === 'admin') {
                // Notify tenant
                $stmt_notif = $conn->prepare("INSERT INTO tenant_notifications (tenant_id, message, type, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
                $stmt_notif->bind_param("iss", $tenant_id, $notification_message, $notification_type);
            } else {
                // Notify all admins
                $stmt_notif = $conn->prepare("INSERT INTO admin_notifications (user_id, tenant_id, message, type, is_read, created_at) 
                                            SELECT id, ?, ?, ?, 0, NOW() FROM users WHERE role = 'admin'");
                $stmt_notif->bind_param("iss", $tenant_id, $notification_message, $notification_type);
            }
            
            $stmt_notif->execute();
            $stmt_notif->close();
            
            $_SESSION['success'] = "Message sent successfully";
        } else {
            $_SESSION['error'] = "Failed to send message";
        }
        
        $stmt->close();
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ($role === 'admin' ? 'Tenant Messages' : 'Admin Messages') ?> | PropertyPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            line-height: 1.6;
        }
        .topbar {
            background-color: white;
            box-shadow: var(--shadow-sm);
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar h1 {
            font-size: 1.5rem;
            color: var(--primary);
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .back-btn {
            color: var(--primary);
            text-decoration: none;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            font-weight: 500;
        }
        .back-btn:hover {
            color: var(--secondary);
            transform: translateX(-3px);
        }
        .container {
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease;
        }
        @keyframes fadeInUp {
            from { transform: translateY(20px); opacity: 0; }
            to   { transform: translateY(0); opacity: 1; }
        }
        .chat-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            overflow: hidden;
            height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
        }
        .chat-header {
            padding: 15px 20px;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-header .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .chat-header .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-weight: bold;
        }
        .messages-area {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f5f7fb;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .message-row {
            display: flex;
            align-items: flex-end;
            margin-bottom: 8px;
        }
        .message-bubble {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px);}
            to   { opacity: 1; transform: translateY(0);}
        }
        .message-row.tenant {
            justify-content: flex-end;
        }
        .message-row.tenant .message-bubble {
            background-color: var(--primary);
            color: white;
            border-bottom-right-radius: 4px;
            border-bottom-left-radius: 18px;
        }
        .message-row.admin {
            justify-content: flex-start;
        }
        .message-row.admin .message-bubble {
            background-color: white;
            color: var(--dark);
            border-bottom-left-radius: 4px;
            border-bottom-right-radius: 18px;
            box-shadow: var(--shadow-sm);
        }
        .message-user-label {
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 2px;
            color: var(--gray);
        }
        .message-time {
            font-size: 0.75rem;
            margin-top: 6px;
            opacity: 0.9;
            text-align: right;
            display: block;
        }
        .message-row.tenant .message-time { color: rgba(255,255,255,.8);}
        .message-row.admin .message-time { color: var(--gray);}
        .message-input {
            padding: 15px;
            background-color: white;
            border-top: 1px solid var(--light-gray);
            display: flex;
            gap: 10px;
        }
        .message-input textarea {
            flex: 1;
            border: 1px solid var(--light-gray);
            border-radius: var(--border-radius);
            padding: 12px 15px;
            resize: none;
            font-family: inherit;
            transition: all 0.3s;
        }
        .message-input textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
        }
        .message-input button {
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 0 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .message-input button:hover {
            background-color: var(--secondary);
        }
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--gray);
            text-align: center;
            padding: 30px;
        }
        .empty-state i {
            font-size: 3rem;
            color: var(--light-gray);
            margin-bottom: 15px;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
        .toast {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        @media (max-width: 768px) {
            .topbar { padding: 15px;}
            .topbar h1 { font-size: 1.2rem;}
            .container { padding: 15px;}
            .chat-container { height: calc(100vh - 160px);}
            .message-bubble { max-width: 85%;}
        }
    </style>
</head>
<body>
    <!-- Top Navigation Bar -->
    <div class="topbar">
        <a href="<?= ($role === 'admin' ? 'messages_admin_list.php' : 'dashboard.php') ?>" class="back-btn">
            <i class="bi bi-arrow-left-circle-fill"></i> Back
        </a>
        <h1><i class="bi bi-chat-left-text"></i> <?= ($role === 'admin' ? 'Tenant Messages' : 'Admin Messages') ?></h1>
    </div>
    <!-- Toast Notifications -->
    <div class="toast-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto">Success</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <?= $_SESSION['success'] ?>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-danger text-white">
                    <strong class="me-auto">Error</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <?= $_SESSION['error'] ?>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </div>
    <!-- Main Container -->
    <div class="container">
        <div class="chat-container">
            <div class="chat-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <?= ($role === 'admin' ? 'T' : 'A') ?>
                    </div>
                    <div>
                        <h6 class="mb-0">
                            <?php if ($role === 'admin'): ?>
                                <?= isset($tenant) ? htmlspecialchars($tenant['name']) : 'Select a Tenant' ?>
                            <?php else: ?>
                                PropertyPro Admin
                            <?php endif; ?>
                        </h6>
                        <small class="opacity-75">
                            <?php if ($role === 'admin'): ?>
                                <?= isset($tenant) ? 'Messaging tenant' : 'Select a tenant to message' ?>
                            <?php else: ?>
                                Messaging property admin
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
            <div class="messages-area" id="messagesArea">
                <?php if (empty($messages)): ?>
                    <div class="empty-state">
                        <i class="bi bi-chat-square-text"></i>
                        <h5>No messages yet</h5>
                        <p>
                            <?php if ($role === 'admin' && !isset($tenant)): ?>
                                Select a tenant to start messaging
                            <?php else: ?>
                                Start the conversation by sending your first message
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message-row <?= $message['sender'] === 'admin' ? 'admin' : 'tenant' ?>">
                            <?php if ($message['sender'] === 'admin'): ?>
                                <div>
                                    <div class="message-user-label mb-1">
                                        <i class="bi bi-person-fill"></i> Admin
                                    </div>
                                    <div class="message-bubble">
                                        <?= nl2br(htmlspecialchars($message['message'])) ?>
                                        <span class="message-time">
                                            <?= date('h:i A | M j', strtotime($message['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="margin-left:auto;">
                                    <div class="message-user-label mb-1 text-end">
                                        <i class="bi bi-person-fill"></i> Tenant
                                    </div>
                                    <div class="message-bubble">
                                        <?= nl2br(htmlspecialchars($message['message'])) ?>
                                        <span class="message-time">
                                            <?= date('h:i A | M j', strtotime($message['created_at'])) ?>
                                            <i class="bi bi-check2-all ms-1"></i>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($tenant_id): ?>
                <form method="POST" class="message-input">
                    <textarea name="message" placeholder="Type your message..." rows="1" required></textarea>
                    <button type="submit">
                        <i class="bi bi-send-fill"></i> Send
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap toasts
            const toastElList = [].slice.call(document.querySelectorAll('.toast'));
            const toastList = toastElList.map(function(toastEl) {
                return new bootstrap.Toast(toastEl, { autohide: true, delay: 5000 });
            });
            toastList.forEach(toast => toast.show());
            // Auto-resize textarea
            const messagesArea = document.getElementById('messagesArea');
            const messageForm = document.querySelector('.message-input');
            const textarea = messageForm?.querySelector('textarea');
            function scrollToBottom() {
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }
            scrollToBottom();
            if (textarea) {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
                messageForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const message = formData.get('message').trim();
                    if (message === '') return;
                    // Create temporary message for immediate UI feedback
                    const tempMsgRow = document.createElement('div');
                    tempMsgRow.className = 'message-row tenant';
                    tempMsgRow.innerHTML = `
                        <div style="margin-left:auto;">
                            <div class="message-user-label mb-1 text-end">
                                <i class="bi bi-person-fill"></i> Tenant
                            </div>
                            <div class="message-bubble">
                                ${message.replace(/\n/g, '<br>')}
                                <span class="message-time">
                                    Just now <i class="bi bi-check2-all ms-1"></i>
                                </span>
                            </div>
                        </div>
                    `;
                    // If empty state exists, replace it
                    const emptyState = messagesArea.querySelector('.empty-state');
                    if (emptyState) {
                        messagesArea.innerHTML = '';
                    }
                    messagesArea.appendChild(tempMsgRow);
                    scrollToBottom();
                    // Clear and reset textarea
                    textarea.value = '';
                    textarea.style.height = 'auto';
                    // Send message via AJAX
                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'alert alert-danger mt-2';
                        errorMsg.textContent = 'Failed to send message. Please try again.';
                        messageForm.parentNode.insertBefore(errorMsg, messageForm.nextSibling);
                        setTimeout(() => { errorMsg.remove(); }, 3000);
                    });
                });
            }
            // Check for new messages every 30 seconds if in a conversation
            <?php if ($tenant_id): ?>
                setInterval(() => {
                    fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newMessages = doc.querySelectorAll('#messagesArea .message-row');
                        if (newMessages.length > document.querySelectorAll('#messagesArea .message-row').length) {
                            window.location.reload();
                        }
                    });
                }, 30000);
            <?php endif; ?>
        });
    </script>
</body>
</html>