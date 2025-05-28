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
            $mail->Subject = 'Your Reset OTP Code';
            $mail->Body = "Your OTP code to reset your password is: <strong>$otp</strong>";

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
<form method="post">
  <label>Email</label>
  <input type="email" name="email" required>
  <button type="submit">Send OTP</button>
  <p style="color:red;"><?php echo $message; ?></p>
</form>
