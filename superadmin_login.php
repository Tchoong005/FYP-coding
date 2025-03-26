<?php
session_start();
include 'connection.php';

if(isset($_POST['superadmin_login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Hard-coded superadmin credentials
    if($username === 'admin' && $password === '12345678'){
         $_SESSION['superadmin'] = true;
         echo "Superadmin login successful!";
         // Optionally redirect to a superadmin dashboard:
         // header("Location: superadmin_dashboard.php");
         exit();
    } else {
         $error = "Invalid superadmin credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Superadmin Login</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Superadmin Login</h2>
        <?php if(isset($error)) echo "<p>$error</p>"; ?>
        <form action="superadmin_login.php" method="post">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <input type="submit" name="superadmin_login" value="Login">
        </form>
    </div>
</body>
</html>
