<?php
session_start();
include 'db.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match!";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $new_password)) {
        $message = "Password must be at least 8 characters long, with uppercase, lowercase, and a number.";
    } else {
        $sql = "UPDATE customers SET password='$new_password' WHERE email='$email'";
        if (mysqli_query($conn, $sql)) {
            $message = "Password successfully reset!";
        } else {
            $message = "Error updating password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <style>
    body {
      background: #ffeeee;
      font-family: Arial, sans-serif;
    }
    .reset-container {
      background: white;
      width: 400px;
      margin: 100px auto;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    h2 {
      margin-bottom: 25px;
      color: #000;
    }
    label {
      font-weight: bold;
      display: block;
      margin-top: 15px;
    }
    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }
    .checkbox {
      margin-top: 10px;
    }
    .reset-btn {
      background: #d6001c;
      color: white;
      padding: 12px;
      width: 100%;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      font-size: 16px;
      margin-top: 20px;
      cursor: pointer;
    }
    .reset-btn:hover {
      background: #a50013;
    }
    .back-login {
      text-align: center;
      margin-top: 10px;
    }
    .back-login a {
      color: #d6001c;
      text-decoration: none;
    }
    .message {
      text-align: center;
      margin-bottom: 10px;
      color: #d6001c;
    }
  </style>
</head>
<body>

<div class="reset-container">
  <h2>Reset Password</h2>
  <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>
  <form method="POST" action="">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>

    <label for="new_password">New Password</label>
    <input type="password" id="new_password" name="new_password" required>

    <label for="confirm_password">Confirm Password</label>
    <input type="password" id="confirm_password" name="confirm_password" required>

    <div class="checkbox">
      <input type="checkbox" id="show_password" onclick="togglePassword()"> Show Password
    </div>

    <button type="submit" class="reset-btn">Reset Password</button>

    <div class="back-login">
      <a href="login.php">← Back to Login</a>
    </div>
  </form>
</div>

<script>
  function togglePassword() {
    const newPass = document.getElementById('new_password');
    const confirmPass = document.getElementById('confirm_password');
    const type = newPass.type === "password" ? "text" : "password";
    newPass.type = type;
    confirmPass.type = type;
  }
</script>

</body>
</html>