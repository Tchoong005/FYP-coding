<?php
session_start();
include 'db.php';

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = $success = "";

function generateOTP($length = 6) {
    return str_pad(random_int(0, pow(10, $length)-1), $length, '0', STR_PAD_LEFT);
}

// Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone    = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm  = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!preg_match('/^011\d{8}$/', $phone) && !preg_match('/^01[2-9]\d{7}$/', $phone)) {
        $error = "Phone number must start with 011 (11 digits) or 012–019 (10 digits).";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
        $error = "Password must be at least 8 characters with uppercase, lowercase, and number.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // 检查 email, phone 和 username 是否已存在
        $check = mysqli_query($conn, "SELECT * FROM customers WHERE email='$email' OR phone='$phone' OR username='$username'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email, phone number, or username already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $otp = generateOTP();

            $insert = "INSERT INTO customers (email, username, phone, password, verification_code, is_verified)
                       VALUES ('$email', '$username', '$phone', '$hashed_password', '$otp', 0)";
            if (mysqli_query($conn, $insert)) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'yewshunyaodennis@gmail.com';
                    $mail->Password = 'ydgu hfqw qgjh daqg';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('yewshunyaodennis@gmail.com', 'FastFood Express');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Your FastFood Express OTP Code';
                    $mail->Body    = "Hi $username,<br><br>Your OTP code is: <strong>$otp</strong>";

                    $mail->send();
                    $_SESSION['pending_email'] = $email;
                    $_SESSION['otp_sent_time'] = time();
                    header("Location: verify_code.php");
                    exit();
                } catch (Exception $e) {
                    $error = "Email sending failed.";
                }
            } else {
                $error = "Registration failed.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register - FastFood Express</title>
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
    }
    
    .btn-primary:hover {
      background-color: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(213, 0, 0, 0.2);
    }
    
    .btn-primary:active {
      transform: translateY(0);
    }
    
    .password-strength {
      margin-top: 8px;
      height: 5px;
      border-radius: 3px;
      background-color: #e9ecef;
      overflow: hidden;
      margin-bottom: 15px;
    }
    
    .password-strength-bar {
      height: 100%;
      width: 0;
      transition: width 0.3s;
    }
    
    .password-requirements {
      background-color: #f8f9fa;
      border-left: 4px solid var(--primary);
      padding: 12px 15px;
      border-radius: 4px;
      margin-top: 20px;
    }
    
    .password-requirements ul {
      margin-bottom: 0;
      padding-left: 20px;
    }
    
    .password-requirements li {
      margin-bottom: 5px;
    }
    
    .requirement {
      display: flex;
      align-items: center;
    }
    
    .requirement i {
      margin-right: 8px;
      width: 20px;
      text-align: center;
    }
    
    .valid {
      color: var(--success);
    }
    
    .invalid {
      color: var(--danger);
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
    
    .valid-feedback {
      color: var(--success);
    }
    
    .invalid-feedback {
      color: var(--danger);
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
  </style>
</head>
<body>
<div class="container-wrapper">
  <div class="brand-header">
    <h1><i class="fas fa-hamburger me-2"></i>FastFood Express</h1>
    <p>Register now to enjoy our delicious food and exclusive offers!</p>
  </div>
  
  <div class="row justify-content-center">
    <div class="col-lg-10">
      <div class="card">
        <div class="card-header">
          <h2><i class="fas fa-user-plus me-2"></i>Create Your Account</h2>
        </div>
        <div class="card-body">
          <div class="form-header">
            <div class="form-icon">
              <i class="fas fa-user"></i>
            </div>
            <div class="form-title">Account Information</div>
          </div>
          
          <?php if ($error): ?>
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            </div>
          <?php endif; ?>
          
          <?php if ($success): ?>
            <div class="alert alert-success">
              <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
          <?php endif; ?>
          
          <form method="post" id="registrationForm">
            <div class="form-section">
              <label for="email" class="form-label">Email Address</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" class="form-control animated-input" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="Enter your email">
              </div>
              <div class="form-feedback" id="emailFeedback"></div>
            </div>
            
            <div class="form-section">
              <label for="username" class="form-label">Username</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-user"></i></span>
                <input type="text" class="form-control animated-input" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       placeholder="Choose a username">
              </div>
              <div class="form-feedback" id="usernameFeedback"></div>
            </div>
            
            <div class="form-section">
              <label for="phone" class="form-label">Phone Number</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                <input type="tel" class="form-control animated-input" id="phone" name="phone" required 
                       pattern="^(011\d{8}|01[2-9]\d{7})$"
                       title="Phone number must start with 011 (11 digits) or 012–019 (10 digits)"
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                       placeholder="01XXXXXXXX">
              </div>
              <div class="form-feedback" id="phoneFeedback"></div>
              <small class="text-muted">Format: 011XXXXXXXX or 01[2-9]XXXXXXX</small>
            </div>
            
            <div class="form-section">
              <label for="password" class="form-label">Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control animated-input" id="password" name="password" required
                       placeholder="Create a password">
                <span class="input-group-text toggle-pass" onclick="togglePassword('password', this)">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
              <div class="password-strength mt-2">
                <div class="password-strength-bar" id="passwordStrengthBar"></div>
              </div>
              <div class="form-feedback" id="passwordFeedback"></div>
            </div>
            
            <div class="form-section">
              <label for="confirm_password" class="form-label">Confirm Password</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                <input type="password" class="form-control animated-input" id="confirm_password" name="confirm_password" required
                       placeholder="Confirm your password">
                <span class="input-group-text toggle-pass" onclick="togglePassword('confirm_password', this)">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
              <div class="form-feedback" id="confirmPasswordFeedback"></div>
            </div>
            
            <div class="password-requirements">
              <p class="mb-2"><strong>Password Requirements:</strong></p>
              <ul class="list-unstyled">
                <li class="requirement" id="reqLength">
                  <i class="fas fa-circle"></i>
                  <span>At least 8 characters</span>
                </li>
                <li class="requirement" id="reqUppercase">
                  <i class="fas fa-circle"></i>
                  <span>At least one uppercase letter</span>
                </li>
                <li class="requirement" id="reqLowercase">
                  <i class="fas fa-circle"></i>
                  <span>At least one lowercase letter</span>
                </li>
                <li class="requirement" id="reqNumber">
                  <i class="fas fa-circle"></i>
                  <span>At least one number</span>
                </li>
              </ul>
            </div>
            
            <div class="d-grid mt-4">
              <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-user-plus me-2"></i>Register Now
              </button>
            </div>
          </form>
               
          <div class="login-link mt-4">
            <p>Already have an account? <a href="login.php">Login here</a></p>
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

// Password strength indicator
function checkPasswordStrength(password) {
  let strength = 0;
  const requirements = {
    length: false,
    uppercase: false,
    lowercase: false,
    number: false
  };
  
  // Check password length
  if (password.length >= 8) {
    strength += 25;
    requirements.length = true;
  }
  
  // Check for uppercase letters
  if (/[A-Z]/.test(password)) {
    strength += 25;
    requirements.uppercase = true;
  }
  
  // Check for lowercase letters
  if (/[a-z]/.test(password)) {
    strength += 25;
    requirements.lowercase = true;
  }
  
  // Check for numbers
  if (/\d/.test(password)) {
    strength += 25;
    requirements.number = true;
  }
  
  // Update password strength bar
  const strengthBar = document.getElementById('passwordStrengthBar');
  strengthBar.style.width = strength + '%';
  
  if (strength < 50) {
    strengthBar.style.backgroundColor = '#dc3545';
  } else if (strength < 75) {
    strengthBar.style.backgroundColor = '#ffc107';
  } else {
    strengthBar.style.backgroundColor = '#28a745';
  }
  
  // Update requirement indicators
  updateRequirement('reqLength', requirements.length);
  updateRequirement('reqUppercase', requirements.uppercase);
  updateRequirement('reqLowercase', requirements.lowercase);
  updateRequirement('reqNumber', requirements.number);
  
  return strength;
}

// Update requirement indicator
function updateRequirement(elementId, isValid) {
  const element = document.getElementById(elementId);
  const icon = element.querySelector('i');
  
  if (isValid) {
    icon.classList.remove('fa-circle');
    icon.classList.add('fa-check-circle', 'valid');
    element.style.color = '#28a745';
  } else {
    icon.classList.remove('fa-check-circle', 'valid');
    icon.classList.add('fa-circle');
    element.style.color = '';
  }
}

// Form validation
function validateForm() {
  let isValid = true;
  const email = document.getElementById('email').value;
  const username = document.getElementById('username').value;
  const phone = document.getElementById('phone').value;
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirm_password').value;
  
  // Validate email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const emailFeedback = document.getElementById('emailFeedback');
  if (!emailRegex.test(email)) {
    emailFeedback.textContent = 'Please enter a valid email address.';
    emailFeedback.className = 'form-feedback invalid-feedback';
    isValid = false;
  } else {
    emailFeedback.textContent = '';
    emailFeedback.className = 'form-feedback';
  }
  
  // Validate username
  const usernameFeedback = document.getElementById('usernameFeedback');
  if (username.length < 3) {
    usernameFeedback.textContent = 'Username must be at least 3 characters.';
    usernameFeedback.className = 'form-feedback invalid-feedback';
    isValid = false;
  } else {
    usernameFeedback.textContent = '';
    usernameFeedback.className = 'form-feedback';
  }
  
  // Validate phone
  const phoneRegex = /^(011\d{8}|01[2-9]\d{7})$/;
  const phoneFeedback = document.getElementById('phoneFeedback');
  if (!phoneRegex.test(phone)) {
    phoneFeedback.textContent = 'Phone number must start with 011 (11 digits) or 012–019 (10 digits).';
    phoneFeedback.className = 'form-feedback invalid-feedback';
    isValid = false;
  } else {
    phoneFeedback.textContent = '';
    phoneFeedback.className = 'form-feedback';
  }
  
  // Validate password
  const passwordFeedback = document.getElementById('passwordFeedback');
  const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;
  if (!passwordRegex.test(password)) {
    passwordFeedback.textContent = 'Password must be at least 8 characters with uppercase, lowercase, and number.';
    passwordFeedback.className = 'form-feedback invalid-feedback';
    isValid = false;
  } else {
    passwordFeedback.textContent = '';
    passwordFeedback.className = 'form-feedback';
  }
  
  // Validate password confirmation
  const confirmFeedback = document.getElementById('confirmPasswordFeedback');
  if (password !== confirmPassword) {
    confirmFeedback.textContent = 'Passwords do not match.';
    confirmFeedback.className = 'form-feedback invalid-feedback';
    isValid = false;
  } else {
    confirmFeedback.textContent = '';
    confirmFeedback.className = 'form-feedback';
  }
  
  return isValid;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
  const passwordInput = document.getElementById('password');
  const confirmInput = document.getElementById('confirm_password');
  const form = document.getElementById('registrationForm');
  
  // Password strength and requirement checks
  passwordInput.addEventListener('input', function() {
    checkPasswordStrength(this.value);
  });
  
  // Confirm password check
  confirmInput.addEventListener('input', function() {
    const password = passwordInput.value;
    const confirmFeedback = document.getElementById('confirmPasswordFeedback');
    
    if (this.value && password !== this.value) {
      confirmFeedback.textContent = 'Passwords do not match.';
      confirmFeedback.className = 'form-feedback invalid-feedback';
    } else if (this.value && password === this.value) {
      confirmFeedback.textContent = 'Passwords match!';
      confirmFeedback.className = 'form-feedback valid-feedback';
    } else {
      confirmFeedback.textContent = '';
      confirmFeedback.className = 'form-feedback';
    }
  });
  
  // Form submission validation
  form.addEventListener('submit', function(e) {
    if (!validateForm()) {
      e.preventDefault();
    }
  });
  
  // Initial password strength check if there's a value
  if (passwordInput.value) {
    checkPasswordStrength(passwordInput.value);
  }
});
</script>
</body>
</html>