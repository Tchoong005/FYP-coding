<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] === false) {
    header("Location: admin_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div style="padding: 20px;">
  <h1>Admin Dashboard</h1>
  <p>Welcome, 
    <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></strong>
    (<?php echo $_SESSION['admin_logged_in']; ?>)
  </p>
  <p>Here you can manage limited features, orders, etc.</p>
</div>

</body>
</html>
