<?php

session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: ../auth/login.php");
    exit();
}

$authUser = $_SESSION['user_name'];
$authUserId = $_SESSION['user_id'];

// ... the rest of your index.php code
date_default_timezone_set('Asia/Dhaka');

?>