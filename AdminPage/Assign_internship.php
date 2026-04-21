<?php
include '../configdb.php';
include '../function.php';

$message = "";
$msgType = "";

$students_result = getStudents();
$assessors_result = getAssessors();

$selected_student_id = $_POST['student_id'] ?? "";
$student_name = "";
$current_assessors = [];

if ($selected_student_id) {
    $sql_student = "SELECT student_name FROM Student WHERE student_id = ?";
    $res_student = executePreparedStatement($sql_student, [$selected_student_id]);
    if ($res_student instanceof mysqli_result && $res_student->num_rows > 0) {
        $student_name = $res_student->fetch_assoc()['student_name'];
    }
}

if ($student_name) {
    $res_current = getStudentAssessors($student_name);
    if ($res_current instanceof mysqli_result) {
        while ($row = $res_current->fetch_assoc()) {
            $current_assessors[] = $row['assessor_name'];
        }
    }
}

if ($student_name && isset($_POST['save_assessors'])) {
    $assessor_ids = $_POST['assessor_ids'] ?? [];
    if (!assignAssessorsToStudent($student_name, $assessor_ids)) {
        $message = "Please select exactly 2 assessors.";
        $msgType = "error";
    } else {
        $message = "Assessors updated successfully!";
        $msgType = "success";
        $current_assessors = [];
        $res_current = getStudentAssessors($student_name);
        if ($res_current instanceof mysqli_result) {
            while ($row = $res_current->fetch_assoc()) {
                $current_assessors[] = $row['assessor_name'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Assign Internship – UNM Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
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

/* HEADER */
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
.sub-title { color: rgba(255,255,255,0.6); font-size: 12px; }

/* LAYOUT */
.page { display: flex; justify-content: center; margin-top: 50px; padding-bottom: 60px; }
.container { width: 520px; }

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

/* TOAST */
.toast {
    margin-bottom: 18px;
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
}
.toast.success { background: rgba(80,200,120,0.2); color: #aaffcc; border: 1px solid rgba(80,200,120,0.3); }
.toast.error   { background: rgba(255,80,80,0.2);  color: #ffb3b3; border: 1px solid rgba(255,80,80,0.3); }

/* FORM FIELDS */
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
.form-group input {
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
.form-group input:focus { border-color: rgba(80,140,255,0.6); }

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

/* DIVIDER */
.divider {
    border: none;
    border-top: 1px solid rgba(255,255,255,0.1);
    margin: 22px 0;
}

/* CURRENT ASSESSORS */
.sub-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(255,255,255,0.5);
    margin-bottom: 10px;
}

.assessor-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 4px; }
.chip {
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    background: rgba(80,200,120,0.2);
    color: #aaffcc;
    border: 1px solid rgba(80,200,120,0.25);
}
.chip-empty {
    font-size: 13px;
    color: rgba(255,255,255,0.35);
    font-style: italic;
}

/* CHECKBOX LIST */
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

/* SUBMIT BUTTON */
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
</style>
</head>
<body>

<div class="top-header">
    <div class="header-left">
        <img src="logo_img.png" class="header-logo">
        <div class="header-text">
            <div class="main-title">UNM Portal</div>
            <div class="sub-title">Administration Portal</div>
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

            <!-- Student Selection -->
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

            <?php if ($student_name): ?>

                <hr class="divider">

                <!-- Current Assessors -->
                <div class="sub-label">Current Assessors</div>
                <div class="assessor-chips" style="margin-bottom:18px;">
                    <?php if (count($current_assessors) > 0): ?>
                        <?php foreach ($current_assessors as $aname): ?>
                            <span class="chip"><?= htmlspecialchars($aname) ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="chip-empty">No assessors assigned yet</span>
                    <?php endif; ?>
                </div>

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

                <button type="submit" name="save_assessors" class="btn-submit">Assign / Update Assessors</button>

            <?php endif; ?>

        </form>
    </div>

</div>
</div>

<script>
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