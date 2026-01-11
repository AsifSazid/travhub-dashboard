<?php

$host = 'localhost';
<<<<<<< HEAD
$dbname = 'travhub_workflow';
$username = 'root';
$password = '';

// FOR SERVER
// $dbname = 'sazummec_travhub_dashboard';
// $username = 'sazummec_common_root';
// $password = 'C0ww0nR001';
=======
// $dbname = 'travhub_workflow';
// $username = 'root';
// $password = '';

// FOR SERVER
$dbname = 'travhub_dashboard';
$username = 'root';
$password = 'travhub2025';
>>>>>>> server

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
