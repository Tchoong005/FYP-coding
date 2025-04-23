<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST['username'];
  $email = $_POST['email'];

  $stmt = $conn->prepare("UPDATE customers SET username = ?, email = ? WHERE id = ?");
  $stmt->bind_param("ssi", $username, $email, $user_id);
  $stmt->execute();
  echo "<script>alert('Profile updated');</script>";
  $stmt->close();
}

$stmt = $conn->prepare("SELECT username, email FROM customers WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();
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
      padding: 40px;
      display: flex;
      justify-content: center;
    }
    .form-container {
      background: #fff;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 500px;
    }
    h2 {
      color: #d6001c;
      margin-bottom: 20px;
      text-align: center;
    }
    input, button {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-size: 14px;
    }
    button {
      background: #d6001c;
      color: white;
      font-weight: bold;
      cursor: pointer;
    }
    a {
      display: block;
      text-align: center;
      color: #d6001c;
      text-decoration: none;
    }
  </style>
</head>
<body>
  <div class="form-container" data-aos="zoom-in">
    <h2>Your Profile</h2>
    <form method="POST">
      <input type="text" name="username" value="<?php echo $username; ?>" required>
      <input type="email" name="email" value="<?php echo $email; ?>" required>
      <button type="submit">Update</button>
      <a href="logout.php">Logout</a>
    </form>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
  <script>AOS.init();</script>
  
</body>
</html>
