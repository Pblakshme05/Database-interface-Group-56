<?php
include '../configdb.php';
include '../function.php';

$message = "";
$msgType = "";

$students_result = getStudents();
$assessors_result = getAssessors();

// Fetch distinct company names from Internship table
$sql_companies = "SELECT DISTINCT company_name FROM Internship ORDER BY company_name ASC";
$companies_result = executePreparedStatement($sql_companies, []);

$selected_student_id = $_POST['student_id'] ?? "";
$student_name = "";
$current_assessors = [];
$current_company = "";
$is_locked = false;

if ($selected_student_id) {
    $sql_student = "SELECT student_name FROM Student WHERE student_id = ?";
    $res_student = executePreparedStatement($sql_student, [$selected_student_id]);
    if ($res_student instanceof mysqli_result && $res_student->num_rows > 0) {
        $student_name = $res_student->fetch_assoc()['student_name'];
    }
}

if ($student_name) {
    // Get current company assigned to this student
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
                 WHERE student_name = ? 
                 AND assessor_1_mark IS NOT NULL 
                 AND assessor_2_mark IS NOT NULL 
                 LIMIT 1";
    $res_lock = executePreparedStatement($sql_lock, [$student_name]);
    if ($res_lock instanceof mysqli_result && $res_lock->num_rows > 0) {
        $is_locked = true;
    }
}

if ($student_name && isset($_POST['save_assessors'])) {
    if ($is_locked) {
        $message = "Cannot change assessors — both assessors have already submitted marks for this student.";
        $msgType = "error";
    } else {
        $company_updated  = false;
        $assessors_updated = false;
        $errors = [];

        // --- Company: only act if admin picked/typed something ---
        $company_select       = trim($_POST['company_select'] ?? "");
        $company_new          = trim($_POST['company_new'] ?? "");
        $company_name_to_save = ($company_select === "__new__") ? $company_new : $company_select;

        if (!empty($company_name_to_save)) {
            $sql_check_intern = "SELECT internship_id FROM Internship WHERE student_id = ? LIMIT 1";
            $res_check_intern = executePreparedStatement($sql_check_intern, [$selected_student_id]);

            if ($res_check_intern instanceof mysqli_result && $res_check_intern->num_rows > 0) {
                $existing = $res_check_intern->fetch_assoc();
                $sql_update_intern = "UPDATE Internship SET company_name = ? WHERE internship_id = ?";
                executePreparedStatement($sql_update_intern, [$company_name_to_save, $existing['internship_id']]);
            } else {
                $sql_insert_intern = "INSERT INTO Internship (company_name, student_id) VALUES (?, ?)";
                executePreparedStatement($sql_insert_intern, [$company_name_to_save, $selected_student_id]);
            }
            $current_company  = $company_name_to_save;
            $company_updated  = true;
        } elseif ($company_select === "__new__" && empty($company_new)) {
            // Admin chose "Add new" but left the text field blank
            $errors[] = "Please enter a name for the new company.";
        }

        // --- Assessors: only act if admin checked any box ---
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

        // --- Build feedback message ---
        if (!empty($errors)) {
            $message = implode(" ", $errors);
            $msgType = "error";
        } elseif ($company_updated || $assessors_updated) {
            $parts = [];
            if ($company_updated)  $parts[] = "Company";
            if ($assessors_updated) $parts[] = "Assessors";
            $message = implode(" and ", $parts) . " updated successfully!";
            $msgType = "success";
        }
        // If nothing was touched, stay silent — no message shown
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assign Internship – UNM Portal</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body, input, select, button {
    font-family: 'Poppins', sans-serif;
}

body {
    min-height: 100vh;
    background-image: url('bg_image.png');
    background-size: cover;
    background-position: center 20%;
    background-repeat: no-repeat;
    background-attachment: fixed;
}

body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: rgba(10, 20, 60, 0.55);
    z-index: -1;
}

.top-header {
    width: 100%;
    padding: 15px 40px;
    background: rgba(15, 30, 70, 0.85);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
}
.header-left { display: flex; align-items: center; gap: 12px; }
.header-logo { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; }
.header-text { display: flex; flex-direction: column; }
.main-title { color: white; font-weight: 600; font-size: 16px; }

.page { display: flex; justify-content: center; margin-top: 50px; padding-bottom: 60px; }
.container { width: 560px; }

.section-title {
    margin-bottom: 15px;
    padding: 14px 20px;
    border-radius: 14px;
    background: rgba(25, 45, 95, 0.52);
    backdrop-filter: blur(22px);
    border: 1px solid rgba(255,255,255,0.13);
    color: white;
    font-weight: 600;
    font-size: 18px;
    text-align: center;
}

.card {
    background: rgba(25, 45, 95, 0.52);
    backdrop-filter: blur(22px);
    border-radius: 24px;
    padding: 32px;
    border: 1px solid rgba(255,255,255,0.13);
}

.toast {
    margin-bottom: 18px;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
}
.toast.success { background: rgba(80,200,120,0.2); color: #aaffcc; border: 1px solid rgba(80,200,120,0.3); }
.toast.error   { background: rgba(255,80,80,0.2);  color: #ffb3b3; border: 1px solid rgba(255,80,80,0.3); }

.form-group { margin-bottom: 18px; }
.form-group label {
    display: block;
    color: rgba(255,255,255,0.75);
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
}

.form-group select,
.form-group input[type="text"] {
    width: 100%;
    height: 46px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.18);
    padding: 0 14px;
    background: rgba(15, 30, 70, 0.65);
    color: white;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    outline: none;
    transition: border-color 0.2s;
}
.form-group select:focus,
.form-group input[type="text"]:focus { border-color: rgba(80,140,255,0.6); }

.form-group select {
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml;utf8,<svg fill='white' height='20' viewBox='0 0 24 24' width='20' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
    background-repeat: no-repeat;
    background-position: right 14px center;
    background-size: 18px;
    padding-right: 40px;
    cursor: pointer;
}
.form-group select option { background: #0f1e46; color: white; }

/* Two-column row for student + company */
.row-two {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 18px;
}

.divider {
    border: none;
    border-top: 1px solid rgba(255,255,255,0.1);
    margin: 22px 0;
}

.sub-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(255,255,255,0.5);
    margin-bottom: 10px;
}

/* Summary row: current company + current assessors side by side */
.summary-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 18px;
}
.summary-box { }
.summary-box .sub-label { margin-bottom: 8px; }

.assessor-chips { display: flex; flex-wrap: wrap; gap: 8px; }
.chip {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    background: rgba(80,200,120,0.2);
    color: #aaffcc;
    border: 1px solid rgba(80,200,120,0.25);
}
.chip-company {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    background: rgba(80,140,255,0.2);
    color: #b3ccff;
    border: 1px solid rgba(80,140,255,0.25);
    display: inline-block;
}
.chip-empty {
    font-size: 13px;
    color: rgba(255,255,255,0.35);
    font-style: italic;
}

.checkbox-list { display: flex; flex-direction: column; gap: 10px; }
.checkbox-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 10px;
    background: rgba(15,30,70,0.4);
    border: 1px solid rgba(255,255,255,0.08);
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s;
}
.checkbox-item:hover { background: rgba(80,140,255,0.12); border-color: rgba(80,140,255,0.25); }
.checkbox-item input[type="checkbox"] {
    width: 16px; height: 16px;
    accent-color: #508cff;
    cursor: pointer;
    flex-shrink: 0;
}
.checkbox-item span { color: white; font-size: 14px; }

