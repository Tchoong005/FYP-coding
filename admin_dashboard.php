<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
</head>
<body>
<?php include 'header.php'; ?>
<h1>Welcome to the Admin Dashboard!</h1>
<p>You are logged in as superadmin.</p>
</body>
</html>
