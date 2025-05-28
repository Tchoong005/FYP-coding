<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// 计算购物车数量
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
  <title>About Us - FastFood Express</title>
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
    
    /* 顶部导航栏样式 - 与支付页面一致 */
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
    
    .about-hero {
      padding: 100px 20px;
      background: linear-gradient(135deg, #ffecec 0%, #ffffff 100%);
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    
    .about-hero::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23d6001c" fill-opacity="0.1" d="M0,128L48,117.3C96,107,192,85,288,101.3C384,117,480,171,576,181.3C672,192,768,160,864,128C960,96,1056,64,1152,74.7C1248,85,1344,139,1392,165.3L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
      background-size: cover;
      background-position: center bottom;
      opacity: 0.3;
    }
    
    .about-hero h1 {
      font-size: 42px;
      color: var(--primary);
      margin-bottom: 20px;
      position: relative;
    }
    
    .about-hero p {
      font-size: 18px;
      color: var(--text);
      max-width: 700px;
      margin: 0 auto;
      position: relative;
    }
    
    .about-section {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      align-items: center;
      gap: 40px;
      padding: 60px 20px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .about-section img {
      width: 100%;
      max-width: 500px;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .about-section .text {
      max-width: 600px;
      font-size: 17px;
      line-height: 1.8;
      color: var(--text);
    }
    
    .about-section .text h2 {
      color: var(--primary);
      margin-bottom: 20px;
      font-size: 32px;
    }
    
    .mission-section {
      background: #fff0f0;
      padding: 80px 20px;
      text-align: center;
    }
    
    .mission-section h2 {
      color: var(--primary);
      font-size: 36px;
      margin-bottom: 20px;
    }
    
    .mission-cards {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
      max-width: 1200px;
      margin: 40px auto 0;
    }
    
    .mission-card {
      background: white;
      border-radius: 16px;
      padding: 30px;
      width: 300px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.08);
      transition: transform 0.3s;
    }
    
    .mission-card:hover {
      transform: translateY(-10px);
    }
    
    .mission-card i {
      font-size: 40px;
      color: var(--primary);
      margin-bottom: 20px;
    }
    
    .mission-card h3 {
      color: var(--primary);
      margin-bottom: 15px;
    }
    
    .mission-card p {
      color: var(--text);
      line-height: 1.6;
    }
    
    .team-section {
      padding: 80px 20px;
      text-align: center;
      background: white;
    }
    
    .team-section h2 {
      color: var(--primary);
      font-size: 36px;
      margin-bottom: 20px;
    }
    
    .team-section p {
      max-width: 700px;
      margin: 0 auto 50px;
      font-size: 18px;
      color: var(--text);
    }
    
    .team-members {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .team-member {
      background: #f9f9f9;
      border-radius: 16px;
      overflow: hidden;
      width: 250px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.08);
      transition: transform 0.3s;
    }
    
    .team-member:hover {
      transform: translateY(-10px);
    }
    
    .team-member img {
      width: 100%;
      height: 250px;
      object-fit: cover;
    }
    
    .member-info {
      padding: 20px;
    }
    
    .member-info h3 {
      color: var(--primary);
      margin-bottom: 5px;
    }
    
    .member-info p {
      color: var(--text-light);
      margin-bottom: 15px;
    }
    
    
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
      
      .about-hero {
        padding: 80px 15px;
      }
      
      .about-hero h1 {
        font-size: 32px;
      }
      
      .about-section {
        padding: 40px 15px;
        flex-direction: column;
      }
      
      .mission-cards, .team-members {
        flex-direction: column;
        align-items: center;
      }
    }
  </style>
</head>
<body>

<!-- 顶部导航栏 -->
<div class="topbar">
    <div class="logo"><i class="fas fa-hamburger"></i> Fast<span>Food</span> Express</div>
    <div class="nav-links">
        <a href="index_user.php">Home</a>
        
        <!-- 订单下拉菜单 -->
        <div class="dropdown">
            <button class="dropbtn">Orders <span class="dropdown-icon">▼</span></button>
            <div class="dropdown-content">
                <a href="products_user.php">Products</a>
                <a href="order_trace.php">Order Trace</a>
                <a href="order_history.php">Order History</a>
            </div>
        </div>
        
        <a href="profile.php">Profile</a>
        <a href="about.php" class="active-link">About</a>
        <a href="contact.php">Contact</a>
        <a href="logout.php">Logout</a>
        <div class="cart-icon" data-count="<?php echo $cart_count; ?>" onclick="location.href='order_list.php'"><i class="fas fa-shopping-cart"></i></div>
    </div>
</div>

<!-- 关于我们主区域 -->
<div class="about-hero" data-aos="fade-up">
  <h1>About FastFood Express</h1>
  <p>Serving smiles since 2020! We are passionate about crafting delicious, fresh fast food with a heart. 🍟❤️</p>
</div>

<!-- 公司介绍 -->
<div class="about-section">
  <img src="https://images.unsplash.com/photo-1600891964599-f61ba0e24092?auto=format&fit=crop&w=800&q=80" alt="Kitchen" data-aos="zoom-in">
  <div class="text" data-aos="fade-left">
    <h2>Our Story</h2>
    <p>
      FastFood Express was founded by a group of food lovers who believe fast food can be fresh, affordable, and exciting.
      Every meal is made with love by our talented kitchen team, ensuring the perfect balance of taste, nutrition, and satisfaction.
    </p>
    <p>
      Our journey began in a small kitchen with just three employees. Today, we serve thousands of happy customers daily across multiple locations, 
      but we've never lost sight of our original mission: to deliver happiness through every bite.
    </p>
    <p>
      Whether it's a classic cheeseburger or crispy fried chicken, we guarantee flavor in every order! Our ingredients are locally sourced whenever possible,
      and we never use artificial preservatives.
    </p>
  </div>
</div>

<!-- 使命部分 -->
<div class="mission-section">
  <h2 data-aos="fade-up">Our Mission & Values</h2>
  <p data-aos="fade-up" data-aos-delay="100">We stand by these core principles in everything we do</p>
  
  <div class="mission-cards">
    <div class="mission-card" data-aos="fade-up" data-aos-delay="200">
      <i class="fas fa-heart"></i>
      <h3>Quality Food</h3>
      <p>We use only the freshest ingredients to create delicious meals that satisfy your cravings.</p>
    </div>
    
    <div class="mission-card" data-aos="fade-up" data-aos-delay="300">
      <i class="fas fa-bolt"></i>
      <h3>Fast Service</h3>
      <p>Your time is valuable. We strive to deliver your order within 15 minutes or less.</p>
    </div>
    
    <div class="mission-card" data-aos="fade-up" data-aos-delay="400">
      <i class="fas fa-smile"></i>
      <h3>Customer Happiness</h3>
      <p>Your satisfaction is our top priority. We go the extra mile to ensure you leave with a smile.</p>
    </div>
  </div>
</div>

<!-- 团队部分 -->
<div class="team-section">
  <h2 data-aos="fade-up">Meet Our Team</h2>
  <p data-aos="fade-up" data-aos-delay="100">The passionate people behind your favorite meals</p>
  
  <div class="team-members">
    <div class="team-member" data-aos="fade-up" data-aos-delay="200">
      <img src="" alt="Leader">
      <div class="member-info">
        <h3>Dennis Yew Shun Yao</h3>
        <p>Leader</p>
      </div>
    </div>
    
    <div class="team-member" data-aos="fade-up" data-aos-delay="300">
      <img src="" alt="Member">
      <div class="member-info">
        <h3>Chong Kai Yang</h3>
        <p>Member</p>
      </div>
    </div>
    
    <div class="team-member" data-aos="fade-up" data-aos-delay="400">
      <img src="img/Tan Chun Hoong.png" alt="Member">
      <div class="member-info">
        <h3>Tan Chun Hoong</h3>
        <p>Member</p>
      </div>
    </div>
  </div>
</div>

<!-- 页脚 -->
<footer class="footer">
  &copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.
</footer>

<!-- AOS 动画库 -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init({
    duration: 800,
    once: true
  });
  
  // 添加当前页面活动链接指示
  document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.topbar a, .dropdown-content a');
    
    navLinks.forEach(link => {
      if (link.getAttribute('href') === currentPage) {
        link.classList.add('active-link');
      }
    });
  });
</script>

</body>
</html>