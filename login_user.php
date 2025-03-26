<?php
session_start();
require_once 'connection.php'; // This file should create a $conn connection object

// If already logged in, redirect to user home
if (isset($_SESSION['user_id'])) {
    header("Location: index_user.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $password = $_POST['password'];

    // Prepare a statement to fetch the hashed password from Users table by full name
    $stmt = $conn->prepare("SELECT User_password FROM Users WHERE Full_name = ?");
    if (!$stmt) {
        $error = "Database error: " . $conn->error;
    } else {
        $stmt->bind_param("s", $fullName);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            $error = "User not found. Please register first.";
        } else {
            $stmt->bind_result($storedPassword);
            $stmt->fetch();
            // Compare the entered password with the stored hashed password
            if (password_verify($password, $storedPassword)) {
                $_SESSION['user_id'] = $fullName; // you might use a unique id instead
                header("Location: index_user.php");
                exit();
            } else {
                $error = "Invalid password.";
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Login</title>
  <link rel="stylesheet" href="login_style.css">
  <script>
    // Simple client-side validation
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

<!-- Include your existing header so your header remains -->
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
      <input 
        type="text" 
        id="full_name" 
        name="full_name" 
        placeholder="e.g. Tan Chun Hoong" 
        autocomplete="off" 
        required>
    </div>
    <div class="input-group">
      <label for="password">Password</label>
      <input 
        type="password" 
        id="password" 
        name="password" 
        placeholder="Enter your password" 
        autocomplete="off" 
        required>
    </div>
    <button type="submit" class="login-btn">Log In</button>
  </form>
  
  <div class="or-line">OR</div>
  
  <div class="social-buttons">
    <button>Continue with Google</button>
    <button>Continue with Facebook</button>
  </div>
  
  <!-- Bottom links, each on its own line, centered -->
  <div class="bottom-links">
    <p>Don't have an account? <a href="register_user.php">Sign up now!</a></p>
    <p><a href="admin_login.php">Admin Login</a></p>
    <p><a href="index.php">Continue as Guest</a></p>
  </div>
</div>

</body>
</html>
