<?php

session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: ../auth/login.php");
    exit();
}
// ... the rest of your index.php code
date_default_timezone_set('Asia/Dhaka');

?>