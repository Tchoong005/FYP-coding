<?php
session_start();
include 'connection.php';

$product_id = intval($_POST['product_id']);
$sauces = isset($_POST['sauces']) ? $_POST['sauces'] : [];
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

$result = mysqli_query($conn, "SELECT * FROM products WHERE id = $product_id");
$product = mysqli_fetch_assoc($result);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}

$stock = intval($product['stock']);
$currentQty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;

if ($currentQty >= $stock) {
    echo json_encode(['success' => false, 'message' => 'Maximum stock reached.']);
    exit;
}

$_SESSION['cart'][$product_id] = [
    'product_id' => $product_id,
    'quantity' => $currentQty + 1,
    'sauces' => $sauces,
    'message' => $message
];

$totalItems = array_sum(array_column($_SESSION['cart'], 'quantity'));

echo json_encode([
    'success' => true,
    'message' => 'Added to order list!',
    'cart_count' => $totalItems
]);
?>
