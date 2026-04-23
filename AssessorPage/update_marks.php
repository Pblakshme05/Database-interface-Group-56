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
        $error = "No internship record found for this student.";
    } else {
        $internship_id = $row['internship_id'];

        // Get existing assessment
        $stmt = $conn->prepare("
            SELECT assessment_id FROM Assessment 
            WHERE internship_id = ? AND assessor_id = ?
        ");
        $stmt->bind_param("ii", $internship_id, $assessor_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if (!$existing) {
            $error = "No existing assessment found. Please use Enter Marks instead.";
        } else {
            $assessment_id = $existing['assessment_id'];

            // Fetch criteria
            $criteria_res = $conn->query("SELECT criteria_id, weightage FROM Assessment_criteria");
            $criteria_map = [];
            while ($c = $criteria_res->fetch_assoc()) {
                $criteria_map[$c['criteria_id']] = $c['weightage'];
            }

            // Recalculate weighted score
            $weighted_total = 0;
            foreach ($criteria_map as $cid => $weight) {
                $mark = floatval($_POST['mark_' . $cid] ?? 0);
                $weighted_total += ($mark * $weight) / 100;
            }

            // Update Assessment
            $stmt = $conn->prepare("
                UPDATE Assessment SET comments = ?, final_score = ?
                WHERE assessment_id = ?
            ");
            $stmt->bind_param("sdi", $comments, $weighted_total, $assessment_id);
            $stmt->execute();

            // Update individual marks
            $stmt = $conn->prepare("
                UPDATE Assessment_marks SET mark = ?
                WHERE assessment_id = ? AND criteria_id = ?
            ");
            foreach ($criteria_map as $cid => $weight) {
                $mark = floatval($_POST['mark_' . $cid] ?? 0);
                $stmt->bind_param("dii", $mark, $assessment_id, $cid);
                $stmt->execute();
            }

            // ── SYNC final_result ─────────────────────────────────────────────

            // Get student name
            $sname_stmt = $conn->prepare("SELECT student_name FROM Student WHERE student_id = ?");
            $sname_stmt->bind_param("i", $student_id);
            $sname_stmt->execute();
            $sname_row = $sname_stmt->get_result()->fetch_assoc();
            $sname = $sname_row['student_name'];

            // Get the 2 assigned assessors for this student (ordered by assessor_id for consistency)
            $sa_stmt = $conn->prepare("
                SELECT a.assessor_id
                FROM student_assessors sa
                JOIN Assessor a ON sa.assessor_name = a.assessor_name
                WHERE sa.student_name = ?
                ORDER BY a.assessor_id ASC
            ");
            $sa_stmt->bind_param("s", $sname);
            $sa_stmt->execute();
            $assigned_assessors = $sa_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $a1_id = $assigned_assessors[0]['assessor_id'] ?? null;
            $a2_id = $assigned_assessors[1]['assessor_id'] ?? null;

            // Get all submitted marks for this internship
            $marks_stmt = $conn->prepare("
                SELECT assessor_id, final_score 
                FROM Assessment 
                WHERE internship_id = ?
            ");
            $marks_stmt->bind_param("i", $internship_id);
            $marks_stmt->execute();
            $all_marks = $marks_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Map assessor_id => final_score
            $markMap = [];
            foreach ($all_marks as $m) {
                $markMap[$m['assessor_id']] = $m['final_score'];
            }

            // Determine each assessor's mark (NULL if not yet submitted)
            $a1_mark = isset($markMap[$a1_id]) ? floatval($markMap[$a1_id]) : null;
            $a2_mark = ($a2_id && isset($markMap[$a2_id])) ? floatval($markMap[$a2_id]) : null;

            // Calculate final mark
            if ($a1_mark !== null && $a2_mark !== null) {
                $final_avg = round(($a1_mark + $a2_mark) / 2, 2);
            } elseif ($a1_mark !== null) {
                $final_avg = round($a1_mark, 2);
            } elseif ($a2_mark !== null) {
                $final_avg = round($a2_mark, 2);
            } else {
                $final_avg = null;
            }

            // Check if final_result row exists
            $chk = $conn->prepare("SELECT result_id FROM final_result WHERE student_name = ?");
            $chk->bind_param("s", $sname);
            $chk->execute();
            $existing_result = $chk->get_result()->fetch_assoc();

            if ($existing_result) {
                $upd = $conn->prepare("
                    UPDATE final_result 
                    SET assessor_1_id   = ?,
                        assessor_1_mark = ?,
                        assessor_2_id   = ?,
                        assessor_2_mark = ?,
                        final_avg_mark  = ?
                    WHERE student_name = ?
                ");
                $upd->bind_param("ididds", $a1_id, $a1_mark, $a2_id, $a2_mark, $final_avg, $sname);
                $upd->execute();
            } else {
                $ins = $conn->prepare("
                    INSERT INTO final_result 
                        (student_name, assessor_1_id, assessor_1_mark, assessor_2_id, assessor_2_mark, final_avg_mark)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $ins->bind_param("sididd", $sname, $a1_id, $a1_mark, $a2_id, $a2_mark, $final_avg);
                $ins->execute();
            }

            // ─────────────────────────────────────────────────────────────────

            $success = "Marks updated successfully!";
        }
    }
}

// Fetch only ASSESSED students for this assessor
$students_query = $conn->prepare("
    SELECT s.student_id, s.student_name, s.programme,
           i.internship_id,
           (SELECT COUNT(*) FROM Assessment a 
            WHERE a.internship_id = i.internship_id 
              AND a.assessor_id = ?) AS already_assessed
    FROM student_assessors sa
    JOIN Student s ON sa.student_name = s.student_name
    LEFT JOIN Internship i ON i.student_id = s.student_id
    WHERE sa.assessor_name = ?
    HAVING already_assessed > 0
    ORDER BY s.student_name
");
$students_query->bind_param("is", $assessor_id, $assessor_name);
$students_query->execute();
$students_result = $students_query->get_result();

// Fetch criteria
$criteria_result = $conn->query("SELECT * FROM Assessment_criteria ORDER BY criteria_id");
$criteria_list   = $criteria_result->fetch_all(MYSQLI_ASSOC);

// Selected student
$selected_id = intval($_GET['student_id'] ?? $_POST['student_id'] ?? 0);
$selected_student = null;
$existing_marks = [];
$existing_comments = '';

if ($selected_id) {
    $stmt = $conn->prepare("SELECT * FROM Student WHERE student_id = ?");
    $stmt->bind_param("i", $selected_id);
    $stmt->execute();
    $selected_student = $stmt->get_result()->fetch_assoc();

    // Fetch existing marks
    $stmt = $conn->prepare("
        SELECT am.criteria_id, am.mark, a.comments
        FROM Assessment a
        JOIN Assessment_marks am ON a.assessment_id = am.assessment_id
        JOIN Internship i ON a.internship_id = i.internship_id
        WHERE i.student_id = ? AND a.assessor_id = ?
    ");
    $stmt->bind_param("ii", $selected_id, $assessor_id);
    $stmt->execute();
    $marks_result = $stmt->get_result();
    while ($m = $marks_result->fetch_assoc()) {
        $existing_marks[$m['criteria_id']] = $m['mark'];
        $existing_comments = $m['comments'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Marks</title>
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
    color: #0d1f3c;
}

/* ── Top Header (from enter_marks) ── */
.top-header {
    width: 100%;
    padding: 15px 40px;
    background: #0d1f3c;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 10;
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
    font-family: 'Poppins', sans-serif;
    font-size: 15px;
    font-weight: 600;
}

.sub-title {
    font-size: 12px;
    opacity: 0.6;
    font-family: 'DM Sans', sans-serif;
}

.assessor-pill {
    font-size: 12px;
    font-weight: 500;
    background: #fdf6e3;
    color: #c8a84b;
    border: 1px solid #e8d99a;
    padding: 4px 14px;
    border-radius: 20px;
    font-family: 'DM Sans', sans-serif;
}

/* ── Container ── */
.container {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px 40px;
}

.back-link {
    font-size: 13px;
    color: #0d1f3c;
    text-decoration: none;
    display: inline-block;
    margin-bottom: 1rem;
    opacity: 0.6;
    font-family: 'DM Sans', sans-serif;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid #dde3ef;
    background: #e8e8f8;
    transition: opacity 0.2s, background 0.2s;
}
.back-link:hover { opacity: 1; background: #d8d8f0; }

h2 {
    font-family: 'Poppins', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: #0d1f3c;
}

/* ── Card ── */
.card {
    background: #ffffff;
    border: 1px solid #dde3ef;
    border-radius: 20px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0px 2px 6px rgba(0,0,0,0.06);
}

.section-title {
    font-size: 11px;
    color: #4a5f7a;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 1rem;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
}

/* ── Student list ── */
.student-list a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 8px;
    border-bottom: 1px solid #f0f2f8;
    text-decoration: none;
    color: inherit;
    border-radius: 8px;
    transition: background 0.15s;
}
.student-list a:last-child { border-bottom: none; }
.student-list a:hover { background: #f4f6fb; }

.avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #e0e8ff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Syne', sans-serif;
    font-size: 12px;
    font-weight: 700;
    color: #0d1f3c;
    flex-shrink: 0;
}

.stu-name {
    font-weight: 600;
    font-size: 14px;
    color: #0d1f3c;
    font-family: 'Syne', sans-serif;
}
.stu-prog {
    font-size: 12px;
    color: #4a5f7a;
    font-family: 'DM Sans', sans-serif;
}

.done-badge {
    margin-left: auto;
    font-size: 11px;
    background: #dcfce7;
    color: #166534;
    padding: 3px 10px;
    border-radius: 20px;
    white-space: nowrap;
    font-weight: 500;
    font-family: 'DM Sans', sans-serif;
}

/* ── Criteria rows ── */
.criteria-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 1.2rem;
}

.criteria-label { width: 150px; flex-shrink: 0; }
.criteria-label strong {
    display: block;
    font-size: 14px;
    color: #0d1f3c;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
}
.criteria-label span {
    font-size: 11px;
    color: #4a5f7a;
    font-family: 'DM Sans', sans-serif;
}

.criteria-row input[type=range] {
    flex: 1;
    accent-color: #0d1f3c;
}

.slider-val {
    min-width: 42px;
    text-align: right;
    font-weight: 600;
    font-size: 14px;
    color: #0d1f3c;
    font-family: 'Syne', sans-serif;
}

/* ── Comments ── */
.comments-label {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 6px;
    color: #0d1f3c;
    font-family: 'Poppins', sans-serif;
}

textarea {
    width: 100%;
    border: 1px solid #dde3ef;
    border-radius: 10px;
    padding: 10px;
    font-size: 14px;
    resize: vertical;
    min-height: 80px;
    font-family: 'Poppins', sans-serif;
    color: #0d1f3c;
    background: #f4f6fb;
    outline: none;
    transition: border-color 0.2s;
}
textarea:focus { border-color: #0d1f3c; }

/* ── Submit row ── */
.submit-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.5rem;
}

.weighted-score {
    font-size: 13px;
    color: #4a5f7a;
    font-family: 'DM Sans', sans-serif;
}
.weighted-score strong {
    font-family: 'Syne', sans-serif;
    font-size: 1.4rem;
    color: #0d1f3c;
}

.btn-submit {
    background: #0d1f3c;
    color: #fff;
    border: none;
    padding: 10px 28px;
    border-radius: 10px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-submit:hover { background: #1e3560; }

/* ── Alerts ── */
.alert {
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 14px;
    margin-bottom: 1rem;
    font-family: 'DM Sans', sans-serif;
}
.alert-success { background: #dcfce7; color: #166534; }
.alert-error   { background: #fee2e2; color: #991b1b; }

/* ── No students ── */
.no-students {
    text-align: center;
    color: #4a5f7a;
    font-size: 14px;
    padding: 1rem 0;
    font-family: 'DM Sans', sans-serif;
}
</style>
</head>
<body>

<div class="top-header">
    <div class="header-left">
        <img src="../logo_img.png" class="header-logo">
        <div class="header-text">
            <div class="main-title">UNM Internship Portal</div>
            <div class="sub-title">Update Marks</div>
        </div>
    </div>
    <span class="assessor-pill"><?= htmlspecialchars($assessor_name) ?></span>
</div>

<div class="container">
  <a href="../AssessorPage/AssessorPage.php" class="back-link">← Back to Dashboard</a>
  <h2>Update Student Marks</h2>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if (!$selected_student || $success): ?>
  <!-- STEP 1: Student list (only assessed) -->
  <div class="card">
    <div class="section-title">Select an assessed student to update</div>
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
      ?>
      <a href="update_marks.php?student_id=<?= $stu['student_id'] ?>">
        <div class="avatar"><?= $initials ?></div>
        <div>
          <div class="stu-name"><?= htmlspecialchars($stu['student_name']) ?></div>
          <div class="stu-prog"><?= htmlspecialchars($stu['programme']) ?></div>
        </div>
        <span class="done-badge">Assessed</span>
      </a>
      <?php endwhile; ?>
      <?php if ($count === 0): ?>
        <div class="no-students">No assessed students yet.</div>
      <?php endif; ?>
    </div>
  </div>

  <?php else: ?>
  <!-- STEP 2: Pre-filled marks form -->
  <div class="card">
    <div class="section-title">Updating marks for</div>
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

      <?php foreach ($criteria_list as $c): 
        $existing_mark = $existing_marks[$c['criteria_id']] ?? 0;
      ?>
      <div class="criteria-row">
        <div class="criteria-label">
          <strong><?= htmlspecialchars($c['criteria_name']) ?></strong>
          <span>weight: <?= $c['weightage'] ?>%</span>
        </div>
        <input type="range"
               name="mark_<?= $c['criteria_id'] ?>"
               id="slider_<?= $c['criteria_id'] ?>"
               min="0" max="100" step="1"
               value="<?= $existing_mark ?>"
               oninput="updateSlider(<?= $c['criteria_id'] ?>, this.value)">
        <div class="slider-val" id="val_<?= $c['criteria_id'] ?>"><?= $existing_mark ?>%</div>
      </div>
      <?php endforeach; ?>

      <hr style="border:none;border-top:1px solid #dde3ef;margin:1rem 0">

      <div class="comments-label">Comments</div>
      <textarea name="comments"><?= htmlspecialchars($existing_comments) ?></textarea>

      <div class="submit-row">
        <div class="weighted-score">
          Weighted score: <strong id="weightedDisplay">0.00</strong>
          <span style="font-size:12px;color:#4a5f7a"> / 100</span>
        </div>
        <button type="submit" class="btn-submit">Update Marks</button>
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