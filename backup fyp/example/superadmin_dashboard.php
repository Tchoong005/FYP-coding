<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== 'superadmin') {
    header("Location: admin_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Superadmin Dashboard</title>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div style="padding: 20px;">
  <h1>Superadmin Dashboard</h1>
  <p>Welcome, superadmin: 
    <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?></strong>
  </p>
  <p>Here you can manage everything, including other admins.</p>
</div>

</body>
</html>
