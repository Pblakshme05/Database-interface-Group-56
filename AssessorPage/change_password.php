<?php
session_start();
include '../configdb.php';

if (!isset($_SESSION['assessor_id'])) {
    header("Location: ../login.php");
    exit();
}

$assessor_id   = $_SESSION['assessor_id'];
$assessor_name = $_SESSION['assessor_name'];
$error   = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current  = $_POST['current_password'];
    $new      = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    // Fetch current password hash
    $stmt = $conn->prepare("SELECT password FROM Assessor WHERE assessor_id = ?");
    $stmt->bind_param("i", $assessor_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row || !password_verify($current, $row['password'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new) < 8) {
        $error = "New password must be at least 8 characters.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $upd = $conn->prepare("UPDATE Assessor SET password = ? WHERE assessor_id = ?");
        $upd->bind_param("si", $hashed, $assessor_id);
        $upd->execute();
        $success = "Password changed successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Change Password</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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

  body {
    font-family: 'Poppins', sans-serif;
    background: var(--bg);
    color: var(--ink);
    min-height: 100vh;
  }

  /* ── Header ── */
  .top-header {
    width: 100%;
    padding: 0 2rem;
    height: 60px;
    background: var(--accent);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 10;
  }
  .header-left { display: flex; align-items: center; gap: 12px; }
  .header-logo { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; }
  .main-title { font-size: 15px; font-weight: 600; color: #fff; }
  .sub-title { font-size: 12px; opacity: 0.6; }
  .assessor-pill {
    font-size: 12px; font-weight: 500;
    background: var(--gold-light); color: var(--gold);
    border: 1px solid #e8d99a;
    padding: 4px 12px; border-radius: 20px;
  }

  /* ── Container ── */
  .container {
    max-width: 520px;
    margin: 3rem auto;
    padding: 0 20px 40px;
  }

  .back-link {
    font-size: 13px;
    color: var(--accent);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 1.2rem;
    font-weight: 500;
    padding: 6px 12px;
    border-radius: 8px;
    border: 1px solid var(--accent-light);
    background: var(--accent-light);
    transition: background 0.15s;
  }
  .back-link:hover { background: #d8d8f0; }

  h2 {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: var(--ink);
  }

  /* ── Card ── */
  .card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 2rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  }

  .card-icon {
    width: 52px; height: 52px;
    background: var(--accent-light);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    margin-bottom: 1.2rem;
  }

  .card-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 4px;
  }

  .card-sub {
    font-size: 13px;
    color: var(--ink-soft);
    margin-bottom: 1.8rem;
  }

  /* ── Form fields ── */
  .field { margin-bottom: 1.2rem; }

  .field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
    margin-bottom: 6px;
  }

  .input-wrap {
    position: relative;
  }

  .input-wrap input {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 10px 42px 10px 14px;
    font-size: 14px;
    font-family: 'Poppins', sans-serif;
    color: var(--ink);
    background: var(--bg);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  .input-wrap input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(13,31,60,0.08);
  }

  .toggle-eye {
    position: absolute;
    right: 12px; top: 50%;
    transform: translateY(-50%);
    background: none; border: none;
    cursor: pointer; font-size: 16px;
    color: var(--ink-soft);
    padding: 0; line-height: 1;
    transition: color 0.15s;
  }
  .toggle-eye:hover { color: var(--accent); }

  .match-label {
    font-size: 11px;
    margin-top: 4px;
  }

  hr { border: none; border-top: 1px solid var(--border); margin: 1.5rem 0; }

  /* ── Submit button ── */
  .btn-submit {
    width: 100%;
    background: var(--accent);
    color: #fff;
    border: none;
    padding: 12px;
    border-radius: 10px;
    font-size: 15px;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    margin-top: 0.5rem;
  }
  .btn-submit:hover { background: var(--accent-hover); }

  /* ── Alerts ── */
  .alert {
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 1.2rem;
    display: flex; align-items: center; gap: 8px;
  }
  .alert-success { background: var(--green-light); color: var(--green); }
  .alert-error   { background: #fee2e2; color: #991b1b; }


</style>
</head>
<body>

<div class="top-header">
  <div class="header-left">
    <img src="../logo_img.png" class="header-logo">
    <div>
      <div class="main-title">UNM Internship Portal</div>
      <div class="sub-title">Change Password</div>
    </div>
  </div>
  <span class="assessor-pill"><?= htmlspecialchars($assessor_name) ?></span>
</div>

<div class="container">
  <a href="../AssessorPage/AssessorPage.php" class="back-link">← Back to Dashboard</a>
  <h2>Change Password</h2>

  <?php if ($error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-icon">🔒</div>
    <div class="card-title">Update your password</div>
    <div class="card-sub">Enter your current password, then choose a new one.</div>

    <form method="POST" id="pwForm">

      <div class="field">
        <label for="current_password">Current Password</label>
        <div class="input-wrap">
          <input type="password" id="current_password" name="current_password" required placeholder="Enter current password">
          <button type="button" class="toggle-eye" onclick="toggleVis('current_password', this)">👁</button>
        </div>
      </div>

      <hr>

      <div class="field">
        <label for="new_password">New Password</label>
        <div class="input-wrap">
          <input type="password" id="new_password" name="new_password" required placeholder="Enter new password">
          <button type="button" class="toggle-eye" onclick="toggleVis('new_password', this)">👁</button>
        </div>
      </div>

      <div class="field">
        <label for="confirm_password">Confirm New Password</label>
        <div class="input-wrap">
          <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repeat new password" oninput="checkMatch()">
          <button type="button" class="toggle-eye" onclick="toggleVis('confirm_password', this)">👁</button>
        </div>
        <div class="strength-label" id="matchLabel"></div>
      </div>

      <button type="submit" class="btn-submit">Update Password</button>
    </form>
  </div>
</div>

<script>
function toggleVis(id, btn) {
  const input = document.getElementById(id);
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = '🙈';
  } else {
    input.type = 'password';
    btn.textContent = '👁';
  }
}

function checkMatch() {
  const np = document.getElementById('new_password').value;
  const cp = document.getElementById('confirm_password').value;
  const lbl = document.getElementById('matchLabel');
  if (!cp) { lbl.textContent = ''; return; }
  if (np === cp) {
    lbl.textContent = '✓ Passwords match';
    lbl.style.color = '#166534';
  } else {
    lbl.textContent = '✗ Passwords do not match';
    lbl.style.color = '#991b1b';
  }
}
</script>

</body>
</html>