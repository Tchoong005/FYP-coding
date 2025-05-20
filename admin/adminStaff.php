<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "fyp_fastfood");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    

    // Validate inputs
    $errors = [];
    
    // Prevent deletion of superadmin (ID 1)
    if ($action == 'delete' && $id == '1') {
        $errors[] = "Cannot delete superadmin account";
    }
    
    if (empty($id)) $errors[] = "Staff ID is required";
    if ($action != 'delete' && empty($name)) $errors[] = "Name is required";
    
    // Validate phone format
    if (!empty($phone) && !preg_match('/^01[0-9]{8,9}$/', $phone)) {
        $errors[] = "Phone must be 10-11 digits starting with 01";
    }

    if (empty($errors)) {
        if ($action == 'add') {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO staff (id, name, role, phone, email, password) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $id, $name, $role, $phone, $email, $hashed_password);
        } elseif ($action == 'edit') {
            // Regular edit - no password change
            $sql = "UPDATE staff SET name=?, role=?, phone=?, email=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $name, $role, $phone, $email, $id);
        } elseif ($action == 'reset_password') {
            // Password reset only
            if (empty($password)) {
                $errors[] = "New password is required";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE staff SET password=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $hashed_password, $id);
            }
        } elseif ($action == 'delete') {
            $sql = "DELETE FROM staff WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $id);
        }

        if (empty($errors)) {
            if ($stmt->execute()) {
                $success = "Operation completed successfully!";
            } else {
                $errors[] = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Load all staff
$staffList = $conn->query("SELECT * FROM staff ORDER BY id");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management</title>
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
            margin-left: 280px;
            margin-top: 80px;
            padding: 30px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            max-width: 1200px;
            width: calc(100% - 300px);
        }

        .page-title {
            color: #dc4949;
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 600;
        }

        .staff-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 30px;
        }

        .staff-table th {
            background-color: #dc4949;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 500;
        }

        .staff-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .staff-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .staff-table tr:hover {
            background-color: #f5f5f5;
        }

        .action-form {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-top: 30px;
        }

        .form-title {
            color: #dc4949;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }

        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border 0.3s;
        }

        .form-control:focus {
            border-color: #dc4949;
            outline: none;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 10px;
        }

        .btn-primary {
            background-color: #dc4949;
            color: white;
        }

        .btn-primary:hover {
            background-color: #c53737;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-admin {
            background-color: #d4edff;
            color: #004085;
        }

        .badge-superadmin {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .badge-staff {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 6px;
            overflow: hidden;
        }

        .dropdown-content button {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            color: #333;
            font-size: 14px;
        }

        .dropdown-content button:hover {
            background-color: #f1f1f1;
        }

        .dropdown-toggle {
            background-color: #dc4949;
            color: white;
        }

        .show {
            display: block;
        }

        .password-reset-form {
            display: none;
            margin-top: 20px;
            padding: 15px;
            background-color: #f1f1f1;
            border-radius: 6px;
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
        <h1 class="page-title">Staff Management</h1>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?= $error ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <table class="staff-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $staffList->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td>
                            <?php 
                            $badgeClass = 'badge-staff';
                            if ($row['id'] == '1') $badgeClass = 'badge-superadmin';
                            elseif ($row['usertype'] == 'admin') $badgeClass = 'badge-admin';
                            ?>
                            <span class="status-badge <?= $badgeClass ?>">
                                <?= htmlspecialchars($row['role']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td class="action-buttons">
                            <div class="dropdown">
                                <button class="btn dropdown-toggle" onclick="toggleDropdown(this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-content">
                                    <button onclick="fillEditForm(
                                        '<?= $row['id'] ?>',
                                        '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['role'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['phone'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>'
                                    )">Edit Profile</button>
                                    
                                    <button onclick="showResetPasswordForm('<?= $row['id'] ?>')">Reset Password</button>
                                    
                                    <?php if ($row['id'] != '1'): ?>
                                        <button onclick="confirmDelete('<?= $row['id'] ?>')" style="color: #dc3545;">Delete</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="action-form">
            <h3 class="form-title">Staff Actions</h3>
            <form method="post" id="staffForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="id">Staff ID</label>
                        <input type="text" id="id" name="id" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" class="form-control" required>
                            <option value="IT Technician">IT Technician</option>
                            <option value="IT Support">IT Support</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="01XXXXXXXX" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                        <small>Required for new staff only</small>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" name="action" value="add" class="btn btn-primary">Add Staff</button>
                    <button type="submit" name="action" value="edit" class="btn btn-secondary">Update Profile</button>
                    <button type="button" onclick="clearForm()" class="btn">Clear</button>
                </div>
            </form>
            
            <div id="passwordResetForm" class="password-reset-form">
                <h4>Reset Password</h4>
                <form method="post" id="resetForm">
                    <input type="hidden" id="reset_id" name="id">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="password" class="form-control" required>
                    </div>
                    <div class="action-buttons">
                        <button type="submit" name="action" value="reset_password" class="btn btn-warning">Reset Password</button>
                        <button type="button" onclick="hideResetPasswordForm()" class="btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle dropdown menu
        function toggleDropdown(button) {
            const dropdown = button.nextElementSibling;
            dropdown.classList.toggle("show");
            
            // Close other dropdowns
            document.querySelectorAll('.dropdown-content').forEach(item => {
                if (item !== dropdown) {
                    item.classList.remove('show');
                }
            });
        }
        
        // Close dropdowns when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.dropdown-toggle') && !event.target.matches('.dropdown-toggle *')) {
                document.querySelectorAll('.dropdown-content').forEach(item => {
                    item.classList.remove('show');
                });
            }
        }
        
        // Fill edit form
        function fillEditForm(id, name, role, phone, email) {
            document.getElementById('id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('role').value = role;
            document.getElementById('phone').value = phone;
            document.getElementById('email').value = email;
            document.getElementById('password').required = false;
            document.getElementById('password').placeholder = "(Leave blank to keep current)";
            
            // Scroll to form
            document.querySelector('.action-form').scrollIntoView({ behavior: 'smooth' });
            
            // Close dropdown
            document.querySelectorAll('.dropdown-content').forEach(item => {
                item.classList.remove('show');
            });
        }
        
        // Clear form
        function clearForm() {
            document.getElementById('staffForm').reset();
            document.getElementById('password').required = true;
            document.getElementById('password').placeholder = "";
        }
        
        // Show password reset form
        function showResetPasswordForm(id) {
            document.getElementById('reset_id').value = id;
            document.getElementById('passwordResetForm').style.display = 'block';
            
            // Close dropdown
            document.querySelectorAll('.dropdown-content').forEach(item => {
                item.classList.remove('show');
            });
        }
        
        // Hide password reset form
        function hideResetPasswordForm() {
            document.getElementById('passwordResetForm').style.display = 'none';
            document.getElementById('resetForm').reset();
        }
        
        // Confirm before deleting
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this staff member?')) {
                document.getElementById('id').value = id;
                document.getElementById('staffForm').action.value = 'delete';
                document.getElementById('staffForm').submit();
            }
        }
        
        // Phone number validation
        document.getElementById('phone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (!this.value.startsWith('01') && this.value.length > 0) {
                this.value = '01' + this.value.substring(0, 9);
            }
            if (this.value.length > 11) {
                this.value = this.value.substring(0, 11);
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