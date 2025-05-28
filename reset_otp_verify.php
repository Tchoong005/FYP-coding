<?php
session_start();
include 'db.php';

$error = $success = '';
$remaining_seconds = 0;

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    header("Location: forgot_password.php");
    exit();
}

// 倒计时计算
if (isset($_SESSION['reset_otp_time'])) {
    $elapsed = time() - $_SESSION['reset_otp_time'];
    if ($elapsed < 60) {
        $remaining_seconds = 60 - $elapsed;
    }
}

// 处理 resend 请求
if (isset($_GET['resend'])) {
    if ($remaining_seconds === 0) {
        $newOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['reset_otp'] = $newOTP;
        $_SESSION['reset_otp_time'] = time();

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
            $mail->addAddress($_SESSION['reset_email']);
            $mail->isHTML(true);
            $mail->Subject = 'Your New OTP Code';
            $mail->Body    = "Your new OTP is: <strong>$newOTP</strong>";

            $mail->send();
            $success = "New OTP sent!";
            $remaining_seconds = 60;
        } catch (Exception $e) {
            $error = "Failed to resend OTP.";
        }
    } else {
        $error = "Please wait before resending.";
    }
}

// 提交 OTP
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $entered = $_POST['otp'];
    if ($entered === $_SESSION['reset_otp']) {
        $_SESSION['reset_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Invalid OTP. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify OTP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #ffeeee;
      font-family: Arial, sans-serif;
    }
    .card {
      border-radius: 12px;
      border: none;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .btn-danger {
      background-color: #d6001c;
      border: none;
    }
    .btn-danger:hover {
      background-color: #b40000;
    }
    .message {
      text-align: center;
      margin-bottom: 10px;
      padding: 10px;
      border-radius: 6px;
    }
    .error {
      background-color: #fff0f0;
      color: #d6001c;
    }
    .success {
      background-color: #e7fff0;
      color: #0a8a42;
    }
    .resend-link {
      color: #d6001c;
      text-decoration: none;
      font-size: 14px;
    }
    .resend-link.disabled {
      pointer-events: none;
      opacity: 0.5;
    }
  </style>
</head>
<body>
  <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="col-md-5">
      <div class="card p-4">
        <h3 class="text-center text-danger mb-3">OTP Verification</h3>
        <p class="text-center text-muted">Enter the 6-digit code sent to <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong></p>

        <?php if ($error): ?>
          <div class="message error"><?= $error ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="message success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label for="otp" class="form-label fw-bold">OTP Code</label>
            <input type="text" class="form-control text-center fw-bold" maxlength="6" pattern="\d{6}" id="otp" name="otp" required placeholder="123456">
          </div>
          <button type="submit" class="btn btn-danger w-100">Verify OTP</button>
        </form>

        <div class="text-center mt-3">
          <?php if ($remaining_seconds > 0): ?>
            <span class="text-muted">Resend available in <span id="countdown"><?= $remaining_seconds ?></span>s</span>
          <?php else: ?>
            <a href="?resend" class="resend-link">Resend OTP</a>
          <?php endif; ?>
        </div>

        <div class="text-center mt-3">
          <a href="login.php" class="resend-link">← Back to Login</a>
        </div>
      </div>
    </div>
  </div>

  <?php if ($remaining_seconds > 0): ?>
  <script>
    let countdown = <?= $remaining_seconds ?>;
    const timer = document.getElementById('countdown');
    const interval = setInterval(() => {
      countdown--;
      if (countdown <= 0) {
        clearInterval(interval);
        location.reload();
      } else {
        timer.innerText = countdown;
      }
    }, 1000);
  </script>
  <?php endif; ?>
</body>
</html>
