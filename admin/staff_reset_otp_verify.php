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

// OTP expiration time (10 minutes)
$otp_expiry = 600;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered_otp = trim($_POST['otp']);
    
    // Check if OTP is valid and not expired
    if (time() - $_SESSION['staff_reset_otp_time'] > $otp_expiry) {
        $message = "OTP has expired. Please request a new one.";
        unset($_SESSION['staff_reset_otp']);
        unset($_SESSION['staff_reset_otp_time']);
    } elseif ($entered_otp == $_SESSION['staff_reset_otp']) {
        header("Location: staff_reset_password.php");
        exit();
    } else {
        $message = "Invalid OTP. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify OTP</title>
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
    input[type="text"] {
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
  </style>
</head>
<body>

<div class="container">
  <h2>Verify OTP</h2>
  
  <?php if (!empty($message)): ?>
    <div class="message error"><?= $message ?></div>
  <?php endif; ?>

  <p>We've sent a 6-digit OTP to <?= $_SESSION['staff_reset_email'] ?></p>
  
  <form method="POST">
    <label for="otp">Enter OTP:</label>
    <input type="text" id="otp" name="otp" required maxlength="6">
    
    <button type="submit" class="btn">Verify OTP</button>
  </form>
</div>

</body>
</html>