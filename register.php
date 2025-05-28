<?php
session_start();
include 'db.php';

$error = $success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $username  = mysqli_real_escape_string($conn, $_POST['username']);
    $phone     = mysqli_real_escape_string($conn, $_POST['phone']);
    $password  = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm   = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $question  = mysqli_real_escape_string($conn, $_POST['security_question']);
    $answer    = mysqli_real_escape_string($conn, $_POST['security_answer']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match('/^01[0-9]{8,9}$/', $phone)) {
        $error = "Phone number must start with 01 and be 10–11 digits.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $error = "Password must be at least 8 characters with uppercase, lowercase, and number.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (empty($question) || empty($answer)) {
        $error = "Security question and answer are required.";
    } else {
        $check = mysqli_query($conn, "SELECT * FROM customers WHERE email='$email' OR phone='$phone'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email or phone number already used.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert = "INSERT INTO customers (email, username, phone, password, security_question, security_answer, is_verified)
                       VALUES ('$email', '$username', '$phone', '$hashed_password', '$question', '$answer', 0)";
            if (mysqli_query($conn, $insert)) {
               
<?php
// ...existing code...
if (mysqli_query($conn, $insert)) {
    // Send welcome email (English version)
    $to = $email;
    $subject = "Welcome to FastFood Express!";
    $message = "Hi $username,\n\nThank you for registering at FastFood Express.\n\nEnjoy your meal!";
    $headers = "From: no-reply@fastfoodexpress.com\r\n";
    mail($to, $subject, $message, $headers);

    $success = "Registration successful! You can now login.";
} else {
    $error = "Something went wrong. Please try again.";
}
// ...existing code...
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - FastFood Express</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #fff0f0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .register-container {
      background: white;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      width: 400px;
    }
    h2 {
      color: #d6001c;
      text-align: center;
      margin-bottom: 20px;
    }
    label {
      font-weight: bold;
      display: block;
      margin-top: 10px;
    }
    input[type=text], input[type=email], input[type=password] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    button {
      background: #d6001c;
      color: white;
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
      margin-top: 15px;
    }
    .bottom-link {
      text-align: center;
      margin-top: 15px;
    }
    .error, .success {
      text-align: center;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
    }
    .error { background: #ffe0e0; color: #d6001c; }
    .success { background: #e0ffe0; color: green; }
    .toggle-password {
      float: right;
      margin-top: -25px;
      margin-right: 10px;
      font-size: 12px;
      color: #555;
      cursor: pointer;
      position: relative;
      z-index: 2;
    }
  </style>
</head>
<body>

<div class="register-container">
  <h2>Register</h2>
  <?php if ($error) echo "<div class='error'>$error</div>"; ?>
  <?php if ($success) echo "<div class='success'>$success</div>"; ?>
  <form method="post">
    <label>Email</label>
    <input type="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

    <label>User Name</label>
    <input type="text" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">

    <label>Phone Number</label>
    <input type="text" name="phone" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">

    <label>Password</label>
    <input type="password" name="password" id="password" required>
    <span class="toggle-password" onclick="togglePassword('password')">Show</span>

    <label>Confirm Password</label>
    <input type="password" name="confirm_password" id="confirm_password" required>
    <span class="toggle-password" onclick="togglePassword('confirm_password')">Show</span>

    <label>Security Question</label>
    <input type="text" name="security_question" required placeholder="e.g. What is your favorite food?" value="<?php echo isset($_POST['security_question']) ? htmlspecialchars($_POST['security_question']) : ''; ?>">

    <label>Answer</label>
    <input type="text" name="security_answer" required value="<?php echo isset($_POST['security_answer']) ? htmlspecialchars($_POST['security_answer']) : ''; ?>">

    <button type="submit">Register</button>
    <div class="bottom-link">
      Already have an account? <a href="login.php" style="color: #d6001c;">Login</a>
    </div>
  </form>
</div>

<script>
function togglePassword(id) {
  const input = document.getElementById(id);
  const type = input.type === 'password' ? 'text' : 'password';
  input.type = type;
  event.target.textContent = type === 'password' ? 'Show' : 'Hide';
}
</script>

</body>
</html>