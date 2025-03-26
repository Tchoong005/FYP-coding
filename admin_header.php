<?php
session_start();
?>
<link rel="stylesheet" href="admin_header.css">
<div class="admin-header">
  <div class="header-left">
    <!-- Hamburger icon (if needed) -->
    <span class="menu-icon" onclick="openPanel()">&#9776;</span>
    <!-- Brand (link back to index.php) -->
    <div class="brand-logo"><a href="index.php">Fast Food Ordering</a></div>
  </div>
  <div class="header-right">
    <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>
      <!-- When logged in, show admin profile image and username -->
      <div class="admin-profile">
        <img src="images/admin_profile.png" alt="Admin Profile">
        <span class="admin-name"><?php echo $_SESSION['admin_username']; ?></span>
      </div>
    <?php else: ?>
      <a href="admin_login.php">Admin Login</a>
    <?php endif; ?>
    <!-- Link to return to index -->
    <a href="index.php" class="return-index">Return to Index</a>
  </div>
</div>

<!-- Optional: Side Panel (if you want a sliding menu) -->
<div class="overlay" id="overlay"></div>
<div class="side-panel" id="sidePanel">
  <span class="close-btn" onclick="closePanel()">&times;</span>
  <ul>
    <li>Dashboard</li>
    <li>Manage Users</li>
    <li>Reports</li>
    <li>Settings</li>
  </ul>
</div>

<script>
function openPanel() {
  document.getElementById('sidePanel').classList.add('open');
  document.getElementById('overlay').classList.add('show');
}
function closePanel() {
  document.getElementById('sidePanel').classList.remove('open');
  document.getElementById('overlay').classList.remove('show');
}
</script>
