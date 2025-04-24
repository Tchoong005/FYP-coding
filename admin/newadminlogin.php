<?php

$host="localhost";
$user="root";
$password="";
$db="kfg";

session_start();


$data=mysqli_connect($host,$user,$password,$db);

if($data===false)
{
	die("connection error");
}


if($_SERVER["REQUEST_METHOD"]=="POST")
{
	$username=$_POST["username"];
	$password=$_POST["password"];


	$sql="select * from login where username='".$username."' AND password='".$password."' ";

	$result=mysqli_query($data,$sql);

	$row=mysqli_fetch_array($result);

	if($row["usertype"]=="superadmin")
	{	

		$_SESSION["username"]=$username;

		header("location:adminHome.html");
	}

	elseif($row["usertype"]=="admin")
	{

		$_SESSION["username"]=$username;
		
		header("location:adminStaff.php");
	}

	else
	{
        echo "<script>alert('Invalid Username or Password');</script>";
		
	}

}




?>


<!DOCTYPE html>
<htm1 lang="en">

<head>
    <meta charset="UTF-8">

    <title>Admin Login Page</title>
    <STYle>
        *{
            margin: 0;
            padding:0;
            box-sizing: border-box;
        }
        body{
            height: 750px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('img/OIP.jpg') no-repeat;
            background-size: cover;
            background-position: center;
        }
        .smallbox{
            display: flex;
            flex-direction: column;
            width: 300px;
            padding: 15px;
            border: 1px solid;
            color: skyblue;
            border-radius: 20px;


        }
        input{
            margin: 5px 0px;
            padding: 7px;
            height: 35px;
        }

        button{
            height: 35px;
            margin: 5px 0px;
            background-color: skyblue;
            color: #333;
            border: none;
            border-radius: 5px;

        }
        button:hover{
            background-color: #333;
            color: skyblue;

        }
        h1 {
            font-size: 80px;
            font-style: oblique;
            color: rgb(215, 249, 250);
            margin-bottom: 20px;
        }

        H2 {
            color: rgb(215, 249, 250);
            margin-bottom: 15px;
            
        }
        .i{
            position: absolute;
            right: 46%;
            top: 60%;
            font-size: 20px;
            transform: translateY(-50%);
        }
        .o{
            position: absolute;
            right: 46%;
            top: 66.5%;
            font-size: 20px;
            transform: translateY(-50%);
        }

    </STYle>

</head>

<body>
    
    <h1>KFG FOOD</h1>
    
    <form method="POST">
    <div class="smallbox">
        <H2>Admin Login</H2>
        
        <input type="text" name="username" placeholder="USERNAME" required>
        <img class="i" src="img/user-solid-24.png" alt="">
        <input type="password" name="password" placeholder="PASSWORD" required>
        <img class="o" src="img/lock-solid-24.png" alt="">
        <button type="submit">SUBMIT</button>
    
    </div>
    </form>

    

</body>


</htm1>