<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$db = "fyp_fastfood";



$sql = "SELECT * FROM staff WHERE email = ? AND deleted = 0";

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
    $sql = "SELECT * FROM staff WHERE email = ?";
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
        $error = "Invalid email or password";
    }
    
    // Generic error message (don't reveal which was wrong)
    echo "<script>alert('Invalid email or password');</script>";
}
?>


<!DOCTYPE html>
<htm1 lang="en">

<head>
    <meta charset="UTF-8">

    <title>Admin Login Page</title>
    <STYle>
        *{
            margin: 0;
            padding:0;
            box-sizing: border-box;
        }
        body{
            height: 750px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('img/OIP.jpg') no-repeat;
            background-size: cover;
            background-position: center;
        }
        .smallbox{
            display: flex;
            flex-direction: column;
            width: 300px;
            padding: 15px;
            border: 1px solid;
            color: skyblue;
            border-radius: 20px;


        }
        input{
            margin: 5px 0px;
            padding: 7px;
            height: 35px;
        }

        button{
            height: 35px;
            margin: 5px 0px;
            background-color: skyblue;
            color: #333;
            border: none;
            border-radius: 5px;

        }
        button:hover{
            background-color: #333;
            color: skyblue;

        }
        h1 {
            font-size: 80px;
            font-style: oblique;
            color: rgb(215, 249, 250);
            margin-bottom: 20px;
        }

        H2 {
            color: rgb(215, 249, 250);
            margin-bottom: 15px;
            
        }
        .i{
            position: absolute;
            right: 46%;
            top: 60%;
            font-size: 20px;
            transform: translateY(-50%);
        }
        .o{
            position: absolute;
            right: 46%;
            top: 66.5%;
            font-size: 20px;
            transform: translateY(-50%);
        }

    </STYle>

</head>

<body>
    
    <h1>FastFood Express</h1>
    
    <form method="POST">
    <div class="smallbox">
        <H2>Admin Login</H2>
        
        <input type="text" name="email" placeholder="EMAIL" required>
        <img class="i" src="img/user-solid-24.png" alt="">
        <input type="password" name="password" placeholder="PASSWORD" required>
        <img class="o" src="img/lock-solid-24.png" alt="">
        <button type="submit">SUBMIT</button>
    
    </div>
    </form>

    

</body>


</htm1>