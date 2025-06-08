<?php
session_start();
include 'db.php';

$error = "";
$success = "";

// 处理登录表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = "SELECT * FROM customers WHERE email='$email'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // 检查用户是否被封禁
        if ($user['is_banned'] == 1) {
            $error = "Your account has been suspended. Please contact support.";
        } 
        // 验证密码
        elseif (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: index_user.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - FastFood Express</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #d50000;
      --primary-dark: #b40000;
      --secondary: #f8f9fa;
      --text: #333;
      --light: #fff;
      --border: #ddd;
      --success: #28a745;
      --warning: #ffc107;
      --danger: #dc3545;
      --light-red: #fff5f5;
    }
    
    body {
      background: linear-gradient(135deg, #fff5f5 0%, #fff0f0 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      padding: 20px;
    }
    
    .container-wrapper {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .brand-header {
      text-align: center;
      margin-bottom: 30px;
      padding: 20px;
      border-radius: 16px;
      background: var(--light);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      position: relative;
    }
    
    .brand-header h1 {
      color: var(--primary);
      font-weight: 700;
      font-size: 2.8rem;
      margin-bottom: 10px;
    }
    
    .brand-header p {
      color: #666;
      font-size: 1.2rem;
      max-width: 600px;
      margin: 0 auto;
    }
    
    .card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      background: var(--light);
    }
    
    .card-header {
      background-color: var(--primary);
      color: white;
      text-align: center;
      padding: 25px;
      border-bottom: none;
    }
    
    .card-header h2 {
      margin: 0;
      font-weight: 600;
      font-size: 1.8rem;
    }
    
    .card-body {
      padding: 30px;
    }
    
    .form-label {
      font-weight: 600;
      color: var(--text);
      margin-bottom: 8px;
    }
    
    .form-control {
      padding: 12px 15px;
      border: 2px solid var(--border);
      border-radius: 8px;
      transition: all 0.3s;
    }
    
    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.25rem rgba(213, 0, 0, 0.25);
    }
    
    .input-group {
      margin-bottom: 5px;
    }
    
    .input-group-text {
      background-color: #f8f9fa;
      border: 2px solid var(--border);
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .input-group-text:hover {
      background-color: #e9ecef;
    }
    
    .btn-primary {
      background-color: var(--primary);
      border: none;
      padding: 14px;
      font-weight: 600;
      border-radius: 8px;
      transition: all 0.3s;
      font-size: 1.1rem;
      width: 100%;
      max-width: 300px;
      margin: 20px auto;
      display: block;
    }
    
    .btn-primary:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(213, 0, 0, 0.2);
    }
    
    .btn-primary:active {
      transform: translateY(0);
    }
    
    .alert {
      border-radius: 8px;
      padding: 12px 15px;
      margin-bottom: 20px;
    }
    
    .login-link {
      text-align: center;
      margin-top: 20px;
      color: #666;
    }
    
    .login-link a {
      color: var(--primary);
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s;
    }
    
    .login-link a:hover {
      text-decoration: underline;
    }
    
    .form-feedback {
      font-size: 0.85rem;
      margin-top: 5px;
      display: block;
    }
    
    .featured-offers {
      background-color: rgba(213, 0, 0, 0.05);
      border-radius: 12px;
      padding: 20px;
      margin-top: 30px;
      text-align: center;
      border: 1px dashed var(--primary);
    }
    
    .offer-title {
      font-weight: 600;
      color: var(--primary);
      margin-bottom: 15px;
      font-size: 1.2rem;
    }
    
    .offer-item {
      display: inline-block;
      background: white;
      padding: 10px 20px;
      border-radius: 30px;
      margin: 5px;
      font-weight: 500;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }
    
    @media (max-width: 768px) {
      .card-body {
        padding: 20px;
      }
      
      .brand-header h1 {
        font-size: 2.2rem;
      }
      
      .brand-header p {
        font-size: 1rem;
      }
    }
    
    .animated-input {
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .animated-input:focus {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .form-section {
      margin-bottom: 25px;
    }
    
    .form-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      background-color: rgba(213, 0, 0, 0.1);
      border-radius: 50%;
      margin-right: 15px;
      color: var(--primary);
    }
    
    .form-header {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid var(--light-red);
    }
    
    .form-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--primary);
    }
    
    .banned-warning {
      color: #d32f2f;
      background-color: #ffebee;
      border-left: 4px solid var(--primary);
      padding: 12px 15px;
      margin: 20px 0;
      border-radius: 8px;
      font-size: 14px;
    }
    
    .back-btn {
      position: absolute;
      left: 20px;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255, 255, 255, 0.9);
      border: 2px solid var(--primary);
      color: var(--primary);
      border-radius: 50%;
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      cursor: pointer;
      transition: all 0.3s;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }
    
    .back-btn:hover {
      background-color: var(--primary);
      color: white;
      transform: translateY(-50%) scale(1.05);
    }
    
    .login-btn-container {
      text-align: center;
      margin: 25px 0;
    }
  </style>
</head>
<body>
<div class="container-wrapper">
  <div class="brand-header">
    <div class="back-btn" onclick="window.location.href='index_user.html'">
      <i class="fas fa-arrow-left"></i>
    </div>
    <h1><i class="fas fa-hamburger me-2"></i>FastFood Express</h1>
    <p>Login to your account to continue your delicious journey!</p>
  </div>
  
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-sign-in-alt me-2"></i>Login to Your Account</h2>
        </div>
        <div class="card-body">
          <div class="form-header">
            <div class="form-icon">
              <i class="fas fa-user"></i>
            </div>
            <div class="form-title">Account Login</div>
          </div>
          
          <?php if ($error): ?>
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
          <?php endif; ?>
          
          <div class="banned-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Account Suspension Notice:</strong> Suspended accounts will be unable to login. 
            Contact fastfoodexpress74@gmail.com for assistance.
          </div>
          
          <form method="post" id="loginForm">
            <div class="form-section">
              <label for="email" class="form-label">Email Address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" class="form-control animated-input" id="email" name="email" required 
                       placeholder="Enter your email">
              </div>
            </div>
            
            <div class="form-section">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control animated-input" id="password" name="password" required
                       placeholder="Enter your password">
                <span class="input-group-text toggle-pass" onclick="togglePassword('password', this)">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
            </div>

            <div class="login-btn-container">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt me-2"></i>Login Now
              </button>
            </div>
          </form>
               
          <div class="login-link mt-4">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
            <p><a href="reset_password.php" class="text-danger"><i class="fas fa-key me-1"></i>Forgot Password?</a></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId, toggleBtn) {
  const input = document.getElementById(fieldId);
  const icon = toggleBtn.querySelector('i');
  
  if (input.type === "password") {
    input.type = "text";
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = "password";
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}

// Form validation
function validateForm() {
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  
  if (!email || !password) {
    alert('Please fill in all fields');
    return false;
  }
  
  return true;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('loginForm');
  
  // Form submission validation
  form.addEventListener('submit', function(e) {
    if (!validateForm()) {
      e.preventDefault();
    }
  });
});
</script>
</body>
</html>