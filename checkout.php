<?php
session_start();
include 'db.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 修改点1：确保session已正确启动后再生成CSRF令牌
if (empty($_SESSION)) {
    session_regenerate_id(true); // 防止会话固定攻击
}

// 修改点2：只在令牌不存在时生成新令牌
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
date_default_timezone_set('Asia/Kuala_Lumpur');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize session data
$_SESSION['delivery_method'] = $_SESSION['delivery_method'] ?? 'delivery';
$_SESSION['payment_method'] = $_SESSION['payment_method'] ?? 'credit_card';
$_SESSION['checkout_info'] = $_SESSION['checkout_info'] ?? [];

// Initialize variables
$user_id = (int)$_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];
$error = '';
$success = '';
$show_message = false;

// Redirect if cart is empty
if (empty($cart)) {
    header("Location: order_list.php");
    exit;
}

// Get user info using prepared statement
$user_sql = "SELECT username, phone, address, postcode, city, state FROM customers WHERE id = ? LIMIT 1";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_stmt->close();

// Check if user info is complete
$user_info_complete = true;
if (empty($user_data['username']) || empty($user_data['phone']) || empty($user_data['address']) || 
    empty($user_data['postcode']) || empty($user_data['city']) || empty($user_data['state'])) {
    $user_info_complete = false;
}

