<?php
session_start();
include 'db.php';

// 强制销毁任何现有的会话，确保用户处于未登录状态
session_destroy();
$_SESSION = array();

// 设置访客状态
$is_logged_in = false;
$cart_count = 0; // 购物车数量强制为0

// 获取搜索关键词
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
        $category_display_names[$row['category_name']] = $row['display_name'];
        $products_by_category[$row['category_name']][] = $row;
    }
} else {
    die("Query failed: " . mysqli_error($conn));
}

// 计算购物车商品数量
$cart_count = 0;
if ($is_logged_in && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += (int)($item['quantity'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_logged_in ? 'Order Now' : 'Browse Menu'; ?> - FastFood Express</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary: #d6001c;
            --primary-dark: #b50018;
            --secondary: #f9fafb;
            --light-bg: #f8f9fa;
            --dark-bg: #222;
            --text: #1f2937;
            --text-light: #6b7280;
            --border: #e5e7eb;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text);
        }
        
        /* Top navigation bar */
        .topbar {
            background-color: #222;
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
        
        /* Guest notice */
        .guest-notice {
            background: #f8f9fa;
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
            display: <?php echo $is_logged_in ? 'none' : 'block'; ?>;
        }
        
        .guest-notice p {
            margin: 0;
            font-size: 16px;
            color: #444;
        }
        
        .guest-notice a {
            color: var(--primary);
            font-weight: bold;
            text-decoration: none;
            margin-left: 5px;
        }
        
        /* Header section */
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
        
        .category-title {
            font-size: 28px;
            color: var(--primary);
            margin: 40px 0 20px;
            padding-left: 20px;
            text-transform: capitalize;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* 横向滚动容器样式 */
        .scroll-container {
            position: relative;
            padding: 0 40px;
            margin-bottom: 50px;
        }
        
        .product-grid {
            display: flex;
            gap: 30px;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding: 20px 0;
            margin: 0 -15px;
            scrollbar-width: none;
        }
        
        .product-grid::-webkit-scrollbar {
            display: none;
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
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
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
            background-color: var(--error);
            color: white;
        }
        
        .disabled-btn {
            background-color: #888 !important;
            cursor: not-allowed !important;
            pointer-events: none;
            color: #ddd !important;
            text-decoration: none;
        }
        
        /* 横向滚动导航按钮 */
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
        
        .footer {
            background-color: #eee;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            margin-top: 40px;
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
                display: none;
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

<!-- Guest Notice -->
<div class="guest-notice">
    <p>You're browsing as a guest. <a href="#" onclick="showLoginPrompt('Personalized Experience')">Sign in</a> for a personalized experience!</p>
</div>

<!-- Top Navigation Bar -->
<div class="topbar">
    <div class="logo"><i class="fas fa-hamburger"></i> Fast<span>Food</span> Express</div>
    <div class="nav-links">
        <a href="index_user.html">Home</a>
        
        <!-- Orders Dropdown -->
        <div class="dropdown">
            <button class="dropbtn">Orders <span class="dropdown-icon">▼</span></button>
            <div class="dropdown-content">
                <a href="product_guest.php" class="active-link">Products</a>
                <?php if($is_logged_in): ?>
                    <a href="order_trace.php">Order Trace</a>
                    <a href="order_history.php">Order History</a>
                <?php else: ?>
                    <a href="#" class="require-login" data-page="Order Trace">Order Trace</a>
                    <a href="#" class="require-login" data-page="Order History">Order History</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if($is_logged_in): ?>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="about_guest.html">About</a>
            <a href="contact_guest.html">Contact</a>
            <a href="choose_login_register.html">Login</a>
        <?php endif; ?>
        
        <div class="cart-icon" 
             data-count="<?php echo $is_logged_in ? $cart_count : '0'; ?>" 
             onclick="<?php echo $is_logged_in ? "location.href='order_list.php'" : "showLoginPrompt('View Cart')"; ?>">
            <i class="fas fa-shopping-cart"></i>
        </div>
    </div>
</div>

<!-- Header Section -->
<div class="header-section" data-aos="fade-down">
    <h2><i class="fas fa-utensils"></i> 
        <?php echo $is_logged_in ? 'Order Your Favorites Now' : 'Browse Our Menu'; ?>
    </h2>
    <p>
        <?php echo $is_logged_in 
            ? 'Freshly made, delivered fast. Pick your meal from our delicious selection below!' 
            : 'Explore our delicious selection of fast food favorites. Sign in to place an order.'; ?>
    </p>
</div>

<div class="container">
    <!-- Search Bar -->
    <div class="search-bar">
        <form method="GET" action="product_guest.php">
            <input type="text" name="search" placeholder="Search for products..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </form>
    </div>
    
    <!-- Product List with Horizontal Scrolling -->
    <?php
    if (!empty($products_by_category)) {
        foreach ($products_by_category as $category => $products) {
            if (count($products) > 0) {
                $display_name = $category_display_names[$category] ?? $category;
                echo '<div class="category-title" data-aos="fade-right"><i class="fas fa-tag"></i> ' . htmlspecialchars($display_name) . '</div>';
                
                echo '<div class="scroll-container">';
                echo '<div class="product-grid" id="grid-' . htmlspecialchars($category) . '">';
                
                foreach ($products as $product) {
                    $button_label = ($product['stock_quantity'] > 0) ? "Order Now" : "Out of Stock";
                    $stock_indicator = ($product['stock_quantity'] > 0) 
                        ? '<span class="stock-indicator in-stock">In Stock</span>' 
                        : '<span class="stock-indicator out-of-stock">Out of Stock</span>';

                    echo '<div class="product-card" data-aos="fade-up">';
                    echo '<div class="product-image">';
                    echo '<img src="' . htmlspecialchars($product['image_url']) . '" alt="' . htmlspecialchars($product['name']) . '">';
                    echo '</div>';
                    echo '<h3>' . htmlspecialchars($product['name']) . $stock_indicator . '</h3>';
                    echo '<p>' . (!empty($product['description']) ? htmlspecialchars($product['description']) : '') . '</p>';
                    echo '<div class="price">RM ' . number_format($product['price'], 2) . '</div>';
                    
                    if ($is_logged_in) {
                        $button_class = ($product['stock_quantity'] > 0) ? "btn" : "btn disabled-btn";
                        $button_link = ($product['stock_quantity'] > 0) ? "product_detail.php?id=" . $product['id'] : "#";
                        echo '<a href="' . $button_link . '" class="' . $button_class . '" ' . (($product['stock_quantity'] > 0) ? "" : "onclick='return false;'") . '>' . $button_label . '</a>';
                    } else {
                        $button_class = ($product['stock_quantity'] > 0) ? "btn require-login" : "btn disabled-btn";
                        echo '<button class="' . $button_class . '" data-page="Add to Cart">' . $button_label . '</button>';
                    }
                    
                    echo '</div>';
                }
                echo '</div>';
                
                echo '<button class="scroll-btn scroll-left" onclick="scrollGrid(\'grid-' . htmlspecialchars($category) . '\', -300)"><i class="fas fa-chevron-left"></i></button>';
                echo '<button class="scroll-btn scroll-right" onclick="scrollGrid(\'grid-' . htmlspecialchars($category) . '\', 300)"><i class="fas fa-chevron-right"></i></button>';
                echo '</div>';
            }
        }
    } else {
        echo '<p class="no-products">No products found. Please try a different search term.</p>';
    }
    ?>
</div>

<!-- Footer -->
<footer class="footer">
    &copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Initialize animations
    AOS.init({
        duration: 800,
        once: true
    });

    // Handle login requirements
    document.querySelectorAll('.require-login').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const pageName = this.getAttribute('data-page') || "this feature";
            
            Swal.fire({
                icon: 'warning',
                title: 'Login Required',
                html: `You need to login to access the <b>${pageName}</b>.`,
                showCancelButton: true,
                confirmButtonText: 'Go to Login',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d6001c'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'choose_login_register.html';
                }
            });
        });
    });

    // Show login prompt for guests
    function showLoginPrompt(featureName) {
        Swal.fire({
            icon: 'info',
            title: 'Guest Shopping',
            html: `Please <b>login</b> to access the ${featureName}.`,
            showCancelButton: true,
            confirmButtonText: 'Login Now',
            cancelButtonText: 'Continue Browsing',
            confirmButtonColor: '#d6001c'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'choose_login_register.html';
            }
        });
    }

    // Add active link indicator
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
            const walk = (x - startX) * 2;
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