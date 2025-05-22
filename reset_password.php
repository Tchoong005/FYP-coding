<?php
session_start();
include 'db.php';

$message = "";
$security_question = "";

// Fetch security question if email is provided
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['email'])) {
    $email = mysqli_real_escape_string($conn, $_GET['email']);
    $sql = "SELECT security_question, password FROM customers WHERE email='$email'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $security_question = $row['security_question'];
        $_SESSION['current_hashed_password'] = $row['password']; // Store current password for comparison
    } else {
        $message = "Email not found in our system.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $security_answer = mysqli_real_escape_string($conn, $_POST['security_answer']);

    // First verify the security answer and get current password
    $sql = "SELECT security_answer, password FROM customers WHERE email='$email'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $stored_answer = $row['security_answer'];
    $current_hashed_password = $row['password'];

    if (!password_verify($security_answer, $stored_answer)) {
        $message = "Security answer is incorrect!";
    } elseif (password_verify($new_password, $current_hashed_password)) {
        $message = "New password cannot be the same as your current password!";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match!";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $new_password)) {
        $message = "Password must be at least 8 characters long, with uppercase, lowercase, and a number.";
    } else {
        // Hash the new password before storing
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE customers SET password='$hashed_password' WHERE email='$email'";
        if (mysqli_query($conn, $sql)) {
            $message = "Password successfully reset!";
            $security_question = ""; // Clear the question after successful reset
            unset($_SESSION['current_hashed_password']);
        } else {
            $message = "Error updating password.";
        }
    }
    
    // Get the security question again to display in case of error
    if (!empty($email)) {
        $sql = "SELECT security_question FROM customers WHERE email='$email'";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $security_question = $row['security_question'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <style>
    body {
      background: #ffeeee;
      font-family: Arial, sans-serif;
    }
    .reset-container {
      background: white;
      width: 400px;
      margin: 100px auto;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    h2 {
      margin-bottom: 25px;
      color: #000;
    }
    label {
      font-weight: bold;
      display: block;
      margin-top: 15px;
    }
    input[type="email"],
    input[type="password"],
    input[type="text"] {
      width: 100%;
      padding: 10px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-size: 14px;
    }
    .checkbox {
      margin-top: 10px;
    }
    .reset-btn {
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
    .reset-btn:hover {
      background: #a50013;
    }
    .back-login {
      text-align: center;
      margin-top: 10px;
    }
    .back-login a {
      color: #d6001c;
      text-decoration: none;
    }
    .message {
      text-align: center;
      margin-bottom: 10px;
      color: #d6001c;
    }
    .email-form {
      display: <?php echo empty($security_question) ? 'block' : 'none'; ?>;
    }
    .security-form {
      display: <?php echo !empty($security_question) ? 'block' : 'none'; ?>;
    }
  </style>
</head>
<body>

<div class="reset-container">
  <h2>Reset Password</h2>
  <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>
  
  <!-- Email input form (first step) -->
  <form method="GET" action="" class="email-form" id="emailForm">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>
    
    <button type="submit" class="reset-btn">Continue</button>
    
    <div class="back-login">
      <a href="login.php">← Back to Login</a>
    </div>
  </form>
  
  <!-- Security question and password form (second step) -->
  <form method="POST" action="" class="security-form" id="securityForm">
    <input type="hidden" name="email" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
    
    <div id="securityQuestionDisplay">
      <?php if (!empty($security_question)): ?>
        <label>Security Question:</label>
        <p><?php echo htmlspecialchars($security_question); ?></p>
        
        <label for="security_answer">Your Answer</label>
        <input type="text" id="security_answer" name="security_answer" required>
      <?php endif; ?>
    </div>
    
    <label for="new_password">New Password</label>
    <input type="password" id="new_password" name="new_password" required>

    <label for="confirm_password">Confirm Password</label>
    <input type="password" id="confirm_password" name="confirm_password" required>

    <div class="checkbox">
      <input type="checkbox" id="show_password" onclick="togglePassword()"> Show Password
    </div>

    <button type="submit" class="reset-btn">Reset Password</button>

    <div class="back-login">
      <a href="login.php">← Back to Login</a>
    </div>
  </form>
</div>

<script>
  function togglePassword() {
    const newPass = document.getElementById('new_password');
    const confirmPass = document.getElementById('confirm_password');
    const type = newPass.type === "password" ? "text" : "password";
    newPass.type = type;
    confirmPass.type = type;
  }
  
  // If we have a security question to show, switch forms
  <?php if (!empty($security_question)): ?>
    document.getElementById('emailForm').style.display = 'none';
    document.getElementById('securityForm').style.display = 'block';
  <?php endif; ?>
</script>

</body>
</html>