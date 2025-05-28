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
        
        /* 统一顶部导航栏样式 - 与order_trace.php相同 */
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
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .product-detail {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
        }

        .product-image {
            flex: 1 1 350px;
            max-width: 480px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,0.1);
        }
        .product-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .product-info {
            flex: 1 1 480px;
            display: flex;
            flex-direction: column;
        }

        .product-info h2 {
            font-size: 32px;
            color: #222;
            margin: 0;
        }

        .product-info p.description {
            color: var(--text-light);
            font-size: 16px;
            margin: 12px 0 20px;
        }

        .product-info .price {
            font-size: 28px;
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 10px;
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
        }

        .product-info input[type="number"],
        .product-info select,
        .product-info textarea {
            width: 100%;
            padding: 10px 12px;
            font-size: 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 12px;
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
            margin-bottom: 20px;
        }

        .btn-add-cart:hover {
            background-color: var(--primary-dark);
        }

        /* 新增的消息提示框 */
        .message-box {
            position: fixed;
            top: 20px;
            left: 20px;
            background-color: #d4edda;
            border: 2px solid var(--success);
            color: #155724;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            font-weight: 600;
            font-size: 16px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease;
            z-index: 9999;
            min-width: 280px;
        }
        .message-box.show {
            opacity: 1;
            pointer-events: auto;
        }
        .message-box.error {
            background-color: #f8d7da;
            border-color: var(--danger);
            color: #721c24;
        }

        /* 推荐商品多选样式 */
        .suggestions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
        }
        .suggestion-card {
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            user-select: none;
        }
        .suggestion-card:hover {
            transform: scale(1.03);
        }
        .suggestion-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        .suggestion-content {
            padding: 14px;
            flex-grow: 1;
        }
        .suggestion-content h4 {
            margin: 0 0 10px;
            font-size: 18px;
            color: var(--primary);
        }
        .suggestion-content p {
            margin: 0;
            font-size: 15px;
            color: var(--text-light);
        }
        .suggestion-checkbox {
            padding: 10px 14px;
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
        }

        @media (max-width: 768px) {
            .product-detail {
                flex-direction: column;
            }
            .btn-add-cart {
                width: 100%;
            }
            
            .topbar {
                padding: 12px 15px;
            }
            
            .nav-links {
                gap: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .topbar .logo {
                font-size: 20px;
            }
        }

        /* 新增推荐商品数量输入框样式 */
        .suggestion-qty {
            width: 60px;
            margin-left: auto;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 4px 8px;
            font-size: 14px;
            text-align: center;
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

<!-- 🔝 Topbar - 与order_trace.php相同 -->
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
    <h2><i class="fas fa-utensils"></i> Please select</h2>
    <p>Freshly made, delivered fast. Pick your meal below!</p>
</div>

<div class="container" data-aos="fade-up">
    <div class="product-detail">
        <div class="product-image" data-aos="fade-right" data-aos-delay="100">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" />
        </div>
        <div class="product-info" data-aos="fade-left" data-aos-delay="150">
            <h2><?php echo htmlspecialchars($product['name']); ?></h2>
            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
            <div class="price">RM <?php echo number_format($product['price'], 2); ?></div>
            <div class="stock-info">Stock: <?php echo (int)$product['stock_quantity']; ?></div>

            <label for="quantity">Quantity:</label>
            <input type="number" id="quantity" name="quantity" min="1" value="1" />

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

            <label for="comment">Add a Message:</label>
            <textarea id="comment" name="comment" placeholder="Any special instructions?"></textarea>

            <button class="btn-add-cart" id="addToCartBtn">Add to Cart</button>
        </div>
    </div>
</div>

<!-- 推荐商品区域 -->
<div class="container" data-aos="fade-up" style="margin-top: 60px;">
    <h3 style="margin-bottom: 20px; font-size: 26px; color: var(--text);">You Might Also Like 🍴</h3>

    <button id="toggleRecommendationsBtn" style="margin-bottom:12px; padding:8px 16px; font-size:16px; cursor:pointer;">
        Show Recommendations ▼
    </button>

    <form id="suggestionsForm">
        <div class="suggestions-grid" id="recommendationsSection" style="display:none; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px;">
            <?php
            $suggest_sql = "SELECT * FROM products WHERE id != $product_id AND stock_quantity > 0 ORDER BY RAND() LIMIT 4";
            $suggest_res = mysqli_query($conn, $suggest_sql);
            if ($suggest_res && mysqli_num_rows($suggest_res) > 0) {
                while ($suggest = mysqli_fetch_assoc($suggest_res)) {
                    ?>
                    <label class="suggestion-card" for="suggest_<?php echo $suggest['id']; ?>" style="border: 1px solid var(--border); border-radius: 8px; padding: 12px; display: flex; flex-direction: column; align-items: center; cursor: pointer;">
                        <img src="<?php echo htmlspecialchars($suggest['image_url']); ?>" alt="<?php echo htmlspecialchars($suggest['name']); ?>" class="suggestion-image" style="max-width: 100%; border-radius: 6px;" />
                        <div class="suggestion-content" style="margin-top: 8px; text-align: center;">
                            <h4 style="margin: 4px 0;"><?php echo htmlspecialchars($suggest['name']); ?></h4>
                            <p style="color: var(--text-light);">RM <?php echo number_format($suggest['price'], 2); ?></p>
                        </div>
                        <div class="suggestion-checkbox" style="margin-top: auto; display: flex; flex-direction: column; align-items: center;">
                            <input type="checkbox" id="suggest_<?php echo $suggest['id']; ?>" name="suggestions[]" value="<?php echo $suggest['id']; ?>" style="margin-bottom: 6px;" />
                            <div class="suggestion-qty-control" data-suggest-id="<?php echo $suggest['id']; ?>" style="display: inline-flex; align-items: center; gap: 6px;">
                                <button type="button" class="qty-minus" disabled style="width: 28px; height: 28px; font-size: 18px; cursor: not-allowed;">-</button>
                                <input type="text" value="1" readonly class="suggestion-qty" style="width: 36px; text-align: center; border-radius: 8px; border: 1px solid var(--border); padding: 4px 0;" />
                                <button type="button" class="qty-plus" disabled style="width: 28px; height: 28px; font-size: 18px; cursor: not-allowed;">+</button>
                            </div>
                        </div>
                    </label>
                    <?php
                }
            }
            ?>
        </div>
    </form>
</div>

<div class="message-box" id="messageBox"></div>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
    AOS.init();

    // 推荐商品勾选时启用数量输入框
    document.querySelectorAll('input[name="suggestions[]"]').forEach(cb => {
        cb.addEventListener('change', function() {
            const qtyControl = this.closest('.suggestion-checkbox').querySelector('.suggestion-qty-control');
            const minusBtn = qtyControl.querySelector('.qty-minus');
            const plusBtn = qtyControl.querySelector('.qty-plus');
            const qtyInput = qtyControl.querySelector('.suggestion-qty');

            if (this.checked) {
                minusBtn.disabled = false;
                plusBtn.disabled = false;
                minusBtn.style.cursor = 'pointer';
                plusBtn.style.cursor = 'pointer';
            } else {
                minusBtn.disabled = true;
                plusBtn.disabled = true;
                minusBtn.style.cursor = 'not-allowed';
                plusBtn.style.cursor = 'not-allowed';
                qtyInput.value = 1;
            }
        });
    });

    function showMessage(msg, isError = false) {
        const box = document.getElementById('messageBox');
        box.textContent = msg;
        if (isError) {
            box.className = 'message-box error show';
        } else {
            box.className = 'message-box show';
        }
        setTimeout(() => {
            box.className = 'message-box';
        }, 3500);
    }

    document.getElementById('addToCartBtn').addEventListener('click', function () {
        // 获取勾选的推荐商品及数量
        const checkedBoxes = document.querySelectorAll('input[name="suggestions[]"]:checked');
        let recommendedItems = [];
        let invalidQty = false;
        checkedBoxes.forEach(cb => {
            const suggestId = cb.value;
            const qtyInput = cb.closest('.suggestion-checkbox').querySelector('.suggestion-qty');
            let qty = parseInt(qtyInput.value);
            if (isNaN(qty) || qty < 1) {
                invalidQty = true;
                return;
            }
            recommendedItems.push({id: suggestId, quantity: qty});
        });
        if (invalidQty) {
            showMessage('Recommended items quantity must be at least 1.', true);
            return;
        }

        // 主商品参数
        const productId = <?php echo $product['id']; ?>;
        const mainQtyInput = document.getElementById('quantity');
        let mainQty = parseInt(mainQtyInput.value);
        if (isNaN(mainQty) || mainQty < 1) {
            showMessage('Quantity must be at least 1.', true);
            return;
        }

        <?php if (!$is_beverage): ?>
        const sauceSelect = document.getElementById('sauce');
        if (!sauceSelect || !sauceSelect.value) {
            showMessage('Please select a sauce.', true);
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

        fetch('add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(res => res.json())
          .then(res => {
            if (res.success) {
                showMessage('Added to cart successfully!');
                const cartIcon = document.querySelector('.cart-icon');
                if (cartIcon) {
                    cartIcon.setAttribute('data-count', res.cart_count);
                }

                // ✅ 添加延迟跳转
                setTimeout(() => {
                    window.location.href = 'products_user.php';
                }, 1500);
            } else {
                showMessage(res.message || 'Failed to add to cart.', true);
            }
          }).catch(() => {
            showMessage('Server error.', true);
          });
    });

    // 显示/隐藏推荐商品区域
    const toggleBtn = document.getElementById('toggleRecommendationsBtn');
    const recSection = document.getElementById('recommendationsSection');

    toggleBtn.addEventListener('click', () => {
        if (recSection.style.display === 'none') {
            recSection.style.display = 'grid';
            toggleBtn.textContent = 'Hide Recommendations ▲';
        } else {
            recSection.style.display = 'none';
            toggleBtn.textContent = 'Show Recommendations ▼';
        }
    });

    // 数量加减按钮功能
    document.querySelectorAll('.suggestion-qty-control').forEach(control => {
        const minusBtn = control.querySelector('.qty-minus');
        const plusBtn = control.querySelector('.qty-plus');
        const qtyInput = control.querySelector('.suggestion-qty');

        minusBtn.addEventListener('click', () => {
            let val = parseInt(qtyInput.value);
            if (val > 1) qtyInput.value = val - 1;
        });

        plusBtn.addEventListener('click', () => {
            let val = parseInt(qtyInput.value);
            qtyInput.value = val + 1;
        });
    });
</script>
<div class="footer">© <?php echo date('Y'); ?> FastFood Express. All rights reserved.</div>
</body>
</html>