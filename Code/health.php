<?php 

session_start();
require_once("tools.php");

$numPosts = countTable($ddbClient, "posts");
$numAccounts = countTable($ddbClient, "login");

htmlHeader();


echo <<<CDATA
    <h1>Community Health</h1>
    <p?>Useful data visualiations for understanding the health of the community.</p>
    <h2>Number of Posts</h2>
    <p>$numPosts</p>
    <h2>Number of Accounts Created</h2>
    <p>$numAccounts</p>
    <h2>Top Posters</h2>

CDATA;
?>


<?
htmlFooter();
?>