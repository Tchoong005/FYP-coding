<?php
session_start();
include 'db.php';

$error = $success = '';
$email = '';

// 检查是否有 pending_email session
if (!isset($_SESSION['pending_email'])) {
    $error = "Session expired or invalid access. Please register again.";
} else {
    $email = $_SESSION['pending_email'];

    // 处理重新发送OTP请求
    if (isset($_GET['resend'])) {
        $newOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $update = "UPDATE customers SET verification_code = '$newOTP' WHERE email = '$email'";
        if (mysqli_query($conn, $update)) {
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
                $mail->Subject = 'Your New OTP Code';
                $mail->Body = "<h2>Your OTP is:</h2><h1 style='color:red;'>$newOTP</h1>";

                $mail->send();
                $success = "New OTP sent to your email.";
            } catch (Exception $e) {
                $error = "Failed to resend OTP.";
            }
        }
    }

    // 验证OTP
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $userOTP = $_POST['otp'];
        $query = "SELECT * FROM customers WHERE email = '$email' AND verification_code = '$userOTP'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) == 1) {
            $update = "UPDATE customers SET is_verified = 1, verification_code = NULL WHERE email = '$email'";
            if (mysqli_query($conn, $update)) {
                unset($_SESSION['pending_email']);
                $_SESSION['registration_success'] = "Registration successful!";
                header("Location: login.php");
                exit();
            } else {
                $error = "Database error.";
            }
        } else {
            $error = "Invalid OTP. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Email Verification</title>
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
      text-align: center;
    }
    h2 {
      color: #d6001c;
      margin-bottom: 20px;
    }
    .otp-boxes {
      display: flex;
      justify-content: space-between;
      margin: 20px 0;
    }
    .otp-input {
      width: 45px;
      height: 55px;
      font-size: 24px;
      text-align: center;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    .verify-btn {
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
    .verify-btn:hover {
      background: #a50013;
    }
    .resend-link {
      margin-top: 15px;
      display: inline-block;
      font-size: 14px;
      color: #d6001c;
      text-decoration: none;
    }
    .resend-link:hover {
      text-decoration: underline;
    }
    .message {
      margin-bottom: 15px;
      padding: 10px;
      border-radius: 5px;
    }
    .error {
      background-color: #fff0f0;
      color: #d6001c;
    }
    .success {
      background-color: #e0ffe8;
      color: green;
    }
    .email-info {
      font-size: 14px;
      color: #555;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>Enter OTP Code</h2>

  <?php if (!empty($email)): ?>
    <p class="email-info">OTP sent to: <strong><?= htmlspecialchars($email) ?></strong></p>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="message error"><?= $error ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="message success"><?= $success ?></div>
  <?php endif; ?>

  <?php if (!empty($email)): ?>
    <form method="POST" onsubmit="combineOTP();">
      <div class="otp-boxes">
        <?php for ($i = 1; $i <= 6; $i++): ?>
          <input type="text" id="otp<?= $i ?>" maxlength="1" class="otp-input" required>
        <?php endfor; ?>
      </div>
      <input type="hidden" name="otp" id="otpFull">
      <button type="submit" class="verify-btn">Verify OTP</button>
    </form>

    <a href="?resend" class="resend-link">Resend OTP</a>
  <?php else: ?>
    <p style="color: #d6001c;">Error: Session expired. Please go back to <a href="register.php">Register</a>.</p>
  <?php endif; ?>
</div>

<script>
  const inputs = document.querySelectorAll('.otp-input');
  inputs[0]?.focus();

  inputs.forEach((input, index) => {
    input.addEventListener('input', () => {
      if (input.value.length > 0 && index < inputs.length - 1) {
        inputs[index + 1].focus();
      }
    });

    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !input.value && index > 0) {
        inputs[index - 1].focus();
      }
    });
  });

  function combineOTP() {
    let otp = '';
    inputs.forEach(input => otp += input.value);
    document.getElementById('otpFull').value = otp;
  }
</script>

</body>
</html>
