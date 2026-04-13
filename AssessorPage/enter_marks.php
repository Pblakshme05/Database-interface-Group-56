<?php
session_start();
include '../configdb.php';
include '../function.php';

if (!isset($_GET['id'])) {
    die("No student selected");
}

$student_id = $_GET['id'];
$assessor_name = $_SESSION['assessor_name'];

// STEP 1: Get internship_id
$stmt = $conn->prepare("SELECT internship_id FROM Internship WHERE student_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();

$internship_id = $data['internship_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // STEP 2: Create Assessment
    $comments = $_POST['comments'];

    $stmt = $conn->prepare("
        INSERT INTO Assessment (internship_id, comments)
        VALUES (?, ?)
    ");
    $stmt->bind_param("is", $internship_id, $comments);
    $stmt->execute();

    $assessment_id = $stmt->insert_id;

    // STEP 3: Insert Marks
    $criteria_marks = [
        1 => $_POST['communication'],
        2 => $_POST['technical'],
        3 => $_POST['teamwork'],
        4 => $_POST['problem_solving'],
        5 => $_POST['discipline'],
        6 => $_POST['report']
    ];

    $total = 0;

    $stmt = $conn->prepare("
        INSERT INTO Assessment_marks (assessment_id, criteria_id, mark)
        VALUES (?, ?, ?)
    ");

    foreach ($criteria_marks as $cid => $mark) {
        $stmt->bind_param("iid", $assessment_id, $cid, $mark);
        $stmt->execute();
        $total += $mark;
    }

    // STEP 4: Save total
    $stmt = $conn->prepare("
        UPDATE Assessment SET final_score = ? WHERE assessment_id = ?
    ");
    $stmt->bind_param("di", $total, $assessment_id);
    $stmt->execute();

    echo " Marks submitted successfully!";
}
?>

<form method="POST">
    Communication: <input type="number" name="communication"><br>
    Technical: <input type="number" name="technical"><br>
    Teamwork: <input type="number" name="teamwork"><br>
    Problem Solving: <input type="number" name="problem_solving"><br>
    Discipline: <input type="number" name="discipline"><br>
    Report: <input type="number" name="report"><br>
    Comments: <textarea name="comments"></textarea><br>

    <button type="submit">Submit</button>
</form>