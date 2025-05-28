<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../public/login.php');
    exit();
}

// Check if we're viewing a specific tenant's messages
$current_tenant = null;
if (isset($_GET['tenant_id'])) {
    $tenant_id = intval($_GET['tenant_id']);
    $result = $conn->query("SELECT id, name, email FROM tenants WHERE id = $tenant_id");
    if ($result && $result->num_rows > 0) {
        $current_tenant = $result->fetch_assoc();
    }
}

// Fetch all tenants
$tenants = [];
$result = $conn->query("SELECT id, name, email FROM tenants ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $tenants[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Messages | PropertyPro Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
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
            max-width: 1200px;
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
        
        h3 {
            margin-top: 0;
            color: var(--primary);
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }
        
        h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 4px;
            background: var(--primary);
            border-radius: 2px;
        }
        
        .back-button {
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            transform: translateX(-3px);
        }
        
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            margin-bottom: 25px;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .tenant-card {
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }
        
        .tenant-card:hover {
            transform: translateX(5px);
            border-left-color: var(--secondary);
        }
        
        .tenant-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .message-btn {
            transition: all 0.2s;
        }
        
        .message-btn:hover {
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
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .section-divider {
            margin: 30px 0;
            border: 0;
            height: 1px;
            background-image: linear-gradient(to right, rgba(67, 97, 238, 0.1), rgba(67, 97, 238, 0.6), rgba(67, 97, 238, 0.1));
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-box input {
            padding-left: 40px;
            border-radius: 50px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 12px;
            color: var(--gray);
        }
        
        /* Message conversation styles */
        .message-container {
            height: 500px;
            overflow-y: auto;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        
        .empty-message-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            color: var(--gray);
        }
        
        .empty-message-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--light-gray);
        }
        
        .message-input-container {
            display: flex;
            gap: 10px;
        }
        
        .message-input {
            flex: 1;
            border-radius: 50px;
            padding: 12px 20px;
            border: 1px solid var(--light-gray);
            transition: all 0.3s;
        }
        
        .message-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
            outline: none;
        }
        
        .send-btn {
            border-radius: 50px;
            padding: 0 25px;
            background-color: var(--primary);
            color: white;
            border: none;
            transition: all 0.3s;
        }
        
        .send-btn:hover {
            background-color: var(--secondary);
        }
        
        .conversation-header {
            display: flex;
            align-items: center;
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .conversation-back-btn {
            margin-right: 15px;
            color: var(--primary);
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
<div class="container fade-in">
    <?php if ($current_tenant): ?>
        <!-- Conversation View -->
        <div class="conversation-header">
            <a href="messages_admin.php" class="conversation-back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="d-flex align-items-center">
                <div class="tenant-avatar me-3">
                    <?= strtoupper(substr($current_tenant['name'], 0, 1)) ?>
                </div>
                <div>
                    <h5 class="mb-1"><?= htmlspecialchars($current_tenant['name']) ?></h5>
                    <small class="text-muted"><?= htmlspecialchars($current_tenant['email']) ?></small>
                </div>
            </div>
        </div>
        
        <div class="message-container">
            <div class="empty-message-state">
                <i class="far fa-comment-dots"></i>
                <h4>No messages yet</h4>
                <p>Start the conversation!</p>
            </div>
        </div>
        
        <div class="message-input-container">
            <input type="text" class="form-control message-input" placeholder="Type your message...">
            <button class="btn send-btn">Send</button>
        </div>
        
        <div class="mt-3">
            <a href="messages_admin.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Tenants
            </a>
        </div>
    <?php else: ?>
        <!-- Tenant List View -->
        <a href="dashboard.php" class="back-button btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
        
        <h3><i class="fa-solid fa-comments me-2"></i>Tenant Messages</h3>
        
        <?php if(isset($_SESSION['flash_message'])): ?>
            <div class="flash-message alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['flash_message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Search tenants..." id="tenantSearch">
                </div>
                
                <?php if (empty($tenants)): ?>
                    <div class="alert alert-info">No tenants found.</div>
                <?php else: ?>
                    <div class="row g-3" id="tenantList">
                        <?php foreach ($tenants as $tenant): ?>
                        <div class="col-md-6 fade-in">
                            <div class="card tenant-card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="tenant-avatar me-3">
                                                <?= strtoupper(substr($tenant['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <h5 class="mb-1"><?= htmlspecialchars($tenant['name']) ?></h5>
                                                <small class="text-muted"><?= htmlspecialchars($tenant['email']) ?></small>
                                            </div>
                                        </div>
                                        <a href="messages_admin.php?tenant_id=<?= $tenant['id'] ?>" 
                                           class="message-btn btn btn-primary btn-sm rounded-pill">
                                            <i class="fa-solid fa-envelope me-1"></i> Message
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>

<script>
    $(document).ready(function() {
        // Search functionality
        $('#tenantSearch').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('#tenantList .col-md-6').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(searchTerm) > -1);
            });
        });
        
        // Close flash message after 3 seconds
        const flashMessage = $('.flash-message');
        if (flashMessage.length) {
            setTimeout(() => {
                flashMessage.alert('close');
            }, 3000);
        }
        
        // Add animation to tenant cards
        const cards = document.querySelectorAll('#tenantList .col-md-6');
        cards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.05}s`;
            card.classList.add('fade-in');
        });
    });
</script>
</body>
</html>