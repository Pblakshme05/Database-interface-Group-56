<?php
include 'configdb.php';
include 'function.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        echo "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
    } else {
        $result = createAssessor($name, $email, $password);

        if ($result) {
            echo "Assessor added successfully.";
        } else {
            echo "Failed to add assessor.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Assessor</title>
</head>
<body>

<h2>Add Assessor</h2>

<form method="post" action="">
    <label for="name">Assessor Name:</label>
    <input type="text" name="name" id="name" required><br><br>

    <label for="email">Email:</label>
    <input type="email" name="email" id="email" required><br><br>

    <label for="password">Password:</label>
    <input type="password" name="password" id="password" required><br><br>

    <input type="submit" value="Add Assessor">
</form>

</body>
</html>