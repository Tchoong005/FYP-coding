<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get search keyword
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : "";

// 修正后的查询：使用 category_id 进行 JOIN
$sql = "SELECT p.*, c.display_name, c.name as category_name 
        FROM products p
        INNER JOIN categories c ON p.category_id = c.id
        WHERE c.deleted_at IS NULL 
        AND p.deleted_at IS NULL";

if (!empty($search)) {
    $sql .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%' OR c.name LIKE '%$search%' OR c.display_name LIKE '%$search%')";
}

$result = mysqli_query($conn, $sql);

$products_by_category = [];
$category_display_names = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // 使用分类名称作为键，存储显示名称
        $category_display_names[$row['category_name']] = $row['display_name'];
        // 按分类分组产品
        $products_by_category[$row['category_name']][] = $row;
    }
} else {
    die("Query failed: " . mysqli_error($conn));
}

// Calculate total items in cart
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--text);
            line-height: 1.6;
        }
        
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
        
        .search-bar {
            text-align: center;
            margin: 20px 0 30px;
        }
        
        .search-bar input[type="text"] {
            padding: 12px 16px;
            width: 320px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .search-bar button {
            padding: 12px 20px;
            border: none;
            background-color: var(--primary);
            color: white;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 16px;
            margin-left: 8px;
            transition: background-color 0.3s;
        }
        
        .search-bar button:hover {
            background-color: var(--primary-dark);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }
        
        /* 修改：移除分类标题下的横线 */
        .category-title {
            font-size: 28px;
            color: var(--primary);
            margin: 40px 0 20px;
            padding-left: 20px;
            text-transform: capitalize;
            display: flex;
            align-items: center;
            gap: 10px;
            /* 移除了 border-bottom */
        }
        
        /* 修改：创建横向滚动容器 */
        .scroll-container {
            position: relative;
            padding: 0 40px; /* 为导航按钮留出空间 */
            margin-bottom: 40px;
        }
        
        .product-grid {
            display: flex;
            gap: 30px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding: 20px 0;
            margin: 0 -15px;
            scrollbar-width: none; /* 隐藏Firefox的滚动条 */
        }
        
        .product-grid::-webkit-scrollbar {
            display: none; /* 隐藏Chrome/Safari的滚动条 */
        }
        
        .product-card {
            width: 260px;
            flex-shrink: 0;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .product-image {
            width: 100%;
            height: 160px;
            overflow: hidden;
        }
        
        .product-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .product-card:hover img {
            transform: scale(1.05);
        }
        
        .product-card h3 {
            margin: 10px;
            color: var(--primary);
            font-size: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .product-card p {
            font-size: 14px;
            color: var(--text-light);
            padding: 0 10px;
            min-height: 40px;
            line-height: 1.4;
        }
        
        .product-card .price {
            color: var(--primary);
            font-weight: bold;
            padding: 0 10px;
            font-size: 18px;
            margin-top: 5px;
        }
        
        .product-card .btn {
            background-color: var(--primary);
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
            background-color: var(--primary-dark);
        }
        
        .stock-indicator {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .in-stock {
            background-color: var(--success);
            color: white;
        }
        
        .out-of-stock {
            background-color: var(--danger);
            color: white;
        }
        
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
        
        .no-products {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-light);
            font-size: 18px;
            width: 100%;
        }
        
        /* 添加：横向滚动导航按钮 */
        .scroll-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 10;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        
        .scroll-btn:hover {
            opacity: 1;
            background: var(--primary);
            color: white;
        }
        
        .scroll-left {
            left: -5px;
        }
        
        .scroll-right {
            right: -5px;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .topbar {
                padding: 12px 15px;
            }
            
            .nav-links {
                gap: 10px;
            }
            
            .search-bar input[type="text"] {
                width: 70%;
            }
            
            .product-grid {
                gap: 15px;
            }
            
            .product-card {
                width: calc(50% - 15px);
                min-width: 220px;
            }
            
            .header-section h2 {
                font-size: 2.2rem;
            }
            
            .header-section p {
                font-size: 1rem;
            }
            
            .scroll-container {
                padding: 0 20px;
            }
            
            .scroll-btn {
                display: none; /* 在移动设备上隐藏导航按钮 */
            }
        }

        @media (max-width: 480px) {
            .topbar .logo {
                font-size: 20px;
            }
            
            .product-card {
                width: 100%;
                min-width: 200px;
            }
            
            .category-title {
                padding-left: 10px;
                font-size: 24px;
            }
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
    <h2><i class="fas fa-utensils"></i> Order Your Favorites Now</h2>
    <p>Freshly made, delivered fast. Pick your meal from our delicious selection below!</p>
</div>

<div class="container">
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
            // 确保分类下有产品才显示
            if (count($products) > 0) {
                // 使用display_name作为分类标题
                $display_name = isset($category_display_names[$category]) ? $category_display_names[$category] : $category;
                echo '<div class="category-title" data-aos="fade-right"><i class="fas fa-tag"></i> ' . htmlspecialchars($display_name) . '</div>';
                
                // 为每个分类添加横向滚动容器
                echo '<div class="scroll-container">';
                echo '<div class="product-grid" id="grid-' . htmlspecialchars($category) . '">';
                
                foreach ($products as $product) {
                    $button_label = ($product['stock_quantity'] > 0) ? "Order Now" : "Out of Stock";
                    $button_class = ($product['stock_quantity'] > 0) ? "btn" : "btn disabled-btn";
                    $button_link = ($product['stock_quantity'] > 0) ? "product_detail.php?id=" . $product['id'] : "#";
                    $stock_indicator = ($product['stock_quantity'] > 0) 
                        ? '<span class="stock-indicator in-stock">In Stock</span>' 
                        : '<span class="stock-indicator out-of-stock">Out of Stock</span>';

                    echo '
                        <div class="product-card" data-aos="fade-up">
                            <div class="product-image">
                                <img src="' . htmlspecialchars($product['image_url']) . '" alt="' . htmlspecialchars($product['name']) . '">
                            </div>
                            <h3>' . htmlspecialchars($product['name']) . $stock_indicator . '</h3>
                            <p>' . (!empty($product['description']) ? htmlspecialchars($product['description']) : '') . '</p>
                            <div class="price">RM ' . number_format($product['price'], 2) . '</div>
                            <a href="' . $button_link . '" class="' . $button_class . '" ' . (($product['stock_quantity'] > 0) ? "" : "onclick='return false;'") . '>' . $button_label . '</a>
                        </div>';
                }
                echo '</div>';
                
                // 添加导航按钮
                echo '<button class="scroll-btn scroll-left" onclick="scrollGrid(\'grid-' . htmlspecialchars($category) . '\', -300)"><i class="fas fa-chevron-left"></i></button>';
                echo '<button class="scroll-btn scroll-right" onclick="scrollGrid(\'grid-' . htmlspecialchars($category) . '\', 300)"><i class="fas fa-chevron-right"></i></button>';
                
                echo '</div>'; // 关闭滚动容器
            }
        }
    } else {
        echo '<p class="no-products">No products found. Please try a different search term.</p>';
    }
    ?>
</div>

<!-- 🔚 Footer -->
<footer class="footer">
    &copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
    // Initialize AOS animations
    AOS.init({
        duration: 800,
        once: true
    });
    
    // Add active link indicator to current page in topbar
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.topbar a, .dropdown-content a');
        
        navLinks.forEach(link => {
            if (link.getAttribute('href') === currentPage) {
                link.classList.add('active-link');
            }
        });
    });
    
    // 横向滚动函数
    function scrollGrid(gridId, scrollAmount) {
        const grid = document.getElementById(gridId);
        if (grid) {
            grid.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        }
    }
    
    // 添加触摸滑动支持
    document.querySelectorAll('.product-grid').forEach(grid => {
        let isDown = false;
        let startX;
        let scrollLeft;
        
        grid.addEventListener('mousedown', (e) => {
            isDown = true;
            startX = e.pageX - grid.offsetLeft;
            scrollLeft = grid.scrollLeft;
        });
        
        grid.addEventListener('mouseleave', () => {
            isDown = false;
        });
        
        grid.addEventListener('mouseup', () => {
            isDown = false;
        });
        
        grid.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - grid.offsetLeft;
            const walk = (x - startX) * 2; // 滚动速度
            grid.scrollLeft = scrollLeft - walk;
        });
        
        // 触摸事件支持
        grid.addEventListener('touchstart', (e) => {
            isDown = true;
            startX = e.touches[0].pageX - grid.offsetLeft;
            scrollLeft = grid.scrollLeft;
        });
        
        grid.addEventListener('touchend', () => {
            isDown = false;
        });
        
        grid.addEventListener('touchmove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.touches[0].pageX - grid.offsetLeft;
            const walk = (x - startX) * 2;
            grid.scrollLeft = scrollLeft - walk;
        });
    });
</script>
</body>
</html>