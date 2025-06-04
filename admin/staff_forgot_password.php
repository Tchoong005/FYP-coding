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
$message = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Check if email exists in staff table
    $query = "SELECT * FROM staff WHERE email = ? AND deleted = 0";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        // Generate OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['staff_reset_email'] = $email;
        $_SESSION['staff_reset_otp'] = $otp;
        $_SESSION['staff_reset_otp_time'] = time();

        // Send OTP email
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';


        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'yewshunyaodennis@gmail.com'; // Your Gmail
            $mail->Password   = 'ydgu hfqw qgjh daqg'; // Your App Password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Temporary SSL bypass (for development only)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            // Recipients
            $mail->setFrom('yewshunyaodennis@gmail.com', 'FastFood Admin');
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your OTP for Password Reset (Staff Portal)';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif;'>
                    <h3 style='color: #d6001c;'>Your OTP Code</h3>
                    <p>Use the following code to reset your staff account password:</p>
                    <h1 style='color: #d6001c;'>$otp</h1>
                    <p>This code will expire in 10 minutes.</p>
                </div>
            ";

            $mail->send();
            header("Location: staff_reset_otp_verify.php");
            exit();
        } catch (Exception $e) {
            $message = "Failed to send OTP. Please contact administrator. Error: " . $e->getMessage();
        }
    } else {
        $message = "Staff email not found or account is inactive.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Staff Forgot Password</title>
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
    <h2>Staff Forgot Password</h2>
    
    <?php if (!empty($message)): ?>
      <div class="message error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <form method="POST">
      <label for="email">Enter your staff email:</label>
      <input type="email" id="email" name="email" required>
      <button type="submit" class="btn">Send OTP</button>
    </form>
    
    <div class="back-login">
      <a href="newadminlogin.php">← Back to Login</a>
    </div>
  </div>
</body>
</html>