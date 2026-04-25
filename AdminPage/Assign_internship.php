<?php
session_start();
include '../configdb.php';
include '../function.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../loginpage.php");
    exit();
}

$message = "";
$msgType = "";

$students_result  = getStudents();
$assessors_result = getAssessors();

$sql_companies    = "SELECT DISTINCT company_name FROM Internship ORDER BY company_name ASC";
$companies_result = executePreparedStatement($sql_companies, []);

$selected_student_id = $_POST['student_id'] ?? "";
$student_name        = "";
$current_assessors   = [];
$current_company     = "";
$is_locked           = false;

if ($selected_student_id) {
    $sql_student = "SELECT student_name FROM Student WHERE student_id = ?";
    $res_student = executePreparedStatement($sql_student, [$selected_student_id]);
    if ($res_student instanceof mysqli_result && $res_student->num_rows > 0) {
        $student_name = $res_student->fetch_assoc()['student_name'];
    }
}

if ($student_name) {
    $sql_company = "SELECT company_name FROM Internship WHERE student_id = ? LIMIT 1";
    $res_company = executePreparedStatement($sql_company, [$selected_student_id]);
    if ($res_company instanceof mysqli_result && $res_company->num_rows > 0) {
        $current_company = $res_company->fetch_assoc()['company_name'];
    }

    $res_current = getStudentAssessors($student_name);
    if ($res_current instanceof mysqli_result) {
        while ($row = $res_current->fetch_assoc()) {
            $current_assessors[] = $row['assessor_name'];
        }
    }

    $sql_lock = "SELECT assessor_1_mark, assessor_2_mark FROM final_result 
                 WHERE student_name = ? AND assessor_1_mark IS NOT NULL AND assessor_2_mark IS NOT NULL LIMIT 1";
    $res_lock = executePreparedStatement($sql_lock, [$student_name]);
    if ($res_lock instanceof mysqli_result && $res_lock->num_rows > 0) {
        $is_locked = true;
    }
}

