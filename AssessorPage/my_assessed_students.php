<?php
session_start();
include '../configdb.php';

if (!isset($_SESSION['assessor_id'])) {
    header("Location: ../login.php");
    exit();
}

$assessor_id   = $_SESSION['assessor_id'];
$assessor_name = $_SESSION['assessor_name'];

// Fetch students this assessor has assessed, along with THEIR OWN final_score only
$stmt = $conn->prepare("
    SELECT 
        s.student_id,
        s.student_name,
        s.programme,
        i.company_name,
        a.final_score,
        a.comments,
        a.assessment_id
    FROM student_assessors sa
    JOIN Student s ON sa.student_name = s.student_name
    JOIN Internship i ON i.student_id = s.student_id
    JOIN assessment a ON a.internship_id = i.internship_id AND a.assessor_id = ?
    WHERE sa.assessor_name = ?
    ORDER BY s.student_name ASC
");
$stmt->bind_param("is", $assessor_id, $assessor_name);
$stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Assessed Students</title>
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
    --radius: 14px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
  }

  /* ── Header ── */
  .topbar {
    background: var(--card);
    border-bottom: 1px solid var(--border);
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
    font-size: 13px; color: var(--accent); text-decoration: none;
    font-family: 'DM Sans', sans-serif; font-weight: 500;
    padding: 6px 12px; border-radius: 8px;
    border: 1px solid var(--accent-light);
    background: var(--accent-light);
    transition: background 0.15s;
  }
  .back-btn:hover { background: #d8d8f0; }
  .page-title {
    font-family: 'Syne', sans-serif;
    font-size: 15px; font-weight: 700;
    color: var(--ink); letter-spacing: 0.02em;
  }
  .assessor-pill {
    font-size: 12px; font-weight: 500;
    background: var(--gold-light); color: var(--gold);
    border: 1px solid #e8d99a;
    padding: 4px 12px; border-radius: 20px;
  }

  /* ── Main ── */
  .main { max-width: 960px; margin: 0 auto; padding: 2.5rem 1.5rem; }

  .section-header {
    margin-bottom: 2rem;
  }
  .section-header h1 {
    font-family: 'Syne', sans-serif;
    font-size: 1.9rem; font-weight: 800;
    color: var(--ink); line-height: 1.1;
  }
  .section-header p {
    font-size: 14px; color: var(--ink-soft); margin-top: 6px;
  }

  /* ── Stats bar ── */
  .stats-bar {
    display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;
  }
  .stat-chip {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 12px 20px;
    display: flex; align-items: center; gap: 10px;
  }
  .stat-num {
    font-family: 'Syne', sans-serif;
    font-size: 1.5rem; font-weight: 800; color: var(--accent);
  }
  .stat-label { font-size: 12px; color: var(--ink-soft); line-height: 1.3; }

  /* ── Cards ── */
  .cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.2rem;
  }

  .student-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.4rem;
    position: relative;
    transition: box-shadow 0.2s, transform 0.2s;
  }
  .student-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    transform: translateY(-2px);
  }

  .card-top {
    display: flex; align-items: flex-start;
    justify-content: space-between; margin-bottom: 1rem;
  }

  .avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: var(--accent-light);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif;
    font-size: 13px; font-weight: 700; color: var(--accent);
    flex-shrink: 0;
  }

  .score-badge {
    font-family: 'Syne', sans-serif;
    font-size: 1.4rem; font-weight: 800;
    color: var(--accent);
    line-height: 1;
  }
  .score-badge span { font-size: 11px; font-weight: 400; color: var(--ink-soft); }

  .stu-name {
    font-family: 'Syne', sans-serif;
    font-size: 15px; font-weight: 700;
    margin-bottom: 3px;
  }
  .stu-prog {
    font-size: 12px; color: var(--ink-soft);
    margin-bottom: 4px;
  }
  .stu-company {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 500;
    background: var(--gold-light); color: var(--gold);
    border: 1px solid #e8d99a;
    padding: 2px 8px; border-radius: 20px;
  }

  /* score bar */
  .score-bar-wrap {
    margin: 1rem 0 0.8rem;
  }
  .score-bar-label {
    display: flex; justify-content: space-between;
    font-size: 11px; color: var(--ink-soft); margin-bottom: 5px;
  }
  .score-bar-bg {
    height: 6px; background: var(--border); border-radius: 99px; overflow: hidden;
  }
  .score-bar-fill {
    height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, var(--accent), #6c63ff);
    transition: width 0.8s ease;
  }

  /* grade badge */
  .grade-badge {
    display: inline-block;
    font-size: 11px; font-weight: 600;
    padding: 3px 10px; border-radius: 20px;
    margin-bottom: 0.8rem;
  }
  .grade-A  { background: var(--green-light); color: var(--green); }
  .grade-B  { background: #e8f0fe; color: #1a56db; }
  .grade-C  { background: var(--gold-light); color: #92660a; }
  .grade-D  { background: #fff3e0; color: #b45309; }
  .grade-F  { background: #fee2e2; color: #991b1b; }

  .comments-box {
    background: var(--bg); border-radius: 8px;
    padding: 8px 10px; font-size: 12px; color: var(--ink-soft);
    font-style: italic; border-left: 3px solid var(--border);
    line-height: 1.5;
  }
  .no-comments { color: #bbb; font-size: 12px; font-style: italic; }

  /* empty state */
  .empty-state {
    text-align: center; padding: 4rem 2rem;
    background: var(--card); border-radius: var(--radius);
    border: 1px dashed var(--border);
  }
  .empty-icon { font-size: 2.5rem; margin-bottom: 1rem; }
  .empty-state h3 {
    font-family: 'Syne', sans-serif;
    font-size: 1.1rem; font-weight: 700; margin-bottom: 6px;
  }
  .empty-state p { font-size: 13px; color: var(--ink-soft); }
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-left">
    <a href="../AssessorPage/AssessorPage.php" class="back-btn">← Dashboard</a>
    <span class="page-title">My Assessed Students</span>
  </div>
  <span class="assessor-pill"><?= htmlspecialchars($assessor_name) ?></span>
</div>

<div class="main">

  <div class="section-header">
    <h1>Your Assessment Records</h1>
    <p>Showing only marks you have personally submitted</p>
  </div>

  <?php
    $count = count($results);
    $avg_score = $count > 0 ? round(array_sum(array_column($results, 'final_score')) / $count, 1) : 0;
  ?>

  <div class="stats-bar">
    <div class="stat-chip">
      <div class="stat-num"><?= $count ?></div>
      <div class="stat-label">Students<br>Assessed</div>
    </div>
    <?php if ($count > 0): ?>
    <div class="stat-chip">
      <div class="stat-num"><?= $avg_score ?></div>
      <div class="stat-label">Your Average<br>Score Given</div>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($count === 0): ?>
    <div class="empty-state">
      <div class="empty-icon">📋</div>
      <h3>No assessments yet</h3>
      <p>You haven't submitted marks for any students yet.</p>
    </div>
  <?php else: ?>
  <div class="cards-grid">
    <?php foreach ($results as $r):
      $initials = implode('', array_map(
        fn($w) => strtoupper($w[0]),
        array_slice(explode(' ', trim($r['student_name'])), 0, 2)
      ));
      $score = floatval($r['final_score']);

      // Grade
      if ($score >= 80)      { $grade = 'A'; $gc = 'grade-A'; }
      elseif ($score >= 70)  { $grade = 'B'; $gc = 'grade-B'; }
      elseif ($score >= 60)  { $grade = 'C'; $gc = 'grade-C'; }
      elseif ($score >= 50)  { $grade = 'D'; $gc = 'grade-D'; }
      else                   { $grade = 'F'; $gc = 'grade-F'; }
    ?>
    <div class="student-card">
      <div class="card-top">
        <div class="avatar"><?= $initials ?></div>
        <div style="text-align:right">
          <div class="score-badge"><?= number_format($score, 2) ?><span> / 100</span></div>
        </div>
      </div>

      <div class="stu-name"><?= htmlspecialchars($r['student_name']) ?></div>
      <div class="stu-prog"><?= htmlspecialchars($r['programme']) ?></div>
      <div style="margin-top:5px">
        <span class="stu-company">🏢 <?= htmlspecialchars($r['company_name']) ?></span>
      </div>

      <div class="score-bar-wrap">
        <div class="score-bar-label">
          <span>Score</span><span><?= number_format($score, 1) ?>%</span>
        </div>
        <div class="score-bar-bg">
          <div class="score-bar-fill" style="width:<?= $score ?>%"></div>
        </div>
      </div>

      <span class="grade-badge <?= $gc ?>">Grade <?= $grade ?></span>

      <div>
        <?php if (!empty($r['comments'])): ?>
          <div class="comments-box">"<?= htmlspecialchars($r['comments']) ?>"</div>
        <?php else: ?>
          <div class="no-comments">No comments added</div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>
</body>
</html>