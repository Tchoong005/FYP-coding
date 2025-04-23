<?php
include 'db.php';
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $new_password = mysqli_real_escape_string($conn, $_POST['password']);

  $check_query = "SELECT * FROM customers WHERE email='$email'";
  $result = mysqli_query($conn, $check_query);

  if (mysqli_num_rows($result) == 1) {
    $update_query = "UPDATE customers SET password='$new_password' WHERE email='$email'";
    if (mysqli_query($conn, $update_query)) {
      $success = "Password updated successfully. You can now login.";
    } else {
      $error = "Something went wrong. Please try again.";
    }
  } else {
    $error = "No user found with that email.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password - FastFood Express</title>
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
    .reset-container {
      background: white;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      width: 350px;
    }
    h2 {
      color: #d6001c;
      text-align: center;
      margin-bottom: 20px;
    }
    input[type=email], input[type=password] {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
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
    }
    .message {
      text-align: center;
      margin-bottom: 10px;
      color: green;
    }
    .error {
      text-align: center;
      margin-bottom: 10px;
      color: red;
    }
  </style>
</head>
<body>

<div class="reset-container">
  <h2>Reset Password</h2>
  <?php
    if ($error) echo "<p class='error'>$error</p>";
    if ($success) echo "<p class='message'>$success</p>";
  ?>
  <form method="post">
    <input type="email" name="email" placeholder="Your Email" required>
    <input type="password" name="password" placeholder="New Password" required>
    <button type="submit">Update Password</button>
  </form>
</div>

</body>
</html>
