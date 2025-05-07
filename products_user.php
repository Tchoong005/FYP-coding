Dennis Yew Shun Yao, [5/7/2025 8:58 PM]
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
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
} else {
    die("Query failed: " . mysqli_error($conn));
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
        }
        .product-card {
            width: 200px;
            background: #fff7f7;
            border-radius: 10px;
            text-align: center;
            padding: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .product-card:hover {
            transform: scale(1.05);
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
        #productModal {
            display: none;
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%) scale(0.9);
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            z-index: 1000;
            width: 300px;
            text-align: center;
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        #productModal.active {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }
        #productModal img {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
        }
        #productModal .close-btn {
            cursor: pointer;
            float: right;
            font-size: 18px;
        }
        .quantity-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 10px;
        }
        .quantity-controls button {
            padding: 5px 10px;
            font-size: 16px;
            margin: 0 5px;
            cursor: pointer;
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

Dennis Yew Shun Yao, [5/7/2025 8:58 PM]
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
    <div class="product-card" data-aos="zoom-in" data-category="<?php echo $product['category']; ?>"
        onclick="showDetails('<?php echo $product['name']; ?>', '<?php echo $product['price']; ?>', '<?php echo $product['image_url']; ?>')">
        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" onerror="this.onerror=null; this.src='images/default.jpg';">
        <h3><?php echo $product['name']; ?></h3>
    </div>
<?php endforeach; ?>
</div>

<!-- Modal -->
<div id="productModal" data-aos="fade-up">
    <span class="close-btn" onclick="closeModal()">❌</span>
    <img id="modalImage" src="" alt="">
    <h3 id="modalName"></h3>
    <p>RM<span id="modalPrice"></span></p>
    <div class="quantity-controls">
        <button onclick="decreaseQty()">-</button>
        <input type="text" id="modalQty" value="1" readonly style="width:30px; text-align:center;">
        <button onclick="increaseQty()">+</button>
    </div>
    <button class="add-cart-btn" onclick="addToCart()">Add to Cart</button>
</div>

<script>
let currentProduct = '';

function filterProducts(category) {
    var cards = document.querySelectorAll('.product-card');
    cards.forEach(function(card) {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function showDetails(name, price, image) {
    document.getElementById('modalName').innerText = name;
    document.getElementById('modalPrice').innerText = parseFloat(price).toFixed(2);
    document.getElementById('modalImage').src = image;
    document.getElementById('modalQty').value = 1;
    currentProduct = name;

    var modal = document.getElementById('productModal');
    modal.style.display = 'block';
    setTimeout(function() {
        modal.classList.add('active');
    }, 10);
}

function closeModal() {
    var modal = document.getElementById('productModal');
    modal.classList.remove('active');
    setTimeout(function() {
        modal.style.display = 'none';
    }, 300);
}

function increaseQty() {
    var input = document.getElementById('modalQty');
    input.value = parseInt(input.value) + 1;
}

function decreaseQty() {
    var input = document.getElementById('modalQty');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function addToCart() {
    var qty = document.getElementById('modalQty').value;
    alert("✅ " + currentProduct + " (x" + qty + ") has been added to cart!");
}
</script>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init({ duration: 1000, once: true });
</script>

</body>
</html>