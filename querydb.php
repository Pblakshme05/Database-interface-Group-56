<?php
include 'configdb.php';
include 'function.php';

function executeQuery($sql) {
    global $conn;
    return $conn->query($sql);
}


// JOIN multiple tables based on your ER diagram
$sql = "
SELECT 
    Internship.internship_id,
    Student.student_name,
    Internship.company_name,
    Assessor.assessor_name
FROM Internship
JOIN Student ON Internship.student_id = Student.student_id
JOIN Assessor ON Internship.assessor_id = Assessor.assessor_id
";

$result = executeQuery($sql);

$sql = "SELECT * FROM Student";
$result = $conn->query($sql);

echo "<h4> Student Data: <h4/>";

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["student_id"] .
             " - Name: " . $row["student_name"] .
             " - Programme: " . $row["programme"] . "<br>";
    }
} else {
    echo "No students found.";
}

// assessor info
$sql = "SELECT * FROM Assessor";
$result = $conn->query($sql);

echo "<h4> Assessor Data: <h4/>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["assessor_id"] .
             " - Name: " . $row["assessor_name"] .
             " - Email: " . $row["email"] . "<br>";
    }
} else {
    echo "No results found.";
}
?>