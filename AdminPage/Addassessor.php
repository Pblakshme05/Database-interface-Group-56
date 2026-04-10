<?php
include '../configdb.php';
include '../function.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    }
    else {
        $result = createAssessor($name, $email, $password);

        if ($result) {
            $message = "Assessor added successfully.";
        } else {
            $message = "Failed to add assessor.";
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

<?php if (!empty($message)) { ?>
    <p><?php echo $message; ?></p>
<?php } ?>

<form method="post">
    <label>Assessor Name:</label>
    <input type="text" name="name" required><br><br>

    <label>Email:</label>
    <input type="email" name="email" required><br><br>

    <label>Password:</label>
    <input type="password" name="password" required><br><br>

    <button type="submit">Add Assessor</button>
</form>

</body>
</html>