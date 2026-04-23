<?php
session_start();
include '../configdb.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../loginpage.php");
    exit();
}

$student_count  = $conn->query("SELECT COUNT(*) FROM Student")->fetch_row()[0] ?? 0;
$assessor_count = $conn->query("SELECT COUNT(*) FROM Assessor")->fetch_row()[0] ?? 0;
$result_count   = $conn->query("SELECT COUNT(*) FROM final_result WHERE final_avg_mark IS NOT NULL")->fetch_row()[0] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<style>
  :root {
    --navy:      #0d1f3c;
    --navy-soft: #1e3560;
    --off-white: #f4f6fb;
    --muted:     #4a5f7a;
    --border:    #dde3ef;
    --gold:      #c9a84c;
    --card-bg:   #ffffff;
    --radius:    16px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Outfit', sans-serif;
    background: var(--off-white);
    color: var(--navy);
    min-height: 100vh;
  }

  .header {
    background: var(--navy);
    height: 68px;
    padding: 0 2.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 20px rgba(13,31,60,0.5);
  }

  .header-brand {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .header-brand img {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid rgba(255,255,255,0.15);
  }

  .brand-text {
    font-family: 'Playfair Display', serif;
    font-size: 23px; /* was 19px */
    font-weight: 700;
    color: #ffffff;
  }

  .brand-sub {
    font-size: 13px; /* was 11px */
    color: #a8bccc;
    font-weight: 400;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    display: block;
    margin-top: -2px;
  }

  .logout {
    display: flex;
    align-items: center;
    gap: 6px;
    font-family: 'Outfit', sans-serif;
    font-size: 16px; /* was 14px */
    font-weight: 600;
    color: #fca5a5;
    background: rgba(239,68,68,0.1);
    border: 1px solid rgba(239,68,68,0.2);
    padding: 7px 18px;
    border-radius: 10px;
    text-decoration: none;
    transition: background 0.15s;
    cursor: pointer;
  }
  .logout:hover { background: rgba(239,68,68,0.22); }

  .welcome-card {
    background: var(--navy);
    margin: 2rem 2.5rem 0;
    padding: 2.8rem 3rem;
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(13,31,60,0.22);
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
  }

  .welcome-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(255,255,255,0.045) 1px, transparent 1px);
    background-size: 22px 22px;
    pointer-events: none;
  }

  .welcome-card::after {
    content: '';
    position: absolute;
    right: -80px; top: -80px;
    width: 340px; height: 340px;
    border-radius: 50%;
    background: radial-gradient(ellipse, rgba(201,168,76,0.13) 0%, transparent 70%);
    pointer-events: none;
  }

  .welcome-left { position: relative; z-index: 1; }

  .welcome-eyebrow {
    font-size: 14px; /* was 12px */
    font-weight: 600;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 12px;
    display: block;
  }

  .welcome-card h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2.8rem; /* was 2.5rem */
    font-weight: 800;
    color: #ffffff;
    line-height: 1.15;
    margin: 0 0 12px 0;
  }

  .welcome-card p {
    font-size: 18px; /* was 15px */
    color: rgba(255,255,255,0.75);
    font-weight: 300;
    margin: 0;
  }

  .admin-name {
    color: var(--gold);
    font-weight: 700;
  }

  .hero-stats {
    display: flex;
    gap: 1.2rem;
    position: relative;
    z-index: 1;
  }

  .hero-stat {
    text-align: center;
    padding: 1.2rem 1.8rem;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 14px;
    min-width: 105px;
  }

  .hero-stat-num {
    font-family: 'Playfair Display', serif;
    font-size: 2.4rem; /* was 2.1rem */
    font-weight: 700;
    color: #ffffff;
    display: block;
    line-height: 1;
  }

  .hero-stat-label {
    font-size: 13px; /* was 11px */
    color: #a8bccc;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-top: 5px;
    display: block;
  }

  .container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    padding: 2rem 2.5rem 2.5rem;
  }

  .card {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.8rem 1.6rem 2.6rem;
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    position: relative;
    overflow: hidden;
    box-shadow: 0px 2px 6px rgba(0,0,0,0.06);
  }

  .card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--navy);
    opacity: 0;
    transition: opacity 0.2s;
    border-radius: var(--radius) var(--radius) 0 0;
  }

  .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 14px 36px rgba(13,31,60,0.13);
    border-color: #c2cde0;
  }

  .card:hover::before { opacity: 1; }

  .card a {
    text-decoration: none;
    color: black;
    display: block;
  }

  .card h3 {
    font-family: 'Playfair Display', serif;
    font-size: 20px; /* was 17px */
    font-weight: 700;
    color: var(--navy);
    margin: 0 0 6px 0;
  }

  .card p {
    font-size: 15px; /* was 13.5px */
    color: var(--muted);
    font-weight: 400;
    line-height: 1.55;
  }

  .card-arrow {
    position: absolute;
    bottom: 1.5rem;
    right: 1.5rem;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: var(--off-white);
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px; /* was 14px */
    color: var(--muted);
    transition: background 0.2s, color 0.2s, border-color 0.2s;
  }

  .card:hover .card-arrow {
    background: var(--navy);
    color: #ffffff;
    border-color: var(--navy);
  }

  @media (max-width: 1100px) {
    .container { grid-template-columns: repeat(3, 1fr); }
    .hero-stats { display: none; }
  }
  @media (max-width: 700px) {
    .header { padding: 0 1.2rem; }
    .welcome-card { margin: 1rem; padding: 1.8rem 1.5rem; }
    .welcome-card h1 { font-size: 1.9rem; } /* was 1.7rem */
    .container { grid-template-columns: repeat(2, 1fr); padding: 1rem 1rem 1.5rem; }
  }
  @media (max-width: 480px) {
    .container { grid-template-columns: 1fr; }
  }
