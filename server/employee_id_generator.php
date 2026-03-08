<?php
require 'db_connection.php'; // must return $pdo

function generateEmployeeId(int $department, string $joiningDate): string
{
    global $pdo;

    // Validate department
    if ($department < 1 || $department > 8) {
        throw new Exception('Invalid Department');
    }

    // Parse date
    $date  = new DateTime($joiningDate);
    $month = $date->format('m'); // MM
    $year  = $date->format('y'); // YY

    /*
        Prefix without CC
        Example: EMP-50226
    */
    $prefix = "EMP-{$department}{$month}{$year}";

    /*
        We ONLY care about department for CC
        So search: EMP-5%
    */
    $deptPrefix = "EMP-{$department}";

    // Find last CC for this department (ignore MMYY)
    $stmt = $pdo->prepare("
        SELECT sys_id
        FROM employees
        WHERE sys_id LIKE ?
        ORDER BY CAST(SUBSTRING(sys_id, -2) AS UNSIGNED) DESC
        LIMIT 1
    ");
    $stmt->execute([$deptPrefix . '%']);
    $lastEmployeeId = $stmt->fetchColumn();

    if ($lastEmployeeId) {
        $lastCode = (int) substr($lastEmployeeId, -2);
        $newCode = $lastCode + 1;
    } else {
        $newCode = 1;
    }

    // Always 2 digit CC
    $cc = str_pad($newCode, 2, '0', STR_PAD_LEFT);

    return "{$prefix}{$cc}";
}
