<?php
session_start();
include 'db.php';

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = $success = "";

function generateOTP($length = 6) {
    return str_pad(random_int(0, pow(10, $length)-1), $length, '0', STR_PAD_LEFT);
}

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm  = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match('/^011\d{8}$/', $phone) && !preg_match('/^01[2-9]\d{7}$/', $phone)) {
        $error = "Phone number must start with 011 (11 digits) or 012–019 (10 digits).";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $error = "Password must be at least 8 characters with uppercase, lowercase, and number.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $check = mysqli_query($conn, "SELECT * FROM customers WHERE email='$email' OR phone='$phone'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email or phone already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $otp = generateOTP();

            $insert = "INSERT INTO customers (email, username, phone, password, verification_code, is_verified)
                       VALUES ('$email', '$username', '$phone', '$hashed_password', '$otp', 0)";
            if (mysqli_query($conn, $insert)) {
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
                    $mail->Subject = 'Your FastFood Express OTP Code';
                    $mail->Body    = "Hi $username,<br><br>Your OTP code is: <strong>$otp</strong>";

                    $mail->send();
                    $_SESSION['pending_email'] = $email;
                    $_SESSION['otp_sent_time'] = time();
                    header("Location: verify_code.php");
                    exit();
                } catch (Exception $e) {
                    $error = "Email sending failed.";
                }
            } else {
                $error = "Registration failed.";
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
                   pattern="^(011\d{8}|01[2-9]\d{7})$"
                   title="Phone number must start with 011 (11 digits) or 012–019 (10 digits)"
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

          <button type="submit" class="btn btn-danger w-100">Register</button>
        </form>

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
