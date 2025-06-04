<?php
session_start();
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'fyp_fastfood'; // 确保与你 phpMyAdmin 的数据库名一致

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Check if email is in session
if (!isset($_SESSION['staff_reset_email'])) {
    header("Location: staff_forgot_password.php");
    exit();
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $message = "Password must be at least 8 characters long.";
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $email = $_SESSION['staff_reset_email'];
        
        // Update password in database
        $query = "UPDATE staff SET password = '$hashed_password' WHERE email = '$email'";
        if (mysqli_query($conn, $query)) {
            // Clear session variables
            unset($_SESSION['staff_reset_email']);
            unset($_SESSION['staff_reset_otp']);
            unset($_SESSION['staff_reset_otp_time']);
            
            $message = "Password updated successfully. You can now login with your new password.";
            header("refresh:3; url=newadminlogin.php");
        } else {
            $message = "Error updating password. Please try again.";
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
      background-color: #f5f5f5;
      font-family: Arial, sans-serif;
    }
    .container {
      max-width: 400px;
      margin: 100px auto;
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      color: #d6001c;
      margin-bottom: 20px;
    }
    label {
      font-weight: bold;
      margin-top: 10px;
      display: block;
    }
    input[type="password"] {
      width: 100%;
      padding: 12px;
      border-radius: 6px;
      border: 1px solid #ccc;
      margin-top: 8px;
      font-size: 14px;
    }
    .btn {
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
    .btn:hover {
      background: #b40000;
    }
    .message {
      text-align: center;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 15px;
    }
    .error {
      background-color: #fff0f0;
      color: #d6001c;
    }
    .success {
      background-color: #e7fff0;
      color: #0a8a42;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>Reset Password</h2>
  
  <?php if (!empty($message)): ?>
    <div class="message <?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>"><?= $message ?></div>
  <?php endif; ?>

  <form method="POST">
    <label for="password">New Password:</label>
    <input type="password" id="password" name="password" required minlength="8">
    
    <label for="confirm_password">Confirm New Password:</label>
    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
    
    <button type="submit" class="btn">Reset Password</button>
  </form>
</div>

</body>
</html>