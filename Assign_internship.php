<?php
// assign_internship.php
include 'configdb.php'; // Your DB connection

// Handle form submission
if (isset($_POST['assign'])) {
    $student_id = $_POST['student_id'];
    $assessor_ids = $_POST['assessor_ids']; // array of selected assessors

    if (count($assessor_ids) != 2) {
        $message = "Please select exactly 2 assessors.";
    } else {
        // Delete old assignments for this student
        mysqli_query($conn, "DELETE FROM student_assessors WHERE student_id = $student_id");

        // Insert new assignments
        foreach ($assessor_ids as $assessor_id) {
            mysqli_query($conn, "INSERT INTO student_assessors (student_id, assessor_id) VALUES ($student_id, $assessor_id)");
        }
        $message = "Assessors assigned successfully!";
    }
}

// Fetch all students
$students = mysqli_query($conn, "SELECT * FROM students");

// Fetch all assessors
$assessors = mysqli_query($conn, "SELECT * FROM assessors");

// Get selected student ID (for showing current assignments)
$selected_student_id = isset($_POST['student_id']) ? $_POST['student_id'] : 0;

// Fetch current assignments
$current_assessors = [];
if ($selected_student_id) {
    $result = mysqli_query($conn, "SELECT assessor_id FROM student_assessors WHERE student_id = $selected_student_id");
    while ($row = mysqli_fetch_assoc($result)) {
        $current_assessors[] = $row['assessor_id'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Assessors to Student</title>
</head>
<body>
<h2>Assign Assessors to Student</h2>

<?php if(isset($message)) echo "<p style='color:red;'>$message</p>"; ?>

<form method="post" action="">
    <label for="student">Select Student:</label>
    <select name="student_id" id="student" onchange="this.form.submit()">
        <option value="">-- Select Student --</option>
        <?php
        while($row = mysqli_fetch_assoc($students)) {
            $selected = ($row['id'] == $selected_student_id) ? "selected" : "";
            echo "<option value='".$row['id']."' $selected>".$row['name']."</option>";
        }
        ?>
    </select>
    <br><br>

    <label for="assessors">Select Assessors (2):</label>
    <select name="assessor_ids[]" id="assessors" multiple size="5" required>
        <?php
        // Reset pointer for multiple selection after student selection
        mysqli_data_seek($assessors, 0);
        while($row = mysqli_fetch_assoc($assessors)) {
            $selected = in_array($row['id'], $current_assessors) ? "selected" : "";
            echo "<option value='".$row['id']."' $selected>".$row['name']."</option>";
        }
        ?>
    </select>
    <br><br>

    <input type="submit" name="assign" value="Assign">
</form>

<?php
// Optional: display current assignments table
if ($selected_student_id) {
    echo "<h3>Current Assessors:</h3>";
    if(count($current_assessors) > 0){
        echo "<ul>";
        foreach($current_assessors as $assessor_id){
            $a = mysqli_query($conn, "SELECT name FROM assessors WHERE id = $assessor_id");
            $name = mysqli_fetch_assoc($a)['name'];
            echo "<li>$name</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No assessors assigned yet.</p>";
    }
}
?>
</body>
</html>