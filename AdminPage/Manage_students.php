<?php
session_start();
include '../configdb.php';
include '../function.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../loginpage.php");
    exit();
}

$message = ""; $msgType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $id = $_POST['id']; $name = $_POST['name']; $programme = $_POST['programme'];
    if (empty($name) || empty($programme)) {
        $message = "Name and programme are required."; $msgType = "error"; $editMode = true;
    } else {
        $result = updateStudent($id, $name, $programme);
        if ($result) {
            $message = "Student updated successfully."; $msgType = "success";
            $stmt = $conn->prepare("SELECT * FROM Student WHERE student_id = ?");
            $stmt->bind_param("i", $id); $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) { $row = $res->fetch_assoc(); $name = $row['student_name']; $programme = $row['programme']; }
            $editMode = true;
        } else {
            $message = "Failed to update student."; $msgType = "error"; $editMode = true;
        }
    }
} elseif (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    $stmtGet = $conn->prepare("SELECT internship_id FROM internship WHERE student_id = ?");
    $stmtGet->bind_param("i", $deleteId); $stmtGet->execute();
    $resInternships = $stmtGet->get_result();
    while ($internRow = $resInternships->fetch_assoc()) {
        $intId = $internRow['internship_id'];
        $stmtDA = $conn->prepare("DELETE FROM assessment WHERE internship_id = ?");
        $stmtDA->bind_param("i", $intId); $stmtDA->execute();
    }
    $stmtDI = $conn->prepare("DELETE FROM internship WHERE student_id = ?");
    $stmtDI->bind_param("i", $deleteId); $stmtDI->execute();
    $stmt = $conn->prepare("DELETE FROM Student WHERE student_id = ?");
    $stmt->bind_param("i", $deleteId);
    $deleteSuccess = $stmt->execute();
    $message = $deleteSuccess ? "Student deleted successfully." : "Failed to delete student.";
    $msgType  = $deleteSuccess ? "success" : "error";
} elseif (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM Student WHERE student_id = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc(); $name = $row['student_name']; $programme = $row['programme']; $editMode = true;
    }
}

