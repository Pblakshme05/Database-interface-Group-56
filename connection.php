<?php
$servername = "localhost";
$port = 8889; // MAMP MySQL port
$username = "root";
$password = "root"; // make sure it's exactly "root" with quotes
$database = "book_library";

// Create connection
$conn = new mysqli($servername, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
else 
echo "Connected successfully";
?>

