<?php
session_start();
include 'db.php';

$message = "";
$security_question = "";

// 第一步：获取用户邮箱并验证是否存在
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['email'])) {
    $email = mysqli_real_escape_string($conn, $_GET['email']);
    $sql = "SELECT security_question, password FROM customers WHERE email='$email'";
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $security_question = $row['security_question'];
        $_SESSION['current_hashed_password'] = $row['password']; // 存储当前密码哈希用于后续比对
    } else {
        $message = "Email not found in our system.";
    }
}

// 第二步：处理密码重置请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $security_answer = mysqli_real_escape_string($conn, $_POST['security_answer']);

    // 获取安全答案和当前密码
    $sql = "SELECT security_answer, password FROM customers WHERE email='$email'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    $stored_answer = $row['security_answer'];
    $current_hashed_password = $row['password'];

    // 验证流程
    if (!password_verify($security_answer, $stored_answer)) {
        $message = "Security answer is incorrect!";
    } elseif (password_verify($new_password, $current_hashed_password)) {
        $message = "新密码不能与旧密码相同!"; // Chinese error message
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match!";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $new_password)) {
        $message = "Password must be at least 8 characters long, with uppercase, lowercase, and a number.";
    } else {
        // 所有验证通过，更新密码
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE customers SET password='$hashed_password' WHERE email='$email'";
        
        if (mysqli_query($conn, $sql)) {
            $message = "密码重置成功!";
            $security_question = "";
            unset($_SESSION['current_hashed_password']);
        } else {
            $message = "Error updating password.";
        }
    }
    
    // 重新获取安全问题用于显示
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
    /* 原有CSS样式保持不变 */
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
    /* 其他样式... */
  </style>
</head>
<body>

<div class="reset-container">
  <h2>重置密码</h2>
  <?php if (!empty($message)) echo "<div class='message'>$message</div>"; ?>
  
  <!-- 邮箱输入表单 -->
  <form method="GET" action="" class="email-form" id="emailForm" style="display: <?php echo empty($security_question) ? 'block' : 'none'; ?>;">
    <label for="email">邮箱</label>
    <input type="email" id="email" name="email" required>
    
    <button type="submit" class="reset-btn">继续</button>
    
    <div class="back-login">
      <a href="login.php">← 返回登录</a>
    </div>
  </form>
  
  <!-- 安全问题和新密码表单 -->
  <form method="POST" action="" class="security-form" id="securityForm" style="display: <?php echo !empty($security_question) ? 'block' : 'none'; ?>;">
    <input type="hidden" name="email" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
    
    <?php if (!empty($security_question)): ?>
      <label>安全问题:</label>
      <p><?php echo htmlspecialchars($security_question); ?></p>
      
      <label for="security_answer">您的答案</label>
      <input type="text" id="security_answer" name="security_answer" required>
    <?php endif; ?>
    
    <label for="new_password">新密码</label>
    <input type="password" id="new_password" name="new_password" required>

    <label for="confirm_password">确认密码</label>
    <input type="password" id="confirm_password" name="confirm_password" required>

    <div class="checkbox">
      <input type="checkbox" id="show_password" onclick="togglePassword()"> 显示密码
    </div>

    <button type="submit" class="reset-btn">重置密码</button>

    <div class="back-login">
      <a href="login.php">← 返回登录</a>
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
  
  // 自动切换表单显示
  <?php if (!empty($security_question)): ?>
    document.getElementById('emailForm').style.display = 'none';
    document.getElementById('securityForm').style.display = 'block';
  <?php endif; ?>
</script>

</body>
</html>