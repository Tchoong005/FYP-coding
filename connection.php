<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "fast_food";  // Make sure your database name matches this

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

