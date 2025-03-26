<?php
session_start();
// If already logged in, redirect to user home
if (isset($_SESSION['user_id'])) {
    header("Location: index_user.php");
    exit();
}

$error = '';
// When the form is submitted, check the "database" (stored in $_SESSION['users'])
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $password = $_POST['password'];
    
    // Check if the user exists in our simulated database
    if (isset($_SESSION['users'][$fullName])) {
        $storedHash = $_SESSION['users'][$fullName]['password'];
        if (password_verify($password, $storedHash)) {
            $_SESSION['user_id'] = $fullName;  // using full name as user ID for demo
            header("Location: index_user.php");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found. Please register first.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <!-- Link to the separate login CSS -->
  <link rel="stylesheet" href="login_style.css">
  <script>
    // Client-side validation is optional (login mainly checks the database)
    function validateLoginForm() {
      var fullName = document.getElementById("full_name").value.trim();
      var password = document.getElementById("password").value;
      if (fullName === "") {
          alert("Please enter your full name.");
          return false;
      }
      if (password === "") {
          alert("Please enter your password.");
          return false;
      }
      return true;
    }
  </script>
</head>
<body>

<!-- Include your existing header.php so your header remains -->
<?php include 'header.php'; ?>

<div class="login-container">
  <h2>Log in to enjoy exclusive KFC deals!</h2>
  <p>Please enter your full name and password below.</p>
  
  <?php if (!empty($error)): ?>
    <div class="error-msg"><?php echo $error; ?></div>
  <?php endif; ?>
  
  <form action="" method="post" onsubmit="return validateLoginForm();">
    <div class="input-group">
      <label for="full_name">Full Name</label>
      <input type="text" id="full_name" name="full_name" placeholder="e.g. Tan Chun Hoong" required>
    </div>
    <div class="input-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Enter your password" required>
    </div>
    <button type="submit" class="login-btn">Log In</button>
  </form>
  
  <div class="or-line">OR</div>
  
  <div class="social-buttons">
    <button>Continue with Google</button>
    <button>Continue with Facebook</button>
  </div>
  
  <div class="bottom-links">
    Don't have an account? <a href="register_user.php">Sign up now!</a>
  </div>
  <div class="admin-link">
    <a href="admin_login.php">Admin Login</a>
  </div>
</div>

</body>
</html>
