<?php 
session_start();
include 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// 读取 JSON 请求体
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$product_id = (int)$input['product_id'];
$quantity = max(1, (int)$input['quantity']);
$sauce = isset($input['sauce']) ? trim($input['sauce']) : '';
$comment = isset($input['comment']) ? trim($input['comment']) : '';
$recommendations = isset($input['recommendations']) && is_array($input['recommendations']) ? $input['recommendations'] : [];

if ($quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
    exit;
}

// 检查产品是否存在，并验证酱料要求
$sql = "SELECT category FROM products WHERE id = $product_id LIMIT 1";
$res = mysqli_query($conn, $sql);
if ($res && mysqli_num_rows($res) > 0) {
    $row = mysqli_fetch_assoc($res);
    if (strtolower($row['category']) !== 'beverages' && !$sauce) {
        echo json_encode(['success' => false, 'message' => 'Sauce is required for this product']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// 初始化购物车结构
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ===== ✅ 添加主商品到购物车（使用 md5 构造唯一 key） =====
$key_data = [
    'product_id' => $product_id,
    'sauce' => $sauce,
    'comment' => $comment
];
$main_item_key = 'product_' . md5(json_encode($key_data));

if (isset($_SESSION['cart'][$main_item_key])) {
    $_SESSION['cart'][$main_item_key]['quantity'] += $quantity;
} else {
    $_SESSION['cart'][$main_item_key] = [
        'product_id' => $product_id,
        'quantity' => $quantity,
        'sauce' => $sauce,
        'comment' => $comment
    ];
}

// ===== ✅ 添加推荐商品（推荐项不区分酱料与备注） =====
foreach ($recommendations as $rec) {
    $rid = (int)$rec['id'];
    $rqty = max(1, (int)$rec['quantity']);
    if ($rid < 1) continue;

    $rec_key = "recommend_" . $rid;
    if (isset($_SESSION['cart'][$rec_key])) {
        $_SESSION['cart'][$rec_key]['quantity'] += $rqty;
    } else {
        $_SESSION['cart'][$rec_key] = [
            'product_id' => $rid,
            'quantity' => $rqty,
            'sauce' => '',
            'comment' => ''
        ];
    }
}

// ===== ✅ 计算购物车商品总数 =====
$total_count = 0;
foreach ($_SESSION['cart'] as $item) {
    $total_count += $item['quantity'];
}

echo json_encode(['success' => true, 'cart_count' => $total_count]);
?>
