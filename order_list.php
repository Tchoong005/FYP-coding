<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

function esc($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// 处理购物车数量增减
if (isset($_GET['action'], $_GET['key']) && ($_GET['type'] ?? '') === 'session') {
    $key = $_GET['key'];
    $cart = $_SESSION['cart'] ?? [];

    if (isset($cart[$key])) {
        switch ($_GET['action']) {
            case 'add':
                $cart[$key]['quantity']++;
                break;
            case 'subtract':
                if ($cart[$key]['quantity'] > 1) {
                    $cart[$key]['quantity']--;
                } else {
                    unset($cart[$key]);
                }
                break;
        }
        $_SESSION['cart'] = $cart;
    }
    header("Location: order_list.php");
    exit;
}

// 处理编辑订单项
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_item') {
    $key = $_POST['edit_key'] ?? '';
    $sauce = $_POST['edit_sauce'] ?? '';
    $comment = $_POST['edit_comment'] ?? '';

    $cart = $_SESSION['cart'] ?? [];
    if (isset($cart[$key])) {
        $cart[$key]['sauce'] = trim($sauce);
        $cart[$key]['comment'] = trim($comment);
        $_SESSION['cart'] = $cart;
        header("Location: order_list.php");
        exit;
    }
}

// 获取购物车数据
$cart = $_SESSION['cart'] ?? [];
$cart_count = array_sum(array_column($cart, 'quantity'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Your Order List</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary: #d6001c;
    --primary-dark: #b80018;
    --secondary: #ff9800;
    --light-bg: #f8f9fa;
    --dark-bg: #222;
    --text: #333;
    --text-light: #666;
    --border: #e0e0e0;
    --success: #4caf50;
    --warning: #ff9800;
    --danger: #f44336;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f7fa;
    color: var(--text);
    line-height: 1.6;
}

/* 🔝 Topbar - 与products_user.php相同 */
.topbar {
    background-color: var(--dark-bg);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    flex-wrap: wrap;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    position: sticky;
    top: 0;
    z-index: 100;
}

.topbar .logo {
    font-size: 24px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 10px;
}

.topbar .logo span {
    color: var(--primary);
}

.topbar .nav-links {
    display: flex;
    align-items: center;
    gap: 20px;
}

.topbar a {
    color: white;
    text-decoration: none;
    font-weight: bold;
    padding: 0 10px;
    line-height: 1.5;
    transition: color 0.3s;
}

.topbar a:hover {
    color: var(--primary);
}

.cart-icon {
    position: relative;
    cursor: pointer;
    font-size: 20px;
    padding: 0 10px;
    line-height: 1.5;
    user-select: none;
}

.cart-icon::after {
    content: attr(data-count);
    position: absolute;
    top: -6px;
    right: -10px;
    background: var(--primary);
    color: white;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 12px;
    font-weight: bold;
    min-width: 20px;
    text-align: center;
    box-sizing: border-box;
    display: inline-block;
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropbtn {
    background-color: transparent;
    color: white;
    font-weight: bold;
    padding: 0 10px;
    line-height: 1.5;
    font-size: inherit;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: color 0.3s;
}

.dropbtn:hover {
    color: var(--primary);
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #333;
    min-width: 180px;
    box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
    z-index: 1;
    border-radius: 4px;
    overflow: hidden;
    top: 100%;
    left: 0;
}

.dropdown-content a {
    color: white;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    font-size: 14px;
    border-bottom: 1px solid #444;
    transition: background-color 0.3s;
}

.dropdown-content a:hover {
    background-color: var(--primary);
}

.dropdown:hover .dropdown-content {
    display: block;
}

.dropdown-icon {
    font-size: 14px;
    transition: transform 0.3s;
}

.dropdown:hover .dropdown-icon {
    transform: rotate(180deg);
}

.active-link {
    position: relative;
}

.active-link::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 10px;
    right: 10px;
    height: 3px;
    background: var(--primary);
    border-radius: 2px;
}

/* 购物车页面特有样式 */
main {
    max-width: 900px;
    margin: 30px auto;
    padding: 0 20px;
}

h2 {
    color: #d6001c;
    font-size: 28px;
    margin-bottom: 30px;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 20px;
    border-radius: 10px;
    background: #fefefe;
    margin-bottom: 20px;
    box-shadow: 0 1px 6px rgba(0,0,0,0.1);
    flex-wrap: wrap;
}

.order-info {
    display: flex;
    align-items: center;
    gap: 18px;
    flex: 1;
    min-width: 250px;
}

.order-info img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
}

.order-details strong {
    font-size: 18px;
    color: #333;
    display: block;
}

.order-details span {
    display: block;
    font-size: 14px;
    color: #555;
    margin-top: 4px;
}

.order-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
    margin-top: 12px;
}

