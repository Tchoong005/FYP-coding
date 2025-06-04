<?php
session_start();
include 'db.php';

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

$error = $success = "";

if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['pending_email'];

// ========== RESEND OTP ==========
if (isset($_GET['resend'])) {
    if (isset($_SESSION['otp_sent_time']) && time() - $_SESSION['otp_sent_time'] < 60) {
        $error = "Please wait " . (60 - (time() - $_SESSION['otp_sent_time'])) . " seconds before resending.";
    } else {
        $newOTP = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $update = "UPDATE customers SET verification_code = '$newOTP' WHERE email = '$email'";
        if (mysqli_query($conn, $update)) {
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
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #d6001c;'>New OTP Code</h2>
                        <p>Your new verification code is:</p>
                        <h1 style='color: #d6001c; text-align: center;'>$newOTP</h1>
                        <p>Please use this code to verify your email address.</p>
                    </div>
                ";
                $mail->send();
                $_SESSION['otp_sent_time'] = time();
                $success = "New OTP has been sent to your email.";
            } catch (Exception $e) {
                $error = "Failed to resend OTP. Please try again.";
            }
        } else {
            $error = "Database error. Please try again.";
        }
    }
}

// ========== VERIFY OTP ==========
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userOTP = $_POST['otp'];
    $query = "SELECT * FROM customers WHERE email = '$email' AND verification_code = '$userOTP'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $update = "UPDATE customers SET is_verified = 1, verification_code = NULL WHERE email = '$email'";
        if (mysqli_query($conn, $update)) {
            $user = mysqli_fetch_assoc($result); // 获取用户资料
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
                $mail->Subject = '🎉 Welcome to FastFood Express!';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto;'>
                        <h2 style='color: #d6001c;'>You’re now verified!</h2>
                        <p>Hi there 👋,</p>
                        <p>Thank you for registering and verifying your email address.</p>
                        <p>You can now login and enjoy the full features of FastFood Express 🍟🍔</p>
                        <br>
                        <p style='color: #888;'>— FastFood Express Team</p>
                    </div>
                ";
                $mail->send();
            } catch (Exception $e) {
                // Optional logging
            }

            // 自动登入 🎉
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            unset($_SESSION['pending_email'], $_SESSION['otp_sent_time']);
            header("Location: index_user.php");
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
            word-break: break-word;
        }
        .resend-section {
            margin-top: 15px;
            font-size: 14px;
        }
        .resend-link {
            color: #d6001c;
            text-decoration: none;
            display: none;
        }
        .resend-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="verification-box">
    <h2>Email Verification</h2>
    <div class="email-display">OTP sent to: <?php echo htmlspecialchars($email); ?></div>

    <?php if ($error): ?>
        <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="message success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="otp" class="otp-input" placeholder="Enter 6-digit OTP" required maxlength="6" pattern="\d{6}">
        <button type="submit" class="verify-btn">Verify OTP</button>
    </form>

    <div class="resend-section">
        <span id="resendText"></span>
        <a href="?resend" id="resendLink" class="resend-link">Resend OTP</a>
    </div>
</div>

<script>
let countdown = <?php echo isset($_SESSION['otp_sent_time']) ? max(0, 60 - (time() - $_SESSION['otp_sent_time'])) : 0; ?>;
const resendText = document.getElementById('resendText');
const resendLink = document.getElementById('resendLink');

function updateCountdown() {
    if (countdown > 0) {
        resendText.innerText = `Wait ${countdown}s...`;
        resendLink.style.display = 'none';
    } else {
        resendText.innerText = '';
        resendLink.style.display = 'inline';
    }
    countdown--;
}

updateCountdown();
let timer = setInterval(() => {
    updateCountdown();
    if (countdown < 0) clearInterval(timer);
}, 1000);
</script>

</body>
</html>
