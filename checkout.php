<?php
session_start();
include 'db.php';

// 启用错误报告（调试用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 添加CSRF保护
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 初始化会话数据
$_SESSION['delivery_method'] = $_SESSION['delivery_method'] ?? 'delivery';
$_SESSION['payment_method'] = $_SESSION['payment_method'] ?? 'credit_card';
$_SESSION['checkout_info'] = $_SESSION['checkout_info'] ?? [];

// 初始化变量
$user_id = (int)$_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];
$error = '';
$success = '';

// 如果购物车为空则重定向
if (empty($cart)) {
    header("Location: order_list.php");
    exit;
}

// 获取用户信息
$user_sql = "SELECT username, phone, address FROM customers WHERE id = $user_id LIMIT 1";
$user_result = mysqli_query($conn, $user_sql);
$user_data = mysqli_fetch_assoc($user_result);

// 计算总价和获取产品信息
$total_price = 0;
$product_info = [];
foreach ($cart as $item) {
    $pid = (int)$item['product_id'];
    if (!isset($product_info[$pid])) {
        $sql = "SELECT id, name, price, image_url FROM products WHERE id = $pid LIMIT 1";
        $res = mysqli_query($conn, $sql);
        if (!$res || mysqli_num_rows($res) === 0) {
            // 如果产品不存在，从购物车中移除
            unset($_SESSION['cart'][$pid]);
            header("Location: checkout.php");
            exit;
        }
        $product_info[$pid] = mysqli_fetch_assoc($res);
    }
    $total_price += $product_info[$pid]['price'] * $item['quantity'];
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证CSRF令牌
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token";
    } else {
        // 更新配送方式
        if (isset($_POST['delivery_method'])) {
            $_SESSION['delivery_method'] = mysqli_real_escape_string($conn, $_POST['delivery_method']);
        }
        
        // 更新收件人信息
        if (isset($_POST['recipient_name'])) {
            $_SESSION['checkout_info'] = [
                'recipient_name' => mysqli_real_escape_string($conn, $_POST['recipient_name']),
                'recipient_phone' => mysqli_real_escape_string($conn, $_POST['recipient_phone']),
                'recipient_address' => mysqli_real_escape_string($conn, $_POST['recipient_address'])
            ];
        }
        
        // 处理订单提交
        if (isset($_POST['submit_payment'])) {
            // 获取支付方式
            $payment_method = isset($_POST['payment_method']) 
                ? mysqli_real_escape_string($conn, $_POST['payment_method']) 
                : ($_SESSION['payment_method'] ?? 'credit_card');
            
            // 获取收件人信息
            $recipient_name = $_SESSION['checkout_info']['recipient_name'] ?? $user_data['username'];
            $recipient_phone = $_SESSION['checkout_info']['recipient_phone'] ?? $user_data['phone'];
            $recipient_address = $_SESSION['checkout_info']['recipient_address'] ?? $user_data['address'];
            $delivery_method = $_SESSION['delivery_method'] ?? 'delivery';

            // 开始事务
            mysqli_begin_transaction($conn);

            try {
                // 创建订单
                $now = date('Y-m-d H:i:s');
                // 修正：根据配送方式设置配送费
                $delivery_fee = ($delivery_method === 'delivery') ? 6.00 : 0.00;
                $final_total = $total_price + $delivery_fee;
                
                // 根据支付方式设置支付状态
                $payment_status = 'pending';
                if ($payment_method === 'cash') {
                    $payment_status = 'cash_on_delivery';
                } elseif ($payment_method === 'counter') {
                    $payment_status = 'pay_at_counter';
                }
                
                // 订单状态始终为pending
                $order_status = 'pending';
                
                // 修复SQL语句：字段数量和绑定参数一致
                $sql_order = "INSERT INTO orders (user_id, recipient_name, recipient_phone, recipient_address, 
                              delivery_method, total_price, delivery_fee, final_total, payment_method, 
                              payment_status, order_status, created_at)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql_order);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $conn->error);
                }
                
                // 修复绑定参数：确保12个参数对应12个字段
                $stmt->bind_param("issssdddssss", 
                    $user_id, 
                    $recipient_name, 
                    $recipient_phone,
                    $recipient_address, 
                    $delivery_method, 
                    $total_price, 
                    $delivery_fee, 
                    $final_total, 
                    $payment_method, 
                    $payment_status, 
                    $order_status, 
                    $now
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                
                $order_id = $conn->insert_id;
                $stmt->close();

                // 添加订单项
                foreach ($cart as $item) {
                    $pid = (int)$item['product_id'];
                    // 确保产品信息存在
                    if (!isset($product_info[$pid])) {
                        $sql = "SELECT id, name, price FROM products WHERE id = $pid LIMIT 1";
                        $res = mysqli_query($conn, $sql);
                        if (!$res || mysqli_num_rows($res) === 0) {
                            throw new Exception("Invalid product ID: $pid");
                        }
                        $product_info[$pid] = mysqli_fetch_assoc($res);
                    }
                    
                    $quantity = (int)$item['quantity'];
                    $sauce = isset($item['sauce']) ? mysqli_real_escape_string($conn, $item['sauce']) : '';
                    $comment = isset($item['comment']) ? mysqli_real_escape_string($conn, $item['comment']) : '';
                    $price = $product_info[$pid]['price'];

                    $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, sauce, comment, price)
                                 VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_item = $conn->prepare($sql_item);
                    if (!$stmt_item) {
                        throw new Exception("Item prepare failed: " . $conn->error);
                    }
                    
                    $stmt_item->bind_param("iiissd", $order_id, $pid, $quantity, $sauce, $comment, $price);
                    
                    if (!$stmt_item->execute()) {
                        throw new Exception("Item execute failed: " . $stmt_item->error);
                    }
                    $stmt_item->close();
                }

                // 提交事务
                mysqli_commit($conn);
                
                // 清除会话数据
                unset($_SESSION['cart']);
                unset($_SESSION['checkout_info']);
                unset($_SESSION['delivery_method']);
                unset($_SESSION['payment_method']);
                
                // 根据支付方式重定向
                if ($payment_method === 'credit_card') {
                    header("Location: payment.php?order_id=".$order_id);
                } else {
                    header("Location: index_user.php?order_success=".$order_id);
                }
                exit;

            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = "Order processing failed: " . $e->getMessage();
                error_log("Order Error: " . $e->getMessage() . "\nSQL Error: " . ($conn->error ?? ''));
            }
        }
    }
}

