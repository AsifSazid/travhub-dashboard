<?php
require 'db_connection.php'; // must return $pdo

function generateInvestorId(string $investingDate): string
{
    global $pdo;

    // Parse date
    $date  = new DateTime($investingDate);
    $month = $date->format('m'); // MM
    $year  = $date->format('y'); // YY

    /*
        Prefix without CC
        Example: EMP-50226
    */
    $prefix = "IVS-{$month}{$year}T";

    /*
        We ONLY care about department for CC
        So search: EMP-5%
    */

    // Find last CC for this department (ignore MMYY)
    $stmt = $pdo->prepare("
        SELECT sys_id
        FROM investors
        WHERE sys_id LIKE ?
        ORDER BY CAST(SUBSTRING(sys_id, -2) AS UNSIGNED) DESC
        LIMIT 1
    ");
    $stmt->execute([$prefix . '%']);
    $lastInvestorId = $stmt->fetchColumn();

    if ($lastInvestorId) {
        $lastCode = (int) substr($lastInvestorId, -2);
        $newCode = $lastCode + 1;
    } else {
        $newCode = 1;
    }

    // Always 2 digit CC
    $cc = str_pad($newCode, 2, '0', STR_PAD_LEFT);

    return "{$prefix}{$cc}";
}
