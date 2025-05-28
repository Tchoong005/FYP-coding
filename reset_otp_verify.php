<?php
session_start();
include 'db.php';

$error = "";
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userOTP = $_POST['otp'];
    if ($userOTP === $_SESSION['reset_otp']) {
        $_SESSION['reset_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        $error = "Invalid OTP. Try again.";
    }
}
?>
<form method="post">
  <label>Enter 6-digit OTP</label>
  <input type="text" name="otp" maxlength="6" required pattern="\d{6}">
  <button type="submit">Verify OTP</button>
  <p style="color:red;"><?php echo $error; ?></p>
</form>
