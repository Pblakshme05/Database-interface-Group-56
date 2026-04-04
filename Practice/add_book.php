<?php
include 'connection.php';
include 'functions.php'; // CRUD functions file
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Book</title>
</head>
<body>

<h2>Add Book</h2>

<form method="post" action="">
<label for="title">Title:</label>
<input type="text" name="title" id="title" required><br><br>

<label for="author">Author:</label>
<input type="text" name="author" id="author" required><br><br>

<label for="publication_year">Publication Year:</label>
<input type="number" name="publication_year" id="publication_year"><br><br>

<input type="submit" name="submit" value="Add Book">
</form>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = $_POST['title'];
    $author = $_POST['author'];
    $publicationYear = $_POST['publication_year'];

    if (empty($title) || empty($author)) {
        echo "Title and author are required.";
    } else {
        $result = createBook($title, $author, $publicationYear);

        if ($result) {
            echo "Book added successfully.";
        } else {
            echo "Failed to add book: " . $conn->error;
        }
    }
}
?>

</body>
</html>