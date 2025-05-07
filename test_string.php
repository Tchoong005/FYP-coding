<?php
require __DIR__ . '/vendor/autoload.php';  // Composer 安装
// 或：require __DIR__ . '/vendor/stripe/stripe-php/init.php';  // 手动安装

\Stripe\Stripe::setApiKey('sk_test_51RGvaeIeMdrcW0DLETTGKifHW790cW8ul4gTMxSXeFI1uMmQndKjjvqyiPibqVzxPelDhE486ESLdKZAWdE9nc7300k3zQsa2B');

try {
    $balance = \Stripe\Balance::retrieve();
    echo "Stripe SDK 正常工作，currencies: " . implode(', ', array_keys($balance->available));
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
