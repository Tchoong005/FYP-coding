<?php
// header_login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 模拟 cart 数量
$cart_count = array_sum($_SESSION['cart'] ?? []);
$user = $_SESSION['user_id'] ?? [];
?>
<link rel="stylesheet" href="header_login.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="user-header">
  <div class="left">
    <span class="menu-icon" onclick="openPanel()">&#9776;</span>
    <div class="logo">KFG Food</div>
  </div>
  <div class="right">
    <a href="profile.php" class="icon-link">
      <i class="fa fa-user-circle"></i>
      <span class="username"><?= htmlspecialchars($user['name'] ?? '') ?></span>
    </a>
    <a href="wallet.php" class="icon-link wallet-info">
      <i class="fa fa-wallet"></i>
      <span class="wallet-amount">MYR <?= number_format($user['wallet_balance'] ?? 0, 2) ?></span>
    </a>
    <a href="order_now.php" class="order-now-btn">
      <i class="fa fa-shopping-cart"></i>
      <span id="cart-count"><?= $cart_count ?></span>
    </a>
    <a href="logout.php" class="icon-link">
      <i class="fa fa-sign-out-alt"></i> Logout
    </a>
  </div>
</div>

<!-- Dimmed overlay behind the side panel -->
<div class="overlay" id="overlay"></div>

<!-- Side Panel -->
<div class="side-panel" id="sidePanel">
  <!-- Close button (X) at the top-right -->
  <span class="close-btn" onclick="closePanel()">&times;</span>

  <ul>
    <li onclick="toggleSubmenu('ordersSub')">
      <span class="label">Track My Orders</span>
      <span class="icon" id="ordersIcon">+</span>
    </li>
    <ul class="submenu" id="ordersSub">
      <li>Order History</li>
      <li>Current Orders</li>
    </ul>

    <li onclick="toggleSubmenu('servicesSub')">
      <span class="label">Services</span>
      <span class="icon" id="servicesIcon">+</span>
    </li>
    <ul class="submenu" id="servicesSub">
      <li>Delivery</li>
      <li>Self Collect</li>
    </ul>

    <li onclick="toggleSubmenu('aboutSub')">
      <span class="label">About Us</span>
      <span class="icon" id="aboutIcon">+</span>
    </li>
    <ul class="submenu" id="aboutSub">
      <li>Our Story</li>
      <li>Careers</li>
    </ul>

    <li onclick="toggleSubmenu('foodSub')">
      <span class="label">Our Food</span>
      <span class="icon" id="foodIcon">+</span>
    </li>
    <ul class="submenu" id="foodSub">
      <li>Menu</li>
      <li>Promotions</li>
    </ul>

    <li onclick="toggleSubmenu('helpSub')">
      <span class="label">Help & Support</span>
      <span class="icon" id="helpIcon">+</span>
    </li>
    <ul class="submenu" id="helpSub">
      <li>Contact Us</li>
      <li>FAQ</li>
    </ul>
  </ul>

  <!-- Example payment icons at bottom -->
  <div class="payment-section">
    <p>Secure Payment</p>
    <div class="payment-icons">
      <img src="https://upload.wikimedia.org/wikipedia/commons/0/04/Visa.png" alt="Visa">
      <img src="https://upload.wikimedia.org/wikipedia/commons/5/50/MasterCard_Logo.svg" alt="MasterCard">
      <img src="https://upload.wikimedia.org/wikipedia/commons/1/16/UnionPay_logo.svg" alt="UnionPay">
    </div>
  </div>
</div>

<script>
// Open side panel & overlay
function openPanel() {
  document.getElementById('sidePanel').classList.add('open');
  document.getElementById('overlay').classList.add('show');
}

// Close side panel & overlay
function closePanel() {
  document.getElementById('sidePanel').classList.remove('open');
  document.getElementById('overlay').classList.remove('show');
}

// Toggle submenus (+ / –)
function toggleSubmenu(submenuId) {
  const submenu = document.getElementById(submenuId);
  const icon = document.getElementById(submenuId.replace('Sub','Icon'));
  if (submenu.style.display === 'block') {
    submenu.style.display = 'none';
    icon.textContent = '+';
    icon.classList.remove('rotate');
  } else {
    submenu.style.display = 'block';
    icon.textContent = '–';
    icon.classList.add('rotate');
  }
}
</script>
