<?php

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ERROR | E_WARNING | E_PARSE);

require __DIR__ . "/vendor/autoload.php";

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\ElasticBeanstalk\ElasticBeanstalkClient;
use Aws\ElasticBeanstalk\Exception\ElasticBeanstalkException;
use Aws\S3\Exception\S3Exception;
use Aws\Lambda\Exception\LambdaException;
use Aws\DynamoDb\DynamoDbClient;
use Aws\S3\S3Client;
use Aws\Iam\IamClient;
use Aws\Emr\EmrClient;

// Service # 1 ELASTIC BEANSTALK

// Create Application
try {
    $results = $beanstalkClient->CreateApplication([
        "ApplicationName" => "A3"
    ]);

    echo "Application created" . "<br><br>";
} catch (ElasticBeanstalkException $e) {
    echo $e->getMessage() . "<br><br>";
}

// Create Environment
try {
    $results = $beanstalkClient->createEnvironment([
        "ApplicationName" => "A3",
        "EnvironmentName" => "A3-env",
        "OperationsRole" => $iamRole,
        "OptionSettings" => [
            [
                "Namespace" => "aws:autoscaling:launchconfiguration",
                "OptionName" => "IamInstanceProfile",
                //"ResourceName" => $iamRole,
                "Value" => "EMR_EC2_DefaultRole"
            ]
            ],
        "SolutionStackName" => "64bit Amazon Linux 2 v3.5.1 running PHP 8.1",
    ]);

    echo "Environment created." . "<br><br>";
} catch (ElasticBeanstalkException $e) {
    echo $e->getMessage() . "<br><br>";
}

// Upload application code to S3. 
try {
    $s3Client->getObject([
        "Bucket" => $s3Bucket,
        "Key" => "Code.zip"
    ]);

} catch (S3Exception $e) {
    echo $e->getMessage(). "<br><br>";

    $s3Client->putObject([
        "Bucket" => $s3Bucket,
        "Key" => "Code.zip",
        "Body" => file_get_contents("Code.zip")
    ]);
    
    echo "Source code uploaded.";
}   

// Create Beanstalk version from S3
try {
$result = $beanstalkClient->createApplicationVersion([
    "ApplicationName" => "A3",
    "AutoCreateApplication" => true,
    "Description" => "A3 Submission",
    "Process" => true,
    "SourceBundle" => [
        "S3Bucket" => $s3Bucket,
        "S3Key" => "Code.zip"
    ],
    "VersionLabel" => "1.1"
]);

    echo "Running version uploaded <br><br>";
} catch (ElasticBeanstalkException $e) {
    echo $e->getMessage() . "<br><br>";
}

// Deploy Beanstalk application
try {
    $result = $beanstalkClient->updateEnvironment([
        "ApplicationName" => "A3",
        "Description" => "A3 Submission",
        "EnvironmentName" => "A3-env",
        "VersionLabel" => "1.1"
    ]);
    echo "Application version deployed.<br><br>";
} catch (ElasticBeanstalkException $e) {
    echo $e->getMessage() . "<br><br>";
}

// Other Services
// S3
try {
    createBucket($s3Client, $s3Bucket);
    echo "S3 Bucket Created.<br><br>";
} catch (S3Exception $e) {
    echo $e->getMessage() . "<br><br>";
} finally {
    try {
        $results = $s3Client->getObject([
            "Bucket" => $s3Bucket,
            "Key" => "getUser.zip"
        ]);
    } catch (S3Exception $e) {
        echo $e->getMessage() . "<br><br>";

        $s3Client->putObject([
            "Bucket" => $s3Bucket,
            "Key" => "getUser.zip",
            "Body" => file_get_contents("getUser.zip")
        ]);
        echo "Lambda code uploaded<br><br>";
    }
    try {
        $results = $s3Client->getObject([
            "Bucket" => $s3Bucket,
            "Key" => "getPosts.zip"
        ]);
    } catch (S3Exception $e) {
        echo $e->getMessage() . "<br><br>";

        $s3Client->putObject([
            "Bucket" => $s3Bucket,
            "Key" => "getPosts.zip",
            "Body" => file_get_contents("getPosts.zip")
        ]);
        echo "Lambda code uploaded<br><br>";
    }
    try {
        $results = $s3Client->getObject([
            "Bucket" => $s3Bucket,
            "Key" => "postToForum.zip"
        ]);
    } catch (S3Exception $e) {
        echo $e->getMessage() . "<br><br>";

        $s3Client->putObject([
            "Bucket" => $s3Bucket,
            "Key" => "postToForum.zip",
            "Body" => file_get_contents("postToForum.zip")
        ]);
        echo "Lambda code uploaded<br><br>";
    }
    try {
        $results = $s3Client->getObject([
            "Bucket" => $s3Bucket,
            "Key" => "addUser.zip"
        ]);
    } catch (S3Exception $e) {
        echo $e->getMessage() . "<br><br>";

        $s3Client->putObject([
            "Bucket" => $s3Bucket,
            "Key" => "addUser.zip",
            "Body" => file_get_contents("addUser.zip")
        ]);
        echo "Lambda code uploaded<br><br>";
    }
    try {
        $results = $s3Client->getObject([
            "Bucket" => $s3Bucket,
            "Key" => "getPostUsernames.zip"
        ]);
    } catch (S3Exception $e) {
        echo $e->getMessage() . "<br><br>";

        $s3Client->putObject([
            "Bucket" => $s3Bucket,
            "Key" => "getPostUsernames.zip",
            "Body" => file_get_contents("getPostUsernames.zip")
        ]);
        echo "Lambda code uploaded<br><br>";
    }


}
// Map Reduce S3 setup
try {
    createBucket($s3Client, $mapReduceBucketName);
    echo "Map Reduce Bucket Created.<br><br>";

} catch (S3Exception $e) {
    echo $e->getMessage() . "<br><br>";
} finally {
    //A3 Files
    try {
        $results = $s3Client->getObject([
            "Bucket" => $mapReduceBucketName,
            "Key" => "code/PostCount.jar"
        ]);
    } catch (S3Exception $e) {
        echo $e->getMessage() . "<br><br>";

        $s3Client->putObject([
            "Bucket" => $mapReduceBucketName,
            "Key" => "code/PostCount.jar",
            "Body" => file_get_contents("EMR/PostCount.jar")
        ]);
        echo "Post Count Jar file uploaded.<br><br>";
    }

}

