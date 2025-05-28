<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header('Location: ../public/login.php');
    exit();
}

$tenant_id = $_SESSION['user_id'];

// Fetch tenant info
$stmt = $conn->prepare("SELECT name, email, phone, contact, address, username FROM tenants WHERE id=?");
$stmt->bind_param("i", $tenant_id);
$stmt->execute();
$tenant = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form POST
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check for unique username if changed
    if ($username !== $tenant['username']) {
        $check = $conn->prepare("SELECT id FROM tenants WHERE username=? AND id!=?");
        $check->bind_param("si", $username, $tenant_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "Username already taken. Please choose another.";
        }
        $check->close();
    }

    if (!$error) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE tenants SET name=?, phone=?, contact=?, address=?, username=?, password=? WHERE id=?");
            $stmt->bind_param("ssssssi", $name, $phone, $contact, $address, $username, $hash, $tenant_id);
        } else {
            $stmt = $conn->prepare("UPDATE tenants SET name=?, phone=?, contact=?, address=?, username=? WHERE id=?");
            $stmt->bind_param("sssssi", $name, $phone, $contact, $address, $username, $tenant_id);
        }
        if ($stmt->execute()) {
            $success = true;
            // Refresh tenant data after update
            $stmt->close();
            $stmt = $conn->prepare("SELECT name, email, phone, contact, address, username FROM tenants WHERE id=?");
            $stmt->bind_param("i", $tenant_id);
            $stmt->execute();
            $tenant = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile | PropertyPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-light: #e0e7ff;
            --secondary: #3f37c9;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray: #64748b;
            --light-gray: #e2e8f0;
            --border-radius: 12px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light);
            color: var(--dark);
        }

        .container {
            max-width: 600px;
            padding: 30px;
            margin: 50px auto;
            animation: fadeInUp 0.6s ease;
        }

        @keyframes fadeInUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .card {
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: none;
            padding: 30px;
            background-color: white;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
        }

        input.form-control {
            border-radius: var(--border-radius);
            border: 1px solid var(--light-gray);
            transition: all 0.3s;
        }

        input.form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }

        .btn-primary {
            background-color: var(--primary);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 500;
            padding: 10px;
        }

        .btn-primary:hover {
            background-color: var(--secondary);
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }

        .alert {
            border-radius: var(--border-radius);
        }

        h2 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>

<div class="topbar">
    <a href="dashboard.php" class="back-btn">
        <i class="bi bi-arrow-left-circle-fill"></i> Back
    </a>

<div class="container">
    <div class="card">
        <h2>Edit Profile</h2>
        <?php if ($success): ?>
            <div class="alert alert-success">Profile updated successfully.</div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($tenant['name']) ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($tenant['phone']) ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact" value="<?= htmlspecialchars($tenant['contact']) ?>" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="address" value="<?= htmlspecialchars($tenant['address']) ?>" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($tenant['username']) ?>" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Save Changes</button>
        </form>
    </div>
</div>

</body>
</html>
