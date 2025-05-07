<?php
session_start();
include 'connection.php'; // 你需要创建这个文件连接数据库
include 'header_login.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Now</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .order-container {
      max-width: 1000px;
      margin: 50px auto;
      padding: 20px;
    }
    .product-grid {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
      justify-content: center;
    }
    .product-card {
      width: 250px;
      border: 1px solid #ddd;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
      background: #fff;
    }
    .product-card img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }
    .product-card h3 {
      margin: 10px 0 5px;
    }
    .product-card p {
      font-size: 0.9rem;
      color: #666;
      margin: 0 10px 10px;
    }
    .product-card .price {
      color: #e4002b;
      font-weight: bold;
      margin-bottom: 10px;
    }
    .product-card form {
      margin-bottom: 15px;
    }
    .product-card button {
      padding: 6px 12px;
      background-color: #e4002b;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .product-card button:hover {
      background-color: #c2001a;
    }
  </style>
</head>
<body>

<div class="order-container">
  <h2 style="text-align: center; margin-bottom: 30px;">Order Your Favorites</h2>
  <div class="product-grid">
    <?php
    $result = mysqli_query($conn, "SELECT * FROM products");
    while($row = mysqli_fetch_assoc($result)) {
      echo '
      <div class="product-card">
        <img src="'.$row['image'].'" alt="'.$row['name'].'">
        <h3>'.$row['name'].'</h3>
        <p>'.$row['description'].'</p>
        <div class="price">RM '.$row['price'].'</div>
        <form action="payment.php" method="POST">
          <input type="hidden" name="product_id" value="'.$row['id'].'">
          <button type="submit">Order Now</button>
        </form>
      </div>
      ';
    }
    ?>
  </div>
</div>

</body>
</html>
