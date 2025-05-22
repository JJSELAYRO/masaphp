<?php
include 'conn.php';
session_start();

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
} else {
    echo "Invalid request.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $address = $_POST['address'];
    $gender = $_POST['gender'];
    $contact = $_POST['contact'];
    $password = $_POST['password'];

    // Check for duplicate email excluding current user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Duplicate email found
        echo "
        <script>
            alert('This email is already taken by another account.');
            window.history.back();
        </script>
        ";
        $stmt->close();
        exit();
    }
    $stmt->close();

    // Handle password hashing or keep old password
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    } else {
        $user_query = "SELECT password FROM users WHERE id = ?";
        $user_stmt = $conn->prepare($user_query);
        $user_stmt->bind_param("i", $id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user_data = $user_result->fetch_assoc();
        $hashedPassword = $user_data['password'];
        $user_stmt->close();
    }

    // Update user record
    $update_sql = "UPDATE users SET fullname = ?, email = ?, address = ?, gender = ?, contact = ?, password = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssssssi", $fullname, $email, $address, $gender, $contact, $hashedPassword, $id);

    if ($stmt->execute()) {

        if ($id == $_SESSION['id']) {
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
        } 

        echo "
        <script>
            alert('User updated successfully!');
            window.location.href = '../index.php';
        </script>
        ";
        $stmt->close();
        exit();
    } else {
        echo "Error updating record: " . $stmt->error;
    }
}
?>
