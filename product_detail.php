<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!isset($_GET['id'])) {
    die('Product ID missing.');
}

$product_id = intval($_GET['id']);
$query = mysqli_query($conn, "SELECT * FROM products WHERE id = $product_id");

if (!$query || mysqli_num_rows($query) === 0) {
    die('Product not found.');
}

$product = mysqli_fetch_assoc($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($product['name']) ?> - Details</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f9f9f9;
    }

    .topbar {
      background-color: #222;
      color: white;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .topbar .logo {
      font-size: 24px;
      font-weight: bold;
    }

    .topbar a {
      color: white;
      margin-left: 20px;
      text-decoration: none;
      font-weight: bold;
    }

    .cart-icon {
      position: relative;
      cursor: pointer;
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

    .container {
      max-width: 900px;
      margin: 40px auto;
      background: white;
      border-radius: 12px;
      box-shadow: 0 6px 16px rgba(0,0,0,0.1);
      padding: 30px;
      display: flex;
      gap: 30px;
    }

    .product-image {
      width: 350px;
      border-radius: 10px;
      object-fit: cover;
    }

    .details {
      flex: 1;
    }

    h1 {
      margin-top: 0;
      color: #d6001c;
    }

    .price {
      font-size: 20px;
      font-weight: bold;
      margin-bottom: 20px;
    }

    .sauces label {
      display: block;
      margin: 10px 0;
    }

    textarea {
      width: 100%;
      padding: 10px;
      margin-top: 15px;
      border-radius: 8px;
      border: 1px solid #ccc;
      resize: vertical;
    }

    .btn {
      background: #d6001c;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      margin-top: 20px;
    }

    .btn:hover {
      background: #b80018;
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

<div class="topbar">
  <div class="logo">🍔 FastFood Express</div>
  <div>
    <a href="index_user.html">Home</a>
    <a href="order_now.php">Order Now</a>
    <a href="product_user.html">Products</a>
    <a href="order_list.php" class="cart-icon" data-count="<?= array_sum($_SESSION['cart']) ?>">🛒</a>
  </div>
</div>

<div class="container">
  <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">

  <div class="details">
    <h1><?= htmlspecialchars($product['name']) ?></h1>
    <div class="price">RM <?= number_format($product['price'], 2) ?></div>
    <p><?= htmlspecialchars($product['description']) ?></p>

    <form id="orderForm">
      <h4>Choose Sauces:</h4>
      <div class="sauces">
        <label><input type="checkbox" name="sauces[]" value="Mayo"> Mayo</label>
        <label><input type="checkbox" name="sauces[]" value="Ketchup"> Ketchup</label>
        <label><input type="checkbox" name="sauces[]" value="Chili"> Chili</label>
      </div>

      <h4>Message to restaurant:</h4>
      <textarea name="message" rows="4" placeholder="E.g. No onions, extra spicy..."></textarea>

      <input type="hidden" name="product_id" value="<?= $product_id ?>">
      <button class="btn" type="submit">Add to Order List</button>
    </form>
  </div>
</div>

<div id="toast"></div>

<script>
document.getElementById('orderForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const formData = new FormData(this);

  fetch('add_to_cart.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      showToast(data.message);
      document.querySelector('.cart-icon').setAttribute('data-count', data.cart_count);
    } else {
      showToast("Failed to add item.");
    }
  });
});

function showToast(message) {
  const toast = document.getElementById('toast');
  toast.innerText = message;
  toast.style.display = 'block';
  toast.style.opacity = 1;

  setTimeout(() => { toast.style.opacity = 0; }, 2500);
  setTimeout(() => { toast.style.display = 'none'; }, 3000);
}
</script>

</body>
</html>
