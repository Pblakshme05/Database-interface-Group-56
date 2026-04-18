<?php
session_start();
include '../configdb.php';

if (!isset($_SESSION['assessor_id'])) {
    header("Location: ../login.php");
    exit();
}

$assessor_name = $_SESSION['assessor_name'];

// Handle logout — destroys session and sends to Admin page
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../AdminPage/AdminPage.php");
    exit();
}

// Fetch all students with their final_result data
$stmt = $conn->query("
    SELECT 
        fr.result_id,
        fr.student_name,
        s.programme,
        i.company_name,
        a1.assessor_name AS assessor_1_name,
        fr.assessor_1_mark,
        a2.assessor_name AS assessor_2_name,
        fr.assessor_2_mark,
        fr.final_avg_mark
    FROM final_result fr
    JOIN Student s ON fr.student_name = s.student_name
    JOIN Internship i ON i.student_id = s.student_id
    LEFT JOIN Assessor a1 ON fr.assessor_1_id = a1.assessor_id
    LEFT JOIN Assessor a2 ON fr.assessor_2_id = a2.assessor_id
    ORDER BY fr.student_name ASC
");
$results = $stmt->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Final Results</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --bg: #f0ede8;
    --card: #ffffff;
    --ink: #1a1a1a;
    --ink-soft: #666;
    --accent: #2d2d6b;
    --accent-light: #e8e8f8;
    --gold: #c8a84b;
    --gold-light: #fdf6e3;
    --border: #e0dbd2;
    --green: #2d6b4a;
    --green-light: #e8f5ee;
    --pending: #92660a;
    --pending-bg: #fef9c3;
    --radius: 14px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
  }

  .topbar {
    background: var(--card);
    border-bottom: 1px solid var(--border);
    padding: 0 2rem;
    height: 60px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 10;
  }
  .topbar-left  { display: flex; align-items: center; gap: 1rem; }
  .topbar-right { display: flex; align-items: center; gap: 0.75rem; }

  .back-btn {
    display: flex; align-items: center; gap: 6px;
    font-size: 13px; color: var(--accent); text-decoration: none;
    font-weight: 500; padding: 6px 12px; border-radius: 8px;
    border: 1px solid var(--accent-light); background: var(--accent-light);
    transition: background 0.15s;
  }
  .back-btn:hover { background: #d8d8f0; }

  .logout-btn {
    display: flex; align-items: center; gap: 6px;
    font-size: 13px; color: #991b1b; text-decoration: none;
    font-weight: 500; padding: 6px 14px; border-radius: 8px;
    border: 1px solid #fecaca; background: #fee2e2;
    transition: background 0.15s; cursor: pointer;
  }
  .logout-btn:hover { background: #fecaca; }

  .page-title {
    font-family: 'Syne', sans-serif;
    font-size: 15px; font-weight: 700; color: var(--ink);
  }
  .assessor-pill {
    font-size: 12px; font-weight: 500;
    background: var(--gold-light); color: var(--gold);
    border: 1px solid #e8d99a;
    padding: 4px 12px; border-radius: 20px;
  }

  .main { max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem; }

  .section-header { margin-bottom: 2rem; }
  .section-header h1 {
    font-family: 'Syne', sans-serif;
    font-size: 1.9rem; font-weight: 800; line-height: 1.1;
  }
  .section-header p { font-size: 14px; color: var(--ink-soft); margin-top: 6px; }

  .stats-bar { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
  .stat-chip {
    background: var(--card); border: 1px solid var(--border);
    border-radius: 10px; padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
  }
  .stat-num {
    font-family: 'Syne', sans-serif;
    font-size: 1.5rem; font-weight: 800; color: var(--accent);
  }
  .stat-label { font-size: 12px; color: var(--ink-soft); line-height: 1.3; }

  .table-wrap {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
  }

  table { width: 100%; border-collapse: collapse; }
  thead tr { background: var(--accent); color: #fff; }
  thead th {
    font-family: 'Syne', sans-serif;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.08em;
    padding: 14px 18px; text-align: left;
  }
  tbody tr { border-bottom: 1px solid var(--border); transition: background 0.12s; }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: #fafaf8; }
  td { padding: 14px 18px; font-size: 14px; vertical-align: middle; }

  .stu-cell { display: flex; align-items: center; gap: 12px; }
  .avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--accent-light); flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif;
    font-size: 11px; font-weight: 700; color: var(--accent);
  }
  .stu-name { font-weight: 600; font-size: 14px; }
  .stu-prog { font-size: 11px; color: var(--ink-soft); }
  .company-tag {
    font-size: 11px; color: var(--gold);
    background: var(--gold-light); border: 1px solid #e8d99a;
    padding: 2px 7px; border-radius: 20px;
    display: inline-block; margin-top: 3px; font-weight: 500;
  }

  .mark-cell { text-align: center; }
  .mark-val { font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700; color: var(--accent); }
  .mark-name { font-size: 11px; color: var(--ink-soft); margin-top: 2px; }
  .mark-pending { font-size: 13px; color: #bbb; font-style: italic; }

  .final-cell { text-align: center; }
  .final-score { font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800; }
  .final-pending {
    font-size: 12px; color: var(--pending); background: var(--pending-bg);
    padding: 4px 10px; border-radius: 20px; display: inline-block; font-weight: 500;
  }

  .grade-badge { font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 20px; display: inline-block; margin-top: 4px; }
  .grade-A { background: var(--green-light); color: var(--green); }
  .grade-B { background: #e8f0fe; color: #1a56db; }
  .grade-C { background: var(--gold-light); color: #92660a; }
  .grade-D { background: #fff3e0; color: #b45309; }
  .grade-F { background: #fee2e2; color: #991b1b; }

  .status-both  { font-size: 11px; font-weight: 600; background: var(--green-light); color: var(--green); padding: 3px 9px; border-radius: 20px; }
  .status-one   { font-size: 11px; font-weight: 600; background: var(--pending-bg); color: var(--pending); padding: 3px 9px; border-radius: 20px; }
  .status-none  { font-size: 11px; font-weight: 600; background: #fee2e2; color: #991b1b; padding: 3px 9px; border-radius: 20px; }

  .mini-bar-bg  { height: 4px; background: var(--border); border-radius: 99px; overflow: hidden; margin-top: 4px; width: 80px; }
  .mini-bar-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, var(--accent), #6c63ff); }

  .empty-state { text-align: center; padding: 4rem 2rem; background: var(--card); border-radius: var(--radius); border: 1px dashed var(--border); }
  .empty-icon { font-size: 2.5rem; margin-bottom: 1rem; }
  .empty-state h3 { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; margin-bottom: 6px; }
  .empty-state p { font-size: 13px; color: var(--ink-soft); }

  @media (max-width: 700px) {
    table, thead, tbody, th, td, tr { display: block; }
    thead tr { display: none; }
    tbody tr { margin-bottom: 1rem; border: 1px solid var(--border); border-radius: 10px; padding: 12px; }
    td { padding: 6px 0; border: none; }
    td::before { content: attr(data-label); font-size: 11px; color: var(--ink-soft); display: block; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 2px; }
    .mark-cell, .final-cell { text-align: left; }
  }
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-left">
    <a href="../AssessorPage/AssessorPage.php" class="back-btn">← Dashboard</a>
    <span class="page-title">Final Results</span>
  </div>
  <div class="topbar-right">
    <span class="assessor-pill"><?= htmlspecialchars($assessor_name) ?></span>
    <a href="?logout=1" class="logout-btn"
       onclick="return confirm('Log out and return to the Admin page?')">
      ↩ Logout
    </a>
  </div>
</div>

<div class="main">

  <div class="section-header">
    <h1>Final Assessment Results</h1>
    <p>Combined results from both assessors. Final mark updates automatically as assessors submit.</p>
  </div>

  <?php
    $total     = count($results);
    $completed = count(array_filter($results, fn($r) => $r['assessor_1_mark'] !== null && $r['assessor_2_mark'] !== null));
    $pending   = $total - $completed;
  ?>

  <div class="stats-bar">
    <div class="stat-chip">
      <div class="stat-num"><?= $total ?></div>
      <div class="stat-label">Total<br>Students</div>
    </div>
    <div class="stat-chip">
      <div class="stat-num" style="color:var(--green)"><?= $completed ?></div>
      <div class="stat-label">Fully<br>Assessed</div>
    </div>
    <div class="stat-chip">
      <div class="stat-num" style="color:var(--pending)"><?= $pending ?></div>
      <div class="stat-label">Awaiting<br>2nd Assessor</div>
    </div>
  </div>

  <?php if ($total === 0): ?>
    <div class="empty-state">
      <div class="empty-icon">📊</div>
      <h3>No results yet</h3>
      <p>Final results will appear here once assessors begin submitting marks.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th style="text-align:center">Assessor 1</th>
          <th style="text-align:center">Assessor 2</th>
          <th style="text-align:center">Status</th>
          <th style="text-align:center">Final Mark</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($results as $r):
          $initials = implode('', array_map(
            fn($w) => strtoupper($w[0]),
            array_slice(explode(' ', trim($r['student_name'])), 0, 2)
          ));

          $a1_mark = $r['assessor_1_mark'];
          $a2_mark = $r['assessor_2_mark'];
          $final   = $r['final_avg_mark'];

          if ($a1_mark !== null && $a2_mark !== null) {
            $status_html = '<span class="status-both">✓ Complete</span>';
          } elseif ($a1_mark !== null || $a2_mark !== null) {
            $status_html = '<span class="status-one">⏳ 1 Pending</span>';
          } else {
            $status_html = '<span class="status-none">✗ Not Started</span>';
          }

          $grade = ''; $gc = '';
          if ($final !== null) {
            $f = floatval($final);
            if ($f >= 80)      { $grade = 'A'; $gc = 'grade-A'; }
            elseif ($f >= 70)  { $grade = 'B'; $gc = 'grade-B'; }
            elseif ($f >= 60)  { $grade = 'C'; $gc = 'grade-C'; }
            elseif ($f >= 50)  { $grade = 'D'; $gc = 'grade-D'; }
            else               { $grade = 'F'; $gc = 'grade-F'; }
          }
        ?>
        <tr>
          <td data-label="Student">
            <div class="stu-cell">
              <div class="avatar"><?= $initials ?></div>
              <div>
                <div class="stu-name"><?= htmlspecialchars($r['student_name']) ?></div>
                <div class="stu-prog"><?= htmlspecialchars($r['programme']) ?></div>
                <span class="company-tag">🏢 <?= htmlspecialchars($r['company_name']) ?></span>
              </div>
            </div>
          </td>

          <td data-label="Assessor 1" class="mark-cell">
            <?php if ($a1_mark !== null): ?>
              <div class="mark-val"><?= number_format($a1_mark, 2) ?></div>
              <div class="mark-name"><?= htmlspecialchars($r['assessor_1_name'] ?? '–') ?></div>
            <?php else: ?>
              <div class="mark-pending">–</div>
              <div class="mark-name"><?= htmlspecialchars($r['assessor_1_name'] ?? '–') ?></div>
            <?php endif; ?>
          </td>

          <td data-label="Assessor 2" class="mark-cell">
            <?php if ($a2_mark !== null): ?>
              <div class="mark-val"><?= number_format($a2_mark, 2) ?></div>
              <div class="mark-name"><?= htmlspecialchars($r['assessor_2_name'] ?? '–') ?></div>
            <?php else: ?>
              <div class="mark-pending">–</div>
              <div class="mark-name"><?= htmlspecialchars($r['assessor_2_name'] ?? '–') ?></div>
            <?php endif; ?>
          </td>

          <td data-label="Status" style="text-align:center">
            <?= $status_html ?>
          </td>

          <td data-label="Final Mark" class="final-cell">
            <?php if ($final !== null): ?>
              <div class="final-score" style="color:var(--accent)"><?= number_format($final, 2) ?></div>
              <div class="mini-bar-bg" style="margin:4px auto 0">
                <div class="mini-bar-fill" style="width:<?= floatval($final) ?>%"></div>
              </div>
              <span class="grade-badge <?= $gc ?>">Grade <?= $grade ?></span>
            <?php else: ?>
              <span class="final-pending">Not finalised</span>
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