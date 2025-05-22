<?php
include 'conn.php';
session_start();

if (isset($_POST['submit'])) {
    // Get input
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query to verify user by email
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    // Check if user exists
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Verify password of the user
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['id'] = $user['id'];
            
            // Redirect to dashboard
            header("Location: ../index.php");
            exit();
        } else {
            echo "
            <script>
                alert('Incorrect password.');
                window.location.href = '../login.php';
            </script>
            ";
        }
    } else {
        echo "
        <script>
            alert('No user found with that email.');
            window.location.href = '../login.php';
        </script>
        ";
    }
} else {
    echo "
    <script>
        alert('Login failed. Please try again.');
        window.location.href = '../login.php';
    </script>
    ";
}
?>



