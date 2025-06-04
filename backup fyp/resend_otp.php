<?php
session_start();
include 'db.php';

if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['pending_email'];

// 生成新OTP
$otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

// 更新数据库中的OTP
$update = "UPDATE customers SET verification_code = '$otp' WHERE email = '$email'";
if (mysqli_query($conn, $update)) {
    // 重新发送OTP邮件
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
        $mail->Subject = 'Your New FastFood Express OTP Code';
        $mail->Body    = "Your new OTP code is: <strong>$otp</strong><br>Please enter this code to verify your email.";

        $mail->send();
        $_SESSION['resend_success'] = "New OTP has been sent to your email.";
    } catch (Exception $e) {
        $_SESSION['resend_error'] = "Failed to resend OTP. Please try again.";
    }
} else {
    $_SESSION['resend_error'] = "Database error. Please try again.";
}

header("Location: verify_code.php");
exit();
?>