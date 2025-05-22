s      <?php
include 'conn.php';
if (isset($_POST['submit'])) {
    // assign form data to variables
    $fullname   = $_POST['fullname'];
    $email      = $_POST['email'];
    $address    = $_POST['address'];
    $gender     = $_POST['gender'];
    $contact    = $_POST['contactnum'];
    $password   = $_POST['password'];
    $cpassword  = $_POST['confirm_password'];

    // check if passwords match
    if ($password != $cpassword) {
        echo "
        <script>
            alert('Password and Confirm Password must match.');
            window.location.href = '../signup.php';
        </script>
        ";
        exit();
    }
    // check if email already in databse
    $checkEmail = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $checkEmail);

    if (mysqli_num_rows($result) > 0) {
        echo "
        <script>
            alert('Email already exists. Please use a different email.');
            window.location.href = '../signup.php';
        </script>
        ";
        exit();
    }

    // hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // insert user into databasse
    $sql = "INSERT INTO users (fullname, email, address, gender, contact, password)
            VALUES ('$fullname', '$email', '$address', '$gender', '$contact', '$hashedPassword')";

    if (mysqli_query($conn, $sql)) {
        echo "
        <script>
            alert('User Registered Successfully!');
            window.location.href = '../signup.php';
        </script>
        ";
    } else {
        echo "Error: " . mysqli_error($conn);
    }

} else {
    echo "
    <script>
        alert('Signup failed. Please try again.');
        window.location.href = '../signup.php';
    </script>
    ";
}

?>




