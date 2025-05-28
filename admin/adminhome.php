<?php
$host = '127.0.0.1';
$db   = 'fyp_fastfood';
$user = 'root'; // Change to your MySQL username
$pass = '';     // Change to your MySQL password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Get today's orders count and revenue
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(final_total) as revenue FROM orders WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$todayData = $stmt->fetch();

// Get total products count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE deleted_at IS NULL");
$productsData = $stmt->fetch();

// Get active customers count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM customers WHERE is_banned = 0");
$customersData = $stmt->fetch();

// Get recent orders
$stmt = $pdo->query("SELECT o.id, o.recipient_name, o.final_total, o.order_status, o.created_at 
                     FROM orders o 
                     ORDER BY o.created_at DESC 
                     LIMIT 5");
$recentOrders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FastFood Express</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
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
            width: calc(100%-260px);
            left: 260px;
            min-height: calc(100%-60px);

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
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        .main {
            position: absolute;
            top: 60px;
            left: 260px;
            width: calc(100% - 260px);
            min-height: calc(100vh - 60px);
            padding: 30px;
            background: #f5f5f5;
        }

        .welcome {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .welcome h1 {
            color: #dc4949;
            margin-bottom: 10px;
        }

        .welcome p {
            color: #666;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(220, 73, 73, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .card-icon i {
            font-size: 24px;
            color: #dc4949;
        }

        .card-info h3 {
            color: #555;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .card-info h2 {
            color: #333;
            font-size: 24px;
        }

        .panels {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .panel {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .panel h2 {
            color: #555;
            margin-bottom: 20px;
            font-size: 18px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .recent-orders table {
            width: 100%;
            border-collapse: collapse;
        }

        .recent-orders th, .recent-orders td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .recent-orders th {
            color: #555;
            font-weight: 600;
        }

        .status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status.completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status.pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .quick-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .quick-link {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s;
            text-decoration: none;
        }

        .quick-link:hover {
            background: rgba(220, 73, 73, 0.1);
            transform: translateY(-3px);
        }

        .quick-link i {
            font-size: 24px;
            color: #dc4949;
            margin-bottom: 10px;
        }

        .quick-link h3 {
            color: #333;
            font-size: 14px;
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
        <div class="welcome">
            <h1>Welcome back, Admin!</h1>
            <p>Here's what's happening with your restaurant today.</p>
        </div>
        
        <div class="cards">
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="card-info">
                    <h3>TODAY'S ORDERS</h3>
                    <h2><?= $todayData['count'] ?? 0 ?></h2>
                </div>
            </div>
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="card-info">
                    <h3>TODAY'S REVENUE</h3>
                    <h2>RM <?= number_format($todayData['revenue'] ?? 0, 2) ?></h2>
                </div>
            </div>
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="card-info">
                    <h3>TOTAL PRODUCTS</h3>
                    <h2><?= $productsData['count'] ?? 0 ?></h2>
                </div>
            </div>
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="card-info">
                    <h3>ACTIVE CUSTOMERS</h3>
                    <h2><?= $customersData['count'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        
        <div class="panels">
            <div class="panel recent-orders">
                <h2>Recent Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><?= htmlspecialchars($order['recipient_name']) ?></td>
                            <td><?= date('Y-m-d', strtotime($order['created_at'])) ?></td>
                            <td>RM <?= number_format($order['final_total'], 2) ?></td>
                            <td>
                                <span class="status <?= $order['order_status'] ?>">
                                    <?= ucfirst($order['order_status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="panel quick-actions">
                <h2>Quick Actions</h2>
                <div class="quick-links">
                    <a href="adminProduct.php" class="quick-link">
                        <i class="fas fa-plus-circle"></i>
                        <h3>Add New Product</h3>
                    </a>
                    <a href="adminStaff.php" class="quick-link">
                        <i class="fas fa-user-plus"></i>
                        <h3>Add Staff Member</h3>
                    </a>
                    <a href="adminCategories.php" class="quick-link">
                        <i class="fas fa-tag"></i>
                        <h3>Manage Categories</h3>
                    </a>
                    <a href="adminReport.php" class="quick-link">
                        <i class="fas fa-chart-pie"></i>
                        <h3>View Reports</h3>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


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