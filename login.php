<?php
session_start();
include 'db.php';

$error = "";
$success = "";

// ✅ 注册成功后的 alert 弹窗
if (isset($_SESSION['registration_success'])) {
    echo "<script>alert('Registration successful! Please login with your credentials.');</script>";
    unset($_SESSION['registration_success']);
}

// ✅ 处理登录表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = "SELECT * FROM customers WHERE email='$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: index_user.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - FastFood Express</title>
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
    .login-container {
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
    label {
      font-weight: bold;
      display: block;
      margin-top: 10px;
    }
    input[type=email], input[type=password] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    .password-toggle {
      float: right;
      font-size: 12px;
      color: #d6001c;
      cursor: pointer;
      margin-top: 5px;
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
      margin-top: 20px;
    }
    .bottom-link {
      text-align: center;
      margin-top: 15px;
    }
    .error {
      color: red;
      text-align: center;
      margin-bottom: 10px;
    }
    .success {
      color: green;
      text-align: center;
      margin-bottom: 10px;
      background: #e8f5e9;
      padding: 10px;
      border-radius: 5px;
    }
  </style>
</head>
<body>

<div class="login-container">
  <h2>Login</h2>
  <?php if ($error) echo "<div class='error'>$error</div>"; ?>
  <?php if ($success) echo "<div class='success'>$success</div>"; ?>
  <form method="post">
    <label>Email</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" id="password" required>
    <span class="password-toggle" onclick="togglePassword()">Show</span>

    <button type="submit">Login</button>
    <div class="bottom-link">
      Don't have an account? <a href="register.php">Register</a><br>
      <a href="reset_password.php">Forgot Password?</a>
    </div>
  </form>
</div>

<script>
function togglePassword() {
  const pwd = document.getElementById("password");
  const toggle = document.querySelector(".password-toggle");
  if (pwd.type === "password") {
    pwd.type = "text";
    toggle.textContent = "Hide";
  } else {
    pwd.type = "password";
    toggle.textContent = "Show";
  }
}
</script>

</body>
</html>
