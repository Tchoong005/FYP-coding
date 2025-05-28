<?php
// Database connection
$conn = new mysqli('127.0.0.1', 'root', '', 'fyp_fastfood');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Fetch order details
$order_sql = "SELECT * FROM orders WHERE id = $order_id";
$order_result = $conn->query($order_sql);
$order = $order_result->fetch_assoc();

// Fetch order items
$items_sql = "SELECT oi.*, p.name as product_name 
              FROM order_items oi 
              LEFT JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = $order_id";
$items_result = $conn->query($items_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Order Details</title>
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'poppins', sans-serif;
        }

        .user {
            position: relative;
            width: 50px;
            height: 50px;
        }

        .user img {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            object-fit: cover;
        }

        .topbar {
            position: fixed;
            background: white;
            box-shadow: 0px 4px 8px 0 rgba(0, 0, 0, 0.08);
            width: 100%;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 2fr 10fr 0.4fr 1fr;
            align-items: center;
            z-index: 1;
        }

        .logo h2 {
            color: red;
        }

        .search {
            position: relative;
            width: 60%;
            justify-self: center;
        }

        .search input {
            width: 100%;
            height: 40px;
            padding: 0 40px;
            font-size: 16px;
            outline: none;
            border: none;
            border-radius: 10px;
            background: #f5f5f5;
        }

        .search i {
            position: absolute;
            right: 30px;
            height: 15px;
            top: 15px;
            cursor: pointer;
        }

        .list {
            position: fixed;
            top: 60px;
            width: 260px;
            height: 100%;
            background: rgba(220, 73, 73, 0.897);
            overflow-x: hidden;
        }

        .list ul {
            margin-top: 20px;
        }

        .list ul li {
            width: 100%;
            list-style: none;
        }

        .list ul li a {
            width: 100%;
            text-decoration: none;
            color: #fff;
            height: 60px;
            display: flex;
            align-items: center;
        }

        .list ul li a i {
            min-width: 60px;
            font-size: 24px;
            text-align: center;
        }

        .list ul li:hover {
            background: rgb(227, 125, 125);
        }

        .main {
            position: absolute;
            top: 60px;
            width: calc(100% - 260px);
            left: 260px;
            min-height: calc(100vh - 60px);
            padding: 20px;
            background: #f5f5f5;
        }

        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .user-dropdown img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 120px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            z-index: 1;
            border-radius: 6px;
        }

        .dropdown-content a {
            color: black;
            padding: 10px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .user-dropdown.show .dropdown-content {
            display: block;
        }

        .order-details-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .order-info, .customer-info {
            flex: 1;
        }

        .order-info h3, .customer-info h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .order-info p, .customer-info p {
            margin: 5px 0;
            color: #666;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: 500;
            margin-top: 10px;
        }

        .status-pending {
            background-color: #fff3e0;
            color: #ff9800;
        }

        .status-preparing {
            background-color: #e3f2fd;
            color: #2196f3;
        }

        .status-delivery {
            background-color: #e8f5e9;
            color: #4caf50;
        }

        .status-completed {
            background-color: #f5f5f5;
            color: #9e9e9e;
        }

        .order-items {
            margin-top: 20px;
        }

        .order-items table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-items th, .order-items td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .order-items th {
            background-color: #f8f8f8;
            font-weight: 600;
        }

        .order-summary {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
        }

        .summary-label {
            font-weight: 500;
        }

        .summary-value {
            font-weight: 600;
        }

        .total-row {
            font-size: 18px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #dc4949;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        }

        .back-btn:hover {
            background-color: #c14141;
        }

        .special-instructions {
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 4px;
            border-left: 4px solid #dc4949;
        }

        .special-instructions h4 {
            margin-bottom: 10px;
            color: #333;
        }

        .no-items {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="top">
        <div class="topbar">
            <div class="logo">
                <h2>FastFood Express</h2>
            </div>
            <div class="search">
                
            </div>
            
            <div class="user-dropdown" id="userDropdown">
                <img src="img/72-729716_user-avatar-png-graphic-free-download-icon.png" alt="User Avatar">
                <div class="dropdown-content">
                    <a href="profile.php">Edit profile</a>
                    <a href="adminlogout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="list">
        <ul>
            <li>
                <a href="adminhome.php">
                    <i class="fas fa-home"></i>
                    <h4>DASHBOARD</h4>
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="adminorder.php">
                    <i class="fas fa-receipt"></i>
                    <h4>ORDERS</h4>
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="adminProduct.php">
                    <i class="fas fa-box-open"></i>
                    <h4>PRODUCTS</h4>
                </a>
            </li>
        </ul>
        <ul>
        <li>
            <a href="adminCategories.php">
                <i class="fas fa-tags"></i>
                <h4>CATEGORIES</h4>
            </a>
        </li>
        </ul>
        <ul>
            <li>
                <a href="adminStaff.php">
                    <i class="fas fa-user-tie"></i>
                    <h4>STAFFS</h4>
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="adminCustomer.php">
                    <i class="fas fa-users"></i>
                    <h4>CUSTOMER</h4>
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="adminReport.php">
                    <i class="fas fa-chart-line"></i>
                    <h4>REPORT</h4>
                </a>
            </li>
        </ul>
    </div>

    <div class="main">
        <div class="order-details-container">
            <?php if ($order): ?>
                <div class="order-header">
                    <div class="order-info">
                        <h3>Order #<?php echo $order['id']; ?></h3>
                        <p>Date: <?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></p>
                        <span class="status-badge status-<?php echo strtolower($order['order_status']); ?>">
                            <?php echo ucfirst($order['order_status']); ?>
                        </span>
                    </div>
                    <div class="customer-info">
                        <h3>Customer Information</h3>
                        <p>Name: <?php echo htmlspecialchars($order['recipient_name']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($order['recipient_phone']); ?></p>
                        <p>Address: <?php echo htmlspecialchars($order['recipient_address']); ?></p>
                        <p>Delivery Method: <?php echo ucfirst(str_replace('_', ' ', $order['delivery_method'])); ?></p>
                        <p>Payment Method: <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></p>
                        <p>Payment Status: <?php echo ucfirst(str_replace('_', ' ', $order['payment_status'])); ?></p>
                    </div>
                </div>

                <div class="order-items">
                    <h3>Order Items</h3>
                    <?php if ($items_result->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Sauce</th>
                                    <th>Comment</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal = 0;
                                while($item = $items_result->fetch_assoc()): 
                                    $item_total = $item['price'] * $item['quantity'];
                                    $subtotal += $item_total;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo htmlspecialchars($item['sauce'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($item['comment'] ?: '-'); ?></td>
                                        <td>RM<?php echo number_format($item['price'], 2); ?></td>
                                        <td>RM<?php echo number_format($item_total, 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-items">No items found for this order</div>
                    <?php endif; ?>
                </div>

                <div class="order-summary">
                    <h3>Order Summary</h3>
                    
                    <div class="summary-row">
                        <span class="summary-label">Delivery Fee:</span>
                        <span class="summary-value">RM<?php echo number_format($order['delivery_fee'], 2); ?></span>
                    </div>
                    <div class="summary-row total-row">
                        <span class="summary-label">Total:</span>
                        <span class="summary-value">RM<?php echo number_format($order['final_total'], 2); ?></span>
                    </div>
                </div>

                <a href="adminorder.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            <?php else: ?>
                <div class="no-order">
                    <h3>Order not found</h3>
                    <p>The requested order could not be found.</p>
                    <a href="adminorder.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const dropdown = document.getElementById('userDropdown');
        dropdown.addEventListener('click', function (event) {
          event.stopPropagation();
          this.classList.toggle('show');
        });
      
        // Close dropdown if clicked outside
        window.addEventListener('click', function () {
          dropdown.classList.remove('show');
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>