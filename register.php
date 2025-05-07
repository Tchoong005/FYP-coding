<?php
session_start();
include 'db.php';
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $hometown = mysqli_real_escape_string($conn, $_POST['hometown']);

    // 手机号格式验证
    if (!preg_match('/^01\d{8,9}$/', $phone)) {
        $error = "Phone number must start with 01 and have 10-11 digits.";
    } else {
        // 检查 email 是否已存在
        $check_query_email = "SELECT * FROM customers WHERE email='$email'";
        $check_result_email = mysqli_query($conn, $check_query_email);

        // 检查 phone 是否已存在
        $check_query_phone = "SELECT * FROM customers WHERE phone='$phone'";
        $check_result_phone = mysqli_query($conn, $check_query_phone);

        if (mysqli_num_rows($check_result_email) > 0) {
            $error = "Email already registered.";
        } elseif (mysqli_num_rows($check_result_phone) > 0) {
            $error = "Phone number already registered.";
        } else {
            $query = "INSERT INTO customers (username, email, phone, password, hometown) VALUES ('$user_id', '$email', '$phone', '$password', '$hometown')";
            if (mysqli_query($conn, $query)) {
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
    label {
      font-weight: bold;
      color: #d6001c;
      margin-top: 10px;
      display: block;
    }
  </style>
</head>
<body>

<div class="register-container" data-aos="zoom-in">
  <h2>Register</h2>
  <?php
    if ($error) echo "<div class='error'>$error</div>";
    if ($success) echo "<div class='success'>$success</div>";
  ?>
  <form method="post">
    <input type="text" name="user_id" placeholder="User ID" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="phone" placeholder="Phone Number (10-11 digits, starts with 01)" pattern="01\d{8,9}" required>
    <input type="password" name="password" placeholder="Password" required>
    <input type="text" name="hometown" placeholder="Where is your hometown?" required>
    <button type="submit">Register</button>
    <div class="bottom-link">Already have an account? <a href="login.php">Login</a></div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init();</script>

</body>
</html>
