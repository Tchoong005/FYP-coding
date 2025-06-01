<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['quantity'])) {
            $cart_count += (int)$item['quantity'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contact Us - FastFood Express</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #d6001c;
      --primary-dark: #b50018;
      --text: #1f2937;
      --border: #e5e7eb;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: #f5f5f5;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      color: var(--text);
    }

    .topbar {
      background-color: #222;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 30px;
      flex-wrap: wrap;
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .topbar .logo {
      font-size: 24px;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .topbar .logo span {
      color: var(--primary);
    }

    .topbar .nav-links {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .topbar a {
      color: white;
      text-decoration: none;
      font-weight: bold;
      padding: 0 10px;
      transition: color 0.3s;
    }

    .topbar a:hover {
      color: var(--primary);
    }

    .cart-icon {
      position: relative;
      cursor: pointer;
      font-size: 20px;
    }

    .cart-icon::after {
      content: attr(data-count);
      position: absolute;
      top: -6px;
      right: -10px;
      background: var(--primary);
      color: white;
      border-radius: 12px;
      padding: 2px 8px;
      font-size: 12px;
      font-weight: bold;
      min-width: 20px;
      text-align: center;
    }

    .dropdown {
      position: relative;
    }

    .dropbtn {
      background: none;
      color: white;
      font-weight: bold;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .dropdown-content {
      display: none;
      position: absolute;
      background-color: #333;
      min-width: 180px;
      box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
      border-radius: 4px;
      top: 100%;
      left: 0;
    }

    .dropdown-content a {
      color: white;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
      font-size: 14px;
      border-bottom: 1px solid #444;
    }

    .dropdown-content a:hover {
      background-color: var(--primary);
    }

    .dropdown:hover .dropdown-content {
      display: block;
    }

    .active-link::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 10px;
      right: 10px;
      height: 3px;
      background: var(--primary);
      border-radius: 2px;
    }

    .contact-hero {
      padding: 100px 20px;
      background: linear-gradient(135deg, #ffecec 0%, #ffffff 100%);
      text-align: center;
    }

    .contact-hero h1 {
      font-size: 42px;
      color: var(--primary);
      margin-bottom: 20px;
    }

    .contact-hero p {
      font-size: 18px;
      color: var(--text);
      max-width: 700px;
      margin: 0 auto;
    }

    .contact-grid {
      display: flex;
      justify-content: center;
      padding: 80px 20px;
      min-height: 60vh;
    }

    .info-box {
      background: #fff6f6;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      max-width: 700px;
    }

    .info-box h3 {
      color: var(--primary);
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 22px;
    }

    .info-box p {
      color: var(--text);
      line-height: 1.8;
      margin-bottom: 15px;
      font-size: 17px;
    }

    .info-box a {
      color: inherit;
      text-decoration: none;
      font-weight: bold;
    }

    .info-box a:hover {
      text-decoration: underline;
      color: var(--primary-dark);
    }

    .footer {
      background-color: #eee;
      text-align: center;
      padding: 20px;
      font-size: 14px;
      margin-top: auto;
    }

    @media (max-width: 768px) {
      .topbar {
        padding: 12px 15px;
      }

      .contact-hero h1 {
        font-size: 32px;
      }

      .info-box {
        width: 100%;
      }
    }
  </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <div class="logo"><i class="fas fa-hamburger"></i> Fast<span>Food</span> Express</div>
  <div class="nav-links">
    <a href="index_user.php">Home</a>
    <div class="dropdown">
      <button class="dropbtn">Orders <span class="dropdown-icon">▼</span></button>
      <div class="dropdown-content">
        <a href="products_user.php">Products</a>
        <a href="order_trace.php">Order Trace</a>
        <a href="order_history.php">Order History</a>
      </div>
    </div>
    <a href="profile.php">Profile</a>
    <a href="about.php">About</a>
    <a href="contact.php" class="active-link">Contact</a>
    <a href="logout.php">Logout</a>
    <div class="cart-icon" data-count="<?php echo $cart_count; ?>" onclick="location.href='order_list.php'"><i class="fas fa-shopping-cart"></i></div>
  </div>
</div>

<!-- Hero -->
<div class="contact-hero">
  <h1>Contact Us</h1>
  <p>We'd love to hear from you! Here's how to reach us. 🍟</p>
</div>

<!-- Info Section -->
<div class="contact-grid">
  <div class="info-box" data-aos="fade-up">
    <h3><i class="fas fa-phone-alt"></i> Phone</h3>
    <p>016-774 8568</p>

    <h3><i class="fas fa-envelope"></i> Email</h3>
    <p>fastfoodexpress74@gmail.com</p>

    <h3><i class="fas fa-clock"></i> Opening Hours</h3>
    <p>Monday–Friday: 9:00 AM – 10:00 PM</p>
    <p>Weekend: 10:00 AM – 11:00 PM</p>

    <h3><i class="fab fa-facebook-f"></i> Facebook</h3>
    <p><a href="https://www.facebook.com/FastFoodExpress" target="_blank">Fast FoodExpress</a></p>
  </div>
</div>

<!-- Footer -->
<footer class="footer">
  &copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.
</footer>

<!-- AOS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init({ duration: 800, once: true });
</script>

</body>
</html>
