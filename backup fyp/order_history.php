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

// Fetch order history
$sql = "SELECT * FROM orders 
        WHERE user_id = $user_id 
        AND (order_status = 'completed' OR payment_status = 'canceled')
        ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

$orders = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
} else {
    die("Query failed: " . mysqli_error($conn));
}

// Fetch order items for each order
foreach ($orders as &$order) {
    $order_id = $order['id'];
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
    $order['items'] = $order_items;
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
        
        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin: 20px 0;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
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
        }
        
        .status-completed {
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
        
        .order-actions a {
            display: inline-block;
            padding: 8px 16px;
            background: white;
            border: 1px solid var(--primary);
            color: var(--primary);
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            margin-left: 10px;
            transition: all 0.3s;
        }
        
        .order-actions a:hover {
            background: var(--primary);
            color: white;
        }
        
        .payment-status {
            font-weight: bold;
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
            
            .order-actions {
                margin-top: 15px;
                width: 100%;
            }
            
            .order-actions a {
                display: block;
                text-align: center;
                margin-left: 0;
                margin-top: 10px;
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
    <p>Review your completed and canceled orders with FastFood Express</p>
</div>

<div class="container">
    <?php if (empty($orders)): ?>
        <div class="no-orders" data-aos="fade-up">
            <i class="fas fa-box-open"></i>
            <h3>No Order History Found</h3>
            <p>You haven't completed any orders yet. Browse our menu and place your first order!</p>
            <a href="products_user.php" class="btn">Browse Menu</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): 
            $status_class = ($order['order_status'] == 'completed') ? 'status-completed' : 'status-canceled';
            $payment_class = 'status-' . str_replace(' ', '-', strtolower($order['payment_status']));
        ?>
        <div class="order-card" data-aos="fade-up">
            <div class="order-header">
                <div class="order-header-left">
                    <h3>Order #<?php echo $order['id']; ?></h3>
                    <p><i class="far fa-calendar"></i> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                </div>
                <div class="order-status <?php echo $status_class; ?>">
                    <?php echo ucfirst($order['order_status']); ?>
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
                <div class="payment-status <?php echo $payment_class; ?>">
                    <i class="fas fa-credit-card"></i> Payment Status: 
                    <span><?php echo ucfirst($order['payment_status']); ?></span>
                </div>
                <div class="order-actions">
                    <a href="#"><i class="fas fa-redo"></i> Reorder</a>
                    <a href="#"><i class="fas fa-question-circle"></i> Get Help</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
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
</script>
</body>
</html>