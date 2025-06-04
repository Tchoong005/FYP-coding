<?php
session_start();
require __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$pid = $_GET['payment_intent'] ?? '';
$order_id = $_GET['order_id'] ?? 0;
$status = '';
$amount = '';
$currency = '';
$receipt_url = '';
$error = '';
$order_items = [];
$order_time = '';
$payment_processed = false;

// 修正快递费为6.00
define('DELIVERY_FEE', 6.00);

if ($pid && $order_id) {
    try {
        $pi = \Stripe\PaymentIntent::retrieve($pid);
        
        if ($pi->status === 'succeeded') {
            $status = 'succeeded';
            $amount = number_format($pi->amount_received / 100, 2);
            $currency = strtoupper($pi->currency);
            
            if (!empty($pi->charges->data[0]->receipt_url)) {
                $receipt_url = $pi->charges->data[0]->receipt_url;
            }
            
            $current_payment_status = getPaymentStatus($order_id);
            
            if ($current_payment_status !== 'paid') {
                if (updatePaymentStatus($order_id, 'paid')) {
                    reduceStockForOrder($order_id);
                    $payment_processed = true;
                }
            }
            
            $order_items = getOrderItems($order_id);
            $order_time = getOrderTime($order_id);
        } else {
            $status = $pi->status;
            $error = 'Payment not completed: ' . $status;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    $error = 'Missing required parameters';
}

function getPaymentStatus($order_id) {
    include 'db.php';
    
    $stmt = $conn->prepare("SELECT payment_status FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['payment_status'];
    }
    
    $stmt->close();
    $conn->close();
    
    return null;
}

function updatePaymentStatus($order_id, $status) {
    include 'db.php';
    
    $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Payment status update failed: " . $stmt->error);
        return false;
    }
    
    $stmt->close();
    $conn->close();
}

function getOrderTime($order_id) {
    include 'db.php';
    
    $stmt = $conn->prepare("SELECT created_at FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // 直接使用数据库中的时间，不进行时区转换
        return date('M j, Y H:i', strtotime($row['created_at'])) . ' (MYT)';
    }
    
    $stmt->close();
    $conn->close();
    
    return date('M j, Y H:i') . ' (MYT)';
}

function reduceStockForOrder($order_id) {
    include 'db.php';
    
    $stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $product_id = $row['product_id'];
        $quantity = $row['quantity'];
        
        $update_stmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $update_stmt->bind_param("ii", $quantity, $product_id);
        $update_stmt->execute();
        
        if ($update_stmt->error) {
            error_log("Stock reduction failed for product {$product_id}: " . $update_stmt->error);
        }
        
        $update_stmt->close();
    }
    
    $stmt->close();
    $conn->close();
}

