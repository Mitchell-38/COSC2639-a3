<?php

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

require __DIR__ . "/vendor/autoload.php";

use Aws\DynamoDb\DynamoDbClient;
use Aws\S3\S3Client;
use Aws\ElasticBeanstalk\ElasticBeanstalkClient;
use Aws\S3\Exception\S3Exception;
use Aws\Lambda\Exception\LambdaException;
use Aws\Iam\IamClient;
use Aws\Emr\EmrClient;

$sharedConfig = [
    'region' => 'us-east-1',
    'version' => 'latest'
];

$sdk = new Aws\Sdk($sharedConfig);
$ddbClient = $sdk->createDynamoDb();
$lambdaClient = $sdk->createLambda();
$s3Client = $sdk->createS3();
$beanstalkClient = $sdk->createElasticBeanstalk();
$emrClient = $sdk->createEmr();

function importNames($jsonFile)
{
    $json = file_get_contents($jsonFile);
    $obj = json_decode($json, true);
    return $obj;
}

function createBucket($s3Client, $bucketName)
{
    $results = $s3Client->createBucket([
        "Bucket" => $bucketName,
        "ACL" => "public-read"
    ]);
}

function echoArray($array)
{
    echo "<pre>";
    print_r($array);
    echo "</pre>";
}
function fetchUserInfo($lambdaClient, $functionName, $tableName, $email)
{

    $results = $lambdaClient->invoke([
        "FunctionName" => $functionName,
        "Payload" => "{
            \"email\": \"$email\",
            \"tableName\": \"$tableName\"
        }",
        "LogType" => "Tail"
    ]);

    return $results['Payload']->__toString();
}

function fetchPosts($lambdaClient)
{
    $results = $lambdaClient->invoke([
        "FunctionName" => "getPosts",
        "Payload" => "{}",
        "LogType" => "Tail"
    ]);

    return $results["Payload"]->__toString();
}

function postToForum($lambdaClient, $title, $content, $image, $username)
{

    $results = $lambdaClient->invoke([
        "FunctionName" => "postToForum",
        "Payload" => "{
            \"user\": \"$username\",
            \"title\": \"$title\",
            \"content\": \"$content\",
            \"image\": \"$image\"
        }",
        "LogType" => "Tail"

    ]);
    
    return $results;
}

function uploadPostImage($s3Client, $imageData, $username, $s3Bucket) {

    $imageName = $username . date("Y-m-d H:i:s") . ".png";

    $s3Client->putObject([
        "ACL" => "public-read",
        "Body" => $imageData,
        "Bucket" => $s3Bucket,
        "Key" => $imageName
    ]);

    return $imageName;

}

function createUser($lambdaClient, $email, $username, $password) {
    $results = $lambdaClient->invoke([
        "FunctionName" => "addUser",
        "Payload" => "{
            \"email\": \"$email\",
            \"username\": \"$username\",
            \"password\": \"$password\"
        }",
        "LogType" => "Tail"
    ]);

    return $results;
}

function countTable($ddbClient, $tableName) {
    $results = $ddbClient->DescribeTable([
        "TableName" => $tableName
    ]);
    $number = $results["Table"]["ItemCount"];
    return $number;
}


function htmlHeader() {

    echo <<<CDATA
    <html>
        <head>
            <link rel="stylesheet" href="style.css">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@500&display=swap" rel="stylesheet">
        </head>
        <body>
        <nav>
            <a href="main.php">Home<a>
            <a href="health.php">Community Health</a>
        </nav>
CDATA;
}

function htmlFooter() {
    echo <<<CDATA

    </body>
    </html>

CDATA;
    
}

function getImage($s3Client, $bucket, $key) {
    $results = $s3Client->getObject([
        "Bucket" => $bucket,
        "Key" => $key
    ]);

    return $results['Body']->__toString();
}
