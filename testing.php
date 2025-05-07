<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "kfg");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Add, Edit, Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($action == 'add') {
        $sql = "INSERT INTO staff (id, name, role, phone, email) VALUES ('$id', '$name', '$role', '$phone', '$email')";
        $conn->query($sql);
    } elseif ($action == 'edit') {
        $sql = "UPDATE staff SET name='$name', role='$role', phone='$phone', email='$email' WHERE id='$id'";
        $conn->query($sql);
    } elseif ($action == 'delete') {
        $sql = "DELETE FROM staff WHERE id='$id'";
        $conn->query($sql);
    }
}

// Load all staff
$staffList = $conn->query("SELECT * FROM staff");
?>

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
            padding: 12px 100px;
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

        .button-group {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            gap: 15px; 
        }

         .button-group button {
            padding: 10px 20px;
            border: none;
            background-color: #dc4949;
            color: white;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
    
        }

        .button-group button:hover {
            background-color: #c53737;
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
            <div class="user">
                <img src="img/72-729716_user-avatar-png-graphic-free-download-icon.png" alt="">
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
                <a href="adminProduct.html">
                    <i class="fas fa-box-open"></i>
                    <h4>PRODUCTS</h4>
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="adminStaff.html">
                    <i class="fas fa-user-tie"></i>
                    <h4>STAFFS</h4>
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="adminCustomer.html">
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
        <h2>Staff Management</h2>
        <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Role</th>
                <th>Phone</th>
                <th>Emails</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $staffList->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['role'] ?></td>
                    <td><?= $row['phone'] ?></td>
                    <td><?= $row['email'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>

            
        </table>

        <div class="button-group">
        <form method="post">
        <h3>Add / Edit / Delete Staff</h3>
        <input type="text" name="id" placeholder="Staff ID" required>
        <input type="text" name="name" placeholder="Name">
        <input type="text" name="role" placeholder="Role">
        <input type="text" name="phone" placeholder="Phone">
        <input type="email" name="email" placeholder="Email">
        
        <button type="submit" name="action" value="add">Add Staff</button>
        <button type="submit" name="action" value="edit">Edit Staff</button>
        <button type="submit" name="action" value="delete" style="background:#999;">Delete Staff</button>
    </form>
        </div>
        
    </div>

          
    
        
   

</body>

</html>