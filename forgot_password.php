<?php
session_start();
include 'db.php';

$message = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // 检查 email 是否存在
    $query = "SELECT * FROM customers WHERE email = '$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        // 生成 OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_otp_time'] = time();

        // 发送 OTP 邮件
        require 'PHPMailer/src/PHPMailer.php';
        require 'PHPMailer/src/SMTP.php';
        require 'PHPMailer/src/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'yewshunyaodennis@gmail.com';
            $mail->Password = 'ydgu hfqw qgjh daqg';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('yewshunyaodennis@gmail.com', 'FastFood Express');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP for Password Reset';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif;'>
                    <h3 style='color: #d6001c;'>Your OTP Code</h3>
                    <p>Use the code below to reset your password:</p>
                    <h1 style='color: #d6001c;'>$otp</h1>
                </div>
            ";

            $mail->send();
            $success = "OTP has been sent to your email.";
            header("refresh:2; url=reset_otp_verify.php"); // 自动跳转
        } catch (Exception $e) {
            $message = "Failed to send OTP. Please try again.";
        }
    } else {
        $message = "Email not found in our system.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <style>
    body {
      background-color: #ffeeee;
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
    input[type="email"] {
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
    .back-login {
      text-align: center;
      margin-top: 15px;
    }
    .back-login a {
      color: #d6001c;
      text-decoration: none;
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
  <h2>Forgot Password</h2>

  <?php if (!empty($message)): ?>
    <div class="message error"><?= $message ?></div>
  <?php endif; ?>
  
  <?php if (!empty($success)): ?>
    <div class="message success"><?= $success ?></div>
  <?php endif; ?>

  <form method="POST">
    <label for="email">Enter your registered email:</label>
    <input type="email" id="email" name="email" required>

    <button type="submit" class="btn">Send OTP</button>
  </form>

  <div class="back-login">
    <a href="login.php">← Back to Login</a>
  </div>
</div>

</body>
</html>
