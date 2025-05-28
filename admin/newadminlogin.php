<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$db = "fyp_fastfood";

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'use_strict_mode' => true
    ]);
}

$data = mysqli_connect($host, $user, $password, $db);
if ($data === false) {
    die("Connection error: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($data, $_POST["email"]);
    $password = $_POST["password"];

    // Prepared statement to prevent SQL injection
    $sql = "SELECT * FROM staff WHERE email = ? AND deleted = 0"; // 添加 deleted = 0 条件
    $stmt = mysqli_prepare($data, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        // Verify password against hashed password in database
        if (password_verify($password, $row['password'])) {
            $_SESSION["email"] = $row['email'];
            $_SESSION["usertype"] = $row['usertype'];
            $_SESSION["user_id"] = $row['id'];

            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            if ($row["usertype"] == "superadmin") {
                header("location:adminHome.html");
                exit();
            } elseif ($row["usertype"] == "admin") {
                header("location:adminHome.html");
                exit();
            }
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Invalid email or password or account is disabled";
    }
    
    // Generic error message (don't reveal which was wrong)
    echo "<script>alert('Invalid email or password or account is disabled');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | FastFood Express</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #166088;
            --accent-color: #4fc3f7;
            --text-color: #333;
            --light-color: #f8f9fa;
            --error-color: #e63946;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('img/OIP.jpg') no-repeat center center fixed;
            background-size: cover;
            color: var(--light-color);
            padding: 20px;
        }
        
        .login-container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        
        .welcome-section {
            flex: 1;
            padding: 40px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .welcome-section h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        .welcome-section p {
            font-size: 1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .logo {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--accent-color);
        }
        
        .login-form {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-form h2 {
            font-size: 2rem;
            color: var(--secondary-color);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .form-group i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }
        
        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.2);
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .forgot-password {
            text-align: right;
            margin-top: 10px;
        }
        
        .forgot-password a {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: var(--error-color);
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .welcome-section, .login-form {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="welcome-section">
            <div class="logo">
                <i class="fas fa-hamburger"></i>
            </div>
            <h1>Welcome Back</h1>
            <p>FastFood Express Administration Panel. Please login to access the management system.</p>
        </div>
        
        <div class="login-form">
            <h2>Admin Login</h2>
            <form method="POST">
                <div class="form-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" class="form-control" name="email" placeholder="Email Address" required>
                </div>
                
                <div class="form-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                </div>
                
                <div class="forgot-password">
                    <a href="#">Forgot Password?</a>
                </div>
                
                <button type="submit" class="btn">LOGIN</button>
                
                <?php if(isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>