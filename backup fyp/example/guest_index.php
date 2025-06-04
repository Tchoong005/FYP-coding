<?php
session_start();
// If user is already logged in, redirect to user home
if (isset($_SESSION['user_id'])) {
    header("Location: index_user.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Fast Food Ordering - Visitor Home</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'header_guest.php'; ?>

<!-- Hero / Banner -->
<div class="hero-section">
  <div class="hero-text">
    <h1>RM 17 OFF</h1>
    <p>Order Ahead from 2-5 PM</p>
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
      <img src="images/giftvoucher.png" alt="Gift Voucher">
      <p>Gift Voucher</p>
    </li>
    <li>
      <img src="images/catering.png" alt="Catering">
      <p>Catering</p>
    </li>
    <li>
      <img src="images/party.png" alt="Party">
      <p>Party</p>
    </li>
  </ul>
</div>

<!-- Promotions Section -->
<div class="promotions-section">
  <ul>
    <li>
      <img src="images/promo1.jpg" alt="Promo 1">
      <p>RM 17 OFF All Day</p>
    </li>
    <li>
      <img src="images/promo2.jpg" alt="Promo 2">
      <p>RM 7 OFF from 2-5 PM</p>
    </li>
  </ul>
</div>

</body>
</html>
