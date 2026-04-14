<?php
session_start();
include '../configdb.php';

if (!isset($_SESSION['assessor_id'])) {
    header("Location: ../login.php");
    exit();
}

$assessor_id   = $_SESSION['assessor_id'];
$assessor_name = $_SESSION['assessor_name'];
$error         = '';
$success       = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $student_id = intval($_POST['student_id']);
    $comments   = trim($_POST['comments']);

    // Get internship_id
    $stmt = $conn->prepare("SELECT internship_id FROM Internship WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        $error = "No internship record found for this student. Please contact the admin.";
    } else {
        $internship_id = $row['internship_id'];

        // Check if this assessor already assessed this student
        $stmt = $conn->prepare("
            SELECT assessment_id FROM Assessment 
            WHERE internship_id = ? AND assessor_id = ?
        ");
        $stmt->bind_param("ii", $internship_id, $assessor_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if ($existing) {
            $error = "You have already assessed this student. Use Update Marks to edit.";
        } else {
            // Fetch criteria for weightage calculation
            $criteria_res = $conn->query("SELECT criteria_id, weightage FROM Assessment_criteria");
            $criteria_map = [];
            while ($c = $criteria_res->fetch_assoc()) {
                $criteria_map[$c['criteria_id']] = $c['weightage'];
            }

            // Calculate weighted score
            $weighted_total = 0;
            foreach ($criteria_map as $cid => $weight) {
                $mark = floatval($_POST['mark_' . $cid] ?? 0);
                $weighted_total += ($mark * $weight) / 100;
            }

            // Insert Assessment row
            $stmt = $conn->prepare("
                INSERT INTO Assessment (internship_id, assessor_id, comments, final_score)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iisd", $internship_id, $assessor_id, $comments, $weighted_total);
            $stmt->execute();
            $assessment_id = $stmt->insert_id;

            // Insert individual marks
            $stmt = $conn->prepare("
                INSERT INTO Assessment_marks (assessment_id, criteria_id, mark)
                VALUES (?, ?, ?)
            ");
            foreach ($criteria_map as $cid => $weight) {
                $mark = floatval($_POST['mark_' . $cid] ?? 0);
                $stmt->bind_param("iid", $assessment_id, $cid, $mark);
                $stmt->execute();
            }

            $success = "Marks submitted successfully!";
        }
    }
}

// Fetch assigned students using LEFT JOIN so students without internship still show
$students_query = $conn->prepare("
    SELECT s.student_id, s.student_name, s.programme,
           i.internship_id,
           CASE 
               WHEN i.internship_id IS NULL THEN 0
               ELSE (SELECT COUNT(*) FROM Assessment a 
                     WHERE a.internship_id = i.internship_id 
                       AND a.assessor_id = ?)
           END AS already_assessed
    FROM student_assessors sa
    JOIN Student s ON sa.student_name = s.student_name
    LEFT JOIN Internship i ON i.student_id = s.student_id
    WHERE sa.assessor_name = ?
    ORDER BY s.student_name
");
$students_query->bind_param("is", $assessor_id, $assessor_name);
$students_query->execute();
$students_result = $students_query->get_result();

// Fetch criteria for the form
$criteria_result = $conn->query("SELECT * FROM Assessment_criteria ORDER BY criteria_id");
$criteria_list   = $criteria_result->fetch_all(MYSQLI_ASSOC);

// Selected student (from GET or POST)
$selected_id = intval($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
$selected_student = null;
if ($selected_id) {
    $stmt = $conn->prepare("SELECT * FROM Student WHERE student_id = ?");
    $stmt->bind_param("i", $selected_id);
    $stmt->execute();
    $selected_student = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Enter Marks</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; background: #f4f4f4; color: #333; }
  .container { max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
  h2 { font-size: 1.3rem; font-weight: 600; margin-bottom: 1.5rem; }
  .card { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem; }
  .student-list a { display: flex; align-items: center; gap: 12px; padding: 10px 0;
    border-bottom: 1px solid #f0f0f0; text-decoration: none; color: inherit; }
  .student-list a:last-child { border-bottom: none; }
  .student-list a:hover { background: #f9f9f9; border-radius: 6px; padding-left: 6px; transition: padding 0.15s; }
  .avatar { width: 38px; height: 38px; border-radius: 50%; background: #dbeafe;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 600; color: #1d4ed8; flex-shrink: 0; }
  .stu-name { font-weight: 600; font-size: 14px; }
  .stu-prog { font-size: 12px; color: #888; }
  .done-badge { margin-left: auto; font-size: 11px; background: #dcfce7;
    color: #166534; padding: 2px 8px; border-radius: 20px; white-space: nowrap; }
  .pending-badge { margin-left: auto; font-size: 11px; background: #fef9c3;
    color: #854d0e; padding: 2px 8px; border-radius: 20px; white-space: nowrap; }
  .no-internship-badge { margin-left: auto; font-size: 11px; background: #fee2e2;
    color: #991b1b; padding: 2px 8px; border-radius: 20px; white-space: nowrap; }
  .criteria-row { display: flex; align-items: center; gap: 12px; margin-bottom: 1.2rem; }
  .criteria-label { width: 150px; flex-shrink: 0; }
  .criteria-label strong { display: block; font-size: 14px; }
  .criteria-label span { font-size: 11px; color: #999; }
  .criteria-row input[type=range] { flex: 1; accent-color: #6c63ff; }
  .slider-val { min-width: 42px; text-align: right; font-weight: 600; font-size: 14px; }
  .comments-label { font-size: 14px; font-weight: 600; margin-bottom: 6px; }
  textarea { width: 100%; border: 1px solid #ddd; border-radius: 8px;
    padding: 10px; font-size: 14px; resize: vertical; min-height: 80px; }
  .submit-row { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
  .weighted-score { font-size: 13px; color: #666; }
  .weighted-score strong { font-size: 1.4rem; color: #333; }
  .btn-submit { background: #6c63ff; color: #fff; border: none;
    padding: 10px 28px; border-radius: 8px; font-size: 15px; cursor: pointer; }
  .btn-submit:hover { background: #5a52e0; }
  .alert { padding: 10px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 1rem; }
  .alert-success { background: #dcfce7; color: #166534; }
  .alert-error   { background: #fee2e2; color: #991b1b; }
  .back-link { font-size: 13px; color: #6c63ff; text-decoration: none; display: inline-block; margin-bottom: 1rem; }
  .section-title { font-size: 11px; color: #999; text-transform: uppercase;
    letter-spacing: 0.06em; margin-bottom: 1rem; }
  .no-students { text-align: center; color: #aaa; font-size: 14px; padding: 1rem 0; }
</style>
</head>
<body>
<div class="container">
  <a href="../AssessorPage/AssessorPage.php" class="back-link">← Back to Dashboard</a>
  <h2>Enter Student Marks</h2>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if (!$selected_student || $success): ?>
  <!-- STEP 1: Student list -->
  <div class="card">
    <div class="section-title">Select a student to assess</div>
    <div class="student-list">
      <?php
      $students_result->data_seek(0);
      $count = 0;
      while ($stu = $students_result->fetch_assoc()):
        $count++;
        $initials = implode('', array_map(
          fn($w) => strtoupper($w[0]),
          array_slice(explode(' ', trim($stu['student_name'])), 0, 2)
        ));
        $has_internship = !is_null($stu['internship_id']);
      ?>
      <a href="<?= $has_internship ? 'enter_marks.php?student_id=' . $stu['student_id'] : '#' ?>"
         <?= !$has_internship ? 'onclick="return false;" style="opacity:0.5;cursor:not-allowed;"' : '' ?>>
        <div class="avatar"><?= $initials ?></div>
        <div>
          <div class="stu-name"><?= htmlspecialchars($stu['student_name']) ?></div>
          <div class="stu-prog"><?= htmlspecialchars($stu['programme']) ?></div>
        </div>
        <?php if (!$has_internship): ?>
          <span class="no-internship-badge">No internship</span>
        <?php elseif ($stu['already_assessed']): ?>
          <span class="done-badge">Assessed</span>
        <?php else: ?>
          <span class="pending-badge">Pending</span>
        <?php endif; ?>
      </a>
      <?php endwhile; ?>
      <?php if ($count === 0): ?>
        <div class="no-students">No students assigned to you yet.</div>
      <?php endif; ?>
    </div>
  </div>

  <?php else: ?>
  <!-- STEP 2: Marks form -->
  <div class="card">
    <div class="section-title">Assessing</div>
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.5rem">
      <?php
        $initials = implode('', array_map(
          fn($w) => strtoupper($w[0]),
          array_slice(explode(' ', trim($selected_student['student_name'])), 0, 2)
        ));
      ?>
      <div class="avatar" style="width:44px;height:44px;font-size:14px"><?= $initials ?></div>
      <div>
        <div class="stu-name" style="font-size:16px"><?= htmlspecialchars($selected_student['student_name']) ?></div>
        <div class="stu-prog"><?= htmlspecialchars($selected_student['programme']) ?></div>
      </div>
    </div>

    <form method="POST" id="marksForm">
      <input type="hidden" name="student_id" value="<?= $selected_id ?>">

      <?php foreach ($criteria_list as $c): ?>
      <div class="criteria-row">
        <div class="criteria-label">
          <strong><?= htmlspecialchars($c['criteria_name']) ?></strong>
          <span>weight: <?= $c['weightage'] ?>%</span>
        </div>
        <input type="range"
               name="mark_<?= $c['criteria_id'] ?>"
               id="slider_<?= $c['criteria_id'] ?>"
               min="0" max="100" step="1" value="0"
               oninput="updateSlider(<?= $c['criteria_id'] ?>, this.value)">
        <div class="slider-val" id="val_<?= $c['criteria_id'] ?>">0%</div>
      </div>
      <?php endforeach; ?>

      <hr style="border:none;border-top:1px solid #eee;margin:1rem 0">

      <div class="comments-label">Comments</div>
      <textarea name="comments" placeholder="Optional notes about this student's performance..."></textarea>

      <div class="submit-row">
        <div class="weighted-score">
          Weighted score: <strong id="weightedDisplay">0.00</strong>
          <span style="font-size:12px;color:#aaa"> / 100</span>
        </div>
        <button type="submit" class="btn-submit">Submit Marks</button>
      </div>
    </form>
  </div>

  <script>
  const weightages = {
    <?php foreach ($criteria_list as $c): ?>
    <?= $c['criteria_id'] ?>: <?= $c['weightage'] ?>,
    <?php endforeach; ?>
  };

  function updateSlider(id, value) {
    document.getElementById('val_' + id).textContent = value + '%';
    recalcWeighted();
  }

  function recalcWeighted() {
    let total = 0;
    for (const [id, weight] of Object.entries(weightages)) {
      const slider = document.getElementById('slider_' + id);
      const mark = parseFloat(slider ? slider.value : 0);
      total += (mark * weight) / 100;
    }
    document.getElementById('weightedDisplay').textContent = total.toFixed(2);
  }

  recalcWeighted();
  </script>
  <?php endif; ?>

</div>
</body>
</html>