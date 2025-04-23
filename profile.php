<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM customers WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $phone = mysqli_real_escape_string($conn, $_POST['phone']);
  $password = mysqli_real_escape_string($conn, $_POST['password']);

  $update_query = "UPDATE customers SET email='$email', phone='$phone', password='$password' WHERE id='$user_id'";
  if (mysqli_query($conn, $update_query)) {
    $success = "Profile updated successfully!";
  } else {
    $error = "Update failed. Please try again.";
  }

  // Refresh data
  $result = mysqli_query($conn, $query);
  $user = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Profile - FastFood Express</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #fff0f0;
      margin: 0;
    }

    .topbar {
      background-color: #222;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 30px;
    }

    .topbar .logo {
      font-size: 24px;
      font-weight: bold;
    }

    .topbar a {
      color: white;
      text-decoration: none;
      margin-left: 20px;
      font-weight: bold;
    }

    .container {
      max-width: 600px;
      margin: 60px auto;
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    h2 {
      color: #d6001c;
      text-align: center;
    }

    form input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 8px;
    }

    input[readonly] {
      background-color: #f3f3f3;
    }

    form button {
      background: #d6001c;
      color: white;
      padding: 12px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      width: 100%;
    }

    .message {
      text-align: center;
      font-weight: bold;
      margin: 10px 0;
    }

    .success { color: green; }
    .error { color: red; }

    .footer {
      background-color: #eee;
      text-align: center;
      padding: 20px;
      font-size: 14px;
      margin-top: 40px;
    }
  </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
  <div class="logo">🍔 FastFood Express</div>
  <div>
    <a href="index_user.php">Home</a>
    <a href="products_user.php">Products</a>
    <a href="profile.php">Profile</a>
    <a href="about.php">About</a>
    <a href="contact.php">Contact</a>
    <a href="logout.php">Logout</a>
  </div>
</div>

<!-- Profile Form -->
<div class="container" data-aos="fade-up">
  <h2>My Profile</h2>
  <?php
    if ($success) echo "<div class='message success'>$success</div>";
    if ($error) echo "<div class='message error'>$error</div>";
  ?>
  <form method="POST">
    <input type="text" name="username" value="<?php echo $user['username']; ?>" readonly>
    <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
    <input type="text" name="phone" value="<?php echo $user['phone']; ?>" required>
    <input type="password" name="password" value="<?php echo $user['password']; ?>" required>
    <button type="submit">Update Profile</button>
  </form>
</div>

<!-- Footer -->
<div class="footer">
  © 2025 FastFood Express. All rights reserved.
</div>

<!-- AOS Script -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init();</script>

</body>
</html>
