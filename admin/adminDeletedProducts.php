<?php
session_start();
require_once 'db_connection.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Restore product
    if (isset($_POST['restore_product'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE products SET deleted_at=NULL WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "";
        } else {
            $_SESSION['error'] = "Error restoring product: " . $conn->error;
        }
        $stmt->close();
        header("Location: adminDeletedProducts.php");
        exit;
    }
}

// Fetch all deleted products with category display names
$products = $conn->query("
    SELECT p.*, c.display_name as category_display 
    FROM products p
    JOIN categories c ON p.category = c.name
    WHERE p.deleted_at IS NOT NULL
    ORDER BY p.deleted_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deleted Products - KFG FOOD</title>
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
            font-family: 'poppins', sans-serif;
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

        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            margin-bottom: 15px;
            color: #333;
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
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn-edit {
            background-color: #4CAF50;
            color: white;
        }

        .btn-hide {
            background-color: #f44336;
            color: white;
        }

        .btn-add {
            background-color: #2196F3;
            color: white;
            margin-bottom: 20px;
        }

        .btn-restore {
            background-color: #ff9800;
            color: white;
        }

        .btn-delete {
            background-color: #9e9e9e;
            color: white;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .product-image {
            max-width: 100px;
            max-height: 100px;
            border-radius: 4px;
        }

        .category-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .beverages {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .chicken {
            background-color: #fff8e1;
            color: #ff8f00;
        }

        .burger {
            background-color: #fce4ec;
            color: #c2185b;
        }

        .desserts_sides {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .deleted-row {
            opacity: 0.7;
            background-color: #ffeeee;
        }

        .deleted-row:hover {
            opacity: 1;
        }
        .deleted-row {
            opacity: 0.7;
            background-color: #ffeeee;
        }
        .deleted-row:hover {
            opacity: 1;
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
        <div class="card">
            <h3>Deleted Products</h3>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <a href="adminProduct.php" class="btn btn-add">
                <i class="fas fa-arrow-left"></i> Back to Active Products
            </a>
            
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price (RM)</th>
                        <th>Category</th>
                        <th>Deleted At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($product = $products->fetch_assoc()): ?>
                    <tr class="deleted-row">
                        <td>
                            <?php if($product['image_url']): ?>
                                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                            <?php else: ?>
                                <i class="fas fa-image" style="font-size: 24px;"></i>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= htmlspecialchars($product['description']) ?></td>
                        <td><?= number_format($product['price'], 2) ?></td>
                        <td>
                            <span class="category-badge <?= htmlspecialchars($product['category']) ?>">
                                <?= htmlspecialchars($product['category_display']) ?>
                            </span>
                        </td>
                        <td><?= date('Y-m-d H:i', strtotime($product['deleted_at'])) ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                <button type="submit" name="restore_product" class="btn btn-restore">
                                    <i class="fas fa-trash-restore"></i> Restore
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>

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