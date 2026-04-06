<?php
include 'Configdb.php';
include 'Function.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'];
    $name = $_POST['name'];
    $programme = $_POST['programme'];

    // Validate input
    if (empty($name) || empty($programme)) {
        echo "Name and programme are required.";
    } else {

        $result = updateStudent($id, $name, $programme);

        if ($result) {
            echo "Student updated successfully.";
            header("Refresh:2; url=Managestudents.php");
            exit;
        } else {
            echo "Failed to update student.";
        }
    }

} else {

    // Check if ID exists
    if (!isset($_GET['id'])) {
        echo "No student ID provided.";
        exit;
    }

    $id = $_GET['id'];

    // ✅ Get student data
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

<input type="submit" name="submit" value="Update Student">
</form>

</body>
</html>