<?php
include '../configdb.php';
include '../function.php';

$message = "";
$messageType = "";
$editMode = false;
$editRow = null;
$deleteSuccess = false;

// Handle POST requests for updating
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {

    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];

    if (empty($name) || empty($email)) {
        $message = "Name and email are required.";
        $messageType = "error";
    } else {
        $result = updateAssessor($id, $name, $email);
        if ($result) {
            $message = "Assessor updated successfully.";
            $messageType = "success";
            // Re-fetch updated row
            $sql = "SELECT * FROM Assessor WHERE assessor_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $editRow = $res->fetch_assoc();
            }
            $editMode = true;
        } else {
            $message = "Failed to update assessor: " . $conn->error;
            $messageType = "error";
            $editMode = true;
        }
    }

// Handle GET requests for deletion
} elseif (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    $sql = "DELETE FROM Assessor WHERE assessor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        $deleteSuccess = true;
        $message = "Assessor deleted successfully.";
        $messageType = "success";
    } else {
        $message = "Failed to delete assessor: " . $conn->error;
        $messageType = "error";
    }

// Handle GET for edit mode
} elseif (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "SELECT * FROM Assessor WHERE assessor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $editRow = $result->fetch_assoc();
        $editMode = true;
    } else {
        $message = "Assessor not found.";
        $messageType = "error";
    }
}

// Fetch all assessors for the table (skip if delete success screen)
$allAssessors = [];
if (!$deleteSuccess) {
    $sql = "SELECT * FROM Assessor";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $allAssessors[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $editMode ? 'Update Assessor' : 'Manage Assessors'; ?> – UNM Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body, input, button, select {
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
.sub-title { color: rgba(255,255,255,0.6); font-size: 12px; }

.page { display: flex; justify-content: center; margin-top: 60px; padding-bottom: 60px; }
.container { width: 900px; }

.page-header {
    margin-bottom: 15px;
    padding: 15px 20px;
    border-radius: 16px;
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
    padding: 25px;
    border: 1px solid rgba(255,255,255,0.13);
}

/* RETURN BUTTON */
.btn-return {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 20px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.22);
    background: rgba(255,255,255,0.08);
    color: white;
    font-size: 13px;
    font-weight: 500;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    text-decoration: none;
    margin-bottom: 14px;
    transition: background 0.2s, border-color 0.2s;
}
.btn-return:hover {
    background: rgba(255,255,255,0.16);
    border-color: rgba(255,255,255,0.4);
    color: white;
}

/* TABLE */
table { width: 100%; border-collapse: collapse; color: white; }
th {
    text-align: left;
    padding: 12px;
    font-size: 13px;
    color: rgba(255,255,255,0.7);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
tr:hover td { background: rgba(255,255,255,0.05); }

.actions { display: flex; gap: 8px; }
.btn-action {
    padding: 6px 12px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-size: 12px;
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    text-decoration: none;
    display: inline-block;
    transition: opacity 0.2s;
}
.btn-action:hover { opacity: 0.8; }
.edit   { background: rgba(80,140,255,0.4); color: white; }
.delete { background: rgba(255,80,80,0.4);  color: white; }

/* EDIT FORM */
.edit-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
}
.edit-title {
    color: white;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 18px;
}
.field { margin-bottom: 16px; }
.field label { display: block; color: rgba(255,255,255,0.85); font-size: 13px; margin-bottom: 6px; }
.field input {
    width: 100%;
    height: 45px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.18);
    padding: 0 12px;
    background: rgba(15,30,70,0.65);
    color: white;
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}
.field input:focus { border-color: rgba(100,150,255,0.5); }
.field input::placeholder { color: rgba(255,255,255,0.4); }
.form-row { display: flex; gap: 12px; align-items: flex-end; }
.form-row .field { flex: 1; margin-bottom: 0; }

.btn-submit {
    height: 45px;
    padding: 0 24px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(50,80,150,0.55);
    color: white;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: 0.2s;
    white-space: nowrap;
}
.btn-submit:hover { background: rgba(70,105,180,0.7); }

.btn-cancel {
    height: 45px;
    padding: 0 20px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.08);
    color: rgba(255,255,255,0.7);
    font-family: 'Poppins', sans-serif;
    font-weight: 500;
    font-size: 14px;
    cursor: pointer;
    transition: 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}
.btn-cancel:hover { background: rgba(255,255,255,0.13); }

