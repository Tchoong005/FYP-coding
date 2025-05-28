<?php
session_start();
include 'db.php';

// 加载PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = $success = "";
$showOTPField = false; // 控制是否显示OTP输入字段

// 生成6位随机OTP
function generateOTP() {
    return rand(100000, 999999);
}

// 发送OTP邮件
function sendOTP($email, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // Gmail SMTP配置
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your@gmail.com'; // 替换为您的Gmail
        $mail->Password   = 'your_app_password'; // Gmail的16位App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // 收件人
        $mail->setFrom('no-reply@fastfoodexpress.com', 'FastFood Express');
        $mail->addAddress($email);
        
        // 邮件内容
        $mail->isHTML(true);
        $mail->Subject = 'Your FastFood Express Verification Code';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #d6001c;'>FastFood Express Verification Code</h2>
                <p>Your OTP code is: <strong style='font-size: 24px;'>$otp</strong></p>
                <p>This code will expire in 10 minutes.</p>
                <p>If you didn't request this, please ignore this email.</p>
            </div>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 如果是验证OTP的请求
    if (isset($_POST['verify_otp'])) {
        $userOTP = $_POST['otp'];
        $storedOTP = $_SESSION['register_otp'];
        $storedEmail = $_SESSION['register_email'];
        
        if ($userOTP == $storedOTP && time() < $_SESSION['otp_expiry']) {
            // OTP验证成功，完成注册
            $email = $_SESSION['register_email'];
            $username = $_SESSION['register_username'];
            $phone = $_SESSION['register_phone'];
            $password = $_SESSION['register_password'];
            $question = $_SESSION['register_question'];
            $answer = $_SESSION['register_answer'];
            
            // 插入数据库
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert = "INSERT INTO customers (email, username, phone, password, security_question, security_answer, is_verified)
                       VALUES ('$email', '$username', '$phone', '$hashed_password', '$question', '$answer', 1)";
            
            if (mysqli_query($conn, $insert)) {
                // 清除session中的注册数据
                unset($_SESSION['register_email']);
                unset($_SESSION['register_otp']);
                unset($_SESSION['otp_expiry']);
                unset($_SESSION['register_username']);
                unset($_SESSION['register_phone']);
                unset($_SESSION['register_password']);
                unset($_SESSION['register_question']);
                unset($_SESSION['register_answer']);
                
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Database error. Please try again.";
            }
        } else {
            $error = "Invalid OTP or OTP has expired. Please try again.";
            $showOTPField = true;
        }
    } 
    // 如果是初始注册表单提交
    else {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $confirm = mysqli_real_escape_string($conn, $_POST['confirm_password']);
        $question = mysqli_real_escape_string($conn, $_POST['security_question']);
        $answer = mysqli_real_escape_string($conn, $_POST['security_answer']);

        // 验证输入
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } elseif (!preg_match('/^01[0-9]{8,9}$/', $phone)) {
            $error = "Phone number must start with 01 and be 10-11 digits.";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
            $error = "Password must be at least 8 characters with uppercase, lowercase, and number.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } elseif (empty($question) || empty($answer)) {
            $error = "Security question and answer are required.";
        } else {
            // 检查邮箱和手机号是否已存在
            $check = mysqli_query($conn, "SELECT * FROM customers WHERE email='$email' OR phone='$phone'");
            if (mysqli_num_rows($check) > 0) {
                $error = "Email or phone number already used.";
            } else {
                // 生成并发送OTP
                $otp = generateOTP();
                if (sendOTP($email, $otp)) {
                    // 存储OTP和注册数据到session
                    $_SESSION['register_otp'] = $otp;
                    $_SESSION['otp_expiry'] = time() + 600; // 10分钟过期
                    $_SESSION['register_email'] = $email;
                    $_SESSION['register_username'] = $username;
                    $_SESSION['register_phone'] = $phone;
                    $_SESSION['register_password'] = $password;
                    $_SESSION['register_question'] = $question;
                    $_SESSION['register_answer'] = $answer;
                    
                    $success = "OTP has been sent to your email. Please check and enter it below.";
                    $showOTPField = true;
                } else {
                    $error = "Failed to send OTP. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - FastFood Express</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #ffecec; }
    .card { border: none; border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .btn-danger { background-color: #d50000; border: none; }
    .btn-danger:hover { background-color: #b40000; }
    .input-group-text.toggle-pass { cursor: pointer; }
    .otp-container { max-width: 300px; margin: 0 auto; }
  </style>
</head>
<body>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
      <div class="card p-4">
        <h2 class="text-center text-danger mb-4">Register</h2>
        
        <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (!$showOTPField): ?>
        <!-- 初始注册表单 -->
        <form method="post">
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
          </div>
          
          <div class="mb-3">
            <label for="username" class="form-label">User Name</label>
            <input type="text" class="form-control" id="username" name="username" required 
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
          </div>
          
          <div class="mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="tel" class="form-control" id="phone" name="phone" required 
                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                   placeholder="01XXXXXXXX">
          </div>
          
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="password" name="password" required>
              <span class="input-group-text toggle-pass" onclick="togglePassword('password', this)">Show</span>
            </div>
            <small class="text-muted">Must contain: 8+ chars, uppercase, lowercase, number</small>
          </div>
          
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
              <span class="input-group-text toggle-pass" onclick="togglePassword('confirm_password', this)">Show</span>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="security_question" class="form-label">Security Question</label>
            <input type="text" class="form-control" id="security_question" name="security_question" required 
                   value="<?php echo isset($_POST['security_question']) ? htmlspecialchars($_POST['security_question']) : ''; ?>" 
                   placeholder="e.g. What is your pet's name?">
          </div>
          
          <div class="mb-3">
            <label for="security_answer" class="form-label">Answer</label>
            <input type="text" class="form-control" id="security_answer" name="security_answer" required 
                   value="<?php echo isset($_POST['security_answer']) ? htmlspecialchars($_POST['security_answer']) : ''; ?>">
          </div>
          
          <button type="submit" class="btn btn-danger w-100">Send OTP</button>
        </form>
        <?php else: ?>
        <!-- OTP验证表单 -->
        <form method="post" class="otp-container">
          <div class="mb-3">
            <label for="otp" class="form-label">Enter OTP</label>
            <input type="text" class="form-control text-center" id="otp" name="otp" required 
                   maxlength="6" pattern="\d{6}" title="Please enter 6-digit OTP">
            <small class="text-muted">Check your email for the 6-digit code</small>
          </div>
          <button type="submit" name="verify_otp" class="btn btn-danger w-100">Verify OTP</button>
          <div class="text-center mt-3">
            <a href="register.php" class="text-danger">Resend OTP</a>
          </div>
        </form>
        <?php endif; ?>
        
        <div class="text-center mt-3">
          Already have an account? <a href="login.php" class="text-danger fw-semibold">Login</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function togglePassword(fieldId, toggleSpan) {
  const input = document.getElementById(fieldId);
  if (input.type === "password") {
    input.type = "text";
    toggleSpan.textContent = "Hide";
  } else {
    input.type = "password";
    toggleSpan.textContent = "Show";
  }
}
</script>
</body>
</html>