// Calculate total price and get product info
$total_price = 0;
$product_info = [];
$has_stock_issues = false;
foreach ($cart as $item) {
    $pid = (int)$item['product_id'];
    if (!isset($product_info[$pid])) {
        $sql = "SELECT id, name, price, image_url, stock_quantity FROM products WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if (!$res || $res->num_rows === 0) {
            unset($_SESSION['cart'][$pid]);
            header("Location: checkout.php");
            exit;
        }
        $product_info[$pid] = $res->fetch_assoc();
        $stmt->close();
    }
    $total_price += $product_info[$pid]['price'] * $item['quantity'];
    
    // Check stock issues
    if ($product_info[$pid]['stock_quantity'] < $item['quantity']) {
        $has_stock_issues = true;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token. Please try submitting the form again.";
        $show_message = true;
    } else {
        // Regenerate CSRF token after successful verification
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Update delivery method
        if (isset($_POST['delivery_method'])) {
            $_SESSION['delivery_method'] = $_POST['delivery_method'];
        }
        
        // Update recipient info
        if (isset($_POST['recipient_name'])) {
            $_SESSION['checkout_info'] = [
                'recipient_name' => $_POST['recipient_name'],
                'recipient_phone' => $_POST['recipient_phone'],
                'recipient_address' => $_POST['recipient_address'],
                'recipient_postcode' => $_POST['recipient_postcode'],
                'recipient_city' => $_POST['recipient_city'],
                'recipient_state' => $_POST['recipient_state']
            ];
        }
        
        // Handle order submission
        if (isset($_POST['submit_payment'])) {
            // Get payment method
            $payment_method = isset($_POST['payment_method']) 
                ? $_POST['payment_method'] 
                : ($_SESSION['payment_method'] ?? 'credit_card');
            
            // Get recipient info
            $recipient_name = $_SESSION['checkout_info']['recipient_name'] ?? $user_data['username'] ?? '';
            $recipient_phone = $_SESSION['checkout_info']['recipient_phone'] ?? $user_data['phone'] ?? '';
            $recipient_address = $_SESSION['checkout_info']['recipient_address'] ?? $user_data['address'] ?? '';
            $recipient_postcode = $_SESSION['checkout_info']['recipient_postcode'] ?? $user_data['postcode'] ?? '';
            $recipient_city = $_SESSION['checkout_info']['recipient_city'] ?? $user_data['city'] ?? '';
            $recipient_state = $_SESSION['checkout_info']['recipient_state'] ?? $user_data['state'] ?? '';
            $delivery_method = $_SESSION['delivery_method'] ?? 'delivery';

            // For dine-in orders, don't store address info
            $full_address = '';
            if ($delivery_method === 'delivery') {
                $full_address = $recipient_address . ', ' . $recipient_postcode . ' ' . $recipient_city . ', ' . $recipient_state;
            }

            // Validate recipient info
            $info_error = '';
            if (empty($recipient_name)) {
                $info_error = "Recipient name is required";
            } elseif (empty($recipient_phone)) {
                $info_error = "Recipient phone is required";
            } elseif ($delivery_method === 'delivery' && (empty($recipient_address) || empty($recipient_postcode) || empty($recipient_city) || empty($recipient_state))) {
                $info_error = "All address fields are required for delivery";
            }
            
            if (!empty($info_error)) {
                $error = $info_error;
                $show_message = true;
            } else {
                // Begin transaction
                mysqli_begin_transaction($conn);

                try {
                    // Check stock availability (for all payment methods)
                    $insufficient_stock = [];
                    foreach ($cart as $item) {
                        $pid = (int)$item['product_id'];
                        $quantity = (int)$item['quantity'];
                        
                        if (!isset($product_info[$pid])) {
                            $sql = "SELECT stock_quantity FROM products WHERE id = ? LIMIT 1";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $pid);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            if (!$res || $res->num_rows === 0) {
                                throw new Exception("Invalid product ID: $pid");
                            }
                            $product_info[$pid] = $res->fetch_assoc();
                            $stmt->close();
                        }
                        
                        if ($product_info[$pid]['stock_quantity'] < $quantity) {
                            $insufficient_stock[] = $product_info[$pid]['name'] . 
                                " (Available: {$product_info[$pid]['stock_quantity']}, Requested: $quantity)";
                        }
                    }
                    
                    if (!empty($insufficient_stock)) {
                        throw new Exception("Insufficient stock: " . implode(", ", $insufficient_stock));
                    }

                    // Create order
                    $now = date('Y-m-d H:i:s');
                    $delivery_fee = ($delivery_method === 'delivery') ? 6.00 : 0.00;
                    $final_total = $total_price + $delivery_fee;
                    
                    $payment_status = 'pending';
                    if ($payment_method === 'cash') {
                        $payment_status = 'cash_on_delivery';
                    } elseif ($payment_method === 'counter') {
                        $payment_status = 'pay_at_counter';
                    }
                    
                    $order_status = 'pending';
                    
                    // Set is_valid based on payment method and CSRF validation
                    // For credit card, set to 0 until payment completes
                    $is_valid = ($payment_method === 'credit_card') ? 0 : 1;
                    
                    // Create order using prepared statement
                    $sql_order = "INSERT INTO orders (user_id, recipient_name, recipient_phone, recipient_address, 
                                  delivery_method, total_price, delivery_fee, final_total, payment_method, 
                                  payment_status, order_status, created_at, is_valid)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                    $stmt = $conn->prepare($sql_order);
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("issssddsssssi", 
                        $user_id, 
                        $recipient_name, 
                        $recipient_phone,
                        $full_address, 
                        $delivery_method, 
                        $total_price, 
                        $delivery_fee, 
                        $final_total, 
                        $payment_method, 
                        $payment_status, 
                        $order_status, 
                        $now,
                        $is_valid
                    );
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                    
                    $order_id = $conn->insert_id;
                    $stmt->close();

                    // Add order items
                    foreach ($cart as $item) {
                        $pid = (int)$item['product_id'];
                        if (!isset($product_info[$pid])) {
                            $sql = "SELECT id, name, price FROM products WHERE id = ? LIMIT 1";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $pid);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            if (!$res || $res->num_rows === 0) {
                                throw new Exception("Invalid product ID: $pid");
                            }
                            $product_info[$pid] = $res->fetch_assoc();
                            $stmt->close();
                        }
                        
                        $quantity = (int)$item['quantity'];
                        $sauce = isset($item['sauce']) ? $item['sauce'] : '';
                        $comment = isset($item['comment']) ? $item['comment'] : '';
                        $price = $product_info[$pid]['price'];

                        $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, sauce, comment, price)
                                     VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt_item = $conn->prepare($sql_item);
                        if (!$stmt_item) {
                            throw new Exception("Item prepare failed: " . $conn->error);
                        }
                        
                        $stmt_item->bind_param("iiissd", $order_id, $pid, $quantity, $sauce, $comment, $price);
                        
                        if (!$stmt_item->execute()) {
                            throw new Exception("Item execute failed: " . $stmt_item->error);
                        }
                        $stmt_item->close();
                        
                        // Only reduce stock for non-credit card payments
                        if ($payment_method !== 'credit_card') {
                            $sql_update = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                            $stmt_update = $conn->prepare($sql_update);
                            if (!$stmt_update) {
                                throw new Exception("Stock update prepare failed: " . $conn->error);
                            }
                            
                            $stmt_update->bind_param("ii", $quantity, $pid);
                            if (!$stmt_update->execute()) {
                                throw new Exception("Stock update failed: " . $stmt_update->error);
                            }
                            $stmt_update->close();
                        }
                    }

                    // Commit transaction
                    mysqli_commit($conn);
                    
                    // Redirect based on payment method
                    if ($payment_method === 'credit_card') {
                        // Save order ID for payment process
                        $_SESSION['current_order_id'] = $order_id;
                        header("Location: payment.php?order_id=".$order_id);
                        exit;
                    } else {
                        // Clear cart for non-credit card payments
                        unset($_SESSION['cart']);
                        header("Location: index_user.php?order_success=".$order_id);
                        exit;
                    }

                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    $error = "Order processing failed: " . $e->getMessage();
                    $show_message = true;
                    error_log("Order Error: " . $e->getMessage() . "\nSQL Error: " . ($conn->error ?? ''));
                }
            }
        }
    }
}

