<?php
session_start();
include 'db.php';

$error = $success = '';

if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['pending_email'];

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userOTP = $_POST['otp_full'];

    $query = "SELECT * FROM customers WHERE email = '$email' AND verification_code = '$userOTP'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $update = "UPDATE customers SET is_verified = 1, verification_code = NULL WHERE email = '$email'";
        if (mysqli_query($conn, $update)) {
            unset($_SESSION['pending_email']);
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
  <title>Verify OTP</title>
  <style>
    body {
      background: #ffeeee;
      font-family: Arial, sans-serif;
    }
    .container {
      max-width: 400px;
      margin: 100px auto;
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      text-align: center;
    }
    h2 {
      color: #d6001c;
    }
    .otp-box {
      display: flex;
      justify-content: space-between;
      margin: 20px 0;
    }
    .otp-input {
      width: 50px;
      height: 50px;
      font-size: 22px;
      text-align: center;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    .btn {
      background: #d6001c;
      color: white;
      border: none;
      padding: 12px;
      width: 100%;
      border-radius: 6px;
      font-weight: bold;
      font-size: 16px;
      cursor: pointer;
    }
    .btn:hover {
      background: #b00015;
    }
    .message {
      padding: 10px;
      border-radius: 5px;
      margin-bottom: 15px;
    }
    .error {
      background-color: #ffe6e6;
      color: #d6001c;
    }
    .success {
      background-color: #e7ffe7;
      color: green;
    }
    .resend-link {
      margin-top: 15px;
      display: block;
      color: #d6001c;
      font-size: 14px;
      text-decoration: none;
    }
  </style>
</head>
<body>

<div class="container">
  <h2>Email Verification</h2>
  <p>OTP sent to: <strong><?php echo htmlspecialchars($email); ?></strong></p>

  <?php if ($error): ?>
    <div class="message error"><?= $error ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="message success"><?= $success ?></div>
  <?php endif; ?>

  <form method="POST" id="otpForm">
    <div class="otp-box">
      <?php for ($i = 1; $i <= 6; $i++): ?>
        <input type="text" maxlength="1" class="otp-input" id="otp<?= $i ?>" oninput="moveNext(this, <?= $i ?>)" required>
      <?php endfor; ?>
    </div>
    <input type="hidden" name="otp_full" id="otp_full">
    <button type="submit" class="btn">Verify OTP</button>
  </form>

  <a class="resend-link" href="?resend">Didn’t receive? Resend OTP</a>
</div>

<script>
  const inputs = document.querySelectorAll('.otp-input');

  function moveNext(elem, index) {
    let value = elem.value;
    if (!/^\d$/.test(value)) {
      elem.value = '';
      return;
    }
    if (index < 6) {
      document.getElementById('otp' + (index + 1)).focus();
    }

    let otp = '';
    inputs.forEach(input => otp += input.value);
    document.getElementById('otp_full').value = otp;
  }

  // Prevent non-digit input
  inputs.forEach(input => {
    input.addEventListener('keydown', (e) => {
      if (e.key === "Backspace" && input.value === '') {
        const prev = input.previousElementSibling;
        if (prev) prev.focus();
      }
      if (!e.key.match(/[0-9]|Backspace|Arrow/)) {
        e.preventDefault();
      }
    });
  });
</script>

</body>
</html>
