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
        header("Location: AssessorPage/AssessorPage.php");
        exit();
    }

    $error = "Invalid email or password!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login – Internship Result System</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Poppins', Arial, sans-serif;
      min-height: 100vh;
      background-image: url('bg_image.png');
      background-size: cover;
      background-position: center top;
      background-repeat: no-repeat;
      display: flex;
      align-items: center;
      justify-content: flex-start;
      padding-left: 80px;
    }

    /* Floating rounded glass card — NOT full height */
    .login-card {
      width: 440px;
      background: rgba(25, 45, 95, 0.52);
      backdrop-filter: blur(22px);
      -webkit-backdrop-filter: blur(22px);
      border: 1px solid rgba(255,255,255,0.13);
      border-radius: 24px;
      padding: 48px 44px 52px;
    }

    /* Logo */
    .logo-wrap {
      display: flex;
      justify-content: center;
      margin-bottom: 18px;
    }

    .logo-circle {
      width: 88px;
      height: 88px;
      border-radius: 20px;
      background: #1a3a7a;
      border: 2px solid rgba(255,255,255,0.22);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .logo-circle img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 18px;
    }

    .uni-name {
      text-align: center;
      font-size: 17px;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 5px;
    }

    .uni-sub {
      text-align: center;
      font-size: 13px;
      color: rgba(255,255,255,0.55);
      margin-bottom: 28px;
    }

    .separator {
      height: 1px;
      background: rgba(255,255,255,0.15);
      margin-bottom: 28px;
    }

    .form-title {
      font-size: 24px;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 5px;
    }

    .form-subtitle {
      font-size: 13px;
      color: rgba(255,255,255,0.55);
      margin-bottom: 28px;
    }

    /* Error */
    .error-msg {
      background: rgba(180, 30, 30, 0.35);
      border: 1px solid rgba(255,120,120,0.4);
      border-radius: 8px;
      padding: 10px 14px;
      font-size: 13px;
      color: #ffd0d0;
      margin-bottom: 16px;
    }

    /* Fields */
    .field { margin-bottom: 20px; }

    .field label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: rgba(255,255,255,0.85);
      margin-bottom: 8px;
    }

    .field input {
      width: 100%;
      height: 48px;
      border: 1px solid rgba(255,255,255,0.18);
      border-radius: 10px;
      padding: 0 16px;
      font-size: 14px;
      background: rgba(15, 30, 70, 0.65);
      color: #ffffff;
      outline: none;
      transition: border-color 0.15s, background 0.15s;
    }

    .field input::placeholder { color: rgba(255,255,255,0.30); }

    .field input:focus {
      border-color: rgba(255,255,255,0.5);
      background: rgba(20, 40, 90, 0.75);
    }

    /* Button */
    .btn-login {
      width: 100%;
      height: 50px;
      background: rgba(50, 80, 150, 0.55);
      color: #ffffff;
      border: 1px solid rgba(255,255,255,0.22);
      border-radius: 10px;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 4px;
      transition: background 0.15s;
      letter-spacing: 0.3px;
    }

    .btn-login:hover { background: rgba(70, 105, 180, 0.7); }

    @media (max-width: 700px) {
      body {
        justify-content: center;
        padding-left: 0;
        padding: 24px;
      }
      .login-card {
        width: 100%;
        padding: 36px 28px 40px;
      }
    }
  </style>
</head>
<body>

  <div class="login-card">

    <div class="logo-wrap">
      <div class="logo-circle">
        <img src="logo_img.png" alt="UoN Logo"/>
      </div>
    </div>

    <div class="uni-name">Internship Result System</div>
    <div class="uni-sub">University of Nottingham</div>
    <div class="separator"></div>

    <div class="form-title">Sign in</div>
    <div class="form-subtitle">Enter your Email & Password</div>

    <?php if ($error): ?>
      <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" placeholder="you@nottingham.edu.my" required/>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="••••••••" required/>
      </div>
      <button type="submit" class="btn-login">Sign in</button>
    </form>

  </div>

</body>
</html>