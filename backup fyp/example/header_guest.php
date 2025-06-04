<?php
// header.php
?>

<link rel="stylesheet" href="style.css">

<!-- Top Bar -->
<div class="header">
  <div class="left">
    <!-- Hamburger icon: click to open side panel -->
    <span class="menu-icon" onclick="openPanel()">&#9776;</span>
    <!-- Brand name -->
    <div class="logo">Fast Food Ordering</div>
  </div>
  <div class="right">
    <!-- You can conditionally hide these if user is logged in, etc. -->
    <a href="login_user.php">Login / Register</a>
    <a href="set_location.php" class="order-now-btn">Order Now</a>
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
