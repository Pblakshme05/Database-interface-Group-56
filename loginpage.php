<?php
session_start();
include 'configdb.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Admin Login Check
    $sql = "SELECT * FROM Admin WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {

        $row = $result->fetch_assoc();

        $_SESSION['admin_id'] = $row['admin_id'];
        $_SESSION['admin_name'] = $row['admin_name'];

        header("Location: AdminPage/Adminpage.php");
        exit();
    }

    //Assessor Login Check
    $sql2 = "SELECT * FROM Assessor WHERE email='$email' AND password='$password'";
    $result2 = $conn->query($sql2);

    if ($result2 && $result2->num_rows == 1) {

        $row = $result2->fetch_assoc();

        $_SESSION['assessor_id'] = $row['assessor_id'];
        $_SESSION['assessor_name'] = $row['assessor_name'];

        header("Location: Assessor/AssessorPage.php");
        exit();
    }

    $error = "Invalid email or password!";
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>
</head>
<body>

<h2>Internship Result System</h2>

<form method="POST">
    Email: <input type="email" name="email" required><br><br>
    Password: <input type="password" name="password" required><br><br>
    <button type="submit">Login</button>
</form>

<p style="color:red;"><?php echo $error; ?></p>

</body>
</html>