<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>FastFood Express - Home</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #fff;
    }
    .topbar {
      background-color: #222;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 30px;
    }
    .topbar .logo {
      font-size: 24px;
      font-weight: bold;
    }
    .topbar a {
      color: white;
      text-decoration: none;
      margin-left: 20px;
      font-weight: bold;
    }
    .hero {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 60px;
      background: #ffecec;
    }
    .hero-text {
      max-width: 50%;
    }
    .hero-text h1 {
      font-size: 48px;
      color: #d6001c;
    }
    .hero-text p {
      font-size: 18px;
    }
    .hero-text .btn {
      background: #d6001c;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 8px;
      margin-top: 15px;
      cursor: pointer;
      font-weight: bold;
      text-decoration: none;
    }
    .hero-image img {
      width: 400px;
      border-radius: 16px;
    }
    .categories {
      text-align: center;
      padding: 50px 20px;
    }
    .cat-grid {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 30px;
      margin-top: 20px;
    }
    .cat-box a {
      display: block;
      width: 150px;
      padding: 20px;
      background: #d6001c;
      color: white;
      border-radius: 16px;
      font-weight: bold;
      text-decoration: none;
      transition: background 0.3s ease, transform 0.2s ease;
    }
    .cat-box a:hover {
      background: #a50013;
      transform: scale(1.05);
    }
    .footer {
      background-color: #eee;
      text-align: center;
      padding: 20px;
      font-size: 14px;
    }
  </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <div class="logo">🍔 FastFood Express</div>
  <div>
    <a href="index_user.php">Home</a>
    <a href="products_user.php">Products</a>
    <a href="profile.php">Profile</a>
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
    <a href="logout.php">Logout</a>
  </div>
</div>

<!-- Hero Section -->
<div class="hero" data-aos="fade-up">
  <div class="hero-text">
    <h1 data-aos="zoom-in">BEST BURGERS IN GALAXY</h1>
    <p data-aos="fade-right">We craft juicy burgers to satisfy your cravings. Order now and enjoy greatness!</p>
    <a href="products_user.php" class="btn" data-aos="fade-up" data-aos-delay="200">Go Order</a>
  </div>
  <div class="hero-image" data-aos="zoom-in">
    <img src="https://images.unsplash.com/photo-1600891964599-f61ba0e24092?auto=format&fit=crop&w=800&q=80" alt="Burger">
  </div>
</div>

<!-- Categories Section -->
<div class="categories">
  <h2 data-aos="fade-up">Menus</h2>
  <div class="cat-grid">
    <div class="cat-box" data-aos="fade-up">
      <a href="products_user.php?category=beverages">🥤 Beverages</a>
    </div>
    <div class="cat-box" data-aos="fade-up" data-aos-delay="100">
      <a href="products_user.php?category=chicken">🍗 Chicken</a>
    </div>
    <div class="cat-box" data-aos="fade-up" data-aos-delay="200">
      <a href="products_user.php?category=burger">🍔 Burger</a>
    </div>
    <div class="cat-box" data-aos="fade-up" data-aos-delay="300">
      <a href="products_user.php?category=desserts_sides">🍰 Desserts & Sides</a>
    </div>
  </div>
</div>

<!-- Footer -->
<div class="footer">
  © 2025 FastFood Express. All rights reserved.
</div>

<!-- AOS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init();</script>

</body>
</html>
