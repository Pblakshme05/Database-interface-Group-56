<?php
session_start();

// destroy all session data
session_unset();
session_destroy();

// redirect to login page
header("Location: loginPage.php");
exit();
?>