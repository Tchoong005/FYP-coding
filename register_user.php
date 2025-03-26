<?php
session_start();
// If already logged in, redirect to user home
if (isset($_SESSION['user_id'])) {
    header("Location: index_user.php");
    exit();
}

$error = '';
// Server-side validation for password conditions in registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName  = trim($_POST['last_name']);
    $mobile    = trim($_POST['mobile']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];
    $dob       = trim($_POST['dob']);
    
    // Combine first and last name to get full name
    $fullName = $firstName . ' ' . $lastName;
    
    // Validate password length exactly 12 characters
    if (strlen($password) !== 12) {
        $error = "Password must be exactly 12 characters.";
    }
    // Validate password contains at least one letter
    elseif (!preg_match('/[A-Za-z]/', $password)) {
        $error = "Password must contain at least one letter.";
    }
    // Validate password contains at least one digit
    elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one digit.";
    }
    // Validate password contains at least one symbol (non-alphanumeric)
    elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = "Password must contain at least one symbol.";
    }
    // Check if passwords match
    elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    }
    
    // If no error, store user in the simulated "database" (session array)
    if (empty($error)) {
        // Simulate a database by using $_SESSION['users'] array
        if (!isset($_SESSION['users'])) {
            $_SESSION['users'] = array();
        }
        // Optionally check if user already exists (by full name)
        if (isset($_SESSION['users'][$fullName])) {
            $error = "User already exists. Please log in.";
        } else {
            // Store the user; hash the password for security
            $_SESSION['users'][$fullName] = array(
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'mobile'     => $mobile,
                'email'      => $email,
                'password'   => password_hash($password, PASSWORD_DEFAULT),
                'dob'        => $dob
            );
            // Redirect to login page after successful registration
            header("Location: login_user.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <!-- Link to the separate register CSS -->
  <link rel="stylesheet" href="register_style.css">
  <script>
    // Client-side password validation for register page
    function validateRegisterForm() {
      var pwd = document.getElementById("password").value;
      var confirmPwd = document.getElementById("confirmPassword").value;
      if (pwd.length !== 12) {
          alert("Password must be exactly 12 characters.");
          return false;
      }
      if (!/[A-Za-z]/.test(pwd)) {
          alert("Password must contain at least one letter.");
          return false;
      }
      if (!/[0-9]/.test(pwd)) {
          alert("Password must contain at least one digit.");
          return false;
      }
      if (!/[^A-Za-z0-9]/.test(pwd)) {
          alert("Password must contain at least one symbol.");
          return false;
      }
      if (pwd !== confirmPwd) {
          alert("Passwords do not match.");
          return false;
      }
      return true;
    }
  </script>
</head>
<body>

<!-- Include your existing header -->
<?php include 'header.php'; ?>

<div class="register-container">
  <h2>Let's Get Started!</h2>
  <p class="subtitle">
    Sign up now to enjoy finger lickin' good deals and rewards!<br>
    It's absolutely free!
  </p>

  <?php if (!empty($error)): ?>
    <div class="error-msg"><?php echo $error; ?></div>
  <?php endif; ?>

  <form action="" method="post" onsubmit="return validateRegisterForm();">
    <div class="form-group">
      <label for="firstName">First Name*</label>
      <input type="text" id="firstName" name="first_name" placeholder="Enter your first name" required>
    </div>

    <div class="form-group">
      <label for="lastName">Last Name*</label>
      <input type="text" id="lastName" name="last_name" placeholder="Enter your last name" required>
    </div>

    <div class="form-group">
      <label for="mobile">Mobile Number*</label>
      <input type="text" id="mobile" name="mobile" placeholder="Enter your mobile number" required>
    </div>

    <div class="form-group">
      <label for="email">Email*</label>
      <input type="email" id="email" name="email" placeholder="Enter your email address" required>
    </div>

    <div class="form-group">
      <label for="password">Password* (12 characters, include letter, digit, symbol)</label>
      <input type="password" id="password" name="password" placeholder="Enter your password" required>
    </div>

    <div class="form-group">
      <label for="confirmPassword">Confirm Password*</label>
      <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter your password" required>
    </div>

    <div class="form-group">
      <label for="dob">Date of Birth</label>
      <input type="text" id="dob" name="dob" placeholder="DD-MM-YYYY">
      <div class="small-note">Date of birth can't be changed once submitted.</div>
    </div>

    <div class="checkbox-group">
      <input type="checkbox" id="news" name="news">
      <label for="news">I want the latest news and promotions!</label>
    </div>

    <div class="checkbox-group">
      <input type="checkbox" id="terms" name="terms" required>
      <label for="terms">
        I accept the <strong>Terms and Condition</strong> and Privacy Policy.
      </label>
    </div>

    <div class="recaptcha-box">
      [ reCAPTCHA widget goes here ]
    </div>

    <button type="submit" class="register-btn">Register Now</button>
  </form>
</div>

</body>
</html>
