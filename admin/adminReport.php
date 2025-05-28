<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['staff_email'])) {
    header("Location: adminlogin.php");
    exit();
}

// Database connection
$db = new mysqli('127.0.0.1', 'root', '', 'fyp_fastfood');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'sales';

// Function to generate sales report
function getSalesReport($db, $start_date, $end_date) {
    $report = [];
    
    // Total sales
    $query = "SELECT SUM(total_price) as total_sales, COUNT(*) as total_orders 
              FROM orders 
              WHERE status = 'completed' 
              AND created_at BETWEEN ? AND ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $report['summary'] = $result->fetch_assoc();
    
    // Sales by day
    $query = "SELECT DATE(created_at) as day, SUM(total_price) as daily_sales, COUNT(*) as daily_orders 
              FROM orders 
              WHERE status = 'completed' 
              AND created_at BETWEEN ? AND ?
              GROUP BY DATE(created_at)
              ORDER BY DATE(created_at)";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $report['daily_sales'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // Top products
    $query = "SELECT p.name, SUM(oi.quantity) as total_quantity, SUM(oi.price * oi.quantity) as total_revenue
              FROM order_items oi
              JOIN products p ON oi.product_id = p.id
              JOIN orders o ON oi.order_id = o.id
              WHERE o.status = 'completed'
              AND o.created_at BETWEEN ? AND ?
              GROUP BY p.name
              ORDER BY total_revenue DESC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $report['top_products'] = $result->fetch_all(MYSQLI_ASSOC);
    
    return $report;
}

// Function to generate customer report
function getCustomerReport($db, $start_date, $end_date) {
    $report = [];
    
    // Top customers
    $query = "SELECT c.username, c.email, COUNT(o.id) as total_orders, SUM(o.total_price) as total_spent
              FROM orders o
              JOIN customers c ON o.user_id = c.id
              WHERE o.status = 'completed'
              AND o.created_at BETWEEN ? AND ?
              GROUP BY c.id
              ORDER BY total_spent DESC
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $report['top_customers'] = $result->fetch_all(MYSQLI_ASSOC);
    
    // New customers
    $query = "SELECT COUNT(*) as new_customers 
              FROM customers 
              WHERE created_at BETWEEN ? AND ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $report['new_customers'] = $result->fetch_assoc();
    
    return $report;
}

