<?php
session_start();

// If already logged in as admin, redirect to admin dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Check for superadmin credentials
    if ($username === 'admin' && $password === 'admin@12345678') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = 'admin';
        header("Location: admin_dashboard.php");
        exit();
    } else {
        // Check normal admin credentials from a simulated database (stored in $_SESSION['admin_users'])
        if (isset($_SESSION['admin_users']) && isset($_SESSION['admin_users'][$username])) {
            $storedHash = $_SESSION['admin_users'][$username];
            if (password_verify($password, $storedHash)) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Invalid password for admin user.";
            }
        } else {
            $error = "Admin user not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <link rel="stylesheet" href="admin_login.css">
  <script>
    function validateAdminForm() {
      var user = document.getElementById("username").value.trim();
      var pass = document.getElementById("password").value;
      if (user === "" || pass === "") {
        alert("Please fill in all fields.");
        return false;
      }
      return true;
    }
  </script>
</head>
<body>

<!-- Use the admin header -->
<?php include 'admin_header.php'; ?>

<div class="admin-login-container">
  <h2>Admin Login</h2>
  <p>Please enter your admin credentials.</p>
  
  <?php if (!empty($error)): ?>
    <div class="error-msg"><?php echo $error; ?></div>
  <?php endif; ?>
  
  <form method="post" onsubmit="return validateAdminForm();">
    <div class="input-group">
      <label for="username">Username</label>
      <input type="text" name="username" id="username" placeholder="Enter admin username" required>
    </div>
    <div class="input-group">
      <label for="password">Password</label>
      <input type="password" name="password" id="password" placeholder="Enter admin password" required>
    </div>
    <button type="submit" class="login-btn">Login</button>
  </form>
</div>

</body>
</html>
