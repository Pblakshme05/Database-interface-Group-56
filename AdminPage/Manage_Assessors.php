<?php
session_start();
include '../configdb.php';
include '../function.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../loginpage.php");
    exit();
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $id    = $_POST['id'];
    $name  = $_POST['name'];
    $email = $_POST['email'];
    if (empty($name) || empty($email)) {
        $message = "Name and email are required."; $messageType = "error";
    } else {
        $result = updateAssessor($id, $name, $email);
        $message = $result ? "Updated successfully." : "Failed to update: " . $conn->error;
        $messageType = $result ? "success" : "error";
    }
} elseif (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM Assessor WHERE assessor_id = ?");
    $stmt->bind_param("i", $deleteId);
    $deleteSuccess = $stmt->execute();
    $message = $deleteSuccess ? "Assessor deleted successfully." : "Failed to delete assessor.";
    $messageType = $deleteSuccess ? "success" : "error";
}

$editMode = false; $editRow = null;
if (isset($_GET['id'])) {
    $id   = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM Assessor WHERE assessor_id = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $res  = $stmt->get_result();
    if ($res->num_rows > 0) { $editRow = $res->fetch_assoc(); $editMode = true; }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update']) && !empty($_POST['id'])) {
    $id   = $_POST['id'];
    $stmt = $conn->prepare("SELECT * FROM Assessor WHERE assessor_id = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $res  = $stmt->get_result();
    if ($res->num_rows > 0) { $editRow = $res->fetch_assoc(); $editMode = true; }
}

$allAssessors = [];
$res = $conn->query("SELECT * FROM Assessor");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) $allAssessors[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Assessors</title>
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
    margin-right: 10px; vertical-align: middle; flex-shrink: 0;
  }
  .stu-name { font-weight: 600; font-size: 14px; color: var(--ink); }

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
    box-shadow: 0 2px 6px rgba(0,0,0,0.04); margin-top: 1.2rem;
  }
  .edit-title { font-size: 15px; font-weight: 700; color: var(--ink); margin-bottom: 1.2rem; }
  .form-row { display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap; }
  .form-group { flex: 1; min-width: 160px; }
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
    height: 44px; padding: 0 24px; border-radius: 10px;
    border: none; background: var(--accent); color: #fff;
    font-family: 'Poppins', sans-serif; font-weight: 600;
    font-size: 14px; cursor: pointer; transition: background 0.2s; white-space: nowrap;
  }
  .btn-save:hover { background: var(--accent-hover); }
  .btn-cancel {
    height: 44px; padding: 0 18px; border-radius: 10px;
    border: 1px solid var(--border); background: var(--bg);
    color: var(--ink-soft); font-family: 'Poppins', sans-serif;
    font-size: 14px; cursor: pointer; text-decoration: none;
    display: inline-flex; align-items: center; transition: background 0.15s;
  }
  .btn-cancel:hover { background: var(--accent-light); }

  .no-data { text-align: center; padding: 3rem; color: var(--ink-soft); font-size: 14px; }

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
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-left">
    <a href="../AdminPage/AdminPage.php" class="back-btn">← Dashboard</a>
    <span class="page-title">Manage Assessors</span>
  </div>
  <span class="admin-pill"><?= htmlspecialchars($_SESSION['admin_name']) ?></span>
</div>

<div class="main">
  <div class="section-header">
    <h1>Manage Assessors</h1>
  </div>

  <?php if (!empty($message)): ?>
    <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
      <?= $messageType === 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <?php if (isset($deleteSuccess)): ?>
    <div class="result-card">
      <h2>✓ Assessor Deleted Successfully</h2>
      <p>The assessor has been removed from the database.</p>
      <a href="Manage_Assessors.php" class="btn-return">← Return to Manage Assessors</a>
    </div>
  <?php else: ?>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Email</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (count($allAssessors) > 0): ?>
          <?php foreach ($allAssessors as $assessor):
            $initials = strtoupper(substr($assessor['assessor_name'], 0, 1));
          ?>
          <tr>
            <td style="color:var(--ink-soft); font-size:13px;"><?= htmlspecialchars($assessor['assessor_id']) ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div class="avatar"><?= $initials ?></div>
                <span class="stu-name"><?= htmlspecialchars($assessor['assessor_name']) ?></span>
              </div>
            </td>
            <td style="color:var(--ink-soft)"><?= htmlspecialchars($assessor['email']) ?></td>
            <td>
              <div class="actions">
                <a href="?id=<?= $assessor['assessor_id'] ?>" class="btn-action btn-edit">Edit</a>
                <a href="?delete=<?= $assessor['assessor_id'] ?>" class="btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this assessor?')">Delete</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4" class="no-data">No assessors found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($editMode && $editRow): ?>
  <div class="edit-card">
    <div class="edit-title">Edit Assessor — <?= htmlspecialchars($editRow['assessor_name']) ?></div>
    <form method="post">
      <input type="hidden" name="id" value="<?= htmlspecialchars($editRow['assessor_id']) ?>">
      <div class="form-row">
        <div class="form-group">
          <label>Assessor Name</label>
          <input type="text" name="name" value="<?= htmlspecialchars($editRow['assessor_name']) ?>" required>
        </div>
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($editRow['email']) ?>" required>
        </div>
        <button type="submit" name="update" class="btn-save">Update</button>
        <a href="Manage_Assessors.php" class="btn-cancel">Cancel</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <?php endif; ?>
</div>

</body>
</html>