.btn-submit {
    width: 100%;
    height: 48px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(50,80,150,0.55);
    color: white;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 22px;
    transition: background 0.2s;
}
.btn-submit:hover { background: rgba(70,105,180,0.7); }

.new-company-wrap {
    margin-top: 10px;
    display: none;
}
.new-company-wrap.visible { display: block; }

.lock-notice {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 16px 18px;
    border-radius: 12px;
    background: rgba(255, 180, 50, 0.12);
    border: 1px solid rgba(255, 180, 50, 0.3);
    margin-top: 20px;
}
.lock-text h4 {
    color: #ffd97a;
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 4px;
}
.lock-text p {
    color: rgba(255, 220, 130, 0.7);
    font-size: 12px;
    line-height: 1.5;
}
</style>
</head>
<body>

<div class="top-header">
    <div class="header-left">
        <img src="../logo_img.png" class="header-logo">
        <div class="header-text">
            <div class="main-title">UNM Internship Portal</div>
        </div>
    </div>
</div>

<div class="page">
<div class="container">

    <div class="section-title">Assign Internship</div>

    <div class="card">

        <?php if ($message): ?>
            <div class="toast <?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="post">

            <!-- Student + Company Selection (side by side) -->
            <div class="row-two">
                <div class="form-group" style="margin-bottom:0;">
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

                <div class="form-group" style="margin-bottom:0;">
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

                <hr class="divider">

                <!-- Current Summary -->
                <div class="summary-row">
                    <div class="summary-box">
                        <div class="sub-label">Current Company</div>
                        <?php if ($current_company): ?>
                            <span class="chip-company"><?= htmlspecialchars($current_company) ?></span>
                        <?php else: ?>
                            <span class="chip-empty">No company assigned yet</span>
                        <?php endif; ?>
                    </div>
                    <div class="summary-box">
                        <div class="sub-label">Current Assessors</div>
                        <div class="assessor-chips">
                            <?php if (count($current_assessors) > 0): ?>
                                <?php foreach ($current_assessors as $aname): ?>
                                    <span class="chip"><?= htmlspecialchars($aname) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="chip-empty">No assessors assigned yet</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($is_locked): ?>

                    <hr class="divider">
                    <div class="lock-notice">
                        <div class="lock-text">
                            <h4>Not allowed to Assign Assessors</h4>
                            <p>Both assessors have already submitted their marks for this student. The assignment can no longer be changed.</p>
                        </div>
                    </div>

                <?php else: ?>

                    <hr class="divider">

                    <!-- Assign Assessors -->
                    <div class="form-group">
                        <label>Assign Assessors <span style="font-weight:400;text-transform:none;letter-spacing:0;color:rgba(255,255,255,0.4)">(select exactly 2)</span></label>
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
</div>

<script>
function toggleNewCompany(sel) {
    const wrap = document.getElementById('new_company_wrap');
    const input = document.getElementById('company_new');
    if (sel.value === '__new__') {
        wrap.classList.add('visible');
        input.focus();
    } else {
        wrap.classList.remove('visible');
        input.value = '';
    }
}

// Init on load in case of POST-back with __new__ selected
window.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('company_select');
    if (sel && sel.value === '__new__') {
        document.getElementById('new_company_wrap').classList.add('visible');
    }
});

document.querySelectorAll('input[name="assessor_ids[]"]').forEach(cb => {
    cb.addEventListener('change', function () {
        const checked = document.querySelectorAll('input[name="assessor_ids[]"]:checked');
        if (checked.length > 2) {
            alert("You can select only 2 assessors.");
            this.checked = false;
        }
    });
});
</script>

</body>
</html>