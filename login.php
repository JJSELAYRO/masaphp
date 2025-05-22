<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" type="text/css" href="style.css">
  <title>LOGIN</title>
</head>
<body>
  <form method="POST" action="backend/login_user.php">
    <h2>Login Form</h2>
    <label>Email</label>
      <input type="text" name="email" required>
      <label>Password</label>
      <input type="password" name="password" required>
      <p> Don't have an account?
      <a href="signup.php">Sign Up</a></p><br>
      <input type="submit" name="submit" value="Login">
    
  </form>

</body>
</html>
