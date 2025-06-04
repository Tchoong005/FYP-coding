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

// Calculate total items in cart
$cart_count = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['quantity'])) {
            $cart_count += (int)$item['quantity'];
        }
    }
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Fetch all orders
$sql_all = "SELECT * FROM orders 
           WHERE user_id = $user_id 
           ORDER BY created_at DESC";
$result_all = mysqli_query($conn, $sql_all);

$all_orders = [];
if ($result_all) {
    while ($row = mysqli_fetch_assoc($result_all)) {
        $all_orders[] = $row;
    }
} else {
    die("Query failed: " . mysqli_error($conn));
}

// Fetch successful orders (paid and completed/delivered)
$sql_success = "SELECT * FROM orders 
               WHERE user_id = $user_id 
               AND payment_status = 'paid'
               AND order_status IN ('completed', 'delivered')
               ORDER BY created_at DESC";
$result_success = mysqli_query($conn, $sql_success);

$success_orders = [];
if ($result_success) {
    while ($row = mysqli_fetch_assoc($result_success)) {
        $success_orders[] = $row;
    }
} else {
    die("Query failed: " . mysqli_error($conn));
}

// Fetch canceled orders
$sql_canceled = "SELECT * FROM orders 
                WHERE user_id = $user_id 
                AND payment_status = 'canceled'
                ORDER BY created_at DESC";
$result_canceled = mysqli_query($conn, $sql_canceled);

$canceled_orders = [];
if ($result_canceled) {
    while ($row = mysqli_fetch_assoc($result_canceled)) {
        $canceled_orders[] = $row;
    }
} else {
    die("Query failed: " . mysqli_error($conn));
}

// Function to fetch order items
function fetch_order_items($conn, $order_id) {
    $sql_items = "SELECT oi.*, p.name, p.image_url 
                 FROM order_items oi
                 JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = $order_id";
    $result_items = mysqli_query($conn, $sql_items);
    
    $order_items = [];
    if ($result_items) {
        while ($item = mysqli_fetch_assoc($result_items)) {
            $order_items[] = $item;
        }
    }
    return $order_items;
}

// Add items to all orders
foreach ($all_orders as &$order) {
    $order['items'] = fetch_order_items($conn, $order['id']);
}
unset($order); // break the reference

// Add items to successful orders
foreach ($success_orders as &$order) {
    $order['items'] = fetch_order_items($conn, $order['id']);
}
unset($order); // break the reference

