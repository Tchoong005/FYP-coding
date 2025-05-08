<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Admin Home</title>
    <style>
        *{
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'poppins',sans-serif;
        }

        .user{
            position: relative;
            width: 50px;
            height: 50px;
        }

        .user img{
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            object-fit: cover;
        }

        .topbar{
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

        .logo h2{
            color: red;
        }

        .search{
            position: relative;
            width: 60%;
            justify-self: center;
        }

        .search input{
            width: 100%;
            height: 40px;
            padding: 0 40px;
            font-size: 16px;
            outline: none;
            border: none;
            border-radius: 10px;
            background: #f5f5f5;
        }

        .search i{
            position: absolute;
            right: 30px;
            height: 15px;
            top: 15px;
            cursor: pointer;
        }

        .list{
            position: fixed;
            top: 60px;
            width: 260px;
            height: 100%;
            background: rgba(220, 73, 73, 0.897);
            overflow-x: hidden;
        }
        .list ul{
            margin-top: 20px;
        }

        .list ul li{
            width: 100%;
            list-style: none;
        }
        .list ul li a{
            width: 100%;
            text-decoration: none;
            color: #fff;
            height: 60px;
            display: flex;
            align-items: center;
        }
        .list ul li a i{
            min-width: 60px;
            font-size: 24px;
            text-align: center;
        }
        .list ul li:hover{
           background:rgb(227, 125, 125);
        }

        .main {
            margin-left: 280px;
            margin-top: 80px;
            padding: 20px;
            font-size: 16px;
            position: absolute;
            background-color: #ffffff17;
            padding: 12px;
            border-radius: 40px;
            box-shadow: 0 4px 12px  rgba(0, 0, 0, 0.08);
            max-width: 1000px;
        }

        table{
            border-collapse: separate;
            border-spacing: 0 10px;
            width: 100%;
            font-size: 16px;
        }

        table th, table td{
            padding: 12px 20px;
            text-align: left;
        }

        thead th{
            background-color: #f5f5f5;
            border-bottom: 2px solid #b3a8a8;
        }

        tbody tr {
            background-color: #f3f2eec7;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        tbody td {
            border-bottom: 1px solid #eee;
        }

        .ban-btn {
            padding: 6px 12px;
            border: none;
            background-color: #dc4949;
            color: white;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .ban-btn:hover {
            background-color: #c53737;
        }

        .unban-btn {
            padding: 6px 12px;
            border: none;
            background-color: #4CAF50;
            color: white;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .unban-btn:hover {
            background-color: #3e8e41;
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
        
        .status-banned {
            color: #dc4949;
            font-weight: bold;
        }
        
        .status-active {
            color: #4CAF50;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="top">
        <div class="topbar">
            <div class="logo">
                <h2>KFG FOOD</h2>
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
                <a href="adminorder.html">
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
                    <h4>ABOURT US</h4>
                </a>
            </li>
        </ul>
    </div>

    <div class="main">
        <h2>Customer Management</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Database connection
                $conn = new mysqli('127.0.0.1', 'root', '', 'fyp_fastfood');
                
                // Check connection
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }
                
                // Handle ban/unban actions
                if (isset($_POST['action']) && isset($_POST['user_id'])) {
                    $user_id = $_POST['user_id'];
                    $action = $_POST['action'];
                    
                    if ($action === 'ban') {
                        $stmt = $conn->prepare("UPDATE customers SET is_banned = 1 WHERE id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                    } elseif ($action === 'unban') {
                        $stmt = $conn->prepare("UPDATE customers SET is_banned = 0 WHERE id = ?");
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                    }
                }
                
                // First, check if the is_banned column exists, if not, add it
                $checkColumn = $conn->query("SHOW COLUMNS FROM customers LIKE 'is_banned'");
                if ($checkColumn->num_rows == 0) {
                    $conn->query("ALTER TABLE customers ADD COLUMN is_banned TINYINT(1) DEFAULT 0");
                }
                
                // Fetch customer data with ban status
                $sql = "SELECT id, username, email, phone, is_banned FROM customers";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    // Output data of each row
                    while($row = $result->fetch_assoc()) {
                        $status = $row['is_banned'] ? '<span class="status-banned">Banned</span>' : '<span class="status-active">Active</span>';
                        $actionBtn = $row['is_banned'] ? 
                            '<form method="post" style="display:inline;">
                                <input type="hidden" name="user_id" value="'.$row['id'].'">
                                <input type="hidden" name="action" value="unban">
                                <button type="submit" class="unban-btn">Unban</button>
                            </form>' : 
                            '<form method="post" style="display:inline;">
                                <input type="hidden" name="user_id" value="'.$row['id'].'">
                                <input type="hidden" name="action" value="ban">
                                <button type="submit" class="ban-btn">Ban</button>
                            </form>';
                        
                        echo "<tr>
                                <td>".$row["id"]."</td>
                                <td>".$row["username"]."</td>
                                <td>".$row["email"]."</td>
                                <td>".$row["phone"]."</td>
                                <td>".$status."</td>
                                <td>".$actionBtn."</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No customers found</td></tr>";
                }
                $conn->close();
                ?>
            </tbody>
        </table>
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