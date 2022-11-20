<?php

session_start();
require_once("tools.php");
htmlHeader();
echo <<<CDATA

    <h1>Create Post</h1>
    <form action="main.php" method="post" enctype="multipart/form-data">
        <label>Title:</label>
        <input type="text" placeholder="Title" name="title" required>
        <label>Content:</label>
        <input type="text" placeholder="Content" name="content">
        <label>Image:</label>
        <input type="file" name="img" accept="image/*">
        <input type="submit" name="post" value="Post">
    </form>

CDATA;

?>


<?
htmlFooter();
?>