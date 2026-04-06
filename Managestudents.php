<?php
include 'Configdb.php';
include 'Function.php';

// Handle POST requests for updating
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {

    $id = $_POST['id'];
    $name = $_POST['name'];
    $programme = $_POST['programme'];

    if (empty($name) || empty($programme)) {
        echo "Name and programme are required.";
    } else {
        $result = updateStudent($id, $name, $programme);
        if ($result) {
            echo "Student updated successfully.";
            header("Refresh:3; url=ManageStudents.php");
            exit;
        } else {
            echo "Failed to update student: " . $conn->error;
        }
    }

// Handle GET requests for deletion
} elseif (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    $sql = "DELETE FROM Student WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        echo "Student deleted successfully.";
        header("Refresh:2; url=ManageStudents.php");
        exit;
    } else {
        echo "Failed to delete student: " . $conn->error;
    }

// Handle GET requests for editing
} else {

    // If no ID is provided, show list of students
    if (!isset($_GET['id'])) {
        $sql = "SELECT * FROM Student";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<h2>Select a student (Edit/Delete) :</h2>";
            echo "<ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>" . htmlspecialchars($row['student_name']) . 
                     " (" . htmlspecialchars($row['programme']) . ") - " .
                     "<a href='?id=" . $row['student_id'] . "'>Edit</a> | " .
                     "<a href='?delete=" . $row['student_id'] . "' onclick=\"return confirm('Are you sure you want to delete this student?');\">Delete</a>" .
                     "</li>";
            }
            echo "</ul>";
        } else {
            echo "No students found.";
        }
        exit;
    }

    // Get student data for editing
    $id = $_GET['id'];
    $sql = "SELECT * FROM Student WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = $row['student_name'];
        $programme = $row['programme'];
    } else {
        echo "Student not found.";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Update Student</title>
</head>
<body>

<h2>Update Student</h2>

<form method="post" action="">
<input type="hidden" name="id" value="<?php echo $id; ?>">

<label for="name">Student Name:</label>
<input type="text" name="name" id="name" value="<?php echo $name; ?>" required><br><br>

<label for="programme">Programme:</label>
<input type="text" name="programme" id="programme" value="<?php echo $programme; ?>" required><br><br>

<input type="submit" name="update" value="Update Student">
</form>

</body>
</html>