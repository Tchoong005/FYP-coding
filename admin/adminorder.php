<?php
// Database connection
$conn = new mysqli('127.0.0.1', 'root', '', 'fyp_fastfood');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update order status if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: adminorder.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Admin Orders</title>
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

        .orders-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
           background-color: #dc4949;
           color: white;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        /* Status Styles */
        .status-pending {
            color: #ff9800;
            font-weight: 600;
        }

        .status-preparing {
            color: #2196f3;
            font-weight: 600;
        }

        .status-on_delivery {
            color: #4caf50;
            font-weight: 600;
        }

        .status-delivered {
            color: #9e9e9e;
            font-weight: 600;
        }

        .status-ready {
            color: #4caf50;
            font-weight: 600;
        }

        .status-completed {
            color: #9e9e9e;
            font-weight: 600;
        }

        .status-canceled {
            color: #f44336;
            font-weight: 600;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: #dc4949;
            color: white;
            font-weight: 500;
        }

        .action-btn:hover {
            background-color: #c14141;
        }

        .view-btn {
            background-color: #4CAF50;
        }

        .view-btn:hover {
            background-color: #3e8e41;
        }

        .status-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .status-select {
            padding: 6px;
            border-radius: 4px;
            border: 1px solid #ddd;
            min-width: 120px;
        }

        .disabled-select {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }

        .h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .status-indicator {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .delivery-method {
            text-transform: capitalize;
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
        <div class="orders-container">
            <h2>Order Management</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Delivery Method</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Payment Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 只查询 is_valid = 1 的有效订单
                    $sql = "SELECT * FROM orders WHERE is_valid = 1 ORDER BY created_at DESC";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            // Format status text for display
                            $status_display = ucwords(str_replace('_', ' ', $row['order_status']));
                            $status_class = 'status-' . $row['order_status'];
                            
                            // 检查订单是否处于最终状态（管理员不能操作取消状态）
                            $is_final = in_array($row['order_status'], ['completed', 'delivered', 'canceled']);
                            
                            echo "<tr>";
                            echo "<td>#" . $row['id'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['recipient_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['recipient_phone']) . "</td>";
                            echo "<td class='delivery-method'>" . ucwords(str_replace('_', ' ', $row['delivery_method'])) . "</td>";
                            echo "<td>RM" . number_format($row['final_total'], 2) . "</td>";
                            echo "<td><span class='$status_class status-indicator'>" . $status_display . "</span></td>";
                            echo "<td>" . ucwords(str_replace('_', ' ', $row['payment_status'])) . "</td>";
                            echo "<td>" . date('d M Y H:i', strtotime($row['created_at'])) . "</td>";
                            echo "<td class='action-btns'>";
                            echo "<div class='status-form'>";
                            
                            if (!$is_final) {
                                echo "<form method='post' action='adminorder.php'>";
                                echo "<input type='hidden' name='order_id' value='" . $row['id'] . "'>";
                                echo "<input type='hidden' name='update_status' value='1'>";
                                echo "<select name='status' class='status-select' onchange='this.form.submit()'>";
                                
                                // 根据配送方式显示不同的状态选项（移除了canceled选项）
                                if ($row['delivery_method'] == 'delivery') {
                                    $options = [
                                        'pending' => 'Pending',
                                        'preparing' => 'Preparing',
                                        'on_delivery' => 'On the Way',
                                        'delivered' => 'Delivered'
                                    ];
                                } else {
                                    $options = [
                                        'pending' => 'Pending',
                                        'preparing' => 'Preparing',
                                        'ready' => 'Ready',
                                        'completed' => 'Completed'
                                    ];
                                }
                                
                                foreach ($options as $value => $label) {
                                    $selected = $row['order_status'] == $value ? 'selected' : '';
                                    echo "<option value='$value' $selected>$label</option>";
                                }
                                
                                echo "</select>";
                                echo "</form>";
                            } else {
                                echo "<select class='status-select disabled-select' disabled>";
                                echo "<option>" . $status_display . "</option>";
                                echo "</select>";
                            }
                            
                            echo "<a href='order_details.php?order_id=" . $row['id'] . "' class='action-btn view-btn'>View</a>";
                            echo "</div>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9'>No orders found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
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