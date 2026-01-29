<?php
require 'db_connection.php'; // must return $pdo

function generateDirectorId(): string
{
    global $pdo;

    /*
        Prefix without CC
        Example: DIR-50226
    */
    $prefix = "DIR-31J26";

    // Find last CC for this department (ignore MMYY)
    $stmt = $pdo->prepare("
        SELECT sys_id
        FROM directors
        WHERE sys_id LIKE ?
        ORDER BY CAST(SUBSTRING(sys_id, -2) AS UNSIGNED) DESC
        LIMIT 1
    ");
    $stmt->execute([$prefix . '%']);
    $lastDirectorId = $stmt->fetchColumn();

    if ($lastDirectorId) {
        $lastCode = (int) substr($lastDirectorId, -2);
        $newCode = $lastCode + 1;
    } else {
        $newCode = 1;
    }

    // Always 2 digit CC
    $cc = str_pad($newCode, 2, '0', STR_PAD_LEFT);

    return "{$prefix}{$cc}";
}
