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
      font-family: Arial;
      background: #fff0f0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }
    .reset-box {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      width: 350px;
    }
    input[type=email], input[type=password] {
      width: 100%;
      padding: 10px;
      margin-top: 10px;
      border: 1px solid #ccc;
      border-radius: 6px;
    }
    .toggle {
      margin-top: -10px;
      margin-bottom: 10px;
      font-size: 13px;
    }
    button {
      width: 100%;
      padding: 12px;
      border: none;
      background: #d6001c;
      color: white;
      font-weight: bold;
      border-radius: 6px;
      margin-top: 10px;
      cursor: pointer;
    }
    .message {
      color: red;
      margin-bottom: 10px;
      text-align: center;
    }
  </style>
</head>
<body>
<div class="reset-box">
  <h2>Reset Password</h2>
  <?php if ($message): ?>
    <div class="message"><?= $message ?></div>
  <?php endif; ?>
  <form method="POST">
    <label>Email</label>
    <input type="email" name="email" required>

    <label>New Password</label>
    <input type="password" name="new_password" id="new_password" required>

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" id="confirm_password" required>

    <div class="toggle">
      <input type="checkbox" onclick="togglePassword()"> Show Password
    </div>

    <button type="submit">Reset Password</button>
  </form>
</div>

<script>
function togglePassword() {
  var np = document.getElementById("new_password");
  var cp = document.getElementById("confirm_password");
  np.type = np.type === "password" ? "text" : "password";
  cp.type = cp.type === "password" ? "text" : "password";
}
</script>
</body>
</html>
