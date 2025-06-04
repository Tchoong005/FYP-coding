<?php
session_start();
require_once 'connection.php';  // connection.php returns $conn for database connection

// If already logged in, redirect to the appropriate dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] !== false) {
    if ($_SESSION['admin_logged_in'] === 'superadmin') {
        header("Location: superadmin_dashboard.php");
    } else {
        header("Location: admin_dashboard.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Query the Admins table to check if the username exists
    $stmt = $conn->prepare("SELECT admin_password, admin_role FROM Admins WHERE admin_username = ?");
    if (!$stmt) {
        $error = "Database error: " . $conn->error;
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            $error = "Admin user not found.";
        } else {
            $stmt->bind_result($storedPassword, $adminRole);
            $stmt->fetch();
            // Directly compare plain text passwords
            if ($password === $storedPassword) {
                $_SESSION['admin_logged_in'] = ($adminRole === 'superadmin') ? 'superadmin' : 'admin';
                $_SESSION['admin_username'] = $username;
                if ($_SESSION['admin_logged_in'] === 'superadmin') {
                    header("Location: superadmin_dashboard.php");
                } else {
                    header("Location: admin_dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid password for admin user.";
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
  
  <div class="bottom-links">
    <p><a href="index.php">Return to Index</a></p>
  </div>
</div>
</body>
</html>