.order-actions a {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    background: #d6001c;
    color: white;
    font-weight: bold;
    font-size: 18px;
    border-radius: 50%;
    text-decoration: none;
    user-select: none;
}

.order-actions a.edit {
    width: auto;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 14px;
}

.qty {
    font-weight: bold;
    font-size: 16px;
    min-width: 28px;
    text-align: center;
    color: #222;
}

.total {
    font-size: 22px;
    font-weight: bold;
    text-align: right;
    margin-top: 30px;
    color: #333;
    border-top: 2px solid #ccc;
    padding-top: 15px;
}

.checkout-btn {
    text-align: right;
    margin-top: 20px;
}

.checkout-btn button {
    background: #d6001c;
    color: white;
    font-weight: bold;
    padding: 14px 30px;
    border-radius: 8px;
    font-size: 16px;
    border: none;
    cursor: pointer;
}

.checkout-btn button:disabled {
    background: #999;
    cursor: not-allowed;
}

.loading-overlay {
    position: fixed;
    top:0; left:0; right:0; bottom:0;
    background: rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: bold;
    display: none;
    z-index: 9999;
}

.footer {
    background-color: #eee;
    text-align: center;
    padding: 20px;
    font-size: 14px;
    margin-top: 40px;
}

/* 弹窗样式 */
#editModal {
    display: none;
    position: fixed;
    top:0; left:0; right:0; bottom:0;
    background: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

#editModal .modal-content {
    background: white;
    border-radius: 8px;
    padding: 20px 30px;
    width: 350px;
    box-sizing: border-box;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

#editModal h3 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #d6001c;
}

#editModal label {
    display: block;
    margin-top: 12px;
    font-weight: bold;
    font-size: 14px;
}

#editModal input[type="text"],
#editModal select,
#editModal textarea {
    width: 100%;
    padding: 8px;
    margin-top: 4px;
    box-sizing: border-box;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

#editModal textarea {
    resize: vertical;
}

#editModal .modal-buttons {
    margin-top: 20px;
    text-align: right;
}

#editModal button {
    background: #d6001c;
    color: white;
    border: none;
    padding: 10px 20px;
    font-weight: bold;
    border-radius: 6px;
    cursor: pointer;
    margin-left: 10px;
}

#editModal button.cancel-btn {
    background: #999;
}

