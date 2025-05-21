<?php
session_start();
include 'db.php';

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $username  = mysqli_real_escape_string($conn, $_POST['username']);
    $phone     = mysqli_real_escape_string($conn, $_POST['phone']);
    $password  = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm   = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    // 验证邮箱格式
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    }
    // 手机号验证
    elseif (!preg_match('/^01[0-9]{8,9}$/', $phone)) {
        $error = "Phone number must start with 01 and be 10–11 digits.";
    }
    // 密码强度验证
    elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $error = "Password must be at least 8 characters with uppercase, lowercase, and number.";
    }
    // 确认密码匹配
    elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    }
    // 检查 email 或 phone 是否已存在
    else {
        $check = mysqli_query($conn, "SELECT * FROM customers WHERE email='$email' OR phone='$phone'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email or phone number already used.";
        } else {
            $insert = "INSERT INTO customers (email, username, phone, password) VALUES ('$email', '$username', '$phone', '$password')";
            if (mysqli_query($conn, $insert)) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - FastFood Express</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #fff0f0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .register-container {
      background: white;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      width: 350px;
    }
    h2 {
      color: #d6001c;
      text-align: center;
      margin-bottom: 20px;
    }
    input[type=text], input[type=email], input[type=password] {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    button {
      background: #d6001c;
      color: white;
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
    }
    .bottom-link {
      text-align: center;
      margin-top: 15px;
    }
    .error { color: red; text-align: center; margin-bottom: 10px; }
    .success { color: green; text-align: center; margin-bottom: 10px; }
    .toggle-password {
      float: right;
      margin-right: 10px;
      margin-top: -30px;
      position: relative;
      z-index: 2;
      cursor: pointer;
      font-size: 12px;
      color: #555;
    }
  </style>
</head>
<body>

<div class="register-container" data-aos="zoom-in">
  <h2>Register</h2>
  <?php if ($error) echo "<div class='error'>$error</div>"; ?>
  <?php if ($success) echo "<div class='success'>$success</div>"; ?>
  <form method="post">
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="username" placeholder="User ID" required>
    <input type="text" name="phone" placeholder="Phone Number" required>
    
    <input type="password" name="password" id="password" placeholder="Password" required>
    <span class="toggle-password" onclick="togglePassword('password')">Show</span>
    
    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
    <span class="toggle-password" onclick="togglePassword('confirm_password')">Show</span>

    <button type="submit">Register</button>
    <div class="bottom-link">Already have an account? <a href="login.php">Login</a></div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init();</script>

<script>
function togglePassword(id) {
  const input = document.getElementById(id);
  const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
  input.setAttribute('type', type);
}
</script>

</body>
</html>
