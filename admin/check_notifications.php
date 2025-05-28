<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$admin_id = $_SESSION['user_id'];
$count = $conn->query("SELECT COUNT(*) as cnt FROM admin_notifications WHERE user_id = $admin_id AND is_read = 0")->fetch_assoc()['cnt'];

header('Content-Type: application/json');
echo json_encode(['new_notifications' => $count]);
?>