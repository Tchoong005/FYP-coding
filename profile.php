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

$show_notice = empty($user['address']) || empty($user['birthday']);
$success = $error = $pass_message = "";

// 更新个人信息
if (isset($_POST['update_profile'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $birthday = mysqli_real_escape_string($conn, $_POST['birthday']);

    $update_sql = "UPDATE customers SET username='$username', phone='$phone', address='$address', birthday='$birthday' WHERE id='$user_id'";
    if (mysqli_query($conn, $update_sql)) {
        $success = "Profile updated!";
        $show_notice = false;
        $user['username'] = $username;
        $user['phone'] = $phone;
        $user['address'] = $address;
        $user['birthday'] = $birthday;
    } else {
        $error = "Update failed!";
    }
}

// 修改密码
if (isset($_POST['change_password'])) {
    $old_password = mysqli_real_escape_string($conn, $_POST['old_password']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);

    $check_sql = "SELECT * FROM customers WHERE id='$user_id' AND password='$old_password'";
    $check_result = mysqli_query($conn, $check_sql);

    if (mysqli_num_rows($check_result) > 0) {
        $update_sql = "UPDATE customers SET password='$new_password' WHERE id='$user_id'";
        if (mysqli_query($conn, $update_sql)) {
            $pass_message = "<span style='color:green;'>Password updated successfully!</span>";
        } else {
            $pass_message = "<span style='color:red;'>Failed to update password.</span>";
        }
    } else {
        $pass_message = "<span style='color:red;'>Old password is incorrect.</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Profile - FastFood Express</title>
<style>
body {
    font-family: Arial;
    background: #fff;
    margin: 0;
    padding: 0;
    opacity: 0;
    animation: fadeIn 1s forwards;
}
@keyframes fadeIn {
    to { opacity: 1; }
}
.sidebar {
    width: 200px;
    background: #d6001c;
    color: white;
    float: left;
    height: 100vh;
    padding: 20px;
    box-sizing: border-box;
}
.sidebar h2 {
    color: #ffd700; /* 金色字更醒目，你也可以换成 white */
}
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
</style>
<script>
function showTab(tab) {
    document.getElementById('infoTab').classList.remove('active');
    document.getElementById('passTab').classList.remove('active');
    document.getElementById(tab).classList.add('active');
}
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
            <label>Birthday</label>
            <input type="date" name="birthday" value="<?php echo $user['birthday']; ?>">
            <button type="submit" name="update_profile">Update Profile</button>
        </form>
    </div>

    <div id="passTab" class="tab">
        <h2>Change Password</h2>
        <?php if ($pass_message) echo "<div class='pass-message'>$pass_message</div>"; ?>
        <form method="post">
            <input type="password" name="old_password" placeholder="Old Password" required>
            <input type="password" name="new_password" placeholder="New Password" required>
            <button type="submit" name="change_password">Change Password</button>
        </form>
    </div>
</div>

</body>
</html>
