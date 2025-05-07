<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// 获取当前用户信息
$query = "SELECT * FROM customers WHERE id='$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $update_query = "UPDATE customers SET email='$email', phone='$phone', password='$password' WHERE id='$user_id'";
    if (mysqli_query($conn, $update_query)) {
        $success = "Profile updated successfully!";
    } else {
        $error = "Something went wrong. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile - FastFood Express</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #fff0f0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .profile-container {
      background: white;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      width: 350px;
    }
    h2 {
      color: #d6001c;
      text-align: center;
      margin-bottom: 20px;
    }
    input[type=text], input[type=email], input[type=password] {
      width: 100%;
      padding: 10px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    input[readonly] {
      background: #f0f0f0;
    }
    button {
      background: #d6001c;
      color: white;
      width: 100%;
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-weight: bold;
      cursor: pointer;
    }
    .error { color: red; text-align: center; margin-bottom: 10px; }
    .success { color: green; text-align: center; margin-bottom: 10px; }
  </style>
</head>
<body>

<div class="profile-container" data-aos="zoom-in">
  <h2>Your Profile</h2>
  <?php
    if ($error) echo "<div class='error'>$error</div>";
    if ($success) echo "<div class='success'>$success</div>";
  ?>
  <form method="post">
    <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
    <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
    <input type="password" name="password" value="<?php echo htmlspecialchars($user['password']); ?>" required>
    <button type="submit">Update Profile</button>
    <div class="bottom-link">
      <a href="index_user.php">Back to Home</a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init();</script>

</body>
</html>
