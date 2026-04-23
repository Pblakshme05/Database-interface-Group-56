<?php
include '../configdb.php';
include '../function.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $programme = $_POST['programme'];

    if (empty($name) || empty($programme)) {
        $message = "Name and programme are required.";
        $msgType = "error";
        $editMode = true;
    } else {
        $result = updateStudent($id, $name, $programme);
        if ($result) {
            $message = "Student updated successfully.";
            $msgType = "success";
            $sql = "SELECT * FROM Student WHERE student_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $row = $res->fetch_assoc();
                $name = $row['student_name'];
                $programme = $row['programme'];
            }
            $editMode = true;
        } else {
            $message = "Failed to update student: " . $conn->error;
            $msgType = "error";
            $editMode = true;
        }
    }

} elseif (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];

    // Step 1: Get all internship_ids for this student
    $sqlGetInternships = "SELECT internship_id FROM internship WHERE student_id = ?";
    $stmtGet = $conn->prepare($sqlGetInternships);
    $stmtGet->bind_param("i", $deleteId);
    $stmtGet->execute();
    $resInternships = $stmtGet->get_result();

    // Step 2: Delete assessments linked to each internship
    while ($internRow = $resInternships->fetch_assoc()) {
        $intId = $internRow['internship_id'];
        $sqlDelAssessment = "DELETE FROM assessment WHERE internship_id = ?";
        $stmtDA = $conn->prepare($sqlDelAssessment);
        $stmtDA->bind_param("i", $intId);
        $stmtDA->execute();
    }

    // Step 3: Delete internship records for this student
    $sqlDelInternship = "DELETE FROM internship WHERE student_id = ?";
    $stmtDI = $conn->prepare($sqlDelInternship);
    $stmtDI->bind_param("i", $deleteId);
    $stmtDI->execute();

    // Step 4: Now safe to delete the student
    $sql = "DELETE FROM Student WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        $message = "Student deleted successfully.";
        $msgType = "success";
        $deleteSuccess = true;
    } else {
        $message = "Failed to delete student.";
        $msgType = "error";
        $deleteSuccess = true;
    }

} elseif (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM Student WHERE student_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = $row['student_name'];
        $programme = $row['programme'];
        $editMode = true;
    }
}

// Fetch all students for list view
if (!isset($editMode)) {
    $sql = "SELECT * FROM Student";
    $allStudents = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Students – UNM Portal</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body, input, button, select {
    font-family: 'Poppins', sans-serif;
}

body {
    min-height: 100vh;
    background-image: url('bg_image.png');
    background-size: cover;
    background-position: center 20%;
    background-repeat: no-repeat;
    background-attachment: fixed;
}

body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: rgba(10, 20, 60, 0.55);
    z-index: -1;
}

.top-header {
    width: 100%;
    padding: 15px 40px;
    background: rgba(15, 30, 70, 0.85);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
}
.header-left { display: flex; align-items: center; gap: 12px; }
.header-logo { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; }
.header-text { display: flex; flex-direction: column; }
.main-title { color: white; font-weight: 600; font-size: 16px; }
.sub-title { color: rgba(255,255,255,0.6); font-size: 12px; }

.page { display: flex; justify-content: center; margin-top: 50px; padding-bottom: 60px; }
.container { width: 900px; }

.section-title {
    margin-bottom: 15px;
    padding: 14px 20px;
    border-radius: 14px;
    background: rgba(25, 45, 95, 0.52);
    backdrop-filter: blur(22px);
    border: 1px solid rgba(255,255,255,0.13);
    color: white;
    font-weight: 600;
    font-size: 18px;
    text-align: center;
}

.card {
    background: rgba(25, 45, 95, 0.52);
    backdrop-filter: blur(22px);
    border-radius: 24px;
    padding: 28px;
    border: 1px solid rgba(255,255,255,0.13);
}

.toast {
    margin-bottom: 16px;
    padding: 12px 18px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 500;
}
.toast.success { background: rgba(80,200,120,0.25); color: #aaffcc; border: 1px solid rgba(80,200,120,0.3); }
.toast.error   { background: rgba(255,80,80,0.25);  color: #ffb3b3; border: 1px solid rgba(255,80,80,0.3); }

table { width: 100%; border-collapse: collapse; color: white; }
th {
    text-align: left;
    padding: 12px 14px;
    font-size: 12px;
    font-weight: 600;
    color: rgba(255,255,255,0.6);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid rgba(255,255,255,0.12);
}
td { padding: 13px 14px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
tr:hover td { background: rgba(255,255,255,0.04); }

.avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: rgba(80,140,255,0.25);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: #a0c4ff;
    margin-right: 10px; vertical-align: middle;
}

.badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
    background: rgba(80,140,255,0.2);
    color: #a0c4ff;
    border: 1px solid rgba(80,140,255,0.25);
}

.actions { display: flex; gap: 8px; }
.btn {
    padding: 6px 14px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    transition: opacity 0.2s;
    text-decoration: none;
    display: inline-block;
}
.btn:hover { opacity: 0.75; }
.btn-edit   { background: rgba(80,140,255,0.35); color: white; }
.btn-delete { background: rgba(255,80,80,0.35);  color: white; }
.btn-save   { background: rgba(80,200,120,0.35); color: white; padding: 10px 24px; font-size: 14px; width: 100%; margin-top: 8px; }

.btn-return {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 20px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.22);
    background: rgba(255,255,255,0.08);
    color: white;
    font-size: 13px;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    text-decoration: none;
    margin-bottom: 14px;
    transition: background 0.2s, border-color 0.2s;
}
.btn-return:hover {
    background: rgba(255,255,255,0.16);
    border-color: rgba(255,255,255,0.4);
    color: white;
}

