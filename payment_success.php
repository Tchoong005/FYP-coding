<?php
session_start();
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey('sk_test_51RGvaeIeMdrcW0DLETTGKifHW790cW8ul4gTMxSXeFI1uMmQndKjjvqyiPibqVzxPelDhE486ESLdKZAWdE9nc7300k3zQsa2B');

$pid = $_GET['pid'] ?? '';
$status = '';
$amount = '';
$currency = '';
$error = '';

if ($pid) {
    try {
        $pi = \Stripe\PaymentIntent::retrieve($pid);
        if ($pi->status === 'succeeded') {
            $status = 'succeeded';
            $amount = number_format($pi->amount_received / 100, 2);
            $currency = strtoupper($pi->currency);
        } else {
            $status = $pi->status;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    $error = '缺少 PaymentIntent ID';
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>支付状态</title>
  <style>
    body { font-family: Arial; background: #f4f4f4; text-align: center; padding: 100px; }
    .box { background: white; padding: 40px; border-radius: 10px; display: inline-block; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    h1 { color: #28a745; }
    .error { color: red; }
  </style>
</head>
<body>
  <div class="box">
    <?php if ($error): ?>
      <h1 class="error">❌ 支付状态未知</h1>
      <p><?php echo htmlspecialchars($error); ?></p>
    <?php elseif ($status === 'succeeded'): ?>
      <h1>🎉 支付成功</h1>
      <p>金额：<strong><?php echo $amount . ' ' . $currency; ?></strong></p>
      <p>Payment ID：<strong><?php echo htmlspecialchars($pid); ?></strong></p>
    <?php else: ?>
      <h1 class="error">⚠️ 支付未完成</h1>
      <p>状态：<?php echo htmlspecialchars($status); ?></p>
    <?php endif; ?>
    <br><br>
    <a href="payment.php"><button>返回支付页面</button></a>
  </div>
</body>
</html>
