<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['staff_email'])) {
    header("Location: adminhome.html");
    exit();
}

// Database connection
$db = new mysqli('127.0.0.1', 'root', '', 'fyp_fastfood');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Check staff credentials
        $stmt = $db->prepare("SELECT id, name, email, password, usertype FROM staff WHERE email = ? AND deleted = 0");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $staff = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $staff['password'])) {
                // Set session variables
                $_SESSION['staff_id'] = $staff['id'];
                $_SESSION['staff_name'] = $staff['name'];
                $_SESSION['staff_email'] = $staff['email'];
                $_SESSION['staff_usertype'] = $staff['usertype'];
                
                // Redirect to admin home
                header("Location: adminhome.html");
                exit();
            } else {
                $error = 'Invalid email or password';
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastFood Express - Admin Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 400px;
            padding: 40px;
            text-align: center;
        }
        
        .logo {
            margin-bottom: 30px;
        }
        
        .logo h2 {
            color: #dc4949;
            font-size: 28px;
        }
        
        .login-form h3 {
            margin-bottom: 20px;
            color: #333;
            font-size: 22px;
        }
        
        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 14px;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #dc4949;
        }
        
        .login-button {
            width: 100%;
            padding: 12px;
            background-color: #dc4949;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .login-button:hover {
            background-color: #c43c3c;
        }
        
        .error-message {
            color: #dc4949;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .forgot-password {
            margin-top: 15px;
            font-size: 14px;
        }
        
        .forgot-password a {
            color: #dc4949;
            text-decoration: none;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .login-container {
                width: 90%;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h2>FastFood Express</h2>
        </div>
        
        <div class="login-form">
            <h3>Admin Login</h3>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form action="adminlogin.php" method="POST">
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="login-button">Login</button>
            </form>
            
            <div class="forgot-password">
                <a href="forgot-password.php">Forgot password?</a>
            </div>
        </div>
    </div>
</body>
</html>