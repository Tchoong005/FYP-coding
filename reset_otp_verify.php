<?php
session_start();
include 'db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$error = $success = '';
$email = $_SESSION['pending_email'] ?? $_SESSION['reset_email'] ?? null;

// 如果没 email，跳回首页
if (!$email) {
    header("Location: login.php");
    exit();
}

// 处理重新发送 OTP（仅 GET）
if (isset($_GET['resend'])) {
    $newOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $column = isset($_SESSION['pending_email']) ? 'verification_code' : 'reset_otp';

    $update = "UPDATE customers SET $column = '$newOTP' WHERE email = '$email'";
    if (mysqli_query($conn, $update)) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'yewshunyaodennis@gmail.com';
            $mail->Password = 'ydgu hfqw qgjh daqg'; // 应使用 App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('yewshunyaodennis@gmail.com', 'FastFood Express');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #d6001c;'>Your OTP Code</h2>
                    <p>Use the code below to proceed:</p>
                    <h1 style='color: #d6001c; text-align: center;'>$newOTP</h1>
                </div>";

            $mail->send();
            $success = "OTP resent to your email.";
        } catch (Exception $e) {
            $error = "Failed to send OTP.";
        }
    } else {
        $error = "Database error.";
    }
}

// 处理 OTP 验证（仅 POST）
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enteredOTP = $_POST['otp'] ?? '';
    $column = isset($_SESSION['pending_email']) ? 'verification_code' : 'reset_otp';

    $query = "SELECT * FROM customers WHERE email = '$email' AND $column = '$enteredOTP'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        if ($column === 'verification_code') {
            mysqli_query($conn, "UPDATE customers SET is_verified = 1, verification_code = NULL WHERE email = '$email'");
            unset($_SESSION['pending_email']);
            $_SESSION['registration_success'] = "Email verified. Please login.";
            header("Location: login.php");
            exit();
        } else {
            mysqli_query($conn, "UPDATE customers SET reset_otp = NULL WHERE email = '$email'");
            $_SESSION['reset_verified'] = true;
            header("Location: reset_password.php");
            exit();
        }
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
    <style>
        body {
            background: #fff0f0;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .box {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            width: 350px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            color: #d6001c;
            margin-bottom: 10px;
        }
        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 6px;
        }
        .error { color: #d6001c; background: #ffeeee; }
        .success { color: green; background: #eaffea; }
        input[name="otp"] {
            width: 100%;
            padding: 12px;
            font-size: 18px;
            letter-spacing: 5px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        button {
            background: #d6001c;
            color: white;
            padding: 12px;
            width: 100%;
            font-weight: bold;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover {
            background: #a00015;
        }
        .resend-link {
            display: block;
            margin-top: 15px;
            font-size: 14px;
            color: #d6001c;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="box">
        <h2>Verify OTP</h2>
        <p>OTP sent to: <strong><?php echo htmlspecialchars($email); ?></strong></p>

        <?php if ($error) echo "<div class='message error'>$error</div>"; ?>
        <?php if ($success) echo "<div class='message success'>$success</div>"; ?>

        <form method="POST" action="">
            <input type="text" name="otp" maxlength="6" pattern="\d{6}" required placeholder="Enter 6-digit OTP">
            <button type="submit">Verify OTP</button>
        </form>

        <a href="?resend=1" class="resend-link">Didn't receive? Resend OTP</a>
    </div>
</body>
</html>
