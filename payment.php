<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

// 生成CSRF令牌
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 获取订单ID
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// 连接数据库获取订单信息
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

if ($order_id === 0) {
    die("Invalid order ID");
}

// 获取订单信息
$order_sql = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id";
$order_result = mysqli_query($conn, $order_sql);
if (!$order_result || mysqli_num_rows($order_result) === 0) {
    die("Order not found");
}
$order = mysqli_fetch_assoc($order_result);

// 检查订单状态
if ($order['payment_status'] === 'canceled') {
    $payment_canceled = true;
} else {
    $payment_canceled = false;
}

// 获取订单项
$items_sql = "SELECT oi.*, p.name, p.image_url, p.stock_quantity 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE order_id = $order_id";
$items_result = mysqli_query($conn, $items_sql);
$order_items = [];
while ($row = mysqli_fetch_assoc($items_result)) {
    $order_items[] = $row;
}

// 计算金额（分）
$amount_in_cents = (int)($order['final_total'] * 100);

// 创建 PaymentIntent（使用 $_SESSION 保存）
$paymentIntent = null;

if (!$payment_canceled) {
    // 如果 session 里已有该订单对应的 token，尝试复用
    if (isset($_SESSION['stripe_payment'][$order_id])) {
        $client_secret = $_SESSION['stripe_payment'][$order_id]['client_secret'];
        $intent_id = $_SESSION['stripe_payment'][$order_id]['payment_intent_id'];

        try {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($intent_id);
        } catch (Exception $e) {
            // 如果 retrieve 失败，就重新生成
            try {
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => $amount_in_cents,
                    'currency' => 'myr',
                    'metadata' => [
                        'order_id' => $order_id,
                        'user_id' => $user_id
                    ]
                ]);
                $_SESSION['stripe_payment'][$order_id] = [
                    'payment_intent_id' => $paymentIntent->id,
                    'client_secret' => $paymentIntent->client_secret
                ];
            } catch (Exception $e) {
                die("Error creating payment intent: " . $e->getMessage());
            }
        }

    } else {
        // 如果 session 里没有，就第一次创建
        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $amount_in_cents,
                'currency' => 'myr',
                'metadata' => [
                    'order_id' => $order_id,
                    'user_id' => $user_id
                ]
            ]);
            $_SESSION['stripe_payment'][$order_id] = [
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret
            ];
        } catch (Exception $e) {
            die("Error creating payment intent: " . $e->getMessage());
        }
    }
}


// 计算购物车数量
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        if (isset($item['quantity'])) {
            $cart_count += (int)$item['quantity'];
        }
    }
}

