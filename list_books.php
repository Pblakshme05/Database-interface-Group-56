<?php
include 'connection.php';
include 'functions.php';
?>
<!DOCTYPE html>
<html>
<head>
<title>List Books</title>
</head>
<body>
<h2>List of Books</h2>
<table>
<tr>
<th>Title</th>
<th>Author</th>
<th>Publication Year</th>
<th>Actions</th>
</tr>
<?php
$result = getBooks();
if ($result->num_rows > 0) {
while ($row = $result->fetch_assoc()) {
echo "<tr>";
echo "<td>" . $row['title'] . "</td>";
echo "<td>" . $row['author'] . "</td>";
echo "<td>" . $row['publication_year'] . "</td>";
echo "<td>
<a href='update_book.php?id=" . $row['book_id'] .
"'>Update</a>
<a href='delete_book.php?id=" . $row['book_id'] . "'
onclick='return confirm(\"Are you sure you want to delete this
book?\")'>Delete</a>
</td>";
echo "</tr>";
}
} else {
echo "<tr><td colspan='4'>No books found.</td></tr>";
}
?>
</table>
</body>
</html>