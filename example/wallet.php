<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_user.php");
    exit();
}

// 假设用户数据存储在 $_SESSION['user'] 中，包括 wallet_balance
$wallet_balance = isset($_SESSION['user']['wallet_balance']) ? $_SESSION['user']['wallet_balance'] : 0.00;
$message = "";

// 处理充值请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);
    if ($amount > 0) {
        // 实际项目中应更新数据库，此处仅更新 session 作为示例
        $wallet_balance += $amount;
        $_SESSION['user']['wallet_balance'] = $wallet_balance;
        $message = "Recharge successful. Your new balance is MYR " . number_format($wallet_balance, 2) . ".";
        // 同时需在数据库中执行 UPDATE 语句更新相应余额
    } else {
        $message = "Please enter a valid amount.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>E-Wallet - KFG Food</title>
  <!-- 引入登录用户头部样式 -->
  <link rel="stylesheet" href="header_login.css">
  <!-- 全局通用样式可选 -->
  <link rel="stylesheet" href="style.css">
  <style>
    /* Wallet page styles */
    .wallet-container {
      max-width: 500px;
      margin: 40px auto;
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .wallet-container h2 {
      text-align: center;
      font-size: 1.8rem;
      color: #e4002b;
      margin-bottom: 20px;
    }
    .wallet-balance {
      text-align: center;
      font-size: 1.4rem;
      margin-bottom: 20px;
    }
    .message {
      text-align: center;
      margin-bottom: 20px;
      color: green;
      font-size: 1rem;
    }
    .recharge-form label {
      display: block;
      margin-bottom: 10px;
      font-size: 1rem;
    }
    .recharge-form input[type="number"] {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      font-size: 1rem;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    .recharge-form button {
      background: #e4002b;
      color: #fff;
      border: none;
      padding: 10px 20px;
      font-size: 1rem;
      border-radius: 4px;
      cursor: pointer;
      width: 100%;
    }
    .recharge-form button:hover {
      background: #c2001a;
    }
  </style>
</head>
<body>
  <?php include 'header_login.php'; ?>
  <div class="wallet-container">
    <h2>E-Wallet</h2>
    <?php if ($message != ""): ?>
      <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <div class="wallet-balance">
      Current Balance: MYR <?php echo number_format($wallet_balance, 2); ?>
    </div>
    <form class="recharge-form" method="POST" action="wallet.php">
      <label for="amount">Enter recharge amount (MYR):</label>
      <input type="number" step="0.01" name="amount" id="amount" required>
      <button type="submit">Recharge</button>
    </form>
    <p style="text-align:center; margin-top:20px;">Transaction history coming soon...</p>
  </div>
</body>
</html>