// Get current checkout info
$recipient_name = $_SESSION['checkout_info']['recipient_name'] ?? $user_data['username'] ?? '';
$recipient_phone = $_SESSION['checkout_info']['recipient_phone'] ?? $user_data['phone'] ?? '';
$recipient_address = $_SESSION['checkout_info']['recipient_address'] ?? $user_data['address'] ?? '';
$recipient_postcode = $_SESSION['checkout_info']['recipient_postcode'] ?? $user_data['postcode'] ?? '';
$recipient_city = $_SESSION['checkout_info']['recipient_city'] ?? $user_data['city'] ?? '';
$recipient_state = $_SESSION['checkout_info']['recipient_state'] ?? $user_data['state'] ?? '';
$delivery_method = $_SESSION['delivery_method'] ?? 'delivery';
$payment_method = $_SESSION['payment_method'] ?? 'credit_card';

// Calculate cart count
$cart_count = array_sum(array_column($cart, 'quantity'));

// Calculate totals
$delivery_fee = ($delivery_method === 'delivery') ? 6.00 : 0.00;
$final_total = $total_price + $delivery_fee;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout - FastFood Express</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: var(--text);
            line-height: 1.6;
        }

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

        main {
            max-width: 1200px; 
            margin: 30px auto; 
            padding: 0 20px;
            display: flex;
            gap: 30px;
        }
        
        .left-column {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .right-column {
            width: 400px;
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        h2 { 
            color: #d6001c; 
            font-size: 24px; 
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        h3 {
            font-size: 18px;
            color: #555;
            margin-top: 20px;
            position: relative;
            padding-left: 25px;
        }
        
        .h3-icon {
            position: absolute;
            left: 0;
            top: 2px;
            color: #d6001c;
        }
        
        .order-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 4px;
            object-fit: cover;
            border: 1px solid #eee;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #d6001c;
            font-weight: bold;
        }
        
        .item-extras {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }
        
        .delivery-method {
            display: flex;
            gap: 15px;
            margin: 15px 0;
        }
        
        .delivery-option {
            flex: 1;
            padding: 15px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .delivery-option.selected {
            border-color: #d6001c;
            background: #fff0f0;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(214, 0, 28, 0.1);
        }
        
        .delivery-option i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #d6001c;
        }
        
        .form-group {
            margin-bottom: 15px;
            position: relative;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="tel"],
        textarea,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: inherit;
            transition: border 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="tel"]:focus,
        textarea:focus,
        select:focus {
            border-color: #d6001c;
            outline: none;
            box-shadow: 0 0 0 3px rgba(214, 0, 28, 0.1);
        }
        
        textarea {
            height: 80px;
            resize: vertical;
        }
        
        .payment-option {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-option.selected {
            border-color: #d6001c;
            background: #fff0f0;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(214, 0, 28, 0.1);
        }
        
        .payment-option input {
            margin-right: 10px;
        }
        
        .payment-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        
        .payment-icon {
            margin-right: 10px;
            font-size: 24px;
            width: 30px;
            text-align: center;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-total {
            font-weight: bold;
            font-size: 18px;
            color: #d6001c;
            padding-top: 10px;
        }
        
        .btn {
            background: #d6001c;
            color: white;
            border: none;
            padding: 15px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn:hover {
            background: #b50018;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(214, 0, 28, 0.2);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-update {
            background: #666;
            margin-top: 10px;
        }
        
        .btn-update:hover {
            background: #555;
        }
        
        .error {
            color: #d6001c;
            background: #fff0f0;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            padding-right: 40px;
        }
        
        .success {
            color: #4caf50;
            background: #f0fff0;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            padding-right: 40px;
        }
        
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            cursor: pointer;
            font-size: 20px;
            font-weight: bold;
        }
        
        .footer {
            background-color: #eee;
            text-align: center;
            padding: 20px;
            font-size: 14px;
            margin-top: 40px;
            color: #666;
        }
        
        .message-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .message-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .message-box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .message-box h3 {
            color: #d6001c;
            margin-bottom: 20px;
            font-size: 22px;
            padding-left: 0;
        }
        
        .message-box p {
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .spinner {
            border: 4px solid rgba(0,0,0,0.1);
            border-radius: 50%;
            border-top: 4px solid #d6001c;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .btn-auto-fill {
            background: #f0f0f0;
            border: 1px solid #ddd;
            color: #555;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .btn-auto-fill:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }
        
        .secure-badge {
            background: #4caf50;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .header-security {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .security-info {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 13px;
            color: #666;
        }
        
        .address-row {
            display: flex;
            gap: 15px;
        }
        
        .address-row .form-group {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .topbar {
                padding: 12px 15px;
            }
            
            .nav-links {
                gap: 10px;
            }
            
            main {
                flex-direction: column;
            }
            
            .right-column {
                width: 100%;
            }
            
            .delivery-method {
                flex-direction: column;
            }
            
            .address-row {
                flex-direction: column;
                gap: 0;
            }
        }
        
        @media (max-width: 480px) {
            .topbar .logo {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
<!-- 🔝 Top Navigation -->
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

<!-- Message Overlay -->
<div class="message-overlay" id="messageOverlay">
    <div class="message-box">
        <div class="spinner"></div>
        <h3 id="messageTitle">Processing Order</h3>
        <p id="messageText">Please wait while we process your order...</p>
    </div>
</div>

<main>
    <div class="left-column">
        <h2>Your Order</h2>
        
        <?php foreach ($cart as $item): ?>
            <?php 
            $pid = (int)$item['product_id'];
            if (isset($product_info[$pid])): 
                $product = $product_info[$pid]; 
            ?>
                <div class="order-item">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="item-image">
                    <div class="item-details">
                        <div class="item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="item-price">RM <?php echo number_format($product['price'], 2); ?></div>
                        <div class="item-extras">
                            Quantity: <?php echo $item['quantity']; ?>
                            <?php if (!empty($item['sauce'])): ?>
                                <br>Sauce: <?php echo htmlspecialchars($item['sauce']); ?>
                            <?php endif; ?>
                            <?php if (!empty($item['comment'])): ?>
                                <br>Remark: <?php echo htmlspecialchars($item['comment']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="item-total">
                        RM <?php echo number_format($product['price'] * $item['quantity'], 2); ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="right-column">
        <h2>Payment Details</h2>
        
        <?php if (!empty($error) && $show_message): ?>
            <div class="error" id="errorMessage">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
                <span class="close-btn">&times;</span>
            </div>
        <?php endif; ?>

        <form id="paymentForm" method="POST" action="checkout.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <h3>
                <i class="fas fa-truck h3-icon"></i>
                Order Method
            </h3>
            <div class="delivery-method">
                <label class="delivery-option <?php echo $delivery_method === 'delivery' ? 'selected' : ''; ?>">
                    <input type="radio" name="delivery_method" value="delivery" 
                        <?php echo $delivery_method === 'delivery' ? 'checked' : ''; ?> hidden>
                    <i class="fas fa-truck"></i>
                    <div>Delivery</div>
                </label>
                <label class="delivery-option <?php echo $delivery_method === 'dine_in' ? 'selected' : ''; ?>">
                    <input type="radio" name="delivery_method" value="dine_in" 
                        <?php echo $delivery_method === 'dine_in' ? 'checked' : ''; ?> hidden>
                    <i class="fas fa-store"></i>
                    <div>Dine-in</div>
                </label>
            </div>

            <h3>
                <i class="fas fa-user h3-icon"></i>
                Recipient Information
            </h3>
            <div class="form-group">
                <button type="button" id="fill-address" class="btn-auto-fill">
                    <i class="fas fa-user-check"></i> Use My Information
                </button>
            </div>
            <div class="form-group">
                <label for="recipient_name">Full Name</label>
                <input type="text" id="recipient_name" name="recipient_name" 
                    value="<?php echo htmlspecialchars($recipient_name); ?>" required>
            </div>
            <div class="form-group">
                <label for="recipient_phone">Phone Number</label>
                <input type="tel" id="recipient_phone" name="recipient_phone" 
                    value="<?php echo htmlspecialchars($recipient_phone); ?>" required
                    pattern="[0-9]{10,15}" title="10-15 digit phone number">
            </div>
            <div class="form-group" id="address-field" 
                style="<?php echo $delivery_method === 'dine_in' ? 'display: none;' : ''; ?>">
                <label for="recipient_address">Street Address</label>
                <textarea id="recipient_address" name="recipient_address" 
                    <?php echo $delivery_method === 'dine_in' ? '' : 'required'; ?>><?php echo htmlspecialchars($recipient_address); ?></textarea>
            </div>
            <div class="address-row" id="address-details" style="<?php echo $delivery_method === 'dine_in' ? 'display: none;' : ''; ?>">
                <div class="form-group">
                    <label for="recipient_postcode">Postcode</label>
                    <input type="text" id="recipient_postcode" name="recipient_postcode" 
                        value="<?php echo htmlspecialchars($recipient_postcode); ?>" 
                        <?php echo $delivery_method === 'dine_in' ? '' : 'required'; ?>
                        pattern="\d{5}" title="5-digit postcode">
                </div>
                <div class="form-group">
                    <label for="recipient_city">City</label>
                    <input type="text" id="recipient_city" name="recipient_city" 
                        value="<?php echo htmlspecialchars($recipient_city); ?>" 
                        <?php echo $delivery_method === 'dine_in' ? '' : 'required'; ?>>
                </div>
                <div class="form-group">
                    <label for="recipient_state">State</label>
                    <select name="recipient_state" id="recipient_state" <?php echo $delivery_method === 'dine_in' ? '' : 'required'; ?>>
                        <option value="">-- Please select state --</option>
                        <?php
                        $states = [
                            "Johor", "Kedah", "Kelantan", "Melaka", "Negeri Sembilan", 
                            "Pahang", "Pulau Pinang", "Perak", "Perlis", "Sabah", 
                            "Sarawak", "Selangor", "Terengganu", 
                            "Kuala Lumpur", "Labuan", "Putrajaya"
                        ];
                        foreach ($states as $state_option) {
                            $selected = ($recipient_state === $state_option) ? "selected" : "";
                            echo "<option value=\"$state_option\" $selected>$state_option</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <h3>
                <i class="fas fa-credit-card h3-icon"></i>
                Payment Method
            </h3>
            <label class="payment-option <?php echo $payment_method === 'credit_card' ? 'selected' : ''; ?>">
                <input type="radio" name="payment_method" value="credit_card" 
                    <?php echo $payment_method === 'credit_card' ? 'checked' : ''; ?> hidden>
                <span class="payment-icon">💳</span>
                <span>Credit/Debit Card</span>
            </label>
            <label class="payment-option <?php echo $payment_method === 'cash' ? 'selected' : ''; ?> 
                  <?php echo $delivery_method === 'delivery' ? '' : 'disabled'; ?>">
                <input type="radio" name="payment_method" value="cash" 
                    <?php echo $payment_method === 'cash' ? 'checked' : ''; ?>
                    <?php echo $delivery_method === 'delivery' ? '' : 'disabled'; ?> hidden>
                <span class="payment-icon">💵</span>
                <span>Cash on Delivery</span>
            </label>
            <label class="payment-option <?php echo $payment_method === 'counter' ? 'selected' : ''; ?> 
                  <?php echo $delivery_method === 'dine_in' ? '' : 'disabled'; ?>">
                <input type="radio" name="payment_method" value="counter" 
                    <?php echo $payment_method === 'counter' ? 'checked' : ''; ?>
                    <?php echo $delivery_method === 'dine_in' ? '' : 'disabled'; ?> hidden>
                <span class="payment-icon">🏪</span>
                <span>Pay at Counter</span>
            </label>
            
            <h3>
                <i class="fas fa-receipt h3-icon"></i>
                Order Summary
            </h3>
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>RM <?php echo number_format($total_price, 2); ?></span>
            </div>
            <div class="summary-row" id="delivery-fee-row">
                <span>Delivery Fee:</span>
                <span><?php echo $delivery_method === 'delivery' ? 'RM 6.00' : 'Free'; ?></span>
            </div>
            <div class="summary-row summary-total">
                <span>Total:</span>
                <span>RM <?php echo number_format($final_total, 2); ?></span>
            </div>
            
            <div class="header-security">
                <div class="security-info">
                    <i class="fas fa-lock"></i>
                    <span>Secure Payment</span>
                </div>
                <div class="security-info">
                    <i class="fas fa-shield-alt"></i>
                    <span>SSL Encryption</span>
                </div>
            </div>

            <button type="submit" name="submit_payment" class="btn" id="submitBtn">
                <i class="fas fa-lock"></i>
                <?php echo ($payment_method === 'credit_card') ? 'Proceed to Payment' : 'Complete Order'; ?>
            </button>
        </form>
    </div>
</main>

<footer class="footer">
    &copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Auto-fill address
        $('#fill-address').click(function() {
            var name = <?php echo json_encode($user_data['username'] ?? ''); ?>;
            var phone = <?php echo json_encode($user_data['phone'] ?? ''); ?>;
            var address = <?php echo json_encode($user_data['address'] ?? ''); ?>;
            var postcode = <?php echo json_encode($user_data['postcode'] ?? ''); ?>;
            var city = <?php echo json_encode($user_data['city'] ?? ''); ?>;
            var state = <?php echo json_encode($user_data['state'] ?? ''); ?>;
            
            // Check if user info is complete
            if (!name || !phone || !address || !postcode || !city || !state) {
                if (confirm('Your profile information is incomplete. Would you like to update your profile now?')) {
                    window.location.href = 'profile.php';
                }
            } else {
                $('#recipient_name').val(name);
                $('#recipient_phone').val(phone);
                $('#recipient_address').val(address);
                $('#recipient_postcode').val(postcode);
                $('#recipient_city').val(city);
                $('#recipient_state').val(state);
            }
        });
        
        // Update delivery method and UI
        $('input[name="delivery_method"]').change(function() {
            $('.delivery-option').removeClass('selected');
            $(this).closest('.delivery-option').addClass('selected');
            
            // Toggle address fields display
            if ($(this).val() === 'delivery') {
                $('#address-field').show();
                $('#address-details').show();
                $('#recipient_address').prop('required', true);
                $('#recipient_postcode').prop('required', true);
                $('#recipient_city').prop('required', true);
                $('#recipient_state').prop('required', true);
            } else {
                $('#address-field').hide();
                $('#address-details').hide();
                $('#recipient_address').prop('required', false);
                $('#recipient_postcode').prop('required', false);
                $('#recipient_city').prop('required', false);
                $('#recipient_state').prop('required', false);
            }
            
            // Update payment options
            $('.payment-option').removeClass('selected disabled');
            $('.payment-option input').prop('disabled', false);
            
            if ($(this).val() === 'delivery') {
                // Disable "pay at counter" for delivery
                $('.payment-option:has(input[value="counter"])').addClass('disabled')
                    .find('input').prop('disabled', true);
                    
                // If currently selected counter pay, auto-select first available payment method
                if ($('input[name="payment_method"]:checked').val() === 'counter') {
                    $('input[name="payment_method"][value="credit_card"]').prop('checked', true).trigger('change');
                    $('.payment-option:has(input[value="credit_card"])').addClass('selected');
                }
            } else {
                // Disable "cash on delivery" for dine-in
                $('.payment-option:has(input[value="cash"])').addClass('disabled')
                    .find('input').prop('disabled', true);
                    
                // If currently selected cash payment, auto-select first available payment method
                if ($('input[name="payment_method"]:checked').val() === 'cash') {
                    $('input[name="payment_method"][value="credit_card"]').prop('checked', true).trigger('change');
                    $('.payment-option:has(input[value="credit_card"])').addClass('selected');
                }
            }
            
            // Update total display
            const subtotal = <?php echo $total_price; ?>;
            const deliveryFee = $(this).val() === 'delivery' ? 6.00 : 0.00;
            $('#delivery-fee-row span:last-child').text(
                deliveryFee > 0 ? 'RM ' + deliveryFee.toFixed(2) : 'Free'
            );
            $('.summary-total span:last-child').text(
                'RM ' + (subtotal + deliveryFee).toFixed(2)
            );
            
            // Update button text
            updateSubmitButtonText();
        });
        
        // Payment method selection
        $('input[name="payment_method"]').change(function() {
            $('.payment-option').removeClass('selected');
            $(this).closest('.payment-option').addClass('selected');
            updateSubmitButtonText();
        });
        
        // Update submit button text based on selection
        function updateSubmitButtonText() {
            const paymentMethod = $('input[name="payment_method"]:checked').val();
            
            if (paymentMethod === 'credit_card') {
                $('#submitBtn').html('<i class="fas fa-lock"></i> Proceed to Payment');
            } else {
                $('#submitBtn').html('<i class="fas fa-check-circle"></i> Complete Order');
            }
        }
        
        // Handle form submission
        $('#paymentForm').on('submit', function(e) {
            // Validate phone format
            const phone = $('#recipient_phone').val();
            if (!/^\d{10,15}$/.test(phone)) {
                alert('Please enter a valid 10-15 digit phone number');
                e.preventDefault();
                return;
            }
            
            // For delivery, validate address fields
            if ($('input[name="delivery_method"]:checked').val() === 'delivery') {
                const postcode = $('#recipient_postcode').val();
                if (!/^\d{5}$/.test(postcode)) {
                    alert('Please enter a valid 5-digit postcode');
                    e.preventDefault();
                    return;
                }
                
                if (!$('#recipient_address').val() || !$('#recipient_city').val() || !$('#recipient_state').val()) {
                    alert('All address fields are required for delivery');
                    e.preventDefault();
                    return;
                }
            }
            
            const paymentMethod = $('input[name="payment_method"]:checked').val();
            const overlay = $('#messageOverlay');
            
            // Show appropriate message
            if (paymentMethod === 'credit_card') {
                $('#messageTitle').text('Redirecting to Payment');
                $('#messageText').text('You selected Credit/Debit Card payment. Redirecting to our secure payment gateway...');
            } else if (paymentMethod === 'cash') {
                $('#messageTitle').text('Processing Order');
                $('#messageText').text('Your order is being processed. You will pay with cash upon delivery.');
            } else if (paymentMethod === 'counter') {
                $('#messageTitle').text('Processing Order');
                $('#messageText').text('Your order is being processed. Please pay at the counter when you arrive.');
            }
            
            overlay.addClass('active');
            
            // Ensure payment method is included in form data
            if (!$('input[name="payment_method"]').is(':checked')) {
                $(this).append('<input type="hidden" name="payment_method" value="' + 
                    ($('input[name="payment_method"]').first().val()) + '">');
            }
            
            // If credit card payment, temporarily clear cart display
            if (paymentMethod === 'credit_card') {
                $('.cart-icon').attr('data-count', '0');
            }
            
            // Submit form
            setTimeout(() => {
                this.submit();
            }, 1500);
        });
        
        // Auto-hide messages after 3 seconds
        setTimeout(function() {
            $('.error, .success').fadeOut('slow');
        }, 3000);

        // Close button for messages
        $(document).on('click', '.close-btn', function() {
            $(this).closest('.error, .success').fadeOut('slow');
        });
    });
</script>
</body>
</html>