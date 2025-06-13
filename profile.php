<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM customers WHERE id='$user_id'";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

$show_notice = empty($user['address']) || empty($user['postcode']) || empty($user['city']) || empty($user['state']);
$success = $error = $pass_message = "";

// 更新个人信息
if (isset($_POST['update_profile'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $postcode = mysqli_real_escape_string($conn, $_POST['postcode']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);

    if (!preg_match("/^01\d{8,9}$/", $phone)) {
        $error = "Phone number must start with 01 and be 10–11 digits.";
    } elseif (!preg_match("/^\d{5}$/", $postcode)) {
        $error = "Postcode must be exactly 5 digits.";
    } elseif (empty($state)) {
        $error = "Please select a state.";
    }

    if (empty($error)) {
        $update_sql = "UPDATE customers SET username='$username', phone='$phone', address='$address', postcode='$postcode', city='$city', state='$state' WHERE id='$user_id'";
        if (mysqli_query($conn, $update_sql)) {
            $success = "Profile updated!";
            $show_notice = false;
            $user = array_merge($user, $_POST);
        } else {
            $error = "Update failed!";
        }
    }
}

// 修改密码
if (isset($_POST['change_password'])) {
    $old_password = mysqli_real_escape_string($conn, $_POST['old_password']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $pass_message = "<span style='color:red;'>New password and confirm password do not match.</span>";
    } elseif (strlen($new_password) < 8 || !preg_match("/[A-Z]/", $new_password) || !preg_match("/[0-9]/", $new_password) || !preg_match("/[\W_]/", $new_password)) {
        $pass_message = "Password must be at least 8 characters long, contain an uppercase letter, a number, and a symbol.";
    } else {
        $check_sql = "SELECT password FROM customers WHERE id='$user_id'";
        $check_result = mysqli_query($conn, $check_sql);
        $row = mysqli_fetch_assoc($check_result);

        if ($row && password_verify($old_password, $row['password'])) {
            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE customers SET password='$new_hashed' WHERE id='$user_id'";
            if (mysqli_query($conn, $update_sql)) {
                $pass_message = "<span style='color:green;'>Password updated successfully!</span>";
            } else {
                $pass_message = "<span style='color:red;'>Failed to update password.</span>";
            }
        } else {
            $pass_message = "<span style='color:red;'>Old password is incorrect.</span>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile - FastFood Express</title>

<!-- 🔹 加入 Select2 样式和脚本 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
body {
    font-family: Arial;
    margin: 0;
    padding: 0;
    opacity: 0;
    animation: fadeIn 1s forwards;
}
@keyframes fadeIn { to { opacity: 1; } }
.sidebar {
    width: 200px;
    background: #d6001c;
    color: white;
    float: left;
    height: 100vh;
    padding: 20px;
    box-sizing: border-box;
}
.sidebar h2 { color: #ffd700; }
.sidebar a {
    display: block;
    color: white;
    margin: 10px 0;
    text-decoration: none;
}
.container {
    margin-left: 220px;
    padding: 20px;
}
h2 {
    color: #d6001c;
}
input, button, select {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 5px;
    border: 1px solid #ccc;
}
.tab {
    display: none;
}
.tab.active {
    display: block;
}
.notice {
    background: #ffe0e0;
    padding: 10px;
    border-radius: 5px;
    color: #d6001c;
    text-align: center;
}
.success, .error, .pass-message {
    text-align: center;
    margin-bottom: 10px;
}
.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 12px;
    color: #d6001c;
}
</style>
<script>
function showTab(tab) {
    document.getElementById('infoTab').classList.remove('active');
    document.getElementById('passTab').classList.remove('active');
    document.getElementById(tab).classList.add('active');
}
function togglePassword(id, element) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
        element.textContent = "Hide";
    } else {
        input.type = "password";
        element.textContent = "Show";
    }
}
// 🔹 初始化 Select2
$(document).ready(function() {
    $('#state').select2({ width: '100%' });
});
</script>
</head>
<body>

<div class="sidebar">
    <h2>My Profile</h2>
    <a href="#" onclick="showTab('infoTab')">Personal Info</a>
    <a href="#" onclick="showTab('passTab')">Change Password</a>
    <a href="index_user.php">Go to Home Page</a>
</div>

<div class="container">
    <div id="infoTab" class="tab active">
        <h2>Personal Info</h2>
        <?php if ($show_notice) echo "<div class='notice'>Please complete your information</div>"; ?>
        <?php if ($success) echo "<div class='success'>$success</div>"; ?>
        <?php if ($error) echo "<div class='error'>$error</div>"; ?>
        <form method="post">
            <label>Email</label>
            <input type="email" value="<?php echo $user['email']; ?>" readonly>
            <label>Name</label>
            <input type="text" name="username" value="<?php echo $user['username']; ?>" required>
            <label>Phone</label>
            <input type="text" name="phone" value="<?php echo $user['phone']; ?>" required>
            <label>Address</label>
            <input type="text" name="address" value="<?php echo $user['address']; ?>">
            <label>Postcode</label>
            <input type="text" name="postcode" value="<?php echo $user['postcode']; ?>">
            <label>City</label>
            <input type="text" name="city" value="<?php echo $user['city']; ?>">
            <label>State</label>
            <select name="state" id="state" required>
                <option value="">-- Please select state --</option>
                <?php
                $states = [
                    "Johor", "Kedah", "Kelantan", "Melaka", "Negeri Sembilan", 
                    "Pahang", "Pulau Pinang", "Perak", "Perlis", "Sabah", 
                    "Sarawak", "Selangor", "Terengganu", 
                    "Kuala Lumpur", "Labuan", "Putrajaya"
                ];
                foreach ($states as $state_option) {
                    $selected = ($user['state'] === $state_option) ? "selected" : "";
                    echo "<option value=\"$state_option\" $selected>$state_option</option>";
                }
                ?>
            </select>
            <button type="submit" name="update_profile">Update Profile</button>
        </form>
    </div>

    <div id="passTab" class="tab">
        <h2>Change Password</h2>
        <?php if ($pass_message) echo "<div class='pass-message'>$pass_message</div>"; ?>
        <form method="post">
            <div style="position: relative;">
                <input type="password" name="old_password" id="old_password" placeholder="Old Password" required>
                <span class="toggle-password" onclick="togglePassword('old_password', this)">Show</span>
            </div>
            <div style="position: relative;">
                <input type="password" name="new_password" id="new_password" placeholder="New Password" required>
                <span class="toggle-password" onclick="togglePassword('new_password', this)">Show</span>
            </div>
            <div style="position: relative;">
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" required>
                <span class="toggle-password" onclick="togglePassword('confirm_password', this)">Show</span>
            </div>
            <button type="submit" name="change_password">Change Password</button>
        </form>
    </div>
</div>

</body>
</html>
