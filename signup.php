<!DOCTYPE html>
<html>
<head>
   <meta charset="utf-8">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <link rel="stylesheet" type="text/css" href="style.css">
   <title>SIGNUP</title>
</head>
<body>
   <form method="POST" action="backend/insert_user.php">
      <h2>Signup Form</h2>

      <label>Fullname</label>
      <input type="text" name="fullname" required>

      <label>Email</label>
      <input type="email" name="email" required>

      <label>Address</label>
      <input type="text" name="address" required>

      <label>Gender</label><br>

      <label class="radio-option">
      <input type="radio" name="gender" value="Male" required> Male
      </label>

      <label class="radio-option">
      <input type="radio" name="gender" value="Female" required> Female
      </label> <br>

      <label>Contact Number</label>
      <input type="text" name="contactnum" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Confirm Password</label>
      <input type="password" name="confirm_password" required>

      <p> Already have an account?
      <a href="login.php">Login</a></p><br>
      <input type="submit" name="submit" value="Sign Up">

   </form>
</body>
</html>
