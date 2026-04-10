<?php
include '../configdb.php';
include '../function.php';
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Student</title>
</head>
<body>

<h2>Add Student</h2>

<form method="post" action="">

<label for="name">Student Name:</label>
<input type="text" name="name" id="name" required><br><br>

<label for="programme">Programme:</label>
<input type="text" name="programme" id="programme" required><br><br>

<input type="submit" name="submit" value="Add Student">
</form>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $programme = $_POST['programme'];

    if (empty($name) || empty($programme)) {
        echo "Name and programme are required.";
    } else {
        $result = createStudent($name, $programme);

        if ($result) {
            echo "Student info added successfully.";
        } else {
            echo "Failed to add student.";
        }
    }
}
?>

</body>
</html>