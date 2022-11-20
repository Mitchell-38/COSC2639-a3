<?php

session_start();
require_once("tools.php");

// // Redirect to login if user hasn't logged in.
if (!array_key_exists("email", $_SESSION)) {
    header("Location: index.php");
} else {
    $email = $_SESSION["email"];
    $username = $_SESSION["username"];
}

if (array_key_exists("title", $_POST)) {

    $title = $_POST["title"];
    $content = $_POST["content"];

    if (array_key_exists("img", $_FILES) && $_FILES["img"]["error"] == 0) {
        $imageName = uploadPostImage($s3Client, file_get_contents($_FILES["img"]["tmp_name"]), $username, $s3Bucket);
    } else {
        $imageName = "";
    } 

    $results = postToForum($lambdaClient, htmlspecialchars($title), htmlspecialchars($content), $imageName, $username);
}

htmlHeader();

echo <<<CDATA

        <h1>USER AREA</h1>
            <h2>$username</h2>
            <form method="post" action="index.php">
                <input type="submit" name="logout" value="Logout">
            </form>
            <form method="post" action="create.php">
                <input type="submit" name="post" value="Create Post">
            </form>
        <h1>POSTS</h1>

CDATA;


$results = fetchPosts($lambdaClient);
$results = json_decode($results, true);
$time = array_column($results, "time");
array_multisort($time, SORT_DESC, $results);

echo "<div class=\"post-box\">";

foreach ($results as $item) {
    $title = $item["title"];
    $user = $item["user"];
    $content = $item["content"];
    $image = $item["image"];


    if (strlen($image) > 0) {
        $image = "<img src=\"https://s3858182-a3-storage.s3.amazonaws.com/$image\">";
    } else {
        $image="";
    }
    echo <<<CDATA
    <div class="post">
        <h2>$title</h2>
        <i>$user</i>
        <p>$content</p>
        $image
    </div>


    CDATA;
}

echo "</div>";

// // Getting the information from each found song and displaying it.
// foreach ($songs as $song) {
//     $title = $song["title"]["S"];
//     $artist = $song["artist"]["S"];

//     $songInfo = $ddbClient->getItem([
//         "Key" => [
//             "artist" => [
//                 "S" => $artist
//             ],
//             "title" =>
//             [
//                 "S" => $title
//             ]
//         ],
//         "TableName" => "music"
//     ]);

//     $songInfo = $songInfo["Item"];

//     $songArtist = $songInfo["artist"]["S"];
//     $songTitle = $songInfo["title"]["S"];
//     $songYear = $songInfo["year"]["S"];
//     $songURL = $songInfo["web_url"]["S"];
//     $songImg = $songInfo["img_url"]["S"];

//     echo <<<CDATA
//     <form action="main.php" method="post">
//         <h3>$songTitle ($songYear)</h3>
//         <p>$songArtist</p><br>
//         <img src="$songImg"><br>
//         <a href="$songURL">Link</a>
//         <input type="hidden" name="title" value="$songTitle">
//         <input type="submit" name="remove" value="Remove">
//         <br><br>
//     </form>

// CDATA;
// }

// echo <<<CDATA
//         <h1>Query Area</h1>
//         <form action="" method="post">
//             <label>Artist:</label>
//             <input type="text" placeholder="Artist" name="artist">
//             <label>Title:</label>
//             <input type="text" placeholder="Title" name="title">
//             <label>Year:</label>
//             <input type="text" placeholder="Year" name="year">
//             <input type="submit" name="query" value="Query">
//         </form>
// CDATA;

// // Showing queried results if found.
// if (isset($scanResults)) {
//     $resultsCount = 0;
//     foreach ($scanResults as $song) {

//         $songArtist = $song["artist"]["S"];
//         $songTitle = $song["title"]["S"];
//         $songYear = $song["year"]["S"];
//         $songURL = $song["web_url"]["S"];
//         $songImg = $song["img_url"]["S"];

//         $resultsCount += 1;

//         echo <<<CDATA
//     <form action="main.php" method="post">
//         <h3>$songTitle ($songYear)</h3>
//         <p>$songArtist</p><br>
//         <img src="$songImg"><br>
//         <a href="$songURL">Link</a>
//         <input type="hidden" name="artist" value="$songArtist">
//         <input type="hidden" name="title" value="$songTitle">
//         <input type="submit" name="subscribe" value="Subscribe">
//         <br><br>
//     </form>

// CDATA;
//     }
//     if ($resultsCount == 0) {
//         echo "No result is retrieved. Please query again.";
//     }
// } else if (array_key_exists("query", $_POST)) {
//     echo "No result is retrieved. Please query again.";
// }

// echo <<<CDATA
//     </body>
// </html>

// CDATA;


htmlFooter();
?>