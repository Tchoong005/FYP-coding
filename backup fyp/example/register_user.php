<?php
session_start();
require_once 'connection.php';  // Ensure this file sets up $conn to fast_food DB

// If the user is already logged in, redirect them (optional)
if (isset($_SESSION['user_id'])) {
    header("Location: index_user.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form inputs
    $firstName = trim($_POST['first_name']);
    $lastName  = trim($_POST['last_name']);
    $mobile    = trim($_POST['mobile']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];
    $dob       = trim($_POST['dob']); // "DD-MM-YYYY" from user input
    
    // Combine first and last name
    $fullName = $firstName . ' ' . $lastName;

    // --- Validate required fields ---
    if (empty($firstName) || empty($lastName) || empty($mobile) || empty($email) || empty($password) || empty($confirm)) {
        $error = "Please fill in all required fields.";
    } else {
        // --- Validate password rules ---
        if (strlen($password) !== 12) {
            $error = "Password must be exactly 12 characters.";
        } elseif (!preg_match('/[A-Za-z]/', $password)) {
            $error = "Password must contain at least one letter.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = "Password must contain at least one digit.";
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $error = "Password must contain at least one symbol.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        }
    }

    // If no error so far, proceed with DB insertion
    if (empty($error)) {
        // Hash the password for security
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Convert DOB from "DD-MM-YYYY" to "YYYY-MM-DD" if not empty
        $dobSQL = null;
        if (!empty($dob)) {
            $parts = explode('-', $dob); // expect [DD, MM, YYYY]
            if (count($parts) === 3) {
                $dobSQL = $parts[2] . '-' . $parts[1] . '-' . $parts[0]; // "YYYY-MM-DD"
            }
        }

        // Prepare the INSERT statement (adjust columns if your table differs)
        $stmt = $conn->prepare("
            INSERT INTO Users 
            (Full_name, User_password, User_Email, User_phone_num, User_DOB)
            VALUES (?,?,?,?,?)
        ");

        if (!$stmt) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param(
                "sssss", 
                $fullName,
                $hashedPassword,
                $email,
                $mobile,
                $dobSQL
            );

            if ($stmt->execute()) {
                // Registration successful
                $stmt->close();
                // Optionally set a success message or redirect
                header("Location: login_user.php"); 
                exit();
            } else {
                $error = "Failed to register user: " . $stmt->error;
                $stmt->close();
            }
        }
    }
}

// Close DB connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <link rel="stylesheet" href="register_style.css">
  <script>
    // Optional client-side password checks
    function validateRegisterForm() {
      var pwd = document.getElementById("password").value;
      var confirmPwd = document.getElementById("confirmPassword").value;
      if (pwd.length !== 12) {
          alert("Password must be exactly 12 characters.");
          return false;
      }
      if (!/[A-Za-z]/.test(pwd)) {
          alert("Password must contain at least one letter.");
          return false;
      }
      if (!/[0-9]/.test(pwd)) {
          alert("Password must contain at least one digit.");
          return false;
      }
      if (!/[^A-Za-z0-9]/.test(pwd)) {
          alert("Password must contain at least one symbol.");
          return false;
      }
      if (pwd !== confirmPwd) {
          alert("Passwords do not match.");
          return false;
      }
      return true;
    }
  </script>
</head>
<body>

<!-- Optionally include a user header if needed -->
<?php include 'header.php'; ?>

<div class="register-container">
  <h2>Let's Get Started!</h2>
  <p class="subtitle">
    Sign up now to enjoy finger lickin' good deals and rewards!<br>
    It's absolutely free!
  </p>

  <!-- Display any error message -->
  <?php if (!empty($error)): ?>
    <div class="error-msg"><?php echo $error; ?></div>
  <?php endif; ?>

  <form action="" method="post" onsubmit="return validateRegisterForm();">
    <div class="form-group">
      <label for="firstName">First Name*</label>
      <input type="text" id="firstName" name="first_name" placeholder="Enter your first name" required>
    </div>

    <div class="form-group">
      <label for="lastName">Last Name*</label>
      <input type="text" id="lastName" name="last_name" placeholder="Enter your last name" required>
    </div>

    <div class="form-group">
      <label for="mobile">Mobile Number*</label>
      <input type="text" id="mobile" name="mobile" placeholder="Enter your mobile number" required>
    </div>

    <div class="form-group">
      <label for="email">Email*</label>
      <input type="email" id="email" name="email" placeholder="Enter your email address" required>
    </div>

    <div class="form-group">
      <label for="password">Password* (12 characters, include letter, digit, symbol)</label>
      <input type="password" id="password" name="password" placeholder="Enter your password" required>
    </div>

    <div class="form-group">
      <label for="confirmPassword">Confirm Password*</label>
      <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter your password" required>
    </div>

    <div class="form-group">
      <label for="dob">Date of Birth</label>
      <input type="text" id="dob" name="dob" placeholder="DD-MM-YYYY">
      <div class="small-note">Date of birth can't be changed once submitted.</div>
    </div>

    <!-- Additional checkboxes if needed -->
    <!-- <div class="checkbox-group">
      <input type="checkbox" id="news" name="news">
      <label for="news">I want the latest news and promotions!</label>
    </div>
    <div class="checkbox-group">
      <input type="checkbox" id="terms" name="terms" required>
      <label for="terms">
        I accept the <strong>Terms and Condition</strong> and Privacy Policy.
      </label>
    </div> -->

    <!-- <div class="recaptcha-box">
      [ reCAPTCHA widget goes here ]
    </div> -->

    <button type="submit" class="register-btn">Register Now</button>
  </form>
</div>

</body>
</html>
