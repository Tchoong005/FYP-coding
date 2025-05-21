<?php
session_start();
require_once 'db_connection.php';


// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: newadminlogin.php");
    exit();
}


if (!isset($_GET['order_id'])) {
    header("Location: adminorder.php");
    exit();
}


$order_id = intval($_GET['order_id']);

// Get order details
$order_query = "SELECT o.*, c.username, c.email, c.phone 
               FROM orders o 
               LEFT JOIN customers c ON o.user_id = c.id 
               WHERE o.id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    header("Location: adminorder.php");
    exit();
}

// Get order items
$items_query = "SELECT oi.*, p.name, p.image_url 
               FROM order_items oi 
               LEFT JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
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

        /* Order details styles */
        .order-details-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .order-info {
            display: flex;
            gap: 30px;
            margin-bottom: 20px;
        }

        .info-section {
            flex: 1;
        }

        .info-section h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .info-section p {
            margin: 5px 0;
            color: #555;
        }

        .status-pending {
            color: #ff9800;
            font-weight: bold;
        }

        .status-delivery {
            color: #2196f3;
            font-weight: bold;
        }

        .status-complete {
            color: #4caf50;
            font-weight: bold;
        }

        .status-cancelled {
            color: #f44336;
            font-weight: bold;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .items-table th, .items-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .items-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .item-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            margin-right: 10px;
            vertical-align: middle;
        }

        .back-btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #2196f3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }

        .back-btn:hover {
            background-color: #0b7dda;
        }

        .special-requests {
            font-style: italic;
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
                <input type="text" id="search" placeholder="search here">
                <label for="search"><i class="fas fa-search"></i></label>
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
                <a href="adminhome.html">
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
                <a href="adminReport.html">
                    <i class="fas fa-chart-line"></i>
                    <h4>REPORT</h4>
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="adminAboutUs.html">
                    <i class="fas fa-info-circle"></i>
                    <h4>ABOUT US</h4>
                </a>
            </li>
        </ul>
    </div>

    <div class="main">
        <div class="order-details-container">
            <div class="order-header">
                <h2>Order #<?php echo $order['id']; ?></h2>
                <span class="status-<?php echo strtolower($order['status']); ?>">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>

            <div class="order-info">
                <div class="info-section">
                    <h3>Customer Information</h3>
                    <p><strong>Name:</strong> <?php echo $order['username'] ?? 'Guest'; ?></p>
                    <p><strong>Email:</strong> <?php echo $order['email'] ?? 'N/A'; ?></p>
                    <p><strong>Phone:</strong> <?php echo $order['phone'] ?? 'N/A'; ?></p>
                </div>

                <div class="info-section">
                    <h3>Order Information</h3>
                    <p><strong>Order Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                    <p><strong>Total Amount:</strong> RM <?php echo number_format($order['total_price'], 2); ?></p>
                </div>
            </div>

            <?php if ($order['recipient_name'] || $order['address']): ?>
                <div class="info-section" style="margin-bottom: 20px;">
                    <h3>Delivery Information</h3>
                    <p><strong>Recipient:</strong> <?php echo $order['recipient_name'] ?? 'N/A'; ?></p>
                    <p><strong>Phone:</strong> <?php echo $order['phone'] ?? 'N/A'; ?></p>
                    <p><strong>Address:</strong> <?php echo $order['address'] ?? 'N/A'; ?></p>
                </div>
            <?php endif; ?>

            <h3>Order Items</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = 0;
                    while ($item = $items_result->fetch_assoc()): 
                        $subtotal = $item['price'] * $item['quantity'];
                        $total += $subtotal;
                    ?>
                        <tr>
                            <td>
                                <?php if ($item['image_url']): ?>
                                    <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>" class="item-img">
                                <?php endif; ?>
                                <?php echo $item['name']; ?>
                                <?php if ($item['sauce']): ?>
                                    <div class="special-requests">Sauce: <?php echo $item['sauce']; ?></div>
                                <?php endif; ?>
                                <?php if ($item['comment']): ?>
                                    <div class="special-requests">Note: <?php echo $item['comment']; ?></div>
                                <?php endif; ?>
                            </td>
                            <td>RM <?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>RM <?php echo number_format($subtotal, 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                        <td style="font-weight: bold;">RM <?php echo number_format($total, 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <a href="adminorder.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
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