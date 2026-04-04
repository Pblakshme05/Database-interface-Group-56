<?php
include 'connection.php';
include 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'];

    $result = deleteBook($id);

    if ($result) {
        echo "Book deleted successfully.";
        header("Location: list_books.php");
        exit;
    } else {
        echo "Failed to delete book: " . $conn->error;
    }

} else {

    // Check if ID exists
    if (!isset($_GET['id'])) {
        echo "No book ID provided.";
        exit;
    }

    $id = $_GET['id'];

    // ✅ FIX: use prepared statement
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
<title>Delete Book</title>
</head>
<body>

<h2>Delete Book</h2>

<p>Are you sure you want to delete the following book?</p>

<p><strong>Title:</strong> <?php echo $title; ?></p>
<p><strong>Author:</strong> <?php echo $author; ?></p>
<p><strong>Publication Year:</strong> <?php echo $publicationYear; ?></p>

<form method="post" action="">
<input type="hidden" name="id" value="<?php echo $id; ?>">
<input type="submit" name="submit" value="Delete Book">
</form>

</body>
</html>