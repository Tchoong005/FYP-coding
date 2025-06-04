<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

// Connect to database
$conn = new mysqli("localhost", "root", "", "fyp_fastfood");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$errors = [];
$success = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Common fields
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($id)) $errors[] = "Staff ID is required";
    
    // Special handling for password reset
    if ($action === 'reset_password') {
        if (empty($password)) {
            $errors[] = "New password is required";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
    } 
    // Validate for other actions
    elseif ($action != 'delete') {
        if (empty($name)) $errors[] = "Name is required";
        if (!empty($phone) && !preg_match('/^01[0-9]{8,9}$/', $phone)) {
            $errors[] = "Phone must be 10-11 digits starting with 01";
        }
    }

    // Prevent deletion of superadmin (ID 1)
    if ($action == 'delete' && $id == '1') {
        $errors[] = "Cannot delete superadmin account";
    }

    // Process if no errors
    if (empty($errors)) {
        try {
            if ($action == 'add') {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO staff (id, name, role, phone, email, password) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $id, $name, $role, $phone, $email, $hashed_password);
            } 
            elseif ($action == 'edit') {
                $sql = "UPDATE staff SET name=?, role=?, phone=?, email=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $name, $role, $phone, $email, $id);
            } 
            elseif ($action == 'reset_password') {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE staff SET password=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $hashed_password, $id);
            } 
            elseif ($action == 'delete') {
                $sql = "DELETE FROM staff WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $id);
            }

            if (isset($stmt) && $stmt->execute()) {
                $success = "Operation completed successfully!";
                // Refresh page to show changes
                echo "<script>setTimeout(function(){ window.location.reload(); }, 1500);</script>";
            }
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        } finally {
            if (isset($stmt)) $stmt->close();
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

        body {
            background-color: #f5f5f5;
        }

        .topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.08);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 100;
            height: 60px;
        }

        .logo h2 {
            color: #dc4949;
            font-size: 24px;
        }

        .search {
            position: relative;
            width: 40%;
            max-width: 500px;
        }

        .search input {
            width: 100%;
            height: 40px;
            padding: 0 40px 0 15px;
            font-size: 16px;
            outline: none;
            border: 1px solid #ddd;
            border-radius: 20px;
            background: #f5f5f5;
        }

        .search i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }

        .user-dropdown {
            position: relative;
        }

        .user-dropdown img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            object-fit: cover;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1;
            border-radius: 6px;
            overflow: hidden;
        }

        .dropdown-content a {
            color: #333;
            padding: 10px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            transition: background 0.2s;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .user-dropdown.show .dropdown-content {
            display: block;
        }

        .list {
            position: fixed;
            top: 60px;
            left: 0;
            bottom: 0;
            width: 260px;
            background: rgba(220, 73, 73, 0.9);
            overflow-y: auto;
            z-index: 90;
        }

        .list ul {
            padding-top: 20px;
        }

        .list ul li {
            list-style: none;
        }

        .list ul li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: background 0.2s;
        }

        .list ul li a:hover {
            background: rgba(227, 125, 125, 0.8);
        }

        .list ul li a i {
            font-size: 20px;
            width: 30px;
            text-align: center;
            margin-right: 10px;
        }

        .list ul li a h4 {
            font-size: 16px;
            font-weight: 500;
        }

        .main {
            margin-left: 260px;
            margin-top: 60px;
            padding: 30px;
            min-height: calc(100vh - 60px);
        }

        .page-title {
            color: #dc4949;
            margin-bottom: 25px;
            font-size: 24px;
            font-weight: 600;
        }

        .staff-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        }

        .staff-table tr:last-child td {
            border-bottom: none;
        }

        .staff-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .staff-table tr:hover {
            background-color: #f5f5f5;
        }

        .action-form {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .form-title {
            color: #dc4949;
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 500;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
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
            font-size: 14px;
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

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn i {
            margin-right: 8px;
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

        .btn-light {
            background-color: #f8f9fa;
            color: #212529;
        }

        .btn-light:hover {
            background-color: #e2e6ea;
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
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
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

        .dropdown-toggle {
            background-color: #dc4949;
            color: white;
            padding: 8px 12px;
            min-width: 40px;
            text-align: center;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1;
            border-radius: 6px;
            overflow: hidden;
        }

        .dropdown-menu button {
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            padding: 10px 16px;
            cursor: pointer;
            color: #333;
            font-size: 14px;
            transition: background 0.2s;
        }

        .dropdown-menu button:hover {
            background-color: #f1f1f1;
        }

        .dropdown.show .dropdown-menu {
            display: block;
        }

        .password-reset-form {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .password-container {
            position: relative;
        }

        .password-strength {
            height: 4px;
            margin-top: 8px;
            background: #eee;
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }

        .weak {
            background-color: #ff4757;
            width: 30%;
        }

        .medium {
            background-color: #ffa502;
            width: 60%;
        }

        .strong {
            background-color: #2ed573;
            width: 100%;
        }

        .password-feedback {
            font-size: 12px;
            margin-top: 5px;
            color: #666;
        }

        .password-match {
            font-size: 12px;
            margin-top: 5px;
        }

        .match {
            color: #2ed573;
        }

        .no-match {
            color: #ff4757;
        }

        .fa-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 992px) {
            .list {
                width: 220px;
            }
            .main {
                margin-left: 220px;
            }
            .form-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .list {
                width: 60px;
                overflow: hidden;
            }
            .list ul li a h4 {
                display: none;
            }
            .list ul li a i {
                margin-right: 0;
                font-size: 24px;
            }
            .main {
                margin-left: 60px;
                padding: 15px;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
            .action-buttons {
                justify-content: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div class="logo">
            <h2>KFG FOOD</h2>
        </div>
        <div class="search">
            <input type="text" placeholder="Search here">
            <i class="fas fa-search"></i>
        </div>
        <div class="user-dropdown" id="userDropdown">
            <img src="img/72-729716_user-avatar-png-graphic-free-download-icon.png" alt="User">
            <div class="dropdown-content">
                <a href="profile.php"><i class="fas fa-user-edit"></i> Edit Profile</a>
                <a href="adminlogout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
            <li>
                <a href="adminorder.html">
                    <i class="fas fa-receipt"></i>
                    <h4>ORDERS</h4>
                </a>
            </li>
            <li>
                <a href="adminProduct.html">
                    <i class="fas fa-box-open"></i>
                    <h4>PRODUCTS</h4>
                </a>
            </li>
            <li>
                <a href="adminStaff.php">
                    <i class="fas fa-user-tie"></i>
                    <h4>STAFFS</h4>
                </a>
            </li>
            <li>
                <a href="adminCustomer.html">
                    <i class="fas fa-users"></i>
                    <h4>CUSTOMER</h4>
                </a>
            </li>
            <li>
                <a href="adminReport.html">
                    <i class="fas fa-chart-line"></i>
                    <h4>REPORT</h4>
                </a>
            </li>
            <li>
                <a href="adminAboutUs.html">
                    <i class="fas fa-info-circle"></i>
                    <h4>ABOUT US</h4>
                </a>
            </li>
        </ul>
    </div>

    <div class="main">
        <h1 class="page-title">Staff Management</h1>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></p>
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
                        <td>
                            <div class="dropdown">
                                <button class="btn dropdown-toggle" onclick="toggleDropdown(this)">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <button onclick="fillEditForm(
                                        '<?= $row['id'] ?>',
                                        '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['role'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['phone'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>'
                                    )">
                                        <i class="fas fa-edit"></i> Edit Profile
                                    </button>
                                    
                                    <button onclick="showResetPasswordForm('<?= $row['id'] ?>')">
                                        <i class="fas fa-key"></i> Reset Password
                                    </button>
                                    
                                    <?php if ($row['id'] != '1'): ?>
                                        <button onclick="confirmDelete('<?= $row['id'] ?>')" style="color: #dc3545;">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
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
                <input type="hidden" name="action" id="formAction" value="add">
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
                            <option value="Manager">Manager</option>
                            <option value="Cashier">Cashier</option>
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
                        <input type="password" id="password" name="password" class="form-control">
                        <small class="text-muted">Required for new staff only</small>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" name="action" value="add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Staff
                    </button>
                    <button type="submit" name="action" value="edit" class="btn btn-secondary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                    <button type="button" onclick="clearForm()" class="btn btn-light">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </form>
            
            <div id="passwordResetForm" class="password-reset-form">
                <h4><i class="fas fa-key"></i> Reset Password</h4>
                <form method="post" id="resetForm">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" id="reset_id" name="id">
                    <div class="form-group password-container">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="password" class="form-control" required>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <div class="password-feedback" id="passwordFeedback"></div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" class="form-control" required>
                        <div class="password-match" id="passwordMatch"></div>
                    </div>
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-warning" id="resetBtn">
                            <i class="fas fa-sync-alt"></i> Reset Password
                        </button>
                        <button type="button" onclick="hideResetPasswordForm()" class="btn btn-light">
                            <i class="fas fa-times"></i> Cancel
                        </button>
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
                document.querySelectorAll('.dropdown-menu').forEach(item => {
                    item.classList.remove('show');
                });
            }
            
            // Close user dropdown
            if (!event.target.matches('.user-dropdown') && !event.target.matches('.user-dropdown *')) {
                document.getElementById('userDropdown').classList.remove('show');
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
            document.getElementById('formAction').value = 'edit';
            
            // Scroll to form
            document.querySelector('.action-form').scrollIntoView({ behavior: 'smooth' });
            
            // Close dropdown
            document.querySelectorAll('.dropdown-menu').forEach(item => {
                item.classList.remove('show');
            });
        }
        
        // Clear form
        function clearForm() {
            document.getElementById('staffForm').reset();
            document.getElementById('password').required = true;
            document.getElementById('password').placeholder = "";
            document.getElementById('formAction').value = 'add';
        }
        
        // Enhanced password strength checker
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            const feedback = document.getElementById('passwordFeedback');
            
            // Reset
            strengthBar.className = 'password-strength-bar';
            feedback.textContent = '';
            
            if (password.length === 0) return;
            
            // Check strength
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^A-Za-z0-9]/)) strength++;
            
            // Update UI
            if (strength < 2) {
                strengthBar.className = 'password-strength-bar weak';
                feedback.textContent = 'Weak password - add more characters, numbers, or symbols';
            } else if (strength < 4) {
                strengthBar.className = 'password-strength-bar medium';
                feedback.textContent = 'Medium strength password - could be stronger';
            } else {
                strengthBar.className = 'password-strength-bar strong';
                feedback.textContent = 'Strong password';
            }
            
            // Check password match if confirmation field has value
            if (document.getElementById('confirm_password').value.length > 0) {
                checkPasswordMatch();
            }
        });

        // Password confirmation check
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirm.length === 0) {
                matchDiv.textContent = '';
                matchDiv.className = 'password-match';
                document.getElementById('resetBtn').disabled = true;
                return;
            }
            
            if (password !== confirm) {
                matchDiv.textContent = 'Passwords do not match';
                matchDiv.className = 'password-match no-match';
                document.getElementById('resetBtn').disabled = true;
            } else {
                matchDiv.textContent = 'Passwords match';
                matchDiv.className = 'password-match match';
                document.getElementById('resetBtn').disabled = false;
            }
        }

        // Enhanced showResetPasswordForm
        function showResetPasswordForm(id) {
            // Hide all dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(item => {
                item.classList.remove('show');
            });
            
            // Reset and show form
            document.getElementById('resetForm').reset();
            document.getElementById('reset_id').value = id;
            document.getElementById('passwordResetForm').style.display = 'block';
            
            // Clear any previous messages
            document.getElementById('passwordMatch').textContent = '';
            document.getElementById('passwordStrengthBar').className = 'password-strength-bar';
            document.getElementById('passwordFeedback').textContent = '';
            document.getElementById('resetBtn').disabled = true;
            
            // Focus on password field
            document.getElementById('new_password').focus();
            
            // Scroll to form
            document.getElementById('passwordResetForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Enhanced hideResetPasswordForm
        function hideResetPasswordForm() {
            document.getElementById('passwordResetForm').style.display = 'none';
            document.getElementById('resetForm').reset();
            document.getElementById('resetBtn').disabled = false;
        }

        // Confirm before deleting
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this staff member?')) {
                document.getElementById('id').value = id;
                document.getElementById('formAction').value = 'delete';
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

        // Form submission handler
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters!');
                return;
            }
            
            // Show loading state
            const btn = document.getElementById('resetBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
        });

        // User dropdown functionality
        document.getElementById('userDropdown').addEventListener('click', function(event) {
            event.stopPropagation();
            this.classList.toggle('show');
        });
    </script>
</body>
</html>