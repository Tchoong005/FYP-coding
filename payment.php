<?php
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey('sk_test_51RGvaeIeMdrcW0DLETTGKifHW790cW8ul4gTMxSXeFI1uMmQndKjjvqyiPibqVzxPelDhE486ESLdKZAWdE9nc7300k3zQsa2B');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Stripe Payment</title>
  <script src="https://js.stripe.com/v3/"></script>
  <style>
    body { font-family: Arial; padding: 50px; background: #f4f4f4; }
    #card-element { border: 1px solid #ccc; padding: 10px; margin-bottom: 15px; }
    button { padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; }
    button:hover { background: #218838; }
    #card-errors { color: red; margin-top: 10px; }
  </style>
</head>
<body>
  <h2>测试支付</h2>
  <form id="payment-form">
    <div id="card-element"></div>
    <button type="submit">Pay Now</button>
    <div id="card-errors"></div>
  </form>

  <script>
    const stripe = Stripe('<?php echo $_ENV['STRIPE_PUBLISHABLE_KEY']; ?>');
    const elements = stripe.elements();
    const card = elements.create('card');
    card.mount('#card-element');

    document.getElementById('payment-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      document.getElementById('card-errors').textContent = '';

      const res = await fetch('create_payment_intent.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount: 1500, currency: 'myr' })
      });

      const { success, clientSecret, message } = await res.json();
      if (!success) {
        document.getElementById('card-errors').textContent = message;
        return;
      }

      const { error, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
        payment_method: { card }
      });

      if (error) {
        document.getElementById('card-errors').textContent = error.message;
        return;
      }

      if (paymentIntent.status === 'succeeded') {
        window.location.href = 'http://localhost/FYP-coding/payment_success.php?pid=' + paymentIntent.id;
      } else {
        document.getElementById('card-errors').textContent = '支付未完成：' + paymentIntent.status;
      }
    });
  </script>
</body>
</html>