// Generate report based on type
if ($report_type == 'sales') {
    $report = getSalesReport($db, $start_date, $end_date);
} else {
    $report = getCustomerReport($db, $start_date, $end_date);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastFood Express - Reports</title>
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

        .report-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .report-title {
            font-size: 24px;
            color: #333;
        }

        .report-filters {
            display: flex;
            gap: 10px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            margin-bottom: 5px;
            font-size: 14px;
            color: #555;
        }

        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .filter-button {
            padding: 8px 16px;
            background: #dc4949;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            align-self: flex-end;
        }

        .filter-button:hover {
            background: #c43c3c;
        }

        .report-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .summary-card h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }

        .summary-card p {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .chart-container {
            height: 300px;
            margin-bottom: 30px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f2f2f2;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-primary {
            background-color: #dc4949;
            color: white;
        }

        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }

        .report-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }

        .report-tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }

        .report-tab.active {
            border-bottom: 2px solid #dc4949;
            color: #dc4949;
            font-weight: 600;
        }

        .report-tab:hover:not(.active) {
            background-color: #f5f5f5;
        }

        @media (max-width: 768px) {
            .main {
                width: 100%;
                left: 0;
            }
            
            .list {
                display: none;
            }
            
            .report-summary {
                grid-template-columns: 1fr;
            }
            
            .report-filters {
                flex-direction: column;
            }
        }
    </style>
    <!-- Chart.js for visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="adminReport.php">
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
        <div class="report-container">
            <div class="report-header">
                <h1 class="report-title">Sales Report</h1>
                <div class="report-filters">
                    <div class="filter-group">
                        <label for="start_date">From</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label for="end_date">To</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="filter-group">
                        <label for="report_type">Report Type</label>
                        <select id="report_type" name="report_type">
                            <option value="sales" <?php echo $report_type == 'sales' ? 'selected' : ''; ?>>Sales Report</option>
                            <option value="customer" <?php echo $report_type == 'customer' ? 'selected' : ''; ?>>Customer Report</option>
                        </select>
                    </div>
                    <button class="filter-button" onclick="applyFilters()">Apply</button>
                </div>
            </div>

            <div class="report-tabs">
                <div class="report-tab <?php echo $report_type == 'sales' ? 'active' : ''; ?>" onclick="changeReportType('sales')">Sales</div>
                <div class="report-tab <?php echo $report_type == 'customer' ? 'active' : ''; ?>" onclick="changeReportType('customer')">Customers</div>
            </div>

            <?php if ($report_type == 'sales'): ?>
                <div class="report-summary">
                    <div class="summary-card">
                        <h3>Total Sales</h3>
                        <p>RM <?php echo number_format($report['summary']['total_sales'] ?? 0, 2); ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Total Orders</h3>
                        <p><?php echo $report['summary']['total_orders'] ?? 0; ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Average Order Value</h3>
                        <p>RM <?php echo $report['summary']['total_orders'] > 0 ? number_format($report['summary']['total_sales'] / $report['summary']['total_orders'], 2) : '0.00'; ?></p>
                    </div>
                </div>

                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>

                <h2>Top Products</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity Sold</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['top_products'] as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo $product['total_quantity']; ?></td>
                                    <td>RM <?php echo number_format($product['total_revenue'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="report-summary">
                    <div class="summary-card">
                        <h3>Total Customers</h3>
                        <p><?php echo count($report['top_customers']); ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>New Customers</h3>
                        <p><?php echo $report['new_customers']['new_customers'] ?? 0; ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Repeat Customers</h3>
                        <p><?php echo count($report['top_customers']) - ($report['new_customers']['new_customers'] ?? 0); ?></p>
                    </div>
                </div>

                <h2>Top Customers</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Orders</th>
                                <th>Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['top_customers'] as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['username']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo $customer['total_orders']; ?></td>
                                    <td>RM <?php echo number_format($customer['total_spent'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // User dropdown functionality
        const dropdown = document.getElementById('userDropdown');
        dropdown.addEventListener('click', function (event) {
            event.stopPropagation();
            this.classList.toggle('show');
        });

        // Close dropdown if clicked outside
        window.addEventListener('click', function () {
            dropdown.classList.remove('show');
        });

        // Apply filters
        function applyFilters() {
            const start_date = document.getElementById('start_date').value;
            const end_date = document.getElementById('end_date').value;
            const report_type = document.getElementById('report_type').value;
            
            window.location.href = `adminReport.php?start_date=${start_date}&end_date=${end_date}&report_type=${report_type}`;
        }

        // Change report type
        function changeReportType(type) {
            const start_date = document.getElementById('start_date').value;
            const end_date = document.getElementById('end_date').value;
            
            window.location.href = `adminReport.php?start_date=${start_date}&end_date=${end_date}&report_type=${type}`;
        }

        // Initialize charts if sales report
        <?php if ($report_type == 'sales' && !empty($report['daily_sales'])): ?>
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: [<?php echo implode(',', array_map(function($day) { return "'" . $day['day'] . "'"; }, $report['daily_sales'])); ?>],
                    datasets: [{
                        label: 'Daily Sales (RM)',
                        data: [<?php echo implode(',', array_map(function($day) { return $day['daily_sales']; }, $report['daily_sales'])); ?>],
                        backgroundColor: 'rgba(220, 73, 73, 0.2)',
                        borderColor: 'rgba(220, 73, 73, 1)',
                        borderWidth: 2,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'RM ' + context.raw.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'RM ' + value;
                                }
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>