// 获取当前结账信息
$recipient_name = $_SESSION['checkout_info']['recipient_name'] ?? $user_data['username'];
$recipient_phone = $_SESSION['checkout_info']['recipient_phone'] ?? $user_data['phone'];
$recipient_address = $_SESSION['checkout_info']['recipient_address'] ?? $user_data['address'];
$delivery_method = $_SESSION['delivery_method'] ?? 'delivery';
$payment_method = $_SESSION['payment_method'] ?? 'credit_card';

// 计算购物车数量
$cart_count = array_sum(array_column($cart, 'quantity'));

// 计算总额 - 修正：根据配送方式设置配送费
$delivery_fee = ($delivery_method === 'delivery') ? 6.00 : 0.00;
$final_total = $total_price + $delivery_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - FastFood Express</title>
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

        /* 🔝 更新后的顶部导航栏 */
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

        /* 结账页面特有样式 */
        main {
            max-width: 1200px; 
            margin: 30px auto; 
            padding: 0 20px;
            display: flex;
            gap: 30px;
        }
        
        .left-column {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .right-column {
            width: 400px;
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        h2 { 
            color: #d6001c; 
            font-size: 24px; 
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        h3 {
            font-size: 18px;
            color: #555;
            margin-top: 0;
        }
        
        .order-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 4px;
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
            color: #d6001c;
            font-weight: bold;
        }
        
        .item-extras {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .delivery-method {
            display: flex;
            gap: 15px;
            margin: 15px 0;
        }
        
        .delivery-option {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
        }
        
        .delivery-option.selected {
            border-color: #d6001c;
            background: #fff0f0;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="tel"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
        }
        
        textarea {
            height: 80px;
            resize: vertical;
        }
        
        .payment-option {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        
        .payment-option.selected {
            border-color: #d6001c;
            background: #fff0f0;
        }
        
        .payment-option input {
            margin-right: 10px;
        }
        
        .payment-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .payment-icon {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-total {
            font-weight: bold;
            font-size: 18px;
            color: #d6001c;
        }
        
        .btn {
            background: #d6001c;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #b50018;
        }
        
        .btn-update {
            background: #666;
            margin-top: 10px;
        }
        
        .btn-update:hover {
            background: #555;
        }
        
        .error {
            color: #d6001c;
            background: #fff0f0;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .footer {
            background-color: #eee;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            margin-top: 40px;
        }
        
        /* Message overlay styles */
        .message-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .message-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .message-box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .message-box h3 {
            color: #d6001c;
            margin-bottom: 20px;
        }
        
        .message-box p {
            margin-bottom: 20px;
        }
        
        .spinner {
            border: 4px solid rgba(0,0,0,0.1);
            border-radius: 50%;
            border-top: 4px solid #d6001c;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .topbar {
                padding: 12px 15px;
            }
            
            .nav-links {
                gap: 10px;
            }
            
            main {
                flex-direction: column;
            }
            
            .right-column {
                width: 100%;
            }
            
            .delivery-method {
                flex-direction: column;
            }
        }
        
        @media (max-width: 480px) {
            .topbar .logo {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
<!-- 🔝 更新后的顶部导航栏 -->
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

<!-- Message Overlay -->
<div class="message-overlay" id="messageOverlay">
    <div class="message-box">
        <div class="spinner"></div>
        <h3 id="messageTitle">Processing Order</h3>
        <p id="messageText">Please wait while we process your order...</p>
    </div>
</div>

<main>
    <div class="left-column">
        <h2>Your Order</h2>
        
        <?php foreach ($cart as $item): ?>
            <?php 
            $pid = (int)$item['product_id'];
            if (isset($product_info[$pid])): 
                $product = $product_info[$pid]; 
            ?>
                <div class="order-item">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="item-image">
                    <div class="item-details">
                        <div class="item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="item-price">RM <?php echo number_format($product['price'], 2); ?></div>
                        <div class="item-extras">
                            Quantity: <?php echo $item['quantity']; ?>
                            <?php if (!empty($item['sauce'])): ?>
                                <br>Sauce: <?php echo htmlspecialchars($item['sauce']); ?>
                            <?php endif; ?>
                            <?php if (!empty($item['comment'])): ?>
                                <br>Note: <?php echo htmlspecialchars($item['comment']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="item-total">
                        RM <?php echo number_format($product['price'] * $item['quantity'], 2); ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="right-column">
        <h2>Payment Details</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form id="paymentForm" method="POST" action="checkout.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <h3>Order Method</h3>
            <div class="delivery-method">
                <label class="delivery-option <?php echo $delivery_method === 'delivery' ? 'selected' : ''; ?>">
                    <input type="radio" name="delivery_method" value="delivery" 
                        <?php echo $delivery_method === 'delivery' ? 'checked' : ''; ?> hidden>
                    Delivery
                </label>
                <label class="delivery-option <?php echo $delivery_method === 'dine_in' ? 'selected' : ''; ?>">
                    <input type="radio" name="delivery_method" value="dine_in" 
                        <?php echo $delivery_method === 'dine_in' ? 'checked' : ''; ?> hidden>
                    Dine-in
                </label>
            </div>

            <h3>Recipient Information</h3>
            <div class="form-group">
                <label for="recipient_name">Full Name</label>
                <input type="text" id="recipient_name" name="recipient_name" 
                    value="<?php echo htmlspecialchars($recipient_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="recipient_phone">Phone Number</label>
                <input type="tel" id="recipient_phone" name="recipient_phone" 
                    value="<?php echo htmlspecialchars($recipient_phone); ?>" required
                    pattern="[0-9]{10,15}" title="10-15 digit phone number">
            </div>
            <div class="form-group" id="address-field" 
                style="<?php echo $delivery_method === 'dine_in' ? 'display: none;' : ''; ?>">
                <label for="recipient_address">Delivery Address</label>
                <textarea id="recipient_address" name="recipient_address" 
                    <?php echo $delivery_method === 'dine_in' ? '' : 'required'; ?>><?php echo htmlspecialchars($recipient_address); ?></textarea>
            </div>

            <h3>Payment Method</h3>
            <label class="payment-option <?php echo $payment_method === 'credit_card' ? 'selected' : ''; ?>">
                <input type="radio" name="payment_method" value="credit_card" 
                    <?php echo $payment_method === 'credit_card' ? 'checked' : ''; ?> hidden>
                <span class="payment-icon">💳</span>
                <span>Credit/Debit Card</span>
            </label>
            <label class="payment-option <?php echo $payment_method === 'cash' ? 'selected' : ''; ?> 
                  <?php echo $delivery_method === 'delivery' ? '' : 'disabled'; ?>">
                <input type="radio" name="payment_method" value="cash" 
                    <?php echo $payment_method === 'cash' ? 'checked' : ''; ?>
                    <?php echo $delivery_method === 'delivery' ? '' : 'disabled'; ?> hidden>
                <span class="payment-icon">💵</span>
                <span>Cash on Delivery</span>
            </label>
            <label class="payment-option <?php echo $payment_method === 'counter' ? 'selected' : ''; ?> 
                  <?php echo $delivery_method === 'dine_in' ? '' : 'disabled'; ?>">
                <input type="radio" name="payment_method" value="counter" 
                    <?php echo $payment_method === 'counter' ? 'checked' : ''; ?>
                    <?php echo $delivery_method === 'dine_in' ? '' : 'disabled'; ?> hidden>
                <span class="payment-icon">🏪</span>
                <span>Pay at Counter</span>
            </label>

            <h3>Order Summary</h3>
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>RM <?php echo number_format($total_price, 2); ?></span>
            </div>
            <div class="summary-row" id="delivery-fee-row">
                <span>Delivery Fee:</span>
                <span><?php echo $delivery_method === 'delivery' ? 'RM 6.00' : 'Free'; ?></span>
            </div>
            <div class="summary-row summary-total">
                <span>Total:</span>
                <span>RM <?php echo number_format($final_total, 2); ?></span>
            </div>

            <button type="submit" name="submit_payment" class="btn" id="submitBtn">
                <?php echo ($payment_method === 'credit_card') ? 'Proceed to Payment' : 'Complete Order'; ?>
            </button>
        </form>
    </div>
</main>

<footer class="footer">
    &copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // 更新配送方式和UI
        $('input[name="delivery_method"]').change(function() {
            $('.delivery-option').removeClass('selected');
            $(this).closest('.delivery-option').addClass('selected');
            
            // 切换地址字段显示
            if ($(this).val() === 'delivery') {
                $('#address-field').show();
                $('#recipient_address').prop('required', true);
            } else {
                $('#address-field').hide();
                $('#recipient_address').prop('required', false);
            }
            
            // 更新支付选项
            $('.payment-option').removeClass('selected disabled');
            $('.payment-option input').prop('disabled', false);
            
            if ($(this).val() === 'delivery') {
                // 配送时禁用"pay at counter"
                $('.payment-option:has(input[value="counter"])').addClass('disabled')
                    .find('input').prop('disabled', true);
            } else {
                // 堂食时禁用"cash on delivery"
                $('.payment-option:has(input[value="cash"])').addClass('disabled')
                    .find('input').prop('disabled', true);
            }
            
            // 更新总计显示
            const subtotal = <?php echo $total_price; ?>;
            const deliveryFee = $(this).val() === 'delivery' ? 6.00 : 0.00;
            $('#delivery-fee-row span:last-child').text(
                deliveryFee > 0 ? 'RM ' + deliveryFee.toFixed(2) : 'Free'
            );
            $('.summary-total span:last-child').text(
                'RM ' + (subtotal + deliveryFee).toFixed(2)
            );
            
            // 更新按钮文本
            updateSubmitButtonText();
        });
        
        // 支付方式选择
        $('input[name="payment_method"]').change(function() {
            $('.payment-option').removeClass('selected');
            $(this).closest('.payment-option').addClass('selected');
            updateSubmitButtonText();
        });
        
        // 根据选择更新提交按钮文本
        function updateSubmitButtonText() {
            const paymentMethod = $('input[name="payment_method"]:checked').val();
            
            if (paymentMethod === 'credit_card') {
                $('#submitBtn').text('Proceed to Payment');
            } else {
                $('#submitBtn').text('Complete Order');
            }
        }
        
        // 处理表单提交
        $('#paymentForm').on('submit', function(e) {
            // 验证电话号码格式
            const phone = $('#recipient_phone').val();
            if (!/^\d{10,15}$/.test(phone)) {
                alert('Please enter a valid 10-15 digit phone number');
                e.preventDefault();
                return;
            }
            
            const paymentMethod = $('input[name="payment_method"]:checked').val();
            const overlay = $('#messageOverlay');
            
            // 显示适当的消息
            if (paymentMethod === 'credit_card') {
                $('#messageTitle').text('Redirecting to Payment');
                $('#messageText').text('You selected Credit/Debit Card payment. Redirecting to payment gateway...');
            } else {
                $('#messageTitle').text('Processing Order');
                $('#messageText').text('Your order is being processed...');
            }
            
            overlay.addClass('active');
            
            // 确保支付方式包含在表单数据中
            if (!$('input[name="payment_method"]').is(':checked')) {
                $(this).append('<input type="hidden" name="payment_method" value="' + 
                    ($('input[name="payment_method"]').first().val()) + '">');
            }
            
            // 提交表单
            setTimeout(() => {
                this.submit();
            }, 1500);
        });
    });
</script>
</body>
</html>