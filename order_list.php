<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$alertMessage = '';
if (isset($_GET['msg'])) {
    $alertMessage = htmlspecialchars($_GET['msg']);
}

// 操作处理（增、减、删）
if (isset($_GET['action'], $_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);

    if (isset($_SESSION['cart'][$product_id])) {
        $item = $_SESSION['cart'][$product_id];

        // 获取当前库存
        $res = mysqli_query($conn, "SELECT stock FROM products WHERE id = $product_id");
        $row = mysqli_fetch_assoc($res);
        $stock = intval($row['stock']);

        if ($_GET['action'] === 'add') {
            if ($item['quantity'] < $stock) {
                $_SESSION['cart'][$product_id]['quantity']++;
            } else {
                header("Location: order_list.php?msg=Maximum stock reached.");
                exit();
            }
        } elseif ($_GET['action'] === 'subtract') {
            if ($item['quantity'] > 1) {
                $_SESSION['cart'][$product_id]['quantity']--;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        } elseif ($_GET['action'] === 'remove') {
            unset($_SESSION['cart'][$product_id]);
        }
    }

    header("Location: order_list.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Order List</title>
  <style>
    * { box-sizing: border-box; }
    body, html {
      margin: 0; padding: 0;
      font-family: Arial, sans-serif;
      background-color: #fff;
    }
    .topbar {
      background-color: #222; color: white;
      display: flex; justify-content: space-between; align-items: center;
      padding: 16px 32px; font-size: 18px;
    }
    .topbar .logo {
      font-size: 22px; font-weight: bold;
      display: flex; align-items: center; gap: 8px;
    }
    .topbar nav {
      display: flex; align-items: center; gap: 24px;
    }
    .topbar a {
      color: white; text-decoration: none; font-weight: bold;
    }
    .cart-icon {
      position: relative; font-size: 20px;
      text-decoration: none; color: white;
    }
    .cart-icon::after {
      content: attr(data-count);
      position: absolute; top: -8px; right: -10px;
      background: red; color: white; border-radius: 50%;
      padding: 2px 6px; font-size: 12px;
    }

    .order-list {
      flex: 1; /* 主体自动撑开 */
      max-width: 1000px;
      margin: 30px auto; padding: 20px;
    }
    .order-list h2 {
      color: #d6001c; margin-bottom: 25px; font-size: 26px;
    }
    .order-item {
      display: flex; justify-content: space-between;
      gap: 16px; align-items: center;
      padding: 20px; border-bottom: 1px solid #ccc;
      flex-wrap: wrap; background-color: #fdfdfd;
      border-radius: 10px; margin-bottom: 16px;
    }
    .order-info {
      display: flex; align-items: center; gap: 20px; flex: 1;
    }
    .order-info img {
      width: 100px; height: 100px;
      object-fit: cover; border-radius: 10px;
      box-shadow: 0 1px 6px rgba(0,0,0,0.08);
    }
    .order-details {
      display: flex; flex-direction: column; max-width: 400px;
    }
    .order-details strong {
      font-size: 18px; font-weight: bold; color: #333;
    }
    .order-details span {
      font-size: 16px; color: #666; margin-top: 4px;
    }
    .order-actions {
      display: flex; align-items: center; gap: 12px;
    }
    .order-actions a {
      text-decoration: none; color: white;
      border-radius: 50%; font-weight: bold;
      font-size: 18px; display: inline-flex;
      align-items: center; justify-content: center;
    }
    .order-actions a.triangle {
      width: 36px; height: 36px; background: #d6001c;
    }
    .order-actions a.delete {
      font-size: 14px; background: #d6001c; padding: 6px;
    }
    .order-actions a.edit-btn {
      background-color: #007bff; padding: 6px 14px;
      font-size: 14px; border-radius: 8px;
    }
    .order-actions .qty {
      font-size: 18px; font-weight: bold;
      min-width: 24px; text-align: center; color: #222;
    }
    .total {
      text-align: right; font-size: 20px;
      font-weight: bold; margin-top: 30px;
      padding-top: 12px; border-top: 2px solid #ccc; color: #333;
    }
    .checkout-btn {
      display: block; text-align: right; margin-top: 20px;
    }
    .checkout-btn a {
      background: #d6001c; color: white; padding: 12px 24px;
      border-radius: 8px; text-decoration: none; font-weight: bold;
    }
    .footer {
      background-color: #f2f2f2;
  padding: 20px;
  text-align: center;
  font-size: 14px;
  color: #333;
    }

    /* Alert box */
    #customAlert {
      position: fixed;
      top: 20px;
      right: 20px;
      background-color: #17a2b8;
      color: white;
      padding: 14px 20px;
      border-radius: 8px;
      font-weight: bold;
      display: none;
      z-index: 9999;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    .wrapper {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
  </style>
<body>

<div id="customAlert"></div>

<div class="wrapper">
  <div class="topbar">
    <div class="logo">🍔 FastFood Express</div>
    <nav>
      <a href="index_user.html">Home</a>
      <a href="order_now.php">Order Now</a>
      <a href="product_user.html">Products</a>
      <a href="user_about.html">About</a>
      <a href="contact_user.html">Contact</a>
      <a href="login.php">Login</a>
      <a href="order_list.php" class="cart-icon" data-count="<?php echo array_sum(array_column($_SESSION['cart'], 'quantity')); ?>">🛒</a>
    </nav>
  </div>

  <main class="order-list">
    <h2>Your Order List</h2>

    <?php
    $total = 0;
    if (empty($_SESSION['cart'])) {
        echo "<p>Your cart is empty.</p>";
    } else {
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
            $message = isset($item['message']) ? htmlspecialchars($item['message']) : '';
            $sauces = isset($item['sauces']) 
                      ? (is_array($item['sauces']) ? implode(', ', $item['sauces']) : htmlspecialchars($item['sauces'])) 
                      : 'None';

            // 加引号以防SQL语法错误
            $res = mysqli_query($conn, "SELECT * FROM products WHERE id = " . intval($product_id));
            if ($product = mysqli_fetch_assoc($res)) {
                $subtotal = $product['price'] * $quantity;
                $total += $subtotal;

                echo '
                <div class="order-item">
                  <div class="order-info">
                    <img src="' . htmlspecialchars($product['image_url']) . '" alt="' . htmlspecialchars($product['name']) . '">
                    <div class="order-details">
                      <strong>' . htmlspecialchars($product['name']) . '</strong>
                      <span>RM ' . number_format($product['price'], 2) . '</span>
                      <span>Sauce: ' . htmlspecialchars($sauces) . '</span>
                      <span>Note: ' . $message . '</span>
                    </div>
                  </div>
                  <div class="order-actions">';
                
                if ($quantity > 1) {
                    echo '<a href="order_list.php?action=subtract&product_id=' . $product_id . '" class="triangle">▼</a>';
                } else {
                    echo '<a href="order_list.php?action=remove&product_id=' . $product_id . '" class="delete">🗑️</a>';
                }

                echo '<span class="qty">' . $quantity . '</span>
                      <a href="order_list.php?action=add&product_id=' . $product_id . '" class="triangle">▲</a>
                      <a href="#" class="edit-btn">Edit</a>
                  </div>
                </div>';
            }
        }

        echo '<div class="total">Total: RM ' . number_format($total, 2) . '</div>';
        echo '<div class="checkout-btn"><a href="checkout.php">Proceed to Checkout</a></div>';
    }
    ?>
  </main>

  <footer class="footer">
    © 2025 FastFood Express. All rights reserved.
  </footer>
</div>

<script>
function showCustomAlert(message) {
  const alertBox = document.getElementById('customAlert');
  alertBox.textContent = message;
  alertBox.style.display = 'block';
  setTimeout(() => { alertBox.style.display = 'none'; }, 3000);
}

<?php if (!empty($alertMessage)): ?>
showCustomAlert("<?php echo $alertMessage; ?>");
<?php endif; ?>
</script>
</body>
</html>
