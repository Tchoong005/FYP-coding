<?php
session_start();
require_once 'db_connection.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new product
    if (isset($_POST['add_product'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $category = $_POST['category'];
        $stock = (int)$_POST['stock_quantity'];
        $image_url = trim($_POST['image_url']);
        
        // Validate product name uniqueness
        if (!isProductNameUnique($conn, $name)) {
            $_SESSION['error'] = "A product named '$name' already exists.";
            header("Location: adminProduct.php");
            exit;
        }
        
        // Validate category is not deleted
        if (isCategoryDeleted($conn, $category)) {
            $_SESSION['error'] = "Selected category is not available.";
            header("Location: adminProduct.php");
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, category, stock_quantity, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsis", $name, $description, $price, $category, $stock, $image_url);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Product added successfully!";
        } else {
            $_SESSION['error'] = "Error adding product: " . $conn->error;
        }
        $stmt->close();
    }
    
    // Update product
    if (isset($_POST['update_product'])) {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $category = $_POST['category'];
        $stock = (int)$_POST['stock_quantity'];
        $image_url = trim($_POST['image_url']);
        
        // Validate product name uniqueness (excluding current product)
        if (!isProductNameUnique($conn, $name, $id)) {
            $_SESSION['error'] = "Another product named '$name' already exists.";
            header("Location: adminProduct.php?action=edit&id=$id");
            exit;
        }
        
        // Validate category is not deleted
        if (isCategoryDeleted($conn, $category)) {
            $_SESSION['error'] = "Selected category is not available.";
            header("Location: adminProduct.php?action=edit&id=$id");
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, category=?, stock_quantity=?, image_url=? WHERE id=?");
        $stmt->bind_param("ssdsisi", $name, $description, $price, $category, $stock, $image_url, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Product updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating product: " . $conn->error;
        }
        $stmt->close();
    }
    
    // Soft delete product
    if (isset($_POST['hide_product'])) {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE products SET deleted_at=NOW() WHERE id=?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Product deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting product: " . $conn->error;
        }
        $stmt->close();
    }
    
    header("Location: adminProduct.php");
    exit;
}

// Fetch all active products with active categories only
$products = $conn->query("
    SELECT p.*, c.display_name as category_display 
    FROM products p
    JOIN categories c ON p.category = c.name
    WHERE p.deleted_at IS NULL AND c.deleted_at IS NULL
    ORDER BY c.display_name, p.name
");

// Helper function to check product name uniqueness
function isProductNameUnique($conn, $name, $currentId = null) {
    $stmt = $conn->prepare("SELECT id FROM products WHERE name = ? AND deleted_at IS NULL");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Allow the same product to keep its name during update
        return ($currentId && $row['id'] == $currentId);
    }
    return true;
}

// Helper function to check if category is deleted
function isCategoryDeleted($conn, $categoryName) {
    $stmt = $conn->prepare("SELECT deleted_at FROM categories WHERE name = ?");
    $stmt->bind_param("s", $categoryName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['deleted_at'] !== null;
    }
    return true; // Consider non-existent categories as deleted
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - KFG FOOD</title>
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

        .btn:hover {
            opacity: 0.8;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 50%;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
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

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #dff0d8;
            border-color: #d6e9c6;
            color: #3c763d;
        }

        .alert-danger {
            background-color: #f2dede;
            border-color: #ebccd1;
            color: #a94442;
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
            <h3>Product Management</h3>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <button class="btn btn-add" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Product
            </button>
            
            <a href="adminDeletedProducts.php" class="btn btn-restore">
                <i class="fas fa-trash-restore"></i> View Deleted Products
            </a>
            
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Price (RM)</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($product = $products->fetch_assoc()): ?>
                    <tr>
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
                        <td><?= $product['stock_quantity'] ?></td>
                        <td>
                            <button class="btn btn-edit" onclick="openEditModal(
                                <?= $product['id'] ?>,
                                '<?= addslashes($product['name']) ?>',
                                '<?= addslashes($product['description']) ?>',
                                <?= $product['price'] ?>,
                                '<?= $product['category'] ?>',
                                <?= $product['stock_quantity'] ?>,
                                '<?= addslashes($product['image_url']) ?>'
                            )">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                <button type="submit" name="hide_product" class="btn btn-hide" onclick="return confirm('Are you sure you want to delete this product?');">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h3>Add New Product</h3>
            <form method="POST" id="addProductForm">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                    <small id="name-error" class="error-message" style="color: red; display: none;"></small>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price (RM)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <?php 
                        $categories = $conn->query("SELECT name, display_name FROM categories WHERE deleted_at IS NULL ORDER BY display_name");
                        while($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['display_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="stock_quantity">Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" min="0" required>
                </div>
                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <input type="text" id="image_url" name="image_url" placeholder="https://example.com/image.jpg">
                </div>
                <button type="submit" name="add_product" class="btn btn-add">Add Product</button>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Edit Product</h3>
            <form method="POST" id="editProductForm">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_name">Product Name</label>
                    <input type="text" id="edit_name" name="name" required>
                    <small id="edit-name-error" class="error-message" style="color: red; display: none;"></small>
                </div>
                <div class="form-group">
                    <label for="edit_description">Description</label>
                    <textarea id="edit_description" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="edit_price">Price (RM)</label>
                    <input type="number" id="edit_price" name="price" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_category">Category</label>
                    <select id="edit_category" name="category" required>
                        <?php 
                        $categories = $conn->query("SELECT name, display_name FROM categories WHERE deleted_at IS NULL ORDER BY display_name");
                        while($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['display_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_stock_quantity">Stock Quantity</label>
                    <input type="number" id="edit_stock_quantity" name="stock_quantity" min="0" required>
                </div>
                <div class="form-group">
                    <label for="edit_image_url">Image URL</label>
                    <input type="text" id="edit_image_url" name="image_url" placeholder="https://example.com/image.jpg">
                </div>
                <button type="submit" name="update_product" class="btn btn-edit">Update Product</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openEditModal(id, name, description, price, category, stock, image_url) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_category').value = category;
            document.getElementById('edit_stock_quantity').value = stock;
            document.getElementById('edit_image_url').value = image_url;
            
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Name validation
        function checkProductName(name, currentId = null) {
            return fetch('check_product_name.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, currentId })
            })
            .then(response => response.json());
        }

        // Add product name validation
        document.getElementById('name').addEventListener('blur', function() {
            const name = this.value.trim();
            const errorElement = document.getElementById('name-error');
            
            if (name) {
                checkProductName(name)
                    .then(data => {
                        if (!data.available) {
                            errorElement.textContent = 'This product name already exists! ' + 
                                (data.suggestions.length ? 'Suggestions: ' + data.suggestions.join(', ') : '');
                            errorElement.style.display = 'block';
                        } else {
                            errorElement.style.display = 'none';
                        }
                    });
            }
        });

        // Edit product name validation
        document.getElementById('edit_name').addEventListener('blur', function() {
            const name = this.value.trim();
            const productId = document.getElementById('edit_id').value;
            const errorElement = document.getElementById('edit-name-error');
            
            if (name && productId) {
                checkProductName(name, productId)
                    .then(data => {
                        if (!data.available) {
                            errorElement.textContent = 'This product name already exists! ' + 
                                (data.suggestions.length ? 'Suggestions: ' + data.suggestions.join(', ') : '');
                            errorElement.style.display = 'block';
                        } else {
                            errorElement.style.display = 'none';
                        }
                    });
            }
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        };

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