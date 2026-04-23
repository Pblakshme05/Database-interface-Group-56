<?php
session_start();
include '../configdb.php';

if (!isset($_SESSION['assessor_id'])) {
    header("Location: ../login.php");
    exit();
}

$assessor_name = $_SESSION['assessor_name'];

// Fetch all students with their final_result data
// Join to get assessor names too
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
    --pending: #854d0e;
    --pending-bg: #fef9c3;
    --radius: 14px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
  }

  /* ── Header ── */
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
  .page-title {
    font-family: 'Poppins', sans-serif;
    font-size: 15px; font-weight: 600; color: #fff;
  }
  .assessor-pill {
    font-size: 12px; font-weight: 500;
    background: var(--gold-light); color: var(--gold);
    border: 1px solid #e8d99a;
    padding: 4px 12px; border-radius: 20px;
    font-family: 'Poppins', sans-serif;
  }

  /* ── Main ── */
  .main { max-width: 1100px; margin: 0 auto; padding: 2.5rem 1.5rem; }

  .section-header { margin-bottom: 2rem; }
  .section-header h1 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.3rem; font-weight: 700; color: var(--ink);
  }
  .section-header p { font-size: 13px; color: var(--ink-soft); margin-top: 4px; }

  /* ── Stats ── */
  .stats-bar { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
  .stat-chip {
    background: var(--card); border: 1px solid var(--border);
    border-radius: 10px; padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  }
  .stat-num {
    font-family: 'Poppins', sans-serif;
    font-size: 1.5rem; font-weight: 700; color: var(--accent);
  }
  .stat-label { font-size: 12px; color: var(--ink-soft); line-height: 1.3; }

  /* ── Table ── */
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
    font-family: 'Poppins', sans-serif;
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

  /* Student cell */
  .stu-cell { display: flex; align-items: center; gap: 12px; }
  .avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--accent-light); flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Poppins', sans-serif;
    font-size: 11px; font-weight: 700; color: var(--accent);
  }
  .stu-name { font-weight: 600; font-size: 14px; color: var(--ink); }
  .stu-prog { font-size: 11px; color: var(--ink-soft); }
  .company-tag {
    font-size: 11px; color: var(--gold);
    background: var(--gold-light); border: 1px solid #e8d99a;
    padding: 2px 7px; border-radius: 20px;
    display: inline-block; margin-top: 3px; font-weight: 500;
  }

  /* Mark cell */
  .mark-cell { text-align: center; }
  .mark-val {
    font-family: 'Poppins', sans-serif;
    font-size: 15px; font-weight: 700; color: var(--accent);
  }
  .mark-name { font-size: 11px; color: var(--ink-soft); margin-top: 2px; }
  .mark-pending { font-size: 13px; color: #bbb; font-style: italic; }

  /* Final mark cell */
  .final-cell { text-align: center; }
  .final-score {
    font-family: 'Poppins', sans-serif;
    font-size: 1.3rem; font-weight: 700;
  }
  .final-pending {
    font-size: 12px; color: var(--pending);
    background: var(--pending-bg);
    padding: 4px 10px; border-radius: 20px;
    display: inline-block; font-weight: 500;
  }

  /* Grade badge */
  .grade-badge {
    font-size: 11px; font-weight: 600;
    padding: 3px 9px; border-radius: 20px;
    display: inline-block; margin-top: 4px;
    font-family: 'Poppins', sans-serif;
  }
  .grade-A { background: var(--green-light); color: var(--green); }
  .grade-B { background: #e8f0fe; color: #1a56db; }
  .grade-C { background: var(--gold-light); color: #92660a; }
  .grade-D { background: #fff3e0; color: #b45309; }
  .grade-F { background: #fee2e2; color: #991b1b; }

  /* status pill */
  .status-both {
    font-size: 11px; font-weight: 600;
    background: var(--green-light); color: var(--green);
    padding: 3px 9px; border-radius: 20px;
    font-family: 'Poppins', sans-serif;
  }
  .status-one {
    font-size: 11px; font-weight: 600;
    background: var(--pending-bg); color: var(--pending);
    padding: 3px 9px; border-radius: 20px;
    font-family: 'Poppins', sans-serif;
  }
  .status-none {
    font-size: 11px; font-weight: 600;
    background: #fee2e2; color: #991b1b;
    padding: 3px 9px; border-radius: 20px;
    font-family: 'Poppins', sans-serif;
  }

  /* Score mini-bar inside table */
  .mini-bar-bg {
    height: 4px; background: var(--border);
    border-radius: 99px; overflow: hidden; margin-top: 4px; width: 80px;
  }
  .mini-bar-fill {
    height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, var(--accent), #3b5bdb);
  }

  /* empty */
  .empty-state {
    text-align: center; padding: 4rem 2rem;
    background: var(--card); border-radius: var(--radius);
    border: 1px dashed var(--border);
  }
  .empty-icon { font-size: 2.5rem; margin-bottom: 1rem; }
  .empty-state h3 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.1rem; font-weight: 700; margin-bottom: 6px; color: var(--ink);
  }
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
  <span class="assessor-pill"><?= htmlspecialchars($assessor_name) ?></span>
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
      <div class="stat-num" style="color:#166534"><?= $completed ?></div>
      <div class="stat-label">Fully<br>Assessed</div>
    </div>
    <div class="stat-chip">
      <div class="stat-num" style="color:#854d0e"><?= $pending ?></div>
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

          // Status
          if ($a1_mark !== null && $a2_mark !== null) {
            $status_html = '<span class="status-both">✓ Complete</span>';
          } elseif ($a1_mark !== null || $a2_mark !== null) {
            $status_html = '<span class="status-one">⏳ 1 Pending</span>';
          } else {
            $status_html = '<span class="status-none">✗ Not Started</span>';
          }

          // Grade
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
          <!-- Student -->
          <td data-label="Student">
            <div class="stu-cell">
              <div class="avatar"><?= $initials ?></div>
              <div>
                <div class="stu-name"><?= htmlspecialchars($r['student_name']) ?></div>
                <div class="stu-prog"><?= htmlspecialchars($r['programme']) ?></div>
                <span class="company-tag"> <?= htmlspecialchars($r['company_name']) ?></span>
              </div>
            </div>
          </td>

          <!-- Assessor 1 -->
          <td data-label="Assessor 1" class="mark-cell">
            <?php if ($a1_mark !== null): ?>
              <div class="mark-val"><?= number_format($a1_mark, 2) ?></div>
              <div class="mark-name"><?= htmlspecialchars($r['assessor_1_name'] ?? '–') ?></div>
            <?php else: ?>
              <div class="mark-pending">–</div>
              <div class="mark-name"><?= htmlspecialchars($r['assessor_1_name'] ?? '–') ?></div>
            <?php endif; ?>
          </td>

          <!-- Assessor 2 -->
          <td data-label="Assessor 2" class="mark-cell">
            <?php if ($a2_mark !== null): ?>
              <div class="mark-val"><?= number_format($a2_mark, 2) ?></div>
              <div class="mark-name"><?= htmlspecialchars($r['assessor_2_name'] ?? '–') ?></div>
            <?php else: ?>
              <div class="mark-pending">–</div>
              <div class="mark-name"><?= htmlspecialchars($r['assessor_2_name'] ?? '–') ?></div>
            <?php endif; ?>
          </td>

          <!-- Status -->
          <td data-label="Status" style="text-align:center">
            <?= $status_html ?>
          </td>

          <!-- Final Mark -->
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