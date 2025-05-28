<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 初始化购物车
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// 获取搜索关键词
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : "";

// 拉取所有产品（带搜索）
$sql = "SELECT * FROM products";
if (!empty($search)) {
    $sql .= " WHERE name LIKE '%$search%' OR description LIKE '%$search%' OR category LIKE '%$search%'";
}
$result = mysqli_query($conn, $sql);

$products_by_category = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products_by_category[$row['category']][] = $row;
    }
} else {
    die("Query failed: " . mysqli_error($conn));
}

// 计算购物车商品总数量
$cart_count = 0;
if (!empty($_SESSION['cart'])) {
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
            padding: 0 10px;
            line-height: 1.5;
        }

        .topbar a:hover {
            text-decoration: underline;
        }

        .cart-icon {
            position: relative;
            cursor: pointer;
            font-size: 20px;
            padding: 0 10px;
            line-height: 1.5;
            user-select: none;
        }

        /* 购物车角标样式调整，变成长条椭圆形 */
        .cart-icon::after {
            content: attr(data-count);
            position: absolute;
            top: -6px;
            right: -10px;
            background: red;
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


        .header-section {
            text-align: center;
            padding: 40px 10px;
            background: linear-gradient(to right, #ffecec, #ffffff);
        }

        .header-section h2 {
            color: #d6001c;
            font-size: 36px;
        }

        .search-bar {
            text-align: center;
            margin: 20px 0;
        }

        .search-bar input[type="text"] {
            padding: 10px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        .search-bar button {
            padding: 10px 16px;
            border: none;
            background-color: #d6001c;
            color: white;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
        }

        .category-title {
            font-size: 28px;
            color: #d6001c;
            margin: 40px 0 20px;
            padding-left: 20px;
            text-transform: capitalize;
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

        /* 库存不足按钮样式 */
        .disabled-btn {
            background-color: #888 !important;
            cursor: not-allowed !important;
            pointer-events: none;
            color: #ddd !important;
            text-decoration: none;
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

<!-- 🔝 Topbar -->
<div class="topbar">
    <div class="logo">🍔 FastFood Express</div>
    <div class="nav-links">
        <a href="index_user.php">Home</a>
        <a href="products_user.php">Products</a>
        <a href="profile.php">Profile</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="logout.php">Logout</a>
        <div class="cart-icon" data-count="<?php echo array_sum(array_column($_SESSION['cart'], 'quantity')); ?>" onclick="location.href='order_list.php'">🛒</div>
    </div>
</div>

<!-- 🧾 Header -->
<div class="header-section" data-aos="fade-down">
    <h2>🍟 Order Your Favorites Now</h2>
    <p style="color: #555;">Freshly made, delivered fast. Pick your meal below!</p>
</div>

<!-- 🔍 Search -->
<div class="search-bar">
    <form method="GET" action="products_user.php">
        <input type="text" name="search" placeholder="Search for products..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
    </form>
</div>

<!-- 🧾 Product List -->
<?php
if (!empty($products_by_category)) {
    foreach ($products_by_category as $category => $products) {
        echo '<div class="category-title" data-aos="fade-right">🍽 ' . htmlspecialchars($category) . '</div>';
        echo '<div class="product-grid">';
        foreach ($products as $product) {
            $button_label = ($product['stock_quantity'] > 0) ? "Order Now" : "Out of Stock";
            $button_class = ($product['stock_quantity'] > 0) ? "btn" : "btn disabled-btn";
            $button_link = ($product['stock_quantity'] > 0) ? "product_detail.php?id=" . $product['id'] : "#";

            echo '
                <div class="product-card" data-aos="fade-up">
                    <img src="' . htmlspecialchars($product['image_url']) . '" alt="' . htmlspecialchars($product['name']) . '">
                    <h3>' . htmlspecialchars($product['name']) . '</h3>
                    <p>' . (!empty($product['description']) ? htmlspecialchars($product['description']) : '') . '</p>
                    <div class="price">RM ' . number_format($product['price'], 2) . '</div>
                    <a href="' . $button_link . '" class="' . $button_class . '" ' . (($product['stock_quantity'] > 0) ? "" : "onclick='return false;'") . '>' . $button_label . '</a>
                </div>';
        }
        echo '</div>';
    }
} else {
    echo '<p style="text-align:center; color:red;">No products found.</p>';
}
?>

<!-- 🔚 Footer -->
<div class="footer">© 2025 FastFood Express. All rights reserved.</div>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
    AOS.init();
</script>
</body>
</html>
