<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - FastFood Express</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background-color: #fff0f0;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .login-container {
      background-color: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
    }

    h2 {
      text-align: center;
      color: #d6001c;
      margin-bottom: 20px;
    }

    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 8px;
      font-size: 14px;
    }

    .remember-row {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .remember-row input[type="checkbox"] {
      margin-right: 10px;
      transform: scale(1.2);
    }

    .remember-row label {
      font-size: 14px;
      color: #555;
    }

    button {
      background-color: #d6001c;
      color: white;
      border: none;
      padding: 12px;
      width: 100%;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      font-size: 16px;
    }

    button:hover {
      background-color: #b80018;
    }

    .bottom-text {
      text-align: center;
      margin-top: 15px;
      font-size: 14px;
    }

    .bottom-text a {
      color: #d6001c;
      text-decoration: none;
      font-weight: bold;
    }

    .bottom-text a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="login-container" data-aos="fade-up">
  <h2>Login to FastFood Express</h2>
  <form>
    <input type="text" placeholder="Username or Email" required>
    <input type="password" placeholder="Password" required>

    <div class="remember-row">
      <input type="checkbox" id="remember">
      <label for="remember">Remember Me</label>
    </div>

    <button type="submit">Login</button>

    <div class="bottom-text">
      Don't have an account? <a href="register.html">Register here</a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>AOS.init();</script>


</body>
</html>
