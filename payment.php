<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include Stripe PHP SDK (make sure you installed it via Composer)
    require 'vendor/autoload.php';

    // Set your Stripe secret key (use test key during development)
    \Stripe\Stripe::setApiKey('YOUR_STRIPE_SECRET_KEY');

    // Retrieve the posted token and cardholder name
    $token = $_POST['stripeToken'] ?? null;
    $cardholderName = $_POST['cardholder_name'] ?? 'Unknown';
    
    // Example payment amount in cents (e.g., 125299 = $1,252.99)
    $amount = 125299;

    if ($token) {
        try {
            // Create a charge using the token
            $charge = \Stripe\Charge::create([
                'amount'      => $amount,
                'currency'    => 'usd',
                'description' => 'Order Payment',
                'source'      => $token,
                'metadata'    => ['cardholder_name' => $cardholderName],
            ]);

            echo "<h1 style='text-align:center; margin-top:100px;'>Payment Successful!</h1>";
            exit;
        } catch (\Stripe\Exception\CardException $e) {
            echo "<h1 style='text-align:center; margin-top:100px;'>Payment Failed: " 
                 . $e->getError()->message . "</h1>";
            exit;
        } catch (Exception $e) {
            echo "<h1 style='text-align:center; margin-top:100px;'>Error: " 
                 . $e->getMessage() . "</h1>";
            exit;
        }
    } else {
        // If no token, possibly user selected e-transfer or something else
        echo "<h1 style='text-align:center; margin-top:100px;'>No card token provided.</h1>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Page</title>
  <link rel="stylesheet" type="text/css" href="payment.css">
  <!-- Stripe.js library -->
  <script src="https://js.stripe.com/v3/"></script>
</head>
<body>

<!-- Container for the entire layout -->
<div class="wrapper">

  <!-- Top steps bar -->
  <div class="steps-bar">
    <div class="step">Order</div>
    <div class="step active">Payment</div>
    <div class="step">Complete</div>
  </div>

  <div class="payment-container">
    <!-- Left side: Payment form -->
    <div class="payment-left">
      <h2>Payment Details</h2>

      <!-- Payment method tabs -->
      <div class="tabs">
        <div class="tab active" id="creditTab">Credit Card</div>
        <div class="tab" id="etransferTab">e-transfer</div>
      </div>

      <!-- Actual payment form -->
      <form id="payment-form" action="payment.php" method="POST">
        <!-- Cardholder Name (always shown) -->
        <div class="form-group">
          <label for="cardholder_name">Cardholder Name</label>
          <input 
            type="text"
            id="cardholder_name"
            name="cardholder_name"
            placeholder="Regina Phalange"
            required
          >
        </div>

        <!-- Credit card fields (Stripe Elements area) -->
        <div id="creditCardSection">
          <label for="card-element">Credit/Debit Card Details</label>
          <div id="card-element" class="card-element"></div>
          <div id="card-errors" class="card-errors" role="alert"></div>
        </div>

        <!-- e-transfer info -->
        <div id="etransferSection" class="etransfer-section">
          <p>
            Please send your e-transfer to <strong>pay@example.com</strong> 
            and include your order number in the note.
          </p>
        </div>

        <button type="submit" class="pay-now-btn">Pay Now</button>
      </form>
    </div>

    <!-- Right side: Order summary -->
    <div class="payment-right">
      <h3>Order Summary</h3>
      <div class="order-item">
        <span class="item-title">iPhone 16 Pro Max</span>
        <span class="item-price">$1199.00</span>
        <span class="item-details">Colour: Black | 512 GB</span>
      </div>
      <div class="order-item">
        <span class="item-title">Apple 20W USB-C Power Adapter</span>
        <span class="item-price">$25.00</span>
      </div>
      <div class="calculation">
        <div>
          <span>Subtotal:</span>
          <span>$1224.00</span>
        </div>
        <div>
          <span>Shipping:</span>
          <span>$19.00</span>
        </div>
        <div>
          <span>Taxes:</span>
          <span>$9.99</span>
        </div>
      </div>
      <div class="total">
        <strong>Total: $1,252.99</strong>
      </div>
    </div>
  </div>
</div>

<script>
  // Initialize Stripe with your public key
  const stripe = Stripe('YOUR_STRIPE_PUBLIC_KEY');
  const elements = stripe.elements();

  // Custom styling for Stripe Elements
  const style = {
    base: {
      color: "#32325d",
      fontFamily: 'Arial, sans-serif',
      fontSize: "16px",
      "::placeholder": {
        color: "#aab7c4"
      }
    },
    invalid: {
      color: "#fa755a"
    }
  };

  // Create an instance of the card Element
  const card = elements.create("card", { style: style });
  card.mount("#card-element");

  // Show errors if card details are invalid
  card.addEventListener('change', function(event) {
    const displayError = document.getElementById('card-errors');
    if (event.error) {
      displayError.textContent = event.error.message;
    } else {
      displayError.textContent = '';
    }
  });

  // Tabs
  const creditTab = document.getElementById('creditTab');
  const etransferTab = document.getElementById('etransferTab');
  const creditCardSection = document.getElementById('creditCardSection');
  const etransferSection = document.getElementById('etransferSection');

  // Default state: Credit Card tab active
  creditTab.addEventListener('click', () => {
    creditTab.classList.add('active');
    etransferTab.classList.remove('active');
    creditCardSection.style.display = 'block';
    etransferSection.style.display = 'none';
  });

  etransferTab.addEventListener('click', () => {
    etransferTab.classList.add('active');
    creditTab.classList.remove('active');
    creditCardSection.style.display = 'none';
    etransferSection.style.display = 'block';
  });

  // On form submission, if credit card tab is active, create a Stripe token
  const form = document.getElementById('payment-form');
  form.addEventListener('submit', function(event) {
    if (creditTab.classList.contains('active')) {
      // If paying by credit card, we need to generate a token from Stripe
      event.preventDefault();
      stripe.createToken(card).then(function(result) {
        if (result.error) {
          const errorElement = document.getElementById('card-errors');
          errorElement.textContent = result.error.message;
        } else {
          // Append the token to the form and submit
          const hiddenInput = document.createElement('input');
          hiddenInput.setAttribute('type', 'hidden');
          hiddenInput.setAttribute('name', 'stripeToken');
          hiddenInput.setAttribute('value', result.token.id);
          form.appendChild(hiddenInput);
          form.submit();
        }
      });
    }
  });
</script>
</body>
</html>
