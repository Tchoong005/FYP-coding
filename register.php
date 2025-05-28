<?php
session_start();
require 'db.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';
$showOTPField = false;

function generateOTP() {
  return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['verify_otp'])) {
  $email = trim($_POST['email']);
  $username = trim($_POST['username']);
  $phone = trim($_POST['phone']);
  $password = $_POST['password'];
  $confirm_password = $_POST['confirm_password'];
  $security_question = trim($_POST['security_question']);
  $security_answer = trim($_POST['security_answer']);

  if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
    $error = "Password must be at least 8 characters and include uppercase, lowercase and a number.";
  } elseif ($password !== $confirm_password) {
    $error = "Passwords do not match.";
  } else {
    $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $email, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      $error = "Email or Username already exists.";
    } else {
      $otp = generateOTP();
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);

      $stmt = $conn->prepare("INSERT INTO customers (email, username, phone, password, security_question, security_answer, verification_code, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
      $stmt->bind_param("sssssss", $email, $username, $phone, $hashed_password, $security_question, $security_answer, $otp);
      if ($stmt->execute()) {
        $mail = new PHPMailer(true);
        try {
          $mail->isSMTP();
          $mail->Host = 'smtp.gmail.com';
          $mail->SMTPAuth = true;
          $mail->Username = 'your_email@gmail.com'; // 替换
          $mail->Password = 'your_app_password';     // 替换
          $mail->SMTPSecure = 'tls';
          $mail->Port = 587;

          $mail->setFrom('your_email@gmail.com', 'FastFood Express');
          $mail->addAddress($email, $username);
          $mail->isHTML(true);
          $mail->Subject = 'Your Verification Code';
          $mail->Body = "<p>Your verification code is: <strong>$otp</strong></p>";

          $mail->send();
          $_SESSION['pending_email'] = $email;
          $showOTPField = true;
        } catch (Exception $e) {
          $error = "Email sending failed. " . $mail->ErrorInfo;
        }
      } else {
        $error = "Database error: Could not register.";
      }
    }
  }
}

if (isset($_POST['verify_otp'])) {
  $entered_otp = trim($_POST['otp']);
  $email = $_SESSION['pending_email'] ?? '';

  if (!$email) {
    $error = "Session expired. Please register again.";
  } else {
    $stmt = $conn->prepare("SELECT verification_code FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && $user['verification_code'] === $entered_otp) {
      $stmt = $conn->prepare("UPDATE customers SET is_verified = 1, verification_code = NULL WHERE email = ?");
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $success = "Registration complete! You can now login.";
      unset($_SESSION['pending_email']);
    } else {
      $error = "Invalid verification code.";
      $showOTPField = true;
    }
  }
}

if (isset($_GET['resend']) && isset($_SESSION['pending_email'])) {
  $email = $_SESSION['pending_email'];
  $new_otp = generateOTP();
  $stmt = $conn->prepare("UPDATE customers SET verification_code = ? WHERE email = ?");
  $stmt->bind_param("ss", $new_otp, $email);
  $stmt->execute();

  $mail = new PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your_email@gmail.com'; // 替换
    $mail->Password = 'your_app_password';     // 替换
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('your_email@gmail.com', 'FastFood Express');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Resent Verification Code';
    $mail->Body = "<p>Your new verification code is: <strong>$new_otp</strong></p>";

    $mail->send();
    $success = "A new OTP has been sent.";
    $showOTPField = true;
  } catch (Exception $e) {
    $error = "Could not resend OTP. " . $mail->ErrorInfo;
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
    .verification-notice {
      background-color: #fff8f8;
      border-left: 4px solid #d6001c;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
    }
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
        <form method="post">
          <div class="mb-3"><label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required 
                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
          </div>
          <div class="mb-3"><label for="username" class="form-label">User Name</label>
            <input type="text" class="form-control" id="username" name="username" required 
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
          </div>
          <div class="mb-3"><label for="phone" class="form-label">Phone Number</label>
            <input type="tel" class="form-control" id="phone" name="phone" required 
                   placeholder="01XXXXXXXX"
                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
          </div>
          <div class="mb-3"><label for="password" class="form-label">Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="password" name="password" required>
              <span class="input-group-text toggle-pass" onclick="togglePassword('password', this)">Show</span>
            </div>
            <small class="text-muted">Must contain: 8+ chars, uppercase, lowercase, number</small>
          </div>
          <div class="mb-3"><label for="confirm_password" class="form-label">Confirm Password</label>
            <div class="input-group">
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
              <span class="input-group-text toggle-pass" onclick="togglePassword('confirm_password', this)">Show</span>
            </div>
          </div>
          <div class="mb-3"><label for="security_question" class="form-label">Security Question</label>
            <input type="text" class="form-control" id="security_question" name="security_question" required 
                   placeholder="e.g. What is your pet's name?"
                   value="<?php echo isset($_POST['security_question']) ? htmlspecialchars($_POST['security_question']) : ''; ?>">
          </div>
          <div class="mb-3"><label for="security_answer" class="form-label">Answer</label>
            <input type="text" class="form-control" id="security_answer" name="security_answer" required 
                   value="<?php echo isset($_POST['security_answer']) ? htmlspecialchars($_POST['security_answer']) : ''; ?>">
          </div>
          <button type="submit" class="btn btn-danger w-100">Register</button>
        </form>
        <?php else: ?>
        <div class="verification-notice">
          <p>A verification code has been sent to your email. Please enter it below.</p>
        </div>
        <form method="post" class="otp-container">
          <div class="mb-3">
            <label for="otp" class="form-label">Enter OTP</label>
            <input type="text" class="form-control text-center" id="otp" name="otp" required maxlength="6" pattern="\d{6}" title="Please enter 6-digit OTP">
            <small class="text-muted">Check your email for the 6-digit code</small>
          </div>
          <button type="submit" name="verify_otp" class="btn btn-danger w-100">Verify OTP</button>
          <div class="text-center mt-3">
            Didn't receive code? <a href="?resend" class="text-danger">Resend OTP</a>
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