// Add items to canceled orders
foreach ($canceled_orders as &$order) {
    $order['items'] = fetch_order_items($conn, $order['id']);
}
unset($order); // break the reference
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History - FastFood Express</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ... (保留所有原有样式) ... */
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
            --info: #2196f3;
            --processing: #2196f3;
            --ready: #9c27b0;
            --on-the-way: #673ab7;
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
        
        /* Topbar - Consistent with other pages */
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
        
        /* Order History Page Styles */
        .header-section {
            text-align: center;
            padding: 60px 20px 40px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }
        
        .no-orders {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin: 20px 0;
        }
        
        .no-orders i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .no-orders h3 {
            color: var(--text-light);
            margin-bottom: 15px;
        }
        
        .no-orders p {
            color: var(--text-light);
            max-width: 500px;
            margin: 0 auto 20px;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        /* Order Tabs */
        .order-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            background-color: white;
            border: none;
            padding: 12px 24px;
            margin: 0 5px;
            border-radius: 30px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }
        
        .tab-btn i {
            margin-right: 8px;
        }
        
        .tab-btn:hover {
            background-color: #f0f0f0;
        }
        
        .tab-btn.active {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 8px rgba(214, 0, 28, 0.2);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Order Cards */
        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin: 20px 0;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 5px solid var(--primary);
        }
        
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
        }
        
        .order-header-left h3 {
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .order-header-left p {
            color: var(--text-light);
            font-size: 14px;
        }
        
        .order-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            display: flex;
            align-items: center;
        }
        
        .status-preparing {
            background-color: #e3f2fd;
            color: var(--processing);
        }
        
        .status-pending {
            background-color: #fff3e0;
            color: var(--warning);
        }
        
        .status-on-the-way {
            background-color: #ede7f6;
            color: var(--on-the-way);
        }
        
        .status-completed {
            background-color: #e8f5e9;
            color: var(--success);
        }
        
        .status-delivered {
            background-color: #e8f5e9;
            color: var(--success);
        }
        
        .status-canceled {
            background-color: #ffebee;
            color: var(--danger);
        }
        
        .order-details {
            padding: 20px;
            display: flex;
            flex-wrap: wrap;
        }
        
        .order-summary {
            flex: 1;
            min-width: 300px;
            padding-right: 20px;
            border-right: 1px solid #eee;
        }
        
        .order-items {
            flex: 2;
            min-width: 300px;
            padding-left: 20px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #eee;
        }
        
        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .summary-label {
            color: var(--text-light);
        }
        
        .summary-value {
            font-weight: bold;
        }
        
        .total-row {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #eee;
            font-size: 18px;
        }
        
        .item-list {
            margin-top: 15px;
        }
        
        .item-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            background: #fafafa;
            margin-bottom: 10px;
            transition: transform 0.2s;
        }
        
        .item-card:hover {
            transform: translateX(5px);
        }
        
        .item-card:last-child {
            margin-bottom: 0;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: var(--text-light);
        }
        
        .item-quantity {
            font-weight: bold;
            color: var(--primary);
            min-width: 60px;
            text-align: right;
        }
        
        .order-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .payment-status {
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        
        .status-paid {
            color: var(--success);
        }
        
        .status-pending {
            color: var(--warning);
        }
        
        .status-canceled {
            color: var(--danger);
        }
        
        /* Status timeline */
        .status-timeline {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            background: #f5f7fa;
            border-top: 1px solid #eee;
        }
        
        .status-step {
            text-align: center;
            position: relative;
            flex: 1;
            padding: 0 10px;
        }
        
        .status-step::before {
            content: '';
            position: absolute;
            top: 25px;
            left: 0;
            right: 0;
            height: 3px;
            background: #ddd;
            z-index: 1;
        }
        
        .status-step:first-child::before {
            left: 50%;
        }
        
        .status-step:last-child::before {
            right: 50%;
        }
        
        .step-icon {
            position: relative;
            width: 50px;
            height: 50px;
            background: #fff;
            border-radius: 50%;
            margin: 0 auto 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ddd;
            z-index: 2;
        }
        
        .step-icon.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: #888;
        }
        
        .step-icon.active + .step-label {
            color: var(--primary);
            font-weight: bold;
        }
        
        /* Section headers */
        .section-header {
            display: flex;
            align-items: center;
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary);
        }
        
        .section-header h2 {
            color: var(--primary);
            font-size: 1.8rem;
            margin: 0;
        }
        
        .section-header i {
            margin-right: 12px;
            font-size: 1.5rem;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-header-left {
                margin-bottom: 15px;
            }
            
            .order-details {
                flex-direction: column;
            }
            
            .order-summary {
                padding-right: 0;
                border-right: none;
                border-bottom: 1px solid #eee;
                padding-bottom: 20px;
                margin-bottom: 20px;
            }
            
            .order-items {
                padding-left: 0;
            }
            
            .order-footer {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .status-timeline {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .status-step {
                text-align: left;
                padding: 10px 0;
                width: 100%;
                display: flex;
                align-items: center;
            }
            
            .status-step::before {
                display: none;
            }
            
            .step-icon {
                margin: 0 15px 0 0;
            }
            
            .tab-btn {
                margin: 5px;
                padding: 10px 15px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .topbar .logo {
                font-size: 20px;
            }
            
            .header-section h2 {
                font-size: 2.2rem;
            }
            
            .header-section p {
                font-size: 1rem;
            }
            
            .item-card {
                flex-wrap: wrap;
            }
            
            .item-image {
                margin-bottom: 10px;
            }
            
            .item-quantity {
                margin-top: 10px;
                text-align: left;
                width: 100%;
            }
            
            .tab-btn {
                width: 100%;
                margin: 5px 0;
                justify-content: center;
            }
        }
        
        .footer {
            background-color: #eee;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            margin-top: 40px;
        }
        
        /* Status indicators */
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-indicator.preparing {
            background-color: var(--processing);
        }
        
        .status-indicator.pending {
            background-color: var(--warning);
        }
        
        .status-indicator.on-the-way {
            background-color: var(--on-the-way);
        }
        
        .status-indicator.completed {
            background-color: var(--success);
        }
        
        .status-indicator.delivered {
            background-color: var(--success);
        }
        
        .status-indicator.canceled {
            background-color: var(--danger);
        }
        /* 新增进度条样式 - 与order_trace一致 */
        .progress-tracker {
            padding: 25px;
            background: #f0f7ff;
            border-top: 1px solid var(--border);
        }
        
        .tracker-title {
            font-weight: bold;
            margin-bottom: 20px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }
        
        .tracker-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            padding: 20px 0 30px;
        }
        
        .tracker-steps::before {
            content: '';
            position: absolute;
            top: 40px;
            left: 0;
            right: 0;
            height: 6px;
            background: #e0e0e0;
            z-index: 1;
            border-radius: 3px;
        }
        
        .progress-bar {
            position: absolute;
            top: 40px;
            left: 0;
            height: 6px;
            background: var(--success);
            z-index: 2;
            transition: width 0.5s ease;
            border-radius: 3px;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 3;
            flex: 1;
        }
        
        .step-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            font-size: 1.2rem;
            transition: all 0.3s;
        }
        
        .step.active .step-icon {
            background: var(--success);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
        }
        
        .step-text {
            text-align: center;
            font-size: 0.9rem;
            color: var(--text-light);
            max-width: 100px;
            line-height: 1.4;
        }
        
        .step.active .step-text {
            color: var(--text);
            font-weight: bold;
        }
        
        /* 移除的pending状态样式 */
        .status-pending {
            display: none;
        }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="logo"><i class="fas fa-hamburger"></i> Fast<span>Food</span> Express</div>
    <div class="nav-links">
        <a href="index_user.php">Home</a>
        
        <!-- Orders Dropdown -->
        <div class="dropdown">
            <button class="dropbtn">Orders <span class="dropdown-icon">▼</span></button>
            <div class="dropdown-content">
                <a href="products_user.php">Products</a>
                <a href="order_trace.php">Order Trace</a>
                <a href="order_history.php" class="active-link">Order History</a>
            </div>
        </div>
        
        <a href="profile.php">Profile</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="logout.php">Logout</a>
        <div class="cart-icon" data-count="<?php echo $cart_count; ?>" onclick="location.href='order_list.php'"><i class="fas fa-shopping-cart"></i></div>
    </div>
</div>

<!-- Header -->
<div class="header-section" data-aos="fade-down">
    <h2><i class="fas fa-history"></i> Your Order History</h2>
    <p>Review all your orders with FastFood Express</p>
</div>

<div class="container">
    <!-- 修改标签 - 移除Pending -->
    <div class="order-tabs">
        <button class="tab-btn active" onclick="showTab('all')">
            <i class="fas fa-list"></i> All Orders
        </button>
        <button class="tab-btn" onclick="showTab('success')">
            <i class="fas fa-check-circle"></i> Successful
        </button>
        <button class="tab-btn" onclick="showTab('canceled')">
            <i class="fas fa-times-circle"></i> Canceled
        </button>
    </div>
    
    <!-- All Orders Tab -->
    <div id="all" class="tab-content active">
        <?php if (empty($all_orders)): ?>
            <div class="no-orders" data-aos="fade-up">
                <i class="fas fa-box-open"></i>
                <h3>No Orders Found</h3>
                <p>You haven't placed any orders yet. Browse our menu and place your first order!</p>
                <a href="products_user.php" class="btn">Browse Menu</a>
            </div>
        <?php else: ?>
            <?php foreach ($all_orders as $order): 
                // 计算进度条状态
                $steps = [];
                $progress = 0;
                
                if ($order['delivery_method'] == 'delivery') {
                    $steps = [
                        ['icon' => '📝', 'label' => 'Order Placed', 'status' => 'pending'],
                        ['icon' => '👨‍🍳', 'label' => 'Preparing', 'status' => 'preparing'],
                        ['icon' => '🚚', 'label' => 'On the Way', 'status' => 'on_delivery'],
                        ['icon' => '📦', 'label' => 'Delivered', 'status' => 'delivered']
                    ];
                } else {
                    $steps = [
                        ['icon' => '📝', 'label' => 'Order Placed', 'status' => 'pending'],
                        ['icon' => '👨‍🍳', 'label' => 'Preparing', 'status' => 'preparing'],
                        ['icon' => '🛎️', 'label' => 'Ready', 'status' => 'ready'],
                        ['icon' => '✅', 'label' => 'Completed', 'status' => 'completed']
                    ];
                }
                
                $current_step_index = 0;
                foreach ($steps as $index => $step) {
                    if ($step['status'] == $order['order_status']) {
                        $current_step_index = $index;
                        $progress = ($index + 1) * (100 / count($steps));
                        break;
                    }
                }
                
                $status_class = '';
                $status_text = '';
                
                // Determine status based on order_status
                switch ($order['order_status']) {
                    case 'preparing':
                        $status_class = 'status-preparing';
                        $status_text = 'Preparing <i class="fas fa-utensils"></i>';
                        break;
                    case 'pending':
                        $status_class = 'status-pending';
                        $status_text = 'Pending <i class="fas fa-clock"></i>';
                        break;
                    case 'on_delivery':
                        $status_class = 'status-on-the-way';
                        $status_text = 'On the Way <i class="fas fa-truck"></i>';
                        break;
                    case 'completed':
                        $status_class = 'status-completed';
                        $status_text = 'Completed <i class="fas fa-check-circle"></i>';
                        break;
                    case 'delivered':
                        $status_class = 'status-delivered';
                        $status_text = 'Delivered <i class="fas fa-home"></i>';
                        break;
                    default:
                        if ($order['payment_status'] == 'canceled') {
                            $status_class = 'status-canceled';
                            $status_text = 'Canceled <i class="fas fa-ban"></i>';
                        } else {
                            $status_class = 'status-pending';
                            $status_text = 'Processing <i class="fas fa-cog"></i>';
                        }
                }
                
                $payment_class = 'status-' . str_replace(' ', '-', strtolower($order['payment_status']));
            ?>
            <div class="order-card" data-aos="fade-up">
                <div class="order-header">
                    <div class="order-header-left">
                        <h3>Order #<?php echo $order['id']; ?></h3>
                        <p><i class="far fa-calendar"></i> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div class="order-status <?php echo $status_class; ?>">
                        <span class="status-indicator <?php echo $order['order_status']; ?>"></span>
                        <?php echo $status_text; ?>
                    </div>
                </div>
                
                <!-- 使用新的进度条样式 -->
                <?php if ($order['payment_status'] != 'canceled' && $order['order_status'] != 'canceled'): ?>
                <div class="progress-tracker">
                    <div class="tracker-title">
                        <i class="fas fa-shipping-fast"></i> Order Progress
                    </div>
                    <div class="tracker-steps">
                        <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                        <?php foreach ($steps as $index => $step): ?>
                            <div class="step <?php echo $index <= $current_step_index ? 'active' : ''; ?>">
                                <div class="step-icon"><?php echo $step['icon']; ?></div>
                                <div class="step-text"><?php echo $step['label']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="progress-tracker" style="text-align: center; background: #ffebee; color: #f44336;">
                    <i class="fas fa-ban"></i> This order has been canceled.
                </div>
                <?php endif; ?>
                
                <div class="order-details">
                    <div class="order-summary">
                        <h4><i class="fas fa-receipt"></i> Order Summary</h4>
                        <div class="summary-item">
                            <span class="summary-label">Subtotal:</span>
                            <span class="summary-value">RM <?php echo number_format($order['total_price'], 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Delivery Fee:</span>
                            <span class="summary-value">RM <?php echo number_format($order['delivery_fee'], 2); ?></span>
                        </div>
                        <div class="summary-item total-row">
                            <span class="summary-label">Total Amount:</span>
                            <span class="summary-value">RM <?php echo number_format($order['final_total'], 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Payment Method:</span>
                            <span class="summary-value"><?php 
                                if ($order['payment_method'] == 'credit_card') echo 'Credit Card';
                                elseif ($order['payment_method'] == 'cash') echo 'Cash';
                                else echo 'Counter Payment';
                            ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Delivery Method:</span>
                            <span class="summary-value"><?php 
                                echo ($order['delivery_method'] == 'delivery') ? 'Delivery' : 'Dine In';
                            ?></span>
                        </div>
                    </div>
                    
                    <div class="order-items">
                        <h4><i class="fas fa-utensils"></i> Order Items</h4>
                        <div class="item-list">
                            <?php foreach ($order['items'] as $item): ?>
                            <div class="item-card">
                                <div class="item-image">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                </div>
                                <div class="item-quantity">
                                    Qty: <?php echo $item['quantity']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="order-footer">
                    <div class="payment-status <?php echo $payment_class; ?>">
                        <i class="fas fa-credit-card"></i> Payment Status: 
                        <span><?php echo ucfirst($order['payment_status']); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Successful Orders Tab -->
    <div id="success" class="tab-content">
        <?php if (empty($success_orders)): ?>
            <div class="no-orders" data-aos="fade-up">
                <i class="fas fa-check-circle"></i>
                <h3>No Successful Orders</h3>
                <p>You haven't completed any orders yet. Place an order to get started!</p>
                <a href="products_user.php" class="btn">Browse Menu</a>
            </div>
        <?php else: ?>
            <?php foreach ($success_orders as $order): 
                // 计算进度条状态
                $steps = [];
                $progress = 0;
                
                if ($order['delivery_method'] == 'delivery') {
                    $steps = [
                        ['icon' => '📝', 'label' => 'Order Placed', 'status' => 'pending'],
                        ['icon' => '👨‍🍳', 'label' => 'Preparing', 'status' => 'preparing'],
                        ['icon' => '🚚', 'label' => 'On the Way', 'status' => 'on_delivery'],
                        ['icon' => '📦', 'label' => 'Delivered', 'status' => 'delivered']
                    ];
                } else {
                    $steps = [
                        ['icon' => '📝', 'label' => 'Order Placed', 'status' => 'pending'],
                        ['icon' => '👨‍🍳', 'label' => 'Preparing', 'status' => 'preparing'],
                        ['icon' => '🛎️', 'label' => 'Ready', 'status' => 'ready'],
                        ['icon' => '✅', 'label' => 'Completed', 'status' => 'completed']
                    ];
                }
                
                $current_step_index = count($steps) - 1; // 成功订单显示全部完成
                $progress = 100;
                
                $status_class = ($order['order_status'] == 'completed') ? 'status-completed' : 'status-delivered';
                $status_text = ($order['order_status'] == 'completed') ? 'Completed <i class="fas fa-check-circle"></i>' : 'Delivered <i class="fas fa-home"></i>';
                $payment_class = 'status-paid';
            ?>
            <div class="order-card" data-aos="fade-up">
                <div class="order-header">
                    <div class="order-header-left">
                        <h3>Order #<?php echo $order['id']; ?></h3>
                        <p><i class="far fa-calendar"></i> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div class="order-status <?php echo $status_class; ?>">
                        <span class="status-indicator <?php echo $order['order_status']; ?>"></span>
                        <?php echo $status_text; ?>
                    </div>
                </div>
                
                <!-- 使用新的进度条样式 -->
                <div class="progress-tracker">
                    <div class="tracker-title">
                        <i class="fas fa-shipping-fast"></i> Order Progress
                    </div>
                    <div class="tracker-steps">
                        <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                        <?php foreach ($steps as $index => $step): ?>
                            <div class="step <?php echo $index <= $current_step_index ? 'active' : ''; ?>">
                                <div class="step-icon"><?php echo $step['icon']; ?></div>
                                <div class="step-text"><?php echo $step['label']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="order-details">
                    <div class="order-summary">
                        <h4><i class="fas fa-receipt"></i> Order Summary</h4>
                        <div class="summary-item">
                            <span class="summary-label">Subtotal:</span>
                            <span class="summary-value">RM <?php echo number_format($order['total_price'], 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Delivery Fee:</span>
                            <span class="summary-value">RM <?php echo number_format($order['delivery_fee'], 2); ?></span>
                        </div>
                        <div class="summary-item total-row">
                            <span class="summary-label">Total Amount:</span>
                            <span class="summary-value">RM <?php echo number_format($order['final_total'], 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Payment Method:</span>
                            <span class="summary-value"><?php 
                                if ($order['payment_method'] == 'credit_card') echo 'Credit Card';
                                elseif ($order['payment_method'] == 'cash') echo 'Cash';
                                else echo 'Counter Payment';
                            ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Delivery Method:</span>
                            <span class="summary-value"><?php 
                                echo ($order['delivery_method'] == 'delivery') ? 'Delivery' : 'Dine In';
                            ?></span>
                        </div>
                    </div>
                    
                    <div class="order-items">
                        <h4><i class="fas fa-utensils"></i> Order Items</h4>
                        <div class="item-list">
                            <?php foreach ($order['items'] as $item): ?>
                            <div class="item-card">
                                <div class="item-image">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                </div>
                                <div class="item-quantity">
                                    Qty: <?php echo $item['quantity']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="order-footer">
                    <div class="payment-status status-paid">
                        <i class="fas fa-credit-card"></i> Payment Status: 
                        <span>Paid</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Canceled Orders Tab -->
    <div id="canceled" class="tab-content">
        <?php if (empty($canceled_orders)): ?>
            <div class="no-orders" data-aos="fade-up">
                <i class="fas fa-ban"></i>
                <h3>No Canceled Orders</h3>
                <p>You haven't canceled any orders. That's great!</p>
            </div>
        <?php else: ?>
            <?php foreach ($canceled_orders as $order): 
                $status_class = 'status-canceled';
                $status_text = 'Canceled <i class="fas fa-ban"></i>';
                $payment_class = 'status-canceled';
            ?>
            <div class="order-card" data-aos="fade-up">
                <div class="order-header">
                    <div class="order-header-left">
                        <h3>Order #<?php echo $order['id']; ?></h3>
                        <p><i class="far fa-calendar"></i> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div class="order-status <?php echo $status_class; ?>">
                        <span class="status-indicator canceled"></span>
                        <?php echo $status_text; ?>
                    </div>
                </div>
                
                <!-- 取消订单显示特殊提示 -->
                <div class="progress-tracker" style="text-align: center; background: #ffebee; color: #f44336;">
                    <i class="fas fa-ban"></i> This order has been canceled.
                </div>
                
                <div class="order-details">
                    <div class="order-summary">
                        <h4><i class="fas fa-receipt"></i> Order Summary</h4>
                        <div class="summary-item">
                            <span class="summary-label">Subtotal:</span>
                            <span class="summary-value">RM <?php echo number_format($order['total_price'], 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Delivery Fee:</span>
                            <span class="summary-value">RM <?php echo number_format($order['delivery_fee'], 2); ?></span>
                        </div>
                        <div class="summary-item total-row">
                            <span class="summary-label">Total Amount:</span>
                            <span class="summary-value">RM <?php echo number_format($order['final_total'], 2); ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Payment Method:</span>
                            <span class="summary-value"><?php 
                                if ($order['payment_method'] == 'credit_card') echo 'Credit Card';
                                elseif ($order['payment_method'] == 'cash') echo 'Cash';
                                else echo 'Counter Payment';
                            ?></span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Delivery Method:</span>
                            <span class="summary-value"><?php 
                                echo ($order['delivery_method'] == 'delivery') ? 'Delivery' : 'Dine In';
                            ?></span>
                        </div>
                    </div>
                    
                    <div class="order-items">
                        <h4><i class="fas fa-utensils"></i> Order Items</h4>
                        <div class="item-list">
                            <?php foreach ($order['items'] as $item): ?>
                            <div class="item-card">
                                <div class="item-image">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                </div>
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-price">RM <?php echo number_format($item['price'], 2); ?></div>
                                </div>
                                <div class="item-quantity">
                                    Qty: <?php echo $item['quantity']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div class="order-footer">
                    <div class="payment-status status-canceled">
                        <i class="fas fa-credit-card"></i> Payment Status: 
                        <span>Canceled</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
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
    
    // Tab switching functionality
    function showTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Deactivate all tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabId).classList.add('active');
        
        // Activate selected button
        document.querySelector(`.tab-btn[onclick="showTab('${tabId}')"]`).classList.add('active');
    }
</script>
</body>
</html>