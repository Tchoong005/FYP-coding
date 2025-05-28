<?php
session_start();
include 'db.php';

$error = $success = "";

if (!isset($_SESSION['email'])) {
    header("Location: register.php");
    exit();
}

$email = $_SESSION['email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = mysqli_real_escape_string($conn, $_POST['verification_code']);
    $query = "SELECT verification_code FROM customers WHERE email='$email'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        if ($row['verification_code'] == $code) {
            $update = "UPDATE customers SET is_verified=1 WHERE email='$email'";
            if (mysqli_query($conn, $update)) {
                $success = "Email verified successfully!";
                unset($_SESSION['email']);
            } else {
                $error = "Failed to update verification status.";
            }
        } else {
            $error = "Incorrect verification code.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Verify Email - FastFood Express</title>
  <style>
    body {
      font-family: Arial, sans-serif
::contentReference[oaicite:4]{index=4}
 
