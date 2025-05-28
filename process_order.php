<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    echo "Your cart is empty.";
    exit;
}

// 计算总价
$total_price = 0;
foreach ($cart as $item) {
    $pid = (int)$item['product_id'];
    $qty = (int)$item['quantity'];

    $res = mysqli_query($conn, "SELECT price FROM products WHERE id = $pid LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $total_price += $row['price'] * $qty;
    }
}

// 开启事务
mysqli_begin_transaction($conn);

try {
    // 查找用户是否已有未完成订单（pending 或 in_checkout）
    $sql_check = "SELECT * FROM orders WHERE user_id = $user_id AND status IN ('pending', 'in_checkout') ORDER BY created_at DESC LIMIT 1";
    $res_check = mysqli_query($conn, $sql_check);

    if ($res_check && mysqli_num_rows($res_check) > 0) {
        // 更新已有订单
        $order = mysqli_fetch_assoc($res_check);
        $order_id = $order['id'];

        $stmt_update = $conn->prepare("UPDATE orders SET total_price = ?, created_at = NOW() WHERE id = ?");
        $stmt_update->bind_param("di", $total_price, $order_id);
        $stmt_update->execute();
        $stmt_update->close();

        // 删除旧的订单项，重新插入
        $del_sql = "DELETE FROM order_items WHERE order_id = $order_id";
        mysqli_query($conn, $del_sql);
    } else {
        // 插入新订单
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, created_at, status) VALUES (?, ?, NOW(), 'in_checkout')");
        $stmt->bind_param("id", $user_id, $total_price);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();
    }

    // 插入订单项
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, sauce, comment, price) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($cart as $item) {
        $pid = (int)$item['product_id'];
        $qty = (int)$item['quantity'];
        $sauce = $item['sauce'] ?? '';
        $comment = $item['comment'] ?? '';

        $res = mysqli_query($conn, "SELECT price FROM products WHERE id = $pid LIMIT 1");
        $price = 0;
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            $price = $row['price'];
        }

        $stmt_item->bind_param("iiissd", $order_id, $pid, $qty, $sauce, $comment, $price);
        $stmt_item->execute();
    }
    $stmt_item->close();

    mysqli_commit($conn);

    unset($_SESSION['cart']); // 清空购物车

    header("Location: checkout.php?order_id=$order_id");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo "Failed to create order: " . $e->getMessage();
}
