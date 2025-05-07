<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'fyp_fastfood'; // 确保与你 phpMyAdmin 的数据库名一致

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
