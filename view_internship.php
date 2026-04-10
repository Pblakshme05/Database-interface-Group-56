<?php
include 'configdb.php';
include 'function.php';

// Correct table names (SINGULAR)
$sql = "SELECT 
            s.student_id,
            s.student_name,
            s.programme,
            a.name AS assessor_name
        FROM student s
        LEFT JOIN internship i ON s.student_id = i.student_id
        LEFT JOIN assessor a ON i.assessor_id = a.assessor_id";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Profiles</title>
    <style>
        body {
            font-family: Arial;
            background-color: #f8f9fa;
        }
        h2 {
            text-align: center;
        }
        table {
            border-collapse: collapse;
            width: 85%;
            margin: auto;
            background: white;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
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
        tr:hover {
            background-color: #f1f1f1;
        }
        .btn {
            padding: 6px 12px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<h2>Student Profiles</h2>

<table>
    <tr>
        <th>Student ID</th>
        <th>Name</th>
        <th>Programme</th>
        <th>Assessor</th>
        <th>Action</th>
    </tr>

<?php
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['student_id']}</td>
                <td>{$row['student_name']}</td>
                <td>{$row['programme']}</td>
                <td>" . (!empty($row['assessor_name']) ? $row['assessor_name'] : 'Not Assigned') . "</td>
                <td>
                    <a class='btn' href='student_profile.php?id={$row['student_id']}'>
                        View Profile
                    </a>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='5'>No records found</td></tr>";
}
?>

</table>

</body>
</html>