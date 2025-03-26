<?php
session_start();
// If not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Fast Food Ordering - User Home</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Example top bar / header -->
<div class="topbar">
  <div class="container">
    <a href="index_user.php" class="logo">Fast Food Ordering</a>
    <ul class="topbar-links">
      <li><a href="#">Order Now</a></li>
      <li><a href="logout.php">Logout</a></li>  <!-- Logout link -->
    </ul>
  </div>
</div>

<!-- Alternatively, if you have a separate header.php, include it and 
     add the logout link there, for example:
<?php // include 'header.php'; ?>
-->

<!-- Hero / Banner -->
<div class="hero-section">
  <div class="hero-text">
    <h1>Welcome!</h1>
    <p>You are logged in. Feel free to order now.</p>
  </div>
  <div class="hero-image">
    <img src="images/banner.jpg" alt="Promo Banner">
  </div>
</div>

<!-- Categories Section -->
<div class="categories-section">
  <ul>
    <li>
      <img src="images/ktown.png" alt="K-Town">
      <p>K-Town</p>
    </li>
    <li>
      <img src
