<?php
session_start();
include 'db.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    $check = mysqli_query($conn, "SELECT * FROM customers WHERE email='$email' AND is_verified = 1");
    if (mysqli_num_rows($check) == 1) {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_otp'] = $otp;
        $_SESSION['reset_otp_time'] = time();

        $mail = new PHPMailer(true);
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
            $mail->Subject = 'Reset Password OTP';
            $mail->Body = "Hi,<br><br>Your OTP to reset your password is: <strong>$otp</strong><br>Please enter this code to proceed.";

            $mail->send();
            header("Location: reset_otp_verify.php");
            exit();
        } catch (Exception $e) {
            $message = "Failed to send OTP.";
        }
    } else {
        $message = "Email not found or not verified.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password - FastFood Express</title>
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
    .error-message {
      color: #d6001c;
      background-color: #fff0f0;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 15px;
      text-align: center;
    }
    .form-label {
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="col-md-5">
      <div class="card p-4">
        <h3 class="text-center text-danger mb-3">Forgot Your Password?</h3>
        <p class="text-center text-muted">Enter your email and we'll send you a 6-digit OTP code to reset your password.</p>

        <?php if ($message): ?>
          <div class="error-message"><?= $message ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label for="email" class="form-label">Your Email</label>
            <input type="email" class="form-control" id="email" name="email" required placeholder="example@email.com">
          </div>

          <button type="submit" class="btn btn-danger w-100">Send OTP</button>
        </form>

        <div class="text-center mt-3">
          <a href="login.php" class="text-danger fw-semibold">← Back to Login</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
