<?php
// admin_header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine which profile image to show
$profileImage = "images/admin_profile.png"; // default for normal admin
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === 'superadmin') {
    $profileImage = "images/superadmin_profile.png"; // for superadmin
}
?>
<link rel="stylesheet" href="admin_header.css">

<div class="header">
  <div class="header-left">
    <!-- Hamburger icon -->
    <span class="menu-icon">&#9776;</span>
    <!-- Brand/Logo (same as user header) -->
    <a href="index.php" class="logo">Fast Food Ordering</a>
  </div>
  <div class="header-right">
    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) : ?>
      <!-- Show admin profile image and logout icon -->
      <img src="<?php echo $profileImage; ?>" alt="Admin Profile" class="profile-img">
      <span class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
      <a href="admin_logout.php" class="logout-icon">
        <img src="images/logout_icon.png" alt="Logout">
      </a>
    <?php else : ?>
      <!-- If not logged in, show admin login and return to index links -->
      <a href="admin_login.php" class="header-link">Admin Login</a>
      <a href="index.php" class="header-link">Return to Index</a>
    <?php endif; ?>
  </div>
</div>
