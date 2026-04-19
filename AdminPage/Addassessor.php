<?php
include '../configdb.php';
include '../function.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required.";
        $messageType = "error";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $messageType = "error";
    }
    else {
        $result = createAssessor($name, $email, $password);

        if ($result) {
            $message = "Assessor added successfully.";
            $messageType = "success";
        } else {
            $message = "Failed to add assessor.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Assessor</title>


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
    line-height: 1;
}

.sub-title {
    color: rgba(255,255,255,0.6);
    font-size: 12px;
    margin-top: 2px;
}

.page {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-top: 60px;
}

.container {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.header {
    width: 420px;
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
    width: 420px;
    background: rgba(25, 45, 95, 0.52);
    backdrop-filter: blur(22px);
    border-radius: 24px;
    padding: 40px;
    border: 1px solid rgba(255,255,255,0.13);
}

.logo-wrap {
    display: flex;
    justify-content: center;
    margin-bottom: 15px;
}

.logo {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    object-fit: cover;
    border: 2px solid rgba(255,255,255,0.2);
}

.uni-name {
    text-align: center;
    color: white;
    font-weight: 700;
    font-size: 16px;
}

.uni-sub {
    text-align: center;
    color: rgba(255,255,255,0.6);
    font-size: 13px;
    margin-bottom: 20px;
}

.separator {
    height: 1px;
    background: rgba(255,255,255,0.15);
    margin: 20px 0;
}

.title {
    color: white;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 20px;
    text-align: left;
}

.field {
    margin-bottom: 18px;
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

.btn {
    width: 100%;
    height: 48px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(50, 80, 150, 0.55);
    color: white;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: 0.2s;
}

.btn:hover {
    background: rgba(70, 105, 180, 0.7);
}

.message {
    width: 100%;
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

    <div class="header">
        <h2>Add Assessor</h2>
    </div>

    <div class="card">


        <div class="title">Register a new assessor</div>

        <?php if (!empty($message)) { ?>
            <div class="message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php } ?>

        <form method="post">

            <div class="field">
                <label>Assessor Name</label>
                <input type="text" name="name" placeholder="Enter assessor name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>

            <div class="field">
                <label>Email</label>
                <input type="email" name="email" placeholder="Enter email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="field">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter password">
            </div>

            <button type="submit" class="btn">Add Assessor</button>

        </form>

    </div>

</div>
</div>

</body>
</html>