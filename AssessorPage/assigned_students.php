<?php
session_start();
include '../configdb.php';
include '../function.php';
 
// Auth guard — redirect if assessor not logged in
if (!isset($_SESSION['assessor_id']) || !isset($_SESSION['assessor_name'])) {
    header("Location: login.php");
    exit();
}
 
$assessor_name = $_SESSION['assessor_name'];
 
// Fetch only students assigned to the logged-in assessor
$stmt = $conn->prepare("
    SELECT 
        s.student_id,
        s.student_name,
        s.programme
    FROM student_assessors sa
    JOIN Student s ON sa.student_name = s.student_name
    WHERE sa.assessor_name = ?
    ORDER BY s.student_name ASC
");
$stmt->bind_param("s", $assessor_name);
$stmt->execute();
$result = $stmt->get_result();
?>
 
<!DOCTYPE html>
<html>
<head>
    <title>My Assigned Students</title>
    <style>
        body { font-family: Arial; background-color: #f8f9fa; }
        h2 { text-align: center; }
        table {
            border-collapse: collapse;
            width: 85%;
            margin: auto;
            background: white;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
        }
        th {
            background-color: #343a40;
            color: white;
        }
        tr:hover { background-color: #f1f1f1; }
        .btn {
            padding: 6px 12px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
 
<h2>My Assigned Students</h2>
 
<table>
    <tr>
        <th>Student ID</th>
        <th>Name</th>
        <th>Programme</th>
        <th>Action</th>
    </tr>
 
<?php
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['student_id']}</td>
                <td>{$row['student_name']}</td>
                <td>{$row['programme']}</td>
                <td>
                    <a class='btn' href='student_profile.php?id={$row['student_id']}'>
                        View Profile
                    </a>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='4'>No students assigned to you yet.</td></tr>";
}
 
$stmt->close();
?>
 
</table>
 
</body>
</html>