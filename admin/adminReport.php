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

// Handle month selection
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$year = date('Y', strtotime($selectedMonth));
$month = date('m', strtotime($selectedMonth));

// Get sales data for the selected month by day
$salesData = [];
$dates = [];
$daysInMonth = date('t', strtotime($selectedMonth));

for ($i = 1; $i <= $daysInMonth; $i++) {
    $date = date('Y-m-d', strtotime("$selectedMonth-$i"));
    $dates[] = date('j M', strtotime($date));
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(final_total), 0) as total 
                          FROM orders 
                          WHERE DATE(created_at) = ? 
                            AND is_valid = 1");
    $stmt->execute([$date]);
    $result = $stmt->fetch();
    $salesData[] = $result['total'];
}

// Get sales by category for the selected month
$categoryData = [];
$categoryLabels = [];
$stmt = $pdo->prepare("
    SELECT c.display_name, SUM(oi.price * oi.quantity) as total 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id  -- FIXED HERE
    JOIN orders o ON oi.order_id = o.id
    WHERE YEAR(o.created_at) = ? AND MONTH(o.created_at) = ? AND o.is_valid = 1
    GROUP BY c.display_name
");
$stmt->execute([$year, $month]);
while ($row = $stmt->fetch()) {
    $categoryLabels[] = $row['display_name'];
    $categoryData[] = $row['total'];
}

// Get recent orders for the table
$stmt = $pdo->prepare("
    SELECT o.id, o.recipient_name, o.final_total, o.order_status, o.created_at 
    FROM orders o 
    WHERE YEAR(o.created_at) = ? AND MONTH(o.created_at) = ? AND o.is_valid = 1
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$stmt->execute([$year, $month]);
$recentOrders = $stmt->fetchAll();

// Get total metrics for the selected month
$stmt = $pdo->prepare("SELECT COUNT(*) as order_count, COALESCE(SUM(final_total), 0) as total_revenue 
                      FROM orders 
                      WHERE YEAR(created_at) = ? 
                        AND MONTH(created_at) = ? 
                        AND is_valid = 1");
$stmt->execute([$year, $month]);
$totals = $stmt->fetch();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as customer_count 
                      FROM orders 
                      WHERE YEAR(created_at) = ? 
                        AND MONTH(created_at) = ? 
                        AND is_valid = 1");
$stmt->execute([$year, $month]);
$customerCount = $stmt->fetch();

// Handle PDF generation
if (isset($_GET['download_pdf'])) {

require_once(__DIR__ . '/../tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('FastFood Express');
    $pdf->SetAuthor('FastFood Express');
    $pdf->SetTitle('Sales Report - ' . date('F Y', strtotime($selectedMonth)));
    $pdf->SetSubject('Sales Report');
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'FastFood Express - Sales Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, date('F Y', strtotime($selectedMonth)), 0, 1, 'C');
    
    // Add summary cards
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(90, 10, 'Total Orders:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $totals['order_count'] ?? 0, 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(90, 10, 'Total Revenue:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'RM ' . number_format($totals['total_revenue'] ?? 0, 2), 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(90, 10, 'Active Customers:', 0, 0);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, $customerCount['customer_count'] ?? 0, 0, 1);
    
    // Add recent orders table
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Recent Orders', 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(30, 7, 'Order ID', 1, 0, 'C', 1);
    $pdf->Cell(50, 7, 'Customer', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Date', 1, 0, 'C', 1);
    $pdf->Cell(30, 7, 'Amount', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Status', 1, 1, 'C', 1);
    
    $pdf->SetFont('helvetica', '', 10);
    foreach ($recentOrders as $order) {
        $pdf->Cell(30, 7, '#' . $order['id'], 1, 0, 'C');
        $pdf->Cell(50, 7, $order['recipient_name'], 1, 0, 'L');
        $pdf->Cell(40, 7, date('Y-m-d', strtotime($order['created_at'])), 1, 0, 'C');
        $pdf->Cell(30, 7, 'RM ' . number_format($order['final_total'], 2), 1, 0, 'R');
        $pdf->Cell(40, 7, ucfirst($order['order_status']), 1, 1, 'C');
    }
    
    // Output PDF
    $pdf->Output('sales_report_' . $selectedMonth . '.pdf', 'D');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - FastFood Express</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .main {
            position: absolute;
            top: 60px;
            left: 260px;
            width: calc(100% - 260px);
            min-height: calc(100vh - 60px);
            padding: 20px;
            background: #f5f5f5;
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .report-header h1 {
            color: #dc4949;
        }

        .report-period {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .report-period select {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #ddd;
            background: white;
        }

        .report-period .download-btn {
            padding: 8px 15px;
            background: #dc4949;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .report-period .download-btn:hover {
            background: #c44141;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .card h3 {
            color: #555;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .card h2 {
            color: #dc4949;
            font-size: 28px;
        }

        .card i {
            font-size: 40px;
            color: #dc4949;
            margin-bottom: 15px;
        }

        .charts {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .chart-container h2 {
            color: #555;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .recent-orders {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .recent-orders h2 {
            color: #555;
            margin-bottom: 20px;
            font-size: 18px;
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
            background-color: #f8f8f8;
            color: #555;
        }

        tr:hover {
            background-color: #f5f5f5;
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
        <div class="report-header">
            <h1>Sales Report</h1>
            <div class="report-period">
                <form method="get" action="">
                    <select name="month" onchange="this.form.submit()">
                        <?php
                        // Generate options for 2025 months
                        for ($m = 1; $m <= 12; $m++) {
                            $monthValue = "2025-" . str_pad($m, 2, '0', STR_PAD_LEFT);
                            $monthName = date('F Y', strtotime($monthValue));
                            $selected = ($monthValue == $selectedMonth) ? 'selected' : '';
                            echo "<option value='$monthValue' $selected>$monthName</option>";
                        }
                        ?>
                    </select>
                </form>
                <a href="?month=<?= $selectedMonth ?>&download_pdf=1" class="download-btn">
                    <i class="fas fa-download"></i> Download PDF
                </a>
            </div>
        </div>
        
        <div class="cards">
            <div class="card">
                <i class="fas fa-shopping-cart"></i>
                <h3>Total Orders</h3>
                <h2><?= $totals['order_count'] ?? 0 ?></h2>
            </div>
            <div class="card">
                <i class="fas fa-money-bill-wave"></i>
                <h3>Total Revenue</h3>
                <h2>RM <?= number_format($totals['total_revenue'] ?? 0, 2) ?></h2>
            </div>
            <div class="card">
                <i class="fas fa-users"></i>
                <h3>Active Customers</h3>
                <h2><?= $customerCount['customer_count'] ?? 0 ?></h2>
            </div>
        </div>
        
        <div class="charts">
            <div class="chart-container">
                <h2>Sales Overview - <?= date('F Y', strtotime($selectedMonth)) ?></h2>
                <canvas id="salesChart"></canvas>
            </div>
           
        </div>
        
        <div class="recent-orders">
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
    </div>

    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(range(1, $daysInMonth)) ?>,
                datasets: [{
                    label: 'Daily Sales (RM)',
                    data: <?= json_encode($salesData) ?>,
                    backgroundColor: 'rgba(220, 73, 73, 0.2)',
                    borderColor: 'rgba(220, 73, 73, 1)',
                    borderWidth: 2,
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });



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