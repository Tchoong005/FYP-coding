<?php
session_start();
include 'db.php';
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = "SELECT * FROM customers WHERE username='$user_id' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        header("Location: index_user.php");
        exit();
    } else {
        $error = "Invalid User ID or Password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - FastFood Express</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
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
    .login-container {
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
    input[type=text], input[type=password] {
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
    .bottom-link {
      text-align: center;
      margin-top: 15px;
    }
    .error { color: red; text-align: center; margin-bottom: 10px; }
  </style>
</head>
<body>

<div class="login-container" data-aos="zoom-in">
  <h2>Login</h2>
  <?php
    if ($error) echo "<div class='error'>$error</div>";
  ?>
  <form method="post">
    <input type="text" name="user_id" placeholder="User ID" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
    <div class="bottom-link">
      Don't have an account? <a href="register.php">Register</a><br>
      <a href="reset_password.php">Forgot Password?</a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init();</script>

</body>
</html>
