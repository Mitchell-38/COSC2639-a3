<?php

// Initialise
session_start();
require_once "tools.php";

// $response = $s3Client->getObject([
//     "Bucket" => "s3858182-a3-emr",
//     "Key" => "output/part-r-00000"
// ]);

// echoArray($response);

if (array_key_exists("email", $_SESSION)) {
    header("Location: main.php");
}

// // Login attempt
if (array_key_exists("login", $_POST)) {

    $email = $_POST["email"];
    $password = $_POST["password"];

    $userInfo = fetchUserInfo($lambdaClient, "getUser", "login", $email);
    $userInfo = json_decode($userInfo, true);

    if (isset($userInfo["Item"])) {
        $userInfo = $userInfo["Item"];

        if ($userInfo["password"]["S"] == $password) {
            echo "Login successful";
            $_SESSION["email"] = $email;
            $_SESSION["username"] = $userInfo["user_name"]["S"];
            header("Location: main.php");
        } else {
            echo "Incorrect password";
        }
    } else {
        echo "Incorrect email address";
    }
} else if (array_key_exists("logout", $_POST)) {
    session_unset();
}

htmlHeader();
?>




<body>
    <h1>Login Page</h1>
    <form action="" method="post">
        <label>Email:</label>
        <input type="text" placeholder="Email Address" name="email" required>
        <label>Password:</label>
        <input type="password" placeholder="Password" name="password" required>
        <input type="submit" name="login" value="Log in">
        <a href="register.php">Register</a>
    </form>
</body>

</html>

<?
htmlFooter();
?>