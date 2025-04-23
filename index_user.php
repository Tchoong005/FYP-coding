<?php
session_start();
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

    .cat-box {
      width: 120px;
      padding: 20px;
      background: #fff6f6;
      border: 2px solid #d6001c;
      border-radius: 16px;
      font-weight: bold;
    }

    .offers-section {
      text-align: center;
      padding: 40px 20px;
      background: #fff8f8;
    }

    .offer-card {
      display: block;
      margin: 20px auto;
      padding: 20px;
      border: 1px solid #ddd;
      border-radius: 10px;
      max-width: 300px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .offer-card img {
      width: 100%;
      border-radius: 8px;
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

<!-- ✅ 顶部导航栏 -->
<div class="topbar">
  <div class="logo">🍔 FastFood Express</div>
  <div>
    <a href="index_user.php">Home</a>
    <a href="products_user.php">Products</a>
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
    <a href="logout.php">Logout</a>
  </div>
</div>

<!-- ✅ Hero Banner -->
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

<!-- ✅ Menu Categories -->
<div class="categories">
  <h2 data-aos="fade-up">Menus</h2>
  <div class="cat-grid">
    <div class="cat-box" data-aos="fade-up">🍔 Combo</div>
    <div class="cat-box" data-aos="fade-up" data-aos-delay="100">🍕 Pizza</div>
    <div class="cat-box" data-aos="fade-up" data-aos-delay="200">🥪 Burger</div>
    <div class="cat-box" data-aos="fade-up" data-aos-delay="300">🍹 Kids Menu</div>
  </div>
</div>

<!-- ✅ Latest Offers / New Items -->
<div class="offers-section">
  <h2 data-aos="fade-up">Latest Offers & New Items</h2>

  <div class="offer-card" data-aos="zoom-in">
    <img src="https://www.shutterstock.com/image-photo/double-decker-crispy-chicken-doppler-600nw-2253091785.jpg" alt="Double Chicken">
    <h3>Double Chicken Delight</h3>
    <p>Double the crunch, double the fun. Try our new limited-time offer!</p>
  </div>

  <div class="offer-card" data-aos="zoom-in" data-aos-delay="100">
    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTGzLcecaziC9mvGA6FEa3KAwYgSA6P1iwxbg&s" alt="Chicken Tenders">
    <h3>Chicken Tenders Box</h3>
    <p>Crispy and juicy chicken tenders perfect for dipping!</p>
  </div>
</div>

<!-- ✅ Footer -->
<div class="footer">
  © 2025 FastFood Express. All rights reserved.
</div>

<!-- AOS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init();
</script>

</body>
</html>
