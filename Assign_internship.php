<?php
include 'configdb.php';
include 'function.php';

$message = "";

// ================= FETCH STUDENTS AND ASSESSORS =================
$students_result = getStudents();
$assessors_result = getAssessors();

// ================= HANDLE SELECTED STUDENT =================
$selected_student_id = $_POST['student_id'] ?? "";
$student_name = "";

// Get student name if selected
if ($selected_student_id) {
    $sql_student = "SELECT student_name FROM Student WHERE student_id = ?";
    $res_student = executePreparedStatement($sql_student, [$selected_student_id]);
    if ($res_student instanceof mysqli_result && $res_student->num_rows > 0) {
        $student_name = $res_student->fetch_assoc()['student_name'];
    }
}

// ================= FETCH CURRENT ASSIGNMENTS =================
$current_assessors = [];
if ($student_name) {
    $res_current = getStudentAssessors($student_name);
    if ($res_current instanceof mysqli_result) {
        while ($row = $res_current->fetch_assoc()) {
            $current_assessors[] = $row['assessor_name'];
        }
    }
}

// ================= ASSIGN OR MODIFY =================
if ($student_name && isset($_POST['save_assessors'])) {
    $assessor_ids = $_POST['assessor_ids'] ?? [];

    if (!assignAssessorsToStudent($student_name, $assessor_ids)) {
        $message = "Please select exactly 2 assessors.";
    } else {
        $message = "Assessors updated successfully!";
        // Refresh current assignments
        $current_assessors = [];
        $res_current = getStudentAssessors($student_name);
        if ($res_current instanceof mysqli_result) {
            while ($row = $res_current->fetch_assoc()) {
                $current_assessors[] = $row['assessor_name'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign / Modify Assessors</title>
</head>
<body>

<h2>Assign / Modify Assessors for Student</h2>

<?php if ($message) echo "<p style='color:green;'>$message</p>"; ?>

<form method="post">

    <!-- STUDENT SELECTION -->
    <label>Select Student:</label>
    <select name="student_id" required onchange="this.form.submit()">
        <option value="">-- Select Student --</option>
        <?php
        if ($students_result instanceof mysqli_result) {
            while ($row = $students_result->fetch_assoc()) {
                $selected = ($row['student_id'] == $selected_student_id) ? "selected" : "";
                echo "<option value='{$row['student_id']}' $selected>{$row['student_name']}</option>";
            }
        }
        ?>
    </select>

    <br><br>

    <?php if ($student_name): ?>
        <!-- CURRENT ASSIGNMENTS -->
        <h3>Current Assessors:</h3>
        <?php if (count($current_assessors) > 0): ?>
            <ul>
                <?php foreach ($current_assessors as $name) echo "<li>$name</li>"; ?>
            </ul>
            <p>Select 2 assessors below and click ASSIGN to update.</p>
        <?php else: ?>
            <p>No assessors assigned yet. Select 2 below and click ASSIGN.</p>
        <?php endif; ?>

        <!-- ASSIGN / MODIFY ASSESSORS -->
        <h3>Assign / Modify Assessors:</h3>
        <?php
        if ($assessors_result instanceof mysqli_result) {
            $assessors_result->data_seek(0);
            while ($row = $assessors_result->fetch_assoc()) {
                echo "<input type='checkbox' name='assessor_ids[]' value='{$row['assessor_id']}'> {$row['assessor_name']}<br>";
            }
        }
        ?>

        <br>
        <input type="submit" name="save_assessors" value="Assign / Modify">

    <?php endif; ?>

</form>

<!-- LIMIT CHECK JS -->
<script>
document.querySelectorAll('input[name="assessor_ids[]"]').forEach(cb => {
    cb.addEventListener('change', function () {
        let checked = document.querySelectorAll('input[name="assessor_ids[]"]:checked');
        if (checked.length > 2) {
            alert("You can select only 2 assessors.");
            this.checked = false;
        }
    });
});
</script>

</body>
</html>