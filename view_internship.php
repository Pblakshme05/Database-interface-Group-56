<?php
include 'configdb.php';
include 'function.php';

$conn = new mysqli("localhost", "root", "", "your_database");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT 
            s.student_id,
            s.name,
            s.programme,
            a.assessor_name
        FROM students s
        LEFT JOIN internships i ON s.student_id = i.student_id
        LEFT JOIN assessors a ON i.assessor_id = a.assessor_id";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Student Profiles</title>
    <style>
        table {
            border-collapse: collapse;
            width: 80%;
            margin: auto;
        }
        th, td {
            border: 1px solid black;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<h2 style="text-align:center;">Student Profiles</h2>

<table>
    <tr>
        <th>Student ID</th>
        <th>Name</th>
        <th>Programme</th>
        <th>Assessor</th>
    </tr>

    <?php
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['student_id']}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['programme']}</td>
                    <td>" . ($row['assessor_name'] ?? 'Not Assigned') . "</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='4'>No records found</td></tr>";
    }
    ?>

</table>

</body>
</html>

<?php $conn->close(); ?>