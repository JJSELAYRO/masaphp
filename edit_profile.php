<?php
include 'backend/conn.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
$id = isset($_GET['id']) ? $_GET['id'] : $_SESSION['id'];


// Fetch user details based on the ID
$sql = "SELECT * FROM users WHERE id = '$id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo "User not found.";
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <link rel="stylesheet" type="text/css" href="style.css">
    <title>Edit User</title>
</head>
<body>

<!-- Form for editing profile -->
<form method="POST" action="backend/update_user.php?id=<?php echo $user['id']; ?>"> 
    <h2>Edit User</h2>

    <label>Fullname</label>
    <input type="text" name="fullname" value="<?php echo $user['fullname']; ?>" required>

    <label>Email</label>
    <input type="email" name="email" value="<?php echo $user['email']; ?>" required>

    <label>Address</label>
    <input type="text" name="address" value="<?php echo $user['address']; ?>" required>

    <label>Gender</label><br>
    <label>
        <input type="radio" name="gender" value="Male" <?php echo ($user['gender'] == 'Male') ? 'checked' : ''; ?> required> Male
    </label>
    <label>
        <input type="radio" name="gender" value="Female" <?php echo ($user['gender'] == 'Female') ? 'checked' : ''; ?> required> Female
    </label><br>

    <label>Contact Number</label>
    <input type="text" name="contact" value="<?php echo $user['contact']; ?>" required>

    

    <input type="submit" name="submit" value="Update">
    <a href="index.php" class="cancel-btn">Cancel</a>
</form>

</body>
</html>