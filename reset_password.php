<?php
session_start();
include 'db.php';
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $security_answer = mysqli_real_escape_string($conn, $_POST['security_answer']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);

    $query = "SELECT * FROM customers WHERE email='$email' AND security_answer='$security_answer'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $update_query = "UPDATE customers SET password='$new_password' WHERE email='$email'";
        if (mysqli_query($conn, $update_query)) {
            $success = "Password reset successful! You can now login.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    } else {
        $error = "Invalid email or security answer.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password - FastFood Express</title>
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
    .reset-container {
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
    input[type=email], input[type=text], input[type=password] {
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
    .error { color: red; text-align: center; margin-bottom: 10px; }
    .success { color: green; text-align: center; margin-bottom: 10px; }
  </style>
</head>
<body>

<div class="reset-container" data-aos="zoom-in">
  <h2>Reset Password</h2>
  <?php
    if ($error) echo "<div class='error'>$error</div>";
    if ($success) echo "<div class='success'>$success</div>";
  ?>
  <form method="post">
    <input type="email" name="email" placeholder="Your Registered Email" required>
    <input type="text" name="security_answer" placeholder="Security Answer" required>
    <input type="password" name="new_password" placeholder="New Password" required>
    <button type="submit">Reset Password</button>
    <div class="bottom-link">
      Back to <a href="login.php">Login</a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init();</script>

</body>
</html>
