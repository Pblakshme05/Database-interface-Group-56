<?php
session_start();

if (!isset($_SESSION['assessor_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Assessor Dashboard</title>

<style>
body {
    font-family: Arial;
    background-color: #f5f6fa;
    margin: 0;
}

/* HEADER */
.header {
    background: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #ddd;
}

.logout {
    text-decoration: none;
    color: red;
    font-weight: bold;
}

/* WELCOME CARD */
.welcome-card {
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    color: white;
    margin: 30px;
    padding: 30px;
    border-radius: 15px;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.2);
}

.welcome-card h1 {
    margin: 0;
    font-size: 28px;
}

.welcome-card p {
    margin-top: 10px;
    font-size: 18px;
}

.assessor-name {
    font-weight: bold;
    color: #ffe082;
}

/* CARDS GRID */
.container {
    padding: 30px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

/* CARD */
.card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0px 2px 6px rgba(0,0,0,0.1);
    transition: 0.2s;
}

.card:hover {
    transform: scale(1.05);
}

.card a {
    text-decoration: none;
    color: black;
    display: block;
}

.card h3 {
    margin: 0;
    margin-bottom: 10px;
}
</style>

</head>

<body>

<!-- HEADER -->
<div class="header">
    <h2>ASSESSOR DASHBOARD</h2>
    <a class="logout" href="logout.php">Logout</a>
</div>

<!-- WELCOME SECTION -->
<div class="welcome-card">
    <h1>Welcome to the Assessor Dashboard</h1>
    <p>Hello, <span class="assessor-name">
        <?php echo $_SESSION['assessor_name']; ?>
    </span> 👨‍🏫</p>
</div>

<!-- DASHBOARD CARDS -->
<div class="container">

    <div class="card">
        <a href="assigned_students.php">
            <h3>View Assigned Students</h3>
            <p>See all students assigned to you</p>
        </a>
    </div>

    <div class="card">
        <a href="enter_marks.php">
            <h3>Enter Student Marks</h3>
            <p>Assess and enter marks for students</p>
        </a>
    </div>

    <div class="card">
        <a href="update_marks.php">
            <h3>Update Marks</h3>
            <p>Edit previously entered marks</p>
        </a>
    </div>

    <div class="card">
        <a href="my_assessed_students.php">
            <h3>View My Assessed Students</h3>
            <p>View students you have assessed</p>
        </a>
    </div>

    <div class="card">
        <a href="view_final_results.php">
            <h3>View Final Results</h3>
            <p>Check complete assessment results</p>
        </a>
    </div>

</div>

</body>
</html>