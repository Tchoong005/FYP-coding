<?php
session_start();
include('db_connection.php'); 

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: newadminlogin.php");
    exit();
}

// Get current user data
$email = $_SESSION['email'];
$query = "SELECT * FROM staff WHERE email = '$email'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    // Validate phone number
    if (!preg_match('/^01[0-9]{9}$/', $phone)) {
        $error = "Phone number must be 11 digits starting with 01";
    } else {
        // Update query
        $update_query = "UPDATE staff SET 
                        name = '$name', 
                        phone = '$phone'
                        WHERE email = '$email'";

        if (mysqli_query($conn, $update_query)) {
            $success = "Profile updated successfully!";
            // Refresh user data
            $query = "SELECT * FROM staff WHERE email = '$email'";
            $result = mysqli_query($conn, $query);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error = "Error updating profile: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .readonly-field {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        button, .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        button:hover, .button:hover {
            background-color: #45a049;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .error {
            background-color: #f2dede;
            color: #a94442;
        }
        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Profile</h2>
        
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="profile.php" method="POST" onsubmit="return validatePhone()">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="role">Role:</label>
                <input type="text" id="role" value="<?php echo htmlspecialchars($user['role']); ?>" readonly class="readonly-field">
                <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['role']); ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number:</label>
                <input type="text" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($user['phone']); ?>" 
                       pattern="01[0-9]{9}" 
                       title="Phone must be 11 digits starting with 01" 
                       required>
                <small>Format: 11 digits starting with 01 (e.g., 01123456789)</small>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly class="readonly-field">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>
            
            <div class="button-group">
                <div>
                    <a href="adminhome.html" class="button">Return to Homepage</a>
                    <a href="changepassword.php" class="button">Change Password</a>
                </div>
                <button type="submit">Update Profile</button>
            </div>
        </form>
    </div>

    <script>
        function validatePhone() {
            const phoneInput = document.getElementById('phone');
            const phoneRegex = /^01[0-9]{9}$/;
            
            if (!phoneRegex.test(phoneInput.value)) {
                alert('Phone number must be 11 digits starting with 01');
                phoneInput.focus();
                return false;
            }
            return true;
        }

        // Prevent typing invalid characters in phone field
        document.getElementById('phone').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html>