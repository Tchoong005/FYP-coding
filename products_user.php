<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$sql = "SELECT * FROM products";
$result = mysqli_query($conn, $sql);
$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Products - FastFood Express</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff;
            margin: 0;
            padding: 20px;
        }
        .topbar {
            background: #222;
            color: white;
            display: flex;
            justify-content: space-between;
            padding: 15px 30px;
        }
        .topbar .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .topbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
        }
        .categories {
            text-align: center;
            margin: 20px 0;
        }
        .categories button {
            margin: 5px;
            padding: 10px 20px;
            border: none;
            background: #d6001c;
            color: white;
            border-radius: 20px;
            cursor: pointer;
        }
        .product-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            position: relative;
        }
        .product-card {
            width: 200px;
            background: #fff7f7;
            border-radius: 10px;
            text-align: center;
            padding: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.2s ease, opacity 0.3s ease;
        }
        .product-card:active {
            transform: scale(0.95);
        }
        .product-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
        }
        .product-card h3 {
            margin: 10px 0 5px;
            font-size: 18px;
            color: #d6001c;
        }
        .product-card.hide {
            opacity: 0;
            transform: scale(0.9);
            pointer-events: none;
            position: absolute;
        }
        .add-cart-btn {
            margin-top: 10px;
            padding: 10px 20px;
            background: #d6001c;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="topbar" data-aos="fade-down">
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

<div class="categories" data-aos="fade-up">
    <button onclick="filterProducts('all')">All</button>
    <button onclick="filterProducts('beverages')">Beverages</button>
    <button onclick="filterProducts('chicken')">Chicken</button>
    <button onclick="filterProducts('burger')">Burger</button>
    <button onclick="filterProducts('desserts_sides')">Desserts & Sides</button>
</div>

<div class="product-grid">
<?php foreach ($products as $product): ?>
    <div class="product-card" data-aos="zoom-in" data-category="<?php echo $product['category']; ?>">
        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" onerror="this.onerror=null; this.src='images/default.jpg';">
        <h3><?php echo $product['name']; ?></h3>
        <p>RM<?php echo number_format($product['price'], 2); ?></p>
        <button class="add-cart-btn" onclick="addToCart('<?php echo $product['name']; ?>')">Add to Cart</button>
    </div>
<?php endforeach; ?>
</div>

<script>
function filterProducts(category) {
    var cards = document.querySelectorAll('.product-card');
    cards.forEach(function(card) {
        if (category === 'all' || card.dataset.category === category) {
            card.classList.remove('hide');
        } else {
            card.classList.add('hide');
        }
    });
}

function addToCart(productName) {
    alert("✅ " + productName + " has been added to cart!");
}
</script>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init({ duration: 1000, once: true });
</script>

</body>
</html>