.form-group { margin-bottom: 18px; }
.form-group label { display: block; color: rgba(255,255,255,0.75); font-size: 13px; margin-bottom: 7px; }
.form-group input {
    width: 100%;
    height: 46px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.18);
    padding: 0 14px;
    background: rgba(15,30,70,0.65);
    color: white;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    outline: none;
    transition: border-color 0.2s;
}
.form-group input:focus { border-color: rgba(80,140,255,0.6); }

.result-box {
    text-align: center;
    padding: 40px 20px;
    color: white;
}
.result-box .big-icon {
    font-size: 52px;
    margin-bottom: 16px;
}
.result-box h2 {
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 8px;
    color: #aaffcc;
}
.result-box p {
    color: rgba(255,255,255,0.5);
    font-size: 13px;
    margin-bottom: 24px;
}
</style>
</head>
<body>

<div class="top-header">
    <div class="header-left">
        <img src="../logo_img.png" class="header-logo">
        <div class="header-text">
            <div class="main-title">UNM Internship Portal</div>
        </div>
    </div>
</div>

<div class="page">
<div class="container">

<?php if (isset($deleteSuccess)): ?>

    <!-- DELETION PART -->
    <a href="http://localhost:8888/AdminPage/Manage_students.php" class="btn-return">&#8592; Return to Manage Students</a>
    <div class="section-title">Manage Students</div>
    <div class="card" style="max-width:480px; margin:0 auto;">
        <div class="result-box">
            <h2>Student Deleted Successfully</h2>
            <p>The student and all related records have been removed from the database.</p>
            <a href="http://localhost:8888/AdminPage/Manage_students.php" class="btn btn-return" style="margin-bottom:0;">&#8592; Return to Manage Students</a>
        </div>
    </div>

<?php elseif (isset($editMode) && $editMode): ?>

    <!-- EDITING PART -->
    <a href="http://localhost:8888/AdminPage/Manage_students.php" class="btn-return">&#8592; Return to Manage Students</a>
    <div class="section-title">Edit Student</div>
    <div class="card" style="max-width:480px; margin:0 auto;">
        <?php if (!empty($message)): ?>
            <div class="toast <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post" action="http://localhost:8888/AdminPage/Manage_students.php">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="form-group">
                <label>Student Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
            </div>
            <div class="form-group">
                <label>Programme</label>
                <input type="text" name="programme" value="<?= htmlspecialchars($programme) ?>" required>
            </div>
            <button type="submit" name="update" class="btn btn-save">Save Changes</button>
        </form>
    </div>

<?php else: ?>

    <!-- STUDENT LIST -->
    <div class="section-title">Manage Students</div>
    <div class="card">
        <?php if (!empty($message)): ?>
            <div class="toast <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Programme</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (isset($allStudents) && $allStudents->num_rows > 0):
                $i = 1;
                while ($row = $allStudents->fetch_assoc()):
                    $initials = strtoupper(substr($row['student_name'], 0, 1));
            ?>
                <tr>
                    <td style="color:rgba(255,255,255,0.4); font-size:13px;"><?= $i++ ?></td>
                    <td>
                        <span class="avatar"><?= $initials ?></span>
                        <?= htmlspecialchars($row['student_name']) ?>
                    </td>
                    <td><span class="badge"><?= htmlspecialchars($row['programme']) ?></span></td>
                    <td class="actions">
                        <a href="http://localhost:8888/AdminPage/Manage_students.php?id=<?= $row['student_id'] ?>" class="btn btn-edit">Edit</a>
                        <a href="http://localhost:8888/AdminPage/Manage_students.php?delete=<?= $row['student_id'] ?>" onclick="return confirm('Delete this student and all related records?')" class="btn btn-delete">Delete</a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="4" style="text-align:center; color:rgba(255,255,255,0.4); padding:30px;">No students found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

</div>
</div>
</body>
</html>