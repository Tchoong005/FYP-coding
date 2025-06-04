<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Now - FastFood Express</title>
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
      flex-wrap: wrap;
    }

    .topbar .logo {
      font-size: 24px;
      font-weight: bold;
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
    }

    .cart-icon {
      position: relative;
      cursor: pointer;
      font-size: 20px;
    }

    .cart-icon::after {
      content: attr(data-count);
      position: absolute;
      top: -8px;
      right: -12px;
      background: red;
      color: white;
      border-radius: 50%;
      padding: 2px 6px;
      font-size: 12px;
      display: none;
    }

    .cart-icon[data-count]:not([data-count="0"])::after {
      display: block;
    }

    .header-section {
      text-align: center;
      padding: 40px 10px;
      background: linear-gradient(to right, #ffecec, #ffffff);
    }

    .header-section h2 {
      color: #d6001c;
      font-size: 36px;
    }

    .category-title {
      font-size: 28px;
      color: #d6001c;
      margin: 40px 0 20px;
      padding-left: 20px;
    }

    .product-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 30px;
      padding: 0 20px;
    }

    .product-card {
      width: 260px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      transition: transform 0.3s;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .product-card:hover {
      transform: scale(1.03);
    }

    .product-card img {
      width: 100%;
      height: 160px;
      object-fit: cover;
    }

    .product-card h3 {
      margin: 10px;
      color: #d6001c;
    }

    .product-card p {
      font-size: 14px;
      color: #333;
      padding: 0 10px;
      min-height: 40px;
    }

    .product-card .price {
      color: #e4002b;
      font-weight: bold;
      padding-left: 10px;
    }

    .product-card .btn {
      background-color: #d6001c;
      color: white;
      padding: 10px 16px;
      margin: 12px 10px 16px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      text-align: center;
      cursor: pointer;
      transition: 0.3s;
      text-decoration: none;
      display: inline-block;
      white-space: nowrap;
    }

    .product-card .btn:hover {
      background-color: #b80018;
    }

    .footer {
      background-color: #eee;
      text-align: center;
      padding: 20px;
      font-size: 14px;
      margin-top: 40px;
    }

    #toast {
      position: fixed;
      bottom: 30px;
      left: 30px;
      background: #1abc9c;
      color: white;
      padding: 14px 20px;
      border-radius: 8px;
      font-size: 14px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      display: none;
      z-index: 9999;
      opacity: 0;
      transition: opacity 0.3s;
    }
  </style>
</head>
<body>

<!-- 🔝 Topbar -->
<div class="topbar">
  <div class="logo">🍔 FastFood Express</div>
  <div class="nav-links">
    <a href="index_user.html">Home</a>
    <a href="order_now.php">Order Now</a>
    <a href="product_user.html">Products</a>
    <a href="user_about.html">About</a>
    <a href="contact_user.html">Contact</a>
    <a href="login.php">Login</a>
    <div class="cart-icon" data-count="<?php echo array_sum($_SESSION['cart']); ?>" onclick="location.href='order_list.php'">🛒</div>
  </div>
</div>

<!-- 🧾 Header -->
<div class="header-section" data-aos="fade-down">
  <h2>🍟 Order Your Favorites Now</h2>
  <p style="color: #555;">Freshly made, delivered fast. Pick your meal below!</p>
</div>

<?php
$category_query = mysqli_query($conn, "SELECT * FROM categories");
while ($category = mysqli_fetch_assoc($category_query)) {
    echo '<div class="category-title" data-aos="fade-right">🍽 ' . htmlspecialchars($category['name']) . '</div>';
    echo '<div class="product-grid">';

    $cat_id = $category['id'];
    $product_query = mysqli_query($conn, "SELECT * FROM products WHERE category_id = $cat_id");

    if (mysqli_num_rows($product_query) > 0) {
        while ($row = mysqli_fetch_assoc($product_query)) {
            echo '
            <div class="product-card" data-aos="fade-up">
              <img src="'.htmlspecialchars($row['image_url']).'" alt="'.htmlspecialchars($row['name']).'">
              <h3>'.htmlspecialchars($row['name']).'</h3>
              <p>'.htmlspecialchars($row['description']).'</p>
              <div class="price">RM '.number_format($row['price'], 2).'</div>
              <a href="product_detail.php?id=' . $row['id'] . '" class="btn">Order Now</a>
            </div>';
        }
    } else {
        echo '<p style="text-align:center; color:red;">No products in this category.</p>';
    }

    echo '</div>';
}
?>

<!-- 🔚 Footer -->
<div class="footer">© 2025 FastFood Express. All rights reserved.</div>

<!-- ✅ Toast Notification -->
<div id="toast"></div>

<!-- AOS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init();
</script>

</body>
</html>
