<?php
session_start();
include 'connection.php'; // 确保该文件已建立数据库连接
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Now - FastFood Express</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" />
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #fff;
    }

    .order-header {
      text-align: center;
      padding: 50px 20px 20px;
      background: linear-gradient(to right, #ffecec, #ffffff);
    }

    .order-header h2 {
      color: #d6001c;
      font-size: 36px;
    }

    .order-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 40px 20px;
    }

    .product-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      justify-content: center;
    }

    .product-card {
      width: 280px;
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      overflow: hidden;
      transition: transform 0.3s;
    }

    .product-card:hover {
      transform: scale(1.03);
    }

    .product-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }

    .product-card h3 {
      margin: 10px 0 5px;
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
      margin: 10px 0;
    }

    .product-card form {
      text-align: center;
      margin-bottom: 15px;
    }

    .product-card button {
      background-color: #d6001c;
      color: white;
      padding: 10px 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s ease;
    }

    .product-card button:hover {
      background-color: #b80018;
      transform: scale(1.05);
    }

    .footer {
      background-color: #eee;
      text-align: center;
      padding: 20px;
      font-size: 14px;
      margin-top: 40px;
    }
  </style>
</head>
<body>

<!-- 🧾 Order Header -->
<div class="order-header" data-aos="fade-down">
  <h2>🍟 Order Your Favorites Now</h2>
  <p style="color: #555;">Freshly made, delivered fast. Pick your meal below!</p>
</div>

<!-- 🧺 Products Grid -->
<div class="order-container" data-aos="fade-up">
  <div class="product-grid">
    <?php
    $result = mysqli_query($conn, "SELECT * FROM products");
    if (mysqli_num_rows($result) > 0) {
      while ($row = mysqli_fetch_assoc($result)) {
        echo '
        <div class="product-card" data-aos="fade-up">
          <img src="'.$row['image'].'" alt="'.$row['name'].'">
          <h3>'.$row['name'].'</h3>
          <p>'.$row['description'].'</p>
          <div class="price">RM '.$row['price'].'</div>
          <form action="payment.php" method="POST">
            <input type="hidden" name="product_id" value="'.$row['id'].'">
            <button type="submit">Order Now</button>
          </form>
        </div>';
      }
    } else {
      echo '<p style="text-align:center; color:red;">No products available at the moment.</p>';
    }
    ?>
  </div>
</div>

<!-- 🔚 Footer -->
<div class="footer">
  © 2025 FastFood Express. All rights reserved.
</div>

<!-- AOS Library -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init();
</script>

</body>
</html>
