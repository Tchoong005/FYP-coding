<?php
session_start();
include 'db.php';

// 检查登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 获取产品
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$sql = ($category === 'all') ? "SELECT * FROM products" : "SELECT * FROM products WHERE category='$category'";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products - FastFood Express</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #fff; }
        .topbar { background: #222; color: white; padding: 15px 30px; display: flex; justify-content: space-between; }
        .topbar .logo { font-size: 24px; font-weight: bold; }
        .topbar a { color: white; text-decoration: none; margin-left: 20px; }

        .category-bar { text-align: center; margin: 20px; }
        .category-bar a { margin: 0 10px; padding: 8px 16px; background: #d6001c; color: white; text-decoration: none; border-radius: 20px; }
        .category-bar a:hover { background: #a30014; }

        .product-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 30px; padding: 20px; }
        .product-card { width: 250px; background: #fff7f7; border-radius: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center; padding: 15px; }
        .product-card img { width: 100%; height: 160px; object-fit: cover; border-radius: 10px; }
        .product-card h3 { color: #d6001c; margin: 10px 0; }
        .product-card .price { font-weight: bold; margin-bottom: 10px; }
        .quantity-control { display: flex; justify-content: center; align-items: center; gap: 10px; margin-bottom: 10px; }
        .quantity-control button { padding: 5px 10px; font-size: 18px; }
        .footer { background: #eee; text-align: center; padding: 20px; margin-top: 40px; font-size: 14px; }
    </style>
    <script>
        function changeQuantity(id, change) {
            const qtyInput = document.getElementById('qty-' + id);
            let qty = parseInt(qtyInput.value);
            qty = isNaN(qty) ? 1 : qty;
            qty += change;
            if (qty < 1) qty = 1;
            qtyInput.value = qty;
        }
    </script>
</head>
<body>

<div class="topbar">
    <div class="logo">🍔 FastFood Express</div>
    <div>
        <a href="index_user.php">Home</a>
        <a href="products_user.php">Products</a>
        <a href="profile.php">Profile</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="category-bar">
    <a href="products_user.php?category=all">All</a>
    <a href="products_user.php?category=beverages">Beverages</a>
    <a href="products_user.php?category=chicken">Chicken</a>
    <a href="products_user.php?category=burger">Burger</a>
    <a href="products_user.php?category=desserts and sides">Desserts & Sides</a>
</div>

<div class="product-grid">
<?php while ($row = mysqli_fetch_assoc($result)) { ?>
    <div class="product-card">
        <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
        <h3><?= htmlspecialchars($row['name']) ?></h3>
        <div class="price">RM<?= number_format($row['price'], 2) ?></div>
        <div class="quantity-control">
            <button onclick="changeQuantity(<?= $row['id'] ?>, -1)">-</button>
            <input id="qty-<?= $row['id'] ?>" type="text" value="1" style="width:30px; text-align:center;" readonly>
            <button onclick="changeQuantity(<?= $row['id'] ?>, 1)">+</button>
        </div>
    </div>
<?php } ?>
</div>

<div class="footer">
    © 2025 FastFood Express. All rights reserved.
</div>

</body>
</html>