/* TOAST */
.message {
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 18px;
    text-align: center;
}
.message.success { background: rgba(50,180,100,0.25); border: 1px solid rgba(50,200,100,0.3); color: #7fffa0; }
.message.error   { background: rgba(220,60,60,0.25);  border: 1px solid rgba(220,80,80,0.3);  color: #ffaaaa; }

.no-data { color: rgba(255,255,255,0.5); text-align: center; padding: 30px; font-size: 14px; }

/* DELETE SUCCESS SCREEN */
.result-box {
    text-align: center;
    padding: 40px 20px;
    color: white;
}
.result-box .big-icon { font-size: 52px; margin-bottom: 16px; }
.result-box h2 { font-size: 20px; font-weight: 600; margin-bottom: 8px; color: #aaffcc; }
.result-box p  { color: rgba(255,255,255,0.5); font-size: 13px; margin-bottom: 24px; }
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

<?php if ($deleteSuccess): ?>

    <!-- DELETE SUCCESS SCREEN -->
    <a href="http://localhost:8888/AdminPage/ManageAssessors.php" class="btn-return">&#8592; Return to Manage Assessors</a>
    <div class="page-header">Manage Assessors</div>
    <div class="card" style="max-width:480px; margin:0 auto;">
        <div class="result-box">
            <div class="big-icon">🗑️</div>
            <h2>Assessor Deleted Successfully</h2>
            <p>The assessor record has been removed from the system.</p>
            <a href="http://localhost:8888/AdminPage/ManageAssessors.php" class="btn-return" style="margin-bottom:0; justify-content:center;">&#8592; Return to Manage Assessors</a>
        </div>
    </div>

<?php elseif ($editMode && $editRow): ?>

    <!-- EDIT MODE -->
    <a href="http://localhost:8888/AdminPage/ManageAssessors.php" class="btn-return">&#8592; Return to Manage Assessors</a>
    <div class="page-header">Edit Assessor</div>
    <div class="card">

        <?php if (!empty($message)): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Full table still visible -->
        <?php if (count($allAssessors) > 0): ?>
        <table>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Actions</th>
            </tr>
            <?php foreach ($allAssessors as $assessor): ?>
            <tr>
                <td><?= htmlspecialchars($assessor['assessor_id']) ?></td>
                <td><?= htmlspecialchars($assessor['assessor_name']) ?></td>
                <td><?= htmlspecialchars($assessor['email']) ?></td>
                <td>
                    <div class="actions">
                        <a href="http://localhost:8888/AdminPage/ManageAssessors.php?id=<?= $assessor['assessor_id'] ?>" class="btn-action edit">Edit</a>
                        <a href="http://localhost:8888/AdminPage/ManageAssessors.php?delete=<?= $assessor['assessor_id'] ?>" class="btn-action delete" onclick="return confirm('Are you sure you want to delete this assessor?');">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="edit-section">
            <div class="edit-title">Update Assessor — <?= htmlspecialchars($editRow['assessor_name']) ?></div>
            <form method="post" action="http://localhost:8888/AdminPage/ManageAssessors.php">
                <input type="hidden" name="id" value="<?= htmlspecialchars($editRow['assessor_id']) ?>">
                <div class="form-row">
                    <div class="field">
                        <label>Assessor Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($editRow['assessor_name']) ?>" required>
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($editRow['email']) ?>" required>
                    </div>
                    <button type="submit" name="update" class="btn-submit">Update</button>
                    <a href="http://localhost:8888/AdminPage/ManageAssessors.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>

    </div>

<?php else: ?>

    <!-- MAIN LIST VIEW -->
    <div class="page-header">Manage Assessors</div>
    <div class="card">

        <?php if (!empty($message)): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (count($allAssessors) > 0): ?>
        <table>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Actions</th>
            </tr>
            <?php foreach ($allAssessors as $assessor): ?>
            <tr>
                <td><?= htmlspecialchars($assessor['assessor_id']) ?></td>
                <td><?= htmlspecialchars($assessor['assessor_name']) ?></td>
                <td><?= htmlspecialchars($assessor['email']) ?></td>
                <td>
                    <div class="actions">
                        <a href="http://localhost:8888/AdminPage/ManageAssessors.php?id=<?= $assessor['assessor_id'] ?>" class="btn-action edit">Edit</a>
                        <a href="http://localhost:8888/AdminPage/ManageAssessors.php?delete=<?= $assessor['assessor_id'] ?>" class="btn-action delete" onclick="return confirm('Are you sure you want to delete this assessor?');">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
            <div class="no-data">No assessors found.</div>
        <?php endif; ?>

    </div>

<?php endif; ?>

</div>
</div>
</body>
</html>