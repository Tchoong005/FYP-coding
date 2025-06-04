<?php
// Database connection
$conn = new mysqli('127.0.0.1', 'root', '', 'fyp_fastfood');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new category
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'];
        $display_name = $_POST['display_name'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("INSERT INTO categories (name, display_name, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $display_name, $description);
        $stmt->execute();
        $stmt->close();
    }
    
    // Update category
    if (isset($_POST['update_category'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $display_name = $_POST['display_name'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("UPDATE categories SET name=?, display_name=?, description=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $display_name, $description, $id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Soft delete category
    if (isset($_POST['hide_category'])) {
        $id = $_POST['id'];
        $stmt = $conn->prepare("UPDATE categories SET deleted_at=NOW() WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch all active categories (not deleted)
$categories = $conn->query("SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - KFG FOOD</title>
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

        .category-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
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
<!-- Include the same topbar and sidebar as your product management page -->
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
        <h3>Category Management</h3>
        
        <button class="btn btn-add" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Add New Category
        </button>
        
        <a href="adminDeletedCategories.php" class="btn btn-restore">
            <i class="fas fa-trash-restore"></i> View Deleted Categories
        </a>
        
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Display Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($category = $categories->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                    <td><?php echo htmlspecialchars($category['display_name']); ?></td>
                    <td><?php echo htmlspecialchars($category['description']); ?></td>
                    <td>
                        <button class="btn btn-edit" onclick="openEditModal(
                            <?php echo $category['id']; ?>,
                            '<?php echo addslashes($category['name']); ?>',
                            '<?php echo addslashes($category['display_name']); ?>',
                            '<?php echo addslashes($category['description']); ?>'
                        )">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                            <button type="submit" name="hide_category" class="btn btn-hide" onclick="return confirm('Are you sure you want to delete this category? This will not delete associated products.');">
                                <i class=""></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddModal()">&times;</span>
        <h3>Add New Category</h3>
        <form method="POST">
            <div class="form-group">
                <label for="name">System Name (unique identifier, lowercase, no spaces)</label>
                <input type="text" id="name" name="name" pattern="[a-z0-9_]+" title="Lowercase letters, numbers, and underscores only" required>
            </div>
            <div class="form-group">
                <label for="display_name">Display Name</label>
                <input type="text" id="display_name" name="display_name" required>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3"></textarea>
            </div>
            <button type="submit" name="add_category" class="btn btn-add">Add Category</button>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>Edit Category</h3>
        <form method="POST">
            <input type="hidden" id="edit_id" name="id">
            <div class="form-group">
                <label for="edit_name">System Name</label>
                <input type="text" id="edit_name" name="name" pattern="[a-z0-9_]+" title="Lowercase letters, numbers, and underscores only" required readonly>
                <small>System name cannot be changed after creation</small>
            </div>
            <div class="form-group">
                <label for="edit_display_name">Display Name</label>
                <input type="text" id="edit_display_name" name="display_name" required>
            </div>
            <div class="form-group">
                <label for="edit_description">Description</label>
                <textarea id="edit_description" name="description" rows="3"></textarea>
            </div>
            <button type="submit" name="update_category" class="btn btn-edit">Update Category</button>
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

    function openEditModal(id, name, display_name, description) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_display_name').value = display_name;
        document.getElementById('edit_description').value = description;
        
        document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }

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