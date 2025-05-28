<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_type = isset($_GET['type']) ? $_GET['type'] : 'delivery'; // Default to delivery

// Fetch active orders for the current user
$sql = "SELECT * FROM orders 
        WHERE user_id = $user_id 
        AND order_status NOT IN ('delivered', 'completed', 'cancelled')
        AND delivery_method = '$order_type'
        ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

$orders = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
}

// Calculate cart count for topbar
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
    <title>Order Trace - FastFood Express</title>
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }
        
        .order-toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            max-width: 500px;
            margin: 0 auto 40px;
        }
        
        .toggle-btn {
            flex: 1;
            padding: 16px 20px;
            border: none;
            background: white;
            color: var(--text-light);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .toggle-btn:first-child {
            border-right: 1px solid var(--border);
        }
        
        .toggle-btn.active {
            background: var(--primary);
            color: white;
        }
        
        .toggle-btn i {
            font-size: 1.2rem;
        }
        
        .no-orders {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }
        
        .no-orders i {
            font-size: 5rem;
            color: var(--primary);
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .no-orders h3 {
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .no-orders p {
            color: var(--text-light);
            font-size: 1.1rem;
            margin-bottom: 25px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .btn {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 14px 32px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            font-size: 1.1rem;
            box-shadow: 0 4px 12px rgba(214,0,28,0.2);
        }
        
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(214,0,28,0.3);
        }
        
        .orders-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
            gap: 30px;
        }
        
        @media (max-width: 600px) {
            .orders-container {
                grid-template-columns: 1fr;
            }
        }
        
        .order-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .order-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }
        
        .order-header {
            background: var(--light-bg);
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
        }
        
        .order-id {
            font-weight: bold;
            color: var(--text);
            font-size: 1.2rem;
        }
        
        .order-id span {
            color: var(--primary);
        }
        
        .order-date {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .order-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .status-pending {
            background-color: var(--warning);
            color: white;
        }
        
        .status-preparing {
            background-color: #2196f3;
            color: white;
        }
        
        .status-delivery {
            background-color: var(--success);
            color: white;
        }
        
        .order-details {
            padding: 25px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        @media (max-width: 600px) {
            .order-details {
                grid-template-columns: 1fr;
            }
        }
        
        .detail-section h4 {
            margin-top: 0;
            color: var(--primary);
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .detail-section h4 i {
            font-size: 1.2rem;
        }
        
        .detail-content {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 18px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .detail-label {
            color: var(--text-light);
            font-size: 0.95rem;
        }
        
        .detail-value {
            font-weight: 500;
            text-align: right;
        }
        
        .total-row {
            font-weight: bold;
            font-size: 1.1rem;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 2px dashed var(--border);
        }
        
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
        
        .actions {
            padding: 20px;
            background: var(--light-bg);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }
        
        .action-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .action-cancel {
            background: #ffebee;
            color: var(--danger);
        }
        
        .action-cancel:hover {
            background: #ffcdd2;
        }
        
        .action-support {
            background: #e3f2fd;
            color: #2196f3;
        }
        
        .action-support:hover {
            background: #bbdefb;
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
            
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-section h2 {
                font-size: 2.2rem;
            }
            
            .header-section p {
                font-size: 1rem;
            }
            
            .orders-container {
                grid-template-columns: 1fr;
            }
            
            .toggle-btn {
                padding: 14px 15px;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .topbar .logo {
                font-size: 20px;
            }
            
            .step {
                flex: 0 0 50%;
                margin-bottom: 20px;
            }
            
            .tracker-steps {
                flex-wrap: wrap;
            }
            
            .order-details {
                padding: 20px;
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
                <a href="products_user.php">Products</a>
                <a href="order_trace.php" class="active-link">Order Trace</a>
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
    <h2><i class="fas fa-map-marker-alt"></i> Track Your Orders</h2>
    <p>Real-time updates on your food preparation and delivery status</p>
</div>

<div class="container">
    <!-- Order Type Toggle -->
    <div class="order-toggle">
        <button class="toggle-btn <?php echo $order_type == 'delivery' ? 'active' : ''; ?>" data-type="delivery">
            <i class="fas fa-truck"></i> Delivery Orders
        </button>
        <button class="toggle-btn <?php echo $order_type == 'dine_in' ? 'active' : ''; ?>" data-type="dine_in">
            <i class="fas fa-store"></i> Dine-In Orders
        </button>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="no-orders" data-aos="fade-up">
            <i class="fas fa-clipboard-list"></i>
            <h3>No Active <?php echo $order_type == 'delivery' ? 'Delivery' : 'Dine-In'; ?> Orders</h3>
            <p>You don't have any <?php echo $order_type == 'delivery' ? 'delivery' : 'dine-in'; ?> orders in progress right now. Place a new order to see it tracked here!</p>
            <a href="products_user.php" class="btn"><i class="fas fa-utensils"></i> Order Food Now</a>
        </div>
    <?php else: ?>
        <div class="orders-container">
            <?php foreach ($orders as $order): 
                // Determine progress based on order status
                $progress = 0;
                $steps = [];
                
                if ($order['delivery_method'] == 'delivery') {
                    $steps = [
                        ['status' => 'pending', 'label' => 'Order Placed', 'icon' => '📝'],
                        ['status' => 'preparing', 'label' => 'Preparing', 'icon' => '👨‍🍳'],
                        ['status' => 'on_delivery', 'label' => 'On the Way', 'icon' => '🚚'],
                        ['status' => 'delivered', 'label' => 'Delivered', 'icon' => '✅']
                    ];
                } else {
                    $steps = [
                        ['status' => 'pending', 'label' => 'Order Placed', 'icon' => '📝'],
                        ['status' => 'preparing', 'label' => 'Preparing', 'icon' => '👨‍🍳'],
                        ['status' => 'ready', 'label' => 'Ready for Pickup', 'icon' => '🛎️'],
                        ['status' => 'completed', 'label' => 'Completed', 'icon' => '✅']
                    ];
                }
                
                $current_step_index = 0;
                foreach ($steps as $index => $step) {
                    if ($step['status'] == $order['order_status']) {
                        $current_step_index = $index;
                        $progress = ($index + 1) * 25;
                        break;
                    }
                }
            ?>
                <div class="order-card" data-aos="fade-up">
                    <div class="order-header">
                        <div>
                            <div class="order-id">Order #<span><?php echo $order['id']; ?></span></div>
                            <div class="order-date">Ordered on <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="order-status status-<?php echo $order['order_status']; ?>">
                            <?php 
                                $statusText = ucfirst(str_replace('_', ' ', $order['order_status']));
                                echo $statusText; 
                            ?>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="detail-section">
                            <h4><i class="fas fa-info-circle"></i> Order Information</h4>
                            <div class="detail-content">
                                <div class="detail-row">
                                    <span class="detail-label">Recipient:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['recipient_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['recipient_phone']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Method:</span>
                                    <span class="detail-value">
                                        <?php 
                                            if ($order['delivery_method'] == 'delivery') {
                                                echo '<i class="fas fa-truck"></i> Delivery';
                                            } else {
                                                echo '<i class="fas fa-store"></i> Dine In';
                                            }
                                        ?>
                                    </span>
                                </div>
                                <?php if ($order['delivery_method'] == 'delivery'): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Address:</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['recipient_address']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h4><i class="fas fa-receipt"></i> Payment Details</h4>
                            <div class="detail-content">
                                <div class="detail-row">
                                    <span class="detail-label">Payment Method:</span>
                                    <span class="detail-value">
                                        <?php 
                                            if ($order['payment_method'] == 'credit_card') {
                                                echo '<i class="fas fa-credit-card"></i> Credit Card';
                                            } elseif ($order['payment_method'] == 'cash') {
                                                echo '<i class="fas fa-money-bill"></i> Cash';
                                            } else {
                                                echo '<i class="fas fa-cash-register"></i> Counter';
                                            }
                                        ?>
                                    </span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Payment Status:</span>
                                    <span class="detail-value"><?php echo ucfirst($order['payment_status']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Subtotal:</span>
                                    <span class="detail-value">RM <?php echo number_format($order['total_price'], 2); ?></span>
                                </div>
                                <?php if ($order['delivery_method'] == 'delivery'): ?>
                                <div class="detail-row">
                                    <span class="detail-label">Delivery Fee:</span>
                                    <span class="detail-value">RM <?php echo number_format($order['delivery_fee'], 2); ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="detail-row total-row">
                                    <span class="detail-label">Total:</span>
                                    <span class="detail-value">RM <?php echo number_format($order['final_total'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
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
                    
                    <div class="actions">
                        <button class="action-btn action-cancel">
                            <i class="fas fa-times-circle"></i> Cancel Order
                        </button>
                        <button class="action-btn action-support">
                            <i class="fas fa-headset"></i> Contact Support
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

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
        
        // Order type toggle functionality
        document.querySelectorAll('.toggle-btn').forEach(button => {
            button.addEventListener('click', function() {
                const type = this.getAttribute('data-type');
                window.location.href = `order_trace.php?type=${type}`;
            });
        });
        
        // Add functionality to action buttons
        document.querySelectorAll('.action-cancel').forEach(button => {
            button.addEventListener('click', function() {
                const orderCard = this.closest('.order-card');
                const orderId = orderCard.querySelector('.order-id span').textContent;
                if (confirm(`Are you sure you want to cancel order #${orderId}?`)) {
                    alert(`Cancellation request for order #${orderId} sent. Our team will contact you shortly.`);
                }
            });
        });
        
        document.querySelectorAll('.action-support').forEach(button => {
            button.addEventListener('click', function() {
                const orderCard = this.closest('.order-card');
                const orderId = orderCard.querySelector('.order-id span').textContent;
                alert(`Support request for order #${orderId} submitted. We'll contact you shortly.`);
            });
        });
    });
</script>
</body>
</html>