/* Responsive design */
@media (max-width: 768px) {
    .topbar {
        padding: 12px 15px;
    }
    
    .nav-links {
        gap: 10px;
    }
    
    .order-grid {
        gap: 15px;
        padding: 0 10px;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .order-actions {
        width: 100%;
        justify-content: flex-end;
    }
}

@media (max-width: 480px) {
    .topbar .logo {
        font-size: 20px;
    }
    
    .order-item {
        width: 100%;
    }
}
</style>
</head>
<body>

<!-- 🔝 更新后的顶部导航栏 -->
<div class="topbar">
    <div class="logo"><i class="fas fa-hamburger"></i> Fast<span>Food</span> Express</div>
    <div class="nav-links">
        <a href="index_user.php">Home</a>
        
        <!-- Orders Dropdown -->
        <div class="dropdown">
            <button class="dropbtn">Orders <span class="dropdown-icon">▼</span></button>
            <div class="dropdown-content">
                <a href="products_user.php">Products</a>
                <a href="order_trace.php">Order Trace</a>
                <a href="order_history.php">Order History</a>
            </div>
        </div>
        
        <a href="profile.php">Profile</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="logout.php">Logout</a>
        <div class="cart-icon" data-count="<?php echo $cart_count; ?>" onclick="location.href='order_list.php'"><i class="fas fa-shopping-cart"></i></div>
    </div>
</div>

<main>
    <h2>Your Orders</h2>
    <?php if (empty($cart)): ?>
        <p>Your orderlist is empty.</p>
    <?php else: 
        $total = 0;
        foreach ($cart as $key => $item):
            $pid = (int)$item['product_id'];
            $quantity = (int)$item['quantity'];
            $sauce = $item['sauce'] ?? '';
            $comment = $item['comment'] ?? '';

            // 查询商品信息和分类判断是否饮料
            $sql = "SELECT name, price, image_url, category FROM products WHERE id = $pid LIMIT 1";
            $res = mysqli_query($conn, $sql);
            $product = mysqli_fetch_assoc($res);
            if (!$product) continue;

            $subtotal = $product['price'] * $quantity;
            $total += $subtotal;

            $is_beverage = strtolower($product['category']) === 'beverages' ? true : false;
    ?>
    <div class="order-item" data-key="<?php echo esc($key); ?>" data-is-beverage="<?php echo $is_beverage ? '1' : '0'; ?>">
        <div class="order-info">
            <img src="<?php echo esc($product['image_url']); ?>" alt="<?php echo esc($product['name']); ?>" />
            <div class="order-details">
                <strong><?php echo esc($product['name']); ?></strong>
                <span>RM <?php echo number_format($product['price'], 2); ?> x <?php echo $quantity; ?></span>
                <span><strong>Sauce:</strong> <?php echo esc($sauce ?: '-'); ?></span>
                <span><strong>Comment:</strong> <?php echo esc($comment ?: '-'); ?></span>
            </div>
        </div>
        <div class="order-actions">
            <a href="?action=subtract&type=session&key=<?php echo urlencode($key); ?>" title="Subtract Quantity">−</a>
            <div class="qty"><?php echo $quantity; ?></div>
            <a href="?action=add&type=session&key=<?php echo urlencode($key); ?>" title="Add Quantity">＋</a>
            <a href="javascript:void(0)" class="edit" onclick="editItem('<?php echo esc($key); ?>', '<?php echo esc(addslashes($sauce)); ?>', '<?php echo esc(addslashes($comment)); ?>')" title="Edit Item">Edit</a>
            <div style="min-width:80px; font-weight:bold; text-align:right;">RM <?php echo number_format($subtotal, 2); ?></div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="total">Total: RM <?php echo number_format($total, 2); ?></div>

    <div class="checkout-btn">
        <button id="checkoutBtn">Proceed to Checkout</button>
    </div>

    <?php endif; ?>
</main>

<footer class="footer">
    &copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.
</footer>

<!-- 编辑弹窗 -->
<div id="editModal">
    <div class="modal-content">
        <h3>Edit Order Item</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit_item" />
            <input type="hidden" id="edit_key" name="edit_key" />
            
            <div id="sauce-container">
                <label for="edit_sauce">Choose Sauce:</label>
                <select id="edit_sauce" required>
                    <option value="">-- Select Sauce --</option>
                    <option value="BBQ">BBQ</option>
                    <option value="Cheese">Cheese</option>
                    <option value="Sweet and Sour">Sweet and Sour</option>
                    <option value="Spicy Mayo">Spicy Mayo</option>
                </select>
            </div>

            <label for="edit_comment">Comment:</label>
            <textarea id="edit_comment" name="edit_comment" rows="3" placeholder="Add your comment (optional)"></textarea>

            <div class="modal-buttons">
                <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
                <button type="submit">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="loading-overlay" id="loadingOverlay">Loading...</div>

<script>
function escJS(str) {
    return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
}

function editItem(key, sauce, comment) {
    const orderItem = document.querySelector(`.order-item[data-key="${key}"]`);
    if (!orderItem) return alert('Item not found');

    // Fix: Check if data-is-beverage is '1' (true) or '0' (false)
    const isBeverage = orderItem.getAttribute('data-is-beverage') === '1';
    const form = document.querySelector('#editModal form');
    const sauceContainer = document.querySelector('#sauce-container');
    const sauceSelect = document.getElementById('edit_sauce');
    const sauceLabel = sauceContainer.querySelector('label');

    document.getElementById('edit_key').value = key;
    document.getElementById('edit_comment').value = comment;

    if (isBeverage) {
        // For beverage items, completely hide sauce selection
        sauceContainer.style.display = 'none';
        sauceSelect.removeAttribute('name');
        sauceSelect.removeAttribute('required');
        
        // Remove any existing hidden sauce input
        const existingHidden = form.querySelector('input[name="edit_sauce"][type="hidden"]');
        if (existingHidden) {
            form.removeChild(existingHidden);
        }
        
        // Add new empty hidden input for sauce
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'edit_sauce';
        hiddenInput.value = '';
        form.appendChild(hiddenInput);
    } else {
        // For non-beverage items, show sauce selection
        sauceContainer.style.display = 'block';
        sauceSelect.setAttribute('name', 'edit_sauce');
        sauceSelect.setAttribute('required', 'required');
        sauceSelect.value = sauce;
        
        // Remove hidden sauce input if exists
        const existingHidden = form.querySelector('input[name="edit_sauce"][type="hidden"]');
        if (existingHidden) {
            form.removeChild(existingHidden);
        }
    }

    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

function submitEditForm() {
    const form = document.querySelector('#editModal form');
    const isBeverage = document.querySelector('.order-item[data-key="' + document.getElementById('edit_key').value + '"]')
        .getAttribute('data-is-beverage') === 'true';
    
    if (isBeverage) {
        // For beverages, we need to add a hidden field with empty sauce
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'edit_sauce';
        hiddenInput.value = '';
        form.appendChild(hiddenInput);
    }
    
    return true; // Allow form submission
}

// Checkout AJAX handler
document.getElementById('checkoutBtn')?.addEventListener('click', function() {
    // 直接跳转到结账页面，不发送AJAX请求
    window.location.href = 'checkout.php';
});

// 添加当前页面活动链接指示器
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const navLinks = document.querySelectorAll('.topbar a, .dropdown-content a');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active-link');
        }
    });
});
</script>

</body>
</html>