// 确定订单类型和显示文本
$order_type = ($order['delivery_method'] === 'dine_in') ? 'dine_in' : 'delivery';
$order_type_title = ($order_type === 'dine_in') ? 'Dine In Information' : 'Delivery Information';
$order_type_icon = ($order_type === 'dine_in') ? 'fa-utensils' : 'fa-truck';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Secure Payment | FastFood Express</title>
  <script src="https://js.stripe.com/v3/"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary: #d6001c;
      --primary-dark: #b50018;
      --secondary: #f9fafb;
      --text: #1f2937;
      --text-light: #6b7280;
      --border: #e5e7eb;
      --success: #10b981;
      --error: #ef4444;
      --warning: #f59e0b;
      --info: #3b82f6;
      --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      --card-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
    
    /* 顶部导航栏样式 */
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
    
    .main-content {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 100px 20px 20px;
      width: 100%;
    }
    
    .container {
      display: flex;
      max-width: 1200px;
      width: 100%;
      gap: 30px;
    }
    
    .payment-details {
      flex: 1;
      background: white;
      color: var(--text);
      padding: 25px;
      border-radius: 8px;
      box-shadow: var(--card-shadow);
    }
    
    .order-summary {
      flex: 1;
      padding: 25px;
      background: white;
      border-radius: 8px;
      box-shadow: var(--card-shadow);
    }
    
    .logo-section {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 20px;
    }
    
    .logo-section i {
      font-size: 28px;
      background: var(--primary);
      color: white;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .logo-section h1 {
      font-size: 24px;
      font-weight: 700;
      color: var(--primary);
    }
    
    .order-info {
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
    }
    
    .order-info h3 {
      margin-bottom: 15px;
      font-weight: 600;
      font-size: 18px;
      color: var(--primary);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .order-item {
      display: flex;
      gap: 15px;
      padding: 15px 0;
      border-bottom: 1px solid var(--border);
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
      color: var(--primary);
      font-weight: bold;
    }
    
    .item-extras {
      font-size: 13px;
      color: #666;
      margin-top: 5px;
    }
    
    .item-stock {
      font-size: 13px;
      margin-top: 5px;
      padding: 2px 8px;
      border-radius: 4px;
      display: inline-block;
    }
    
    .stock-ok {
      background-color: rgba(16, 185, 129, 0.15);
      color: var(--success);
    }
    
    .stock-low {
      background-color: rgba(245, 158, 11, 0.15);
      color: var(--warning);
    }
    
    .total {
      display: flex;
      justify-content: space-between;
      padding: 15px 0;
      font-size: 18px;
      font-weight: 700;
      border-top: 1px solid var(--border);
      margin-top: 10px;
    }
    
    .security {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: 20px;
      font-size: 14px;
      color: var(--text-light);
    }
    
    .form-header {
      margin-bottom: 20px;
    }
    
    .form-header h2 {
      font-size: 24px;
      margin-bottom: 10px;
      color: var(--primary);
    }
    
    .form-header p {
      color: var(--text-light);
    }
    
    .form-group {
      margin-bottom: 15px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: var(--text);
      font-size: 14px;
    }
    
    .form-group input {
      width: 100%;
      padding: 10px;
      border: 1px solid var(--border);
      border-radius: 4px;
      font-size: 16px;
      transition: all 0.3s;
    }
    
    .form-group input:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 3px rgba(214, 0, 28, 0.1);
    }
    
    .form-row {
      display: flex;
      gap: 15px;
    }
    
    .form-row .form-group {
      flex: 1;
    }
    
    #card-element {
      border: 1px solid var(--border);
      border-radius: 4px;
      padding: 10px;
      margin-bottom: 5px;
    }
    
    #card-errors {
      color: var(--error);
      font-size: 14px;
      margin-top: 8px;
      min-height: 20px;
    }
    
    .payment-buttons {
      display: flex;
      gap: 15px;
      margin-top: 20px;
    }
    
    .btn {
      flex: 1;
      padding: 12px;
      border-radius: 4px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      text-align: center;
      text-decoration: none;
    }
    
    .btn-primary {
      background: var(--primary);
      color: white;
      border: none;
    }
    
    .btn-primary:hover {
      background: var(--primary-dark);
    }
    
    .btn-outline {
      background: transparent;
      color: var(--primary);
      border: 2px solid var(--primary);
    }
    
    .btn-outline:hover {
      background: rgba(214, 0, 28, 0.1);
    }
    
    .btn i {
      font-size: 18px;
    }
    
    .secure-note {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 15px;
      color: var(--text-light);
      font-size: 14px;
    }
    
    .footer {
      background-color: #eee;
      text-align: center;
      padding: 20px;
      font-size: 14px;
      margin-top: 40px;
    }
    
    .order-detail-row {
      display: flex;
      margin-bottom: 10px;
    }
    
    .detail-label {
      width: 120px;
      font-weight: 600;
      color: var(--text-light);
      font-size: 14px;
    }
    
    .detail-value {
      flex: 1;
      font-size: 14px;
    }
    
    /* 支付状态样式 */
    .payment-status {
      padding: 15px;
      border-radius: 8px;
      text-align: center;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      font-weight: 600;
      font-size: 18px;
    }
    
    .status-pending {
      background-color: rgba(245, 158, 11, 0.15);
      color: var(--warning);
      border: 1px solid var(--warning);
    }
    
    .status-canceled {
      background-color: rgba(239, 68, 68, 0.15);
      color: var(--error);
      border: 1px solid var(--error);
    }
    
    .status-success {
      background-color: rgba(16, 185, 129, 0.15);
      color: var(--success);
      border: 1px solid var(--success);
    }
    
    /* 取消提示框 */
    .cancel-notification {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 400px;
      max-width: 90%;
      padding: 25px;
      background: #f0f0f0;
      border: 3px solid #10b981;
      border-radius: 10px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.2);
      z-index: 1000;
      text-align: center;
      display: none;
    }
    
    .cancel-notification h3 {
      color: #ef4444;
      margin-bottom: 15px;
      font-size: 22px;
    }
    
    .cancel-notification p {
      color: #333;
      margin-bottom: 20px;
      font-size: 16px;
      line-height: 1.6;
    }
    
    .cancel-notification .countdown {
      color: #ef4444;
      font-weight: bold;
      font-size: 18px;
    }
    
    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      z-index: 999;
      display: none;
    }
    
    /* 消息覆盖样式 */
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
      color: var(--primary);
      margin-bottom: 20px;
    }
    
    .message-box p {
      margin-bottom: 20px;
    }
    
    .spinner {
      border: 4px solid rgba(0,0,0,0.1);
      border-radius: 50%;
      border-top: 4px solid var(--primary);
      width: 30px;
      height: 30px;
      animation: spin 1s linear infinite;
      margin: 0 auto 20px;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* 支付成功动画 */
    .success-animation {
      display: flex;
      justify-content: center;
      margin: 20px 0;
    }
    
    .checkmark {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      display: block;
      stroke-width: 2;
      stroke: #4bb71b;
      stroke-miterlimit: 10;
      box-shadow: inset 0px 0px 0px #4bb71b;
      animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
    }
    
    .checkmark__circle {
      stroke-dasharray: 166;
      stroke-dashoffset: 166;
      stroke-width: 2;
      stroke-miterlimit: 10;
      stroke: #4bb71b;
      fill: none;
      animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    
    .checkmark__check {
      transform-origin: 50% 50%;
      stroke-dasharray: 48;
      stroke-dashoffset: 48;
      animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }
    
    @keyframes stroke {
      100% {
        stroke-dashoffset: 0;
      }
    }
    
    @keyframes scale {
      0%, 100% {
        transform: none;
      }
      50% {
        transform: scale3d(1.1, 1.1, 1);
      }
    }
    
    @keyframes fill {
      100% {
        box-shadow: inset 0px 0px 0px 50px #4bb71b;
      }
    }
    
    @media (max-width: 768px) {
      .container {
        flex-direction: column;
      }
      
      .main-content {
        padding: 80px 15px 15px;
      }
      
      .payment-buttons {
        flex-direction: column;
      }
      
      .topbar {
        padding: 12px 15px;
      }
      
      .nav-links {
        gap: 10px;
      }
    }
  </style>
</head>
<body>
<!-- 顶部导航栏 -->
<div class="topbar">
    <div class="logo"><i class="fas fa-hamburger"></i> Fast<span>Food</span> Express</div>
    <div class="nav-links">
        <a href="index_user.php">Home</a>
        
        <!-- 订单下拉菜单 -->
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

<!-- 取消支付提示框 -->
<div class="overlay" id="overlay"></div>
<div class="cancel-notification" id="cancelNotification">
  <i class="fas fa-times-circle fa-3x" style="color: #ef4444; margin-bottom: 15px;"></i>
  <h3>Payment Canceled</h3>
  <p>You have canceled this payment. You will be redirected to the home page in <span class="countdown" id="countdown">5</span> seconds.</p>
</div>

<!-- 消息覆盖层 -->
<div class="message-overlay" id="messageOverlay">
  <div class="message-box">
    <div class="spinner"></div>
    <h3 id="messageTitle">Processing Payment</h3>
    <p id="messageText">Please wait while we process your payment...</p>
  </div>
</div>

<div class="main-content">
  <div class="container">
    <div class="payment-details">
      <div class="logo-section">
        <i class="fas fa-lock"></i>
        <h1>Secure Payment</h1>
      </div>
      
      <!-- 支付状态显示 -->
      <?php if ($payment_canceled): ?>
        <div class="payment-status status-canceled">
          <i class="fas fa-times-circle"></i>
          <span>Payment Canceled</span>
        </div>
        <div class="form-header">
          <h2>Payment Canceled</h2>
          <p>You have canceled this payment. You can restart the payment process or view your orders.</p>
        </div>
      <?php else: ?>
        <div class="payment-status status-pending">
          <i class="fas fa-clock"></i>
          <span>Payment Pending</span>
        </div>
        <div class="form-header">
          <h2>Payment Details</h2>
          <p>Enter your card information to complete the order</p>
        </div>
      <?php endif; ?>
      
      <?php if (!$payment_canceled): ?>
        <form id="payment-form">
          <!-- CSRF 令牌 -->
          <input type="hidden" id="csrf_token" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
          
          <div class="form-group">
            <label for="name">Cardholder Name</label>
            <input type="text" id="name" placeholder="John Doe" required>
          </div>
          
          <div class="form-group">
            <label>Card Details</label>
            <div id="card-element"></div>
            <div id="card-errors" role="alert"></div>
          </div>
          
          <div class="payment-buttons">
            <button type="submit" id="submit-button" class="btn btn-primary">
              <i class="fas fa-lock"></i>
              Pay RM <?php echo number_format($order['final_total'], 2); ?>
            </button>
            
            <button type="button" id="cancel-payment-btn" class="btn btn-outline">
              <i class="fas fa-ban"></i>
              Cancel Payment
            </button>
          </div>
          
          <div class="secure-note">
            <i class="fas fa-shield-alt"></i>
            <span>Secured by Stripe | No card data stored on our servers</span>
          </div>
        </form>
      <?php else: ?>
        <div class="payment-buttons">
          <a href="index_user.php" class="btn btn-primary">
            <i class="fas fa-home"></i>
            Return to Home
          </a>
          
          <a href="order_history.php" class="btn btn-outline">
            <i class="fas fa-history"></i>
            View Order History
          </a>
        </div>
      <?php endif; ?>
    </div>
    
    <div class="order-summary">
      <div class="logo-section">
        <i class="fas fa-receipt"></i>
        <h1>Order Summary</h1>
      </div>
      
      <div class="order-info">
        <h3>Your Order Details</h3>
        <?php foreach ($order_items as $item): ?>
          <div class="order-item">
            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="item-image">
            <div class="item-details">
              <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
              <div class="item-price">RM <?php echo number_format($item['price'], 2); ?></div>
              <div class="item-extras">
                Quantity: <?php echo $item['quantity']; ?>
                <?php if (!empty($item['sauce'])): ?>
                  <br>Sauce: <?php echo htmlspecialchars($item['sauce']); ?>
                <?php endif; ?>
                <?php if (!empty($item['comment'])): ?>
                  <br>Note: <?php echo htmlspecialchars($item['comment']); ?>
                <?php endif; ?>
              </div>
              <div class="item-stock <?php echo $item['stock_quantity'] >= $item['quantity'] ? 'stock-ok' : 'stock-low'; ?>">
                <i class="fas fa-box"></i> 
                Stock: <?php echo $item['stock_quantity']; ?> 
                <?php if ($item['stock_quantity'] < $item['quantity']): ?>
                  (Insufficient)
                <?php endif; ?>
              </div>
            </div>
            <div class="item-price">RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
          </div>
        <?php endforeach; ?>
        <div class="order-item">
          <span>Delivery Fee</span>
          <span>RM <?php echo number_format($order['delivery_fee'], 2); ?></span>
        </div>
        <div class="total">
          <span>Total Amount</span>
          <span>RM <?php echo number_format($order['final_total'], 2); ?></span>
        </div>
      </div>
      
      <!-- 动态显示订单类型信息 -->
      <div class="order-info">
        <h3>
          <i class="fas <?php echo $order_type_icon; ?>"></i>
          <?php echo $order_type_title; ?>
        </h3>
        <div class="order-detail-row">
          <div class="detail-label">Order ID:</div>
          <div class="detail-value">#<?php echo $order_id; ?></div>
        </div>
        <div class="order-detail-row">
          <div class="detail-label">Name:</div>
          <div class="detail-value"><?php echo htmlspecialchars($order['recipient_name']); ?></div>
        </div>
        <div class="order-detail-row">
          <div class="detail-label">Phone:</div>
          <div class="detail-value"><?php echo htmlspecialchars($order['recipient_phone']); ?></div>
        </div>
        
        <?php if ($order_type === 'delivery'): ?>
          <!-- 配送信息 -->
          <div class="order-detail-row">
            <div class="detail-label">Address:</div>
            <div class="detail-value"><?php echo htmlspecialchars($order['recipient_address']); ?></div>
          </div>
        <?php endif; ?>
        
        <div class="order-detail-row">
          <div class="detail-label">Method:</div>
          <div class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $order['delivery_method'])); ?></div>
        </div>
        <div class="order-detail-row">
          <div class="detail-label">Status:</div>
          <div class="detail-value">
            <?php if ($payment_canceled): ?>
              <span class="status-canceled" style="padding: 4px 8px; border-radius: 4px; display: inline-block;">Canceled</span>
            <?php else: ?>
              <span class="status-pending" style="padding: 4px 8px; border-radius: 4px; display: inline-block;">Pending Payment</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <div class="security">
        <i class="fas fa-shield-alt"></i>
        <span>Your payment details are securely encrypted and processed by Stripe</span>
      </div>
    </div>
  </div>
