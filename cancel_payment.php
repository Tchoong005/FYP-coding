<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

// 检查订单是否属于当前用户
$order_sql = "SELECT * FROM orders WHERE id = $order_id AND user_id = $user_id";
$order_result = mysqli_query($conn, $order_sql);
if (!$order_result || mysqli_num_rows($order_result) === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit();
}

// 检查订单状态
$order_data = mysqli_fetch_assoc($order_result);
if ($order_data['payment_status'] !== 'pending') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Order cannot be canceled']);
    exit();
}

// 更新订单状态为已取消
$update_sql = "UPDATE orders SET payment_status = 'canceled', order_status = 'canceled' WHERE id = $order_id";
if (mysqli_query($conn, $update_sql)) {
    // 新增：清空用户的购物车
    if (isset($_SESSION['cart'])) {
        unset($_SESSION['cart']);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Payment canceled successfully']);
    exit();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error updating order: ' . mysqli_error($conn)]);
    exit();
}