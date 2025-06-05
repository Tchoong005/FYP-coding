<?php
require_once 'db_connection.php';

if (isset($_GET['name'])) {
    $name = $_GET['name'];
    
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();
    
    header('Content-Type: application/json');
    echo json_encode(['exists' => $stmt->num_rows > 0]);
    exit;
}

header('Content-Type: application/json');
echo json_encode(['exists' => false]);