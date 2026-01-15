<?php

require '../../server/db_connection.php';

header('Content-Type: application/json'); // Tell the client this is JSON


try {
    $company = 'THR';
    $year    = date('y'); // 26

    // 🔹 Last sys_id of CURRENT YEAR only
    $stmt = $pdo->prepare("
        SELECT sys_id
        FROM invoices
        WHERE sys_id LIKE :yearPattern
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':yearPattern' => "%-{$year}-%"
    ]);

    $lastSysId = $stmt->fetchColumn();

    if ($lastSysId) {
        // THR-IN-26-00K999
        $parts  = explode('-', $lastSysId);
        $blockSerial = explode('K', $parts[3]);

        $block  = $blockSerial[0];    // 00K
        $serial = $blockSerial[1];    // 999


        if ((int)$serial >= 999) {
            $block  = str_pad((int)$block + 1, 2, '0', STR_PAD_LEFT);
            $serial = '001'; // always string with leading zeros
        } else {
            $serial = str_pad((int)$serial + 1, 3, '0', STR_PAD_LEFT);
        }
    } else {
        // 🔹 New year or empty table
        $block  = '00';
        $serial = '001';
    }
    
    $sys_id = "{$company}-IN-{$year}-{$block}K{$serial}";

    echo json_encode(['invoice_no' => $sys_id, 'success' => true]); // Send JSON to the client
} catch (Exception $e) {
    // Return error as JSON too
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
