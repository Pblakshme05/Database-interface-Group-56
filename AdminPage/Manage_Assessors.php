<?php
include '../configdb.php';
include '../function.php';


// Handle POST requests for updating
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {

    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];

    if (empty($name) || empty($email)) {
        echo "Name and email are required.";
    } else {
        $result = updateAssessor($id, $name, $email);
        if ($result) {
            echo "Assessor updated successfully.";
            header("Refresh:3; url=ManageAssessors.php");
            exit;
        } else {
            echo "Failed to update assessor: " . $conn->error;
        }
    }

// Handle GET requests for deletion
} elseif (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    $sql = "DELETE FROM Assessor WHERE assessor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        echo "Assessor deleted successfully.";
        header("Refresh:2; url=ManageAssessors.php");
        exit;
    } else {
        echo "Failed to delete assessor: " . $conn->error;
    }

// Handle GET requests for editing
} else {

    // If no ID is provided, show list of assessors
    if (!isset($_GET['id'])) {
        $sql = "SELECT * FROM Assessor";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<h2>Select an assessor (Edit/Delete) :</h2>";
            echo "<ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>" . htmlspecialchars($row['assessor_name']) . 
                     " (" . htmlspecialchars($row['email']) . ") - " .
                     "<a href='?id=" . $row['assessor_id'] . "'>Edit</a> | " .
                     "<a href='?delete=" . $row['assessor_id'] . "' onclick=\"return confirm('Are you sure you want to delete this assessor?');\">Delete</a>" .
                     "</li>";
            }
            echo "</ul>";
        } else {
            echo "No assessors found.";
        }
        exit;
    }

    // Get assessor data for editing
    $id = $_GET['id'];
    $sql = "SELECT * FROM Assessor WHERE assessor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = $row['assessor_name'];
        $email = $row['email'];
    } else {
        echo "Assessor not found.";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Update Assessor</title>
</head>
<body>

<h2>Update Assessor</h2>

<form method="post" action="">
<input type="hidden" name="id" value="<?php echo $id; ?>">

<label for="name">Assessor Name:</label>
<input type="text" name="name" id="name" value="<?php echo $name; ?>" required><br><br>

<label for="email">Email:</label>
<input type="email" name="email" id="email" value="<?php echo $email; ?>" required><br><br>

<input type="submit" name="update" value="Update Assessor">
</form>

</body>
</html>