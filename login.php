<?php
// 必须放在文件最上方，确保不会输出 HTML 或空格
session_start();
include 'db.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = $_POST['email'];
  $password = $_POST['password'];

  $stmt = $conn->prepare("SELECT id, password FROM customers WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows == 1) {
    $stmt->bind_result($id, $hashed_password);
    $stmt->fetch();
    if (password_verify($password, $hashed_password)) {
      $_SESSION['user_id'] = $id;
      // 🔥 关键！一定要立刻跳转并停止输出
      header("Location: profile.php");
      exit();
    } else {
      $error = "Incorrect password.";
    }
  } else {
    $error = "Email not found.";
  }

  $stmt->close();
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
      align-items: center;
      justify-content: center;
      height: 100vh;
    }
    .form-container {
      background: #fff;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
    }
    h2 {
      color: #d6001c;
      margin-bottom: 20px;
    }
    input, button {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 14px;
    }
    button {
      background: #d6001c;
      color: white;
      font-weight: bold;
      cursor: pointer;
    }
    a {
      display: block;
      text-align: center;
      color: #d6001c;
      text-decoration: none;
    }
    .error {
      color: red;
      text-align: center;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>
  <div class="form-container" data-aos="zoom-in">
    <h2>Login</h2>

    <?php if (!empty($error)) { echo "<div class='error'>" . $error . "</div>"; } ?>

    <form method="POST">
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
      <a href="register.php">No account? Register</a>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script>AOS.init();</script>
</body>
</html>
