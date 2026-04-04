<?php
include 'connection.php';
include 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $publicationYear = $_POST['publication_year'];

    // Validate input
    if (empty($title) || empty($author)) {
        echo "Title and author are required.";
    } else {

        $result = updateBook($id, $title, $author, $publicationYear);

        if ($result) {
            echo "Book updated successfully.";
            header("Refresh:3; url=list_books.php"); // Redirect after 3 seconds
            exit;
        } else {
            echo "Failed to update book: " . $conn->error;
        }
    }

} else {

    // Check if ID exists
    if (!isset($_GET['id'])) {
        echo "No book ID provided.";
        exit;
    }

    $id = $_GET['id'];

    // ✅ FIX: use SQL directly (since getBooks() has no parameter)
    $sql = "SELECT * FROM books WHERE book_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $title = $row['title'];
        $author = $row['author'];
        $publicationYear = $row['publication_year'];
    } else {
        echo "Book not found.";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Update Book</title>
</head>
<body>

<h2>Update Book</h2>

<form method="post" action="">
<input type="hidden" name="id" value="<?php echo $id; ?>">

<label for="title">Title:</label>
<input type="text" name="title" id="title" value="<?php echo $title; ?>" required><br><br>

<label for="author">Author:</label>
<input type="text" name="author" id="author" value="<?php echo $author; ?>" required><br><br>

<label for="publication_year">Publication Year:</label>
<input type="number" name="publication_year" id="publication_year" value="<?php echo $publicationYear; ?>"><br><br>

<input type="submit" name="submit" value="Update Book">
</form>

</body>
</html>