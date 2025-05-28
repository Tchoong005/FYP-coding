<?php
session_start();
include 'db.php';

$error = $success = '';

if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit();
}

// 处理重新发送OTP请求
if (isset($_GET['resend'])) {
    $email = $_SESSION['pending_email'];
    $newOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // 更新数据库中的OTP
    $update = "UPDATE customers SET verification_code = '$newOTP' WHERE email = '$email'";
    if (mysqli_query($conn, $update)) {
        // 发送新OTP邮件
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
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #d6001c;'>New OTP Code</h2>
                    <p>Your new verification code is:</p>
                    <h1 style='color: #d6001c; text-align: center;'>$newOTP</h1>
                    <p>Please use this code to verify your email address.</p>
                </div>
            ";
            
            $mail->send();
            $success = "New OTP has been sent to your email.";
        } catch (Exception $e) {
            $error = "Failed to resend OTP. Please try again.";
        }
    } else {
        $error = "Database error. Please try again.";
    }
}

// 处理OTP验证请求
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userOTP = $_POST['otp'];
    $email = $_SESSION['pending_email'];
    
    // 验证OTP
    $query = "SELECT * FROM customers WHERE email = '$email' AND verification_code = '$userOTP'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) == 1) {
        // OTP验证成功，更新用户状态
        $update = "UPDATE customers SET is_verified = 1, verification_code = NULL WHERE email = '$email'";
        if (mysqli_query($conn, $update)) {
            // 清除session
            unset($_SESSION['pending_email']);
            
            // 设置成功消息并重定向到登录页面
            $_SESSION['registration_success'] = "Registration successful! You can now login.";
            header("Location: login.php");
            exit();
        } else {
            $error = "Database error. Please try again.";
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
    <title>Verify OTP - FastFood Express</title>
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
        .verification-box {
            background: white;
            padding: 30px 40px;
            border-radius: 10px;
            width: 350px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        h2 {
            color: #d6001c;
            margin-bottom: 20px;
        }
        .otp-input {
            width: 100%;
            padding: 12px;
            margin: 15px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            text-align: center;
            font-size: 18px;
            letter-spacing: 5px;
        }
        .verify-btn {
            background: #d6001c;
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        .verify-btn:hover {
            background: #b50017;
        }
        .message {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        .error {
            color: #d6001c;
            background-color: #ffeeee;
        }
        .success {
            color: green;
            background-color: #eeffee;
        }
        .email-display {
            color: #666;
            margin-bottom: 15px;
            word-break: break-all;
        }
        .resend-link {
            color: #d6001c;
            text-decoration: none;
            font-size: 14px;
            margin-top: 15px;
            display: inline-block;
        }
        .resend-link:hover {
            text-decoration: underline;
        }
        .bottom-text {
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>

<div class="verification-box">
    <h2>Email Verification</h2>
    <div class="email-display">OTP sent to: <?php echo htmlspecialchars($_SESSION['pending_email']); ?></div>
    
    <?php if ($error): ?>
        <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="message success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="post">
        <input type="text" name="otp" class="otp-input" placeholder="Enter 6-digit OTP" required 
               maxlength="6" pattern="\d{6}" title="Please enter 6-digit OTP">
        <button type="submit" class="verify-btn">Verify OTP</button>
    </form>
    
    <div class="bottom-text">
        Didn't receive code? <a href="?resend" class="resend-link">Resend OTP</a>
    </div>
</div>

</body>
</html>