<?php
session_start();
include 'connection.php';

if(isset($_POST['admin_login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Check for admin account in the Admin table
    $sql = "SELECT * FROM Admin WHERE admin_username='$username'";
    $result = $conn->query($sql);
    if($result && $result->num_rows > 0){
         $admin = $result->fetch_assoc();
         if($admin['admin_password'] == $password){
             $_SESSION['admin_id'] = $admin['Admin_Id'];
             echo "Admin login successful!";
             // Optionally redirect to an admin dashboard:
             // header("Location: admin_dashboard.php");
             exit();
         } else {
             $error = "Incorrect password.";
         }
    } else {
         $error = "Admin not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Admin Login</h2>
        <?php if(isset($error)) echo "<p>$error</p>"; ?>
        <form action="admin_login.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <input type="submit" name="admin_login" value="Login">
        </form>
    </div>
</body>
</html>
