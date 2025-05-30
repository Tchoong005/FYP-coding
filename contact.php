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
  <title>Contact Us - FastFood Express</title>
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
    
    .contact-hero {
      padding: 100px 20px;
      background: linear-gradient(135deg, #ffecec 0%, #ffffff 100%);
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    
    .contact-hero::before {
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
    
    .contact-hero h1 {
      font-size: 42px;
      color: var(--primary);
      margin-bottom: 20px;
      position: relative;
    }
    
    .contact-hero p {
      font-size: 18px;
      color: var(--text);
      max-width: 700px;
      margin: 0 auto;
      position: relative;
    }
    
    .contact-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 40px;
      padding: 40px 20px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .info-box {
      flex: 1 1 300px;
      background: #fff6f6;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      transition: transform 0.3s;
    }
    
    .info-box:hover {
      transform: translateY(-5px);
    }
    
    .info-box h3 {
      color: var(--primary);
      margin-bottom: 15px;
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
    
    .form-box {
      flex: 1 1 300px;
      background: #fff;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.08);
      transition: transform 0.3s;
    }
    
    .form-box:hover {
      transform: translateY(-5px);
    }
    
    form input, form textarea {
      width: 100%;
      margin-bottom: 20px;
      padding: 14px;
      border: 1px solid var(--border);
      border-radius: 10px;
      font-size: 16px;
      transition: all 0.3s;
    }
    
    form input:focus, form textarea:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(214, 0, 28, 0.1);
    }
    
    form textarea {
      min-height: 150px;
      resize: vertical;
    }
    
    form button {
      background-color: var(--primary);
      color: white;
      border: none;
      padding: 14px 25px;
      border-radius: 10px;
      cursor: pointer;
      font-weight: bold;
      font-size: 17px;
      width: 100%;
      transition: background 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    
    form button:hover {
      background: var(--primary-dark);
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
      
      .contact-hero {
        padding: 80px 15px;
      }
      
      .contact-hero h1 {
        font-size: 32px;
      }
      
      .contact-grid {
        flex-direction: column;
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
        <a href="about.php">About</a>
        <a href="contact.php" class="active-link">Contact</a>
        <a href="logout.php">Logout</a>
        <div class="cart-icon" data-count="<?php echo $cart_count; ?>" onclick="location.href='order_list.php'"><i class="fas fa-shopping-cart"></i></div>
    </div>
</div>

<!-- Hero -->
<div class="contact-hero" data-aos="fade-up">
  <h1>Contact Us</h1>
  <p>We'd love to hear from you! Drop us a message or give us a visit. 🍟</p>
</div>

<!-- Contact Grid -->
<div class="contact-grid">
  <!-- Info -->
  <div class="info-box" data-aos="fade-right">
    <h3><i class="fas fa-map-marker-alt"></i> Address</h3>
    <p>123 FastFood Lane, Burger City, 43000</p>

    <h3><i class="fas fa-phone-alt"></i> Phone</h3>
    <p>+60 16-7748568</p>

    <h3><i class="fas fa-envelope"></i> Email</h3>
    <p>hello@fastfoodexpress.com</p>
    
    <h3><i class="fas fa-clock"></i> Opening Hours</h3>
    <p>Monday-Friday: 9:00 AM - 10:00 PM</p>
    <p>Weekend: 10:00 AM - 11:00 PM</p>
  </div>

  <!-- Form -->
  <div class="form-box" data-aos="fade-left">
    <form>
      <input type="text" placeholder="Your Name" required>
      <input type="email" placeholder="Your Email" required>
      <textarea rows="5" placeholder="Your Message..." required></textarea>
      <button type="submit"><i class="fas fa-paper-plane"></i> Send Message</button>
    </form>
  </div>
</div>

<!-- Footer -->
<footer class="footer">
  &copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.
</footer>


<!-- AOS Script -->
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
    
    // 表单提交处理
    const contactForm = document.querySelector('form');
    if (contactForm) {
      contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        alert('Thank you for your message! We will get back to you soon.');
        contactForm.reset();
      });
    }
  });
</script>

</body>
</html>