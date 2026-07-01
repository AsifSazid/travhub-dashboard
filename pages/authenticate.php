<?php

// include_once('./under-maintenance.php');
// die;

session_start();
if (!isset($_SESSION['user_email'])) {
    header("Location: ../auth/login.php");
    exit();
}

$authUser = $_SESSION['user_name'];
$authUserId = $_SESSION['user_id'];

$initialName = getInitials($authUser);

function getInitials($name) {
    // Name take space diye bhange array te neya hocche
    $words = explode(' ', trim($name));
    
    $initials = '';
    
    // Prothom word er 1st letter
    if (isset($words[0])) {
        $initials .= strtoupper($words[0][0]);
    }
    
    // Dwitiyo word er 1st letter
    if (isset($words[1])) {
        $initials .= strtoupper($words[1][0]);
    }
    
    return $initials;
}

// ... the rest of your index.php code
date_default_timezone_set('Asia/Dhaka');

?>