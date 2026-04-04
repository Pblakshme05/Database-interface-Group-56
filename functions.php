<?php
include 'connection.php';

function executePreparedStatement($sql, $params) {
    global $conn;

    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }

    if (stripos($sql, "SELECT") === 0) {
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $stmt->execute();
    }

    $stmt->close();
    return $result;
}

function createBook($title, $author, $year) {
    $sql = "INSERT INTO books (title, author, publication_year) VALUES (?, ?, ?)";
    $params = [$title, $author, $year];
    return executePreparedStatement($sql, $params);
}

function getBooks() {
    $sql = "SELECT * FROM books";
    return executePreparedStatement($sql, []);
}

function updateBook($id, $title, $author, $year) {
    $sql = "UPDATE books SET title = ?, author = ?, publication_year = ? WHERE book_id = ?";
    $params = [$title, $author, $year, $id];
    return executePreparedStatement($sql, $params);
}

function deleteBook($id) {
    $sql = "DELETE FROM books WHERE book_id = ?";
    $params = [$id];
    return executePreparedStatement($sql, $params);
}
?>