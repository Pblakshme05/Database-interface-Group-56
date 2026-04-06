<?php
$host = "127.0.0.1";;
$user = "root";
$password = "root";   // MAMP default
$database = "internship_db";
$port = 8889;         // VERY IMPORTANT for MAMP

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "";
?>
