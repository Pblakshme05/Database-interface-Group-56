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
    $new      = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    if (strlen($new) < 1) {
        $error = "Please enter a new password.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        $upd = $conn->prepare("UPDATE Assessor SET password = ? WHERE assessor_id = ?");
        $upd->bind_param("si", $new, $assessor_id);
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

  .card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 2rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
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

  .field { margin-bottom: 1.2rem; }

  .field label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--ink);
    margin-bottom: 6px;
  }

  .input-wrap { position: relative; }

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
    cursor: pointer;
    color: var(--ink-soft);
    padding: 0; line-height: 0;
    display: flex; align-items: center;
    transition: color 0.15s;
  }
  .toggle-eye:hover { color: var(--accent); }
  .toggle-eye svg { width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

  hr { border: none; border-top: 1px solid var(--border); margin: 1.5rem 0; }

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
    <div class="card-title">Update your password</div>
    <div class="card-sub">Choose a new password for your account.</div>

    <form method="POST" id="pwForm">

      <div class="field">
        <label for="new_password">New Password</label>
        <div class="input-wrap">
          <input type="password" id="new_password" name="new_password" required placeholder="Enter new password">
          <button type="button" class="toggle-eye" onclick="toggleVis('new_password', this)" aria-label="Toggle visibility">
            <svg viewBox="0 0 24 24" class="icon-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg viewBox="0 0 24 24" class="icon-eye-off" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
      </div>

      <div class="field">
        <label for="confirm_password">Confirm New Password</label>
        <div class="input-wrap">
          <input type="password" id="confirm_password" name="confirm_password" required placeholder="Repeat new password" oninput="checkMatch()">
          <button type="button" class="toggle-eye" onclick="toggleVis('confirm_password', this)" aria-label="Toggle visibility">
            <svg viewBox="0 0 24 24" class="icon-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg viewBox="0 0 24 24" class="icon-eye-off" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
          </button>
        </div>
        <div style="font-size:11px;margin-top:4px" id="matchLabel"></div>
      </div>

      <button type="submit" class="btn-submit">Update Password</button>
    </form>
  </div>
</div>

<script>
function toggleVis(id, btn) {
  const input = document.getElementById(id);
  const eyeOn  = btn.querySelector('.icon-eye');
  const eyeOff = btn.querySelector('.icon-eye-off');
  if (input.type === 'password') {
    input.type = 'text';
    eyeOn.style.display  = 'none';
    eyeOff.style.display = '';
  } else {
    input.type = 'password';
    eyeOn.style.display  = '';
    eyeOff.style.display = 'none';
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