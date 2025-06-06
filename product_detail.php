<?php 
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$sql_user = "SELECT username FROM customers WHERE id = $user_id";
$res_user = mysqli_query($conn, $sql_user);
$username = "Guest";
if ($res_user && mysqli_num_rows($res_user) > 0) {
    $row_user = mysqli_fetch_assoc($res_user);
    $username = htmlspecialchars($row_user['username']);
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart_count = 0;
foreach ($_SESSION['cart'] as $item) {
    if (isset($item['quantity'])) {
        $cart_count += (int)$item['quantity'];
    }
}

if (!isset($_GET['id'])) {
    echo "Product ID missing.";
    exit();
}
$product_id = (int)$_GET['id'];

$sql = "SELECT * FROM products WHERE id = $product_id LIMIT 1";
$res = mysqli_query($conn, $sql);
if (!$res || mysqli_num_rows($res) == 0) {
    echo "Product not found.";
    exit();
}
$product = mysqli_fetch_assoc($res);

// 判断当前产品是否是饮料类
$is_beverage = (strtolower($product['category']) === 'beverages');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Product Detail - FastFood Express</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #d6001c;
            --primary-dark: #b80018;
            --secondary: #ff9800;
            --light-bg: #f8f9fa;
            --dark-bg: #222;
            --text: #333;
            --text-light: #666;
            --border: #e0e0e0;
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #fefefe;
            color: var(--text);
            line-height: 1.6;
        }
        
        /* 统一顶部导航栏样式 */
        .topbar {
            background-color: var(--dark-bg);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .topbar .logo {
            font-size: 24px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .topbar .logo span {
            color: var(--primary);
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
            transition: color 0.3s;
        }
        
        .topbar a:hover {
            color: var(--primary);
        }
        
        .cart-icon {
            position: relative;
            cursor: pointer;
            font-size: 20px;
            padding: 0 10px;
            line-height: 1.5;
            user-select: none;
        }
        
        .cart-icon::after {
            content: attr(data-count);
            position: absolute;
            top: -6px;
            right: -10px;
            background: var(--primary);
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
        
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropbtn {
            background-color: transparent;
            color: white;
            font-weight: bold;
            padding: 0 10px;
            line-height: 1.5;
            font-size: inherit;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: color 0.3s;
        }
        
        .dropbtn:hover {
            color: var(--primary);
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #333;
            min-width: 180px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
            overflow: hidden;
            top: 100%;
            left: 0;
        }
        
        .dropdown-content a {
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            border-bottom: 1px solid #444;
            transition: background-color 0.3s;
        }
        
        .dropdown-content a:hover {
            background-color: var(--primary);
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        .dropdown-icon {
            font-size: 14px;
            transition: transform 0.3s;
        }
        
        .dropdown:hover .dropdown-icon {
            transform: rotate(180deg);
        }
        
        .active-link {
            position: relative;
        }
        
        .active-link::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 10px;
            right: 10px;
            height: 3px;
            background: var(--primary);
            border-radius: 2px;
        }

        .header-section {
            text-align: center;
            padding: 60px 20px 40px;
            background: linear-gradient(135deg, #ffecec 0%, #ffffff 100%);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23d6001c" fill-opacity="0.1" d="M0,128L48,117.3C96,107,192,85,288,101.3C384,117,480,171,576,181.3C672,192,768,160,864,128C960,96,1056,64,1152,74.7C1248,85,1344,139,1392,165.3L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: center bottom;
            opacity: 0.3;
        }
        
        .header-section h2 {
            color: var(--primary);
            font-size: 2.8rem;
            margin: 0 0 15px;
            position: relative;
        }
        
        .header-section p {
            color: var(--text-light);
            font-size: 1.2rem;
            max-width: 700px;
            margin: 0 auto;
            position: relative;
        }

        .container {
            max-width: 1300px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
        }

        .product-image {
            grid-column: 1;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,0.1);
            height: 400px;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .product-info {
            grid-column: 2;
            display: flex;
            flex-direction: column;
            padding: 15px;
        }

        .product-info h2 {
            font-size: 32px;
            color: #222;
            margin: 0 0 10px;
        }

        .product-info p.description {
            color: var(--text-light);
            font-size: 16px;
            margin: 0 0 20px;
            line-height: 1.7;
        }

        .product-info .price {
            font-size: 28px;
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 15px;
        }

        .stock-info {
            color: #444;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .product-info label {
            font-weight: 600;
            margin-top: 12px;
            margin-bottom: 4px;
            display: block;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--light-bg);
            border: 1px solid var(--border);
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .qty-btn:hover {
            background: #e9ecef;
        }

        .qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        #quantity {
            width: 60px;
            height: 40px;
            text-align: center;
            font-size: 18px;
            border: 1px solid var(--border);
            border-radius: 10px;
        }

        .product-info select,
        .product-info textarea {
            width: 100%;
            padding: 10px 12px;
            font-size: 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 12px;
            background: white;
        }

        .product-info textarea {
            resize: vertical;
            height: 80px;
        }

        .btn-add-cart {
            background-color: var(--primary);
            color: white;
            padding: 14px 24px;
            font-weight: bold;
            font-size: 18px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 4px 8px rgba(214, 0, 28, 0.2);
        }

        .btn-add-cart:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(214, 0, 28, 0.3);
        }

        .btn-add-cart:disabled {
            background-color: #ccc;
            cursor: not-allowed;
            box-shadow: none;
        }

        /* 推荐商品区域 */
        .product-recommendations {
            grid-column: 3;
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            height: fit-content;
            border: 1px solid #f0f0f0;
        }

        .recommendations-title {
            margin-bottom: 20px;
            font-size: 22px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
        }

        .suggestions-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .suggestion-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            user-select: none;
        }
        
        .suggestion-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        
        .suggestion-image {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        
        .suggestion-content {
            padding: 12px;
        }
        
        .suggestion-content h4 {
            margin: 0 0 8px;
            font-size: 16px;
            color: var(--text);
        }
        
        .suggestion-content p {
            margin: 0;
            font-size: 14px;
            color: var(--text-light);
        }
        
        .suggestion-checkbox {
            padding: 10px 12px;
            border-top: 1px solid var(--border);
            background: var(--light-bg);
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: space-between;
        }
        
        .suggestion-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .suggestion-checkbox label {
            cursor: pointer;
            font-weight: 600;
            user-select: none;
            font-size: 14px;
        }

        .suggestion-qty-control {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
        }

        .suggestion-qty-btn {
            width: 28px;
            height: 28px;
            font-size: 16px;
            border-radius: 50%;
            background: #fff;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .suggestion-qty-btn:hover {
            background: #f0f0f0;
        }

        .suggestion-qty-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .suggestion-qty {
            width: 40px;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 4px;
            font-size: 14px;
            text-align: center;
        }

        .stock-indicator {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .in-stock {
            background-color: rgba(76, 175, 80, 0.15);
            color: var(--success);
        }
        
        .low-stock {
            background-color: rgba(255, 152, 0, 0.15);
            color: var(--warning);
        }
        
        .out-of-stock {
            background-color: rgba(244, 67, 54, 0.15);
            color: var(--danger);
        }

        /* 消息提示框 - 居中顶部 */
        .message-box {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.85);
            color: white;
            padding: 15px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            font-weight: 600;
            font-size: 16px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease;
            z-index: 9999;
            min-width: 280px;
            text-align: center;
        }
        
        .message-box.show {
            opacity: 1;
            pointer-events: auto;
        }
        
        .message-box.error {
            background-color: rgba(244, 67, 54, 0.9);
        }
        
        .message-box.success {
            background-color: rgba(76, 175, 80, 0.9);
        }

        /* 响应式布局 */
        @media (max-width: 992px) {
            .product-detail {
                grid-template-columns: 1fr 1fr;
            }
            .product-recommendations {
                grid-column: 1 / span 2;
                margin-top: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
            }
            .product-recommendations {
                grid-column: 1;
            }
            
            .topbar {
                padding: 12px 15px;
            }
            
            .nav-links {
                gap: 10px;
            }
            
            .product-image {
                height: 300px;
            }
        }
        
        @media (max-width: 480px) {
            .topbar .logo {
                font-size: 20px;
            }
            .header-section h2 {
                font-size: 2.2rem;
            }
            .product-image {
                height: 250px;
            }
        }

        .footer {
            background-color: #eee;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            margin-top: 40px;
        }
        
        .nutrition-info {
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid var(--border);
        }
        
        .nutrition-info h3 {
            margin-bottom: 12px;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nutrition-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        
        .nutrition-item {
            text-align: center;
            padding: 8px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .nutrition-value {
            font-weight: bold;
            font-size: 16px;
            color: var(--text);
        }
        
        .nutrition-label {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .product-actions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }
        
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            font-size: 18px;
            color: var(--text);
        }
    </style>
</head>
<body>

<!-- 🔝 Topbar -->
<div class="topbar">
    <div class="logo"><i class="fas fa-hamburger"></i> Fast<span>Food</span> Express</div>
    <div class="nav-links">
        <a href="index_user.php">Home</a>
        
        <!-- Orders Dropdown -->
        <div class="dropdown">
            <button class="dropbtn">Orders <span class="dropdown-icon">▼</span></button>
            <div class="dropdown-content">
                <a href="products_user.php" class="active-link">Products</a>
                <a href="order_trace.php">Order Trace</a>
                <a href="order_history.php">Order History</a>
            </div>
        </div>
        
        <a href="profile.php">Profile</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="logout.php">Logout</a>
        <div class="cart-icon" data-count="<?php echo $cart_count; ?>" onclick="location.href='order_list.php'"><i class="fas fa-shopping-cart"></i></div>
    </div>
</div>

<!-- 🧾 Header -->
<div class="header-section" data-aos="fade-down">
    <h2><i class="fas fa-utensils"></i> Product Details</h2>
    <p>Enjoy our premium quality products with fast delivery</p>
</div>

<div class="container" data-aos="fade-up">
    <div class="product-detail">
        <div class="product-image" data-aos="fade-right" data-aos-delay="100">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
        </div>
        
        <div class="product-info" data-aos="fade-up" data-aos-delay="150">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
            <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
            
            <?php 
            $stock_class = 'in-stock';
            $stock_text = 'In Stock';
            if ($product['stock_quantity'] <= 0) {
                $stock_class = 'out-of-stock';
                $stock_text = 'Out of Stock';
            } elseif ($product['stock_quantity'] < 10) {
                $stock_class = 'low-stock';
                $stock_text = 'Low Stock';
            }
            ?>
            <div class="stock-info">
                <span class="stock-indicator <?php echo $stock_class; ?>">
                    <i class="fas fa-<?php echo $product['stock_quantity'] > 0 ? 'check-circle' : 'times-circle'; ?>"></i> 
                    <?php echo $stock_text; ?>: 
                    <span><?php echo (int)$product['stock_quantity']; ?></span>
                </span>
            </div>
            
            <!-- 数量选择和备注区域 -->
            <div class="product-actions">
                <div class="section-title">
                    <i class="fas fa-cart-plus"></i> Customize Your Order
                </div>
                
                <label>Quantity:</label>
                <div class="quantity-control">
                    <button type="button" class="qty-btn" id="qtyMinus" disabled>-</button>
                    <input type="text" id="quantity" name="quantity" value="1" readonly />
                    <button type="button" class="qty-btn" id="qtyPlus">+</button>
                </div>

                <?php if (!$is_beverage): ?>
                    <label for="sauce">Choose Sauce:</label>
                    <select id="sauce" name="sauce" required>
                        <option value="">-- Select Sauce --</option>
                        <option value="BBQ">BBQ</option>
                        <option value="Cheese">Cheese</option>
                        <option value="Sweet and Sour">Sweet and Sour</option>
                        <option value="Spicy Mayo">Spicy Mayo</option>
                    </select>
                <?php endif; ?>

                <label for="comment">Special Instructions:</label>
                <textarea id="comment" name="comment" placeholder="Any special requests? (e.g. no onions, extra sauce)"></textarea>
            </div>
            
            <button class="btn-add-cart" id="addToCartBtn" <?php echo $product['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                <?php echo $product['stock_quantity'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                <i class="fas fa-shopping-cart ml-2"></i>
            </button>
        </div>
        
        <!-- 推荐商品区域 -->
        <div class="product-recommendations" data-aos="fade-left" data-aos-delay="200">
            <h3 class="recommendations-title"><i class="fas fa-star"></i> Recommended for You</h3>
            <form id="suggestionsForm">
                <div class="suggestions-grid">
                    <?php
                    // 添加 deleted_at IS NULL 条件过滤已删除商品
                    $suggest_sql = "SELECT * FROM products WHERE id != $product_id AND stock_quantity > 0 AND deleted_at IS NULL ORDER BY RAND() LIMIT 3";
                    $suggest_res = mysqli_query($conn, $suggest_sql);
                    if ($suggest_res && mysqli_num_rows($suggest_res) > 0) {
                        while ($suggest = mysqli_fetch_assoc($suggest_res)) {
                            $suggest_stock_class = 'in-stock';
                            $suggest_stock_text = 'In Stock';
                            if ($suggest['stock_quantity'] <= 0) {
                                $suggest_stock_class = 'out-of-stock';
                                $suggest_stock_text = 'Out of Stock';
                            } elseif ($suggest['stock_quantity'] < 10) {
                                $suggest_stock_class = 'low-stock';
                                $suggest_stock_text = 'Low Stock';
                            }
                            ?>
                            <label class="suggestion-card" for="suggest_<?php echo $suggest['id']; ?>">
                                <img src="<?php echo htmlspecialchars($suggest['image_url']); ?>" alt="<?php echo htmlspecialchars($suggest['name']); ?>" class="suggestion-image" />
                                <div class="suggestion-content">
                                    <h4><?php echo htmlspecialchars($suggest['name']); ?></h4>
                                    <p>RM <?php echo number_format($suggest['price'], 2); ?> 
                                        <span class="stock-indicator <?php echo $suggest_stock_class; ?>">
                                            <?php echo $suggest_stock_text; ?>: <?php echo (int)$suggest['stock_quantity']; ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="suggestion-checkbox">
                                    <input type="checkbox" id="suggest_<?php echo $suggest['id']; ?>" name="suggestions[]" value="<?php echo $suggest['id']; ?>" 
                                        <?php echo $suggest['stock_quantity'] <= 0 ? 'disabled' : ''; ?> />
                                    <label for="suggest_<?php echo $suggest['id']; ?>">
                                        <?php echo $suggest['stock_quantity'] > 0 ? 'Add to Order' : 'Out of Stock'; ?>
                                    </label>
                                    <div class="suggestion-qty-control" data-suggest-id="<?php echo $suggest['id']; ?>" data-stock="<?php echo (int)$suggest['stock_quantity']; ?>">
                                        <button type="button" class="suggestion-qty-btn minus" disabled>-</button>
                                        <input type="text" value="1" readonly class="suggestion-qty" 
                                            <?php echo $suggest['stock_quantity'] <= 0 ? 'disabled' : ''; ?> />
                                        <button type="button" class="suggestion-qty-btn plus" 
                                            <?php echo $suggest['stock_quantity'] <= 1 ? 'disabled' : ''; ?>>+</button>
                                    </div>
                                </div>
                            </label>
                            <?php
                        }
                    } else {
                        echo "<p>No recommendations available at the moment.</p>";
                    }
                    ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="message-box" id="messageBox"></div>

<div class="footer">© <?php echo date('Y'); ?> FastFood Express. All rights reserved.</div>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
    // 初始化AOS动画
    AOS.init({
        duration: 800,
        once: true
    });
    
    // 获取库存数量
    const stockQuantity = <?php echo (int)$product['stock_quantity']; ?>;
    
    // 主商品数量控制
    const qtyMinus = document.getElementById('qtyMinus');
    const qtyPlus = document.getElementById('qtyPlus');
    const quantityInput = document.getElementById('quantity');
    const addToCartBtn = document.getElementById('addToCartBtn');
    
    // 初始数量设置
    let currentQty = 1;
    
    // 更新数量按钮状态
    function updateQtyButtons() {
        qtyMinus.disabled = currentQty <= 1;
        qtyPlus.disabled = currentQty >= stockQuantity;
        
        // 如果库存为0，禁用所有按钮
        if (stockQuantity <= 0) {
            qtyMinus.disabled = true;
            qtyPlus.disabled = true;
            addToCartBtn.disabled = true;
        }
    }
    
    // 减号按钮事件
    qtyMinus.addEventListener('click', () => {
        if (currentQty > 1) {
            currentQty--;
            quantityInput.value = currentQty;
            updateQtyButtons();
        }
    });
    
    // 加号按钮事件
    qtyPlus.addEventListener('click', () => {
        if (currentQty < stockQuantity) {
            currentQty++;
            quantityInput.value = currentQty;
            updateQtyButtons();
        }
    });
    
    // 初始化按钮状态
    updateQtyButtons();
    
    // 推荐商品勾选时启用数量输入框
    document.querySelectorAll('input[name="suggestions[]"]').forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.disabled) return;
            
            const qtyControl = this.closest('.suggestion-checkbox').querySelector('.suggestion-qty-control');
            const minusBtn = qtyControl.querySelector('.minus');
            const plusBtn = qtyControl.querySelector('.plus');
            const qtyInput = qtyControl.querySelector('.suggestion-qty');
            const stock = parseInt(qtyControl.dataset.stock);
            
            if (this.checked) {
                minusBtn.disabled = true; // 初始数量为1，减号禁用
                plusBtn.disabled = stock <= 1; // 库存为1时加号禁用
            } else {
                minusBtn.disabled = true;
                plusBtn.disabled = true;
                qtyInput.value = 1;
            }
        });
    });
    
    // 推荐商品数量控制
    document.querySelectorAll('.suggestion-qty-control').forEach(control => {
        const minusBtn = control.querySelector('.minus');
        const plusBtn = control.querySelector('.plus');
        const qtyInput = control.querySelector('.suggestion-qty');
        const stock = parseInt(control.dataset.stock);
        const checkbox = control.closest('.suggestion-checkbox').querySelector('input[type="checkbox"]');
        
        minusBtn.addEventListener('click', () => {
            if (!checkbox.checked) return;
            
            let val = parseInt(qtyInput.value);
            if (val > 1) {
                qtyInput.value = val - 1;
                // 启用加号按钮（如果之前因库存禁用）
                if (val - 1 < stock) {
                    plusBtn.disabled = false;
                }
            }
            // 如果减到1，禁用减号按钮
            minusBtn.disabled = (val - 1) <= 1;
        });
        
        plusBtn.addEventListener('click', () => {
            if (!checkbox.checked) return;
            
            let val = parseInt(qtyInput.value);
            if (val < stock) {
                qtyInput.value = val + 1;
                // 如果达到库存上限，禁用加号按钮
                if (val + 1 >= stock) {
                    plusBtn.disabled = true;
                }
                // 启用减号按钮
                minusBtn.disabled = false;
            }
        });
    });

    // 显示消息函数
    function showMessage(msg, type = 'success') {
        const box = document.getElementById('messageBox');
        box.textContent = msg;
        box.className = `message-box ${type} show`;
        
        setTimeout(() => {
            box.className = 'message-box';
        }, 3500);
    }

    // 加入购物车事件
    addToCartBtn.addEventListener('click', function () {
        // 获取勾选的推荐商品及数量
        const checkedBoxes = document.querySelectorAll('input[name="suggestions[]"]:checked:not([disabled])');
        let recommendedItems = [];
        let isValid = true;
        
        checkedBoxes.forEach(cb => {
            const suggestId = cb.value;
            const qtyInput = cb.closest('.suggestion-checkbox').querySelector('.suggestion-qty');
            let qty = parseInt(qtyInput.value);
            const stock = parseInt(cb.closest('.suggestion-checkbox').querySelector('.suggestion-qty-control').dataset.stock);
            
            if (isNaN(qty) || qty < 1) {
                qty = 1;
            }
            
            if (qty > stock) {
                const productName = cb.closest('.suggestion-card').querySelector('h4').textContent;
                showMessage(`Not enough stock for ${productName}. Only ${stock} available.`, 'error');
                isValid = false;
            }
            
            recommendedItems.push({id: suggestId, quantity: qty});
        });

        if (!isValid) return;
        
        // 主商品参数
        const productId = <?php echo $product['id']; ?>;
        let mainQty = currentQty;
        
        <?php if (!$is_beverage): ?>
        const sauceSelect = document.getElementById('sauce');
        if (!sauceSelect || !sauceSelect.value) {
            showMessage('Please select a sauce.', 'error');
            return;
        }
        <?php endif; ?>

        const commentInput = document.getElementById('comment');

        const payload = {
            product_id: productId,
            quantity: mainQty,
            <?php if (!$is_beverage): ?>
            sauce: sauceSelect.value,
            <?php endif; ?>
            comment: commentInput.value,
            recommendations: recommendedItems
        };

        // 发送请求到服务器
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                showMessage('Items added to cart successfully!');
                const cartIcon = document.querySelector('.cart-icon');
                if (cartIcon) {
                    cartIcon.setAttribute('data-count', res.cart_count);
                }
                
                // 延迟跳转
                setTimeout(() => {
                    window.location.href = 'products_user.php';
                }, 1500);
            } else {
                showMessage(res.message || 'Failed to add to cart.', 'error');
            }
        })
        .catch(() => {
            showMessage('An error occurred. Please try again.', 'error');
        });
    });
</script>
</body>
</html>