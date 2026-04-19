<?php
include '../configdb.php';
include '../function.php';

$message = "";
$messageType = "";

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
            header("Refresh:3; url=ManageAssessors.php");
            exit;
        } else {
            $message = "Failed to update assessor: " . $conn->error;
            $messageType = "error";
        }
    }

// Handle GET requests for deletion
} elseif (isset($_GET['delete'])) {
    $deleteId = $_GET['delete'];
    $sql = "DELETE FROM Assessor WHERE assessor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        header("Refresh:2; url=ManageAssessors.php");
        exit;
    } else {
        $message = "Failed to delete assessor: " . $conn->error;
        $messageType = "error";
    }
}

// Determine view mode
$editMode = false;
$editRow = null;

if (isset($_GET['id'])) {
    // Edit mode: fetch specific assessor
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

// Always fetch all assessors for the table
$allAssessors = [];
$sql = "SELECT * FROM Assessor";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allAssessors[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title><?php echo $editMode ? 'Update Assessor' : 'Manage Assessors'; ?></title>
<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
body {
    font-family: 'Poppins', sans-serif;
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
button {
    font-family: 'Poppins', sans-serif;
}
.top-header {
    width: 100%;
    padding: 15px 40px;
    background: rgba(15, 30, 70, 0.85);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 4px 12px rgba(0,0,0,0.25);
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
    object-fit: cover;
}
.header-text {
    display: flex;
    flex-direction: column;
}
.main-title {
    color: white;
    font-weight: 600;
    font-size: 16px;
}
.sub-title {
    color: rgba(255,255,255,0.6);
    font-size: 12px;
}
.page {
    display: flex;
    justify-content: center;
    margin-top: 60px;
    padding-bottom: 60px;
}
.container {
    width: 900px;
}
.page-header {
    margin-bottom: 15px;
    padding: 15px 20px;
    border-radius: 16px;
    background: rgba(25, 45, 95, 0.52);
    backdrop-filter: blur(22px);
    border: 1px solid rgba(255,255,255,0.13);
    color: white;
    font-weight: 600;
    text-align: center;
}
.card {
    background: rgba(25, 45, 95, 0.52);
    backdrop-filter: blur(22px);
    border-radius: 24px;
    padding: 25px;
    border: 1px solid rgba(255,255,255,0.13);
}

/* Table styles */
table {
    width: 100%;
    border-collapse: collapse;
    color: white;
}
th {
    text-align: left;
    padding: 12px;
    font-size: 13px;
    color: rgba(255,255,255,0.7);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
td {
    padding: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    font-size: 14px;
}
tr:hover td {
    background: rgba(255,255,255,0.05);
}
.actions {
    display: flex;
    gap: 8px;
}
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
.btn-action:hover {
    opacity: 0.8;
}
.edit {
    background: rgba(80, 140, 255, 0.4);
    color: white;
}
.delete {
    background: rgba(255, 80, 80, 0.4);
    color: white;
}

/* Edit form styles */
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
.field {
    margin-bottom: 16px;
}
.field label {
    display: block;
    color: rgba(255,255,255,0.85);
    font-size: 13px;
    margin-bottom: 6px;
}
.field input {
    width: 100%;
    height: 45px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.18);
    padding: 0 12px;
    background: rgba(15, 30, 70, 0.65);
    color: white;
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}
.field input:focus {
    border-color: rgba(100, 150, 255, 0.5);
}
.field input::placeholder {
    color: rgba(255,255,255,0.4);
}
.form-row {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}
.form-row .field {
    flex: 1;
    margin-bottom: 0;
}
.btn-submit {
    height: 45px;
    padding: 0 24px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(50, 80, 150, 0.55);
    color: white;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: 0.2s;
    white-space: nowrap;
}
.btn-submit:hover {
    background: rgba(70, 105, 180, 0.7);
}
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
.btn-cancel:hover {
    background: rgba(255,255,255,0.13);
}
.message {
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 18px;
    text-align: center;
}
.message.success {
    background: rgba(50, 180, 100, 0.25);
    border: 1px solid rgba(50, 200, 100, 0.3);
    color: #7fffa0;
}
.message.error {
    background: rgba(220, 60, 60, 0.25);
    border: 1px solid rgba(220, 80, 80, 0.3);
    color: #ffaaaa;
}
.no-data {
    color: rgba(255,255,255,0.5);
    text-align: center;
    padding: 30px;
    font-size: 14px;
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

    <div class="page-header">
        <h2>Manage Assessors</h2>
    </div>

    <div class="card">

        <?php if (!empty($message)) { ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php } ?>

        <!-- Assessors Table -->
        <?php if (count($allAssessors) > 0) { ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($allAssessors as $assessor) { ?>
            <tr>
                <td><?php echo htmlspecialchars($assessor['assessor_id']); ?></td>
                <td><?php echo htmlspecialchars($assessor['assessor_name']); ?></td>
                <td><?php echo htmlspecialchars($assessor['email']); ?></td>
                <td>
                    <div class="actions">
                        <a href="?id=<?php echo $assessor['assessor_id']; ?>" class="btn-action edit">Edit</a>
                        <a href="?delete=<?php echo $assessor['assessor_id']; ?>" class="btn-action delete" onclick="return confirm('Are you sure you want to delete this assessor?');">Delete</a>
                    </div>
                </td>
            </tr>
            <?php } ?>
        </table>
        <?php } else { ?>
            <div class="no-data">No assessors found.</div>
        <?php } ?>

        <!-- Edit Form (shown when an assessor is selected) -->
        <?php if ($editMode && $editRow) { ?>
        <div class="edit-section">
            <div class="edit-title">Update Assessor — <?php echo htmlspecialchars($editRow['assessor_name']); ?></div>
            <form method="post" action="">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editRow['assessor_id']); ?>">
                <div class="form-row">
                    <div class="field">
                        <label>Assessor Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($editRow['assessor_name']); ?>" required>
                    </div>
                    <div class="field">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($editRow['email']); ?>" required>
                    </div>
                    <button type="submit" name="update" class="btn-submit">Update</button>
                    <a href="ManageAssessors.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
        <?php } ?>

    </div>

</div>
</div>

</body>
</html>