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

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row["student_id"] .
             " - Name: " . $row["student_name"] .
             " - Programme: " . $row["programme"] . "<br>";
    }
} else {
    echo "No students found.";
}

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Internship ID: " . $row["internship_id"] .
             " - Student: " . $row["student_name"] .
             " - Company: " . $row["company_name"] .
             " - Assessor: " . $row["assessor_name"] . "<br>";
    }
} else {
    echo "No results found.";
}
?>