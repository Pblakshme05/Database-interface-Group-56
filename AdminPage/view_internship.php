<?php
session_start();
include '../configdb.php';
include '../function.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../loginpage.php");
    exit();
}

$sql = "SELECT 
            s.student_id,
            s.student_name,
            s.programme,
            GROUP_CONCAT(sa.assessor_name SEPARATOR ', ') AS assessor_list,
            i.company_name
        FROM Student s
        LEFT JOIN student_assessors sa ON s.student_name = sa.student_name
        LEFT JOIN Internship i ON i.student_id = s.student_id
        GROUP BY s.student_id, s.student_name, s.programme, i.company_name";

$result = $conn->query($sql);
$rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$total = count($rows);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Internships</title>
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
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 10;
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
  .page-title {
    font-size: 15px; font-weight: 600; color: #fff;
  }

  .main { max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem; }

  .section-header { margin-bottom: 2rem; }
  .section-header h1 {
    font-size: 1.3rem; font-weight: 700; color: var(--ink);
  }
  .section-header p { font-size: 13px; color: var(--ink-soft); margin-top: 4px; }

  .stats-bar { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
  .stat-chip {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  }
  .stat-num {
    font-size: 1.5rem; font-weight: 700; color: var(--accent);
  }
  .stat-label { font-size: 12px; color: var(--ink-soft); line-height: 1.3; }

  .table-wrap {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  }

  table { width: 100%; border-collapse: collapse; }

  thead tr { background: var(--accent); color: #fff; }
  thead th {
    font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.08em;
    padding: 14px 18px; text-align: left;
  }

  tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background 0.12s;
  }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: #f8f9fc; }

  td { padding: 14px 18px; font-size: 14px; vertical-align: middle; }

  .stu-cell { display: flex; align-items: center; gap: 12px; }
  .avatar {
    width: 38px; height: 38px; border-radius: 50%;
    background: var(--accent-light); flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: var(--accent);
  }
  .stu-name { font-weight: 600; font-size: 14px; color: var(--ink); }
  .stu-id { font-size: 11px; color: var(--ink-soft); margin-top: 2px; }

  .prog-tag {
    font-size: 11px; font-weight: 500;
    background: var(--accent-light); color: var(--accent);
    padding: 3px 10px; border-radius: 20px;
    display: inline-block;
  }

  .company-tag {
    font-size: 11px; font-weight: 500;
    background: var(--gold-light); color: var(--gold);
    border: 1px solid #e8d99a;
    padding: 3px 10px; border-radius: 20px;
    display: inline-block;
  }
  .no-company {
    font-size: 12px; color: #bbb; font-style: italic;
  }

  .assessor-tag {
    font-size: 11px; font-weight: 500;
    background: var(--green-light); color: var(--green);
    padding: 3px 10px; border-radius: 20px;
    display: inline-block; margin: 2px 2px 2px 0;
  }
  .no-assessor {
    font-size: 12px; color: #bbb; font-style: italic;
  }

  .empty-state {
    text-align: center; padding: 4rem 2rem;
    background: var(--card); border-radius: var(--radius);
    border: 1px dashed var(--border);
  }
  .empty-icon { font-size: 2.5rem; margin-bottom: 1rem; }
  .empty-state h3 {
    font-size: 1.1rem; font-weight: 700; margin-bottom: 6px; color: var(--ink);
  }
  .empty-state p { font-size: 13px; color: var(--ink-soft); }

  @media (max-width: 700px) {
    table, thead, tbody, th, td, tr { display: block; }
    thead tr { display: none; }
    tbody tr { margin-bottom: 1rem; border: 1px solid var(--border); border-radius: 10px; padding: 12px; }
    td { padding: 6px 0; border: none; }
    td::before { content: attr(data-label); font-size: 11px; color: var(--ink-soft); display: block; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 2px; }
  }
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-left">
    <a href="../AdminPage/AdminPage.php" class="back-btn">← Dashboard</a>
    <span class="page-title">View Internships</span>
  </div>
</div>

<div class="main">

  <div class="section-header">
    <h1>All Internship Records</h1>
    <p>Students, their assigned assessors and internship companies</p>
  </div>

  <div class="stats-bar">
    <div class="stat-chip">
      <div class="stat-num"><?= $total ?></div>
      <div class="stat-label">Total<br>Students</div>
    </div>
    <div class="stat-chip">
      <div class="stat-num" style="color:#166534">
        <?= count(array_filter($rows, fn($r) => !empty($r['company_name']))) ?>
      </div>
      <div class="stat-label">With<br>Internship</div>
    </div>
    <div class="stat-chip">
      <div class="stat-num" style="color:#854d0e">
        <?= count(array_filter($rows, fn($r) => empty($r['assessor_list']))) ?>
      </div>
      <div class="stat-label">No Assessor<br>Assigned</div>
    </div>
  </div>

  <?php if ($total === 0): ?>
    <div class="empty-state">
      <div class="empty-icon">📋</div>
      <h3>No records found</h3>
      <p>No student internship records available yet.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Student ID</th>
          <th>Programme</th>
          <th>Assessors</th>
          <th>Company</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row):
          $initials = implode('', array_map(
            fn($w) => strtoupper($w[0]),
            array_slice(explode(' ', trim($row['student_name'])), 0, 2)
          ));
          $assessors = !empty($row['assessor_list'])
            ? explode(', ', $row['assessor_list'])
            : [];
        ?>
        <tr>
          <td data-label="Student">
            <div class="stu-cell">
              <div class="avatar"><?= $initials ?></div>
              <div>
                <div class="stu-name"><?= htmlspecialchars($row['student_name']) ?></div>
              </div>
            </div>
          </td>
          <td data-label="Student ID">
            <div class="stu-id"><?= htmlspecialchars($row['student_id']) ?></div>
          </td>
          <td data-label="Programme">
            <span class="prog-tag"><?= htmlspecialchars($row['programme']) ?></span>
          </td>
          <td data-label="Assessors">
            <?php if (!empty($assessors)): ?>
              <?php foreach ($assessors as $a): ?>
                <span class="assessor-tag"><?= htmlspecialchars(trim($a)) ?></span>
              <?php endforeach; ?>
            <?php else: ?>
              <span class="no-assessor">Not assigned</span>
            <?php endif; ?>
          </td>
          <td data-label="Company">
            <?php if (!empty($row['company_name'])): ?>
              <span class="company-tag"><?= htmlspecialchars($row['company_name']) ?></span>
            <?php else: ?>
              <span class="no-company">No internship</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</div>
</body>
</html>