function getOrderItems($order_id) {
    include 'db.php';
    $items = [];
    
    $stmt = $conn->prepare("
        SELECT p.name, oi.quantity, oi.price 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $items;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Status - Fast Food Order</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #d32f2f; /* 主红色 */
      --primary-dark: #b71c1c; /* 深红色 */
      --secondary: #212121; /* 黑色 */
      --light: #ffffff; /* 白色 */
      --dark: #212529; /* 深灰 */
      --success: #28a745; /* 绿色用于成功状态 */
      --danger: #dc3545; /* 错误红色 */
      --warning: #ffc107; /* 警告黄色 */
      --info: #17a2b8; /* 蓝色信息 */
      --gray: #6c757d; /* 灰色 */
      --light-gray: #f8f9fa; /* 浅灰色 */
      --border-radius: 12px;
      --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s ease;
      --bg-dark: #f5f7fa; /* 浅灰色背景 */
      --bg-card: rgba(255, 255, 255, 0.95); /* 卡片背景 */
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg-dark);
      color: #333;
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    
    .container {
      max-width: 900px;
      width: 100%;
      margin: 0 auto;
      animation: fadeIn 0.6s ease-out;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .logo {
      text-align: center;
      margin-bottom: 30px;
      animation: slideInDown 0.5s ease-out;
    }
    
    @keyframes slideInDown {
      from { opacity: 0; transform: translateY(-50px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .logo h1 {
      font-size: 2.5rem;
      color: var(--primary);
      margin-bottom: 5px;
      font-weight: 800;
      letter-spacing: 1px;
    }
    
    .logo p {
      color: var(--gray);
      font-size: 1.1rem;
      letter-spacing: 1px;
    }
    
    .payment-card {
      background: var(--bg-card);
      border-radius: var(--border-radius);
      box-shadow: var(--box-shadow);
      overflow: hidden;
      margin-bottom: 30px;
      border: 1px solid rgba(0, 0, 0, 0.1);
      animation: scaleIn 0.4s ease-out;
    }
    
    @keyframes scaleIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
    
    .payment-header {
      padding: 40px 30px;
      text-align: center;
      background: linear-gradient(to right, var(--primary), var(--primary-dark));
      color: var(--light);
      position: relative;
      overflow: hidden;
    }
    
    .payment-header.success {
      background: linear-gradient(to right, var(--primary), var(--primary-dark));
    }
    
    .payment-header.error {
      background: linear-gradient(to right, var(--danger), #c82333);
    }
    
    .payment-header.processing {
      background: linear-gradient(to right, #17a2b8, #138496);
    }
    
    .status-icon {
      width: 100px;
      height: 100px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
      70% { box-shadow: 0 0 0 15px rgba(255, 255, 255, 0); }
      100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
    }
    
    .status-icon i {
      font-size: 50px;
      color: var(--light);
    }
    
    .error .status-icon i {
      color: var(--light);
    }
    
    .processing .status-icon i {
      color: var(--light);
    }
    
    .payment-header h2 {
      font-size: 2.2rem;
      margin-bottom: 10px;
      font-weight: 700;
    }
    
    .payment-header p {
      font-size: 1.1rem;
      opacity: 0.9;
      max-width: 600px;
      margin: 0 auto;
    }
    
    .payment-body {
      padding: 30px;
      color: #333;
    }
    
    .payment-details {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .detail-card {
      background: #ffffff;
      border-radius: 12px;
      padding: 25px 20px;
      text-align: center;
      transition: var(--transition);
      border: 1px solid rgba(0, 0, 0, 0.1);
      position: relative;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    
    .detail-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      border-color: rgba(0, 0, 0, 0.2);
    }
    
    .detail-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: var(--primary);
    }
    
    .detail-card i {
      font-size: 36px;
      color: var(--primary);
      margin-bottom: 15px;
    }
    
    .detail-card h3 {
      font-size: 1rem;
      color: var(--gray);
      margin-bottom: 8px;
      font-weight: 500;
    }
    
    .detail-card p {
      font-size: 1.4rem;
      font-weight: 700;
      color: #333;
    }
    
    .amount {
      color: var(--primary);
      font-size: 1.8rem !important;
      font-weight: 800;
    }
    
    .action-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      justify-content: center;
      margin-top: 30px;
    }
    
    .btn {
      padding: 14px 28px;
      border-radius: 50px;
      font-size: 1rem;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      cursor: pointer;
      transition: var(--transition);
      border: none;
      min-width: 200px;
    }
    
    .btn-primary {
      background: var(--primary);
      color: var(--light);
    }
    
    .btn-primary:hover {
      background: var(--primary-dark);
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(211, 47, 47, 0.3);
    }
    
    .btn-outline {
      background: transparent;
      color: var(--primary);
      border: 2px solid var(--primary);
    }
    
    .btn-outline:hover {
      background: rgba(211, 47, 47, 0.1);
      transform: translateY(-3px);
    }
    
    .btn-success {
      background: var(--success);
      color: var(--light);
    }
    
    .btn-success:hover {
      background: #218838;
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }
    
    .btn-danger {
      background: var(--danger);
      color: var(--light);
    }
    
    .btn-danger:hover {
      background: #c82333;
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }
    
    .receipt-section {
      background: #f8f9fa;
      border-radius: 12px;
      padding: 25px;
      margin-top: 30px;
      text-align: center;
      border: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .receipt-section h3 {
      margin-bottom: 20px;
      color: #333;
      font-size: 1.4rem;
      font-weight: 700;
    }
    
    .footer {
      text-align: center;
      color: var(--gray);
      font-size: 0.9rem;
      padding: 20px;
    }
    
    .footer p {
      margin-bottom: 5px;
    }
    
    @media (max-width: 768px) {
      .payment-details {
        grid-template-columns: 1fr;
      }
      
      .action-buttons {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
      }
    }
    
    .wave {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      overflow: hidden;
      line-height: 0;
      transform: rotate(180deg);
    }
    
    .wave svg {
      position: relative;
      display: block;
      width: calc(100% + 1.3px);
      height: 80px;
    }
    
    .wave .shape-fill {
      fill: rgba(255, 255, 255, 0.15);
    }
    
    .payment-id {
      background: rgba(255, 255, 255, 0.15);
      padding: 8px 15px;
      border-radius: 50px;
      font-size: 0.9rem;
      display: inline-block;
      margin-top: 15px;
      color: var(--light);
      border: 1px solid rgba(255,255,255,0.3);
    }
    
    /* Order Items Styles */
    .order-items {
      margin-top: 30px;
      border: 1px solid rgba(0, 0, 0, 0.1);
      border-radius: 12px;
      overflow: hidden;
      background: #fff;
    }
    
    .order-items-header {
      background: #f8f9fa;
      padding: 15px 20px;
      font-weight: 700;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
      color: #333;
    }
    
    .order-item {
      display: flex;
      justify-content: space-between;
      padding: 15px 20px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
      background: #fff;
      transition: var(--transition);
    }
    
    .order-item:hover {
      background: #f8f9fa;
    }
    
    .order-item:last-child {
      border-bottom: none;
    }
    
    .item-name {
      flex: 2;
      font-weight: 500;
      color: #333;
    }
    
    .item-qty {
      flex: 1;
      text-align: center;
      color: var(--primary);
    }
    
    .item-price {
      flex: 1;
      text-align: right;
      font-weight: 600;
      color: #333;
    }
    
    .order-total {
      display: flex;
      justify-content: space-between;
      padding: 15px 20px;
      background: #f8f9fa;
      font-weight: 700;
      font-size: 1.1rem;
      border-top: 1px solid rgba(0, 0, 0, 0.1);
      color: #333;
    }
    
    .total-label {
      color: var(--gray);
    }
    
    .total-value {
      color: var(--primary);
      font-size: 1.2rem;
    }
    
    /* PDF Receipt Styles */
    #pdf-receipt {
      display: none;
      padding: 40px;
      background: #ffffff;
      max-width: 500px;
      margin: 0 auto;
      box-shadow: var(--box-shadow);
      color: #333333;
      border-radius: var(--border-radius);
    }
    
    .receipt-header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid var(--primary);
    }
    
    .receipt-title {
      font-size: 24px;
      font-weight: 800;
      margin-bottom: 5px;
      color: var(--primary);
    }
    
    .receipt-subtitle {
      color: var(--gray);
      font-size: 16px;
      margin-bottom: 10px;
    }
    
    .receipt-location {
      font-size: 14px;
      color: var(--gray);
      margin-bottom: 5px;
    }
    
    .receipt-details {
      margin-bottom: 30px;
    }
    
    .receipt-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
    }
    
    .receipt-label {
      font-weight: 600;
      color: var(--gray);
    }
    
    .receipt-value {
      font-weight: 700;
      color: #222;
    }
    
    .receipt-items {
      margin: 20px 0;
    }
    
    .receipt-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
      padding-bottom: 5px;
      border-bottom: 1px dashed #e0e0e0;
    }
    
    .receipt-total {
      font-size: 18px;
      font-weight: 800;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 2px solid var(--primary);
    }
    
    .receipt-footer {
      text-align: center;
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #e0e0e0;
      color: var(--gray);
      font-size: 14px;
    }
    
    .thank-you {
      font-weight: 700;
      color: var(--primary);
      margin-top: 10px;
    }
    
    .processing-text {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-top: 20px;
      animation: pulseText 1.5s infinite;
    }
    
    @keyframes pulseText {
      0% { opacity: 0.5; }
      50% { opacity: 1; }
      100% { opacity: 0.5; }
    }
    
    .status-notice {
      background: #e8f4ff;
      padding: 15px;
      border-radius: 8px;
      margin: 20px 0;
      text-align: center;
      border-left: 4px solid var(--primary);
      color: #333;
    }
    
    .status-notice i {
      color: var(--primary);
      margin-right: 10px;
    }
    
    .notification {
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 15px 25px;
      border-radius: 8px;
      background: var(--success);
      color: white;
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
      transform: translateX(200%);
      transition: transform 0.4s ease;
      z-index: 1000;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .notification.show {
      transform: translateX(0);
    }
    
    .notification.error {
      background: var(--danger);
    }
    
    /* 快递费显示样式 */
    .delivery-fee-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 20px;
      background: #f8f9fa;
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    .delivery-fee-label {
      color: var(--gray);
    }
    
    .delivery-fee-value {
      color: var(--primary);
      font-weight: 600;
    }
    
    .subtotal-row {
      display: flex;
      justify-content: space-between;
      padding: 12px 20px;
      background: #f8f9fa;
    }
    
    .subtotal-label {
      color: var(--gray);
    }
    
    .subtotal-value {
      color: #333;
      font-weight: 600;
    }
    
    /* 时间戳样式 */
    .timestamp {
      color: var(--gray);
      font-size: 0.85rem;
      text-align: center;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <div id="notification" class="notification">
    <i class="fas fa-check-circle"></i>
    <span>PDF receipt generated successfully!</span>
  </div>
  
  <div class="container">
    <div class="logo">
      <h1>FASTFOOD EXPRESS</h1>
      <p>ORDER CONFIRMATION & PAYMENT RECEIPT</p>
    </div>
    
    <div class="payment-card">
      <?php if ($error): ?>
        <div class="payment-header error">
          <div class="status-icon">
            <i class="fas fa-times-circle"></i>
          </div>
          <h2>Payment Failed</h2>
          <p>We encountered an issue processing your payment</p>
          <div class="payment-id">Payment ID: <?php echo htmlspecialchars($pid); ?></div>
          <div class="wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
              <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
          </div>
        </div>
        <div class="payment-body">
          <div class="detail-card">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Error Message</h3>
            <p><?php echo htmlspecialchars($error); ?></p>
          </div>
          
          <div class="action-buttons">
            <a href="checkout.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary">
              <i class="fas fa-redo-alt"></i> Retry Payment
            </a>
            <a href="index.php" class="btn btn-outline">
              <i class="fas fa-home"></i> Return to Home
            </a>
          </div>
          
          <div class="timestamp">Error occurred at: <?php echo date('M j, Y H:i'); ?> (MYT)</div>
        </div>
      <?php elseif ($status === 'succeeded'): ?>
        <div class="payment-header success">
          <div class="status-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <h2>Payment Successful!</h2>
          <p>Thank you for your order. Your payment has been processed.</p>
          <div class="payment-id">Payment ID: <?php echo htmlspecialchars($pid); ?></div>
          <div class="wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
              <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
          </div>
        </div>
        <div class="payment-body">
          <div class="status-notice">
            <i class="fas fa-info-circle"></i>
            <span>Your payment has been processed successfully. Inventory has been updated.</span>
          </div>
          
          <div class="payment-details">
            <div class="detail-card">
              <i class="fas fa-receipt"></i>
              <h3>Order ID</h3>
              <p>#<?php echo htmlspecialchars($order_id); ?></p>
            </div>
            
            <div class="detail-card">
              <i class="fas fa-money-bill-wave"></i>
              <h3>Total Amount</h3>
              <p class="amount"><?php echo $amount . ' ' . $currency; ?></p>
            </div>
            
            <div class="detail-card">
              <i class="fas fa-calendar-check"></i>
              <h3>Date & Time</h3>
              <p><?php echo $order_time; ?> </p>
            </div>
          </div>
          
          <!-- Order Items Section with Delivery Fee -->
          <?php if (!empty($order_items)): 
            // 计算商品小计
            $subtotal = 0;
            foreach ($order_items as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            $delivery_fee = DELIVERY_FEE;
            $total = $subtotal + $delivery_fee;
          ?>
            <div class="order-items">
              <div class="order-items-header">Order Details</div>
              <?php foreach ($order_items as $item): ?>
                <div class="order-item">
                  <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                  <div class="item-qty">x <?php echo htmlspecialchars($item['quantity']); ?></div>
                  <div class="item-price">RM <?php echo number_format($item['price'], 2); ?></div>
                </div>
              <?php endforeach; ?>
              
              <!-- 添加快递费行 -->
              <div class="delivery-fee-row">
                <div class="delivery-fee-label">Delivery Fee</div>
                <div class="delivery-fee-value">RM <?php echo number_format($delivery_fee, 2); ?></div>
              </div>
              
              <div class="subtotal-row">
                <div class="subtotal-label">Subtotal:</div>
                <div class="subtotal-value">RM <?php echo number_format($subtotal, 2); ?></div>
              </div>
              
              <div class="order-total">
                <div class="total-label">Total:</div>
                <div class="total-value">RM <?php echo $amount; ?></div>
              </div>
            </div>
          <?php endif; ?>
          
          <div class="receipt-section">
            <h3>Download Your Payment Receipt</h3>
            <div class="action-buttons">
              <button id="generate-receipt" class="btn btn-success">
                <i class="fas fa-file-pdf"></i> Generate PDF Receipt
              </button>
              <a href="index_user.php" class="btn btn-outline">
                <i class="fas fa-home"></i> Return to Home
              </a>
            </div>
          </div>
          
          <div class="timestamp">Order processed at: <?php echo date('M j, Y H:i'); ?> (MYT)</div>
        </div>
      <?php else: ?>
        <div class="payment-header processing">
          <div class="status-icon">
            <i class="fas fa-clock"></i>
          </div>
          <h2>Payment Processing</h2>
          <p>Your payment is being processed. Please wait...</p>
          <div class="payment-id">Payment ID: <?php echo htmlspecialchars($pid); ?></div>
          <div class="wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
              <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
          </div>
        </div>
        <div class="payment-body">
          <div class="status-notice">
            <i class="fas fa-info-circle"></i>
            <span>Please do not refresh this page while we process your payment.</span>
          </div>
          
          <div class="detail-card">
            <i class="fas fa-info-circle"></i>
            <h3>Current Status</h3>
            <p><?php echo htmlspecialchars($status); ?></p>
          </div>
          
          <div class="processing-text">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Processing your payment...</span>
          </div>
          
          <div class="action-buttons">
            <a href="index.php" class="btn btn-outline">
              <i class="fas fa-home"></i> Return to Home
            </a>
          </div>
          
          <div class="timestamp">Last updated: <?php echo date('M j, Y H:i'); ?> (MYT)</div>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- Hidden receipt template for PDF generation with Delivery Fee -->
    <div id="pdf-receipt">
      <div class="receipt-header">
        <div class="receipt-title">FASTFOOD EXPRESS</div>
        <div class="receipt-subtitle">PAYMENT RECEIPT</div>
      </div>
      
      <div class="receipt-details">
        <div class="receipt-row">
          <span class="receipt-label">Order ID:</span>
          <span class="receipt-value">#<?php echo $order_id; ?></span>
        </div>
        <div class="receipt-row">
          <span class="receipt-label">Payment ID:</span>
          <span class="receipt-value"><?php echo $pid; ?></span>
        </div>
        <div class="receipt-row">
          <span class="receipt-label">Date & Time:</span>
          <span class="receipt-value"><?php echo $order_time; ?> (MYT)</span>
        </div>
        <div class="receipt-row">
          <span class="receipt-label">Payment Status:</span>
          <span class="receipt-value">Completed</span>
        </div>
      </div>
      
      <div class="receipt-items">
        <div class="receipt-row" style="font-weight: 700; border-bottom: 1px solid #ddd; padding-bottom: 8px; margin-bottom: 10px;">
          <span>Item</span>
          <span>Amount</span>
        </div>
        <?php 
        $subtotal = 0;
        foreach ($order_items as $item): 
          $itemTotal = $item['price'] * $item['quantity'];
          $subtotal += $itemTotal;
        ?>
          <div class="receipt-item">
            <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
            <span>RM <?php echo number_format($itemTotal, 2); ?></span>
          </div>
        <?php endforeach; ?>
        
        <!-- 添加快递费到PDF -->
        <div class="receipt-item">
          <span>Delivery Fee</span>
          <span>RM <?php echo number_format(DELIVERY_FEE, 2); ?></span>
        </div>
        
        <div class="receipt-item" style="font-weight: 600; border-bottom: none; padding-top: 10px;">
          <span>Subtotal</span>
          <span>RM <?php echo number_format($subtotal, 2); ?></span>
        </div>
      </div>
      
      <div class="receipt-total">
        <div class="receipt-row">
          <span class="receipt-label">Total Amount:</span>
          <span class="receipt-value">RM <?php echo $amount; ?></span>
        </div>
      </div>
      
      <div class="receipt-footer">
        <p>Thank you for choosing FastFood Express!</p>
        <p class="thank-you">We appreciate your business</p>
        <p>FastFood Express &copy; <?php echo date('Y'); ?></p>
      </div>
    </div>
    
    <div class="footer">
      <p>&copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.</p>
    </div>
  </div>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const generateBtn = document.getElementById('generate-receipt');
      const notification = document.getElementById('notification');
      
      if (generateBtn) {
        generateBtn.addEventListener('click', function() {
          // Show loading state
          const originalText = generateBtn.innerHTML;
          generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
          generateBtn.disabled = true;
          
          // Generate PDF
          setTimeout(() => {
            const { jsPDF } = window.jspdf;
            
            // Create a PDF
            const pdf = new jsPDF('p', 'mm', 'a5');
            const pageWidth = pdf.internal.pageSize.getWidth();
            const centerX = pageWidth / 2;
            
            // Add content to PDF
            pdf.setFontSize(22);
            pdf.setFont('helvetica', 'bold');
            pdf.setTextColor(0, 0, 0);
            pdf.text('FASTFOOD EXPRESS', centerX, 15, null, null, 'center');
            
            pdf.setFontSize(14);
            pdf.setTextColor(0, 0, 0);
            pdf.setFont('helvetica', 'normal');
            pdf.text('PAYMENT RECEIPT', centerX, 22, null, null, 'center');

            // Draw line
            pdf.setLineWidth(0.5);
            pdf.setDrawColor(0, 0, 0);
            pdf.line(20, 32, pageWidth - 20, 32);
            
            // Add details
            pdf.setFontSize(10);
            pdf.setTextColor(0, 0, 0);
            pdf.text(`Order ID: #<?php echo $order_id; ?>`, 20, 40);
            pdf.text(`Payment ID: <?php echo $pid; ?>`, 20, 45);
            pdf.text(`Date & Time: <?php echo $order_time; ?>`, 20, 50);
            pdf.text(`Payment Status: Completed`, 20, 55);
            
            // Order items table
            const items = [
              <?php 
              foreach ($order_items as $item) {
                echo "['" . addslashes($item['name']) . " x " . $item['quantity'] . "', 'RM " . number_format($item['price'] * $item['quantity'], 2) . "'],";
              }
              ?>
            ];
            
            // Add delivery fee row
            items.push(['Delivery Fee', 'RM <?php echo number_format(DELIVERY_FEE, 2); ?>']);
            
            // Add items header
            pdf.setFont('helvetica', 'bold');
            pdf.text('Item', 20, 65);
            pdf.text('Amount', pageWidth - 20, 65, null, null, 'right');
            
            // Add items
            let yPos = 70;
            pdf.setFont('helvetica', 'normal');
            items.forEach(item => {
              pdf.text(item[0], 20, yPos);
              pdf.text(item[1], pageWidth - 20, yPos, null, null, 'right');
              yPos += 5;
            });
            
            // Subtotal
            pdf.setFont('helvetica', 'bold');
            pdf.text('Subtotal:', 20, yPos + 5);
            pdf.text('RM <?php echo number_format($subtotal, 2); ?>', pageWidth - 20, yPos + 5, null, null, 'right');
            
            // Total amount
            pdf.setLineWidth(0.2);
            pdf.line(20, yPos + 10, pageWidth - 20, yPos + 10);
            pdf.setFontSize(12);
            pdf.text('Total Amount:', 20, yPos + 15);
            pdf.setTextColor(211, 47, 47); // Red color for total
            pdf.text('RM <?php echo $amount; ?>', pageWidth - 20, yPos + 15, null, null, 'right');
            
            // Footer
            const pageHeight = pdf.internal.pageSize.getHeight();
            const footerY = pageHeight - 20;
            
            pdf.setFontSize(10);
            pdf.setTextColor(0, 0, 0);
            pdf.text('Thank you for choosing FastFood Express!', centerX, footerY, null, null, 'center');
            pdf.setFont('helvetica', 'bold');
            pdf.setTextColor(211, 47, 47); // Red color
            pdf.text('We appreciate your business', centerX, footerY + 5, null, null, 'center');
            pdf.setFont('helvetica', 'normal');
            pdf.setTextColor(100, 100, 100);
            pdf.text(`FastFood Express © ${new Date().getFullYear()}`, centerX, footerY + 10, null, null, 'center');
            
            // Save the PDF
            pdf.save(`receipt_<?php echo $order_id; ?>.pdf`);
            
            // Show notification
            notification.classList.add('show');
            setTimeout(() => {
              notification.classList.remove('show');
            }, 3000);
            
            // Restore button state
            generateBtn.innerHTML = originalText;
            generateBtn.disabled = false;
          }, 1000);
        });
      }
      
      // Auto redirect if payment is processing
      <?php if (!$status && !$error): ?>
        setTimeout(() => {
          window.location.reload();
        }, 5000);
      <?php endif; ?>
    });
  </script>
</body>
</html>