//DynamoDB 
try {
    $ddbClient->CreateTable([
        "AttributeDefinitions" => [
            [
                "AttributeName" => "email",
                "AttributeType" => "S"
            ]
        ],
        "KeySchema" => [
            [
                "AttributeName" => "email",
                "KeyType" => "HASH"
            ]
        ],
        "ProvisionedThroughput" => [
            "ReadCapacityUnits" => 1,
            "WriteCapacityUnits" => 1
        ],
        "TableName" => "login"
    ]);

    echo "login table created. <br><br>";
} catch (DynamoDbException $e) {
    echo $e->getMessage() . "<br><br>";
}

try {
    $ddbClient->CreateTable([
        "AttributeDefinitions" => [
            [
                "AttributeName" => "postID",
                "AttributeType" => "S"
            ]
        ],
        "KeySchema" => [
            [
                "AttributeName" => "postID",
                "KeyType" => "HASH"
            ]
        ],
        "ProvisionedThroughput" => [
            "ReadCapacityUnits" => 1,
            "WriteCapacityUnits" => 1
        ],
        "TableName" => "posts"
    ]);

    echo "posts table created. <br><br>";
} catch (DynamoDbException $e) {
    echo $e->getMessage() . "<br><br>";
}




// Service #2 LAMBDA
// Create lambda
try {
    $results = $lambdaClient->CreateFunction([
        "Code" => [
            "S3Bucket" => $s3Bucket,
            "S3Key" => "getUser.zip"
        ],
        "EphemeralStorage" => [
            "Size" => 512
        ],
        "FunctionName" => "getUser",
        "Handler" => "getUser.lambda_handler",
        "Role" => $iamRole,
        "Runtime" => "python3.8"

    ]);

    echo "Lambda getUser function created" . "<br><br>";
} catch (LambdaException $e) {
    echo $e->getMessage() . "<br><br>";
}

try {
    $results = $lambdaClient->CreateFunction([
        "Code" => [
            "S3Bucket" => $s3Bucket,
            "S3Key" => "getPosts.zip"
        ],
        "EphemeralStorage" => [
            "Size" => 512
        ],
        "FunctionName" => "getPosts",
        "Handler" => "getPosts.lambda_handler",
        "Role" => $iamRole,
        "Runtime" => "python3.8"
    ]);

    echo "Lambda getPosts function created" . "<br><br>";
} catch (LambdaException $e) {
    echo $e->getMessage() . "<br><br>";
}

try {
    $results = $lambdaClient->CreateFunction([
        "Code" => [
            "S3Bucket" => $s3Bucket,
            "S3Key" => "postToForum.zip"
        ],
        "EphemeralStorage" => [
            "Size" => 512
        ],
        "FunctionName" => "postToForum",
        "Handler" => "postToForum.lambda_handler",
        "Role" => $iamRole,
        "Runtime" => "python3.8"
    ]);

    echo "Lambda postToForum function created" . "<br><br>";
} catch (LambdaException $e) {
    echo $e->getMessage() . "<br><br>";
}

try {
    $results = $lambdaClient->CreateFunction([
        "Code" => [
            "S3Bucket" => $s3Bucket,
            "S3Key" => "addUser.zip"
        ],
        "EphemeralStorage" => [
            "Size" => 512
        ],
        "FunctionName" => "addUser",
        "Handler" => "addUser.lambda_handler",
        "Role" => $iamRole,
        "Runtime" => "python3.8"
    ]);

    echo "Lambda addUser function created" . "<br><br>";
} catch (LambdaException $e) {
    echo $e->getMessage() . "<br><br>";
}
try {
    $results = $lambdaClient->CreateFunction([
        "Code" => [
            "S3Bucket" => $s3Bucket,
            "S3Key" => "getPostUsernames.zip"
        ],
        "EphemeralStorage" => [
            "Size" => 512
        ],
        "FunctionName" => "getPostUsernames",
        "Handler" => "getPostUsernames.lambda_handler",
        "Role" => $iamRole,
        "Runtime" => "python3.8"
    ]);

    echo "Lambda getPostUsernames function created" . "<br><br>";
} catch (LambdaException $e) {
    echo $e->getMessage() . "<br><br>";
}


// Elastic MapReduce EMR








// ECS
// 1. Create docker container
// 2. Create ECR
// 3. Tag the container image.
// 4. Upload image to ECR
// 5. Deploy container on AWS ECS