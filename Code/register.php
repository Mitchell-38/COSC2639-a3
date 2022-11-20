<?php

session_start();
require_once("tools.php");


// Redirect if user is already logged in.
if (array_key_exists("email", $_SESSION)) {
    header("Location: main.php");
}

if (array_key_exists("register", $_POST)) {

    $email = htmlspecialchars($_POST["email"]);
    $username = htmlspecialchars($_POST["username"]);
    $password = htmlspecialchars($_POST["password"]);
    // Check if user details exist before registering. Add user info to database.
    $userInfo = fetchUserInfo($lambdaClient, "getUser", "login", $email);
    $userInfo = json_decode($userInfo, true);

    if (isset($userInfo["Item"])) {
        echo "Email is already registered.";
    } else {
        createUser($lambdaClient, $email, $username, $password);
        header("Location: index.php");
    }
}



htmlHeader();
?>
    <h1>Register Page</h1>
    <form action="" method="post">
        <label>Email:</label>
        <input type="text" name="email" placeholder="Email Address" required>
        <label?>Username:</label>
        <input type="text" name="username" placeholder="Username" required>
        <label>Password:</label>
        <input type="password" name="password" placeholder="Password" required>
        <input type="submit" name="register" value="Register">
        <a href="index.php">Log In</a>
    </form>


<?
htmlFooter();
?>