</div>

<footer class="footer">
  &copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.
</footer>

<?php if (!$payment_canceled): ?>
<script>
  // 添加支付状态标志
  let paymentCompleted = false;
  
  const stripe = Stripe('<?php echo $_ENV['STRIPE_PUBLISHABLE_KEY']; ?>');
  const elements = stripe.elements();
  
  // 创建不带邮政编码字段的卡元素，并禁用Link功能
  const card = elements.create('card', {
    style: {
      base: {
        fontSize: '16px',
        color: '#1f2937',
        '::placeholder': {
          color: '#9ca3af',
        },
        iconColor: '#d6001c',
      },
    },
    hidePostalCode: true,
    disableLink: true  // 禁用Stripe Link功能
  });
  
  card.mount('#card-element');
  
  // 处理卡元素的实时验证错误
  card.addEventListener('change', function(event) {
    const displayError = document.getElementById('card-errors');
    if (event.error) {
      displayError.textContent = event.error.message;
    } else {
      displayError.textContent = '';
    }
  });
  
  // 处理表单提交
  const form = document.getElementById('payment-form');
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    
    // 验证CSRF令牌
    const csrfToken = document.getElementById('csrf_token').value;
    if (!csrfToken) {
      const displayError = document.getElementById('card-errors');
      displayError.textContent = 'Security token missing. Please refresh the page.';
      return;
    }
    
    // 显示处理覆盖层
    const overlay = document.getElementById('messageOverlay');
    document.getElementById('messageTitle').textContent = 'Processing Payment';
    document.getElementById('messageText').textContent = 'Please wait while we process your payment...';
    overlay.classList.add('active');
    
    // 禁用提交按钮防止多次提交
    const submitButton = document.getElementById('submit-button');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment';
    
    try {
      const {paymentIntent, error} = await stripe.confirmCardPayment(
        '<?php echo $paymentIntent->client_secret; ?>',
        {
          payment_method: {
            card: card,
            billing_details: {
              name: document.getElementById('name').value
            }
          }
        }
      );
      
      if (error) {
        // 向客户显示错误
        overlay.classList.remove('active');
        submitButton.disabled = false;
        submitButton.innerHTML = '<i class="fas fa-lock"></i> Pay RM <?php echo number_format($order["final_total"], 2); ?>';
        
        const errorElement = document.getElementById('card-errors');
        errorElement.textContent = error.message || 'An error occurred during payment processing.';
      } else if (paymentIntent.status === 'succeeded') {
        // 标记支付已完成
        paymentCompleted = true;
        
        // 更新消息显示支付成功
        document.getElementById('messageTitle').textContent = 'Payment Successful!';
        document.getElementById('messageText').textContent = 'Your payment was processed successfully.';
        
        // 更新UI显示成功状态
        setTimeout(() => {
          // 重定向到成功页面
          window.location.href = 'payment_success.php?order_id=<?php echo $order_id; ?>&payment_intent=' + paymentIntent.id;
        }, 2000);
      }
    } catch (error) {
      console.error('Payment processing error:', error);
      overlay.classList.remove('active');
      submitButton.disabled = false;
      submitButton.innerHTML = '<i class="fas fa-lock"></i> Pay RM <?php echo number_format($order["final_total"], 2); ?>';
      
      const errorElement = document.getElementById('card-errors');
      errorElement.textContent = 'An unexpected error occurred. Please try again.';
    }
  });
  
  // 处理取消支付按钮
  document.getElementById('cancel-payment-btn').addEventListener('click', function() {
    if (confirm('Are you sure you want to cancel this payment?')) {
      // 标记支付已完成
      paymentCompleted = true;
      
      // 显示加载状态
      const overlay = document.getElementById('messageOverlay');
      document.getElementById('messageTitle').textContent = 'Canceling Payment';
      document.getElementById('messageText').textContent = 'Please wait while we cancel your payment...';
      overlay.classList.add('active');
      
      // 发送AJAX请求取消支付
      fetch('cancel_payment.php?order_id=<?php echo $order_id; ?>')
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // 隐藏加载状态
            overlay.classList.remove('active');
            
            // 显示取消通知
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('cancelNotification').style.display = 'block';
            
            // 开始倒计时
            let count = 5;
            const countdown = document.getElementById('countdown');
            countdown.textContent = count;
            
            const timer = setInterval(() => {
              count--;
              countdown.textContent = count;
              
              if (count <= 0) {
                clearInterval(timer);
                window.location.href = 'index_user.php';
              }
            }, 1000);
          } else {
            // 处理错误
            overlay.classList.remove('active');
            alert('Error canceling payment: ' + (data.message || 'Unknown error'));
          }
        })
        .catch(error => {
          overlay.classList.remove('active');
          console.error('Error:', error);
          alert('An error occurred while canceling the payment.');
        });
    }
  });
  
  // 处理浏览器后退键
  window.addEventListener('beforeunload', function(e) {
    if (!paymentCompleted) {
      // 用户离开页面时取消支付
      fetch('cancel_payment.php?order_id=<?php echo $order_id; ?>', {
        method: 'POST',
        keepalive: true
      });
    }
  });
</script>
<?php endif; ?>
</body>
</html>