</style>
</head>

<body>

<div class="header">
    <div class="header-brand">
        <img src="../logo_img.png" alt="Logo">
        <div>
            <span class="brand-text">UNM Internship Portal</span>
        </div>
    </div>
    <a class="logout" onclick="confirmLogout()">Logout</a>
</div>

<div class="welcome-card">
    <div class="welcome-left">
        <span class="welcome-eyebrow">Admin Dashboard</span>
        <h1>Welcome to the Admin Dashboard</h1>
        <p>Hello, <span class="admin-name"><?php echo $_SESSION['admin_name']; ?></span></p>
    </div>
    <div class="hero-stats">
        <div class="hero-stat">
            <span class="hero-stat-num"><?= $student_count ?></span>
            <span class="hero-stat-label">Students</span>
        </div>
        <div class="hero-stat">
            <span class="hero-stat-num"><?= $assessor_count ?></span>
            <span class="hero-stat-label">Assessors</span>
        </div>
        <div class="hero-stat">
            <span class="hero-stat-num"><?= $result_count ?></span>
            <span class="hero-stat-label">Results</span>
        </div>
    </div>
</div>

<div class="container">

    <div class="card">
        <a href="Addstudent.php">
            <h3>Add Student</h3>
            <p>Register a new student</p>
            <div class="card-arrow">→</div>
        </a>
    </div>

    <div class="card">
        <a href="Manage_students.php">
            <h3>Manage Students</h3>
            <p>Edit, delete, view students</p>
            <div class="card-arrow">→</div>
        </a>
    </div>

    <div class="card">
        <a href="Addassessor.php">
            <h3>Add Assessor</h3>
            <p>Register new assessor</p>
            <div class="card-arrow">→</div>
        </a>
    </div>

    <div class="card">
        <a href="Manage_Assessors.php">
            <h3>Manage Assessors</h3>
            <p>Edit or delete assessors</p>
            <div class="card-arrow">→</div>
        </a>
    </div>

    <div class="card">
        <a href="Assign_internship.php">
            <h3>Assign Internship</h3>
            <p>Assign student to assessor</p>
            <div class="card-arrow">→</div>
        </a>
    </div>

    <div class="card">
        <a href="view_internship.php">
            <h3>View All Internship</h3>
            <p>See all internship records</p>
            <div class="card-arrow">→</div>
        </a>
    </div>

    <div class="card">
        <a href="view_results.php">
            <h3>View Results</h3>
            <p>Check student results</p>
            <div class="card-arrow">→</div>
        </a>
    </div>

</div>

<script>
function confirmLogout() {
    if (confirm("Are you sure you want to logout?")) {
        window.location.href = "../loginpage.php";
    }
}
</script>

</body>
</html>