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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name      = trim($_POST['name'] ?? '');
    $programme = trim($_POST['programme'] ?? '');

    if (empty($name) || empty($programme)) {
        $message = "Name and programme are required.";
        $messageType = "error";
    } else {
        $result = createStudent($name, $programme);
        if ($result) {
            $message = "Student info added successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to add student.";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Student</title>
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

  body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
  }

  .topbar {
    background: var(--accent);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding: 0 2rem;
    height: 60px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 10;
  }
  .topbar-left { display: flex; align-items: center; gap: 1rem; }
  .back-btn {
    display: flex; align-items: center; gap: 6px;
    font-size: 13px; color: #fff; text-decoration: none;
    font-weight: 500; padding: 6px 12px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.1);
    transition: background 0.15s;
  }
  .back-btn:hover { background: rgba(255,255,255,0.2); }
  .page-title { font-size: 15px; font-weight: 600; color: #fff; }
  .admin-pill {
    font-size: 12px; font-weight: 500;
    background: var(--gold-light); color: var(--gold);
    border: 1px solid #e8d99a;
    padding: 4px 12px; border-radius: 20px;
  }

  .main {
    max-width: 520px;
    margin: 3rem auto;
    padding: 0 20px 40px;
  }

  .section-header { margin-bottom: 1.5rem; }
  .section-header h1 { font-size: 1.3rem; font-weight: 700; color: var(--ink); }

  .card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 2rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  }

  .card-title { font-size: 16px; font-weight: 700; color: var(--ink); margin-bottom: 4px; }
  .card-sub { font-size: 13px; color: var(--ink-soft); margin-bottom: 1.8rem; }

  .field { margin-bottom: 1.2rem; }
  .field label {
    display: block; font-size: 13px; font-weight: 600;
    color: var(--ink); margin-bottom: 6px;
  }
  .field input {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    color: var(--ink);
    background: var(--bg);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .field input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(13,31,60,0.08);
  }
  .field input::placeholder { color: #aab; }

  .btn-submit {
    width: 100%;
    background: var(--accent);
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-size: 15px;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    margin-top: 0.5rem;
  }
  .btn-submit:hover { background: var(--accent-hover); }

  .alert {
    padding: 10px 16px; border-radius: 10px;
    font-size: 13px; margin-bottom: 1.2rem;
    display: flex; align-items: center; gap: 8px;
  }
  .alert-success { background: var(--green-light); color: var(--green); }
  .alert-error   { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-left">
    <a href="../AdminPage/AdminPage.php" class="back-btn">← Dashboard</a>
    <span class="page-title">Add Student</span>
  </div>
  <span class="admin-pill"><?= htmlspecialchars($_SESSION['admin_name']) ?></span>
</div>

<div class="main">
  <div class="section-header">
    <h1>Add Student</h1>
  </div>

  <?php if (!empty($message)): ?>
    <div class="alert <?= $messageType === 'success' ? 'alert-success' : 'alert-error' ?>">
      <?= $messageType === 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-title">Register a new student</div>
    <div class="card-sub">Fill in the details below to add a new student.</div>

    <form method="post">
      <div class="field">
        <label>Student Name</label>
        <input type="text" name="name" placeholder="Enter student name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Programme</label>
        <input type="text" name="programme" placeholder="Enter programme" value="<?= htmlspecialchars($_POST['programme'] ?? '') ?>">
      </div>
      <button type="submit" name="submit" class="btn-submit">Add Student</button>
    </form>
  </div>
</div>

</body>
</html>