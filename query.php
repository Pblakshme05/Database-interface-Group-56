<?php
include 'connection.php';
function executeQuery($sql) {
global $conn;
$result = $conn->query($sql);
return $result;
}
$sql = "SELECT * FROM books";
$result = executeQuery($sql);
if ($result->num_rows > 0) {
while($row = $result->fetch_assoc()) {
echo "Book ID: " . $row["book_id"]. " - Title: " . $row["title"]. " -
Author: " . $row["author"]. " - Publication Year: " .
$row["publication_year"]. "<br>";
}
} else {
echo "No results found.";
}
?>