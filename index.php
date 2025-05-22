<?php
include 'backend/conn.php';
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
}

$fullname = $_SESSION['fullname'];
$email = $_SESSION['email'];
$sql = "SELECT id, fullname, email, address, gender, contact FROM users WHERE email != '$email'";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="icon" type="image" href="logo.png">
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h2>Welcome, <?php echo $fullname; ?>!</h2>
        </div>
        <div class="header-buttons">
            <a class="action-btn edit-btn" href="edit_profile.php">Edit Profile</a>
            <a class="logout-btn" href="backend/logout_user.php"
                onclick="return confirm('Are you sure you want to Logout?');"
            >Logout</a>
        </div>
    </div>
    <h3 class="table-title">List of Users</h3>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Fullname</th>
            <th>Email</th>
            <th>Address</th>
            <th>Gender</th>
            <th>Contact</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $counter = 1;
         while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $counter++; ?></td>
                <td><?php echo $row['fullname']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['address']; ?></td>
                <td><?php echo $row['gender']; ?></td>
                <td><?php echo $row['contact']; ?></td>
                <td>
                <div class="action-container">
                    <a class="action-btn edit-btn" href="edit_user.php?id=<?php echo $row['id']; ?>">Edit</a>
                    <a class="action-btn delete-btn" href="backend/delete_user.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                </div>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>