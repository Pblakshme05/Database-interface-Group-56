<?php
session_start();
include '../configdb.php';
include '../function.php';

if (!isset($_SESSION['assessor_id']) || !isset($_SESSION['assessor_name'])) {
    header("Location: login.php");
    exit();
}

$assessor_name = $_SESSION['assessor_name'];

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
<title>Assigned Students</title>
<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    background: #f4f6fb;
}
.top-header {
    width: 100%;
    padding: 15px 40px;
    background: #0d1f3c;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}
.header-logo {
    width: 40px;
    height: 40px;
    border-radius: 10px;
}
.header-text {
    display: flex;
    flex-direction: column;
}
.main-title {
    font-size: 15px;
    font-weight: 600;
}
.sub-title {
    font-size: 12px;
    opacity: 0.6;
}
.page {
    display: flex;
    justify-content: center;
    margin-top: 40px;
    padding: 0 20px 40px;
}
.container {
    max-width: 1100px;
    width: 100%;
}
.header-box {
    margin-bottom: 15px;
    padding: 15px 20px;
    border-radius: 16px;
    background: #0d1f3c;
    border: 1px solid #dde3ef;
    color: white;
    text-align: center;
}
.card {
    padding: 20px;
    border-radius: 20px;
    background: #ffffff;
    border: 1px solid #dde3ef;
    box-shadow: 0px 2px 6px rgba(0,0,0,0.06);
}
table {
    width: 100%;
    border-collapse: collapse;
    color: #0d1f3c;
}
th {
    text-align: left;
    padding: 12px;
    font-size: 13px;
    color: #4a5f7a;
    border-bottom: 1px solid #dde3ef;
}
td {
    padding: 12px;
    border-bottom: 1px solid #f0f2f8;
    font-size: 14px;
}
tr:hover td {
    background: #f4f6fb;
}
.empty-row td {
    text-align: center;
    opacity: 0.6;
    padding: 24px;
}
.back-link {
    font-size: 13px;
    color: #0d1f3c;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 1rem;
    opacity: 0.6;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid #dde3ef;
    background: #e8e8f8;
    transition: opacity 0.2s, background 0.2s;
}
.back-link:hover { opacity: 1; background: #d8d8f0; }
</style>
</head>
<body>

<div class="top-header">
    <div class="header-left">
        <img src="../logo_img.png" class="header-logo">
        <div class="header-text">
            <div class="main-title">UNM Internship Portal</div>
            <div class="sub-title">Assigned Students</div>
        </div>
    </div>
</div>

<div class="page">
<div class="container">
    <a href="../AssessorPage/AssessorPage.php" class="back-link">← Back to Dashboard</a>
    <div class="header-box">
        <h2>My Assigned Students</h2>
    </div>
    <div class="card">
        <table>
            <tr>
                <th>Student ID</th>
                <th>Name</th>
                <th>Programme</th>
            </tr>

            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['student_id']}</td>
                            <td>{$row['student_name']}</td>
                            <td>{$row['programme']}</td>
                          </tr>";
                }
            } else {
                echo "<tr class='empty-row'><td colspan='3'>No students assigned to you yet.</td></tr>";
            }

            $stmt->close();
            $conn->close();
            ?>

        </table>
    </div>
</div>
</div>

</body>
</html>