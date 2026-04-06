<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

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
    background: linear-gradient(135deg, #3311cbd1, #2575fc);
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

.admin-name {
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
    <h2>ADMIN DASHBOARD</h2>
    <a class="logout" href="logout.php">Logout</a>
</div>

<!-- WELCOME SECTION -->
<div class="welcome-card">
    <h1>Welcome to the Admin Dashboard</h1>
    <p>Hello, <span class="admin-name"><?php echo $_SESSION['admin_name']; ?></span> 👋</p>
</div>

<!-- DASHBOARD CARDS -->
<div class="container">

    <div class="card">
        <a href="add_student.php">
            <h3>Add Student</h3>
            <p>Register a new student</p>
        </a>
    </div>

    <div class="card">
        <a href="manage_students.php">
            <h3>Manage Students</h3>
            <p>Edit, delete, view students</p>
        </a>
    </div>

    <div class="card">
        <a href="add_assessor.php">
            <h3>Add Assessor</h3>
            <p>Register new assessor</p>
        </a>
    </div>

    <div class="card">
        <a href="manage_assessors.php">
            <h3>Manage Assessors</h3>
            <p>Edit or delete assessors</p>
        </a>
    </div>

    <div class="card">
        <a href="assign_internship.php">
            <h3>Assign Internship</h3>
            <p>Assign student to assessor</p>
        </a>
    </div>

    <div class="card">
        <a href="view_internships.php">
            <h3>View All Internships</h3>
            <p>See all internship records</p>
        </a>
    </div>

    <div class="card">
        <a href="view_results.php">
            <h3>View Results</h3>
            <p>Check student results</p>
        </a>
    </div>

    <div class="card">
        <a href="search_student.php">
            <h3>Search Student</h3>
            <p>Find student records</p>
        </a>
    </div>

</div>

</body>
</html>