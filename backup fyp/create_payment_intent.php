<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey('sk_test_51RGvaeIeMdrcW0DLETTGKifHW790cW8ul4gTMxSXeFI1uMmQndKjjvqyiPibqVzxPelDhE486ESLdKZAWdE9nc7300k3zQsa2B');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

try {
    $intent = \Stripe\PaymentIntent::create([
        'amount' => $data['amount'] ?? 1500, // 单位 cents
        'currency' => $data['currency'] ?? 'myr',
        'automatic_payment_methods' => ['enabled' => true]
    ]);
    echo json_encode(['success' => true, 'clientSecret' => $intent->client_secret]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
