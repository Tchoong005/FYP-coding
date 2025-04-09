<?php
session_start();
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
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      background-color: #fffaf5;
    }

    .topbar {
      background-color: #ff6b6b;
      padding: 15px 30px;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .topbar .logo {
      font-size: 28px;
      font-weight: bold;
      text-decoration: none;
      color: white;
    }

    .topbar-links {
      list-style: none;
      display: flex;
      gap: 25px;
    }

    .topbar-links li a {
      color: white;
      text-decoration: none;
      font-weight: bold;
    }

    .hero-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 50px;
      background: linear-gradient(90deg, #ffe1bd, #ffd6d6);
    }

    .hero-text {
      max-width: 50%;
    }

    .hero-text h1 {
      font-size: 50px;
      color: #ff6b6b;
    }

    .hero-text p {
      font-size: 20px;
      color: #444;
      margin-top: 10px;
    }

    .hero-text a.button {
      display: inline-block;
      margin-top: 20px;
      padding: 12px 25px;
      background-color: #ff6b6b;
      color: white;
      font-weight: bold;
      border-radius: 8px;
      text-decoration: none;
      transition: background-color 0.3s;
    }

    .hero-text a.button:hover {
      background-color: #ff3e3e;
    }

    .hero-image img {
      width: 400px;
      border-radius: 20px;
    }

    .categories-section {
      padding: 60px 30px;
      text-align: center;
    }

    .categories-section h2 {
      font-size: 32px;
      margin-bottom: 30px;
      color: #333;
    }

    .categories-section ul {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
      padding: 0;
      list-style: none;
    }

    .categories-section li {
      background-color: white;
      border-radius: 16px;
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
      width: 220px;
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .categories-section li:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
    }

    .category-link {
      text-decoration: none;
      color: inherit;
      display: block;
      padding: 20px;
    }

    .category-link img {
      width: 100%;
      height: 140px;
      object-fit: cover;
      border-radius: 12px;
    }

    .category-link p {
      font-size: 18px;
      margin-top: 10px;
      font-weight: bold;
    }

    .category-link span {
      display: block;
      font-size: 14px;
      color: #777;
      margin-top: 4px;
    }
  </style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
  <a href="index_user.php" class="logo">🍔 Fast Food Ordering</a>
  <ul class="topbar-links">
    <li><a href="menu_user.php">Order Now</a></li>
    <li><a href="logout.php">Logout</a></li>
  </ul>
</div>

<!-- Hero Section -->
<div class="hero-section">
  <div class="hero-text">
    <h1>Welcome Back, Foodie!</h1>
    <p>Your cravings, just one click away. Delicious meals await.</p>
    <a href="menu_user.php" class="button">Start Ordering 🍽️</a>
  </div>
  <div class="hero-image">
    <img src="images/banner.jpg" alt="Delicious Promo">
  </div>
</div>

<!-- Categories Section -->
<div class="categories-section">
  <h2>Explore Categories</h2>
  <ul>
    <li>
      <a class="category-link" href="menu.php?category=ktown">
        <img src="images/ktown.png" alt="K-Town">
        <p>K-Town</p>
        <span>Try our Korean fried chicken!</span>
      </a>
    </li>
    <li>
      <a class="category-link" href="menu.php?category=western">
        <img src="images/western.png" alt="Western Food">
        <p>Western</p>
        <span>Burgers, fries, steaks & more!</span>
      </a>
    </li>
    <li>
      <a class="category-link" href="menu.php?category=local">
        <img src="images/local.png" alt="Local Delights">
        <p>Local Delights</p>
        <span>Your Malaysian favorites</span>
      </a>
    </li>
    <li>
      <a class="category-link" href="menu.php?category=drinks">
        <img src="images/drinks.png" alt="Drinks">
        <p>Drinks</p>
        <span>Cool down with sweet beverages</span>
      </a>
    </li>
  </ul>
</div>

</body>
</html>
