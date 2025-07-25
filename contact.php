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
      --primary-light: #ffecec;
      --secondary: #ff9800;
      --light-bg: #f8f9fa;
      --dark-bg: #222;
      --text: #333;
      --text-light: #666;
      --border: #e0e0e0;
      --success: #4caf50;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
      background-color: #f5f7fa;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      color: var(--text);
    }
    
    /* Topbar - consistent with products page */
    .topbar {
      background-color: var(--dark-bg);
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
    
    /* Hero Section */
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
    
    /* Contact Information Section */
    .contact-info-section {
      max-width: 1200px;
      margin: 0 auto;
      padding: 60px 20px;
    }
    
    .contact-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
      padding: 50px;
      width: 100%;
      position: relative;
      overflow: hidden;
    }
    
    .contact-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 8px;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
    }
    
    .contact-header {
      text-align: center;
      margin-bottom: 40px;
    }
    
    .contact-header h2 {
      font-size: 36px;
      color: var(--primary);
      margin-bottom: 15px;
    }
    
    .contact-header p {
      color: var(--text-light);
      font-size: 18px;
      max-width: 600px;
      margin: 0 auto;
    }
    
    .contact-container {
      display: flex;
      flex-wrap: wrap;
      gap: 40px;
    }
    
    .contact-left {
      flex: 1;
      min-width: 300px;
    }
    
    .contact-right {
      flex: 1;
      min-width: 300px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .contact-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 30px;
    }
    
    .contact-item {
      display: flex;
      gap: 20px;
      padding: 25px;
      border-radius: 15px;
      background: var(--primary-light);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .contact-item:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(214, 0, 28, 0.15);
    }
    
    .contact-icon {
      width: 70px;
      height: 70px;
      background: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .contact-icon i {
      font-size: 30px;
      color: var(--primary);
    }
    
    .contact-details h3 {
      font-size: 22px;
      margin-bottom: 12px;
      color: var(--primary);
    }
    
    .contact-details p {
      font-size: 18px;
      color: var(--text);
      line-height: 1.6;
    }
    
    .social-section {
      background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
      padding: 40px 30px;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.05);
      text-align: center;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    
    .social-section h3 {
      font-size: 28px;
      color: var(--text);
      margin-bottom: 25px;
      position: relative;
      padding-bottom: 15px;
    }
    
    .social-section h3::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 4px;
      background: var(--primary);
      border-radius: 2px;
    }
    
    .social-section p {
      color: var(--text-light);
      font-size: 18px;
      margin-bottom: 30px;
      max-width: 400px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .social-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      background: #3b5998;
      color: white;
      padding: 16px 35px;
      border-radius: 50px;
      text-decoration: none;
      font-size: 18px;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 5px 15px rgba(59, 89, 152, 0.3);
      max-width: 300px;
      margin: 0 auto;
    }
    
    .social-link:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(59, 89, 152, 0.4);
      background: #324d84;
    }
    
    .social-link i {
      font-size: 24px;
    }
    
    .social-icons {
      display: flex;
      justify-content: center;
      gap: 20px;
      margin-top: 30px;
    }
    
    .social-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: var(--primary-light);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: var(--primary);
      transition: all 0.3s;
    }
    
    .social-icon:hover {
      background: var(--primary);
      color: white;
      transform: translateY(-5px);
    }
    
    /* Footer */
    .footer {
      background-color: #eee;
      text-align: center;
      padding: 20px;
      font-size: 14px;
      margin-top: auto;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
      .topbar {
        padding: 12px 15px;
      }
      
      .contact-hero h1 {
        font-size: 32px;
      }
      
      .contact-hero p {
        font-size: 16px;
      }
      
      .contact-info-section {
        padding: 40px 20px;
      }
      
      .contact-card {
        padding: 30px;
      }
      
      .contact-header h2 {
        font-size: 28px;
      }
      
      .contact-item {
        padding: 20px;
      }
      
      .contact-icon {
        width: 60px;
        height: 60px;
      }
      
      .contact-icon i {
        font-size: 24px;
      }
      
      .contact-container {
        flex-direction: column;
      }
    }
    
    @media (max-width: 480px) {
      .topbar .logo {
        font-size: 20px;
      }
      
      .contact-hero {
        padding: 70px 20px;
      }
      
      .contact-hero h1 {
        font-size: 28px;
      }
      
      .contact-grid {
        grid-template-columns: 1fr;
      }
      
      .contact-item {
        flex-direction: column;
        text-align: center;
      }
      
      .contact-icon {
        margin: 0 auto;
      }
    }
  </style>
</head>
<body>
  <!-- Topbar - consistent with products page -->
  <div class="topbar">
    <div class="logo"><i class="fas fa-hamburger"></i> Fast<span>Food</span> Express</div>
    <div class="nav-links">
      <a href="index_user.php">Home</a>
      
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
      <a href="contact.php" class="active-link">Contact</a>
      <a href="logout.php">Logout</a>
      <div class="cart-icon" data-count="<?php echo $cart_count; ?>" onclick="location.href='order_list.php'"><i class="fas fa-shopping-cart"></i></div>
    </div>
  </div>

  <!-- Hero Section -->
  <div class="contact-hero" data-aos="fade-down">
    <h1>Contact Us</h1>
    <p>We'd love to hear from you! Reach out through any of the following channels. 🍟</p>
  </div>

  <!-- Contact Information Section -->
  <div class="contact-info-section">
    <div class="contact-card" data-aos="zoom-in">
      <div class="contact-header">
        <h2>Contact Information</h2>
        <p>Reach out to us through any of the following channels</p>
      </div>
      
      <div class="contact-container">
        <div class="contact-left">
          <div class="contact-grid">
            <div class="contact-item" data-aos="fade-right" data-aos-delay="100">
              <div class="contact-icon">
                <i class="fas fa-phone-alt"></i>
              </div>
              <div class="contact-details">
                <h3>Phone</h3>
                <p>016-774 8568<br>011-1052 5772</p>
              </div>
            </div>
            
            <div class="contact-item" data-aos="fade-right" data-aos-delay="200">
              <div class="contact-icon">
                <i class="fas fa-envelope"></i>
              </div>
              <div class="contact-details">
                <h3>Email</h3>
                <p>fastfoodexpress74@gmail.com</p>
              </div>
            </div>
            
            <div class="contact-item" data-aos="fade-right" data-aos-delay="300">
              <div class="contact-icon">
                <i class="fas fa-clock"></i>
              </div>
              <div class="contact-details">
                <h3>Opening Hours</h3>
                <p>Monday–Friday: 9:00 AM – 10:00 PM<br>Weekend: 10:00 AM – 11:00 PM</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="contact-right" data-aos="fade-left" data-aos-delay="400">
          <div class="social-section">
            <h3>Connect With Us</h3>
            <p>Follow us on social media for the latest updates and promotions</p>
            <a href="https://www.facebook.com/profile.php?id=61576758549907" target="_blank" class="social-link">
              <i class="fab fa-facebook-f"></i>
              <span>Visit our Facebook</span>
            </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    &copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.
  </footer>

  <!-- AOS -->
  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script>
    // Initialize AOS animations
    AOS.init({
      duration: 800,
      once: true
    });
    
    // Add active link indicator to current page in topbar
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