if (!isset($editMode)) {
    $allStudents = $conn->query("SELECT * FROM Student");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Students</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #f4f6fb;
    --card: #ffffff;
    --ink: #0d1f3c;
    --ink-soft: #4a5f7a;
    --accent: #0d1f3c;
    --accent-hover: #1e3560;
    --accent-light: #e8e8f8;
    --gold: #c8a84b;
    --gold-light: #fdf6e3;
    --border: #dde3ef;
    --green: #166534;
    --green-light: #dcfce7;
    --radius: 14px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }
  body, input, button { font-family: 'Poppins', sans-serif; }

  body { background: var(--bg); color: var(--ink); min-height: 100vh; }

  .topbar {
    background: var(--accent); border-bottom: 1px solid rgba(255,255,255,0.1);
    padding: 0 2rem; height: 60px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 10;
  }
  .topbar-left { display: flex; align-items: center; gap: 1rem; }
  .back-btn {
    display: flex; align-items: center; gap: 6px;
    font-size: 13px; color: #fff; text-decoration: none;
    font-weight: 500; padding: 6px 12px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.1);
    transition: background 0.15s;
  }
  .back-btn:hover { background: rgba(255,255,255,0.2); }
  .page-title { font-size: 15px; font-weight: 600; color: #fff; }
  .admin-pill {
    font-size: 12px; font-weight: 500;
    background: var(--gold-light); color: var(--gold);
    border: 1px solid #e8d99a; padding: 4px 12px; border-radius: 20px;
  }

  .main { max-width: 900px; margin: 2.5rem auto; padding: 0 20px 60px; }

  .section-header { margin-bottom: 1.5rem; }
  .section-header h1 { font-size: 1.3rem; font-weight: 700; color: var(--ink); }

  .alert {
    padding: 10px 16px; border-radius: 10px; font-size: 13px;
    margin-bottom: 1.2rem; display: flex; align-items: center; gap: 8px;
  }
  .alert-success { background: var(--green-light); color: var(--green); }
  .alert-error   { background: #fee2e2; color: #991b1b; }

  .table-wrap {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  }
  table { width: 100%; border-collapse: collapse; }
  thead tr { background: var(--accent); color: #fff; }
  thead th {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.08em; padding: 14px 18px; text-align: left;
  }
  tbody tr { border-bottom: 1px solid var(--border); transition: background 0.12s; }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: #f8f9fc; }
  td { padding: 13px 18px; font-size: 14px; vertical-align: middle; }

  .avatar {
    width: 34px; height: 34px; border-radius: 50%;
    background: var(--accent-light);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: var(--accent);
    margin-right: 10px; vertical-align: middle;
  }
  .badge {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 500;
    background: var(--accent-light); color: var(--accent);
  }

  .actions { display: flex; gap: 8px; }
  .btn-action {
    padding: 6px 14px; border-radius: 8px; border: none;
    cursor: pointer; font-size: 12px; font-weight: 500;
    font-family: 'Poppins', sans-serif; transition: opacity 0.2s;
    text-decoration: none; display: inline-block;
  }
  .btn-action:hover { opacity: 0.8; }
  .btn-edit   { background: var(--accent-light); color: var(--accent); }
  .btn-delete { background: #fee2e2; color: #991b1b; }

  .edit-card {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 1.8rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
    max-width: 520px; margin: 0 auto;
  }
  .edit-title { font-size: 15px; font-weight: 700; color: var(--ink); margin-bottom: 1.2rem; }
  .form-group { margin-bottom: 1.2rem; }
  .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--ink); margin-bottom: 6px; }
  .form-group input {
    width: 100%; height: 44px; border-radius: 10px;
    border: 1px solid var(--border); padding: 0 14px;
    background: var(--bg); color: var(--ink);
    font-size: 14px; font-family: 'Poppins', sans-serif;
    outline: none; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .form-group input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(13,31,60,0.08); }

  .btn-save {
    width: 100%; background: var(--accent); color: #fff;
    border: none; padding: 12px; border-radius: 10px;
    font-size: 15px; font-family: 'Poppins', sans-serif;
    font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 4px;
  }
  .btn-save:hover { background: var(--accent-hover); }

  .result-card {
    background: var(--card); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 3rem 2rem;
    text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  }
  .result-card h2 { font-size: 1.1rem; font-weight: 700; color: var(--green); margin-bottom: 8px; }
  .result-card p  { font-size: 13px; color: var(--ink-soft); margin-bottom: 1.5rem; }
  .btn-return {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 20px; border-radius: 10px;
    border: 1px solid var(--border); background: var(--accent-light);
    color: var(--accent); font-size: 13px; font-weight: 500;
    font-family: 'Poppins', sans-serif; text-decoration: none; transition: background 0.15s;
  }
  .btn-return:hover { background: #d8d8f0; }

  .no-data { text-align: center; padding: 3rem; color: var(--ink-soft); font-size: 14px; }
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-left">
    <a href="../AdminPage/AdminPage.php" class="back-btn">← Dashboard</a>
    <span class="page-title">Manage Students</span>
  </div>
  <span class="admin-pill"><?= htmlspecialchars($_SESSION['admin_name']) ?></span>
</div>

<div class="main">
  <div class="section-header">
    <h1>Manage Students</h1>
  </div>

  <?php if (!empty($message) && !isset($deleteSuccess)): ?>
    <div class="alert <?= $msgType === 'success' ? 'alert-success' : 'alert-error' ?>">
      <?= $msgType === 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <?php if (isset($deleteSuccess)): ?>
    <div class="result-card">
      <h2>✓ Student Deleted Successfully</h2>
      <p>The student and all related records have been removed from the database.</p>
      <a href="Manage_students.php" class="btn-return">← Return to Manage Students</a>
    </div>

  <?php elseif (isset($editMode) && $editMode): ?>
    <div class="edit-card">
      <div class="edit-title">Edit Student</div>
      <?php if (!empty($message)): ?>
        <div class="alert <?= $msgType === 'success' ? 'alert-success' : 'alert-error' ?>" style="margin-bottom:1rem;">
          <?= $msgType === 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>
      <form method="post" action="Manage_students.php">
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-group">
          <label>Student Name</label>
          <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
        </div>
        <div class="form-group">
          <label>Programme</label>
          <input type="text" name="programme" value="<?= htmlspecialchars($programme) ?>" required>
        </div>
        <button type="submit" name="update" class="btn-save">Save Changes</button>
      </form>
    </div>

  <?php else: ?>
    <div class="table-wrap">
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
            <td style="color:var(--ink-soft); font-size:13px;"><?= $i++ ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar"><?= $initials ?></div>
                <span style="font-weight:600"><?= htmlspecialchars($row['student_name']) ?></span>
              </div>
            </td>
            <td><span class="badge"><?= htmlspecialchars($row['programme']) ?></span></td>
            <td>
              <div class="actions">
                <a href="Manage_students.php?id=<?= $row['student_id'] ?>" class="btn-action btn-edit">Edit</a>
                <a href="Manage_students.php?delete=<?= $row['student_id'] ?>" onclick="return confirm('Delete this student and all related records?')" class="btn-action btn-delete">Delete</a>
              </div>
            </td>
          </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="4" class="no-data">No students found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

</body>
</html>