if ($student_name && isset($_POST['save_assessors'])) {
    if ($is_locked) {
        $message = "Cannot change assessors — both assessors have already submitted marks for this student.";
        $msgType  = "error";
    } else {
        $company_updated   = false;
        $assessors_updated = false;
        $errors            = [];

        $company_select       = trim($_POST['company_select'] ?? "");
        $company_new          = trim($_POST['company_new'] ?? "");
        $company_name_to_save = ($company_select === "__new__") ? $company_new : $company_select;

        if (!empty($company_name_to_save)) {
            $sql_check_intern = "SELECT internship_id FROM Internship WHERE student_id = ? LIMIT 1";
            $res_check_intern = executePreparedStatement($sql_check_intern, [$selected_student_id]);
            if ($res_check_intern instanceof mysqli_result && $res_check_intern->num_rows > 0) {
                $existing = $res_check_intern->fetch_assoc();
                executePreparedStatement("UPDATE Internship SET company_name = ? WHERE internship_id = ?", [$company_name_to_save, $existing['internship_id']]);
            } else {
                executePreparedStatement("INSERT INTO Internship (company_name, student_id) VALUES (?, ?)", [$company_name_to_save, $selected_student_id]);
            }
            $current_company = $company_name_to_save;
            $company_updated = true;
        } elseif ($company_select === "__new__" && empty($company_new)) {
            $errors[] = "Please enter a name for the new company.";
        }

        $assessor_ids = $_POST['assessor_ids'] ?? [];
        if (!empty($assessor_ids)) {
            if (count($assessor_ids) !== 2) {
                $errors[] = "Please select exactly 2 assessors.";
            } else {
                if (assignAssessorsToStudent($student_name, $assessor_ids)) {
                    $assessors_updated = true;
                    $current_assessors = [];
                    $res_current = getStudentAssessors($student_name);
                    if ($res_current instanceof mysqli_result) {
                        while ($row = $res_current->fetch_assoc()) {
                            $current_assessors[] = $row['assessor_name'];
                        }
                    }
                } else {
                    $errors[] = "Failed to update assessors.";
                }
            }
        }

        if (!empty($errors)) {
            $message = implode(" ", $errors);
            $msgType  = "error";
        } elseif ($company_updated || $assessors_updated) {
            $parts = [];
            if ($company_updated)   $parts[] = "Company";
            if ($assessors_updated) $parts[] = "Assessors";
            $message = implode(" and ", $parts) . " updated successfully!";
            $msgType  = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assign Internship</title>
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
  body, input, select, button { font-family: 'Poppins', sans-serif; }

  body {
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
  }

  .topbar {
    background: var(--accent);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding: 0 2rem; height: 60px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 10;
  }
  .topbar-left { display: flex; align-items: center; gap: 1rem; }
  .back-btn {
    display: flex; align-items: center; gap: 6px;
    font-size: 14px; color: #fff; text-decoration: none;
    font-weight: 500; padding: 6px 12px; border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.1);
    transition: background 0.15s;
  }
  .back-btn:hover { background: rgba(255,255,255,0.2); }
  .page-title { font-size: 16px; font-weight: 600; color: #fff; }
  .admin-pill {
    font-size: 13px; font-weight: 500;
    background: var(--gold-light); color: var(--gold);
    border: 1px solid #e8d99a; padding: 4px 12px; border-radius: 20px;
  }

  .main { max-width: 620px; margin: 3rem auto; padding: 0 20px 60px; }

  .section-header { margin-bottom: 1.5rem; }
  .section-header h1 { font-size: 1.5rem; font-weight: 700; color: var(--ink); }

  .alert {
    padding: 10px 16px; border-radius: 10px;
    font-size: 14px; margin-bottom: 1.2rem;
    display: flex; align-items: center; gap: 8px;
  }
  .alert-success { background: var(--green-light); color: var(--green); }
  .alert-error   { background: #fee2e2; color: #991b1b; }

  .card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.8rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    margin-bottom: 1.2rem;
  }

  .card-title { font-size: 14px; font-weight: 600; color: var(--ink-soft); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 1rem; }

  .row-two { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

  .form-group { margin-bottom: 0; }
  .form-group label {
    display: block; font-size: 14px; font-weight: 600;
    color: var(--ink); margin-bottom: 6px;
  }
  .form-group select,
  .form-group input[type="text"] {
    width: 100%; height: 46px; border-radius: 10px;
    border: 1px solid var(--border); padding: 0 14px;
    background: var(--bg); color: var(--ink);
    font-size: 15px; font-family: 'Poppins', sans-serif;
    outline: none; transition: border-color 0.2s, box-shadow 0.2s;
  }
  .form-group select:focus,
  .form-group input[type="text"]:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(13,31,60,0.08);
  }
  .form-group select {
    appearance: none; -webkit-appearance: none;
    background-image: url("data:image/svg+xml;utf8,<svg fill='%234a5f7a' height='20' viewBox='0 0 24 24' width='20' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
    background-repeat: no-repeat; background-position: right 12px center; background-size: 18px;
    padding-right: 36px; cursor: pointer;
  }
  .form-group select option { background: #fff; color: var(--ink); }

  .new-company-wrap { margin-top: 8px; display: none; }
  .new-company-wrap.visible { display: block; }

  .divider { border: none; border-top: 1px solid var(--border); margin: 1.2rem 0; }

  .summary-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 0; }
  .sub-label { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: var(--ink-soft); margin-bottom: 8px; }

  .chip {
    display: inline-block; padding: 4px 13px; border-radius: 20px;
    font-size: 13px; font-weight: 500;
    background: var(--green-light); color: var(--green);
    border: 1px solid #bbf7d0; margin: 2px 2px 2px 0;
  }
  .chip-company {
    display: inline-block; padding: 4px 13px; border-radius: 20px;
    font-size: 13px; font-weight: 500;
    background: var(--gold-light); color: var(--gold);
    border: 1px solid #e8d99a;
  }
  .chip-empty { font-size: 13px; color: #bbb; font-style: italic; }

  .checkbox-list { display: flex; flex-direction: column; gap: 8px; }
  .checkbox-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 10px;
    background: var(--bg); border: 1px solid var(--border);
    cursor: pointer; transition: background 0.15s, border-color 0.15s;
  }
  .checkbox-item:hover { background: var(--accent-light); border-color: #c2cde0; }
  .checkbox-item input[type="checkbox"] { width: 17px; height: 17px; accent-color: var(--accent); cursor: pointer; flex-shrink: 0; }
  .checkbox-item span { color: var(--ink); font-size: 15px; }

  .lock-notice {
    padding: 14px 16px; border-radius: 10px;
    background: #fefce8; border: 1px solid #fde68a;
    margin-top: 1rem;
  }
  .lock-notice h4 { font-size: 14px; font-weight: 600; color: #92400e; margin-bottom: 4px; }
  .lock-notice p  { font-size: 13px; color: #b45309; line-height: 1.5; }

  .btn-submit {
    width: 100%; background: var(--accent); color: #fff;
    border: none; padding: 12px; border-radius: 10px;
    font-size: 16px; font-family: 'Poppins', sans-serif;
    font-weight: 600; cursor: pointer; transition: background 0.2s; margin-top: 1rem;
  }
  .btn-submit:hover { background: var(--accent-hover); }
</style>
</head>
<body>

<div class="topbar">
  <div class="topbar-left">
    <a href="../AdminPage/AdminPage.php" class="back-btn">← Dashboard</a>
    <span class="page-title">Assign Internship</span>
  </div>
  <span class="admin-pill"><?= htmlspecialchars($_SESSION['admin_name']) ?></span>
</div>

<div class="main">
  <div class="section-header">
    <h1>Assign Internship</h1>
  </div>

  <?php if ($message): ?>
    <div class="alert <?= $msgType === 'success' ? 'alert-success' : 'alert-error' ?>">
      <?= $msgType === 'success' ? '✓' : '⚠' ?> <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <form method="post">

      <div class="row-two">
        <div class="form-group">
          <label>Select Student</label>
          <select name="student_id" onchange="this.form.submit()">
            <option value="">— Choose a student —</option>
            <?php
            if ($students_result instanceof mysqli_result) {
                while ($row = $students_result->fetch_assoc()) {
                    $sel = ($row['student_id'] == $selected_student_id) ? "selected" : "";
                    echo "<option value='{$row['student_id']}' $sel>" . htmlspecialchars($row['student_name']) . "</option>";
                }
            }
            ?>
          </select>
        </div>

        <div class="form-group">
          <label>Company Name</label>
          <select name="company_select" id="company_select" onchange="toggleNewCompany(this)">
            <option value="">— Select company —</option>
            <?php
            $company_list = [];
            if ($companies_result instanceof mysqli_result) {
                while ($row = $companies_result->fetch_assoc()) {
                    $company_list[] = $row['company_name'];
                }
            }
            foreach ($company_list as $cname) {
                $sel = ($cname === $current_company) ? "selected" : "";
                echo "<option value='" . htmlspecialchars($cname) . "' $sel>" . htmlspecialchars($cname) . "</option>";
            }
            ?>
            <option value="__new__">+ Add new company…</option>
          </select>
          <div class="new-company-wrap" id="new_company_wrap">
            <input type="text" name="company_new" id="company_new" placeholder="Enter new company name">
          </div>
        </div>
      </div>

      <?php if ($student_name): ?>
        <div class="divider"></div>

        <div class="summary-row">
          <div>
            <div class="sub-label">Current Company</div>
            <?php if ($current_company): ?>
              <span class="chip-company"><?= htmlspecialchars($current_company) ?></span>
            <?php else: ?>
              <span class="chip-empty">No company assigned yet</span>
            <?php endif; ?>
          </div>
          <div>
            <div class="sub-label">Current Assessors</div>
            <?php if (count($current_assessors) > 0): ?>
              <?php foreach ($current_assessors as $aname): ?>
                <span class="chip"><?= htmlspecialchars($aname) ?></span>
              <?php endforeach; ?>
            <?php else: ?>
              <span class="chip-empty">No assessors assigned yet</span>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($is_locked): ?>
          <div class="lock-notice">
            <h4>Cannot Assign New Assessors</h4>
            <p>Both assessors have already submitted marks. The assignment can no longer be changed.</p>
          </div>
        <?php else: ?>
          <div class="divider"></div>
          <div class="form-group">
            <label>Assign Assessors <span style="font-weight:400;color:var(--ink-soft)">(select exactly 2)</span></label>
            <div class="checkbox-list">
              <?php
              if ($assessors_result instanceof mysqli_result) {
                  $assessors_result->data_seek(0);
                  while ($row = $assessors_result->fetch_assoc()) {
                      echo "
                      <label class='checkbox-item'>
                        <input type='checkbox' name='assessor_ids[]' value='{$row['assessor_id']}'>
                        <span>" . htmlspecialchars($row['assessor_name']) . "</span>
                      </label>";
                  }
              }
              ?>
            </div>
          </div>
          <button type="submit" name="save_assessors" class="btn-submit">Save Internship Assignment</button>
        <?php endif; ?>
      <?php endif; ?>

    </form>
  </div>
</div>

<script>
function toggleNewCompany(sel) {
    const wrap  = document.getElementById('new_company_wrap');
    const input = document.getElementById('company_new');
    if (sel.value === '__new__') { wrap.classList.add('visible'); input.focus(); }
    else { wrap.classList.remove('visible'); input.value = ''; }
}
window.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('company_select');
    if (sel && sel.value === '__new__') document.getElementById('new_company_wrap').classList.add('visible');
});
document.querySelectorAll('input[name="assessor_ids[]"]').forEach(cb => {
    cb.addEventListener('change', function () {
        const checked = document.querySelectorAll('input[name="assessor_ids[]"]:checked');
        if (checked.length > 2) { alert("You can select only 2 assessors."); this.checked = false; }
    });
});
</script>
</body>
</html>