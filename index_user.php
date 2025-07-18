<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Calculate cart count
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
  <title>FastFood Express - Home</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #d6001c;
      --primary-dark: #b50018;
      --secondary: #f9fafb;
      --text: #1f2937;
      --text-light: #6b7280;
      --border: #e5e7eb;
      --success: #10b981;
      --error: #ef4444;
      --warning: #f59e0b;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
    
    /* Top navigation bar styles */
    .topbar {
      background-color: #222;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 30px;
      flex-wrap: wrap;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
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
      line-height: 1.5;
      transition: color 0.3s;
    }
    
    .topbar a:hover {
      color: var(--primary);
    }
    
    .cart-icon {
      position: relative;
      cursor: pointer;
      font-size: 20px;
      padding: 0 10px;
      line-height: 1.5;
      user-select: none;
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
      box-sizing: border-box;
      display: inline-block;
    }
    
    .dropdown {
      position: relative;
      display: inline-block;
    }
    
    .dropbtn {
      background-color: transparent;
      color: white;
      font-weight: bold;
      padding: 0 10px;
      line-height: 1.5;
      font-size: inherit;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: color 0.3s;
    }
    
    .dropbtn:hover {
      color: var(--primary);
    }
    
    .dropdown-content {
      display: none;
      position: absolute;
      background-color: #333;
      min-width: 180px;
      box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
      z-index: 1;
      border-radius: 4px;
      overflow: hidden;
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
      transition: background-color 0.3s;
    }
    
    .dropdown-content a:hover {
      background-color: var(--primary);
    }
    
    .dropdown:hover .dropdown-content {
      display: block;
    }
    
    .dropdown-icon {
      font-size: 14px;
      transition: transform 0.3s;
    }
    
    .dropdown:hover .dropdown-icon {
      transform: rotate(180deg);
    }
    
    .active-link {
      position: relative;
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
    
    .hero {
      position: relative;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      height: 500px;
      background-image: url('https://images.unsplash.com/photo-1600891964599-f61ba0e24092?auto=format&fit=crop&w=1600&q=80');
      background-size: cover;
      background-position: center;
      color: white;
      overflow: hidden;
    }
    
    .hero::before {
      content: "";
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: linear-gradient(to right, rgba(0,0,0,0.7), rgba(0,0,0,0.4));
    }
    
    .hero-content {
      position: relative;
      z-index: 1;
      max-width: 800px;
      padding: 0 20px;
    }
    
    .hero-content h1 {
      font-size: 3.5rem;
      margin-bottom: 1rem;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    
    .hero-content p {
      font-size: 1.3rem;
      margin-bottom: 2rem;
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .hero-btn {
      background: var(--primary);
      color: white;
      padding: 14px 28px;
      border: none;
      border-radius: 50px;
      font-weight: bold;
      text-decoration: none;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 1.1rem;
      display: inline-block;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
    
    .hero-btn:hover {
      background: var(--primary-dark);
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.3);
    }
    
    .categories {
      text-align: center;
      padding: 80px 20px;
      background: linear-gradient(135deg, #fff0f0 0%, #ffffff 100%);
    }
    
    .section-title {
      font-size: 2.5rem;
      color: var(--primary);
      margin-bottom: 1.5rem;
      position: relative;
      display: inline-block;
    }
    
    .section-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: var(--primary);
      border-radius: 2px;
    }
    
    .cat-grid {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 30px;
      margin-top: 50px;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .cat-box {
      width: 220px;
      height: 220px;
      perspective: 1000px;
    }
    
    .cat-card {
      width: 100%;
      height: 100%;
      position: relative;
      transform-style: preserve-3d;
      transition: transform 0.8s;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .cat-box:hover .cat-card {
      transform: rotateY(180deg);
    }
    
    .cat-front, .cat-back {
      position: absolute;
      width: 100%;
      height: 100%;
      backface-visibility: hidden;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 20px;
      text-align: center;
    }
    
    .cat-front {
      background: var(--primary);
      color: white;
      font-size: 2.5rem;
    }
    
    .cat-back {
      background: white;
      color: var(--text);
      transform: rotateY(180deg);
      padding: 25px;
    }
    
    .cat-back h3 {
      color: var(--primary);
      margin-bottom: 15px;
      font-size: 1.5rem;
    }
    
    .cat-back p {
      margin-bottom: 20px;
      font-size: 0.95rem;
    }
    
    .cat-btn {
      background: var(--primary);
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 50px;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: bold;
      transition: all 0.3s;
    }
    
    .cat-btn:hover {
      background: var(--primary-dark);
      transform: translateY(-3px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    
    .features {
      padding: 80px 20px;
      background: white;
    }
    
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
      max-width: 1200px;
      margin: 50px auto 0;
    }
    
    .feature-card {
      background: #fff;
      border-radius: 16px;
      padding: 35px 25px;
      text-align: center;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      transition: transform 0.3s;
    }
    
    .feature-card:hover {
      transform: translateY(-10px);
    }
    
    .feature-icon {
      width: 80px;
      height: 80px;
      background: rgba(214, 0, 28, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 25px;
    }
    
    .feature-icon i {
      font-size: 36px;
      color: var(--primary);
    }
    
    .feature-card h3 {
      color: var(--primary);
      margin-bottom: 15px;
      font-size: 1.5rem;
    }
    
    .feature-card p {
      color: var(--text);
      line-height: 1.6;
    }
    
    /* Removed testimonials section */
    
    .footer {
            background-color: #eee;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            margin-top: 40px;
        }
    
    @media (max-width: 768px) {
      .topbar {
        padding: 12px 15px;
      }
      
      .nav-links {
        gap: 10px;
      }
      
      .hero {
        height: 400px;
      }
      
      .hero-content h1 {
        font-size: 2.5rem;
      }
      
      .hero-content p {
        font-size: 1.1rem;
      }
    }
  </style>
</head>
<body>

<!-- Top Navigation Bar -->
<div class="topbar">
    <div class="logo"><i class="fas fa-hamburger"></i> Fast<span>Food</span> Express</div>
    <div class="nav-links">
        <a href="index_user.php" class="active-link">Home</a>
        
        <!-- Orders Dropdown -->
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
        <a href="contact.php">Contact</a>
        <a href="logout.php">Logout</a>
        <div class="cart-icon" data-count="<?php echo $cart_count; ?>" onclick="location.href='order_list.php'"><i class="fas fa-shopping-cart"></i></div>
    </div>
</div>

<!-- Hero Section -->
<div class="hero">
  <div class="hero-content">
    <h1 data-aos="fade-down">BEST BURGERS IN GALAXY</h1>
    <p data-aos="fade-right" data-aos-delay="100">We craft juicy burgers to satisfy your cravings. Order now and enjoy greatness!</p>
    <a href="products_user.php" class="hero-btn" data-aos="fade-up" data-aos-delay="200">
      <i class="fas fa-shopping-bag"></i> Order Now
    </a>
  </div>
</div>

<!-- Categories Section -->
<div class="categories">
  <h2 class="section-title" data-aos="fade-up">Our Menus</h2>
  <p data-aos="fade-up" data-aos-delay="100">Explore our delicious selection of fast food favorites</p>
  
  <div class="cat-grid">
    <div class="cat-box" data-aos="fade-up">
      <div class="cat-card">
        <div class="cat-front">
          <i class="fas fa-coffee"></i>
          <h3>Beverages</h3>
        </div>
        <div class="cat-back">
          <h3>Refreshing Beverages</h3>
          <p>Cool down with our selection of cold drinks, from classic sodas to specialty coffees.</p>
          <a href="products_user.php?category=beverages" class="cat-btn">Browse Drinks</a>
        </div>
      </div>
    </div>
    
    <div class="cat-box" data-aos="fade-up" data-aos-delay="100">
      <div class="cat-card">
        <div class="cat-front">
          <i class="fas fa-drumstick-bite"></i>
          <h3>Chicken</h3>
        </div>
        <div class="cat-back">
          <h3>Crispy Chicken</h3>
          <p>Enjoy our perfectly seasoned and crispy chicken in sandwiches, tenders, and more.</p>
          <a href="products_user.php?category=chicken" class="cat-btn">Browse Chicken</a>
        </div>
      </div>
    </div>
    
    <div class="cat-box" data-aos="fade-up" data-aos-delay="200">
      <div class="cat-card">
        <div class="cat-front">
          <i class="fas fa-hamburger"></i>
          <h3>Burgers</h3>
        </div>
        <div class="cat-back">
          <h3>Juicy Burgers</h3>
          <p>Our signature burgers made with premium beef and fresh ingredients.</p>
          <a href="products_user.php?category=burger" class="cat-btn">Browse Burgers</a>
        </div>
      </div>
    </div>
    
    <div class="cat-box" data-aos="fade-up" data-aos-delay="300">
      <div class="cat-card">
        <div class="cat-front">
          <i class="fas fa-ice-cream"></i>
          <h3>Desserts</h3>
        </div>
        <div class="cat-back">
          <h3>Sweet Treats</h3>
          <p>Indulge in our delicious desserts, from ice creams to cakes and cookies.</p>
          <a href="products_user.php?category=desserts_sides" class="cat-btn">Browse Desserts</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Features Section -->
<div class="features">
  <h2 class="section-title" data-aos="fade-up">Why Choose Us</h2>
  <p data-aos="fade-up" data-aos-delay="100">Discover what makes FastFood Express the best choice</p>
  
  <div class="features-grid">
    <div class="feature-card" data-aos="fade-up">
      <div class="feature-icon">
        <i class="fas fa-bolt"></i>
      </div>
      <h3>Fast Delivery</h3>
      <p>Get your food delivered to your doorstep in under 30 minutes or it's on us!</p>
    </div>
    
    <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
      <div class="feature-icon">
        <i class="fas fa-seedling"></i>
      </div>
      <h3>Fresh Ingredients</h3>
      <p>We use only the freshest ingredients sourced from local suppliers daily.</p>
    </div>
    
    <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
      <div class="feature-icon">
        <i class="fas fa-money-bill-wave"></i>
      </div>
      <h3>Affordable Prices</h3>
      <p>Delicious food doesn't have to break the bank. Enjoy great value with every meal.</p>
    </div>
  </div>
</div>

<!-- Removed Customer Reviews Section -->

<!-- Simplified Footer -->
<footer class="footer">
    &copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.
</footer>

<!-- AOS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 800,
    once: true
  });
</script>

</body>
</html>