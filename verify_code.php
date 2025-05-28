<?php
session_start();
include 'db.php';

$notice = "";

if (!isset($_SESSION['pending_email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['pending_email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = mysqli_real_escape_string($conn, $_POST['otp']);
    $query = "SELECT * FROM customers WHERE email='$email' AND verification_code='$otp'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $update = "UPDATE customers SET is_verified=1, verification_code=NULL WHERE email='$email'";
        mysqli_query($conn, $update);
        unset($_SESSION['pending_email']);
        $notice = "Email verified! You may now login. <a href='login.php'>Go to login</a>";
    } else {
        $notice = "Invalid OTP. Please check your email again.";
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
            font-family: Arial;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 350px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #d6001c;
            text-align: center;
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background: #d6001c;
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
        }
        .message {
            text-align: center;
            margin-top: 15px;
            color: #d6001c;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Email Verification</h2>
    <form method="post">
        <label>Enter OTP sent to your email</label>
        <input type="text" name="otp" required maxlength="6">
        <button type="submit">Verify</button>
    </form>
    <div class="message"><?php echo $notice; ?></div>
</div>

</body>
</html>
