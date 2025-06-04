<?php
include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$order_id = (int)($data['order_id'] ?? 0);

// 验证订单状态
$order_check = mysqli_query($conn, "SELECT payment_status FROM orders WHERE id = $order_id");
if (!$order_check || mysqli_num_rows($order_check) === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$order = mysqli_fetch_assoc($order_check);
if ($order['payment_status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'Invalid order status for stock update']);
    exit;
}

// 事务处理
mysqli_begin_transaction($conn);

try {
    // 获取订单项
    $items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = $order_id FOR UPDATE";
    $items_result = mysqli_query($conn, $items_sql);
    
    if (!$items_result) {
        throw new Exception("Failed to fetch order items: " . mysqli_error($conn));
    }
    
    $updates = [];
    while ($item = mysqli_fetch_assoc($items_result)) {
        $product_id = (int)$item['product_id'];
        $quantity = (int)$item['quantity'];
        
        // 更新库存
        $update_sql = "UPDATE products SET stock_quantity = stock_quantity - $quantity 
                      WHERE id = $product_id AND stock_quantity >= $quantity";
        
        if (!mysqli_query($conn, $update_sql)) {
            throw new Exception("Update failed: " . mysqli_error($conn));
        }
        
        if (mysqli_affected_rows($conn) === 0) {
            throw new Exception("Insufficient stock for product ID: $product_id");
        }
    }
    
    // 标记订单为库存已更新
    $mark_sql = "UPDATE orders SET stock_updated = 1 WHERE id = $order_id";
    if (!mysqli_query($conn, $mark_sql)) {
        throw new Exception("Failed to mark order: " . mysqli_error($conn));
    }
    
    // 提交事务
    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
} catch (Exception $e) {
    // 回滚事务
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>