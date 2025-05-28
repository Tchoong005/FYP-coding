<?php
session_start();
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$pid = $_GET['payment_intent'] ?? '';
$order_id = $_GET['order_id'] ?? 0;
$status = '';
$amount = '';
$currency = '';
$receipt_url = '';
$error = '';

if ($pid && $order_id) {
    try {
        $pi = \Stripe\PaymentIntent::retrieve($pid);
        
        if ($pi->status === 'succeeded') {
            $status = 'succeeded';
            $amount = number_format($pi->amount_received / 100, 2);
            $currency = strtoupper($pi->currency);
            
            if (!empty($pi->charges->data[0]->receipt_url)) {
                $receipt_url = $pi->charges->data[0]->receipt_url;
            }
            
            updatePaymentStatus($order_id, 'paid');
        } else {
            $status = $pi->status;
            $error = 'Payment not completed: ' . $status;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    $error = 'Missing required parameters';
}

function updatePaymentStatus($order_id, $status) {
    include 'db.php';
    
    $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Payment status update failed: " . $stmt->error);
        return false;
    }
    
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment Status - Fast Food Order</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <style>
    :root {
      --black: #000000;
      --white: #ffffff;
      --light-gray: #f5f5f5;
      --medium-gray: #e0e0e0;
      --dark-gray: #333333;
      --text-gray: #666666;
      --success-gray: #444444;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);
      color: var(--dark-gray);
      line-height: 1.6;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    
    .container {
      max-width: 800px;
      width: 100%;
      margin: 0 auto;
    }
    
    .logo {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .logo h1 {
      font-size: 2.5rem;
      color: var(--black);
      margin-bottom: 5px;
      font-weight: 800;
      letter-spacing: -0.5px;
    }
    
    .logo p {
      color: var(--text-gray);
      font-size: 1.1rem;
      letter-spacing: 1px;
    }
    
    .payment-card {
      background: var(--white);
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      margin-bottom: 30px;
      border: 1px solid var(--medium-gray);
    }
    
    .payment-header {
      padding: 30px;
      text-align: center;
      background: linear-gradient(to right, #1a1a1a, #2a2a2a);
      color: var(--white);
      position: relative;
    }
    
    .payment-header.success {
      background: linear-gradient(to right, #2a2a2a, #1a1a1a);
    }
    
    .payment-header.error {
      background: linear-gradient(to right, #333333, #222222);
    }
    
    .status-icon {
      width: 80px;
      height: 80px;
      background: var(--white);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      border: 3px solid var(--black);
    }
    
    .status-icon i {
      font-size: 40px;
      color: var(--success-gray);
    }
    
    .error .status-icon i {
      color: var(--dark-gray);
    }
    
    .payment-header h2 {
      font-size: 2.2rem;
      margin-bottom: 10px;
      font-weight: 700;
    }
    
    .payment-header p {
      font-size: 1.1rem;
      opacity: 0.85;
    }
    
    .payment-body {
      padding: 30px;
    }
    
    .payment-details {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    
    .detail-card {
      background: var(--light-gray);
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      transition: transform 0.3s ease;
      border: 1px solid var(--medium-gray);
    }
    
    .detail-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      background: var(--white);
    }
    
    .detail-card i {
      font-size: 28px;
      color: var(--black);
      margin-bottom: 15px;
    }
    
    .detail-card h3 {
      font-size: 1rem;
      color: var(--text-gray);
      margin-bottom: 8px;
      font-weight: 600;
    }
    
    .detail-card p {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--black);
    }
    
    .amount {
      color: var(--black);
      font-size: 1.8rem !important;
      font-weight: 800;
    }
    
    .action-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      justify-content: center;
      margin-top: 30px;
    }
    
    .btn {
      padding: 14px 28px;
      border-radius: 50px;
      font-size: 1rem;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      cursor: pointer;
      transition: all 0.3s ease;
      border: none;
    }
    
    .btn-primary {
      background: var(--black);
      color: var(--white);
    }
    
    .btn-primary:hover {
      background: #333333;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .btn-outline {
      background: transparent;
      color: var(--black);
      border: 2px solid var(--black);
    }
    
    .btn-outline:hover {
      background: rgba(0, 0, 0, 0.05);
      transform: translateY(-2px);
    }
    
    .btn-success {
      background: var(--dark-gray);
      color: var(--white);
    }
    
    .btn-success:hover {
      background: #444444;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }
    
    .receipt-section {
      background: var(--light-gray);
      border-radius: 12px;
      padding: 25px;
      margin-top: 30px;
      text-align: center;
      border: 1px solid var(--medium-gray);
    }
    
    .receipt-section h3 {
      margin-bottom: 20px;
      color: var(--dark-gray);
      font-size: 1.4rem;
      font-weight: 700;
    }
    
    .footer {
      text-align: center;
      color: var(--text-gray);
      font-size: 0.9rem;
      padding: 20px;
    }
    
    .footer p {
      margin-bottom: 5px;
    }
    
    @media (max-width: 768px) {
      .payment-details {
        grid-template-columns: 1fr;
      }
      
      .action-buttons {
        flex-direction: column;
      }
      
      .btn {
        width: 100%;
      }
    }
    
    .wave {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      overflow: hidden;
      line-height: 0;
      transform: rotate(180deg);
    }
    
    .wave svg {
      position: relative;
      display: block;
      width: calc(100% + 1.3px);
      height: 80px;
    }
    
    .wave .shape-fill {
      fill: var(--white);
    }
    
    .payment-id {
      background: rgba(255, 255, 255, 0.15);
      padding: 8px 15px;
      border-radius: 50px;
      font-size: 0.9rem;
      display: inline-block;
      margin-top: 15px;
      color: var(--white);
      border: 1px solid rgba(255,255,255,0.3);
    }
    
    /* PDF Receipt Styles */
    #pdf-receipt {
      display: none;
      padding: 40px;
      background: var(--white);
      max-width: 500px;
      margin: 0 auto;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .receipt-header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid var(--black);
    }
    
    .receipt-header h1 {
      font-size: 28px;
      font-weight: 800;
      margin-bottom: 5px;
      color: var(--black);
    }
    
    .receipt-header p {
      color: var(--text-gray);
      font-size: 16px;
    }
    
    .receipt-details {
      margin-bottom: 30px;
    }
    
    .receipt-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 12px;
      padding-bottom: 12px;
      border-bottom: 1px dashed var(--medium-gray);
    }
    
    .receipt-label {
      font-weight: 600;
      color: var(--text-gray);
    }
    
    .receipt-value {
      font-weight: 700;
      color: var(--black);
    }
    
    .receipt-total {
      font-size: 20px;
      font-weight: 800;
      margin-top: 20px;
      padding-top: 20px;
      border-top: 2px solid var(--black);
    }
    
    .receipt-footer {
      text-align: center;
      margin-top: 40px;
      padding-top: 20px;
      border-top: 1px solid var(--medium-gray);
      color: var(--text-gray);
      font-size: 14px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">
      <h1>FASTFOOD EXPRESS</h1>
      <p>ORDER CONFIRMATION & PAYMENT RECEIPT</p>
    </div>
    
    <div class="payment-card">
      <?php if ($error): ?>
        <div class="payment-header error">
          <div class="status-icon">
            <i class="fas fa-times-circle"></i>
          </div>
          <h2>Payment Failed</h2>
          <p>We encountered an issue processing your payment</p>
          <div class="wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
              <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
          </div>
        </div>
        <div class="payment-body">
          <div class="detail-card">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Error Message</h3>
            <p><?php echo htmlspecialchars($error); ?></p>
          </div>
          
          <div class="action-buttons">
            <a href="checkout.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary">
              <i class="fas fa-redo-alt"></i> Retry Payment
            </a>
            <a href="index.php" class="btn btn-outline">
              <i class="fas fa-home"></i> Return to Home
            </a>
          </div>
        </div>
      <?php elseif ($status === 'succeeded'): ?>
        <div class="payment-header success">
          <div class="status-icon">
            <i class="fas fa-check-circle"></i>
          </div>
          <h2>Payment Successful!</h2>
          <p>Thank you for your order</p>
          <div class="payment-id">Payment ID: <?php echo htmlspecialchars($pid); ?></div>
          <div class="wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
              <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
          </div>
        </div>
        <div class="payment-body">
          <div class="payment-details">
            <div class="detail-card">
              <i class="fas fa-receipt"></i>
              <h3>Order ID</h3>
              <p>#<?php echo htmlspecialchars($order_id); ?></p>
            </div>
            
            <div class="detail-card">
              <i class="fas fa-money-bill-wave"></i>
              <h3>Total Amount</h3>
              <p class="amount"><?php echo $amount . ' ' . $currency; ?></p>
            </div>
            
            <div class="detail-card">
              <i class="fas fa-calendar-check"></i>
              <h3>Date & Time</h3>
              <p><?php echo date('M j, Y H:i'); ?></p>
            </div>
          </div>
          
          <div class="receipt-section">
            <h3>Download Your Payment Receipt</h3>
            <div class="action-buttons">
              <button id="generate-receipt" class="btn btn-success">
                <i class="fas fa-file-pdf"></i> Generate PDF Receipt
              </button>
              <a href="index_user.php" class="btn btn-outline">
                <i class="fas fa-home"></i> Return to Home
              </a>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="payment-header">
          <div class="status-icon">
            <i class="fas fa-clock"></i>
          </div>
          <h2>Payment Processing</h2>
          <p>Your payment is being processed</p>
          <div class="wave">
            <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
              <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
            </svg>
          </div>
        </div>
        <div class="payment-body">
          <div class="detail-card">
            <i class="fas fa-info-circle"></i>
            <h3>Current Status</h3>
            <p><?php echo htmlspecialchars($status); ?></p>
          </div>
          
          <div class="action-buttons">
            <a href="index.php" class="btn btn-primary">
              <i class="fas fa-home"></i> Return to Home
            </a>
          </div>
        </div>
      <?php endif; ?>
    </div>
    
    <!-- Hidden receipt template for PDF generation -->
    <div id="pdf-receipt">
      <div class="receipt-header">
        <h1>FASTFOOD EXPRESS</h1>
        <p>ORDER RECEIPT</p>
      </div>
      
      <div class="receipt-details">
        <div class="receipt-row">
          <span class="receipt-label">Order ID:</span>
          <span class="receipt-value">#<?php echo $order_id; ?></span>
        </div>
        <div class="receipt-row">
          <span class="receipt-label">Payment ID:</span>
          <span class="receipt-value"><?php echo $pid; ?></span>
        </div>
        <div class="receipt-row">
          <span class="receipt-label">Date & Time:</span>
          <span class="receipt-value"><?php echo date('M j, Y H:i'); ?></span>
        </div>
        <div class="receipt-row">
          <span class="receipt-label">Payment Status:</span>
          <span class="receipt-value">Completed</span>
        </div>
        <div class="receipt-row">
          <span class="receipt-label">Payment Method:</span>
          <span class="receipt-value">Credit Card (Stripe)</span>
        </div>
      </div>
      
      <div class="receipt-total">
        <div class="receipt-row">
          <span class="receipt-label">Total Amount:</span>
          <span class="receipt-value"><?php echo $amount . ' ' . $currency; ?></span>
        </div>
      </div>
      
      <div class="receipt-footer">
        <p>Thank you for your order!</p>
        <p>FastFood Express &copy; <?php echo date('Y'); ?></p>
      </div>
    </div>
    
    <div class="footer">
      <p>&copy; <?php echo date('Y'); ?> FastFood Express. All rights reserved.</p>
    </div>
  </div>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const generateBtn = document.getElementById('generate-receipt');
      
      if (generateBtn) {
        generateBtn.addEventListener('click', function() {
          // Show loading state
          const originalText = generateBtn.innerHTML;
          generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
          generateBtn.disabled = true;
          
          // Generate PDF
          setTimeout(() => {
            const { jsPDF } = window.jspdf;
            
            // Get the receipt element
            const element = document.getElementById('pdf-receipt');
            
            // Create a PDF
            const pdf = new jsPDF('p', 'mm', 'a4');
            
            // Add content to PDF
            pdf.setFontSize(22);
            pdf.setFont(undefined, 'bold');
            pdf.text('FASTFOOD EXPRESS', 105, 20, null, null, 'center');
            
            pdf.setFontSize(14);
            pdf.setFont(undefined, 'normal');
            pdf.text('ORDER RECEIPT', 105, 28, null, null, 'center');
            
            // Draw line
            pdf.setLineWidth(0.5);
            pdf.line(20, 32, 190, 32);
            
            // Add details
            pdf.setFontSize(12);
            pdf.text(`Order ID: #<?php echo $order_id; ?>`, 20, 42);
            pdf.text(`Payment ID: <?php echo $pid; ?>`, 20, 50);
            pdf.text(`Date & Time: ${new Date().toLocaleString()}`, 20, 58);
            pdf.text(`Payment Status: Completed`, 20, 66);
            pdf.text(`Payment Method: Credit Card (Stripe)`, 20, 74);
            
            // Total amount
            pdf.setFontSize(16);
            pdf.setFont(undefined, 'bold');
            pdf.text(`Total Amount: <?php echo $amount . ' ' . $currency; ?>`, 20, 90);
            
            // Signature line
            pdf.setLineWidth(0.2);
            pdf.line(20, 220, 80, 220);
            pdf.setFontSize(10);
            pdf.text('Authorized Signature', 20, 225);
            
            // Footer
            pdf.setFontSize(10);
            pdf.text('Thank you for your order!', 105, 250, null, null, 'center');
            pdf.text(`FastFood Express © ${new Date().getFullYear()}`, 105, 255, null, null, 'center');
            
            // Save the PDF
            pdf.save(`receipt_order_<?php echo $order_id; ?>.pdf`);
            
            // Restore button state
            generateBtn.innerHTML = originalText;
            generateBtn.disabled = false;
          }, 1000);
        });
      }
    });
  </script>
</body>
</html>